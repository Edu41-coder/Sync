<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Assemblées Générales', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$labelsStatut = [
    'planifiee' => ['couleur' => 'secondary', 'libelle' => 'Planifiée', 'icone' => 'fa-calendar'],
    'convoquee' => ['couleur' => 'info',      'libelle' => 'Convoquée', 'icone' => 'fa-paper-plane'],
    'tenue'     => ['couleur' => 'success',   'libelle' => 'Tenue',     'icone' => 'fa-check'],
    'annulee'   => ['couleur' => 'danger',    'libelle' => 'Annulée',   'icone' => 'fa-ban'],
];
$labelsType = ['ordinaire' => 'AGO', 'extraordinaire' => 'AGE'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-gavel text-primary me-2"></i>Assemblées Générales</h1>
            <p class="text-muted mb-0"><?= count($residences) ?> résidence<?= count($residences) > 1 ? 's' : '' ?> · <?= count($ags) ?> AG affichée<?= count($ags) > 1 ? 's' : '' ?></p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($isManager && !empty($residences)): ?>
            <a href="<?= BASE_URL ?>/assemblee/form<?= $residenceIdSelected ? '?residence_id=' . $residenceIdSelected : '' ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Nouvelle AG
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php else: ?>

    <!-- KPI -->
    <div class="row g-3 mb-3">
        <div class="col-md-4 col-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info small fw-bold mb-1">AG à venir</h6>
                    <h3 class="mb-0"><?= (int)$stats['planifiees'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-6">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success small fw-bold mb-1">AG tenues (<?= date('Y') ?>)</h6>
                    <h3 class="mb-0"><?= (int)$stats['tenues_annee'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4 col-12">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary small fw-bold mb-1">Prochaine AG</h6>
                    <?php if ($stats['prochaine']): ?>
                    <a href="<?= BASE_URL ?>/assemblee/show/<?= (int)$stats['prochaine']['id'] ?>" class="text-decoration-none">
                        <strong><?= date('d/m/Y H:i', strtotime($stats['prochaine']['date_ag'])) ?></strong>
                        <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($labelsType[$stats['prochaine']['type']] ?? '') ?></span>
                        <small class="d-block text-muted"><?= htmlspecialchars($stats['prochaine']['residence_nom']) ?></small>
                    </a>
                    <?php else: ?>
                    <small class="text-muted">Aucune AG planifiée</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?= BASE_URL ?>/assemblee/index" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">— Toutes —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= $residenceIdSelected === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">— Tous —</option>
                        <?php foreach ($labelsStatut as $slug => $meta): ?>
                        <option value="<?= $slug ?>" <?= ($filtres['statut'] ?? '') === $slug ? 'selected' : '' ?>><?= htmlspecialchars($meta['libelle']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">— Tous —</option>
                        <option value="ordinaire" <?= ($filtres['type'] ?? '') === 'ordinaire' ? 'selected' : '' ?>>AGO</option>
                        <option value="extraordinaire" <?= ($filtres['type'] ?? '') === 'extraordinaire' ? 'selected' : '' ?>>AGE</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Année</label>
                    <input type="number" name="annee" class="form-control form-control-sm" min="2000" max="2050"
                           value="<?= htmlspecialchars($filtres['annee'] ?? '') ?>" placeholder="<?= date('Y') ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                </div>
                <div class="col-md-1">
                    <a href="<?= BASE_URL ?>/assemblee/index" class="btn btn-sm btn-outline-secondary w-100" title="Réinitialiser"><i class="fas fa-redo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($ags)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune AG ne correspond aux filtres.
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/assemblee/form" class="alert-link">Créer la première</a>.
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher (lieu, résidence…)">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="agTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Résidence</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th class="text-center">Résolutions</th>
                            <th class="text-center">Chantiers</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ags as $a):
                            $s = $labelsStatut[$a['statut']];
                        ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($a['date_ag']) ?>">
                                <strong><?= date('d/m/Y', strtotime($a['date_ag'])) ?></strong>
                                <small class="text-muted d-block"><?= date('H:i', strtotime($a['date_ag'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $a['type'] === 'extraordinaire' ? 'warning text-dark' : 'primary' ?>">
                                    <?= htmlspecialchars($labelsType[$a['type']]) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['residence_nom']) ?></td>
                            <td><small><?= htmlspecialchars($a['lieu'] ?? '—') ?></small></td>
                            <td>
                                <span class="badge bg-<?= $s['couleur'] ?>"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$a['nb_resolutions'] > 0): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-list-ol me-1"></i><?= (int)$a['nb_resolutions'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$a['nb_chantiers'] > 0): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-hammer me-1"></i><?= (int)$a['nb_chantiers'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/assemblee/show/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                                <?php if ($isManager && $a['statut'] !== 'annulee'): ?>
                                <a href="<?= BASE_URL ?>/assemblee/form/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></a>
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

<?php if (!empty($ags)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('agTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    excludeColumns: [7],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
