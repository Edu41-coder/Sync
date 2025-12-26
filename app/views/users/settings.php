<!-- Page des paramètres utilisateur -->
<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-user-circle', 'text' => 'Mon Profil', 'url' => BASE_URL . '/user/profile'],
        ['icon' => 'fas fa-cog', 'text' => 'Paramètres', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-cog me-2"></i>Paramètres</h1>
            <p class="text-muted mb-0">Personnalisez votre expérience</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/user/profile" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left me-1"></i> Retour au profil
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Colonne gauche - Menu des paramètres -->
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-header settings-header">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Catégories</h6>
                </div>
                <div class="list-group list-group-flush">
                    <a href="#general" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                        <i class="fas fa-sliders-h me-2"></i> Général
                    </a>
                    <a href="#notifications" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-bell me-2"></i> Notifications
                    </a>
                    <a href="#privacy" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-shield-alt me-2"></i> Confidentialité
                    </a>
                    <a href="#appearance" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-palette me-2"></i> Apparence
                    </a>
                    <a href="#advanced" class="list-group-item list-group-item-action" data-bs-toggle="list">
                        <i class="fas fa-cogs me-2"></i> Avancé
                    </a>
                </div>
            </div>

            <!-- Carte d'aide -->
            <div class="card shadow-sm mt-3">
                <div class="card-body">
                    <h6 class="card-title"><i class="fas fa-question-circle me-2"></i>Besoin d'aide ?</h6>
                    <p class="card-text small text-muted">Consultez notre documentation ou contactez le support.</p>
                    <a href="#" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-book me-1"></i> Documentation
                    </a>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Contenu des paramètres -->
        <div class="col-md-9">
            <div class="tab-content">
                
                <!-- Onglet Général -->
                <div class="tab-pane fade show active" id="general">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-sliders-h me-2"></i>Paramètres généraux</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/updateSettings">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="general">
                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label">Langue</label>
                                    <div class="col-md-8">
                                        <select class="form-select" name="language">
                                            <option value="fr" <?= (isset($prefs['language']) && $prefs['language'] === 'fr') || !isset($prefs['language']) ? 'selected' : '' ?>>Français</option>
                                            <option value="en" <?= (isset($prefs['language']) && $prefs['language'] === 'en') ? 'selected' : '' ?>>English</option>
                                            <option value="es" <?= (isset($prefs['language']) && $prefs['language'] === 'es') ? 'selected' : '' ?>>Español</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label">Fuseau horaire</label>
                                    <div class="col-md-8">
                                        <select class="form-select" name="timezone">
                                            <option value="Europe/Paris" <?= (!empty($prefs['timezone']) && $prefs['timezone'] === 'Europe/Paris') || empty($prefs['timezone'] ) ? 'selected' : '' ?>>Europe/Paris (GMT+1)</option>
                                            <option value="Europe/London" <?= (!empty($prefs['timezone']) && $prefs['timezone'] === 'Europe/London') ? 'selected' : '' ?>>Europe/London (GMT+0)</option>
                                            <option value="America/New_York" <?= (!empty($prefs['timezone']) && $prefs['timezone'] === 'America/New_York') ? 'selected' : '' ?>>America/New_York (GMT-5)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label">Format de date</label>
                                    <div class="col-md-8">
                                        <select class="form-select" name="date_format">
                                            <option value="dd/mm/yyyy" <?= (!empty($prefs['date_format']) && $prefs['date_format'] === 'dd/mm/yyyy') || empty($prefs['date_format']) ? 'selected' : '' ?>>JJ/MM/AAAA (31/12/2025)</option>
                                            <option value="mm/dd/yyyy" <?= (!empty($prefs['date_format']) && $prefs['date_format'] === 'mm/dd/yyyy') ? 'selected' : '' ?>>MM/JJ/AAAA (12/31/2025)</option>
                                            <option value="yyyy-mm-dd" <?= (!empty($prefs['date_format']) && $prefs['date_format'] === 'yyyy-mm-dd') ? 'selected' : '' ?>>AAAA-MM-JJ (2025-12-31)</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label">Format horaire</label>
                                    <div class="col-md-8">
                                        <select class="form-select" name="time_format">
                                            <option value="24h" <?= (!empty($prefs['time_format']) && $prefs['time_format'] === '24h') || empty($prefs['time_format']) ? 'selected' : '' ?>>24 heures (14:30)</option>
                                            <option value="12h" <?= (!empty($prefs['time_format']) && $prefs['time_format'] === '12h') ? 'selected' : '' ?>>12 heures (2:30 PM)</option>
                                        </select>
                                    </div>
                                </div>

                                <hr>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Notifications -->
                <div class="tab-pane fade" id="notifications">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Préférences de notifications</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/updateSettings">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="notifications">
                                
                                <h6 class="text-primary mb-3"><i class="fas fa-envelope me-2"></i>Notifications par email</h6>
                                
                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="emailNewMessage" id="emailNewMessage" <?= !empty($prefs['email_new_message']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="emailNewMessage">
                                        Nouveaux messages
                                        <small class="text-muted d-block">Recevoir un email lors de nouveaux messages</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="emailAppelFonds" id="emailAppelFonds" <?= !empty($prefs['email_appel_fonds']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="emailAppelFonds">
                                        Appels de fonds
                                        <small class="text-muted d-block">Notification lors de nouveaux appels de charges</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="emailTravaux" id="emailTravaux" <?= !empty($prefs['email_travaux']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="emailTravaux">
                                        Travaux
                                        <small class="text-muted d-block">Mise à jour sur les travaux en cours</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-4">
                                    <input class="form-check-input" type="checkbox" name="emailAssemblee" id="emailAssemblee" <?= !empty($prefs['email_assemblee']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="emailAssemblee">
                                        Assemblées générales
                                        <small class="text-muted d-block">Convocations et procès-verbaux</small>
                                    </label>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-desktop me-2"></i>Notifications dans l'application</h6>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="appNotifications" id="appNotifications" <?= !empty($prefs['app_notifications']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="appNotifications">
                                        Activer les notifications
                                        <small class="text-muted d-block">Afficher les notifications dans l'interface</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="appSounds" id="appSounds" <?= !empty($prefs['app_sounds']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="appSounds">
                                        Sons de notification
                                        <small class="text-muted d-block">Jouer un son lors des notifications</small>
                                    </label>
                                </div>

                                <hr>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Confidentialité -->
                <div class="tab-pane fade" id="privacy">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Confidentialité et sécurité</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/updateSettings">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="privacy">
                                
                                <h6 class="text-primary mb-3"><i class="fas fa-eye me-2"></i>Visibilité du profil</h6>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="profileVisibility" id="visibilityPublic" value="public" <?= (!isset($prefs['profile_visibility']) || $prefs['profile_visibility'] === 'public') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="visibilityPublic">
                                        Public
                                        <small class="text-muted d-block">Visible par tous les utilisateurs</small>
                                    </label>
                                </div>

                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="profileVisibility" id="visibilityPrivate" value="private" <?= (isset($prefs['profile_visibility']) && $prefs['profile_visibility'] === 'private') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="visibilityPrivate">
                                        Privé
                                        <small class="text-muted d-block">Visible uniquement par vous</small>
                                    </label>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-lock me-2"></i>Sécurité du compte</h6>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="twoFactor" id="twoFactor" <?= !empty($prefs['two_factor']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="twoFactor">
                                        Authentification à deux facteurs
                                        <small class="text-muted d-block">Ajouter une couche de sécurité supplémentaire</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="loginNotifications" id="loginNotifications" <?= !empty($prefs['login_notifications']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="loginNotifications">
                                        Alertes de connexion
                                        <small class="text-muted d-block">Recevoir une notification lors de chaque connexion</small>
                                    </label>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-database me-2"></i>Données personnelles</h6>

                                <div class="d-grid gap-2 mb-3">
                                    <button type="button" class="btn btn-outline-primary" onclick="alert('Fonctionnalité à venir')">
                                        <i class="fas fa-download me-2"></i> Télécharger mes données
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="if(confirm('Êtes-vous sûr de vouloir supprimer votre compte ?')) alert('Fonctionnalité à venir')">
                                        <i class="fas fa-trash me-2"></i> Supprimer mon compte
                                    </button>
                                </div>

                                <hr>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Apparence -->
                <div class="tab-pane fade" id="appearance">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-palette me-2"></i>Personnalisation de l'interface</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/updateSettings">
                                <?= csrf_field() ?>
                                <input type="hidden" name="section" value="appearance">
                                
                                <h6 class="text-primary mb-3"><i class="fas fa-moon me-2"></i>Thème</h6>

                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card theme-card <?= (!isset($prefs['theme']) || $prefs['theme'] === 'light') ? 'border-primary' : '' ?>" onclick="document.getElementById('themeLight').checked = true;">
                                            <div class="card-body text-center bg-light">
                                                <i class="fas fa-sun fa-3x text-warning mb-2"></i>
                                                <h6>Clair</h6>
                                                <input class="form-check-input" type="radio" name="theme" id="themeLight" value="light" <?= (!isset($prefs['theme']) || $prefs['theme'] === 'light') ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card theme-card <?= (isset($prefs['theme']) && $prefs['theme'] === 'dark') ? 'border-primary' : '' ?>" onclick="document.getElementById('themeDark').checked = true;">
                                            <div class="card-body text-center bg-dark text-white">
                                                <i class="fas fa-moon fa-3x text-info mb-2"></i>
                                                <h6>Sombre</h6>
                                                <input class="form-check-input" type="radio" name="theme" id="themeDark" value="dark" <?= (isset($prefs['theme']) && $prefs['theme'] === 'dark') ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card theme-card theme-card-auto <?= (isset($prefs['theme']) && $prefs['theme'] === 'auto') ? 'border-primary' : '' ?>" onclick="document.getElementById('themeAuto').checked = true;">
                                            <div class="card-body text-center">
                                                <i class="fas fa-adjust fa-3x mb-2"></i>
                                                <h6>Auto</h6>
                                                <input class="form-check-input" type="radio" name="theme" id="themeAuto" value="auto" <?= (isset($prefs['theme']) && $prefs['theme'] === 'auto') ? 'checked' : '' ?>>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-desktop me-2"></i>Affichage</h6>

                                <div class="row mb-3">
                                    <label class="col-md-4 col-form-label">Densité de l'interface</label>
                                    <div class="col-md-8">
                                        <select class="form-select" name="density">
                                            <option value="comfortable" <?= (!isset($prefs['density']) || $prefs['density'] === 'comfortable') ? 'selected' : '' ?>>Confortable</option>
                                            <option value="compact" <?= (isset($prefs['density']) && $prefs['density'] === 'compact') ? 'selected' : '' ?>>Compact</option>
                                            <option value="spacious" <?= (isset($prefs['density']) && $prefs['density'] === 'spacious') ? 'selected' : '' ?>>Spacieux</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" name="showAnimations" id="showAnimations" <?= !empty($prefs['show_animations']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="showAnimations">
                                        Activer les animations
                                    </label>
                                </div>

                                <hr>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Avancé -->
                <div class="tab-pane fade" id="advanced">
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-cogs me-2"></i>Paramètres avancés</h5>
                        </div>
                        <div class="card-body">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Attention :</strong> Ces paramètres sont destinés aux utilisateurs avancés.
                            </div>

                            <form>
                                <h6 class="text-primary mb-3"><i class="fas fa-code me-2"></i>Développeur</h6>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="devMode">
                                    <label class="form-check-label" for="devMode">
                                        Mode développeur
                                        <small class="text-muted d-block">Afficher les informations de débogage</small>
                                    </label>
                                </div>

                                <div class="form-check form-switch mb-3">
                                    <input class="form-check-input" type="checkbox" id="apiAccess">
                                    <label class="form-check-label" for="apiAccess">
                                        Accès API
                                        <small class="text-muted d-block">Permettre l'accès via API REST</small>
                                    </label>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-database me-2"></i>Maintenance</h6>

                                <div class="d-grid gap-2">
                                    <button type="button" class="btn btn-outline-info">
                                        <i class="fas fa-sync me-2"></i> Vider le cache
                                    </button>
                                    <button type="button" class="btn btn-outline-warning">
                                        <i class="fas fa-redo me-2"></i> Réinitialiser les préférences
                                    </button>
                                </div>

                                <hr>

                                <h6 class="text-primary mb-3"><i class="fas fa-key me-2"></i>Clé API</h6>

                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" value="sk_live_51J..." readonly>
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    <button class="btn btn-outline-danger" type="button">
                                        <i class="fas fa-sync"></i> Régénérer
                                    </button>
                                </div>

                                <hr>

                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Enregistrer
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
// Auto-fermeture des messages flash après 5 secondes
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
