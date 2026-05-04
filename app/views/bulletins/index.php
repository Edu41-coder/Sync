<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-file-invoice',   'text' => 'Bulletins de paie', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['brouillon' => 'secondary', 'valide' => 'info', 'emis' => 'success', 'annule' => 'danger'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-file-invoice me-2 text-primary"></i>Bulletins de paie
            <small class="text-muted fs-6">— <?= count($bulletins) ?> bulletin(s)</small>
        </h2>
        <a href="<?= BASE_URL ?>/bulletinPaie/create" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Nouveau bulletin
        </a>
    </div>

    <div class="alert alert-warning small mb-3">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Pilote vitrine.</strong> Les bulletins générés portent un watermark « PILOTE — Document non contractuel » et ne doivent pas être remis tels quels au salarié pour usage légal sans validation expert-comptable.
    </div>

    <!-- Filtres -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php for ($y = (int)date('Y') + 1; $y >= (int)date('Y') - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Mois</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($moisLabels as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $mois == $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($statuts as $k => $v): ?>
                        <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher (nom, prénom)...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($bulletins)): ?>
            <p class="text-center py-5 text-muted mb-0">Aucun bulletin trouvé pour ces filtres.</p>
            <?php else: ?>
            <table class="table table-hover mb-0" id="bulletinsTable">
                <thead class="table-light">
                    <tr>
                        <th>Période</th>
                        <th>Salarié</th>
                        <th>Convention</th>
                        <th class="text-end">Brut</th>
                        <th class="text-end">Net à payer</th>
                        <th class="text-end">Coût employeur</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bulletins as $b):
                        $col = $statutColors[$b['statut']] ?? 'secondary';
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($moisLabels[$b['periode_mois']] ?? '') ?> <?= (int)$b['periode_annee'] ?></strong>
                        </td>
                        <td>
                            <?= htmlspecialchars(trim($b['snapshot_prenom'] . ' ' . $b['snapshot_nom'])) ?>
                            <?php if (!empty($b['username'])): ?><br><small class="text-muted">@<?= htmlspecialchars($b['username']) ?></small><?php endif; ?>
                        </td>
                        <td class="small"><?= htmlspecialchars($b['snapshot_convention_nom'] ?? '—') ?></td>
                        <td class="text-end"><?= number_format((float)$b['total_brut'], 2, ',', ' ') ?> €</td>
                        <td class="text-end"><strong class="text-success"><?= number_format((float)$b['net_a_payer'], 2, ',', ' ') ?> €</strong></td>
                        <td class="text-end"><?= number_format((float)$b['cout_employeur_total'], 2, ',', ' ') ?> €</td>
                        <td class="text-center">
                            <span class="badge bg-<?= $col ?>"><?= $statuts[$b['statut']] ?? $b['statut'] ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/bulletinPaie/show/<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <a href="<?= BASE_URL ?>/bulletinPaie/print/<?= (int)$b['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Imprimer" target="_blank"><i class="fas fa-print"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($bulletins) && count($bulletins) > 25): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($bulletins)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if (count($bulletins) > 25): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('bulletinsTable', { rowsPerPage: 25, searchInputId: 'searchInput', excludeColumns: [7], paginationId: 'pagination', infoId: 'tableInfo' });</script>
<?php else: ?>
<script>new DataTable('bulletinsTable', { searchInputId: 'searchInput', excludeColumns: [7] });</script>
<?php endif; ?>
<?php endif; ?>
