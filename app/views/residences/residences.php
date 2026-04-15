<?php
/**
 * Liste des Résidences Seniors - Admin
 */
?>

<div class="container-fluid py-4">
    <?php
    $currentRole = $currentRole ?? ($_SESSION['user_role'] ?? null);
    $canManageResidences = $canManageResidences ?? false;
    $canExportResidences = $canExportResidences ?? false;
    $isProprietaire = ($currentRole === 'proprietaire');
    $canCreateResidence = $canCreateResidence ?? false;
    $canSeeMap = $canSeeMap ?? false;
    ?>
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-building', 'text' => 'Résidences Seniors', 'url' => null]
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
                        Résidences Seniors
                    </h1>
                    <p class="text-muted mb-0">
                        <?= $totalRecords ?> résidence<?= $totalRecords > 1 ? 's' : '' ?> trouvée<?= $totalRecords > 1 ? 's' : '' ?>
                    </p>
                </div>
                <div class="btn-stack-mobile">
                    <?php if ($canSeeMap): ?>
                    <a href="<?= BASE_URL ?>/admin/carteResidences" class="btn btn-secondary">
                        <i class="fas fa-map-marked-alt"></i> Vue Carte
                    </a>
                    <?php endif; ?>
                    <?php if ($canExportResidences): ?>
                    <button class="btn btn-success" onclick="exportExcel()">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </button>
                    <?php endif; ?>
                    <?php if ($canCreateResidence): ?>
                    <a href="<?= BASE_URL ?>/admin/createResidence" class="btn btn-danger">
                        <i class="fas fa-plus"></i> Nouvelle résidence
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card principale -->
    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Recherche et Filtres</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="<?= BASE_URL ?>/admin/residences" class="row g-3">
                <!-- Recherche -->
                <div class="col-12 col-md-4">
                    <label class="form-label"><i class="fas fa-search me-1"></i>Rechercher</label>
                    <input type="text" name="search" class="form-control" 
                           placeholder="Nom, ville, adresse..." 
                           value="<?= htmlspecialchars($search) ?>">
                </div>
                
                <!-- Filtre Ville -->
                <div class="col-12 col-md-2">
                    <label class="form-label"><i class="fas fa-map-marker-alt me-1"></i>Ville</label>
                    <select name="ville" class="form-select">
                        <option value="">Toutes</option>
                        <?php foreach ($villes as $v): ?>
                            <option value="<?= htmlspecialchars($v) ?>" 
                                    <?= $ville === $v ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Filtre Exploitant -->
                <?php if ($currentRole !== 'exploitant'): ?>
                <div class="col-12 col-md-3">
                    <label class="form-label"><i class="fas fa-building me-1"></i>Exploitant</label>
                    <select name="exploitant" class="form-select">
                        <option value="">Tous</option>
                        <?php foreach ($exploitants as $e): ?>
                            <option value="<?= $e['id'] ?>" 
                                    <?= $exploitant == $e['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($e['raison_sociale']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php else: ?>
                <input type="hidden" name="exploitant" value="<?= htmlspecialchars((string)$exploitant) ?>">
                <?php endif; ?>
                
                <?php if (!$isProprietaire): ?>
                <!-- Filtre Taux -->
                <div class="col-12 col-md-2">
                    <label class="form-label"><i class="fas fa-percent me-1"></i>Taux min.</label>
                    <select name="taux_min" class="form-select">
                        <option value="">Tous</option>
                        <option value="0" <?= $taux_min === '0' ? 'selected' : '' ?>>0%</option>
                        <option value="50" <?= $taux_min === '50' ? 'selected' : '' ?>>50%</option>
                        <option value="80" <?= $taux_min === '80' ? 'selected' : '' ?>>80%</option>
                    </select>
                </div>
                <?php endif; ?>
                
                <!-- Boutons -->
                <div class="col-12 col-md-1">
                    <label class="form-label d-none d-md-block">&nbsp;</label>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Tableau -->
    <div class="card shadow-sm mt-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0" id="residencesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="nom">Résidence</th>
                            <th class="sortable" data-column="ville">Ville</th>
                            <th class="sortable" data-column="exploitant">Exploitant</th>
                            <th class="sortable text-center" data-column="lots" data-type="number">Lots</th>
                            <?php if (!$isProprietaire): ?>
                            <th class="sortable text-center" data-column="occupations" data-type="number">Occupations</th>
                            <th class="sortable text-center" data-column="taux" data-type="number">Taux</th>
                            <th class="sortable text-end" data-column="revenus" data-type="number">Revenus/mois</th>
                            <th class="text-center" data-no-sort>Statut</th>
                            <?php endif; ?>
                            <th class="text-center" data-no-sort>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($residences)): ?>
                            <tr>
                                <td colspan="<?= $isProprietaire ? 5 : 9 ?>" class="text-center text-muted py-4">
                                    <i class="fas fa-search fa-3x mb-3 d-block"></i>
                                    Aucune résidence trouvée
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($residences as $residence): ?>
                                <tr>
                                    <td data-sort="<?= htmlspecialchars($residence['nom']) ?>">
                                        <strong><?= htmlspecialchars($residence['nom']) ?></strong>
                                        <?php if (isset($residence['actif']) && !$residence['actif']): ?>
                                            <span class="badge bg-secondary ms-1">Inactive</span>
                                        <?php endif; ?>
                                        <br>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($residence['adresse']) ?>
                                        </small>
                                    </td>
                                    <td data-sort="<?= htmlspecialchars($residence['ville']) ?>">
                                        <?= htmlspecialchars($residence['ville']) ?>
                                        <br>
                                        <small class="text-muted"><?= htmlspecialchars($residence['code_postal']) ?></small>
                                    </td>
                                    <td data-sort="<?= htmlspecialchars($residence['exploitant'] ?? 'Non assigné') ?>">
                                        <?= htmlspecialchars($residence['exploitant'] ?? 'Non assigné') ?>
                                    </td>
                                    <td class="text-center" data-sort="<?= $residence['nb_lots'] ?>">
                                        <span class="badge bg-secondary"><?= $residence['nb_lots'] ?></span>
                                    </td>
                                    <?php if (!$isProprietaire): ?>
                                    <td class="text-center" data-sort="<?= $residence['nb_occupations'] ?>">
                                        <span class="badge bg-info"><?= $residence['nb_occupations'] ?></span>
                                    </td>
                                    <td class="text-center" data-sort="<?= $residence['taux_occupation'] ?>">
                                        <?php
                                        $taux = (float)$residence['taux_occupation'];
                                        $badgeClass = $taux >= 80 ? 'success' : ($taux >= 50 ? 'warning' : 'danger');
                                        ?>
                                        <span class="badge bg-<?= $badgeClass ?>">
                                            <?= number_format($taux, 1) ?>%
                                        </span>
                                    </td>
                                    <td class="text-end" data-sort="<?= $residence['revenus_mensuels'] ?>">
                                        <strong><?= number_format($residence['revenus_mensuels'], 0, ',', ' ') ?>€</strong>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($residence['statut']): ?>
                                            <span class="badge bg-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <?php endif; ?>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= BASE_URL ?>/admin/viewResidence/<?= $residence['id'] ?>"
                                               class="btn btn-outline-primary"
                                               title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <?php if (!empty($residence['latitude']) && !empty($residence['longitude'])): ?>
                                            <a href="<?= BASE_URL ?>/admin/carteResidence/<?= $residence['id'] ?>"
                                               class="btn btn-outline-success"
                                               title="Vue carte">
                                                <i class="fas fa-map-marker-alt"></i>
                                            </a>
                                            <?php endif; ?>
                                            <?php if ($canManageResidences): ?>
                                            <a href="<?= BASE_URL ?>/admin/editResidence/<?= $residence['id'] ?>"
                                               class="btn btn-outline-secondary"
                                               title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if (isset($residence['actif']) && !$residence['actif']): ?>
                                            <a href="<?= BASE_URL ?>/admin/restoreResidence/<?= $residence['id'] ?>"
                                               class="btn btn-outline-success"
                                               title="Réactiver"
                                               onclick="return confirm('Réactiver cette résidence ?')">
                                                <i class="fas fa-undo"></i>
                                            </a>
                                            <?php else: ?>
                                            <button type="button"
                                                    class="btn btn-outline-danger"
                                                    onclick="confirmDelete(<?= $residence['id'] ?>)"
                                                    title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                            <?php endif; ?>
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
        
        <!-- Pagination -->
        <?php
        // Préparer les variables pour le partial de pagination réutilisable
        $currentPage = $page;
        $params = $_GET;
        include __DIR__ . '/../partials/pagination.php';
        ?>
    </div>

