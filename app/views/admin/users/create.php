<?php $title = "Créer un utilisateur"; ?>

<?php
// Fil d'Ariane
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-users', 'text' => 'Utilisateurs', 'url' => BASE_URL . '/admin/users'],
    ['icon' => 'fas fa-plus', 'text' => 'Créer un utilisateur', 'url' => null]
];
include __DIR__ . '/../../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-user-plus text-dark"></i>
                Créer un Utilisateur
            </h1>
        </div>
    </div>

    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user me-2"></i>
                        Informations de l'utilisateur
                    </h5>
                </div>
                
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/admin/users/store" method="POST" id="userForm">
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
                                           placeholder="Jean"
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
                                           placeholder="Dupont"
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
                                           placeholder="jean.dupont@example.com"
                                           autocomplete="email">
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    L'email doit être unique dans le système
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
                                           placeholder="jdupont"
                                           autocomplete="username">
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Minimum 3 caractères, doit être unique
                                </div>
                            </div>
                            
                            <!-- Mot de passe -->
                            <div class="col-12 col-md-6">
                                <label for="password" class="form-label">
                                    Mot de passe <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password" 
                                           name="password" 
                                           required 
                                           minlength="8"
                                           autocomplete="new-password">
                                    <button class="btn btn-outline-secondary" 
                                            type="button" 
                                            id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Minimum 8 caractères
                                </div>
                            </div>
                            
                            <!-- Confirmation mot de passe -->
                            <div class="col-12 col-md-6">
                                <label for="password_confirm" class="form-label">
                                    Confirmer le mot de passe <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" 
                                           class="form-control" 
                                           id="password_confirm" 
                                           name="password_confirm" 
                                           required 
                                           minlength="8"
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
                                    <option value="admin">
                                        <i class="fas fa-user-shield"></i> Admin - Accès complet
                                    </option>
                                    <option value="gestionnaire">
                                        Gestionnaire - Gestion syndic
                                    </option>
                                    <option value="exploitant">
                                        Exploitant - Opérateur Domitys
                                    </option>
                                    <option value="proprietaire">
                                        Propriétaire - Investisseur
                                    </option>
                                    <option value="resident">
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
                                           checked>
                                    <label class="form-check-label" for="actif">
                                        <span class="badge bg-success">
                                            <i class="fas fa-check-circle me-1"></i>Compte actif
                                        </span>
                                    </label>
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Un compte inactif ne peut pas se connecter
                                </div>
                            </div>
                        </div>
                        
                        <!-- Boutons -->
                        <hr class="my-4">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <a href="<?= BASE_URL ?>/admin/users" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Retour
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Créer l'utilisateur
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Colonne d'aide -->
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Aide
                    </h5>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">
                        <i class="fas fa-user-shield text-danger me-1"></i>
                        Admin
                    </h6>
                    <p class="small mb-3">Accès complet à toutes les fonctionnalités. Gestion des utilisateurs, permissions, configuration système.</p>
                    
                    <h6 class="fw-bold">
                        <i class="fas fa-user-tie text-info me-1"></i>
                        Gestionnaire
                    </h6>
                    <p class="small mb-3">Gestion des copropriétés, appels de fonds, travaux, comptabilité syndic.</p>
                    
                    <h6 class="fw-bold">
                        <i class="fas fa-building text-warning me-1"></i>
                        Exploitant
                    </h6>
                    <p class="small mb-3">Opérateur Domitys. Gestion des résidents, occupations, paiements aux propriétaires.</p>
                    
                    <h6 class="fw-bold">
                        <i class="fas fa-home text-success me-1"></i>
                        Propriétaire
                    </h6>
                    <p class="small mb-3">Investisseur immobilier. Consultation des contrats, paiements reçus, fiscalité LMNP.</p>
                    
                    <h6 class="fw-bold">
                        <i class="fas fa-user text-secondary me-1"></i>
                        Résident
                    </h6>
                    <p class="small">Senior occupant. Consultation de son occupation, paiements, profil, messagerie.</p>
                </div>
            </div>
            
            <div class="card shadow mt-3">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Sécurité
                    </h5>
                </div>
                <div class="card-body">
                    <ul class="small mb-0">
                        <li>Le mot de passe doit contenir au moins <strong>8 caractères</strong></li>
                        <li>L'email et le username doivent être <strong>uniques</strong></li>
                        <li>Les mots de passe sont <strong>hashés</strong> en base</li>
                        <li>Un compte inactif ne peut <strong>pas se connecter</strong></li>
                    </ul>
                </div>
            </div>
            
            <div class="card shadow mt-3 border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-plus me-2"></i>
                        Profil Résident Senior
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small mb-2">
                        <i class="fas fa-info-circle text-success me-1"></i>
                        Si vous créez un utilisateur avec le rôle <strong>Résident</strong>, vous pourrez compléter son profil senior après la création du compte.
                    </p>
                    <p class="small mb-0 text-muted">
                        Le profil senior contient les informations détaillées : santé, autonomie, CNI, contacts d'urgence, etc.
                    </p>
                </div>
            </div>
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
});

// Auto-générer username depuis prénom/nom
document.getElementById('prenom').addEventListener('blur', generateUsername);
document.getElementById('nom').addEventListener('blur', generateUsername);

function generateUsername() {
    const usernameField = document.getElementById('username');
    
    // Ne générer que si le champ est vide
    if (usernameField.value.length > 0) return;
    
    const prenom = document.getElementById('prenom').value.trim();
    const nom = document.getElementById('nom').value.trim();
    
    if (prenom && nom) {
        const username = (prenom.charAt(0) + nom).toLowerCase()
            .normalize('NFD').replace(/[\u0300-\u036f]/g, '') // Supprimer accents
            .replace(/[^a-z0-9]/g, ''); // Garder alphanumériques
        
        usernameField.value = username;
    }
}

// Indicateur de force du mot de passe
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const colors = ['danger', 'warning', 'info', 'success', 'success'];
    const labels = ['Très faible', 'Faible', 'Moyen', 'Fort', 'Très fort'];
    
    // Afficher l'indicateur
    let indicator = this.parentElement.nextElementSibling;
    if (!indicator || !indicator.classList.contains('password-strength')) {
        indicator = document.createElement('div');
        indicator.className = 'password-strength form-text';
        this.parentElement.insertAdjacentElement('afterend', indicator);
    }
    
    indicator.innerHTML = `
        <i class="fas fa-shield-alt me-1"></i>
        Force: <span class="badge bg-${colors[strength]}">${labels[strength]}</span>
    `;
});
</script>
