<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-clipboard-list', 'text' => 'Menus', 'url' => BASE_URL . '/restauration/menus?residence_id=' . $menu['residence_id']],
    ['icon' => 'fas fa-clipboard-list', 'text' => date('d/m/Y', strtotime($menu['date_menu'])), 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <?php
    $serviceLabels = ['petit_dejeuner'=>'Petit-déjeuner','dejeuner'=>'Déjeuner','gouter'=>'Goûter','diner'=>'Dîner'];
    $serviceIcons = ['petit_dejeuner'=>'fa-coffee','dejeuner'=>'fa-sun','gouter'=>'fa-cookie','diner'=>'fa-moon'];
    $catLabels = ['entree'=>'Entrées','plat'=>'Plats principaux','dessert'=>'Desserts','accompagnement'=>'Accompagnements','boisson'=>'Boissons'];
    $catIcons = ['entree'=>'fa-leaf','plat'=>'fa-drumstick-bite','dessert'=>'fa-ice-cream','accompagnement'=>'fa-carrot','boisson'=>'fa-glass-water'];
    ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas <?= $serviceIcons[$menu['type_service']] ?? 'fa-utensils' ?> me-2"></i>
                            <?= $serviceLabels[$menu['type_service']] ?? $menu['type_service'] ?>
                            — <?= date('l d F Y', strtotime($menu['date_menu'])) ?>
                        </h5>
                        <span class="badge bg-dark"><?= htmlspecialchars($menu['residence_nom']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <?php if ($menu['nom']): ?>
                    <h4 class="text-center mb-4"><?= htmlspecialchars($menu['nom']) ?></h4>
                    <?php endif; ?>

                    <?php if (empty($platsByCategorie)): ?>
                        <p class="text-muted text-center">Aucun plat associé à ce menu.</p>
                    <?php else: ?>
                        <?php foreach ($platsByCategorie as $cat => $plats): ?>
                        <h6 class="text-warning mt-3 mb-2">
                            <i class="fas <?= $catIcons[$cat] ?? 'fa-utensils' ?> me-2"></i><?= $catLabels[$cat] ?? ucfirst($cat) ?>
                        </h6>
                        <div class="list-group mb-3">
                            <?php foreach ($plats as $i => $p): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-secondary me-2">Choix <?= chr(65 + $i) ?></span>
                                    <strong><?= htmlspecialchars($p['plat_nom']) ?></strong>
                                    <?php if ($p['plat_description']): ?>
                                        <br><small class="text-muted ms-4"><?= htmlspecialchars($p['plat_description']) ?></small>
                                    <?php endif; ?>
                                    <?php if ($p['regime'] !== 'normal'): ?>
                                        <span class="badge bg-info text-dark ms-1" style="font-size:0.65rem"><?= $p['regime'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($p['allergenes']): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.6rem" title="<?= htmlspecialchars($p['allergenes']) ?>"><i class="fas fa-allergies"></i> <?= htmlspecialchars($p['allergenes']) ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-muted"><?= $p['prix_unitaire'] > 0 ? number_format($p['prix_unitaire'], 2, ',', ' ') . ' €' : '' ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <?php if ($menu['prix_menu']): ?>
                    <div class="text-center mt-4">
                        <span class="badge bg-success fs-5 px-4 py-2">Menu complet : <?= number_format($menu['prix_menu'], 2, ',', ' ') ?> &euro;</span>
                    </div>
                    <?php endif; ?>

                    <?php if ($menu['notes']): ?>
                    <div class="alert alert-light mt-3"><i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($menu['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/restauration/menus?residence_id=<?= $menu['residence_id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                    <div>
                        <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'restauration_manager'])): ?>
                        <a href="<?= BASE_URL ?>/restauration/menus/edit/<?= $menu['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-2"></i>Modifier</a>
                        <form method="POST" action="<?= BASE_URL ?>/restauration/menus/delete/<?= $menu['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce menu ?')">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-2"></i>Supprimer</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
