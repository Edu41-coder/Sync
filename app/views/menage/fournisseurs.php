<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fas fa-truck-loading me-2 text-info"></i>Fournisseurs (Ménage)</h2>
        <select class="form-select form-select-sm" style="width:auto" onchange="window.location='?residence_id='+this.value">
            <option value="0">-- Résidence --</option>
            <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>" <?= $selectedResidence==$r['id']?'selected':'' ?>><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
        </select>
    </div>

    <?php if (!$selectedResidence): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour voir ses fournisseurs.</div>
    <?php else: ?>

    <!-- Liste fournisseurs -->
    <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between">
            <h6 class="mb-0"><?= count($fournisseurs) ?> fournisseur(s) pour cette résidence</h6>
            <input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher..." style="width:200px">
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="fournisseursTable">
                <thead><tr><th>Fournisseur</th><th>Contact</th><th>Livraison</th><th class="text-center">Commandes</th><th class="text-end">Total dépensé</th><th>Dernière commande</th><th class="text-center">Actions</th></tr></thead>
                <tbody>
                    <?php if (empty($fournisseurs)): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Aucun fournisseur lié à cette résidence.</td></tr>
                    <?php else: foreach ($fournisseurs as $f): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($f['nom']) ?></strong>
                            <?php if ($f['type_service']): ?><br><small class="text-muted"><?= htmlspecialchars($f['type_service']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($f['contact_local'] ?? null): ?><small><?= htmlspecialchars($f['contact_local']) ?></small><br><?php endif; ?>
                            <?php if ($f['telephone_local'] ?? $f['telephone'] ?? null): ?>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($f['telephone_local'] ?? $f['telephone']) ?></small>
                            <?php endif; ?>
                            <?php if ($f['email'] ?? null): ?><br><small><a href="mailto:<?= htmlspecialchars($f['email']) ?>"><?= htmlspecialchars($f['email']) ?></a></small><?php endif; ?>
                        </td>
                        <td>
                            <?php if ($f['jour_livraison'] ?? null): ?><span class="badge bg-secondary"><?= htmlspecialchars($f['jour_livraison']) ?></span><?php endif; ?>
                            <?php if ($f['delai_livraison_jours'] ?? null): ?><br><small class="text-muted"><?= $f['delai_livraison_jours'] ?>j délai</small><?php endif; ?>
                        </td>
                        <td class="text-center"><span class="badge bg-info"><?= $f['nb_commandes'] ?? 0 ?></span></td>
                        <td class="text-end"><strong><?= number_format((float)($f['total_commandes'] ?? 0), 0, ',', ' ') ?> &euro;</strong></td>
                        <td><?= ($f['derniere_commande'] ?? null) ? date('d/m/Y', strtotime($f['derniere_commande'])) : '-' ?></td>
                        <td class="text-end">
                            <div class="btn-group btn-group-sm" role="group">
                                <a href="<?= BASE_URL ?>/menage/commandes/create?fournisseur_id=<?= $f['id'] ?>" class="btn btn-outline-info" title="Commander" data-bs-toggle="tooltip"><i class="fas fa-cart-plus"></i></a>
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
</div>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>new DataTableWithPagination('fournisseursTable', { rowsPerPage: 20, searchInputId: 'searchInput', paginationId: 'pagination', infoId: 'tableInfo' });</script>
