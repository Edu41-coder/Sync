<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-concierge-bell text-info me-2"></i>Accueil</h1>
            <p class="text-muted mb-0">Pilotage des résidents, réservations, animations, hôtes temporaires.</p>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune résidence ne vous est accessible. Contactez l'administrateur.
    </div>

    <?php else: ?>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md col-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary small fw-bold mb-1">Résidences</h6>
                    <h3 class="mb-0"><?= count($residences) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info small fw-bold mb-1">Résidents</h6>
                    <h3 class="mb-0"><?= (int)$stats['nb_residents'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning small fw-bold mb-1">Hôtes présents</h6>
                    <h3 class="mb-0"><?= (int)$stats['nb_hotes_presents'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md col-6">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success small fw-bold mb-1">Notes (7 j)</h6>
                    <h3 class="mb-0"><?= (int)$stats['nb_notes_recentes'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md col-6">
            <a href="<?= BASE_URL ?>/accueil/reservations?statut=en_attente" class="text-decoration-none">
                <div class="card border-start border-danger border-4 shadow-sm h-100">
                    <div class="card-body p-3 text-center">
                        <h6 class="text-danger small fw-bold mb-1">Réservations en attente</h6>
                        <h3 class="mb-0"><?= (int)($stats['nb_reservations_attente'] ?? 0) ?></h3>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <!-- Cartes accès rapide -->
    <div class="row g-3">
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/residents" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-users fa-3x text-info mb-3"></i>
                        <h5 class="mb-1">Mes résidents</h5>
                        <small class="text-muted">Liste, contact, notes texte libre</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/reservations" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-calendar-check fa-3x text-warning mb-3"></i>
                        <h5 class="mb-1">Réservations</h5>
                        <small class="text-muted">Salles, équipements, services</small>
                        <?php if (!empty($stats['nb_reservations_attente'])): ?>
                        <div class="mt-2"><span class="badge bg-danger"><?= (int)$stats['nb_reservations_attente'] ?> en attente</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/hote/index" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-suitcase-rolling fa-3x text-warning mb-3"></i>
                        <h5 class="mb-1">Hôtes temporaires</h5>
                        <small class="text-muted">Séjours courts</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/planning" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-calendar-alt fa-3x text-primary mb-3"></i>
                        <h5 class="mb-1">Planning</h5>
                        <small class="text-muted">Vue d'ensemble : animations, staff, hôtes</small>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/message/index" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-envelope fa-3x text-primary mb-3"></i>
                        <h5 class="mb-1">Messagerie</h5>
                        <small class="text-muted">Communiquer avec direction & équipe</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Salles communes -->
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/salles" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-door-open fa-3x text-info mb-3"></i>
                        <h5 class="mb-1">Salles communes</h5>
                        <small class="text-muted">Catalogue & disponibilités</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Équipements prêtables -->
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/equipements" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-toolbox fa-3x text-success mb-3"></i>
                        <h5 class="mb-1">Équipements prêtables</h5>
                        <small class="text-muted">Fauteuils, tablettes, jeux…</small>
                    </div>
                </div>
            </a>
        </div>

        <!-- Animations -->
        <div class="col-md-4 col-sm-6">
            <a href="<?= BASE_URL ?>/accueil/animations" class="text-decoration-none">
                <div class="card shadow-sm h-100 hover-shadow">
                    <div class="card-body text-center p-4">
                        <i class="fas fa-music fa-3x text-info mb-3"></i>
                        <h5 class="mb-1">Animations</h5>
                        <small class="text-muted">Programme & inscriptions</small>
                        <?php if (!empty($stats['nb_animations_semaine'])): ?>
                        <div class="mt-2"><span class="badge bg-info text-white"><?= (int)$stats['nb_animations_semaine'] ?> cette semaine</span></div>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <?php endif; ?>
</div>

<style>.hover-shadow:hover{transform:translateY(-2px);box-shadow:0 .5rem 1rem rgba(0,0,0,.15)!important;transition:all .2s}</style>
