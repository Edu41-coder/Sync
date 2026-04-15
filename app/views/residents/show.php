<?php $title = "Profil du résident"; ?>

<div class="container-fluid py-4">
    
    <?php
    // Vérifier si le résident existe
    if (!isset($resident) || !$resident):
    ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Résident introuvable
        </div>
        <a href="<?= BASE_URL ?>/resident/index" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour aux résidents
        </a>
    <?php
        return;
    endif;
    ?>
    
    <!-- Fil d'Ariane -->
    <?php
    // Fil d'Ariane contextuel
    if (isset($breadcrumbContext) && $breadcrumbContext && $breadcrumbContext['residence_id']):
        // Contexte : vient d'un lot
        $breadcrumb = [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
            ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
            ['icon' => 'fas fa-building', 'text' => $breadcrumbContext['residence_nom'], 'url' => BASE_URL . '/admin/viewResidence/' . $breadcrumbContext['residence_id']],
            ['icon' => 'fas fa-door-open', 'text' => 'Lot ' . $breadcrumbContext['numero_lot'], 'url' => BASE_URL . '/lot/show/' . $breadcrumbContext['lot_id']],
            ['icon' => 'fas fa-user', 'text' => $resident->prenom . ' ' . $resident->nom, 'url' => null]
        ];
    else:
        // Contexte normal : depuis liste résidents
        $breadcrumb = [
            ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
            ['icon' => 'fas fa-users', 'text' => 'Résidents', 'url' => BASE_URL . '/resident/index'],
            ['icon' => 'fas fa-user', 'text' => $resident->prenom . ' ' . $resident->nom, 'url' => null]
        ];
    endif;
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-user text-dark"></i>
                    <?= htmlspecialchars($resident->civilite . ' ' . $resident->prenom . ' ' . $resident->nom) ?>
                </h1>
                <p class="text-muted mb-0">
                    <i class="fas fa-birthday-cake me-1"></i>
                    <?= $resident->age ?? 'N/A' ?> ans
                    <?php if ($resident->date_naissance): ?>
                    - Né(e) le <?= date('d/m/Y', strtotime($resident->date_naissance)) ?>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <?php if (in_array($userRole, ['admin', 'exploitant'])): ?>
                <a href="<?= BASE_URL ?>/resident/edit/<?= $resident->id ?>" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Modifier
                </a>
                <?php endif; ?>
                <?php
                $backUrl = BASE_URL . '/resident/index';
                $backLabel = 'Retour';
                if (isset($_GET['from']) && $_GET['from'] === 'users' && isset($resident->user_id)) {
                    $backUrl = BASE_URL . '/admin/users/show/' . $resident->user_id;
                    $backLabel = 'Retour à l\'utilisateur';
                }
                ?>
                <a href="<?= $backUrl ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i><?= $backLabel ?>
                </a>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Colonne principale -->
        <div class="col-12 col-lg-8">
            
            <!-- Informations personnelles -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-id-card me-2"></i>
                        Informations personnelles
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-user text-dark me-2"></i>Civilité</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->civilite ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-signature text-dark me-2"></i>Nom de naissance</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->nom_naissance ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-map-marker-alt text-dark me-2"></i>Lieu de naissance</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->lieu_naissance ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-calendar-alt text-dark me-2"></i>Date d'entrée</label>
                            <div class="fw-bold">
                                <?= $resident->date_entree ? date('d/m/Y', strtotime($resident->date_entree)) : 'N/A' ?>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-phone text-dark me-2"></i>Téléphone fixe</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->telephone_fixe ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-mobile-alt text-dark me-2"></i>Téléphone mobile</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->telephone_mobile ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12">
                            <label class="text-muted small mb-1"><i class="fas fa-envelope text-dark me-2"></i>Email</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->email ?? 'N/A') ?></div>
                        </div>
                        
                        <!-- CNI -->
                        <div class="col-12">
                            <hr class="my-3">
                            <h6 class="text-muted mb-3">
                                <i class="fas fa-id-card-alt text-dark me-2"></i>
                                Carte Nationale d'Identité
                            </h6>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-hashtag text-dark me-2"></i>Numéro CNI</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->numero_cni ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-calendar text-dark me-2"></i>Date de délivrance</label>
                            <div class="fw-bold">
                                <?= $resident->date_delivrance_cni ? date('d/m/Y', strtotime($resident->date_delivrance_cni)) : 'N/A' ?>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-map-marker-alt text-dark me-2"></i>Lieu de délivrance</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->lieu_delivrance_cni ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Situation familiale -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-users me-2"></i>
                        Situation familiale
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-ring text-dark me-2"></i>Statut</label>
                            <div class="fw-bold"><?= ucfirst(str_replace('_', ' ', $resident->situation_familiale ?? 'N/A')) ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-child text-dark me-2"></i>Nombre d'enfants</label>
                            <div class="fw-bold"><?= $resident->nombre_enfants ?? 0 ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Santé et autonomie -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-heartbeat me-2"></i>
                        Santé et autonomie
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-walking text-dark me-2"></i>Niveau d'autonomie</label>
                            <div class="fw-bold">
                                <?php
                                $niveauColors = [
                                    'autonome' => 'success',
                                    'semi_autonome' => 'warning',
                                    'dependant' => 'danger',
                                    'gir1' => 'danger',
                                    'gir2' => 'danger',
                                    'gir3' => 'warning',
                                    'gir4' => 'warning',
                                    'gir5' => 'info',
                                    'gir6' => 'success'
                                ];
                                $color = $niveauColors[$resident->niveau_autonomie] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $color ?>">
                                    <?= strtoupper(str_replace('_', ' ', $resident->niveau_autonomie ?? 'N/A')) ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-hands-helping text-dark me-2"></i>Besoin d'assistance</label>
                            <div class="fw-bold">
                                <?php if ($resident->besoin_assistance): ?>
                                    <span class="badge bg-warning">Oui</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Non</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <label class="text-muted small mb-1"><i class="fas fa-allergies text-dark me-2"></i>Allergies</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->allergies ?? 'Aucune') ?></div>
                        </div>
                        
                        <div class="col-12">
                            <label class="text-muted small mb-1"><i class="fas fa-utensils text-dark me-2"></i>Régime alimentaire</label>
                            <div class="fw-bold">
                                <?= $resident->regime_alimentaire ? ucfirst(str_replace('_', ' ', $resident->regime_alimentaire)) : 'Normal' ?>
                            </div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-user-md text-dark me-2"></i>Médecin traitant</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->medecin_traitant_nom ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-phone-alt text-dark me-2"></i>Téléphone médecin</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->medecin_traitant_tel ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-id-card-alt text-dark me-2"></i>N° Sécurité sociale</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->num_securite_sociale ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-shield-alt text-dark me-2"></i>Mutuelle</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->mutuelle ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contacts d'urgence -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-phone-square-alt me-2"></i>
                        Contacts d'urgence
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-user-friends text-dark me-2"></i>Nom</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->urgence_nom ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-6">
                            <label class="text-muted small mb-1"><i class="fas fa-link text-dark me-2"></i>Lien de parenté</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->urgence_lien ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-phone text-dark me-2"></i>Téléphone 1</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->urgence_telephone ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-phone text-dark me-2"></i>Téléphone 2</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->urgence_telephone_2 ?? 'N/A') ?></div>
                        </div>
                        
                        <div class="col-12 col-md-4">
                            <label class="text-muted small mb-1"><i class="fas fa-envelope text-dark me-2"></i>Email</label>
                            <div class="fw-bold"><?= htmlspecialchars($resident->urgence_email ?? 'N/A') ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if (isset($resident->notes) && !empty($resident->notes)): ?>
            <!-- Remarques -->
            <div class="card shadow mb-4">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-sticky-note me-2"></i>
                        Remarques
                    </h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($resident->notes)) ?></p>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        
        <!-- Colonne latérale -->
        <div class="col-12 col-lg-4">
            
            <!-- Occupation actuelle -->
            <div class="card shadow mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-home me-2"></i>
                        Occupation actuelle
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($occupation) && $occupation): ?>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Résidence</label>
                            <div class="fw-bold"><?= htmlspecialchars($occupation['residence_nom']) ?></div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Lot</label>
                            <div class="fw-bold">
                                N° <?= htmlspecialchars($occupation['numero_lot']) ?>
                                <?php if (isset($occupation['lot_type'])): ?>
                                - <?= htmlspecialchars(ucfirst($occupation['lot_type'])) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <?php if (isset($occupation['surface'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Surface</label>
                            <div class="fw-bold"><?= $occupation['surface'] ?> m²</div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($occupation['nombre_pieces'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Pièces</label>
                            <div class="fw-bold"><?= $occupation['nombre_pieces'] ?> pièce(s)</div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Date d'entrée</label>
                            <div class="fw-bold">
                                <?= date('d/m/Y', strtotime($occupation['date_entree'])) ?>
                            </div>
                        </div>
                        
                        <?php if (isset($occupation['adresse'])): ?>
                        <div class="mb-3">
                            <label class="text-muted small mb-1">Adresse</label>
                            <div class="fw-bold">
                                <?= htmlspecialchars($occupation['adresse']) ?><br>
                                <?= htmlspecialchars($occupation['code_postal']) ?> 
                                <?= htmlspecialchars($occupation['ville']) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid gap-2 mt-3">
                            <a href="<?= BASE_URL ?>/occupation/show/<?= $occupation['id'] ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>Voir l'occupation
                            </a>
                            <a href="<?= BASE_URL ?>/lot/show/<?= $occupation['lot_id'] ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-door-open me-1"></i>Voir le lot
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucune occupation active
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Historique des occupations -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historique des occupations
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (isset($occupationHistory) && !empty($occupationHistory)): ?>
                        <div class="list-group">
                            <?php foreach ($occupationHistory as $hist): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1"><?= htmlspecialchars($hist['residence_nom']) ?></h6>
                                        <p class="mb-1 small text-muted">
                                            Lot n° <?= htmlspecialchars($hist['numero_lot']) ?>
                                            <?php if (isset($hist['lot_type'])): ?>
                                            (<?= htmlspecialchars(ucfirst($hist['lot_type'])) ?>)
                                            <?php endif; ?>
                                        </p>
                                        <p class="mb-0 small">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($hist['date_entree'])) ?>
                                            <?php if ($hist['date_sortie']): ?>
                                                <i class="fas fa-arrow-right mx-1"></i>
                                                <?= date('d/m/Y', strtotime($hist['date_sortie'])) ?>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                    <span class="badge bg-<?= $hist['statut'] === 'actif' ? 'success' : 'secondary' ?>">
                                        <?= ucfirst($hist['statut']) ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Aucun historique disponible
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Statut compte -->
            <div class="card shadow mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cog me-2"></i>
                        Compte utilisateur
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Statut</label>
                        <div class="fw-bold">
                            <?php if ($resident->actif): ?>
                                <span class="badge bg-success">Actif</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Inactif</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if (isset($resident->date_creation)): ?>
                    <div class="mb-3">
                        <label class="text-muted small mb-1">Date de création</label>
                        <div class="fw-bold">
                            <?= date('d/m/Y', strtotime($resident->date_creation)) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (in_array($userRole, ['admin', 'exploitant'])): ?>
                    <div class="d-grid gap-2 mt-3">
                        <?php if ($resident->actif): ?>
                        <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <i class="fas fa-user-slash me-1"></i>Désactiver
                        </button>
                        <?php else: ?>
                        <button class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal">
                            <i class="fas fa-user-check me-1"></i>Activer
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>
</div>

<!-- Modal de suppression -->
<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header <?= $resident->actif ? 'bg-danger text-white' : 'bg-success text-white' ?> border-0">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Confirmation
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-4">
                <?php if ($resident->actif): ?>
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h5>Êtes-vous sûr de vouloir désactiver ce résident ?</h5>
                    <p class="text-muted mb-0">Le résident et son compte utilisateur lié passeront en statut inactif.</p>
                <?php else: ?>
                    <i class="fas fa-user-check fa-3x text-success mb-3"></i>
                    <h5>Êtes-vous sûr de vouloir réactiver ce résident ?</h5>
                    <p class="text-muted mb-0">Le résident et son compte utilisateur lié repasseront en statut actif.</p>
                <?php endif; ?>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <form action="<?= BASE_URL ?>/resident/<?= $resident->actif ? 'delete' : 'activate' ?>/<?= $resident->id ?>" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                    <?php if ($resident->actif): ?>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-slash me-1"></i>Désactiver
                        </button>
                    <?php else: ?>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-user-check me-1"></i>Activer
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>