</div>

<!-- Modal de confirmation de suppression -->
<?php if ($canManageResidences): ?>
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white border-0">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmation de suppression
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-trash-alt fa-3x text-danger mb-3"></i>
                    <h5 class="text-dark">Êtes-vous sûr de vouloir supprimer cette résidence ?</h5>
                </div>
                <div class="alert alert-warning border-0" style="background-color: #fff3cd;">
                    <i class="fas fa-exclamation-circle text-warning me-2"></i>
                    <strong>Attention :</strong> Cette action est irréversible !
                </div>
                <p class="text-muted mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    La suppression d'une résidence peut affecter les lots et occupations associés.
                </p>
            </div>
            <div class="modal-footer border-0 bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer définitivement
                </button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script>
let residenceIdToDelete = null;

// Ouvrir le modal de confirmation
function confirmDelete(id) {
    <?php if (!$canManageResidences): ?>
    return;
    <?php endif; ?>
    residenceIdToDelete = id;
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Confirmer la suppression
<?php if ($canManageResidences): ?>
document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    if (residenceIdToDelete) {
        window.location.href = '<?= BASE_URL ?>/admin/deleteResidence/' + residenceIdToDelete;
    }
});
<?php endif; ?>

// Export Excel
function exportExcel() {
    <?php if (!$canExportResidences): ?>
    return;
    <?php endif; ?>
    // Récupérer les filtres actuels
    const urlParams = new URLSearchParams(window.location.search);
    const exportUrl = '<?= BASE_URL ?>/admin/exportResidencesExcel?' + urlParams.toString();
    
    // Télécharger le fichier
    window.location.href = exportUrl;
}

// Initialiser le tri des colonnes avec DataTable.js
document.addEventListener('DOMContentLoaded', function() {
    const dataTable = new DataTable('residencesTable', {
        sortable: true,
        excludeColumns: [7, 8] // Statut et Actions ne sont pas triables
    });
    
    console.log('DataTable initialisé pour le tableau des résidences');
});
</script>
