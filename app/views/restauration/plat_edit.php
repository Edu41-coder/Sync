<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-book-open', 'text' => 'Plats', 'url' => BASE_URL . '/restauration/plats'],
    ['icon' => 'fas fa-edit', 'text' => 'Modifier', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier le plat : <?= htmlspecialchars($plat['nom']) ?></h5>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/restauration/plats/update/<?= $plat['id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label">Nom du plat <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($plat['nom']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Prix unitaire (&euro;)</label>
                                <input type="number" name="prix_unitaire" class="form-control" step="0.01" min="0" value="<?= $plat['prix_unitaire'] ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                                <select name="categorie" class="form-select" required>
                                    <?php foreach (['entree'=>'Entrée','plat'=>'Plat','dessert'=>'Dessert','boisson'=>'Boisson','snack'=>'Snack','petit_dejeuner'=>'Petit-déjeuner','autre'=>'Autre'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $plat['categorie'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type de service</label>
                                <select name="type_service" class="form-select">
                                    <?php foreach (['tous'=>'Tous','petit_dejeuner'=>'Petit-déjeuner','dejeuner'=>'Déjeuner','gouter'=>'Goûter','diner'=>'Dîner','snack_bar'=>'Snack-bar'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $plat['type_service'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Régime</label>
                                <select name="regime" class="form-select">
                                    <?php foreach (['normal'=>'Normal','vegetarien'=>'Végétarien','vegan'=>'Vegan','sans_gluten'=>'Sans gluten','sans_lactose'=>'Sans lactose','halal'=>'Halal'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= $plat['regime'] === $val ? 'selected' : '' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($plat['description'] ?? '') ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Allergènes</label>
                                <input type="text" name="allergenes" class="form-control" value="<?= htmlspecialchars($plat['allergenes'] ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Calories (kcal)</label>
                                <input type="number" name="calories" class="form-control" min="0" value="<?= $plat['calories'] ?? '' ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Ordre d'affichage</label>
                                <input type="number" name="ordre_affichage" class="form-control" value="<?= $plat['ordre_affichage'] ?? 0 ?>" min="0">
                            </div>
                            <div class="col-12">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="actif" value="1" <?= $plat['actif'] ? 'checked' : '' ?> id="chkActif">
                                    <label class="form-check-label" for="chkActif">Plat actif</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/restauration/plats" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-2"></i>Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
