<?php
$periode = $moisLabels[$bulletin['periode_mois']] . ' ' . $bulletin['periode_annee'];
$nom = trim($bulletin['snapshot_prenom'] . ' ' . $bulletin['snapshot_nom']);
$fmt = fn($v) => number_format((float)$v, 2, ',', ' ') . ' €';
$dateEmission = !empty($bulletin['emis_at']) ? date('d/m/Y', strtotime($bulletin['emis_at']))
              : (!empty($bulletin['valide_at']) ? date('d/m/Y', strtotime($bulletin['valide_at']))
              : date('d/m/Y', strtotime($bulletin['created_at'])));
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f5f5f5; font-family: 'Segoe UI', Tahoma, sans-serif; font-size: 11px; }
        .bulletin {
            background: white;
            max-width: 800px;
            margin: 20px auto;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        /* Watermark PILOTE en diagonale */
        .bulletin::before {
            content: 'PILOTE — DOCUMENT NON CONTRACTUEL';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-30deg);
            font-size: 50px;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.12);
            white-space: nowrap;
            pointer-events: none;
            z-index: 1;
        }
        .bulletin > * { position: relative; z-index: 2; }
        .header-bul { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 15px; }
        h1 { font-size: 18px; }
        h2 { font-size: 14px; margin-top: 15px; }
        table { font-size: 10px; }
        table th { background: #e9ecef; }
        .ligne-total { font-weight: bold; background: #fff3cd; }
        .net-final { font-size: 14px; font-weight: bold; text-align: center; padding: 12px; background: #d4edda; border: 1px solid #28a745; margin-top: 15px; }
        .actions-noprint { max-width: 800px; margin: 10px auto; text-align: right; }
        .footer-print { font-size: 9px; color: #6c757d; margin-top: 20px; padding-top: 10px; border-top: 1px solid #ddd; }

        @media print {
            body { background: white; }
            .actions-noprint { display: none; }
            .bulletin { box-shadow: none; margin: 0; padding: 15px; max-width: 100%; }
            .bulletin::before { color: rgba(220, 53, 69, 0.18); }
        }
    </style>
</head>
<body>

<div class="actions-noprint">
    <button onclick="window.print()" class="btn btn-primary"><i class="fas fa-print me-1"></i>Imprimer ou Enregistrer en PDF</button>
    <button onclick="window.close()" class="btn btn-outline-secondary">Fermer</button>
</div>

<div class="bulletin">
    <!-- En-tête -->
    <div class="header-bul d-flex justify-content-between align-items-start">
        <div>
            <h1 class="mb-0">BULLETIN DE PAIE</h1>
            <p class="mb-0 text-muted"><strong>Période :</strong> <?= htmlspecialchars($periode) ?>
                (du <?= date('d/m/Y', strtotime($bulletin['date_debut'])) ?> au <?= date('d/m/Y', strtotime($bulletin['date_fin'])) ?>)</p>
            <p class="mb-0 text-muted small"><strong>Émis le :</strong> <?= $dateEmission ?></p>
        </div>
        <div class="text-end">
            <p class="mb-0 small"><strong>Statut :</strong> <span class="badge bg-secondary"><?= htmlspecialchars($bulletin['statut']) ?></span></p>
            <p class="mb-0 small"><strong>N° interne :</strong> #<?= (int)$bulletin['id'] ?></p>
        </div>
    </div>

    <!-- Identité salarié + employeur -->
    <div class="row mb-3">
        <div class="col-6">
            <h2>Salarié</h2>
            <p class="mb-1"><strong><?= htmlspecialchars($nom) ?></strong></p>
            <p class="mb-1 small">N° Sécurité Sociale : <?= htmlspecialchars($bulletin['snapshot_numero_ss'] ?? '—') ?></p>
            <p class="mb-1 small">Type contrat : <?= htmlspecialchars($bulletin['snapshot_type_contrat'] ?? '—') ?></p>
            <p class="mb-1 small">Catégorie : <?= htmlspecialchars($bulletin['snapshot_categorie'] ?? '—') ?>
               <?= !empty($bulletin['snapshot_coefficient']) ? ' / Coef. ' . htmlspecialchars($bulletin['snapshot_coefficient']) : '' ?></p>
            <?php if (!empty($bulletin['snapshot_iban'])): ?>
            <p class="mb-0 small">IBAN : <code><?= htmlspecialchars($bulletin['snapshot_iban']) ?></code></p>
            <?php endif; ?>
        </div>
        <div class="col-6">
            <h2>Convention collective</h2>
            <p class="mb-0 small"><?= htmlspecialchars($bulletin['snapshot_convention_nom'] ?? '—') ?></p>
            <?php if (!empty($bulletin['snapshot_convention_idcc'])): ?>
            <p class="mb-0 small">IDCC : <code><?= htmlspecialchars($bulletin['snapshot_convention_idcc']) ?></code></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Heures + Brut -->
    <h2>Salaire brut</h2>
    <table class="table table-sm table-bordered">
        <thead><tr><th>Désignation</th><th class="text-end">Base</th><th class="text-end">Taux</th><th class="text-end">Montant</th></tr></thead>
        <tbody>
            <tr>
                <td>Salaire de base</td>
                <td class="text-end"><?= number_format((float)$bulletin['heures_normales'], 2, ',', ' ') ?> h</td>
                <td class="text-end"><?= number_format((float)$bulletin['taux_horaire_normal'], 4, ',', ' ') ?> €/h</td>
                <td class="text-end"><?= $fmt($bulletin['brut_salaire_base']) ?></td>
            </tr>
            <?php if ((float)$bulletin['heures_sup_25'] > 0): ?>
            <tr>
                <td>Heures sup 25%</td>
                <td class="text-end"><?= number_format((float)$bulletin['heures_sup_25'], 2, ',', ' ') ?> h</td>
                <td class="text-end"><?= number_format((float)$bulletin['taux_horaire_normal'] * 1.25, 4, ',', ' ') ?> €/h</td>
                <td class="text-end"><?= $fmt($bulletin['brut_heures_sup_25']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$bulletin['heures_sup_50'] > 0): ?>
            <tr>
                <td>Heures sup 50%</td>
                <td class="text-end"><?= number_format((float)$bulletin['heures_sup_50'], 2, ',', ' ') ?> h</td>
                <td class="text-end"><?= number_format((float)$bulletin['taux_horaire_normal'] * 1.50, 4, ',', ' ') ?> €/h</td>
                <td class="text-end"><?= $fmt($bulletin['brut_heures_sup_50']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$bulletin['brut_primes'] > 0): ?>
            <tr><td>Primes</td><td colspan="2" class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['brut_primes']) ?></td></tr>
            <?php endif; ?>
            <?php if ((float)$bulletin['brut_indemnites'] > 0): ?>
            <tr><td>Indemnités</td><td colspan="2" class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['brut_indemnites']) ?></td></tr>
            <?php endif; ?>
            <tr class="ligne-total"><td colspan="3"><strong>TOTAL BRUT</strong></td><td class="text-end"><strong><?= $fmt($bulletin['total_brut']) ?></strong></td></tr>
        </tbody>
    </table>

    <!-- Cotisations -->
    <h2>Cotisations &amp; contributions</h2>
    <table class="table table-sm table-bordered">
        <thead><tr><th>Cotisation</th><th class="text-end">Part salariale</th><th class="text-end">Part patronale</th></tr></thead>
        <tbody>
            <tr><td>URSSAF — Maladie</td><td class="text-end"><?= $fmt($bulletin['cot_sal_urssaf_maladie']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_urssaf_maladie']) ?></td></tr>
            <tr><td>URSSAF — Vieillesse déplafonnée</td><td class="text-end"><?= $fmt($bulletin['cot_sal_urssaf_vieillesse_dep']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_urssaf_vieillesse']) ?></td></tr>
            <tr><td>URSSAF — Vieillesse plafonnée</td><td class="text-end"><?= $fmt($bulletin['cot_sal_urssaf_vieillesse_plaf']) ?></td><td class="text-end">—</td></tr>
            <tr><td>URSSAF — Allocations familiales</td><td class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['cot_pat_urssaf_alloc_familiales']) ?></td></tr>
            <tr><td>URSSAF — AT/MP</td><td class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['cot_pat_urssaf_at_mp']) ?></td></tr>
            <tr><td>URSSAF — FNAL</td><td class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['cot_pat_urssaf_fnal']) ?></td></tr>
            <tr><td>CSG déductible</td><td class="text-end"><?= $fmt($bulletin['cot_sal_csg_deductible']) ?></td><td class="text-end">—</td></tr>
            <tr><td>CSG non déductible + CRDS</td><td class="text-end"><?= $fmt($bulletin['cot_sal_csg_non_deductible'] + $bulletin['cot_sal_crds']) ?></td><td class="text-end">—</td></tr>
            <tr><td>AGIRC-ARRCO T1</td><td class="text-end"><?= $fmt($bulletin['cot_sal_agirc_arrco_t1']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_agirc_arrco_t1']) ?></td></tr>
            <?php if ((float)$bulletin['cot_pat_agirc_arrco_t2'] > 0 || (float)$bulletin['cot_sal_agirc_arrco_t2'] > 0): ?>
            <tr><td>AGIRC-ARRCO T2</td><td class="text-end"><?= $fmt($bulletin['cot_sal_agirc_arrco_t2']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_agirc_arrco_t2']) ?></td></tr>
            <?php endif; ?>
            <tr><td>Mutuelle</td><td class="text-end"><?= $fmt($bulletin['cot_sal_mutuelle']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_mutuelle']) ?></td></tr>
            <tr><td>Prévoyance</td><td class="text-end"><?= $fmt($bulletin['cot_sal_prevoyance']) ?></td><td class="text-end"><?= $fmt($bulletin['cot_pat_prevoyance']) ?></td></tr>
            <tr><td>Formation professionnelle</td><td class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['cot_pat_formation_pro']) ?></td></tr>
            <tr><td>Taxe d'apprentissage</td><td class="text-end">—</td><td class="text-end"><?= $fmt($bulletin['cot_pat_taxe_apprentissage']) ?></td></tr>
            <tr class="ligne-total"><td><strong>TOTAUX</strong></td><td class="text-end"><strong><?= $fmt($bulletin['total_cotisations_salariales']) ?></strong></td><td class="text-end"><strong><?= $fmt($bulletin['total_cotisations_patronales']) ?></strong></td></tr>
        </tbody>
    </table>

    <!-- Net + PAS + Net à payer -->
    <table class="table table-sm table-bordered mb-2">
        <tbody>
            <tr><td><strong>Net imposable</strong></td><td class="text-end"><?= $fmt($bulletin['net_imposable']) ?></td></tr>
            <tr>
                <td>Prélèvement à la source (taux <?= number_format((float)$bulletin['taux_pas'], 2, ',', '') ?>%)</td>
                <td class="text-end">−<?= $fmt($bulletin['prelevement_source']) ?></td>
            </tr>
        </tbody>
    </table>

    <div class="net-final">
        NET À PAYER : <?= $fmt($bulletin['net_a_payer']) ?>
    </div>

    <!-- Coût employeur -->
    <p class="text-end small text-muted mt-2 mb-0">Coût total employeur : <strong><?= $fmt($bulletin['cout_employeur_total']) ?></strong></p>

    <!-- Footer -->
    <div class="footer-print">
        Bulletin émis par <?= APP_NAME ?> — système d'aide à la gestion paie. Document <strong>non contractuel</strong> en l'état actuel.
        Pour toute question, contactez le service comptabilité de votre résidence.
    </div>
</div>

</body>
</html>
