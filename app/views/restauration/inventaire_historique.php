<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-boxes-stacked', 'text' => 'Inventaire', 'url' => BASE_URL . '/restauration/inventaire?residence_id=' . ($item['residence_id'] ?? '')],
    ['icon' => 'fas fa-history', 'text' => 'Historique', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <h2 class="mb-4"><i class="fas fa-history me-2"></i>Historique : <?= htmlspecialchars($item['produit_nom']) ?>
        <span class="badge bg-<?= $item['quantite_stock'] <= ($item['seuil_alerte'] ?? 0) && $item['seuil_alerte'] > 0 ? 'danger' : 'success' ?> ms-2">Stock : <?= $item['quantite_stock'] ?> <?= $item['unite'] ?></span>
    </h2>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0"><?= count($mouvements) ?> mouvement(s)</h6>
            <div class="d-flex gap-2 flex-wrap">
                <select id="filterType" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous types</option>
                    <option value="Entree">Entrée</option>
                    <option value="Sortie">Sortie</option>
                    <option value="Ajustement">Ajustement</option>
                </select>
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
            </div>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="historiqueTable">
                <thead><tr><th>Date</th><th>Type</th><th class="text-center">Quantité</th><th>Motif</th><th>Notes</th><th>Par</th></tr></thead>
                <tbody>
                    <?php if (empty($mouvements)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Aucun mouvement enregistré.</td></tr>
                    <?php else: foreach ($mouvements as $m):
                        $typeColors = ['entree'=>'success','sortie'=>'danger','ajustement'=>'warning'];
                        $typeIcons = ['entree'=>'fa-arrow-down','sortie'=>'fa-arrow-up','ajustement'=>'fa-sync'];
                        $signe = $m['type_mouvement'] === 'entree' ? '+' : ($m['type_mouvement'] === 'sortie' ? '-' : '=');
                        $qteSort = ((float)$m['quantite']) * ($m['type_mouvement'] === 'sortie' ? -1 : 1);
                    ?>
                    <tr>
                        <td data-sort="<?= strtotime($m['created_at']) ?>"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                        <td><span class="badge bg-<?= $typeColors[$m['type_mouvement']] ?? 'secondary' ?>"><i class="fas <?= $typeIcons[$m['type_mouvement']] ?? 'fa-exchange' ?> me-1"></i><?= ucfirst($m['type_mouvement']) ?></span></td>
                        <td class="text-center" data-sort="<?= $qteSort ?>"><strong class="text-<?= $m['type_mouvement'] === 'entree' ? 'success' : ($m['type_mouvement'] === 'sortie' ? 'danger' : 'warning') ?>"><?= $signe ?><?= $m['quantite'] ?></strong> <?= $item['unite'] ?></td>
                        <td><?= ucfirst(str_replace('_',' ',$m['motif'])) ?></td>
                        <td><small class="text-muted"><?= htmlspecialchars($m['notes'] ?? '-') ?></small></td>
                        <td><small><?= $m['user_prenom'] ? htmlspecialchars($m['user_prenom'].' '.$m['user_nom']) : '-' ?></small></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center flex-wrap gap-2">
            <a href="<?= BASE_URL ?>/restauration/inventaire?residence_id=<?= $item['residence_id'] ?? '' ?>" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left me-2"></i>Retour inventaire</a>
            <div class="d-flex align-items-center gap-3">
                <div class="text-muted small" id="tableInfo"></div>
                <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($mouvements)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('historiqueTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    filters: [{ id: 'filterType', column: 1 }],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
