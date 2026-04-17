<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-bed', 'text' => 'Intérieur', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['a_faire'=>'warning','en_cours'=>'primary','termine'=>'success','pas_deranger'=>'secondary','annule'=>'danger'];
$statutIcons = ['a_faire'=>'fa-clock','en_cours'=>'fa-spinner fa-spin','termine'=>'fa-check','pas_deranger'=>'fa-moon','annule'=>'fa-times'];
$statutLabels = ['a_faire'=>'À faire','en_cours'=>'En cours','termine'=>'Terminé','pas_deranger'=>'Pas déranger','annule'=>'Annulé'];
?>

<div class="container-fluid py-4">
    <!-- En-tête + filtres -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-bed me-2 text-info"></i>Ménage Intérieur — <?= date('d/m/Y', strtotime($date)) ?></h2>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id='+this.value+'&date=<?= $date ?>'">
                <option value="0">-- Résidence --</option>
                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
            </select>
            <input type="date" class="form-control form-control-sm" value="<?= $date ?>" onchange="window.location='?residence_id=<?= $selectedResidence ?>&date='+this.value" style="width:auto">
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence.</div>
    <?php else: ?>

    <!-- Actions manager -->
    <?php if ($isManager): ?>
    <div class="row g-2 mb-4">
        <div class="col-auto">
            <?php if (!$dejaGenere): ?>
            <form method="POST" action="<?= BASE_URL ?>/menage/interieur/generer" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <button type="submit" class="btn btn-info text-white"><i class="fas fa-magic me-2"></i>Générer les tâches du jour</button>
            </form>
            <?php else: ?>
            <span class="badge bg-success fs-6 py-2 px-3"><i class="fas fa-check me-2"></i>Tâches générées</span>
            <?php endif; ?>
        </div>
        <?php if ($dejaGenere): ?>
        <div class="col-auto">
            <form method="POST" action="<?= BASE_URL ?>/menage/interieur/distribuer" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <button type="submit" class="btn btn-warning text-dark" onclick="return confirm('Distribuer automatiquement les tâches non assignées ?')"><i class="fas fa-random me-2"></i>Distribuer automatiquement</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Affectations par employé -->
    <?php if (!empty($affectations)): ?>
    <div class="row g-2 mb-4">
        <?php foreach ($affectations as $a):
            $pct = $a['nb_taches'] > 0 ? round(($a['terminees'] / $a['nb_taches']) * 100) : 0;
        ?>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <strong><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></strong>
                        <span class="badge bg-primary"><?= $a['terminees'] ?>/<?= $a['nb_taches'] ?></span>
                    </div>
                    <div class="progress" style="height:8px">
                        <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                    </div>
                    <small class="text-muted">Poids : <?= $a['poids_total'] ?> | <?= $pct ?>%</small>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tableau toutes les tâches (manager) -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0"><i class="fas fa-list me-2"></i>Toutes les tâches (<?= count($taches) ?>)</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="tachesTable">
                <thead><tr><th>Lot</th><th>Occupant</th><th>Service</th><th class="text-center">Poids</th><th>Assigné à</th><th class="text-center">Statut</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($taches)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4"><?= $dejaGenere ? 'Aucune tâche (pas de lot éligible)' : 'Cliquez "Générer les tâches" pour commencer' ?></td></tr>
                    <?php else: foreach ($taches as $t): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($t['numero_lot'] ?? '-') ?></strong><br><small class="text-muted"><?= $t['lot_type'] ?? '' ?></small></td>
                        <td><?= htmlspecialchars($t['resident_nom'] ?? $t['hote_nom'] ?? '-') ?>
                            <?php if ($t['hote_id']): ?><span class="badge bg-info" style="font-size:0.6rem">hôte</span><?php endif; ?></td>
                        <td><span class="badge bg-<?= $t['niveau_service'] === 'premium' ? 'warning text-dark' : ($t['niveau_service'] === 'basique' ? 'info' : 'secondary') ?>"><?= $t['niveau_service'] ?? '-' ?></span></td>
                        <td class="text-center"><?= $t['poids'] ?></td>
                        <td><?= $t['employe_prenom'] ? htmlspecialchars($t['employe_prenom'] . ' ' . $t['employe_nom']) : '<span class="text-danger">Non assigné</span>' ?></td>
                        <td class="text-center"><span class="badge bg-<?= $statutColors[$t['statut']] ?? 'secondary' ?>"><i class="fas <?= $statutIcons[$t['statut']] ?? 'fa-question' ?> me-1"></i><?= $statutLabels[$t['statut']] ?? $t['statut'] ?></span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= BASE_URL ?>/menage/interieur/tache/<?= $t['id'] ?>" class="btn btn-outline-info" title="Détail" data-bs-toggle="tooltip"><i class="fas fa-eye"></i></a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo"></div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
    </div>
    <?php endif; // isManager ?>

    <!-- Mes tâches du jour (employé) -->
    <?php if (!empty($mesTaches)): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-info text-white"><h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Mes tâches du jour (<?= count($mesTaches) ?>)</h6></div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($mesTaches as $t):
                    $pctItems = ($t['total_items'] > 0) ? round(($t['items_faits'] / $t['total_items']) * 100) : 0;
                ?>
                <a href="<?= BASE_URL ?>/menage/interieur/tache/<?= $t['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Lot <?= htmlspecialchars($t['numero_lot'] ?? '-') ?></strong>
                        <small class="text-muted ms-2"><?= $t['lot_type'] ?> | étage <?= $t['etage'] ?? '?' ?></small>
                        <br><small><?= htmlspecialchars($t['resident_nom'] ?? $t['hote_nom'] ?? '') ?>
                            <?php if ($t['hote_id']): ?><span class="badge bg-info" style="font-size:0.6rem">hôte</span><?php endif; ?></small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-<?= $statutColors[$t['statut']] ?? 'secondary' ?> mb-1"><?= $statutLabels[$t['statut']] ?? $t['statut'] ?></span>
                        <?php if ($t['total_items'] > 0): ?>
                        <div class="progress" style="height:6px;width:80px">
                            <div class="progress-bar bg-success" style="width:<?= $pctItems ?>%"></div>
                        </div>
                        <small class="text-muted"><?= $t['items_faits'] ?>/<?= $t['total_items'] ?></small>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php elseif (!$isManager): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune tâche assignée pour aujourd'hui.</div>
    <?php endif; ?>

    <?php endif; // selectedResidence ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
if (document.getElementById('tachesTable')) {
    new DataTableWithPagination('tachesTable', { rowsPerPage: 25, searchInputId: 'searchInput', paginationId: 'pagination', infoId: 'tableInfo' });
}
</script>
