<?php $title = "Tableau de bord"; ?>

<!-- Carte de bienvenue -->
<div class="container-fluid py-4">
    <div class="welcome-card">
        <h1>
            <i class="fas fa-tachometer-alt me-2"></i>
            Tableau de bord
        </h1>
        <p class="text-muted">
            Bienvenue, <?php echo htmlspecialchars($user->prenom); ?> !
        </p>
        <p>
            <i class="fas fa-user-tag me-2"></i>
            Rôle : <strong><?php 
                $roles = [
                    'admin' => 'Administrateur',
                    'gestionnaire' => 'Gestionnaire',
                    'exploitant' => 'Exploitant',
                    'proprietaire' => 'Propriétaire',
                    'resident' => 'Résident'
                ];
                echo $roles[$user->role] ?? $user->role;
            ?></strong>
        </p>
    </div>
    
    <!-- Informations -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-rocket me-2"></i>
                        Bienvenue dans Synd_Gest - Plateforme Domitys
                    </h5>
                </div>
                <div class="card-body">
                    <p>✅ <strong>Architecture MVC fonctionnelle !</strong></p>
                    <p>L'application utilise maintenant une architecture MVC (Model-View-Controller) propre et modulaire.</p>
                    <ul>
                        <li>✅ Routeur personnalisé</li>
                        <li>✅ Contrôleurs (HomeController, AuthController, AdminController)</li>
                        <li>✅ Modèles (User, Copropriete, Lot, etc.)</li>
                        <li>✅ Vues avec templates réutilisables</li>
                        <li>✅ Authentification fonctionnelle (5 rôles)</li>
                        <li>✅ Architecture de sécurité (CSRF, Rate Limiting, Logging)</li>
                        <li>✅ DataTables avec tri, recherche, filtres et pagination</li>
                        <li>✅ Carte interactive Leaflet.js des résidences</li>
                    </ul>
                    <p class="mb-0">
                        <strong>Module Domitys actif :</strong> Gestion résidences seniors, résidents, occupations, contrats.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
