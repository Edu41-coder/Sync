<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typesLabels = Fournisseur::TYPES_SERVICE;
$typeColors = [
    'restauration' => 'warning', 'menage' => 'info', 'jardinage' => 'success',
    'piscine' => 'primary', 'travaux_elec' => 'danger', 'travaux_plomberie' => 'secondary', 'autre' => 'dark'
];
?>

<div class="container-fluid py-4">
    <?php
    // Construire l'URL d'export en préservant les filtres en cours
    $exportParams = array_filter([
        'type_service'     => $typeFilter ?? null,
        'q'                => $search ?? null,
        'inclure_inactifs' => !$actifsOnly ? '1' : null,
    ], fn($v) => $v !== null && $v !== '');
    $exportUrl = BASE_URL . '/fournisseur/export' . (empty($exportParams) ? '' : '?' . http_build_query($exportParams));
    ?>
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-truck-loading me-2 text-primary"></i>Fournisseurs</h2>
        <div class="d-flex gap-2">
            <?php if (!empty($fournisseurs)): ?>
            <a href="<?= htmlspecialchars($exportUrl) ?>" class="btn btn-outline-secondary" title="Exporter la liste filtrée en CSV">
                <i class="fas fa-file-csv me-1"></i>Exporter CSV
            </a>
            <?php endif; ?>
            <a href="<?= BASE_URL ?>/fournisseur/create" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Nouveau fournisseur
            </a>
        </div>
    </div>

    <!-- Filtres visuels par type de service -->
    <?php
    // Préserver les autres params dans les liens des pills (sauf type_service qu'on remplace)
    $baseQuery = array_filter([
        'q'                => $search ?? null,
        'inclure_inactifs' => !$actifsOnly ? '1' : null,
    ], fn($v) => $v !== null && $v !== '');
    $buildUrl = function(?string $type) use ($baseQuery) {
        $params = $baseQuery;
        if ($type !== null) $params['type_service'] = $type;
        return BASE_URL . '/fournisseur/index' . (empty($params) ? '' : '?' . http_build_query($params));
    };
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                <span class="text-muted small me-2"><i class="fas fa-filter me-1"></i>Filtrer par service :</span>
                <a href="<?= htmlspecialchars($buildUrl(null)) ?>" class="badge text-decoration-none px-3 py-2 <?= empty($typeFilter) ? 'bg-primary' : 'bg-light text-dark border' ?>">
                    <i class="fas fa-th-large me-1"></i>Tous
                </a>
                <?php foreach ($typesLabels as $k => $l): ?>
                <a href="<?= htmlspecialchars($buildUrl($k)) ?>" class="badge text-decoration-none px-3 py-2 <?= $typeFilter === $k ? 'bg-' . ($typeColors[$k] ?? 'secondary') : 'bg-light text-dark border' ?>">
                    <?= htmlspecialchars($l) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <form method="GET" class="row g-2 align-items-center mt-1">
                <?php if (!empty($typeFilter)): ?>
                <input type="hidden" name="type_service" value="<?= htmlspecialchars($typeFilter) ?>">
                <?php endif; ?>
                <div class="col-12 col-md-5">
                    <input type="text" name="q" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher (nom, ville, SIRET...)" value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inclure_inactifs" value="1" id="cbInactifs" <?= !$actifsOnly ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small" for="cbInactifs">Inclure inactifs</label>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-search me-1"></i>Rechercher</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="fournTable">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Services</th>
                        <th>Contact</th>
                        <th>Ville</th>
                        <th class="text-center">Résidences liées</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:160px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fournisseurs as $f):
                        $types = $f['type_service'] ? explode(',', $f['type_service']) : [];
                    ?>
                    <tr class="<?= $f['actif'] ? '' : 'text-muted' ?>">
                        <td>
                            <strong><a href="<?= BASE_URL ?>/fournisseur/show/<?= (int)$f['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($f['nom']) ?></a></strong>
                            <?php if ($f['siret']): ?><br><small class="text-muted">SIRET <?= htmlspecialchars($f['siret']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php foreach ($types as $t): if (!$t) continue; ?>
                                <span class="badge bg-<?= $typeColors[$t] ?? 'secondary' ?> me-1"><?= $typesLabels[$t] ?? $t ?></span>
                            <?php endforeach; ?>
                            <?php if (empty($types)): ?><small class="text-muted">—</small><?php endif; ?>
                        </td>
                        <td class="small">
                            <?php if ($f['contact_nom']): ?><?= htmlspecialchars($f['contact_nom']) ?><br><?php endif; ?>
                            <?php if ($f['telephone']): ?><i class="fas fa-phone me-1 text-muted"></i><?= htmlspecialchars($f['telephone']) ?><br><?php endif; ?>
                            <?php if ($f['email']): ?><a href="mailto:<?= htmlspecialchars($f['email']) ?>" class="text-decoration-none"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($f['email']) ?></a><?php endif; ?>
                        </td>
                        <td><?= $f['ville'] ? htmlspecialchars($f['ville']) : '—' ?></td>
                        <td class="text-center" data-sort="<?= (int)$f['nb_residences'] ?>">
                            <span class="badge bg-info"><?= (int)$f['nb_residences'] ?></span>
                        </td>
                        <td class="text-center" data-sort="<?= (int)$f['actif'] ?>">
                            <span class="badge bg-<?= $f['actif'] ? 'success' : 'secondary' ?>"><?= $f['actif'] ? 'Actif' : 'Inactif' ?></span>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/fournisseur/show/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-info" title="Détail"><i class="fas fa-eye"></i></a>
                            <a href="<?= BASE_URL ?>/fournisseur/edit/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Modifier"><i class="fas fa-edit"></i></a>
                            <?php if ($f['actif']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/fournisseur/delete/<?= (int)$f['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver ce fournisseur ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver"><i class="fas fa-times"></i></button>
                            </form>
                            <?php else: ?>
                            <form method="POST" action="<?= BASE_URL ?>/fournisseur/activate/<?= (int)$f['id'] ?>" class="d-inline" onsubmit="return confirm('Réactiver ce fournisseur ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver"><i class="fas fa-check"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($fournisseurs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun fournisseur<?= $typeFilter ? ' de ce type' : '' ?>.</td></tr>
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

<?php if (!empty($fournisseurs)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('fournTable', {
    rowsPerPage: 25,
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
