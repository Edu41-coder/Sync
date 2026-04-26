<?php
/**
 * Partial réutilisable : page "Fournisseurs {module}" (liste + CRUD des liens résidence).
 *
 * Paramètres attendus en scope (depuis le controller du module) :
 *   - $modulePath   : string ex: 'menage', 'restauration', 'jardinage'
 *   - $moduleLabel  : string ex: 'Ménage', 'Restauration', 'Jardinage'
 *   - $moduleIcon   : string ex: 'fa-broom'
 *   - $moduleColor  : string ex: 'info' (classe Bootstrap : info/warning/success/...)
 *   - $residences   : array [{id,nom}]
 *   - $selectedResidence : int
 *   - $fournisseurs : array (actuellement liés, avec stats commandes : nb_commandes, total_commandes, derniere_commande)
 *   - $disponibles  : array (non liés, filtrés par type_service module)
 *
 * Les actions POST vont vers /{modulePath}/fournisseurs/lier, /update/<pivot_id>, /delier/<pivot_id>.
 * Les controllers délèguent au Model `Fournisseur` (pivot unique fournisseur_residence).
 */
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-truck-loading me-2 text-<?= htmlspecialchars($moduleColor) ?>"></i>Fournisseurs <?= htmlspecialchars($moduleLabel) ?></h2>
        <?php if ($selectedResidence && !empty($disponibles)): ?>
        <button class="btn btn-<?= htmlspecialchars($moduleColor) ?>" data-bs-toggle="modal" data-bs-target="#modalLier">
            <i class="fas fa-link me-1"></i>Lier un fournisseur
        </button>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Résidence :</label></div>
                <div class="col-12 col-md-6">
                    <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">— Sélectionner —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-5 ms-auto">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher fournisseur...">
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour gérer ses fournisseurs <?= htmlspecialchars(strtolower($moduleLabel)) ?>.</div>
    <?php else: ?>

    <?php if (empty($fournisseurs)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-circle me-2"></i>Aucun fournisseur <?= htmlspecialchars(strtolower($moduleLabel)) ?> lié à cette résidence.
        <?= !empty($disponibles)
            ? 'Utilisez le bouton <strong>« Lier un fournisseur »</strong> ci-dessus pour en ajouter.'
            : 'Aucun fournisseur disponible dans la base avec le tag « ' . htmlspecialchars($modulePath) . ' » — créez-en un via <a href="' . BASE_URL . '/fournisseur/create" class="alert-link">/fournisseur/create</a>.' ?>
    </div>
    <?php else: ?>

    <div class="card shadow-sm">
        <div class="card-header py-2"><h6 class="mb-0"><?= count($fournisseurs) ?> fournisseur(s) lié(s)</h6></div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="fournTable">
                <thead class="table-light">
                    <tr>
                        <th>Fournisseur</th>
                        <th>Contact local / global</th>
                        <th>Téléphone</th>
                        <th>Livraison</th>
                        <th class="text-center">Commandes</th>
                        <th class="text-end">Total dépensé</th>
                        <th>Dernière</th>
                        <th class="text-end no-sort" style="width:180px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fournisseurs as $f): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($f['nom']) ?></strong>
                            <?php if (!empty($f['ville'])): ?><br><small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i><?= htmlspecialchars($f['ville']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($f['contact_local'])): ?>
                                <strong><?= htmlspecialchars($f['contact_local']) ?></strong> <small class="text-muted">(local)</small>
                            <?php elseif (!empty($f['contact_nom'])): ?>
                                <small><?= htmlspecialchars($f['contact_nom']) ?> <span class="text-muted">(global)</span></small>
                            <?php else: ?>—<?php endif; ?>
                            <?php if (!empty($f['email'])): ?><br><small><a href="mailto:<?= htmlspecialchars($f['email']) ?>" class="text-decoration-none"><?= htmlspecialchars($f['email']) ?></a></small><?php endif; ?>
                        </td>
                        <td>
                            <?php $tel = $f['telephone_local'] ?? $f['telephone'] ?? null; ?>
                            <?php if ($tel): ?><small><i class="fas fa-phone me-1"></i><a href="tel:<?= htmlspecialchars($tel) ?>" class="text-decoration-none"><?= htmlspecialchars($tel) ?></a></small><?php if (!empty($f['telephone_local'])): ?><br><small class="text-muted">(local)</small><?php endif; ?><?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($f['jour_livraison'])): ?><span class="badge bg-secondary"><?= htmlspecialchars($f['jour_livraison']) ?></span><?php endif; ?>
                            <?php if (!empty($f['delai_livraison_jours'])): ?><br><small class="text-muted"><?= (int)$f['delai_livraison_jours'] ?> j délai</small><?php endif; ?>
                            <?php if (empty($f['jour_livraison']) && empty($f['delai_livraison_jours'])): ?><small class="text-muted">—</small><?php endif; ?>
                        </td>
                        <td class="text-center" data-sort="<?= (int)($f['nb_commandes'] ?? 0) ?>"><span class="badge bg-<?= htmlspecialchars($moduleColor) ?>"><?= (int)($f['nb_commandes'] ?? 0) ?></span></td>
                        <td class="text-end" data-sort="<?= (float)($f['total_commandes'] ?? 0) ?>"><strong><?= number_format((float)($f['total_commandes'] ?? 0), 0, ',', ' ') ?> €</strong></td>
                        <td class="small" data-sort="<?= !empty($f['derniere_commande']) ? strtotime($f['derniere_commande']) : 0 ?>">
                            <?= !empty($f['derniere_commande']) ? date('d/m/Y', strtotime($f['derniere_commande'])) : '—' ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/fournisseur/show/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-info" title="Détail global"><i class="fas fa-eye"></i></a>
                            <button class="btn btn-sm btn-outline-primary" onclick='editLien(<?= json_encode($f) ?>)' title="Modifier le lien"><i class="fas fa-edit"></i></button>
                            <form method="GET" action="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/fournisseurs/delier/<?= (int)$f['pivot_id'] ?>" class="d-inline" onsubmit="return confirm('Délier ce fournisseur de la résidence ? (statut → inactif)')">
                                <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Délier"><i class="fas fa-unlink"></i></button>
                            </form>
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
    <?php endif; // empty fournisseurs ?>
    <?php endif; // !$selectedResidence ?>
