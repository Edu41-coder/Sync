<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-box', 'text' => 'Produits', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes-stacked me-2 text-info"></i>Catalogue Produits Ménage</h2>
        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalProduit"><i class="fas fa-plus me-2"></i>Nouveau produit</button>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <div class="d-flex gap-2">
                <select id="filterCategorie" class="form-select form-select-sm" style="width:auto">
                    <option value="">Toutes catégories</option>
                    <?php foreach (['nettoyant'=>'Nettoyant','desinfectant'=>'Désinfectant','lessive'=>'Lessive','materiel'=>'Matériel','sac_poubelle'=>'Sac poubelle','papier'=>'Papier','autre'=>'Autre'] as $k=>$v): ?>
                    <option value="<?= $v ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterSection" class="form-select form-select-sm" style="width:auto">
                    <option value="">Toutes sections</option>
                    <?php foreach (['interieur'=>'Intérieur','exterieur'=>'Extérieur','laverie'=>'Laverie','commun'=>'Commun'] as $k=>$v): ?>
                    <option value="<?= $v ?>"><?= $v ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:250px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="produitsTable">
                <thead><tr><th>Produit</th><th>Catégorie</th><th>Section</th><th>Unité</th><th>Marque</th><th>Fournisseur</th><th class="text-end">Prix réf.</th><th class="text-center">Statut</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($produits)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucun produit. Commencez par en créer.</td></tr>
                    <?php else: foreach ($produits as $p): ?>
                    <tr class="<?= $p['actif'] ? '' : 'opacity-50' ?>">
                        <td><strong><?= htmlspecialchars($p['nom']) ?></strong><?php if ($p['conditionnement']): ?><br><small class="text-muted"><?= htmlspecialchars($p['conditionnement']) ?></small><?php endif; ?></td>
                        <td><?= ucfirst(str_replace('_',' ',$p['categorie'])) ?></td>
                        <td><?= ucfirst(str_replace('_',' ',$p['section'] ?? '-')) ?></td>
                        <td><?= $p['unite'] ?></td>
                        <td><?= htmlspecialchars($p['marque'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($p['fournisseur_nom'] ?? '-') ?></td>
                        <td class="text-end"><?= $p['prix_reference'] ? number_format($p['prix_reference'],2,',',' ').' €' : '-' ?></td>
                        <td class="text-center"><span class="badge bg-<?= $p['actif'] ? 'success' : 'danger' ?>"><?= $p['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= BASE_URL ?>/menage/produits/edit/<?= $p['id'] ?>" class="btn btn-outline-primary" title="Modifier" data-bs-toggle="tooltip"><i class="fas fa-edit"></i></a>
                                <?php if ($p['actif']): ?><a href="<?= BASE_URL ?>/menage/produits/delete/<?= $p['id'] ?>" class="btn btn-outline-danger" title="Désactiver" data-bs-toggle="tooltip" onclick="return confirm('Désactiver ?')"><i class="fas fa-times"></i></a><?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfoProd"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationProd"></ul></nav>
        </div>
    </div>
</div>

<!-- Modal Nouveau Produit -->
<div class="modal fade" id="modalProduit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/menage/produits/create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header"><h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouveau Produit Ménage</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6"><label class="form-label">Nom <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control" required></div>
                        <div class="col-md-3"><label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" class="form-select" required>
                                <?php foreach (['nettoyant'=>'Nettoyant','desinfectant'=>'Désinfectant','lessive'=>'Lessive','materiel'=>'Matériel','sac_poubelle'=>'Sac poubelle','papier'=>'Papier','autre'=>'Autre'] as $k=>$v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Section <span class="text-danger">*</span></label>
                            <select name="section" class="form-select" required>
                                <?php foreach (['interieur'=>'Intérieur','exterieur'=>'Extérieur','laverie'=>'Laverie','commun'=>'Commun'] as $k=>$v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Unité <span class="text-danger">*</span></label>
                            <select name="unite" class="form-select" required>
                                <?php foreach (['litre','ml','kg','unite','carton','rouleau','sachet','bidon','boite'] as $u): ?><option value="<?= $u ?>"><?= $u ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Prix réf. (€)</label><input type="number" name="prix_reference" class="form-control" step="0.01" min="0"></div>
                        <div class="col-md-3"><label class="form-label">Code-barres</label><input type="text" name="code_barre" class="form-control"></div>
                        <div class="col-md-3"><label class="form-label">Marque</label><input type="text" name="marque" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Fournisseur</label>
                            <select name="fournisseur_id" class="form-select"><option value="">-- Aucun --</option>
                                <?php foreach ($fournisseurs as $f): ?><option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="col-md-6"><label class="form-label">Conditionnement</label><input type="text" name="conditionnement" class="form-control" placeholder="Pack de 6, carton de 12..."></div>
                        <div class="col-md-6"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div>
                        <div class="col-12"><div class="form-check"><input class="form-check-input" type="checkbox" name="actif" value="1" checked><label class="form-check-label">Produit actif</label></div></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-info"><i class="fas fa-save me-2"></i>Créer</button></div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('produitsTable', { rowsPerPage: 20, searchInputId: 'searchInput', filters: [{ id: 'filterCategorie', column: 1 }, { id: 'filterSection', column: 2 }], paginationId: 'paginationProd', infoId: 'tableInfoProd' });</script>
