<?php $title = "Modifier un utilisateur"; ?>

<?php
// Fil d'Ariane
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-users', 'text' => 'Utilisateurs', 'url' => BASE_URL . '/admin/users'],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier un utilisateur', 'url' => null]
];
include __DIR__ . '/../../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-user-edit text-warning"></i>
                Modifier l'Utilisateur : <?= htmlspecialchars($user['username']) ?>
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-user-edit me-2"></i>
                        Modifier l'utilisateur
                    </h5>
                </div>
                
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/users/update/<?= $user['id'] ?>" method="POST" id="userForm">
                        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                        
                        <div class="row g-3">
                            <!-- Prénom -->
                            <div class="col-12 col-md-6">
                                <label for="prenom" class="form-label">
                                    Prénom <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="prenom" 
                                           name="prenom" 
                                           required 
                                           value="<?= htmlspecialchars($user['prenom']) ?>"
                                           autocomplete="given-name">
                                </div>
                            </div>
                            
                            <!-- Nom -->
                            <div class="col-12 col-md-6">
                                <label for="nom" class="form-label">
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-user"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="nom" 
                                           name="nom" 
                                           required 
                                           value="<?= htmlspecialchars($user['nom']) ?>"
                                           autocomplete="family-name">
                                </div>
                            </div>
                            
                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">
                                    Email <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" 
                                           class="form-control" 
                                           id="email" 
                                           name="email" 
                                           required 
                                           value="<?= htmlspecialchars($user['email']) ?>"
                                           autocomplete="email">
                                </div>
                            </div>
                            
                            <!-- Username -->
                            <div class="col-12 col-md-6">
                                <label for="username" class="form-label">
                                    Nom d'utilisateur <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-at"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="username" 
                                           name="username" 
                                           required 
                                           minlength="3"
                                           value="<?= htmlspecialchars($user['username']) ?>"
                                           autocomplete="username">
                                </div>
                            </div>
                            
                            <!-- Mot de passe (optionnel en édition) -->
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Mot de passe :</strong> Laissez vide pour conserver le mot de passe actuel. 
                                    Remplissez uniquement si vous souhaitez le modifier.
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label">
                                    Nouveau mot de passe
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           minlength="8"
                                           placeholder="Laisser vide pour ne pas modifier"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Minimum 8 caractères si modification
                                </div>
                            </div>
                            
                            <!-- Confirmation mot de passe -->
                            <div class="col-12 col-md-6">
                                <label for="password_confirm" class="form-label">
                                    Confirmer le nouveau mot de passe
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirm" 
                                           name="password_confirm" 
                                           minlength="8"
                                           placeholder="Confirmer le nouveau mot de passe"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePasswordConfirm">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <!-- Rôle -->
                            <div class="col-12 col-md-6">
                                <label for="role" class="form-label">
                                    Rôle <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="role" name="role" required>
                                    <option value="">-- Sélectionner un rôle --</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>
                                        Admin - Accès complet
                                    </option>
                                    <option value="gestionnaire" <?= $user['role'] === 'gestionnaire' ? 'selected' : '' ?>>
                                        Gestionnaire - Gestion syndic
                                    </option>
                                    <option value="exploitant" <?= $user['role'] === 'exploitant' ? 'selected' : '' ?>>
                                        Exploitant - Opérateur Domitys
                                    </option>
                                    <option value="proprietaire" <?= $user['role'] === 'proprietaire' ? 'selected' : '' ?>>
                                        Propriétaire - Investisseur
                                    </option>
                                    <option value="resident" <?= $user['role'] === 'resident' ? 'selected' : '' ?>>
                                        Résident - Senior occupant
                                    </option>
                                </select>
                            </div>
                            
                            <!-- Statut actif -->
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">
                                    Statut
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="actif" 
                                           name="actif" 
                                           <?= $user['actif'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="actif">
                                        <span class="badge" id="statusBadge">
                                            <i class="fas fa-check-circle me-1"></i>
                                            <span id="statusText"><?= $user['actif'] ? 'Actif' : 'Inactif' ?></span>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Informations supplémentaires -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Informations système
                                </h6>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <div class="form-text">
                                    <strong>Créé le :</strong> 
                                    <?= date('d/m/Y à H:i', strtotime($user['created_at'])) ?>
                                </div>
                            </div>
                            
                            <div class="col-12 col-md-6">
                                <div class="form-text">
                                    <strong>Dernière modification :</strong> 
                                    <?= date('d/m/Y à H:i', strtotime($user['updated_at'])) ?>
                                </div>
                            </div>
                            
                            <?php if (!empty($user['last_login'])): ?>
                            <div class="col-12">
                                <div class="form-text">
                                    <strong>Dernière connexion :</strong> 
                                    <?= date('d/m/Y à H:i', strtotime($user['last_login'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Boutons -->
                        <hr class="my-4">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Enregistrer les modifications
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Colonne d'aide -->
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <!-- Carte d'avertissement sur les changements de rôle -->
            <?php if ($user['role'] === 'resident'): ?>
            <div class="card shadow border-warning mb-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Restriction Résident Senior
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning mb-0">
                        <p class="small mb-2">
                            <i class="fas fa-ban me-1"></i>
                            <strong>Un résident senior ne peut PAS changer de rôle.</strong>
                        </p>
                        <p class="small mb-0">
                            Si vous devez donner un autre statut à cette personne (propriétaire, exploitant, etc.), 
                            <strong>créez un nouveau compte utilisateur</strong> avec le rôle souhaité.
                        </p>
                        <?php if (isset($residentProfile) && $residentProfile): ?>
                        <hr class="my-2">
                        <p class="small mb-0 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Cet utilisateur possède un profil résident senior complet (ID: <?= $residentProfile['id'] ?>)
                        </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow border-info mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-shield me-2"></i>
                        Restriction de Rôle
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0">
                        <p class="small mb-2">
                            <i class="fas fa-ban me-1"></i>
                            <strong>Cet utilisateur ne peut PAS devenir résident senior.</strong>
                        </p>
                        <p class="small mb-0">
                            Pour créer un résident senior, vous devez 
                            <strong>créer un nouveau compte utilisateur</strong> avec le rôle "Résident" 
                            dès le départ.
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Aide
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Modification</h6>
                    <ul class="small mb-3">
                        <li>Tous les champs sauf le mot de passe sont obligatoires</li>
                        <li>Le mot de passe est optionnel (laissez vide pour ne pas le modifier)</li>
                        <li>L'email et le username doivent rester uniques</li>
                        <li>Désactiver un compte empêche la connexion</li>
                    </ul>
                    
                    <h6 class="fw-bold">Sécurité</h6>
                    <ul class="small mb-0">
                        <li>Les modifications sont loguées dans le système</li>
                        <li>Vous ne pouvez pas modifier votre propre statut</li>
                        <li>Le changement de rôle affecte immédiatement les permissions</li>
                    </ul>
                </div>
            </div>
            
            <?php if ($user['id'] == $_SESSION['user_id']): ?>
            <div class="card shadow mt-3 border-warning">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Attention
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small mb-0">
                        <strong>Vous modifiez votre propre compte.</strong>
                        Soyez prudent avec le changement de rôle, cela pourrait affecter vos accès.
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($user['role'] === 'resident'): ?>
            <div class="card shadow mt-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        Profil Résident Senior
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($residentProfile) && $residentProfile): ?>
                        <p class="small mb-3">
                            <i class="fas fa-check-circle text-success me-1"></i>
                            Le profil senior de cet utilisateur existe.
                        </p>
                        <div class="d-grid gap-2">
                            <a href="<?= BASE_URL ?>/resident/show/<?= $residentProfile['id'] ?>" class="btn btn-success btn-sm">
                                <i class="fas fa-eye me-1"></i>
                                Voir le profil senior
                            </a>
                            <a href="<?= BASE_URL ?>/resident/edit/<?= $residentProfile['id'] ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-edit me-1"></i>
                                Modifier le profil senior
                            </a>
                        </div>
                    <?php else: ?>
                        <p class="small mb-3">
                            <i class="fas fa-exclamation-circle text-warning me-1"></i>
                            Le profil senior de cet utilisateur n'existe pas encore.
                        </p>
                        <div class="d-grid">
                            <a href="<?= BASE_URL ?>/resident/create?user_id=<?= $user['id'] ?>" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-plus-circle me-1"></i>
                                Créer le profil senior
                            </a>
                        </div>
                        <small class="text-muted d-block mt-2">
                            Le profil senior contient les informations détaillées : santé, autonomie, CNI, contacts d'urgence, etc.
                        </small>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function() {
    const password = document.getElementById('password');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

document.getElementById('togglePasswordConfirm').addEventListener('click', function() {
    const password = document.getElementById('password_confirm');
    const icon = this.querySelector('i');
    
    if (password.type === 'password') {
        password.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        password.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
});

// Validation du formulaire
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const passwordConfirm = document.getElementById('password_confirm').value;
    
    // Validation uniquement si un nouveau mot de passe est saisi
    if (password || passwordConfirm) {
        if (password !== passwordConfirm) {
            e.preventDefault();
            alert('Les mots de passe ne correspondent pas !');
            document.getElementById('password_confirm').focus();
            return false;
        }
        
        if (password.length < 8) {
            e.preventDefault();
            alert('Le mot de passe doit contenir au moins 8 caractères !');
            document.getElementById('password').focus();
            return false;
        }
    }
});

// Mettre à jour le badge de statut en temps réel
document.getElementById('actif').addEventListener('change', function() {
    const badge = document.getElementById('statusBadge');
    const text = document.getElementById('statusText');
    
    if (this.checked) {
        badge.className = 'badge bg-success';
        text.textContent = 'Actif';
    } else {
        badge.className = 'badge bg-secondary';
        text.textContent = 'Inactif';
    }
});
</script>