</div>

<?php if ($selectedResidence): ?>
<!-- Modal lier un nouveau fournisseur -->
<div class="modal fade" id="modalLier" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/fournisseurs/lier">
                <div class="modal-header bg-<?= htmlspecialchars($moduleColor) ?> text-white">
                    <h5 class="modal-title"><i class="fas fa-link me-2"></i>Lier un fournisseur à la résidence</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">

                    <div class="mb-3">
                        <label class="form-label">Fournisseur <span class="text-danger">*</span></label>
                        <select name="fournisseur_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($disponibles as $d): ?>
                            <option value="<?= (int)$d['id'] ?>"><?= htmlspecialchars($d['nom']) ?><?= !empty($d['type_service']) ? ' — ' . htmlspecialchars($d['type_service']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Seuls les fournisseurs tagués « <?= htmlspecialchars($modulePath) ?> » et pas encore liés apparaissent.</small>
                    </div>

                    <h6 class="text-muted text-uppercase small mt-4 mb-2">Informations locales (optionnel)</h6>
                    <p class="text-muted small">Ces champs surchargent les infos globales pour cette résidence uniquement.</p>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact local</label>
                            <input type="text" name="contact_local" class="form-control" maxlength="150" placeholder="Ex : Commercial Durand">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone local</label>
                            <input type="text" name="telephone_local" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Jour(s) de livraison</label>
                            <input type="text" name="jour_livraison" class="form-control" maxlength="50" placeholder="Ex : Lundi et Jeudi">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Délai (jours)</label>
                            <input type="number" min="0" name="delai_livraison_jours" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-<?= htmlspecialchars($moduleColor) ?>"><i class="fas fa-link me-1"></i>Lier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal édition du lien -->
<div class="modal fade" id="modalEditLien" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formEditLien">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le lien — <span id="editFournNom"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact local</label>
                            <input type="text" name="contact_local" id="editContactLocal" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone local</label>
                            <input type="text" name="telephone_local" id="editTelLocal" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Jour(s) de livraison</label>
                            <input type="text" name="jour_livraison" id="editJourLiv" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Délai (jours)</label>
                            <input type="number" min="0" name="delai_livraison_jours" id="editDelai" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
const PF_MODULE_PATH = <?= json_encode($modulePath) ?>;
function editLien(f) {
    document.getElementById('formEditLien').action = '<?= BASE_URL ?>/' + PF_MODULE_PATH + '/fournisseurs/update/' + f.pivot_id;
    document.getElementById('editFournNom').textContent = f.nom;
    document.getElementById('editContactLocal').value = f.contact_local || '';
    document.getElementById('editTelLocal').value = f.telephone_local || '';
    document.getElementById('editJourLiv').value = f.jour_livraison || '';
    document.getElementById('editDelai').value = f.delai_livraison_jours || '';
    document.getElementById('editNotes').value = f.pivot_notes || f.notes_residence || '';
    new bootstrap.Modal(document.getElementById('modalEditLien')).show();
}
</script>
<?php endif; // $selectedResidence ?>

<?php if ($selectedResidence && !empty($fournisseurs)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('fournTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    excludeColumns: [7],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
