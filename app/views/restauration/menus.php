<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-clipboard-list', 'text' => 'Menus', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-clipboard-list me-2 text-warning"></i>Menus de la semaine</h2>
        <div class="d-flex gap-2">
            <!-- Filtre résidence -->
            <form method="GET" class="d-flex gap-2 align-items-center">
                <input type="hidden" name="date_debut" value="<?= $dateDebut ?>">
                <input type="hidden" name="date_fin" value="<?= $dateFin ?>">
                <select name="residence_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="0">-- Résidence --</option>
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        </div>
    </div>

    <!-- Navigation semaine -->
    <?php
    $prevWeek = date('Y-m-d', strtotime($dateDebut . ' -7 days'));
    $prevWeekEnd = date('Y-m-d', strtotime($dateFin . ' -7 days'));
    $nextWeek = date('Y-m-d', strtotime($dateDebut . ' +7 days'));
    $nextWeekEnd = date('Y-m-d', strtotime($dateFin . ' +7 days'));
    $thisMonday = date('Y-m-d', strtotime('monday this week'));
    $thisSunday = date('Y-m-d', strtotime('sunday this week'));
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="?residence_id=<?= $selectedResidence ?>&date_debut=<?= $prevWeek ?>&date_fin=<?= $prevWeekEnd ?>" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-chevron-left me-1"></i>Semaine préc.
        </a>
        <div class="text-center">
            <h5 class="mb-0"><?= date('d/m', strtotime($dateDebut)) ?> — <?= date('d/m/Y', strtotime($dateFin)) ?></h5>
            <?php if ($dateDebut !== $thisMonday): ?>
            <a href="?residence_id=<?= $selectedResidence ?>&date_debut=<?= $thisMonday ?>&date_fin=<?= $thisSunday ?>" class="small">Revenir à cette semaine</a>
            <?php endif; ?>
        </div>
        <a href="?residence_id=<?= $selectedResidence ?>&date_debut=<?= $nextWeek ?>&date_fin=<?= $nextWeekEnd ?>" class="btn btn-sm btn-outline-secondary">
            Semaine suiv.<i class="fas fa-chevron-right ms-1"></i>
        </a>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour voir les menus.</div>
    <?php else: ?>

    <!-- Grille semaine -->
    <?php
    $joursSemaine = ['Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi','Dimanche'];
    $serviceLabels = ['petit_dejeuner'=>'Petit-déj','dejeuner'=>'Déjeuner','gouter'=>'Goûter','diner'=>'Dîner'];
    $serviceIcons = ['petit_dejeuner'=>'fa-coffee','dejeuner'=>'fa-sun','gouter'=>'fa-cookie','diner'=>'fa-moon'];
    ?>

    <div class="row g-2">
        <?php for ($i = 0; $i < 7; $i++):
            $date = date('Y-m-d', strtotime($dateDebut . " +$i days"));
            $dateAffichage = date('d/m', strtotime($date));
            $isToday = $date === date('Y-m-d');
            $dayMenus = $menusByDate[$date] ?? [];
        ?>
        <div class="col-12 col-lg-6 col-xl">
            <div class="card shadow-sm h-100 <?= $isToday ? 'border-warning border-2' : '' ?>">
                <div class="card-header py-2 <?= $isToday ? 'bg-warning text-dark' : '' ?>">
                    <div class="d-flex justify-content-between align-items-center">
                        <strong><?= $joursSemaine[$i] ?> <?= $dateAffichage ?></strong>
                        <?php if (!$isReadOnly): ?>
                        <div class="dropdown">
                            <button class="btn btn-sm <?= $isToday ? 'btn-dark' : 'btn-outline-warning' ?> dropdown-toggle py-0" data-bs-toggle="dropdown"><i class="fas fa-plus"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end dropdown-menu-dark">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/menus/create?date=<?= $date ?>&type_service=dejeuner&residence_id=<?= $selectedResidence ?>"><i class="fas fa-sun me-2"></i>Déjeuner</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/menus/create?date=<?= $date ?>&type_service=diner&residence_id=<?= $selectedResidence ?>"><i class="fas fa-moon me-2"></i>Dîner</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body p-2" style="min-height:120px">
                    <?php if (empty($dayMenus)): ?>
                        <p class="text-muted text-center small mt-3"><i class="fas fa-utensils me-1"></i>Pas de menu</p>
                    <?php else: ?>
                        <?php foreach ($dayMenus as $menu): ?>
                        <div class="mb-2 p-2 rounded" style="background:rgba(255,193,7,0.08)">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="badge bg-warning text-dark">
                                    <i class="fas <?= $serviceIcons[$menu['type_service']] ?? 'fa-utensils' ?> me-1"></i>
                                    <?= $serviceLabels[$menu['type_service']] ?? $menu['type_service'] ?>
                                </span>
                                <span class="small text-muted"><?= $menu['nb_plats'] ?> plats</span>
                            </div>
                            <?php if ($menu['nom']): ?>
                                <small class="fw-bold"><?= htmlspecialchars($menu['nom']) ?></small><br>
                            <?php endif; ?>
                            <div class="mt-1">
                                <a href="<?= BASE_URL ?>/restauration/menus/show/<?= $menu['id'] ?>" class="btn btn-sm btn-outline-secondary py-0 me-1" title="Voir"><i class="fas fa-eye"></i></a>
                                <?php if (!$isReadOnly): ?>
                                <a href="<?= BASE_URL ?>/restauration/menus/edit/<?= $menu['id'] ?>" class="btn btn-sm btn-outline-primary py-0 me-1" title="Modifier"><i class="fas fa-edit"></i></a>
                                <a href="<?= BASE_URL ?>/restauration/menus/duplicate/<?= $menu['id'] ?>?date=<?= date('Y-m-d', strtotime($date . ' +7 days')) ?>" class="btn btn-sm btn-outline-info py-0" title="Dupliquer semaine suivante" onclick="return confirm('Dupliquer ce menu ?')"><i class="fas fa-copy"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <?php endif; ?>
</div>
