<nav class="navbar navbar-expand-lg navbar-dark bg-primary shadow-sm">
    <div class="container-fluid">
        <!-- Logo et nom de l'application -->
        <span class="navbar-brand d-flex align-items-center">
            <img src="<?= BASE_URL ?>/assets/images/domitys-logo.svg" alt="Domitys Logo" class="navbar-logo me-2" style="height: 35px; filter: brightness(0) invert(1);">
            <strong><?php echo APP_NAME; ?></strong>
        </span>
        
        <!-- Bouton toggle pour mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" 
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Menu de navigation -->
        <div class="collapse navbar-collapse" id="navbarNav">
            <!-- Menu principal (gauche) -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>">
                        <i class="fas fa-home me-1 text-white"></i> Tableau de bord
                    </a>
                </li>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'proprietaire'): ?>
                <!-- Propriétaire : menus spécifiques -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navPropResidences" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-building me-1 text-info"></i> Résidences
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navPropResidences">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/mesLots">
                            <i class="fas fa-door-open me-2 text-primary"></i> Mes lots
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/mesResidences">
                            <i class="fas fa-star me-2 text-warning"></i> Mes résidences
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/residences">
                            <i class="fas fa-list me-2 text-info"></i> Toutes les résidences
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/carteResidences">
                            <i class="fas fa-map-marked-alt me-2 text-success"></i> Carte
                        </a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/coproprietaire/calendrier">
                        <i class="fas fa-calendar-alt me-1 text-info"></i> Calendrier
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/coproprietaireDocument/index">
                        <i class="fas fa-folder-open me-1 text-warning"></i> Mes Documents
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navPropCompta" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calculator me-1 text-success"></i> Comptabilité
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navPropCompta">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/comptabilite">
                            <i class="fas fa-chart-line me-2 text-success"></i> Vue d'ensemble
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/declarationFiscale">
                            <i class="fas fa-file-invoice me-2 text-primary"></i> Déclaration fiscale
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'directeur_residence'])): ?>

                <!-- Menu Copropriétés -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navCoproprietes" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-building me-1 text-info"></i> Copropriétés
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navCoproprietes">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/residences">
                            <i class="fas fa-list me-2 text-info"></i> Liste
                        </a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/createResidence">
                            <i class="fas fa-plus me-2 text-success"></i> Nouvelle copropriété
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <!-- Menu Lots -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navLots" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-door-open me-1 text-warning"></i> Lots
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navLots">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/lot/index">
                            <i class="fas fa-list me-2 text-info"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/lot/create">
                            <i class="fas fa-plus me-2 text-success"></i> Nouveau lot
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Propriétaires -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navCoproprietaires" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-home me-1 text-success"></i> Propriétaires
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navCoproprietaires">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/index">
                            <i class="fas fa-list me-2 text-info"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/coproprietaire/create">
                            <i class="fas fa-user-plus me-2 text-success"></i> Nouveau propriétaire
                        </a></li>
                    </ul>
                </li>

                <!-- Menu Fournisseurs (global, admin + directeur_residence) -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navFournisseurs" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-truck-loading me-1 text-primary"></i> Fournisseurs
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navFournisseurs">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/fournisseur/index">
                            <i class="fas fa-list me-2 text-info"></i> Liste
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/fournisseur/create">
                            <i class="fas fa-plus me-2 text-success"></i> Nouveau fournisseur
                        </a></li>
                    </ul>
                </li>

                <!-- Menu Comptabilité -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navComptabilite" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-calculator me-1 text-success"></i> Comptabilité
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navComptabilite">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/charge/index">
                            <i class="fas fa-file-invoice-dollar me-2 text-warning"></i> Appels de fonds
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/comptabilite/ecritures">
                            <i class="fas fa-book me-2 text-primary"></i> Écritures
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/comptabilite/balance">
                            <i class="fas fa-chart-bar me-2 text-success"></i> Balance
                        </a></li>
                    </ul>
                </li>
                
                <!-- Menu Travaux -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/travaux/index">
                        <i class="fas fa-tools me-1 text-warning"></i> Travaux
                    </a>
                </li>
                
                <!-- Menu Documents -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/document/index">
                        <i class="fas fa-folder me-1 text-warning"></i> Documents
                    </a>
                </li>
                
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'employe_residence'])): ?>
                <!-- Menu Hôtes Temporaires -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/hote/index">
                        <i class="fas fa-calendar-check me-1 text-success"></i> Hôtes
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'comptable'])): ?>
                <!-- Planning Staff -->
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/planning/index">
                        <i class="fas fa-calendar-alt me-1 text-info"></i> Planning
                    </a>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'restauration_manager', 'restauration_serveur', 'restauration_cuisine'])): ?>
                <!-- Menu Restauration -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-utensils me-1 text-warning"></i> Restauration
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/index">
                            <i class="fas fa-tachometer-alt me-2 text-info"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/residents">
                            <i class="fas fa-users me-2 text-primary"></i> Résidents
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/planning">
                            <i class="fas fa-calendar-alt me-2 text-info"></i> Planning
                        </a></li>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'restauration_manager'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/equipe">
                            <i class="fas fa-user-friends me-2 text-primary"></i> Équipe
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/plats">
                            <i class="fas fa-book-open me-2 text-warning"></i> Plats
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/menus">
                            <i class="fas fa-clipboard-list me-2 text-success"></i> Menus
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/produits">
                            <i class="fas fa-box me-2 text-warning"></i> Produits
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/inventaire">
                            <i class="fas fa-boxes-stacked me-2 text-info"></i> Inventaire
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/commandes">
                            <i class="fas fa-truck me-2 text-success"></i> Commandes
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/fournisseurs">
                            <i class="fas fa-truck-loading me-2 text-secondary"></i> Fournisseurs
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/comptabilite">
                            <i class="fas fa-calculator me-2 text-success"></i> Comptabilité
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'restauration_manager', 'restauration_cuisine'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/laverie">
                            <i class="fas fa-soap me-2 text-info"></i> Laverie
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'restauration_manager', 'restauration_serveur'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/restauration/service">
                            <i class="fas fa-cash-register me-2 text-danger"></i> Service / Facturer
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager', 'menage_interieur', 'menage_exterieur', 'employe_laverie'])): ?>
                <!-- Menu Ménage -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-broom me-1 text-info"></i> Ménage
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/index">
                            <i class="fas fa-tachometer-alt me-2 text-info"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/planning">
                            <i class="fas fa-calendar-alt me-2 text-info"></i> Planning
                        </a></li>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager', 'menage_interieur'])): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/interieur">
                            <i class="fas fa-bed me-2 text-primary"></i> Intérieur
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager', 'menage_exterieur'])): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/exterieur">
                            <i class="fas fa-tree me-2 text-success"></i> Extérieur
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager', 'employe_laverie'])): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/laverie">
                            <i class="fas fa-tshirt me-2 text-info"></i> Laverie
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/equipe">
                            <i class="fas fa-user-friends me-2 text-primary"></i> Équipe
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/zones">
                            <i class="fas fa-map-marked me-2 text-success"></i> Zones extérieures
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/produits">
                            <i class="fas fa-box me-2 text-warning"></i> Produits
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/inventaire">
                            <i class="fas fa-boxes-stacked me-2 text-info"></i> Inventaire
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/commandes">
                            <i class="fas fa-truck me-2 text-success"></i> Commandes
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/fournisseurs">
                            <i class="fas fa-truck-loading me-2 text-secondary"></i> Fournisseurs
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/menage/comptabilite">
                            <i class="fas fa-calculator me-2 text-success"></i> Comptabilité
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'jardinier_manager', 'jardinier_employe'])): ?>
                <!-- Menu Jardinage -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-seedling me-1 text-success"></i> Jardinage
                    </a>
                    <ul class="dropdown-menu dropdown-menu-dark">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/index">
                            <i class="fas fa-tachometer-alt me-2 text-info"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/planning">
                            <i class="fas fa-calendar-alt me-2 text-info"></i> Planning
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/espaces">
                            <i class="fas fa-tree me-2 text-success"></i> Espaces jardin
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/inventaire">
                            <i class="fas fa-boxes-stacked me-2 text-info"></i> Inventaire
                        </a></li>
                        <?php
                        // Item Ruches visible uniquement si user a au moins 1 résidence avec ruches=1
                        $showRuches = false;
                        try {
                            $rPdo = Database::getInstance()->getConnection();
                            if ($_SESSION['user_role'] === 'admin') {
                                $showRuches = (bool)$rPdo->query("SELECT 1 FROM coproprietees WHERE ruches = 1 AND actif = 1 LIMIT 1")->fetchColumn();
                            } else {
                                $rStmt = $rPdo->prepare("SELECT 1 FROM user_residence ur JOIN coproprietees c ON ur.residence_id=c.id WHERE ur.user_id=? AND ur.statut='actif' AND c.ruches=1 AND c.actif=1 LIMIT 1");
                                $rStmt->execute([$_SESSION['user_id']]);
                                $showRuches = (bool)$rStmt->fetchColumn();
                            }
                        } catch (Exception $e) {}
                        ?>
                        <?php if ($showRuches): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/ruches">
                            🐝 <span class="ms-1">Ruches</span>
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/apiculture">
                            <i class="fas fa-cog me-2"></i>Config apiculture
                        </a></li>
                        <?php endif; ?>
                        <?php if (in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'jardinier_manager'])): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/produits">
                            <i class="fas fa-book-open me-2 text-warning"></i> Catalogue produits & outils
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/fournisseurs">
                            <i class="fas fa-truck-loading me-2 text-success"></i> Fournisseurs
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/commandes">
                            <i class="fas fa-truck me-2 text-info"></i> Commandes
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/comptabilite">
                            <i class="fas fa-calculator me-2 text-success"></i> Comptabilité
                        </a></li>
                        <?php if ($showRuches): ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/traitements">
                            <i class="fas fa-shield-virus me-2 text-warning"></i> Traitements apicoles
                        </a></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/jardinage/equipe">
                            <i class="fas fa-user-friends me-2 text-primary"></i> Équipe
                        </a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                <!-- Menu Administration -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navAdmin" role="button"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-cogs me-1 text-secondary"></i> Administration
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="navAdmin">
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/users">
                            <i class="fas fa-users me-2 text-primary"></i> Utilisateurs
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/services">
                            <i class="fas fa-concierge-bell me-2 text-warning"></i> Services
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/contrats">
                            <i class="fas fa-file-contract me-2 text-primary"></i> Contrats de gestion
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/carteResidences">
                            <i class="fas fa-map-marked-alt me-2 text-success"></i> Carte des résidences
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/logs">
                            <i class="fas fa-shield-alt me-2 text-danger"></i> Logs de sécurité
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/migrate">
                            <i class="fas fa-database me-2 text-info"></i> Migrations DB
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>

                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'exploitant'): ?>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo BASE_URL; ?>/admin/residences">
                        <i class="fas fa-building me-1 text-info"></i> Mes résidences
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Menu utilisateur (droite) -->
            <ul class="navbar-nav ms-auto">
                <!-- Messagerie -->
                <li class="nav-item">
                    <a class="nav-link position-relative" href="<?php echo BASE_URL; ?>/message/index" title="Messagerie">
                        <i class="fas fa-envelope text-info"></i>
                        <span class="badge rounded-pill bg-danger d-none" id="msgBadge" style="font-size:0.55rem;position:absolute;top:2px;right:0px"></span>
                    </a>
                </li>

                <!-- Notifications (futur) -->
                <li class="nav-item">
                    <a class="nav-link position-relative" href="#" title="Notifications">
                        <i class="fas fa-bell text-warning"></i>
                        <span class="badge rounded-pill bg-danger d-none" id="notifBadge" style="font-size:0.55rem;position:absolute;top:2px;right:0px"></span>
                    </a>
                </li>

                <!-- Profil utilisateur -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navUser" role="button" 
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <?php
                        $navPhoto = null;
                        if (isset($_SESSION['user_id'])) {
                            try {
                                $navPdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASS);
                                $navStmt = $navPdo->prepare("SELECT photo_profil FROM users WHERE id=?");
                                $navStmt->execute([$_SESSION['user_id']]);
                                $navPhoto = $navStmt->fetchColumn() ?: null;
                            } catch (Exception $e) {}
                        }
                        ?>
                        <?php if ($navPhoto): ?>
                        <img src="<?= BASE_URL . '/' . $navPhoto ?>" alt="" class="rounded-circle me-2" style="width:24px;height:24px;object-fit:cover">
                        <?php else: ?>
                        <i class="fas fa-user-circle me-2 text-info"></i>
                        <?php endif; ?>
                        <span><?php echo $_SESSION['user_username'] ?? 'Utilisateur'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navUser">
                        <li><h6 class="dropdown-header">
                            <?php echo $_SESSION['user_prenom'] ?? ''; ?> 
                            <?php echo $_SESSION['user_nom'] ?? ''; ?>
                        </h6></li>
                        <li><small class="dropdown-item-text text-muted text-nowrap">
                            <i class="fas fa-shield-alt me-1 text-warning"></i>
                            <?php 
                            $role = $_SESSION['user_role'] ?? 'user';
                            $roleNames = [
                                'admin' => 'Administrateur',
                                'directeur_residence' => 'Directeur de résidence',
                                'proprietaire' => 'Propriétaire',
                                'exploitant' => 'Exploitant',
                                'comptable' => 'Comptable',
                            ];
                            echo $roleNames[$role] ?? $role;
                            ?>
                        </small></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/user/profile">
                            <i class="fas fa-user me-2 text-info"></i> Mon profil
                        </a></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/user/settings">
                            <i class="fas fa-cog me-2 text-secondary"></i> Paramètres
                        </a></li>
                        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin'): ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-nowrap" href="<?php echo BASE_URL; ?>/admin/logs">
                            <i class="fas fa-shield-alt me-2 text-danger"></i> Logs de Sécurité
                        </a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger text-nowrap" href="#" onclick="event.preventDefault(); confirmLogout();">
                            <i class="fas fa-sign-out-alt me-2"></i> Déconnexion
                        </a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<!-- Modal de confirmation de déconnexion -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true" role="dialog" style="display: none;">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="logoutModalLabel">
                    <i class="fas fa-sign-out-alt me-2"></i>Confirmation de déconnexion
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fermer"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-power-off fa-3x text-danger mb-3"></i>
                    <h5 class="text-dark">Êtes-vous sûr de vouloir vous déconnecter ?</h5>
                </div>
                <div class="alert alert-info border-0" style="background-color: #cff4fc;">
                    <i class="fas fa-info-circle text-info me-2"></i>
                    <strong>Information :</strong> Votre session sera terminée.
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-shield-alt me-2"></i>
                    Par mesure de sécurité, pensez à vous déconnecter lorsque vous n'utilisez pas l'application.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmLogoutBtn">
                    <i class="fas fa-sign-out-alt me-1"></i>Se déconnecter
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// Variable globale pour stocker l'instance du modal
let logoutModalInstance = null;

