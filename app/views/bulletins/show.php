<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-file-invoice',   'text' => 'Bulletins',       'url' => BASE_URL . '/bulletinPaie/index'],
    ['icon' => 'fas fa-eye',            'text' => 'Détail',          'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['brouillon' => 'secondary', 'valide' => 'info', 'emis' => 'success', 'annule' => 'danger'];
$col = $statutColors[$bulletin['statut']] ?? 'secondary';
$periode = $moisLabels[$bulletin['periode_mois']] . ' ' . $bulletin['periode_annee'];
$nom = trim($bulletin['snapshot_prenom'] . ' ' . $bulletin['snapshot_nom']);

$row = function($label, $value, $isCurrency = true, $isStrong = false) {
    $v = $isCurrency ? number_format((float)$value, 2, ',', ' ') . ' €' : htmlspecialchars((string)$value);
    $cls = $isStrong ? 'fw-bold' : '';
    echo "<dt class=\"col-7 small $cls\">" . htmlspecialchars($label) . "</dt><dd class=\"col-5 text-end small $cls\">" . $v . "</dd>";
};
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">
                <i class="fas fa-file-invoice me-2 text-primary"></i>Bulletin <?= $periode ?>
                <span class="badge bg-<?= $col ?> ms-2"><?= $statuts[$bulletin['statut']] ?? $bulletin['statut'] ?></span>
            </h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($nom) ?> <?= $bulletin['snapshot_numero_ss'] ? '— SS ' . htmlspecialchars($bulletin['snapshot_numero_ss']) : '' ?></p>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/bulletinPaie/index" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <a href="<?= BASE_URL ?>/bulletinPaie/print/<?= (int)$bulletin['id'] ?>" target="_blank" class="btn btn-outline-secondary"><i class="fas fa-print me-1"></i>Imprimable</a>

            <?php if ($bulletin['statut'] === 'brouillon'): ?>
            <form method="POST" action="<?= BASE_URL ?>/bulletinPaie/valider/<?= (int)$bulletin['id'] ?>" class="d-inline" onsubmit="return confirm('Valider ce bulletin ?')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-info"><i class="fas fa-check me-1"></i>Valider</button>
            </form>
            <form method="POST" action="<?= BASE_URL ?>/bulletinPaie/delete/<?= (int)$bulletin['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce brouillon ? Action irréversible.')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
            </form>
            <?php elseif ($bulletin['statut'] === 'valide'): ?>
            <form method="POST" action="<?= BASE_URL ?>/bulletinPaie/emettre/<?= (int)$bulletin['id'] ?>" class="d-inline" onsubmit="return confirm('Émettre ce bulletin ? Le salarié pourra le consulter.')">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <button type="submit" class="btn btn-success"><i class="fas fa-paper-plane me-1"></i>Émettre</button>
            </form>
            <?php endif; ?>

            <?php if ($bulletin['statut'] !== 'annule'): ?>
            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalAnnuler"><i class="fas fa-ban me-1"></i>Annuler</button>
            <?php endif; ?>
        </div>
    </div>

    <div class="alert alert-warning small">
        <i class="fas fa-exclamation-triangle me-1"></i>
        <strong>PILOTE — Document non contractuel.</strong> Calculs réalistes mais à valider par expert-comptable avant remise au salarié.
    </div>

    <div class="row g-4">
        <!-- Heures -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-clock me-2"></i>Heures travaillées</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php $row('Heures normales', $bulletin['heures_normales'] . ' h', false); ?>
                        <?php $row('Heures sup 25%', $bulletin['heures_sup_25'] . ' h', false); ?>
                        <?php $row('Heures sup 50%', $bulletin['heures_sup_50'] . ' h', false); ?>
                        <?php if ((float)$bulletin['heures_repos_compensateur'] > 0): ?>
                        <?php $row('Repos compensateur', $bulletin['heures_repos_compensateur'] . ' h', false); ?>
                        <?php endif; ?>
                        <?php $row('Mode heures sup', $bulletin['mode_heures_sup'] === 'paiement' ? 'Paiement' : 'Repos compensateur', false); ?>
                        <?php $row('Taux horaire', $bulletin['taux_horaire_normal'] . ' €/h', false); ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Brut -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-arrow-up me-2"></i>Salaire brut</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php $row('Salaire base', $bulletin['brut_salaire_base']); ?>
                        <?php if ((float)$bulletin['brut_heures_sup_25'] > 0): ?>
                        <?php $row('Heures sup 25%', $bulletin['brut_heures_sup_25']); ?>
                        <?php endif; ?>
                        <?php if ((float)$bulletin['brut_heures_sup_50'] > 0): ?>
                        <?php $row('Heures sup 50%', $bulletin['brut_heures_sup_50']); ?>
                        <?php endif; ?>
                        <?php if ((float)$bulletin['brut_primes'] > 0): ?>
                        <?php $row('Primes', $bulletin['brut_primes']); ?>
                        <?php endif; ?>
                        <?php if ((float)$bulletin['brut_indemnites'] > 0): ?>
                        <?php $row('Indemnités', $bulletin['brut_indemnites']); ?>
                        <?php endif; ?>
                        <hr>
                        <?php $row('TOTAL BRUT', $bulletin['total_brut'], true, true); ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Cotisations salariales -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-warning text-dark"><h6 class="mb-0"><i class="fas fa-minus me-2"></i>Cotisations salariales (à déduire)</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php $row('URSSAF maladie', $bulletin['cot_sal_urssaf_maladie']); ?>
                        <?php $row('URSSAF vieillesse déplafonnée', $bulletin['cot_sal_urssaf_vieillesse_dep']); ?>
                        <?php $row('URSSAF vieillesse plafonnée', $bulletin['cot_sal_urssaf_vieillesse_plaf']); ?>
                        <?php $row('CSG déductible', $bulletin['cot_sal_csg_deductible']); ?>
                        <?php $row('CSG non déductible', $bulletin['cot_sal_csg_non_deductible']); ?>
                        <?php $row('CRDS', $bulletin['cot_sal_crds']); ?>
                        <?php $row('AGIRC-ARRCO T1', $bulletin['cot_sal_agirc_arrco_t1']); ?>
                        <?php if ((float)$bulletin['cot_sal_agirc_arrco_t2'] > 0): ?>
                        <?php $row('AGIRC-ARRCO T2', $bulletin['cot_sal_agirc_arrco_t2']); ?>
                        <?php endif; ?>
                        <?php $row('Mutuelle', $bulletin['cot_sal_mutuelle']); ?>
                        <?php $row('Prévoyance', $bulletin['cot_sal_prevoyance']); ?>
                        <hr>
                        <?php $row('TOTAL COTISATIONS SAL.', $bulletin['total_cotisations_salariales'], true, true); ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Cotisations patronales -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-secondary text-white"><h6 class="mb-0"><i class="fas fa-building me-2"></i>Cotisations patronales (charge employeur)</h6></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <?php $row('URSSAF maladie pat.', $bulletin['cot_pat_urssaf_maladie']); ?>
                        <?php $row('URSSAF vieillesse', $bulletin['cot_pat_urssaf_vieillesse']); ?>
                        <?php $row('Allocations familiales', $bulletin['cot_pat_urssaf_alloc_familiales']); ?>
                        <?php $row('AT/MP (accidents)', $bulletin['cot_pat_urssaf_at_mp']); ?>
                        <?php $row('FNAL', $bulletin['cot_pat_urssaf_fnal']); ?>
                        <?php $row('AGIRC-ARRCO T1 pat.', $bulletin['cot_pat_agirc_arrco_t1']); ?>
                        <?php if ((float)$bulletin['cot_pat_agirc_arrco_t2'] > 0): ?>
                        <?php $row('AGIRC-ARRCO T2 pat.', $bulletin['cot_pat_agirc_arrco_t2']); ?>
                        <?php endif; ?>
                        <?php $row('Formation professionnelle', $bulletin['cot_pat_formation_pro']); ?>
                        <?php $row('Taxe apprentissage', $bulletin['cot_pat_taxe_apprentissage']); ?>
                        <?php $row('Mutuelle pat.', $bulletin['cot_pat_mutuelle']); ?>
                        <?php $row('Prévoyance pat.', $bulletin['cot_pat_prevoyance']); ?>
                        <hr>
                        <?php $row('TOTAL COTISATIONS PAT.', $bulletin['total_cotisations_patronales'], true, true); ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Net + Coût -->
        <div class="col-12">
            <div class="card shadow border-success">
                <div class="card-header bg-success text-white"><h5 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Net &amp; coût employeur</h5></div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 border-end">
                            <small class="text-muted text-uppercase">Brut</small>
                            <h3 class="mb-0"><?= number_format((float)$bulletin['total_brut'], 2, ',', ' ') ?> €</h3>
                        </div>
                        <div class="col-md-3 border-end">
                            <small class="text-muted text-uppercase">Net imposable</small>
                            <h3 class="mb-0 text-info"><?= number_format((float)$bulletin['net_imposable'], 2, ',', ' ') ?> €</h3>
                            <small class="text-muted">PAS <?= number_format((float)$bulletin['taux_pas'], 2, ',', '') ?>% = <?= number_format((float)$bulletin['prelevement_source'], 2, ',', ' ') ?> €</small>
                        </div>
                        <div class="col-md-3 border-end">
                            <small class="text-muted text-uppercase">Net à payer</small>
                            <h2 class="mb-0 text-success fw-bold"><?= number_format((float)$bulletin['net_a_payer'], 2, ',', ' ') ?> €</h2>
                        </div>
                        <div class="col-md-3">
                            <small class="text-muted text-uppercase">Coût employeur</small>
                            <h3 class="mb-0 text-warning"><?= number_format((float)$bulletin['cout_employeur_total'], 2, ',', ' ') ?> €</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($bulletin['annule_motif'])): ?>
        <div class="col-12">
            <div class="alert alert-danger">
                <strong>Annulé</strong> le <?= date('d/m/Y H:i', strtotime($bulletin['annule_at'])) ?> — motif : <?= htmlspecialchars($bulletin['annule_motif']) ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Annulation -->
<?php if ($bulletin['statut'] !== 'annule'): ?>
<div class="modal fade" id="modalAnnuler" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/bulletinPaie/annuler/<?= (int)$bulletin['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="modal-header bg-warning"><h5 class="modal-title"><i class="fas fa-ban me-2"></i>Annuler le bulletin</h5></div>
                <div class="modal-body">
                    <p>L'annulation est irréversible. Renseignez le motif :</p>
                    <textarea name="motif" class="form-control" rows="3" required maxlength="255"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-ban me-1"></i>Confirmer l'annulation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
