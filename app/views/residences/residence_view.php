<?php
/**
 * Voir détails d'une résidence senior - Admin
 */
$taux_occupation = $stats['total_lots'] > 0 ? ($stats['occupations_actives'] / $stats['total_lots']) * 100 : 0;
$marge_mensuelle = $stats['revenus_mensuels'] - $stats['charges_mensuelles'];
?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences', 'url' => BASE_URL . '/admin/residences'],
        ['icon' => 'fas fa-eye', 'text' => $residence['nom'], 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-building text-dark"></i>
                        <?= htmlspecialchars($residence['nom']) ?>
                    </h1>
                    <p class="text-muted mb-0">
                        <i class="fas fa-map-marker-alt"></i>
                        <?= htmlspecialchars($residence['adresse']) ?>, 
                        <?= htmlspecialchars($residence['code_postal']) ?> 
                        <?= htmlspecialchars($residence['ville']) ?>
                    </p>
                </div>
                <div>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'directeur_residence'])): ?>
                    <a href="<?= BASE_URL ?>/admin/editResidence/<?= $residence['id'] ?>" class="btn btn-warning">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <?php endif; ?>
                    <?php
                    $backUrl = BASE_URL . '/admin/residences';
                    $backLabel = 'Retour';
                    if (isset($_GET['from']) && $_GET['from'] === 'mesLots') {
                        $backUrl = BASE_URL . '/coproprietaire/mesLots';
                        $backLabel = 'Retour à mes lots';
                    } elseif (isset($_GET['from']) && $_GET['from'] === 'mesResidences') {
                        $backUrl = BASE_URL . '/coproprietaire/mesResidences';
                        $backLabel = 'Retour à mes résidences';
                    }
                    ?>
                    <a href="<?= $backUrl ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> <?= $backLabel ?>
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <?php $isProprietaire = ($_SESSION['user_role'] ?? '') === 'proprietaire'; ?>

    <!-- Stats cards (pas pour propriétaire) -->
    <?php if (!$isProprietaire): ?>
    <div class="row mb-4">
        <!-- Total Lots -->
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">Total Lots</h6>
                            <h2 class="mb-0 text-gray-800"><?= $stats['total_lots'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-door-open fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Occupations -->
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">Occupés</h6>
                            <h2 class="mb-0 text-gray-800"><?= $stats['occupations_actives'] ?></h2>
                        </div>
                        <div>
                            <i class="fas fa-users fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Taux d'occupation -->
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <?php
            $borderClass = $taux_occupation >= 80 ? 'success' : ($taux_occupation >= 50 ? 'warning' : 'primary');
            $textClass = $taux_occupation >= 80 ? 'success' : ($taux_occupation >= 50 ? 'warning' : 'primary');
            ?>
            <div class="card border-left-<?= $borderClass ?> shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-<?= $textClass ?> mb-1 fw-bold">Taux d'occupation</h6>
                            <h2 class="mb-0 text-gray-800"><?= number_format($taux_occupation, 1) ?>%</h2>
                        </div>
                        <div>
                            <i class="fas fa-chart-pie fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Marge mensuelle -->
        <div class="col-12 col-md-6 col-lg-3 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">Marge/mois</h6>
                            <h2 class="mb-0 text-gray-800"><?= number_format($marge_mensuelle, 0, ',', ' ') ?>€</h2>
                        </div>
                        <div>
                            <i class="fas fa-euro-sign fa-3x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Informations générales -->
    <div class="row mb-4">
        <div class="col-12 col-lg-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informations générales</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td class="text-muted"><i class="fas fa-building me-2"></i>Exploitant</td>
                            <td><strong><?= htmlspecialchars($residence['exploitant_nom'] ?? 'Non assigné') ?></strong></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fas fa-tag me-2"></i>Type</td>
                            <td><span class="badge bg-danger">Résidence Seniors</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fas fa-calendar me-2"></i>Date de création</td>
                            <td><?= date('d/m/Y', strtotime($residence['created_at'])) ?></td>
                        </tr>
                    </table>
                    
                    <?php if (!empty($residence['description'])): ?>
                    <hr>
                    <h6><i class="fas fa-align-left me-2"></i>Description</h6>
                    <p class="text-muted"><?= nl2br(htmlspecialchars($residence['description'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Finances (pas pour propriétaire) -->
        <?php if (!$isProprietaire): ?>
        <div class="col-12 col-lg-6 mb-3">
            <div class="card shadow">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>Finances mensuelles</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-success"><i class="fas fa-arrow-up"></i> Revenus (résidents)</span>
                            <strong class="text-success"><?= number_format($stats['revenus_mensuels'], 2, ',', ' ') ?>€</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" style="width: 100%">
                                <?= number_format($stats['revenus_mensuels'], 0, ',', ' ') ?>€
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-danger"><i class="fas fa-arrow-down"></i> Charges (propriétaires)</span>
                            <strong class="text-danger"><?= number_format($stats['charges_mensuelles'], 2, ',', ' ') ?>€</strong>
                        </div>
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-danger" style="width: <?= $stats['revenus_mensuels'] > 0 ? ($stats['charges_mensuelles'] / $stats['revenus_mensuels']) * 100 : 0 ?>%">
                                <?= number_format($stats['charges_mensuelles'], 0, ',', ' ') ?>€
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="d-flex justify-content-between">
                        <h5><i class="fas fa-calculator me-2"></i>Marge nette</h5>
                        <h4 class="text-<?= $marge_mensuelle >= 0 ? 'success' : 'danger' ?>">
                            <?= number_format($marge_mensuelle, 2, ',', ' ') ?>€
                        </h4>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Liste des lots -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-door-open me-2"></i><?= $isProprietaire ? 'Mes lots' : 'Lots de la résidence' ?> (<?= count($lots) ?>)</h5>
            <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'directeur_residence'])): ?>
            <a href="<?= BASE_URL ?>/lot/create/<?= $residence['id'] ?>" class="btn btn-sm btn-danger">
                <i class="fas fa-plus"></i> Ajouter un lot
            </a>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <?php if (empty($lots)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Aucun lot créé pour cette résidence</p>
                    <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'directeur_residence'])): ?>
                    <a href="<?= BASE_URL ?>/lot/create/<?= $residence['id'] ?>" class="btn btn-danger">
                        <i class="fas fa-plus"></i> Créer le premier lot
                    </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Barre de recherche et filtres (pas pour proprio avec 1 lot) -->
                <?php if (!$isProprietaire || count($lots) > 1): ?>
                <div class="row mb-3">
                    <div class="col-12 col-md-5 mb-2">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Rechercher un lot...">
                        </div>
                    </div>
                    <div class="col-12 col-md-3 mb-2">
                        <select class="form-select" id="filterType">
                            <option value="">Tous types</option>
                            <option value="studio">Studio</option>
                            <option value="t2">T2</option>
                            <option value="t2_bis">T2 Bis</option>
                            <option value="t3">T3</option>
                            <option value="parking">Parking</option>
                            <option value="cave">Cave</option>
                        </select>
                    </div>
                    <?php if (!$isProprietaire): ?>
                    <div class="col-12 col-md-2 mb-2">
                        <select class="form-select" id="filterStatut">
                            <option value="">Tous statuts</option>
                            <option value="Occupé">Occupé</option>
                            <option value="Libre">Libre</option>
                        </select>
                    </div>
                    <?php endif; ?>
                    <div class="col-12 col-md-2 mb-2">
                        <button class="btn btn-secondary w-100" id="btnReset">
                            <i class="fas fa-redo"></i> Réinitialiser
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Tableau avec tri -->
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="lotsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="sortable" data-column="numero">Numéro</th>
                                <th class="sortable" data-column="type">Type</th>
                                <th class="sortable text-center" data-column="surface" data-type="number">Surface</th>
                                <?php if (!$isProprietaire): ?>
                                <th class="sortable text-center" data-column="tantiemes" data-type="number">Tantièmes</th>
                                <th class="sortable" data-column="statut">Statut</th>
                                <th>Résident</th>
                                <?php endif; ?>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lots as $lot): ?>
                            <tr>
                                <td data-sort="<?= htmlspecialchars($lot['numero_lot']) ?>">
                                    <strong><?= htmlspecialchars($lot['numero_lot']) ?></strong>
                                </td>
                                <td data-sort="<?= htmlspecialchars($lot['type']) ?>">
                                    <span class="badge bg-secondary">
                                        <?= ucfirst(htmlspecialchars($lot['type'])) ?>
                                    </span>
                                </td>
                                <td class="text-center" data-sort="<?= $lot['surface'] ?>">
                                    <?= $lot['surface'] ?> m²
                                </td>
                                <?php if (!$isProprietaire): ?>
                                <td class="text-center" data-sort="<?= $lot['tantiemes_generaux'] ?>">
                                    <?= $lot['tantiemes_generaux'] ?>
                                </td>
                                <td data-sort="<?= $lot['occupation_id'] ? 'occupe' : 'libre' ?>" data-filter="<?= $lot['occupation_id'] ? 'occupe' : 'libre' ?>">
                                    <?php if ($lot['occupation_id']): ?>
                                        <span class="badge bg-success">Occupé</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Libre</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($lot['resident_nom']): ?>
                                        <i class="fas fa-user me-1"></i>
                                        <?= htmlspecialchars($lot['resident_nom']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <?php endif; ?>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/lot/show/<?= $lot['id'] ?>"
                                           class="btn btn-outline-primary"
                                           title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (in_array($_SESSION['user_role'] ?? '', ['admin', 'directeur_residence'])): ?>
                                        <a href="<?= BASE_URL ?>/lot/edit/<?= $lot['id'] ?>"
                                           class="btn btn-outline-secondary"
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination info -->
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted" id="tableInfo">
                        Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($lots)) ?></span> 
                        sur <span id="totalEntries"><?= count($lots) ?></span> lots
                    </div>
                    <nav>
                        <ul class="pagination mb-0" id="pagination"></ul>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le DataTable avec recherche, filtres et pagination
    const lotsTable = new DataTableWithPagination('lotsTable', {
        sortable: true,
        excludeColumns: [6], // Actions non triables
        rowsPerPage: 10,
        searchInputId: 'searchInput',
        filters: [
            { id: 'filterType', column: 1 },    // Colonne Type
            { id: 'filterStatut', column: 4 }   // Colonne Statut (Occupé/Libre)
        ],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
    
    console.log('DataTable initialisé pour le tableau des lots');
});
</script>
