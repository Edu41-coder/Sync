<?php

/**
 * Classe Security - Gestion de la sécurité de l'application
 * 
 * Fonctionnalités :
 * - Protection CSRF (Cross-Site Request Forgery)
 * - Rate limiting simple
 * - Validation des entrées
 * - Headers de sécurité
 */
class Security {
    
    /**
     * Générer un token CSRF
     * @return string Token CSRF sécurisé (64 caractères hexadécimaux)
     */
    public static function generateToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        $_SESSION['csrf_token_time'] = time();
        
        return $token;
    }
    
    /**
     * Vérifier un token CSRF
     * @param string $token Token à vérifier
     * @return bool True si le token est valide
     */
    public static function verifyToken($token) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérifier que le token existe
        if (!isset($_SESSION['csrf_token']) || !isset($_SESSION['csrf_token_time'])) {
            return false;
        }
        
        // Vérifier l'expiration (2 heures)
        if (time() - $_SESSION['csrf_token_time'] > 7200) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_time']);
            return false;
        }
        
        // Comparaison sécurisée contre les attaques de timing
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Obtenir le token CSRF actuel (ou en générer un nouveau)
     * @return string Token CSRF
     */
    public static function getToken() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Vérifier si un token valide existe déjà
        if (isset($_SESSION['csrf_token']) && isset($_SESSION['csrf_token_time'])) {
            if (time() - $_SESSION['csrf_token_time'] <= 7200) {
                return $_SESSION['csrf_token'];
            }
        }
        
        // Générer un nouveau token
        return self::generateToken();
    }
    
    /**
     * Valider un ID numérique
     * @param mixed $id ID à valider
     * @return bool True si l'ID est valide
     */
    public static function validateId($id) {
        return isset($id) && is_numeric($id) && $id > 0;
    }
    
    /**
     * Nettoyer une chaîne (XSS protection)
     * @param string $input Chaîne à nettoyer
     * @return string Chaîne nettoyée
     */
    public static function sanitize($input) {
        if (is_array($input)) {
            return array_map([self::class, 'sanitize'], $input);
        }
        
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Valider un email
     * @param string $email Email à valider
     * @return bool True si l'email est valide
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Vérifier le rate limiting (limitation du nombre de requêtes)
     * @param int $maxRequests Nombre maximum de requêtes par minute (défaut: 100)
     * @return bool True si la limite n'est pas atteinte
     */
    public static function checkRateLimit($maxRequests = 100) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $currentTime = time();
        
        // Initialiser le compteur si nécessaire
        if (!isset($_SESSION['last_request'])) {
            $_SESSION['last_request'] = $currentTime;
            $_SESSION['request_count'] = 1;
            return true;
        }
        
        // Si plus d'une minute s'est écoulée, réinitialiser
        if ($currentTime - $_SESSION['last_request'] >= 60) {
            $_SESSION['last_request'] = $currentTime;
            $_SESSION['request_count'] = 1;
            return true;
        }
        
        // Incrémenter le compteur
        $_SESSION['request_count']++;
        
        // Vérifier si la limite est dépassée
        if ($_SESSION['request_count'] > $maxRequests) {
            Logger::logRateLimitExceeded();
            return false;
        }
        
        return true;
    }
    
    /**
     * Vérifier le rate limiting par IP (plus strict)
     * Utilise un fichier cache au lieu de la session
     * @param int $maxRequests Nombre maximum de requêtes par minute
     * @return bool True si la limite n'est pas atteinte
     */
    public static function checkRateLimitByIp($maxRequests = 60) {
        $ip = self::getClientIp();
        
        // Vérifier si l'IP est bloquée
        if (Logger::isIpBlocked($ip)) {
            Logger::logUnauthorizedAccess('BLOCKED_IP', 'IP temporarily blocked');
            return false;
        }
        
        $cacheDir = '../logs/ratelimit/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        
        $cacheFile = $cacheDir . md5($ip) . '.cache';
        $currentTime = time();
        
        $requests = [];
        if (file_exists($cacheFile)) {
            $requests = json_decode(file_get_contents($cacheFile), true) ?? [];
        }
        
        // Nettoyer les requêtes de plus d'une minute
        $oneMinuteAgo = $currentTime - 60;
        $requests = array_filter($requests, function($timestamp) use ($oneMinuteAgo) {
            return $timestamp > $oneMinuteAgo;
        });
        
        // Ajouter la requête actuelle
        $requests[] = $currentTime;
        
        // Sauvegarder
        file_put_contents($cacheFile, json_encode($requests));
        
        // Vérifier la limite
        if (count($requests) > $maxRequests) {
            Logger::logRateLimitExceeded();
            return false;
        }
        
        return true;
    }
    
    /**
     * Obtenir l'IP du client (même derrière un proxy)
     * @return string IP du client
     */
    public static function getClientIp() {
        $ipKeys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($ipKeys as $key) {
            if (isset($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Définir les headers de sécurité HTTP
     */
    public static function setSecurityHeaders() {
        // Empêcher le clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Bloquer le sniffing MIME
        header('X-Content-Type-Options: nosniff');
        
        // Activer la protection XSS du navigateur
        header('X-XSS-Protection: 1; mode=block');
        
        // Référer policy - Ne pas envoyer le référer vers des sites externes
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions Policy (anciennement Feature Policy)
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        
        // Forcer HTTPS en production
        if (($_ENV['ENVIRONMENT'] ?? 'development') === 'production') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
        
        // Content Security Policy renforcée
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com https://cdn.jsdelivr.net https://code.jquery.com https://cdnjs.cloudflare.com",
            "style-src 'self' 'unsafe-inline' https://unpkg.com https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
            "img-src 'self' data: https: http:",
            "connect-src 'self' https://cdn.jsdelivr.net https://unpkg.com https://cdnjs.cloudflare.com https://*.tile.openstreetmap.org",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        header('Content-Security-Policy: ' . implode('; ', $csp));
        
        // Cache control pour les pages sécurisées
        if (isset($_SESSION['user_id'])) {
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }
    }
    
    /**
     * Générer un hash sécurisé pour les mots de passe
     * @param string $password Mot de passe en clair
     * @return string Hash du mot de passe
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Vérifier un mot de passe contre son hash
     * @param string $password Mot de passe en clair
     * @param string $hash Hash stocké
     * @return bool True si le mot de passe correspond
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Régénérer l'ID de session (contre le session fixation)
     */
    public static function regenerateSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
}
