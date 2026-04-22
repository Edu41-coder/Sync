<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-boxes-stacked', 'text' => 'Inventaire', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$categorieLabels = [
    'engrais' => 'Engrais', 'terreau' => 'Terreau', 'semence' => 'Semence', 'plant' => 'Plant',
    'phytosanitaire' => 'Phytosanitaire', 'outillage_main' => 'Outil main',
    'outillage_motorise' => 'Outil motorisé', 'arrosage' => 'Arrosage',
    'protection' => 'Protection', 'consommable' => 'Consommable', 'autre' => 'Autre'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-boxes-stacked me-2 text-success"></i>Inventaire Jardinage</h2>
        <?php if ($alertes > 0): ?>
        <span class="badge bg-danger fs-6"><i class="fas fa-exclamation-triangle me-1"></i><?= $alertes ?> alerte(s) stock</span>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Résidence :</label></div>
                <div class="col-12 col-md-4">
                    <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">— Sélectionner —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if ($selectedResidence): ?>
                <div class="col-auto">
                    <select name="categorie" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categorieLabels as $k => $l): ?>
                        <option value="<?= $k ?>" <?= ($_GET['categorie'] ?? '') === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="alertes" value="1" id="filterAlertes" <?= isset($_GET['alertes']) ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small" for="filterAlertes">Alertes seulement</label>
                    </div>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-3 ms-auto">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher...">
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour consulter son inventaire.</div>
    <?php else: ?>

    <?php if ($isManager && !empty($produitsHors)): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-light"><h6 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter un produit à l'inventaire de cette résidence</h6></div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/inventaire/ajouter" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <div class="col-md-5">
                    <label class="form-label small">Produit</label>
                    <select name="produit_id" class="form-select form-select-sm" required>
                        <option value="">— Sélectionner —</option>
                        <?php foreach ($produitsHors as $p): ?>
                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (<?= $categorieLabels[$p['categorie']] ?? $p['categorie'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Seuil d'alerte</label>
                    <input type="number" step="0.001" min="0" name="seuil_alerte" class="form-control form-control-sm" value="0">
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Emplacement</label>
                    <input type="text" name="emplacement" maxlength="150" class="form-control form-control-sm" placeholder="Ex : Cabane jardin">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-success w-100"><i class="fas fa-plus"></i></button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="invTable">
                <thead class="table-light">
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th>Emplacement</th>
                        <th class="text-end">Stock</th>
                        <th class="text-end">Seuil</th>
                        <th class="text-end">Valeur</th>
                        <th class="text-end no-sort" style="width:230px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($inventaire as $i):
                        $alerte = $i['seuil_alerte'] > 0 && $i['quantite_actuelle'] <= $i['seuil_alerte'];
                        $valeur = $i['quantite_actuelle'] * ($i['prix_unitaire'] ?? 0);
                    ?>
                    <tr class="<?= $alerte ? 'table-warning' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($i['produit_nom']) ?></strong>
                            <?php if ($i['bio']): ?><span class="badge bg-success ms-1">BIO</span><?php endif; ?>
                            <?php if ($i['type'] === 'outil'): ?><span class="badge bg-secondary ms-1">Outil</span><?php endif; ?>
                            <?php if ($i['marque']): ?><br><small class="text-muted"><?= htmlspecialchars($i['marque']) ?></small><?php endif; ?>
                        </td>
                        <td data-sort="<?= htmlspecialchars($categorieLabels[$i['categorie']] ?? $i['categorie']) ?>"><small><?= $categorieLabels[$i['categorie']] ?? $i['categorie'] ?></small></td>
                        <td><small class="text-muted"><?= $i['emplacement'] ? htmlspecialchars($i['emplacement']) : '—' ?></small></td>
                        <td class="text-end" data-sort="<?= (float)$i['quantite_actuelle'] ?>">
                            <strong class="<?= $alerte ? 'text-danger' : '' ?>"><?= number_format($i['quantite_actuelle'], 3, ',', ' ') ?></strong> <?= htmlspecialchars($i['unite']) ?>
                            <?php if ($alerte): ?><i class="fas fa-exclamation-triangle text-danger ms-1"></i><?php endif; ?>
                        </td>
                        <td class="text-end text-muted" data-sort="<?= (float)($i['seuil_alerte'] ?? 0) ?>"><?= $i['seuil_alerte'] > 0 ? number_format($i['seuil_alerte'], 3, ',', ' ') : '—' ?></td>
                        <td class="text-end" data-sort="<?= (float)$valeur ?>"><?= $i['prix_unitaire'] ? number_format($valeur, 2, ',', ' ') . ' €' : '—' ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-success" onclick='openMouvement(<?= (int)$i['id'] ?>, <?= json_encode($i['produit_nom']) ?>, "entree")'><i class="fas fa-plus"></i></button>
                            <button class="btn btn-sm btn-outline-warning" onclick='openMouvement(<?= (int)$i['id'] ?>, <?= json_encode($i['produit_nom']) ?>, "sortie")'><i class="fas fa-minus"></i></button>
                            <a href="<?= BASE_URL ?>/jardinage/inventaire/historique/<?= $i['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-history"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($inventaire)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun produit à l'inventaire pour les filtres sélectionnés.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal mouvement stock -->
<?php if ($selectedResidence): ?>
<div class="modal fade" id="modalMouvement" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formMouvement">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalMouvementTitle">Mouvement de stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                    <input type="hidden" name="type_mouvement" id="mvType" value="sortie">
                    <div class="mb-3"><strong id="mvProduitNom"></strong></div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quantité <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0.001" name="quantite" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Motif</label>
                            <select name="motif" class="form-select">
                                <option value="livraison">Livraison</option>
                                <option value="usage" selected>Usage sur espace</option>
                                <option value="perte">Perte</option>
                                <option value="casse">Casse</option>
                                <option value="inventaire">Correction inventaire</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-12" id="divEspace">
                            <label class="form-label">Espace affecté (pour calcul coût par espace)</label>
                            <select name="espace_id" class="form-select">
                                <option value="">— Non précisé —</option>
                                <?php foreach ($espaces as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success" id="btnMvSubmit"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function openMouvement(invId, nom, type) {
    const f = document.getElementById('formMouvement');
    f.action = '<?= BASE_URL ?>/jardinage/inventaire/mouvement/' + invId;
    document.getElementById('mvType').value = type;
    document.getElementById('mvProduitNom').textContent = nom;
    document.getElementById('modalMouvementTitle').innerHTML = type === 'entree'
        ? '<i class="fas fa-plus me-2"></i>Entrée de stock'
        : '<i class="fas fa-minus me-2"></i>Sortie de stock';
    document.getElementById('btnMvSubmit').className = type === 'entree' ? 'btn btn-success' : 'btn btn-warning';
    document.getElementById('divEspace').style.display = type === 'sortie' ? 'block' : 'none';
    f.querySelector('[name=quantite]').value = '';
    f.querySelector('[name=notes]').value = '';
    new bootstrap.Modal(document.getElementById('modalMouvement')).show();
}

</script>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
if (document.querySelector('#invTable tbody tr td:not([colspan])')) {
    new DataTableWithPagination('invTable', {
        rowsPerPage: 25,
        searchInputId: 'searchInput',
        excludeColumns: [6],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
}
</script>
<?php endif; ?>
