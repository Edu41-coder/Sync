<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle ResidentSenior
 * ====================================================================
 * Gestion des résidents seniors dans les résidences Domitys
 */

class ResidentSenior extends Model {

    /**
     * Vérifier si un profil résident existe déjà pour un utilisateur.
     */
    public function hasProfileForUser($userId) {
        $sql = "SELECT COUNT(*) as count FROM residents_seniors WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        return (int)($result->count ?? 0) > 0;
    }
    
    /**
     * Récupérer tous les résidents actifs
     */
    public function getAll() {
        $sql = "SELECT 
                    rs.*,
                    u.username, u.email as user_email, u.actif as user_active,
                    o.loyer_mensuel_resident,
                    l.numero_lot,
                    c.nom as residence
                FROM residents_seniors rs
                LEFT JOIN users u ON rs.user_id = u.id
                LEFT JOIN occupations_residents o ON rs.id = o.resident_id AND o.statut = 'actif'
                LEFT JOIN lots l ON o.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                ORDER BY rs.actif DESC, rs.nom, rs.prenom";
        
        return $this->db->query($sql)->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer un résident par ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM residents_seniors WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer un résident par user_id
     */
    public function findByUserId($userId) {
        $sql = "SELECT * FROM residents_seniors WHERE user_id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer l'occupation actuelle d'un résident
     */
    public function getCurrentOccupation($residentId) {
        $sql = "SELECT o.*, 
                l.numero_lot, l.surface, l.nombre_pieces,
                c.nom as residence, c.adresse, c.code_postal, c.ville,
                e.raison_sociale as exploitant
                FROM occupations_residents o
                INNER JOIN lots l ON o.lot_id = l.id
                INNER JOIN coproprietees c ON l.copropriete_id = c.id
                LEFT JOIN exploitants e ON o.exploitant_id = e.id
                WHERE o.resident_id = ? AND o.statut = 'actif'
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$residentId]);
        return $stmt->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Créer un nouveau résident
     */
    public function create($data) {
        $sql = "INSERT INTO residents_seniors (
                    user_id, civilite, nom, prenom, date_naissance,
                    lieu_naissance, telephone_mobile, email,
                    numero_cni, date_delivrance_cni, lieu_delivrance_cni,
                    niveau_autonomie, besoin_assistance,
                    situation_familiale, nombre_enfants, num_securite_sociale,
                    urgence_nom, urgence_lien, urgence_telephone, urgence_telephone_2, urgence_email,
                    medecin_traitant_nom, medecin_traitant_tel,
                    regime_alimentaire, allergies,
                    mutuelle, num_mutuelle,
                    animal_compagnie, animal_type, animal_nom,
                    centres_interet,
                    date_entree, notes, actif
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['user_id']              ?? null,
            $data['civilite'],
            $data['nom'],
            $data['prenom'],
            $data['date_naissance'],
            $data['lieu_naissance']        ?? null,
            $data['telephone_mobile']      ?? null,
            $data['email']                 ?? null,
            $data['numero_cni']            ?? null,
            $data['date_delivrance_cni']   ?? null,
            $data['lieu_delivrance_cni']   ?? null,
            $data['niveau_autonomie']      ?? 'autonome',
            $data['besoin_assistance']     ?? 0,
            $data['situation_familiale']   ?? null,
            $data['nombre_enfants']        ?? 0,
            $data['num_securite_sociale']  ?? null,
            $data['urgence_nom']           ?? null,
            $data['urgence_lien']          ?? null,
            $data['urgence_telephone']     ?? null,
            $data['urgence_telephone_2']   ?? null,
            $data['urgence_email']         ?? null,
            $data['medecin_traitant_nom']  ?? null,
            $data['medecin_traitant_tel']  ?? null,
            $data['regime_alimentaire']    ?? 'normal',
            $data['allergies']             ?? null,
            $data['mutuelle']              ?? null,
            $data['num_mutuelle']          ?? null,
            $data['animal_compagnie']      ?? 0,
            $data['animal_type']           ?? null,
            $data['animal_nom']            ?? null,
            $data['centres_interet']       ?? null,
            $data['date_entree']           ?? null,
            $data['notes']                 ?? null,
            $data['actif']                 ?? 1,
        ]);

