<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Quittance Résident (Phase 13)
 * ====================================================================
 *
 * Snapshot mensuel par occupation : 1 quittance = 1 mois × 1 occupation.
 *
 * Workflow :
 *   emise → payee | partiellement_payee | impayee | annulee
 *
 * La quittance fige les montants au moment de l'émission (loyer + charges
 * + services - APL - APA) — modifications ultérieures du loyer dans
 * `occupations_residents` n'affectent pas les quittances déjà émises.
 *
 * ⚠️ PILOTE VITRINE : le PDF est une vue HTML imprimable avec watermark
 * "PILOTE — DOCUMENT NON CONTRACTUEL". Pour un PDF officiel, valider
 * avec un cabinet juridique avant émission.
 */

class QuittanceResident extends Model {

    public const STATUTS = [
        'emise'                 => 'Émise',
        'partiellement_payee'   => 'Partiellement payée',
        'payee'                 => 'Payée',
        'impayee'               => 'Impayée',
        'annulee'               => 'Annulée',
    ];

    /** Délai en jours après le jour de prélèvement avant de basculer en 'impayee'. */
    public const DELAI_IMPAYE_JOURS = 10;

    /**
     * Génère les quittances d'un mois pour toutes les occupations actives
     * d'une ou plusieurs résidences. Évite les doublons (UNIQUE occupation+période).
     *
     * @return array  ['created' => N, 'skipped' => N, 'errors' => [...]]
     */
    public function genererPourMois(array $residenceIds, int $annee, int $mois, ?int $userId = null): array {
        $result = ['created' => 0, 'skipped' => 0, 'errors' => []];
        if (empty($residenceIds)) return $result;

        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $premierJourMois = sprintf('%d-%02d-01', $annee, $mois);
        $dernierJourMois = date('Y-m-t', strtotime($premierJourMois));

        // Toutes les occupations actives sur le mois (entrée <= fin et (sortie NULL ou sortie >= début))
        $sql = "SELECT o.id, o.lot_id, o.resident_id, o.loyer_mensuel_resident, o.charges_mensuelles_resident,
                       o.montant_services_sup, o.montant_apl, o.montant_apa,
                       l.copropriete_id AS residence_id
                FROM occupations_residents o
                JOIN lots l ON l.id = o.lot_id
                WHERE l.copropriete_id IN ($resPh)
                  AND o.date_entree <= ?
                  AND (o.date_sortie IS NULL OR o.date_sortie >= ?)";
        $params = array_merge(array_map('intval', $residenceIds), [$dernierJourMois, $premierJourMois]);

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $occupations = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $result['errors'][] = 'Erreur lecture occupations : ' . $e->getMessage();
            return $result;
        }

        foreach ($occupations as $occ) {
            try {
                // Saute si quittance déjà émise pour cette occupation × période
                $stmt = $this->db->prepare(
                    "SELECT id FROM quittances_residents WHERE occupation_id = ? AND periode_annee = ? AND periode_mois = ?"
                );
                $stmt->execute([$occ['id'], $annee, $mois]);
                if ($stmt->fetch()) {
                    $result['skipped']++;
                    continue;
                }

                $this->genererPourOccupation((int)$occ['id'], $annee, $mois, $userId, $occ);
                $result['created']++;
            } catch (Throwable $e) {
                $result['errors'][] = "Occupation #{$occ['id']} : " . $e->getMessage();
            }
        }

