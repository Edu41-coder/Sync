<?php
/**
 * ====================================================================
 * SYND_GEST - Configuration Générale de l'Application
 * ====================================================================
 * Configuration principale pour l'environnement local et production
 * 
 * @author Synd_Gest Team
 * @version 1.0
 * @date 2025-11-29
 */

// Note: La session est déjà démarrée dans index.php

// ====================================================================
// DÉTECTION DE L'ENVIRONNEMENT
// ====================================================================

// Détecter si on est en production (Heroku) ou en local (XAMPP)
define('IS_PRODUCTION', isset($_ENV['HEROKU_APP_NAME']) || getenv('HEROKU_APP_NAME') || isset($_SERVER['DYNO']));
define('ENVIRONMENT', IS_PRODUCTION ? 'production' : 'development');

// ====================================================================
// CONFIGURATION DES CHEMINS
// ====================================================================

// Chemin racine du projet
define('ROOT_PATH', dirname(__DIR__));

// Chemin absolu (filesystem)
define('APP_PATH', ROOT_PATH);
define('CONFIG_PATH', APP_PATH . '/config');
define('CLASSES_PATH', APP_PATH . '/classes');
define('INCLUDES_PATH', APP_PATH . '/includes');
define('MODULES_PATH', APP_PATH . '/modules');
define('UPLOADS_PATH', APP_PATH . '/uploads');
define('TEMPLATES_PATH', APP_PATH . '/templates');
define('LOGS_PATH', APP_PATH . '/logs');

// URL de base (pour les liens) - Architecture MVC
if (IS_PRODUCTION) {
    // En production, utiliser l'URL Heroku
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    define('BASE_URL', $protocol . '://' . $host);
} else {
    // En local, utiliser localhost avec le dossier public
    define('BASE_URL', 'http://localhost/Synd_Gest/public');
}

// URLs des ressources
define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');
define('UPLOADS_URL', BASE_URL . '/uploads');

// ====================================================================
// CONFIGURATION DE L'APPLICATION
// ====================================================================

// Informations de l'application
define('APP_NAME', 'Synd_Gest');
define('APP_VERSION', '1.0.0');
define('APP_DESCRIPTION', 'Logiciel de Gestion Immobilière et Syndic');
define('APP_AUTHOR', 'Synd_Gest Team');

// Langue par défaut
define('DEFAULT_LANGUAGE', 'fr');
define('DEFAULT_TIMEZONE', 'Europe/Paris');

// Définir le fuseau horaire
date_default_timezone_set(DEFAULT_TIMEZONE);

// ====================================================================
// CONFIGURATION DE SÉCURITÉ
// ====================================================================

// Clé secrète pour le hashage (IMPORTANT: changer en production)
if (IS_PRODUCTION) {
    define('SECRET_KEY', getenv('SECRET_KEY') ?: bin2hex(random_bytes(32)));
} else {
    define('SECRET_KEY', 'synd_gest_local_secret_key_change_in_production');
}

// Salt pour les tokens CSRF
define('CSRF_SALT', 'synd_gest_csrf_salt_2025');

// Durée de vie de la session (en secondes) - 2 heures par défaut
define('SESSION_LIFETIME', 7200);

// Durée d'inactivité avant déconnexion automatique (en secondes) - 30 minutes
define('SESSION_TIMEOUT', 1800);

// Activer la protection CSRF
define('CSRF_PROTECTION', true);

// Domaine des cookies (vide pour le domaine actuel)
define('COOKIE_DOMAIN', '');

// Chemin des cookies
define('COOKIE_PATH', '/');

// Cookies sécurisés (HTTPS uniquement)
define('COOKIE_SECURE', IS_PRODUCTION);

// Cookies HTTP only (protection XSS)
define('COOKIE_HTTPONLY', true);

// SameSite cookie attribute
define('COOKIE_SAMESITE', 'Lax');

// ====================================================================
// CONFIGURATION DES ERREURS ET LOGS
// ====================================================================

if (IS_PRODUCTION) {
    // En production : masquer les erreurs, les logger
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
} else {
    // En développement : afficher toutes les erreurs
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    ini_set('log_errors', '1');
    ini_set('error_log', LOGS_PATH . '/php_errors.log');
}

// Niveau de log (DEBUG, INFO, WARNING, ERROR, CRITICAL)
define('LOG_LEVEL', IS_PRODUCTION ? 'ERROR' : 'DEBUG');

// Activer les logs d'application
define('ENABLE_LOGGING', true);

// Fichier de log général
define('APP_LOG_FILE', LOGS_PATH . '/app.log');

// Fichier de log des accès
define('ACCESS_LOG_FILE', LOGS_PATH . '/access.log');

// ====================================================================
// CONFIGURATION DES UPLOADS
// ====================================================================

// Taille maximale des fichiers uploadés (en Mo)
define('MAX_UPLOAD_SIZE', 10);

// Taille maximale en bytes
define('MAX_UPLOAD_SIZE_BYTES', MAX_UPLOAD_SIZE * 1024 * 1024);

// Types MIME autorisés pour les documents
define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv'
]);

// Extensions autorisées pour les documents
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv']);

// Types MIME autorisés pour les images
define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]);

// Extensions autorisées pour les images
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ====================================================================
// CONFIGURATION DE LA PAGINATION
// ====================================================================

// Nombre d'éléments par page par défaut
define('ITEMS_PER_PAGE', 20);

// Options de pagination disponibles
define('PAGINATION_OPTIONS', [10, 20, 50, 100]);

