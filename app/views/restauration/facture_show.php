<?php $statutColors = ['brouillon'=>'secondary','emise'=>'warning','payee'=>'success','annulee'=>'danger']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-file-invoice', 'text' => 'Factures', 'url' => BASE_URL . '/restauration/factures'],
    ['icon' => 'fas fa-file-invoice', 'text' => $facture['numero_facture'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i>Facture <?= htmlspecialchars($facture['numero_facture']) ?></h5>
                    <span class="badge bg-<?= $statutColors[$facture['statut']] ?? 'secondary' ?> fs-6"><?= ucfirst($facture['statut']) ?></span>
                </div>
                <div class="card-body">
                    <!-- En-tête facture -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="text-muted">Client</h6>
                            <p class="mb-1"><strong><?= htmlspecialchars($facture['client_nom'] ?? 'N/A') ?></strong></p>
                            <span class="badge bg-<?= $facture['type_client'] === 'resident' ? 'primary' : ($facture['type_client'] === 'hote' ? 'info' : 'secondary') ?>"><?= ucfirst($facture['type_client']) ?></span>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <h6 class="text-muted">Détails</h6>
                            <p class="mb-0">Date : <strong><?= date('d/m/Y', strtotime($facture['date_facture'])) ?></strong></p>
                            <p class="mb-0">Résidence : <?= htmlspecialchars($facture['residence_nom']) ?></p>
                            <?php if ($facture['mode_paiement']): ?>
                            <p class="mb-0">Paiement : <?= ucfirst($facture['mode_paiement']) ?></p>
                            <?php endif; ?>
                            <?php if ($facture['date_paiement']): ?>
                            <p class="mb-0">Payée le : <?= date('d/m/Y', strtotime($facture['date_paiement'])) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Lignes -->
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Désignation</th>
                                <th>Type</th>
                                <th class="text-center">Qté</th>
                                <th class="text-end">Prix unit.</th>
                                <th class="text-end">Total HT</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facture['lignes'] as $l): ?>
                            <tr>
                                <td><?= htmlspecialchars($l['designation']) ?></td>
                                <td><small class="text-muted"><?= str_replace('_', ' ', $l['type_ligne']) ?></small></td>
                                <td class="text-center"><?= $l['quantite'] ?></td>
                                <td class="text-end"><?= number_format($l['prix_unitaire'], 2, ',', ' ') ?> &euro;</td>
                                <td class="text-end"><?= number_format($l['montant_ht'], 2, ',', ' ') ?> &euro;</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr><td colspan="4" class="text-end"><strong>Total HT</strong></td><td class="text-end"><?= number_format($facture['montant_ht'], 2, ',', ' ') ?> &euro;</td></tr>
                            <tr><td colspan="4" class="text-end">TVA <?= $facture['taux_tva'] ?> %</td><td class="text-end"><?= number_format($facture['montant_tva'], 2, ',', ' ') ?> &euro;</td></tr>
                            <tr class="table-dark"><td colspan="4" class="text-end"><strong>Total TTC</strong></td><td class="text-end"><strong><?= number_format($facture['montant_ttc'], 2, ',', ' ') ?> &euro;</strong></td></tr>
                        </tfoot>
                    </table>

                    <?php if ($facture['notes']): ?>
                    <div class="alert alert-light"><i class="fas fa-info-circle me-2"></i><?= htmlspecialchars($facture['notes']) ?></div>
                    <?php endif; ?>
                </div>
                <div class="card-footer d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/restauration/factures" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
                    <div>
                        <?php if ($isManager && $facture['statut'] === 'emise'): ?>
                        <div class="dropdown d-inline">
                            <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-check me-2"></i>Marquer payée</button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/factures/payer/<?= $facture['id'] ?>?mode=cb"><i class="fas fa-credit-card me-2"></i>Carte bancaire</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/factures/payer/<?= $facture['id'] ?>?mode=especes"><i class="fas fa-money-bill me-2"></i>Espèces</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/factures/payer/<?= $facture['id'] ?>?mode=cheque"><i class="fas fa-money-check me-2"></i>Chèque</a></li>
                                <li><a class="dropdown-item" href="<?= BASE_URL ?>/restauration/factures/payer/<?= $facture['id'] ?>?mode=virement"><i class="fas fa-university me-2"></i>Virement</a></li>
                            </ul>
                        </div>
                        <?php endif; ?>
                        <button class="btn btn-outline-dark" onclick="window.print()"><i class="fas fa-print me-2"></i>Imprimer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