        return $result;
    }

    /**
     * Génère une quittance pour une occupation × mois donné.
     * Si $occData fourni (depuis genererPourMois), évite une requête supplémentaire.
     *
     * @return int  ID de la quittance créée
     * @throws RuntimeException
     */
    public function genererPourOccupation(int $occupationId, int $annee, int $mois, ?int $userId = null, ?array $occData = null): int {
        if (!$occData) {
            $stmt = $this->db->prepare(
                "SELECT o.lot_id, o.resident_id, o.loyer_mensuel_resident, o.charges_mensuelles_resident,
                        o.montant_services_sup, o.montant_apl, o.montant_apa, l.copropriete_id AS residence_id
                 FROM occupations_residents o
                 JOIN lots l ON l.id = o.lot_id
                 WHERE o.id = ?"
            );
            $stmt->execute([$occupationId]);
            $occData = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$occData) {
                throw new RuntimeException("Occupation #$occupationId introuvable.");
            }
        }

        // Refuse si quittance existe déjà
        $stmt = $this->db->prepare(
            "SELECT id FROM quittances_residents WHERE occupation_id = ? AND periode_annee = ? AND periode_mois = ?"
        );
        $stmt->execute([$occupationId, $annee, $mois]);
        if ($existing = $stmt->fetch(PDO::FETCH_ASSOC)) {
            throw new RuntimeException("Quittance déjà émise pour cette période (#{$existing['id']}).");
        }

        $loyer    = (float)$occData['loyer_mensuel_resident'];
        $charges  = (float)($occData['charges_mensuelles_resident'] ?? 0);
        $services = (float)($occData['montant_services_sup'] ?? 0);
        $apl      = (float)($occData['montant_apl'] ?? 0);
        $apa      = (float)($occData['montant_apa'] ?? 0);
        $totalDu  = round($loyer + $charges + $services - $apl - $apa, 2);

        // Numéro de quittance unique : QR-YYYY-MM-{residence}-{seq}
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM quittances_residents WHERE periode_annee = ? AND periode_mois = ? AND residence_id = ?"
        );
        $stmt->execute([$annee, $mois, $occData['residence_id']]);
        $seq = (int)$stmt->fetchColumn() + 1;
        $numero = sprintf('QR-%04d-%02d-%d-%04d', $annee, $mois, (int)$occData['residence_id'], $seq);

        $stmt = $this->db->prepare(
            "INSERT INTO quittances_residents
                (occupation_id, residence_id, resident_id, periode_mois, periode_annee, numero_quittance,
                 loyer_mensuel, charges_mensuelles, services_supp, montant_apl, montant_apa, montant_du_total,
                 statut, emis_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'emise', ?)"
        );
        $stmt->execute([
            $occupationId, $occData['residence_id'], $occData['resident_id'],
            $mois, $annee, $numero,
            $loyer, $charges, $services, $apl, $apa, $totalDu,
            $userId,
        ]);
        $newId = (int)$this->db->lastInsertId();

        Logger::audit('quittance_emise', 'quittances_residents', $newId, [
            'numero'        => $numero,
            'periode'       => sprintf('%04d-%02d', $annee, $mois),
            'montant_du'    => $totalDu,
            'occupation_id' => $occupationId,
            'resident_id'   => (int)$occData['resident_id'],
        ], $userId);

        return $newId;
    }

    /**
     * Marque une quittance comme payée.
     */
    public function marquerPayee(int $id, float $montantPaye, string $modePaiement, ?string $reference = null, ?string $datePaiement = null, ?int $userId = null): bool {
        $existing = $this->findById($id);
        if (!$existing) return false;

        $statut = abs($montantPaye - (float)$existing['montant_du_total']) < 0.01
                ? 'payee'
                : ($montantPaye > 0 ? 'partiellement_payee' : $existing['statut']);

        $stmt = $this->db->prepare(
            "UPDATE quittances_residents
             SET statut = ?, montant_paye = ?, mode_paiement = ?, reference_paiement = ?,
                 date_paiement = ?, paid_by = ?, paid_at = NOW()
             WHERE id = ?"
        );
        $ok = $stmt->execute([
            $statut, $montantPaye, $modePaiement, $reference,
            $datePaiement ?: date('Y-m-d'), $userId, $id,
        ]);

        if ($ok) {
            Logger::audit('quittance_payee', 'quittances_residents', $id, [
                'numero'      => $existing['numero_quittance'],
                'montant_paye'=> $montantPaye,
                'statut'      => $statut,
                'mode'        => $modePaiement,
            ], $userId);
        }
        return $ok;
    }

    /**
     * Annule une quittance (réservé aux quittances pas encore payées).
     */
    public function annuler(int $id, ?int $userId = null): bool {
        $stmt = $this->db->prepare(
            "UPDATE quittances_residents SET statut = 'annulee'
             WHERE id = ? AND statut IN ('emise', 'impayee')"
        );
        $stmt->execute([$id]);
        $ok = $stmt->rowCount() > 0;
        if ($ok) {
            Logger::audit('quittance_annulee', 'quittances_residents', $id, [], $userId);
        }
        return $ok;
    }

    /**
     * Auto-bascule des quittances 'emise' vers 'impayee' après J+10 du jour
     * de prélèvement (cf. occupations_residents.jour_prelevement, défaut 5).
     * Appelée à l'ouverture de la page impayés (Q7b du brief Phase 13).
     */
    public function escaladerImpayes(): int {
        // Pour chaque quittance émise dont la date d'émission + délai dépasse aujourd'hui
        $stmt = $this->db->prepare(
            "UPDATE quittances_residents q
             JOIN occupations_residents o ON o.id = q.occupation_id
             SET q.statut = 'impayee'
             WHERE q.statut = 'emise'
               AND DATE(q.emis_at) <= DATE_SUB(CURDATE(), INTERVAL ? DAY)"
        );
        $stmt->execute([self::DELAI_IMPAYE_JOURS]);
        return $stmt->rowCount();
    }

    public function findById(int $id): ?array {
        $stmt = $this->db->prepare(
            "SELECT q.*, c.nom AS residence_nom,
                    rs.prenom AS resident_prenom, rs.nom AS resident_nom,
                    u.email AS resident_email,
                    l.numero_lot, l.type AS lot_type
             FROM quittances_residents q
             LEFT JOIN coproprietees c ON c.id = q.residence_id
             LEFT JOIN residents_seniors rs ON rs.id = q.resident_id
             LEFT JOIN users u ON u.id = rs.user_id
             LEFT JOIN occupations_residents o ON o.id = q.occupation_id
             LEFT JOIN lots l ON l.id = o.lot_id
             WHERE q.id = ?"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Liste filtrée pour l'admin / la vue résident.
     *
     * Filtres : residence_ids[], resident_id, statut, periode_annee, periode_mois, search
     */
    public function listFiltered(array $filters = [], int $limit = 500): array {
        $sql = "SELECT q.*, c.nom AS residence_nom,
                       rs.prenom AS resident_prenom, rs.nom AS resident_nom
                FROM quittances_residents q
                LEFT JOIN coproprietees c ON c.id = q.residence_id
                LEFT JOIN residents_seniors rs ON rs.id = q.resident_id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['residence_ids']) && is_array($filters['residence_ids'])) {
            $ph = implode(',', array_fill(0, count($filters['residence_ids']), '?'));
            $sql .= " AND q.residence_id IN ($ph)";
            $params = array_merge($params, array_map('intval', $filters['residence_ids']));
        }
        if (!empty($filters['resident_id']))   { $sql .= " AND q.resident_id = ?"; $params[] = (int)$filters['resident_id']; }
        if (!empty($filters['statut']) && array_key_exists($filters['statut'], self::STATUTS)) {
            $sql .= " AND q.statut = ?"; $params[] = $filters['statut'];
        }
        if (!empty($filters['periode_annee'])) { $sql .= " AND q.periode_annee = ?"; $params[] = (int)$filters['periode_annee']; }
        if (!empty($filters['periode_mois']))  { $sql .= " AND q.periode_mois = ?"; $params[] = (int)$filters['periode_mois']; }
        if (!empty($filters['search'])) {
            $sql .= " AND (q.numero_quittance LIKE ? OR rs.nom LIKE ? OR rs.prenom LIKE ?)";
            $like = '%' . $filters['search'] . '%';
            $params[] = $like; $params[] = $like; $params[] = $like;
        }

        $sql .= " ORDER BY q.periode_annee DESC, q.periode_mois DESC, q.id DESC LIMIT " . max(1, min(2000, $limit));

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }

    /**
     * Liste des quittances d'un résident (pour son espace personnel).
     */
    public function listByResident(int $residentId, int $limit = 100): array {
        $stmt = $this->db->prepare(
            "SELECT q.*, c.nom AS residence_nom
             FROM quittances_residents q
             LEFT JOIN coproprietees c ON c.id = q.residence_id
             WHERE q.resident_id = ?
             ORDER BY q.periode_annee DESC, q.periode_mois DESC LIMIT " . (int)$limit
        );
        $stmt->execute([$residentId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
