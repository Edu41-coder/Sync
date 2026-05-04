<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-chart-line',     'text' => 'SIG',             'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

// Couleurs en cascade pour les soldes
function classSig($val) {
    return $val > 0 ? 'text-success' : ($val < 0 ? 'text-danger' : 'text-muted');
}
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-chart-line me-2 text-primary"></i>Soldes Intermédiaires de Gestion (SIG)</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Cascade des soldes intermédiaires de gestion : <strong>Production → Valeur Ajoutée → EBE → Résultat</strong>.
        Les amortissements et provisions ne sont pas gérés dans cette version pilote.
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

    <div class="alert alert-secondary py-2 small">
        <strong>Période :</strong> <?= htmlspecialchars($periode['libelle']) ?>
        (<?= htmlspecialchars($periode['debut']) ?> → <?= htmlspecialchars($periode['fin']) ?>)
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-primary">
                    <tr>
                        <th style="width: 5%">Étape</th>
                        <th>Solde intermédiaire</th>
                        <th class="text-end" style="width: 20%">Calcul</th>
                        <th class="text-end" style="width: 20%">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="badge bg-secondary">+</span></td>
                        <td><strong>Production de l'exercice</strong> <small class="text-muted d-block">Comptes 70x (hors 701, 758)</small></td>
                        <td class="text-end text-muted">+</td>
                        <td class="text-end fw-bold"><?= number_format((float)$sig['production'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">−</span></td>
                        <td>Consommations en provenance des tiers <small class="text-muted d-block">Comptes 60x, 61x, 62x</small></td>
                        <td class="text-end text-muted">−</td>
                        <td class="text-end"><?= number_format((float)$sig['consommations'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-success fw-bold">
                        <td><span class="badge bg-success">=</span></td>
                        <td><strong>VALEUR AJOUTÉE</strong></td>
                        <td class="text-end">=</td>
                        <td class="text-end <?= classSig($sig['valeur_ajoutee']) ?>"><?= number_format((float)$sig['valeur_ajoutee'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">−</span></td>
                        <td>Charges de personnel <small class="text-muted d-block">Compte 64x</small></td>
                        <td class="text-end text-muted">−</td>
                        <td class="text-end"><?= number_format((float)$sig['charges_personnel'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">−</span></td>
                        <td>Impôts, taxes et versements assimilés <small class="text-muted d-block">Compte 63x</small></td>
                        <td class="text-end text-muted">−</td>
                        <td class="text-end"><?= number_format((float)$sig['impots_taxes'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-info fw-bold">
                        <td><span class="badge bg-info">=</span></td>
                        <td><strong>EXCÉDENT BRUT D'EXPLOITATION (EBE)</strong></td>
                        <td class="text-end">=</td>
                        <td class="text-end <?= classSig($sig['ebe']) ?>"><?= number_format((float)$sig['ebe'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-warning fw-bold">
                        <td><span class="badge bg-warning text-dark">=</span></td>
                        <td>Résultat d'exploitation <small class="text-muted d-block">EBE (amortissements non gérés)</small></td>
                        <td class="text-end">=</td>
                        <td class="text-end <?= classSig($sig['resultat_exploitation']) ?>"><?= number_format((float)$sig['resultat_exploitation'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-secondary">+</span></td>
                        <td>Produits exceptionnels <small class="text-muted d-block">Comptes 701, 758</small></td>
                        <td class="text-end text-muted">+</td>
                        <td class="text-end"><?= number_format((float)$sig['produits_exceptionnels'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><span class="badge bg-danger">−</span></td>
                        <td>Charges exceptionnelles <small class="text-muted d-block">Compte 67x</small></td>
                        <td class="text-end text-muted">−</td>
                        <td class="text-end"><?= number_format((float)$sig['charges_exceptionnelles'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-primary fw-bold" style="font-size: 1.1rem;">
                        <td><span class="badge bg-primary">=</span></td>
                        <td><strong>RÉSULTAT NET DE L'EXERCICE</strong></td>
                        <td class="text-end">=</td>
                        <td class="text-end <?= classSig($sig['resultat_net']) ?>"><?= number_format((float)$sig['resultat_net'], 2, ',', ' ') ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <div class="text-end mt-3">
        <a href="<?= BASE_URL ?>/comptabilite/bilan?residence_id=<?= $selectedResidence ?>&annee=<?= (int)$annee ?>" class="btn btn-outline-primary">
            <i class="fas fa-balance-scale me-1"></i>Voir le bilan
        </a>
    </div>
</div>
