<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur de base
 * ====================================================================
 * Classe de base pour tous les contrôleurs
 */

class Controller {
    
    /**
     * Charger un modèle
     * 
     * @param string $model Nom du modèle
     * @return object Instance du modèle
     */
    protected function model($model) {
        // Classe chargée automatiquement par l'autoloader
        return new $model();
    }
    
    /**
     * Charger une vue
     * 
     * @param string $view Nom de la vue (ex: 'home/index')
     * @param array $data Données à passer à la vue
     * @param bool $useLayout Utiliser le layout principal (true) ou afficher la vue seule (false)
     */
    protected function view($view, $data = [], $useLayout = false) {
        // Extraire les données en variables
        extract($data);
        
        // Vérifier si la vue existe
        $viewPath = '../app/views/' . $view . '.php';
        if (!file_exists($viewPath)) {
            die('La vue ' . $view . ' n\'existe pas');
        }
        
        // Si on utilise le layout
        if ($useLayout) {
            // Capturer le contenu de la vue
            ob_start();
            require_once $viewPath;
            $content = ob_get_clean();
            
            // Charger le layout avec le contenu
            require_once '../app/views/layouts/main.php';
        } else {
            // Afficher la vue directement (sans layout)
            require_once $viewPath;
        }
    }
    
    /**
     * Rediriger vers une URL
     * 
     * @param string $url URL de destination
     */
    protected function redirect($url) {
        // Éviter le doublement si $url contient déjà une URL absolue ou BASE_URL
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            header('Location: ' . $url);
        } elseif (str_starts_with($url, BASE_URL)) {
            header('Location: ' . $url);
        } else {
            header('Location: ' . BASE_URL . '/' . ltrim($url, '/'));
        }
        exit;
    }
    
    /**
     * Définir un message flash
     * 
     * @param string $type Type de message (success, error, warning, info)
     * @param string $message Message
     * @param int $duration Durée d'affichage en millisecondes (par défaut 5000ms)
     */
    protected function setFlash($type, $message, $duration = 5000) {
        $_SESSION['flash'] = [
            'type' => $type,
            'message' => $message,
            'duration' => $duration
        ];
    }
    
    /**
     * Obtenir et supprimer le message flash
     * 
     * @return array|null Message flash
     */
    protected function getFlash() {
        if (isset($_SESSION['flash'])) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
    
    /**
     * Vérifier si l'utilisateur est connecté
     * 
     * @return bool
     */
    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    /**
     * Obtenir l'ID de l'utilisateur connecté
     * 
     * @return int|null
     */
    protected function getUserId() {
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Exiger que l'utilisateur soit connecté
     */
    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->setFlash('error', 'Vous devez être connecté pour accéder à cette page.');
            $this->redirect('auth/login');
        }
        
        // Vérifier le timeout de session (30 minutes d'inactivité)
        $this->checkSessionTimeout();
    }
    
    /**
     * Vérifier le timeout de session (logout automatique après inactivité)
     */
    protected function checkSessionTimeout() {
        $timeout = 1800; // 30 minutes en secondes
        
        // Vérifier si le timestamp de dernière activité existe
        if (isset($_SESSION['last_activity'])) {
            $inactiveTime = time() - $_SESSION['last_activity'];
            
            // Si l'inactivité dépasse 30 minutes
            if ($inactiveTime > $timeout) {
                // Logger la déconnexion automatique
                Logger::logSensitiveAction('AUTO_LOGOUT', [
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'username' => $_SESSION['user_username'] ?? null,
                    'inactive_time' => $inactiveTime,
                    'reason' => 'Session timeout (30 minutes)'
                ]);
                
                // Détruire la session
                session_unset();
                session_destroy();
                
                // Rediriger vers login avec message
                session_start(); // Redémarrer pour le message flash
                $this->setFlash('warning', 'Votre session a expiré après 30 minutes d\'inactivité. Veuillez vous reconnecter.');
                $this->redirect('auth/login');
            }
        }
        
        // Mettre à jour le timestamp de dernière activité
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * Vérifier le token CSRF pour les requêtes POST
     * À appeler dans les méthodes qui traitent des formulaires
     */
    protected function verifyCsrf() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !Security::verifyToken($_POST['csrf_token'])) {
                // Logger la violation CSRF
                Logger::logCsrfViolation();
                
                http_response_code(403);
                $this->setFlash('error', 'Token CSRF invalide. Veuillez recharger la page et réessayer.');
                $this->redirect($_SERVER['HTTP_REFERER'] ?? 'home');
            }
        }
    }
    
    /**
     * Vérifier les permissions (rôle)
     * 
     * @param string|array $roles Rôle(s) autorisé(s)
     * @return bool
     */
    protected function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }
        
        $userRole = $_SESSION['user_role'] ?? null;
        
        if (is_array($roles)) {
            return in_array($userRole, $roles);
        }
        
        return $userRole === $roles;
    }
    
    /**
     * Exiger un rôle spécifique
     * 
     * @param string|array $roles Rôle(s) requis
     */
    protected function requireRole($roles) {
        $this->requireAuth();
        
        if (!$this->hasRole($roles)) {
            // Logger la tentative d'accès non autorisée
            $resource = $_SERVER['REQUEST_URI'] ?? 'unknown';
            $requiredRoles = is_array($roles) ? implode(',', $roles) : $roles;
            $userRole = $_SESSION['user_role'] ?? 'none';
            Logger::logUnauthorizedAccess($resource, "Required: $requiredRoles, Has: $userRole");
            
            $this->setFlash('error', 'Accès refusé. Permissions insuffisantes.');
            $this->redirect('home');
        }
    }
    
    /**
     * Envoyer une réponse JSON
     * 
     * @param mixed $data Données à envoyer
     * @param int $statusCode Code de statut HTTP
     */
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Obtenir une connexion PDO à la base de données via Database Singleton
     * 
     * @return PDO Instance de connexion PDO
     */
    protected function getDbConnection() {
        return Database::getInstance()->getConnection();
    }
}
