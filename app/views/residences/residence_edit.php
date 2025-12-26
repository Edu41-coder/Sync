<?php
/**
 * Modifier une résidence senior - Admin
 */
?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3">
                <i class="fas fa-edit text-warning"></i>
                Modifier <?= htmlspecialchars($residence['nom']) ?>
            </h1>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i>Informations de la résidence</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/admin/editResidence/<?= $residence['id'] ?>">
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
                                   value="<?= htmlspecialchars($residence['nom']) ?>"
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
                                   value="<?= htmlspecialchars($residence['adresse']) ?>"
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
                                       value="<?= htmlspecialchars($residence['code_postal']) ?>"
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
                                       value="<?= htmlspecialchars($residence['ville']) ?>"
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
                                    <option value="<?= $exp['id'] ?>" <?= $residence['exploitant_id'] == $exp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($exp['raison_sociale']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Description -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-1"></i>Description
                            </label>
                            <textarea class="form-control" 
                                      id="description" 
                                      name="description" 
                                      rows="4"><?= htmlspecialchars($residence['description'] ?? '') ?></textarea>
                        </div>
                        
                        <hr class="my-4">
                        
                        <!-- Boutons -->
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Annuler
                            </a>
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-save"></i> Enregistrer les modifications
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
                        <i class="fas fa-info-circle text-info"></i> Informations
                    </h5>
                    <p class="card-text">
                        <strong>ID de la résidence :</strong> #<?= $residence['id'] ?><br>
                        <strong>Créée le :</strong> <?= date('d/m/Y', strtotime($residence['created_at'])) ?>
                    </p>
                    <hr>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Attention :</strong> La modification affectera toutes les informations liées à cette résidence.
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
