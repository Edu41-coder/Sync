<?php $title = "Propriétaires"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-home', 'text' => 'Propriétaires', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-home text-dark"></i> Propriétaires</h1>
            <a href="<?= BASE_URL ?>/coproprietaire/create" class="btn btn-danger">
                <i class="fas fa-plus me-1"></i>Nouveau propriétaire
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Total propriétaires</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-success mb-1 fw-bold small">Avec contrat actif</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['avec_contrat'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-info mb-1 fw-bold small">Loyers garantis / mois</h6>
                    <h3 class="mb-0 text-gray-800"><?= number_format($stats['revenus_total'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-6">
                    <input type="text" id="searchProp" class="form-control form-control-sm" placeholder="Rechercher nom, ville, email...">
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="propTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0" data-type="number" style="width:50px">#</th>
                            <th class="sortable" data-column="1">Propriétaire</th>
                            <th class="sortable" data-column="2">Contact</th>
                            <th class="sortable" data-column="3">Ville</th>
                            <th class="sortable text-center" data-column="4" data-type="number">Contrats</th>
                            <th class="sortable text-end" data-column="5" data-type="number">Revenus / mois</th>
                            <th class="text-center" data-no-sort style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proprietaires)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Aucun propriétaire</td></tr>
                        <?php else: ?>
                        <?php foreach ($proprietaires as $p): ?>
                        <tr>
                            <td data-sort="<?= $p['id'] ?>"><?= $p['id'] ?></td>
                            <td data-sort="<?= htmlspecialchars($p['nom'] . ' ' . $p['prenom']) ?>">
                                <div class="d-flex align-items-center">
                                    <div class="avatar-circle avatar-sm me-2" style="background:#fd7e14;color:#fff">
                                        <?= strtoupper(substr($p['prenom'], 0, 1) . substr($p['nom'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <strong><?= htmlspecialchars($p['civilite'] . ' ' . $p['prenom'] . ' ' . $p['nom']) ?></strong>
                                        <?php if ($p['profession']): ?><br><small class="text-muted"><?= htmlspecialchars($p['profession']) ?></small><?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td data-sort="<?= htmlspecialchars($p['email'] ?? '') ?>">
                                <?php if ($p['email']): ?><a href="mailto:<?= htmlspecialchars($p['email']) ?>" class="small"><?= htmlspecialchars($p['email']) ?></a><?php endif; ?>
                                <?php if ($p['telephone_mobile']): ?><br><small class="text-muted"><?= htmlspecialchars($p['telephone_mobile']) ?></small><?php endif; ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($p['ville'] ?? '') ?>"><?= htmlspecialchars($p['ville'] ?? '-') ?></td>
                            <td class="text-center" data-sort="<?= $p['nb_contrats_actifs'] ?>">
                                <?php if ($p['nb_contrats_actifs'] > 0): ?>
                                <span class="badge bg-success"><?= $p['nb_contrats_actifs'] ?> actif(s)</span>
                                <?php elseif ($p['nb_contrats'] > 0): ?>
                                <span class="badge bg-secondary"><?= $p['nb_contrats'] ?> (inactifs)</span>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end" data-sort="<?= $p['revenus_mensuels'] ?>">
                                <?= $p['revenus_mensuels'] > 0 ? number_format($p['revenus_mensuels'], 2, ',', ' ') . ' €' : '-' ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/coproprietaire/show/<?= $p['id'] ?>" class="btn btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                                    <?php if ($p['user_id']): ?>
                                    <a href="<?= BASE_URL ?>/admin/users/edit/<?= $p['user_id'] ?>" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo">
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($proprietaires)) ?></span>
                sur <span id="totalEntries"><?= count($proprietaires) ?></span> propriétaires
            </div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('propTable', {
    rowsPerPage: 10,
    searchInputId: 'searchProp',
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
