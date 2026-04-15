<?php $title = "Mes Résidences"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-home', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-building', 'text' => 'Mes Résidences', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-building text-dark"></i> Mes Résidences</h1>
            <a href="<?= BASE_URL ?>/admin/carteResidences?from=mesResidences" class="btn btn-outline-success">
                <i class="fas fa-map-marked-alt me-1"></i>Voir sur la carte
            </a>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="card shadow">
        <div class="card-body text-center py-5">
            <i class="fas fa-building fa-3x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Aucune résidence</h5>
            <p class="text-muted">Vous n'avez pas encore de contrat de gestion actif.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Résidences</h6>
                    <h3 class="mb-0"><?= count($residences) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Mes lots</h6>
                    <h3 class="mb-0"><?= array_sum(array_column($residences, 'mes_lots')) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Revenus / mois</h6>
                    <h3 class="mb-0"><?= number_format(array_sum(array_column($residences, 'revenus_mensuels')), 0, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes résidences -->
    <div class="row g-4">
        <?php foreach ($residences as $r): ?>
        <div class="col-12 col-md-6">
            <div class="card shadow h-100">
                <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-building me-2"></i><?= htmlspecialchars($r['nom']) ?></h5>
                    <?php if (!empty($r['latitude'])): ?>
                    <a href="<?= BASE_URL ?>/admin/carteResidence/<?= $r['id'] ?>" class="btn btn-sm btn-light" title="Carte">
                        <i class="fas fa-map-marker-alt"></i>
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <div class="row g-2 small">
                        <div class="col-12">
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            <?= htmlspecialchars($r['adresse'] . ', ' . $r['code_postal'] . ' ' . $r['ville']) ?>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Exploitant</span><br>
                            <strong><?= htmlspecialchars($r['exploitant'] ?? 'Domitys') ?></strong>
                        </div>
                        <div class="col-6">
                            <span class="text-muted">Total lots résidence</span><br>
                            <strong><?= $r['total_lots'] ?></strong>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <div>
                        <span class="badge bg-primary me-1"><?= $r['mes_lots'] ?> lot(s)</span>
                        <span class="badge bg-success"><?= number_format($r['revenus_mensuels'], 0, ',', ' ') ?> €/mois</span>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $r['id'] ?>?from=mesResidences" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-eye me-1"></i>Voir mes lots
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
