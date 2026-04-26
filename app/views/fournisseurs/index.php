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
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-truck-loading me-2 text-primary"></i>Fournisseurs</h2>
        <a href="<?= BASE_URL ?>/fournisseur/create" class="btn btn-success">
            <i class="fas fa-plus me-1"></i>Nouveau fournisseur
        </a>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Type :</label></div>
                <div class="col-auto">
                    <select name="type_service" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous types</option>
                        <?php foreach ($typesLabels as $k => $l): ?>
                        <option value="<?= $k ?>" <?= $typeFilter === $k ? 'selected' : '' ?>><?= $l ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-4">
                    <input type="text" name="q" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher (nom, ville, SIRET...)" value="<?= htmlspecialchars($search ?? '') ?>">
                </div>
                <div class="col-auto">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inclure_inactifs" value="1" id="cbInactifs" <?= !$actifsOnly ? 'checked' : '' ?> onchange="this.form.submit()">
                        <label class="form-check-label small" for="cbInactifs">Inclure inactifs</label>
                    </div>
                </div>
                <div class="col-auto">
                    <button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrer</button>
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
                            <a href="<?= BASE_URL ?>/fournisseur/delete/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Désactiver ce fournisseur ?')" title="Désactiver"><i class="fas fa-times"></i></a>
                            <?php else: ?>
                            <a href="<?= BASE_URL ?>/fournisseur/activate/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-success" title="Réactiver"><i class="fas fa-check"></i></a>
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
