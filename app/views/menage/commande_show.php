<?php $statutColors = ['brouillon'=>'secondary','envoyee'=>'primary','livree_partiel'=>'warning','livree'=>'success','facturee'=>'info','annulee'=>'danger']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/menage/commandes'],
    ['icon' => 'fas fa-truck', 'text' => $commande['numero_commande'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-truck me-2 text-info"></i>Commande <?= htmlspecialchars($commande['numero_commande']) ?></h5>
                    <span class="badge bg-<?= $statutColors[$commande['statut']] ?? 'secondary' ?> fs-6"><?= ucfirst(str_replace('_',' ',$commande['statut'])) ?></span>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <h6 class="text-muted">Fournisseur</h6>
                            <p class="mb-1"><strong><?= htmlspecialchars($commande['fournisseur_nom']) ?></strong></p>
                            <?php if ($commande['fournisseur_email']): ?><small class="text-muted"><?= htmlspecialchars($commande['fournisseur_email']) ?></small><br><?php endif; ?>
                            <?php if ($commande['fournisseur_telephone']): ?><small class="text-muted"><?= htmlspecialchars($commande['fournisseur_telephone']) ?></small><?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <h6 class="text-muted">Résidence</h6>
                            <p><?= htmlspecialchars($commande['residence_nom']) ?></p>
                        </div>
                        <div class="col-md-4 text-md-end">
                            <h6 class="text-muted">Dates</h6>
                            <p class="mb-0">Commande : <strong><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></strong></p>
                            <?php if ($commande['date_livraison_prevue']): ?><p class="mb-0">Livraison prévue : <?= date('d/m/Y', strtotime($commande['date_livraison_prevue'])) ?></p><?php endif; ?>
                            <?php if ($commande['date_livraison_effective']): ?><p class="mb-0 text-success">Livrée le : <?= date('d/m/Y', strtotime($commande['date_livraison_effective'])) ?></p><?php endif; ?>
                        </div>
                    </div>

                    <!-- Lignes de commande -->
                    <?php $showReception = $isManager && in_array($commande['statut'], ['envoyee', 'livree_partiel']); ?>
                    <?php if ($showReception): ?><form method="POST" action="<?= BASE_URL ?>/menage/commandes/receptionner/<?= $commande['id'] ?>"><input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>"><?php endif; ?>

                    <table class="table">
                        <thead><tr><th>Produit</th><th class="text-center">Commandé</th><?php if ($showReception || $commande['statut'] === 'livree'): ?><th class="text-center">Reçu</th><?php endif; ?><th class="text-end">Prix unit. HT</th><th class="text-end">Total HT</th></tr></thead>
                        <tbody>
                        <?php foreach ($commande['lignes'] as $l): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($l['produit_nom'] ?? $l['designation']) ?></strong><small class="text-muted ms-1">(<?= $l['unite'] ?>)</small></td>
                            <td class="text-center"><?= $l['quantite_commandee'] ?></td>
                            <?php if ($showReception): ?>
                            <td class="text-center"><input type="number" name="quantites_recues[<?= $l['id'] ?>]" class="form-control form-control-sm text-center" style="width:80px;display:inline" value="<?= $l['quantite_recue'] ?? $l['quantite_commandee'] ?>" step="0.1" min="0"></td>
                            <?php elseif ($commande['statut'] === 'livree'): ?>
                            <td class="text-center"><?= $l['quantite_recue'] ?? '-' ?></td>
                            <?php endif; ?>
                            <td class="text-end"><?= number_format($l['prix_unitaire_ht'],2,',',' ') ?> &euro;</td>
                            <td class="text-end"><?= number_format($l['montant_ligne_ht'],2,',',' ') ?> &euro;</td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="<?= ($showReception || $commande['statut'] === 'livree') ? 4 : 3 ?>" class="text-end"><strong>Total HT</strong></td><td class="text-end"><?= number_format($commande['montant_total_ht'],2,',',' ') ?> &euro;</td></tr>
                            <tr><td colspan="<?= ($showReception || $commande['statut'] === 'livree') ? 4 : 3 ?>" class="text-end">TVA (20%)</td><td class="text-end"><?= number_format($commande['montant_tva'],2,',',' ') ?> &euro;</td></tr>
                            <tr class="table-dark"><td colspan="<?= ($showReception || $commande['statut'] === 'livree') ? 4 : 3 ?>" class="text-end"><strong>Total TTC</strong></td><td class="text-end"><strong><?= number_format($commande['montant_total_ttc'],2,',',' ') ?> &euro;</strong></td></tr>
                        </tfoot>
                    </table>

                    <?php if ($showReception): ?><div class="text-end"><button type="submit" class="btn btn-success"><i class="fas fa-check me-2"></i>Réceptionner (mettre à jour stock)</button></div></form><?php endif; ?>

                    <?php if ($commande['notes']): ?><div class="alert alert-light mt-3"><i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($commande['notes']) ?></div><?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/menage/commandes" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                    <?php if ($isManager): ?>
                    <div class="d-flex gap-2">
                        <?php if ($commande['statut'] === 'brouillon'): ?>
                        <a href="<?= BASE_URL ?>/menage/commandes/envoyer/<?= $commande['id'] ?>" class="btn btn-primary" onclick="return confirm('Marquer comme envoyée ?')"><i class="fas fa-paper-plane me-2"></i>Envoyer</a>
                        <a href="<?= BASE_URL ?>/menage/commandes/delete/<?= $commande['id'] ?>" class="btn btn-danger" onclick="return confirm('Supprimer ?')"><i class="fas fa-trash me-2"></i>Supprimer</a>
                        <?php endif; ?>
                        <button class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimer</button>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
