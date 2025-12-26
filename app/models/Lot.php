<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Lot
 * ====================================================================
 * Gestion des lots (appartements, parkings, caves) dans les copropriétés
 */

class Lot extends Model {
    
    /**
     * Récupérer tous les lots d'une résidence avec occupation et résident
     */
    public function getByResidence($residenceId) {
        try {
            $sql = "SELECT l.*, 
                           o.id as occupation_id,
                           o.statut as occupation_statut,
                           o.date_entree as occupation_date_entree,
                           o.loyer_mensuel_resident,
                           CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                           r.id as resident_id,
                           r.telephone_mobile as resident_telephone,
                           r.niveau_autonomie
                    FROM lots l
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                    LEFT JOIN residents_seniors r ON o.resident_id = r.id
                    WHERE l.copropriete_id = ?
                    ORDER BY l.numero_lot";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByResidence: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les lots disponibles (non occupés) d'une résidence
     */
    public function getAvailable($residenceId, $type = null) {
        try {
            $sql = "SELECT l.*
                    FROM lots l
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                    WHERE l.copropriete_id = ?
                    AND o.id IS NULL";
            
            $params = [$residenceId];
            
            if ($type) {
                $sql .= " AND l.type = ?";
                $params[] = $type;
            }
            
            $sql .= " ORDER BY l.numero_lot";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAvailable: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les lots d'un propriétaire
     */
    public function getByProprietaire($proprietaireId) {
        try {
            $sql = "SELECT l.*,
                           c.nom as residence_nom,
                           c.ville,
                           c.adresse,
                           o.statut as occupation_statut,
                           CONCAT(r.prenom, ' ', r.nom) as resident_nom
                    FROM lots l
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    INNER JOIN coproprietaires_lots cl ON l.id = cl.lot_id
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                    LEFT JOIN residents_seniors r ON o.resident_id = r.id
                    WHERE cl.coproprietaire_id = ?
                    ORDER BY c.nom, l.numero_lot";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$proprietaireId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByProprietaire: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un lot avec ses détails complets
     */
    public function findWithDetails($id) {
        try {
            $sql = "SELECT l.*,
                           c.nom as residence_nom,
                           c.ville,
                           c.adresse,
                           c.code_postal,
                           o.id as occupation_id,
                           o.statut as occupation_statut,
                           o.date_entree,
                           o.loyer_mensuel_resident,
                           o.forfait_type,
                           r.id as resident_id,
                           CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                           r.telephone_mobile as resident_telephone,
                           r.email as resident_email,
                           r.niveau_autonomie
                    FROM lots l
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                    LEFT JOIN residents_seniors r ON o.resident_id = r.id
                    WHERE l.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur findWithDetails: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Compter les lots par type dans une résidence
     */
    public function countByType($residenceId) {
        try {
            $sql = "SELECT 
                        type,
                        COUNT(*) as total,
                        COUNT(o.id) as occupes
                    FROM lots l
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                    WHERE l.copropriete_id = ?
                    GROUP BY type";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur countByType: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les lots pour un select (dropdown)
     */
    public function getAllForSelect($residenceId = null) {
        try {
            $sql = "SELECT l.id, 
                           CONCAT(c.nom, ' - Lot ', l.numero_lot, ' (', l.type, ')') as nom
                    FROM lots l
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id";
            
            $params = [];
            
            if ($residenceId) {
                $sql .= " WHERE l.copropriete_id = ?";
                $params[] = $residenceId;
            }
            
            $sql .= " ORDER BY c.nom, l.numero_lot";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAllForSelect: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer un nouveau lot
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO lots (
                        copropriete_id, numero_lot, type, etage,
                        surface, nombre_pieces, description,
                        tantiemes_generaux, tantiemes_chauffage, tantiemes_ascenseur
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['copropriete_id'],
                $data['numero_lot'],
                $data['type'],
                $data['etage'] ?? null,
                $data['surface'] ?? null,
                $data['nombre_pieces'] ?? null,
                $data['description'] ?? null,
                $data['tantiemes_generaux'] ?? 0,
                $data['tantiemes_chauffage'] ?? 0,
                $data['tantiemes_ascenseur'] ?? 0
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour un lot
     */
    public function updateLot($id, $data) {
        try {
            $sql = "UPDATE lots SET
                        numero_lot = ?,
                        type = ?,
                        etage = ?,
                        surface = ?,
                        nombre_pieces = ?,
                        description = ?,
                        tantiemes_generaux = ?,
                        tantiemes_chauffage = ?,
                        tantiemes_ascenseur = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['numero_lot'],
                $data['type'],
                $data['etage'] ?? null,
                $data['surface'] ?? null,
                $data['nombre_pieces'] ?? null,
                $data['description'] ?? null,
                $data['tantiemes_generaux'] ?? 0,
                $data['tantiemes_chauffage'] ?? 0,
                $data['tantiemes_ascenseur'] ?? 0,
                $id
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur updateLot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un lot
     */
    public function deleteLot($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM lots WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logError("Erreur deleteLot: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un lot est occupé
     */
    public function isOccupied($lotId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM occupations_residents 
                    WHERE lot_id = ? AND statut = 'actif'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lotId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur isOccupied: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer les statistiques d'une résidence (lots + occupations)
     */
    public function getResidenceStats($residenceId) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT l.id) as total_lots,
                        COUNT(DISTINCT CASE WHEN l.type = 'appartement' THEN l.id END) as total_appartements,
                        COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as occupations_actives,
                        COALESCE(SUM(CASE WHEN o.statut = 'actif' THEN o.loyer_mensuel_resident END), 0) as revenus_mensuels,
                        COALESCE(SUM(CASE WHEN cg.statut = 'actif' THEN cg.loyer_mensuel_garanti END), 0) as charges_mensuelles,
                        COUNT(DISTINCT CASE WHEN o.statut = 'actif' AND l.type = 'appartement' THEN o.id END) * 100.0 / 
                            NULLIF(COUNT(DISTINCT CASE WHEN l.type = 'appartement' THEN l.id END), 0) as taux_occupation
                    FROM lots l
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id
                    LEFT JOIN contrats_gestion cg ON l.id = cg.lot_id
                    WHERE l.copropriete_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getResidenceStats: " . $e->getMessage());
            return null;
        }
    }
}