// Ouvrir le modal de confirmation de déconnexion
function confirmLogout() {
    // Nettoyer tous les backdrops existants avant d'ouvrir le modal
    const existingBackdrops = document.querySelectorAll('.modal-backdrop');
    existingBackdrops.forEach(function(backdrop) {
        backdrop.remove();
    });
    
    // Fermer le menu navbar sur mobile avant d'ouvrir le modal
    const navbarCollapse = document.getElementById('navbarNav');
    if (navbarCollapse && navbarCollapse.classList.contains('show')) {
        const bsCollapse = bootstrap.Collapse.getInstance(navbarCollapse);
        if (bsCollapse) {
            bsCollapse.hide();
        }
    }
    
    const modalElement = document.getElementById('logoutModal');
    
    // Détruire l'instance précédente si elle existe
    if (logoutModalInstance) {
        logoutModalInstance.dispose();
        logoutModalInstance = null;
    }
    
    // Créer une nouvelle instance propre
    logoutModalInstance = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Petit délai pour s'assurer que le menu est fermé
    setTimeout(function() {
        logoutModalInstance.show();
    }, 300);
}

// Confirmer la déconnexion et gérer les événements du modal
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier que Bootstrap est chargé
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap n\'est pas chargé !');
        return;
    }
    
    // CRITIQUE : Nettoyer tous les backdrops résiduels au chargement
    const cleanupBackdrops = function() {
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(function(backdrop) {
            if (!backdrop.classList.contains('show')) {
                backdrop.remove();
            }
        });
    };
    
    // Nettoyer immédiatement
    cleanupBackdrops();
    
    // Nettoyer à nouveau après un court délai
    setTimeout(cleanupBackdrops, 100);
    
    const confirmLogoutBtn = document.getElementById('confirmLogoutBtn');
    const logoutModalElement = document.getElementById('logoutModal');
    
    // S'assurer que le modal est complètement caché au démarrage
    if (logoutModalElement) {
        logoutModalElement.style.display = 'none';
        logoutModalElement.setAttribute('aria-hidden', 'true');
        logoutModalElement.classList.remove('show');
    }
    
    // Action sur le bouton de confirmation de déconnexion
    if (confirmLogoutBtn) {
        confirmLogoutBtn.addEventListener('click', function() {
            // Fermer le modal d'abord
            if (logoutModalInstance) {
                logoutModalInstance.hide();
            }
            // Rediriger après un court délai
            setTimeout(function() {
                window.location.href = '<?php echo BASE_URL; ?>/auth/logout';
            }, 200);
        });
    }
    
    // Nettoyer aria-hidden quand le modal est caché
    if (logoutModalElement) {
        logoutModalElement.addEventListener('hidden.bs.modal', function () {
            // Nettoyer complètement le modal
            logoutModalElement.style.display = 'none';
            logoutModalElement.setAttribute('aria-hidden', 'true');
            logoutModalElement.removeAttribute('aria-modal');
            
            // Détruire l'instance
            if (logoutModalInstance) {
                logoutModalInstance.dispose();
                logoutModalInstance = null;
            }
            
            // CRITIQUE : Supprimer TOUS les backdrops résiduels
            setTimeout(function() {
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(function(backdrop) {
                    backdrop.remove();
                });
            }, 50);
        });
        
        // S'assurer que aria-hidden est bien géré à l'ouverture
        logoutModalElement.addEventListener('shown.bs.modal', function () {
            logoutModalElement.setAttribute('aria-modal', 'true');
            logoutModalElement.removeAttribute('aria-hidden');
        });
    }
});
</script>
