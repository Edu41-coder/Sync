<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-percent',        'text' => 'TVA',             'url' => BASE_URL . '/comptabilite/tva'],
    ['icon' => 'fas fa-cogs',           'text' => 'Calcul',          'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-cogs me-2 text-primary"></i>Calcul TVA — préparation d'une déclaration</h2>

    <form method="POST" action="<?= BASE_URL ?>/comptabilite/tvaCalculer" class="card shadow-sm mb-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-cogs me-2"></i>Paramètres de la période
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Résidence <span class="text-danger">*</span></label>
                    <select name="residence_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)$params['residence_id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold">Régime</label>
                    <select name="regime" id="regimeSelect" class="form-select" required>
                        <?php foreach ($regimes as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $params['regime'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold">Année</label>
                    <select name="annee" class="form-select">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$params['annee'] ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2" id="moisGroup">
                    <label class="form-label small fw-bold">Mois</label>
                    <select name="mois" class="form-select">
                        <?php $moisLabels = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
                        foreach ($moisLabels as $i => $lbl): ?>
                        <option value="<?= $i + 1 ?>" <?= ($i + 1) === (int)$params['mois'] ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-none" id="trimGroup">
                    <label class="form-label small fw-bold">Trimestre</label>
                    <select name="trimestre" class="form-select">
                        <option value="1" <?= (int)$params['trimestre'] === 1 ? 'selected' : '' ?>>T1 (jan-mar)</option>
                        <option value="2" <?= (int)$params['trimestre'] === 2 ? 'selected' : '' ?>>T2 (avr-jun)</option>
                        <option value="3" <?= (int)$params['trimestre'] === 3 ? 'selected' : '' ?>>T3 (jui-sep)</option>
                        <option value="4" <?= (int)$params['trimestre'] === 4 ? 'selected' : '' ?>>T4 (oct-déc)</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary"><i class="fas fa-calculator me-1"></i>Calculer</button>
        </div>
    </form>

    <?php if ($calcul): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
            <span><i class="fas fa-file-invoice me-2"></i>Brouillon TVA — <?= htmlspecialchars($calcul['residence_nom']) ?> — <?= htmlspecialchars($calcul['libelle_periode']) ?></span>
            <span class="badge bg-light text-dark">
                <?= (int)$calcul['nb_ecritures'] ?> écriture<?= $calcul['nb_ecritures'] > 1 ? 's' : '' ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <!-- KPI 1 : TVA collectée -->
                <div class="col-md-4">
                    <div class="card border-warning h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">TVA collectée</div>
                            <div class="h3 mb-0 text-warning"><?= number_format($calcul['tva_collectee_total'], 2, ',', ' ') ?> €</div>
                            <small class="text-muted">Sur ventes / prestations</small>
                        </div>
                    </div>
                </div>
                <!-- KPI 2 : TVA déductible -->
                <div class="col-md-4">
                    <div class="card border-info h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">TVA déductible</div>
                            <div class="h3 mb-0 text-info"><?= number_format($calcul['tva_deductible_total'], 2, ',', ' ') ?> €</div>
                            <small class="text-muted">Sur achats fournisseurs</small>
                        </div>
                    </div>
                </div>
                <!-- KPI 3 : Solde -->
                <div class="col-md-4">
                    <div class="card border-<?= $calcul['tva_a_payer'] > 0 ? 'danger' : 'success' ?> h-100">
                        <div class="card-body text-center">
                            <div class="text-muted small">
                                <?= $calcul['tva_a_payer'] > 0 ? 'TVA à payer' : 'Crédit à reporter' ?>
                            </div>
                            <div class="h3 mb-0 text-<?= $calcul['tva_a_payer'] > 0 ? 'danger' : 'success' ?>">
                                <?= number_format($calcul['tva_a_payer'] > 0 ? $calcul['tva_a_payer'] : $calcul['credit_a_reporter'], 2, ',', ' ') ?> €
                            </div>
                            <?php if ($calcul['credit_tva_anterieur'] > 0): ?>
                            <small class="text-muted">Après crédit antérieur <?= number_format($calcul['credit_tva_anterieur'], 2, ',', ' ') ?> €</small>
                            <?php else: ?>
                            <small class="text-muted">Pas de crédit antérieur</small>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <h5 class="mb-3"><i class="fas fa-list me-2"></i>Détail par taux de TVA</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Taux</th>
                            <th class="text-end">Base HT (recettes)</th>
                            <th class="text-end">TVA collectée</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>20 %</td>
                            <td class="text-end"><?= number_format($calcul['ca_ht_20'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format($calcul['tva_collectee_20'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr>
                            <td>10 %</td>
                            <td class="text-end"><?= number_format($calcul['ca_ht_10'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format($calcul['tva_collectee_10'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr>
                            <td>5,5 %</td>
                            <td class="text-end"><?= number_format($calcul['ca_ht_55'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format($calcul['tva_collectee_55'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr>
                            <td>2,1 %</td>
                            <td class="text-end"><?= number_format($calcul['ca_ht_21'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format($calcul['tva_collectee_21'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr class="table-secondary">
                            <td>Exonéré / hors champ</td>
                            <td class="text-end"><?= number_format($calcul['ca_ht_exonere'], 2, ',', ' ') ?> €</td>
                            <td class="text-end text-muted">—</td>
                        </tr>
                        <tr class="table-warning fw-bold">
                            <td>TOTAL TVA collectée</td>
                            <td></td>
                            <td class="text-end"><?= number_format($calcul['tva_collectee_total'], 2, ',', ' ') ?> €</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <h5 class="mb-3 mt-4"><i class="fas fa-arrow-down me-2"></i>TVA déductible</h5>
            <table class="table table-bordered">
                <tr>
                    <td>Biens et services (achats fournisseurs, charges)</td>
                    <td class="text-end" style="width: 200px;"><?= number_format($calcul['tva_deductible_biens_services'], 2, ',', ' ') ?> €</td>
                </tr>
                <tr>
                    <td>Immobilisations <small class="text-muted">(non géré pilote)</small></td>
                    <td class="text-end"><?= number_format($calcul['tva_deductible_immobilisations'], 2, ',', ' ') ?> €</td>
                </tr>
                <tr class="table-info fw-bold">
                    <td>TOTAL TVA déductible</td>
                    <td class="text-end"><?= number_format($calcul['tva_deductible_total'], 2, ',', ' ') ?> €</td>
                </tr>
            </table>

            <form method="POST" action="<?= BASE_URL ?>/comptabilite/tvaArchiver" class="mt-4 border-top pt-4">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <input type="hidden" name="residence_id" value="<?= (int)$calcul['residence_id'] ?>">
                <input type="hidden" name="regime" value="<?= htmlspecialchars($calcul['regime']) ?>">
                <input type="hidden" name="annee" value="<?= (int)date('Y', strtotime($calcul['periode_debut'])) ?>">
                <input type="hidden" name="mois" value="<?= (int)date('n', strtotime($calcul['periode_debut'])) ?>">
                <input type="hidden" name="trimestre" value="<?= (int)ceil((int)date('n', strtotime($calcul['periode_debut'])) / 3) ?>">

                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes (optionnelles)</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Ex: Crédit antérieur reporté de la déclaration de mars 2026"></textarea>
                </div>

                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/comptabilite/tva" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i>Retour
                    </a>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Archiver ce brouillon
                    </button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    const regimeSelect = document.getElementById('regimeSelect');
    const moisGroup    = document.getElementById('moisGroup');
    const trimGroup    = document.getElementById('trimGroup');

    function toggleGroups() {
        const v = regimeSelect.value;
        if (v === 'CA3_trimestriel') {
            moisGroup.classList.add('d-none');
            trimGroup.classList.remove('d-none');
        } else if (v === 'CA12_annuel') {
            moisGroup.classList.add('d-none');
            trimGroup.classList.add('d-none');
        } else {
            moisGroup.classList.remove('d-none');
            trimGroup.classList.add('d-none');
        }
    }

    regimeSelect.addEventListener('change', toggleGroups);
    toggleGroups();
})();
</script>
