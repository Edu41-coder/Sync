<?php
/**
 * Carte Interactive des Résidences Seniors - Admin
 * Utilise Leaflet.js pour l'affichage cartographique
 */
?>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<!-- Styles personnalisés pour la carte -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/map.css" />

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-map-marked-alt', 'text' => 'Carte', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>

    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h2 mb-2">
                <i class="fas fa-map-marked-alt text-danger me-2"></i>
                Carte des Résidences
            </h1>
            <p class="text-muted mb-0">
                Visualisation géographique des <?= count($residences) ?> résidences seniors
            </p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/admin/residences" class="btn btn-secondary me-2">
                <i class="fas fa-list me-2"></i> Vue Liste
            </a>
            <a href="<?= BASE_URL ?>/admin/createResidence" class="btn btn-danger">
                <i class="fas fa-plus me-2"></i> Nouvelle résidence
            </a>
        </div>
    </div>

    <!-- Cartes statistiques -->
    <div class="row">
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">Total Résidences</h6>
                            <h2 class="mb-0 text-gray-800"><?= $stats['total'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-building fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">Total Lots</h6>
                            <h2 class="mb-0 text-gray-800"><?= $stats['lots'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-door-open fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold">Occupations</h6>
                            <h2 class="mb-0 text-gray-800"><?= $stats['occupations'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <?php
            $tauxGlobal = $stats['lots'] > 0 ? ($stats['occupations'] / $stats['lots']) * 100 : 0;
            $borderClass = $tauxGlobal >= 80 ? 'success' : ($tauxGlobal >= 50 ? 'warning' : 'primary');
            $textClass = $tauxGlobal >= 80 ? 'success' : ($tauxGlobal >= 50 ? 'warning' : 'primary');
            ?>
            <div class="card border-left-<?= $borderClass ?> shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-<?= $textClass ?> mb-1 fw-bold">Taux Global</h6>
                            <h2 class="mb-0 text-gray-800"><?= number_format($tauxGlobal, 1) ?>%</h2>
                        </div>
                        <div>
                            <i class="fas fa-chart-pie fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Carte -->
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-map me-2"></i> Localisation des résidences
            </h5>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnZoomIn">
                    <i class="fas fa-search-plus"></i> Zoom +
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnZoomOut">
                    <i class="fas fa-search-minus"></i> Zoom -
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetView">
                    <i class="fas fa-redo"></i> Réinitialiser
                </button>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnToggleColors">
                    <i class="fas fa-palette"></i> Code couleur
                </button>
            </div>
        </div>
        <div class="card-body p-0">
            <div id="map" style="height: 600px;"></div>
        </div>
        <div class="card-footer text-muted">
            <i class="fas fa-info-circle me-2"></i>
            Cliquez sur un marqueur pour voir les détails de la résidence. 
            Utilisez les contrôles pour naviguer sur la carte.
            <span class="float-end">
                <small>Powered by <a href="https://leafletjs.com" target="_blank">Leaflet.js</a></small>
            </span>
        </div>
    </div>

    <!-- Légende -->
    <div class="card shadow">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-info-circle me-2"></i> Légende
            </h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-success me-2" style="width: 60px;">≥ 80%</span>
                        <span>Taux d'occupation élevé</span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-warning me-2" style="width: 60px;">50-79%</span>
                        <span>Taux d'occupation moyen</span>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="d-flex align-items-center mb-2">
                        <span class="badge bg-danger me-2" style="width: 60px;">< 50%</span>
                        <span>Taux d'occupation faible</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<!-- Script personnalisé pour la carte -->
<script src="<?= BASE_URL ?>/assets/js/map.js"></script>

<script>
// Initialiser la carte avec les données des résidences
document.addEventListener('DOMContentLoaded', function() {
    const residences = <?= json_encode($residences) ?>;
    const baseUrl = '<?= BASE_URL ?>';
    
    // Créer l'instance de la carte
    const residenceMap = new ResidenceMap('map', residences, baseUrl);
    
    // Rendre l'instance accessible globalement
    window.residenceMap = residenceMap;
});
</script>
