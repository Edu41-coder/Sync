<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle MaintenanceIntervention
 * ====================================================================
 * CRUD interventions courantes + filtrage par spécialités/résidences user.
 */
class MaintenanceIntervention extends Model {

    public const TYPES   = ['entretien_courant', 'reparation', 'controle_reglementaire', 'urgence'];
    public const PRIORITES = ['basse', 'normale', 'haute', 'urgente'];
    public const STATUTS = ['a_planifier', 'planifiee', 'en_cours', 'terminee', 'annulee'];

    /**
     * Liste filtrée des interventions selon le rôle/spécialités du user.
     *
     * @param int    $userId       ID du user connecté
     * @param string $userRole     Rôle (admin/directeur_residence/technicien_chef = tout, technicien = filtré)
     * @param array  $specialitesIds Spécialités du technicien (utilisé si rôle == technicien)
     * @param array  $residencesIds Résidences accessibles (filtre supplémentaire pour staff non-admin)
     * @param array  $filtres        ['statut' => '...', 'specialite_id' => N, 'residence_id' => N]
     */
    public function getInterventions(int $userId, string $userRole, array $specialitesIds, array $residencesIds, array $filtres = []): array {
        $sql = "SELECT i.*,
                       s.slug AS specialite_slug, s.nom AS specialite_nom, s.icone AS specialite_icone, s.couleur AS specialite_couleur,
                       c.nom AS residence_nom,
                       l.numero_lot,
                       u.prenom AS assigne_prenom, u.nom AS assigne_nom
                FROM maintenance_interventions i
                JOIN specialites s ON s.id = i.specialite_id
                JOIN coproprietees c ON c.id = i.residence_id
                LEFT JOIN lots l ON l.id = i.lot_id
                LEFT JOIN users u ON u.id = i.user_assigne_id
                WHERE 1=1";
        $params = [];

        $isManager = in_array($userRole, ['admin', 'directeur_residence', 'technicien_chef'], true);

        // Restriction technicien : interventions de ses spécialités OU qui lui sont assignées
        if (!$isManager) {
            if (empty($specialitesIds)) {
                // Aucune spécialité → ne voit que ce qui lui est assigné
                $sql .= " AND i.user_assigne_id = ?";
                $params[] = $userId;
            } else {
                $placeholders = implode(',', array_fill(0, count($specialitesIds), '?'));
                $sql .= " AND (i.specialite_id IN ($placeholders) OR i.user_assigne_id = ?)";
                array_push($params, ...$specialitesIds);
                $params[] = $userId;
            }
        }

        // Restriction résidences accessibles (sauf admin)
        if ($userRole !== 'admin' && !empty($residencesIds)) {
            $placeholders = implode(',', array_fill(0, count($residencesIds), '?'));
            $sql .= " AND i.residence_id IN ($placeholders)";
            array_push($params, ...$residencesIds);
        }

        // Filtres optionnels
        if (!empty($filtres['statut'])) { $sql .= " AND i.statut = ?"; $params[] = $filtres['statut']; }
        if (!empty($filtres['specialite_id'])) { $sql .= " AND i.specialite_id = ?"; $params[] = (int)$filtres['specialite_id']; }
        if (!empty($filtres['residence_id'])) { $sql .= " AND i.residence_id = ?"; $params[] = (int)$filtres['residence_id']; }

        $sql .= " ORDER BY FIELD(i.priorite, 'urgente','haute','normale','basse'), i.date_planifiee ASC, i.date_signalement DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Événements pour le calendrier TUI sur une période donnée.
     */
    public function getEventsCalendrier(int $userId, string $userRole, array $specialitesIds, array $residencesIds, string $start, string $end): array {
        $sql = "SELECT i.id, i.titre, i.description, i.statut, i.priorite, i.date_planifiee,
                       i.date_realisee, i.user_assigne_id,
                       COALESCE(i.date_realisee, i.date_planifiee) AS date_event,
                       s.slug AS specialite_slug, s.nom AS specialite_nom, s.couleur, s.bg_couleur,
                       c.nom AS residence_nom
                FROM maintenance_interventions i
                JOIN specialites s ON s.id = i.specialite_id
                JOIN coproprietees c ON c.id = i.residence_id
                WHERE i.date_planifiee IS NOT NULL
                  AND i.date_planifiee BETWEEN ? AND ?
                  AND i.statut != 'annulee'";
        $params = [$start, $end];

        $isManager = in_array($userRole, ['admin', 'directeur_residence', 'technicien_chef'], true);
        if (!$isManager) {
            if (empty($specialitesIds)) {
                $sql .= " AND i.user_assigne_id = ?";
                $params[] = $userId;
            } else {
                $placeholders = implode(',', array_fill(0, count($specialitesIds), '?'));
                $sql .= " AND (i.specialite_id IN ($placeholders) OR i.user_assigne_id = ?)";
                array_push($params, ...$specialitesIds);
                $params[] = $userId;
            }
        }
        if ($userRole !== 'admin' && !empty($residencesIds)) {
            $placeholders = implode(',', array_fill(0, count($residencesIds), '?'));
            $sql .= " AND i.residence_id IN ($placeholders)";
            array_push($params, ...$residencesIds);
        }

        $sql .= " ORDER BY i.date_planifiee";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findById(int $id): ?array {
        $sql = "SELECT i.*,
                       s.slug AS specialite_slug, s.nom AS specialite_nom, s.icone AS specialite_icone, s.couleur AS specialite_couleur,
                       c.nom AS residence_nom, c.id AS residence_id_check,
                       l.numero_lot, l.type AS lot_type,
                       u.prenom AS assigne_prenom, u.nom AS assigne_nom, u.email AS assigne_email,
                       cu.prenom AS createur_prenom, cu.nom AS createur_nom
                FROM maintenance_interventions i
                JOIN specialites s ON s.id = i.specialite_id
                JOIN coproprietees c ON c.id = i.residence_id
                LEFT JOIN lots l ON l.id = i.lot_id
                LEFT JOIN users u ON u.id = i.user_assigne_id
                LEFT JOIN users cu ON cu.id = i.created_by
                WHERE i.id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function create(array $data): int {
        $sql = "INSERT INTO maintenance_interventions
                (residence_id, specialite_id, lot_id, titre, description, type_intervention,
                 priorite, statut, user_assigne_id, prestataire_externe, prestataire_externe_tel,
                 date_planifiee, notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $this->db->prepare($sql)->execute([
            $data['residence_id'],
            $data['specialite_id'],
            $data['lot_id'] ?: null,
            $data['titre'],
            $data['description'] ?: null,
            $data['type_intervention'] ?? 'entretien_courant',
            $data['priorite'] ?? 'normale',
            $data['statut'] ?? 'a_planifier',
            $data['user_assigne_id'] ?: null,
            $data['prestataire_externe'] ?: null,
            $data['prestataire_externe_tel'] ?: null,
            $data['date_planifiee'] ?: null,
            $data['notes'] ?: null,
            $data['created_by'] ?: null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool {
        $sql = "UPDATE maintenance_interventions SET
                    residence_id = ?, specialite_id = ?, lot_id = ?, titre = ?, description = ?,
                    type_intervention = ?, priorite = ?, statut = ?, user_assigne_id = ?,
                    prestataire_externe = ?, prestataire_externe_tel = ?,
                    date_planifiee = ?, date_realisee = ?, duree_minutes = ?, cout = ?, notes = ?
                WHERE id = ?";
        return $this->db->prepare($sql)->execute([
            $data['residence_id'],
            $data['specialite_id'],
            $data['lot_id'] ?: null,
            $data['titre'],
            $data['description'] ?: null,
            $data['type_intervention'] ?? 'entretien_courant',
            $data['priorite'] ?? 'normale',
            $data['statut'] ?? 'a_planifier',
            $data['user_assigne_id'] ?: null,
            $data['prestataire_externe'] ?: null,
            $data['prestataire_externe_tel'] ?: null,
            $data['date_planifiee'] ?: null,
            $data['date_realisee'] ?: null,
            !empty($data['duree_minutes']) ? (int)$data['duree_minutes'] : null,
            !empty($data['cout']) ? (float)$data['cout'] : null,
            $data['notes'] ?: null,
            $id,
        ]);
    }

    public function updateStatut(int $id, string $statut): bool {
        $extra = '';
        $params = [$statut];
        if ($statut === 'terminee') {
            $extra = ", date_realisee = COALESCE(date_realisee, NOW())";
        }
        $sql = "UPDATE maintenance_interventions SET statut = ?$extra WHERE id = ?";
        $params[] = $id;
        return $this->db->prepare($sql)->execute($params);
    }

    public function updateDatePlanifiee(int $id, ?string $datePlanifiee): bool {
        $sql = "UPDATE maintenance_interventions SET date_planifiee = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([$datePlanifiee, $id]);
    }

    public function updatePhoto(int $id, string $champ, string $chemin): bool {
        if (!in_array($champ, ['photo_avant', 'photo_apres'], true)) {
            throw new InvalidArgumentException('Champ photo invalide');
        }
        $sql = "UPDATE maintenance_interventions SET $champ = ? WHERE id = ?";
        return $this->db->prepare($sql)->execute([$chemin, $id]);
    }

    public function deleteIntervention(int $id): ?array {
        $row = $this->findById($id);
        if (!$row) return null;
        $this->db->prepare("DELETE FROM maintenance_interventions WHERE id = ?")->execute([$id]);
        return $row;
    }

    /**
     * Stats dashboard : compteurs par statut/spécialité (filtrés selon rôle).
     */
    public function getStats(int $userId, string $userRole, array $specialitesIds, array $residencesIds): array {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN i.statut IN ('a_planifier','planifiee') THEN 1 ELSE 0 END) AS en_attente,
                    SUM(CASE WHEN i.statut = 'en_cours' THEN 1 ELSE 0 END) AS en_cours,
                    SUM(CASE WHEN i.statut = 'terminee' THEN 1 ELSE 0 END) AS terminees,
                    SUM(CASE WHEN i.priorite = 'urgente' AND i.statut NOT IN ('terminee','annulee') THEN 1 ELSE 0 END) AS urgentes
                FROM maintenance_interventions i
                WHERE 1=1";
        $params = [];
        $isManager = in_array($userRole, ['admin', 'directeur_residence', 'technicien_chef'], true);
        if (!$isManager) {
            if (empty($specialitesIds)) {
                $sql .= " AND i.user_assigne_id = ?";
                $params[] = $userId;
            } else {
                $placeholders = implode(',', array_fill(0, count($specialitesIds), '?'));
                $sql .= " AND (i.specialite_id IN ($placeholders) OR i.user_assigne_id = ?)";
                array_push($params, ...$specialitesIds);
                $params[] = $userId;
            }
        }
        if ($userRole !== 'admin' && !empty($residencesIds)) {
            $placeholders = implode(',', array_fill(0, count($residencesIds), '?'));
            $sql .= " AND i.residence_id IN ($placeholders)";
            array_push($params, ...$residencesIds);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total'=>0,'en_attente'=>0,'en_cours'=>0,'terminees'=>0,'urgentes'=>0];
    }
}
