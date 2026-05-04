<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-shield-alt',     'text' => 'Sinistres',       'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabels = [
    'degat_eaux' => 'Dégât des eaux', 'incendie' => 'Incendie', 'vol_cambriolage' => 'Vol/Cambriolage',
    'bris_glace' => 'Bris de glace', 'catastrophe_naturelle' => 'Catastrophe naturelle',
    'vandalisme' => 'Vandalisme', 'chute_resident' => 'Chute résident',
    'panne_equipement' => 'Panne équipement', 'autre' => 'Autre',
];
$typeIcons = [
    'degat_eaux' => 'fa-tint', 'incendie' => 'fa-fire', 'vol_cambriolage' => 'fa-user-secret',
    'bris_glace' => 'fa-shield-alt', 'catastrophe_naturelle' => 'fa-cloud-showers-heavy',
    'vandalisme' => 'fa-hammer', 'chute_resident' => 'fa-user-injured',
    'panne_equipement' => 'fa-tools', 'autre' => 'fa-exclamation-circle',
];
$statutLabels = [
    'declare' => 'Déclaré', 'transmis_assureur' => 'Transmis assureur',
    'expertise_en_cours' => 'Expertise', 'en_reparation' => 'En réparation',
    'indemnise' => 'Indemnisé', 'clos' => 'Clos', 'refuse' => 'Refusé',
];
$statutColors = [
    'declare' => 'warning', 'transmis_assureur' => 'info', 'expertise_en_cours' => 'primary',
    'en_reparation' => 'primary', 'indemnise' => 'success', 'clos' => 'secondary', 'refuse' => 'danger',
];
$graviteLabels = ['mineur' => 'Mineur', 'modere' => 'Modéré', 'majeur' => 'Majeur', 'catastrophe' => 'Catastrophe'];
$graviteColors = ['mineur' => 'info', 'modere' => 'warning', 'majeur' => 'danger', 'catastrophe' => 'dark'];

$totalIndemnise = (float)($stats['montant_indemnise_total'] ?? 0);
$totalEstime    = (float)($stats['montant_estime_total'] ?? 0);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-shield-alt me-2 text-danger"></i>Sinistres</h2>
        <?php if ($canDeclare): ?>
        <a href="<?= BASE_URL ?>/sinistre/create" class="btn btn-danger">
            <i class="fas fa-plus me-1"></i>Déclarer un sinistre
        </a>
        <?php endif; ?>
    </div>

    <!-- KPI -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Total</div>
                    <div class="h3 mb-0"><?= (int)($stats['total'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100 border-warning">
                <div class="card-body">
                    <div class="text-warning small text-uppercase">En cours</div>
                    <div class="h3 mb-0"><?= (int)($stats['en_cours'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">Clos / Indemnisés / Refusés</div>
                    <div class="h3 mb-0"><?= (int)($stats['clos'] ?? 0) ?></div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm h-100 border-success">
                <div class="card-body">
                    <div class="text-success small text-uppercase">Indemnisé total</div>
                    <div class="h5 mb-0"><?= number_format($totalIndemnise, 2, ',', ' ') ?> €</div>
                    <div class="small text-muted">sur <?= number_format($totalEstime, 2, ',', ' ') ?> € estimés</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-12 col-md-3">
                    <input type="text" name="search" class="form-control form-control-sm" placeholder="Rechercher (titre, n° dossier...)" value="<?= htmlspecialchars($filters['search'] ?? '') ?>">
                </div>
                <div class="col-6 col-md-2">
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">— Tous statuts —</option>
                        <?php foreach ($statutLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($filters['statut'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="type" class="form-select form-select-sm">
                        <option value="">— Tous types —</option>
                        <?php foreach ($typeLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($filters['type'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-6 col-md-2">
                    <select name="gravite" class="form-select form-select-sm">
                        <option value="">— Toutes gravités —</option>
                        <?php foreach ($graviteLabels as $k => $v): ?>
                            <option value="<?= $k ?>" <?= ($filters['gravite'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php if (count($residences) > 1): ?>
                <div class="col-6 col-md-2">
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="">— Toutes résidences —</option>
                        <?php foreach ($residences as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= (int)($filters['residence_id'] ?? 0) === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-1">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Liste -->
    <?php if (empty($sinistres)): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun sinistre à afficher.</div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-hover mb-0" id="sinistresTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width:80px">#</th>
                            <th>Date</th>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Gravité</th>
                            <th>Lieu</th>
                            <th>Résidence</th>
                            <th>Déclarant</th>
                            <th class="text-end">Montant estimé</th>
                            <th>Statut</th>
                            <th class="no-sort text-center" style="width:80px">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sinistres as $s): ?>
                        <tr>
                            <td>#<?= (int)$s['id'] ?></td>
                            <td data-sort="<?= htmlspecialchars($s['date_survenue']) ?>"><?= date('d/m/Y', strtotime($s['date_survenue'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($s['titre']) ?></strong>
                                <?php if (!empty($s['nb_documents'])): ?>
                                    <span class="badge bg-secondary ms-1" title="Documents joints"><i class="fas fa-paperclip"></i> <?= (int)$s['nb_documents'] ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas <?= $typeIcons[$s['type_sinistre']] ?? 'fa-question' ?> me-1 text-muted"></i>
                                <?= $typeLabels[$s['type_sinistre']] ?? htmlspecialchars($s['type_sinistre']) ?>
                            </td>
                            <td><span class="badge bg-<?= $graviteColors[$s['gravite']] ?? 'secondary' ?>"><?= $graviteLabels[$s['gravite']] ?? htmlspecialchars($s['gravite']) ?></span></td>
                            <td>
                                <?php if (!empty($s['lot_id'])): ?>
                                    <i class="fas fa-door-open text-info me-1"></i>Lot <?= htmlspecialchars($s['numero_lot'] ?? '?') ?>
                                <?php else: ?>
                                    <i class="fas fa-building text-secondary me-1"></i><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $s['lieu_partie_commune'] ?? ''))) ?>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($s['residence_nom']) ?></td>
                            <td class="small"><?= htmlspecialchars(trim($s['declarant_nom'] ?? '') ?: ($s['declarant_username'] ?? '—')) ?></td>
                            <td class="text-end" data-sort="<?= (float)($s['montant_estime'] ?? 0) ?>">
                                <?= $s['montant_estime'] !== null ? number_format((float)$s['montant_estime'], 2, ',', ' ') . ' €' : '—' ?>
                            </td>
                            <td><span class="badge bg-<?= $statutColors[$s['statut']] ?? 'secondary' ?>"><?= $statutLabels[$s['statut']] ?? htmlspecialchars($s['statut']) ?></span></td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/sinistre/show/<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="d-flex justify-content-between align-items-center px-3 py-2">
                    <small class="text-muted" id="tableInfo"></small>
                    <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (document.getElementById('sinistresTable')) {
        new DataTableWithPagination('sinistresTable', {
            rowsPerPage: 15,
            excludeColumns: [10],
            paginationId: 'pagination',
            infoId: 'tableInfo'
        });
    }
});
</script>
