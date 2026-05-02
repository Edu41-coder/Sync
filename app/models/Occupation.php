<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle Occupation
 * ====================================================================
 * Gestion des occupations (locations) des résidents dans les lots
 */

class Occupation extends Model {
    
    /**
     * Récupérer toutes les occupations avec détails complets
     */
    public function getAll() {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.telephone_mobile as resident_telephone,
                        r.email as resident_email,
                        r.niveau_autonomie,
                        l.numero_lot,
                        l.type as lot_type,
                        l.surface,
                        c.nom as residence_nom,
                        c.ville,
                        e.raison_sociale as exploitant_nom
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    ORDER BY o.date_entree DESC";
            
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer une occupation par ID avec tous les détails
     */
    public function findById($id) {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.id as resident_id,
                        r.civilite as resident_civilite,
                        r.telephone_mobile as resident_telephone,
                        r.email as resident_email,
                        r.niveau_autonomie,
                        r.age,
                        r.user_id as resident_user_id,
                        l.numero_lot,
                        l.id as lot_id,
                        l.type as lot_type,
                        l.surface,
                        l.nombre_pieces,
                        l.etage,
                        c.nom as residence_nom,
                        c.id as residence_id,
                        c.adresse,
                        c.code_postal,
                        c.ville,
                        e.raison_sociale as exploitant_nom,
                        e.id as exploitant_id_data,
                        e.telephone as exploitant_telephone,
                        e.email as exploitant_email
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    WHERE o.id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur findById: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer les occupations actives
     */
    public function getActive() {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.telephone_mobile as resident_telephone,
                        r.niveau_autonomie,
                        l.numero_lot,
                        l.type as lot_type,
                        c.nom as residence_nom,
                        c.ville,
                        e.raison_sociale as exploitant_nom
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    WHERE o.statut = 'actif'
                    ORDER BY c.nom, l.numero_lot";
            
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getActive: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les occupations d'une résidence
     */
    public function getByResidence($residenceId, $statut = null) {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.age,
                        r.telephone_mobile as resident_telephone,
                        r.niveau_autonomie,
                        l.numero_lot,
                        l.type as lot_type,
                        l.surface,
                        e.raison_sociale as exploitant_nom
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    WHERE l.copropriete_id = ?";
            
            $params = [$residenceId];
            
            if ($statut) {
                $sql .= " AND o.statut = ?";
                $params[] = $statut;
            }
            
            $sql .= " ORDER BY l.numero_lot, o.date_entree DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByResidence: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer l'occupation active d'un lot
     */
    public function getActiveByLot($lotId) {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.id as resident_id,
                        r.telephone_mobile as resident_telephone,
                        r.email as resident_email,
                        r.niveau_autonomie,
                        r.age,
                        l.numero_lot,
                        l.type as lot_type,
                        l.surface,
                        c.nom as residence_nom,
                        c.ville
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    WHERE o.lot_id = ? AND o.statut = 'actif'
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lotId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getActiveByLot: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupérer l'occupation actuelle d'un résident
     */
    public function getCurrentByResident($residentId) {
        try {
            $sql = "SELECT 
                        o.*,
                        l.numero_lot,
                        l.type as lot_type,
                        l.surface,
                        l.nombre_pieces,
                        c.nom as residence_nom,
                        c.adresse,
                        c.code_postal,
                        c.ville,
                        e.raison_sociale as exploitant_nom,
                        e.telephone as exploitant_telephone
                    FROM occupations_residents o
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    WHERE o.resident_id = ? AND o.statut = 'actif'
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residentId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getCurrentByResident: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Toutes les occupations actives d'un résident (depuis migration 018,
     * un résident peut avoir N lots dans N résidences).
     */
    public function getActivesByResident($residentId) {
        try {
            $sql = "SELECT
                        o.*,
                        l.numero_lot,
                        l.type as lot_type,
                        l.surface,
                        l.nombre_pieces,
                        l.etage,
                        c.id as residence_id,
                        c.nom as residence_nom,
                        c.adresse,
                        c.code_postal,
                        c.ville,
                        e.raison_sociale as exploitant_nom,
                        e.telephone as exploitant_telephone
                    FROM occupations_residents o
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    LEFT JOIN exploitants e ON o.exploitant_id = e.id
                    WHERE o.resident_id = ? AND o.statut = 'actif'
                    ORDER BY l.type, c.nom, l.numero_lot";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getActivesByResident: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Récupérer l'historique des occupations d'un résident
     */
    public function getHistoryByResident($residentId) {
        try {
            $sql = "SELECT 
                        o.*,
                        l.numero_lot,
                        l.type as lot_type,
                        c.nom as residence_nom,
                        c.ville
                    FROM occupations_residents o
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    WHERE o.resident_id = ?
                    ORDER BY o.date_entree DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getHistoryByResident: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer les occupations par exploitant
     */
    public function getByExploitant($exploitantId, $statut = null) {
        try {
            $sql = "SELECT 
                        o.*,
                        CONCAT(r.prenom, ' ', r.nom) as resident_nom,
                        r.niveau_autonomie,
                        l.numero_lot,
                        c.nom as residence_nom,
                        c.ville
                    FROM occupations_residents o
                    INNER JOIN residents_seniors r ON o.resident_id = r.id
                    INNER JOIN lots l ON o.lot_id = l.id
                    INNER JOIN coproprietees c ON l.copropriete_id = c.id
                    WHERE o.exploitant_id = ?";
            
            $params = [$exploitantId];
            
            if ($statut) {
                $sql .= " AND o.statut = ?";
                $params[] = $statut;
            }
            
            $sql .= " ORDER BY o.date_entree DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getByExploitant: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Créer une nouvelle occupation
     */
    public function create($data) {
        try {
            $sql = "INSERT INTO occupations_residents (
                        lot_id, resident_id, exploitant_id, date_entree,
                        type_sejour, duree_prevue_mois, loyer_mensuel_resident,
                        charges_mensuelles_resident, services_inclus,
                        services_supplementaires, montant_services_sup,
                        forfait_type, depot_garantie, date_versement_depot,
                        mode_paiement, jour_prelevement, beneficie_apl,
                        montant_apl, beneficie_apa, montant_apa, statut, notes
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $data['lot_id'],
                $data['resident_id'],
                $data['exploitant_id'],
                $data['date_entree'],
                $data['type_sejour'] ?? 'permanent',
                $data['duree_prevue_mois'] ?? null,
                $data['loyer_mensuel_resident'],
                $data['charges_mensuelles_resident'] ?? 0,
                $data['services_inclus'] ?? null,
                $data['services_supplementaires'] ?? null,
                $data['montant_services_sup'] ?? 0,
                $data['forfait_type'] ?? 'essentiel',
                $data['depot_garantie'] ?? 0,
                $data['date_versement_depot'] ?? null,
                $data['mode_paiement'] ?? 'prelevement',
                $data['jour_prelevement'] ?? 5,
                $data['beneficie_apl'] ?? 0,
                $data['montant_apl'] ?? null,
                $data['beneficie_apa'] ?? 0,
                $data['montant_apa'] ?? null,
                $data['statut'] ?? 'actif',
                $data['notes'] ?? null
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur create: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mettre à jour une occupation
     */
    public function updateOccupation($id, $data) {
        try {
            $sql = "UPDATE occupations_residents SET
                        resident_id = COALESCE(?, resident_id),
                        loyer_mensuel_resident = ?,
                        charges_mensuelles_resident = ?,
                        montant_services_sup = ?,
                        forfait_type = ?,
                        mode_paiement = ?,
                        jour_prelevement = ?,
                        beneficie_apl = ?,
                        montant_apl = ?,
                        beneficie_apa = ?,
                        montant_apa = ?,
                        statut = ?,
                        notes = ?,
                        updated_at = NOW()
                    WHERE id = ?";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['resident_id'] ?? null,
                $data['loyer_mensuel_resident'],
                $data['charges_mensuelles_resident'] ?? 0,
                $data['montant_services_sup'] ?? 0,
                $data['forfait_type'] ?? 'essentiel',
                $data['mode_paiement'] ?? 'prelevement',
                $data['jour_prelevement'] ?? 5,
                $data['beneficie_apl'] ?? 0,
                $data['montant_apl'] ?? null,
                $data['beneficie_apa'] ?? 0,
                $data['montant_apa'] ?? null,
                $data['statut'],
                $data['notes'] ?? null,
                $id
            ]);
            
            if (!$result) {
                $errorInfo = $stmt->errorInfo();
                $this->logError("Erreur SQL updateOccupation: " . $errorInfo[2]);
            }
            
            return $result;
        } catch (PDOException $e) {
            $this->logError("Erreur updateOccupation: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Terminer une occupation
     */
    public function terminate($id, $dateSortie, $notes = null) {
        try {
            $sql = "UPDATE occupations_residents 
                    SET statut = 'termine', 
                        date_sortie = ?,
                        notes = CONCAT(COALESCE(notes, ''), '\n', ?)
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([
                $dateSortie,
                $notes ?? 'Occupation terminée',
                $id
            ]);
        } catch (PDOException $e) {
            $this->logError("Erreur terminate: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Calculer les revenus d'un exploitant sur une période
     */
    public function getRevenues($exploitantId, $dateDebut, $dateFin) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT o.id) as nb_occupations,
                        SUM(o.loyer_mensuel_resident) as total_loyers_residents,
                        SUM(cg.loyer_mensuel_garanti) as total_loyers_proprietaires,
                        SUM(o.loyer_mensuel_resident - COALESCE(cg.loyer_mensuel_garanti, 0)) as marge_totale
                    FROM occupations_residents o
                    LEFT JOIN contrats_gestion cg ON o.lot_id = cg.lot_id AND cg.statut = 'actif'
                    WHERE o.exploitant_id = ?
                    AND o.statut = 'actif'
                    AND o.date_entree <= ?
                    AND (o.date_sortie IS NULL OR o.date_sortie >= ?)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$exploitantId, $dateFin, $dateDebut]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getRevenues: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Calculer le taux d'occupation d'une résidence
     */
    public function getTauxOccupation($residenceId) {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT CASE WHEN l.type IN ('studio','t2','t2_bis','t3') THEN l.id END) as total_studios,
                        COUNT(DISTINCT CASE WHEN l.type IN ('studio','t2','t2_bis','t3') AND o.statut = 'actif' THEN o.id END) as studios_occupes,
                        ROUND(
                            COUNT(DISTINCT CASE WHEN l.type IN ('studio','t2','t2_bis','t3') AND o.statut = 'actif' THEN o.id END) * 100.0 / 
                            NULLIF(COUNT(DISTINCT CASE WHEN l.type IN ('studio','t2','t2_bis','t3') THEN l.id END), 0),
                            2
                        ) as taux_occupation
                    FROM lots l
                    LEFT JOIN occupations_residents o ON l.id = o.lot_id
                    WHERE l.copropriete_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$residenceId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getTauxOccupation: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Statistiques globales des occupations
     */
    public function getGlobalStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_occupations,
                        COUNT(CASE WHEN statut = 'actif' THEN 1 END) as occupations_actives,
                        COUNT(CASE WHEN statut = 'termine' THEN 1 END) as occupations_terminees,
                        COUNT(CASE WHEN statut = 'preavis' THEN 1 END) as preavis,
                        AVG(CASE WHEN statut = 'actif' THEN loyer_mensuel_resident END) as loyer_moyen,
                        SUM(CASE WHEN statut = 'actif' THEN loyer_mensuel_resident END) as revenus_mensuels_totaux
                    FROM occupations_residents";
            
            return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getGlobalStats: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Vérifier si un lot est déjà occupé
     */
    public function isLotOccupied($lotId) {
        try {
            $sql = "SELECT COUNT(*) as count 
                    FROM occupations_residents 
                    WHERE lot_id = ? AND statut = 'actif'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$lotId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur isLotOccupied: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  FORMULAIRES — RÉSIDENTS DISPONIBLES & LOTS LIBRES
    // ─────────────────────────────────────────────────────────────

    /**
     * Tous les résidents actifs (un résident peut louer plusieurs lots
     * dans plusieurs résidences sans contrainte applicative — la seule
     * contrainte est « 1 occupant actif max par lot » au niveau BDD).
     * Le paramètre $lotType est conservé pour compatibilité avec les
     * vues existantes mais n'est plus utilisé pour filtrer.
     */
    public function getResidentsDisponibles(?string $lotType = null, ?int $excludeOccupationId = null): array {
        unset($lotType, $excludeOccupationId); // signature gardée pour compat des vues existantes
        try {
            $sql = "SELECT rs.id, rs.civilite, rs.nom, rs.prenom,
                       CONCAT(rs.civilite, ' ', rs.prenom, ' ', rs.nom) as nom_complet
                    FROM residents_seniors rs
                    WHERE rs.actif = 1
                    ORDER BY rs.nom, rs.prenom";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getResidentsDisponibles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Lots libres (non occupés) groupés par résidence
     */
    public function getLotsLibres(): array {
        $sql = "SELECT l.id, l.numero_lot, l.type, l.surface, l.etage, l.terrasse,
                   c.id as residence_id, c.nom as residence_nom
                FROM lots l
                JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
                WHERE o.id IS NULL
                ORDER BY c.nom, l.numero_lot";
        try {
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getLotsLibres: " . $e->getMessage());
            return [];
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  VALIDATION — RÈGLES MÉTIER
    // ─────────────────────────────────────────────────────────────

    /**
     * Type d'un lot par ID
     */
    public function getLotType(int $lotId): ?string {
        try {
            $stmt = $this->db->prepare("SELECT type FROM lots WHERE id=?");
            $stmt->execute([$lotId]);
            return $stmt->fetchColumn() ?: null;
        } catch (PDOException $e) {
            $this->logError("Erreur getLotType: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Type de lot d'une occupation existante
     */
    public function getOccupationLotType(int $occupationId): ?string {
        try {
            $stmt = $this->db->prepare("SELECT l.type FROM lots l JOIN occupations_residents o ON o.lot_id=l.id WHERE o.id=?");
            $stmt->execute([$occupationId]);
            return $stmt->fetchColumn() ?: null;
        } catch (PDOException $e) {
            $this->logError("Erreur getOccupationLotType: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Exploitant principal d'un lot (via exploitant_residences)
     */
    public function getLotExploitant(int $lotId): int {
        try {
            $stmt = $this->db->prepare("
                SELECT er.exploitant_id FROM lots l
                JOIN exploitant_residences er ON er.residence_id = l.copropriete_id AND er.statut = 'actif'
                WHERE l.id = ? ORDER BY er.pourcentage_gestion DESC LIMIT 1
            ");
            $stmt->execute([$lotId]);
            return (int)($stmt->fetchColumn() ?: 1);
        } catch (PDOException $e) {
            $this->logError("Erreur getLotExploitant: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Terminer une occupation (avec date_fin et motif)
     */
    public function terminerOccupation(int $id, string $dateFin, ?string $motif): bool {
        try {
            $sql = "UPDATE occupations_residents SET statut='termine', date_fin=?, motif_fin=? WHERE id=?";
            return $this->db->prepare($sql)->execute([$dateFin, $motif, $id]);
        } catch (PDOException $e) {
            $this->logError("Erreur terminerOccupation: " . $e->getMessage());
            return false;
        }
    }
}
