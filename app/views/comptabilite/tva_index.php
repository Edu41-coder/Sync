<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-percent',        'text' => 'Déclarations TVA', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatuts = [
    'brouillon' => 'secondary',
    'declaree'  => 'success',
    'annulee'   => 'danger',
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-percent me-2 text-primary"></i>Déclarations TVA</h2>
        <a href="<?= BASE_URL ?>/comptabilite/tvaCalculer" class="btn btn-primary">
            <i class="fas fa-calculator me-1"></i>Nouveau calcul
        </a>
    </div>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Calcul de TVA collectée vs déductible à partir des écritures saisies dans les modules.
        <strong>Vitrine pilote</strong> — vérifier les chiffres avec le cabinet comptable avant transmission au SIE.
    </div>

    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Régime</label>
                    <select name="regime" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($regimes as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $regime === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($statuts as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $statut === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($declarations)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune déclaration TVA archivée</h5>
            <p class="text-muted mb-0">Cliquez sur « Nouveau calcul » pour préparer une déclaration.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="tvaTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Résidence</th>
                            <th>Régime</th>
                            <th>Période</th>
                            <th class="text-end">CA HT</th>
                            <th class="text-end">TVA collectée</th>
                            <th class="text-end">TVA déductible</th>
                            <th class="text-end">Solde</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($declarations as $d):
                            $caHt = (float)$d['ca_ht_20'] + (float)$d['ca_ht_10'] + (float)$d['ca_ht_55'] + (float)$d['ca_ht_21'] + (float)$d['ca_ht_exonere'];
                            $solde = (float)$d['tva_a_payer'] - (float)$d['credit_a_reporter'];
                        ?>
                        <tr>
                            <td><strong>#<?= (int)$d['id'] ?></strong></td>
                            <td><?= htmlspecialchars($d['residence_nom'] ?? '—') ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($regimes[$d['regime']] ?? $d['regime']) ?></span></td>
                            <td>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($d['periode_debut']))) ?> →</small><br>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($d['periode_fin']))) ?></small>
                            </td>
                            <td class="text-end" data-sort="<?= $caHt ?>"><?= number_format($caHt, 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= (float)$d['tva_collectee_total'] ?>"><?= number_format((float)$d['tva_collectee_total'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= (float)$d['tva_deductible_total'] ?>"><?= number_format((float)$d['tva_deductible_total'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= $solde ?>">
                                <?php if ((float)$d['tva_a_payer'] > 0): ?>
                                    <strong class="text-danger">+<?= number_format((float)$d['tva_a_payer'], 2, ',', ' ') ?> €</strong>
                                <?php elseif ((float)$d['credit_a_reporter'] > 0): ?>
                                    <strong class="text-success">-<?= number_format((float)$d['credit_a_reporter'], 2, ',', ' ') ?> €</strong>
                                <?php else: ?>
                                    <span class="text-muted">0,00 €</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><span class="badge bg-<?= $badgeStatuts[$d['statut']] ?? 'secondary' ?>"><?= htmlspecialchars($statuts[$d['statut']] ?? $d['statut']) ?></span></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/tvaShow/<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="tvaPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="tvaInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="tvaPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($declarations)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tvaTable', {
    rowsPerPage: 25,
    excludeColumns: [9],
    paginationId: 'tvaPager',
    infoId: 'tvaInfo'
});
</script>
<?php endif; ?>
