<?php
/**
 * ====================================================================
 * SYND_GEST - Configuration Générale de l'Application
 * ====================================================================
 * Chargé après : 1. DotEnv → 2. database.php → 3. config.php
 * Toutes les valeurs configurables lisent $_ENV avec fallback.
 */

// ====================================================================
// DÉTECTION DE L'ENVIRONNEMENT
// ====================================================================

define('IS_PRODUCTION', isset($_ENV['HEROKU_APP_NAME']) || getenv('HEROKU_APP_NAME') || isset($_SERVER['DYNO']));
define('ENVIRONMENT', IS_PRODUCTION ? 'production' : 'development');

// ====================================================================
// CHEMINS FILESYSTEM
// ====================================================================

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH);
define('UPLOADS_PATH', APP_PATH . '/uploads');
define('LOGS_PATH', APP_PATH . '/logs');

// ====================================================================
// URLS
// ====================================================================

if (IS_PRODUCTION) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    define('BASE_URL', $protocol . '://' . $_SERVER['HTTP_HOST']);
} else {
    define('BASE_URL', $_ENV['APP_URL'] ?? 'http://localhost/Synd_Gest/public');
}

define('ASSETS_URL', BASE_URL . '/assets');
define('CSS_URL', ASSETS_URL . '/css');
define('JS_URL', ASSETS_URL . '/js');
define('IMAGES_URL', ASSETS_URL . '/images');
define('UPLOADS_URL', BASE_URL . '/uploads');

// ====================================================================
// APPLICATION
// ====================================================================

define('APP_NAME', $_ENV['APP_NAME'] ?? 'Synd_Gest');
define('APP_VERSION', '1.0.0');

define('DEFAULT_LANGUAGE', 'fr');
define('DEFAULT_TIMEZONE', 'Europe/Paris');
date_default_timezone_set(DEFAULT_TIMEZONE);

// ====================================================================
// API CLAUDE (Anthropic)
// ====================================================================

define('ANTHROPIC_API_KEY', $_ENV['ANTHROPIC_API_KEY'] ?? getenv('ANTHROPIC_API_KEY') ?: '');
define('ANTHROPIC_MODEL', $_ENV['ANTHROPIC_MODEL'] ?? 'claude-sonnet-4-20250514');

// ====================================================================
// SÉCURITÉ
// ====================================================================

if (IS_PRODUCTION) {
    define('SECRET_KEY', getenv('SECRET_KEY') ?: bin2hex(random_bytes(32)));
} else {
    define('SECRET_KEY', $_ENV['SECRET_KEY'] ?? 'synd_gest_local_dev_key_change_in_production');
}

// ====================================================================
// ERREURS ET LOGS
// ====================================================================

if (IS_PRODUCTION) {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
}
ini_set('log_errors', '1');
ini_set('error_log', LOGS_PATH . '/php_errors.log');

define('LOG_LEVEL', $_ENV['LOG_LEVEL'] ?? (IS_PRODUCTION ? 'ERROR' : 'DEBUG'));
define('ENABLE_LOGGING', filter_var($_ENV['ENABLE_LOGGING'] ?? true, FILTER_VALIDATE_BOOLEAN));

// ====================================================================
// UPLOADS
// ====================================================================

define('MAX_UPLOAD_SIZE', (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10));
define('MAX_UPLOAD_SIZE_BYTES', MAX_UPLOAD_SIZE * 1024 * 1024);

define('ALLOWED_DOCUMENT_TYPES', [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'text/plain',
    'text/csv'
]);
define('ALLOWED_DOCUMENT_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv']);

define('ALLOWED_IMAGE_TYPES', [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp'
]);
define('ALLOWED_IMAGE_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ====================================================================
// EMAILS
// ====================================================================

define('MAIL_HOST', $_ENV['MAIL_HOST'] ?? (IS_PRODUCTION ? 'smtp.sendgrid.net' : 'localhost'));
define('MAIL_PORT', (int)($_ENV['MAIL_PORT'] ?? (IS_PRODUCTION ? 587 : 25)));
define('MAIL_USERNAME', $_ENV['MAIL_USERNAME'] ?? '');
define('MAIL_PASSWORD', $_ENV['MAIL_PASSWORD'] ?? '');
define('MAIL_ENCRYPTION', $_ENV['MAIL_ENCRYPTION'] ?? (IS_PRODUCTION ? 'tls' : ''));
define('MAIL_FROM_ADDRESS', $_ENV['MAIL_FROM_ADDRESS'] ?? 'noreply@syndgest.fr');
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? APP_NAME);

// ====================================================================
// FORMATS D'AFFICHAGE
// ====================================================================

define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i');
define('DB_DATE_FORMAT', 'Y-m-d');
define('DB_DATETIME_FORMAT', 'Y-m-d H:i:s');

define('CURRENCY', 'EUR');
define('CURRENCY_SYMBOL', '€');
define('DECIMAL_SEPARATOR', ',');
define('THOUSANDS_SEPARATOR', ' ');
define('DECIMAL_PLACES', 2);

// ====================================================================
// VÉRIFICATION DE L'ENVIRONNEMENT
// ====================================================================

$required_dirs = [
    LOGS_PATH,
    UPLOADS_PATH . '/documents',
    UPLOADS_PATH . '/photos',
    UPLOADS_PATH . '/temp',
    APP_PATH . '/cache'
];

foreach ($required_dirs as $dir) {
    if (!file_exists($dir)) {
        @mkdir($dir, 0755, true);
    }
}

$uploads_htaccess = UPLOADS_PATH . '/.htaccess';
if (!file_exists($uploads_htaccess)) {
    file_put_contents($uploads_htaccess, "Options -Indexes\n");
}
