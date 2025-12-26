/**
 * ====================================================================
 * SYND_GEST - JavaScript principal
 * ====================================================================
 */

// Attendre que le DOM soit chargé
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto-fermeture des alertes après 5 secondes
    const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
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
