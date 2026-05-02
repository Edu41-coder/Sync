<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Comptabilité Maintenance Technique
 * ====================================================================
 * Agrège les coûts de toutes les sources du module maintenance :
 *   - maintenance_interventions.cout (interventions courantes)
 *   - chantiers.montant_paye / montant_engage (chantiers travaux)
 *   - ascenseur_journal.cout (visites + maintenance ascenseurs)
 *
 * Toujours filtré par résidences accessibles + année.
 * Réservé aux managers (vérification rôle dans le contrôleur).
 */
class MaintenanceComptabilite extends Model {

    /**
     * Totaux annuels consolidés.
     * @return ['total','interventions','chantiers_payes','chantiers_engages','ascenseurs','nb_chantiers_actifs']
     */
    public function getTotauxAnnuels(array $residencesIds, int $annee): array {
        $totaux = [
            'interventions'      => 0.0,
            'chantiers_payes'    => 0.0,
            'chantiers_engages'  => 0.0,
            'ascenseurs'         => 0.0,
            'nb_chantiers_actifs' => 0,
            'total'              => 0.0,
        ];
        $whereRes = !empty($residencesIds) ? 'IN (' . implode(',', array_map('intval', $residencesIds)) . ')' : 'IS NULL';

        // Interventions
        $sql = "SELECT COALESCE(SUM(cout),0) FROM maintenance_interventions
                WHERE residence_id $whereRes AND YEAR(COALESCE(date_realisee, date_planifiee, date_signalement)) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee]);
        $totaux['interventions'] = (float)$stmt->fetchColumn();

        // Chantiers payés sur l'année
        $sql = "SELECT COALESCE(SUM(montant_paye),0), COALESCE(SUM(montant_engage),0), COUNT(*)
                FROM chantiers
                WHERE residence_id $whereRes AND YEAR(created_at) = ? AND statut != 'annule'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee]);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        $totaux['chantiers_payes']     = (float)$row[0];
        $totaux['chantiers_engages']   = (float)$row[1];
        $totaux['nb_chantiers_actifs'] = (int)$row[2];

        // Ascenseurs (coûts journal)
        $sql = "SELECT COALESCE(SUM(j.cout),0) FROM ascenseur_journal j
                JOIN ascenseurs a ON a.id = j.ascenseur_id
                WHERE a.residence_id $whereRes AND YEAR(j.date_event) = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee]);
        $totaux['ascenseurs'] = (float)$stmt->fetchColumn();

        $totaux['total'] = $totaux['interventions'] + $totaux['chantiers_payes'] + $totaux['ascenseurs'];
        return $totaux;
    }

    /**
     * Ventilation par spécialité (interventions + chantiers).
     */
    public function getVentilationParSpecialite(array $residencesIds, int $annee): array {
        $whereRes = !empty($residencesIds) ? 'IN (' . implode(',', array_map('intval', $residencesIds)) . ')' : 'IS NULL';

        $sql = "SELECT s.id, s.nom, s.couleur, s.icone,
                       COALESCE(SUM(i.cout), 0) AS cout_interventions,
                       COUNT(DISTINCT i.id) AS nb_interventions
                FROM specialites s
                LEFT JOIN maintenance_interventions i
                  ON i.specialite_id = s.id
                  AND i.residence_id $whereRes
                  AND YEAR(COALESCE(i.date_realisee, i.date_planifiee, i.date_signalement)) = ?
                GROUP BY s.id
                ORDER BY s.ordre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee]);
        $bySpec = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $bySpec[(int)$r['id']] = $r + ['cout_chantiers' => 0.0, 'nb_chantiers' => 0];
        }

        // Ajout des chantiers
        $sql = "SELECT specialite_id, COALESCE(SUM(montant_paye), 0) AS cout_chantiers, COUNT(*) AS nb_chantiers
                FROM chantiers
                WHERE residence_id $whereRes AND YEAR(created_at) = ? AND specialite_id IS NOT NULL AND statut != 'annule'
                GROUP BY specialite_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee]);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $sid = (int)$r['specialite_id'];
            if (isset($bySpec[$sid])) {
                $bySpec[$sid]['cout_chantiers'] = (float)$r['cout_chantiers'];
                $bySpec[$sid]['nb_chantiers']   = (int)$r['nb_chantiers'];
            }
        }

        // Total par spécialité
        foreach ($bySpec as &$row) {
            $row['cout_total'] = (float)$row['cout_interventions'] + (float)$row['cout_chantiers'];
        }
        // Trier par cout_total desc, mais garder les 0 à la fin
        usort($bySpec, fn($a, $b) => $b['cout_total'] <=> $a['cout_total']);
        return $bySpec;
    }

    /**
     * Ventilation par résidence.
     */
    public function getVentilationParResidence(array $residencesIds, int $annee): array {
        if (empty($residencesIds)) return [];
        $whereRes = 'IN (' . implode(',', array_map('intval', $residencesIds)) . ')';

        $sql = "SELECT c.id, c.nom, c.ville,
                       (SELECT COALESCE(SUM(cout),0) FROM maintenance_interventions WHERE residence_id = c.id AND YEAR(COALESCE(date_realisee, date_planifiee, date_signalement)) = ?) AS cout_interventions,
                       (SELECT COALESCE(SUM(montant_paye),0) FROM chantiers WHERE residence_id = c.id AND YEAR(created_at) = ? AND statut != 'annule') AS cout_chantiers,
                       (SELECT COALESCE(SUM(j.cout),0) FROM ascenseur_journal j JOIN ascenseurs a ON a.id = j.ascenseur_id WHERE a.residence_id = c.id AND YEAR(j.date_event) = ?) AS cout_ascenseurs
                FROM coproprietees c
                WHERE c.id $whereRes AND c.actif = 1
                ORDER BY c.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee, $annee, $annee]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['cout_total'] = (float)$r['cout_interventions'] + (float)$r['cout_chantiers'] + (float)$r['cout_ascenseurs'];
        }
        return $rows;
    }

    /**
     * Détail des écritures pour la DataTable (interventions + chantiers + ascenseurs).
     */
    public function getDetailEcritures(array $residencesIds, int $annee, int $limit = 200): array {
        if (empty($residencesIds)) return [];
        $whereResI = 'i.residence_id IN (' . implode(',', array_map('intval', $residencesIds)) . ')';
        $whereResC = 'c.residence_id IN (' . implode(',', array_map('intval', $residencesIds)) . ')';
        $whereResA = 'a.residence_id IN (' . implode(',', array_map('intval', $residencesIds)) . ')';

        $sql = "
            SELECT 'intervention' AS source, i.id, i.titre AS libelle,
                   COALESCE(i.date_realisee, i.date_planifiee, i.date_signalement) AS date_op,
                   i.cout AS montant, s.nom AS specialite, c.nom AS residence_nom
            FROM maintenance_interventions i
            LEFT JOIN specialites s ON s.id = i.specialite_id
            JOIN coproprietees c ON c.id = i.residence_id
            WHERE $whereResI AND i.cout IS NOT NULL AND i.cout > 0
              AND YEAR(COALESCE(i.date_realisee, i.date_planifiee, i.date_signalement)) = ?

            UNION ALL

            SELECT 'chantier' AS source, c.id, c.titre AS libelle,
                   COALESCE(c.date_fin_reelle, c.date_debut_reelle, c.created_at) AS date_op,
                   c.montant_paye AS montant, s.nom AS specialite, co.nom AS residence_nom
            FROM chantiers c
            LEFT JOIN specialites s ON s.id = c.specialite_id
            JOIN coproprietees co ON co.id = c.residence_id
            WHERE $whereResC AND c.montant_paye > 0 AND c.statut != 'annule'
              AND YEAR(c.created_at) = ?

            UNION ALL

            SELECT 'ascenseur' AS source, j.id, CONCAT(a.nom, ' — ', j.type_entree) AS libelle,
                   j.date_event AS date_op,
                   j.cout AS montant, 'Ascenseur' AS specialite, c.nom AS residence_nom
            FROM ascenseur_journal j
            JOIN ascenseurs a ON a.id = j.ascenseur_id
            JOIN coproprietees c ON c.id = a.residence_id
            WHERE $whereResA AND j.cout IS NOT NULL AND j.cout > 0
              AND YEAR(j.date_event) = ?

            ORDER BY date_op DESC
            LIMIT " . (int)$limit;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee, $annee, $annee]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Synthèse mensuelle pour graphique (12 mois de l'année).
     */
    public function getSyntheseMensuelle(array $residencesIds, int $annee): array {
        if (empty($residencesIds)) return array_fill(0, 12, 0);
        $whereRes = 'IN (' . implode(',', array_map('intval', $residencesIds)) . ')';

        $sql = "SELECT mois, SUM(montant) AS total FROM (
                  SELECT MONTH(COALESCE(date_realisee, date_planifiee, date_signalement)) AS mois, COALESCE(SUM(cout),0) AS montant
                  FROM maintenance_interventions WHERE residence_id $whereRes AND YEAR(COALESCE(date_realisee, date_planifiee, date_signalement)) = ?
                  GROUP BY mois
                  UNION ALL
                  SELECT MONTH(created_at) AS mois, COALESCE(SUM(montant_paye),0) AS montant
                  FROM chantiers WHERE residence_id $whereRes AND YEAR(created_at) = ? AND statut != 'annule'
                  GROUP BY mois
                  UNION ALL
                  SELECT MONTH(j.date_event) AS mois, COALESCE(SUM(j.cout),0) AS montant
                  FROM ascenseur_journal j
                  JOIN ascenseurs a ON a.id = j.ascenseur_id
                  WHERE a.residence_id $whereRes AND YEAR(j.date_event) = ?
                  GROUP BY mois
                ) t
                GROUP BY mois";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$annee, $annee, $annee]);
        $byMois = array_fill(1, 12, 0);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $byMois[(int)$r['mois']] = (float)$r['total'];
        }
        return array_values($byMois);
    }
}
