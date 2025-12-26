<?php
/**
 * ====================================================================
 * SYND_GEST - Modèle de base
 * ====================================================================
 * Classe de base pour tous les modèles avec PDO
 */

class Model {
    
    protected $db;
    protected $table;
    
    /**
     * Constructeur - Initialise la connexion à la base de données via Database Singleton
     */
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Exécuter une requête SELECT
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return array Résultats (objets)
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            // Convertir les arrays en objets pour compatibilité
            $results = $stmt->fetchAll();
            return array_map(function($row) {
                return (object) $row;
            }, $results);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return [];
        }
    }
    
    /**
     * Exécuter une requête SELECT et retourner une seule ligne
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return object|false Résultat (objet)
     */
    protected function queryOne($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetch();
            // Convertir l'array en objet pour compatibilité
            return $result ? (object) $result : false;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return false;
        }
    }
    
    /**
     * Exécuter une requête INSERT, UPDATE, DELETE
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     * @return bool Succès
     */
    protected function execute($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql, $params);
            return false;
        }
    }
    
    /**
     * Obtenir le dernier ID inséré
     * 
     * @return int
     */
    protected function lastInsertId() {
        return $this->db->lastInsertId();
    }
    
    /**
     * Compter le nombre d'enregistrements
     * 
     * @param string $table Nom de la table
     * @param string $where Clause WHERE (optionnelle)
     * @param array $params Paramètres
     * @return int
     */
    protected function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $result = $this->queryOne($sql, $params);
        return $result ? (int)$result->total : 0;
    }
    
    /**
     * Récupérer tous les enregistrements d'une table
     * 
     * @param string $orderBy Ordre de tri
     * @return array
     */
    public function all($orderBy = 'id DESC') {
        $sql = "SELECT * FROM {$this->table} ORDER BY $orderBy";
        return $this->query($sql);
    }
    
    /**
     * Récupérer un enregistrement par ID
     * 
     * @param int $id
     * @return object|false
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->queryOne($sql, [$id]);
    }
    
    /**
     * Supprimer un enregistrement
     * 
     * @param int $id
     * @return bool
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        return $this->execute($sql, [$id]);
    }
    
    /**
     * Commencer une transaction
     */
    protected function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    /**
     * Valider une transaction
     */
    protected function commit() {
        $this->db->commit();
    }
    
    /**
     * Annuler une transaction
     */
    protected function rollback() {
        $this->db->rollBack();
    }
    
    /**
     * Logger les erreurs
     * 
     * @param string $message
     * @param string $sql
     * @param array $params
     */
    protected function logError($message, $sql = '', $params = []) {
        $logFile = '../logs/database.log';
        $logMessage = date('Y-m-d H:i:s') . " - ERROR: $message\n";
        if ($sql) {
            $logMessage .= "SQL: $sql\n";
            $logMessage .= "Params: " . json_encode($params) . "\n";
        }
        $logMessage .= "---\n";
        
        if (!file_exists('../logs')) {
            mkdir('../logs', 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
    
    /**
     * Obtenir la connexion PDO
     * 
     * @return PDO
     */
    public function getDb() {
        return $this->db;
    }
}
