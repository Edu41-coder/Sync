<?php $moisNoms = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-calculator', 'text' => 'Comptabilité', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <!-- Filtres -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-calculator me-2 text-success"></i>Comptabilité Restauration — <?= $annee ?></h2>
        <form method="GET" class="d-flex gap-2 align-items-center">
            <select name="residence_id" class="form-select form-select-sm" style="width:auto">
                <option value="0">Toutes résidences</option>
                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
            </select>
            <select name="annee" class="form-select form-select-sm" style="width:auto">
                <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?><option value="<?= $y ?>" <?= $annee==$y?'selected':'' ?>><?= $y ?></option><?php endfor; ?>
            </select>
            <select name="mois" class="form-select form-select-sm" style="width:auto">
                <option value="">Année complète</option>
                <?php for ($m = 1; $m <= 12; $m++): ?><option value="<?= $m ?>" <?= $mois==$m?'selected':'' ?>><?= $moisNoms[$m] ?></option><?php endfor; ?>
            </select>
            <button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrer</button>
            <a href="<?= BASE_URL ?>/restauration/comptabilite/export?residence_id=<?= $selectedResidence ?>&annee=<?= $annee ?>&mois=<?= $mois ?>" class="btn btn-sm btn-outline-success" title="Export CSV"><i class="fas fa-download"></i></a>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-success border-4 text-center py-3">
                <h4 class="mb-0 text-success"><?= number_format($totaux['recettes_ttc'], 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">Recettes TTC</small>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-danger border-4 text-center py-3">
                <h4 class="mb-0 text-danger"><?= number_format($totaux['depenses_ttc'], 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">Dépenses TTC</small>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-<?= $totaux['resultat_ttc'] >= 0 ? 'primary' : 'warning' ?> border-4 text-center py-3">
                <h4 class="mb-0 text-<?= $totaux['resultat_ttc'] >= 0 ? 'primary' : 'warning' ?>"><?= number_format($totaux['resultat_ttc'], 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">Résultat TTC</small>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-info border-4 text-center py-3">
                <h4 class="mb-0 text-info"><?= number_format((float)($tva['collectee'] ?? 0), 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">TVA collectée</small>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm text-center py-3">
                <h4 class="mb-0"><?= number_format((float)($tva['deductible'] ?? 0), 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">TVA déductible</small>
            </div>
        </div>
        <div class="col-6 col-lg-2">
            <div class="card shadow-sm border-start border-dark border-4 text-center py-3">
                <h4 class="mb-0"><?= number_format((float)($tva['a_reverser'] ?? 0), 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">TVA à reverser</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Graphique mensuel -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Évolution mensuelle <?= $annee ?></h6></div>
                <div class="card-body">
                    <canvas id="chartMensuel" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Répartition par catégorie -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-arrow-down me-2"></i>Recettes par catégorie</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($recettesCat)): ?><p class="text-muted text-center py-3">Aucune recette</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <?php foreach ($recettesCat as $c): ?>
                        <tr><td><?= ucfirst(str_replace('_',' ',$c['categorie'])) ?></td><td class="text-end text-success"><strong><?= number_format($c['total_ttc'],0,',',' ') ?> &euro;</strong></td></tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card shadow-sm">
                <div class="card-header bg-danger text-white"><h6 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Dépenses par catégorie</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($depensesCat)): ?><p class="text-muted text-center py-3">Aucune dépense</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <?php foreach ($depensesCat as $c): ?>
                        <tr><td><?= ucfirst(str_replace('_',' ',$c['categorie'])) ?></td><td class="text-end text-danger"><strong><?= number_format($c['total_ttc'],0,',',' ') ?> &euro;</strong></td></tr>
                        <?php endforeach; ?>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Dépenses par fournisseur -->
    <?php if (!empty($depensesFournisseurs)): ?>
    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-truck me-2 text-danger"></i>Dépenses par fournisseur <?= $annee ?><?= $mois ? ' — ' . $moisNoms[$mois] : '' ?></h6>
                    <a href="<?= BASE_URL ?>/restauration/fournisseurs<?= $selectedResidence ? '?residence_id='.$selectedResidence : '' ?>" class="btn btn-sm btn-outline-warning">Voir fournisseurs</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>Fournisseur</th><th class="text-center">Commandes</th><th class="text-end">Total HT</th><th class="text-end">TVA</th><th class="text-end">Total TTC</th><th style="width:150px">Part</th></tr></thead>
                        <tbody>
                            <?php
                            $totalDepensesFourn = array_sum(array_column($depensesFournisseurs, 'total_ttc'));
                            foreach ($depensesFournisseurs as $df):
                                $pct = $totalDepensesFourn > 0 ? round(($df['total_ttc'] / $totalDepensesFourn) * 100, 1) : 0;
                            ?>
                            <tr>
                                <td>
                                    <a href="<?= BASE_URL ?>/restauration/fournisseurs/show/<?= $df['fournisseur_id'] ?>" class="text-decoration-none">
                                        <strong><?= htmlspecialchars($df['fournisseur_nom']) ?></strong>
                                    </a>
                                </td>
                                <td class="text-center"><span class="badge bg-primary"><?= $df['nb_commandes'] ?></span></td>
                                <td class="text-end"><?= number_format((float)$df['total_ht'], 2, ',', ' ') ?> &euro;</td>
                                <td class="text-end text-muted"><?= number_format((float)$df['total_tva'], 2, ',', ' ') ?> &euro;</td>
                                <td class="text-end"><strong class="text-danger"><?= number_format((float)$df['total_ttc'], 2, ',', ' ') ?> &euro;</strong></td>
                                <td>
                                    <div class="progress" style="height:18px">
                                        <div class="progress-bar bg-danger" style="width:<?= $pct ?>%"><?= $pct ?>%</div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="table-dark">
                                <td><strong>Total</strong></td>
                                <td class="text-center"><?= array_sum(array_column($depensesFournisseurs, 'nb_commandes')) ?></td>
                                <td class="text-end"><?= number_format((float)array_sum(array_column($depensesFournisseurs, 'total_ht')), 2, ',', ' ') ?> &euro;</td>
                                <td class="text-end"><?= number_format((float)array_sum(array_column($depensesFournisseurs, 'total_tva')), 2, ',', ' ') ?> &euro;</td>
                                <td class="text-end"><strong><?= number_format((float)$totalDepensesFourn, 2, ',', ' ') ?> &euro;</strong></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Formulaire nouvelle écriture -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nouvelle écriture</h6></div>
                <form method="POST" action="<?= BASE_URL ?>/restauration/comptabilite/ecriture">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="annee" value="<?= $annee ?>">
                    <div class="card-body">
                        <div class="mb-2"><label class="form-label small">Résidence</label>
                            <select name="residence_id" class="form-select form-select-sm" required>
                                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
                            </select></div>
                        <div class="mb-2"><label class="form-label small">Date</label><input type="date" name="date_ecriture" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label small">Type</label>
                                <select name="type_ecriture" class="form-select form-select-sm" required>
                                    <option value="recette">Recette</option><option value="depense">Dépense</option>
                                </select></div>
                            <div class="col-6"><label class="form-label small">Catégorie</label>
                                <select name="categorie" class="form-select form-select-sm" required>
                                    <option value="repas_residents">Repas résidents</option>
                                    <option value="repas_hotes">Repas hôtes</option>
                                    <option value="repas_passages">Repas passages</option>
                                    <option value="achat_fournisseur">Achat fournisseur</option>
                                    <option value="charge_personnel">Charge personnel</option>
                                    <option value="autre">Autre</option>
                                </select></div>
                        </div>
                        <div class="mb-2"><label class="form-label small">Libellé</label><input type="text" name="libelle" class="form-control form-control-sm" required></div>
                        <div class="row g-2 mb-2">
                            <div class="col-6"><label class="form-label small">Montant HT</label><input type="number" name="montant_ht" class="form-control form-control-sm" step="0.01" min="0" required></div>
                            <div class="col-6"><label class="form-label small">TVA %</label><input type="number" name="taux_tva" class="form-control form-control-sm" value="10" step="0.01" min="0"></div>
                        </div>
                        <div class="mb-2"><label class="form-label small">Compte comptable</label><input type="text" name="compte_comptable" class="form-control form-control-sm" placeholder="706100, 601100..."></div>
                        <div class="mb-2"><label class="form-label small">Notes</label><input type="text" name="notes" class="form-control form-control-sm"></div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-warning btn-sm w-100"><i class="fas fa-save me-2"></i>Enregistrer</button></div>
                </form>
            </div>
        </div>

        <!-- Journal des écritures -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="mb-0"><i class="fas fa-book me-2"></i>Journal des écritures (<?= count($ecritures) ?>)</h6>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0" id="journalTable">
                        <thead><tr><th>Date</th><th>Libellé</th><th>Catégorie</th><th>Compte</th><th class="text-end">HT</th><th class="text-end">TVA</th><th class="text-end">TTC</th></tr></thead>
                        <tbody>
                        <?php if (empty($ecritures)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">Aucune écriture.</td></tr>
                        <?php else: foreach ($ecritures as $e): ?>
                        <tr>
                            <td><?= date('d/m', strtotime($e['date_ecriture'])) ?></td>
                            <td>
                                <span class="badge bg-<?= $e['type_ecriture']==='recette'?'success':'danger' ?> me-1" style="font-size:0.6rem"><?= $e['type_ecriture']==='recette'?'R':'D' ?></span>
                                <?= htmlspecialchars($e['libelle']) ?>
                            </td>
                            <td><small><?= ucfirst(str_replace('_',' ',$e['categorie'])) ?></small></td>
                            <td><code class="small"><?= $e['compte_comptable'] ?? '-' ?></code></td>
                            <td class="text-end"><?= number_format($e['montant_ht'],2,',',' ') ?></td>
                            <td class="text-end text-muted"><?= number_format($e['montant_tva'],2,',',' ') ?></td>
                            <td class="text-end"><strong class="text-<?= $e['type_ecriture']==='recette'?'success':'danger' ?>"><?= number_format($e['montant_ttc'],2,',',' ') ?> &euro;</strong></td>
                        </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="tableInfoJournal"></div>
                    <nav><ul class="pagination pagination-sm mb-0" id="paginationJournal"></ul></nav>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartMensuel'), {
    type: 'bar',
    data: {
        labels: <?= $moisLabels ?>,
        datasets: [
            { label: 'Recettes TTC', data: <?= $recettesData ?>, backgroundColor: 'rgba(25,135,84,0.7)', borderColor: '#198754', borderWidth: 1 },
            { label: 'Dépenses TTC', data: <?= $depensesData ?>, backgroundColor: 'rgba(220,53,69,0.7)', borderColor: '#dc3545', borderWidth: 1 }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top', labels: { color: '#ccc' } } },
        scales: {
            y: { beginAtZero: true, ticks: { color: '#999', callback: v => v.toLocaleString('fr-FR') + ' €' }, grid: { color: 'rgba(255,255,255,0.05)' } },
            x: { ticks: { color: '#999' }, grid: { display: false } }
        }
    }
});
</script>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('journalTable', { rowsPerPage: 25, searchInputId: 'searchInput', paginationId: 'paginationJournal', infoId: 'tableInfoJournal' });</script>
