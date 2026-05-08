<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',        'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-receipt',        'text' => 'Quittances résidents','url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatut = [
    'emise'                 => 'primary',
    'partiellement_payee'   => 'warning',
    'payee'                 => 'success',
    'impayee'               => 'danger',
    'annulee'               => 'secondary',
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-receipt me-2 text-primary"></i>Quittances résidents</h2>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#genererModal">
            <i class="fas fa-magic me-1"></i>Générer les quittances d'un mois
        </button>
    </div>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Une quittance = 1 mois × 1 occupation. Les montants sont figés à l'émission (snapshot du loyer + charges + services - APL - APA).
        <strong>Pilote — non contractuel.</strong>
    </div>

    <!-- Filtres -->
    <form method="GET" class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Résidence</label>
                    <select name="residence_id" class="form-select form-select-sm">
                        <option value="0">Toutes accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Année</label>
                    <select name="annee" class="form-select form-select-sm">
                        <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                        <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Mois</label>
                    <select name="mois" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($moisLabels as $m => $lbl): ?>
                        <option value="<?= (int)$m ?>" <?= (int)$m === (int)$mois ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-bold mb-1">Statut</label>
                    <select name="statut" class="form-select form-select-sm">
                        <option value="">Tous</option>
                        <?php foreach ($statuts as $key => $label): ?>
                        <option value="<?= htmlspecialchars($key) ?>" <?= $statut === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-filter me-1"></i>Filtrer</button>
                </div>
            </div>
        </div>
    </form>

    <?php if (empty($quittances)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">Aucune quittance pour ces filtres. Cliquez sur « Générer les quittances d'un mois » pour démarrer.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="qrTable" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Numéro</th>
                            <th>Période</th>
                            <th>Résident</th>
                            <th>Résidence</th>
                            <th class="text-end">Loyer</th>
                            <th class="text-end">Charges</th>
                            <th class="text-end">Services</th>
                            <th class="text-end">APL/APA</th>
                            <th class="text-end">Net dû</th>
                            <th class="text-center">Statut</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quittances as $q):
                            $aides = (float)$q['montant_apl'] + (float)$q['montant_apa'];
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($q['numero_quittance']) ?></strong></td>
                            <td><?= htmlspecialchars($moisLabels[$q['periode_mois']] ?? '') ?> <?= (int)$q['periode_annee'] ?></td>
                            <td>
                                <?= htmlspecialchars(($q['resident_prenom'] ?? '') . ' ' . ($q['resident_nom'] ?? '')) ?>
                            </td>
                            <td><small><?= htmlspecialchars($q['residence_nom'] ?? '—') ?></small></td>
                            <td class="text-end"><?= number_format((float)$q['loyer_mensuel'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format((float)$q['charges_mensuelles'], 2, ',', ' ') ?> €</td>
                            <td class="text-end"><?= number_format((float)$q['services_supp'], 2, ',', ' ') ?> €</td>
                            <td class="text-end text-success"><?= $aides > 0 ? '-' . number_format($aides, 2, ',', ' ') . ' €' : '—' ?></td>
                            <td class="text-end"><strong><?= number_format((float)$q['montant_du_total'], 2, ',', ' ') ?> €</strong></td>
                            <td class="text-center">
                                <span class="badge bg-<?= $badgeStatut[$q['statut']] ?? 'secondary' ?>"><?= htmlspecialchars($statuts[$q['statut']] ?? $q['statut']) ?></span>
                            </td>
                            <td class="text-center">
                                <a href="<?= BASE_URL ?>/comptabilite/quittanceShow/<?= (int)$q['id'] ?>" class="btn btn-sm btn-outline-primary" title="Détail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/comptabilite/quittancePrintable/<?= (int)$q['id'] ?>" target="_blank" class="btn btn-sm btn-outline-secondary" title="PDF imprimable">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="qrPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="qrInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="qrPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Génération -->
<div class="modal fade" id="genererModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/comptabilite/quittanceGenerer" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-magic me-2"></i>Générer les quittances d'un mois</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    Génère 1 quittance par occupation active sur la période. Les quittances déjà émises sont ignorées (pas de doublon).
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Résidence</label>
                    <select name="residence_id" class="form-select">
                        <option value="0">Toutes accessibles</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $selectedResidence ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="row g-2">
                    <div class="col-6">
                        <label class="form-label small fw-bold">Année</label>
                        <select name="annee" class="form-select" required>
                            <?php for ($a = (int)date('Y'); $a >= 2020; $a--): ?>
                            <option value="<?= $a ?>" <?= $a === (int)$annee ? 'selected' : '' ?>><?= $a ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-bold">Mois</label>
                        <select name="mois" class="form-select" required>
                            <?php foreach ($moisLabels as $m => $lbl): ?>
                            <option value="<?= (int)$m ?>" <?= $mois ? ((int)$m === (int)$mois ? 'selected' : '') : ((int)$m === (int)date('n') ? 'selected' : '') ?>><?= htmlspecialchars($lbl) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" name="envoyer_notif" value="1" id="envoyerNotif" checked>
                    <label class="form-check-label small" for="envoyerNotif">
                        Notifier les résidents par messagerie interne (recommandé)
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-magic me-1"></i>Générer</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($quittances)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('qrTable', {
    rowsPerPage: 25,
    excludeColumns: [10],
    paginationId: 'qrPager',
    infoId: 'qrInfo'
});
</script>
<?php endif; ?>
