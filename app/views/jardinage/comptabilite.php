<?php
$moisNoms = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-calculator', 'text' => 'Comptabilité', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$catLabels = [
    'achat_fournisseur' => 'Achat fournisseur',
    'recolte_miel'      => 'Récolte miel',
    'charge_personnel'  => 'Charge personnel',
    'autre_recette'     => 'Autre recette',
    'autre_depense'     => 'Autre dépense',
];
$typeEspaceLabels = [
    'potager' => 'Potager', 'parterre_fleuri' => 'Parterre fleuri', 'pelouse' => 'Pelouse',
    'haie' => 'Haie', 'arbre_fruitier' => 'Arbre fruitier', 'serre' => 'Serre',
    'verger' => 'Verger', 'rocaille' => 'Rocaille', 'bassin' => 'Bassin',
    'compost' => 'Compost', 'rucher' => 'Rucher', 'autre' => 'Autre'
];
?>

<div class="container-fluid py-4">
    <!-- Filtres -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-calculator me-2 text-success"></i>Comptabilité Jardinage — <?= $annee ?></h2>
        <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
            <select name="residence_id" class="form-select form-select-sm" style="width:auto">
                <option value="0">Toutes résidences</option>
                <?php foreach ($residences as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="annee" class="form-select form-select-sm" style="width:auto">
                <?php for ($y = (int)date('Y'); $y >= (int)date('Y') - 3; $y--): ?>
                <option value="<?= $y ?>" <?= $annee == $y ? 'selected' : '' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <select name="mois" class="form-select form-select-sm" style="width:auto">
                <option value="">Année complète</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $mois == $m ? 'selected' : '' ?>><?= $moisNoms[$m] ?></option>
                <?php endfor; ?>
            </select>
            <button class="btn btn-sm btn-outline-success"><i class="fas fa-filter me-1"></i>Filtrer</button>
            <a href="<?= BASE_URL ?>/jardinage/comptabilite/export?residence_id=<?= (int)$selectedResidence ?>&annee=<?= $annee ?>&mois=<?= $mois ?>" class="btn btn-sm btn-success" title="Export CSV">
                <i class="fas fa-download me-1"></i>Export CSV
            </a>
            <?php if ($selectedResidence): ?>
            <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalEcriture">
                <i class="fas fa-plus me-1"></i>Nouvelle écriture
            </button>
            <?php endif; ?>
        </form>
    </div>

    <!-- KPI Cards -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Recettes TTC</h6>
                    <h3 class="mb-0 text-success"><?= number_format($totaux['recettes_ttc'], 2, ',', ' ') ?> €</h3>
                    <small class="text-muted">HT : <?= number_format($totaux['recettes_ht'], 2, ',', ' ') ?> €</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Dépenses TTC</h6>
                    <h3 class="mb-0 text-danger"><?= number_format($totaux['depenses_ttc'], 2, ',', ' ') ?> €</h3>
                    <small class="text-muted">HT : <?= number_format($totaux['depenses_ht'], 2, ',', ' ') ?> €</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-<?= $totaux['resultat_ttc'] >= 0 ? 'primary' : 'warning' ?> border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Résultat TTC</h6>
                    <h3 class="mb-0 text-<?= $totaux['resultat_ttc'] >= 0 ? 'primary' : 'warning' ?>"><?= number_format($totaux['resultat_ttc'], 2, ',', ' ') ?> €</h3>
                    <small class="text-muted"><?= $totaux['resultat_ttc'] >= 0 ? 'Bénéfice' : 'Perte' ?></small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Écritures</h6>
                    <h3 class="mb-0"><?= (int)$totaux['nb_ecritures'] ?></h3>
                    <small class="text-muted">sur l'année <?= $annee ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphique mensuel + Coût par espace -->
    <div class="row g-4 mb-4">
        <div class="col-lg-7">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Évolution mensuelle <?= $annee ?></h6></div>
                <div class="card-body">
                    <canvas id="chartMensuel" height="220"></canvas>
                </div>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-tree me-2"></i>Coût par espace jardin</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($coutEspaces)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucun espace jardin actif.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Espace</th><th class="text-end">Écritures</th><th class="text-end">Sorties stock</th><th class="text-end">Total</th></tr></thead>
                        <tbody>
                            <?php foreach ($coutEspaces as $e): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($e['espace_nom']) ?></strong><br>
                                    <small class="text-muted"><?= $typeEspaceLabels[$e['espace_type']] ?? $e['espace_type'] ?><?php if (!$selectedResidence): ?> · <?= htmlspecialchars($e['residence_nom']) ?><?php endif; ?></small>
                                </td>
                                <td class="text-end"><?= $e['total_compta'] > 0 ? number_format($e['total_compta'], 2, ',', ' ') . ' €' : '—' ?></td>
                                <td class="text-end"><?= $e['total_sorties'] > 0 ? number_format($e['total_sorties'], 2, ',', ' ') . ' €' : '—' ?></td>
                                <td class="text-end"><strong class="text-<?= $e['total_cout'] > 0 ? 'danger' : 'muted' ?>"><?= number_format($e['total_cout'], 2, ',', ' ') ?> €</strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php if (!empty($coutEspaces)): ?>
                <div class="card-footer small text-muted">
                    <i class="fas fa-info-circle me-1"></i>Coût = écritures imputées + sorties de stock (quantité × prix produit) sur l'année.
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dépenses par fournisseur + Récoltes à comptabiliser -->
    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-truck me-2"></i>Dépenses par fournisseur (commandes livrées/facturées)</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($depensesFournisseurs)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucune commande livrée sur la période.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Fournisseur</th><th class="text-center">Commandes</th><th class="text-end">Total HT</th><th class="text-end">Total TTC</th></tr></thead>
                        <tbody>
                            <?php foreach ($depensesFournisseurs as $f): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($f['nom']) ?></strong></td>
                                <td class="text-center"><span class="badge bg-info"><?= (int)$f['nb_commandes'] ?></span></td>
                                <td class="text-end"><?= number_format($f['total_ht'], 2, ',', ' ') ?> €</td>
                                <td class="text-end"><strong><?= number_format($f['total_ttc'], 2, ',', ' ') ?> €</strong></td>
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
                <div class="card-header bg-warning text-dark"><h6 class="mb-0">🍯 Récoltes de miel à comptabiliser <?= !empty($recoltesNonCo) ? '(' . count($recoltesNonCo) . ')' : '' ?></h6></div>
                <div class="card-body p-0">
                    <?php if (empty($recoltesNonCo)): ?>
                    <p class="text-center text-muted p-4 mb-0">
                        <i class="fas fa-check-circle me-1"></i>Toutes les récoltes <?= $annee ?> sont comptabilisées.
                    </p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light"><tr><th>Date</th><th>Ruche</th><th class="text-end">Kg</th><th style="width:120px">Prix €/kg</th><th style="width:80px"></th></tr></thead>
                        <tbody>
                            <?php foreach ($recoltesNonCo as $r): ?>
                            <tr>
                                <form method="POST" action="<?= BASE_URL ?>/jardinage/comptabilite/recolte">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                    <input type="hidden" name="visite_id" value="<?= (int)$r['visite_id'] ?>">
                                    <input type="hidden" name="residence_id" value="<?= (int)$r['residence_id'] ?>">
                                    <input type="hidden" name="ruche_numero" value="<?= htmlspecialchars($r['ruche_numero']) ?>">
                                    <input type="hidden" name="date_ecriture" value="<?= htmlspecialchars($r['date_visite']) ?>">
                                    <input type="hidden" name="quantite_kg" value="<?= (float)$r['quantite_miel_kg'] ?>">
                                    <input type="hidden" name="annee" value="<?= $annee ?>">
                                    <td class="small"><?= date('d/m/Y', strtotime($r['date_visite'])) ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($r['ruche_numero']) ?></strong>
                                        <?php if (!$selectedResidence): ?><br><small class="text-muted"><?= htmlspecialchars($r['residence_nom']) ?></small><?php endif; ?>
                                    </td>
                                    <td class="text-end"><strong><?= number_format($r['quantite_miel_kg'], 1, ',', ' ') ?></strong></td>
                                    <td><input type="number" step="0.01" min="0.01" name="prix_kg" class="form-control form-control-sm" placeholder="10.00" required></td>
                                    <td><button type="submit" class="btn btn-sm btn-warning" title="Comptabiliser"><i class="fas fa-check"></i></button></td>
                                </form>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Liste des écritures -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Écritures (<?= count($ecritures) ?>)</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:240px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="ecrituresTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Libellé</th>
                        <th>Type</th>
                        <th>Catégorie</th>
                        <th>Espace</th>
                        <th class="text-end">Montant HT</th>
                        <th class="text-end">TVA</th>
                        <th class="text-end">Montant TTC</th>
                        <th class="no-sort text-end" style="width:80px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ecritures as $e): ?>
                    <tr>
                        <td class="small" data-sort="<?= strtotime($e['date_ecriture']) ?>"><?= date('d/m/Y', strtotime($e['date_ecriture'])) ?></td>
                        <td>
                            <?= htmlspecialchars($e['libelle']) ?>
                            <?php if (!$selectedResidence): ?><br><small class="text-muted"><?= htmlspecialchars($e['residence_nom']) ?></small><?php endif; ?>
                        </td>
                        <td><span class="badge bg-<?= $e['type_ecriture'] === 'recette' ? 'success' : 'danger' ?>"><?= $e['type_ecriture'] === 'recette' ? 'Recette' : 'Dépense' ?></span></td>
                        <td><small><?= $catLabels[$e['categorie']] ?? $e['categorie'] ?></small></td>
                        <td class="small"><?= $e['espace_nom'] ? htmlspecialchars($e['espace_nom']) : '—' ?></td>
                        <td class="text-end" data-sort="<?= (float)$e['montant_ht'] ?>"><?= number_format($e['montant_ht'], 2, ',', ' ') ?> €</td>
                        <td class="text-end" data-sort="<?= (float)$e['montant_tva'] ?>"><?= number_format($e['montant_tva'], 2, ',', ' ') ?> €</td>
                        <td class="text-end" data-sort="<?= (float)$e['montant_ttc'] ?>"><strong class="text-<?= $e['type_ecriture'] === 'recette' ? 'success' : 'danger' ?>"><?= number_format($e['montant_ttc'], 2, ',', ' ') ?> €</strong></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/jardinage/comptabilite/delete/<?= (int)$e['id'] ?>?residence_id=<?= (int)$selectedResidence ?>&annee=<?= $annee ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Supprimer cette écriture ?')" title="Supprimer"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ecritures)): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Aucune écriture pour la période sélectionnée.</td></tr>
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

<?php if ($selectedResidence): ?>
<!-- Modal nouvelle écriture -->
<div class="modal fade" id="modalEcriture" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/comptabilite/ecriture">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouvelle écriture</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">
                    <input type="hidden" name="annee" value="<?= $annee ?>">

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_ecriture" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type_ecriture" class="form-select" required onchange="toggleCategories(this.value)">
                                <option value="depense" selected>Dépense</option>
                                <option value="recette">Recette</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select name="categorie" id="fieldCategorie" class="form-select" required>
                                <option value="achat_fournisseur">Achat fournisseur</option>
                                <option value="charge_personnel">Charge personnel</option>
                                <option value="autre_depense">Autre dépense</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Libellé <span class="text-danger">*</span></label>
                            <input type="text" name="libelle" class="form-control" maxlength="255" required placeholder="Ex : Sac de terreau 40L, facture fournisseur X">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Espace imputé <small class="text-muted">(optionnel)</small></label>
                            <select name="espace_id" class="form-select">
                                <option value="">— Non imputé —</option>
                                <?php foreach (($espacesResidence ?? []) as $esp): ?>
                                <option value="<?= $esp['id'] ?>"><?= htmlspecialchars($esp['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Montant HT <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="montant_ht" id="fieldHt" class="form-control" required oninput="recalcTtc()">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">TVA</label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" name="montant_tva" id="fieldTva" class="form-control" value="0" oninput="recalcTtc()">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Compte comptable</label>
                            <input type="text" name="compte_comptable" class="form-control" maxlength="20" placeholder="606, 411, …">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Montant TTC (calculé)</label>
                            <div class="input-group">
                                <input type="text" id="fieldTtc" class="form-control bg-light" readonly value="0.00">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function toggleCategories(type) {
    const sel = document.getElementById('fieldCategorie');
    sel.innerHTML = '';
    const opts = type === 'recette'
        ? [['recolte_miel', 'Récolte miel'], ['autre_recette', 'Autre recette']]
        : [['achat_fournisseur', 'Achat fournisseur'], ['charge_personnel', 'Charge personnel'], ['autre_depense', 'Autre dépense']];
    opts.forEach(([v, l]) => { const o = document.createElement('option'); o.value = v; o.textContent = l; sel.appendChild(o); });
}
function recalcTtc() {
    const ht = parseFloat(document.getElementById('fieldHt').value) || 0;
    const tva = parseFloat(document.getElementById('fieldTva').value) || 0;
    document.getElementById('fieldTtc').value = (ht + tva).toFixed(2);
}
</script>
<?php endif; ?>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartMensuel'), {
    type: 'bar',
    data: {
        labels: <?= $moisLabels ?>,
        datasets: [
            { label: 'Recettes TTC', data: <?= $recettesData ?>, backgroundColor: 'rgba(25, 135, 84, 0.7)' },
            { label: 'Dépenses TTC', data: <?= $depensesData ?>, backgroundColor: 'rgba(220, 53, 69, 0.7)' }
        ]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'top' } },
        scales: { y: { beginAtZero: true, ticks: { callback: v => v.toLocaleString('fr-FR') + ' €' } } }
    }
});
</script>

<?php if (!empty($ecritures)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('ecrituresTable', {
    rowsPerPage: 25,
    searchInputId: 'searchInput',
    excludeColumns: [8],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
