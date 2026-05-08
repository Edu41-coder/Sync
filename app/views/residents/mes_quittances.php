<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Mon espace', 'url' => BASE_URL . '/resident/monEspace'],
    ['icon' => 'fas fa-chart-line',     'text' => 'Comptabilité', 'url' => BASE_URL . '/resident/comptabilite'],
    ['icon' => 'fas fa-receipt',        'text' => 'Mes quittances', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatut = [
    'emise'                 => 'primary',
    'partiellement_payee'   => 'warning',
    'payee'                 => 'success',
    'impayee'               => 'danger',
    'annulee'               => 'secondary',
];
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-receipt me-2 text-success"></i>Mes quittances de loyer</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Retrouvez ici toutes vos quittances mensuelles. Chaque quittance peut être téléchargée en PDF
        (utile pour la déclaration d'impôts ou la transmission à votre CAF).
    </div>

    <?php if (empty($quittances)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucune quittance disponible</h5>
            <p class="text-muted mb-0">Vos quittances apparaîtront ici dès leur émission par le service comptable.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="qrTable" class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Période</th>
                            <th>Numéro</th>
                            <th>Résidence</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quittances as $q): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($moisLabels[$q['periode_mois']] ?? '') ?> <?= (int)$q['periode_annee'] ?></strong></td>
                            <td><small class="text-muted"><?= htmlspecialchars($q['numero_quittance']) ?></small></td>
                            <td><small><?= htmlspecialchars($q['residence_nom'] ?? '—') ?></small></td>
                            <td class="text-end"><strong><?= number_format((float)$q['montant_du_total'], 2, ',', ' ') ?> €</strong></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badgeStatut[$q['statut']] ?? 'secondary' ?>"><?= htmlspecialchars($statuts[$q['statut']] ?? $q['statut']) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/quittancePrintable/<?= (int)$q['id'] ?>" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fas fa-download me-1"></i>Télécharger
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="qrPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="qrInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="qrPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if (!empty($quittances)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('qrTable', {
    rowsPerPage: 12,
    excludeColumns: [5],
    paginationId: 'qrPager',
    infoId: 'qrInfo'
});
</script>
<?php endif; ?>
