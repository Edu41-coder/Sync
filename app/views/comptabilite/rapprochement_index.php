<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',         'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-university',     'text' => 'Rapprochement bancaire','url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-university me-2 text-primary"></i>Rapprochement bancaire</h2>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#importModal">
            <i class="fas fa-upload me-1"></i>Importer un relevé CSV
        </button>
    </div>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Importez un relevé bancaire CSV pour rapprocher chaque opération avec une écriture comptable.
        Le système suggère automatiquement les meilleurs candidats — la validation reste manuelle.
        <strong>Pilote — non contractuel.</strong>
    </div>

    <?php if (empty($imports)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-file-csv fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun import de relevé bancaire</h5>
            <p class="text-muted">Cliquez sur « Importer un relevé CSV » pour commencer.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="importsTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Résidence</th>
                            <th>Fichier</th>
                            <th>Période</th>
                            <th class="text-center">Ops</th>
                            <th class="text-center">Rapprochées</th>
                            <th class="text-center">Progression</th>
                            <th>Importé</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($imports as $imp):
                            $total = (int)$imp['nb_operations'];
                            $rapp  = (int)$imp['nb_rapp'];
                            $pct   = $total > 0 ? round(100 * $rapp / $total) : 0;
                            $color = $pct >= 90 ? 'success' : ($pct >= 50 ? 'warning' : 'danger');
                        ?>
                        <tr>
                            <td><strong>#<?= (int)$imp['id'] ?></strong></td>
                            <td><?= htmlspecialchars($imp['residence_nom'] ?? '—') ?></td>
                            <td>
                                <small><i class="fas fa-file-csv me-1"></i><?= htmlspecialchars($imp['nom_fichier']) ?></small>
                            </td>
                            <td>
                                <?php if ($imp['periode_debut']): ?>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($imp['periode_debut']))) ?> →</small><br>
                                <small><?= htmlspecialchars(date('d/m/Y', strtotime($imp['periode_fin']))) ?></small>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $total ?></td>
                            <td class="text-center"><?= $rapp ?></td>
                            <td class="text-center" data-sort="<?= $pct ?>">
                                <div class="progress" style="height: 20px; min-width: 100px;">
                                    <div class="progress-bar bg-<?= $color ?>" role="progressbar"
                                         style="width: <?= $pct ?>%"><?= $pct ?>%</div>
                                </div>
                            </td>
                            <td>
                                <small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($imp['imported_at']))) ?></small><br>
                                <small class="text-muted">par <?= htmlspecialchars($imp['imported_by_username'] ?? '—') ?></small>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/rapprochementShow/<?= (int)$imp['id'] ?>" class="btn btn-sm btn-outline-primary" title="Détail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <form method="POST" action="<?= BASE_URL ?>/comptabilite/rapprochementDelete/<?= (int)$imp['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer cet import et toutes ses opérations ? Les rapprochements seront perdus.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="importsPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="importsInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="importsPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Import -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= BASE_URL ?>/comptabilite/rapprochementImport" enctype="multipart/form-data" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Importer un relevé bancaire CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <strong>Format attendu :</strong> CSV avec en-tête, séparateur <code>;</code> ou <code>,</code>,
                    encodage UTF-8 ou ISO-8859-1.
                    <br><strong>Colonnes requises :</strong> <code>date</code>, <code>libellé</code>,
                    et soit <code>montant</code>, soit <code>débit</code> + <code>crédit</code>.
                    Format date <code>JJ/MM/AAAA</code> ou <code>AAAA-MM-JJ</code>.
                </div>

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
                    <label class="form-label small fw-bold">Fichier CSV <span class="text-danger">*</span></label>
                    <input type="file" name="csv_file" class="form-control" accept=".csv,.txt" required>
                    <small class="text-muted">Taille max : 5 Mo</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Notes (optionnelles)</label>
                    <textarea name="notes" rows="2" class="form-control" placeholder="Ex: Relevé Crédit Agricole 05/2026"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Importer</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($imports)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('importsTable', {
    rowsPerPage: 25,
    excludeColumns: [8],
    paginationId: 'importsPager',
    infoId: 'importsInfo'
});
</script>
<?php endif; ?>
