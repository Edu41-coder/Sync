<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-users',          'text' => 'Résidents',       'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$autonomieBadge = ['autonome'=>'success','semi_autonome'=>'info','dependant'=>'warning','gir1'=>'danger','gir2'=>'danger','gir3'=>'warning','gir4'=>'warning','gir5'=>'info','gir6'=>'info'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-users text-info me-2"></i>Résidents</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> — <?= htmlspecialchars($residenceCourante['ville']) ?> · <?= count($residents) ?> résident<?= count($residents) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <?php if (count($residences) > 1): ?>
        <form method="GET" action="<?= BASE_URL ?>/accueil/residents">
            <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                <?php foreach ($residences as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php elseif (empty($residents)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun résident hébergé dans cette résidence.</div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><?= count($residents) ?> résident<?= count($residents) > 1 ? 's' : '' ?> hébergé<?= count($residents) > 1 ? 's' : '' ?></strong>
            <input type="text" id="searchResidents" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:240px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableResidents">
                    <thead class="table-light">
                        <tr><th>Nom</th><th>Âge</th><th>Lots</th><th>Autonomie</th><th>Régime</th><th>Contact urgence</th><th class="text-center">Notes</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($residents as $r): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars(trim(($r['civilite'] ?? '') . ' ' . $r['prenom'] . ' ' . $r['nom'])) ?></strong>
                                <?php if (!empty($r['telephone_mobile'])): ?>
                                <br><small class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($r['telephone_mobile']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= (int)($r['age'] ?? 0) ?>"><?= $r['age'] ?: '—' ?></td>
                            <td><small><?= htmlspecialchars($r['lots']) ?></small></td>
                            <td>
                                <?php if ($r['niveau_autonomie']): ?>
                                <span class="badge bg-<?= $autonomieBadge[$r['niveau_autonomie']] ?? 'secondary' ?>"><?= htmlspecialchars($r['niveau_autonomie']) ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($r['regime_alimentaire'] ?? '—') ?></small></td>
                            <td>
                                <?php if (!empty($r['urgence_nom'])): ?>
                                <small><?= htmlspecialchars($r['urgence_nom']) ?>
                                <?php if ($r['urgence_lien']): ?> <em>(<?= htmlspecialchars($r['urgence_lien']) ?>)</em><?php endif; ?>
                                <?php if ($r['urgence_telephone']): ?><br><i class="fas fa-phone text-danger me-1"></i><?= htmlspecialchars($r['urgence_telephone']) ?><?php endif; ?>
                                </small>
                                <?php else: ?><small class="text-muted">—</small><?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$r['nb_notes'] > 0): ?>
                                <span class="badge bg-warning text-dark" title="<?= $r['derniere_note'] ? 'Dernière : ' . date('d/m/Y', strtotime($r['derniere_note'])) : '' ?>">
                                    <i class="fas fa-sticky-note me-1"></i><?= (int)$r['nb_notes'] ?>
                                </span>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/accueil/residentNotes/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir / ajouter notes">
                                    <i class="fas fa-comment-medical"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($residents) > 10): ?>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoResidents" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationResidents"></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($residents)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if (count($residents) > 10): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableResidents', {
    rowsPerPage: 10, searchInputId: 'searchResidents',
    paginationId: 'paginationResidents', infoId: 'infoResidents', excludeColumns: [7]
});
</script>
<?php else: ?>
<script>
new DataTable('tableResidents', { searchInputId: 'searchResidents', excludeColumns: [7] });
</script>
<?php endif; ?>
<?php endif; ?>
