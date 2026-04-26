<?php
/**
 * Partial détail commande + workflow + réception.
 * Attend : $modulePath, $moduleLabel, $moduleColor, $commande
 */
$statutLabels = Commande::STATUTS_LABELS;
$statutColors = Commande::STATUTS_COLORS;

$canEnvoyer      = $commande['statut'] === 'brouillon';
$canReceptionner = in_array($commande['statut'], ['envoyee', 'livree_partiel']);
$canFacturer     = $commande['statut'] === 'livree';
$canAnnuler      = in_array($commande['statut'], ['brouillon', 'envoyee']);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-file-invoice me-2 text-<?= htmlspecialchars($moduleColor) ?>"></i><?= htmlspecialchars($commande['numero_commande']) ?>
                <span class="badge bg-<?= $statutColors[$commande['statut']] ?? 'secondary' ?> ms-2"><?= $statutLabels[$commande['statut']] ?? $commande['statut'] ?></span>
            </h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($commande['fournisseur_nom']) ?> → <?= htmlspecialchars($commande['residence_nom']) ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <?php if ($canEnvoyer): ?>
            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes/envoyer/<?= (int)$commande['id'] ?>" class="btn btn-info" onclick="return confirm('Marquer cette commande comme envoyée au fournisseur ?')">
                <i class="fas fa-paper-plane me-1"></i>Envoyer
            </a>
            <?php endif; ?>
            <?php if ($canReceptionner): ?>
            <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalReception">
                <i class="fas fa-dolly me-1"></i>Réceptionner
            </button>
            <?php endif; ?>
            <?php if ($canFacturer): ?>
            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes/facturer/<?= (int)$commande['id'] ?>" class="btn btn-primary" onclick="return confirm('Marquer cette commande comme facturée ?')">
                <i class="fas fa-file-invoice-dollar me-1"></i>Facturer
            </a>
            <?php endif; ?>
            <?php if ($canAnnuler): ?>
            <a href="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes/delete/<?= (int)$commande['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('<?= $commande['statut'] === 'brouillon' ? 'Supprimer ce brouillon' : 'Annuler cette commande' ?> ?')">
                <i class="fas fa-<?= $commande['statut'] === 'brouillon' ? 'trash' : 'ban' ?> me-1"></i><?= $commande['statut'] === 'brouillon' ? 'Supprimer' : 'Annuler' ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-truck me-2"></i>Fournisseur</h6></div>
                <div class="card-body">
                    <strong><?= htmlspecialchars($commande['fournisseur_nom']) ?></strong><br>
                    <?php if (!empty($commande['fournisseur_adresse'])): ?><small class="text-muted"><?= htmlspecialchars($commande['fournisseur_adresse']) ?><br><?= htmlspecialchars($commande['fournisseur_cp'] ?? '') ?> <?= htmlspecialchars($commande['fournisseur_ville'] ?? '') ?></small><br><?php endif; ?>
                    <?php if (!empty($commande['fournisseur_telephone'])): ?><small><i class="fas fa-phone me-1"></i><?= htmlspecialchars($commande['fournisseur_telephone']) ?></small><br><?php endif; ?>
                    <?php if (!empty($commande['fournisseur_email'])): ?><small><i class="fas fa-envelope me-1"></i><a href="mailto:<?= htmlspecialchars($commande['fournisseur_email']) ?>"><?= htmlspecialchars($commande['fournisseur_email']) ?></a></small><?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-calendar me-2"></i>Dates & infos</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Date commande</dt><dd class="col-7"><?= date('d/m/Y', strtotime($commande['date_commande'])) ?></dd>
                        <dt class="col-5">Livraison prévue</dt><dd class="col-7"><?= $commande['date_livraison_prevue'] ? date('d/m/Y', strtotime($commande['date_livraison_prevue'])) : '—' ?></dd>
                        <dt class="col-5">Livraison effective</dt><dd class="col-7"><?= $commande['date_livraison_effective'] ? date('d/m/Y', strtotime($commande['date_livraison_effective'])) : '—' ?></dd>
                        <dt class="col-5">Créée par</dt><dd class="col-7"><?= !empty($commande['created_by_prenom']) ? htmlspecialchars($commande['created_by_prenom'] . ' ' . $commande['created_by_nom']) : '—' ?></dd>
                        <?php if (!empty($commande['notes'])): ?>
                        <dt class="col-12 mt-2">Notes</dt>
                        <dd class="col-12"><small class="text-muted"><?= nl2br(htmlspecialchars($commande['notes'])) ?></small></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header py-2"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Lignes de commande</h6></div>
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th class="text-end">Qté commandée</th>
                        <th class="text-end">Qté reçue</th>
                        <th class="text-end">Prix u. HT</th>
                        <th class="text-end">TVA</th>
                        <th class="text-end">Total HT</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($commande['lignes'] as $l):
                        $ligneHt = $l['quantite_commandee'] * $l['prix_unitaire_ht'];
                        $recCompl = $l['quantite_recue'] !== null && $l['quantite_recue'] >= $l['quantite_commandee'];
                        $recPart  = $l['quantite_recue'] !== null && $l['quantite_recue'] > 0 && !$recCompl;
                    ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($l['produit_nom']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($l['designation']) ?></small></td>
                        <td><small><?= htmlspecialchars($l['categorie'] ?? '') ?></small></td>
                        <td class="text-end"><?= number_format($l['quantite_commandee'], 3, ',', ' ') ?> <?= htmlspecialchars($l['unite'] ?? '') ?></td>
                        <td class="text-end">
                            <?php if ($l['quantite_recue'] !== null): ?>
                                <strong class="text-<?= $recCompl ? 'success' : ($recPart ? 'warning' : 'muted') ?>"><?= number_format($l['quantite_recue'], 3, ',', ' ') ?></strong> <?= htmlspecialchars($l['unite'] ?? '') ?>
                                <?php if ($recCompl): ?><i class="fas fa-check-circle text-success ms-1"></i>
                                <?php elseif ($recPart): ?><i class="fas fa-adjust text-warning ms-1"></i>
                                <?php endif; ?>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td class="text-end"><?= number_format($l['prix_unitaire_ht'], 2, ',', ' ') ?> €</td>
                        <td class="text-end"><?= number_format($l['taux_tva'], 0, ',', ' ') ?> %</td>
                        <td class="text-end"><strong><?= number_format($ligneHt, 2, ',', ' ') ?> €</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light">
                    <tr><td colspan="6" class="text-end"><strong>Total HT :</strong></td><td class="text-end"><strong><?= number_format($commande['montant_total_ht'], 2, ',', ' ') ?> €</strong></td></tr>
                    <tr><td colspan="6" class="text-end"><strong>TVA :</strong></td><td class="text-end"><strong><?= number_format($commande['montant_tva'], 2, ',', ' ') ?> €</strong></td></tr>
                    <tr><td colspan="6" class="text-end fs-5"><strong>Total TTC :</strong></td><td class="text-end fs-5 text-<?= htmlspecialchars($moduleColor) ?>"><strong><?= number_format($commande['montant_total_ttc'], 2, ',', ' ') ?> €</strong></td></tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<?php if ($canReceptionner): ?>
<div class="modal fade" id="modalReception" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/<?= htmlspecialchars($modulePath) ?>/commandes/receptionner/<?= (int)$commande['id'] ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-dolly me-2"></i>Réception — <?= htmlspecialchars($commande['numero_commande']) ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <p class="text-muted small">Saisissez les quantités reçues pour chaque ligne. <strong>Le stock sera automatiquement mis à jour</strong> avec un mouvement d'entrée (motif : livraison).</p>

                    <table class="table table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>Produit</th>
                                <th class="text-end">Commandée</th>
                                <th class="text-end">Déjà reçue</th>
                                <th style="width:180px">Quantité reçue totale</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($commande['lignes'] as $l): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($l['produit_nom']) ?></strong></td>
                                <td class="text-end"><?= number_format($l['quantite_commandee'], 3, ',', ' ') ?> <?= htmlspecialchars($l['unite'] ?? '') ?></td>
                                <td class="text-end"><?= $l['quantite_recue'] !== null ? number_format($l['quantite_recue'], 3, ',', ' ') : '—' ?></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.001" min="0"
                                               name="quantites_recues[<?= (int)$l['id'] ?>]"
                                               class="form-control"
                                               value="<?= $l['quantite_recue'] ?? $l['quantite_commandee'] ?>"
                                               max="<?= $l['quantite_commandee'] ?>">
                                        <span class="input-group-text"><?= htmlspecialchars($l['unite'] ?? '') ?></span>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-1"></i>Si toutes les quantités sont reçues en totalité, la commande passe en <strong>Livrée</strong>.
                        Sinon, elle passe en <strong>Livrée partiel</strong> et vous pourrez réceptionner le reste plus tard.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Valider la réception</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
