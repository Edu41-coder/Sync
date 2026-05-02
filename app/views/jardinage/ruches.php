<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-hive', 'text' => 'Ruches', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutLabels = ['active' => 'Active', 'essaim_capture' => 'Essaim capturé', 'inactive' => 'Inactive', 'morte' => 'Morte'];
$statutColors = ['active' => 'success', 'essaim_capture' => 'info', 'inactive' => 'secondary', 'morte' => 'danger'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0">🐝 Ruches <span class="text-muted small">— apiculture</span></h2>
        <div class="d-flex gap-2">
            <?php if ($selectedResidence): ?>
            <a href="<?= BASE_URL ?>/jardinage/apiculture?residence_id=<?= (int)$selectedResidence ?>" class="btn btn-outline-secondary">
                ⚙️ Configuration apiculture
            </a>
            <?php endif; ?>
            <?php if ($isManager && $selectedResidence): ?>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalRuche" onclick="resetRucheModal()">
                <i class="fas fa-plus me-1"></i>Nouvelle ruche
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune de vos résidences n'a l'option apiculture activée.
        L'option se configure via le formulaire d'édition résidence (colonne <code>coproprietees.ruches</code>).
    </div>
    <?php else: ?>

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
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher...">
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour afficher ses ruches.</div>
    <?php elseif (empty($ruches)): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-circle me-2"></i>Aucune ruche enregistrée. <?= $isManager ? 'Créez-en une via le bouton ci-dessus.' : '' ?></div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="ruchesTable">
                <thead class="table-light">
                    <tr>
                        <th class="no-sort" style="width:80px">Photo</th>
                        <th>Numéro</th>
                        <th>Type</th>
                        <th>Race</th>
                        <th>Emplacement</th>
                        <th class="text-center">Visites</th>
                        <th>Dernière visite</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ruches as $r):
                        $derniereTs = $r['derniere_visite'] ? strtotime($r['derniere_visite']) : 0;
                        $alerteVisite = $r['statut'] === 'active' && (!$derniereTs || $derniereTs < strtotime('-30 days'));
                    ?>
                    <tr class="<?= $alerteVisite ? 'table-warning' : '' ?>">
                        <td class="text-center">
                            <?php if (!empty($r['photo'])): ?>
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($r['photo']) ?>" alt="" class="rounded"
                                     style="width:60px;height:45px;object-fit:cover;cursor:zoom-in"
                                     title="Double-clic pour agrandir"
                                     ondblclick="showPhoto('<?= BASE_URL . '/' . htmlspecialchars($r['photo']) ?>', <?= htmlspecialchars(json_encode('Ruche ' . $r['numero']), ENT_QUOTES) ?>)">
                            <?php else: ?>
                                <span style="font-size:1.5rem">🐝</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars($r['numero']) ?></strong>
                            <?php $nbAlertes = $alertesParRuche[(int)$r['id']] ?? 0; if ($nbAlertes > 0): ?>
                                <span class="badge bg-danger ms-1" title="Traitement(s) requis cette période"><i class="fas fa-exclamation-triangle me-1"></i><?= $nbAlertes ?></span>
                            <?php endif; ?>
                            <?php if ($r['date_installation']): ?><br><small class="text-muted">Installée <?= date('d/m/Y', strtotime($r['date_installation'])) ?></small><?php endif; ?>
                        </td>
                        <td><?= $r['type_ruche'] ? htmlspecialchars($r['type_ruche']) : '—' ?></td>
                        <td><?= $r['race_abeilles'] ? htmlspecialchars($r['race_abeilles']) : '—' ?></td>
                        <td class="small"><?= $r['espace_nom'] ? htmlspecialchars($r['espace_nom']) : '—' ?></td>
                        <td class="text-center" data-sort="<?= (int)$r['nb_visites'] ?>"><span class="badge bg-info"><?= (int)$r['nb_visites'] ?></span></td>
                        <td data-sort="<?= $derniereTs ?>" class="<?= $alerteVisite ? 'text-danger' : '' ?>">
                            <?php if ($r['derniere_visite']): ?>
                                <?= date('d/m/Y', $derniereTs) ?>
                                <?php if ($alerteVisite): ?><br><small><i class="fas fa-exclamation-triangle me-1"></i>&gt; 30 jours</small><?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">Jamais</span>
                                <?php if ($alerteVisite): ?><br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> À visiter</small><?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td class="text-center" data-sort="<?= $r['statut'] ?>"><span class="badge bg-<?= $statutColors[$r['statut']] ?? 'secondary' ?>"><?= $statutLabels[$r['statut']] ?? $r['statut'] ?></span></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/jardinage/ruches/show/<?= $r['id'] ?>" class="btn btn-sm btn-outline-info" title="Détail + carnet"><i class="fas fa-book-open"></i></a>
                            <?php if ($isManager): ?>
                            <button class="btn btn-sm btn-outline-primary" onclick='editRuche(<?= json_encode($r) ?>)' title="Modifier"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="<?= BASE_URL ?>/jardinage/ruches/delete/<?= $r['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver cette ruche ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver"><i class="fas fa-times"></i></button>
                            </form>
                            <?php endif; ?>
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
    <?php endif; ?>
</div>

