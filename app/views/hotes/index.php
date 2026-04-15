<?php $title = "Hôtes Temporaires"; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Hôtes temporaires', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-calendar-check text-dark"></i> Hôtes Temporaires</h1>
            <a href="<?= BASE_URL ?>/hote/create" class="btn btn-danger">
                <i class="fas fa-plus me-1"></i>Nouvelle réservation
            </a>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Total séjours</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning mb-1 fw-bold small">Réservés</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['reserves'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-success mb-1 fw-bold small">En cours</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['en_cours'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-info mb-1 fw-bold small">CA encaissé</h6>
                    <h3 class="mb-0 text-gray-800"><?= number_format($stats['ca_total'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <input type="text" id="searchHote" class="form-control form-control-sm" placeholder="Rechercher nom, résidence...">
                </div>
                <div class="col-12 col-md-3">
                    <select id="filterStatut" class="form-select form-select-sm">
                        <option value="">Tous statuts</option>
                        <option value="Réservé">Réservé</option>
                        <option value="En cours">En cours</option>
                        <option value="Terminé">Terminé</option>
                        <option value="Annulé">Annulé</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="hotesTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0" data-type="number">#</th>
                            <th class="sortable" data-column="1">Hôte</th>
                            <th class="sortable" data-column="2">Résidence / Lot</th>
                            <th class="sortable" data-column="3">Arrivée</th>
                            <th class="sortable" data-column="4">Départ</th>
                            <th class="sortable text-center" data-column="5" data-type="number">Nuits</th>
                            <th class="sortable text-end" data-column="6" data-type="number">Montant</th>
                            <th class="sortable text-center" data-column="7">Statut</th>
                            <th class="text-end" data-no-sort style="width:100px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($hotes)): ?>
                        <tr><td colspan="9" class="text-center py-4 text-muted"><i class="fas fa-inbox fa-2x mb-2 d-block"></i>Aucun séjour</td></tr>
                        <?php else: ?>
                        <?php foreach ($hotes as $h):
                            $statutLabels = ['reserve'=>'Réservé','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé'];
                            $statutColors = ['reserve'=>'warning','en_cours'=>'success','termine'=>'secondary','annule'=>'danger'];
                            $paiementLabels = ['en_attente'=>'En attente','partiel'=>'Partiel','paye'=>'Payé','rembourse'=>'Remboursé'];
                            $paiementColors = ['en_attente'=>'warning','partiel'=>'info','paye'=>'success','rembourse'=>'secondary'];
                        ?>
                        <tr>
                            <td data-sort="<?= $h['id'] ?>"><?= $h['id'] ?></td>
                            <td data-sort="<?= htmlspecialchars($h['nom'] . ' ' . $h['prenom']) ?>">
                                <strong><?= htmlspecialchars($h['civilite'] . ' ' . $h['prenom'] . ' ' . $h['nom']) ?></strong>
                                <?php if ($h['email']): ?><br><small class="text-muted"><?= htmlspecialchars($h['email']) ?></small><?php endif; ?>
                            </td>
                            <td data-sort="<?= htmlspecialchars($h['residence_nom'] ?? '') ?>">
                                <?= htmlspecialchars($h['residence_nom'] ?? '-') ?>
                                <?php if ($h['numero_lot']): ?><br><small class="text-muted">Lot <?= htmlspecialchars($h['numero_lot']) ?></small><?php endif; ?>
                            </td>
                            <td data-sort="<?= $h['date_arrivee'] ?>"><?= date('d/m/Y', strtotime($h['date_arrivee'])) ?></td>
                            <td data-sort="<?= $h['date_depart_prevue'] ?>"><?= date('d/m/Y', strtotime($h['date_depart_prevue'])) ?></td>
                            <td class="text-center" data-sort="<?= $h['nb_nuits'] ?>"><?= $h['nb_nuits'] ?></td>
                            <td class="text-end" data-sort="<?= $h['montant_total'] ?? 0 ?>">
                                <?php if ($h['montant_total']): ?>
                                <?= number_format($h['montant_total'], 2, ',', ' ') ?> €
                                <br><span class="badge bg-<?= $paiementColors[$h['statut_paiement']] ?? 'secondary' ?> small"><?= $paiementLabels[$h['statut_paiement']] ?? $h['statut_paiement'] ?></span>
                                <?php else: ?>-<?php endif; ?>
                            </td>
                            <td class="text-center" data-sort="<?= $statutLabels[$h['statut']] ?? '' ?>">
                                <span class="badge bg-<?= $statutColors[$h['statut']] ?? 'secondary' ?>"><?= $statutLabels[$h['statut']] ?? $h['statut'] ?></span>
                            </td>
                            <td class="text-end">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= BASE_URL ?>/hote/show/<?= $h['id'] ?>" class="btn btn-outline-primary" title="Détails"><i class="fas fa-eye"></i></a>
                                    <a href="<?= BASE_URL ?>/hote/edit/<?= $h['id'] ?>" class="btn btn-outline-warning" title="Modifier"><i class="fas fa-edit"></i></a>
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
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($hotes)) ?></span>
                sur <span id="totalEntries"><?= count($hotes) ?></span> séjours
            </div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('hotesTable', {
    rowsPerPage: 10,
    searchInputId: 'searchHote',
    filters: [{ id: 'filterStatut', column: 7 }],
    excludeColumns: [8],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
