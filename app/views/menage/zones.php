<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-map-marked', 'text' => 'Zones Extérieures', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabels = ['terrasse'=>'Terrasse','parking'=>'Parking','entree'=>'Entrée','local_poubelles'=>'Local poubelles','couloir'=>'Couloir','ascenseur'=>'Ascenseur','jardin'=>'Jardin','piscine'=>'Piscine','salle_commune'=>'Salle commune','autre'=>'Autre'];
$typeIcons = ['terrasse'=>'fa-umbrella-beach','parking'=>'fa-car','entree'=>'fa-door-open','local_poubelles'=>'fa-dumpster','couloir'=>'fa-arrows-alt-h','ascenseur'=>'fa-elevator','jardin'=>'fa-seedling','piscine'=>'fa-swimming-pool','salle_commune'=>'fa-couch','autre'=>'fa-map-pin'];
$freqLabels = ['quotidien'=>'Quotidien','hebdomadaire'=>'Hebdomadaire','bihebdomadaire'=>'2x/semaine','mensuel'=>'Mensuel'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-map-marked me-2 text-success"></i>Zones Extérieures</h2>
        <select class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id='+this.value">
            <option value="0">-- Résidence --</option>
            <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
        </select>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence.</div>
    <?php else: ?>

    <!-- Formulaire ajout -->
    <div class="card shadow-sm mb-4 border-success">
        <div class="card-header bg-success text-white py-2"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Ajouter une zone</h6></div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/menage/zones/create" class="row g-2 align-items-end">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                <div class="col-md-3"><label class="form-label small">Nom <span class="text-danger">*</span></label><input type="text" name="nom" class="form-control form-control-sm" required placeholder="Ex: Terrasse piscine"></div>
                <div class="col-md-2"><label class="form-label small">Type</label>
                    <select name="type_zone" class="form-select form-select-sm">
                        <?php foreach ($typeLabels as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-2"><label class="form-label small">Fréquence</label>
                    <select name="frequence" class="form-select form-select-sm">
                        <?php foreach ($freqLabels as $k=>$v): ?><option value="<?= $k ?>"><?= $v ?></option><?php endforeach; ?>
                    </select></div>
                <div class="col-md-2"><label class="form-label small">Jours (si hebdo)</label><input type="text" name="jour_semaine" class="form-control form-control-sm" placeholder="lundi,jeudi"></div>
                <div class="col-md-1"><label class="form-label small">Priorité</label><input type="number" name="priorite" class="form-control form-control-sm" value="0" min="0"></div>
                <div class="col-md-1"><div class="form-check mt-3"><input class="form-check-input" type="checkbox" name="actif" value="1" checked><label class="form-check-label small">Actif</label></div></div>
                <div class="col-md-1"><button type="submit" class="btn btn-success btn-sm w-100"><i class="fas fa-plus"></i></button></div>
            </form>
        </div>
    </div>

    <!-- Liste zones -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0"><?= count($zones) ?> zone(s) configurée(s)</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="zonesTable">
                <thead><tr><th>Zone</th><th>Type</th><th>Fréquence</th><th>Jours</th><th class="text-center">Priorité</th><th class="text-center">Statut</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($zones)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucune zone configurée.</td></tr>
                    <?php else: foreach ($zones as $z): ?>
                    <tr class="<?= $z['actif'] ? '' : 'opacity-50' ?>">
                        <td><i class="fas <?= $typeIcons[$z['type_zone']] ?? 'fa-map-pin' ?> me-2 text-success"></i><strong><?= htmlspecialchars($z['nom']) ?></strong><?php if ($z['description']): ?><br><small class="text-muted"><?= htmlspecialchars($z['description']) ?></small><?php endif; ?></td>
                        <td><span class="badge bg-success"><?= $typeLabels[$z['type_zone']] ?? $z['type_zone'] ?></span></td>
                        <td><?= $freqLabels[$z['frequence']] ?? $z['frequence'] ?></td>
                        <td><small><?= $z['jour_semaine'] ?: '-' ?></small></td>
                        <td class="text-center"><?= $z['priorite'] ?></td>
                        <td class="text-center"><span class="badge bg-<?= $z['actif'] ? 'success' : 'danger' ?>"><?= $z['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        <td class="text-end">
                            <form method="POST" action="<?= BASE_URL ?>/menage/zones/delete/<?= $z['id'] ?>" class="d-inline" onsubmit="return confirm('Désactiver cette zone ?')">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Désactiver" data-bs-toggle="tooltip"><i class="fas fa-times"></i></button>
                            </form>
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
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('zonesTable', { rowsPerPage: 20, searchInputId: 'searchInput', paginationId: 'pagination', infoId: 'tableInfo' });</script>
