<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-chart-bar',      'text' => 'Balance',         'url' => BASE_URL . '/comptabilite/balance'],
    ['icon' => 'fas fa-book',           'text' => 'Grand livre',     'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
?>

<div class="container-fluid py-4">
    <h2 class="mb-3">
        <i class="fas fa-book me-2 text-primary"></i>Grand livre
        <?php if ($compteSel): ?>
            — <span class="text-primary"><?= htmlspecialchars($compteSel['numero_compte']) ?></span>
            <small class="text-muted"><?= htmlspecialchars($compteSel['libelle']) ?></small>
        <?php endif; ?>
    </h2>

    <form method="GET" class="card shadow-sm mb-3" id="filterForm">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Compte</label>
                    <select name="compte_id_select" id="compteSelect" class="form-select form-select-sm">
                        <option value="">— Choisir un compte —</option>
                        <?php foreach ($comptes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$compteSelId ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['numero_compte']) ?> — <?= htmlspecialchars($c['libelle']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes accessibles</option>
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
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Mois</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Période entière</option>
                        <?php foreach ($moisLabels as $i => $lbl): ?>
                        <option value="<?= $i + 1 ?>" <?= ($i + 1) === (int)$mois ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" id="btnApplyCompte" class="btn btn-sm btn-primary w-100"><i class="fas fa-search me-1"></i>Afficher</button>
                </div>
            </div>
        </div>
    </form>

    <div class="alert alert-info py-2 small">
        <i class="fas fa-info-circle me-1"></i>
        <strong>Période :</strong> <?= htmlspecialchars($periode['libelle']) ?>
        (<?= htmlspecialchars($periode['debut']) ?> → <?= htmlspecialchars($periode['fin']) ?>)
    </div>

    <?php if (!$compteSelId): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-mouse-pointer fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Sélectionnez un compte</h5>
            <p class="text-muted">Choisissez un compte dans la liste ci-dessus puis cliquez sur « Afficher »
                pour voir le détail de toutes ses écritures sur la période.</p>
        </div>
    </div>
    <?php elseif (empty($ecritures)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune écriture sur ce compte pour la période</h5>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="glTable" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Pièce</th>
                            <th>Module</th>
                            <th>Libellé</th>
                            <th>Résidence</th>
                            <th class="text-end">Débit</th>
                            <th class="text-end">Crédit</th>
                            <th class="text-end">Solde progr.</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ecritures as $e): ?>
                        <tr>
                            <td><?= htmlspecialchars(date('d/m/Y', strtotime($e['date_ecriture']))) ?></td>
                            <td><?= htmlspecialchars($e['piece_justificative'] ?? ('ECR-' . str_pad((string)$e['id'], 6, '0', STR_PAD_LEFT))) ?></td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars(Ecriture::MODULES[$e['module_source']] ?? $e['module_source']) ?></span></td>
                            <td><?= htmlspecialchars($e['libelle']) ?></td>
                            <td><small><?= htmlspecialchars($e['residence_nom'] ?? '—') ?></small></td>
                            <td class="text-end <?= $e['debit'] > 0 ? 'text-danger' : 'text-muted' ?>">
                                <?= $e['debit'] > 0 ? number_format($e['debit'], 2, ',', ' ') . ' €' : '—' ?>
                            </td>
                            <td class="text-end <?= $e['credit'] > 0 ? 'text-success' : 'text-muted' ?>">
                                <?= $e['credit'] > 0 ? number_format($e['credit'], 2, ',', ' ') . ' €' : '—' ?>
                            </td>
                            <td class="text-end fw-bold <?= $e['solde_progressif'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                <?= number_format($e['solde_progressif'], 2, ',', ' ') ?> €
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="glPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="glInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="glPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const btn = document.getElementById('btnApplyCompte');
    if (!btn) return;
    btn.addEventListener('click', () => {
        const cmp = document.getElementById('compteSelect').value;
        const form = document.getElementById('filterForm');
        const params = new FormData(form);
        // Construire l'URL : /comptabilite/grandLivre/{compteId}?...
        const base = '<?= BASE_URL ?>/comptabilite/grandLivre' + (cmp ? '/' + cmp : '');
        const qs = new URLSearchParams();
        ['residence_id', 'annee', 'mois'].forEach(k => {
            const v = params.get(k);
            if (v !== null && v !== '') qs.set(k, v);
        });
        window.location.href = base + (qs.toString() ? '?' + qs.toString() : '');
    });
})();
</script>

<?php if (!empty($ecritures)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('glTable', {
    rowsPerPage: 25,
    paginationId: 'glPager',
    infoId: 'glInfo'
});
</script>
<?php endif; ?>
