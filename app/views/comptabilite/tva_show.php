<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-percent',        'text' => 'TVA',             'url' => BASE_URL . '/comptabilite/tva'],
    ['icon' => 'fas fa-eye',            'text' => 'Déclaration #' . (int)$decl['id'], 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatuts = [
    'brouillon' => 'secondary',
    'declaree'  => 'success',
    'annulee'   => 'danger',
];
$canDeclarer = $decl['statut'] === 'brouillon';
$canAnnuler  = $decl['statut'] !== 'annulee';
$canDelete   = $decl['statut'] === 'brouillon';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-file-invoice me-2 text-primary"></i>
                Déclaration TVA #<?= (int)$decl['id'] ?>
                <span class="badge bg-<?= $badgeStatuts[$decl['statut']] ?? 'secondary' ?> ms-2"><?= htmlspecialchars($statuts[$decl['statut']] ?? $decl['statut']) ?></span>
            </h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($decl['residence_nom'] ?? '—') ?> —
                <?= htmlspecialchars($regimes[$decl['regime']] ?? $decl['regime']) ?> —
                <?= htmlspecialchars($libellePeriode) ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>/comptabilite/tva" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>

    <div class="alert alert-warning small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>Pilote — non contractuel.</strong>
        Ce brouillon est calculé à partir des écritures comptables. Avant transmission au SIE,
        valider chaque ligne avec le cabinet comptable.
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <div class="text-muted small">TVA collectée</div>
                    <div class="h4 mb-0 text-warning"><?= number_format((float)$decl['tva_collectee_total'], 2, ',', ' ') ?> €</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <div class="text-muted small">TVA déductible</div>
                    <div class="h4 mb-0 text-info"><?= number_format((float)$decl['tva_deductible_total'], 2, ',', ' ') ?> €</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <div class="text-muted small">Crédit antérieur</div>
                    <div class="h4 mb-0 text-muted"><?= number_format((float)$decl['credit_tva_anterieur'], 2, ',', ' ') ?> €</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-<?= (float)$decl['tva_a_payer'] > 0 ? 'danger' : 'success' ?>">
                <div class="card-body text-center">
                    <div class="text-muted small"><?= (float)$decl['tva_a_payer'] > 0 ? 'TVA à payer' : 'Crédit reporté' ?></div>
                    <div class="h4 mb-0 text-<?= (float)$decl['tva_a_payer'] > 0 ? 'danger' : 'success' ?>">
                        <?= number_format((float)$decl['tva_a_payer'] > 0 ? $decl['tva_a_payer'] : $decl['credit_a_reporter'], 2, ',', ' ') ?> €
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapping CERFA CA3 -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-clipboard-list me-2"></i>Mapping CERFA n°3310-CA3 (lignes)
        </div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ligne</th>
                        <th>Désignation</th>
                        <th class="text-end">Montant</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>01</strong></td>
                        <td>Ventes / prestations imposables au taux normal (20 %)</td>
                        <td class="text-end"><?= number_format((float)$decl['ca_ht_20'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><strong>02</strong></td>
                        <td>Ventes / prestations imposables au taux intermédiaire (10 %)</td>
                        <td class="text-end"><?= number_format((float)$decl['ca_ht_10'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><strong>03</strong></td>
                        <td>Ventes / prestations imposables au taux réduit (5,5 %)</td>
                        <td class="text-end"><?= number_format((float)$decl['ca_ht_55'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><strong>04</strong></td>
                        <td>Ventes / prestations imposables au taux super-réduit (2,1 %)</td>
                        <td class="text-end"><?= number_format((float)$decl['ca_ht_21'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr>
                        <td><strong>05</strong></td>
                        <td>Opérations non imposables / exonérées</td>
                        <td class="text-end"><?= number_format((float)$decl['ca_ht_exonere'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-warning">
                        <td><strong>08</strong></td>
                        <td>TVA brute due au taux 20 %</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_collectee_20'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-warning">
                        <td><strong>09</strong></td>
                        <td>TVA brute due au taux 10 %</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_collectee_10'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-warning">
                        <td><strong>9B</strong></td>
                        <td>TVA brute due au taux 5,5 %</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_collectee_55'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-warning fw-bold">
                        <td><strong>16</strong></td>
                        <td>TOTAL TVA brute due (collectée)</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_collectee_total'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-info">
                        <td><strong>19</strong></td>
                        <td>TVA déductible — biens et services (hors immobilisations)</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_deductible_biens_services'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-info">
                        <td><strong>20</strong></td>
                        <td>TVA déductible — immobilisations</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_deductible_immobilisations'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-info fw-bold">
                        <td><strong>22</strong></td>
                        <td>TOTAL TVA déductible</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_deductible_total'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="table-secondary">
                        <td><strong>25</strong></td>
                        <td>Crédit de TVA reporté de la déclaration précédente</td>
                        <td class="text-end"><?= number_format((float)$decl['credit_tva_anterieur'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="<?= (float)$decl['tva_a_payer'] > 0 ? 'table-danger' : '' ?> fw-bold">
                        <td><strong>28</strong></td>
                        <td>TVA NETTE DUE (à payer)</td>
                        <td class="text-end"><?= number_format((float)$decl['tva_a_payer'], 2, ',', ' ') ?> €</td>
                    </tr>
                    <tr class="<?= (float)$decl['credit_a_reporter'] > 0 ? 'table-success' : '' ?> fw-bold">
                        <td><strong>32</strong></td>
                        <td>Crédit de TVA à reporter</td>
                        <td class="text-end"><?= number_format((float)$decl['credit_a_reporter'], 2, ',', ' ') ?> €</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Métadonnées + Actions -->
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Métadonnées</div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Créé par :</strong> <?= htmlspecialchars($decl['created_by_username'] ?? '—') ?></p>
                    <p class="mb-1"><strong>Créé le :</strong> <?= htmlspecialchars(date('d/m/Y à H:i', strtotime($decl['created_at']))) ?></p>
                    <?php if ($decl['declared_at']): ?>
                    <p class="mb-1"><strong>Déclarée par :</strong> <?= htmlspecialchars($decl['declared_by_username'] ?? '—') ?></p>
                    <p class="mb-1"><strong>Déclarée le :</strong> <?= htmlspecialchars(date('d/m/Y à H:i', strtotime($decl['declared_at']))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($decl['notes'])): ?>
                    <p class="mt-2 mb-0"><strong>Notes :</strong></p>
                    <pre class="small mb-0 mt-1 bg-light p-2 rounded"><?= htmlspecialchars($decl['notes']) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-cogs me-2"></i>Actions</div>
                <div class="card-body d-grid gap-2">
                    <?php if ($canDeclarer): ?>
                    <form method="POST" action="<?= BASE_URL ?>/comptabilite/tvaMarquerDeclaree/<?= (int)$decl['id'] ?>" onsubmit="return confirm('Marquer cette déclaration comme transmise au SIE ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn btn-success w-100">
                            <i class="fas fa-check me-1"></i>Marquer comme transmise au SIE
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($canAnnuler): ?>
                    <form method="POST" action="<?= BASE_URL ?>/comptabilite/tvaAnnuler/<?= (int)$decl['id'] ?>" onsubmit="return confirm('Annuler cette déclaration ? Cette action est définitive.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="fas fa-ban me-1"></i>Annuler la déclaration
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if ($canDelete): ?>
                    <form method="POST" action="<?= BASE_URL ?>/comptabilite/tvaDelete/<?= (int)$decl['id'] ?>" onsubmit="return confirm('Supprimer définitivement ce brouillon ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-trash me-1"></i>Supprimer le brouillon
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if (!$canDeclarer && !$canAnnuler && !$canDelete): ?>
                    <p class="text-muted small mb-0">Aucune action disponible (déclaration finalisée).</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
