<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle AppelFonds
 * ====================================================================
 * Gestion des appels de fonds (charges de copropriété)
 */

class AppelFonds extends Model {
    
    /**
     * Récupérer tous les appels de fonds
     */
    public function getAll() {
        try {
            $sql = "SELECT 
                        af.*,
                        c.nom as copropriete,
                        c.ville
                    FROM appels_fonds af
                    JOIN coproprietees c ON af.copropriete_id = c.id
                    ORDER BY af.date_emission DESC";
            
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les appels de fonds récents par gestionnaire (syndic)
     */
    public function getRecentByGestionnaire($gestionnaireId, $limit = 5) {
        try {
            $sql = "SELECT 
                        af.id,
                        c.nom as copropriete,
                        af.date_emission,
                        af.date_echeance,
                        af.montant_total,
                        af.type,
                        af.statut
                    FROM appels_fonds af
                    JOIN coproprietees c ON af.copropriete_id = c.id
                    WHERE c.syndic_id = ?
                    ORDER BY af.date_emission DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$gestionnaireId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getRecentByGestionnaire: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les appels de fonds par copropriété
     */
    public function getByCopropriete($coproprieteId) {
        try {
            $sql = "SELECT * FROM appels_fonds
                    WHERE copropriete_id = ?
                    ORDER BY date_emission DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$coproprieteId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByCopropriete: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les appels de fonds par gestionnaire
     */
    public function getByGestionnaire($gestionnaireId) {
        try {
            $sql = "SELECT 
                        af.*,
                        c.nom as copropriete,
                        c.adresse,
                        c.ville
                    FROM appels_fonds af
                    JOIN coproprietees c ON af.copropriete_id = c.id
                    WHERE c.syndic_id = ?
                    ORDER BY af.date_emission DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$gestionnaireId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByGestionnaire: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les appels de fonds en cours (émis, non soldés)
     */
    public function getEnCours($gestionnaireId = null) {
        try {
            $sql = "SELECT 
                        af.*,
                        c.nom as copropriete
                    FROM appels_fonds af
                    JOIN coproprietees c ON af.copropriete_id = c.id
                    WHERE af.statut = 'emis'";
            
            if ($gestionnaireId) {
                $sql .= " AND c.syndic_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$gestionnaireId]);
            } else {
                $stmt = $this->db->query($sql);
            }
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getEnCours: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer un nouvel appel de fonds
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO appels_fonds (
                        copropriete_id, exercice, numero_appel,
                        type, date_emission, date_echeance,
                        montant_total, description, statut
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['copropriete_id'],
                $data['exercice'],
                $data['numero_appel'],
                $data['type'] ?? 'provisionnel',
                $data['date_emission'],
                $data['date_echeance'],
                $data['montant_total'],
                $data['description'] ?? null,
                $data['statut'] ?? 'emis'
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour un appel de fonds
     */
    public function updateAppelFonds($id, $data) {
        try {
            $sql = "UPDATE appels_fonds SET
                        date_emission = ?,
                        date_echeance = ?,
                        montant_total = ?,
                        description = ?,
                        statut = ?
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['date_emission'],
                $data['date_echeance'],
                $data['montant_total'],
                $data['description'] ?? null,
                $data['statut'],
                $id
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur updateAppelFonds: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprimer un appel de fonds
     */
    public function deleteAppelFonds($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM appels_fonds WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logError("Erreur deleteAppelFonds: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Statistiques des appels de fonds
     */
    public function getStats($gestionnaireId = null) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_appels,
                        COUNT(CASE WHEN statut = 'emis' THEN 1 END) as emis,
                        COUNT(CASE WHEN statut = 'solde' THEN 1 END) as soldes,
                        COUNT(CASE WHEN statut = 'annule' THEN 1 END) as annules,
                        SUM(montant_total) as montant_total_emis,
                        SUM(CASE WHEN statut = 'solde' THEN montant_total ELSE 0 END) as montant_solde
                    FROM appels_fonds af";
            
            if ($gestionnaireId) {
                $sql .= " JOIN coproprietees c ON af.copropriete_id = c.id
                          WHERE c.syndic_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$gestionnaireId]);
            } else {
                $stmt = $this->db->query($sql);
            }
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getStats: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les impayés par gestionnaire
     */
    public function getImpayes($gestionnaireId) {
        try {
            $sql = "SELECT COALESCE(SUM(laf.montant - laf.montant_paye), 0) as total_impayes
                    FROM lignes_appel_fonds laf
                    JOIN appels_fonds af ON laf.appel_fonds_id = af.id
                    JOIN coproprietees c ON af.copropriete_id = c.id
                    WHERE c.syndic_id = ?
                    AND laf.statut_paiement != 'paye'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$gestionnaireId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_impayes'];
        } catch (PDOException $e) {
            $this->logError("Erreur getImpayes: " . $e->getMessage());
            return 0;
        }
    }
}
