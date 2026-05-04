<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-history',        'text' => 'Audit trail',     'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

// Couleurs par préfixe d'action
function actionBadgeColor(string $action): string {
    if (str_starts_with($action, 'ecriture'))     return 'primary';
    if (str_starts_with($action, 'exercice'))     return 'warning';
    if (str_starts_with($action, 'bulletin'))     return 'success';
    if (str_starts_with($action, 'tva'))          return 'info';
    if (str_starts_with($action, 'bank'))         return 'secondary';
    if (str_starts_with($action, 'salarie'))      return 'danger';
    if (str_starts_with($action, 'export'))       return 'dark';
    return 'light';
}
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-history me-2 text-primary"></i>Audit trail comptable</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Trace toutes les opérations comptables sensibles : création/modification/suppression d'écritures,
        bulletins de paie, déclarations TVA, rapprochements bancaires, modifications fiches RH, exports FEC.
        <br><strong>Conformité PCG art. 410-1 + RGPD art. 30</strong> — historique consultable et non modifiable.
    </div>

    <!-- Filtres -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Utilisateur</label>
                    <select name="user_id" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($users as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= !empty($filters['user_id']) && (int)$filters['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['username']) ?> (<?= htmlspecialchars($u['role']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Action (contient)</label>
                    <select name="action" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach ($actions as $a): ?>
                        <option value="<?= htmlspecialchars($a) ?>" <?= ($filters['action'] ?? '') === $a ? 'selected' : '' ?>><?= htmlspecialchars($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Table</label>
                    <select name="table" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <?php foreach ($tables as $t): ?>
                        <option value="<?= htmlspecialchars($t) ?>" <?= ($filters['table'] ?? '') === $t ? 'selected' : '' ?>><?= htmlspecialchars($t) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Du</label>
                    <input type="date" name="date_min" value="<?= htmlspecialchars($filters['date_min'] ?? '') ?>" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Au</label>
                    <input type="date" name="date_max" value="<?= htmlspecialchars($filters['date_max'] ?? '') ?>" class="form-control form-control-sm">
                </div>
            </div>
            <div class="row g-2 mt-1">
                <div class="col-md-9">
                    <label class="form-label small fw-bold mb-1">Recherche dans détails (libelle, montant, etc.)</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($filters['search'] ?? '') ?>" class="form-control form-control-sm" placeholder="Ex: 1500, FEC, Domitys...">
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i>Filtrer</button>
                    <a href="<?= BASE_URL ?>/comptabilite/auditTrail" class="btn btn-sm btn-outline-secondary"><i class="fas fa-times"></i></a>
                </div>
            </div>
        </div>
    </form>

    <div class="alert alert-secondary py-2 small">
        <i class="fas fa-info-circle me-1"></i>
        <strong><?= count($entries) ?></strong> entrée(s) affichée(s) <small>(limite 500 — affinez les filtres si besoin)</small>
    </div>

    <?php if (empty($entries)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">Aucune entrée d'audit pour ces filtres.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="auditTable" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Utilisateur</th>
                            <th>Action</th>
                            <th>Table</th>
                            <th>ID enregistrement</th>
                            <th>Détails</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($entries as $e):
                            $color = actionBadgeColor((string)$e['action']);
                            $details = json_decode((string)($e['details'] ?? ''), true);
                        ?>
                        <tr>
                            <td><small><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($e['created_at']))) ?></small></td>
                            <td>
                                <?php if ($e['username']): ?>
                                <strong><?= htmlspecialchars($e['username']) ?></strong>
                                <br><small class="text-muted"><?= htmlspecialchars($e['role'] ?? '') ?></small>
                                <?php else: ?>
                                <span class="text-muted small">— système</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $color ?>"><?= htmlspecialchars($e['action']) ?></span></td>
                            <td><small><?= htmlspecialchars($e['table_name'] ?? '—') ?></small></td>
                            <td>
                                <?php if (!empty($e['record_id'])): ?>
                                <code>#<?= (int)$e['record_id'] ?></code>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (is_array($details) && !empty($details)): ?>
                                <details>
                                    <summary class="small text-muted">Voir (<?= count($details) ?> champs)</summary>
                                    <pre class="small mb-0 mt-1 bg-light p-2 rounded" style="max-height: 200px; overflow: auto;"><?= htmlspecialchars(json_encode($details, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
                                </details>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td><small class="text-muted"><?= htmlspecialchars($e['ip_address'] ?? '—') ?></small></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="auditPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="auditInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="auditPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($entries)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('auditTable', {
    rowsPerPage: 30,
    excludeColumns: [5],
    paginationId: 'auditPager',
    infoId: 'auditInfo'
});
</script>
<?php endif; ?>
