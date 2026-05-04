<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle DeclarationTva (Phase 6 Comptabilité)
 * ====================================================================
 *
 * Calcule, archive et liste les déclarations TVA (CA3 mensuelle/trimestrielle
 * ou CA12 annuelle) à partir des écritures de `ecritures_comptables`.
 *
 * Le calcul s'appuie sur Ecriture::getDetailTva() qui fournit la
 * ventilation par taux. Le résultat peut être archivé en BDD via
 * `archive()` puis transmis au SIE — passage statut 'brouillon' → 'declaree'.
 *
 * Mapping vers le CERFA n°3310-CA3 :
 *   ca_ht_20  → ligne 01 (ventes/PS imposables 20%)
 *   ca_ht_10  → ligne 02 (10%)
 *   ca_ht_55  → ligne 03 (5,5%)
 *   ca_ht_21  → ligne 04 (2,1%)
 *   ca_ht_exonere → ligne 05 (opérations non imposables)
 *   tva_collectee_20 → ligne 08
 *   tva_collectee_10 → ligne 09
 *   tva_collectee_55 → ligne 9B
 *   tva_collectee_total → ligne 16
 *   tva_deductible_biens_services → ligne 19
 *   tva_deductible_immobilisations → ligne 20
 *   tva_deductible_total → ligne 22
 *   credit_tva_anterieur → ligne 25
 *   tva_a_payer → ligne 28
 *   credit_a_reporter → ligne 32
 */

class DeclarationTva extends Model {

