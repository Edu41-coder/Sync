<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-book-open', 'text' => 'Catalogue produits & outils', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$categorieLabels = [
    'engrais' => 'Engrais', 'terreau' => 'Terreau', 'semence' => 'Semence', 'plant' => 'Plant',
    'phytosanitaire' => 'Phytosanitaire', 'outillage_main' => 'Outil à main',
    'outillage_motorise' => 'Outil motorisé', 'arrosage' => 'Arrosage',
    'protection' => 'Protection', 'consommable' => 'Consommable', 'autre' => 'Autre'
];
$uniteLabels = [
    'kg' => 'kg', 'g' => 'g', 'litre' => 'L', 'ml' => 'ml',
    'sac' => 'sac', 'piece' => 'pièce', 'rouleau' => 'rouleau', 'bidon' => 'bidon', 'autre' => 'autre'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-book-open me-2 text-success"></i>Catalogue produits & outils</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalProduit"><i class="fas fa-plus me-1"></i>Nouveau produit / outil</button>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Filtrer :</label></div>
                <div class="col-auto">
                    <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <option value="produit" <?= $filtreType === 'produit' ? 'selected' : '' ?>>Produits</option>
                        <option value="outil" <?= $filtreType === 'outil' ? 'selected' : '' ?>>Outils</option>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="categorie" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes catégories</option>
                        <?php foreach ($categorieLabels as $k => $l): ?>
                        <option value="<?= $k ?>" <?= $filtreCategorie === $k ? 'selected' : '' ?>><?= $l ?></option>
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
            <table class="table table-hover mb-0" id="produitsTable">
                <thead class="table-light">
                    <tr>
                        <th class="no-sort" style="width:80px">Photo</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th>Catégorie</th>
                        <th>Unité</th>
                        <th class="text-end">Prix u.</th>
                        <th>Fournisseur</th>
                        <th class="text-center">Bio</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($produits as $p): ?>
                    <tr class="<?= !$p['actif'] ? 'text-muted' : '' ?>">
                        <td class="text-center">
                            <?php if (!empty($p['photo'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($p['photo']) ?>" alt="" class="rounded"
                                     style="width:60px;height:45px;object-fit:cover;cursor:zoom-in"
                                     title="Double-clic pour agrandir"
                                     ondblclick="showPhoto('<?= BASE_URL . '/' . htmlspecialchars($p['photo']) ?>', <?= htmlspecialchars(json_encode($p['nom']), ENT_QUOTES) ?>)">
                            <?php else: ?>
                                <i class="fas fa-<?= $p['type'] === 'outil' ? 'wrench' : 'box' ?> text-muted" title="Pas de photo"></i>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($p['nom']) ?></strong><?php if ($p['marque']): ?><br><small class="text-muted"><?= htmlspecialchars($p['marque']) ?></small><?php endif; ?></td>
                        <td data-sort="<?= $p['type'] ?>"><span class="badge bg-<?= $p['type'] === 'outil' ? 'secondary' : 'info' ?>"><?= $p['type'] === 'outil' ? 'Outil' : 'Produit' ?></span></td>
                        <td data-sort="<?= htmlspecialchars($categorieLabels[$p['categorie']] ?? $p['categorie']) ?>"><?= $categorieLabels[$p['categorie']] ?? $p['categorie'] ?></td>
                        <td><?= $uniteLabels[$p['unite']] ?? $p['unite'] ?></td>
                        <td class="text-end" data-sort="<?= (float)($p['prix_unitaire'] ?? 0) ?>"><?= $p['prix_unitaire'] ? number_format($p['prix_unitaire'], 2, ',', ' ') . ' €' : '—' ?></td>
                        <td class="small"><?= $p['fournisseur_nom'] ? htmlspecialchars($p['fournisseur_nom']) : '—' ?></td>
                        <td class="text-center" data-sort="<?= $p['bio'] ? 1 : 0 ?>"><?= $p['bio'] ? '<span class="badge bg-success">BIO</span>' : '—' ?></td>
                        <td class="text-center" data-sort="<?= $p['actif'] ? 1 : 0 ?>"><span class="badge bg-<?= $p['actif'] ? 'success' : 'secondary' ?>"><?= $p['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/jardinage/produits/edit/<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></a>
                            <form method="GET" action="<?= BASE_URL ?>/jardinage/produits/delete/<?= $p['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver ce produit ?')">
                                <button class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($produits)): ?>
                    <tr><td colspan="10" class="text-center text-muted py-4">Aucun produit dans le catalogue.</td></tr>
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

<!-- Modal Nouveau produit -->
<div class="modal fade" id="modalProduit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/produits/create" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouveau produit / outil</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required maxlength="200"></div>
                        <div class="col-md-4"><label class="form-label">Marque</label>
                            <input type="text" name="marque" class="form-control" maxlength="100"></div>
                        <div class="col-md-3"><label class="form-label">Type</label>
                            <select name="type" class="form-select">
                                <option value="produit">Produit (stockable)</option>
                                <option value="outil">Outil (immobilisé)</option>
                            </select></div>
                        <div class="col-md-4"><label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <?php foreach ($categorieLabels as $k => $l): ?>
                                <option value="<?= $k ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-2"><label class="form-label">Unité</label>
                            <select name="unite" class="form-select">
                                <?php foreach ($uniteLabels as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $k === 'piece' ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Prix unitaire (€)</label>
                            <input type="number" step="0.01" min="0" name="prix_unitaire" class="form-control"></div>
                        <div class="col-md-6"><label class="form-label">Fournisseur</label>
                            <select name="fournisseur_id" class="form-select">
                                <option value="">— Aucun —</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                <option value="<?= $f['id'] ?>"><?= htmlspecialchars($f['nom']) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3 d-flex align-items-end">
                            <div class="form-check"><input type="checkbox" class="form-check-input" name="bio" value="1" id="fieldBio">
                                <label class="form-check-label" for="fieldBio">Produit BIO</label></div>
                        </div>
                        <div class="col-12"><label class="form-label">Danger / mentions sécurité</label>
                            <input type="text" name="danger" class="form-control" maxlength="255" placeholder="Ex : Pictogramme SGH07, Hors de portée des enfants..."></div>
                        <div class="col-12"><label class="form-label">Photo <small class="text-muted">(JPG, PNG, WEBP · max 5 Mo)</small></label>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp"></div>
                        <div class="col-12"><label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal viewer photo -->
<div class="modal fade" id="photoViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoViewerTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="photoViewerImg" src="" alt="" style="max-width:100%;max-height:80vh;object-fit:contain">
            </div>
        </div>
    </div>
</div>
<script>
function showPhoto(src, nom) {
    document.getElementById('photoViewerImg').src = src;
    document.getElementById('photoViewerTitle').textContent = nom;
    new bootstrap.Modal(document.getElementById('photoViewer')).show();
}
</script>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
if (document.querySelector('#produitsTable tbody tr td:not([colspan])')) {
    new DataTableWithPagination('produitsTable', {
        rowsPerPage: 20,
        searchInputId: 'searchInput',
        excludeColumns: [0, 9],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
}
</script>
