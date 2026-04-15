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
                c.latitude,
                c.longitude,
                c.actif,
                GROUP_CONCAT(DISTINCT e.raison_sociale ORDER BY e.raison_sociale SEPARATOR ', ') as exploitant,
                GROUP_CONCAT(DISTINCT e.id SEPARATOR ',') as exploitant_id,
                COUNT(DISTINCT l.id) as nb_lots,
                COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as nb_occupations,
                CASE 
                    WHEN COUNT(DISTINCT l.id) > 0 
                    THEN ROUND((COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) / COUNT(DISTINCT l.id)) * 100, 2)
                    ELSE 0 
                END as taux_occupation,
                COALESCE(SUM(CASE WHEN o.statut = 'actif' THEN o.loyer_mensuel_resident END), 0) as revenus_mensuels,
                MAX(CASE WHEN e.actif = 1 THEN 1 ELSE 0 END) as statut
            FROM coproprietees c
            LEFT JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
            LEFT JOIN exploitants e ON er.exploitant_id = e.id
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

        $sql .= " GROUP BY c.id, c.nom, c.adresse, c.ville, c.code_postal, c.latitude, c.longitude, c.actif";

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
        // Récupérer l'id Domitys (exploitant_id par défaut = 1er exploitant)
        $defaultExploitantId = $data['exploitant_id'] ?? null;
        if (!$defaultExploitantId) {
            $defaultExploitantId = $this->db->query("SELECT id FROM exploitants ORDER BY id LIMIT 1")->fetchColumn() ?: null;
        }

        $sql = "INSERT INTO coproprietees (nom, adresse, ville, code_postal, latitude, longitude, exploitant_id, type_residence, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $params = [
            $data['nom'] ?? null,
            $data['adresse'] ?? null,
            $data['ville'] ?? null,
            $data['code_postal'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $defaultExploitantId,
            $data['type_residence'] ?? 'residence_seniors'
        ];

        if (!$this->execute($sql, $params)) return false;
        $newId = $this->lastInsertId();

        // Lier automatiquement l'exploitant principal à 100% dans exploitant_residences
        if ($defaultExploitantId && $newId) {
            try {
                $this->db->prepare("INSERT IGNORE INTO exploitant_residences
                    (exploitant_id, residence_id, pourcentage_gestion, statut, date_debut)
                    VALUES (?,?,100.00,'actif',NOW())")
                    ->execute([$defaultExploitantId, $newId]);
            } catch (PDOException $e) {
                $this->logError("Erreur liaison exploitant_residences: " . $e->getMessage());
            }
        }

        return $newId;
    }

    /**
     * Mettre à jour une résidence
     */
    public function updateResidence(int $id, array $data) {
        $sql = "UPDATE coproprietees SET nom = ?, adresse = ?, ville = ?, code_postal = ?, latitude = ?, longitude = ?, exploitant_id = ?, updated_at = NOW() WHERE id = ? AND type_residence = 'residence_seniors'";
        $params = [
            $data['nom'] ?? null,
            $data['adresse'] ?? null,
            $data['ville'] ?? null,
            $data['code_postal'] ?? null,
            $data['latitude'] ?? null,
            $data['longitude'] ?? null,
            $data['exploitant_id'] ?? null,
            $id
        ];
        return $this->execute($sql, $params);
    }

    /**
     * Soft delete (ou suppression physique selon la logique) — ici on supprime physiquement si nécessaire
     */
    public function deleteResidence(int $id) {
        $sql = "UPDATE coproprietees SET actif = 0, updated_at = NOW() WHERE id = ? AND type_residence = 'residence_seniors'";
        return $this->execute($sql, [$id]);
    }

    public function restoreResidence(int $id) {
        $sql = "UPDATE coproprietees SET actif = 1, updated_at = NOW() WHERE id = ? AND type_residence = 'residence_seniors'";
        return $this->execute($sql, [$id]);
    }

    /**
     * Hard delete — supprimer définitivement (résidence vierge uniquement)
     */
    public function hardDeleteResidence(int $id) {
        try {
            $this->db->beginTransaction();
            $this->db->prepare("DELETE FROM exploitant_residences WHERE residence_id = ?")->execute([$id]);
            $this->db->prepare("DELETE FROM coproprietees WHERE id = ?")->execute([$id]);
            $this->db->commit();
            return true;
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $this->logError("Erreur hardDeleteResidence: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupérer une résidence avec ses informations d'exploitants
     * @param int $id
     * @return array|false
     */
    public function findWithExploitant(int $id) {
        $sql = "SELECT c.*, 
                GROUP_CONCAT(DISTINCT e.raison_sociale ORDER BY e.raison_sociale SEPARATOR ', ') as exploitant_nom,
                GROUP_CONCAT(DISTINCT e.id SEPARATOR ',') as exploitant_id
                FROM coproprietees c
                LEFT JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
                LEFT JOIN exploitants e ON er.exploitant_id = e.id
                WHERE c.id = ? AND c.type_residence = 'residence_seniors'
                GROUP BY c.id";
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
                GROUP_CONCAT(DISTINCT e.raison_sociale ORDER BY e.raison_sociale SEPARATOR ', ') as exploitant,
                COUNT(DISTINCT l.id) as nb_lots,
                COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as nb_occupations,
                CASE 
                    WHEN COUNT(DISTINCT l.id) > 0 
                    THEN ROUND((COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) / COUNT(DISTINCT l.id)) * 100, 2)
                    ELSE 0 
                END as taux_occupation
                FROM coproprietees c
                LEFT JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
                LEFT JOIN exploitants e ON er.exploitant_id = e.id
                LEFT JOIN lots l ON c.id = l.copropriete_id
                LEFT JOIN occupations_residents o ON l.id = o.lot_id
                WHERE c.type_residence = 'residence_seniors'
                AND c.latitude IS NOT NULL 
                AND c.longitude IS NOT NULL
                GROUP BY c.id, c.nom, c.adresse, c.ville, c.code_postal, c.latitude, c.longitude
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
                JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
                JOIN exploitants e ON er.exploitant_id = e.id 
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
                JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
                LEFT JOIN lots l ON c.id = l.copropriete_id
                LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                WHERE er.exploitant_id = ? AND c.type_residence = 'residence_seniors'
                GROUP BY c.id";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$exploitantId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$exploitantId]);
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

    /**
     * Liste des résidences avec exploitant actuel (si existant) pour affectation admin
     * @return array
     */
    public function getAllForAssignment() {
        $sql = "SELECT DISTINCT 
                  c.id, 
                  c.nom, 
                  c.ville, 
                  GROUP_CONCAT(DISTINCT CONCAT(e.id, ':', e.raison_sociale) SEPARATOR '|') as exploitants_info
                FROM coproprietees c
                LEFT JOIN exploitant_residences er ON c.id = er.residence_id AND er.statut IN ('actif', 'suspendu')
                LEFT JOIN exploitants e ON er.exploitant_id = e.id
                WHERE c.type_residence = 'residence_seniors'
                GROUP BY c.id, c.nom, c.ville
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
     * IDs des résidences gérées par un exploitant (via table d'association)
     * @param int $exploitantId
     * @return array
     */
    public function getResidenceIdsByExploitant(int $exploitantId) {
        $sql = "SELECT DISTINCT residence_id 
                FROM exploitant_residences 
                WHERE exploitant_id = ? AND statut IN ('actif', 'suspendu')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$exploitantId]);
            return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$exploitantId]);
            return [];
        }
    }

    /**
     * Synchronise l'affectation des résidences pour un exploitant (table N-N).
     * Le jeu final d'affectation correspond exactement à $residenceIds.
     * Gère les insertions/suppressions nécessaires via la table rejointe.
     * Note: N'ouvre pas de transaction (le appelant doit gérer les transactions)
     *
     * @param int $exploitantId
     * @param array $residenceIds
     * @return bool
     */
    public function syncExploitantResidences(int $exploitantId, array $residenceIds) {
        $residenceIds = array_values(array_unique(array_filter(array_map('intval', $residenceIds), fn($id) => $id > 0)));

        try {
            // Supprimer les associations actuelles (les passer en 'termine')
            $deleteSql = "UPDATE exploitant_residences
                          SET statut = 'termine', date_fin = NOW()
                          WHERE exploitant_id = ? AND statut != 'termine'";
            $deleteStmt = $this->db->prepare($deleteSql);
            $deleteStmt->execute([$exploitantId]);

            // Puis créer les nouvelles associations.
            if (!empty($residenceIds)) {
                $insertSql = "INSERT INTO exploitant_residences 
                              (exploitant_id, residence_id, date_debut, statut)
                              VALUES (?, ?, NOW(), 'actif')";
                $insertStmt = $this->db->prepare($insertSql);
                
                foreach ($residenceIds as $residenceId) {
                    $insertStmt->execute([$exploitantId, $residenceId]);
                }
            }

            return true;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), 'syncExploitantResidences', ['exploitant_id' => $exploitantId, 'residence_ids' => $residenceIds]);
            return false;
        }
    }

    /**
     * Vérifie si un exploitant a accès à une résidence (via table N-N)
     * @param int $residenceId
     * @param int $exploitantId
     * @return bool
     */
    public function hasExploitantAccess(int $residenceId, int $exploitantId) {
        $sql = "SELECT COUNT(*) FROM exploitant_residences 
                WHERE residence_id = ? AND exploitant_id = ? AND statut IN ('actif', 'suspendu')";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId, $exploitantId]);
            return (int)$stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, [$residenceId, $exploitantId]);
            return false;
        }
    }

    /**
     * Compter lots et users liés à une résidence (pour décider hard/soft delete)
     */
    public function getLinkedCounts(int $id): array {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM lots WHERE copropriete_id=?");
            $stmt->execute([$id]);
            $lots = (int)$stmt->fetchColumn();
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM user_residence WHERE residence_id=?");
            $stmt->execute([$id]);
            $users = (int)$stmt->fetchColumn();
            return ['lots' => $lots, 'users' => $users];
        } catch (PDOException $e) {
            $this->logError($e->getMessage());
            return ['lots' => 0, 'users' => 0];
        }
    }

    /**
     * Liste simple des résidences (id, nom, ville) pour formulaires
     */
    public function getAllSimple(): array {
        $sql = "SELECT id, nom, ville FROM coproprietees ORDER BY nom";
        try { return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }
}
