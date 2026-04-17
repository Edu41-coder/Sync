<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-tree', 'text' => 'Extérieur', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['a_faire'=>'warning','en_cours'=>'primary','termine'=>'success','pas_deranger'=>'secondary','annule'=>'danger'];
$statutLabels = ['a_faire'=>'À faire','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé'];
$typeIcons = ['terrasse'=>'fa-umbrella-beach','parking'=>'fa-car','entree'=>'fa-door-open','local_poubelles'=>'fa-dumpster','couloir'=>'fa-arrows-alt-h','ascenseur'=>'fa-elevator','jardin'=>'fa-seedling','piscine'=>'fa-swimming-pool','salle_commune'=>'fa-couch','autre'=>'fa-map-pin'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-tree me-2 text-success"></i>Ménage Extérieur — <?= date('d/m/Y', strtotime($date)) ?></h2>
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

    <?php if ($isManager): ?>
    <div class="row g-2 mb-4">
        <div class="col-auto">
            <?php if (!$dejaGenere): ?>
            <form method="POST" action="<?= BASE_URL ?>/menage/exterieur/generer" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-magic me-2"></i>Générer les tâches</button>
            </form>
            <?php else: ?>
            <span class="badge bg-success fs-6 py-2 px-3"><i class="fas fa-check me-2"></i>Zones planifiées</span>
            <?php endif; ?>
        </div>
        <?php if ($dejaGenere): ?>
        <div class="col-auto">
            <form method="POST" action="<?= BASE_URL ?>/menage/exterieur/distribuer" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <button type="submit" class="btn btn-warning text-dark" onclick="return confirm('Distribuer ?')"><i class="fas fa-random me-2"></i>Distribuer</button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Affectations -->
    <?php if (!empty($affectations)): ?>
    <div class="row g-2 mb-4">
        <?php foreach ($affectations as $a):
            $pct = $a['nb_taches'] > 0 ? round(($a['terminees'] / $a['nb_taches']) * 100) : 0;
        ?>
        <div class="col-md-3">
            <div class="card shadow-sm">
                <div class="card-body py-2">
                    <strong><?= htmlspecialchars($a['prenom'] . ' ' . $a['nom']) ?></strong>
                    <span class="badge bg-primary float-end"><?= $a['terminees'] ?>/<?= $a['nb_taches'] ?></span>
                    <div class="progress mt-1" style="height:6px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Tableau zones/tâches -->
    <div class="card shadow-sm mb-4">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0"><i class="fas fa-map-marked me-2"></i>Zones du jour (<?= count($taches) ?>)</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="tachesExtTable">
                <thead><tr><th>Zone</th><th>Type</th><th>Assigné à</th><th class="text-center">Statut</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($taches)): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4"><?= $dejaGenere ? 'Aucune zone éligible aujourd\'hui' : 'Cliquez "Générer" pour commencer' ?></td></tr>
                    <?php else: foreach ($taches as $t): ?>
                    <tr>
                        <td><i class="fas <?= $typeIcons[$t['type_zone'] ?? ''] ?? 'fa-map-pin' ?> me-2 text-success"></i><strong><?= htmlspecialchars($t['zone_nom'] ?? '-') ?></strong></td>
                        <td><span class="badge bg-success"><?= $t['type_zone'] ?? '-' ?></span></td>
                        <td><?= $t['employe_prenom'] ? htmlspecialchars($t['employe_prenom'] . ' ' . $t['employe_nom']) : '<span class="text-danger">Non assigné</span>' ?></td>
                        <td class="text-center"><span class="badge bg-<?= $statutColors[$t['statut']] ?? 'secondary' ?>"><?= $statutLabels[$t['statut']] ?? $t['statut'] ?></span></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= BASE_URL ?>/menage/exterieur/tache/<?= $t['id'] ?>" class="btn btn-outline-info" title="Détail" data-bs-toggle="tooltip"><i class="fas fa-eye"></i></a>
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
    <?php endif; ?>

    <!-- Mes zones du jour -->
    <?php if (!empty($mesTaches)): ?>
    <div class="card shadow-sm">
        <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-user-check me-2"></i>Mes zones du jour (<?= count($mesTaches) ?>)</h6></div>
        <div class="card-body p-0">
            <div class="list-group list-group-flush">
                <?php foreach ($mesTaches as $t): ?>
                <a href="<?= BASE_URL ?>/menage/exterieur/tache/<?= $t['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    <div>
                        <i class="fas <?= $typeIcons[$t['type_zone'] ?? ''] ?? 'fa-map-pin' ?> me-2 text-success"></i>
                        <strong><?= htmlspecialchars($t['zone_nom'] ?? '-') ?></strong>
                    </div>
                    <div>
                        <span class="badge bg-<?= $statutColors[$t['statut']] ?? 'secondary' ?>"><?= $statutLabels[$t['statut']] ?? $t['statut'] ?></span>
                        <?php if ($t['total_items'] > 0): ?><small class="text-muted ms-2"><?= $t['items_faits'] ?>/<?= $t['total_items'] ?></small><?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>if(document.getElementById('tachesExtTable')){new DataTableWithPagination('tachesExtTable',{rowsPerPage:25,searchInputId:'searchInput',paginationId:'pagination',infoId:'tableInfo'});}</script>
