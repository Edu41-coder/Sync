<?php $title = $proprietaire['prenom'] . ' ' . $proprietaire['nom']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-home', 'text' => 'Propriétaires', 'url' => BASE_URL . '/coproprietaire/index'],
    ['icon' => 'fas fa-eye', 'text' => $proprietaire['prenom'] . ' ' . $proprietaire['nom'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <div class="avatar-circle me-3" style="width:60px;height:60px;font-size:1.5rem;background:#fd7e14;color:#fff">
                    <?= strtoupper(substr($proprietaire['prenom'], 0, 1) . substr($proprietaire['nom'], 0, 1)) ?>
                </div>
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-eye text-dark me-1"></i>
                        <?= htmlspecialchars($proprietaire['civilite'] . ' ' . $proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                    </h1>
                    <span class="badge" style="background:#fd7e14"><i class="fas fa-home me-1"></i>Propriétaire</span>
                    <?php if ($proprietaire['user_actif'] !== null): ?>
                        <?= $proprietaire['user_actif'] ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>' ?>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <?php if ($proprietaire['user_id']): ?>
                <a href="<?= BASE_URL ?>/admin/users/edit/<?= $proprietaire['user_id'] ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Modifier</a>
                <?php endif; ?>
                <?php
                $backUrl = BASE_URL . '/coproprietaire/index';
                if (isset($_GET['from']) && $_GET['from'] === 'users' && $proprietaire['user_id']) {
                    $backUrl = BASE_URL . '/admin/users/show/' . $proprietaire['user_id'];
                }
                ?>
                <a href="<?= $backUrl ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Infos principales -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#fd7e14,#e65100)">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Informations personnelles</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="text-muted small">Nom complet</label>
                            <div class="fw-bold"><?= htmlspecialchars($proprietaire['civilite'] . ' ' . $proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Date de naissance</label>
                            <div><?= $proprietaire['date_naissance'] ? date('d/m/Y', strtotime($proprietaire['date_naissance'])) : '-' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Profession</label>
                            <div><?= htmlspecialchars($proprietaire['profession'] ?? '-') ?: '-' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Compte utilisateur</label>
                            <div><?= $proprietaire['username'] ? '<code>' . htmlspecialchars($proprietaire['username']) . '</code>' : '<span class="text-muted">Non lié</span>' ?></div>
                        </div>
                        <div class="col-12"><hr class="my-1"></div>
                        <div class="col-12">
                            <label class="text-muted small">Adresse</label>
                            <div><i class="fas fa-map-marker-alt text-dark me-1"></i><?= htmlspecialchars(implode(', ', array_filter([
                                $proprietaire['adresse_principale'] ?? '',
                                $proprietaire['code_postal'] ?? '',
                                $proprietaire['ville'] ?? ''
                            ]))) ?: '-' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Email</label>
                            <div><?= $proprietaire['email'] ? '<a href="mailto:' . htmlspecialchars($proprietaire['email']) . '">' . htmlspecialchars($proprietaire['email']) . '</a>' : '-' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Téléphone</label>
                            <div><?= htmlspecialchars($proprietaire['telephone_mobile'] ?? $proprietaire['telephone'] ?? '-') ?: '-' ?></div>
                        </div>
                        <?php if (!empty($proprietaire['notes'])): ?>
                        <div class="col-12">
                            <label class="text-muted small">Notes</label>
                            <div class="small"><?= nl2br(htmlspecialchars($proprietaire['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Résumé financier -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Résumé financier</h5>
                </div>
                <div class="card-body">
                    <?php
                    $contratsActifs = array_filter($contrats, fn($c) => $c['statut'] === 'actif');
                    $totalLoyer = array_sum(array_column($contratsActifs, 'loyer_mensuel_garanti'));
                    $totalMarge = array_sum(array_column(array_filter($contratsActifs, fn($c) => $c['marge'] !== null), 'marge'));
                    ?>
                    <div class="row g-3 text-center">
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-primary mb-0"><?= count($contratsActifs) ?></h4>
                                <small class="text-muted">Contrats actifs</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-success mb-0"><?= number_format($totalLoyer, 0, ',', ' ') ?> €</h4>
                                <small class="text-muted">Revenus / mois</small>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="border rounded p-3">
                                <h4 class="text-info mb-0"><?= number_format($totalLoyer * 12, 0, ',', ' ') ?> €</h4>
                                <small class="text-muted">Revenus / an</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Contrats -->
    <?php if (!empty($contrats)): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Contrats de gestion (<?= count($contrats) ?>)</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#contratModal"><i class="fas fa-plus me-1"></i>Ajouter</button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>N° Contrat</th>
                            <th>Résidence / Lot</th>
                            <th>Type</th>
                            <th class="text-end">Loyer garanti</th>
                            <th class="text-end">Loyer résident</th>
                            <th class="text-end">Marge Domitys</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contrats as $c):
                            $sc = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning','projet'=>'info'];
                            $typeLabels = ['bail_commercial'=>'Bail commercial','bail_professionnel'=>'Bail professionnel','mandat_gestion'=>'Mandat de gestion'];
                            $marge = $c['marge'] ?? null;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong></td>
                            <td>
                                <?= htmlspecialchars($c['residence_nom'] ?? '-') ?>
                                <br><small class="text-muted">Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?> (<?= htmlspecialchars($c['lot_type'] ?? '') ?>, <?= $c['surface'] ?? '-' ?> m²)</small>
                            </td>
                            <td><small><?= $typeLabels[$c['type_contrat']] ?? $c['type_contrat'] ?></small></td>
                            <td class="text-end fw-bold"><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= $c['loyer_mensuel_resident'] ? number_format($c['loyer_mensuel_resident'], 2, ',', ' ') . ' €' : '-' ?></td>
                            <td class="text-end <?= $marge !== null ? ($marge >= 0 ? 'text-success' : 'text-danger') : '' ?>">
                                <?= $marge !== null ? ($marge >= 0 ? '+' : '') . number_format($marge, 2, ',', ' ') . ' €' : '-' ?>
                            </td>
                            <td class="text-center"><span class="badge bg-<?= $sc[$c['statut']] ?? 'secondary' ?>"><?= ucfirst($c['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow mb-4 border-warning">
        <div class="card-body text-center py-4">
            <i class="fas fa-file-contract fa-3x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Aucun contrat de gestion</h5>
            <p class="text-muted mb-3">Ce propriétaire n'a pas encore de contrat avec Domitys.</p>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#contratModal"><i class="fas fa-plus me-1"></i>Créer un contrat</button>
        </div>
    </div>
    <?php endif; ?>

    <!-- Fiscalité -->
    <div class="card shadow mb-4">
        <div class="card-header text-white d-flex justify-content-between align-items-center" style="background:linear-gradient(135deg,#198754,#0d6832)">
            <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Fiscalité (<?= count($fiscalite) ?> année<?= count($fiscalite) > 1 ? 's' : '' ?>)</h5>
            <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#fiscaliteModal"><i class="fas fa-plus me-1"></i>Ajouter année</button>
        </div>
        <?php if (!empty($fiscalite)): ?>
        <div class="card-body p-0">
            <?php foreach ($fiscalite as $i => $f):
                $regimeLabels = ['micro_bic'=>'Micro-BIC','reel_simplifie'=>'Réel simplifié','reel_normal'=>'Réel normal'];
                $statutLabels = ['LMNP'=>'LMNP','LMP'=>'LMP','location_nue'=>'Location nue'];
                $totalCharges = ($f['charges_deductibles'] ?? 0) + ($f['interets_emprunt'] ?? 0) + ($f['travaux_deductibles'] ?? 0)
                    + ($f['assurances_deductibles'] ?? 0) + ($f['taxe_fonciere_deductible'] ?? 0) + ($f['autres_charges_deductibles'] ?? 0);
            ?>
            <div class="<?= $i > 0 ? 'border-top' : '' ?>">
                <div class="p-3">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0">
                                <i class="fas fa-calendar me-1"></i>Année <?= $f['annee_fiscale'] ?>
                                <?php if ($f['numero_lot']): ?>
                                <small class="text-muted ms-2">(Lot <?= htmlspecialchars($f['numero_lot']) ?> — <?= htmlspecialchars($f['residence_nom'] ?? '') ?>)</small>
                                <?php endif; ?>
                            </h6>
                        </div>
                        <div>
                            <span class="badge bg-primary"><?= $regimeLabels[$f['regime_fiscal']] ?? $f['regime_fiscal'] ?></span>
                            <span class="badge bg-info"><?= $statutLabels[$f['statut_fiscal']] ?? $f['statut_fiscal'] ?></span>
                        </div>
                    </div>

                    <div class="row g-3">
                        <!-- Revenus -->
                        <div class="col-12 col-md-6">
                            <div class="card bg-light">
                                <div class="card-body p-2">
                                    <h6 class="small fw-bold text-success mb-2"><i class="fas fa-arrow-up me-1"></i>Revenus</h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <tr><td class="text-muted">Revenus bruts</td><td class="text-end fw-bold"><?= number_format($f['revenus_bruts'] ?? 0, 2, ',', ' ') ?> €</td></tr>
                                        <tr><td class="text-muted">Revenus nets</td><td class="text-end fw-bold text-success"><?= number_format($f['revenus_nets'] ?? 0, 2, ',', ' ') ?> €</td></tr>
                                        <tr><td class="text-muted">Résultat fiscal</td><td class="text-end fw-bold"><?= number_format($f['resultat_fiscal'] ?? 0, 2, ',', ' ') ?> €</td></tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Charges -->
                        <div class="col-12 col-md-6">
                            <div class="card bg-light">
                                <div class="card-body p-2">
                                    <h6 class="small fw-bold text-danger mb-2"><i class="fas fa-arrow-down me-1"></i>Charges déductibles <span class="text-muted">(<?= number_format($totalCharges, 2, ',', ' ') ?> €)</span></h6>
                                    <table class="table table-sm table-borderless mb-0 small">
                                        <?php if ($f['charges_deductibles'] > 0): ?><tr><td class="text-muted">Charges</td><td class="text-end"><?= number_format($f['charges_deductibles'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($f['interets_emprunt'] > 0): ?><tr><td class="text-muted">Intérêts emprunt</td><td class="text-end"><?= number_format($f['interets_emprunt'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($f['travaux_deductibles'] > 0): ?><tr><td class="text-muted">Travaux</td><td class="text-end"><?= number_format($f['travaux_deductibles'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($f['assurances_deductibles'] > 0): ?><tr><td class="text-muted">Assurances</td><td class="text-end"><?= number_format($f['assurances_deductibles'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($f['taxe_fonciere_deductible'] > 0): ?><tr><td class="text-muted">Taxe foncière</td><td class="text-end"><?= number_format($f['taxe_fonciere_deductible'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($f['autres_charges_deductibles'] > 0): ?><tr><td class="text-muted">Autres charges</td><td class="text-end"><?= number_format($f['autres_charges_deductibles'], 2, ',', ' ') ?> €</td></tr><?php endif; ?>
                                        <?php if ($totalCharges == 0): ?><tr><td class="text-muted" colspan="2">Aucune charge</td></tr><?php endif; ?>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Avantages fiscaux -->
                        <?php
                        $hasAvantages = ($f['amortissement'] ?? 0) > 0 || ($f['reduction_censi_bouvard'] ?? 0) > 0
                            || ($f['recuperation_tva'] ?? 0) > 0 || ($f['credit_impot'] ?? 0) > 0;
                        ?>
                        <?php if ($hasAvantages): ?>
                        <div class="col-12">
                            <div class="card bg-light">
                                <div class="card-body p-2">
                                    <h6 class="small fw-bold text-primary mb-2"><i class="fas fa-gift me-1"></i>Avantages fiscaux</h6>
                                    <div class="row small">
                                        <?php if ($f['amortissement'] > 0): ?><div class="col-6 col-md-3"><span class="text-muted">Amortissement</span><br><strong><?= number_format($f['amortissement'], 2, ',', ' ') ?> €</strong></div><?php endif; ?>
                                        <?php if ($f['reduction_censi_bouvard'] > 0): ?><div class="col-6 col-md-3"><span class="text-muted">Censi-Bouvard</span><br><strong><?= number_format($f['reduction_censi_bouvard'], 2, ',', ' ') ?> €</strong></div><?php endif; ?>
                                        <?php if ($f['recuperation_tva'] > 0): ?><div class="col-6 col-md-3"><span class="text-muted">Récup. TVA</span><br><strong><?= number_format($f['recuperation_tva'], 2, ',', ' ') ?> €</strong></div><?php endif; ?>
                                        <?php if ($f['credit_impot'] > 0): ?><div class="col-6 col-md-3"><span class="text-muted">Crédit impôt</span><br><strong><?= number_format($f['credit_impot'], 2, ',', ' ') ?> €</strong></div><?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Impôt estimé -->
                        <?php if ($f['impot_estime']): ?>
                        <div class="col-12 text-end">
                            <strong>Impôt estimé : <span class="text-danger"><?= number_format($f['impot_estime'], 2, ',', ' ') ?> €</span></strong>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="card-body text-center py-4">
            <i class="fas fa-calculator fa-3x text-muted mb-3 d-block"></i>
            <h5 class="text-muted">Aucune donnée fiscale</h5>
            <p class="text-muted mb-3">Les revenus fiscaux de ce propriétaire n'ont pas encore été renseignés.</p>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#fiscaliteModal"><i class="fas fa-plus me-1"></i>Ajouter une année fiscale</button>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Contrat -->
<div class="modal fade" id="contratModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/coproprietaire/storeContrat/<?= $proprietaire['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title"><i class="fas fa-file-contract me-2"></i>Nouveau contrat de gestion</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Lot -->
                        <div class="col-12 col-md-6">
                            <label class="form-label">Lot <span class="text-danger">*</span></label>
                            <select class="form-select" name="lot_id" required>
                                <option value="">— Sélectionner —</option>
                                <?php
                                $lastRes = '';
                                foreach ($lots as $l):
                                    if ($l['residence_nom'] !== $lastRes):
                                        if ($lastRes) echo '</optgroup>';
                                        echo '<optgroup label="' . htmlspecialchars($l['residence_nom']) . '">';
                                        $lastRes = $l['residence_nom'];
                                    endif;
                                ?>
                                <option value="<?= $l['id'] ?>">Lot <?= htmlspecialchars($l['numero_lot']) ?> (<?= $l['type'] ?>, <?= $l['surface'] ?? '-' ?> m²)</option>
                                <?php endforeach; if ($lastRes) echo '</optgroup>'; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Exploitant <span class="text-danger">*</span></label>
                            <select class="form-select" name="exploitant_id" required>
                                <?php foreach ($exploitants as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $e['id'] == 1 ? 'selected' : '' ?>><?= htmlspecialchars($e['raison_sociale']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Type de contrat</label>
                            <select class="form-select" name="type_contrat">
                                <option value="bail_commercial">Bail commercial</option>
                                <option value="bail_professionnel">Bail professionnel</option>
                                <option value="mandat_gestion">Mandat de gestion</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Date de signature</label>
                            <input type="date" class="form-control" name="date_signature" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Statut</label>
                            <select class="form-select" name="statut_contrat">
                                <option value="actif">Actif</option>
                                <option value="projet">Projet</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-4">
                            <label class="form-label">Date d'effet</label>
                            <input type="date" class="form-control" name="date_effet" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Durée (années)</label>
                            <input type="number" class="form-control" name="duree_initiale_annees" value="9" min="1">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Date de fin</label>
                            <input type="date" class="form-control" name="date_fin">
                        </div>

                        <hr>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Loyer mensuel garanti <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="loyer_mensuel_garanti" step="0.01" min="0" placeholder="850.00" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Charges mensuelles</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="charges_mensuelles" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Indexation</label>
                            <select class="form-select" name="indexation_type">
                                <option value="IRL">IRL</option>
                                <option value="ICC">ICC</option>
                                <option value="ILAT">ILAT</option>
                                <option value="fixe">Fixe</option>
                            </select>
                        </div>

                        <div class="col-12 col-md-3">
                            <label class="form-label">Dispositif fiscal</label>
                            <select class="form-select" name="dispositif_fiscal">
                                <option value="LMNP">LMNP</option>
                                <option value="Censi-Bouvard">Censi-Bouvard</option>
                                <option value="LMP">LMP</option>
                                <option value="Pinel">Pinel</option>
                                <option value="Aucun">Aucun</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Statut loueur</label>
                            <select class="form-select" name="statut_loueur">
                                <option value="LMNP">LMNP</option>
                                <option value="LMP">LMP</option>
                                <option value="location_nue">Location nue</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Mode paiement</label>
                            <select class="form-select" name="mode_paiement">
                                <option value="virement">Virement</option>
                                <option value="prelevement">Prélèvement</option>
                                <option value="cheque">Chèque</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-3">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="recuperation_tva" checked>
                                <label class="form-check-label">Récup. TVA</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="garantie_loyer" checked>
                                <label class="form-check-label">Garantie loyer</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="meuble" checked>
                                <label class="form-check-label">Meublé</label>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="notes" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Créer le contrat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Fiscalité -->
<div class="modal fade" id="fiscaliteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/coproprietaire/storeFiscalite/<?= $proprietaire['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                <div class="modal-header text-white" style="background:linear-gradient(135deg,#198754,#0d6832)">
                    <h5 class="modal-title"><i class="fas fa-calculator me-2"></i>Ajouter une année fiscale</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-3">
                            <label class="form-label">Année fiscale <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" name="annee_fiscale" value="<?= date('Y') - 1 ?>" min="2020" max="<?= date('Y') ?>" required>
                        </div>
                        <div class="col-12 col-md-5">
                            <label class="form-label">Lot concerné</label>
                            <select class="form-select" name="lot_id">
                                <option value="">— Tous les lots —</option>
                                <?php foreach ($contrats as $c): ?>
                                <option value="<?= $c['lot_id'] ?? '' ?>">Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?> — <?= htmlspecialchars($c['residence_nom'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Régime fiscal</label>
                            <select class="form-select" name="regime_fiscal">
                                <option value="reel_simplifie">Réel simplifié</option>
                                <option value="micro_bic">Micro-BIC</option>
                                <option value="reel_normal">Réel normal</option>
                            </select>
                        </div>

                        <hr>
                        <div class="col-12"><h6 class="text-success fw-bold small text-uppercase"><i class="fas fa-arrow-up me-1"></i>Revenus</h6></div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Revenus bruts annuels <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="revenus_bruts" step="0.01" min="0" id="fisc_bruts" required>
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Statut fiscal</label>
                            <select class="form-select" name="statut_fiscal">
                                <option value="LMNP">LMNP</option>
                                <option value="LMP">LMP</option>
                                <option value="location_nue">Location nue</option>
                            </select>
                        </div>

                        <div class="col-12"><h6 class="text-danger fw-bold small text-uppercase"><i class="fas fa-arrow-down me-1"></i>Charges déductibles</h6></div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Charges courantes</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="charges_deductibles" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Intérêts emprunt</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="interets_emprunt" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Travaux</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="travaux_deductibles" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Assurances</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="assurances_deductibles" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Taxe foncière</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="taxe_fonciere_deductible" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Autres charges</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control fisc-charge" name="autres_charges_deductibles" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>

                        <div class="col-12"><h6 class="text-primary fw-bold small text-uppercase"><i class="fas fa-gift me-1"></i>Avantages fiscaux</h6></div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Amortissement</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="amortissement" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Censi-Bouvard</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="reduction_censi_bouvard" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Récup. TVA</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="recuperation_tva_montant" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-3">
                            <label class="form-label">Crédit impôt</label>
                            <div class="input-group input-group-sm">
                                <input type="number" class="form-control" name="credit_impot" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>

                        <div class="col-12 col-md-6">
                            <label class="form-label">Impôt estimé</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="impot_estime" step="0.01" min="0" value="0">
                                <span class="input-group-text">€</span>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Notes</label>
                            <input type="text" class="form-control" name="notes" placeholder="Remarques...">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
