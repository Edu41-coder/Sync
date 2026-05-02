<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-truck',          'text' => 'Commandes',       'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$badge = ['brouillon'=>'secondary','envoyee'=>'info','livree_partiel'=>'warning','livree'=>'success','facturee'=>'primary','annulee'=>'dark'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-truck text-success me-2"></i>Commandes fournisseurs</h1>
            <p class="text-muted mb-0"><?= count($commandes) ?> commande<?= count($commandes) > 1 ? 's' : '' ?></p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <form method="GET" action="<?= BASE_URL ?>/maintenance/commandes">
                <select name="statut" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">Tous statuts</option>
                    <?php foreach ($statuts as $s): ?>
                    <option value="<?= $s ?>" <?= $filtreStatut === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="<?= BASE_URL ?>/maintenance/commandeForm" class="btn btn-success">
                <i class="fas fa-plus me-1"></i>Nouvelle commande
            </a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($commandes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="fas fa-truck fa-3x opacity-50 mb-3 d-block"></i>
                <h6>Aucune commande</h6>
                <a href="<?= BASE_URL ?>/maintenance/commandeForm" class="btn btn-sm btn-success mt-2"><i class="fas fa-plus me-1"></i>Créer la première</a>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr><th>N° commande</th><th>Date</th><th>Résidence</th><th>Fournisseur</th><th class="text-center">Lignes</th><th class="text-end">TTC</th><th>Statut</th><th class="text-center">Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($commandes as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['numero_commande']) ?></strong></td>
                            <td><small><?= date('d/m/Y', strtotime($c['date_commande'])) ?></small></td>
                            <td><small><?= htmlspecialchars($c['residence_nom']) ?></small></td>
                            <td><?= htmlspecialchars($c['fournisseur_nom']) ?></td>
                            <td class="text-center"><?= (int)$c['nb_lignes'] ?></td>
                            <td class="text-end"><strong><?= number_format((float)$c['montant_total_ttc'], 2, ',', ' ') ?> €</strong></td>
                            <td><span class="badge bg-<?= $badge[$c['statut']] ?? 'secondary' ?>"><?= $c['statut'] ?></span></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/maintenance/commandeShow/<?= (int)$c['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
