<?php
$serviceLabels = ['petit_dejeuner'=>'Petit-déjeuner','dejeuner'=>'Déjeuner','gouter'=>'Goûter','diner'=>'Dîner','snack_bar'=>'Snack-bar'];
$serviceIcons = ['petit_dejeuner'=>'fa-coffee','dejeuner'=>'fa-sun','gouter'=>'fa-cookie','diner'=>'fa-moon','snack_bar'=>'fa-glass-martini'];
?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-cash-register', 'text' => 'Service', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-cash-register me-2 text-danger"></i>Enregistrement des repas</h2>
        <div class="d-flex gap-2">
            <select id="selResidence" class="form-select form-select-sm" style="width:auto" onchange="updatePage()">
                <?php foreach ($residences as $r): ?>
                <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" id="selDate" class="form-control form-control-sm" value="<?= $date ?>" onchange="updatePage()" style="width:auto">
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence.</div>
    <?php else: ?>

    <!-- Boutons de service -->
    <div class="row g-2 mb-4">
        <?php foreach ($serviceLabels as $sKey => $sLabel): ?>
        <div class="col">
            <a href="?residence_id=<?= $selectedResidence ?>&date=<?= $date ?>&type_service=<?= $sKey ?>"
               class="btn w-100 py-3 <?= $typeService === $sKey ? 'btn-warning' : 'btn-outline-secondary' ?>">
                <i class="fas <?= $serviceIcons[$sKey] ?> fa-2x d-block mb-1"></i>
                <small><?= $sLabel ?></small>
            </a>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-4">
        <!-- Formulaire enregistrement -->
        <div class="col-lg-5">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Enregistrer un repas</h6>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/restauration/service/enregistrer" id="formRepas">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                    <input type="hidden" name="date_service" value="<?= $date ?>">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Service <span class="text-danger">*</span></label>
                            <select name="type_service" class="form-select" required>
                                <?php foreach ($serviceLabels as $sKey => $sLabel): ?>
                                <option value="<?= $sKey ?>" <?= $typeService === $sKey ? 'selected' : '' ?>><?= $sLabel ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de client <span class="text-danger">*</span></label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="type_client" id="tcResident" value="resident" checked>
                                <label class="btn btn-outline-primary" for="tcResident"><i class="fas fa-user me-1"></i>Résident</label>
                                <input type="radio" class="btn-check" name="type_client" id="tcHote" value="hote">
                                <label class="btn btn-outline-info" for="tcHote"><i class="fas fa-suitcase me-1"></i>Hôte</label>
                                <input type="radio" class="btn-check" name="type_client" id="tcPassage" value="passage">
                                <label class="btn btn-outline-secondary" for="tcPassage"><i class="fas fa-walking me-1"></i>Passage</label>
                            </div>
                        </div>

                        <!-- Sélection résident -->
                        <div class="mb-3" id="divResident">
                            <label class="form-label">Résident</label>
                            <select name="resident_id" class="form-select" id="selResident">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?= $r['id'] ?>" data-pension="<?= in_array($r['id'], $pensionIds) ? '1' : '0' ?>">
                                    <?= htmlspecialchars($r['civilite'] . ' ' . $r['prenom'] . ' ' . $r['nom']) ?>
                                    <?= in_array($r['id'], $pensionIds) ? ' [PENSION COMPLÈTE]' : '' ?>
                                    - Lot <?= $r['numero_lot'] ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Sélection hôte -->
                        <div class="mb-3 d-none" id="divHote">
                            <label class="form-label">Hôte temporaire</label>
                            <select name="hote_id" class="form-select">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($hotes as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?> (<?= $h['regime_repas'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Nom passage -->
                        <div class="mb-3 d-none" id="divPassage">
                            <label class="form-label">Nom du client</label>
                            <input type="text" name="nom_passage" class="form-control" placeholder="Nom et prénom">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col">
                                <label class="form-label">Facturation</label>
                                <select name="mode_facturation" class="form-select" id="selMode">
                                    <option value="menu">Menu complet</option>
                                    <option value="carte">À la carte</option>
                                    <option value="pension_complete">Pension complète (inclus)</option>
                                </select>
                            </div>
                            <div class="col">
                                <label class="form-label">Couverts</label>
                                <input type="number" name="nb_couverts" class="form-control" value="1" min="1">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Montant (&euro;)</label>
                            <input type="number" name="montant" class="form-control" step="0.01" min="0" value="0" id="inputMontant">
                            <small class="text-muted" id="tarifInfo"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control" placeholder="Supplément, remarque...">
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-danger w-100"><i class="fas fa-check me-2"></i>Enregistrer le repas</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Repas du jour -->
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Repas du <?= date('d/m/Y', strtotime($date)) ?>
                        <?= $typeService ? ' — ' . ($serviceLabels[$typeService] ?? '') : '' ?>
                    </h6>
                    <div class="d-flex gap-2 align-items-center">
                        <input type="text" id="searchInputRepas" class="form-control form-control-sm" placeholder="Rechercher..." style="width:160px">
                        <span class="badge bg-dark"><?= count($repasJour) ?> repas</span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($repasJour)): ?>
                    <p class="text-muted text-center py-4">Aucun repas enregistré.</p>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0" id="repasJourTable">
                        <thead><tr><th>Client</th><th>Service</th><th>Mode</th><th class="text-end">Montant</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($repasJour as $r): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars($r['client_nom'] ?? 'N/A') ?>
                                <span class="badge bg-<?= $r['type_client'] === 'resident' ? 'primary' : ($r['type_client'] === 'hote' ? 'info' : 'secondary') ?>" style="font-size:0.6rem"><?= $r['type_client'] ?></span>
                            </td>
                            <td><i class="fas <?= $serviceIcons[$r['type_service']] ?? 'fa-utensils' ?> me-1"></i><small><?= str_replace('_',' ',$r['type_service']) ?></small></td>
                            <td><span class="badge bg-<?= $r['mode_facturation'] === 'pension_complete' ? 'success' : ($r['mode_facturation'] === 'menu' ? 'warning text-dark' : 'info') ?>"><?= str_replace('_',' ',$r['mode_facturation']) ?></span></td>
                            <td class="text-end" data-sort="<?= $r['mode_facturation'] === 'pension_complete' ? 0 : (float)$r['montant'] ?>"><?= $r['mode_facturation'] === 'pension_complete' ? '<span class="text-success">inclus</span>' : number_format($r['montant'],2,',',' ').' €' ?></td>
                            <td>
                                <form method="POST" action="<?= BASE_URL ?>/restauration/service/supprimer/<?= $r['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ?')">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0"><i class="fas fa-times"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php if (!empty($repasJour)): ?>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="tableInfoRepas"></div>
                    <nav><ul class="pagination pagination-sm mb-0" id="paginationRepas"></ul></nav>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
function updatePage() {
    const rid = document.getElementById('selResidence').value;
    const date = document.getElementById('selDate').value;
    window.location = '<?= BASE_URL ?>/restauration/service?residence_id=' + rid + '&date=' + date;
}

document.querySelectorAll('input[name="type_client"]').forEach(r => {
    r.addEventListener('change', function() {
        document.getElementById('divResident').classList.toggle('d-none', this.value !== 'resident');
        document.getElementById('divHote').classList.toggle('d-none', this.value !== 'hote');
        document.getElementById('divPassage').classList.toggle('d-none', this.value !== 'passage');
        // Auto pension complète si résident premium
        if (this.value !== 'resident') document.getElementById('selMode').value = 'menu';
    });
});

document.getElementById('selResident')?.addEventListener('change', function() {
    const opt = this.options[this.selectedIndex];
    if (opt && opt.dataset.pension === '1') {
        document.getElementById('selMode').value = 'pension_complete';
        document.getElementById('inputMontant').value = '0';
    } else {
        document.getElementById('selMode').value = 'menu';
    }
});

// Tarifs
const tarifs = <?= json_encode($tarifsMap) ?>;
document.querySelector('select[name="type_service"]')?.addEventListener('change', function() {
    const t = tarifs[this.value];
    if (t && document.getElementById('selMode').value === 'menu') {
        document.getElementById('inputMontant').value = t.prix_menu || 0;
        document.getElementById('tarifInfo').textContent = 'Tarif menu : ' + (t.prix_menu || 0) + ' €';
    }
});
</script>

<?php if (!empty($repasJour)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('repasJourTable', {
    rowsPerPage: 15,
    searchInputId: 'searchInputRepas',
    excludeColumns: [4],
    paginationId: 'paginationRepas',
    infoId: 'tableInfoRepas'
});
</script>
<?php endif; ?>
