<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-box',            'text' => 'Catalogue produits', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-box text-warning me-2"></i>Catalogue produits &amp; outils</h1>
            <p class="text-muted mb-0"><?= count($produits) ?> entrée<?= count($produits) > 1 ? 's' : '' ?></p>
        </div>
        <a href="<?= BASE_URL ?>/maintenance/produitForm" class="btn btn-warning">
            <i class="fas fa-plus me-1"></i>Nouveau produit
        </a>
    </div>

    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-list me-2"></i>Catalogue</strong>
            <input type="text" id="searchProduits" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:240px">
        </div>
        <div class="card-body p-0">
            <?php if (empty($produits)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-box fa-3x opacity-50 mb-3 d-block"></i>
                <h6>Aucun produit dans le catalogue</h6>
                <a href="<?= BASE_URL ?>/maintenance/produitForm" class="btn btn-sm btn-warning mt-2">
                    <i class="fas fa-plus me-1"></i>Créer le premier
                </a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableProduits">
                    <thead class="table-light">
                        <tr><th>Nom</th><th>Spécialité</th><th>Catégorie</th><th>Type</th><th>Unité</th><th class="text-end">Prix HT</th><th>Fourn. préf.</th><th>Actif</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produits as $p): ?>
                        <tr class="<?= !$p['actif'] ? 'opacity-50' : '' ?>">
                            <td><strong><?= htmlspecialchars($p['nom']) ?></strong></td>
                            <td>
                                <?php if ($p['specialite_nom']): ?>
                                <span class="badge" style="background:<?= htmlspecialchars($p['specialite_couleur'] ?? '#6c757d') ?>;color:#fff">
                                    <i class="<?= htmlspecialchars($p['specialite_icone'] ?? '') ?> me-1"></i><?= htmlspecialchars($p['specialite_nom']) ?>
                                </span>
                                <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($p['categorie']) ?></small></td>
                            <td><span class="badge bg-<?= $p['type'] === 'outil' ? 'info' : 'secondary' ?>"><?= $p['type'] ?></span></td>
                            <td><small><?= htmlspecialchars($p['unite'] ?? '—') ?></small></td>
                            <td class="text-end"><?= $p['prix_unitaire'] ? number_format((float)$p['prix_unitaire'], 2, ',', ' ') . ' €' : '—' ?></td>
                            <td><small><?= htmlspecialchars($p['fournisseur_prefere_nom'] ?? '—') ?></small></td>
                            <td><?= $p['actif'] ? '<span class="badge bg-success">Oui</span>' : '<span class="badge bg-secondary">Non</span>' ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/maintenance/produitForm/<?= (int)$p['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="<?= BASE_URL ?>/maintenance/produitDelete/<?= (int)$p['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce produit ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoProduits" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationProduits"></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($produits)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableProduits', {
    rowsPerPage: 15, searchInputId: 'searchProduits',
    paginationId: 'paginationProduits', infoId: 'infoProduits', excludeColumns: [8]
});
</script>
<?php endif; ?>
