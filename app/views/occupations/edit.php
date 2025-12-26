<?php $title = "Modifier l'occupation"; ?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-building', 'text' => $occupation['residence_nom'], 'url' => BASE_URL . '/admin/viewResidence/' . $occupation['residence_id']],
        ['icon' => 'fas fa-user-check', 'text' => 'Occupation', 'url' => BASE_URL . '/occupation/show/' . $occupation['id']],
        ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3">
                    <i class="fas fa-edit text-warning"></i>
                    Modifier l'occupation
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-user me-1"></i><?= htmlspecialchars($occupation['resident_nom']) ?> - 
                    <i class="fas fa-door-open me-1"></i>Lot <?= htmlspecialchars($occupation['numero_lot']) ?>
                </p>
            </div>
            <a href="<?= BASE_URL ?>/occupation/show/<?= $occupation['id'] ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>
    
    <form method="POST" action="<?= BASE_URL ?>/occupation/edit/<?= $occupation['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
        
        <div class="row">
            <div class="col-12 col-lg-8">
                <!-- Informations financières -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-euro-sign me-2"></i>
                            Informations financières
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- Loyer mensuel -->
                            <div class="col-12 col-md-6">
                                <label for="loyer_mensuel_resident" class="form-label">
                                    <i class="fas fa-home text-dark me-1"></i>
                                    Loyer mensuel résident <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="loyer_mensuel_resident" 
                                           name="loyer_mensuel_resident" 
                                           step="0.01" 
                                           value="<?= $occupation['loyer_mensuel_resident'] ?>" 
                                           required>
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Charges mensuelles -->
                            <div class="col-12 col-md-6">
                                <label for="charges_mensuelles_resident" class="form-label">
                                    <i class="fas fa-file-invoice-dollar text-dark me-1"></i>
                                    Charges mensuelles
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="charges_mensuelles_resident" 
                                           name="charges_mensuelles_resident" 
                                           step="0.01" 
                                           value="<?= $occupation['charges_mensuelles_resident'] ?? 0 ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Type de forfait -->
                            <div class="col-12 col-md-6">
                                <label for="forfait_type" class="form-label">
                                    <i class="fas fa-tag text-dark me-1"></i>
                                    Type de forfait
                                </label>
                                <select class="form-select" id="forfait_type" name="forfait_type">
                                    <option value="essentiel" <?= $occupation['forfait_type'] === 'essentiel' ? 'selected' : '' ?>>Essentiel</option>
                                    <option value="confort" <?= $occupation['forfait_type'] === 'confort' ? 'selected' : '' ?>>Confort</option>
                                    <option value="premium" <?= $occupation['forfait_type'] === 'premium' ? 'selected' : '' ?>>Premium</option>
                                </select>
                            </div>
                            
                            <!-- Services supplémentaires montant -->
                            <div class="col-12 col-md-6">
                                <label for="montant_services_sup" class="form-label">
                                    <i class="fas fa-plus-circle text-dark me-1"></i>
                                    Services supplémentaires
                                </label>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_services_sup" 
                                           name="montant_services_sup" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_services_sup'] ?? 0 ?>">
                                    <span class="input-group-text">€</span>
                                </div>
                            </div>
                            
                            <!-- Mode de paiement -->
                            <div class="col-12 col-md-6">
                                <label for="mode_paiement" class="form-label">
                                    <i class="fas fa-credit-card text-dark me-1"></i>
                                    Mode de paiement
                                </label>
                                <select class="form-select" id="mode_paiement" name="mode_paiement">
                                    <option value="prelevement" <?= $occupation['mode_paiement'] === 'prelevement' ? 'selected' : '' ?>>Prélèvement</option>
                                    <option value="virement" <?= $occupation['mode_paiement'] === 'virement' ? 'selected' : '' ?>>Virement</option>
                                    <option value="cheque" <?= $occupation['mode_paiement'] === 'cheque' ? 'selected' : '' ?>>Chèque</option>
                                </select>
                            </div>
                            
                            <!-- Jour de prélèvement -->
                            <div class="col-12 col-md-6">
                                <label for="jour_prelevement" class="form-label">
                                    <i class="fas fa-calendar-day text-dark me-1"></i>
                                    Jour de prélèvement
                                </label>
                                <input type="number" 
                                       class="form-control" 
                                       id="jour_prelevement" 
                                       name="jour_prelevement" 
                                       min="1" 
                                       max="28" 
                                       value="<?= $occupation['jour_prelevement'] ?? 5 ?>">
                            </div>
                            
                            <!-- Services inclus -->
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="fas fa-check-circle text-dark me-1"></i>
                                    Services inclus
                                </label>
                                <?php
                                $servicesInclus = json_decode($occupation['services_inclus'] ?? '{}', true) ?: [];
                                $servicesOptions = [
                                    'wifi' => 'Wi-Fi',
                                    'telephone' => 'Téléphone',
                                    'animations' => 'Animations',
                                    'assistance_24h' => 'Assistance 24h/24',
                                    'entretien_espaces_communs' => 'Entretien des espaces communs',
                                    'restaurant_midi' => 'Restaurant midi',
                                    'restaurant_soir' => 'Restaurant soir',
                                    'menage_hebdomadaire' => 'Ménage hebdomadaire',
                                    'blanchisserie' => 'Blanchisserie',
                                    'coiffeur' => 'Coiffeur',
                                    'podologue' => 'Podologue',
                                    'kine' => 'Kinésithérapeute'
                                ];
                                ?>
                                <div class="row g-2">
                                    <?php foreach ($servicesOptions as $key => $label): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="services_inclus[<?= $key ?>]" 
                                                   id="service_<?= $key ?>" 
                                                   <?= !empty($servicesInclus[$key]) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="service_<?= $key ?>">
                                                <?= htmlspecialchars($label) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Services supplémentaires -->
                            <div class="col-12">
                                <label class="form-label">
                                    <i class="fas fa-plus-circle text-dark me-1"></i>
                                    Services supplémentaires
                                </label>
                                <?php
                                $servicesSup = json_decode($occupation['services_supplementaires'] ?? '{}', true) ?: [];
                                $servicesSupOptions = [
                                    'coiffure' => 'Coiffure',
                                    'podologie' => 'Podologie',
                                    'kinesitherapie' => 'Kinésithérapie',
                                    'menage_supplementaire' => 'Ménage supplémentaire',
                                    'blanchisserie_sup' => 'Blanchisserie supplémentaire',
                                    'repas_supplementaires' => 'Repas supplémentaires',
                                    'sorties' => 'Sorties et excursions',
                                    'accompagnement_medical' => 'Accompagnement médical'
                                ];
                                ?>
                                <div class="row g-2">
                                    <?php foreach ($servicesSupOptions as $key => $label): ?>
                                    <div class="col-12 col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   name="services_supplementaires[<?= $key ?>]" 
                                                   id="service_sup_<?= $key ?>" 
                                                   <?= !empty($servicesSup[$key]) ? 'checked' : '' ?>>
                                            <label class="form-check-label" for="service_sup_<?= $key ?>">
                                                <?= htmlspecialchars($label) ?>
                                            </label>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Aides sociales -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-hands-helping me-2"></i>
                            Aides sociales
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <!-- APL -->
                            <div class="col-12 col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="beneficie_apl" 
                                           name="beneficie_apl" 
                                           <?= $occupation['beneficie_apl'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="beneficie_apl">
                                        <i class="fas fa-home text-dark me-1"></i>
                                        Bénéficie de l'APL
                                    </label>
                                </div>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_apl" 
                                           name="montant_apl" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_apl'] ?? '' ?>" 
                                           placeholder="Montant">
                                    <span class="input-group-text">€/mois</span>
                                </div>
                            </div>
                            
                            <!-- APA -->
                            <div class="col-12 col-md-6">
                                <div class="form-check mb-2">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           id="beneficie_apa" 
                                           name="beneficie_apa" 
                                           <?= $occupation['beneficie_apa'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="beneficie_apa">
                                        <i class="fas fa-heartbeat text-dark me-1"></i>
                                        Bénéficie de l'APA
                                    </label>
                                </div>
                                <div class="input-group">
                                    <input type="number" 
                                           class="form-control" 
                                           id="montant_apa" 
                                           name="montant_apa" 
                                           step="0.01" 
                                           value="<?= $occupation['montant_apa'] ?? '' ?>" 
                                           placeholder="Montant">
                                    <span class="input-group-text">€/mois</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Notes -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-sticky-note text-dark me-2"></i>
                            Notes
                        </h6>
                    </div>
                    <div class="card-body">
                        <textarea class="form-control" 
                                  id="notes" 
                                  name="notes" 
                                  rows="4"><?= htmlspecialchars($occupation['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="col-12 col-lg-4">
                <!-- Statut -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-dark me-2"></i>
                            Statut
                        </h6>
                    </div>
                    <div class="card-body">
                        <label for="statut" class="form-label">Statut de l'occupation</label>
                        <select class="form-select" id="statut" name="statut">
                            <option value="actif" <?= $occupation['statut'] === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="preavis" <?= $occupation['statut'] === 'preavis' ? 'selected' : '' ?>>Préavis</option>
                            <option value="termine" <?= $occupation['statut'] === 'termine' ? 'selected' : '' ?>>Terminé</option>
                        </select>
                    </div>
                </div>
                
                <!-- Informations -->
                <div class="card shadow mb-4">
                    <div class="card-header bg-light">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle text-dark me-2"></i>
                            Informations
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <small class="text-muted">Résident</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['resident_nom']) ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Lot</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['numero_lot']) ?></div>
                        </div>
                        <div class="mb-3">
                            <small class="text-muted">Résidence</small>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['residence_nom']) ?></div>
                        </div>
                        <div>
                            <small class="text-muted">Date d'entrée</small>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($occupation['date_entree'])) ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Actions -->
                <div class="card shadow">
                    <div class="card-body">
                        <button type="submit" class="btn btn-warning w-100 mb-2">
                            <i class="fas fa-save me-1"></i>
                            Enregistrer les modifications
                        </button>
                        <a href="<?= BASE_URL ?>/occupation/show/<?= $occupation['id'] ?>" class="btn btn-secondary w-100">
                            <i class="fas fa-times me-1"></i>
                            Annuler
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
