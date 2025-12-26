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
                    'exploitant' => 'Exploitant Domitys - Mes résidences',
                    'gestionnaire' => 'Gestionnaire - Mes copropriétés'
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

        <?php elseif ($role === 'gestionnaire'): ?>
            <!-- Stats Gestionnaire -->
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Mes Copropriétés</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['mes_coproprietees'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-building fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/copropriete" class="text-primary text-decoration-none small">
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
                                <h6 class="text-muted mb-1">Copropriétaires</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['mes_coproprietaires'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-users fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/coproprietaire" class="text-primary text-decoration-none small">
                            Liste <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-12 col-md-6 col-lg-3">
                <div class="card border shadow-sm">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="text-muted mb-1">Appels en cours</h6>
                                <h2 class="mb-0 text-dark"><?= $stats['appels_en_cours'] ?></h2>
                            </div>
                            <div>
                                <i class="fas fa-file-invoice-dollar fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/appel-fonds" class="text-primary text-decoration-none small">
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
                                <h6 class="text-muted mb-1">Impayés</h6>
                                <h2 class="mb-0 text-dark"><?= number_format($stats['total_impayes'], 0, ',', ' ') ?>€</h2>
                            </div>
                            <div>
                                <i class="fas fa-exclamation-triangle fa-3x text-dark opacity-25"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-light border-top">
                        <a href="<?= BASE_URL ?>/appel-fonds?statut=impaye" class="text-primary text-decoration-none small">
                            Relancer <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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
                    
                    <?php elseif ($role === 'gestionnaire' && !empty($recentActivities['appels_fonds'])): ?>
                        <h6 class="text-muted mb-3">Derniers appels de fonds</h6>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recentActivities['appels_fonds'] as $appel): ?>
                                <div class="list-group-item px-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?= htmlspecialchars($appel['copropriete']) ?></strong>
                                            <br>
                                            <small class="text-muted">
                                                <?= ucfirst($appel['type']) ?> - 
                                                Échéance: <?= date('d/m/Y', strtotime($appel['date_echeance'])) ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <strong class="text-primary"><?= number_format($appel['montant_total'], 2, ',', ' ') ?>€</strong>
                                            <br>
                                            <span class="badge bg-<?= $appel['statut'] === 'emis' ? 'warning' : 'success' ?>">
                                                <?= ucfirst($appel['statut']) ?>
                                            </span>
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
