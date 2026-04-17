<?php
/**
 * Helpers - Fonctions utilitaires globales
 * Uniquement les fonctions qui ne font pas doublon avec Controller/Security
 */

/**
 * Générer le champ hidden pour le token CSRF
 * Utilisé dans les formulaires des vues
 */
function csrf_field() {
    $token = Security::getToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Échapper une valeur pour l'affichage HTML
 * Raccourci pour htmlspecialchars() dans les vues
 */
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Formater un montant en euros
 */
function format_currency($amount, $decimals = 2) {
    return number_format($amount, $decimals, ',', ' ') . ' €';
}

/**
 * Formater une date (gère les dates nulles/invalides)
 */
function format_date($date, $format = 'd/m/Y') {
    if (empty($date) || $date === '0000-00-00') {
        return '-';
    }
    $timestamp = strtotime($date);
    return $timestamp ? date($format, $timestamp) : '-';
}

/**
 * Déboguer une variable (développement uniquement)
 */
function dd($var, $die = true) {
    echo '<pre style="background: #f5f5f5; padding: 15px; border: 1px solid #ddd; border-radius: 4px;">';
    var_dump($var);
    echo '</pre>';
    if ($die) {
        die();
    }
}
