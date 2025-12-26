<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <!-- Logo et nom de l'application -->
        <span class="navbar-brand d-flex align-items-center">
            <img src="<?= BASE_URL ?>/assets/images/domitys-logo.svg" alt="Domitys Logo" class="navbar-logo me-2" style="height: 35px; filter: brightness(0) invert(1);">
            <strong><?php echo APP_NAME; ?></strong>
        </span>
        
        <!-- Bouton toggle pour mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menu de navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu principal (gauche) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">
                        <i class="fas fa-home me-1 text-white"></i> Tableau de bord
                    </a>
                </li>
                
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'gestionnaire'])): ?>
                
                <!-- Menu Copropriétés -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navCoproprietes" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-building me-1"></i> Copropriétés
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navCoproprietes">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/copropriete/index">
                            <i class="fas fa-list me-2"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/copropriete/create">
                            <i class="fas fa-plus me-2"></i> Nouvelle copropriété
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Lots -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navLots" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-door-open me-1"></i> Lots
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navLots">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/lot/index">
                            <i class="fas fa-list me-2"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/lot/create">
                            <i class="fas fa-plus me-2"></i> Nouveau lot
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Copropriétaires -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navCoproprietaires" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-users me-1"></i> Copropriétaires
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navCoproprietaires">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/index">
                            <i class="fas fa-list me-2"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/create">
                            <i class="fas fa-user-plus me-2"></i> Nouveau copropriétaire
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Comptabilité -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navComptabilite" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calculator me-1"></i> Comptabilité
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navComptabilite">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/charge/index">
                            <i class="fas fa-file-invoice-dollar me-2"></i> Appels de fonds
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/comptabilite/ecritures">
                            <i class="fas fa-book me-2"></i> Écritures
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/comptabilite/balance">
                            <i class="fas fa-chart-bar me-2"></i> Balance
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Travaux -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/travaux/index">
                        <i class="fas fa-tools me-1"></i> Travaux
                    </a>
                </li>
                
                <!-- Menu Documents -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/document/index">
                        <i class="fas fa-folder me-1"></i> Documents
                    </a>
                </li>
                
                <?php endif; ?>
            </ul>
            
            <!-- Menu utilisateur (droite) -->
            <ul class="navbar-nav ms-auto">
                <!-- Notifications -->
                <li class="nav-item dropdown">
                    <a class="nav-link d-flex align-items-center" href="#" id="navNotifications" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-bell"></i>
                        <span class="badge rounded-pill bg-danger ms-2" style="font-size: 0.65rem;">
                            3
                            <span class="visually-hidden">notifications non lues</span>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end dropdown-notifications" aria-labelledby="navNotifications">
                        <li><h6 class="dropdown-header">Notifications</h6></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item small text-nowrap" href="#">
                            <i class="fas fa-info-circle me-2" style="color: #0dcaf0;"></i>
                            Nouvelle AG planifiée
                        </a></li>
                        <li><a class="dropdown-item small text-nowrap" href="#">
                            <i class="fas fa-exclamation-triangle me-2" style="color: #ffc107;"></i>
                            Paiement en retard
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-center small" href="#" style="color: #dc3545;">Voir toutes</a></li>
                    </ul>
                </li>
                
                <!-- Profil utilisateur -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navUser" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-user-circle me-2"></i>
                        <span><?php echo $_SESSION['user_username'] ?? 'Utilisateur'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navUser">
                        <li><h6 class="dropdown-header">
                            <?php echo $_SESSION['user_prenom'] ?? ''; ?> 
                            <?php echo $_SESSION['user_nom'] ?? ''; ?>
                        </h6></li>
                        <li><small class="dropdown-item-text text-muted text-nowrap">
                            <i class="fas fa-shield-alt me-1"></i>
                            <?php 
                            $role = $_SESSION['user_role'] ?? 'user';
                            $roleNames = [
                                'admin' => 'Administrateur',
                                'gestionnaire' => 'Gestionnaire',
                                'coproprietaire' => 'Copropriétaire',
                                'locataire' => 'Locataire'
                            ];
                            echo $roleNames[$role] ?? $role;
                            ?>
                        </small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/user/profile">
                            <i class="fas fa-user me-2"></i> Mon profil
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/user/settings">
                            <i class="fas fa-cog me-2"></i> Paramètres
                        </a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/logs">
                            <i class="fas fa-shield-alt me-2 text-danger"></i> Logs de Sécurité
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger text-nowrap" href="#" onclick="event.preventDefault(); confirmLogout();">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Modal de confirmation de déconnexion -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" role="dialog" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt me-2"></i>Confirmation de déconnexion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-power-off fa-3x text-danger mb-3"></i>
                    <h5 class="text-dark">Êtes-vous sûr de vouloir vous déconnecter ?</h5>
                </div>
                <div class="alert alert-info border-0" style="background-color: #cff4fc;">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    <strong>Information :</strong> Votre session sera terminée.
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Par mesure de sécurité, pensez à vous déconnecter lorsque vous n'utilisez pas l'application.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmLogoutBtn">
                    <i class="fas fa-sign-out-alt me-1"></i>Se déconnecter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variable globale pour stocker l'instance du modal
