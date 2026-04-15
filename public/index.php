<?php
/**
 * ====================================================================
 * SYND_GEST - Point d'entrée principal (Front Controller)
 * ====================================================================
 */

// Encodage UTF-8 global — doit être avant tout output
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
header('Content-Type: text/html; charset=UTF-8');

// Configuration de la session : expire à la fermeture du navigateur
ini_set('session.cookie_lifetime', 0); // 0 = expire à la fermeture du navigateur
ini_set('session.gc_maxlifetime', 1800); // 30 minutes d'inactivité max sur le serveur
ini_set('session.cookie_httponly', 1); // Protection XSS
ini_set('session.cookie_secure', 0); // Mettre à 1 en HTTPS
ini_set('session.use_strict_mode', 1); // Sécurité renforcée

// Démarrer la session
session_start();

// Autoloader PSR-4 — charge automatiquement les classes depuis app/core, app/models, app/controllers
require_once '../app/core/Autoloader.php';
Autoloader::register(dirname(__DIR__));

// Vérifier si l'IP est bloquée
if (Logger::isIpBlocked()) {
    http_response_code(403);
    die('Votre IP est temporairement bloquée. Réessayez dans 1 heure.');
}

// Définir les headers de sécurité
Security::setSecurityHeaders();

// Générer un token CSRF si nécessaire
if (!isset($_SESSION['csrf_token'])) {
    Security::generateToken();
}

// Charger le fichier .env si disponible (local development)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    try {
        $dotenv = new DotEnv($envFile);
        $dotenv->load();
    } catch (Exception $e) {
        // En production, ignorer l'erreur (les variables sont dans l'environnement)
        if ($_ENV['ENVIRONMENT'] ?? 'production' !== 'production') {
            die('Erreur de chargement du fichier .env: ' . $e->getMessage());
        }
    }
}

// Charger la configuration dans le bon ordre
// 1. database.php (définit les constantes DB_* avec $_ENV)
require_once '../config/database.php';

// 2. constants.php (constantes métier indépendantes)
require_once '../config/constants.php';

// 3. config.php (configuration générale, chemins, etc.)
require_once '../config/config.php';

// Charger les helpers (pas des classes, require_once nécessaire)
require_once '../app/helpers/functions.php';

// Initialiser le routeur
$router = new Router();
