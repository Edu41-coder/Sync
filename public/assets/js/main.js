/**
 * ====================================================================
 * SYND_GEST - JavaScript principal
 * ====================================================================
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-fermeture des alertes selon leur durée flash
    //
    // Règle :
    //   - Alertes hors modal : timer démarré au chargement de page (cas flash messages classiques)
    //   - Alertes DANS un modal : timer démarré à l'ouverture du modal (shown.bs.modal)
    //                              et annulé à sa fermeture (hidden.bs.modal). Permet à
    //                              l'utilisateur de voir l'alerte à chaque ouverture, même
    //                              s'il a chargé la page longtemps avant d'ouvrir le modal.
    //   - Classe `alert-permanent` : alerte jamais auto-fermée (cas très spécifiques).

    function getFlashDuration(alertEl) {
        const v = parseInt(alertEl.getAttribute('data-flash-duration') || '5000', 10);
        return Number.isFinite(v) && v > 0 ? v : 0;
    }

    function closeAlert(alertEl) {
        if (!alertEl.parentNode) return; // déjà retirée
        try {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            bsAlert.close();
        } catch (e) { /* DOM possiblement détaché */ }
    }

    // 1) Alertes hors modal — timer immédiat
    document.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
        if (alert.closest('.modal')) return; // gérées plus bas
        const duration = getFlashDuration(alert);
        if (duration > 0) setTimeout(() => closeAlert(alert), duration);
    });

    // 2) Alertes dans des modals — timer démarré à l'ouverture du modal
    document.querySelectorAll('.modal').forEach(modal => {
        // Mémorise le HTML d'origine de chaque alerte pour pouvoir la restaurer
        // si elle a été fermée (sortie du DOM par bsAlert.close()) lors d'une
        // ouverture précédente du modal.
        const alertOriginals = new WeakMap();
        const containers = modal.querySelectorAll('.alert:not(.alert-permanent)');
        containers.forEach(alert => {
            alertOriginals.set(alert, { html: alert.outerHTML, parent: alert.parentNode, next: alert.nextSibling });
        });

        let pendingTimers = [];

        modal.addEventListener('show.bs.modal', function () {
            // Restaurer les alertes manquantes (fermées lors d'une précédente ouverture)
            containers.forEach(alert => {
                const meta = alertOriginals.get(alert);
                if (meta && !alert.parentNode && meta.parent) {
                    // Reconstruire l'alerte à partir du HTML mémorisé
                    const tmp = document.createElement('div');
                    tmp.innerHTML = meta.html;
                    const restored = tmp.firstElementChild;
                    if (restored) {
                        meta.parent.insertBefore(restored, meta.next);
                        // Remplacer la référence dans la NodeList par le nouvel élément
                        alertOriginals.set(restored, meta);
                    }
                }
            });
        });

        modal.addEventListener('shown.bs.modal', function () {
            pendingTimers.forEach(clearTimeout);
            pendingTimers = [];
            // Re-query pour avoir les éventuels éléments restaurés
            modal.querySelectorAll('.alert:not(.alert-permanent)').forEach(alert => {
                const duration = getFlashDuration(alert);
                if (duration > 0) {
                    pendingTimers.push(setTimeout(() => closeAlert(alert), duration));
                }
            });
        });

        modal.addEventListener('hidden.bs.modal', function () {
            pendingTimers.forEach(clearTimeout);
            pendingTimers = [];
        });
    });
    
    // Toggle password visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const input = document.querySelector(this.getAttribute('data-target'));
            const icon = this.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
    
    // Validation de formulaire Bootstrap
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', function(event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
    
    // Confirmation avant suppression
    const deleteButtons = document.querySelectorAll('[data-confirm-delete]');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(event) {
            const message = this.getAttribute('data-confirm-delete') || 
                          'Êtes-vous sûr de vouloir supprimer cet élément ?';
            if (!confirm(message)) {
                event.preventDefault();
            }
        });
    });
    
});

// Fonction pour afficher une notification toast
function showToast(message, type = 'info') {
    // TODO: Implémenter les toasts Bootstrap
    console.log(`[${type}] ${message}`);
}

// Fonction pour confirmer une action
function confirmAction(message) {
    return confirm(message);
}
