<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Réservations',    'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$labelsStatut = [
    'en_attente' => ['couleur' => 'warning', 'libelle' => 'En attente', 'icone' => 'fa-clock'],
    'confirmee'  => ['couleur' => 'success', 'libelle' => 'Confirmée',  'icone' => 'fa-check'],
    'refusee'    => ['couleur' => 'danger',  'libelle' => 'Refusée',    'icone' => 'fa-times'],
    'annulee'    => ['couleur' => 'secondary','libelle' => 'Annulée',    'icone' => 'fa-ban'],
    'realisee'   => ['couleur' => 'primary', 'libelle' => 'Réalisée',   'icone' => 'fa-flag-checkered'],
];
$labelsType = [
    'salle'             => ['couleur' => 'info',    'libelle' => 'Salle commune',    'icone' => 'fa-door-open'],
    'equipement'        => ['couleur' => 'success', 'libelle' => 'Équipement',       'icone' => 'fa-toolbox'],
    'service_personnel' => ['couleur' => 'primary', 'libelle' => 'Service personnel','icone' => 'fa-user-tie'],
];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-calendar-check text-info me-2"></i>Réservations</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · <?= count($reservations) ?> réservation<?= count($reservations) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/accueil/reservations">
                <?php if (!empty($filtres['statut'])): ?><input type="hidden" name="statut" value="<?= htmlspecialchars($filtres['statut']) ?>"><?php endif; ?>
                <?php if (!empty($filtres['type'])): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filtres['type']) ?>"><?php endif; ?>
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/reservationForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-info text-white">
                <i class="fas fa-plus me-1"></i>Nouvelle réservation
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php else: ?>

    <!-- Filtres rapides par statut -->
    <div class="mb-3">
        <a href="<?= BASE_URL ?>/accueil/reservations?residence_id=<?= (int)$residenceCourante['id'] ?>"
           class="btn btn-sm <?= empty($filtres['statut']) ? 'btn-secondary' : 'btn-outline-secondary' ?>">Toutes</a>
        <?php foreach ($labelsStatut as $slug => $meta): ?>
        <a href="<?= BASE_URL ?>/accueil/reservations?residence_id=<?= (int)$residenceCourante['id'] ?>&statut=<?= $slug ?>"
           class="btn btn-sm <?= ($filtres['statut'] ?? '') === $slug ? 'btn-' . $meta['couleur'] : 'btn-outline-' . $meta['couleur'] ?>">
            <i class="fas <?= $meta['icone'] ?> me-1"></i><?= $meta['libelle'] ?>
        </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($reservations)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune réservation
        <?= !empty($filtres['statut']) ? 'avec ce statut.' : 'pour cette résidence.' ?>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher (titre, demandeur, salle, équipement…)">
                </div>
                <div class="col-md-3">
                    <select id="filterType" class="form-select form-select-sm">
                        <option value="">— Tous les types —</option>
                        <?php foreach ($labelsType as $slug => $meta): ?>
                        <option value="<?= htmlspecialchars($meta['libelle']) ?>"><?= htmlspecialchars($meta['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="resTable">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Cible</th>
                            <th>Demandeur</th>
                            <th>Période</th>
                            <th>Statut</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservations as $r):
                            $t = $labelsType[$r['type_reservation']] ?? null;
                            $s = $labelsStatut[$r['statut']] ?? null;
                            $cible = '';
                            if ($r['type_reservation'] === 'salle')      $cible = htmlspecialchars($r['salle_nom'] ?? '— supprimée');
                            elseif ($r['type_reservation'] === 'equipement') $cible = htmlspecialchars($r['equipement_nom'] ?? '— supprimé');
                            else $cible = '<span class="badge bg-light text-dark border">' . htmlspecialchars(ucfirst($r['type_service'] ?? '')) . '</span>';

                            $demandeur = '';
                            if ($r['resident_id']) $demandeur = htmlspecialchars(($r['resident_prenom'] ?? '') . ' ' . ($r['resident_nom'] ?? '')) . ' <small class="text-muted">(résident)</small>';
                            elseif ($r['hote_id'])  $demandeur = htmlspecialchars(($r['hote_prenom'] ?? '') . ' ' . ($r['hote_nom'] ?? '')) . ' <small class="text-warning">(hôte)</small>';
                            else $demandeur = '<span class="text-muted">—</span>';
                        ?>
                        <tr>
                            <td>
                                <a href="<?= BASE_URL ?>/accueil/reservationShow/<?= (int)$r['id'] ?>" class="text-decoration-none fw-bold">
                                    <?= htmlspecialchars($r['titre']) ?>
                                </a>
                            </td>
                            <td>
                                <?php if ($t): ?>
                                <span class="badge bg-<?= $t['couleur'] ?>"><i class="fas <?= $t['icone'] ?> me-1"></i><?= htmlspecialchars($t['libelle']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?= $cible ?></td>
                            <td><?= $demandeur ?></td>
                            <td data-sort="<?= htmlspecialchars($r['date_debut']) ?>">
                                <small><?= date('d/m/Y H:i', strtotime($r['date_debut'])) ?></small><br>
                                <small class="text-muted">→ <?= date('d/m H:i', strtotime($r['date_fin'])) ?></small>
                            </td>
                            <td>
                                <?php if ($s): ?>
                                <span class="badge bg-<?= $s['couleur'] ?>"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/accueil/reservationShow/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                                <?php if (in_array($r['statut'], ['en_attente'], true)): ?>
                                <a href="<?= BASE_URL ?>/accueil/reservationForm/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></a>
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

<?php if (!empty($reservations)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('resTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [
        { id: 'filterType', column: 1 }
    ],
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
