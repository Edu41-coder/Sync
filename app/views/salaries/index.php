<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-users',          'text' => 'Salariés',        'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$nbAvecFiche = count(array_filter($staff, fn($s) => !empty($s['fiche_id'])));
$nbSansFiche = count($staff) - $nbAvecFiche;
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Salariés
            <small class="text-muted fs-6">— <?= $nbAvecFiche ?> fiche(s) RH créée(s)<?= $nbSansFiche > 0 ? ', ' . $nbSansFiche . ' sans fiche' : '' ?></small>
        </h2>
    </div>

    <!-- Filtres -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="only_with_fiche" value="1" id="cbOnlyFiche" <?= $onlyWithFiche ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="cbOnlyFiche">Avec fiche RH uniquement</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="inclure_sortis" value="1" id="cbSortis" <?= !$onlyActive ? 'checked' : '' ?>>
                        <label class="form-check-label small" for="cbSortis">Inclure salariés sortis</label>
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher (nom, email)...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </div>
    </form>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($staff)): ?>
            <p class="text-center py-5 text-muted mb-0">Aucun salarié trouvé pour ce filtre.</p>
            <?php else: ?>
            <table class="table table-hover mb-0" id="salariesTable">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Rôle</th>
                        <th>Email</th>
                        <th>Contrat</th>
                        <th>Convention</th>
                        <th>Embauche</th>
                        <th class="text-end">Salaire base</th>
                        <th class="text-center">Fiche RH</th>
                        <th class="text-end no-sort" style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s):
                        $a_fiche = !empty($s['fiche_id']);
                        $sorti   = !empty($s['date_sortie']) && strtotime($s['date_sortie']) <= time();
                    ?>
                    <tr class="<?= $sorti ? 'text-muted' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars(trim($s['prenom'] . ' ' . $s['nom'])) ?></strong>
                            <br><small class="text-muted">@<?= htmlspecialchars($s['username']) ?></small>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($s['role']) ?></span></td>
                        <td class="small"><?= $s['email'] ? htmlspecialchars($s['email']) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $a_fiche && !empty($s['type_contrat']) ? '<span class="badge bg-info">' . htmlspecialchars($s['type_contrat']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="small"><?= !empty($s['convention_nom']) ? htmlspecialchars($s['convention_nom']) : '<span class="text-muted">—</span>' ?></td>
                        <td class="small" data-sort="<?= !empty($s['date_embauche']) ? strtotime($s['date_embauche']) : 0 ?>">
                            <?= !empty($s['date_embauche']) ? date('d/m/Y', strtotime($s['date_embauche'])) : '<span class="text-muted">—</span>' ?>
                            <?php if ($sorti): ?><br><small class="text-danger">Sorti <?= date('d/m/Y', strtotime($s['date_sortie'])) ?></small><?php endif; ?>
                        </td>
                        <td class="text-end" data-sort="<?= (float)($s['salaire_brut_base'] ?? 0) ?>">
                            <?= !empty($s['salaire_brut_base']) ? number_format((float)$s['salaire_brut_base'], 2, ',', ' ') . ' €' : '<span class="text-muted">—</span>' ?>
                        </td>
                        <td class="text-center" data-sort="<?= $a_fiche ? 1 : 0 ?>">
                            <?php if ($a_fiche): ?>
                            <span class="badge bg-success"><i class="fas fa-check"></i></span>
                            <?php else: ?>
                            <span class="badge bg-warning text-dark">À créer</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <?php if ($a_fiche): ?>
                            <a href="<?= BASE_URL ?>/salarie/show/<?= (int)$s['user_id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/salarie/edit/<?= (int)$s['user_id'] ?>" class="btn btn-sm btn-outline-primary" title="<?= $a_fiche ? 'Modifier' : 'Créer fiche' ?>">
                                <i class="fas fa-<?= $a_fiche ? 'edit' : 'plus' ?>"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($staff) && count($staff) > 20): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($staff)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if (count($staff) > 20): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('salariesTable', { rowsPerPage: 20, searchInputId: 'searchInput', excludeColumns: [8], paginationId: 'pagination', infoId: 'tableInfo' });
</script>
<?php else: ?>
<script>
new DataTable('salariesTable', { searchInputId: 'searchInput', excludeColumns: [8] });
</script>
<?php endif; ?>
<?php endif; ?>
