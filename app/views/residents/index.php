<?php $title = "Résidents Seniors"; ?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-users', 'text' => 'Résidents', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    <i class="fas fa-users text-dark"></i>
                    Résidents Seniors
                </h1>
                <p class="text-muted mb-0">Gestion des résidents des résidences seniors</p>
            </div>
            <?php if (in_array($_SESSION['user_role'], ['admin', 'exploitant'])): ?>
            <div>
                <a href="<?= BASE_URL ?>/resident/create" class="btn btn-danger">
                    <i class="fas fa-plus-circle me-1"></i>Nouveau résident
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold">Total résidents</h6>
                            <h3 class="mb-0 text-gray-800"><?= count($residents) ?></h3>
                        </div>
                        <i class="fas fa-users fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-success mb-1 fw-bold">Avec occupation</h6>
                            <h3 class="mb-0 text-gray-800">
                                <?= count(array_filter($residents, fn($r) => !empty($r->residence))) ?>
                            </h3>
                        </div>
                        <i class="fas fa-home fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold">Autonomes</h6>
                            <h3 class="mb-0 text-gray-800">
                                <?= count(array_filter($residents, fn($r) => $r->niveau_autonomie === 'autonome')) ?>
                            </h3>
                        </div>
                        <i class="fas fa-walking fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-6 col-xl-3 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6 class="text-info mb-1 fw-bold">Âge moyen</h6>
                            <h3 class="mb-0 text-gray-800">
                                <?php
                                $ages = array_filter(array_map(fn($r) => $r->age, $residents));
                                echo !empty($ages) ? round(array_sum($ages) / count($ages)) : '-';
                                ?> ans
                            </h3>
                        </div>
                        <i class="fas fa-birthday-cake fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Filtres et recherche -->
    <div class="card shadow mb-4">
        <div class="card-header bg-light">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <h6 class="mb-0">
                        <i class="fas fa-filter text-dark me-2"></i>
                        Filtres
                    </h6>
                </div>
                <div class="col-12 col-md-6">
                    <input type="text" 
                           id="searchInput" 
                           class="form-control" 
                           placeholder="Rechercher un résident...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-3">
                    <label for="filterResidence" class="form-label small">Résidence</label>
                    <select id="filterResidence" class="form-select">
                        <option value="">Toutes les résidences</option>
                        <?php
                        $residences = array_unique(array_filter(array_map(fn($r) => $r->residence, $residents)));
                        sort($residences);
                        foreach ($residences as $res):
                        ?>
                        <option value="<?= htmlspecialchars($res) ?>"><?= htmlspecialchars($res) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="filterAutonomie" class="form-label small">Niveau d'autonomie</label>
                    <select id="filterAutonomie" class="form-select">
                        <option value="">Tous les niveaux</option>
                        <option value="autonome">Autonome</option>
                        <option value="semi_autonome">Semi-autonome</option>
                        <option value="dependant">Dépendant</option>
                        <option value="gir1">GIR 1</option>
                        <option value="gir2">GIR 2</option>
                        <option value="gir3">GIR 3</option>
                        <option value="gir4">GIR 4</option>
                        <option value="gir5">GIR 5</option>
                        <option value="gir6">GIR 6</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="filterOccupation" class="form-label small">Occupation</label>
                    <select id="filterOccupation" class="form-select">
                        <option value="">Tous</option>
                        <option value="avec">Avec résidence</option>
                        <option value="sans">Sans résidence</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="filterStatut" class="form-label small">Statut</label>
                    <select id="filterStatut" class="form-select">
                        <option value="">Tous</option>
                        <option value="1" selected>Actifs uniquement</option>
                        <option value="0">Inactifs uniquement</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Liste des résidents -->
    <div class="card shadow">
        <div class="card-header bg-light">
            <h6 class="mb-0">
                <i class="fas fa-list text-dark me-2"></i>
                Liste des résidents
            </h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="residentsTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="id" data-type="number">ID</th>
                            <th class="sortable" data-column="nom">Nom complet</th>
                            <th class="sortable" data-column="age" data-type="number">Âge</th>
                            <th class="sortable" data-column="autonomie">Autonomie</th>
                            <th class="sortable" data-column="residence">Résidence</th>
                            <th class="sortable" data-column="lot">Lot</th>
                            <th>Contact</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residents)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                                Aucun résident enregistré
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($residents as $resident): ?>
                            <tr data-filter-statut="<?= $resident->actif ?>" data-filter-occupation="<?= !empty($resident->residence) ? 'avec' : 'sans' ?>">
                                <td data-sort="<?= $resident->id ?>"><?= $resident->id ?></td>
                                
                                <td data-sort="<?= htmlspecialchars($resident->nom . ' ' . $resident->prenom) ?>">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-circle avatar-sm bg-primary text-white me-2">
                                            <?= strtoupper(substr($resident->prenom, 0, 1) . substr($resident->nom, 0, 1)) ?>
                                        </div>
                                        <div>
                                            <div class="fw-bold">
                                                <?= htmlspecialchars($resident->civilite . ' ' . $resident->prenom . ' ' . $resident->nom) ?>
                                                <?php if ($resident->actif == 0): ?>
                                                    <span class="badge bg-secondary ms-2">Inactif</span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($resident->email ?: '-') ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                
                                <td data-sort="<?= $resident->age ?? 0 ?>">
                                    <?= $resident->age ?? '-' ?> ans
                                </td>
                                
                                <td data-sort="<?= htmlspecialchars($resident->niveau_autonomie ?? '') ?>" data-filter="<?= htmlspecialchars($resident->niveau_autonomie ?? '') ?>">
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
                                    $color = $niveauColors[$resident->niveau_autonomie ?? 'autonome'] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $color ?>">
                                        <?= strtoupper(str_replace('_', ' ', $resident->niveau_autonomie ?? 'N/A')) ?>
                                    </span>
                                </td>
                                
                                <td data-sort="<?= htmlspecialchars($resident->residence ?? '') ?>" data-filter="<?= htmlspecialchars($resident->residence ?? '') ?>">
                                    <?php if ($resident->residence): ?>
                                        <i class="fas fa-building text-primary me-1"></i>
                                        <?= htmlspecialchars($resident->residence) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td data-sort="<?= htmlspecialchars($resident->numero_lot ?? '') ?>">
                                    <?php if ($resident->numero_lot): ?>
                                        <i class="fas fa-door-open text-success me-1"></i>
                                        <?= htmlspecialchars($resident->numero_lot) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <?php if ($resident->telephone_mobile): ?>
                                        <i class="fas fa-phone text-success me-1"></i>
                                        <?= htmlspecialchars($resident->telephone_mobile) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                
                                <td>
                                    <div class="btn-group btn-group-sm">
                                        <a href="<?= BASE_URL ?>/resident/show/<?= $resident->id ?>" 
                                           class="btn btn-outline-primary"
                                           title="Voir le profil">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <?php if (in_array($_SESSION['user_role'], ['admin', 'exploitant'])): ?>
                                        <a href="<?= BASE_URL ?>/resident/edit/<?= $resident->id ?>" 
                                           class="btn btn-outline-warning"
                                           title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-light">
            <div class="row align-items-center">
                <div class="col-12 col-md-6 mb-2 mb-md-0">
                    <div id="tableInfo" class="text-muted small">
                        Affichage de <span id="startEntry">1</span> à <span id="endEntry">10</span> 
                        sur <span id="totalEntries"><?= count($residents) ?></span> résidents
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <ul class="pagination pagination-sm justify-content-end mb-0" id="pagination"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser le tableau avec tri et pagination
    const table = new DataTableWithPagination('residentsTable', {
        excludeColumns: [7], // Actions
        rowsPerPage: 10,
        searchInputId: 'searchInput',
        filters: [
            { id: 'filterResidence', column: 4 },
            { id: 'filterAutonomie', column: 3 }
        ]
    });
    
    // Sauvegarder la méthode applyFilters originale et l'étendre
    const originalApplyFilters = table.applyFilters.bind(table);
    table.applyFilters = function() {
        // Appliquer d'abord les filtres standard (recherche + filtres colonne)
        originalApplyFilters();

        // Puis appliquer les filtres custom sur data-attributes
        const filterStatutValue = document.getElementById('filterStatut').value;
        const filterOccupationValue = document.getElementById('filterOccupation').value;

        if (filterStatutValue !== '' || filterOccupationValue !== '') {
            this.filteredRows = this.filteredRows.filter(row => {
                if (filterStatutValue !== '') {
                    const rowStatut = row.getAttribute('data-filter-statut');
                    if (filterStatutValue === '1' && rowStatut !== '1') return false;
                    if (filterStatutValue === '0' && rowStatut !== '0') return false;
                }
                if (filterOccupationValue !== '') {
                    const rowOccupation = row.getAttribute('data-filter-occupation');
                    if (filterOccupationValue !== rowOccupation) return false;
                }
                return true;
            });
            this.pagination.currentPage = 1;
            this.renderTable();
        }
    };

    // Écouter les changements sur les filtres custom
    document.getElementById('filterStatut').addEventListener('change', () => table.applyFilters());
    document.getElementById('filterOccupation').addEventListener('change', () => table.applyFilters());

    // Appliquer les filtres par défaut (actifs uniquement sélectionné)
    table.applyFilters();
});
</script>
