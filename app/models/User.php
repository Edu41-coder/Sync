<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle User
 * ====================================================================
 */

class User extends Model {
    
    protected $table = 'users';
    
    /**
     * Trouver un utilisateur par nom d'utilisateur
     * 
     * @param string $username
     * @return object|false
     */
    public function findByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = ?";
        return $this->queryOne($sql, [$username]);
    }
    
    /**
     * Trouver un utilisateur par email
     * 
     * @param string $email
     * @return object|false
     */
    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = ?";
        return $this->queryOne($sql, [$email]);
    }
    
    /**
     * Créer un nouvel utilisateur
     * 
     * @param array $data Données de l'utilisateur
     * @return int|false ID de l'utilisateur créé ou false
     */
    public function create($data) {
        $sql = "INSERT INTO {$this->table} (
            username, email, password_hash, prenom, nom, role, telephone, actif, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $params = [
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['prenom'] ?? null,
            $data['nom'] ?? null,
            $data['role'],
            $data['telephone'] ?? null,
            $data['actif'] ?? 1
        ];
        
        if ($this->execute($sql, $params)) {
            return $this->lastInsertId();
        }
        
        return false;
    }
    
    /**
     * Mettre à jour un utilisateur
     * 
     * @param int $id ID de l'utilisateur
     * @param array $data Données à mettre à jour
     * @return bool
     */
    public function update($id, $data) {
        $sql = "UPDATE {$this->table} SET 
            email = ?, prenom = ?, nom = ?, telephone = ?, updated_at = NOW()
            WHERE id = ?";
        
        $params = [
            $data['email'],
            $data['prenom'] ?? null,
            $data['nom'] ?? null,
            $data['telephone'] ?? null,
            $id
        ];
        
        return $this->execute($sql, $params);
    }
    
    /**
     * Mettre à jour le mot de passe
     * 
     * @param int $id ID de l'utilisateur
     * @param string $password Nouveau mot de passe
     * @return bool
     */
    public function updatePassword($id, $password) {
        $sql = "UPDATE {$this->table} SET password_hash = ?, updated_at = NOW() WHERE id = ?";
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        return $this->execute($sql, [$hashedPassword, $id]);
    }
    
    /**
     * Mettre à jour la dernière connexion
     * 
     * @param int $id ID de l'utilisateur
     * @return bool
     */
    public function updateLastLogin($id) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        return $this->execute($sql, [$id]);
    }
    
    /**
     * Vérifier si l'utilisateur a une permission
     * 
     * @param int $userId ID de l'utilisateur
     * @param string $module Module à vérifier
     * @param string $action Action à vérifier (create, read, update, delete, all)
     * @return bool
     */
    public function hasPermission($userId, $module, $action) {
        $user = $this->find($userId);
        if (!$user) return false;
        
        // Admin a tous les droits
        if ($user->role === 'admin') return true;
        
        $sql = "SELECT allowed FROM permissions 
                WHERE role = ? AND module = ? 
                AND (action = ? OR action = 'all') 
                LIMIT 1";
        
        $result = $this->queryOne($sql, [$user->role, $module, $action]);
        return $result && $result->allowed == 1;
    }
    
    /**
     * Obtenir toutes les permissions d'un rôle
     * 
     * @param string $role Rôle
     * @return array
     */
    public function getRolePermissions($role) {
        $sql = "SELECT * FROM permissions WHERE role = ? AND allowed = 1";
        return $this->query($sql, [$role]);
    }
    
    /**
     * Obtenir la description d'un rôle
     * 
     * @param string $role Rôle
     * @return object|false
     */
    public function getRoleDescription($role) {
        $sql = "SELECT * FROM role_descriptions WHERE role = ?";
        return $this->queryOne($sql, [$role]);
    }
    
    /**
     * Obtenir tous les utilisateurs actifs
     * 
     * @return array
     */
    public function getAllActive() {
        $sql = "SELECT * FROM {$this->table} WHERE actif = 1 ORDER BY username";
        return $this->query($sql);
    }
    
    /**
     * Obtenir les utilisateurs par rôle
     * 
     * @param string $role
     * @return array
     */
    public function getByRole($role) {
        $sql = "SELECT * FROM {$this->table} WHERE role = ? ORDER BY prenom, nom";
        return $this->query($sql, [$role]);
    }
    
    /**
     * Activer/Désactiver un utilisateur
     * 
     * @param int $id
     * @param bool $active
     * @return bool
     */
    public function toggleActive($id, $actif) {
        $sql = "UPDATE {$this->table} SET actif = ?, updated_at = NOW() WHERE id = ?";
        return $this->execute($sql, [$actif ? 1 : 0, $id]);
    }
    
    /**
     * Récupérer tous les utilisateurs avec statistiques
     */
    public function getAll() {
        try {
            $sql = "SELECT * FROM {$this->table} ORDER BY created_at DESC";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAll: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupérer tous les utilisateurs avec leur profil senior (si existe)
     * 
     * @return array Liste des utilisateurs avec resident_id
     */
    public function getAllWithResidentProfile() {
        try {
            $sql = "SELECT u.*, rs.id as resident_id 
                    FROM {$this->table} u
                    LEFT JOIN residents_seniors rs ON u.id = rs.user_id
                    ORDER BY u.created_at DESC";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAllWithResidentProfile: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifier si un utilisateur a une fiche résident liée.
     */
    public function hasResidentProfile($userId) {
        try {
            $sql = "SELECT COUNT(*) as count FROM residents_seniors WHERE user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return ((int)($result['count'] ?? 0)) > 0;
        } catch (PDOException $e) {
            $this->logError("Erreur hasResidentProfile: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un email existe
     */
    public function emailExists($email, $excludeId = null) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE email = ?";
            $params = [$email];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->logError("Erreur emailExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier si un username existe
     */
    public function usernameExists($username, $excludeId = null) {
        try {
            $sql = "SELECT id FROM {$this->table} WHERE username = ?";
            $params = [$username];
            
            if ($excludeId) {
                $sql .= " AND id != ?";
                $params[] = $excludeId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            $this->logError("Erreur usernameExists: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Obtenir tous les rôles disponibles depuis la table roles
     *
     * @return array
     */
    public function getAllRoles() {
        try {
            $sql = "SELECT slug, nom_affichage, categorie, couleur, icone FROM roles WHERE actif = 1 ORDER BY ordre_affichage";
            return $this->db->query($sql)->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getAllRoles: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtenir les résidences associées à un utilisateur (via user_residence)
     *
     * @param int $userId
     * @return array
     */
    public function getUserResidences($userId) {
        try {
            $sql = "SELECT ur.*, c.nom as residence_nom, c.ville
                    FROM user_residence ur
                    JOIN coproprietees c ON ur.residence_id = c.id
                    WHERE ur.user_id = ? AND ur.statut = 'actif'
                    ORDER BY c.nom";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $this->logError("Erreur getUserResidences: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Créer un utilisateur complet (nouvelle version avec tous les champs)
     */
    public function createUser($data) {
        try {
            $password      = $data['password'];
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $sql = "INSERT INTO {$this->table} (
                        nom, prenom, email, username, password_hash, password_plain, role, actif, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['email'],
                $data['username'],
                $hashedPassword,
                $password,
                $data['role'],
                $data['actif'] ?? 1
            ]);

            return $result ? $this->db->lastInsertId() : false;
        } catch (PDOException $e) {
            $this->logError("Erreur createUser: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mettre à jour un utilisateur complet
     */
    public function updateUser($id, $data, $includePassword = false) {
        try {
            if ($includePassword && !empty($data['password'])) {
                $password       = $data['password'];
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE {$this->table}
                        SET nom = ?, prenom = ?, email = ?, username = ?,
                            password_hash = ?, password_plain = ?, role = ?, actif = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $data['nom'],
                    $data['prenom'],
                    $data['email'],
                    $data['username'],
                    $hashedPassword,
                    $password,
                    $data['role'],
                    $data['actif'] ?? 1,
                    $id
                ]);
            } else {
                $sql = "UPDATE {$this->table}
                        SET nom = ?, prenom = ?, email = ?, username = ?, role = ?, actif = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $data['nom'],
                    $data['prenom'],
                    $data['email'],
                    $data['username'],
                    $data['role'],
                    $data['actif'] ?? 1,
                    $id
                ]);
            }
        } catch (PDOException $e) {
            $this->logError("Erreur updateUser: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Soft delete (désactiver un utilisateur)
     */
    public function softDelete($id) {
        try {
            $sql = "UPDATE {$this->table} SET actif = 0, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            $this->logError("Erreur softDelete: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Basculer le statut actif/inactif
     */
    public function toggleStatus($id) {
        try {
            $user = $this->find($id);
            if (!$user) return false;
            
            $newStatus = $user->actif ? 0 : 1;
            $sql = "UPDATE {$this->table} SET actif = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$newStatus, $id]);
        } catch (PDOException $e) {
            $this->logError("Erreur toggleStatus: " . $e->getMessage());
            return false;
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  ADMIN : Profils liés à un utilisateur
    // ─────────────────────────────────────────────────────────────

    public function getProprietaireProfile(int $userId): ?array {
        try { $stmt = $this->db->prepare("SELECT * FROM coproprietaires WHERE user_id = ?"); $stmt->execute([$userId]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function getResidentProfile(int $userId): ?array {
        try { $stmt = $this->db->prepare("SELECT * FROM residents_seniors WHERE user_id = ?"); $stmt->execute([$userId]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function getExploitantProfile(int $userId): ?array {
        try { $stmt = $this->db->prepare("SELECT * FROM exploitants WHERE user_id = ?"); $stmt->execute([$userId]); return $stmt->fetch(PDO::FETCH_ASSOC) ?: null; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return null; }
    }

    public function getUserResidencesList(int $userId): array {
        $sql = "SELECT ur.*, c.nom as residence_nom, c.ville FROM user_residence ur JOIN coproprietees c ON ur.residence_id = c.id WHERE ur.user_id = ? ORDER BY c.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$userId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getOccupationsByResident(int $residentId): array {
        $sql = "SELECT o.*, l.numero_lot, l.type as lot_type, c.nom as residence_nom
                FROM occupations_residents o LEFT JOIN lots l ON o.lot_id = l.id LEFT JOIN coproprietees c ON l.copropriete_id = c.id
                WHERE o.resident_id = ? ORDER BY o.statut = 'actif' DESC, o.date_entree DESC";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$residentId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getContratsByProprietaire(int $proprioId): array {
        $sql = "SELECT cg.*, l.numero_lot, c.nom as residence_nom FROM contrats_gestion cg LEFT JOIN lots l ON cg.lot_id = l.id LEFT JOIN coproprietees c ON l.copropriete_id = c.id WHERE cg.coproprietaire_id = ? ORDER BY cg.statut, c.nom";
        try { $stmt = $this->db->prepare($sql); $stmt->execute([$proprioId]); return $stmt->fetchAll(PDO::FETCH_ASSOC); }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function hasLinkedProfile(int $userId, string $role): bool {
        $tables = ['proprietaire' => 'coproprietaires', 'locataire_permanent' => 'residents_seniors', 'exploitant' => 'exploitants'];
        $table = $tables[$role] ?? null;
        if (!$table) return false;
        try { $stmt = $this->db->prepare("SELECT COUNT(*) FROM $table WHERE user_id = ?"); $stmt->execute([$userId]); return $stmt->fetchColumn() > 0; }
        catch (PDOException $e) { $this->logError($e->getMessage()); return false; }
    }

    // ─────────────────────────────────────────────────────────────
    //  ADMIN : Création profils liés (transaction)
    // ─────────────────────────────────────────────────────────────

    public function assignToResidences(int $userId, array $residenceIds, string $role): void {
        $stmt = $this->db->prepare("INSERT IGNORE INTO user_residence (user_id, residence_id, role, statut) VALUES (?,?,?,?)");
        foreach ($residenceIds as $rid) { $stmt->execute([$userId, $rid, $role, 'actif']); }
    }

    public function createProprietaireProfile(int $userId, array $data): int {
        $this->db->prepare("INSERT INTO coproprietaires (user_id, civilite, nom, prenom, date_naissance, adresse_principale, code_postal, ville, telephone, email, telephone_mobile, profession, notes, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())")
            ->execute([$userId, $data['civilite'] ?? 'M', $data['nom'], $data['prenom'], $data['date_naissance'] ?: null, $data['adresse'] ?: null, $data['code_postal'] ?: null, $data['ville'] ?: null, $data['telephone'] ?: null, $data['email'], $data['telephone_mobile'] ?: null, $data['profession'] ?: null, $data['notes'] ?: null]);
        return (int)$this->db->lastInsertId();
    }

    public function createOccupationForResident(int $residentId, int $lotId, string $dateEntree): void {
        $this->db->prepare("INSERT INTO occupations_residents (resident_id, lot_id, date_entree, statut, created_at) VALUES (?, ?, ?, 'actif', NOW())")->execute([$residentId, $lotId, $dateEntree]);
    }

    public function createExploitantResidenceLinks(int $userId, int $exploitantId, array $residenceIds): void {
        $stmtUR = $this->db->prepare("INSERT IGNORE INTO user_residence (user_id, residence_id, role, statut) VALUES (?,?,?,?)");
        $stmtER = $this->db->prepare("INSERT IGNORE INTO exploitant_residences (exploitant_id, residence_id, pourcentage_gestion, statut, date_debut) VALUES (?,?,0,'actif',NOW())");
        foreach ($residenceIds as $rid) { $stmtUR->execute([$userId, $rid, 'exploitant', 'actif']); $stmtER->execute([$exploitantId, $rid]); }
    }

    // ─────────────────────────────────────────────────────────────
    //  DASHBOARD & PROFIL
    // ─────────────────────────────────────────────────────────────

    public function getAdminDashboardStats(): array {
        $sql = "SELECT (SELECT COUNT(*) FROM users WHERE actif = 1) as total_users, (SELECT COUNT(*) FROM contrats_gestion WHERE statut = 'actif') as total_contrats, (SELECT COUNT(*) FROM residents_seniors) as total_residents, (SELECT COALESCE(SUM(loyer_mensuel_garanti), 0) FROM contrats_gestion WHERE statut = 'actif') as revenus_mensuels, (SELECT COALESCE(AVG(taux_occupation_pct), 0) FROM v_taux_occupation) as taux_occupation_moyen";
        try { return $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: []; }
        catch (PDOException $e) { $this->logError($e->getMessage(), $sql); return []; }
    }

    public function getProfileStats(int $userId): array {
        $stats = ['coproprietes' => 0, 'documents' => 0, 'messages' => 0];
        try { $stats['coproprietes'] = (int)$this->db->query("SELECT COUNT(*) FROM coproprietes")->fetchColumn(); } catch (PDOException $e) {}
        try { $stats['documents'] = (int)$this->db->query("SELECT COUNT(*) FROM documents")->fetchColumn(); } catch (PDOException $e) {}
        try { $stmt = $this->db->prepare("SELECT COUNT(*) FROM messages WHERE destinataire_id = ? AND lu = 0"); $stmt->execute([$userId]); $stats['messages'] = (int)$stmt->fetchColumn(); } catch (PDOException $e) {}
        return $stats;
    }
}
