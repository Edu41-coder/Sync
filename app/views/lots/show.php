<?php $title = "Détails du lot"; ?>

<div class="container-fluid py-4">
    
    <?php
    // Vérifier si le lot existe
    if (!isset($lot) || !$lot):
    ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Lot introuvable
        </div>
        <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour aux résidences
        </a>
    <?php
        return;
    endif;
    ?>
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-building', 'text' => $lot['residence_nom'], 'url' => BASE_URL . '/admin/viewResidence/' . $lot['copropriete_id']],
        ['icon' => 'fas fa-door-open', 'text' => 'Lot ' . $lot['numero_lot'], 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-door-open text-dark"></i>
                    Lot <?= htmlspecialchars($lot['numero_lot']) ?>
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-building me-1"></i>
                    <?= htmlspecialchars($lot['residence_nom']) ?> - 
                    <?= htmlspecialchars($lot['ville']) ?>
                </p>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/lot/edit/<?= $lot['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $lot['copropriete_id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations principales -->
        <div class="col-12 col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Informations du lot
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Numéro -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Numéro de lot</label>
                            <div class="fw-bold fs-5">
                                <i class="fas fa-hashtag text-dark me-2"></i>
                                <?= htmlspecialchars($lot['numero_lot']) ?>
                            </div>
                        </div>
                        
                        <!-- Type -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Type</label>
                            <div class="fw-bold fs-5">
                                <i class="fas fa-home text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($lot['type']) ?></span>
                            </div>
                        </div>
                        
                        <!-- Surface -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Surface</label>
                            <div class="fw-bold">
                                <i class="fas fa-ruler-combined text-dark me-2"></i>
                                <?= $lot['surface'] ? number_format($lot['surface'], 2, ',', ' ') . ' m²' : '-' ?>
                            </div>
                        </div>
                        
                        <!-- Nombre de pièces -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Nombre de pièces</label>
                            <div class="fw-bold">
                                <i class="fas fa-door-closed text-dark me-2"></i>
                                <?= $lot['nombre_pieces'] ?? '-' ?>
                            </div>
                        </div>
                        
                        <!-- Étage -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Étage</label>
                            <div class="fw-bold">
                                <i class="fas fa-layer-group text-dark me-2"></i>
                                <?php
                                if ($lot['etage'] === null) {
                                    echo '-';
                                } elseif ($lot['etage'] == 0) {
                                    echo 'RDC';
                                } elseif ($lot['etage'] < 0) {
                                    echo 'Sous-sol ' . abs($lot['etage']);
                                } else {
                                    echo $lot['etage'];
                                }
                                ?>
                            </div>
                        </div>
                        
                        <!-- Tantièmes généraux -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Tantièmes généraux</label>
                            <div class="fw-bold">
                                <i class="fas fa-percent text-dark me-2"></i>
                                <?= number_format($lot['tantiemes_generaux'] ?? 0, 0, ',', ' ') ?>
                            </div>
                        </div>
                        
                        <!-- Tantièmes chauffage -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Tantièmes chauffage</label>
                            <div class="fw-bold">
                                <i class="fas fa-fire text-danger me-2"></i>
                                <?= number_format($lot['tantiemes_chauffage'] ?? 0, 0, ',', ' ') ?>
                            </div>
                        </div>
                        
                        <!-- Tantièmes ascenseur -->
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1">Tantièmes ascenseur</label>
                            <div class="fw-bold">
                                <i class="fas fa-elevator text-info me-2"></i>
                                <?= number_format($lot['tantiemes_ascenseur'] ?? 0, 0, ',', ' ') ?>
                            </div>
                        </div>
                        
                        <!-- Description -->
                        <?php if (!empty($lot['description'])): ?>
                        <div class="col-12">
                            <label class="text-muted small mb-1">Description</label>
                            <div class="alert alert-light mb-0">
                                <i class="fas fa-info-circle text-info me-2"></i>
                                <?= nl2br(htmlspecialchars($lot['description'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Occupation actuelle -->
            <?php if (!empty($lot['occupation_id'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-check me-2"></i>
                        Occupation actuelle
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <!-- Résident -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Résident</label>
                            <div class="fw-bold fs-5">
                                <i class="fas fa-user text-dark me-2"></i>
                                <?= htmlspecialchars($lot['resident_nom']) ?>
                            </div>
                        </div>
                        
                        <!-- Téléphone -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Téléphone</label>
                            <div class="fw-bold">
                                <i class="fas fa-phone text-dark me-2"></i>
                                <a href="tel:<?= $lot['resident_telephone'] ?>"><?= htmlspecialchars($lot['resident_telephone']) ?></a>
                            </div>
                        </div>
                        
                        <!-- Email -->
                        <?php if (!empty($lot['resident_email'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Email</label>
                            <div class="fw-bold">
                                <i class="fas fa-envelope text-dark me-2"></i>
                                <a href="mailto:<?= $lot['resident_email'] ?>"><?= htmlspecialchars($lot['resident_email']) ?></a>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Niveau d'autonomie -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Niveau d'autonomie</label>
                            <div class="fw-bold">
                                <i class="fas fa-heartbeat text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($lot['niveau_autonomie'] ?? '-') ?></span>
                            </div>
                        </div>
                        
                        <!-- Date d'entrée -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Date d'entrée</label>
                            <div class="fw-bold">
                                <i class="fas fa-calendar text-dark me-2"></i>
                                <?= date('d/m/Y', strtotime($lot['date_entree'])) ?>
                            </div>
                        </div>
                        
                        <!-- Loyer mensuel -->
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Loyer mensuel</label>
                            <div class="fw-bold fs-5 text-success">
                                <i class="fas fa-euro-sign me-2"></i>
                                <?= number_format($lot['loyer_mensuel_resident'], 2, ',', ' ') ?> €
                            </div>
                        </div>
                        
                        <!-- Type de forfait -->
                        <?php if (!empty($lot['forfait_type'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Type de forfait</label>
                            <div class="fw-bold">
                                <i class="fas fa-tag text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($lot['forfait_type']) ?></span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3">
                        <a href="<?= BASE_URL ?>/occupation/show/<?= $lot['occupation_id'] ?>" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-eye me-1"></i>Voir les détails de l'occupation
                        </a>
                        <a href="<?= BASE_URL ?>/resident/show/<?= $lot['resident_id'] ?>?from_lot=<?= $lot['id'] ?>" class="btn btn-outline-info btn-sm">
                            <i class="fas fa-user me-1"></i>Voir le profil du résident
                        </a>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="card shadow mb-4 border-warning">
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-4x text-muted mb-3"></i>
                    <h5 class="text-muted">Lot non occupé</h5>
                    <p class="text-muted mb-3">Aucun résident n'occupe actuellement ce lot</p>
                    <a href="<?= BASE_URL ?>/occupation/create/<?= $lot['id'] ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Créer une occupation
                    </a>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Carte résidence -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-building text-dark me-2"></i>
                        Résidence
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3"><?= htmlspecialchars($lot['residence_nom']) ?></h5>
                    <div class="small">
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <?= htmlspecialchars($lot['adresse']) ?><br>
                            <?= htmlspecialchars($lot['code_postal']) ?> <?= htmlspecialchars($lot['ville']) ?>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $lot['copropriete_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">
                        <i class="fas fa-arrow-right me-1"></i>Voir la résidence
                    </a>
                </div>
            </div>
            
            <!-- Carte informations système -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-info-circle text-dark me-2"></i>
                        Informations système
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <small class="text-muted">ID du lot</small><br>
                        <span class="fw-bold">#<?= $lot['id'] ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Créé le</small><br>
                        <span class="fw-bold"><?= date('d/m/Y à H:i', strtotime($lot['created_at'])) ?></span>
                    </div>
                    <div class="mb-2">
                        <small class="text-muted">Modifié le</small><br>
                        <span class="fw-bold"><?= date('d/m/Y à H:i', strtotime($lot['updated_at'])) ?></span>
                    </div>
                    <div class="mb-0">
                        <small class="text-muted">Statut d'occupation</small><br>
                        <?php if (!empty($lot['occupation_id'])): ?>
                            <span class="badge bg-success">
                                <i class="fas fa-check-circle me-1"></i>Occupé
                            </span>
                        <?php else: ?>
                            <span class="badge bg-warning">
                                <i class="fas fa-exclamation-triangle me-1"></i>Disponible
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
