<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité ' . (int)$annee, 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$badgeSource = ['intervention'=>'info','chantier'=>'warning','ascenseur'=>'secondary'];
$anneeNow = (int)date('Y');
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-calculator text-success me-2"></i>Comptabilité maintenance</h1>
            <p class="text-muted mb-0">Année <?= (int)$annee ?> · Réservé aux managers</p>
        </div>
        <form method="GET" action="<?= BASE_URL ?>/maintenance/comptabilite">
            <select name="annee" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php for ($a = $anneeNow + 1; $a >= $anneeNow - 3; $a--): ?>
                <option value="<?= $a ?>" <?= (int)$annee === $a ? 'selected' : '' ?>><?= $a ?></option>
                <?php endfor; ?>
            </select>
        </form>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success small fw-bold mb-1">Total dépenses <?= (int)$annee ?></h6>
                    <h3 class="mb-0"><?= number_format($totaux['total'], 0, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info small fw-bold mb-1">Interventions</h6>
                    <h4 class="mb-0"><?= number_format($totaux['interventions'], 0, ',', ' ') ?> €</h4>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning small fw-bold mb-1">Chantiers payés</h6>
                    <h4 class="mb-0"><?= number_format($totaux['chantiers_payes'], 0, ',', ' ') ?> €</h4>
                    <small class="text-muted">Engagé : <?= number_format($totaux['chantiers_engages'], 0, ',', ' ') ?> €</small>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-secondary border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-secondary small fw-bold mb-1">Ascenseurs</h6>
                    <h4 class="mb-0"><?= number_format($totaux['ascenseurs'], 0, ',', ' ') ?> €</h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique mensuel -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light"><strong><i class="fas fa-chart-bar me-2"></i>Évolution mensuelle <?= (int)$annee ?></strong></div>
        <div class="card-body">
            <canvas id="chartMensuel" height="80"></canvas>
        </div>
    </div>

    <div class="row g-3">
        <!-- Ventilation par spécialité -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-tags me-2"></i>Par spécialité</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Spécialité</th><th class="text-center">Interv.</th><th class="text-center">Chantiers</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($parSpecialite as $s): if ($s['cout_total'] == 0) continue; ?>
                            <tr>
                                <td><i class="<?= htmlspecialchars($s['icone']) ?> me-1" style="color:<?= htmlspecialchars($s['couleur']) ?>"></i><?= htmlspecialchars($s['nom']) ?></td>
                                <td class="text-center"><small><?= (int)$s['nb_interventions'] ?> · <?= number_format((float)$s['cout_interventions'], 0, ',', ' ') ?> €</small></td>
                                <td class="text-center"><small><?= (int)$s['nb_chantiers'] ?> · <?= number_format((float)$s['cout_chantiers'], 0, ',', ' ') ?> €</small></td>
                                <td class="text-end"><strong><?= number_format((float)$s['cout_total'], 0, ',', ' ') ?> €</strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php $totalSpec = array_sum(array_column($parSpecialite, 'cout_total')); if ($totalSpec == 0): ?>
                            <tr><td colspan="4" class="text-center py-3 text-muted small">Aucune dépense imputée à une spécialité.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Ventilation par résidence -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-building me-2"></i>Par résidence</strong></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Résidence</th><th class="text-end">Interv.</th><th class="text-end">Chantiers</th><th class="text-end">Asc.</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($parResidence as $r): if ($r['cout_total'] == 0) continue; ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($r['nom']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($r['ville']) ?></small></td>
                                <td class="text-end"><small><?= number_format((float)$r['cout_interventions'], 0, ',', ' ') ?> €</small></td>
                                <td class="text-end"><small><?= number_format((float)$r['cout_chantiers'], 0, ',', ' ') ?> €</small></td>
                                <td class="text-end"><small><?= number_format((float)$r['cout_ascenseurs'], 0, ',', ' ') ?> €</small></td>
                                <td class="text-end"><strong><?= number_format((float)$r['cout_total'], 0, ',', ' ') ?> €</strong></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php $totalRes = array_sum(array_column($parResidence, 'cout_total')); if ($totalRes == 0): ?>
                            <tr><td colspan="5" class="text-center py-3 text-muted small">Aucune dépense.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Détail écritures -->
    <div class="card shadow-sm mt-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <strong><i class="fas fa-list me-2"></i>Détail des écritures (<?= count($detailEcritures) ?>)</strong>
            <input type="text" id="searchEcritures" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:240px">
        </div>
        <div class="card-body p-0">
            <?php if (empty($detailEcritures)): ?>
            <div class="text-center py-3 text-muted small">Aucune écriture sur <?= (int)$annee ?>.</div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0" id="tableEcritures">
                    <thead class="table-light">
                        <tr><th>Date</th><th>Source</th><th>Libellé</th><th>Spécialité</th><th>Résidence</th><th class="text-end">Montant</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailEcritures as $e): ?>
                        <tr>
                            <td><small><?= !empty($e['date_op']) ? date('d/m/Y', strtotime($e['date_op'])) : '—' ?></small></td>
                            <td><span class="badge bg-<?= $badgeSource[$e['source']] ?? 'secondary' ?>"><?= $e['source'] ?></span></td>
                            <td><?= htmlspecialchars($e['libelle']) ?></td>
                            <td><small><?= htmlspecialchars($e['specialite'] ?? '—') ?></small></td>
                            <td><small><?= htmlspecialchars($e['residence_nom']) ?></small></td>
                            <td class="text-end"><strong><?= number_format((float)$e['montant'], 2, ',', ' ') ?> €</strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($detailEcritures) > 15): ?>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoEcritures" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationEcritures"></ul>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartMensuel');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan','Fév','Mar','Avr','Mai','Juin','Juil','Août','Sept','Oct','Nov','Déc'],
                datasets: [{
                    label: 'Dépenses (€)',
                    data: <?= json_encode($syntheseMensuelle) ?>,
                    backgroundColor: 'rgba(253, 126, 20, 0.7)',
                    borderColor: 'rgba(253, 126, 20, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-FR') + ' €' } } }
            }
        });
    }
});
</script>

<?php if (!empty($detailEcritures) && count($detailEcritures) > 15): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableEcritures', {
    rowsPerPage: 15, searchInputId: 'searchEcritures',
    paginationId: 'paginationEcritures', infoId: 'infoEcritures'
});
</script>
<?php endif; ?>
