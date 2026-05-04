<?php
$moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-list',           'text' => 'Écritures',       'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Écritures comptables — <?= $annee ?><?= $mois ? ' / ' . $moisNoms[$mois] : '' ?></h2>
        <a href="<?= BASE_URL ?>/comptabilite/index?annee=<?= $annee ?>&residence_id=<?= (int)$selectedResidence ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour dashboard
        </a>
    </div>

    <!-- Filtres -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 4; $y--): ?>
                        <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Mois</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Année complète</option>
                        <?php for ($m = 1; $m <= 12; $m++): ?>
                        <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>><?= $moisNoms[$m] ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Module</label>
                    <select name="module" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($modulesAll as $code => $label): ?>
                        <option value="<?= $code ?>" <?= $module === $code ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label small mb-1">Type</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <option value="recette" <?= $type === 'recette' ? 'selected' : '' ?>>Recette</option>
                        <option value="depense" <?= $type === 'depense' ? 'selected' : '' ?>>Dépense</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Recherche libellé</label>
                    <input type="text" name="q" id="searchInput" class="form-control form-control-sm" value="<?= htmlspecialchars($search ?? '') ?>" placeholder="...">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i></button>
                </div>
            </div>
        </div>
    </form>

    <!-- Table écritures -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-book me-2"></i>Journal — <?= count($ecritures) ?> écriture(s)<?= count($ecritures) >= 1000 ? ' (limité)' : '' ?></h6>
            <input type="text" id="searchTable" class="form-control form-control-sm" style="max-width:250px" placeholder="Filtrer la table...">
        </div>
        <div class="card-body p-0">
            <?php if (empty($ecritures)): ?>
            <p class="text-center text-muted py-5 mb-0">Aucune écriture pour les filtres sélectionnés.</p>
            <?php else: ?>
            <table class="table table-sm table-hover mb-0" id="ecrituresTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Module</th>
                        <th>Catégorie</th>
                        <th>Libellé</th>
                        <th>Compte</th>
                        <th>Résidence</th>
                        <th class="text-end">HT</th>
                        <th class="text-end">TVA</th>
                        <th class="text-end">TTC</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ecritures as $e):
                        $col = $moduleColors[$e['module_source']] ?? 'secondary';
                        $ts  = strtotime($e['date_ecriture']);
                    ?>
                    <tr>
                        <td class="small" data-sort="<?= $ts ?>"><?= date('d/m/Y', $ts) ?></td>
                        <td><span class="badge bg-<?= $col ?>"><?= htmlspecialchars($modulesAll[$e['module_source']] ?? $e['module_source']) ?></span></td>
                        <td><small><?= htmlspecialchars(str_replace('_', ' ', $e['categorie'])) ?></small></td>
                        <td><?= htmlspecialchars($e['libelle']) ?></td>
                        <td class="small"><?= !empty($e['numero_compte']) ? '<span class="badge bg-light text-dark">' . htmlspecialchars($e['numero_compte']) . '</span>' : '<span class="text-muted">—</span>' ?></td>
                        <td class="small"><?= htmlspecialchars($e['residence_nom'] ?? '') ?></td>
                        <td class="text-end" data-sort="<?= (float)$e['montant_ht'] ?>"><?= number_format((float)$e['montant_ht'], 2, ',', ' ') ?></td>
                        <td class="text-end small text-muted"><?= number_format((float)$e['montant_tva'], 2, ',', ' ') ?></td>
                        <td class="text-end" data-sort="<?= (float)$e['montant_ttc'] ?>"><strong class="text-<?= $e['type_ecriture'] === 'recette' ? 'success' : 'danger' ?>"><?= ($e['type_ecriture'] === 'depense' ? '-' : '+') ?><?= number_format((float)$e['montant_ttc'], 2, ',', ' ') ?> &euro;</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php if (!empty($ecritures) && count($ecritures) > 25): ?>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($ecritures)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if (count($ecritures) > 25): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('ecrituresTable', {
    rowsPerPage: 25,
    searchInputId: 'searchTable',
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php else: ?>
<script>
new DataTable('ecrituresTable', { searchInputId: 'searchTable' });
</script>
<?php endif; ?>
<?php endif; ?>
