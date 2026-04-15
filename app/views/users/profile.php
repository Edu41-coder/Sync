<!-- Page de profil utilisateur -->
<?php
// Vérification de sécurité
if (!isset($user) || !is_object($user)) {
    header('Location: ' . BASE_URL . '/auth/login');
    exit;
}
?>
<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-user-circle', 'text' => 'Mon Profil', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête de la page -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-user-circle me-2"></i>Mon Profil</h1>
            <p class="text-muted mb-0">Gérez vos informations personnelles</p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/user/settings" class="btn btn-outline-primary btn-settings-link">
                <i class="fas fa-cog me-1"></i> Paramètres
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Colonne gauche - Carte de profil -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body text-center">
                    <!-- Photo de profil -->
                    <div class="mb-3 position-relative d-inline-block">
                        <?php if (!empty($user->photo_profil)): ?>
                        <img src="<?= BASE_URL . '/' . $user->photo_profil ?>" alt="Photo"
                             class="rounded-circle" style="width:100px;height:100px;object-fit:cover;border:3px solid #dee2e6">
                        <?php else: ?>
                        <div class="avatar-circle bg-primary text-white mx-auto">
                            <span><?= strtoupper(substr($user->prenom ?? 'U', 0, 1)) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Upload / Changer photo -->
                    <div class="mb-3">
                        <form method="POST" action="<?= BASE_URL ?>/user/uploadPhoto" enctype="multipart/form-data" class="d-inline">
                            <?= csrf_field() ?>
                            <label class="btn btn-sm btn-outline-primary" style="cursor:pointer">
                                <i class="fas fa-camera me-1"></i><?= !empty($user->photo_profil) ? 'Changer' : 'Ajouter une photo' ?>
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="d-none"
                                       onchange="this.form.submit()">
                            </label>
                        </form>
                        <?php if (!empty($user->photo_profil)): ?>
                        <a href="<?= BASE_URL ?>/user/deletePhoto" class="btn btn-sm btn-outline-danger ms-1"
                           onclick="return confirm('Supprimer la photo ?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php endif; ?>
                        <br><small class="text-muted">JPG, PNG, WEBP — max 2 Mo</small>
                    </div>
                    
                    <!-- Nom complet -->
                    <h4 class="mb-1"><?= htmlspecialchars($user->prenom . ' ' . $user->nom) ?></h4>
                    <p class="text-muted mb-2">@<?= htmlspecialchars($user->username) ?></p>
                    
                    <!-- Badge de rôle -->
                    <span class="badge bg-<?= $user->role == 'admin' ? 'danger' : ($user->role == 'directeur_residence' ? 'primary' : 'secondary') ?> mb-3">
                        <i class="fas fa-<?= $user->role == 'admin' ? 'crown' : ($user->role == 'directeur_residence' ? 'user-tie' : 'user') ?> me-1"></i>
                        <?= ucfirst($user->role) ?>
                    </span>
                    
                    <!-- Statut -->
                    <div class="mb-3">
                        <?php if ($user->actif): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i> Compte actif
                            </span>
                        <?php else: ?>
                            <span class="badge bg-danger">
                                <i class="fas fa-ban me-1"></i> Compte désactivé
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Dernière connexion -->
                    <?php if ($user->last_login): ?>
                        <p class="text-muted small mb-0">
                            <i class="far fa-clock me-1"></i>
                            Dernière connexion : <?= date('d/m/Y à H:i', strtotime($user->last_login)) ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Carte statistiques -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Statistiques</h6>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-building text-primary me-2"></i>Copropriétés</span>
                        <span class="badge bg-primary rounded-pill"><?= $stats['coproprietes'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-file-alt text-info me-2"></i>Documents</span>
                        <span class="badge bg-info rounded-pill"><?= $stats['documents'] ?? 0 ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span><i class="fas fa-envelope text-warning me-2"></i>Messages non lus</span>
                        <span class="badge bg-warning rounded-pill"><?= $stats['messages'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Colonne droite - Informations et formulaires -->
        <div class="col-md-8">
            
            <!-- Onglets -->
            <ul class="nav nav-tabs mb-4" id="profileTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                        <i class="fas fa-info-circle me-1"></i> Informations
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab">
                        <i class="fas fa-shield-alt me-1"></i> Sécurité
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="activity-tab" data-bs-toggle="tab" data-bs-target="#activity" type="button" role="tab">
                        <i class="fas fa-history me-1"></i> Activité
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="profileTabsContent">
                
                <!-- Onglet Informations -->
                <div class="tab-pane fade show active" id="info" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Mes informations</h5>
                            <?php if (($_SESSION['user_role'] ?? '') !== 'proprietaire'): ?>
                            <button type="button" class="btn btn-primary btn-sm" id="editBtn" onclick="enableEdit()">
                                <i class="fas fa-edit me-1"></i> Modifier
                            </button>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/updateProfile" id="profileForm">
                                <?= csrf_field() ?>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="prenom" class="form-label">Prénom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="prenom" name="prenom" value="<?= htmlspecialchars($user->prenom ?? '') ?>" disabled required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="nom" class="form-label">Nom <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nom" name="nom" value="<?= htmlspecialchars($user->nom ?? '') ?>" disabled required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($user->email ?? '') ?>" disabled required>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="telephone" class="form-label">Téléphone</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                        <input type="tel" class="form-control" id="telephone" name="telephone" value="<?= htmlspecialchars($user->telephone ?? '') ?>" placeholder="06 12 34 56 78" disabled>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="username" class="form-label">Nom d'utilisateur</label>
                                    <input type="text" class="form-control" id="username" value="<?= htmlspecialchars($user->username) ?>" disabled>
                                    <small class="form-text text-muted">Le nom d'utilisateur ne peut pas être modifié.</small>
                                </div>

                                <hr>

                                <?php if (($_SESSION['user_role'] ?? '') !== 'proprietaire'): ?>
                                <div class="d-flex justify-content-between" id="formButtons">
                                    <button type="button" class="btn btn-outline-secondary" onclick="cancelEdit()">
                                        <i class="fas fa-times me-1"></i> Annuler
                                    </button>
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                    </button>
                                </div>
                                <?php else: ?>
                                <div class="alert alert-info alert-permanent small mb-0">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Pour modifier vos informations, contactez l'administration Domitys.
                                </div>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Sécurité -->
                <div class="tab-pane fade" id="security" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-key me-2"></i>Changer mon mot de passe</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="<?= BASE_URL ?>/user/changePassword" id="passwordForm">
                                <?= csrf_field() ?>
                                <div class="mb-3">
                                    <label class="form-label">Mot de passe actuel</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="profileCurrentPwd"
                                               value="<?= htmlspecialchars($user->password_plain ?? '') ?>" readonly>
                                        <button class="btn btn-outline-secondary" type="button" onclick="
                                            const f=document.getElementById('profileCurrentPwd'), i=this.querySelector('i');
                                            if(f.type==='password'){f.type='text';i.classList.replace('fa-eye','fa-eye-slash');}
                                            else{f.type='password';i.classList.replace('fa-eye-slash','fa-eye');}
                                        "><i class="fas fa-eye"></i></button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="new_password" class="form-label">Nouveau mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('new_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                    <small class="form-text text-muted">Minimum 6 caractères</small>
                                </div>

                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirmer le nouveau mot de passe <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                        <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('confirm_password')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Conseils de sécurité :</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Utilisez au moins 8 caractères</li>
                                        <li>Mélangez majuscules, minuscules, chiffres et symboles</li>
                                        <li>Ne réutilisez pas vos anciens mots de passe</li>
                                    </ul>
                                </div>

                                <hr>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-key me-1"></i> Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Onglet Activité -->
                <div class="tab-pane fade" id="activity" role="tabpanel">
                    <div class="card shadow-sm">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Activité récente</h5>
                        </div>
                        <div class="card-body">
                            <div class="timeline">
                                <!-- Exemple d'activité -->
                                <div class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="me-3">
                                        <div class="timeline-icon bg-primary text-white">
                                            <i class="fas fa-sign-in-alt"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Connexion réussie</h6>
                                        <p class="text-muted small mb-0">
                                            <i class="far fa-clock me-1"></i>
                                            <?= $user->last_login ? date('d/m/Y à H:i', strtotime($user->last_login)) : 'N/A' ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="d-flex mb-3 pb-3 border-bottom">
                                    <div class="me-3">
                                        <div class="timeline-icon bg-success text-white">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1">Compte créé</h6>
                                        <p class="text-muted small mb-0">
                                            <i class="far fa-clock me-1"></i>
                                            <?= date('d/m/Y à H:i', strtotime($user->created_at)) ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="text-center text-muted py-3">
                                    <i class="fas fa-history me-2"></i>
                                    Historique complet bientôt disponible
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Variables pour stocker les valeurs originales
let originalValues = {};

// Fonction pour activer le mode édition
function enableEdit() {
    // Sauvegarder les valeurs originales
    originalValues = {
        prenom: document.getElementById('prenom').value,
        nom: document.getElementById('nom').value,
        email: document.getElementById('email').value,
        telephone: document.getElementById('telephone').value
    };
    
    // Activer les champs
    document.getElementById('prenom').disabled = false;
    document.getElementById('nom').disabled = false;
    document.getElementById('email').disabled = false;
    document.getElementById('telephone').disabled = false;
    
    // Masquer le bouton Modifier
    document.getElementById('editBtn').style.display = 'none';
    
    // Afficher les boutons Annuler/Enregistrer - FORCER l'affichage
    const formButtons = document.getElementById('formButtons');
    formButtons.style.setProperty('display', 'flex', 'important');
    
    // Focus sur le premier champ
    document.getElementById('prenom').focus();
}

// Fonction pour annuler l'édition
function cancelEdit() {
    // Restaurer les valeurs originales
    document.getElementById('prenom').value = originalValues.prenom;
    document.getElementById('nom').value = originalValues.nom;
    document.getElementById('email').value = originalValues.email;
    document.getElementById('telephone').value = originalValues.telephone;
    
    // Désactiver les champs
    document.getElementById('prenom').disabled = true;
    document.getElementById('nom').disabled = true;
    document.getElementById('email').disabled = true;
    document.getElementById('telephone').disabled = true;
    
    // Afficher le bouton Modifier
    document.getElementById('editBtn').style.display = 'inline-block';
    
    // Masquer les boutons Annuler/Enregistrer
    document.getElementById('formButtons').style.display = 'none';
}

// Fonction pour afficher/masquer le mot de passe
function togglePassword(fieldId) {
    const field = document.getElementById(fieldId);
    const button = field.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (field.type === 'password') {
        field.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        field.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Validation du formulaire de changement de mot de passe
document.getElementById('passwordForm')?.addEventListener('submit', function(e) {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        e.preventDefault();
        alert('Les mots de passe ne correspondent pas !');
        return false;
    }
    
    if (newPassword.length < 6) {
        e.preventDefault();
        alert('Le mot de passe doit contenir au moins 6 caractères !');
        return false;
    }
});

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
