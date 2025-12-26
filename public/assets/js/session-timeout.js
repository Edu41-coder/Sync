/**
 * Gestion du timeout de session avec avertissement visuel
 * Déconnexion automatique après 30 minutes d'inactivité
 */

(function() {
    'use strict';
    
    // Configuration
    const TIMEOUT_MINUTES = 30; // 30 minutes
    const WARNING_MINUTES = 5;  // Avertir 5 minutes avant
    const TIMEOUT_MS = TIMEOUT_MINUTES * 60 * 1000;
    const WARNING_MS = WARNING_MINUTES * 60 * 1000;
    
    let timeoutTimer;
    let warningTimer;
    let warningShown = false;
    
    /**
     * Réinitialiser les timers d'inactivité
     */
    function resetTimers() {
        // Effacer les timers existants
        clearTimeout(timeoutTimer);
        clearTimeout(warningTimer);
        
        // Réinitialiser l'avertissement
        if (warningShown) {
            closeWarning();
        }
        
        // Définir le timer d'avertissement (25 minutes)
        warningTimer = setTimeout(showWarning, TIMEOUT_MS - WARNING_MS);
        
        // Définir le timer de timeout (30 minutes)
        timeoutTimer = setTimeout(logout, TIMEOUT_MS);
    }
    
    /**
     * Afficher l'avertissement de timeout imminent
     */
    function showWarning() {
        if (warningShown) return;
        
        warningShown = true;
        
        // Créer le toast d'avertissement
        const toast = document.createElement('div');
        toast.id = 'session-timeout-warning';
        toast.className = 'toast align-items-center text-white bg-warning border-0 position-fixed top-0 end-0 m-3';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        toast.style.zIndex = '9999';
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Session expirée dans ${WARNING_MINUTES} minutes</strong>
                    <br>
                    <small>Cliquez n'importe où pour rester connecté</small>
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        
        // Afficher le toast avec Bootstrap
        const bsToast = new bootstrap.Toast(toast, {
            autohide: false
        });
        bsToast.show();
        
        // Jouer un son d'alerte si disponible
        try {
            const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBjGH0fPTgjMGHm7A7+OZUQ4NTKXh8bllHgU7k9n0xnkpBSl+zPLaizsIGGS57OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIAU4lNr1z3YnBSl+zPLajzsIGGO47OihUhELTqfi8LtpIA==');
            audio.play().catch(() => {});
        } catch(e) {}
    }
    
    /**
     * Fermer l'avertissement
     */
    function closeWarning() {
        const toast = document.getElementById('session-timeout-warning');
        if (toast) {
            const bsToast = bootstrap.Toast.getInstance(toast);
            if (bsToast) {
                bsToast.hide();
            }
            setTimeout(() => toast.remove(), 500);
        }
        warningShown = false;
    }
    
    /**
     * Déconnexion automatique
     */
    function logout() {
        // Afficher un message
        alert('Votre session a expiré après 30 minutes d\'inactivité. Vous allez être déconnecté.');
        
        // Rediriger vers la page de logout
        window.location.href = BASE_URL + '/auth/logout';
    }
    
    /**
     * Événements qui réinitialisent le timer
     */
    const events = [
        'mousedown',
        'mousemove',
        'keypress',
        'scroll',
        'touchstart',
        'click'
    ];
    
    /**
     * Initialiser le système de timeout
     */
    function init() {
        // Vérifier que l'utilisateur est connecté
        if (typeof IS_LOGGED_IN !== 'undefined' && IS_LOGGED_IN) {
            // Démarrer les timers
            resetTimers();
            
            // Attacher les événements avec debounce pour éviter trop d'appels
            let debounceTimer;
            events.forEach(function(event) {
                document.addEventListener(event, function() {
                    clearTimeout(debounceTimer);
                    debounceTimer = setTimeout(function() {
                        resetTimers();
                    }, 1000); // Réinitialiser max 1 fois par seconde
                }, true);
            });
            
            console.log('✓ Session timeout activé (30 minutes d\'inactivité)');
        }
    }
    
    // Initialiser quand le DOM est prêt
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
})();
