/**
 * ====================================================================
 * SYND_GEST - Déconnexion automatique à la fermeture d'onglet
 * ====================================================================
 * Utilise l'API Beacon pour envoyer une requête de déconnexion
 * même si l'onglet/fenêtre est en train de se fermer
 */

(function() {
    'use strict';
    
    // Vérifier que l'utilisateur est connecté
    if (typeof IS_LOGGED_IN === 'undefined' || !IS_LOGGED_IN) {
        return;
    }
    
    // Utiliser sessionStorage pour détecter la fermeture de l'onglet
    // (sessionStorage persiste tant que l'onglet est ouvert)
    const SESSION_KEY = 'synd_gest_session_active';
    
    // Marquer la session comme active au chargement de la page
    sessionStorage.setItem(SESSION_KEY, 'true');
    
    /**
     * Fonction de déconnexion silencieuse
     */
    function logoutSilent() {
        // Utiliser navigator.sendBeacon pour envoyer la requête même si la page se ferme
        const url = BASE_URL + '/auth/logoutSilent';
        const data = new FormData();
        data.append('action', 'logout');
        
        // sendBeacon garantit l'envoi même si la page se ferme immédiatement
        if (navigator.sendBeacon) {
            navigator.sendBeacon(url, data);
        } else {
            // Fallback pour navigateurs anciens (requête synchrone - DÉPRÉCIÉ mais nécessaire)
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url, false); // false = synchrone
            xhr.send(data);
        }
    }
    
    /**
     * Événement avant la fermeture de la page
     */
    window.addEventListener('beforeunload', function(e) {
        // Vérifier si c'est vraiment une fermeture d'onglet et pas juste une navigation
        // Si sessionStorage est vide, l'onglet est en train de se fermer
        if (sessionStorage.getItem(SESSION_KEY) === 'true') {
            logoutSilent();
        }
    });
    
    /**
     * Événement à la fermeture de la page
     */
    window.addEventListener('unload', function(e) {
        logoutSilent();
    });
    
    /**
     * Événement de visibilité (optionnel - détecte le changement d'onglet)
     */
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            // L'onglet est caché (changement d'onglet ou minimisation)
            // On ne déconnecte PAS ici, seulement à la fermeture
        }
    });
    
    /**
     * Nettoyer sessionStorage avant de quitter
     */
    window.addEventListener('pagehide', function(e) {
        // Vérifier si l'onglet persiste (back/forward cache)
        if (!e.persisted) {
            // L'onglet ne persiste pas = fermeture réelle
            logoutSilent();
            sessionStorage.removeItem(SESSION_KEY);
        }
    });
    
    /**
     * Détecter le rechargement de la page (F5)
     * Ne PAS déconnecter dans ce cas
     */
    window.addEventListener('load', function() {
        // Vérifier si c'est un rechargement
        const navigationEntries = performance.getEntriesByType('navigation');
        if (navigationEntries.length > 0) {
            const navEntry = navigationEntries[0];
            if (navEntry.type === 'reload') {
                // C'est un rechargement, ne pas déconnecter
                sessionStorage.setItem(SESSION_KEY, 'true');
                return;
            }
        }
        
        // Première visite ou navigation normale
        sessionStorage.setItem(SESSION_KEY, 'true');
    });
    
    console.log('[Synd_Gest] Protection fermeture d\'onglet activée');
})();
