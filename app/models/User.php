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
     * Créer un utilisateur complet (nouvelle version avec tous les champs)
     */
    public function createUser($data) {
        try {
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $sql = "INSERT INTO {$this->table} (
                        nom, prenom, email, username, password_hash, role, actif, created_at, updated_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['nom'],
                $data['prenom'],
                $data['email'],
                $data['username'],
                $hashedPassword,
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
                $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
                $sql = "UPDATE {$this->table} 
                        SET nom = ?, prenom = ?, email = ?, username = ?, password_hash = ?, role = ?, actif = ?, updated_at = NOW()
                        WHERE id = ?";
                $stmt = $this->db->prepare($sql);
                return $stmt->execute([
                    $data['nom'],
                    $data['prenom'],
                    $data['email'],
                    $data['username'],
                    $hashedPassword,
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
}
