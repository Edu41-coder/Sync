<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-file-invoice', 'text' => 'Factures', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-file-invoice me-2 text-warning"></i>Factures Restauration</h2>
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/restauration/factures/create" class="btn btn-warning"><i class="fas fa-plus me-2"></i>Nouvelle facture</a>
        <?php endif; ?>
    </div>

    <!-- Stats mois -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <h4 class="mb-0"><?= $stats['nb_factures'] ?></h4>
                <small class="text-muted">Factures ce mois</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <h4 class="mb-0 text-success"><?= number_format($stats['total_ttc'], 0, ',', ' ') ?> &euro;</h4>
                <small class="text-muted">Total TTC</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <h4 class="mb-0 text-primary"><?= $stats['payees'] ?></h4>
                <small class="text-muted">Payées</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3">
                <h4 class="mb-0 text-warning"><?= $stats['en_attente'] ?></h4>
                <small class="text-muted">En attente</small>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between flex-wrap gap-2">
            <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
                <input type="date" name="date_debut" class="form-control form-control-sm" value="<?= $dateDebut ?>" style="width:auto">
                <span class="text-muted">—</span>
                <input type="date" name="date_fin" class="form-control form-control-sm" value="<?= $dateFin ?>" style="width:auto">
                <select name="statut" class="form-select form-select-sm" style="width:auto">
                    <option value="">Tous statuts</option>
                    <option value="brouillon" <?= $statut === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                    <option value="emise" <?= $statut === 'emise' ? 'selected' : '' ?>>Émise</option>
                    <option value="payee" <?= $statut === 'payee' ? 'selected' : '' ?>>Payée</option>
                    <option value="annulee" <?= $statut === 'annulee' ? 'selected' : '' ?>>Annulée</option>
                </select>
                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-filter me-1"></i>Filtrer</button>
            </form>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="facturesTable">
                <thead>
                    <tr>
                        <th>N° Facture</th>
                        <th>Date</th>
                        <th>Client</th>
                        <th>Résidence</th>
                        <th class="text-end">HT</th>
                        <th class="text-end">TTC</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($factures)): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Aucune facture sur cette période.</td></tr>
                    <?php else: ?>
                    <?php
                    $statutColors = ['brouillon'=>'secondary','emise'=>'warning','payee'=>'success','annulee'=>'danger'];
                    foreach ($factures as $f): ?>
                    <tr>
                        <td><a href="<?= BASE_URL ?>/restauration/factures/show/<?= $f['id'] ?>" class="fw-bold"><?= htmlspecialchars($f['numero_facture']) ?></a></td>
                        <td><?= date('d/m/Y', strtotime($f['date_facture'])) ?></td>
                        <td>
                            <?= htmlspecialchars($f['client_nom'] ?? 'N/A') ?>
                            <span class="badge bg-<?= $f['type_client'] === 'resident' ? 'primary' : ($f['type_client'] === 'hote' ? 'info' : 'secondary') ?>" style="font-size:0.6rem"><?= $f['type_client'] ?></span>
                        </td>
                        <td><small><?= htmlspecialchars($f['residence_nom']) ?></small></td>
                        <td class="text-end"><?= number_format($f['montant_ht'], 2, ',', ' ') ?> &euro;</td>
                        <td class="text-end"><strong><?= number_format($f['montant_ttc'], 2, ',', ' ') ?> &euro;</strong></td>
                        <td class="text-center"><span class="badge bg-<?= $statutColors[$f['statut']] ?? 'secondary' ?>"><?= ucfirst($f['statut']) ?></span></td>
                        <td class="text-center">
                            <a href="<?= BASE_URL ?>/restauration/factures/show/<?= $f['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            <?php if ($isManager && $f['statut'] === 'emise'): ?>
                            <a href="<?= BASE_URL ?>/restauration/factures/payer/<?= $f['id'] ?>" class="btn btn-sm btn-outline-success" onclick="return confirm('Marquer comme payée ?')"><i class="fas fa-check"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
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

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('facturesTable', { rowsPerPage: 20, searchInputId: 'searchInput', paginationId: 'pagination', infoId: 'tableInfo' });</script>
