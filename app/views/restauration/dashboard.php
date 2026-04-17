<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <!-- Titre + filtre résidence -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-utensils me-2 text-warning"></i>Restauration</h2>
        <?php if (count($residences) > 1): ?>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="text-muted small mb-0">Résidence :</label>
            <select name="residence_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="0">Toutes</option>
                <?php foreach ($residences as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <!-- Stats du jour -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Repas servis aujourd'hui</h6>
                            <h3 class="mb-0"><?= $statsJour['repas_servis'] ?></h3>
                        </div>
                        <div class="text-warning fs-2"><i class="fas fa-utensils"></i></div>
                    </div>
                    <small class="text-muted"><?= $statsJour['couverts'] ?> couverts | <?= $statsJour['pension_complete'] ?> pension compl.</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">CA du jour</h6>
                            <h3 class="mb-0"><?= number_format($statsJour['ca_jour'], 2, ',', ' ') ?> &euro;</h3>
                        </div>
                        <div class="text-success fs-2"><i class="fas fa-euro-sign"></i></div>
                    </div>
                    <small class="text-muted">Hors pension complète</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-primary border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Repas ce mois</h6>
                            <h3 class="mb-0"><?= $statsMois['repas_total'] ?></h3>
                        </div>
                        <div class="text-primary fs-2"><i class="fas fa-chart-bar"></i></div>
                    </div>
                    <small class="text-muted">CA mois : <?= number_format($statsMois['ca_mois'], 0, ',', ' ') ?> &euro;</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-danger border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Alertes stock</h6>
                            <h3 class="mb-0"><?= $statsMois['alertes_stock'] ?></h3>
                        </div>
                        <div class="text-danger fs-2"><i class="fas fa-exclamation-triangle"></i></div>
                    </div>
                    <small class="text-muted"><?= $statsMois['commandes_en_cours'] ?> commande(s) en cours</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Accès rapides -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Accès rapides</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php if ($isManager): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/plats" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-book-open fa-2x mb-2 d-block"></i><small>Plats</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/menus" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-clipboard-list fa-2x mb-2 d-block"></i><small>Menus</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/inventaire" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-boxes-stacked fa-2x mb-2 d-block"></i><small>Inventaire</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/commandes" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-truck fa-2x mb-2 d-block"></i><small>Commandes</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/comptabilite" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-calculator fa-2x mb-2 d-block"></i><small>Comptabilité</small>
                            </a>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array($userRole, ['admin', 'restauration_manager', 'restauration_serveur'])): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/service" class="btn btn-outline-danger w-100 py-3">
                                <i class="fas fa-cash-register fa-2x mb-2 d-block"></i><small>Service / Facturer</small>
                            </a>
                        </div>
                        <?php endif; ?>

                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/planning" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i><small>Planning</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/restauration/residents" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i><small>Résidents</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Menu du jour -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Menu du jour — <?= date('d/m/Y') ?></h6>
                    <?php if ($isManager): ?>
                    <a href="<?= BASE_URL ?>/restauration/menus" class="btn btn-sm btn-outline-warning">Gérer</a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($menuDuJour)): ?>
                        <p class="text-muted text-center py-3"><i class="fas fa-info-circle me-2"></i>Aucun menu défini pour aujourd'hui.</p>
                    <?php else: ?>
                        <?php
                        $serviceLabels = ['dejeuner' => 'Déjeuner', 'diner' => 'Dîner', 'petit_dejeuner' => 'Petit-déjeuner', 'gouter' => 'Goûter'];
                        $catLabels = ['entree' => 'Entrées', 'plat' => 'Plats', 'dessert' => 'Desserts', 'accompagnement' => 'Accompagnements', 'boisson' => 'Boissons'];
                        foreach ($menuDuJour as $service => $categories): ?>
                        <h6 class="text-warning mb-2"><i class="fas fa-sun me-1"></i><?= $serviceLabels[$service] ?? $service ?></h6>
                        <?php foreach ($categories as $cat => $plats): ?>
                            <p class="text-muted small mb-1"><strong><?= $catLabels[$cat] ?? $cat ?></strong></p>
                            <ul class="list-unstyled mb-2 ps-3">
                                <?php foreach ($plats as $plat): ?>
                                <li>
                                    <?= htmlspecialchars($plat['plat_nom']) ?>
                                    <?php if ($plat['regime'] !== 'normal'): ?>
                                        <span class="badge bg-info text-dark" style="font-size:0.65rem"><?= $plat['regime'] ?></span>
                                    <?php endif; ?>
                                    <?php if ($plat['allergenes']): ?>
                                        <span class="badge bg-warning text-dark" style="font-size:0.6rem" title="<?= htmlspecialchars($plat['allergenes']) ?>"><i class="fas fa-allergies"></i></span>
                                    <?php endif; ?>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endforeach; ?>
                        <hr class="my-2">
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Activité récente + Alertes -->
        <div class="col-lg-6">
            <!-- Alertes stock -->
            <?php if ($isManager && !empty($alertesStock)): ?>
            <div class="card shadow-sm mb-3 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertes stock bas</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <tbody>
                            <?php foreach ($alertesStock as $alerte): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($alerte['produit_nom']) ?></strong>
                                    <br><small class="text-muted"><?= htmlspecialchars($alerte['residence_nom']) ?></small>
                                </td>
                                <td class="text-end text-danger">
                                    <strong><?= $alerte['quantite_stock'] ?></strong> <?= $alerte['unite'] ?>
                                    <br><small class="text-muted">seuil : <?= $alerte['seuil_alerte'] ?></small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

            <!-- Derniers repas servis -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Derniers repas servis</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($repasRecents)): ?>
                        <p class="text-muted text-center py-3">Aucun repas enregistré.</p>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Client</th><th>Service</th><th>Mode</th><th class="text-end">Montant</th></tr></thead>
                        <tbody>
                            <?php
                            $serviceIcons = ['petit_dejeuner'=>'fa-coffee','dejeuner'=>'fa-sun','gouter'=>'fa-cookie','diner'=>'fa-moon','snack_bar'=>'fa-glass-martini'];
                            foreach ($repasRecents as $repas): ?>
                            <tr>
                                <td>
                                    <?= htmlspecialchars($repas['client_nom'] ?? 'N/A') ?>
                                    <span class="badge bg-<?= $repas['type_client'] === 'resident' ? 'primary' : ($repas['type_client'] === 'hote' ? 'info' : 'secondary') ?>" style="font-size:0.6rem"><?= $repas['type_client'] ?></span>
                                </td>
                                <td><i class="fas <?= $serviceIcons[$repas['type_service']] ?? 'fa-utensils' ?> me-1"></i><?= str_replace('_', ' ', $repas['type_service']) ?></td>
                                <td><span class="badge bg-<?= $repas['mode_facturation'] === 'pension_complete' ? 'success' : 'warning text-dark' ?>"><?= str_replace('_', ' ', $repas['mode_facturation']) ?></span></td>
                                <td class="text-end"><?= $repas['mode_facturation'] === 'pension_complete' ? '<span class="text-success">inclus</span>' : number_format($repas['montant'], 2, ',', ' ') . ' €' ?></td>
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
