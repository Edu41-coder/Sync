<?php $title = "Tous les Lots"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-door-open', 'text' => 'Lots', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-door-open text-dark"></i> Tous les Lots</h1>
            <a href="<?= BASE_URL ?>/lot/create" class="btn btn-danger">
                <i class="fas fa-plus me-1"></i>Nouveau lot
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Total lots</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-success mb-1 fw-bold small">Occupés</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['occupes'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning mb-1 fw-bold small">Libres</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['libres'] ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher lot, résidence, résident...">
                </div>
                <div class="col-12 col-md-3">
                    <select id="filterResidence" class="form-select form-select-sm">
                        <option value="">Toutes résidences</option>
                        <?php foreach ($residences as $res): ?>
                        <option value="<?= htmlspecialchars($res['nom']) ?>"><?= htmlspecialchars($res['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select id="filterType" class="form-select form-select-sm">
                        <option value="">Tous types</option>
                        <option value="Studio">Studio</option>
                        <option value="T2">T2</option>
                        <option value="T2 bis">T2 Bis</option>
                        <option value="T3">T3</option>
                        <option value="Parking">Parking</option>
                        <option value="Cave">Cave</option>
                    </select>
                </div>
                <div class="col-12 col-md-2">
                    <select id="filterStatut" class="form-select form-select-sm">
                        <option value="">Tous statuts</option>
                        <option value="Occupé">Occupé</option>
                        <option value="Libre">Libre</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="lotsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0">Résidence</th>
                            <th class="sortable" data-column="1">Numéro</th>
                            <th class="sortable" data-column="2">Type</th>
                            <th class="sortable text-center" data-column="3" data-type="number">Surface</th>
                            <th class="sortable text-center" data-column="4">Terrasse</th>
                            <th class="sortable text-center" data-column="5" data-type="number">Tantièmes</th>
                            <th class="sortable text-center" data-column="6">Statut</th>
                            <th class="sortable" data-column="7">Résident</th>
                            <th class="text-center" data-no-sort style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($lots)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Aucun lot</td></tr>
                        <?php else: ?>
                        <?php
                        $lastResidence = null;
                        foreach ($lots as $lot):
                            $typeLabelMap = ['studio'=>'Studio','t2'=>'T2','t2_bis'=>'T2 Bis','t3'=>'T3','parking'=>'Parking','cave'=>'Cave'];
                            $typeLabel = $typeLabelMap[$lot['type']] ?? ucfirst($lot['type']);
                            $terrasseLabel = $lot['terrasse'] === 'oui' ? 'Oui' : ($lot['terrasse'] === 'loggia' ? 'Loggia' : 'Non');
                        ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($lot['residence_nom']) ?>">
                                <?php if ($lot['residence_nom'] !== $lastResidence): ?>
                                <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $lot['residence_id'] ?>" class="text-decoration-none">
                                    <strong><?= htmlspecialchars($lot['residence_nom']) ?></strong>
                                </a>
                                <br><small class="text-muted"><?= htmlspecialchars($lot['residence_ville'] ?? '') ?></small>
                                <?php $lastResidence = $lot['residence_nom']; ?>
                                <?php else: ?>
                                <span class="text-muted small"><?= htmlspecialchars($lot['residence_nom']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($lot['numero_lot']) ?>">
                                <strong><?= htmlspecialchars($lot['numero_lot']) ?></strong>
                            </td>
                            <td data-sort="<?= $typeLabel ?>">
                                <span class="badge bg-secondary"><?= $typeLabel ?></span>
                            </td>
                            <td class="text-center" data-sort="<?= $lot['surface'] ?? 0 ?>">
                                <?= $lot['surface'] ? $lot['surface'] . ' m²' : '-' ?>
                            </td>
                            <td class="text-center" data-sort="<?= $terrasseLabel ?>">
                                <?php if ($lot['terrasse'] !== 'non'): ?>
                                <span class="badge bg-info"><?= $terrasseLabel ?></span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center" data-sort="<?= $lot['tantiemes_generaux'] ?? 0 ?>">
                                <?= $lot['tantiemes_generaux'] ?? '-' ?>
                            </td>
                            <td class="text-center" data-sort="<?= $lot['occupation_id'] ? 'Occupé' : 'Libre' ?>">
                                <?php if ($lot['occupation_id']): ?>
                                <span class="badge bg-success">Occupé</span>
                                <?php else: ?>
                                <span class="badge bg-warning">Libre</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($lot['resident_nom'] ?? '') ?>">
                                <?php if ($lot['resident_nom']): ?>
                                <i class="fas fa-user me-1 text-muted"></i><?= htmlspecialchars($lot['resident_nom']) ?>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/lot/show/<?= $lot['id'] ?>" class="btn btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                    <a href="<?= BASE_URL ?>/lot/edit/<?= $lot['id'] ?>" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo">
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($lots)) ?></span>
                sur <span id="totalEntries"><?= count($lots) ?></span> lots
            </div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('lotsTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [
        { id: 'filterResidence', column: 0 },
        { id: 'filterType', column: 2 },
        { id: 'filterStatut', column: 6 }
    ],
    excludeColumns: [8],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
