<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle ContratGestion
 * ====================================================================
 * Gestion des contrats entre propriétaires et exploitants (baux commerciaux)
 */

class ContratGestion extends Model {
    
    /**
     * Récupérer tous les contrats
     */
    public function getAll() {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    c.ville,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                ORDER BY cg.date_debut DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les contrats d'un propriétaire
     */
    public function getByProprietaire($proprietaireId) {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    c.adresse, c.code_postal, c.ville
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                WHERE cg.proprietaire_id = ?
                ORDER BY cg.date_debut DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$proprietaireId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les contrats par user_id du propriétaire
     */
    public function getByProprietaireUserId($userId) {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    c.adresse, c.code_postal, c.ville
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                INNER JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cp.user_id = ?
                ORDER BY cg.date_debut DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les contrats d'un exploitant
     */
    public function getByExploitant($exploitantId) {
        $sql = "SELECT 
                    cg.*,
                    c.nom as residence,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire,
                    cp.email as proprietaire_email
                FROM contrats_gestion cg
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cg.exploitant_id = ?
                ORDER BY cg.date_debut DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$exploitantId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer un contrat par ID
     */
    public function findById($id) {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant, e.siret as exploitant_siret,
                    c.nom as residence, c.adresse, c.code_postal, c.ville,
                    cp.user_id as proprietaire_user_id,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire,
                    cp.email as proprietaire_email, cp.telephone as proprietaire_tel
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cg.id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Créer un nouveau contrat
     */
    public function create($data) {
        try {
            // Calcul du loyer annuel si non fourni
            $loyerMensuel = floatval($data['loyer_mensuel_exploitant']);
            $dureeAnnees = intval($data['duree_annees']);
            $loyerAnnuel = isset($data['loyer_annuel']) ? floatval($data['loyer_annuel']) : ($loyerMensuel * 12 * $dureeAnnees);
            
            $sql = "INSERT INTO contrats_gestion (
                        exploitant_id, copropriete_id, lot_id, coproprietaire_id,
                        date_debut, date_fin, duree_annees,
                        loyer_mensuel_exploitant, loyer_annuel,
                        conditions_indexation, clauses_particulieres, statut
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'actif')";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['exploitant_id'],
                $data['copropriete_id'],
                $data['lot_id'] ?? null,
                $data['coproprietaire_id'] ?? $data['proprietaire_id'] ?? null,
                $data['date_debut'],
                $data['date_fin'],
                $dureeAnnees,
                $loyerMensuel,
                $loyerAnnuel,
                $data['conditions_indexation'] ?? 'IRL',
                $data['clauses_particulieres'] ?? null
            ]);
            
            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logError("Erreur create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour un contrat
     */
    public function update($id, $data) {
        $sql = "UPDATE contrats_gestion SET
                    exploitant_id = ?,
                    copropriete_id = ?,
                    proprietaire_id = ?,
                    date_debut = ?,
                    date_fin = ?,
                    duree_annees = ?,
                    loyer_mensuel_exploitant = ?,
                    loyer_annuel = ?,
                    conditions_indexation = ?,
                    clauses_particulieres = ?,
                    statut = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['exploitant_id'],
            $data['copropriete_id'],
            $data['proprietaire_id'],
            $data['date_debut'],
            $data['date_fin'],
            $data['duree_annees'],
            $data['loyer_mensuel_exploitant'],
            $data['loyer_annuel'],
            $data['conditions_indexation'],
            $data['clauses_particulieres'],
            $data['statut'],
            $id
        ]);
    }
    
    /**
     * Résilier un contrat
     */
    public function resilier($id, $dateResiliation, $motif = null) {
        $sql = "UPDATE contrats_gestion SET
                    statut = 'resilie',
                    date_fin = ?,
                    clauses_particulieres = CONCAT(COALESCE(clauses_particulieres, ''), '\nRésiliation: ', ?)
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$dateResiliation, $motif ?? 'Résiliation anticipée', $id]);
    }
    
    /**
     * Récupérer l'historique des paiements d'un contrat
     */
    public function getPaiements($contratId, $limit = null) {
        $sql = "SELECT * FROM paiements_loyers_exploitant 
                WHERE contrat_gestion_id = ? 
                ORDER BY date_paiement_prevue DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . intval($limit);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contratId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Calculer le montant total versé pour un contrat
     */
    public function getTotalVerse($contratId) {
        $sql = "SELECT 
                    COUNT(*) as nb_paiements,
                    SUM(montant) as total_verse,
                    SUM(CASE WHEN statut = 'paye' THEN montant ELSE 0 END) as total_paye,
                    SUM(CASE WHEN statut = 'impaye' THEN montant ELSE 0 END) as total_impaye
                FROM paiements_loyers_exploitant
                WHERE contrat_gestion_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$contratId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les contrats actifs
     */
    public function getActifs() {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cg.statut = 'actif'
                ORDER BY cg.date_debut DESC";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les contrats arrivant à échéance
     */
    public function getEcheanceProche($joursAvantEcheance = 90) {
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire,
                    DATEDIFF(cg.date_fin, NOW()) as jours_restants
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cg.statut = 'actif'
                AND DATEDIFF(cg.date_fin, NOW()) <= ?
                AND DATEDIFF(cg.date_fin, NOW()) > 0
                ORDER BY cg.date_fin ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$joursAvantEcheance]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les statistiques globales
     */
    public function getStatistiques() {
        $sql = "SELECT 
                    COUNT(*) as total_contrats,
                    COUNT(CASE WHEN statut = 'actif' THEN 1 END) as contrats_actifs,
                    COUNT(CASE WHEN statut = 'resilie' THEN 1 END) as contrats_resilies,
                    SUM(CASE WHEN statut = 'actif' THEN loyer_mensuel_exploitant ELSE 0 END) as total_loyers_mensuels,
                    AVG(CASE WHEN statut = 'actif' THEN duree_annees END) as duree_moyenne
                FROM contrats_gestion";
        
        return $this->db->query($sql)->fetch(PDO::FETCH_OBJ);
    }
    

    
    /**
     * Récupérer les contrats récents avec détails
     */
    public function getRecent($limit = 5) {
        try {
            $sql = "SELECT 
                        cg.id,
                        cg.numero_contrat,
                        CONCAT(cp.prenom, ' ', cp.nom) as proprietaire,
                        c.nom as residence,
                        cg.loyer_mensuel_garanti,
                        cg.date_effet as date_debut,
                        cg.statut
                    FROM contrats_gestion cg
                    JOIN coproprietaires cp ON cg.coproprietaire_id = cp.id
                    JOIN lots l ON cg.lot_id = l.id
                    JOIN coproprietees c ON l.copropriete_id = c.id
                    ORDER BY cg.created_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getRecent: " . $e->getMessage());
            return [];
        }
    }
}
