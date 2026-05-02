<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',      'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-clipboard-list', 'text' => 'Mes occupations', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex align-items-center mb-4">
        <i class="fas fa-clipboard-list fa-2x text-primary me-3"></i>
        <h1 class="h3 mb-0">Mes occupations</h1>
    </div>

    <h5 class="mb-3"><i class="fas fa-key me-2 text-success"></i>Actives (<?= count($occupationsActives) ?>)</h5>
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-end">
            <input type="text" id="searchActives" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:280px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableActives">
                    <thead class="table-light">
                        <tr>
                            <th>Résidence</th><th>Lot</th><th>Type</th>
                            <th class="text-end">Loyer</th><th>Entrée</th><th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($occupationsActives)): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Aucune occupation active.</td></tr>
                        <?php else: foreach ($occupationsActives as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['residence_nom']) ?></strong></td>
                            <td><?= htmlspecialchars($o['numero_lot']) ?></td>
                            <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($o['lot_type']) ?></span></td>
                            <td class="text-end" data-sort="<?= (float)$o['loyer_mensuel_resident'] ?>"><?= number_format((float)$o['loyer_mensuel_resident'], 0, ',', ' ') ?> €</td>
                            <td data-sort="<?= !empty($o['date_entree']) ? date('Y-m-d', strtotime($o['date_entree'])) : '' ?>"><?= !empty($o['date_entree']) ? date('d/m/Y', strtotime($o['date_entree'])) : '-' ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/occupation/show/<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoActives" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationActives"></ul>
            </div>
        </div>
    </div>

    <?php if (!empty($occupationsHistorique)): ?>
    <h5 class="mb-3"><i class="fas fa-history me-2 text-secondary"></i>Historique</h5>
    <div class="card shadow">
        <div class="card-header d-flex justify-content-end">
            <input type="text" id="searchHisto" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:280px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="tableHisto">
                    <thead class="table-light">
                        <tr><th>Résidence</th><th>Lot</th><th>Type</th><th>Période</th><th>Statut</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($occupationsHistorique as $o): ?>
                        <tr class="<?= $o['statut'] !== 'actif' ? 'text-muted' : '' ?>">
                            <td><?= htmlspecialchars($o['residence_nom']) ?></td>
                            <td><?= htmlspecialchars($o['numero_lot']) ?></td>
                            <td><?= htmlspecialchars($o['lot_type']) ?></td>
                            <td data-sort="<?= !empty($o['date_entree']) ? date('Y-m-d', strtotime($o['date_entree'])) : '' ?>">
                                <?= !empty($o['date_entree']) ? date('d/m/Y', strtotime($o['date_entree'])) : '-' ?>
                                →
                                <?= !empty($o['date_sortie']) ? date('d/m/Y', strtotime($o['date_sortie'])) : '<em>en cours</em>' ?>
                            </td>
                            <td><span class="badge bg-<?= $o['statut'] === 'actif' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($o['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoHisto" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationHisto"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof DataTableWithPagination === 'undefined') return;
    new DataTableWithPagination('tableActives', {
        rowsPerPage: 10, searchInputId: 'searchActives',
        paginationId: 'paginationActives', infoId: 'infoActives', excludeColumns: [5]
    });
    if (document.getElementById('tableHisto')) {
        new DataTableWithPagination('tableHisto', {
            rowsPerPage: 10, searchInputId: 'searchHisto',
            paginationId: 'paginationHisto', infoId: 'infoHisto'
        });
    }
});
</script>
