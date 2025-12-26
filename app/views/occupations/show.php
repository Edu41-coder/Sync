<?php $title = "Détails de l'occupation"; ?>

<div class="container-fluid py-4">
    
    <?php
    // Vérifier si l'occupation existe
    if (!isset($occupation) || !$occupation):
    ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Occupation introuvable
        </div>
        <a href="<?= BASE_URL ?>/occupation/index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour aux occupations
        </a>
    <?php
        return;
    endif;
    ?>
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-building', 'text' => $occupation['residence_nom'], 'url' => BASE_URL . '/admin/viewResidence/' . $occupation['residence_id']],
        ['icon' => 'fas fa-users', 'text' => 'Occupation', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-user-check text-dark"></i>
                    Occupation - <?= htmlspecialchars($occupation['resident_nom']) ?>
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-door-open me-1"></i>
                    Lot <?= htmlspecialchars($occupation['numero_lot']) ?> - 
                    <?= htmlspecialchars($occupation['residence_nom']) ?>
                </p>
            </div>
            <div>
                <?php if (in_array($userRole, ['admin', 'gestionnaire', 'exploitant'])): ?>
                <a href="<?= BASE_URL ?>/occupation/edit/<?= $occupation['id'] ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/lot/show/<?= $occupation['lot_id'] ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i>Retour au lot
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Informations principales -->
        <div class="col-12 col-lg-8">
            <!-- Statut occupation -->
            <div class="card shadow mb-4">
                <div class="card-header bg-<?= $occupation['statut'] === 'actif' ? 'success' : ($occupation['statut'] === 'preavis' ? 'warning' : 'secondary') ?> text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Statut de l'occupation
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Statut</label>
                            <div class="fw-bold fs-5">
                                <?php
                                $statutColors = [
                                    'actif' => 'success',
                                    'preavis' => 'warning',
                                    'termine' => 'secondary'
                                ];
                                $statutIcons = [
                                    'actif' => 'check-circle',
                                    'preavis' => 'exclamation-triangle',
                                    'termine' => 'times-circle'
                                ];
                                $color = $statutColors[$occupation['statut']] ?? 'secondary';
                                $icon = $statutIcons[$occupation['statut']] ?? 'circle';
                                ?>
                                <span class="badge bg-<?= $color ?> fs-6">
                                    <i class="fas fa-<?= $icon ?> me-1"></i>
                                    <?= ucfirst($occupation['statut']) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Type de séjour</label>
                            <div class="fw-bold">
                                <i class="fas fa-calendar-alt text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($occupation['type_sejour'] ?? 'permanent') ?></span>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Date d'entrée</label>
                            <div class="fw-bold">
                                <i class="fas fa-sign-in-alt text-success me-2"></i>
                                <?= date('d/m/Y', strtotime($occupation['date_entree'])) ?>
                            </div>
                        </div>
                        
                        <?php if (!empty($occupation['date_sortie'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Date de sortie</label>
                            <div class="fw-bold">
                                <i class="fas fa-sign-out-alt text-danger me-2"></i>
                                <?= date('d/m/Y', strtotime($occupation['date_sortie'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['duree_prevue_mois'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Durée prévue</label>
                            <div class="fw-bold">
                                <i class="fas fa-hourglass-half text-dark me-2"></i>
                                <?= $occupation['duree_prevue_mois'] ?> mois
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Informations financières -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-euro-sign me-2"></i>
                        Informations financières
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Loyer mensuel</label>
                            <div class="fw-bold fs-5 text-success">
                                <i class="fas fa-home me-2"></i>
                                <?= number_format($occupation['loyer_mensuel_resident'], 2, ',', ' ') ?> €
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Charges mensuelles</label>
                            <div class="fw-bold fs-5">
                                <i class="fas fa-file-invoice-dollar text-dark me-2"></i>
                                <?= number_format($occupation['charges_mensuelles_resident'] ?? 0, 2, ',', ' ') ?> €
                            </div>
                        </div>
                        
                        <?php if (!empty($occupation['montant_services_sup'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Services supplémentaires</label>
                            <div class="fw-bold">
                                <i class="fas fa-plus-circle text-dark me-2"></i>
                                <?= number_format($occupation['montant_services_sup'], 2, ',', ' ') ?> €
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Total mensuel</label>
                            <div class="fw-bold fs-4 text-primary">
                                <i class="fas fa-calculator me-2"></i>
                                <?= number_format($totalMensuel, 2, ',', ' ') ?> €
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Type de forfait</label>
                            <div class="fw-bold">
                                <i class="fas fa-tag text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($occupation['forfait_type'] ?? 'essentiel') ?></span>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Mode de paiement</label>
                            <div class="fw-bold">
                                <i class="fas fa-credit-card text-dark me-2"></i>
                                <span class="text-capitalize"><?= htmlspecialchars($occupation['mode_paiement'] ?? 'prelevement') ?></span>
                            </div>
                        </div>
                        
                        <?php if (!empty($occupation['jour_prelevement'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Jour de prélèvement</label>
                            <div class="fw-bold">
                                <i class="fas fa-calendar-day text-dark me-2"></i>
                                Le <?= $occupation['jour_prelevement'] ?> du mois
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['depot_garantie'])): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">Dépôt de garantie</label>
                            <div class="fw-bold">
                                <i class="fas fa-shield-alt text-dark me-2"></i>
                                <?= number_format($occupation['depot_garantie'], 2, ',', ' ') ?> €
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <?php if (!empty($occupation['services_inclus'])): ?>
                    <hr class="my-3">
                    <div>
                        <label class="text-muted small mb-2">Services inclus</label>
                        <?php
                        $servicesInclus = json_decode($occupation['services_inclus'], true);
                        if ($servicesInclus && is_array($servicesInclus)):
                            $servicesLabels = [
                                'wifi' => 'Wi-Fi',
                                'telephone' => 'Téléphone',
                                'animations' => 'Animations',
                                'assistance_24h' => 'Assistance 24h/24',
                                'entretien_espaces_communs' => 'Entretien des espaces communs',
                                'restaurant_midi' => 'Restaurant midi',
                                'restaurant_soir' => 'Restaurant soir',
                                'menage_hebdomadaire' => 'Ménage hebdomadaire',
                                'blanchisserie' => 'Blanchisserie',
                                'coiffeur' => 'Coiffeur',
                                'podologue' => 'Podologue',
                                'kine' => 'Kinésithérapeute'
                            ];
                        ?>
                        <div class="row g-2">
                            <?php foreach ($servicesInclus as $key => $value): ?>
                                <?php if ($value): ?>
                                <div class="col-12 col-md-6">
                                    <span class="badge bg-info">
                                        <i class="fas fa-check-circle me-1"></i>
                                        <?= htmlspecialchars($servicesLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= nl2br(htmlspecialchars($occupation['services_inclus'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($occupation['services_supplementaires'])): ?>
                    <hr class="my-3">
                    <div>
                        <label class="text-muted small mb-2">Services supplémentaires</label>
                        <?php
                        $servicesSup = json_decode($occupation['services_supplementaires'], true);
                        if ($servicesSup && is_array($servicesSup)):
                            $servicesLabels = [
                                'coiffure' => 'Coiffure',
                                'podologie' => 'Podologie',
                                'kinesitherapie' => 'Kinésithérapie',
                                'menage_supplementaire' => 'Ménage supplémentaire',
                                'blanchisserie_sup' => 'Blanchisserie supplémentaire',
                                'repas_supplementaires' => 'Repas supplémentaires',
                                'sorties' => 'Sorties et excursions',
                                'accompagnement_medical' => 'Accompagnement médical'
                            ];
                        ?>
                        <div class="row g-2">
                            <?php foreach ($servicesSup as $key => $value): ?>
                                <?php if ($value): ?>
                                <div class="col-12 col-md-6">
                                    <span class="badge bg-warning text-dark">
                                        <i class="fas fa-plus-circle me-1"></i>
                                        <?= htmlspecialchars($servicesLabels[$key] ?? ucfirst(str_replace('_', ' ', $key))) ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            <?= nl2br(htmlspecialchars($occupation['services_supplementaires'])) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Aides sociales -->
            <?php if ($occupation['beneficie_apl'] || $occupation['beneficie_apa']): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-hands-helping me-2"></i>
                        Aides sociales
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php if ($occupation['beneficie_apl']): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">APL (Aide Personnalisée au Logement)</label>
                            <div class="fw-bold fs-5 text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= number_format($occupation['montant_apl'] ?? 0, 2, ',', ' ') ?> €/mois
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($occupation['beneficie_apa']): ?>
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1">APA (Allocation Personnalisée d'Autonomie)</label>
                            <div class="fw-bold fs-5 text-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <?= number_format($occupation['montant_apa'] ?? 0, 2, ',', ' ') ?> €/mois
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Notes -->
            <?php if (!empty($occupation['notes'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-sticky-note text-dark me-2"></i>
                        Notes
                    </h6>
                </div>
                <div class="card-body">
                    <?= nl2br(htmlspecialchars($occupation['notes'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Carte résident -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-user text-dark me-2"></i>
                        Résident
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">
                        <?= htmlspecialchars($occupation['resident_civilite'] ?? '') ?> 
                        <?= htmlspecialchars($occupation['resident_nom']) ?>
                    </h5>
                    <div class="small">
                        <?php if (!empty($occupation['age'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-birthday-cake text-dark me-2"></i>
                            <?= $occupation['age'] ?> ans
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['resident_telephone'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-phone text-dark me-2"></i>
                            <a href="tel:<?= $occupation['resident_telephone'] ?>"><?= htmlspecialchars($occupation['resident_telephone']) ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['resident_email'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-envelope text-dark me-2"></i>
                            <a href="mailto:<?= $occupation['resident_email'] ?>"><?= htmlspecialchars($occupation['resident_email']) ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['niveau_autonomie'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-heartbeat text-dark me-2"></i>
                            <span class="text-capitalize"><?= htmlspecialchars($occupation['niveau_autonomie']) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/resident/show/<?= $occupation['resident_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">
                        <i class="fas fa-arrow-right me-1"></i>Voir le profil
                    </a>
                </div>
            </div>
            
            <!-- Carte lot -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-door-open text-dark me-2"></i>
                        Lot
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3">Lot <?= htmlspecialchars($occupation['numero_lot']) ?></h5>
                    <div class="small">
                        <div class="mb-2">
                            <i class="fas fa-home text-dark me-2"></i>
                            Type: <span class="text-capitalize"><?= htmlspecialchars($occupation['lot_type']) ?></span>
                        </div>
                        
                        <?php if (!empty($occupation['surface'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-ruler-combined text-dark me-2"></i>
                            Surface: <?= $occupation['surface'] ?> m²
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['nombre_pieces'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-door-closed text-dark me-2"></i>
                            <?= $occupation['nombre_pieces'] ?> pièce(s)
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($occupation['etage'] !== null): ?>
                        <div class="mb-2">
                            <i class="fas fa-layer-group text-dark me-2"></i>
                            Étage: <?php
                            if ($occupation['etage'] == 0) {
                                echo 'RDC';
                            } elseif ($occupation['etage'] < 0) {
                                echo 'Sous-sol ' . abs($occupation['etage']);
                            } else {
                                echo $occupation['etage'];
                            }
                            ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <a href="<?= BASE_URL ?>/lot/show/<?= $occupation['lot_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">
                        <i class="fas fa-arrow-right me-1"></i>Voir le lot
                    </a>
                </div>
            </div>
            
            <!-- Carte résidence -->
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-building text-dark me-2"></i>
                        Résidence
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3"><?= htmlspecialchars($occupation['residence_nom']) ?></h5>
                    <div class="small">
                        <div class="mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <?= htmlspecialchars($occupation['adresse']) ?><br>
                            <?= htmlspecialchars($occupation['code_postal']) ?> <?= htmlspecialchars($occupation['ville']) ?>
                        </div>
                    </div>
                    <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $occupation['residence_id'] ?>" class="btn btn-outline-primary btn-sm w-100 mt-3">
                        <i class="fas fa-arrow-right me-1"></i>Voir la résidence
                    </a>
                </div>
            </div>
            
            <!-- Carte exploitant -->
            <?php if (!empty($occupation['exploitant_nom'])): ?>
            <div class="card shadow mb-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0">
                        <i class="fas fa-building text-dark me-2"></i>
                        Exploitant
                    </h6>
                </div>
                <div class="card-body">
                    <h5 class="mb-3"><?= htmlspecialchars($occupation['exploitant_nom']) ?></h5>
                    <div class="small">
                        <?php if (!empty($occupation['exploitant_telephone'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-phone text-dark me-2"></i>
                            <a href="tel:<?= $occupation['exploitant_telephone'] ?>"><?= htmlspecialchars($occupation['exploitant_telephone']) ?></a>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($occupation['exploitant_email'])): ?>
                        <div class="mb-2">
                            <i class="fas fa-envelope text-dark me-2"></i>
                            <a href="mailto:<?= $occupation['exploitant_email'] ?>"><?= htmlspecialchars($occupation['exploitant_email']) ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
