<?php
/**
 * ====================================================================
 * Vue: Modifier un résident
 * ====================================================================
 */

// Breadcrumb
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Tableau de bord', 'url' => BASE_URL . '/dashboard'],
    ['icon' => 'fas fa-users', 'text' => 'Résidents', 'url' => BASE_URL . '/resident/index'],
    ['icon' => 'fas fa-user', 'text' => $resident->prenom . ' ' . $resident->nom, 'url' => BASE_URL . '/resident/show/' . $resident->id],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    
    <!-- En-tête avec icône jaune -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-edit text-warning"></i>
            Modifier le résident
        </h1>
        <div>
            <a href="<?= BASE_URL ?>/resident/show/<?= $resident->id ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>
                Retour
            </a>
        </div>
    </div>
    
    <form method="POST" action="<?= BASE_URL ?>/resident/edit/<?= $resident->id ?>">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
        
        <div class="row">
            <!-- Colonne principale -->
            <div class="col-12 col-lg-8">
                
                <!-- Informations personnelles -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
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
                                    <option value="M" <?= $resident->civilite === 'M' ? 'selected' : '' ?>>M.</option>
                                    <option value="Mme" <?= $resident->civilite === 'Mme' ? 'selected' : '' ?>>Mme</option>
                                    <option value="Mlle" <?= $resident->civilite === 'Mlle' ? 'selected' : '' ?>>Mlle</option>
                                </select>
                            </div>
                            
                            <!-- Nom -->
                            <div class="col-12 col-md-4">
                                <label for="nom" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Nom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="nom" name="nom" 
                                       value="<?= htmlspecialchars($resident->nom) ?>" required>
                            </div>
                            
                            <!-- Prénom -->
                            <div class="col-12 col-md-5">
                                <label for="prenom" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Prénom <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="prenom" name="prenom" 
                                       value="<?= htmlspecialchars($resident->prenom) ?>" required>
                            </div>
                            
                            <!-- Nom de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="nom_naissance" class="form-label">
                                    <i class="fas fa-signature text-dark me-1"></i>
                                    Nom de naissance
                                </label>
                                <input type="text" class="form-control" id="nom_naissance" name="nom_naissance" 
                                       value="<?= htmlspecialchars($resident->nom_naissance ?? '') ?>">
                            </div>
                            
                            <!-- Date de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="date_naissance" class="form-label">
                                    <i class="fas fa-calendar-alt text-dark me-1"></i>
                                    Date de naissance <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance" 
                                       value="<?= $resident->date_naissance ?>" required>
                            </div>
                            
                            <!-- Lieu de naissance -->
                            <div class="col-12 col-md-6">
                                <label for="lieu_naissance" class="form-label">
                                    <i class="fas fa-map-marker-alt text-dark me-1"></i>
                                    Lieu de naissance
                                </label>
                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" 
                                       value="<?= htmlspecialchars($resident->lieu_naissance ?? '') ?>">
                            </div>
                            
                            <!-- Email -->
                            <div class="col-12 col-md-6">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope text-dark me-1"></i>
                                    Email
                                </label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($resident->email ?? '') ?>">
                            </div>
                            
                            <!-- Téléphone fixe -->
                            <div class="col-12 col-md-6">
                                <label for="telephone_fixe" class="form-label">
                                    <i class="fas fa-phone text-dark me-1"></i>
                                    Téléphone fixe
                                </label>
                                <input type="tel" class="form-control" id="telephone_fixe" name="telephone_fixe" 
                                       value="<?= htmlspecialchars($resident->telephone_fixe ?? '') ?>">
                            </div>
                            
                            <!-- Téléphone mobile -->
                            <div class="col-12 col-md-6">
                                <label for="telephone_mobile" class="form-label">
                                    <i class="fas fa-mobile-alt text-dark me-1"></i>
                                    Téléphone mobile
                                </label>
                                <input type="tel" class="form-control" id="telephone_mobile" name="telephone_mobile" 
                                       value="<?= htmlspecialchars($resident->telephone_mobile ?? '') ?>">
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
                                       value="<?= htmlspecialchars($resident->numero_cni ?? '') ?>"
                                       maxlength="20">
                            </div>
                            
                            <!-- Date délivrance CNI -->
                            <div class="col-12 col-md-4">
                                <label for="date_delivrance_cni" class="form-label">
                                    <i class="fas fa-calendar text-dark me-1"></i>
                                    Date de délivrance
                                </label>
                                <input type="date" class="form-control" id="date_delivrance_cni" name="date_delivrance_cni" 
                                       value="<?= $resident->date_delivrance_cni ?? '' ?>">
                            </div>
                            
                            <!-- Lieu délivrance CNI -->
                            <div class="col-12 col-md-4">
                                <label for="lieu_delivrance_cni" class="form-label">
                                    <i class="fas fa-map-marker-alt text-dark me-1"></i>
                                    Lieu de délivrance
                                </label>
                                <input type="text" class="form-control" id="lieu_delivrance_cni" name="lieu_delivrance_cni" 
                                       value="<?= htmlspecialchars($resident->lieu_delivrance_cni ?? '') ?>"
                                       maxlength="100">
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Situation familiale -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-users me-2"></i>
                            Situation familiale
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Situation familiale -->
                            <div class="col-12 col-md-6">
                                <label for="situation_familiale" class="form-label">
                                    <i class="fas fa-heart text-dark me-1"></i>
                                    Situation familiale
                                </label>
                                <select class="form-select" id="situation_familiale" name="situation_familiale">
                                    <option value="celibataire" <?= ($resident->situation_familiale ?? 'celibataire') === 'celibataire' ? 'selected' : '' ?>>Célibataire</option>
                                    <option value="marie" <?= ($resident->situation_familiale ?? '') === 'marie' ? 'selected' : '' ?>>Marié(e)</option>
                                    <option value="veuf" <?= ($resident->situation_familiale ?? '') === 'veuf' ? 'selected' : '' ?>>Veuf/Veuve</option>
                                    <option value="divorce" <?= ($resident->situation_familiale ?? '') === 'divorce' ? 'selected' : '' ?>>Divorcé(e)</option>
                                    <option value="pacse" <?= ($resident->situation_familiale ?? '') === 'pacse' ? 'selected' : '' ?>>Pacsé(e)</option>
                                    <option value="concubinage" <?= ($resident->situation_familiale ?? '') === 'concubinage' ? 'selected' : '' ?>>Concubinage</option>
                                </select>
                            </div>
                            
                            <!-- Nombre d'enfants -->
                            <div class="col-12 col-md-6">
                                <label for="nombre_enfants" class="form-label">
                                    <i class="fas fa-child text-dark me-1"></i>
                                    Nombre d'enfants
                                </label>
                                <input type="number" class="form-control" id="nombre_enfants" name="nombre_enfants" 
                                       value="<?= $resident->nombre_enfants ?? 0 ?>" min="0">
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Santé et autonomie -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>
                            Santé et autonomie
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Niveau d'autonomie -->
                            <div class="col-12 col-md-6">
                                <label for="niveau_autonomie" class="form-label">
                                    <i class="fas fa-walking text-dark me-1"></i>
                                    Niveau d'autonomie <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="niveau_autonomie" name="niveau_autonomie" required>
                                    <option value="autonome" <?= ($resident->niveau_autonomie ?? 'autonome') === 'autonome' ? 'selected' : '' ?>>Autonome</option>
                                    <option value="semi_autonome" <?= ($resident->niveau_autonomie ?? '') === 'semi_autonome' ? 'selected' : '' ?>>Semi-autonome</option>
                                    <option value="dependant" <?= ($resident->niveau_autonomie ?? '') === 'dependant' ? 'selected' : '' ?>>Dépendant</option>
                                    <option value="gir1" <?= ($resident->niveau_autonomie ?? '') === 'gir1' ? 'selected' : '' ?>>GIR 1</option>
                                    <option value="gir2" <?= ($resident->niveau_autonomie ?? '') === 'gir2' ? 'selected' : '' ?>>GIR 2</option>
                                    <option value="gir3" <?= ($resident->niveau_autonomie ?? '') === 'gir3' ? 'selected' : '' ?>>GIR 3</option>
                                    <option value="gir4" <?= ($resident->niveau_autonomie ?? '') === 'gir4' ? 'selected' : '' ?>>GIR 4</option>
                                    <option value="gir5" <?= ($resident->niveau_autonomie ?? '') === 'gir5' ? 'selected' : '' ?>>GIR 5</option>
                                    <option value="gir6" <?= ($resident->niveau_autonomie ?? '') === 'gir6' ? 'selected' : '' ?>>GIR 6</option>
                                </select>
                            </div>
                            
                            <!-- Besoin assistance -->
                            <div class="col-12 col-md-6">
                                <label class="form-label d-block">
                                    <i class="fas fa-hands-helping text-dark me-1"></i>
                                    Assistance
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="besoin_assistance" name="besoin_assistance" 
                                           <?= ($resident->besoin_assistance ?? 0) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="besoin_assistance">
                                        Besoin d'assistance quotidienne
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Allergies -->
                            <div class="col-12">
                                <label for="allergies" class="form-label">
                                    <i class="fas fa-exclamation-triangle text-dark me-1"></i>
                                    Allergies
                                </label>
                                <textarea class="form-control" id="allergies" name="allergies" rows="2" 
                                          placeholder="Détails des allergies..."><?= htmlspecialchars($resident->allergies ?? '') ?></textarea>
                            </div>
                            
                            <!-- Régime alimentaire -->
                            <div class="col-12">
                                <label for="regime_alimentaire" class="form-label">
                                    <i class="fas fa-utensils text-dark me-1"></i>
                                    Régime alimentaire
                                </label>
                                <textarea class="form-control" id="regime_alimentaire" name="regime_alimentaire" rows="2" 
                                          placeholder="Régime spécial, restrictions..."><?= htmlspecialchars($resident->regime_alimentaire ?? '') ?></textarea>
                            </div>
                            
                            <!-- Médecin traitant -->
                            <div class="col-12 col-md-6">
                                <label for="medecin_traitant_nom" class="form-label">
                                    <i class="fas fa-user-md text-dark me-1"></i>
                                    Médecin traitant
                                </label>
                                <input type="text" class="form-control" id="medecin_traitant_nom" name="medecin_traitant_nom" 
                                       value="<?= htmlspecialchars($resident->medecin_traitant_nom ?? '') ?>">
                            </div>
                            
                            <!-- Téléphone médecin -->
                            <div class="col-12 col-md-6">
                                <label for="medecin_traitant_tel" class="form-label">
                                    <i class="fas fa-phone text-dark me-1"></i>
                                    Téléphone médecin
                                </label>
                                <input type="tel" class="form-control" id="medecin_traitant_tel" name="medecin_traitant_tel" 
                                       value="<?= htmlspecialchars($resident->medecin_traitant_tel ?? '') ?>">
                            </div>
                            
                            <!-- Numéro sécu -->
                            <div class="col-12 col-md-6">
                                <label for="num_securite_sociale" class="form-label">
                                    <i class="fas fa-id-card text-dark me-1"></i>
                                    N° Sécurité Sociale
                                </label>
                                <input type="text" class="form-control" id="num_securite_sociale" name="num_securite_sociale" 
                                       value="<?= htmlspecialchars($resident->num_securite_sociale ?? '') ?>"
                                       maxlength="15">
                            </div>
                            
                            <!-- Mutuelle -->
                            <div class="col-12 col-md-6">
                                <label for="mutuelle" class="form-label">
                                    <i class="fas fa-shield-alt text-dark me-1"></i>
                                    Mutuelle
                                </label>
                                <input type="text" class="form-control" id="mutuelle" name="mutuelle" 
                                       value="<?= htmlspecialchars($resident->mutuelle ?? '') ?>">
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Contact d'urgence -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-phone-alt me-2"></i>
                            Contact d'urgence
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            
                            <!-- Nom contact urgence -->
                            <div class="col-12 col-md-6">
                                <label for="urgence_nom" class="form-label">
                                    <i class="fas fa-user text-dark me-1"></i>
                                    Nom et prénom
                                </label>
                                <input type="text" class="form-control" id="urgence_nom" name="urgence_nom" 
                                       value="<?= htmlspecialchars($resident->urgence_nom ?? '') ?>">
                            </div>
                            
                            <!-- Lien de parenté -->
                            <div class="col-12 col-md-6">
                                <label for="urgence_lien" class="form-label">
                                    <i class="fas fa-link text-dark me-1"></i>
                                    Lien de parenté
                                </label>
                                <input type="text" class="form-control" id="urgence_lien" name="urgence_lien" 
                                       value="<?= htmlspecialchars($resident->urgence_lien ?? '') ?>"
                                       placeholder="Ex: Fils, Fille, Frère...">
                            </div>
                            
                            <!-- Téléphone principal -->
                            <div class="col-12 col-md-4">
                                <label for="urgence_telephone" class="form-label">
                                    <i class="fas fa-phone text-dark me-1"></i>
                                    Téléphone principal
                                </label>
                                <input type="tel" class="form-control" id="urgence_telephone" name="urgence_telephone" 
                                       value="<?= htmlspecialchars($resident->urgence_telephone ?? '') ?>">
                            </div>
                            
                            <!-- Téléphone secondaire -->
                            <div class="col-12 col-md-4">
                                <label for="urgence_telephone_2" class="form-label">
                                    <i class="fas fa-phone text-dark me-1"></i>
                                    Téléphone secondaire
                                </label>
                                <input type="tel" class="form-control" id="urgence_telephone_2" name="urgence_telephone_2" 
                                       value="<?= htmlspecialchars($resident->urgence_telephone_2 ?? '') ?>">
                            </div>
                            
                            <!-- Email urgence -->
                            <div class="col-12 col-md-4">
                                <label for="urgence_email" class="form-label">
                                    <i class="fas fa-envelope text-dark me-1"></i>
                                    Email
                                </label>
                                <input type="email" class="form-control" id="urgence_email" name="urgence_email" 
                                       value="<?= htmlspecialchars($resident->urgence_email ?? '') ?>">
                            </div>
                            
                        </div>
                    </div>
                </div>
                
                <!-- Remarques -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-comment-alt me-2"></i>
                            Remarques
                        </h5>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" id="notes" name="notes" rows="4" 
                                  placeholder="Informations complémentaires..."><?= htmlspecialchars($resident->notes ?? '') ?></textarea>
                    </div>
                </div>
                
            </div>
            
            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                
                <!-- Statut -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-toggle-on me-2"></i>
                            Statut
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="actif" name="actif" 
                                   <?= ($resident->actif ?? 1) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="actif">
                                <strong>Résident actif</strong>
                            </label>
                        </div>
                        <small class="text-muted">
                            (Dé)cochez pour (dés)activer le résident
                        </small>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-warning btn-lg">
                                <i class="fas fa-save me-2"></i>
                                Enregistrer les modifications
                            </button>
                            <a href="<?= BASE_URL ?>/resident/show/<?= $resident->id ?>" class="btn btn-secondary">
                                <i class="fas fa-times me-2"></i>
                                Annuler
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="card shadow-sm border-info">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Informations
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0 small">
                            <li class="mb-2">
                                <i class="fas fa-calendar text-info me-2"></i>
                                <strong>Date d'entrée :</strong><br>
                                <?= $resident->date_entree ? date('d/m/Y', strtotime($resident->date_entree)) : 'Non renseignée' ?>
                            </li>
                            <?php if ($resident->age): ?>
                            <li class="mb-2">
                                <i class="fas fa-birthday-cake text-info me-2"></i>
                                <strong>Âge :</strong> <?= $resident->age ?> ans
                            </li>
                            <?php endif; ?>
                            <li>
                                <i class="fas fa-hashtag text-info me-2"></i>
                                <strong>ID :</strong> #<?= $resident->id ?>
                            </li>
                        </ul>
                    </div>
                </div>
                
            </div>
        </div>
    </form>
    
</div>
