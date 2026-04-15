<?php
/**
 * Dashboard unifié - Admin / Exploitant / Gestionnaire
 */
?>

<div class="container-fluid py-4">
    
    <!-- En-tête avec rôle -->
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-2" style="font-size: 1.75rem;">
                <i class="fas fa-tachometer-alt"></i>
                Tableau de bord
            </h1>
            <p class="text-muted">
                <?php
                $roleLabels = [
                    'admin' => 'Administrateur - Vue globale',
                    'directeur_residence' => 'Directeur de résidence - Vue globale',
                    'exploitant' => 'Exploitant Domitys - Vue globale',
                    'proprietaire' => 'Propriétaire - Mon espace',
                    'comptable' => 'Comptable - Vue globale',
                ];
                echo $roleLabels[$role] ?? '';
                ?>
            </p>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <?php if ($role === 'admin'): ?>
            <!-- Stats Admin -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Résidences</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['total_residences'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-building fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/admin/residences" class="text-primary text-decoration-none small">
                            Voir toutes <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Utilisateurs</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['total_users'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-users fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/admin/users" class="text-primary text-decoration-none small">
                            Gérer <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Contrats actifs</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['total_contrats'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-file-contract fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/admin/contrats" class="text-primary text-decoration-none small">
                            Voir tous <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Résidents</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['total_residents'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-user-friends fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/resident" class="text-primary text-decoration-none small">
                            Liste complète <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

        <?php elseif ($role === 'exploitant'): ?>
            <!-- Stats Exploitant -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Mes Résidences</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['mes_residences'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-building fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/exploitant/residences" class="text-primary text-decoration-none small">
                            Voir la carte <i class="fas fa-map-marked-alt"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Résidents actifs</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['mes_residents'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-user-friends fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/resident" class="text-primary text-decoration-none small">
                            Gérer <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Occupations</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['occupations_actives'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-key fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/occupation" class="text-primary text-decoration-none small">
                            Détails <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Marge ce mois</h6>
                                <h2 class="mb-0 text-dark"><?= number_format($stats['marge_mois'], 0, ',', ' ') ?>€</h2>
                            </div>
                            <div>
                                <i class="fas fa-euro-sign fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <small class="text-muted">
                            Revenus: <?= number_format($stats['revenus_residents_mois'], 0, ',', ' ') ?>€ - 
                            Charges: <?= number_format($stats['paiements_proprietaires_mois'], 0, ',', ' ') ?>€
                        </small>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <?php if ($role === 'proprietaire'): ?>
        <!-- Stats propriétaire -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Mes lots</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['total_lots'] ?? 0 ?></h2>
                            </div>
                            <i class="fas fa-door-open fa-3x text-dark opacity-25"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/coproprietaire/mesLots" class="text-primary text-decoration-none small">
                            Voir mes lots <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Contrats actifs</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['contrats_actifs'] ?? 0 ?></h2>
                            </div>
                            <i class="fas fa-file-contract fa-3x text-dark opacity-25"></i>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/admin/contrats" class="text-primary text-decoration-none small">
                            Voir contrats <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Revenus / mois</h6>
                                <h2 class="mb-0 text-success"><?= number_format($stats['revenus_mensuels'] ?? 0, 0, ',', ' ') ?> €</h2>
                            </div>
                            <i class="fas fa-euro-sign fa-3x text-dark opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Revenus / an</h6>
                                <h2 class="mb-0 text-primary"><?= number_format($stats['revenus_annuels'] ?? 0, 0, ',', ' ') ?> €</h2>
                            </div>
                            <i class="fas fa-chart-line fa-3x text-dark opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($role === 'proprietaire' && !empty($propResidences)): ?>
    <!-- Mes Résidences (propriétaire) -->
    <div class="card shadow mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-building me-2"></i>Mes Résidences</h5>
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
                        <?php foreach ($propResidences as $r): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($r['nom']) ?></strong></td>
                            <td><?= htmlspecialchars($r['ville'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($r['exploitant'] ?? 'Domitys') ?></td>
                            <td class="text-center"><span class="badge bg-primary"><?= $r['mes_lots'] ?></span></td>
                            <td class="text-center">
                                <?php if (!empty($r['latitude'])): ?>
                                <a href="<?= BASE_URL ?>/admin/carteResidence/<?= $r['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-map-marker-alt"></i></a>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($role === 'proprietaire' && !empty($propContrats)): ?>
    <!-- Mes Lots & Contrats (propriétaire) -->
    <div class="card shadow mb-4">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0"><i class="fas fa-file-contract me-2"></i>Mes Lots & Contrats</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Contrat</th>
                            <th>Résidence / Lot</th>
                            <th class="text-end">Loyer garanti</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($propContrats as $c):
                            $sc = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning','projet'=>'info'];
                        ?>
                        <tr class="<?= $c['statut'] !== 'actif' ? 'table-secondary' : '' ?>">
                            <td><strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong></td>
                            <td>
                                <?= htmlspecialchars($c['residence_nom'] ?? '-') ?>
                                <br><small class="text-muted">Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?> (<?= $c['lot_type'] ?? '' ?><?= $c['surface'] ? ', ' . $c['surface'] . ' m²' : '' ?>)</small>
                            </td>
                            <td class="text-end fw-bold text-success"><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €</td>
                            <td class="text-center"><span class="badge bg-<?= $sc[$c['statut']] ?? 'secondary' ?>"><?= ucfirst($c['statut']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Accès rapides -->
    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-bolt text-warning me-2"></i>Accès rapides</h5>
                </div>
                <div class="card-body">
                    <div class="row g-2">

                        <?php if ($role === 'admin'): ?>
                        <!-- ADMIN -->
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/users/create" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-user-plus fa-2x mb-2 d-block"></i>
                                <small>Nouvel utilisateur</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/createResidence" class="btn btn-outline-danger w-100 py-3">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                <small>Nouvelle résidence</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/hote/create" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-calendar-plus fa-2x mb-2 d-block"></i>
                                <small>Nouvelle réservation</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/planning/index" class="btn btn-outline-dark w-100 py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i>
                                <small>Planning staff</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/resident/index" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                <small>Résidents seniors</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                <small>Résidences</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/contrats" class="btn btn-outline-dark w-100 py-3">
                                <i class="fas fa-file-contract fa-2x mb-2 d-block"></i>
                                <small>Contrats</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/services" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-concierge-bell fa-2x mb-2 d-block"></i>
                                <small>Services</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/migrate" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-database fa-2x mb-2 d-block"></i>
                                <small>Migrations DB</small>
                            </a>
                        </div>

                        <?php elseif (in_array($role, ['directeur_residence', 'employe_residence'])): ?>
                        <!-- STAFF RÉSIDENCE -->
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/hote/create" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-calendar-plus fa-2x mb-2 d-block"></i>
                                <small>Nouvelle réservation</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/hote/index" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-calendar-check fa-2x mb-2 d-block"></i>
                                <small>Hôtes temporaires</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/resident/index" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-users fa-2x mb-2 d-block"></i>
                                <small>Résidents seniors</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                <small>Résidences</small>
                            </a>
                        </div>

                        <?php elseif ($role === 'comptable'): ?>
                        <!-- COMPTABLE -->
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/contrats" class="btn btn-outline-dark w-100 py-3">
                                <i class="fas fa-file-contract fa-2x mb-2 d-block"></i>
                                <small>Contrats</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/planning/index" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i>
                                <small>Planning staff</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/index" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-home fa-2x mb-2 d-block"></i>
                                <small>Propriétaires</small>
                            </a>
                        </div>

                        <?php elseif ($role === 'proprietaire'): ?>
                        <!-- PROPRIÉTAIRE -->
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/monEspace" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-id-card fa-2x mb-2 d-block"></i>
                                <small>Mon Profil</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/mesLots" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-door-open fa-2x mb-2 d-block"></i>
                                <small>Mes Lots</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/mesResidences" class="btn btn-outline-danger w-100 py-3">
                                <i class="fas fa-building fa-2x mb-2 d-block"></i>
                                <small>Mes Résidences</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-outline-secondary w-100 py-3">
                                <i class="fas fa-list fa-2x mb-2 d-block"></i>
                                <small>Toutes les résidences</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/comptabilite" class="btn btn-outline-success w-100 py-3">
                                <i class="fas fa-calculator fa-2x mb-2 d-block"></i>
                                <small>Comptabilité</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/declarationFiscale" class="btn btn-outline-primary w-100 py-3">
                                <i class="fas fa-file-invoice fa-2x mb-2 d-block"></i>
                                <small>Déclaration fiscale</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/coproprietaire/calendrier" class="btn btn-outline-warning w-100 py-3">
                                <i class="fas fa-calendar-alt fa-2x mb-2 d-block"></i>
                                <small>Mon Calendrier</small>
                            </a>
                        </div>
                        <div class="col-6 col-md-3">
                            <a href="<?= BASE_URL ?>/admin/carteResidences" class="btn btn-outline-info w-100 py-3">
                                <i class="fas fa-map-marked-alt fa-2x mb-2 d-block"></i>
                                <small>Carte résidences</small>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Graphiques et activités récentes -->
    <div class="row g-3">
        
        <!-- Activités récentes -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-clock"></i>
                            Activités récentes
                        </h5>
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="collapse" data-bs-target="#collapseActivites" aria-expanded="false" aria-controls="collapseActivites">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="collapseActivites">
                    <div class="card-body">
                    <?php if ($role === 'admin' && !empty($recentActivities['contrats'])): ?>
                        <h6 class="text-muted mb-3">Derniers contrats</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities['contrats'] as $contrat): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($contrat['numero_contrat']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($contrat['proprietaire']) ?> - 
                                                <?= htmlspecialchars($contrat['residence']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-primary"><?= number_format($contrat['loyer_mensuel_garanti'], 0, ',', ' ') ?>€</strong>
                                            <br>
                                            <span class="badge bg-<?= $contrat['statut'] === 'actif' ? 'success' : 'secondary' ?>">
                                                <?= ucfirst($contrat['statut']) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    
                    <?php elseif ($role === 'exploitant' && !empty($recentActivities['occupations'])): ?>
                        <h6 class="text-muted mb-3">Dernières occupations</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities['occupations'] as $occupation): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($occupation['resident']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= htmlspecialchars($occupation['residence']) ?> - 
                                                Lot <?= htmlspecialchars($occupation['numero_lot']) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-success"><?= number_format($occupation['loyer_mensuel_resident'], 0, ',', ' ') ?>€/mois</strong>
                                            <br>
                                            <small class="text-muted">Depuis le <?= date('d/m/Y', strtotime($occupation['date_entree'])) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    
                    <?php else: ?>
                        <p class="text-muted mb-0">Aucune activité récente</p>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alertes / Paiements en attente -->
        <div class="col-12 col-lg-6">
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-circle"></i>
                            <?= $role === 'exploitant' ? 'Paiements en attente' : 'Alertes' ?>
                        </h5>
                        <button class="btn btn-sm btn-dark" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAlertes" aria-expanded="false" aria-controls="collapseAlertes">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="collapse" id="collapseAlertes">
                    <div class="card-body">
                    <?php if ($role === 'exploitant' && !empty($recentActivities['paiements'])): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities['paiements'] as $paiement): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($paiement['proprietaire']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= date('F Y', mktime(0, 0, 0, $paiement['mois'], 1, $paiement['annee'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-danger"><?= number_format($paiement['montant_total'], 2, ',', ' ') ?>€</strong>
                                            <br>
                                            <small class="text-muted">
                                                <?php
                                                $echeance = strtotime($paiement['date_echeance']);
                                                $today = time();
                                                $diff = floor(($echeance - $today) / 86400);
                                                if ($diff < 0) {
                                                    echo '<span class="text-danger">En retard de ' . abs($diff) . ' jours</span>';
                                                } else {
                                                    echo 'Échéance: ' . date('d/m/Y', $echeance);
                                                }
                                                ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="<?= BASE_URL ?>/exploitant/paiements" class="btn btn-warning btn-sm w-100">
                                <i class="fas fa-list"></i> Voir tous les paiements
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0">
                            <i class="fas fa-check-circle"></i>
                            Aucune alerte en cours
                        </div>
                    <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>

</div>

<!-- Script pour animer l'icône du collapse -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Collapse Activités
    const collapseActivites = document.getElementById('collapseActivites');
    const toggleBtnActivites = collapseActivites.previousElementSibling.querySelector('button i');
    
    collapseActivites.addEventListener('show.bs.collapse', function () {
        toggleBtnActivites.classList.remove('fa-chevron-down');
        toggleBtnActivites.classList.add('fa-chevron-up');
    });
    
    collapseActivites.addEventListener('hide.bs.collapse', function () {
        toggleBtnActivites.classList.remove('fa-chevron-up');
        toggleBtnActivites.classList.add('fa-chevron-down');
    });
    
    // Collapse Alertes/Paiements
    const collapseAlertes = document.getElementById('collapseAlertes');
    const toggleBtnAlertes = collapseAlertes.previousElementSibling.querySelector('button i');
    
    collapseAlertes.addEventListener('show.bs.collapse', function () {
        toggleBtnAlertes.classList.remove('fa-chevron-down');
        toggleBtnAlertes.classList.add('fa-chevron-up');
    });
    
    collapseAlertes.addEventListener('hide.bs.collapse', function () {
        toggleBtnAlertes.classList.remove('fa-chevron-up');
        toggleBtnAlertes.classList.add('fa-chevron-down');
    });
});
</script>
