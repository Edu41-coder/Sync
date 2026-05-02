<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',      'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-door-open',      'text' => 'Mes lots',         'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-door-open fa-2x text-primary me-3"></i>
        <h1 class="h3 mb-0">Mes lots</h1>
    </div>

    <?php if (empty($occupations)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun lot actif pour le moment.</div>
    <?php else: ?>

    <div class="row g-3">
        <?php foreach ($occupations as $o): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><?= htmlspecialchars($o['residence_nom']) ?></strong>
                    <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($o['lot_type']) ?></span>
                </div>
                <div class="card-body">
                    <p class="mb-1"><i class="fas fa-hashtag text-muted me-1"></i> Lot <strong><?= htmlspecialchars($o['numero_lot']) ?></strong></p>
                    <?php if (!empty($o['surface'])): ?>
                    <p class="mb-1"><i class="fas fa-ruler text-muted me-1"></i> <?= (float)$o['surface'] ?> m²</p>
                    <?php endif; ?>
                    <?php if (!empty($o['etage'])): ?>
                    <p class="mb-1"><i class="fas fa-building text-muted me-1"></i> Étage <?= htmlspecialchars($o['etage']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><i class="fas fa-map-marker-alt text-muted me-1"></i> <?= htmlspecialchars($o['ville'] ?? '') ?></p>
                    <p class="mb-1"><i class="fas fa-euro-sign text-muted me-1"></i> Loyer : <strong><?= number_format((float)$o['loyer_mensuel_resident'], 0, ',', ' ') ?> €</strong> / mois</p>
                    <p class="mb-0 text-muted small">Entrée : <?= !empty($o['date_entree']) ? date('d/m/Y', strtotime($o['date_entree'])) : '-' ?></p>
                </div>
                <div class="card-footer bg-light text-end">
                    <a href="<?= BASE_URL ?>/occupation/show/<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Occupation
                    </a>
                    <a href="<?= BASE_URL ?>/lot/show/<?= (int)$o['lot_id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-door-open me-1"></i>Lot
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
