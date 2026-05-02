<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-truck',          'text' => 'Commandes',       'url' => BASE_URL . '/maintenance/commandes'],
    ['icon' => 'fas fa-eye',            'text' => $commande['numero_commande'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$badge = ['brouillon'=>'secondary','envoyee'=>'info','livree_partiel'=>'warning','livree'=>'success','facturee'=>'primary','annulee'=>'dark'];
$totalRecu = 0; $totalCmd = 0;
foreach ($lignes as $l) { $totalCmd += (float)$l['quantite_commandee']; $totalRecu += (float)($l['quantite_recue'] ?? 0); }
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-truck text-success me-2"></i>Commande <?= htmlspecialchars($commande['numero_commande']) ?></h1>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($commande['fournisseur_nom']) ?> · <?= htmlspecialchars($commande['residence_nom']) ?>
                · <span class="badge bg-<?= $badge[$commande['statut']] ?? 'secondary' ?>"><?= $commande['statut'] ?></span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if (in_array($commande['statut'], ['brouillon','envoyee'])): ?>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalReception"><i class="fas fa-box-open me-1"></i>Réceptionner</button>
            <?php endif; ?>
            <?php if ($commande['statut'] !== 'annulee' && $commande['statut'] !== 'facturee'): ?>
            <form method="POST" action="<?= BASE_URL ?>/maintenance/commandeStatut/<?= (int)$commande['id'] ?>" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <select name="statut" class="form-select form-select-sm d-inline-block w-auto">
                    <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= $commande['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-outline-primary">Mettre à jour</button>
            </form>
            <?php endif; ?>
            <form method="POST" action="<?= BASE_URL ?>/maintenance/commandeDelete/<?= (int)$commande['id'] ?>" class="d-inline" onsubmit="return confirm('Confirmer ?')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i><?= $commande['statut'] === 'brouillon' ? 'Supprimer' : 'Annuler' ?></button>
            </form>
        </div>
    </div>

    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body p-3"><small class="text-muted d-block">Date commande</small><strong><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body p-3"><small class="text-muted d-block">Livraison prévue</small><strong><?= !empty($commande['date_livraison_prevue']) ? date('d/m/Y', strtotime($commande['date_livraison_prevue'])) : '—' ?></strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body p-3"><small class="text-muted d-block">Total HT</small><strong><?= number_format((float)$commande['montant_total_ht'], 2, ',', ' ') ?> €</strong></div></div></div>
        <div class="col-md-3"><div class="card shadow-sm"><div class="card-body p-3"><small class="text-muted d-block">Total TTC</small><strong class="text-success"><?= number_format((float)$commande['montant_total_ttc'], 2, ',', ' ') ?> €</strong></div></div></div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light"><strong><i class="fas fa-list me-2"></i>Lignes</strong></div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead class="table-light"><tr><th>Produit / Désignation</th><th class="text-end">Quantité</th><th class="text-end">PU HT</th><th class="text-end">TVA</th><th class="text-end">Total HT</th><th class="text-end">Reçue</th></tr></thead>
                <tbody>
                    <?php foreach ($lignes as $l): ?>
                    <tr>
                        <td>
                            <?php if ($l['produit_nom']): ?>
                            <strong><?= htmlspecialchars($l['produit_nom']) ?></strong><br>
                            <?php endif; ?>
                            <small class="text-muted"><?= htmlspecialchars($l['designation']) ?></small>
                        </td>
                        <td class="text-end"><?= rtrim(rtrim(number_format((float)$l['quantite_commandee'], 3, ',', ' '), '0'), ',') ?> <small class="text-muted"><?= htmlspecialchars($l['unite'] ?? '') ?></small></td>
                        <td class="text-end"><?= number_format((float)$l['prix_unitaire_ht'], 2, ',', ' ') ?> €</td>
                        <td class="text-end"><?= number_format((float)$l['taux_tva'], 0) ?>%</td>
                        <td class="text-end"><strong><?= number_format((float)$l['montant_ligne_ht'], 2, ',', ' ') ?> €</strong></td>
                        <td class="text-end">
                            <?php if ($l['quantite_recue'] !== null): ?>
                            <span class="badge bg-<?= (float)$l['quantite_recue'] >= (float)$l['quantite_commandee'] ? 'success' : 'warning' ?>">
                                <?= rtrim(rtrim(number_format((float)$l['quantite_recue'], 3, ',', ' '), '0'), ',') ?>
                            </span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($commande['notes'])): ?>
    <div class="alert alert-light border mt-3"><i class="fas fa-comment me-1 text-muted"></i><?= nl2br(htmlspecialchars($commande['notes'])) ?></div>
    <?php endif; ?>

</div>

<!-- Modal réception -->
<div class="modal fade" id="modalReception" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/maintenance/commandeReceptionner/<?= (int)$commande['id'] ?>">
                <div class="modal-header bg-warning"><h5 class="modal-title">Réception commande</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <div class="alert alert-info small">À l'enregistrement, le stock sera mis à jour automatiquement (entrée auto pour les lignes liées à un produit catalogue).</div>
                    <table class="table table-sm">
                        <thead class="table-light"><tr><th>Produit</th><th class="text-end">Commandé</th><th class="text-end">Déjà reçu</th><th class="text-end" style="width:160px">Quantité reçue</th></tr></thead>
                        <tbody>
                            <?php foreach ($lignes as $l): ?>
                            <tr>
                                <td><small><?= htmlspecialchars($l['designation']) ?></small></td>
                                <td class="text-end"><?= rtrim(rtrim(number_format((float)$l['quantite_commandee'], 3, ',', ' '), '0'), ',') ?> <?= htmlspecialchars($l['unite'] ?? '') ?></td>
                                <td class="text-end"><?= rtrim(rtrim(number_format((float)($l['quantite_recue'] ?? 0), 3, ',', ' '), '0'), ',') ?></td>
                                <td><input type="number" step="0.001" min="0" name="quantite_recue[<?= (int)$l['id'] ?>]" class="form-control form-control-sm text-end" value="<?= rtrim(rtrim(number_format((float)$l['quantite_commandee'], 3, '.', ''), '0'), '.') ?>"></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-box-open me-1"></i>Valider la réception</button>
                </div>
            </form>
        </div>
    </div>
</div>
