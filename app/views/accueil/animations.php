<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-music',          'text' => 'Animations',      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-music text-info me-2"></i>Animations</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · <?= count($animations) ?> animation<?= count($animations) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/accueil/animations">
                <input type="hidden" name="periode" value="<?= htmlspecialchars($periode) ?>">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($isManager && $residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/animationForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-info text-white">
                <i class="fas fa-plus me-1"></i>Nouvelle animation
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php else: ?>

    <!-- Filtres période -->
    <div class="mb-3">
        <a href="<?= BASE_URL ?>/accueil/animations?residence_id=<?= (int)$residenceCourante['id'] ?>&periode=futures"
           class="btn btn-sm <?= $periode === 'futures' ? 'btn-info text-white' : 'btn-outline-info' ?>">
            <i class="fas fa-arrow-right me-1"></i>À venir
        </a>
        <a href="<?= BASE_URL ?>/accueil/animations?residence_id=<?= (int)$residenceCourante['id'] ?>&periode=toutes"
           class="btn btn-sm <?= $periode === 'toutes' ? 'btn-secondary' : 'btn-outline-secondary' ?>">
            <i class="fas fa-list me-1"></i>Toutes (historique inclus)
        </a>
    </div>

    <?php if (empty($animations)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune animation
        <?= $periode === 'futures' ? 'à venir.' : 'enregistrée.' ?>
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/accueil/animationForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="alert-link">Créer la première</a>.
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher (titre, animateur…)">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="animTable">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Date / heure</th>
                            <th>Durée</th>
                            <th>Animateur</th>
                            <th class="text-center">Inscrits</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($animations as $a):
                            $isPast = strtotime($a['date_fin']) < time();
                            $duree = (strtotime($a['date_fin']) - strtotime($a['date_debut'])) / 60;
                            $dureeStr = $duree >= 60 ? sprintf('%dh%02d', floor($duree/60), $duree%60) : $duree . ' min';
                        ?>
                        <tr class="<?= $isPast ? 'opacity-75' : '' ?>">
                            <td>
                                <a href="<?= BASE_URL ?>/accueil/animationShow/<?= (int)$a['id'] ?>" class="text-decoration-none fw-bold">
                                    <?= htmlspecialchars($a['titre']) ?>
                                </a>
                                <?php if ($isPast): ?>
                                <span class="badge bg-secondary ms-1">Passé</span>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($a['date_debut']) ?>">
                                <small><strong><?= date('d/m/Y', strtotime($a['date_debut'])) ?></strong> · <?= date('H:i', strtotime($a['date_debut'])) ?> → <?= date('H:i', strtotime($a['date_fin'])) ?></small>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $dureeStr ?></span></td>
                            <td>
                                <?php if ($a['animateur_prenom']): ?>
                                <i class="fas fa-user-circle me-1 text-info"></i><?= htmlspecialchars($a['animateur_prenom'] . ' ' . $a['animateur_nom']) ?>
                                <?php else: ?>
                                <small class="text-muted">— Non assigné —</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-info text-white"><i class="fas fa-users me-1"></i><?= (int)$a['nb_inscrits'] ?></span>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/accueil/animationShow/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir / Inscrire"><i class="fas fa-eye"></i></a>
                                <?php if ($isManager && !$isPast): ?>
                                <a href="<?= BASE_URL ?>/accueil/animationForm/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></a>
                                <?php endif; ?>
                                <?php if ($isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/accueil/animationDelete/<?= (int)$a['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer cette animation ? Toutes les inscriptions seront retirées.')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="pagination" class="mt-3"></div>
            <div id="tableInfo" class="text-muted small mt-2"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php if (!empty($animations)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('animTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    excludeColumns: [5],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
