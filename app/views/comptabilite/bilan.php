<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-balance-scale',  'text' => 'Bilan',           'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$labelsActif = [
    'immobilisations'  => 'Immobilisations (classe 2)',
    'creances_tiers'   => 'Créances clients & tiers',
    'tva_deductible'   => 'TVA déductible',
    'tresorerie'       => 'Trésorerie (banque + caisse)',
];
$labelsPassif = [
    'capitaux_propres'         => 'Capitaux propres (incl. résultat)',
    'dettes_fournisseurs'      => 'Dettes fournisseurs',
    'dettes_personnel'         => 'Dettes envers le personnel',
    'dettes_sociales_fiscales' => 'Dettes sociales et fiscales',
    'tva_collectee'            => 'TVA collectée',
];
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-balance-scale me-2 text-primary"></i>Bilan simplifié</h2>

    <div class="alert alert-warning small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Pilote — bilan agrégé.</strong>
        Vue simplifiée à des fins d'analyse interne. Pour un bilan officiel (formulaire 2050 / 2051),
        un cabinet comptable doit consolider les données avec la situation d'ouverture, les amortissements,
        et les écritures de clôture.
    </div>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
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
    </div>

    <div class="row g-3">
        <!-- ACTIF -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-arrow-down me-2"></i>ACTIF (Emplois)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <?php foreach ($bilan['actif'] as $key => $val): ?>
                            <tr>
                                <td><?= htmlspecialchars($labelsActif[$key] ?? $key) ?></td>
                                <td class="text-end <?= $val < 0 ? 'text-danger' : '' ?>">
                                    <?= number_format((float)$val, 2, ',', ' ') ?> €
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-primary fw-bold">
                            <tr>
                                <td>TOTAL ACTIF</td>
                                <td class="text-end"><?= number_format((float)$bilan['total_actif'], 2, ',', ' ') ?> €</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- PASSIF -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-header bg-success text-white">
                    <i class="fas fa-arrow-up me-2"></i>PASSIF (Ressources)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <tbody>
                            <?php foreach ($bilan['passif'] as $key => $val): ?>
                            <tr>
                                <td><?= htmlspecialchars($labelsPassif[$key] ?? $key) ?></td>
                                <td class="text-end <?= $val < 0 ? 'text-danger' : '' ?>">
                                    <?= number_format((float)$val, 2, ',', ' ') ?> €
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot class="table-success fw-bold">
                            <tr>
                                <td>TOTAL PASSIF</td>
                                <td class="text-end"><?= number_format((float)$bilan['total_passif'], 2, ',', ' ') ?> €</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Synthèse -->
    <div class="card shadow-sm mt-3">
        <div class="card-header bg-secondary text-white">
            <i class="fas fa-info-circle me-2"></i>Synthèse de l'exercice
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <div class="text-muted small">Total produits</div>
                    <div class="h5 text-success"><?= number_format((float)$bilan['total_produits'], 2, ',', ' ') ?> €</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Total charges</div>
                    <div class="h5 text-danger"><?= number_format((float)$bilan['total_charges'], 2, ',', ' ') ?> €</div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Résultat net</div>
                    <div class="h5 <?= $bilan['resultat_net'] >= 0 ? 'text-success' : 'text-danger' ?>">
                        <?= number_format((float)$bilan['resultat_net'], 2, ',', ' ') ?> €
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="text-muted small">Écart actif/passif</div>
                    <div class="h5 <?= abs((float)$bilan['ecart']) < 0.01 ? 'text-success' : 'text-warning' ?>">
                        <?= number_format((float)$bilan['ecart'], 2, ',', ' ') ?> €
                        <?php if (abs((float)$bilan['ecart']) < 0.01): ?>
                            <i class="fas fa-check-circle ms-1" title="Équilibré"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php if (abs((float)$bilan['ecart']) >= 0.01): ?>
            <div class="alert alert-warning small mb-0 mt-2">
                <i class="fas fa-exclamation-triangle me-1"></i>
                <strong>Écart actif/passif détecté.</strong>
                Vérifiez les écritures sans compte affecté ou les comptes mal classés. En pilote, un écart est attendu
                tant que les bilans d'ouverture et amortissements ne sont pas saisis.
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="text-end mt-3">
        <a href="<?= BASE_URL ?>/comptabilite/sig?residence_id=<?= $selectedResidence ?>&annee=<?= (int)$annee ?>" class="btn btn-outline-primary">
            <i class="fas fa-chart-line me-1"></i>Voir les SIG
        </a>
    </div>
</div>
