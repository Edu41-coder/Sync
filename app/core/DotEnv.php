<?php
/**
 * ====================================================================
 * SYND_GEST - Classe DotEnv
 * ====================================================================
 * Chargement des variables d'environnement depuis un fichier .env
 * Alternative légère à vlucas/phpdotenv
 * 
 * @author Synd_Gest Team
 * @version 1.0
 * @date 2025-11-30
 */

class DotEnv {
    
    /**
     * Chemin du fichier .env
     */
    private $path;
    
    /**
     * Constructeur
     * 
     * @param string $path Chemin vers le fichier .env
     */
    public function __construct($path) {
        if (!file_exists($path)) {
            throw new InvalidArgumentException(sprintf('%s does not exist', $path));
        }
        $this->path = $path;
    }
    
    /**
     * Charger les variables d'environnement
     */
    public function load() {
        if (!is_readable($this->path)) {
            throw new RuntimeException(sprintf('%s file is not readable', $this->path));
        }
        
        $lines = file($this->path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            // Ignorer les commentaires
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Parser la ligne KEY=VALUE
            if (strpos($line, '=') !== false) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);
                
                // Supprimer les guillemets si présents
                $value = trim($value, '"\'');
                
                // Ne pas écraser les variables déjà définies
                if (!array_key_exists($name, $_ENV) && !array_key_exists($name, $_SERVER)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }
    
    /**
     * Obtenir une variable d'environnement
     * 
     * @param string $key Nom de la variable
     * @param mixed $default Valeur par défaut si non trouvée
     * @return mixed Valeur de la variable
     */
    public static function get($key, $default = null) {
        // Chercher dans $_ENV
        if (array_key_exists($key, $_ENV)) {
            return $_ENV[$key];
        }
        
        // Chercher dans $_SERVER
        if (array_key_exists($key, $_SERVER)) {
            return $_SERVER[$key];
        }
        
        // Chercher avec getenv()
        $value = getenv($key);
        if ($value !== false) {
            return $value;
        }
        
        // Retourner la valeur par défaut
        return $default;
    }
    
    /**
     * Vérifier si une variable existe
     * 
     * @param string $key Nom de la variable
     * @return bool True si la variable existe
     */
    public static function has($key) {
        return self::get($key) !== null;
    }
    
    /**
     * Obtenir une variable requise (lance une exception si non trouvée)
     * 
     * @param string $key Nom de la variable
     * @return mixed Valeur de la variable
     * @throws RuntimeException Si la variable n'existe pas
     */
    public static function getRequired($key) {
        $value = self::get($key);
        if ($value === null) {
            throw new RuntimeException(sprintf('Environment variable "%s" is required but not set', $key));
        }
        return $value;
    }
    
    /**
     * Obtenir une variable booléenne
     * 
     * @param string $key Nom de la variable
     * @param bool $default Valeur par défaut
     * @return bool Valeur booléenne
     */
    public static function getBool($key, $default = false) {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
    
    /**
     * Obtenir une variable entière
     * 
     * @param string $key Nom de la variable
     * @param int $default Valeur par défaut
     * @return int Valeur entière
     */
    public static function getInt($key, $default = 0) {
        $value = self::get($key);
        if ($value === null) {
            return $default;
        }
        
        return (int) $value;
    }
}
