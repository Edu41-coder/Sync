<?php
$isEdit = !empty($fournisseur);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => BASE_URL . '/fournisseur/index'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? htmlspecialchars($fournisseur['nom']) : 'Nouveau', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typesLabels = Fournisseur::TYPES_SERVICE;
$typesSelected = $isEdit && !empty($fournisseur['type_service'])
    ? explode(',', $fournisseur['type_service']) : [];
?>

<div class="container-fluid py-4" style="max-width:1000px">
    <h2 class="mb-4">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2 text-primary"></i>
        <?= $isEdit ? 'Modifier ' . htmlspecialchars($fournisseur['nom']) : 'Nouveau fournisseur' ?>
    </h2>

    <form method="POST" action="<?= BASE_URL ?>/fournisseur/<?= $action ?>">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-building me-2"></i>Identité</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">Nom / Raison sociale <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required maxlength="200" value="<?= htmlspecialchars($fournisseur['nom'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">SIRET</label>
                        <input type="text" name="siret" class="form-control" maxlength="14" pattern="[0-9]{14}" title="14 chiffres" value="<?= htmlspecialchars($fournisseur['siret'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Adresse</label>
                        <input type="text" name="adresse" class="form-control" maxlength="255" value="<?= htmlspecialchars($fournisseur['adresse'] ?? '') ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Code postal</label>
                        <input type="text" name="code_postal" class="form-control" maxlength="10" value="<?= htmlspecialchars($fournisseur['code_postal'] ?? '') ?>">
                    </div>
                    <div class="col-md-9">
                        <label class="form-label">Ville</label>
                        <input type="text" name="ville" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['ville'] ?? '') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-tags me-2"></i>Services proposés</h6></div>
            <div class="card-body">
                <p class="text-muted small mb-2">Cochez tous les modules auxquels ce fournisseur est rattaché. Il apparaîtra alors dans ces modules.</p>
                <div class="row g-2">
                    <?php foreach ($typesLabels as $k => $l): ?>
                    <div class="col-md-3 col-sm-4 col-6">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="type_service[]" value="<?= $k ?>" id="type_<?= $k ?>"
                                   <?= in_array($k, $typesSelected) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="type_<?= $k ?>"><?= $l ?></label>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Contact & paiement</h6></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nom du contact</label>
                        <input type="text" name="contact_nom" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['contact_nom'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Téléphone</label>
                        <input type="text" name="telephone" class="form-control" maxlength="20" value="<?= htmlspecialchars($fournisseur['telephone'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" maxlength="100" value="<?= htmlspecialchars($fournisseur['email'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control" maxlength="34" value="<?= htmlspecialchars($fournisseur['iban'] ?? '') ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($fournisseur['notes'] ?? '') ?></textarea>
                    </div>
                    <?php if ($isEdit): ?>
                    <div class="col-12">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="actif" value="1" id="fieldActif" <?= !empty($fournisseur['actif']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="fieldActif">Fournisseur actif</label>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between">
            <a href="<?= BASE_URL ?>/fournisseur/<?= $isEdit ? 'show/' . (int)$fournisseur['id'] : 'index' ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer' ?>
            </button>
        </div>
    </form>

    <?php if ($isEdit):
        // Section "Résidences liées" — disponible uniquement en mode édition.
        $residences = $residences ?? [];
        $residencesNonLiees = $residencesNonLiees ?? [];
    ?>
    <div class="card shadow-sm mt-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">
                <i class="fas fa-building me-2"></i>Résidences liées
                (<?= count(array_filter($residences, fn($r) => $r['statut'] === 'actif')) ?> actives / <?= count($residences) ?> total)
            </h6>
            <div class="d-flex gap-2 align-items-center">
                <?php if (!empty($residences)): ?>
                <input type="text" id="searchResidencesEdit" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:200px">
                <?php endif; ?>
                <?php if (!empty($residencesNonLiees)): ?>
                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalLierEdit">
                    <i class="fas fa-plus me-1"></i>Lier une résidence
                </button>
                <?php endif; ?>
            </div>
        </div>
        <div class="card-body p-0">
            <?php if (empty($residences)): ?>
            <p class="text-center text-muted p-4 mb-0">Aucune résidence liée. Cliquez sur « Lier une résidence ».</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm mb-0" id="tableResidencesEdit">
                    <thead class="table-light">
                        <tr><th>Résidence</th><th>Contact local</th><th>Livraison</th><th class="text-center">Statut</th><th class="text-end">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($residences as $r): ?>
                        <tr class="<?= $r['statut'] === 'actif' ? '' : 'text-muted' ?>">
                            <td data-sort="<?= htmlspecialchars($r['nom']) ?>">
                                <strong><?= htmlspecialchars($r['nom']) ?></strong>
                                <?php if ($r['ville']): ?><br><small class="text-muted"><?= htmlspecialchars($r['ville']) ?></small><?php endif; ?>
                            </td>
                            <td class="small" data-sort="<?= htmlspecialchars($r['contact_local'] ?? '') ?>">
                                <?= $r['contact_local'] ? htmlspecialchars($r['contact_local']) : '—' ?>
                                <?php if ($r['telephone_local']): ?><br><i class="fas fa-phone me-1 text-muted"></i><?= htmlspecialchars($r['telephone_local']) ?><?php endif; ?>
                            </td>
                            <td class="small" data-sort="<?= htmlspecialchars($r['jour_livraison'] ?? '') ?>">
                                <?php if ($r['jour_livraison']): ?><span class="badge bg-secondary"><?= htmlspecialchars($r['jour_livraison']) ?></span><?php endif; ?>
                                <?php if ($r['delai_livraison_jours']): ?><br><small class="text-muted"><?= (int)$r['delai_livraison_jours'] ?> j délai</small><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $r['statut'] === 'actif' ? 'success' : 'secondary' ?>"><?= $r['statut'] ?></span>
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick='editLienEdit(<?= json_encode($r) ?>)' title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <?php if ($r['statut'] === 'actif'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/fournisseur/delier/<?= (int)$r['pivot_id'] ?>" class="d-inline" onsubmit="return confirm('Délier ce fournisseur de la résidence ?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="back" value="edit">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Délier"><i class="fas fa-unlink"></i></button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoResidencesEdit" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationResidencesEdit"></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal : Lier une résidence -->
    <?php if (!empty($residencesNonLiees)): ?>
    <div class="modal fade" id="modalLierEdit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="<?= BASE_URL ?>/fournisseur/lier/<?= (int)$fournisseur['id'] ?>">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Lier une résidence</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="back" value="edit">
                        <div class="mb-3">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($residencesNonLiees as $r): ?>
                                <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nom']) ?><?= $r['ville'] ? ' (' . htmlspecialchars($r['ville']) . ')' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact local</label>
                                <input type="text" name="contact_local" class="form-control" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone local</label>
                                <input type="text" name="telephone_local" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jour de livraison</label>
                                <input type="text" name="jour_livraison" class="form-control" maxlength="50" placeholder="Ex: Mardi">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Délai (jours)</label>
                                <input type="number" name="delai_livraison_jours" class="form-control" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success"><i class="fas fa-link me-1"></i>Lier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Modal : Modifier un lien existant -->
    <div class="modal fade" id="modalEditLienEdit" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" id="formEditLienEdit">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le lien</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <input type="hidden" name="back" value="edit">
                        <p class="mb-3"><strong>Résidence :</strong> <span id="lienResidenceNom"></span></p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact local</label>
                                <input type="text" name="contact_local" id="lienContact" class="form-control" maxlength="150">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone local</label>
                                <input type="text" name="telephone_local" id="lienTel" class="form-control" maxlength="30">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jour de livraison</label>
                                <input type="text" name="jour_livraison" id="lienJour" class="form-control" maxlength="50">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Délai (jours)</label>
                                <input type="number" name="delai_livraison_jours" id="lienDelai" class="form-control" min="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Statut</label>
                                <select name="statut" id="lienStatut" class="form-select">
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" id="lienNotes" class="form-control" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Enregistrer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    function editLienEdit(r) {
        document.getElementById('formEditLienEdit').action = '<?= BASE_URL ?>/fournisseur/updateLien/' + r.pivot_id;
        document.getElementById('lienResidenceNom').textContent = r.nom + (r.ville ? ' (' + r.ville + ')' : '');
        document.getElementById('lienContact').value = r.contact_local || '';
        document.getElementById('lienTel').value = r.telephone_local || '';
        document.getElementById('lienJour').value = r.jour_livraison || '';
        document.getElementById('lienDelai').value = r.delai_livraison_jours || '';
        document.getElementById('lienStatut').value = r.statut || 'actif';
        document.getElementById('lienNotes').value = r.notes || '';
        new bootstrap.Modal(document.getElementById('modalEditLienEdit')).show();
    }
    </script>

    <?php if (!empty($residences)): ?>
    <script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
    <script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
    <script>
    new DataTableWithPagination('tableResidencesEdit', {
        rowsPerPage: 10,
        searchInputId: 'searchResidencesEdit',
        paginationId: 'paginationResidencesEdit',
        infoId: 'infoResidencesEdit',
        excludeColumns: [4]
    });
    </script>
    <?php endif; ?>
    <?php endif; ?>
</div>
