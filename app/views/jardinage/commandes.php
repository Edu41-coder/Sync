<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutLabels = [
    'brouillon' => 'Brouillon', 'envoyee' => 'Envoyée',
    'livree_partiel' => 'Livrée partiel', 'livree' => 'Livrée',
    'facturee' => 'Facturée', 'annulee' => 'Annulée'
];
$statutColors = [
    'brouillon' => 'secondary', 'envoyee' => 'info',
    'livree_partiel' => 'warning', 'livree' => 'success',
    'facturee' => 'primary', 'annulee' => 'dark'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-truck me-2 text-success"></i>Commandes Jardinage</h2>
        <a href="<?= BASE_URL ?>/jardinage/commandes/create" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Nouvelle commande
        </a>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Statut :</label></div>
                <div class="col-auto">
                    <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <?php foreach ($statutLabels as $k => $l): ?>
                        <option value="<?= $k ?>" <?= $statut === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4 ms-auto">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher...">
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="commandesTable">
                <thead class="table-light">
                    <tr>
                        <th>N° commande</th>
                        <th>Fournisseur</th>
                        <th>Résidence</th>
                        <th>Date commande</th>
                        <th>Livraison prévue</th>
                        <th class="text-center">Lignes</th>
                        <th class="text-end">Total TTC</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($c['numero_commande']) ?></strong></td>
                        <td><?= htmlspecialchars($c['fournisseur_nom']) ?></td>
                        <td class="small"><?= htmlspecialchars($c['residence_nom']) ?></td>
                        <td data-sort="<?= strtotime($c['date_commande']) ?>"><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                        <td data-sort="<?= $c['date_livraison_prevue'] ? strtotime($c['date_livraison_prevue']) : 0 ?>">
                            <?= $c['date_livraison_prevue'] ? date('d/m/Y', strtotime($c['date_livraison_prevue'])) : '—' ?>
                        </td>
                        <td class="text-center" data-sort="<?= (int)$c['nb_lignes'] ?>"><span class="badge bg-info"><?= (int)$c['nb_lignes'] ?></span></td>
                        <td class="text-end" data-sort="<?= (float)$c['montant_total_ttc'] ?>"><strong><?= number_format($c['montant_total_ttc'], 2, ',', ' ') ?> €</strong></td>
                        <td class="text-center" data-sort="<?= $c['statut'] ?>">
                            <span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?>"><?= $statutLabels[$c['statut']] ?? $c['statut'] ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/jardinage/commandes/show/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <?php if (in_array($c['statut'], ['brouillon'])): ?>
                            <a href="<?= BASE_URL ?>/jardinage/commandes/delete/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer ce brouillon ?')" title="Supprimer"><i class="fas fa-trash"></i></a>
                            <?php elseif (!in_array($c['statut'], ['annulee', 'livree', 'facturee'])): ?>
                            <a href="<?= BASE_URL ?>/jardinage/commandes/delete/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-dark" onclick="return confirm('Annuler cette commande ? (statut → annulée)')" title="Annuler"><i class="fas fa-ban"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($commandes)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune commande<?= $statut ? ' avec ce statut' : '' ?>.</td></tr>
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

<?php if (!empty($commandes)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('commandesTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    excludeColumns: [8],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
