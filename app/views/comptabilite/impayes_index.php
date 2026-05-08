<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',        'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-exclamation-triangle', 'text' => 'Gestion des impayés','url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeSource = [
    'quittance_resident'  => 'primary',
    'paiement_proprio'    => 'success',
    'facture_fournisseur' => 'warning',
];
$labelSource = [
    'quittance_resident'  => 'Résident',
    'paiement_proprio'    => 'Propriétaire',
    'facture_fournisseur' => 'Fournisseur',
];

function urgenceColor(int $jours): string {
    if ($jours <= 15)  return 'success';
    if ($jours <= 45)  return 'warning';
    if ($jours <= 60)  return 'danger';
    return 'dark';
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="mb-0"><i class="fas fa-exclamation-triangle me-2 text-danger"></i>Gestion des impayés</h2>
        <a href="<?= BASE_URL ?>/comptabilite/impayes" class="btn btn-outline-secondary">
            <i class="fas fa-sync me-1"></i>Recharger (auto-escalade)
        </a>
    </div>

    <?php if ($nbEscalades > 0): ?>
    <div class="alert alert-warning small">
        <i class="fas fa-info-circle me-1"></i>
        <?= $nbEscalades ?> quittance(s) basculée(s) automatiquement en statut "impayée" suite à l'escalade J+10.
    </div>
    <?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-danger text-center py-3">
                <div class="text-muted small">Total impayés</div>
                <div class="h3 mb-0 text-danger"><?= (int)$stats['total_count'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-dark text-center py-3">
                <div class="text-muted small">Montant total dû</div>
                <div class="h4 mb-0"><?= number_format((float)$stats['total_montant'], 2, ',', ' ') ?> €</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning text-center py-3">
                <div class="text-muted small">Plus de 60 jours</div>
                <div class="h3 mb-0 text-warning"><?= (int)$stats['par_anciennete']['plus_60j'] ?></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info text-center py-3">
                <div class="text-muted small">Récents (≤ 15j)</div>
                <div class="h3 mb-0 text-info"><?= (int)$stats['par_anciennete']['0_15j'] ?></div>
            </div>
        </div>
    </div>

    <!-- Filtres source -->
    <div class="mb-3">
        <div class="btn-group btn-group-sm">
            <a class="btn btn-<?= !$sourceFilter ? 'primary' : 'outline-primary' ?>" href="<?= BASE_URL ?>/comptabilite/impayes">
                Tous (<?= $stats['total_count'] ?>)
            </a>
            <?php foreach ($sources as $key => $label): ?>
            <a class="btn btn-<?= $sourceFilter === $key ? $badgeSource[$key] : 'outline-' . $badgeSource[$key] ?>" href="?source=<?= htmlspecialchars($key) ?>">
                <?= htmlspecialchars($label) ?> (<?= (int)($stats['par_source'][$key] ?? 0) ?>)
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if (empty($impayes)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
            <h5 class="text-success">Aucun impayé</h5>
            <p class="text-muted mb-0">Tout est à jour.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table id="impayesTable" class="table table-hover table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Source</th>
                            <th>Référence</th>
                            <th>Tiers</th>
                            <th>Résidence</th>
                            <th class="text-end">Montant</th>
                            <th class="text-center">Retard</th>
                            <th class="text-center">Dernière relance</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($impayes as $i):
                            $urgence = urgenceColor((int)$i['jours_retard']);
                            $prochain = ((int)$i['dernier_niveau_relance']) + 1;
                            $prochain = min(3, $prochain);
                        ?>
                        <tr>
                            <td><span class="badge bg-<?= $badgeSource[$i['source_type']] ?? 'secondary' ?>"><?= htmlspecialchars($labelSource[$i['source_type']] ?? '') ?></span></td>
                            <td><strong><?= htmlspecialchars($i['reference']) ?></strong></td>
                            <td><?= htmlspecialchars($i['tiers_label'] ?? '—') ?></td>
                            <td><small><?= htmlspecialchars($i['residence_nom'] ?? '—') ?></small></td>
                            <td class="text-end"><strong><?= number_format((float)$i['montant_du'], 2, ',', ' ') ?> €</strong></td>
                            <td class="text-center" data-sort="<?= (int)$i['jours_retard'] ?>">
                                <span class="badge bg-<?= $urgence ?>"><?= (int)$i['jours_retard'] ?> j</span>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$i['dernier_niveau_relance'] > 0): ?>
                                <span class="badge bg-warning text-dark">N<?= (int)$i['dernier_niveau_relance'] ?></span>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal" data-bs-target="#relanceModal"
                                        data-source="<?= htmlspecialchars($i['source_type']) ?>"
                                        data-id="<?= (int)$i['source_id'] ?>"
                                        data-niveau="<?= $prochain ?>"
                                        data-reference="<?= htmlspecialchars($i['reference']) ?>"
                                        data-tiers="<?= htmlspecialchars($i['tiers_label'] ?? '') ?>"
                                        data-montant="<?= number_format((float)$i['montant_du'], 2, ',', ' ') ?>"
                                        title="Relancer (niveau <?= $prochain ?>)">
                                    <i class="fas fa-bell"></i>
                                </button>
                                <form method="POST" action="<?= BASE_URL ?>/comptabilite/impayeMarquerPaye/<?= htmlspecialchars($i['source_type']) ?>/<?= (int)$i['source_id'] ?>" class="d-inline" onsubmit="return confirm('Marquer cet impayé comme payé ? Toutes les relances actives seront clôturées.');">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success" title="Marquer payé">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="impayesPagination" class="d-flex justify-content-between align-items-center p-3 border-top">
                <span id="impayesInfo" class="text-muted small"></span>
                <ul class="pagination pagination-sm mb-0" id="impayesPager"></ul>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Relance -->
<div class="modal fade" id="relanceModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= BASE_URL ?>/comptabilite/impayeRelancer" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="source_type" id="relSourceType">
            <input type="hidden" name="source_id" id="relSourceId">
            <input type="hidden" name="niveau" id="relNiveau">

            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-bell me-2"></i>Envoyer une relance</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info small mb-3">
                    <strong>Niveau <span id="relNiveauLabel">1</span></strong> — <span id="relNiveauNom"></span>
                    <br>Cible : <strong id="relTiers"></strong>
                    — Référence <strong id="relRef"></strong>
                    — Montant <strong id="relMontant"></strong> €
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Sujet</label>
                    <input type="text" name="sujet" class="form-control" placeholder="(template auto si vide)">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Message</label>
                    <textarea name="corps" class="form-control" rows="10" placeholder="(template auto si vide — modifiable)"></textarea>
                    <small class="text-muted">Variables disponibles : {prenom} {nom} {montant} {periode} {numero} {residence} {date_n1} {contact}</small>
                </div>
                <div class="alert alert-secondary small mb-0">
                    <i class="fas fa-envelope me-1"></i>
                    L'envoi se fait via la <strong>messagerie interne</strong>. Le destinataire reçoit le message dans son espace.
                    <br>Priorité : <span id="relPriorite">normale</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-warning"><i class="fas fa-paper-plane me-1"></i>Envoyer la relance</button>
            </div>
        </form>
    </div>
</div>

<?php if (!empty($impayes)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('impayesTable', {
    rowsPerPage: 30,
    excludeColumns: [7],
    paginationId: 'impayesPager',
    infoId: 'impayesInfo'
});

// Modal relance — pré-remplissage depuis data-attributes
const niveauxLabels = <?= json_encode($niveaux) ?>;
document.getElementById('relanceModal').addEventListener('show.bs.modal', (event) => {
    const btn = event.relatedTarget;
    document.getElementById('relSourceType').value = btn.getAttribute('data-source');
    document.getElementById('relSourceId').value   = btn.getAttribute('data-id');
    document.getElementById('relNiveau').value     = btn.getAttribute('data-niveau');
    document.getElementById('relNiveauLabel').textContent = btn.getAttribute('data-niveau');
    document.getElementById('relNiveauNom').textContent   = niveauxLabels[btn.getAttribute('data-niveau')] || '';
    document.getElementById('relTiers').textContent       = btn.getAttribute('data-tiers');
    document.getElementById('relRef').textContent         = btn.getAttribute('data-reference');
    document.getElementById('relMontant').textContent     = btn.getAttribute('data-montant');
    document.getElementById('relPriorite').textContent    = parseInt(btn.getAttribute('data-niveau')) >= 2 ? 'haute' : 'normale';
});
</script>
<?php endif; ?>
