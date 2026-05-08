<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',     'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',        'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-receipt',        'text' => 'Quittances',          'url' => BASE_URL . '/comptabilite/quittancesResidents'],
    ['icon' => 'fas fa-eye',            'text' => $q['numero_quittance'],'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatut = [
    'emise'                 => 'primary',
    'partiellement_payee'   => 'warning',
    'payee'                 => 'success',
    'impayee'               => 'danger',
    'annulee'               => 'secondary',
];
$canMarquer = in_array($q['statut'], ['emise','partiellement_payee','impayee'], true);
$canAnnuler = in_array($q['statut'], ['emise','impayee'], true);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-receipt me-2 text-primary"></i>
                Quittance <?= htmlspecialchars($q['numero_quittance']) ?>
                <span class="badge bg-<?= $badgeStatut[$q['statut']] ?? 'secondary' ?> ms-2"><?= htmlspecialchars($statuts[$q['statut']] ?? $q['statut']) ?></span>
            </h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars(($q['resident_prenom'] ?? '') . ' ' . ($q['resident_nom'] ?? '')) ?>
                — Lot <?= htmlspecialchars($q['numero_lot'] ?? '') ?>
                — <?= htmlspecialchars($q['residence_nom'] ?? '') ?>
                — <?= htmlspecialchars($moisLabels[$q['periode_mois']] ?? '') ?> <?= (int)$q['periode_annee'] ?>
            </p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/comptabilite/quittancePrintable/<?= (int)$q['id'] ?>" target="_blank" class="btn btn-outline-primary">
                <i class="fas fa-print me-1"></i>Imprimer / PDF
            </a>
            <a href="<?= BASE_URL ?>/comptabilite/quittancesResidents" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-1"></i>Retour
            </a>
        </div>
    </div>

    <div class="row g-3">
        <!-- Détail montants -->
        <div class="col-md-7">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-coins me-2"></i>Détail des montants
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <tr>
                            <td>Loyer mensuel</td>
                            <td class="text-end"><?= number_format((float)$q['loyer_mensuel'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr>
                            <td>Charges mensuelles</td>
                            <td class="text-end"><?= number_format((float)$q['charges_mensuelles'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <tr>
                            <td>Services supplémentaires</td>
                            <td class="text-end"><?= number_format((float)$q['services_supp'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php if ((float)$q['montant_apl'] > 0): ?>
                        <tr class="table-success">
                            <td><i class="fas fa-minus me-1"></i> APL (Aide perso logement)</td>
                            <td class="text-end">- <?= number_format((float)$q['montant_apl'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endif; ?>
                        <?php if ((float)$q['montant_apa'] > 0): ?>
                        <tr class="table-success">
                            <td><i class="fas fa-minus me-1"></i> APA (Allocation perso autonomie)</td>
                            <td class="text-end">- <?= number_format((float)$q['montant_apa'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endif; ?>
                        <tr class="table-primary fw-bold">
                            <td>NET À PAYER</td>
                            <td class="text-end"><?= number_format((float)$q['montant_du_total'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php if ((float)($q['montant_paye'] ?? 0) > 0): ?>
                        <tr class="table-success">
                            <td>Montant payé <small class="text-muted">(<?= htmlspecialchars($q['mode_paiement'] ?? '—') ?> · <?= htmlspecialchars($q['date_paiement'] ?? '') ?>)</small></td>
                            <td class="text-end"><?= number_format((float)$q['montant_paye'], 2, ',', ' ') ?> €</td>
                        </tr>
                        <?php endif; ?>
                    </table>
                </div>
            </div>

            <!-- Historique relances -->
            <?php if (!empty($relances)): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-warning text-dark">
                    <i class="fas fa-bell me-2"></i>Historique des relances (<?= count($relances) ?>)
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Niveau</th>
                                <th>Sujet</th>
                                <th>Statut</th>
                                <th>Par</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($relances as $r): ?>
                            <tr>
                                <td><small><?= htmlspecialchars(date('d/m/Y H:i', strtotime($r['date_envoi']))) ?></small></td>
                                <td><span class="badge bg-warning text-dark">N<?= (int)$r['niveau'] ?> — <?= htmlspecialchars($niveaux[$r['niveau']] ?? '') ?></span></td>
                                <td><small><?= htmlspecialchars(mb_substr($r['sujet'] ?? '', 0, 60)) ?></small></td>
                                <td><span class="badge bg-secondary"><?= htmlspecialchars($r['statut']) ?></span></td>
                                <td><small><?= htmlspecialchars($r['sent_by_username'] ?? '—') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Actions + métadonnées -->
        <div class="col-md-5">
            <div class="card shadow-sm">
                <div class="card-header"><i class="fas fa-cogs me-2"></i>Actions</div>
                <div class="card-body d-grid gap-2">
                    <?php if ($canMarquer): ?>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payeeModal">
                        <i class="fas fa-check me-1"></i>Marquer comme payée
                    </button>
                    <?php endif; ?>

                    <?php if ($canAnnuler): ?>
                    <form method="POST" action="<?= BASE_URL ?>/comptabilite/quittanceAnnuler/<?= (int)$q['id'] ?>" onsubmit="return confirm('Annuler cette quittance ?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <button type="submit" class="btn btn-outline-danger w-100">
                            <i class="fas fa-ban me-1"></i>Annuler la quittance
                        </button>
                    </form>
                    <?php endif; ?>

                    <a href="<?= BASE_URL ?>/comptabilite/quittancePrintable/<?= (int)$q['id'] ?>" target="_blank" class="btn btn-outline-primary">
                        <i class="fas fa-file-pdf me-1"></i>Voir / Imprimer le PDF
                    </a>
                </div>
            </div>

            <div class="card shadow-sm mt-3">
                <div class="card-header"><i class="fas fa-info-circle me-2"></i>Métadonnées</div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Émise le :</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($q['emis_at']))) ?></p>
                    <?php if ($q['paid_at']): ?>
                    <p class="mb-1"><strong>Payée le :</strong> <?= htmlspecialchars(date('d/m/Y H:i', strtotime($q['paid_at']))) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($q['reference_paiement'])): ?>
                    <p class="mb-1"><strong>Référence paiement :</strong> <?= htmlspecialchars($q['reference_paiement']) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($q['notes'])): ?>
                    <p class="mt-2 mb-0"><strong>Notes :</strong></p>
                    <pre class="small mb-0 mt-1 bg-light p-2 rounded"><?= htmlspecialchars($q['notes']) ?></pre>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Marquer payée -->
<?php if ($canMarquer): ?>
<div class="modal fade" id="payeeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/comptabilite/quittanceMarquerPayee/<?= (int)$q['id'] ?>" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-check me-2"></i>Marquer comme payée</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Montant payé (€)</label>
                    <input type="number" name="montant_paye" class="form-control" step="0.01" value="<?= number_format((float)$q['montant_du_total'], 2, '.', '') ?>" required>
                    <small class="text-muted">Net dû : <?= number_format((float)$q['montant_du_total'], 2, ',', ' ') ?> € (modifier si paiement partiel)</small>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Mode de paiement</label>
                    <select name="mode_paiement" class="form-select" required>
                        <option value="prelevement">Prélèvement</option>
                        <option value="virement">Virement</option>
                        <option value="cheque">Chèque</option>
                        <option value="mandat_sepa">Mandat SEPA</option>
                        <option value="especes">Espèces</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Référence paiement (optionnel)</label>
                    <input type="text" name="reference_paiement" class="form-control" placeholder="ex: VIR-2026-1234">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Date paiement</label>
                    <input type="date" name="date_paiement" class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Confirmer paiement</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
