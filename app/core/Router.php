<?php
/**
 * ====================================================================
 * SYND_GEST - Routeur MVC
 * ====================================================================
 * Gère le routage des URLs vers les contrôleurs
 */

class Router {
    
    protected $controller = 'HomeController';
    protected $method = 'index';
    protected $params = [];
    
    /**
     * Constructeur - Parse l'URL et route vers le bon contrôleur
     */
    public function __construct() {
        $url = $this->parseUrl();
        
        // Vérifier si le contrôleur existe
        if (isset($url[0])) {
            $controllerName = ucfirst($url[0]) . 'Controller';
            
            if (file_exists('../app/controllers/' . $controllerName . '.php')) {
                $this->controller = $controllerName;
                unset($url[0]);
            }
        }
        
        // Inclure et instancier le contrôleur
        require_once '../app/controllers/' . $this->controller . '.php';
        $this->controller = new $this->controller;
        
        // Vérifier si la méthode existe
        if (isset($url[1])) {
            if (method_exists($this->controller, $url[1])) {
                $this->method = $url[1];
                unset($url[1]);
            }
        }
        
        // Récupérer les paramètres
        $this->params = $url ? array_values($url) : [];
        
        // Valider les paramètres (sécurité)
        $this->validateParams();
        
        // Vérifier le rate limiting par IP (plus strict)
        if (!Security::checkRateLimitByIp(60)) {
            http_response_code(429);
            die('Trop de requêtes. Veuillez patienter une minute.');
        }
        
        // Appeler la méthode du contrôleur avec les paramètres
        call_user_func_array([$this->controller, $this->method], $this->params);
    }
    
    /**
     * Valider les paramètres de la route
     */
    protected function validateParams() {
        foreach ($this->params as $key => $param) {
            // Si le paramètre ressemble à un ID (nombre), valider qu'il est numérique positif
            if (is_numeric($param)) {
                if (!Security::validateId($param)) {
                    http_response_code(400);
                    die('Paramètre invalide');
                }
            }
            // Nettoyer les paramètres de type chaîne
            else {
                $this->params[$key] = Security::sanitize($param);
            }
        }
    }
    
    /**
     * Parser l'URL
     * 
     * @return array URL parsée
     */
    protected function parseUrl() {
        if (isset($_GET['url'])) {
            $url = rtrim($_GET['url'], '/');
            $url = filter_var($url, FILTER_SANITIZE_URL);
            $url = explode('/', $url);
            return $url;
        }
        return [];
    }
}
