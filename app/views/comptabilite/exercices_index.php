<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',  'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',     'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-calendar-alt',   'text' => 'Exercices',        'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatut = [
    'ouvert'  => 'success',
    'cloture' => 'warning',
    'archive' => 'secondary',
];
$isAdmin = ($_SESSION['user_role'] ?? '') === 'admin';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-calendar-alt me-2 text-primary"></i>Exercices comptables</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newExerciceModal">
            <i class="fas fa-plus me-1"></i>Nouvel exercice
        </button>
    </div>

    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($statuts as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $statut === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($exercices)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun exercice trouvé</h5>
            <p class="text-muted">Cliquez sur « Nouvel exercice » pour en créer un.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="exoTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Résidence</th>
                            <th>Année</th>
                            <th>Période</th>
                            <th class="text-end">Recettes TTC</th>
                            <th class="text-end">Dépenses TTC</th>
                            <th class="text-end">Résultat</th>
                            <th class="text-center">Écritures</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($exercices as $ex):
                            $stats = $ex['stats'];
                        ?>
                        <tr>
                            <td><strong>#<?= (int)$ex['id'] ?></strong></td>
                            <td><?= htmlspecialchars($ex['residence_nom'] ?? '—') ?></td>
                            <td><strong><?= (int)$ex['annee'] ?></strong></td>
                            <td>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($ex['date_debut']))) ?> →</small><br>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($ex['date_fin']))) ?></small>
                            </td>
                            <td class="text-end" data-sort="<?= (float)$stats['recettes_ttc'] ?>"><?= number_format((float)$stats['recettes_ttc'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= (float)$stats['depenses_ttc'] ?>"><?= number_format((float)$stats['depenses_ttc'], 2, ',', ' ') ?> €</td>
                            <td class="text-end" data-sort="<?= (float)$stats['resultat'] ?>">
                                <strong class="<?= $stats['resultat'] >= 0 ? 'text-success' : 'text-danger' ?>">
                                    <?= number_format((float)$stats['resultat'], 2, ',', ' ') ?> €
                                </strong>
                            </td>
                            <td class="text-center"><?= (int)$stats['nb_ecritures'] ?></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badgeStatut[$ex['statut']] ?? 'secondary' ?>"><?= htmlspecialchars($statuts[$ex['statut']] ?? $ex['statut']) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/bilan?residence_id=<?= (int)$ex['copropriete_id'] ?>&annee=<?= (int)$ex['annee'] ?>" class="btn btn-sm btn-outline-primary" title="Bilan">
                                    <i class="fas fa-balance-scale"></i>
                                </a>

                                <?php if ($ex['statut'] === 'ouvert'): ?>
                                <form method="POST" action="<?= BASE_URL ?>/comptabilite/exerciceCloturer/<?= (int)$ex['id'] ?>" class="d-inline" onsubmit="return confirm('Clôturer cet exercice ? Les écritures seront gelées.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning" title="Clôturer">
                                        <i class="fas fa-lock"></i>
                                    </button>
                                </form>
                                <?php elseif ($ex['statut'] === 'cloture'): ?>
                                <?php if ($isAdmin): ?>
                                <form method="POST" action="<?= BASE_URL ?>/comptabilite/exerciceReouvrir/<?= (int)$ex['id'] ?>" class="d-inline" onsubmit="return confirm('Ré-ouvrir cet exercice clôturé ? Cette action est exceptionnelle.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary" title="Ré-ouvrir (admin)">
                                        <i class="fas fa-lock-open"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                                <form method="POST" action="<?= BASE_URL ?>/comptabilite/exerciceArchiver/<?= (int)$ex['id'] ?>" class="d-inline" onsubmit="return confirm('Archiver définitivement cet exercice ?');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-dark" title="Archiver">
                                        <i class="fas fa-archive"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="exoPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="exoInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="exoPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Nouvel exercice -->
<div class="modal fade" id="newExerciceModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/comptabilite/exerciceCreer" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouvel exercice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Résidence <span class="text-danger">*</span></label>
                    <select name="residence_id" class="form-select" required>
                        <option value="">— Choisir —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Année <span class="text-danger">*</span></label>
                    <input type="number" name="annee" class="form-control" min="2020" max="2050" value="<?= (int)date('Y') ?>" required>
                    <small class="text-muted">Les dates seront automatiquement positionnées au 1er janvier → 31 décembre.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Budget prévisionnel (€)</label>
                    <input type="number" name="budget_previsionnel" class="form-control" step="0.01" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes (optionnelles)</label>
                    <textarea name="notes" rows="2" class="form-control"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Créer</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($exercices)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('exoTable', {
    rowsPerPage: 25,
    excludeColumns: [9],
    paginationId: 'exoPager',
    infoId: 'exoInfo'
});
</script>
<?php endif; ?>
