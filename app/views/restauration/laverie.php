<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-soap', 'text' => 'Laverie', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typesLinge = [
    'nappe'            => 'Nappe',
    'serviette_table'  => 'Serviette de table',
    'torchon'          => 'Torchon',
    'tablier_cuisine'  => 'Tablier cuisine',
    'tenue_service'    => 'Tenue de service',
    'autre'            => 'Autre',
];
$statuts = [
    'envoye'  => ['label' => 'En cours', 'color' => 'warning', 'icon' => 'fas fa-paper-plane'],
    'recu'    => ['label' => 'Reçu',     'color' => 'success', 'icon' => 'fas fa-check'],
    'partiel' => ['label' => 'Partiel',  'color' => 'info',    'icon' => 'fas fa-exclamation'],
    'perdu'   => ['label' => 'Perdu',    'color' => 'danger',  'icon' => 'fas fa-times'],
];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-soap me-2 text-info"></i>Laverie restauration</h2>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalEnvoi">
            <i class="fas fa-plus me-2"></i>Nouvel envoi
        </button>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-start border-warning border-4 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Cycles totaux <?= date('Y') ?></div>
                    <div class="h4 mb-0"><?= (int)($stats['cycles_total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-info border-4 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">En cours</div>
                    <div class="h4 mb-0"><?= (int)($stats['en_cours'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-success border-4 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Coût annuel</div>
                    <div class="h4 mb-0"><?= number_format((float)($stats['cout_total'] ?? 0), 2, ',', ' ') ?> €</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-start border-danger border-4 shadow-sm">
                <div class="card-body">
                    <div class="text-muted small">Pertes (pièces)</div>
                    <div class="h4 mb-0"><?= (int)($stats['pertes'] ?? 0) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre résidence -->
    <?php if (count($residences) > 1): ?>
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center gap-2">
                <label class="form-label mb-0 me-2"><i class="fas fa-building me-1"></i>Résidence :</label>
                <select name="residence_id" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                    <option value="">Toutes</option>
                    <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </form>
    <?php endif; ?>

    <!-- Liste cycles -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <div class="d-flex gap-2 flex-wrap">
                <select id="filterStatut" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous statuts</option>
                    <?php foreach ($statuts as $k => $s): ?>
                        <option value="<?= htmlspecialchars($s['label']) ?>"><?= htmlspecialchars($s['label']) ?></option>
                    <?php endforeach; ?>
                </select>
                <select id="filterType" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous types</option>
                    <?php foreach ($typesLinge as $k => $v): ?>
                        <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:250px">
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover mb-0" id="laverieTable">
                <thead class="table-light">
                    <tr>
                        <th>Date envoi</th>
                        <th>Résidence</th>
                        <th>Type linge</th>
                        <th class="text-center">Envoyé</th>
                        <th class="text-center">Reçu</th>
                        <th class="text-center">Statut</th>
                        <th class="text-end">Coût</th>
                        <th>Date retour</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($cycles)): ?>
                        <tr><td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-soap fa-2x mb-2 d-block"></i>
                            Aucun cycle de laverie. Créez un nouvel envoi.
                        </td></tr>
                    <?php else: foreach ($cycles as $c):
                        $st = $statuts[$c['statut']] ?? $statuts['envoye'];
                        $typeLabel = $typesLinge[$c['type_linge']] ?? $c['type_linge'];
                    ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($c['date_envoi']) ?>">
                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['date_envoi']))) ?>
                                <?php if ($c['user_envoi_nom']): ?>
                                    <br><small class="text-muted">par <?= htmlspecialchars($c['user_envoi_nom']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['residence_nom']) ?></td>
                            <td><strong><?= htmlspecialchars($typeLabel) ?></strong></td>
                            <td class="text-center" data-sort="<?= (int)$c['quantite_envoyee'] ?>">
                                <span class="badge bg-secondary"><?= (int)$c['quantite_envoyee'] ?></span>
                            </td>
                            <td class="text-center" data-sort="<?= $c['quantite_recue'] === null ? -1 : (int)$c['quantite_recue'] ?>">
                                <?php if ($c['quantite_recue'] === null): ?>
                                    <span class="text-muted">—</span>
                                <?php else: ?>
                                    <span class="badge bg-<?= $c['quantite_recue'] == $c['quantite_envoyee'] ? 'success' : 'warning' ?>">
                                        <?= (int)$c['quantite_recue'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $st['color'] ?>">
                                    <i class="<?= $st['icon'] ?> me-1"></i><?= $st['label'] ?>
                                </span>
                            </td>
                            <td class="text-end" data-sort="<?= (float)$c['cout'] ?>">
                                <?= number_format((float)$c['cout'], 2, ',', ' ') ?> €
                            </td>
                            <td data-sort="<?= htmlspecialchars($c['date_retour'] ?? '') ?>">
                                <?php if ($c['date_retour']): ?>
                                    <?= htmlspecialchars(date('d/m/Y H:i', strtotime($c['date_retour']))) ?>
                                    <?php if ($c['user_reception_nom']): ?>
                                        <br><small class="text-muted">par <?= htmlspecialchars($c['user_reception_nom']) ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (in_array($c['statut'], ['envoye'], true)): ?>
                                    <button class="btn btn-sm btn-outline-success btn-reception"
                                            data-id="<?= (int)$c['id'] ?>"
                                            data-envoyee="<?= (int)$c['quantite_envoyee'] ?>"
                                            data-type="<?= htmlspecialchars($typeLabel) ?>"
                                            title="Réceptionner" data-bs-toggle="tooltip">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                <?php endif; ?>
                                <?php if ($isManager): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/restauration/laverie/delete/<?= (int)$c['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce cycle ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer" data-bs-toggle="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <div class="row align-items-center">
                <div class="col-md-5">
                    <p class="mb-0 text-muted small" id="tableInfo">
                        Affichage de <strong><span id="startEntry">0</span></strong>
                        à <strong><span id="endEntry">0</span></strong>
                        sur <strong><span id="totalEntries">0</span></strong> cycles
                    </p>
                </div>
                <div class="col-md-7">
                    <ul class="pagination pagination-sm justify-content-end mb-0" id="pagination"></ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nouvel envoi -->
<div class="modal fade" id="modalEnvoi" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/restauration/laverie/create">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Nouvel envoi en laverie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required>
                                <?php if (count($residences) > 1): ?>
                                    <option value="">— Choisir —</option>
                                <?php endif; ?>
                                <?php foreach ($residences as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Type de linge <span class="text-danger">*</span></label>
                            <select name="type_linge" class="form-select" required>
                                <?php foreach ($typesLinge as $k => $v): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantité envoyée <span class="text-danger">*</span></label>
                            <input type="number" name="quantite_envoyee" class="form-control" min="1" value="1" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'envoi</label>
                            <input type="datetime-local" name="date_envoi" class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Coût estimé (€)</label>
                            <input type="number" name="cout" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Observations éventuelles..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-2"></i>Enregistrer l'envoi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Réception -->
<div class="modal fade" id="modalReception" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formReception">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-undo me-2"></i>Réceptionner le linge</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Linge envoyé : <strong id="receptionType">-</strong> — <strong id="receptionQte">0</strong> pièces
                    </div>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Quantité reçue <span class="text-danger">*</span></label>
                            <input type="number" name="quantite_recue" id="quantiteRecue" class="form-control" min="0" required>
                            <small class="text-muted">Si quantité reçue &lt; envoyée → statut "Partiel" (ou "Perdu" si 0)</small>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes (optionnel)</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Pièces manquantes, dégâts, etc."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Confirmer la réception</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    new DataTableWithPagination('laverieTable', {
        rowsPerPage: 15,
        searchInputId: 'searchInput',
        filters: [
            { id: 'filterStatut', column: 5 },
            { id: 'filterType',   column: 2 }
        ],
        excludeColumns: [8],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });

    document.querySelectorAll('.btn-reception').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const envoyee = parseInt(this.dataset.envoyee, 10);
            document.getElementById('receptionType').textContent = this.dataset.type;
            document.getElementById('receptionQte').textContent = envoyee;
            const input = document.getElementById('quantiteRecue');
            input.value = envoyee;
            input.max = envoyee;
            document.getElementById('formReception').action = '<?= BASE_URL ?>/restauration/laverie/reception/' + id;
            new bootstrap.Modal(document.getElementById('modalReception')).show();
        });
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
});
</script>
