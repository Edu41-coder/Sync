<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance technique', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$isManager = in_array($userRole, ['admin', 'directeur_residence', 'technicien_chef'], true);
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-hard-hat text-warning me-2"></i>Maintenance technique</h1>
            <p class="text-muted mb-0">
                <?php if ($isManager): ?>
                    Pilotage interventions, chantiers, certifications et inventaire.
                <?php else: ?>
                    Vos sections autorisées selon vos spécialités assignées.
                <?php endif; ?>
            </p>
        </div>
        <?php if ($isManager): ?>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/maintenance/specialites" class="btn btn-warning btn-sm">
                <i class="fas fa-cogs me-1"></i>Gérer les spécialités
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Alertes certifications expirantes -->
    <?php if ($isManager && !empty($certifsExpirantes)): ?>
    <div class="alert alert-warning d-flex align-items-start">
        <i class="fas fa-exclamation-triangle me-2 mt-1"></i>
        <div>
            <strong>Certifications expirant dans les 3 mois prochains :</strong>
            <ul class="mb-0 mt-1">
                <?php foreach ($certifsExpirantes as $c): ?>
                <li>
                    <?= htmlspecialchars($c['prenom'] . ' ' . $c['user_nom']) ?>
                    — <?= htmlspecialchars($c['nom']) ?>
                    <small class="text-muted">(expire le <?= date('d/m/Y', strtotime($c['date_expiration'])) ?>)</small>
                    <a href="<?= BASE_URL ?>/maintenance/certifications/<?= (int)$c['user_id'] ?>" class="ms-2 small">
                        <i class="fas fa-arrow-right"></i> Voir
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>

    <!-- Mes spécialités (technicien) ou catalogue (manager) -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-tags me-2"></i>
                        <?= $isManager ? 'Catalogue des spécialités' : 'Mes spécialités' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($userSpecialites)): ?>
                        <div class="text-center py-3 text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            <?= $isManager ? 'Aucune spécialité dans le référentiel.' : 'Aucune spécialité ne vous est assignée. Contactez votre chef technique.' ?>
                        </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($userSpecialites as $s): ?>
                        <div class="col-12 col-md-6 col-lg-4">
                            <div class="card h-100 border" style="border-left: 4px solid <?= htmlspecialchars($s['couleur']) ?> !important;">
                                <div class="card-body">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="<?= htmlspecialchars($s['icone']) ?> fa-2x me-3" style="color: <?= htmlspecialchars($s['couleur']) ?>"></i>
                                        <div>
                                            <strong><?= htmlspecialchars($s['nom']) ?></strong>
                                            <?php if (!empty($s['niveau'])): ?>
                                                <br><span class="badge bg-secondary"><?= htmlspecialchars($s['niveau']) ?></span>
                                            <?php endif; ?>
                                            <?php if (!empty($s['certif_obligatoire'])): ?>
                                                <span class="badge bg-warning text-dark" title="Certification obligatoire">
                                                    <i class="fas fa-certificate me-1"></i>certif
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="small text-muted mb-0"><?= htmlspecialchars($s['description'] ?? '') ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes accès rapide -->
    <div class="row g-3">
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/maintenance/certifications" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-certificate fa-3x text-warning mb-3"></i>
                        <h5 class="mb-1">Mes certifications</h5>
                        <small class="text-muted">Suivi de vos qualifications professionnelles</small>
                    </div>
                </div>
            </a>
        </div>
        <?php if ($isManager): ?>
        <div class="col-12 col-md-6 col-lg-4">
            <a href="<?= BASE_URL ?>/maintenance/specialites" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-users-cog fa-3x text-primary mb-3"></i>
                        <h5 class="mb-1">Affecter spécialités</h5>
                        <small class="text-muted">Matrice user × spécialité</small>
                    </div>
                </div>
            </a>
        </div>
        <?php endif; ?>
        <div class="col-12 col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 opacity-50">
                <div class="card-body text-center p-4">
                    <i class="fas fa-tools fa-3x text-secondary mb-3"></i>
                    <h5 class="mb-1">Interventions</h5>
                    <small class="text-muted">À venir (phase 2)</small>
                </div>
            </div>
        </div>
    </div>

</div>

<style>.hover-shadow:hover{transform:translateY(-2px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15)!important;transition:all .2s}</style>
