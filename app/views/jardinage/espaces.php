<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-tree', 'text' => 'Espaces jardin', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabels = [
    'potager' => 'Potager', 'parterre_fleuri' => 'Parterre fleuri', 'pelouse' => 'Pelouse',
    'haie' => 'Haie', 'arbre_fruitier' => 'Arbre fruitier', 'serre' => 'Serre',
    'verger' => 'Verger', 'rocaille' => 'Rocaille', 'bassin' => 'Bassin',
    'compost' => 'Compost', 'rucher' => 'Rucher', 'autre' => 'Autre'
];
$typeIcons = [
    'potager' => 'fa-carrot', 'parterre_fleuri' => 'fa-seedling', 'pelouse' => 'fa-leaf',
    'haie' => 'fa-tree', 'arbre_fruitier' => 'fa-apple-alt', 'serre' => 'fa-home',
    'verger' => 'fa-lemon', 'rocaille' => 'fa-mountain', 'bassin' => 'fa-water',
    'compost' => 'fa-recycle', 'rucher' => 'fa-archive', 'autre' => 'fa-circle'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-tree me-2 text-success"></i>Espaces jardin</h2>
        <?php if ($isManager && $selectedResidence): ?>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalEspace" onclick="resetEspaceModal()">
            <i class="fas fa-plus me-1"></i>Nouvel espace
        </button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Résidence :</label></div>
                <div class="col-12 col-md-4">
                    <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">— Sélectionner —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5 ms-auto">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher un espace...">
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour afficher ses espaces jardin.</div>
    <?php elseif (empty($espaces)): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-circle me-2"></i>Aucun espace jardin défini pour cette résidence. <?= $isManager ? 'Créez-en un via le bouton ci-dessus.' : '' ?></div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="espacesTable">
                <thead class="table-light">
                    <tr>
                        <th class="no-sort" style="width:80px">Photo</th>
                        <th>Nom</th>
                        <th>Type</th>
                        <th class="text-end">Surface (m²)</th>
                        <th class="text-center">Tâches</th>
                        <th>Description</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($espaces as $e): ?>
                    <tr class="<?= !$e['actif'] ? 'text-muted' : '' ?>">
                        <td class="text-center">
                            <?php if (!empty($e['photo'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($e['photo']) ?>" alt="" class="rounded"
                                     style="width:60px;height:45px;object-fit:cover;cursor:zoom-in"
                                     title="Double-clic pour agrandir"
                                     ondblclick="showPhoto('<?= BASE_URL . '/' . htmlspecialchars($e['photo']) ?>', <?= htmlspecialchars(json_encode($e['nom']), ENT_QUOTES) ?>)">
                            <?php else: ?>
                                <i class="fas fa-image text-muted" title="Pas de photo"></i>
                            <?php endif; ?>
                        </td>
                        <td><strong><?= htmlspecialchars($e['nom']) ?></strong></td>
                        <td data-sort="<?= htmlspecialchars($typeLabels[$e['type']] ?? $e['type']) ?>"><i class="fas <?= $typeIcons[$e['type']] ?? 'fa-circle' ?> me-2 text-success"></i><?= $typeLabels[$e['type']] ?? $e['type'] ?></td>
                        <td class="text-end" data-sort="<?= (float)($e['surface_m2'] ?? 0) ?>"><?= $e['surface_m2'] ? number_format($e['surface_m2'], 2, ',', ' ') : '—' ?></td>
                        <td class="text-center" data-sort="<?= (int)$e['nb_taches'] ?>">
                            <a href="<?= BASE_URL ?>/jardinage/espaces/taches/<?= $e['id'] ?>" class="badge bg-info text-decoration-none"><?= $e['nb_taches'] ?> tâche(s)</a>
                        </td>
                        <td class="small text-muted" style="max-width:300px"><?= htmlspecialchars(mb_strimwidth($e['description'] ?? '', 0, 80, '...')) ?></td>
                        <td class="text-center" data-sort="<?= $e['actif'] ? 1 : 0 ?>"><span class="badge bg-<?= $e['actif'] ? 'success' : 'secondary' ?>"><?= $e['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="text-end">
                            <?php if ($isManager): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='editEspace(<?= json_encode($e) ?>)'><i class="fas fa-edit"></i></button>
                            <?php if ($e['actif']): ?>
                            <form method="POST" action="<?= BASE_URL ?>/jardinage/espaces/delete/<?= $e['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver cet espace ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver"><i class="fas fa-times"></i></button>
                            </form>
                            <?php else: ?>
                            <form method="POST" action="<?= BASE_URL ?>/jardinage/espaces/activate/<?= $e['id'] ?>" class="d-inline" onsubmit="return confirm('Réactiver cet espace ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                                <button type="submit" class="btn btn-sm btn-outline-success" title="Réactiver"><i class="fas fa-check"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/jardinage/espaces/taches/<?= $e['id'] ?>" class="btn btn-sm btn-outline-info"><i class="fas fa-tasks"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($isManager && $selectedResidence): ?>
<div class="modal fade" id="modalEspace" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formEspace" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="modalEspaceTitle"><i class="fas fa-plus me-2"></i>Nouvel espace</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" id="fieldNom" class="form-control" required maxlength="150" placeholder="Ex : Potager nord, Parterre entrée...">
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Type</label>
                            <select name="type" id="fieldType" class="form-select">
                                <?php foreach ($typeLabels as $k => $l): ?>
                                <option value="<?= $k ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Surface (m²)</label>
                            <input type="number" step="0.01" min="0" name="surface_m2" id="fieldSurface" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="fieldDescription" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Photo <small class="text-muted">(JPG, PNG, WEBP · max 5 Mo)</small></label>
                        <div id="currentPhotoBlock" class="mb-2" style="display:none">
                            <img id="currentPhotoImg" src="" alt="" class="rounded me-2" style="width:120px;height:90px;object-fit:cover">
                            <button type="submit" form="formDeletePhotoEspace" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer la photo</button>
                        </div>
                        <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="form-check" id="divActif" style="display:none">
                        <input class="form-check-input" type="checkbox" name="actif" id="fieldActif" value="1" checked>
                        <label class="form-check-label" for="fieldActif">Actif</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form séparé pour suppression photo (HTML interdit le form nesting dans formEspace) -->
<form id="formDeletePhotoEspace" method="POST" action="" onsubmit="return confirm('Supprimer cette photo ?')" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">
</form>

<script>
function resetEspaceModal() {
    const f = document.getElementById('formEspace');
    f.action = '<?= BASE_URL ?>/jardinage/espaces/create';
    document.getElementById('modalEspaceTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nouvel espace';
    document.getElementById('fieldNom').value = '';
    document.getElementById('fieldType').value = 'autre';
    document.getElementById('fieldSurface').value = '';
    document.getElementById('fieldDescription').value = '';
    document.getElementById('divActif').style.display = 'none';
    document.getElementById('fieldActif').checked = true;
    document.getElementById('currentPhotoBlock').style.display = 'none';
    f.querySelector('[name=photo]').value = '';
}

let currentEspaceId = null;
function editEspace(e) {
    const f = document.getElementById('formEspace');
    f.action = '<?= BASE_URL ?>/jardinage/espaces/update/' + e.id;
    currentEspaceId = e.id;
    document.getElementById('modalEspaceTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier espace';
    document.getElementById('fieldNom').value = e.nom;
    document.getElementById('fieldType').value = e.type;
    document.getElementById('fieldSurface').value = e.surface_m2 || '';
    document.getElementById('fieldDescription').value = e.description || '';
    document.getElementById('divActif').style.display = 'block';
    document.getElementById('fieldActif').checked = parseInt(e.actif) === 1;
    f.querySelector('[name=photo]').value = '';
    const block = document.getElementById('currentPhotoBlock');
    if (e.photo) {
        document.getElementById('currentPhotoImg').src = '<?= BASE_URL ?>/' + e.photo;
        document.getElementById('formDeletePhotoEspace').action = '<?= BASE_URL ?>/jardinage/espaces/photoDelete/' + e.id;
        block.style.display = 'block';
    } else {
        block.style.display = 'none';
    }
    new bootstrap.Modal(document.getElementById('modalEspace')).show();
}
</script>
<?php endif; ?>

<!-- Modal viewer photo (accessible à tous) -->
<div class="modal fade" id="photoViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="photoViewerTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="photoViewerImg" src="" alt="" style="max-width:100%;max-height:80vh;object-fit:contain">
            </div>
        </div>
    </div>
</div>
<script>
function showPhoto(src, nom) {
    document.getElementById('photoViewerImg').src = src;
    document.getElementById('photoViewerTitle').textContent = nom;
    new bootstrap.Modal(document.getElementById('photoViewer')).show();
}
</script>

<?php if ($selectedResidence && !empty($espaces)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('espacesTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    excludeColumns: [0, 7],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