    public const REGIMES = [
        'CA3_mensuel'     => 'CA3 mensuel (régime réel normal)',
        'CA3_trimestriel' => 'CA3 trimestriel (régime réel normal)',
        'CA12_annuel'     => 'CA12 annuel (régime simplifié)',
    ];

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'declaree'  => 'Déclarée',
        'annulee'   => 'Annulée',
    ];

    /**
     * Calcule un brouillon de déclaration TVA pour une résidence et une période.
     * Ne persiste rien — usage : preview avant archivage.
     *
     * @return array Structure prête pour la vue + la création BDD
     */
    public function calculer(int $residenceId, string $regime, string $periodeDebut, string $periodeFin, float $creditAnterieur = 0.0): array {
        if (!array_key_exists($regime, self::REGIMES)) {
            throw new InvalidArgumentException("Régime TVA invalide : $regime");
        }
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeDebut) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $periodeFin)) {
            throw new InvalidArgumentException("Dates de période invalides (YYYY-MM-DD attendu)");
        }
        if ($periodeFin < $periodeDebut) {
            throw new InvalidArgumentException("La date de fin doit être postérieure à la date de début");
        }

        $eModel = new Ecriture();
        $detail = $eModel->getDetailTva([$residenceId], $periodeDebut, $periodeFin);

        // Application du crédit antérieur sur la TVA due
        $tvaDue           = $detail['tva_collectee']['total'];
        $tvaDeductible    = $detail['tva_deductible']['total'];
        $soldeBrut        = round($tvaDue - $tvaDeductible, 2);
        $soldeApresCredit = round($soldeBrut - $creditAnterieur, 2);

        $tvaAPayer       = $soldeApresCredit > 0 ? $soldeApresCredit : 0.0;
        $creditAReporter = $soldeApresCredit < 0 ? abs($soldeApresCredit) : 0.0;

        return [
            'residence_id'                  => $residenceId,
            'regime'                        => $regime,
            'periode_debut'                 => $periodeDebut,
            'periode_fin'                   => $periodeFin,

            'ca_ht_20'                      => $detail['ca_ht']['20'],
            'ca_ht_10'                      => $detail['ca_ht']['10'],
            'ca_ht_55'                      => $detail['ca_ht']['5.5'],
            'ca_ht_21'                      => $detail['ca_ht']['2.1'],
            'ca_ht_exonere'                 => $detail['ca_ht']['exonere'],

            'tva_collectee_20'              => $detail['tva_collectee']['20'],
            'tva_collectee_10'              => $detail['tva_collectee']['10'],
            'tva_collectee_55'              => $detail['tva_collectee']['5.5'],
            'tva_collectee_21'              => $detail['tva_collectee']['2.1'],
            'tva_collectee_total'           => $detail['tva_collectee']['total'],

            'tva_deductible_biens_services' => $detail['tva_deductible']['biens_services'],
            'tva_deductible_immobilisations'=> $detail['tva_deductible']['immobilisations'],
            'tva_deductible_total'          => $detail['tva_deductible']['total'],

            'credit_tva_anterieur'          => $creditAnterieur,
            'tva_a_payer'                   => $tvaAPayer,
            'credit_a_reporter'             => $creditAReporter,

            'nb_ecritures'                  => $detail['nb_ecritures'],
        ];
    }

    /**
     * Archive un brouillon calculé. Statut initial = 'brouillon'.
     * Refus si une déclaration existe déjà pour le même triplet (résidence, régime, période).
     */
    public function archive(array $calcul, ?int $userId = null, ?string $notes = null): int {
        // Vérif unicité (la contrainte SQL fera office de garde-fou aussi)
        $stmt = $this->db->prepare("SELECT id FROM declarations_tva
                                    WHERE residence_id = ? AND regime = ? AND periode_debut = ? AND periode_fin = ?");
        $stmt->execute([$calcul['residence_id'], $calcul['regime'], $calcul['periode_debut'], $calcul['periode_fin']]);
        if ($stmt->fetch()) {
            throw new RuntimeException("Une déclaration existe déjà pour cette résidence et cette période. Supprimer ou annuler la précédente avant.");
        }

        $sql = "INSERT INTO declarations_tva (
                    residence_id, regime, periode_debut, periode_fin,
                    ca_ht_20, ca_ht_10, ca_ht_55, ca_ht_21, ca_ht_exonere,
                    tva_collectee_20, tva_collectee_10, tva_collectee_55, tva_collectee_21, tva_collectee_total,
                    tva_deductible_biens_services, tva_deductible_immobilisations, tva_deductible_total,
                    credit_tva_anterieur, tva_a_payer, credit_a_reporter,
                    statut, notes, created_by
                ) VALUES (
                    ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?, ?, ?,
                    ?, ?, ?,
                    ?, ?, ?,
                    'brouillon', ?, ?
                )";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $calcul['residence_id'], $calcul['regime'], $calcul['periode_debut'], $calcul['periode_fin'],
            $calcul['ca_ht_20'], $calcul['ca_ht_10'], $calcul['ca_ht_55'], $calcul['ca_ht_21'], $calcul['ca_ht_exonere'],
            $calcul['tva_collectee_20'], $calcul['tva_collectee_10'], $calcul['tva_collectee_55'],
            $calcul['tva_collectee_21'], $calcul['tva_collectee_total'],
            $calcul['tva_deductible_biens_services'], $calcul['tva_deductible_immobilisations'], $calcul['tva_deductible_total'],
            $calcul['credit_tva_anterieur'], $calcul['tva_a_payer'], $calcul['credit_a_reporter'],
            $notes,
            $userId,
        ]);

        $newId = (int)$this->db->lastInsertId();
        Logger::audit('tva_archive_brouillon', 'declarations_tva', $newId, [
            'residence_id' => $calcul['residence_id'],
            'regime'       => $calcul['regime'],
            'periode'      => $calcul['periode_debut'] . ' → ' . $calcul['periode_fin'],
            'tva_a_payer'  => (float)$calcul['tva_a_payer'],
            'credit_a_reporter' => (float)$calcul['credit_a_reporter'],
        ], $userId);
        return $newId;
    }

    /**
     * Marque une déclaration comme déclarée (transmise au SIE).
     */
    public function markAsDeclaree(int $id, int $userId): bool {
        $stmt = $this->db->prepare("UPDATE declarations_tva
                                    SET statut = 'declaree', declared_at = NOW(), declared_by = ?
                                    WHERE id = ? AND statut = 'brouillon'");
        $stmt->execute([$userId, $id]);
        $changed = $stmt->rowCount() > 0;
        if ($changed) {
            Logger::audit('tva_declaree', 'declarations_tva', $id, [
                'note' => 'Transmise au SIE — figée légalement',
            ], $userId);
        }
        return $changed;
    }

    /**
     * Annule une déclaration (réservé aux brouillons et déclarations transmises).
     */
    public function annuler(int $id): bool {
        $stmt = $this->db->prepare("UPDATE declarations_tva SET statut = 'annulee' WHERE id = ? AND statut != 'annulee'");
        $stmt->execute([$id]);
        $changed = $stmt->rowCount() > 0;
        if ($changed) {
            Logger::audit('tva_annulee', 'declarations_tva', $id, []);
        }
        return $changed;
    }

    /**
     * Suppression réservée aux brouillons.
     */
    public function deleteBrouillon(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM declarations_tva WHERE id = ? AND statut = 'brouillon'");
        $stmt->execute([$id]);
        $deleted = $stmt->rowCount() > 0;
        if ($deleted) {
            Logger::audit('tva_delete_brouillon', 'declarations_tva', $id, []);
        }
        return $deleted;
    }

    /**
     * Trouve une déclaration par ID.
     */
    public function findById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT d.*, c.nom AS residence_nom,
                                           u1.username AS created_by_username,
                                           u2.username AS declared_by_username
                                    FROM declarations_tva d
                                    LEFT JOIN coproprietees c ON c.id = d.residence_id
                                    LEFT JOIN users u1 ON u1.id = d.created_by
                                    LEFT JOIN users u2 ON u2.id = d.declared_by
                                    WHERE d.id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Liste filtrée pour l'index admin.
     */
    public function listFiltered(array $residenceIds, ?int $annee = null, ?string $statut = null, ?string $regime = null): array {
        if (empty($residenceIds)) return [];

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $sql = "SELECT d.*, c.nom AS residence_nom
                FROM declarations_tva d
                LEFT JOIN coproprietees c ON c.id = d.residence_id
                WHERE d.residence_id IN ($resPh)";
        $params = array_map('intval', $residenceIds);

        if ($annee) { $sql .= " AND YEAR(d.periode_debut) = ?"; $params[] = (int)$annee; }
        if ($statut && array_key_exists($statut, self::STATUTS)) { $sql .= " AND d.statut = ?"; $params[] = $statut; }
        if ($regime && array_key_exists($regime, self::REGIMES)) { $sql .= " AND d.regime = ?"; $params[] = $regime; }

        $sql .= " ORDER BY d.periode_debut DESC, d.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Récupère le crédit reporté de la déclaration précédente non annulée
     * pour une résidence et un régime donnés.
     */
    public function getCreditAnterieur(int $residenceId, string $regime, string $periodeDebut): float {
        $stmt = $this->db->prepare("SELECT credit_a_reporter FROM declarations_tva
                                    WHERE residence_id = ? AND regime = ? AND periode_fin < ? AND statut != 'annulee'
                                    ORDER BY periode_fin DESC LIMIT 1");
        $stmt->execute([$residenceId, $regime, $periodeDebut]);
        return (float)($stmt->fetchColumn() ?: 0.0);
    }

    /**
     * Calcule les bornes d'une période standard à partir d'un type + référence.
     *
     * @param string $regime  CA3_mensuel | CA3_trimestriel | CA12_annuel
     * @param int    $annee   ex 2026
     * @param int    $index   1-12 si mensuel, 1-4 si trimestriel, ignoré sinon
     * @return array  ['debut' => 'YYYY-MM-DD', 'fin' => 'YYYY-MM-DD', 'libelle' => 'Mai 2026']
     */
    public static function bornesPeriode(string $regime, int $annee, int $index = 1): array {
        switch ($regime) {
            case 'CA3_mensuel':
                $mois = max(1, min(12, $index));
                $debut = sprintf('%d-%02d-01', $annee, $mois);
                $fin   = date('Y-m-t', strtotime($debut));
                $moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                return ['debut' => $debut, 'fin' => $fin, 'libelle' => $moisLabels[$mois - 1] . ' ' . $annee];

            case 'CA3_trimestriel':
                $trim     = max(1, min(4, $index));
                $moisDeb  = ($trim - 1) * 3 + 1;
                $debut    = sprintf('%d-%02d-01', $annee, $moisDeb);
                $fin      = date('Y-m-t', strtotime(sprintf('%d-%02d-01', $annee, $moisDeb + 2)));
                $libTrim  = ['T1', 'T2', 'T3', 'T4'][$trim - 1];
                return ['debut' => $debut, 'fin' => $fin, 'libelle' => $libTrim . ' ' . $annee];

            case 'CA12_annuel':
            default:
                return [
                    'debut'   => sprintf('%d-01-01', $annee),
                    'fin'     => sprintf('%d-12-31', $annee),
                    'libelle' => 'Année ' . $annee,
                ];
        }
    }
}
