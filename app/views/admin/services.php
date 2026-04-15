<?php $title = "Gestion des Services"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Services', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">
                <i class="fas fa-concierge-bell text-dark"></i>
                Catalogue des Services
            </h1>
            <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#serviceModal" onclick="openCreateModal()">
                <i class="fas fa-plus me-1"></i>Nouveau service
            </button>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <?php
        $nbInclus = count(array_filter($services, fn($s) => $s['categorie'] === 'inclus' && $s['actif']));
        $nbSup = count(array_filter($services, fn($s) => $s['categorie'] === 'supplementaire' && $s['actif']));
        $nbInactifs = count(array_filter($services, fn($s) => !$s['actif']));
        ?>
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Services inclus</h6>
                    <h3 class="mb-0 text-gray-800"><?= $nbInclus ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning mb-1 fw-bold small">Services suppl.</h6>
                    <h3 class="mb-0 text-gray-800"><?= $nbSup ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-secondary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-secondary mb-1 fw-bold small">Inactifs</h6>
                    <h3 class="mb-0 text-gray-800"><?= $nbInactifs ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Services</h5>
            <input type="text" id="searchService" class="form-control form-control-sm" style="max-width:250px" placeholder="Rechercher...">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="servicesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0" data-type="number" style="width:50px">#</th>
                            <th class="sortable" data-column="1">Service</th>
                            <th class="sortable text-center" data-column="2">Catégorie</th>
                            <th class="sortable text-end" data-column="3" data-type="number">Prix (€)</th>
                            <th class="sortable text-center" data-column="4" data-type="number">Utilisations</th>
                            <th class="sortable text-center" data-column="5">Statut</th>
                            <th class="text-end" data-no-sort style="width:120px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($services as $svc): ?>
                        <tr class="<?= !$svc['actif'] ? 'table-secondary' : '' ?>">
                            <td data-sort="<?= $svc['ordre_affichage'] ?>"><?= $svc['ordre_affichage'] ?></td>
                            <td data-sort="<?= htmlspecialchars($svc['nom']) ?>">
                                <i class="<?= htmlspecialchars($svc['icone']) ?> me-2 text-muted"></i>
                                <strong><?= htmlspecialchars($svc['nom']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($svc['slug']) ?></small>
                            </td>
                            <td class="text-center" data-sort="<?= $svc['categorie'] ?>">
                                <?php if ($svc['categorie'] === 'inclus'): ?>
                                    <span class="badge bg-info">Inclus</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Supplémentaire</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" data-sort="<?= $svc['prix_defaut'] ?>">
                                <?= $svc['prix_defaut'] > 0 ? number_format($svc['prix_defaut'], 2) . ' €' : '-' ?>
                            </td>
                            <td class="text-center" data-sort="<?= $svc['nb_utilisations'] ?>">
                                <span class="badge bg-light text-dark"><?= $svc['nb_utilisations'] ?></span>
                            </td>
                            <td class="text-center" data-sort="<?= $svc['actif'] ?>">
                                <?php if ($svc['actif']): ?>
                                    <span class="badge bg-success">Actif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-warning" title="Modifier"
                                            onclick='openEditModal(<?= json_encode($svc) ?>)'>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <?php if ($svc['actif']): ?>
                                    <a href="<?= BASE_URL ?>/admin/services/delete/<?= $svc['id'] ?>"
                                       class="btn btn-outline-danger" title="Désactiver"
                                       onclick="return confirm('Désactiver ce service ?')">
                                        <i class="fas fa-ban"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <!-- Pagination -->
            <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="tableInfo">
                    Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($services)) ?></span>
                    sur <span id="totalEntries"><?= count($services) ?></span> services
                </div>
                <nav>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal Création / Édition -->
<div class="modal fade" id="serviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="serviceForm" method="POST">
                <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="serviceModalTitle">
                        <i class="fas fa-concierge-bell me-2"></i>Nouveau service
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nom" id="svc_nom" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Slug</label>
                            <input type="text" class="form-control" name="slug" id="svc_slug" placeholder="Auto-généré">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Catégorie <span class="text-danger">*</span></label>
                            <select class="form-select" name="categorie" id="svc_categorie">
                                <option value="inclus">Inclus</option>
                                <option value="supplementaire">Supplémentaire</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Prix par défaut (€)</label>
                            <input type="number" class="form-control" name="prix_defaut" id="svc_prix" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Icône (Font Awesome)</label>
                            <div class="input-group">
                                <span class="input-group-text"><i id="svc_icone_preview" class="fas fa-concierge-bell"></i></span>
                                <input type="text" class="form-control" name="icone" id="svc_icone" value="fas fa-concierge-bell">
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Ordre d'affichage</label>
                            <input type="number" class="form-control" name="ordre_affichage" id="svc_ordre" min="0" value="0">
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Statut</label>
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="actif" id="svc_actif" checked>
                                <label class="form-check-label" for="svc_actif">Actif</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea class="form-control" name="description" id="svc_description" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
const table = new DataTableWithPagination('servicesTable', {
    rowsPerPage: 10,
    searchInputId: 'searchService',
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});

function openCreateModal() {
    document.getElementById('serviceForm').action = '<?= BASE_URL ?>/admin/services/store';
    document.getElementById('serviceModalTitle').innerHTML = '<i class="fas fa-plus-circle me-2"></i>Nouveau service';
    document.getElementById('svc_nom').value = '';
    document.getElementById('svc_slug').value = '';
    document.getElementById('svc_categorie').value = 'inclus';
    document.getElementById('svc_prix').value = '0';
    document.getElementById('svc_icone').value = 'fas fa-concierge-bell';
    document.getElementById('svc_ordre').value = '0';
    document.getElementById('svc_actif').checked = true;
    document.getElementById('svc_description').value = '';
}

function openEditModal(svc) {
    document.getElementById('serviceForm').action = '<?= BASE_URL ?>/admin/services/update/' + svc.id;
    document.getElementById('serviceModalTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier : ' + svc.nom;
    document.getElementById('svc_nom').value = svc.nom;
    document.getElementById('svc_slug').value = svc.slug;
    document.getElementById('svc_categorie').value = svc.categorie;
    document.getElementById('svc_prix').value = svc.prix_defaut;
    document.getElementById('svc_icone').value = svc.icone || 'fas fa-concierge-bell';
    document.getElementById('svc_ordre').value = svc.ordre_affichage;
    document.getElementById('svc_actif').checked = svc.actif == 1;
    document.getElementById('svc_description').value = svc.description || '';
    new bootstrap.Modal(document.getElementById('serviceModal')).show();
}

// Preview icône
document.getElementById('svc_icone').addEventListener('input', function() {
    document.getElementById('svc_icone_preview').className = this.value || 'fas fa-concierge-bell';
});
</script>
