<?php $title = "Mes Lots"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-door-open', 'text' => 'Mes Lots', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabelMap = ['studio'=>'Studio','t2'=>'T2','t2_bis'=>'T2 Bis','t3'=>'T3','parking'=>'Parking','cave'=>'Cave'];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12">
            <h1 class="h3"><i class="fas fa-door-open text-dark"></i> Mes Lots</h1>
        </div>
    </div>

    <?php if (empty($lots)): ?>
    <div class="card shadow">
        <div class="card-body text-center py-5">
            <i class="fas fa-door-open fa-3x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Aucun lot</h5>
            <p class="text-muted">Vous n'avez pas encore de contrat de gestion actif.</p>
        </div>
    </div>
    <?php else: ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Total lots</h6>
                    <h3 class="mb-0"><?= count($lots) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Revenus / mois</h6>
                    <h3 class="mb-0"><?= number_format(array_sum(array_column($lots, 'loyer_mensuel_garanti')), 0, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Résidences</h6>
                    <h3 class="mb-0"><?= count(array_unique(array_column($lots, 'residence_id'))) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <?php if (count($lots) > 3): ?>
            <input type="text" id="searchLot" class="form-control form-control-sm" style="max-width:300px" placeholder="Rechercher lot, résidence...">
            <?php endif; ?>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="lotsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0">Lot</th>
                            <th class="sortable" data-column="1">Type</th>
                            <th class="sortable text-center" data-column="2" data-type="number">Surface</th>
                            <th class="sortable" data-column="3">Résidence</th>
                            <th class="sortable text-center" data-column="4">Terrasse</th>
                            <th class="sortable text-end" data-column="5" data-type="number">Loyer garanti</th>
                            <th class="text-center" data-no-sort>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lots as $l): ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($l['numero_lot']) ?>">
                                <strong><?= htmlspecialchars($l['numero_lot']) ?></strong>
                                <br><small class="text-muted">Contrat <?= htmlspecialchars($l['numero_contrat'] ?? '-') ?></small>
                            </td>
                            <td data-sort="<?= $typeLabelMap[$l['type']] ?? $l['type'] ?>">
                                <span class="badge bg-secondary"><?= $typeLabelMap[$l['type']] ?? ucfirst($l['type']) ?></span>
                            </td>
                            <td class="text-center" data-sort="<?= $l['surface'] ?? 0 ?>">
                                <?= $l['surface'] ? $l['surface'] . ' m²' : '-' ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($l['residence_nom']) ?>">
                                <?= htmlspecialchars($l['residence_nom']) ?>
                                <br><small class="text-muted"><?= htmlspecialchars($l['residence_ville'] ?? '') ?></small>
                            </td>
                            <td class="text-center" data-sort="<?= $l['terrasse'] ?? 'non' ?>">
                                <?php if (($l['terrasse'] ?? 'non') !== 'non'): ?>
                                <span class="badge bg-info"><?= $l['terrasse'] === 'loggia' ? 'Loggia' : 'Oui' ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-success" data-sort="<?= $l['loyer_mensuel_garanti'] ?? 0 ?>">
                                <?= number_format($l['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/lot/show/<?= $l['id'] ?>?from=mesLots" class="btn btn-outline-primary" title="Voir le lot">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $l['residence_id'] ?>?from=mesLots" class="btn btn-outline-secondary" title="Voir la résidence">
                                        <i class="fas fa-building"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if (count($lots) > 10): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo">
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($lots)) ?></span>
                sur <span id="totalEntries"><?= count($lots) ?></span> lots
            </div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php if (count($lots ?? []) > 3): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('lotsTable', {
    rowsPerPage: 10,
    searchInputId: 'searchLot',
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
