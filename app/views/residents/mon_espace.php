<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-card',        'text' => 'Mon espace',      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$prenom    = $resident['prenom']   ?? '';
$nom       = $resident['nom']      ?? '';
$civilite  = $resident['civilite'] ?? '';
$initiales = strtoupper(mb_substr($prenom, 0, 1) . mb_substr($nom, 0, 1));
?>

<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex align-items-center">
            <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                 style="width:60px;height:60px;font-size:1.5rem;background:#6610f2;color:#fff">
                <?= htmlspecialchars($initiales) ?>
            </div>
            <div>
                <h1 class="h3 mb-0">
                    Bienvenue, <?= htmlspecialchars(trim($civilite . ' ' . $prenom . ' ' . $nom)) ?>
                </h1>
                <span class="badge" style="background:#6610f2">
                    <i class="fas fa-user-circle me-1"></i>Résident Senior
                </span>
            </div>
        </div>
    </div>

    <!-- Stats rapides -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-start border-primary border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary mb-1 fw-bold small">Mes lots actifs</h6>
                    <h2 class="mb-0"><?= count($occupationsActives) ?></h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-success border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success mb-1 fw-bold small">Mes résidences</h6>
                    <h2 class="mb-0"><?= (int)$nbResidences ?></h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-warning border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning mb-1 fw-bold small">Loyer mensuel</h6>
                    <h2 class="mb-0"><?= number_format($totalLoyer, 0, ',', ' ') ?> €</h2>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-start border-info border-4 shadow h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info mb-1 fw-bold small">Niveau autonomie</h6>
                    <h4 class="mb-0 text-uppercase">
                        <?= htmlspecialchars($resident['niveau_autonomie'] ?? '-') ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes d'accès rapide -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/resident/mesLots" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-door-open fa-3x text-primary mb-3"></i>
                        <h5 class="mb-1">Mes lots</h5>
                        <small class="text-muted">Voir mes appartements, caves, parkings</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/resident/mesResidences" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-building fa-3x text-info mb-3"></i>
                        <h5 class="mb-1">Résidences Domitys</h5>
                        <small class="text-muted">Découvrir le catalogue & la carte</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/resident/calendrier" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                        <h5 class="mb-1">Mon calendrier</h5>
                        <small class="text-muted">Loyers, animations, RDV médicaux…</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/residentDocument/index" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-folder-open fa-3x text-warning mb-3"></i>
                        <h5 class="mb-1">Mes documents</h5>
                        <small class="text-muted">Espace personnel 500 MB</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/resident/comptabilite" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-calculator fa-3x text-danger mb-3"></i>
                        <h5 class="mb-1">Ma comptabilité</h5>
                        <small class="text-muted">Budget mensuel & déclaration fiscale</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/message/index" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-envelope fa-3x text-secondary mb-3"></i>
                        <h5 class="mb-1">Messagerie</h5>
                        <small class="text-muted">Contacter la direction & l'équipe</small>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Mes occupations actives -->
    <?php if (!empty($occupationsActives)): ?>
    <div class="card shadow mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">
                <i class="fas fa-key me-2"></i>Mes lots actifs (<?= count($occupationsActives) ?>)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Résidence</th>
                            <th>Lot</th>
                            <th>Type</th>
                            <th class="text-end">Loyer / mois</th>
                            <th>Depuis</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($occupationsActives as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($o['residence_nom']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($o['ville'] ?? '') ?></small></td>
                            <td><?= htmlspecialchars($o['numero_lot']) ?></td>
                            <td><span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($o['lot_type']) ?></span></td>
                            <td class="text-end"><?= number_format((float)$o['loyer_mensuel_resident'], 0, ',', ' ') ?> €</td>
                            <td><?= !empty($o['date_entree']) ? date('d/m/Y', strtotime($o['date_entree'])) : '-' ?></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/occupation/show/<?= (int)$o['id'] ?>"
                                   class="btn btn-sm btn-outline-primary" title="Voir l'occupation">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/lot/show/<?= (int)$o['lot_id'] ?>"
                                   class="btn btn-sm btn-outline-secondary" title="Voir le lot">
                                    <i class="fas fa-door-open"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Aucun lot actif n'est associé à votre profil pour le moment.
    </div>
    <?php endif; ?>

</div>

<style>
.hover-shadow:hover { transform: translateY(-2px); box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important; transition: all 0.2s; }
</style>
