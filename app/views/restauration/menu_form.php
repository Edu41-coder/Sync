<?php
$isEdit = !empty($menu);
$serviceLabels = ['petit_dejeuner'=>'Petit-déjeuner','dejeuner'=>'Déjeuner','gouter'=>'Goûter','diner'=>'Dîner'];
$catLabels = ['entree'=>'Entrées','plat'=>'Plats principaux','dessert'=>'Desserts','accompagnement'=>'Accompagnements','boisson'=>'Boissons'];

// Plats déjà sélectionnés (en édition)
$selectedPlats = [];
if ($isEdit && !empty($menu['plats'])) {
    foreach ($menu['plats'] as $p) {
        $selectedPlats[$p['categorie_plat']][] = $p;
    }
}
?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-clipboard-list', 'text' => 'Menus', 'url' => BASE_URL . '/restauration/menus'],
    ['icon' => 'fas fa-edit', 'text' => $isEdit ? 'Modifier' : 'Nouveau', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <form method="POST" action="<?= BASE_URL ?>/restauration/menus/<?= $isEdit ? 'update/' . $menu['id'] : 'store' ?>" id="menuForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="row g-4">
            <!-- Colonne gauche : infos menu -->
            <div class="col-lg-4">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list me-2"></i><?= $isEdit ? 'Modifier le menu' : 'Nouveau menu' ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= $r['id'] ?>" <?= ($isEdit ? $menu['residence_id'] : ($_GET['residence_id'] ?? '')) == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isEdit): ?><input type="hidden" name="residence_id" value="<?= $menu['residence_id'] ?>"><?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_menu" class="form-control" value="<?= $dateMenu ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="type_service" class="form-select" required id="selectService">
                                <?php foreach ($serviceLabels as $val => $label): ?>
                                <option value="<?= $val ?>" <?= $typeService === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nom du menu <small class="text-muted">(optionnel)</small></label>
                            <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($isEdit ? $menu['nom'] ?? '' : '') ?>" placeholder="Menu du Chef, Menu Tradition...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Prix menu complet (&euro;)</label>
                            <input type="number" name="prix_menu" class="form-control" step="0.01" min="0" value="<?= $isEdit ? $menu['prix_menu'] ?? '' : '' ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($isEdit ? $menu['notes'] ?? '' : '') ?></textarea>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" <?= $menu['actif'] ? 'checked' : '' ?> id="chkActif">
                            <label class="form-check-label" for="chkActif">Menu actif</label>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne droite : composition du menu -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Composition du menu</h5>
                        <small class="text-muted">2 choix par catégorie (entrée, plat, dessert)</small>
                    </div>
                    <div class="card-body">
                        <?php
                        $categoriesMenu = ['entree' => 2, 'plat' => 2, 'dessert' => 2, 'accompagnement' => 0, 'boisson' => 0];
                        $platIndex = 0;
                        foreach ($categoriesMenu as $cat => $nbChoix):
                            $platsInCat = $platsByCategorie[$cat] ?? [];
                            $selectedInCat = $selectedPlats[$cat] ?? [];
                        ?>
                        <h6 class="mt-3 mb-2 text-warning"><i class="fas fa-utensils me-2"></i><?= $catLabels[$cat] ?? ucfirst($cat) ?></h6>

                        <?php if (empty($platsInCat)): ?>
                            <p class="text-muted small ms-3">Aucun plat de type "<?= $cat ?>" dans le catalogue. <a href="<?= BASE_URL ?>/restauration/plats">Ajouter des plats</a></p>
                        <?php else: ?>
                            <?php
                            $maxChoix = $nbChoix ?: 3;
                            for ($choix = 0; $choix < $maxChoix; $choix++):
                                $preSelected = $selectedInCat[$choix] ?? null;
                            ?>
                            <div class="row g-2 mb-2 ms-2">
                                <div class="col-auto pt-2">
                                    <span class="badge bg-secondary">Choix <?= chr(65 + $choix) ?></span>
                                </div>
                                <div class="col">
                                    <select name="plats[<?= $platIndex ?>][plat_id]" class="form-select form-select-sm">
                                        <option value="">— Aucun —</option>
                                        <?php foreach ($platsInCat as $p): ?>
                                        <option value="<?= $p['id'] ?>" <?= ($preSelected && $preSelected['plat_id'] == $p['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($p['nom']) ?>
                                            <?php if ($p['regime'] !== 'normal'): ?> (<?= $p['regime'] ?>)<?php endif; ?>
                                            <?php if ($p['allergenes']): ?> [<?= $p['allergenes'] ?>]<?php endif; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <input type="hidden" name="plats[<?= $platIndex ?>][categorie_plat]" value="<?= $cat ?>">
                                    <input type="hidden" name="plats[<?= $platIndex ?>][ordre]" value="<?= $choix + 1 ?>">
                                </div>
                            </div>
                            <?php $platIndex++; endfor; ?>
                        <?php endif; ?>

                        <?php endforeach; ?>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/restauration/menus" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Annuler</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i><?= $isEdit ? 'Enregistrer' : 'Créer le menu' ?></button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
