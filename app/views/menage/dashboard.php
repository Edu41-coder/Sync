<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$sectionLabels = ['interieur' => 'Intérieur', 'exterieur' => 'Extérieur', 'laverie' => 'Laverie'];
$sectionIcons = ['interieur' => 'fa-bed', 'exterieur' => 'fa-tree', 'laverie' => 'fa-tshirt'];
$sectionColors = ['interieur' => 'info', 'exterieur' => 'success', 'laverie' => 'warning'];
$statsParSection = [];
foreach ($statsSection as $s) { $statsParSection[$s['type_tache']] = $s; }
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-broom me-2 text-info"></i>Ménage</h2>
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

    <!-- Stats globales du jour -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Tâches du jour</h6>
                            <h3 class="mb-0"><?= $statsJour['total'] ?></h3>
                        </div>
                        <div class="text-info fs-2"><i class="fas fa-tasks"></i></div>
                    </div>
                    <div class="progress mt-2" style="height:6px">
                        <div class="progress-bar bg-success" style="width:<?= $statsJour['taux'] ?>%"></div>
                    </div>
                    <small class="text-muted"><?= $statsJour['taux'] ?>% terminé</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">Terminées</h6>
                            <h3 class="mb-0 text-success"><?= $statsJour['terminees'] ?></h3>
                        </div>
                        <div class="text-success fs-2"><i class="fas fa-check-circle"></i></div>
                    </div>
                    <small class="text-muted"><?= $statsJour['en_cours'] ?> en cours | <?= $statsJour['pas_deranger'] ?> pas déranger</small>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-muted small mb-1">À faire</h6>
                            <h3 class="mb-0 text-warning"><?= $statsJour['a_faire'] ?></h3>
                        </div>
                        <div class="text-warning fs-2"><i class="fas fa-clock"></i></div>
                    </div>
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
                    <small class="text-muted"><?= $statsMois['commandes_en_cours'] ?> cmd | <?= $statsMois['laverie_en_attente'] ?> laverie</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats par section -->
    <div class="row g-3 mb-4">
        <?php foreach (['interieur', 'exterieur', 'laverie'] as $sec):
            $s = $statsParSection[$sec] ?? ['total' => 0, 'terminees' => 0, 'a_faire' => 0];
            $show = ($userSection === null || $userSection === $sec);
            if (!$show) continue;
        ?>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-header bg-<?= $sectionColors[$sec] ?> <?= $sec === 'laverie' ? 'text-dark' : 'text-white' ?>">
                    <h6 class="mb-0"><i class="fas <?= $sectionIcons[$sec] ?> me-2"></i><?= $sectionLabels[$sec] ?></h6>
                </div>
                <div class="card-body text-center">
                    <div class="row">
                        <div class="col-4">
                            <h4 class="mb-0"><?= $s['total'] ?></h4>
                            <small class="text-muted">Total</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-success"><?= $s['terminees'] ?></h4>
                            <small class="text-muted">Fait</small>
                        </div>
                        <div class="col-4">
                            <h4 class="mb-0 text-warning"><?= $s['a_faire'] ?></h4>
                            <small class="text-muted">Reste</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Accès rapides -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Accès rapides</h6></div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php if ($userSection === null || $userSection === 'interieur'): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/interieur" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-bed fa-2x mb-2 d-block"></i><small>Intérieur</small>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ($userSection === null || $userSection === 'exterieur'): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/exterieur" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-tree fa-2x mb-2 d-block"></i><small>Extérieur</small>
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if ($userSection === null || $userSection === 'laverie'): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/laverie" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-tshirt fa-2x mb-2 d-block"></i><small>Laverie</small>
                            </a>
                        </div>
                        <?php endif; ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/planning" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i><small>Planning</small>
                            </a>
                        </div>
                        <?php if ($isManager): ?>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/zones" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-map-marked fa-2x mb-2 d-block"></i><small>Zones ext.</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="<?= BASE_URL ?>/menage/inventaire" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-boxes-stacked fa-2x mb-2 d-block"></i><small>Inventaire</small>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Alertes stock -->
        <?php if ($isManager && !empty($alertesStock)): ?>
        <div class="col-lg-6">
            <div class="card shadow-sm border-danger">
                <div class="card-header bg-danger text-white"><h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertes stock bas</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <?php foreach ($alertesStock as $a): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($a['produit_nom']) ?></strong><br><small class="text-muted"><?= $a['section'] ?> — <?= htmlspecialchars($a['residence_nom']) ?></small></td>
                            <td class="text-end text-danger"><strong><?= $a['quantite_stock'] ?></strong> <?= $a['unite'] ?><br><small class="text-muted">seuil : <?= $a['seuil_alerte'] ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Laverie en attente -->
        <?php if (!empty($laverieEnAttente)): ?>
        <div class="col-lg-6">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-tshirt me-2"></i>Laverie — demandes en attente</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <?php foreach ($laverieEnAttente as $d): ?>
                        <tr>
                            <td><?= htmlspecialchars($d['resident_nom']) ?><br><small class="text-muted"><?= htmlspecialchars($d['residence_nom']) ?></small></td>
                            <td><?= str_replace('_', ' ', $d['type_linge']) ?> x<?= $d['quantite'] ?></td>
                            <td><span class="badge bg-<?= $d['statut'] === 'demandee' ? 'warning text-dark' : 'info' ?>"><?= $d['statut'] ?></span></td>
                            <td class="text-muted small"><?= date('d/m', strtotime($d['date_demande'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tâches récentes -->
        <?php if (!empty($tachesRecentes)): ?>
        <div class="col-lg-<?= empty($alertesStock) && empty($laverieEnAttente) ? '12' : '6' ?>">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-history me-2"></i>Dernières tâches terminées</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm table-hover mb-0">
                        <thead><tr><th>Tâche</th><th>Section</th><th>Employé</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($tachesRecentes as $t): ?>
                        <tr>
                            <td><?= $t['numero_lot'] ? 'Lot ' . htmlspecialchars($t['numero_lot']) : htmlspecialchars($t['zone_nom'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $sectionColors[$t['type_tache']] ?? 'secondary' ?>"><?= $sectionLabels[$t['type_tache']] ?? $t['type_tache'] ?></span></td>
                            <td><?= htmlspecialchars(($t['employe_prenom'] ?? '') . ' ' . ($t['employe_nom'] ?? '')) ?></td>
                            <td class="text-muted small"><?= date('d/m H:i', strtotime($t['updated_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
