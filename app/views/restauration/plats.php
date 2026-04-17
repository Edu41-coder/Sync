<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-book-open', 'text' => 'Catalogue des Plats', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-book-open me-2 text-warning"></i>Catalogue des Plats</h2>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalPlat" onclick="resetForm()">
            <i class="fas fa-plus me-2"></i>Nouveau plat
        </button>
    </div>

    <!-- Stats par catégorie -->
    <div class="row g-2 mb-4">
        <?php
        $catIcons = ['entree'=>'fa-leaf','plat'=>'fa-drumstick-bite','dessert'=>'fa-ice-cream','boisson'=>'fa-glass-water','snack'=>'fa-cookie','petit_dejeuner'=>'fa-coffee','autre'=>'fa-ellipsis'];
        $catColors = ['entree'=>'success','plat'=>'danger','dessert'=>'info','boisson'=>'primary','snack'=>'warning','petit_dejeuner'=>'secondary','autre'=>'dark'];
        $totalPlats = 0;
        foreach ($stats as $s): $totalPlats += $s['total']; ?>
        <div class="col-auto">
            <span class="badge bg-<?= $catColors[$s['categorie']] ?? 'secondary' ?> fs-6">
                <i class="fas <?= $catIcons[$s['categorie']] ?? 'fa-utensils' ?> me-1"></i>
                <?= ucfirst(str_replace('_', ' ', $s['categorie'])) ?> : <?= $s['actifs'] ?>/<?= $s['total'] ?>
            </span>
        </div>
        <?php endforeach; ?>
        <div class="col-auto"><span class="badge bg-dark fs-6">Total : <?= $totalPlats ?></span></div>
    </div>

    <!-- Filtres + recherche -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex gap-2">
                <select id="filterCategorie" class="form-select form-select-sm" style="width:auto">
                    <option value="">Toutes catégories</option>
                    <option value="entree">Entrées</option>
                    <option value="plat">Plats</option>
                    <option value="dessert">Desserts</option>
                    <option value="boisson">Boissons</option>
                    <option value="snack">Snacks</option>
                    <option value="petit_dejeuner">Petit-déjeuner</option>
                </select>
                <select id="filterRegime" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous régimes</option>
                    <option value="normal">Normal</option>
                    <option value="vegetarien">Végétarien</option>
                    <option value="vegan">Vegan</option>
                    <option value="sans_gluten">Sans gluten</option>
                    <option value="sans_lactose">Sans lactose</option>
                </select>
            </div>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher un plat..." style="width:250px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="platsTable">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Catégorie</th>
                        <th>Service</th>
                        <th>Régime</th>
                        <th>Allergènes</th>
                        <th class="text-end">Prix</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plats)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4"><i class="fas fa-utensils me-2"></i>Aucun plat dans le catalogue. Commencez par en créer un.</td></tr>
                    <?php else: ?>
                    <?php foreach ($plats as $p): ?>
                    <tr class="<?= $p['actif'] ? '' : 'opacity-50' ?>">
                        <td>
                            <strong><?= htmlspecialchars($p['nom']) ?></strong>
                            <?php if ($p['description']): ?>
                            <br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($p['description'], 0, 60, '...')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $catColors[$p['categorie']] ?? 'secondary' ?>"><i class="fas <?= $catIcons[$p['categorie']] ?? 'fa-utensils' ?> me-1"></i><?= ucfirst($p['categorie']) ?></span></td>
                        <td><small><?= str_replace('_', ' ', $p['type_service']) ?></small></td>
                        <td><?= $p['regime'] !== 'normal' ? '<span class="badge bg-info text-dark">' . $p['regime'] . '</span>' : 'Normal' ?></td>
                        <td>
                            <?php if ($p['allergenes']): ?>
                            <span class="badge bg-warning text-dark" title="<?= htmlspecialchars($p['allergenes']) ?>"><i class="fas fa-allergies me-1"></i><?= htmlspecialchars(mb_strimwidth($p['allergenes'], 0, 20, '...')) ?></span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td class="text-end"><?= number_format($p['prix_unitaire'], 2, ',', ' ') ?> &euro;</td>
                        <td class="text-center">
                            <span class="badge bg-<?= $p['actif'] ? 'success' : 'danger' ?>"><?= $p['actif'] ? 'Actif' : 'Inactif' ?></span>
                        </td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= BASE_URL ?>/restauration/plats/edit/<?= $p['id'] ?>" class="btn btn-outline-primary" title="Modifier" data-bs-toggle="tooltip"><i class="fas fa-edit"></i></a>
                                <?php if ($p['actif']): ?>
                                <a href="<?= BASE_URL ?>/restauration/plats/delete/<?= $p['id'] ?>" class="btn btn-outline-danger" title="Désactiver" data-bs-toggle="tooltip" onclick="return confirm('Désactiver ce plat ?')"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                            </div>
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

<!-- Modal Nouveau Plat -->
<div class="modal fade" id="modalPlat" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/restauration/plats/create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouveau Plat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Nom du plat <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Prix unitaire (&euro;)</label>
                            <input type="number" name="prix_unitaire" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" class="form-select" required>
                                <option value="entree">Entrée</option>
                                <option value="plat" selected>Plat</option>
                                <option value="dessert">Dessert</option>
                                <option value="boisson">Boisson</option>
                                <option value="snack">Snack</option>
                                <option value="petit_dejeuner">Petit-déjeuner</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type de service</label>
                            <select name="type_service" class="form-select">
                                <option value="tous">Tous</option>
                                <option value="petit_dejeuner">Petit-déjeuner</option>
                                <option value="dejeuner">Déjeuner</option>
                                <option value="gouter">Goûter</option>
                                <option value="diner">Dîner</option>
                                <option value="snack_bar">Snack-bar</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Régime</label>
                            <select name="regime" class="form-select">
                                <option value="normal">Normal</option>
                                <option value="vegetarien">Végétarien</option>
                                <option value="vegan">Vegan</option>
                                <option value="sans_gluten">Sans gluten</option>
                                <option value="sans_lactose">Sans lactose</option>
                                <option value="halal">Halal</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Allergènes <small class="text-muted">(séparés par virgule)</small></label>
                            <input type="text" name="allergenes" class="form-control" placeholder="gluten, lactose, arachides...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Calories (kcal)</label>
                            <input type="number" name="calories" class="form-control" min="0">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" name="ordre_affichage" class="form-control" value="0" min="0">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="actif" value="1" checked id="chkActif">
                                <label class="form-check-label" for="chkActif">Plat actif (visible dans les menus)</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Créer le plat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
function resetForm() {
    document.querySelector('#modalPlat form').reset();
    document.querySelector('#modalPlat #chkActif').checked = true;
}
new DataTableWithPagination('platsTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    filters: [
        { id: 'filterCategorie', column: 1 },
        { id: 'filterRegime', column: 3 }
    ],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
