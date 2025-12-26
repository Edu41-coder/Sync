<?php
/**
 * Helpers - Fonctions utilitaires globales
 */

/**
 * Générer le champ hidden pour le token CSRF
 * @return string HTML du champ hidden
 */
function csrf_field() {
    $token = Security::getToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Obtenir le token CSRF actuel
 * @return string Token CSRF
 */
function csrf_token() {
    return Security::getToken();
}

/**
 * Vérifier le token CSRF depuis la requête POST
 * @return bool True si le token est valide
 */
function verify_csrf() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_POST['csrf_token'])) {
            return false;
        }
        return Security::verifyToken($_POST['csrf_token']);
    }
    return true; // GET requests don't need CSRF
}

/**
 * Redirection avec vérification CSRF
 * Lance une exception si le CSRF est invalide
 */
function require_csrf() {
    if (!verify_csrf()) {
        http_response_code(403);
        die('Token CSRF invalide. Veuillez recharger la page et réessayer.');
    }
}

/**
 * Nettoyer et échapper une valeur pour l'affichage
 * @param mixed $value Valeur à échapper
 * @return string Valeur échappée
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Valider un ID
 * @param mixed $id ID à valider
 * @return bool
 */
function is_valid_id($id) {
    return Security::validateId($id);
}

/**
 * Obtenir une valeur POST nettoyée
 * @param string $key Clé du tableau POST
 * @param mixed $default Valeur par défaut
 * @return mixed Valeur nettoyée
 */
function post($key, $default = null) {
    if (!isset($_POST[$key])) {
        return $default;
    }
    return Security::sanitize($_POST[$key]);
}

/**
 * Obtenir une valeur GET nettoyée
 * @param string $key Clé du tableau GET
 * @param mixed $default Valeur par défaut
 * @return mixed Valeur nettoyée
 */
function get($key, $default = null) {
    if (!isset($_GET[$key])) {
        return $default;
    }
    return Security::sanitize($_GET[$key]);
}

/**
 * Générer une URL avec le BASE_URL
 * @param string $path Chemin relatif
 * @return string URL complète
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

/**
 * Générer une URL d'asset
 * @param string $path Chemin relatif de l'asset
 * @return string URL complète
 */
function asset($path) {
    return BASE_URL . '/assets/' . ltrim($path, '/');
}

/**
 * Vérifier si l'utilisateur est connecté
 * @return bool
 */
function is_authenticated() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Vérifier si l'utilisateur a un rôle spécifique
 * @param string|array $roles Rôle(s) à vérifier
 * @return bool
 */
function has_role($roles) {
    if (!is_authenticated()) {
        return false;
    }
    
    if (is_array($roles)) {
        return in_array($_SESSION['role'], $roles);
    }
    
    return $_SESSION['role'] === $roles;
}

/**
 * Obtenir l'utilisateur connecté
 * @return array|null Données de l'utilisateur ou null
 */
function auth_user() {
    if (!is_authenticated()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'nom' => $_SESSION['nom'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role']
    ];
}

/**
 * Formater un montant en euros
 * @param float $amount Montant
 * @param int $decimals Nombre de décimales
 * @return string Montant formaté
 */
function format_currency($amount, $decimals = 2) {
    return number_format($amount, $decimals, ',', ' ') . ' €';
}

/**
 * Formater une date
 * @param string $date Date à formater
 * @param string $format Format de sortie
 * @return string Date formatée
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Calculer le pourcentage
 * @param float $value Valeur
 * @param float $total Total
 * @param int $decimals Nombre de décimales
 * @return float Pourcentage
 */
function percentage($value, $total, $decimals = 2) {
    if ($total == 0) {
        return 0;
    }
    return round(($value / $total) * 100, $decimals);
}

/**
 * Obtenir une classe CSS pour un badge selon le statut
 * @param string $status Statut
 * @return string Classe CSS
 */
function status_badge_class($status) {
    $classes = [
        'actif' => 'bg-success',
        'inactif' => 'bg-secondary',
        'en_attente' => 'bg-warning',
        'occupe' => 'bg-info',
        'libre' => 'bg-light text-dark',
        'paye' => 'bg-success',
        'impaye' => 'bg-danger',
        'en_cours' => 'bg-warning',
        'termine' => 'bg-secondary'
    ];
    
    return $classes[strtolower($status)] ?? 'bg-secondary';
}

/**
 * Déboguer une variable (affichage formaté)
 * @param mixed $var Variable à déboguer
 * @param bool $die Arrêter l'exécution après l'affichage
 */
function dd($var, $die = true) {
    echo '<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">';
    var_dump($var);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}
