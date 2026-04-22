<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-boxes-stacked', 'text' => 'Inventaire', 'url' => BASE_URL . '/jardinage/inventaire?residence_id=' . $item['residence_id']],
    ['icon' => 'fas fa-history', 'text' => 'Historique', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabels = ['entree' => 'Entrée', 'sortie' => 'Sortie', 'ajustement' => 'Ajustement'];
$typeColors = ['entree' => 'success', 'sortie' => 'warning', 'ajustement' => 'info'];
$motifLabels = ['livraison' => 'Livraison', 'usage' => 'Usage', 'perte' => 'Perte', 'casse' => 'Casse', 'inventaire' => 'Inventaire', 'autre' => 'Autre'];
?>

<div class="container-fluid py-4" style="max-width:1200px">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-history me-2 text-success"></i>Historique des mouvements</h2>
            <p class="text-muted mb-0"><strong><?= htmlspecialchars($item['produit_nom']) ?></strong> — <?= htmlspecialchars($item['residence_nom']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/jardinage/inventaire?residence_id=<?= $item['residence_id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-success border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Stock actuel</h6>
                    <h3 class="mb-0"><?= number_format($item['quantite_actuelle'], 3, ',', ' ') ?> <?= htmlspecialchars($item['unite']) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-warning border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Seuil d'alerte</h6>
                    <h3 class="mb-0"><?= $item['seuil_alerte'] > 0 ? number_format($item['seuil_alerte'], 3, ',', ' ') : '—' ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted small mb-1">Mouvements</h6>
                    <h3 class="mb-0"><?= count($mouvements) ?></h3>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher dans l'historique...">
            </div>
            <div class="table-responsive">
            <table class="table table-hover mb-0" id="historiqueTable">
                <thead class="table-light">
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th class="text-end">Quantité</th>
                        <th>Motif</th>
                        <th>Espace</th>
                        <th>Utilisateur</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($mouvements as $m): ?>
                    <tr>
                        <td class="small" data-sort="<?= strtotime($m['created_at']) ?>"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
                        <td data-sort="<?= $m['type_mouvement'] ?>"><span class="badge bg-<?= $typeColors[$m['type_mouvement']] ?? 'secondary' ?>"><?= $typeLabels[$m['type_mouvement']] ?? $m['type_mouvement'] ?></span></td>
                        <td class="text-end" data-sort="<?= (float)$m['quantite'] ?>"><strong><?= number_format($m['quantite'], 3, ',', ' ') ?></strong> <?= htmlspecialchars($item['unite']) ?></td>
                        <td><small><?= $motifLabels[$m['motif']] ?? $m['motif'] ?></small></td>
                        <td class="small"><?= $m['espace_nom'] ? htmlspecialchars($m['espace_nom']) : '—' ?></td>
                        <td class="small"><?= $m['user_prenom'] ? htmlspecialchars($m['user_prenom'] . ' ' . $m['user_nom']) : '—' ?></td>
                        <td class="small text-muted"><?= htmlspecialchars($m['notes'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($mouvements)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun mouvement enregistré.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
if (document.querySelector('#historiqueTable tbody tr td:not([colspan])')) {
    new DataTableWithPagination('historiqueTable', {
        rowsPerPage: 25,
        searchInputId: 'searchInput',
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
}
</script>
