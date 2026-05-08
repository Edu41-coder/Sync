<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle Impayé (helper agrégateur — Phase 13)
 * ====================================================================
 *
 * Vue unifiée des impayés à travers 3 sources hétérogènes :
 *   - quittances_residents (statut='impayee' ou 'partiellement_payee')
 *   - paiements_loyers_exploitant (statut='retard' ou 'impaye')
 *   - factures_fournisseurs (statut='attente'/'validee' avec date_echeance dépassée)
 *
 * Pas de table dédiée — agrégation à la volée via UNION dans listAll().
 *
 * Chaque ligne agrégée porte :
 *   - source_type : 'quittance_resident' | 'paiement_proprio' | 'facture_fournisseur'
 *   - source_id   : ID dans la table source
 *   - residence_id, residence_nom
 *   - tiers_label : nom à afficher (résident, propriétaire, fournisseur)
 *   - tiers_user_id : user_id du destinataire (NULL pour fournisseur)
 *   - reference   : numéro de quittance / facture / mois-année loyer
 *   - date_echeance
 *   - montant_du
 *   - jours_retard
 *   - dernier_niveau_relance (0 si aucune)
 */

class Impaye extends Model {

    /**
     * Liste unifiée des impayés sur les résidences accessibles.
     * Optimisé : 3 SELECT séparés (pas de vrai UNION SQL pour pouvoir filtrer
     * différemment par source) puis fusion + tri PHP.
     */
    public function listAll(array $residenceIds): array {
        if (empty($residenceIds)) return [];
        $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
        $resParams = array_map('intval', $residenceIds);
        $rows = [];

        // 1. Quittances résidents impayées ou partiellement payées
        try {
            $stmt = $this->db->prepare(
                "SELECT q.id, q.residence_id, c.nom AS residence_nom,
                        q.numero_quittance AS reference,
                        DATE(q.emis_at) AS date_echeance,
                        (q.montant_du_total - COALESCE(q.montant_paye, 0)) AS montant_du,
                        DATEDIFF(CURDATE(), DATE(q.emis_at)) AS jours_retard,
                        rs.user_id AS tiers_user_id,
                        CONCAT(rs.prenom, ' ', rs.nom) AS tiers_label
                 FROM quittances_residents q
                 JOIN coproprietees c ON c.id = q.residence_id
                 JOIN residents_seniors rs ON rs.id = q.resident_id
                 WHERE q.residence_id IN ($resPh)
                   AND q.statut IN ('impayee', 'partiellement_payee')"
            );
            $stmt->execute($resParams);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $r['source_type'] = 'quittance_resident';
                $r['source_id']   = (int)$r['id'];
                $rows[] = $r;
            }
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), 'Impaye listAll quittances_residents');
        }

        // 2. Paiements propriétaires en retard ou impayés
        try {
            $stmt = $this->db->prepare(
                "SELECT p.id, p.copropriete_id AS residence_id, c.nom AS residence_nom,
                        CONCAT(LPAD(p.mois, 2, '0'), '/', p.annee) AS reference,
                        p.date_echeance,
                        p.montant_total AS montant_du,
                        DATEDIFF(CURDATE(), p.date_echeance) AS jours_retard,
                        u.id AS tiers_user_id,
                        CONCAT(co.prenom, ' ', co.nom) AS tiers_label
                 FROM paiements_loyers_exploitant p
                 JOIN coproprietees c ON c.id = p.copropriete_id
                 JOIN coproprietaires co ON co.id = p.coproprietaire_id
                 LEFT JOIN users u ON u.id = co.user_id
                 WHERE p.copropriete_id IN ($resPh)
                   AND p.statut IN ('retard', 'impaye')"
            );
            $stmt->execute($resParams);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $r['source_type'] = 'paiement_proprio';
                $r['source_id']   = (int)$r['id'];
                $rows[] = $r;
            }
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), 'Impaye listAll paiements_proprio');
        }

        // 3. Factures fournisseurs : 'attente' ou 'validee' avec date_echeance dépassée
        try {
            $stmt = $this->db->prepare(
                "SELECT f.id, f.copropriete_id AS residence_id, c.nom AS residence_nom,
                        f.numero_facture AS reference,
                        f.date_echeance,
                        f.montant_ttc AS montant_du,
                        DATEDIFF(CURDATE(), f.date_echeance) AS jours_retard,
                        NULL AS tiers_user_id,
                        fr.nom AS tiers_label
                 FROM factures_fournisseurs f
                 JOIN coproprietees c ON c.id = f.copropriete_id
                 JOIN fournisseurs fr ON fr.id = f.fournisseur_id
                 WHERE f.copropriete_id IN ($resPh)
                   AND f.statut IN ('attente', 'validee')
                   AND f.date_echeance IS NOT NULL
                   AND f.date_echeance < CURDATE()"
            );
            $stmt->execute($resParams);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $r['source_type'] = 'facture_fournisseur';
                $r['source_id']   = (int)$r['id'];
                $rows[] = $r;
            }
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), 'Impaye listAll factures_fournisseurs');
        }

        // Enrichit chaque ligne avec le niveau de la dernière relance
        $relanceModel = new Relance();
        foreach ($rows as &$r) {
            $r['dernier_niveau_relance'] = $relanceModel->getDernierNiveau($r['source_type'], (int)$r['source_id']);
            $r['jours_retard'] = (int)$r['jours_retard'];
            $r['montant_du']   = round((float)$r['montant_du'], 2);
        }
        unset($r);

        // Tri : ancienneté du retard décroissante
        usort($rows, fn($a, $b) => (int)$b['jours_retard'] <=> (int)$a['jours_retard']);

        return $rows;
    }

    /**
     * Statistiques agrégées pour le dashboard.
     *
     * @return array ['total_count', 'total_montant', 'par_source' => [...], 'par_anciennete' => [...]]
     */
    public function getStats(array $residenceIds): array {
        $impayes = $this->listAll($residenceIds);

        $stats = [
            'total_count'   => count($impayes),
            'total_montant' => 0.0,
            'par_source'    => ['quittance_resident' => 0, 'paiement_proprio' => 0, 'facture_fournisseur' => 0],
            'par_anciennete'=> ['0_15j' => 0, '15_45j' => 0, '45_60j' => 0, 'plus_60j' => 0],
        ];

        foreach ($impayes as $i) {
            $stats['total_montant'] += (float)$i['montant_du'];
            $stats['par_source'][$i['source_type']] = ($stats['par_source'][$i['source_type']] ?? 0) + 1;
            $j = (int)$i['jours_retard'];
            if ($j <= 15)      $stats['par_anciennete']['0_15j']++;
            elseif ($j <= 45)  $stats['par_anciennete']['15_45j']++;
            elseif ($j <= 60)  $stats['par_anciennete']['45_60j']++;
            else                $stats['par_anciennete']['plus_60j']++;
        }
        $stats['total_montant'] = round($stats['total_montant'], 2);
        return $stats;
    }

    /**
     * Marque un impayé comme réglé (selon source) + clôture les relances actives.
     */
    public function marquerPaye(string $sourceType, int $sourceId, ?int $userId = null): bool {
        $ok = false;
        switch ($sourceType) {
            case 'quittance_resident':
                $stmt = $this->db->prepare(
                    "UPDATE quittances_residents SET statut = 'payee', paid_by = ?, paid_at = NOW(),
                     date_paiement = COALESCE(date_paiement, CURDATE()),
                     montant_paye = montant_du_total
                     WHERE id = ? AND statut IN ('impayee', 'partiellement_payee', 'emise')"
                );
                $ok = $stmt->execute([$userId, $sourceId]) && $stmt->rowCount() > 0;
                break;
            case 'paiement_proprio':
                $stmt = $this->db->prepare(
                    "UPDATE paiements_loyers_exploitant SET statut = 'paye',
                     date_paiement_effectif = COALESCE(date_paiement_effectif, CURDATE())
                     WHERE id = ? AND statut IN ('retard', 'impaye', 'attente')"
                );
                $ok = $stmt->execute([$sourceId]) && $stmt->rowCount() > 0;
                break;
            case 'facture_fournisseur':
                $stmt = $this->db->prepare(
                    "UPDATE factures_fournisseurs SET statut = 'payee',
                     date_paiement = COALESCE(date_paiement, CURDATE())
                     WHERE id = ? AND statut IN ('attente', 'validee')"
                );
                $ok = $stmt->execute([$sourceId]) && $stmt->rowCount() > 0;
                break;
        }

        if ($ok) {
            // Clôt les relances actives sur cet impayé
            $stmt = $this->db->prepare(
                "UPDATE impayes_relances SET statut = 'reglee'
                 WHERE source_type = ? AND source_id = ? AND statut = 'envoyee'"
            );
            $stmt->execute([$sourceType, $sourceId]);

            Logger::audit('impaye_marque_paye', $sourceType, $sourceId, [
                'cloture_relances' => true,
            ], $userId);
        }
        return $ok;
    }

    /**
     * Récupère le détail d'un impayé selon sa source (pour la modal de relance).
     */
    public function getDetail(string $sourceType, int $sourceId): ?array {
        switch ($sourceType) {
            case 'quittance_resident':
                $stmt = $this->db->prepare(
                    "SELECT q.id, q.residence_id, c.nom AS residence_nom,
                            q.numero_quittance AS reference,
                            (q.montant_du_total - COALESCE(q.montant_paye, 0)) AS montant_du,
                            CONCAT(LPAD(q.periode_mois, 2, '0'), '/', q.periode_annee) AS periode,
                            rs.user_id AS tiers_user_id, rs.prenom, rs.nom
                     FROM quittances_residents q
                     JOIN coproprietees c ON c.id = q.residence_id
                     JOIN residents_seniors rs ON rs.id = q.resident_id
                     WHERE q.id = ?"
                );
                break;
            case 'paiement_proprio':
                $stmt = $this->db->prepare(
                    "SELECT p.id, p.copropriete_id AS residence_id, c.nom AS residence_nom,
                            CONCAT(LPAD(p.mois, 2, '0'), '/', p.annee) AS reference,
                            p.montant_total AS montant_du,
                            CONCAT(LPAD(p.mois, 2, '0'), '/', p.annee) AS periode,
                            u.id AS tiers_user_id, co.prenom, co.nom
                     FROM paiements_loyers_exploitant p
                     JOIN coproprietees c ON c.id = p.copropriete_id
                     JOIN coproprietaires co ON co.id = p.coproprietaire_id
                     LEFT JOIN users u ON u.id = co.user_id
                     WHERE p.id = ?"
                );
                break;
            case 'facture_fournisseur':
                $stmt = $this->db->prepare(
                    "SELECT f.id, f.copropriete_id AS residence_id, c.nom AS residence_nom,
                            f.numero_facture AS reference,
                            f.montant_ttc AS montant_du,
                            DATE_FORMAT(f.date_facture, '%m/%Y') AS periode,
                            NULL AS tiers_user_id, '' AS prenom, fr.nom AS nom
                     FROM factures_fournisseurs f
                     JOIN coproprietees c ON c.id = f.copropriete_id
                     JOIN fournisseurs fr ON fr.id = f.fournisseur_id
                     WHERE f.id = ?"
                );
                break;
            default:
                return null;
        }
        $stmt->execute([$sourceId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }
}
