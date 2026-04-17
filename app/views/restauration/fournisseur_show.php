<?php $statutColors = ['brouillon'=>'secondary','envoyee'=>'primary','livree_partiel'=>'warning','livree'=>'success','facturee'=>'info','annulee'=>'danger']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => BASE_URL . '/restauration/fournisseurs'],
    ['icon' => 'fas fa-truck-loading', 'text' => $fournisseur['nom'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="row g-4">
        <!-- Fiche fournisseur -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="fas fa-truck me-2"></i><?= htmlspecialchars($fournisseur['nom']) ?></h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm mb-0">
                        <?php if ($fournisseur['siret']): ?><tr><td class="text-muted">SIRET</td><td><code><?= htmlspecialchars($fournisseur['siret']) ?></code></td></tr><?php endif; ?>
                        <?php if ($fournisseur['type_service']): ?><tr><td class="text-muted">Activité</td><td><?= htmlspecialchars($fournisseur['type_service']) ?></td></tr><?php endif; ?>
                        <?php if ($fournisseur['adresse']): ?><tr><td class="text-muted">Adresse</td><td><?= htmlspecialchars($fournisseur['adresse']) ?><br><?= htmlspecialchars($fournisseur['code_postal'] . ' ' . $fournisseur['ville']) ?></td></tr><?php endif; ?>
                        <?php if ($fournisseur['telephone']): ?><tr><td class="text-muted">Téléphone</td><td><a href="tel:<?= $fournisseur['telephone'] ?>"><?= htmlspecialchars($fournisseur['telephone']) ?></a></td></tr><?php endif; ?>
                        <?php if ($fournisseur['email']): ?><tr><td class="text-muted">Email</td><td><a href="mailto:<?= $fournisseur['email'] ?>"><?= htmlspecialchars($fournisseur['email']) ?></a></td></tr><?php endif; ?>
                        <?php if ($fournisseur['contact_nom']): ?><tr><td class="text-muted">Contact</td><td><?= htmlspecialchars($fournisseur['contact_nom']) ?></td></tr><?php endif; ?>
                        <?php if ($fournisseur['iban']): ?><tr><td class="text-muted">IBAN</td><td><code class="small"><?= htmlspecialchars($fournisseur['iban']) ?></code></td></tr><?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Résidences liées -->
            <div class="card shadow-sm mt-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-building me-2"></i>Résidences liées</h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <?php foreach ($fournisseur['residences'] as $r): ?>
                        <tr>
                            <td><?= htmlspecialchars($r['residence_nom']) ?><br><small class="text-muted"><?= $r['ville'] ?></small></td>
                            <td>
                                <?php if ($r['jour_livraison']): ?><span class="badge bg-secondary"><?= $r['jour_livraison'] ?></span><?php endif; ?>
                                <span class="badge bg-<?= $r['statut']==='actif'?'success':'danger' ?>"><?= $r['statut'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($fournisseur['residences'])): ?><tr><td class="text-muted text-center py-3">Aucune résidence liée</td></tr><?php endif; ?>
                    </table>
                </div>
            </div>
        </div>

        <!-- Dernières commandes -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Dernières commandes</h6>
                    <a href="<?= BASE_URL ?>/restauration/commandes/create?fournisseur_id=<?= $fournisseur['id'] ?>" class="btn btn-sm btn-outline-success"><i class="fas fa-cart-plus me-1"></i>Nouvelle commande</a>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead><tr><th>N°</th><th>Date</th><th>Résidence</th><th class="text-end">Total TTC</th><th class="text-center">Statut</th></tr></thead>
                        <tbody>
                            <?php if (empty($fournisseur['commandes_recentes'])): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Aucune commande</td></tr>
                            <?php else: foreach ($fournisseur['commandes_recentes'] as $c): ?>
                            <tr>
                                <td><a href="<?= BASE_URL ?>/restauration/commandes/show/<?= $c['id'] ?>"><?= htmlspecialchars($c['numero_commande']) ?></a></td>
                                <td><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                                <td><?= htmlspecialchars($c['residence_nom']) ?></td>
                                <td class="text-end"><strong><?= number_format($c['montant_total_ttc'], 2, ',', ' ') ?> &euro;</strong></td>
                                <td class="text-center"><span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_',' ',$c['statut'])) ?></span></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-3">
        <a href="<?= BASE_URL ?>/restauration/fournisseurs" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
    </div>
</div>