<?php if ($isManager && $selectedResidence): ?>
<!-- Modal Ruche (create/edit) -->
<div class="modal fade" id="modalRuche" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formRuche" enctype="multipart/form-data">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalRucheTitle"><i class="fas fa-plus me-2"></i>Nouvelle ruche</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Numéro <span class="text-danger">*</span></label>
                            <input type="text" name="numero" id="fieldNumero" class="form-control" required maxlength="50" placeholder="A1, R-2026-03...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Type de ruche</label>
                            <input type="text" name="type_ruche" id="fieldTypeRuche" class="form-control" maxlength="100" placeholder="Dadant, Warré, Langstroth...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <select name="statut" id="fieldStatut" class="form-select" onchange="toggleMotifStatutList()">
                                <?php foreach ($statutLabels as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $k === 'active' ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12" id="divMotifStatutList" style="display:none">
                            <label class="form-label">Motif du changement de statut <small class="text-muted">(optionnel)</small></label>
                            <input type="text" name="motif_statut" id="fieldMotifStatut" class="form-control" maxlength="255"
                                   placeholder="Ex : Essaim capturé, préparation hivernage, morte suite à varroase…">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emplacement (espace rucher)</label>
                            <select name="espace_id" id="fieldEspaceId" class="form-select">
                                <option value="">— Aucun —</option>
                                <?php foreach ($espacesRucher as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (empty($espacesRucher)): ?>
                            <small class="text-muted">Aucun espace de type "rucher" — créez-en un dans <a href="<?= BASE_URL ?>/jardinage/espaces?residence_id=<?= $selectedResidence ?>">Espaces jardin</a>.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date d'installation</label>
                            <input type="date" name="date_installation" id="fieldDateInstall" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Race d'abeilles</label>
                            <input type="text" name="race_abeilles" id="fieldRace" class="form-control" maxlength="100" placeholder="Buckfast, Noire...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Photo <small class="text-muted">(JPG, PNG, WEBP · max 5 Mo)</small></label>
                            <div id="currentPhotoBlock" class="mb-2" style="display:none">
                                <img id="currentPhotoImg" src="" alt="" class="rounded me-2" style="width:120px;height:90px;object-fit:cover">
                                <button type="submit" form="formDeletePhotoRucheList" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer la photo</button>
                            </div>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="fieldNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Form séparé pour suppression photo (HTML interdit le form nesting dans formRuche) -->
<form id="formDeletePhotoRucheList" method="POST" action="" onsubmit="return confirm('Supprimer cette photo ?')" style="display:none">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">
</form>

<script>
let statutOriginal = 'active'; // Valeur à l'ouverture (create = 'active', edit = statut actuel de la ruche)

function resetRucheModal() {
    const f = document.getElementById('formRuche');
    f.action = '<?= BASE_URL ?>/jardinage/ruches/create';
    document.getElementById('modalRucheTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nouvelle ruche';
    document.getElementById('fieldNumero').value = '';
    document.getElementById('fieldTypeRuche').value = '';
    document.getElementById('fieldStatut').value = 'active';
    statutOriginal = 'active';
    document.getElementById('fieldEspaceId').value = '';
    document.getElementById('fieldDateInstall').value = '';
    document.getElementById('fieldRace').value = '';
    document.getElementById('fieldNotes').value = '';
    document.getElementById('fieldMotifStatut').value = '';
    document.getElementById('divMotifStatutList').style.display = 'none';
    document.getElementById('currentPhotoBlock').style.display = 'none';
    f.querySelector('[name=photo]').value = '';
}

function toggleMotifStatutList() {
    const changed = document.getElementById('fieldStatut').value !== statutOriginal;
    document.getElementById('divMotifStatutList').style.display = changed ? 'block' : 'none';
}

function editRuche(r) {
    const f = document.getElementById('formRuche');
    f.action = '<?= BASE_URL ?>/jardinage/ruches/update/' + r.id;
    document.getElementById('modalRucheTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier ruche';
    document.getElementById('fieldNumero').value = r.numero;
    document.getElementById('fieldTypeRuche').value = r.type_ruche || '';
    document.getElementById('fieldStatut').value = r.statut;
    statutOriginal = r.statut;
    document.getElementById('fieldEspaceId').value = r.espace_id || '';
    document.getElementById('fieldDateInstall').value = r.date_installation || '';
    document.getElementById('fieldRace').value = r.race_abeilles || '';
    document.getElementById('fieldNotes').value = r.notes || '';
    document.getElementById('fieldMotifStatut').value = '';
    document.getElementById('divMotifStatutList').style.display = 'none';
    f.querySelector('[name=photo]').value = '';
    const block = document.getElementById('currentPhotoBlock');
    if (r.photo) {
        document.getElementById('currentPhotoImg').src = '<?= BASE_URL ?>/' + r.photo;
        document.getElementById('formDeletePhotoRucheList').action = '<?= BASE_URL ?>/jardinage/ruches/photoDelete/' + r.id;
        block.style.display = 'block';
    } else {
        block.style.display = 'none';
    }
    new bootstrap.Modal(document.getElementById('modalRuche')).show();
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

<?php if ($selectedResidence && !empty($ruches)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('ruchesTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    excludeColumns: [0, 8],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
