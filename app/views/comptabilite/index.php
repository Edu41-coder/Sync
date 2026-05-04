<?php
$moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator', 'text' => 'Comptabilité', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

// Helper : flèche / pourcentage variation N vs N-1
$variation = function (float $n, float $nm1): array {
    if ($nm1 == 0.0) return ['pct' => null, 'icon' => 'fa-minus', 'color' => 'muted'];
    $pct = (($n - $nm1) / abs($nm1)) * 100;
    return [
        'pct'   => $pct,
        'icon'  => $pct > 0 ? 'fa-arrow-up' : ($pct < 0 ? 'fa-arrow-down' : 'fa-minus'),
        'color' => $pct > 0 ? 'success' : ($pct < 0 ? 'danger' : 'muted'),
    ];
};

// Groupes prédéfinis pour le multi-modules
$groupes = [
    'Recettes'     => ['loyer_proprio', 'loyer_resident', 'services', 'restauration', 'menage', 'jardinage', 'hote'],
    'Dépenses'     => ['admin', 'sinistre'],
    'Personnel'    => ['rh_paie'],
    'Maintenance'  => ['maintenance'],
];
?>

<div class="container-fluid py-4">

    <!-- Header + filtres -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-calculator me-2 text-primary"></i>Tableau de bord comptable — <?= $annee ?></h2>
        <a href="<?= BASE_URL ?>/comptabilite/ecritures?annee=<?= $annee ?>&residence_id=<?= (int)$selectedResidence ?>" class="btn btn-outline-primary">
            <i class="fas fa-list me-1"></i>Toutes les écritures
        </a>
    </div>

    <form method="GET" class="card shadow-sm mb-4">
        <div class="card-body py-3">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes résidences accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
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
                <div class="col-md-3">
                    <label class="form-label small mb-1">Filtres rapides</label>
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <?php foreach ($groupes as $label => $modCodes):
                            $checkedAll = !array_diff($modCodes, $modulesFilter);
                        ?>
                        <button type="button" class="btn <?= $checkedAll ? 'btn-primary' : 'btn-outline-primary' ?>" onclick="toggleGroupe(<?= htmlspecialchars(json_encode($modCodes), ENT_QUOTES) ?>, this)">
                            <?= htmlspecialchars($label) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Appliquer</button>
                </div>
            </div>

            <!-- Multi-modules cases à cocher -->
            <div class="row mt-3">
                <div class="col-12">
                    <small class="text-muted">Modules inclus :</small>
                    <div class="d-flex flex-wrap gap-2 mt-1">
                        <?php foreach ($modulesAll as $code => $label): ?>
                        <label class="badge bg-<?= in_array($code, $modulesFilter, true) ? ($moduleColors[$code] ?? 'secondary') : 'light text-dark border' ?>" style="cursor:pointer; padding:0.5rem 0.75rem">
                            <input type="checkbox" name="modules[]" value="<?= $code ?>" class="d-none mod-checkbox" <?= in_array($code, $modulesFilter, true) ? 'checked' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Actions à mener (Phase 12) -->
    <?php
    $totalTodos = ($todos['bulletins_brouillon'] ?? 0)
                + ($todos['tva_brouillon'] ?? 0)
                + ($todos['bank_non_rapp'] ?? 0)
                + ($todos['rh_manquantes'] ?? 0);
    if ($totalTodos > 0):
    ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark">
            <i class="fas fa-bell me-2"></i><strong><?= $totalTodos ?> action(s) à mener</strong>
            <small class="ms-2">— Pilotage opérationnel comptable</small>
        </div>
        <div class="card-body py-3">
            <div class="row g-2">
                <?php if (!empty($todos['bulletins_brouillon'])): ?>
                <div class="col-md-3 col-sm-6">
                    <a href="<?= BASE_URL ?>/bulletinPaie/index?statut=brouillon" class="text-decoration-none">
                        <div class="card border-success text-center py-2 h-100 hover-shadow">
                            <div class="fs-3 fw-bold text-success"><?= (int)$todos['bulletins_brouillon'] ?></div>
                            <div class="small text-muted">Bulletin(s) en brouillon</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($todos['tva_brouillon'])): ?>
                <div class="col-md-3 col-sm-6">
                    <a href="<?= BASE_URL ?>/comptabilite/tva?statut=brouillon" class="text-decoration-none">
                        <div class="card border-warning text-center py-2 h-100 hover-shadow">
                            <div class="fs-3 fw-bold text-warning"><?= (int)$todos['tva_brouillon'] ?></div>
                            <div class="small text-muted">Déclaration(s) TVA à transmettre</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($todos['bank_non_rapp'])): ?>
                <div class="col-md-3 col-sm-6">
                    <a href="<?= BASE_URL ?>/comptabilite/rapprochement" class="text-decoration-none">
                        <div class="card border-info text-center py-2 h-100 hover-shadow">
                            <div class="fs-3 fw-bold text-info"><?= (int)$todos['bank_non_rapp'] ?></div>
                            <div class="small text-muted">Opération(s) bancaire(s) à rapprocher</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
                <?php if (!empty($todos['rh_manquantes'])): ?>
                <div class="col-md-3 col-sm-6">
                    <a href="<?= BASE_URL ?>/salarie/index" class="text-decoration-none">
                        <div class="card border-danger text-center py-2 h-100 hover-shadow">
                            <div class="fs-3 fw-bold text-danger"><?= (int)$todos['rh_manquantes'] ?></div>
                            <div class="small text-muted">Salarié(s) sans fiche RH</div>
                        </div>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php if (!empty($todos['ecritures_tbd'])): ?>
            <div class="alert alert-secondary py-2 small mb-0 mt-2">
                <i class="fas fa-info-circle me-1"></i>
                <strong><?= (int)$todos['ecritures_tbd'] ?></strong> écriture(s) sans compte comptable affecté
                — affecter manuellement pour qu'elles apparaissent au Bilan / SIG.
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 6 KPIs + comparaison N-1 -->
    <div class="row g-3 mb-4">
        <?php
        $kpis = [
            ['label' => 'Recettes TTC',     'value' => $totaux['recettes_ttc'], 'nm1' => $totauxNm1['recettes_ttc'], 'color' => 'success', 'icon' => 'arrow-down'],
            ['label' => 'Dépenses TTC',     'value' => $totaux['depenses_ttc'], 'nm1' => $totauxNm1['depenses_ttc'], 'color' => 'danger',  'icon' => 'arrow-up'],
            ['label' => 'Résultat TTC',     'value' => $totaux['resultat'],     'nm1' => $totauxNm1['resultat'],     'color' => $totaux['resultat'] >= 0 ? 'primary' : 'warning', 'icon' => 'balance-scale'],
            ['label' => 'TVA collectée',    'value' => $tva['collectee'],       'nm1' => $tvaNm1['collectee'],       'color' => 'info',    'icon' => 'percentage'],
            ['label' => 'TVA déductible',   'value' => $tva['deductible'],      'nm1' => $tvaNm1['deductible'],      'color' => 'secondary','icon' => 'percentage'],
            ['label' => 'TVA à reverser',   'value' => $tva['a_reverser'],      'nm1' => $tvaNm1['a_reverser'],      'color' => 'dark',    'icon' => 'file-invoice-dollar'],
        ];
        foreach ($kpis as $k):
            $v = $variation((float)$k['value'], (float)$k['nm1']);
        ?>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-<?= $k['color'] ?> border-4 text-center py-3 h-100">
                <div class="text-muted small text-uppercase"><i class="fas fa-<?= $k['icon'] ?> me-1"></i><?= $k['label'] ?></div>
                <h4 class="mb-1 text-<?= $k['color'] ?>"><?= number_format((float)$k['value'], 0, ',', ' ') ?> &euro;</h4>
                <?php if ($v['pct'] !== null): ?>
                <small class="text-<?= $v['color'] ?>"><i class="fas <?= $v['icon'] ?> me-1"></i><?= number_format(abs($v['pct']), 1, ',', ' ') ?>% vs <?= $annee - 1 ?></small>
                <?php else: ?>
                <small class="text-muted">— vs <?= $annee - 1 ?></small>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Section Maintenance (parallèle, pas dans ecritures_comptables) -->
    <?php if (in_array('maintenance', $modulesFilter, true)): ?>
    <div class="card shadow-sm mb-4 border-warning">
        <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-wrench me-2"></i>Maintenance Technique <small class="text-muted">(agrégation séparée — interventions / chantiers / ascenseurs)</small></h6>
            <a href="<?= BASE_URL ?>/maintenance/comptabilite?annee=<?= $annee ?>" class="btn btn-sm btn-outline-dark">Voir détail →</a>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-6 col-lg-3">
                    <div class="text-muted small text-uppercase">Coût total</div>
                    <h4 class="mb-0 text-warning"><?= number_format($mainStats['total'], 0, ',', ' ') ?> &euro;</h4>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="text-muted small text-uppercase">Interventions</div>
                    <h4 class="mb-0"><?= number_format($mainStats['interventions'], 0, ',', ' ') ?> &euro;</h4>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="text-muted small text-uppercase">Chantiers payés</div>
                    <h4 class="mb-0"><?= number_format($mainStats['chantiers'], 0, ',', ' ') ?> &euro;</h4>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="text-muted small text-uppercase">Ascenseurs</div>
                    <h4 class="mb-0"><?= number_format($mainStats['ascenseurs'], 0, ',', ' ') ?> &euro;</h4>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accès rapides Phase 12 -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
            <i class="fas fa-rocket me-2 text-primary"></i><strong>Accès rapides</strong>
        </div>
        <div class="card-body">
            <div class="row g-3 text-center">
                <?php
                $shortcuts = [
                    ['url' => '/comptabilite/assistant',     'icon' => 'fa-robot',          'label' => 'Assistant IA',      'color' => 'primary',   'sub' => 'Analyse comptable'],
                    ['url' => '/bulletinPaie/assistant',     'icon' => 'fa-robot',          'label' => 'Assistant Paie IA', 'color' => 'success',   'sub' => 'RH & cotisations'],
                    ['url' => '/comptabilite/tva',           'icon' => 'fa-percent',        'label' => 'Déclarations TVA',  'color' => 'warning',   'sub' => 'CA3 / CA12'],
                    ['url' => '/comptabilite/bilan',         'icon' => 'fa-balance-scale',  'label' => 'Bilan & SIG',       'color' => 'primary',   'sub' => 'États financiers'],
                    ['url' => '/comptabilite/rapprochement', 'icon' => 'fa-university',     'label' => 'Rapprochement',     'color' => 'info',      'sub' => 'Bancaire'],
                    ['url' => '/comptabilite/export',        'icon' => 'fa-file-export',    'label' => 'Exports',           'color' => 'danger',    'sub' => 'FEC / CSV / Cegid'],
                    ['url' => '/comptabilite/exercices',     'icon' => 'fa-calendar-alt',   'label' => 'Exercices',         'color' => 'warning',   'sub' => 'Clôture annuelle'],
                    ['url' => '/comptabilite/auditTrail',    'icon' => 'fa-history',        'label' => 'Audit trail',       'color' => 'secondary', 'sub' => 'Traçabilité légale'],
                ];
                foreach ($shortcuts as $s):
                ?>
                <div class="col-md-3 col-sm-6">
                    <a href="<?= BASE_URL . htmlspecialchars($s['url']) ?>" class="text-decoration-none">
                        <div class="card border-<?= $s['color'] ?> h-100 py-3 hover-shadow">
                            <i class="fas <?= $s['icon'] ?> fa-2x text-<?= $s['color'] ?> mb-2"></i>
                            <strong class="text-<?= $s['color'] ?>"><?= htmlspecialchars($s['label']) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars($s['sub']) ?></small>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Visualisations -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-line me-2"></i>Évolution mensuelle <?= $annee ?> vs <?= $annee - 1 ?></h6></div>
                <div class="card-body"><canvas id="chartMensuel" height="100"></canvas></div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Ventilation par module</h6></div>
                <div class="card-body"><canvas id="chartModules" height="200"></canvas></div>
            </div>
        </div>
    </div>

    <!-- Ventilation par résidence + dernières écritures -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-building me-2"></i>Top résidences (volume)</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($parResidence)): ?>
                    <p class="text-center text-muted py-4 mb-0">Aucune écriture sur la période.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Résidence</th><th class="text-end">Recettes</th><th class="text-end">Dépenses</th><th class="text-end">Solde</th></tr></thead>
                        <tbody>
                            <?php foreach (array_slice($parResidence, 0, 8) as $r):
                                $solde = (float)$r['recettes'] - (float)$r['depenses'];
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($r['residence_nom']) ?></td>
                                <td class="text-end text-success"><?= number_format((float)$r['recettes'], 0, ',', ' ') ?> &euro;</td>
                                <td class="text-end text-danger"><?= number_format((float)$r['depenses'], 0, ',', ' ') ?> &euro;</td>
                                <td class="text-end"><strong class="text-<?= $solde >= 0 ? 'primary' : 'warning' ?>"><?= number_format($solde, 0, ',', ' ') ?> &euro;</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-clock me-2"></i>10 dernières écritures</h6>
                    <a href="<?= BASE_URL ?>/comptabilite/ecritures?annee=<?= $annee ?>" class="btn btn-sm btn-outline-primary">Voir tout</a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($dernieresEcritures)): ?>
                    <p class="text-center text-muted py-4 mb-0">Aucune écriture sur la période.</p>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Module</th><th>Libellé</th><th class="text-end">TTC</th></tr></thead>
                        <tbody>
                            <?php foreach ($dernieresEcritures as $e):
                                $col = $moduleColors[$e['module_source']] ?? 'secondary';
                            ?>
                            <tr>
                                <td class="small"><?= date('d/m', strtotime($e['date_ecriture'])) ?></td>
                                <td><span class="badge bg-<?= $col ?>"><?= htmlspecialchars($modulesAll[$e['module_source']] ?? $e['module_source']) ?></span></td>
                                <td class="small"><?= htmlspecialchars(mb_strimwidth($e['libelle'], 0, 60, '…')) ?></td>
                                <td class="text-end"><strong class="text-<?= $e['type_ecriture'] === 'recette' ? 'success' : 'danger' ?>"><?= ($e['type_ecriture'] === 'depense' ? '-' : '+') ?><?= number_format((float)$e['montant_ttc'], 2, ',', ' ') ?> &euro;</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