let logoutModalInstance = null;

// Ouvrir le modal de confirmation de déconnexion
function confirmLogout() {
    // Nettoyer tous les backdrops existants avant d'ouvrir le modal
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(function(backdrop) {
        backdrop.remove();
    });
    
    // Fermer le menu navbar sur mobile avant d'ouvrir le modal
    const navbarCollapse = document.getElementById('navbarNav');
    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
        if (bsCollapse) {
            bsCollapse.hide();
        }
    }
    
    const modalElement = document.getElementById('logoutModal');
    
    // Détruire l'instance précédente si elle existe
    if (logoutModalInstance) {
        logoutModalInstance.dispose();
        logoutModalInstance = null;
    }
    
    // Créer une nouvelle instance propre
    logoutModalInstance = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Petit délai pour s'assurer que le menu est fermé
    setTimeout(function() {
        logoutModalInstance.show();
    }, 300);
}

// Confirmer la déconnexion et gérer les événements du modal
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas chargé !');
        return;
    }
    
    // CRITIQUE : Nettoyer tous les backdrops résiduels au chargement
    const cleanupBackdrops = function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(backdrop) {
            if (!backdrop.classList.contains('show')) {
                backdrop.remove();
            }
        });
    };
    
    // Nettoyer immédiatement
    cleanupBackdrops();
    
    // Nettoyer à nouveau après un court délai
    setTimeout(cleanupBackdrops, 100);
    
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const logoutModalElement = document.getElementById('logoutModal');
    
    // S'assurer que le modal est complètement caché au démarrage
    if (logoutModalElement) {
        logoutModalElement.style.display = 'none';
        logoutModalElement.setAttribute('aria-hidden', 'true');
        logoutModalElement.classList.remove('show');
    }
    
    // Action sur le bouton de confirmation de déconnexion
    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', function() {
            // Fermer le modal d'abord
            if (logoutModalInstance) {
                logoutModalInstance.hide();
            }
            // Rediriger après un court délai
            setTimeout(function() {
                window.location.href = '<?php echo BASE_URL; ?>/auth/logout';
            }, 200);
        });
    }
    
    // Nettoyer aria-hidden quand le modal est caché
    if (logoutModalElement) {
        logoutModalElement.addEventListener('hidden.bs.modal', function () {
            // Nettoyer complètement le modal
            logoutModalElement.style.display = 'none';
            logoutModalElement.setAttribute('aria-hidden', 'true');
            logoutModalElement.removeAttribute('aria-modal');
            
            // Détruire l'instance
            if (logoutModalInstance) {
                logoutModalInstance.dispose();
                logoutModalInstance = null;
            }
            
            // CRITIQUE : Supprimer TOUS les backdrops résiduels
            setTimeout(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });
            }, 50);
        });
        
        // S'assurer que aria-hidden est bien géré à l'ouverture
        logoutModalElement.addEventListener('shown.bs.modal', function () {
            logoutModalElement.setAttribute('aria-modal', 'true');
            logoutModalElement.removeAttribute('aria-hidden');
        });
    }
});
</script>
