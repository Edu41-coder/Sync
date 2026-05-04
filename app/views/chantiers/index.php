<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-hammer',         'text' => 'Chantiers',       'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$badgePhase = [
    'diagnostic'=>'secondary','cahier_charges'=>'secondary','devis'=>'info','decision'=>'warning',
    'commande'=>'primary','execution'=>'warning','reception'=>'info','garantie'=>'success','cloture'=>'dark',
];
$badgeStatut = ['actif'=>'success','suspendu'=>'warning','termine'=>'dark','annule'=>'danger'];
$badgePrio = ['basse'=>'secondary','normale'=>'primary','haute'=>'warning','urgente'=>'danger'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-hammer text-warning me-2"></i>Chantiers / Travaux</h1>
            <p class="text-muted mb-0">Workflow 9 phases · Auto-création garanties à la réception</p>
        </div>
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/chantier/form" class="btn btn-warning">
            <i class="fas fa-plus me-1"></i>Nouveau chantier
        </a>
        <?php endif; ?>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-md-2 col-6">
            <div class="card border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-primary small fw-bold mb-1">Total</h6>
                    <h3 class="mb-0"><?= (int)$stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning small fw-bold mb-1">En cours</h6>
                    <h3 class="mb-0"><?= (int)$stats['en_cours'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-success small fw-bold mb-1">En garantie</h6>
                    <h3 class="mb-0"><?= (int)$stats['en_garantie'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-start border-danger border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-danger small fw-bold mb-1">Urgents</h6>
                    <h3 class="mb-0"><?= (int)$stats['urgentes'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info small fw-bold mb-1">⚖️ Attente AG</h6>
                    <h3 class="mb-0"><?= (int)$stats['attente_ag'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-2 col-6">
            <div class="card border-start border-dark border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-dark small fw-bold mb-1">Budget total</h6>
                    <h5 class="mb-0"><?= number_format((float)$stats['budget_total'], 0, ',', ' ') ?> €</h5>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?= BASE_URL ?>/chantier/index" class="row g-2 align-items-center">
                <div class="col-auto">
                    <select name="phase" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Toutes phases</option>
                        <?php foreach ($phases as $p): ?>
                        <option value="<?= $p ?>" <?= ($filtres['phase'] ?? '') === $p ? 'selected' : '' ?>>
                            <?= htmlspecialchars($phasesLabels[$p] ?? $p) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-auto">
                    <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Tous statuts</option>
                        <?php foreach (['actif','suspendu','termine','annule'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($filtres['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (!empty($filtres['phase']) || !empty($filtres['statut'])): ?>
                <div class="col-auto">
                    <a href="<?= BASE_URL ?>/chantier/index" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-times"></i>
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
            <?php if (empty($chantiers)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-hammer fa-3x mb-3 d-block opacity-50"></i>
                <h6>Aucun chantier</h6>
                <?php if ($isManager): ?>
                <a href="<?= BASE_URL ?>/chantier/form" class="btn btn-sm btn-warning mt-2">
                    <i class="fas fa-plus me-1"></i>Créer le premier
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableChantiers">
                    <thead class="table-light">
                        <tr>
                            <th>Titre</th>
                            <th>Résidence</th>
                            <th>Catégorie</th>
                            <th>Origine</th>
                            <th>Phase</th>
                            <th class="text-center">Priorité</th>
                            <th>AG</th>
                            <th class="text-end">Montant estimé</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($chantiers as $c): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($c['titre']) ?></strong>
                                <?php if ($c['specialite_nom']): ?>
                                <br><small><span class="badge" style="background:<?= htmlspecialchars($c['specialite_couleur']) ?>;color:#fff;font-size:0.65rem">
                                    <i class="<?= htmlspecialchars($c['specialite_icone']) ?> me-1"></i><?= htmlspecialchars($c['specialite_nom']) ?>
                                </span></small>
                                <?php endif; ?>
                            </td>
                            <td><small><?= htmlspecialchars($c['residence_nom']) ?></small></td>
                            <td><small><?= htmlspecialchars($c['categorie']) ?></small></td>
                            <td data-sort="<?= !empty($c['sinistre_id_lie']) ? 1 : 0 ?>">
                                <?php if (!empty($c['sinistre_id_lie'])): ?>
                                    <a href="<?= BASE_URL ?>/sinistre/show/<?= (int)$c['sinistre_id_lie'] ?>" class="badge bg-danger text-decoration-none" title="<?= htmlspecialchars($c['sinistre_titre'] ?? '') ?>">
                                        <i class="fas fa-shield-alt me-1"></i>Sinistre #<?= (int)$c['sinistre_id_lie'] ?>
                                    </a>
                                <?php else: ?>
                                    <small class="text-muted">Maintenance</small>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-<?= $badgePhase[$c['phase']] ?? 'secondary' ?>"><?= htmlspecialchars($phasesLabels[$c['phase']] ?? $c['phase']) ?></span></td>
                            <td class="text-center"><span class="badge bg-<?= $badgePrio[$c['priorite']] ?? 'secondary' ?>"><?= $c['priorite'] ?></span></td>
                            <td>
                                <?php if ($c['necessite_ag']): ?>
                                    <?php if ($c['ag_id']): ?>
                                        <span class="badge bg-success" title="AG liée"><i class="fas fa-check"></i> AG <?= date('d/m/Y', strtotime($c['date_ag'])) ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">⚖️ Attente AG</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" data-sort="<?= (float)$c['montant_estime'] ?>">
                                <?= $c['montant_estime'] ? number_format((float)$c['montant_estime'], 0, ',', ' ') . ' €' : '—' ?>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/chantier/show/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($isManager): ?>
                                <a href="<?= BASE_URL ?>/chantier/form/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier">
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

<?php if (!empty($chantiers)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableChantiers', {
    rowsPerPage: 15, searchInputId: 'searchInput',
    paginationId: 'pagination', infoId: 'tableInfo', excludeColumns: [8]
});
</script>
<?php endif; ?>