// ====================================================================
// CONFIGURATION DES EMAILS
// ====================================================================

if (IS_PRODUCTION) {
    // Configuration email production (utiliser un service comme SendGrid sur Heroku)
    define('MAIL_HOST', getenv('MAIL_HOST') ?: 'smtp.sendgrid.net');
    define('MAIL_PORT', getenv('MAIL_PORT') ?: 587);
    define('MAIL_USERNAME', getenv('SENDGRID_USERNAME') ?: '');
    define('MAIL_PASSWORD', getenv('SENDGRID_PASSWORD') ?: '');
    define('MAIL_ENCRYPTION', 'tls');
} else {
    // Configuration email local (XAMPP avec Mercury ou MailHog)
    define('MAIL_HOST', 'localhost');
    define('MAIL_PORT', 25);
    define('MAIL_USERNAME', '');
    define('MAIL_PASSWORD', '');
    define('MAIL_ENCRYPTION', '');
}

// Email de l'expéditeur par défaut
define('MAIL_FROM_ADDRESS', 'noreply@syndgest.fr');
define('MAIL_FROM_NAME', APP_NAME);

// Email de l'administrateur
define('ADMIN_EMAIL', 'admin@syndgest.fr');

// ====================================================================
// CONFIGURATION DES FORMATS
// ====================================================================

// Format de date pour l'affichage
define('DATE_FORMAT', 'd/m/Y');

// Format de date et heure pour l'affichage
define('DATETIME_FORMAT', 'd/m/Y H:i');

// Format de date pour la base de données
define('DB_DATE_FORMAT', 'Y-m-d');

// Format de date et heure pour la base de données
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

// Devise par défaut
define('CURRENCY', 'EUR');
define('CURRENCY_SYMBOL', '€');

// Séparateur décimal
define('DECIMAL_SEPARATOR', ',');

// Séparateur de milliers
define('THOUSANDS_SEPARATOR', ' ');

// Nombre de décimales pour les montants
define('DECIMAL_PLACES', 2);

// ====================================================================
// CONFIGURATION DES NOTIFICATIONS
// ====================================================================

// Durée d'affichage des notifications (en millisecondes)
define('NOTIFICATION_DURATION', 5000);

// Activer les notifications par email
define('EMAIL_NOTIFICATIONS', true);

// Activer les notifications dans l'application
define('APP_NOTIFICATIONS', true);

// ====================================================================
// CONFIGURATION DES RÔLES ET PERMISSIONS
// ====================================================================

// Rôles disponibles
define('USER_ROLES', [
    'admin' => 'Administrateur',
    'gestionnaire' => 'Gestionnaire',
    'coproprietaire' => 'Copropriétaire',
    'locataire' => 'Locataire'
]);

// Rôle par défaut pour les nouveaux utilisateurs
define('DEFAULT_ROLE', 'coproprietaire');

// ====================================================================
// CONFIGURATION DE LA COMPTABILITÉ
// ====================================================================

// Trimestres pour les appels de fonds
define('QUARTERS', [
    1 => 'T1 (Janvier - Mars)',
    2 => 'T2 (Avril - Juin)',
    3 => 'T3 (Juillet - Septembre)',
    4 => 'T4 (Octobre - Décembre)'
]);

// TVA par défaut (en %)
define('DEFAULT_VAT', 20);

// ====================================================================
// CONFIGURATION DU CACHE
// ====================================================================

// Activer le cache
define('CACHE_ENABLED', IS_PRODUCTION);

// Durée du cache (en secondes) - 1 heure
define('CACHE_DURATION', 3600);

// Chemin du cache
define('CACHE_PATH', APP_PATH . '/cache');

// ====================================================================
// MODE MAINTENANCE
// ====================================================================

// Activer le mode maintenance
define('MAINTENANCE_MODE', false);

// Message du mode maintenance
define('MAINTENANCE_MESSAGE', 'Le site est actuellement en maintenance. Veuillez réessayer plus tard.');

// IPs autorisées pendant la maintenance (administrateurs)
define('MAINTENANCE_ALLOWED_IPS', ['127.0.0.1', '::1']);

// ====================================================================
// CONFIGURATION API
// ====================================================================

// Activer l'API REST
define('API_ENABLED', true);

// Version de l'API
define('API_VERSION', 'v1');

// Préfixe de l'API
define('API_PREFIX', '/api/' . API_VERSION);

// Limite de requêtes par minute (rate limiting)
define('API_RATE_LIMIT', 60);

// ====================================================================
// INCLURE LES AUTRES FICHIERS DE CONFIGURATION
// ====================================================================

// Note: database.php et constants.php sont déjà chargés dans index.php
// avant ce fichier pour respecter l'ordre optimal :
// 1. DotEnv -> 2. database.php -> 3. constants.php -> 4. config.php

// ====================================================================
// VÉRIFICATION DE L'ENVIRONNEMENT
// ====================================================================

// Créer les dossiers nécessaires s'ils n'existent pas
$required_dirs = [
    LOGS_PATH,
    UPLOADS_PATH . '/documents',
    UPLOADS_PATH . '/photos',
    UPLOADS_PATH . '/temp',
    CACHE_PATH
];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

// Créer le fichier .htaccess dans uploads pour protéger les fichiers sensibles
$uploads_htaccess = UPLOADS_PATH . '/.htaccess';
if (!file_exists($uploads_htaccess)) {
    file_put_contents($uploads_htaccess, "Options -Indexes\n");
}

// ====================================================================
// FIN DE LA CONFIGURATION
// ====================================================================
