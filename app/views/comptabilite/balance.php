<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-chart-bar',      'text' => 'Balance',         'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];

// Couleurs par type comptable
$typeBadge = [
    'actif'   => 'primary',
    'passif'  => 'success',
    'charge'  => 'danger',
    'produit' => 'warning',
    'tiers'   => 'info',
    'autre'   => 'secondary',
];
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-chart-bar me-2 text-primary"></i>Balance comptable</h2>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes les résidences accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Mois (optionnel)</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Période entière</option>
                        <?php foreach ($moisLabels as $i => $lbl): ?>
                        <option value="<?= $i + 1 ?>" <?= ($i + 1) === (int)$mois ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-sync me-1"></i>Recharger</button>
                </div>
            </div>
        </div>
    </form>

    <div class="alert alert-info py-2 small">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Période :</strong> <?= htmlspecialchars($periode['libelle']) ?>
        (<?= htmlspecialchars($periode['debut']) ?> → <?= htmlspecialchars($periode['fin']) ?>)
        — Convention : recette = crédit, dépense = débit. Brouillons exclus.
    </div>

    <?php if (empty($balance)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune écriture sur la période</h5>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="balanceTable" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N°</th>
                            <th>Libellé</th>
                            <th>Type</th>
                            <th class="text-center">Nb écr.</th>
                            <th class="text-end">Total débit</th>
                            <th class="text-end">Total crédit</th>
                            <th class="text-end">Solde</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($balance as $b):
                            $type = (string)$b['type'];
                            $solde = (float)$b['solde'];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($b['numero_compte']) ?></strong></td>
                            <td><?= htmlspecialchars($b['libelle']) ?></td>
                            <td><span class="badge bg-<?= $typeBadge[$type] ?? 'secondary' ?>"><?= htmlspecialchars(ucfirst($type)) ?></span></td>
                            <td class="text-center"><?= (int)$b['nb'] ?></td>
                            <td class="text-end" data-sort="<?= (float)$b['total_debit'] ?>"><?= number_format((float)$b['total_debit'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= (float)$b['total_credit'] ?>"><?= number_format((float)$b['total_credit'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= $solde ?>">
                                <?php if ($solde > 0): ?>
                                    <strong class="text-success">+<?= number_format($solde, 2, ',', ' ') ?> €</strong>
                                <?php elseif ($solde < 0): ?>
                                    <strong class="text-danger"><?= number_format($solde, 2, ',', ' ') ?> €</strong>
                                <?php else: ?>
                                    <span class="text-muted">0,00 €</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/grandLivre/<?= (int)$b['compte_id'] ?>?residence_id=<?= $selectedResidence ?>&annee=<?= (int)$annee ?>&mois=<?= $mois ?? '' ?>" class="btn btn-sm btn-outline-primary" title="Grand livre">
                                    <i class="fas fa-book"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold">
                        <tr>
                            <td colspan="4" class="text-end">TOTAUX</td>
                            <td class="text-end"><?= number_format((float)$totalDebit, 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format((float)$totalCredit, 2, ',', ' ') ?> €</td>
                            <td class="text-end <?= abs((float)$totalCredit - (float)$totalDebit) < 0.01 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format((float)$totalCredit - (float)$totalDebit, 2, ',', ' ') ?> €
                                <?php if (abs((float)$totalCredit - (float)$totalDebit) < 0.01): ?>
                                    <i class="fas fa-check-circle ms-1" title="Équilibrée"></i>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle ms-1 text-warning" title="Écart"></i>
                                <?php endif; ?>
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div id="balancePagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="balanceInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="balancePager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($balance)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('balanceTable', {
    rowsPerPage: 30,
    excludeColumns: [7],
    paginationId: 'balancePager',
    infoId: 'balanceInfo'
});
</script>
<?php endif; ?>
