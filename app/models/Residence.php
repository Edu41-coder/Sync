<?php
/**
 * Model Residence
 * Encapsule les requêtes SQL liées aux résidences (coproprietees avec type_residence = 'residence_seniors')
 */
class Residence extends Model {

    protected $table = 'coproprietees';

    /**
     * Recherche paginée avec filtres
     *
     * @param array $filters (search, ville, exploitant, taux_min)
     * @param int $page
     * @param int $perPage
     * @return array ['rows' => array, 'total' => int]
     */
    public function search(array $filters = [], int $page = 1, int $perPage = 10) {
        $offset = ($page - 1) * $perPage;

        $sql = "
            SELECT 
                c.id,
                c.nom,
                c.adresse,
                c.ville,
                c.code_postal,
                e.raison_sociale as exploitant,
                e.id as exploitant_id,
                COUNT(DISTINCT l.id) as nb_lots,
                COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as nb_occupations,
                CASE 
                    WHEN COUNT(DISTINCT l.id) > 0 
                    THEN ROUND((COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) / COUNT(DISTINCT l.id)) * 100, 2)
                    ELSE 0 
                END as taux_occupation,
                COALESCE(SUM(CASE WHEN o.statut = 'actif' THEN o.loyer_mensuel_resident END), 0) as revenus_mensuels,
                e.actif as statut
            FROM coproprietees c
            LEFT JOIN exploitants e ON c.exploitant_id = e.id
            LEFT JOIN lots l ON c.id = l.copropriete_id
            LEFT JOIN occupations_residents o ON l.id = o.lot_id
            WHERE c.type_residence = 'residence_seniors'
        ";

        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (c.nom LIKE :search OR c.ville LIKE :search2 OR c.adresse LIKE :search3)";
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search2'] = '%' . $filters['search'] . '%';
            $params['search3'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['ville'])) {
            $sql .= " AND c.ville = :ville";
            $params['ville'] = $filters['ville'];
        }

        if (!empty($filters['exploitant'])) {
            $sql .= " AND e.id = :exploitant";
            $params['exploitant'] = $filters['exploitant'];
        }

        $sql .= " GROUP BY c.id, c.nom, c.adresse, c.ville, c.code_postal, e.raison_sociale, e.id, e.actif";

        if (!empty($filters['taux_min'])) {
            $sql .= " HAVING taux_occupation >= :taux_min";
            $params['taux_min'] = $filters['taux_min'];
        }

        // Count total
        $countSql = "SELECT COUNT(*) FROM (" . $sql . ") as subquery";
        try {
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $total = (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $countSql, $params);
            return ['rows' => [], 'total' => 0];
        }

        // Add order/limit
        $sql .= " ORDER BY c.nom ASC LIMIT :limit OFFSET :offset";

        try {
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => $val) {
                // bind appropriate types
                $stmt->bindValue(':' . $key, $val);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return ['rows' => [], 'total' => 0];
        }

        return ['rows' => $rows, 'total' => $total];
    }

    /**
     * Liste des villes où il y a des résidences seniors
     * @return array
     */
    public function getCities() {
        $sql = "SELECT DISTINCT ville FROM coproprietees WHERE type_residence = 'residence_seniors' ORDER BY ville";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Récupérer la liste des exploitants pour un select
     * @return array
     */
    public function getExploitantsList() {
        $sql = "SELECT id, raison_sociale FROM exploitants ORDER BY raison_sociale";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Créer une résidence (format de données attendu: nom, adresse, ville, code_postal, exploitant_id, type_residence...)
     */
    public function create(array $data) {
        $sql = "INSERT INTO coproprietees (nom, adresse, ville, code_postal, exploitant_id, type_residence, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $params = [
            $data['nom'] ?? null,
            $data['adresse'] ?? null,
            $data['ville'] ?? null,
            $data['code_postal'] ?? null,
            $data['exploitant_id'] ?? null,
            $data['type_residence'] ?? 'residence_seniors'
        ];
        return $this->execute($sql, $params) ? $this->lastInsertId() : false;
    }

    /**
     * Mettre à jour une résidence
     */
    public function updateResidence(int $id, array $data) {
        $sql = "UPDATE coproprietees SET nom = ?, adresse = ?, ville = ?, code_postal = ?, exploitant_id = ?, updated_at = NOW() WHERE id = ? AND type_residence = 'residence_seniors'";
        $params = [
            $data['nom'] ?? null,
            $data['adresse'] ?? null,
            $data['ville'] ?? null,
            $data['code_postal'] ?? null,
            $data['exploitant_id'] ?? null,
            $id
        ];
        return $this->execute($sql, $params);
    }

    /**
     * Soft delete (ou suppression physique selon la logique) — ici on supprime physiquement si nécessaire
     */
    public function deleteResidence(int $id) {
        $sql = "DELETE FROM coproprietees WHERE id = ? AND type_residence = 'residence_seniors'";
        return $this->execute($sql, [$id]);
    }

    /**
     * Récupérer une résidence avec ses informations d'exploitant
     * @param int $id
     * @return array|false
     */
    public function findWithExploitant(int $id) {
        $sql = "SELECT c.*, e.raison_sociale as exploitant_nom, e.id as exploitant_id
                FROM coproprietees c
                LEFT JOIN exploitants e ON c.exploitant_id = e.id
                WHERE c.id = ? AND c.type_residence = 'residence_seniors'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$id]);
            return false;
        }
    }

    /**
     * Récupérer toutes les résidences avec coordonnées GPS pour affichage carte
     * @return array
     */
    public function getAllForMap() {
        $sql = "SELECT c.id, c.nom, c.adresse, c.ville, c.code_postal, 
                c.latitude, c.longitude,
                e.raison_sociale as exploitant,
                COUNT(DISTINCT l.id) as nb_lots,
                COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as nb_occupations,
                CASE 
                    WHEN COUNT(DISTINCT l.id) > 0 
                    THEN ROUND((COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) / COUNT(DISTINCT l.id)) * 100, 2)
                    ELSE 0 
                END as taux_occupation
                FROM coproprietees c
                LEFT JOIN exploitants e ON c.exploitant_id = e.id
                LEFT JOIN lots l ON c.id = l.copropriete_id
                LEFT JOIN occupations_residents o ON l.id = o.lot_id
                WHERE c.type_residence = 'residence_seniors'
                AND c.latitude IS NOT NULL 
                AND c.longitude IS NOT NULL
                GROUP BY c.id, c.nom, c.adresse, c.ville, c.code_postal, c.latitude, c.longitude, e.raison_sociale
                ORDER BY c.nom";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

    /**
     * Compter le nombre total de résidences seniors
     * @return int
     */
    public function countAll() {
        $sql = "SELECT COUNT(*) FROM coproprietees WHERE type_residence = 'residence_seniors'";
        try {
            $stmt = $this->db->query($sql);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return 0;
        }
    }

    /**
     * Compter les résidences d'un exploitant spécifique (via user_id)
     * @param int $userId
     * @return int
     */
    public function countByExploitant(int $userId) {
        $sql = "SELECT COUNT(DISTINCT c.id) 
                FROM coproprietees c 
                JOIN exploitants e ON c.exploitant_id = e.id 
                WHERE e.user_id = ? AND c.type_residence = 'residence_seniors'";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$userId]);
            return 0;
        }
    }

    /**
     * Récupérer les résidences d'un exploitant avec statistiques (lots, résidents)
     * @param int $exploitantId
     * @return array
     */
    public function getByExploitantWithStats(int $exploitantId) {
        $sql = "SELECT c.*, 
                COUNT(DISTINCT l.id) as nb_lots,
                COUNT(DISTINCT o.id) as nb_residents
                FROM coproprietees c
                LEFT JOIN lots l ON c.id = l.copropriete_id
                LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif' AND o.exploitant_id = ?
                WHERE c.exploitant_id = ? AND c.type_residence = 'residence_seniors'
                GROUP BY c.id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$exploitantId, $exploitantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$exploitantId, $exploitantId]);
            return [];
        }
    }

    /**
     * Récupérer toutes les résidences pour un select/dropdown (id, nom)
     * @return array
     */
    public function getAllForSelect() {
        $sql = "SELECT id, nom FROM coproprietees WHERE type_residence = 'residence_seniors' ORDER BY nom";
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            return [];
        }
    }

}
