<?php $statutColors = ['brouillon'=>'secondary','envoyee'=>'primary','livree_partiel'=>'warning','livree'=>'success','facturee'=>'info','annulee'=>'danger']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck me-2 text-info"></i>Commandes Fournisseurs (Ménage)</h2>
        <div class="d-flex gap-2">
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/menage/commandes/create" class="btn btn-info"><i class="fas fa-plus me-2"></i>Nouvelle commande</a>
            <?php endif; ?>
            <select onchange="window.location='?statut='+this.value" class="form-select form-select-sm" style="width:auto">
                <option value="">Tous statuts</option>
                <?php foreach ($statutColors as $k=>$label): ?><option value="<?= $k ?>" <?= ($statut ?? '')===$k?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$k)) ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <span><?= count($commandes) ?> commande(s)</span>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="commandesTable">
                <thead><tr><th>N° Commande</th><th>Date</th><th>Fournisseur</th><th>Résidence</th><th class="text-end">Total TTC</th><th class="text-center">Lignes</th><th class="text-center">Statut</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($commandes)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune commande.</td></tr>
                    <?php else: foreach ($commandes as $c): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/menage/commandes/show/<?= $c['id'] ?>" class="fw-bold"><?= htmlspecialchars($c['numero_commande']) ?></a></td>
                        <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                        <td><?= htmlspecialchars($c['fournisseur_nom']) ?></td>
                        <td><small><?= htmlspecialchars($c['residence_nom']) ?></small></td>
                        <td class="text-end"><strong><?= number_format($c['montant_total_ttc'],2,',',' ') ?> &euro;</strong></td>
                        <td class="text-center"><?= $c['nb_lignes'] ?></td>
                        <td class="text-center"><span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>/menage/commandes/show/<?= $c['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-eye"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfoCmd"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationCmd"></ul></nav>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('commandesTable', { rowsPerPage: 20, searchInputId: 'searchInput', paginationId: 'paginationCmd', infoId: 'tableInfoCmd' });</script>
