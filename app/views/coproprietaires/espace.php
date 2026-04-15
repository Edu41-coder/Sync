<?php $title = "Mon Profil"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card', 'text' => 'Mon Profil', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <?php if (!$proprietaire): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        Aucun profil propriétaire n'est associé à votre compte. Contactez l'administrateur.
    </div>
    <?php else: ?>

    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex align-items-center">
            <div class="avatar-circle me-3" style="width:60px;height:60px;font-size:1.5rem;background:#fd7e14;color:#fff">
                <?= strtoupper(substr($proprietaire['prenom'], 0, 1) . substr($proprietaire['nom'], 0, 1)) ?>
            </div>
            <div>
                <h1 class="h3 mb-0">
                    Bienvenue, <?= htmlspecialchars($proprietaire['civilite'] . ' ' . $proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?>
                </h1>
                <span class="badge" style="background:#fd7e14"><i class="fas fa-home me-1"></i>Propriétaire</span>
            </div>
        </div>
    </div>

    <!-- Stats rapides -->
    <?php
    $contratsActifs = array_filter($contrats, fn($c) => $c['statut'] === 'actif');
    $totalLoyer = array_sum(array_column($contratsActifs, 'loyer_mensuel_garanti'));
    $nbLots = count($contratsActifs);
    $nbOccupes = count(array_filter($contratsActifs, fn($c) => !empty($c['occupation_statut'])));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Mes lots</h6>
                    <h2 class="mb-0"><?= $nbLots ?></h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Lots occupés</h6>
                    <h2 class="mb-0"><?= $nbOccupes ?> / <?= $nbLots ?></h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Revenus / mois</h6>
                    <h2 class="mb-0"><?= number_format($totalLoyer, 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning mb-1 fw-bold small">Revenus / an</h6>
                    <h2 class="mb-0"><?= number_format($totalLoyer * 12, 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
    </div>

    <!-- Mes Résidences -->
    <?php if (!empty($mesResidences)): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Mes Résidences (<?= count($mesResidences) ?>)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Résidence</th>
                            <th>Ville</th>
                            <th>Exploitant</th>
                            <th class="text-center">Mes lots</th>
                            <th class="text-center">Carte</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($mesResidences as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($r['ville'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['exploitant'] ?? '-') ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?= $r['nb_lots'] ?></span></td>
                            <td class="text-center">
                                <?php if (!empty($r['latitude']) && !empty($r['longitude'])): ?>
                                <a href="<?= BASE_URL ?>/admin/carteResidence/<?= $r['id'] ?>" class="btn btn-sm btn-outline-success" title="Voir sur la carte">
                                    <i class="fas fa-map-marker-alt"></i>
                                </a>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <!-- Colonne principale -->
        <div class="col-12 col-lg-8">
            <!-- Mes lots & contrats -->
            <div class="card shadow mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Mes Lots & Contrats (<?= count($contrats) ?>)</h5>
                </div>
                <?php if (empty($contrats)): ?>
                <div class="card-body text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted mb-3 d-block"></i>
                    <h5 class="text-muted">Aucun contrat de gestion</h5>
                </div>
                <?php else: ?>
                <div class="card-body p-0">
                    <?php foreach ($contrats as $i => $c):
                        $sc = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning','projet'=>'info'];
                        $typeLabels = ['bail_commercial'=>'Bail commercial','bail_professionnel'=>'Bail professionnel','mandat_gestion'=>'Mandat de gestion'];
                    ?>
                    <div class="p-3 <?= $i > 0 ? 'border-top' : '' ?>">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="mb-0">
                                    <i class="fas fa-building text-dark me-1"></i>
                                    <?= htmlspecialchars($c['residence_nom'] ?? '-') ?>
                                    <small class="text-muted">(<?= htmlspecialchars($c['residence_ville'] ?? '') ?>)</small>
                                </h6>
                                <small class="text-muted">
                                    Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?>
                                    (<?= htmlspecialchars($c['lot_type'] ?? '') ?>
                                    <?php if ($c['surface']): ?>, <?= $c['surface'] ?> m²<?php endif; ?>
                                    <?php if ($c['terrasse'] && $c['terrasse'] !== 'non'): ?>, <?= $c['terrasse'] === 'loggia' ? 'loggia' : 'terrasse' ?><?php endif; ?>)
                                </small>
                            </div>
                            <span class="badge bg-<?= $sc[$c['statut']] ?? 'secondary' ?>"><?= ucfirst($c['statut']) ?></span>
                        </div>

                        <div class="row g-2 small">
                            <div class="col-6 col-md-3">
                                <span class="text-muted">Contrat</span><br>
                                <strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted">Loyer garanti</span><br>
                                <strong class="text-success"><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €/mois</strong>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted">Type</span><br>
                                <?= $typeLabels[$c['type_contrat']] ?? $c['type_contrat'] ?>
                            </div>
                            <div class="col-6 col-md-3">
                                <span class="text-muted">Occupant</span><br>
                                <?php if (!empty($c['resident_nom'])): ?>
                                <i class="fas fa-user text-success me-1"></i><?= htmlspecialchars($c['resident_nom']) ?>
                                <?php else: ?>
                                <span class="text-warning"><i class="fas fa-exclamation-circle me-1"></i>Vacant</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($c['date_effet'] || $c['date_fin']): ?>
                        <div class="small text-muted mt-1">
                            <?php if ($c['date_effet']): ?>Depuis le <?= date('d/m/Y', strtotime($c['date_effet'])) ?><?php endif; ?>
                            <?php if ($c['date_fin']): ?> — Jusqu'au <?= date('d/m/Y', strtotime($c['date_fin'])) ?><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Fiscalité -->
            <?php if (!empty($fiscalite)): ?>
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#198754,#0d6832)">
                    <h5 class="mb-0"><i class="fas fa-calculator me-2"></i>Mes Données Fiscales</h5>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($fiscalite as $i => $f):
                        $regimeLabels = ['micro_bic'=>'Micro-BIC','reel_simplifie'=>'Réel simplifié','reel_normal'=>'Réel normal'];
                        $statutLabels = ['LMNP'=>'LMNP','LMP'=>'LMP','location_nue'=>'Location nue'];
                        $totalCharges = ($f['charges_deductibles'] ?? 0) + ($f['interets_emprunt'] ?? 0) + ($f['travaux_deductibles'] ?? 0)
                            + ($f['assurances_deductibles'] ?? 0) + ($f['taxe_fonciere_deductible'] ?? 0) + ($f['autres_charges_deductibles'] ?? 0);
                    ?>
                    <div class="p-3 <?= $i > 0 ? 'border-top' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">
                                <i class="fas fa-calendar me-1"></i>Année <?= $f['annee_fiscale'] ?>
                                <?php if ($f['numero_lot']): ?><small class="text-muted ms-2">(Lot <?= htmlspecialchars($f['numero_lot']) ?>)</small><?php endif; ?>
                            </h6>
                            <div>
                                <span class="badge bg-primary"><?= $regimeLabels[$f['regime_fiscal']] ?? $f['regime_fiscal'] ?></span>
                                <span class="badge bg-info"><?= $statutLabels[$f['statut_fiscal']] ?? $f['statut_fiscal'] ?></span>
                            </div>
                        </div>
                        <div class="row g-2 small">
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Bruts</span>
                                <strong><?= number_format($f['revenus_bruts'] ?? 0, 0, ',', ' ') ?> €</strong>
                            </div>
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Charges</span>
                                <strong class="text-danger"><?= number_format($totalCharges, 0, ',', ' ') ?> €</strong>
                            </div>
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Nets</span>
                                <strong class="text-success"><?= number_format($f['revenus_nets'] ?? 0, 0, ',', ' ') ?> €</strong>
                            </div>
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Amort.</span>
                                <strong><?= number_format($f['amortissement'] ?? 0, 0, ',', ' ') ?> €</strong>
                            </div>
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Résultat</span>
                                <strong><?= number_format($f['resultat_fiscal'] ?? 0, 0, ',', ' ') ?> €</strong>
                            </div>
                            <div class="col-4 col-md-2 text-center">
                                <span class="text-muted d-block">Impôt</span>
                                <strong class="text-danger"><?= number_format($f['impot_estime'] ?? 0, 0, ',', ' ') ?> €</strong>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Mes informations -->
            <div class="card shadow mb-4">
                <div class="card-header text-white" style="background:linear-gradient(135deg,#fd7e14,#e65100)">
                    <h5 class="mb-0"><i class="fas fa-id-card me-2"></i>Mes Informations</h5>
                </div>
                <div class="card-body small">
                    <table class="table table-sm table-borderless mb-0">
                        <tr><td class="text-muted" style="width:40%">Nom</td><td><?= htmlspecialchars($proprietaire['civilite'] . ' ' . $proprietaire['prenom'] . ' ' . $proprietaire['nom']) ?></td></tr>
                        <?php if ($proprietaire['date_naissance']): ?>
                        <tr><td class="text-muted">Naissance</td><td><?= date('d/m/Y', strtotime($proprietaire['date_naissance'])) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($proprietaire['profession']): ?>
                        <tr><td class="text-muted">Profession</td><td><?= htmlspecialchars($proprietaire['profession']) ?></td></tr>
                        <?php endif; ?>
                        <tr><td class="text-muted">Adresse</td><td><?= htmlspecialchars(implode(', ', array_filter([
                            $proprietaire['adresse_principale'] ?? '',
                            $proprietaire['code_postal'] ?? '',
                            $proprietaire['ville'] ?? ''
                        ]))) ?: '-' ?></td></tr>
                        <tr><td class="text-muted">Email</td><td><?= htmlspecialchars($proprietaire['email'] ?? '-') ?></td></tr>
                        <tr><td class="text-muted">Téléphone</td><td><?= htmlspecialchars($proprietaire['telephone_mobile'] ?? $proprietaire['telephone'] ?? '-') ?></td></tr>
                    </table>
                    <div class="alert alert-info small mb-0 mt-2 alert-permanent">
                        <i class="fas fa-info-circle me-1"></i>
                        Pour modifier vos informations, contactez l'administration Domitys.
                    </div>
                </div>
            </div>

            <!-- Contact Domitys -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-phone me-2"></i>Contact Domitys</h5>
                </div>
                <div class="card-body small">
                    <p class="mb-1"><i class="fas fa-envelope me-2 text-muted"></i>gestion@domitys.fr</p>
                    <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i>01 44 50 50 50</p>
                    <p class="mb-0"><i class="fas fa-globe me-2 text-muted"></i>www.domitys.fr</p>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>
