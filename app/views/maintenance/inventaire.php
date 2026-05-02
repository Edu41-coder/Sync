<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-warehouse',      'text' => 'Inventaire',      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-warehouse text-info me-2"></i>Inventaire</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/maintenance/inventaire">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <a href="?residence_id=<?= (int)($residenceCourante['id'] ?? 0) ?><?= $alertesSeules ? '' : '&alertes=1' ?>" class="btn btn-sm btn-outline-<?= $alertesSeules ? 'warning' : 'secondary' ?>">
                <i class="fas fa-bell me-1"></i><?= $alertesSeules ? 'Voir tout' : 'Alertes seulement' ?>
            </a>
            <?php if ($isManager && !empty($produitsHors)): ?>
            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                <i class="fas fa-plus me-1"></i>Ajouter au stock
            </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php elseif (empty($inventaire)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        <?= $alertesSeules ? 'Aucune alerte stock.' : 'Inventaire vide.' ?>
        <?php if ($isManager && !$alertesSeules && !empty($produitsHors)): ?>
        Cliquez sur « Ajouter au stock » pour créer le 1er produit dans cet inventaire.
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
            <strong><?= count($inventaire) ?> ligne<?= count($inventaire) > 1 ? 's' : '' ?> de stock</strong>
            <input type="text" id="searchInv" class="form-control form-control-sm" placeholder="Rechercher..." style="max-width:240px">
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="tableInv">
                    <thead class="table-light">
                        <tr><th>Produit</th><th>Spé.</th><th>Cat.</th><th class="text-end">Stock</th><th class="text-end">Seuil alerte</th><th>Emplacement</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($inventaire as $i):
                            $isAlerte = $i['seuil_alerte'] !== null && (float)$i['quantite_actuelle'] <= (float)$i['seuil_alerte'];
                        ?>
                        <tr class="<?= $isAlerte ? 'table-warning' : '' ?>">
                            <td><strong><?= htmlspecialchars($i['nom']) ?></strong>
                                <?php if ($isAlerte): ?><i class="fas fa-bell text-warning ms-1" title="Stock bas"></i><?php endif; ?>
                            </td>
                            <td><small style="color:<?= htmlspecialchars($i['specialite_couleur'] ?? '#6c757d') ?>"><?= htmlspecialchars($i['specialite_nom'] ?? '—') ?></small></td>
                            <td><small><?= htmlspecialchars($i['categorie']) ?></small></td>
                            <td class="text-end" data-sort="<?= (float)$i['quantite_actuelle'] ?>"><strong><?= rtrim(rtrim(number_format((float)$i['quantite_actuelle'], 3, ',', ' '), '0'), ',') ?></strong> <small class="text-muted"><?= htmlspecialchars($i['unite'] ?? '') ?></small></td>
                            <td class="text-end"><small class="text-muted"><?= $i['seuil_alerte'] !== null ? rtrim(rtrim(number_format((float)$i['seuil_alerte'], 3, ',', ' '), '0'), ',') : '—' ?></small></td>
                            <td><small><?= htmlspecialchars($i['emplacement'] ?? '—') ?></small></td>
                            <td class="text-center">
                                <button class="btn btn-sm btn-outline-primary"
                                        data-bs-toggle="modal" data-bs-target="#modalMvt"
                                        data-id="<?= (int)$i['id'] ?>" data-nom="<?= htmlspecialchars($i['nom']) ?>"
                                        data-unite="<?= htmlspecialchars($i['unite'] ?? '') ?>"
                                        data-stock="<?= (float)$i['quantite_actuelle'] ?>"
                                        title="Mouvement"><i class="fas fa-exchange-alt"></i></button>
                                <a href="<?= BASE_URL ?>/maintenance/inventaireHistorique/<?= (int)$i['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Historique"><i class="fas fa-history"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="p-2 d-flex justify-content-between align-items-center bg-light border-top">
                <small id="infoInv" class="text-muted"></small>
                <ul class="pagination pagination-sm mb-0" id="paginationInv"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal mouvement stock -->
<div class="modal fade" id="modalMvt" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formMvt">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-exchange-alt me-2"></i>Mouvement de stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="residence_id" value="<?= (int)($residenceCourante['id'] ?? 0) ?>">
                    <p class="mb-2"><strong id="mvtNom"></strong> · Stock actuel : <span id="mvtStock"></span> <span id="mvtUnite"></span></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Type <span class="text-danger">*</span></label>
                            <select name="type" class="form-select" required>
                                <option value="entree">Entrée (+)</option>
                                <option value="sortie">Sortie (−)</option>
                                <option value="ajustement">Ajustement (= valeur exacte)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Quantité <span class="text-danger">*</span></label>
                            <input type="number" step="0.001" min="0" name="quantite" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Motif</label>
                            <select name="motif" class="form-select">
                                <option value="livraison">Livraison</option>
                                <option value="usage">Usage</option>
                                <option value="perte">Perte</option>
                                <option value="casse">Casse</option>
                                <option value="inventaire">Inventaire</option>
                                <option value="intervention">Intervention</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isManager && !empty($produitsHors)): ?>
<!-- Modal ajouter au stock -->
<div class="modal fade" id="modalAjouter" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/maintenance/inventaireAjouter">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Ajouter un produit au stock</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="residence_id" value="<?= (int)$residenceCourante['id'] ?>">
                    <div class="mb-3">
                        <label class="form-label">Produit <span class="text-danger">*</span></label>
                        <select name="produit_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($produitsHors as $p): ?>
                            <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['nom']) ?> (<?= $p['categorie'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Seuil d'alerte (optionnel)</label>
                        <input type="number" step="0.001" min="0" name="seuil_alerte" class="form-control" placeholder="Ex: 5">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Emplacement</label>
                        <input type="text" name="emplacement" class="form-control" placeholder="Ex: Local technique 1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white">Ajouter</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($inventaire)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('tableInv', { rowsPerPage: 15, searchInputId: 'searchInv', paginationId: 'paginationInv', infoId: 'infoInv', excludeColumns: [6] });
</script>
<?php endif; ?>

<script>
const BASE = '<?= BASE_URL ?>';
document.getElementById('modalMvt').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('mvtNom').textContent = btn.dataset.nom;
    document.getElementById('mvtStock').textContent = parseFloat(btn.dataset.stock).toLocaleString('fr-FR');
    document.getElementById('mvtUnite').textContent = btn.dataset.unite || '';
    document.getElementById('formMvt').action = BASE + '/maintenance/inventaireMouvement/' + btn.dataset.id;
});
</script>
