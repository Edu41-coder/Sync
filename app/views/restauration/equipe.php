<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-user-friends', 'text' => 'Équipe', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$residencesUniques = [];
foreach ($staff as $s) {
    $residencesUniques[$s['residence_nom']] = true;
}
$residencesUniques = array_keys($residencesUniques);
sort($residencesUniques);
?>

<div class="container-fluid py-4">

    <h2 class="mb-4"><i class="fas fa-user-friends me-2 text-warning"></i>Équipe Restauration</h2>

    <?php if (empty($staff)): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun personnel restauration affecté à vos résidences.</div>
    <?php else: ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h6 class="mb-0"><i class="fas fa-users me-2"></i><?= count($staff) ?> membre(s)</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <select id="filterResidence" class="form-select form-select-sm" style="width:auto">
                        <option value="">Toutes résidences</option>
                        <?php foreach ($residencesUniques as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>"><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select id="filterStatut" class="form-select form-select-sm" style="width:auto">
                        <option value="">Tous statuts</option>
                        <option value="Actif">Actif</option>
                        <option value="Inactif">Inactif</option>
                    </select>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0" id="equipeTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Rôle</th>
                            <th>Résidence</th>
                            <th>Contact</th>
                            <th>Dernière connexion</th>
                            <th class="text-center">Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($staff as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong></td>
                            <td>
                                <span class="badge" style="background-color:<?= $m['role_couleur'] ?? '#6c757d' ?>">
                                    <i class="fas <?= $m['role_icone'] ?? 'fa-user' ?> me-1"></i><?= htmlspecialchars($m['role_nom'] ?? $m['role']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($m['residence_nom']) ?></td>
                            <td>
                                <?php if ($m['email']): ?><a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="text-decoration-none me-2"><i class="fas fa-envelope"></i></a><?php endif; ?>
                                <?php if ($m['telephone']): ?><a href="tel:<?= htmlspecialchars($m['telephone']) ?>" class="text-decoration-none"><i class="fas fa-phone"></i></a><?php endif; ?>
                            </td>
                            <td class="text-muted small" data-sort="<?= $m['last_login'] ? strtotime($m['last_login']) : 0 ?>">
                                <?= $m['last_login'] ? date('d/m/Y H:i', strtotime($m['last_login'])) : 'Jamais' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= $m['actif'] ? 'success' : 'danger' ?>"><?= $m['actif'] ? 'Actif' : 'Inactif' ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <div class="text-muted small" id="tableInfo"></div>
                <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php if (!empty($staff)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('equipeTable', {
    rowsPerPage: 15,
    searchInputId: 'searchInput',
    filters: [
        { id: 'filterResidence', column: 2 },
        { id: 'filterStatut', column: 5 }
    ],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