// Filtres groupes : toggle d'un ensemble de modules
function toggleGroupe(codes, btn) {
    const checkboxes = document.querySelectorAll('.mod-checkbox');
    const allChecked = codes.every(c => Array.from(checkboxes).find(cb => cb.value === c)?.checked);
    codes.forEach(c => {
        const cb = Array.from(checkboxes).find(x => x.value === c);
        if (cb) {
            cb.checked = !allChecked;
            cb.closest('label').className = cb.checked
                ? 'badge bg-' + (cb.dataset.color || 'primary') + ' p-2'
                : 'badge bg-light text-dark border p-2';
        }
    });
    btn.classList.toggle('btn-primary');
    btn.classList.toggle('btn-outline-primary');
}

// Sync visuel des badges checkbox
document.querySelectorAll('.mod-checkbox').forEach(cb => {
    cb.addEventListener('change', () => {
        const lbl = cb.closest('label');
        lbl.className = cb.checked
            ? 'badge bg-primary'
            : 'badge bg-light text-dark border';
        lbl.style.padding = '0.5rem 0.75rem';
        lbl.style.cursor = 'pointer';
    });
});

// Évolution mensuelle (lines N et N-1)
new Chart(document.getElementById('chartMensuel'), {
    type: 'line',
    data: {
        labels: <?= $chartLabels ?>,
        datasets: [
            { label: 'Recettes <?= $annee ?>', data: <?= $chartRecettes ?>, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.1)', tension: 0.3, fill: true },
            { label: 'Dépenses <?= $annee ?>', data: <?= $chartDepenses ?>, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.1)', tension: 0.3, fill: true },
            { label: 'Recettes <?= $annee - 1 ?>', data: <?= $chartRecNm1 ?>, borderColor: '#198754', borderDash: [5,5], borderWidth: 1, fill: false, pointRadius: 2 },
            { label: 'Dépenses <?= $annee - 1 ?>', data: <?= $chartDepNm1 ?>, borderColor: '#dc3545', borderDash: [5,5], borderWidth: 1, fill: false, pointRadius: 2 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-FR') + ' €' } } }
    }
});

// Camembert ventilation par module
const ventModules = <?= json_encode(array_map(fn($v) => [
    'module' => $v['module_source'],
    'label'  => $modulesAll[$v['module_source']] ?? $v['module_source'],
    'total'  => (float)$v['recettes'] + (float)$v['depenses'],
], $parModule)) ?>;
const moduleColorsHex = {
    success: '#198754', info: '#0dcaf0', warning: '#ffc107', orange: '#fd7e14',
    primary: '#0d6efd', teal: '#20c997', secondary: '#6c757d', danger: '#dc3545',
    dark: '#212529', red: '#b02a37', muted: '#adb5bd'
};
const colorsMap = <?= json_encode($moduleColors) ?>;
new Chart(document.getElementById('chartModules'), {
    type: 'doughnut',
    data: {
        labels: ventModules.map(v => v.label),
        datasets: [{
            data: ventModules.map(v => v.total),
            backgroundColor: ventModules.map(v => moduleColorsHex[colorsMap[v.module] || 'secondary'] || '#6c757d')
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { boxWidth: 12 } },
            tooltip: { callbacks: { label: ctx => ctx.label + ' : ' + ctx.parsed.toLocaleString('fr-FR') + ' €' } }
        }
    }
});
</script>
