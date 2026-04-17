<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-users', 'text' => 'Résidents', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-users me-2"></i>Résidents</h2>
        <?php if (count($residences) > 1): ?>
        <form method="GET" class="d-flex align-items-center gap-2">
            <select name="residence_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="0">Toutes les résidences</option>
                <?php foreach ($residences as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><?= count($residents) ?> résident(s)</h6>
            <div class="d-flex gap-2">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
                <select id="filterRegime" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous régimes</option>
                    <option value="normal">Normal</option>
                    <option value="vegetarien">Végétarien</option>
                    <option value="sans_gluten">Sans gluten</option>
                    <option value="sans_lactose">Sans lactose</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="residentsTable">
                <thead>
                    <tr>
                        <th>Résident</th>
                        <th>Résidence</th>
                        <th>Lot</th>
                        <th>Forfait</th>
                        <th>Régime</th>
                        <th>Allergies</th>
                        <th>Contact</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($residents)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun résident trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($residents as $r): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($r['civilite'] . ' ' . $r['prenom'] . ' ' . $r['nom']) ?></strong>
                            </td>
                            <td><?= htmlspecialchars($r['residence_nom']) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($r['numero_lot']) ?></span> <?= $r['lot_type'] ?></td>
                            <td>
                                <?php
                                $forfaitColors = ['essentiel'=>'primary','confort'=>'info','premium'=>'warning'];
                                $color = $forfaitColors[$r['forfait_type'] ?? ''] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>"><?= ucfirst($r['forfait_type'] ?? 'N/A') ?></span>
                            </td>
                            <td><?= htmlspecialchars($r['regime_alimentaire'] ?? 'normal') ?></td>
                            <td>
                                <?php if (!empty($r['allergies'])): ?>
                                    <span class="badge bg-danger" title="<?= htmlspecialchars($r['allergies']) ?>">
                                        <i class="fas fa-allergies me-1"></i><?= htmlspecialchars(mb_strimwidth($r['allergies'], 0, 20, '...')) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($r['telephone_mobile']): ?>
                                    <a href="tel:<?= htmlspecialchars($r['telephone_mobile']) ?>" class="text-decoration-none"><i class="fas fa-phone me-1"></i></a>
                                <?php endif; ?>
                                <?php if ($r['email']): ?>
                                    <a href="mailto:<?= htmlspecialchars($r['email']) ?>" class="text-decoration-none"><i class="fas fa-envelope me-1"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('residentsTable', {
    rowsPerPage: 15,
    searchInputId: 'searchInput',
    filters: [{ id: 'filterRegime', column: 4 }],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
