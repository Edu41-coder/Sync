<?php
/**
 * ====================================================================
 * SYND_GEST - Classe Database Singleton
 * ====================================================================
 * Gestion centralisée de la connexion PDO à la base de données
 * Pattern Singleton pour éviter les connexions multiples
 * 
 * @author Synd_Gest Team
 * @version 1.0
 * @date 2025-11-30
 */

class Database {
    
    /**
     * Instance unique de Database (Singleton)
     */
    private static $instance = null;
    
    /**
     * Connexion PDO
     */
    private $connection = null;
    
    /**
     * Constructeur privé (Singleton)
     */
    private function __construct() {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::ATTR_PERSISTENT         => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
            
            // Activer le mode strict MySQL si configuré
            if (defined('DB_STRICT_MODE') && DB_STRICT_MODE) {
                $this->connection->exec("SET sql_mode='STRICT_ALL_TABLES'");
            }
            
        } catch (PDOException $e) {
            // Logger l'erreur si le système de logs est disponible
            if (defined('DB_LOG_FILE') && defined('DB_DEBUG') && DB_DEBUG) {
                error_log('[' . date('Y-m-d H:i:s') . '] Database Connection Error: ' . $e->getMessage() . PHP_EOL, 3, DB_LOG_FILE);
            }
            
            // En production, message générique
            if (defined('IS_PRODUCTION') && IS_PRODUCTION) {
                die('Erreur de connexion à la base de données. Veuillez réessayer ultérieurement.');
            } else {
                // En développement, afficher l'erreur détaillée
                die('Erreur de connexion à la base de données : ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Empêcher le clonage de l'instance (Singleton)
     */
    private function __clone() {}
    
    /**
     * Empêcher la désérialisation de l'instance (Singleton)
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    /**
     * Obtenir l'instance unique de Database (Singleton)
     * 
     * @return Database Instance unique
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Obtenir la connexion PDO
     * 
     * @return PDO Connexion PDO
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Exécuter une requête SELECT et retourner tous les résultats
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array Résultats
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return [];
        }
    }
    
    /**
     * Exécuter une requête SELECT et retourner un seul résultat
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return array|false Résultat ou false
     */
    public function queryOne($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Exécuter une requête INSERT, UPDATE, DELETE
     * 
     * @param string $sql Requête SQL
     * @param array $params Paramètres de la requête
     * @return bool Succès ou échec
     */
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            $this->logError($e, $sql, $params);
            return false;
        }
    }
    
    /**
     * Obtenir le dernier ID inséré
     * 
     * @return string|int Dernier ID inséré
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }
    
    /**
     * Commencer une transaction
     * 
     * @return bool Succès ou échec
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }
    
    /**
     * Valider une transaction
     * 
     * @return bool Succès ou échec
     */
    public function commit() {
        return $this->connection->commit();
    }
    
    /**
     * Annuler une transaction
     * 
     * @return bool Succès ou échec
     */
    public function rollback() {
        return $this->connection->rollBack();
    }
    
    /**
     * Vérifier si une transaction est active
     * 
     * @return bool True si transaction active
     */
    public function inTransaction() {
        return $this->connection->inTransaction();
    }
    
    /**
     * Logger une erreur SQL
     * 
     * @param PDOException $e Exception PDO
     * @param string $sql Requête SQL
     * @param array $params Paramètres
     */
    private function logError($e, $sql, $params = []) {
        if (defined('DB_LOG_FILE') && defined('DB_DEBUG') && DB_DEBUG) {
            $errorMsg = sprintf(
                "[%s] SQL Error: %s\nQuery: %s\nParams: %s\nTrace: %s\n\n",
                date('Y-m-d H:i:s'),
                $e->getMessage(),
                $sql,
                json_encode($params),
                $e->getTraceAsString()
            );
            error_log($errorMsg, 3, DB_LOG_FILE);
        }
    }
    
    /**
     * Obtenir les informations de connexion (pour debug)
     * 
     * @return array Informations de connexion
     */
    public function getInfo() {
        return [
            'host' => DB_HOST,
            'database' => DB_NAME,
            'user' => DB_USER,
            'charset' => DB_CHARSET,
            'driver' => $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            'server_version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION)
        ];
    }
    
    /**
     * Tester la connexion
     * 
     * @return bool True si connecté
     */
    public function isConnected() {
        try {
            $this->connection->query('SELECT 1');
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Fermer la connexion (appelé automatiquement à la fin du script)
     */
    public function close() {
        $this->connection = null;
    }
    
    /**
     * Destructeur - Fermer la connexion
     */
    public function __destruct() {
        $this->close();
    }
}
