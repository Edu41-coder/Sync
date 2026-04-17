<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-tshirt', 'text' => 'Laverie', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['demandee'=>'warning','en_cours'=>'primary','prete'=>'info','livree'=>'success','facturee'=>'dark','annulee'=>'danger'];
$statutLabels = ['demandee'=>'Demandée','en_cours'=>'En cours','prete'=>'Prête','livree'=>'Livrée','facturee'=>'Facturée','annulee'=>'Annulée'];
$typeLingeLabels = ['draps_1p'=>'Draps 1p','draps_2p'=>'Draps 2p','housse_couette'=>'Housse couette','serviettes'=>'Serviettes','peignoir'=>'Peignoir','linge_personnel'=>'Linge personnel','autre'=>'Autre'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2><i class="fas fa-tshirt me-2 text-warning"></i>Laverie</h2>
        <div class="d-flex gap-2">
            <select class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id='+this.value">
                <option value="0">-- Résidence --</option>
                <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
            </select>
            <select class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id=<?= $selectedResidence ?>&statut='+this.value">
                <option value="">Tous statuts</option>
                <?php foreach ($statutLabels as $k=>$v): ?><option value="<?= $k ?>" <?= ($statut ?? '')===$k?'selected':'' ?>><?= $v ?></option><?php endforeach; ?>
            </select>
        </div>
    </div>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 border-start border-warning border-4">
                <h4 class="mb-0"><?= $stats['en_attente'] ?></h4><small class="text-muted">En attente</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 border-start border-primary border-4">
                <h4 class="mb-0"><?= $stats['en_cours'] ?></h4><small class="text-muted">En cours</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 border-start border-success border-4">
                <h4 class="mb-0"><?= $stats['total'] ?></h4><small class="text-muted">Total demandes</small>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm text-center py-3 border-start border-info border-4">
                <h4 class="mb-0 text-success"><?= number_format((float)$stats['ca_mois'], 0, ',', ' ') ?> &euro;</h4><small class="text-muted">CA mois (payant)</small>
            </div>
        </div>
    </div>

    <?php if ($selectedResidence): ?>
    <div class="row g-4">
        <!-- Formulaire nouvelle demande -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-warning">
                <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Nouvelle demande</h6></div>
                <form method="POST" action="<?= BASE_URL ?>/menage/laverie/demande">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $selectedResidence ?>">
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Résident <span class="text-danger">*</span></label>
                            <select name="resident_id" class="form-select form-select-sm" required id="selResident">
                                <option value="">-- Choisir --</option>
                                <?php foreach ($residents as $r): ?>
                                <option value="<?= $r['id'] ?>" data-inclus="<?= $r['has_laverie'] ? '1' : '0' ?>"><?= htmlspecialchars($r['civilite'] . ' ' . $r['prenom'] . ' ' . $r['nom']) ?> — Lot <?= $r['numero_lot'] ?><?= $r['has_laverie'] ? ' [INCLUS]' : '' ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Type de linge <span class="text-danger">*</span></label>
                            <select name="type_linge" class="form-select form-select-sm" required id="selTypeLinge">
                                <?php foreach ($tarifs as $t): ?>
                                <option value="<?= $t['type_linge'] ?>" data-prix="<?= $t['prix_unitaire'] ?>"><?= htmlspecialchars($t['libelle']) ?> — <?= number_format($t['prix_unitaire'], 2, ',', ' ') ?> &euro;</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label">Quantité</label>
                                <input type="number" name="quantite" class="form-control form-control-sm" value="1" min="1" id="inputQte">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Prix unit.</label>
                                <input type="number" name="prix_unitaire" class="form-control form-control-sm" step="0.01" id="inputPrix" readonly>
                            </div>
                        </div>
                        <input type="hidden" name="service_inclus" id="inputInclus" value="0">
                        <div class="alert alert-success small d-none" id="alertInclus"><i class="fas fa-check me-2"></i>Service inclus dans le forfait — pas de facturation</div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="Instructions particulières...">
                        </div>
                    </div>
                    <div class="card-footer"><button type="submit" class="btn btn-warning btn-sm w-100"><i class="fas fa-plus me-2"></i>Créer la demande</button></div>
                </form>
            </div>

            <!-- Tarifs -->
            <div class="card shadow-sm mt-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-tag me-2"></i>Tarifs</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <?php foreach ($tarifs as $t): ?>
                        <tr><td><?= htmlspecialchars($t['libelle']) ?></td><td class="text-end"><strong><?= number_format($t['prix_unitaire'], 2, ',', ' ') ?> &euro;</strong></td></tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Liste des demandes -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="mb-0"><i class="fas fa-list me-2"></i>Demandes (<?= count($demandes) ?>)</h6>
                    <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" id="laverieTable">
                        <thead><tr><th>Résident</th><th>Type</th><th class="text-center">Qté</th><th class="text-end">Montant</th><th class="text-center">Statut</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                        <tbody>
                            <?php if (empty($demandes)): ?>
                            <tr><td colspan="7" class="text-center text-muted py-4">Aucune demande.</td></tr>
                            <?php else: foreach ($demandes as $d): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($d['resident_nom']) ?></strong><br><small class="text-muted">Lot <?= $d['numero_lot'] ?></small></td>
                                <td><?= $typeLingeLabels[$d['type_linge']] ?? $d['type_linge'] ?></td>
                                <td class="text-center"><?= $d['quantite'] ?></td>
                                <td class="text-end"><?= $d['service_inclus'] ? '<span class="badge bg-success">Inclus</span>' : number_format($d['montant_total'], 2, ',', ' ') . ' €' ?></td>
                                <td class="text-center"><span class="badge bg-<?= $statutColors[$d['statut']] ?? 'secondary' ?>"><?= $statutLabels[$d['statut']] ?? $d['statut'] ?></span></td>
                                <td class="text-muted small"><?= date('d/m/Y', strtotime($d['date_demande'])) ?></td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm" role="group">
                                        <?php if ($d['statut'] === 'demandee'): ?>
                                        <a href="<?= BASE_URL ?>/menage/laverie/statut/<?= $d['id'] ?>?statut=en_cours&residence_id=<?= $selectedResidence ?>" class="btn btn-outline-primary" title="Prendre en charge" data-bs-toggle="tooltip"><i class="fas fa-hand-paper"></i></a>
                                        <?php endif; ?>
                                        <?php if ($d['statut'] === 'en_cours'): ?>
                                        <a href="<?= BASE_URL ?>/menage/laverie/statut/<?= $d['id'] ?>?statut=prete&residence_id=<?= $selectedResidence ?>" class="btn btn-outline-info" title="Prêt" data-bs-toggle="tooltip"><i class="fas fa-check"></i></a>
                                        <?php endif; ?>
                                        <?php if ($d['statut'] === 'prete'): ?>
                                        <a href="<?= BASE_URL ?>/menage/laverie/statut/<?= $d['id'] ?>?statut=livree&residence_id=<?= $selectedResidence ?>" class="btn btn-outline-success" title="Livré" data-bs-toggle="tooltip"><i class="fas fa-truck"></i></a>
                                        <?php endif; ?>
                                        <?php if (in_array($d['statut'], ['demandee','en_cours'])): ?>
                                        <a href="<?= BASE_URL ?>/menage/laverie/statut/<?= $d['id'] ?>?statut=annulee&residence_id=<?= $selectedResidence ?>" class="btn btn-outline-danger" title="Annuler" data-bs-toggle="tooltip" onclick="return confirm('Annuler ?')"><i class="fas fa-times"></i></a>
                                        <?php endif; ?>
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
        </div>
    </div>
    <?php else: ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence.</div>
    <?php endif; ?>
</div>

<script>
// Auto-remplir le prix et détecter si service inclus
document.getElementById('selTypeLinge')?.addEventListener('change', updatePrix);
document.getElementById('selResident')?.addEventListener('change', updatePrix);
function updatePrix() {
    const typeSel = document.getElementById('selTypeLinge');
    const resSel = document.getElementById('selResident');
    const opt = typeSel.options[typeSel.selectedIndex];
    const resOpt = resSel.options[resSel.selectedIndex];
    const prix = parseFloat(opt?.dataset.prix || 0);
    const inclus = resOpt?.dataset.inclus === '1';

    document.getElementById('inputPrix').value = prix;
    document.getElementById('inputInclus').value = inclus ? '1' : '0';
    document.getElementById('alertInclus').classList.toggle('d-none', !inclus);
}
updatePrix();
</script>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('laverieTable', { rowsPerPage: 20, searchInputId: 'searchInput', paginationId: 'pagination', infoId: 'tableInfo' });</script>