        return $result ? $this->db->lastInsertId() : false;
    }
    
    /**
     * Créer un résident avec son compte utilisateur (transaction)
     */
    public function createWithUser($data) {
        try {
            $this->db->beginTransaction();
            
            // Générer username et email
            $username = strtolower(substr($data['prenom'], 0, 1) . $data['nom']);
            // Ajouter un nombre si username existe déjà
            $baseUsername = $username;
            $counter = 1;
            $sqlCheckUser = "SELECT COUNT(*) as count FROM users WHERE username = ?";
            $stmtCheck = $this->db->prepare($sqlCheckUser);
            $stmtCheck->execute([$username]);
            while ($stmtCheck->fetch(PDO::FETCH_OBJ)->count > 0) {
                $username = $baseUsername . $counter;
                $counter++;
                $stmtCheck->execute([$username]);
            }
            
            $email = strtolower($data['prenom'] . '.' . $data['nom'] . '@resident.syndgest.fr');
            $password = password_hash('resident123', PASSWORD_BCRYPT);
            
            // Creer directement le compte utilisateur resident.
            $sqlUser = "INSERT INTO users (username, email, password_hash, role, prenom, nom, telephone, actif) 
                        VALUES (?, ?, ?, 'resident', ?, ?, ?, 1)";
            $stmtUser = $this->db->prepare($sqlUser);
            $stmtUser->execute([
                $username,
                $email,
                $password,
                $data['prenom'],
                $data['nom'],
                $data['telephone_mobile'] ?? null
            ]);
            $userId = $this->db->lastInsertId();
            
            // Créer le résident
            $sqlResident = "INSERT INTO residents_seniors (
                                user_id, civilite, nom, prenom, nom_naissance, 
                                date_naissance, lieu_naissance,
                                telephone_fixe, telephone_mobile, email, 
                                numero_cni, date_delivrance_cni, lieu_delivrance_cni,
                                niveau_autonomie, notes, actif, date_entree
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";
            $stmtResident = $this->db->prepare($sqlResident);
            $stmtResident->execute([
                $userId,
                $data['civilite'],
                $data['nom'],
                $data['prenom'],
                $data['nom_naissance'] ?? null,
                $data['date_naissance'],
                $data['lieu_naissance'] ?? null,
                $data['telephone_fixe'] ?? null,
                $data['telephone_mobile'] ?? null,
                $data['email'] ?? $email,
                $data['numero_cni'] ?? null,
                $data['date_delivrance_cni'] ?? null,
                $data['lieu_delivrance_cni'] ?? null,
                $data['niveau_autonomie'] ?? 'autonome',
                $data['notes'] ?? null
            ]);
            $residentId = $this->db->lastInsertId();
            
            $this->db->commit();
            
            return [
                'success' => true,
                'user_id' => $userId,
                'resident_id' => $residentId,
                'username' => $username,
                'password' => 'resident123'
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            $this->logError("Erreur createWithUser: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Créer une fiche résident pour un compte utilisateur résident existant.
     */
    public function createForExistingUser($userId, $data) {
        try {
            $this->db->beginTransaction();

            // Valider que l'utilisateur existe et est bien résident
            $sqlUser = "SELECT id, role, email FROM users WHERE id = ? LIMIT 1";
            $stmtUser = $this->db->prepare($sqlUser);
            $stmtUser->execute([$userId]);
            $user = $stmtUser->fetch(PDO::FETCH_OBJ);

            if (!$user) {
                throw new Exception("Utilisateur introuvable");
            }

            if ($user->role !== 'resident') {
                throw new Exception("Le compte sélectionné n'a pas le rôle résident");
            }

            if ($this->hasProfileForUser($userId)) {
                throw new Exception("Ce compte utilisateur a déjà une fiche résident");
            }

            $sqlResident = "INSERT INTO residents_seniors (
                                user_id, civilite, nom, prenom, nom_naissance,
                                date_naissance, lieu_naissance,
                                telephone_fixe, telephone_mobile, email,
                                numero_cni, date_delivrance_cni, lieu_delivrance_cni,
                                niveau_autonomie, notes, actif, date_entree
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, NOW())";

            $stmtResident = $this->db->prepare($sqlResident);
            $stmtResident->execute([
                $userId,
                $data['civilite'],
                $data['nom'],
                $data['prenom'],
                $data['nom_naissance'] ?? null,
                $data['date_naissance'],
                $data['lieu_naissance'] ?? null,
                $data['telephone_fixe'] ?? null,
                $data['telephone_mobile'] ?? null,
                $data['email'] ?? $user->email,
                $data['numero_cni'] ?? null,
                $data['date_delivrance_cni'] ?? null,
                $data['lieu_delivrance_cni'] ?? null,
                $data['niveau_autonomie'] ?? 'autonome',
                $data['notes'] ?? null
            ]);

            $residentId = $this->db->lastInsertId();
            $this->db->commit();

            return [
                'success' => true,
                'resident_id' => $residentId,
                'user_id' => $userId
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            $this->logError("Erreur createForExistingUser: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    /**
     * Mettre à jour un résident
     */
    public function update($id, $data) {
        $sql = "UPDATE residents_seniors SET
                    civilite = ?,
                    nom = ?,
                    prenom = ?,
                    nom_naissance = ?,
                    date_naissance = ?,
                    lieu_naissance = ?,
                    telephone_fixe = ?,
                    telephone_mobile = ?,
                    email = ?,
                    numero_cni = ?,
                    date_delivrance_cni = ?,
                    lieu_delivrance_cni = ?,
                    situation_familiale = ?,
                    nombre_enfants = ?,
                    niveau_autonomie = ?,
                    besoin_assistance = ?,
                    allergies = ?,
                    regime_alimentaire = ?,
                    medecin_traitant_nom = ?,
                    medecin_traitant_tel = ?,
                    num_securite_sociale = ?,
                    mutuelle = ?,
                    urgence_nom = ?,
                    urgence_lien = ?,
                    urgence_telephone = ?,
                    urgence_telephone_2 = ?,
                    urgence_email = ?,
                    notes = ?,
                    actif = ?
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['civilite'],
            $data['nom'],
            $data['prenom'],
            $data['nom_naissance'] ?? null,
            $data['date_naissance'],
            $data['lieu_naissance'] ?? null,
            $data['telephone_fixe'] ?? null,
            $data['telephone_mobile'] ?? null,
            $data['email'] ?? null,
            $data['numero_cni'] ?? null,
            $data['date_delivrance_cni'] ?? null,
            $data['lieu_delivrance_cni'] ?? null,
            $data['situation_familiale'] ?? 'celibataire',
            $data['nombre_enfants'] ?? 0,
            $data['niveau_autonomie'] ?? 'autonome',
            $data['besoin_assistance'] ?? 0,
            $data['allergies'] ?? null,
            $data['regime_alimentaire'] ?? null,
            $data['medecin_traitant_nom'] ?? null,
            $data['medecin_traitant_tel'] ?? null,
            $data['num_securite_sociale'] ?? null,
            $data['mutuelle'] ?? null,
            $data['urgence_nom'] ?? null,
            $data['urgence_lien'] ?? null,
            $data['urgence_telephone'] ?? null,
            $data['urgence_telephone_2'] ?? null,
            $data['urgence_email'] ?? null,
            $data['notes'] ?? null,
            $data['actif'] ?? 1,
            $id
        ]);
    }
    
    /**
     * Désactiver un résident (soft delete)
     */
    public function deactivate($id) {
        $sql = "UPDATE residents_seniors SET actif = 0, date_sortie = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }

    /**
     * Synchroniser le statut actif entre la fiche résident et le compte utilisateur lié.
     */
    public function syncActiveStatusByResidentId($residentId, $isActive) {
        try {
            $ownTransaction = !$this->db->inTransaction();
            if ($ownTransaction) $this->db->beginTransaction();

            $sqlResident = "SELECT id, user_id FROM residents_seniors WHERE id = ? LIMIT 1";
            $stmtResident = $this->db->prepare($sqlResident);
            $stmtResident->execute([$residentId]);
            $resident = $stmtResident->fetch(PDO::FETCH_OBJ);

            if (!$resident) {
                throw new Exception('Résident introuvable');
            }

            $activeValue = $isActive ? 1 : 0;
            $dateSortie = $isActive ? null : date('Y-m-d');

            // Mettre à jour le résident
            $this->db->prepare("UPDATE residents_seniors SET actif = ?, date_sortie = ? WHERE id = ?")
                ->execute([$activeValue, $dateSortie, $residentId]);

            // Mettre à jour le user lié
            if (!empty($resident->user_id)) {
                $this->db->prepare("UPDATE users SET actif = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([$activeValue, (int)$resident->user_id]);
            }

            // Si désactivation → terminer toutes les occupations actives (libérer les lots)
            if (!$isActive) {
                $this->db->prepare("UPDATE occupations_residents
                    SET statut = 'termine', date_sortie = ?, updated_at = NOW()
                    WHERE resident_id = ? AND statut = 'actif'")
                    ->execute([date('Y-m-d'), $residentId]);
            }

            if ($ownTransaction) $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction() && ($ownTransaction ?? false)) {
                $this->db->rollBack();
            }
            $this->logError("Erreur syncActiveStatusByResidentId: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Synchroniser le statut actif en partant du user_id lié au résident.
     */
    public function syncActiveStatusByUserId($userId, $isActive) {
        try {
            $sql = "SELECT id FROM residents_seniors WHERE user_id = ? LIMIT 1";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int)$userId]);
            $resident = $stmt->fetch(PDO::FETCH_OBJ);

            if (!$resident) {
                return false;
            }

            return $this->syncActiveStatusByResidentId((int)$resident->id, $isActive);
        } catch (Exception $e) {
            $this->logError("Erreur syncActiveStatusByUserId: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rechercher des résidents
     */
    public function search($query) {
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT 
                    rs.*,
                    c.nom as residence
                FROM residents_seniors rs
                LEFT JOIN occupations_residents o ON rs.id = o.resident_id AND o.statut = 'actif'
                LEFT JOIN lots l ON o.lot_id = l.id
                LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE rs.actif = 1
                AND (rs.nom LIKE ? OR rs.prenom LIKE ? OR rs.email LIKE ?)
                ORDER BY rs.nom, rs.prenom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les résidents par résidence
     */
    public function getByResidence($coproprieteId) {
        $sql = "SELECT 
                    rs.*,
                    l.numero_lot,
                    o.loyer_mensuel_resident, o.date_debut
                FROM residents_seniors rs
                INNER JOIN occupations_residents o ON rs.id = o.resident_id AND o.statut = 'actif'
                INNER JOIN lots l ON o.lot_id = l.id
                WHERE l.copropriete_id = ?
                ORDER BY rs.nom, rs.prenom";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$coproprieteId]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }
    
    /**
     * Récupérer les statistiques des résidents
     */
    public function getStatistics() {
        $sql = "SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN actif = 1 THEN 1 END) as actifs,
                    COUNT(CASE WHEN actif = 0 THEN 1 END) as inactifs,
                    AVG(age) as age_moyen,
                    COUNT(CASE WHEN niveau_autonomie = 'autonome' THEN 1 END) as autonomes,
                    COUNT(CASE WHEN niveau_autonomie = 'semi_autonome' THEN 1 END) as semi_autonomes,
                    COUNT(CASE WHEN niveau_autonomie = 'dependant' THEN 1 END) as dependants
                FROM residents_seniors";
        
        return $this->db->query($sql)->fetch(PDO::FETCH_OBJ);
    }
    
    /**
     * Vérifier si un email existe déjà
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) as count FROM residents_seniors WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_OBJ);
        
        return $result->count > 0;
    }
}
