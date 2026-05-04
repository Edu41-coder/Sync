<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-toolbox',        'text' => 'Équipements prêtables', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$labelsType = [
    'mobilite'     => ['icon' => 'fa-wheelchair',   'libelle' => 'Mobilité',     'couleur' => 'primary'],
    'informatique' => ['icon' => 'fa-laptop',       'libelle' => 'Informatique', 'couleur' => 'info'],
    'loisirs'      => ['icon' => 'fa-puzzle-piece', 'libelle' => 'Loisirs',      'couleur' => 'success'],
    'medical'      => ['icon' => 'fa-heart-pulse',  'libelle' => 'Médical',      'couleur' => 'danger'],
    'autre'        => ['icon' => 'fa-box',          'libelle' => 'Autre',        'couleur' => 'secondary'],
];
$labelsStatut = [
    'disponible'   => ['couleur' => 'success', 'libelle' => 'Disponible'],
    'prete'        => ['couleur' => 'warning', 'libelle' => 'Prêté'],
    'hors_service' => ['couleur' => 'danger',  'libelle' => 'Hors service'],
    'maintenance'  => ['couleur' => 'info',    'libelle' => 'Maintenance'],
];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-toolbox text-info me-2"></i>Équipements prêtables</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · <?= count($equipements) ?> équipement<?= count($equipements) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/accueil/equipements">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($isManager && $residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/equipementForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-info text-white">
                <i class="fas fa-plus me-1"></i>Nouvel équipement
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php elseif (empty($equipements)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucun équipement prêtable dans cette résidence.
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/accueil/equipementForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="alert-link">Créer le premier</a>.
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher (nom, n° série, notes…)">
                </div>
                <div class="col-md-3">
                    <select id="filterType" class="form-select form-select-sm">
                        <option value="">— Tous les types —</option>
                        <?php foreach ($labelsType as $slug => $meta): ?>
                        <option value="<?= htmlspecialchars($meta['libelle']) ?>"><?= htmlspecialchars($meta['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="filterStatut" class="form-select form-select-sm">
                        <option value="">— Tous les statuts —</option>
                        <?php foreach ($labelsStatut as $slug => $meta): ?>
                        <option value="<?= htmlspecialchars($meta['libelle']) ?>"><?= htmlspecialchars($meta['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="equipTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>N° série</th>
                            <th>Statut</th>
                            <th><?= !$isManager ? 'Notes' : '' ?></th>
                            <?php if ($isManager): ?><th class="text-end">Actions</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipements as $e):
                            $t = $labelsType[$e['type']] ?? $labelsType['autre'];
                            $s = $labelsStatut[$e['statut']] ?? $labelsStatut['disponible'];
                        ?>
                        <tr class="<?= !$e['actif'] ? 'opacity-50' : '' ?>">
                            <td>
                                <strong><?= htmlspecialchars($e['nom']) ?></strong>
                                <?php if (!$e['actif']): ?>
                                <span class="badge bg-secondary ms-1">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?= $t['couleur'] ?>"><i class="fas <?= $t['icon'] ?> me-1"></i><?= htmlspecialchars($t['libelle']) ?></span>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($e['numero_serie'] ?: '—') ?></small></td>
                            <td>
                                <span class="badge bg-<?= $s['couleur'] ?>"><?= htmlspecialchars($s['libelle']) ?></span>
                            </td>
                            <td>
                                <small class="text-muted"><?= $e['notes'] ? nl2br(htmlspecialchars(mb_substr($e['notes'], 0, 80) . (mb_strlen($e['notes']) > 80 ? '…' : ''))) : '' ?></small>
                            </td>
                            <?php if ($isManager): ?>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/accueil/equipementForm/<?= (int)$e['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit"></i></a>
                                <form method="POST" action="<?= BASE_URL ?>/accueil/equipementDelete/<?= (int)$e['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer cet équipement ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                            <?php endif; ?>
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
</div>

<?php if (!empty($equipements)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('equipTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    filters: [
        { id: 'filterType',   column: 1 },
        { id: 'filterStatut', column: 3 }
    ],
    excludeColumns: [<?= $isManager ? '5' : '4' ?>],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
