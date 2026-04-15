<?php $title = "Modifier le lot"; ?>

<div class="container-fluid py-4">
    
    <!-- Messages flash -->
    <?php require_once __DIR__ . '/../partials/flash.php'; ?>
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-building', 'text' => $residence->nom ?? 'Résidence', 'url' => isset($residence->id) ? BASE_URL . '/admin/viewResidence/' . $residence->id : '#'],
        ['icon' => 'fas fa-edit', 'text' => 'Modifier lot ' . ($lot['numero'] ?? ''), 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-door-open text-warning"></i>
                        Modifier le lot <?= isset($lot['numero']) ? htmlspecialchars($lot['numero']) : '' ?>
                    </h1>
                    <?php if (isset($residence)): ?>
                    <p class="text-muted mb-0">
                        Résidence : <strong><?= htmlspecialchars($residence->nom) ?></strong>
                    </p>
                    <?php endif; ?>
                </div>
                <div>
                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $residence->id ?? '' ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Formulaire -->
    <div class="row">
        <div class="col-12 col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-warning">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations du lot
                    </h5>
                </div>
                
                <div class="card-body">
                    <form action="<?= BASE_URL ?>/lot/update/<?= $lot['id'] ?? '' ?>" method="POST" id="lotForm">
                        <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                        <input type="hidden" name="copropriete_id" value="<?= $residence->id ?? '' ?>">
                        
                        <div class="row g-3">
                            <!-- Numéro de lot -->
                            <div class="col-12 col-md-6">
                                <label for="numero" class="form-label">
                                    Numéro de lot <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-hashtag"></i>
                                    </span>
                                    <input type="text" 
                                           class="form-control" 
                                           id="numero" 
                                           name="numero" 
                                           required 
                                           value="<?= htmlspecialchars($lot['numero'] ?? '') ?>"
                                           placeholder="Ex: 101, A12...">
                                </div>
                            </div>
                            
                            <!-- Type -->
                            <div class="col-12 col-md-6">
                                <label for="type" class="form-label">
                                    Type <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-home"></i>
                                    </span>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="">-- Sélectionner --</option>
                                        <option value="studio" <?= (isset($lot['type']) && $lot['type'] === 'studio') ? 'selected' : '' ?>>Studio</option>
                                        <option value="t2" <?= (isset($lot['type']) && $lot['type'] === 't2') ? 'selected' : '' ?>>T2</option>
                                        <option value="t2_bis" <?= (isset($lot['type']) && $lot['type'] === 't2_bis') ? 'selected' : '' ?>>T2 Bis</option>
                                        <option value="t3" <?= (isset($lot['type']) && $lot['type'] === 't3') ? 'selected' : '' ?>>T3</option>
                                        <option value="parking" <?= (isset($lot['type']) && $lot['type'] === 'parking') ? 'selected' : '' ?>>Parking</option>
                                        <option value="cave" <?= (isset($lot['type']) && $lot['type'] === 'cave') ? 'selected' : '' ?>>Cave</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Surface -->
                            <div class="col-12 col-md-4">
                                <label for="surface" class="form-label">
                                    Surface (m²)
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-ruler-combined"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="surface" 
                                           name="surface" 
                                           step="0.01"
                                           min="0"
                                           value="<?= $lot['surface'] ?? '' ?>"
                                           placeholder="Ex: 45.50">
                                    <span class="input-group-text">m²</span>
                                </div>
                            </div>
                            
                            <!-- Nombre de pièces -->
                            <div class="col-12 col-md-4">
                                <label for="nombre_pieces" class="form-label">
                                    Nombre de pièces
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-door-closed"></i>
                                    </span>
                                    <input type="number"
                                           class="form-control"
                                           id="nombre_pieces"
                                           name="nombre_pieces"
                                           min="0"
                                           value="<?= $lot['nombre_pieces'] ?? '' ?>"
                                           placeholder="Ex: 2">
                                </div>
                            </div>

                            <!-- Terrasse -->
                            <div class="col-12 col-md-4">
                                <label for="terrasse" class="form-label">Terrasse / Loggia</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-sun"></i></span>
                                    <select class="form-select" id="terrasse" name="terrasse">
                                        <option value="non" <?= ($lot['terrasse'] ?? '') === 'non' ? 'selected' : '' ?>>Non</option>
                                        <option value="oui" <?= ($lot['terrasse'] ?? '') === 'oui' ? 'selected' : '' ?>>Oui</option>
                                        <option value="loggia" <?= ($lot['terrasse'] ?? '') === 'loggia' ? 'selected' : '' ?>>Loggia</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Étage -->
                            <div class="col-12 col-md-4">
                                <label for="etage" class="form-label">
                                    Étage
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-layer-group"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="etage" 
                                           name="etage" 
                                           value="<?= $lot['etage'] ?? '' ?>"
                                           placeholder="Ex: 3">
                                </div>
                                <div class="form-text">
                                    <i class="fas fa-info-circle me-1"></i>
                                    RDC = 0, Sous-sol = -1
                                </div>
                            </div>
                            
                            <!-- Tantièmes généraux -->
                            <div class="col-12 col-md-4">
                                <label for="tantiemes_generaux" class="form-label">
                                    Tantièmes généraux
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-percent"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="tantiemes_generaux" 
                                           name="tantiemes_generaux" 
                                           min="0"
                                           value="<?= $lot['tantiemes_generaux'] ?? '' ?>"
                                           placeholder="Ex: 100">
                                </div>
                            </div>
                            
                            <!-- Tantièmes chauffage -->
                            <div class="col-12 col-md-4">
                                <label for="tantiemes_chauffage" class="form-label">
                                    Tantièmes chauffage
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-fire"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="tantiemes_chauffage" 
                                           name="tantiemes_chauffage" 
                                           min="0"
                                           value="<?= $lot['tantiemes_chauffage'] ?? '' ?>"
                                           placeholder="Ex: 0">
                                </div>
                            </div>
                            
                            <!-- Tantièmes ascenseur -->
                            <div class="col-12 col-md-4">
                                <label for="tantiemes_ascenseur" class="form-label">
                                    Tantièmes ascenseur
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-elevator"></i>
                                    </span>
                                    <input type="number" 
                                           class="form-control" 
                                           id="tantiemes_ascenseur" 
                                           name="tantiemes_ascenseur" 
                                           min="0"
                                           value="<?= $lot['tantiemes_ascenseur'] ?? '' ?>"
                                           placeholder="Ex: 0">
                                </div>
                            </div>
                            
                            <!-- Description -->
                            <div class="col-12">
                                <label for="description" class="form-label">
                                    Description
                                </label>
                                <textarea class="form-control" 
                                          id="description" 
                                          name="description" 
                                          rows="3"
                                          placeholder="Informations complémentaires sur le lot..."><?= htmlspecialchars($lot['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                        
                        <!-- Boutons -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $residence->id ?? '' ?>" class="btn btn-secondary">
                                        <i class="fas fa-times me-1"></i>Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i>Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Sidebar informations -->
        <div class="col-12 col-lg-4 mt-3 mt-lg-0">
            <!-- Carte informations -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-warning">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle text-primary me-2"></i>
                        Informations du lot
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">Créé le</small><br>
                        <span class="fw-bold"><?= isset($lot['created_at']) ? date('d/m/Y à H:i', strtotime($lot['created_at'])) : 'N/A' ?></span>
                    </div>
                    <?php if (isset($lot['updated_at']) && $lot['updated_at']): ?>
                    <div class="mb-2">
                        <small class="text-muted">Modifié le</small><br>
                        <span class="fw-bold"><?= date('d/m/Y à H:i', strtotime($lot['updated_at'])) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Aide -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-lightbulb text-warning me-2"></i>
                        Aide
                    </h6>
                </div>
                <div class="card-body">
                    <h6 class="fw-bold">Informations requises</h6>
                    <ul class="small">
                        <li><strong>Numéro de lot :</strong> Identifiant unique du lot</li>
                        <li><strong>Type :</strong> Nature du bien (studio, T2, T2 Bis, T3, parking, cave)</li>
                    </ul>
                    
                    <h6 class="fw-bold mt-3">Informations optionnelles</h6>
                    <ul class="small">
                        <li><strong>Surface :</strong> En mètres carrés</li>
                        <li><strong>Pièces :</strong> Nombre de pièces principales</li>
                        <li><strong>Étage :</strong> Niveau du lot</li>
                        <li><strong>Tantièmes :</strong> Quotes-parts de copropriété</li>
                    </ul>
                    
                    <div class="alert alert-info mt-3 mb-0">
                        <i class="fas fa-info-circle me-1"></i>
                        <small>
                            Vous pouvez gérer les propriétaires et résidents depuis la page de détail de la résidence.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Validation du formulaire
document.getElementById('lotForm').addEventListener('submit', function(e) {
    const numero = document.getElementById('numero').value.trim();
    const type = document.getElementById('type').value;
    
    if (!numero || !type) {
        e.preventDefault();
        alert('Veuillez remplir tous les champs obligatoires (*)');
        return false;
    }
});
</script>
