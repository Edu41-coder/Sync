<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-boxes-stacked', 'text' => 'Inventaire', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-boxes-stacked me-2 text-info"></i>Inventaire
            <?php if ($alertes > 0): ?><span class="badge bg-danger ms-2"><?= $alertes ?> alerte(s)</span><?php endif; ?>
        </h2>
        <div class="d-flex gap-2">
            <select id="selResidence" class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id='+this.value">
                <option value="0">-- Résidence --</option>
                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
            </select>
            <?php if ($selectedResidence): ?>
            <a href="?residence_id=<?= $selectedResidence ?>&alertes" class="btn btn-sm btn-outline-danger"><i class="fas fa-exclamation-triangle me-1"></i>Alertes</a>
            <a href="?residence_id=<?= $selectedResidence ?>" class="btn btn-sm btn-outline-secondary">Tous</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence.</div>
    <?php else: ?>

    <!-- Ajouter un produit à l'inventaire (manager) -->
    <?php if ($isManager && !empty($produitsHors)): ?>
    <div class="card shadow-sm mb-4 border-info">
        <div class="card-header bg-info text-white py-2"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Ajouter un produit à l'inventaire</h6></div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/restauration/inventaire/ajouter" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <div class="col-md-4">
                    <label class="form-label small">Produit</label>
                    <select name="produit_id" class="form-select form-select-sm" required>
                        <option value="">-- Choisir --</option>
                        <?php foreach ($produitsHors as $p): ?><option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (<?= $p['unite'] ?>)</option><?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2"><label class="form-label small">Seuil alerte</label><input type="number" name="seuil_alerte" class="form-control form-control-sm" value="5" min="0" step="0.1"></div>
                <div class="col-md-3"><label class="form-label small">Emplacement</label><input type="text" name="emplacement" class="form-control form-control-sm" placeholder="Chambre froide..."></div>
                <div class="col-md-3"><button type="submit" class="btn btn-info btn-sm w-100"><i class="fas fa-plus me-1"></i>Ajouter</button></div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Tableau inventaire -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <select id="filterCategorie" class="form-select form-select-sm" style="width:auto">
                <option value="">Toutes catégories</option>
                <?php foreach (['fruits_legumes'=>'Fruits/Légumes','viandes'=>'Viandes','poissons'=>'Poissons','laitier'=>'Laitier','boulangerie'=>'Boulangerie','epicerie_seche'=>'Épicerie','boissons'=>'Boissons','surgeles'=>'Surgelés','condiments'=>'Condiments'] as $k=>$v): ?>
                <option value="<?= $v ?>"><?= $v ?></option><?php endforeach; ?>
            </select>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="inventaireTable">
                <thead><tr><th>Produit</th><th>Catégorie</th><th class="text-center">Stock</th><th class="text-center">Seuil</th><th>Emplacement</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($inventaire)): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Inventaire vide pour cette résidence.</td></tr>
                    <?php else: foreach ($inventaire as $i):
                        $isAlerte = $i['seuil_alerte'] > 0 && $i['quantite_stock'] <= $i['seuil_alerte'];
                    ?>
                    <tr class="<?= $isAlerte ? 'table-danger' : '' ?>">
                        <td><strong><?= htmlspecialchars($i['produit_nom']) ?></strong><?php if ($i['marque']): ?><br><small class="text-muted"><?= htmlspecialchars($i['marque']) ?></small><?php endif; ?></td>
                        <td><?= ucfirst(str_replace('_',' ',$i['produit_categorie'])) ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?= $isAlerte ? 'danger' : 'success' ?> fs-6"><?= $i['quantite_stock'] ?> <?= $i['unite'] ?></span>
                        </td>
                        <td class="text-center"><?= $i['seuil_alerte'] ?: '-' ?></td>
                        <td><small><?= htmlspecialchars($i['emplacement'] ?? '-') ?></small></td>
                        <td class="text-center">
                            <!-- Bouton mouvement rapide -->
                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalMvt" onclick="setMvt(<?= $i['id'] ?>,'sortie','<?= htmlspecialchars($i['produit_nom']) ?>','<?= $i['unite'] ?>')" title="Sortie"><i class="fas fa-minus"></i></button>
                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalMvt" onclick="setMvt(<?= $i['id'] ?>,'entree','<?= htmlspecialchars($i['produit_nom']) ?>','<?= $i['unite'] ?>')" title="Entrée"><i class="fas fa-plus"></i></button>
                            <a href="<?= BASE_URL ?>/restauration/inventaire/historique/<?= $i['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Historique"><i class="fas fa-history"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfoInv"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="paginationInv"></ul></nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Mouvement -->
<div class="modal fade" id="modalMvt" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formMvt">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <input type="hidden" name="type_mouvement" id="mvtType">
                <div class="modal-header"><h5 class="modal-title" id="mvtTitle">Mouvement</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p>Produit : <strong id="mvtProduit"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Quantité <span id="mvtUnite"></span></label>
                        <input type="number" name="quantite" class="form-control" step="0.1" min="0.1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Motif</label>
                        <select name="motif" class="form-select" id="mvtMotif">
                            <option value="consommation">Consommation</option>
                            <option value="livraison">Livraison</option>
                            <option value="perte">Perte / Péremption</option>
                            <option value="casse">Casse</option>
                            <option value="inventaire">Ajustement inventaire</option>
                        </select>
                    </div>
                    <div class="mb-3"><label class="form-label">Notes</label><input type="text" name="notes" class="form-control"></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button><button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Valider</button></div>
            </form>
        </div>
    </div>
</div>

<script>
function setMvt(id, type, nom, unite) {
    document.getElementById('formMvt').action = '<?= BASE_URL ?>/restauration/inventaire/mouvement/' + id;
    document.getElementById('mvtType').value = type;
    document.getElementById('mvtTitle').textContent = (type === 'entree' ? 'Entrée stock' : 'Sortie stock');
    document.getElementById('mvtProduit').textContent = nom;
    document.getElementById('mvtUnite').textContent = '(' + unite + ')';
    document.getElementById('mvtMotif').value = type === 'entree' ? 'livraison' : 'consommation';
}
</script>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('inventaireTable', { rowsPerPage: 25, searchInputId: 'searchInput', filters: [{ id: 'filterCategorie', column: 1 }], paginationId: 'paginationInv', infoId: 'tableInfoInv' });</script>
