<?php
/**
 * Modèle Parametre
 * Gestion des paramètres système et configurations
 */


class Parametre extends Model {
    
    /**
     * Récupérer un paramètre par sa clé
     */
    public function getByKey($key) {
        $sql = "SELECT valeur FROM parametres WHERE cle = ? LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result ? $result['valeur'] : null;
        } catch (PDOException $e) {
            $this->logError("Erreur getByKey: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer plusieurs paramètres par préfixe
     */
    public function getAllByPrefix($prefix) {
        $sql = "SELECT cle, valeur, type FROM parametres WHERE cle LIKE ? ORDER BY cle";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$prefix . '%']);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAllByPrefix: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer ou mettre à jour un paramètre (UPSERT)
     */
    public function upsert($key, $value, $description = null, $type = 'string') {
        try {
            // Vérifier si le paramètre existe
            $sqlCheck = "SELECT id FROM parametres WHERE cle = ?";
            $stmtCheck = $this->db->prepare($sqlCheck);
            $stmtCheck->execute([$key]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Mise à jour
                $sql = "UPDATE parametres SET valeur = ?, updated_at = NOW() WHERE cle = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$value, $key]);
            } else {
                // Insertion
                $sql = "INSERT INTO parametres (cle, valeur, description, type)
                        VALUES (?, ?, ?, ?)";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([$key, $value, $description, $type]);
            }
        } catch (PDOException $e) {
            $this->logError("Erreur upsert: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un paramètre
     */
    public function deleteByKey($key) {
        $sql = "DELETE FROM parametres WHERE cle = ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$key]);
        } catch (PDOException $e) {
            $this->logError("Erreur deleteByKey: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupérer tous les paramètres
     */
    public function getAll() {
        $sql = "SELECT * FROM parametres ORDER BY cle";
        
        try {
            $stmt = $this->db->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer un paramètre avec tous ses détails
     */
    public function getFullByKey($key) {
        $sql = "SELECT * FROM parametres WHERE cle = ? LIMIT 1";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getFullByKey: " . $e->getMessage());
            return null;
        }
    }
}
