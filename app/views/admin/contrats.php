<?php
$title = "Contrats de Gestion";
$isProprietaire = ($_SESSION['user_role'] ?? '') === 'proprietaire';
?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-file-contract', 'text' => 'Contrats de gestion', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3">
                <i class="fas fa-file-contract text-dark"></i>
                Contrats de Gestion
            </h1>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-primary mb-1 fw-bold small">Total contrats</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-success mb-1 fw-bold small">Contrats actifs</h6>
                    <h3 class="mb-0 text-gray-800"><?= $stats['actifs'] ?></h3>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-info mb-1 fw-bold small">Loyers garantis / mois</h6>
                    <h3 class="mb-0 text-gray-800"><?= number_format($stats['loyer_total'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <?php if (!$isProprietaire): ?>
        <div class="col-12 col-md-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <h6 class="text-warning mb-1 fw-bold small">Marge Domitys / mois</h6>
                    <h3 class="mb-0 text-gray-800"><?= number_format($stats['marge_total'], 2, ',', ' ') ?> €</h3>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Tableau -->
    <div class="card shadow">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0"><i class="fas fa-list me-2"></i>Contrats propriétaire / exploitant</h5>
            <div class="d-flex gap-2">
                <input type="text" id="searchContrat" class="form-control form-control-sm" style="max-width:250px" placeholder="Rechercher...">
                <select id="filterStatut" class="form-select form-select-sm" style="max-width:150px">
                    <option value="">Tous statuts</option>
                    <option value="Actif">Actif</option>
                    <option value="Résilié">Résilié</option>
                    <option value="Terminé">Terminé</option>
                    <option value="Suspendu">Suspendu</option>
                </select>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="contratsTable">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="0">N° Contrat</th>
                            <th class="sortable" data-column="1">Résidence / Lot</th>
                            <?php if (!$isProprietaire): ?>
                            <th class="sortable" data-column="2">Propriétaire</th>
                            <?php endif; ?>
                            <th class="sortable" data-column="<?= $isProprietaire ? 2 : 3 ?>">Type</th>
                            <th class="sortable text-end" data-column="<?= $isProprietaire ? 3 : 4 ?>" data-type="number">Loyer garanti</th>
                            <?php if (!$isProprietaire): ?>
                            <th class="sortable text-end" data-column="5" data-type="number">Loyer résident</th>
                            <th class="sortable text-end" data-column="6" data-type="number">Marge</th>
                            <?php endif; ?>
                            <th class="sortable text-center" data-column="<?= $isProprietaire ? 4 : 7 ?>">Statut</th>
                            <th class="sortable" data-column="<?= $isProprietaire ? 5 : 8 ?>">Dates</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($contrats)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                Aucun contrat enregistré
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($contrats as $c): ?>
                        <?php
                            $marge = $c['marge_mensuelle'] ?? null;
                            $margeClass = $marge === null ? 'text-muted' : ($marge >= 0 ? 'text-success' : 'text-danger');
                            $statutLabels = ['actif'=>'Actif','resilie'=>'Résilié','termine'=>'Terminé','suspendu'=>'Suspendu','projet'=>'Projet','en_litige'=>'Litige'];
                            $statutColors = ['actif'=>'success','resilie'=>'danger','termine'=>'secondary','suspendu'=>'warning','projet'=>'info','en_litige'=>'danger'];
                            $statutLabel = $statutLabels[$c['statut']] ?? ucfirst($c['statut']);
                            $statutColor = $statutColors[$c['statut']] ?? 'secondary';
                            $typeLabels = ['bail_commercial'=>'Bail commercial','bail_professionnel'=>'Bail professionnel','mandat_gestion'=>'Mandat de gestion'];
                        ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($c['numero_contrat'] ?? '') ?>">
                                <strong><?= htmlspecialchars($c['numero_contrat'] ?? '-') ?></strong>
                            </td>
                            <td data-sort="<?= htmlspecialchars(($c['residence'] ?? '') . ' ' . ($c['numero_lot'] ?? '')) ?>">
                                <div><?= htmlspecialchars($c['residence'] ?? '-') ?></div>
                                <small class="text-muted">Lot <?= htmlspecialchars($c['numero_lot'] ?? '-') ?></small>
                            </td>
                            <?php if (!$isProprietaire): ?>
                            <td data-sort="<?= htmlspecialchars($c['proprietaire'] ?? '') ?>">
                                <?= htmlspecialchars($c['proprietaire'] ?? '-') ?>
                                <?php if (!empty($c['proprietaire_email'])): ?>
                                <br><small class="text-muted"><?= htmlspecialchars($c['proprietaire_email']) ?></small>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td data-sort="<?= htmlspecialchars($c['type_contrat'] ?? '') ?>">
                                <small><?= $typeLabels[$c['type_contrat']] ?? htmlspecialchars($c['type_contrat'] ?? '-') ?></small>
                            </td>
                            <td class="text-end" data-sort="<?= $c['loyer_mensuel_garanti'] ?? 0 ?>">
                                <strong><?= number_format($c['loyer_mensuel_garanti'] ?? 0, 2, ',', ' ') ?> €</strong>
                            </td>
                            <?php if (!$isProprietaire): ?>
                            <td class="text-end" data-sort="<?= $c['loyer_mensuel_resident'] ?? 0 ?>">
                                <?php if ($c['loyer_mensuel_resident']): ?>
                                <?= number_format($c['loyer_mensuel_resident'], 2, ',', ' ') ?> €
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end <?= $margeClass ?>" data-sort="<?= $marge ?? 0 ?>">
                                <?php if ($marge !== null): ?>
                                <strong><?= ($marge >= 0 ? '+' : '') . number_format($marge, 2, ',', ' ') ?> €</strong>
                                <?php else: ?>
                                <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <?php endif; ?>
                            <td class="text-center" data-sort="<?= $statutLabel ?>">
                                <span class="badge bg-<?= $statutColor ?>"><?= $statutLabel ?></span>
                            </td>
                            <td data-sort="<?= $c['date_effet'] ?? $c['date_signature'] ?? '' ?>">
                                <?php if (!empty($c['date_effet'])): ?>
                                <small>Du <?= date('d/m/Y', strtotime($c['date_effet'])) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($c['date_fin'])): ?>
                                <br><small>Au <?= date('d/m/Y', strtotime($c['date_fin'])) ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo">
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($contrats)) ?></span>
                sur <span id="totalEntries"><?= count($contrats) ?></span> contrats
            </div>
            <nav>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </nav>
        </div>
    </div>

    <?php if (!$isProprietaire): ?>
    <!-- Explication modèle économique -->
    <div class="card shadow mt-4">
        <div class="card-body small">
            <h6><i class="fas fa-info-circle text-info me-1"></i>Modèle économique Domitys</h6>
            <div class="row">
                <div class="col-md-4">
                    <strong class="text-primary">Propriétaire</strong> reçoit le <strong>loyer garanti</strong> (ex: 850 €)
                </div>
                <div class="col-md-4">
                    <strong class="text-danger">Domitys</strong> facture le <strong>loyer résident</strong> (ex: 1 450 €)
                </div>
                <div class="col-md-4">
                    <strong class="text-success">Marge</strong> = loyer résident - loyer garanti (ex: +600 €)
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
const table = new DataTableWithPagination('contratsTable', {
    rowsPerPage: 10,
    searchInputId: 'searchContrat',
    excludeColumns: [],
    filters: [
        { id: 'filterStatut', column: 7 }
    ],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
