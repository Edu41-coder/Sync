<?php
/**
 * ====================================================================
 * Vue: Créer un résident
 * ====================================================================
 */

// Breadcrumb
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Tableau de bord', 'url' => BASE_URL . '/dashboard'],
    ['icon' => 'fas fa-users', 'text' => 'Résidents', 'url' => BASE_URL . '/resident/index'],
    ['icon' => 'fas fa-plus-circle', 'text' => 'Nouveau résident', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    
    <!-- En-tête avec icône noire -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-plus-circle text-dark"></i>
            Créer un nouveau résident
        </h1>
        <a href="<?= BASE_URL ?>/resident/index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>
            Retour à la liste
        </a>
    </div>
    
    <form method="POST" action="<?= BASE_URL ?>/resident/create">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
        
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-12 col-lg-8">
                
                <!-- Informations personnelles -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-id-card me-2"></i>
                            Informations personnelles
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Civilité -->
                            <div class="col-12 col-md-3">
                                <label for="civilite" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Civilité <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="civilite" name="civilite" required>
                                    <option value="">Choisir...</option>
                                    <option value="M">M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            
                            <!-- Nom -->
                            <div class="col-12 col-md-4">
                                <label for="nom" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= isset($userData) ? htmlspecialchars($userData['nom']) : '' ?>" required>
                            </div>
                            
                            <!-- Prénom -->
                            <div class="col-12 col-md-5">
                                <label for="prenom" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Prénom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?= isset($userData) ? htmlspecialchars($userData['prenom']) : '' ?>" required>
                            </div>
                            
                            <!-- Nom de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="nom_naissance" class="form-label">
                                    <i class="fas fa-signature text-dark me-1"></i>
                                    Nom de naissance
                                </label>
                                <input type="text" class="form-control" id="nom_naissance" name="nom_naissance">
                            </div>
                            
                            <!-- Date de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="date_naissance" class="form-label">
                                    <i class="fas fa-calendar-alt text-dark me-1"></i>
                                    Date de naissance <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" required>
                            </div>
                            
                            <!-- Lieu de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="lieu_naissance" class="form-label">
                                    <i class="fas fa-map-marker-alt text-dark me-1"></i>
                                    Lieu de naissance
                                </label>
                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance">
                            </div>
                            
                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-dark me-1"></i>
                                    Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= isset($userData) ? htmlspecialchars($userData['email']) : '' ?>">
                                <small class="text-muted">
                                    Si vide, un email sera généré automatiquement
                                </small>
                            </div>
                            
                            <!-- Téléphone fixe -->
                            <div class="col-12 col-md-6">
                                <label for="telephone_fixe" class="form-label">
                                    <i class="fas fa-phone text-dark me-1"></i>
                                    Téléphone fixe
                                </label>
                                <input type="tel" class="form-control" id="telephone_fixe" name="telephone_fixe">
                            </div>
                            
                            <!-- Téléphone mobile -->
                            <div class="col-12 col-md-6">
                                <label for="telephone_mobile" class="form-label">
                                    <i class="fas fa-mobile-alt text-dark me-1"></i>
                                    Téléphone mobile
                                </label>
                                <input type="tel" class="form-control" id="telephone_mobile" name="telephone_mobile">
                            </div>
                            
                            <!-- Section CNI -->
                            <div class="col-12">
                                <hr class="my-3">
                                <h6 class="text-muted mb-3">
                                    <i class="fas fa-id-card-alt text-dark me-2"></i>
                                    Carte Nationale d'Identité
                                </h6>
                            </div>
                            
                            <!-- Numéro CNI -->
                            <div class="col-12 col-md-4">
                                <label for="numero_cni" class="form-label">
                                    <i class="fas fa-hashtag text-dark me-1"></i>
                                    Numéro CNI
                                </label>
                                <input type="text" class="form-control" id="numero_cni" name="numero_cni" 
                                       maxlength="20" placeholder="Ex: AB123456">
                            </div>
                            
                            <!-- Date délivrance CNI -->
                            <div class="col-12 col-md-4">
                                <label for="date_delivrance_cni" class="form-label">
                                    <i class="fas fa-calendar text-dark me-1"></i>
                                    Date de délivrance
                                </label>
                                <input type="date" class="form-control" id="date_delivrance_cni" name="date_delivrance_cni">
                            </div>
                            
                            <!-- Lieu délivrance CNI -->
                            <div class="col-12 col-md-4">
                                <label for="lieu_delivrance_cni" class="form-label">
                                    <i class="fas fa-map-marker-alt text-dark me-1"></i>
                                    Lieu de délivrance
                                </label>
                                <input type="text" class="form-control" id="lieu_delivrance_cni" name="lieu_delivrance_cni" 
                                       maxlength="100" placeholder="Ex: Paris">
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Autonomie -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>
                            Autonomie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Niveau d'autonomie -->
                            <div class="col-12">
                                <label for="niveau_autonomie" class="form-label">
                                    <i class="fas fa-walking text-dark me-1"></i>
                                    Niveau d'autonomie <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="niveau_autonomie" name="niveau_autonomie" required>
                                    <option value="autonome" selected>Autonome</option>
                                    <option value="semi_autonome">Semi-autonome</option>
                                    <option value="dependant">Dépendant</option>
                                    <option value="gir1">GIR 1</option>
                                    <option value="gir2">GIR 2</option>
                                    <option value="gir3">GIR 3</option>
                                    <option value="gir4">GIR 4</option>
                                    <option value="gir5">GIR 5</option>
                                    <option value="gir6">GIR 6</option>
                                </select>
                                <small class="text-muted">
                                    GIR 1 = dépendance totale, GIR 6 = autonome
                                </small>
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Remarques -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-comment-alt me-2"></i>
                            Remarques
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                  placeholder="Informations complémentaires..."></textarea>
                    </div>
                </div>
                
            </div>
            
            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                
                <!-- Informations importantes -->
                <div class="card shadow-sm mb-4 border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations importantes
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-user-circle me-2"></i>
                                Compte utilisateur
                            </h6>
                            <p class="mb-2 small">
                                Un compte utilisateur sera créé automatiquement avec :
                            </p>
                            <ul class="mb-0 small">
                                <li>Nom d'utilisateur : <strong>premiereLettrePrénom + nom</strong></li>
                                <li>Mot de passe par défaut : <strong>resident123</strong></li>
                                <li>Email généré si non fourni</li>
                                <li>Rôle : <strong>Résident</strong></li>
                            </ul>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Prochaines étapes
                            </h6>
                            <p class="mb-0 small">
                                Après la création, vous pourrez :
                            </p>
                            <ul class="mb-0 small">
                                <li>Compléter le profil (santé, famille, urgence)</li>
                                <li>Créer une occupation (affecter à un lot)</li>
                                <li>Configurer les services et loyers</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-danger btn-lg">
                                <i class="fas fa-save me-2"></i>
                                Créer le résident
                            </button>
                            <a href="<?= BASE_URL ?>/resident/index" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuler
                            </a>
                        </div>
                        
                        <hr class="my-3">
                        
                        <div class="text-muted small">
                            <i class="fas fa-asterisk text-danger me-1"></i>
                            <strong>Champs obligatoires</strong>
                            <ul class="mt-2 mb-0">
                                <li>Civilité</li>
                                <li>Nom et Prénom</li>
                                <li>Date de naissance</li>
                                <li>Niveau d'autonomie</li>
                            </ul>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </form>
    
</div>
