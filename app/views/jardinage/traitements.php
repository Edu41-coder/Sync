<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-calendar-alt', 'text' => 'Traitements apicoles', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$moisNoms = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
$priorLabels = [1 => 'Critique', 2 => 'Recommandé', 3 => 'Optionnel'];
$priorColors = [1 => 'danger', 2 => 'warning', 3 => 'info'];

function formatFenetre(int $md, int $mf, array $moisNoms): string {
    if ($md === $mf) return $moisNoms[$md];
    if ($md < $mf) return $moisNoms[$md] . ' → ' . $moisNoms[$mf];
    return $moisNoms[$md] . ' → ' . $moisNoms[$mf] . ' (transverse)';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-calendar-alt me-2 text-warning"></i>Calendrier traitements apicoles</h2>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalTraitement" onclick="resetTraitModal()">
            <i class="fas fa-plus me-1"></i>Nouveau traitement
        </button>
    </div>

    <!-- Section Alertes en cours -->
    <?php if (!empty($alertes)): ?>
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-danger text-white">
            <h6 class="mb-0"><i class="fas fa-exclamation-triangle me-2"></i>Alertes actives (<?= count($alertes) ?>) — traitements en fenêtre non effectués cette année</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light">
                    <tr><th>Priorité</th><th>Ruche</th><th>Résidence</th><th>Traitement</th><th>Produit suggéré</th><th>Fenêtre</th><th class="text-end">Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($alertes as $a): ?>
                    <tr>
                        <td><span class="badge bg-<?= $priorColors[$a['priorite']] ?? 'secondary' ?>"><?= $priorLabels[$a['priorite']] ?></span></td>
                        <td>🐝 <strong><?= htmlspecialchars($a['ruche_numero']) ?></strong></td>
                        <td class="small"><?= htmlspecialchars($a['residence_nom']) ?></td>
                        <td><strong><?= htmlspecialchars($a['traitement_nom']) ?></strong></td>
                        <td class="small"><?= $a['produit_suggere'] ? htmlspecialchars($a['produit_suggere']) : '—' ?></td>
                        <td class="small"><?= formatFenetre((int)$a['mois_debut'], (int)$a['mois_fin'], $moisNoms) ?></td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/jardinage/ruches/show/<?= (int)$a['ruche_id'] ?>" class="btn btn-sm btn-warning" title="Aller à la ruche pour enregistrer la visite">
                                <i class="fas fa-arrow-right me-1"></i>Traiter
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer small text-muted">
            <i class="fas fa-info-circle me-1"></i>Une alerte disparaît automatiquement quand une visite type <strong>Traitement</strong> est enregistrée dans la fenêtre correspondante.
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i>Aucune alerte active : tous les traitements en fenêtre actuelle ont été effectués sur les ruches actives.</div>
    <?php endif; ?>

    <!-- Section Calendrier-type -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Référentiel des traitements (<?= count($traitements) ?>)</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="traitTable">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Portée</th>
                        <th>Fenêtre</th>
                        <th>Priorité</th>
                        <th>Produit suggéré</th>
                        <th>Description</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end no-sort" style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($traitements as $t): ?>
                    <tr class="<?= !$t['actif'] ? 'text-muted' : '' ?>">
                        <td><strong><?= htmlspecialchars($t['nom']) ?></strong></td>
                        <td class="small">
                            <?php if ($t['residence_id']): ?>
                                <span class="badge bg-info text-dark"><?= htmlspecialchars($t['residence_nom']) ?></span>
                            <?php else: ?>
                                <span class="badge bg-secondary">🌍 Template système</span>
                            <?php endif; ?>
                        </td>
                        <td data-sort="<?= (int)$t['mois_debut'] ?>"><?= formatFenetre((int)$t['mois_debut'], (int)$t['mois_fin'], $moisNoms) ?></td>
                        <td data-sort="<?= (int)$t['priorite'] ?>"><span class="badge bg-<?= $priorColors[$t['priorite']] ?? 'secondary' ?>"><?= $priorLabels[$t['priorite']] ?></span></td>
                        <td class="small"><?= $t['produit_suggere'] ? htmlspecialchars($t['produit_suggere']) : '—' ?></td>
                        <td class="small text-muted" style="max-width:300px"><?= htmlspecialchars(mb_strimwidth($t['description'] ?? '', 0, 120, '…')) ?></td>
                        <td class="text-center" data-sort="<?= $t['actif'] ?>"><span class="badge bg-<?= $t['actif'] ? 'success' : 'secondary' ?>"><?= $t['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-primary" onclick='editTrait(<?= json_encode($t) ?>)' title="Modifier"><i class="fas fa-edit"></i></button>
                            <form method="POST" action="<?= BASE_URL ?>/jardinage/traitements/delete/<?= (int)$t['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver ce traitement ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver"><i class="fas fa-times"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($traitements)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucun traitement défini.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<!-- Modal traitement (create/edit) -->
<div class="modal fade" id="modalTraitement" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formTrait">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="modalTraitTitle"><i class="fas fa-plus me-2"></i>Nouveau traitement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-8"><label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" id="fieldNom" class="form-control" required maxlength="150" placeholder="Ex : Traitement varroa été">
                        </div>
                        <div class="col-md-4"><label class="form-label">Portée</label>
                            <select name="residence_id" id="fieldResidenceId" class="form-select">
                                <option value="">🌍 Template système (toutes résidences)</option>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Mois début <span class="text-danger">*</span></label>
                            <select name="mois_debut" id="fieldMoisDebut" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>"><?= $moisNoms[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Mois fin <span class="text-danger">*</span></label>
                            <select name="mois_fin" id="fieldMoisFin" class="form-select" required>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>"><?= $moisNoms[$m] ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Priorité</label>
                            <select name="priorite" id="fieldPriorite" class="form-select">
                                <option value="1">Critique</option>
                                <option value="2" selected>Recommandé</option>
                                <option value="3">Optionnel</option>
                            </select>
                        </div>
                        <div class="col-md-3"><label class="form-label">Produit suggéré</label>
                            <input type="text" name="produit_suggere" id="fieldProduit" class="form-control" maxlength="150" placeholder="Ex : Apivar">
                        </div>
                        <div class="col-12"><label class="form-label">Description</label>
                            <textarea name="description" id="fieldDescription" class="form-control" rows="3" placeholder="Protocole, conditions d'application, précautions…"></textarea>
                        </div>
                        <div class="col-12" id="divActif" style="display:none">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="actif" id="fieldActif" value="1" checked>
                                <label class="form-check-label" for="fieldActif">Actif</label>
                            </div>
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

<script>
function resetTraitModal() {
    const f = document.getElementById('formTrait');
    f.action = '<?= BASE_URL ?>/jardinage/traitements/create';
    document.getElementById('modalTraitTitle').innerHTML = '<i class="fas fa-plus me-2"></i>Nouveau traitement';
    document.getElementById('fieldNom').value = '';
    document.getElementById('fieldResidenceId').value = '';
    document.getElementById('fieldMoisDebut').value = '1';
    document.getElementById('fieldMoisFin').value = '1';
    document.getElementById('fieldPriorite').value = '2';
    document.getElementById('fieldProduit').value = '';
    document.getElementById('fieldDescription').value = '';
    document.getElementById('divActif').style.display = 'none';
    document.getElementById('fieldActif').checked = true;
}
function editTrait(t) {
    const f = document.getElementById('formTrait');
    f.action = '<?= BASE_URL ?>/jardinage/traitements/update/' + t.id;
    document.getElementById('modalTraitTitle').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier le traitement';
    document.getElementById('fieldNom').value = t.nom;
    document.getElementById('fieldResidenceId').value = t.residence_id || '';
    document.getElementById('fieldMoisDebut').value = t.mois_debut;
    document.getElementById('fieldMoisFin').value = t.mois_fin;
    document.getElementById('fieldPriorite').value = t.priorite;
    document.getElementById('fieldProduit').value = t.produit_suggere || '';
    document.getElementById('fieldDescription').value = t.description || '';
    document.getElementById('divActif').style.display = 'block';
    document.getElementById('fieldActif').checked = parseInt(t.actif) === 1;
    new bootstrap.Modal(document.getElementById('modalTraitement')).show();
}
</script>

<?php if (!empty($traitements)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('traitTable', {
    rowsPerPage: 20,
    excludeColumns: [7],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
