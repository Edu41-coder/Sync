<?php
/**
 * Créer une nouvelle résidence senior - Admin
 */
?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-plus', 'text' => 'Créer une résidence', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-plus-circle text-dark"></i>
                Nouvelle Résidence Senior
            </h1>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Informations de la résidence</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/admin/createResidence">
                        <?= csrf_field() ?>
                        
                        <!-- Nom -->
                        <div class="mb-3">
                            <label for="nom" class="form-label">
                                <i class="fas fa-tag me-1"></i>Nom de la résidence <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="nom" 
                                   name="nom" 
                                   placeholder="Ex: Domitys - Les Jardins de Lumière"
                                   required>
                        </div>
                        
                        <!-- Adresse -->
                        <div class="mb-3">
                            <label for="adresse" class="form-label">
                                <i class="fas fa-map-marker-alt me-1"></i>Adresse <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control" 
                                   id="adresse" 
                                   name="adresse" 
                                   placeholder="Ex: 25 Avenue des Fleurs"
                                   required>
                        </div>
                        
                        <!-- Code postal et Ville -->
                        <div class="row">
                            <div class="col-12 col-md-4 mb-3">
                                <label for="code_postal" class="form-label">
                                    <i class="fas fa-hashtag me-1"></i>Code postal <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="code_postal" 
                                       name="code_postal" 
                                       placeholder="69001"
                                       maxlength="10"
                                       required>
                            </div>
                            
                            <div class="col-12 col-md-8 mb-3">
                                <label for="ville" class="form-label">
                                    <i class="fas fa-city me-1"></i>Ville <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control" 
                                       id="ville" 
                                       name="ville" 
                                       placeholder="Lyon"
                                       required>
                            </div>
                        </div>
                        
                        <!-- Exploitant -->
                        <div class="mb-3">
                            <label for="exploitant_id" class="form-label">
                                <i class="fas fa-building me-1"></i>Exploitant
                            </label>
                            <select class="form-select" id="exploitant_id" name="exploitant_id">
                                <option value="">-- Aucun exploitant --</option>
                                <?php foreach ($exploitants as $exp): ?>
                                    <option value="<?= $exp['id'] ?>">
                                        <?= htmlspecialchars($exp['raison_sociale']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i>
                                L'exploitant qui gère cette résidence (ex: Domitys)
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"
                                      placeholder="Description de la résidence, équipements, services..."></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-save"></i> Créer la résidence
                            </button>
                        </div>
                        
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Aide -->
        <div class="col-12 col-lg-4">
            <div class="card shadow-sm bg-light">
                <div class="card-body">
                    <h5 class="card-title">
                        <i class="fas fa-info-circle text-danger"></i> Informations
                    </h5>
                    <p class="card-text">
                        <strong>Type :</strong> Cette résidence sera automatiquement créée comme 
                        <span class="badge bg-danger">Résidence Seniors</span>
                    </p>
                    <hr>
                    <h6><i class="fas fa-check-circle text-success"></i> Après la création :</h6>
                    <ul class="small">
                        <li>Vous pourrez ajouter des lots (appartements)</li>
                        <li>Assigner des résidents aux lots</li>
                        <li>Gérer les occupations et loyers</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>
