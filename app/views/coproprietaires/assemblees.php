<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Mes Assemblées Générales', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$labelsStatut = [
    'convoquee' => ['couleur' => 'info',      'libelle' => 'Convoquée — à venir', 'icone' => 'fa-paper-plane'],
    'tenue'     => ['couleur' => 'success',   'libelle' => 'Tenue',                'icone' => 'fa-check'],
    'annulee'   => ['couleur' => 'danger',    'libelle' => 'Annulée',              'icone' => 'fa-ban'],
];
$labelsType = ['ordinaire' => 'AGO', 'extraordinaire' => 'AGE'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-gavel text-primary me-2"></i>Mes Assemblées Générales</h1>
            <p class="text-muted mb-0">Convocations, procès-verbaux et résultats des votes des résidences où vous êtes copropriétaire.</p>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Vous n'avez aucun contrat de gestion actif. Aucune AG ne vous est accessible.
    </div>

    <?php else: ?>

    <!-- Bandeau prochaine AG -->
    <?php if (!empty($stats['prochaine'])): ?>
    <div class="alert alert-info d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
            <i class="fas fa-bell me-2"></i>
            <strong>Prochaine AG :</strong>
            <?= htmlspecialchars($stats['prochaine']['type'] === 'extraordinaire' ? 'AGE' : 'AGO') ?>
            le <strong><?= date('d/m/Y à H\hi', strtotime($stats['prochaine']['date_ag'])) ?></strong>
            — <?= htmlspecialchars($stats['prochaine']['residence_nom']) ?>
            <?php if ($stats['prochaine']['lieu']): ?>
            <small class="text-muted">(<?= htmlspecialchars($stats['prochaine']['lieu']) ?>)</small>
            <?php endif; ?>
        </div>
        <a href="<?= BASE_URL ?>/coproprietaire/assembleeShow/<?= (int)$stats['prochaine']['id'] ?>" class="btn btn-sm btn-info text-white">
            <i class="fas fa-eye me-1"></i>Voir détails
        </a>
    </div>
    <?php endif; ?>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" action="<?= BASE_URL ?>/coproprietaire/assemblees" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">— Toutes mes résidences —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)($filtres['residence_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">— Tous —</option>
                        <option value="convoquee" <?= ($filtres['statut'] ?? '') === 'convoquee' ? 'selected' : '' ?>>À venir</option>
                        <option value="tenue"     <?= ($filtres['statut'] ?? '') === 'tenue'     ? 'selected' : '' ?>>Tenues</option>
                        <option value="annulee"   <?= ($filtres['statut'] ?? '') === 'annulee'   ? 'selected' : '' ?>>Annulées</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter me-1"></i>Filtrer</button>
                    <a href="<?= BASE_URL ?>/coproprietaire/assemblees" class="btn btn-sm btn-outline-secondary"><i class="fas fa-redo"></i></a>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($ags)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune AG ne correspond à votre sélection.
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <input type="search" id="searchInput" class="form-control form-control-sm" placeholder="🔍 Rechercher (résidence, lieu…)">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle" id="agTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Type</th>
                            <th>Résidence</th>
                            <th>Lieu</th>
                            <th>Statut</th>
                            <th class="text-center">Résolutions</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ags as $a):
                            $s = $labelsStatut[$a['statut']] ?? null;
                        ?>
                        <tr>
                            <td data-sort="<?= htmlspecialchars($a['date_ag']) ?>">
                                <strong><?= date('d/m/Y', strtotime($a['date_ag'])) ?></strong>
                                <small class="text-muted d-block"><?= date('H:i', strtotime($a['date_ag'])) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $a['type'] === 'extraordinaire' ? 'warning text-dark' : 'primary' ?>">
                                    <?= htmlspecialchars($labelsType[$a['type']]) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($a['residence_nom']) ?><br><small class="text-muted"><?= htmlspecialchars($a['residence_ville']) ?></small></td>
                            <td><small><?= htmlspecialchars($a['lieu'] ?? '—') ?></small></td>
                            <td>
                                <?php if ($s): ?>
                                <span class="badge bg-<?= $s['couleur'] ?>"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$a['nb_resolutions'] > 0): ?>
                                <span class="badge bg-light text-dark border"><i class="fas fa-list-ol me-1"></i><?= (int)$a['nb_resolutions'] ?></span>
                                <?php else: ?>—<?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= BASE_URL ?>/coproprietaire/assembleeShow/<?= (int)$a['id'] ?>" class="btn btn-sm btn-outline-info" title="Voir"><i class="fas fa-eye"></i></a>
                                <?php if (!empty($a['document_convocation'])): ?>
                                <a href="<?= BASE_URL ?>/coproprietaire/assembleeDownload/<?= (int)$a['id'] ?>/convocation" target="_blank" class="btn btn-sm btn-outline-secondary" title="Convocation"><i class="fas fa-file-alt"></i></a>
                                <?php endif; ?>
                                <?php if (!empty($a['document_pv'])): ?>
                                <a href="<?= BASE_URL ?>/coproprietaire/assembleeDownload/<?= (int)$a['id'] ?>/pv" target="_blank" class="btn btn-sm btn-outline-success" title="Procès-verbal"><i class="fas fa-file-signature"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div id="pagination" class="mt-3"></div>
            <div id="tableInfo" class="text-muted small mt-2"></div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<?php if (!empty($ags)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('agTable', {
    rowsPerPage: 10,
    searchInputId: 'searchInput',
    excludeColumns: [6],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
