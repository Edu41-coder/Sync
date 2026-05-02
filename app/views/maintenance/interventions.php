<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-tools',          'text' => 'Interventions',   'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$badgeStatut = [
    'a_planifier' => 'secondary',
    'planifiee'   => 'info',
    'en_cours'    => 'warning',
    'terminee'    => 'success',
    'annulee'     => 'dark'
];
$badgePrio = [
    'basse'    => 'secondary',
    'normale'  => 'primary',
    'haute'    => 'warning',
    'urgente'  => 'danger'
];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-tools text-warning me-2"></i>Interventions</h1>
            <p class="text-muted mb-0"><?= count($interventions) ?> intervention<?= count($interventions) > 1 ? 's' : '' ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/maintenance/planning" class="btn btn-outline-info">
                <i class="fas fa-calendar-alt me-1"></i>Planning
            </a>
            <a href="<?= BASE_URL ?>/maintenance/interventionForm" class="btn btn-warning">
                <i class="fas fa-plus me-1"></i>Nouvelle intervention
            </a>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?= BASE_URL ?>/maintenance/interventions" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous statuts</option>
                        <?php foreach (['a_planifier','planifiee','en_cours','terminee','annulee'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($filtres['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="specialite_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes spécialités</option>
                        <?php foreach ($specialites as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= (int)($filtres['specialite_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($s['nom']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($filtres['statut']) || !empty($filtres['specialite_id'])): ?>
                <div class="col-auto">
                    <a href="<?= BASE_URL ?>/maintenance/interventions" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Réinitialiser
                    </a>
                </div>
                <?php endif; ?>
                <div class="col-auto ms-auto">
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="min-width:240px">
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($interventions)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-tools fa-3x mb-3 d-block opacity-50"></i>
                    <h6>Aucune intervention</h6>
                    <p class="small mb-0">Cliquez sur "Nouvelle intervention" pour en créer une.</p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableInterventions">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th>
                            <th>Spécialité</th>
                            <th>Résidence / Lot</th>
                            <th>Assigné</th>
                            <th>Date prévue</th>
                            <th class="text-center">Priorité</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($interventions as $i): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($i['titre']) ?></strong>
                                <small class="d-block text-muted"><?= htmlspecialchars($i['type_intervention']) ?></small>
                            </td>
                            <td>
                                <span class="badge" style="background:<?= htmlspecialchars($i['specialite_couleur']) ?>;color:#fff">
                                    <i class="<?= htmlspecialchars($i['specialite_icone']) ?> me-1"></i><?= htmlspecialchars($i['specialite_nom']) ?>
                                </span>
                            </td>
                            <td>
                                <?= htmlspecialchars($i['residence_nom']) ?>
                                <?php if ($i['numero_lot']): ?>
                                <br><small class="text-muted">Lot <?= htmlspecialchars($i['numero_lot']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($i['assigne_prenom']): ?>
                                <small><?= htmlspecialchars($i['assigne_prenom'] . ' ' . $i['assigne_nom']) ?></small>
                                <?php elseif ($i['prestataire_externe']): ?>
                                <small class="text-muted"><i class="fas fa-external-link-alt me-1"></i><?= htmlspecialchars($i['prestataire_externe']) ?></small>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td data-sort="<?= !empty($i['date_planifiee']) ? date('Y-m-d H:i', strtotime($i['date_planifiee'])) : '' ?>">
                                <?php if (!empty($i['date_planifiee'])): ?>
                                <small><?= date('d/m/Y H:i', strtotime($i['date_planifiee'])) ?></small>
                                <?php else: ?>
                                <small class="text-muted">à planifier</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badgePrio[$i['priorite']] ?? 'secondary' ?>"><?= $i['priorite'] ?></span>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badgeStatut[$i['statut']] ?? 'secondary' ?>"><?= $i['statut'] ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/maintenance/interventionShow/<?= (int)$i['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($isManager): ?>
                                <a href="<?= BASE_URL ?>/maintenance/interventionForm/<?= (int)$i['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="tableInfo" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($interventions)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableInterventions', {
    rowsPerPage: 15,
    searchInputId: 'searchInput',
    paginationId: 'pagination',
    infoId: 'tableInfo',
    excludeColumns: [7]
});
</script>
<?php endif; ?>
