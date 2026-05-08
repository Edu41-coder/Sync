<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Quittance') ?></title>
    <style>
        @page { size: A4; margin: 1.5cm; }
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            position: relative;
            min-height: 100vh;
        }
        /* Watermark pilote */
        body::before {
            content: "PILOTE — DOCUMENT NON CONTRACTUEL";
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 4rem;
            font-weight: bold;
            color: rgba(220, 53, 69, 0.08);
            white-space: nowrap;
            z-index: -1;
            pointer-events: none;
        }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #0d6efd; padding-bottom: 15px; }
        .titre-doc { font-size: 18pt; font-weight: bold; color: #0d6efd; }
        .numero-doc { font-size: 14pt; color: #6c757d; }
        .residence-block { text-align: right; font-size: 10pt; }
        .destinataire-block { margin: 30px 0; padding: 15px; background: #f8f9fc; border-left: 4px solid #0d6efd; }
        .periode-block { margin: 20px 0; font-size: 12pt; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.detail th, table.detail td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        table.detail th { background: #f8f9fc; text-align: left; font-weight: bold; }
        table.detail td.right { text-align: right; }
        table.detail tr.total { background: #0d6efd; color: #fff; font-weight: bold; font-size: 12pt; }
        table.detail tr.aide { color: #198754; }
        .pied { margin-top: 50px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 9pt; color: #6c757d; }
        .signature { margin-top: 60px; text-align: right; }
        .actions-noprint { padding: 20px; background: #fff3cd; border-bottom: 2px solid #ffc107; text-align: center; }
        @media print { .actions-noprint { display: none; } }
    </style>
</head>
<body>
    <div class="actions-noprint">
        <button onclick="window.print()" style="padding: 10px 20px; background: #0d6efd; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px;">
            🖨️ Imprimer ou Enregistrer en PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            ✕ Fermer
        </button>
    </div>

    <div class="header">
        <div>
            <div class="titre-doc">QUITTANCE DE LOYER</div>
            <div class="numero-doc">N° <?= htmlspecialchars($q['numero_quittance']) ?></div>
        </div>
        <div class="residence-block">
            <strong><?= htmlspecialchars($q['residence_nom'] ?? '') ?></strong><br>
            <small>Résidence-services seniors</small>
        </div>
    </div>

    <div class="destinataire-block">
        <strong>Destinataire :</strong><br>
        <?= htmlspecialchars(($q['resident_prenom'] ?? '') . ' ' . ($q['resident_nom'] ?? '')) ?><br>
        Lot <?= htmlspecialchars($q['numero_lot'] ?? '') ?> (<?= htmlspecialchars($q['lot_type'] ?? '') ?>)<br>
        <?= htmlspecialchars($q['residence_nom'] ?? '') ?>
    </div>

    <div class="periode-block">
        Période : <?= htmlspecialchars($moisLabels[$q['periode_mois']] ?? '') ?> <?= (int)$q['periode_annee'] ?>
    </div>

    <table class="detail">
        <thead>
            <tr>
                <th>Désignation</th>
                <th class="right">Montant (€)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Loyer mensuel du logement</td>
                <td class="right"><?= number_format((float)$q['loyer_mensuel'], 2, ',', ' ') ?></td>
            </tr>
            <?php if ((float)$q['charges_mensuelles'] > 0): ?>
            <tr>
                <td>Charges mensuelles (provision)</td>
                <td class="right"><?= number_format((float)$q['charges_mensuelles'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$q['services_supp'] > 0): ?>
            <tr>
                <td>Services supplémentaires</td>
                <td class="right"><?= number_format((float)$q['services_supp'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$q['montant_apl'] > 0): ?>
            <tr class="aide">
                <td>− APL (Aide personnalisée au logement)</td>
                <td class="right">− <?= number_format((float)$q['montant_apl'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$q['montant_apa'] > 0): ?>
            <tr class="aide">
                <td>− APA (Allocation personnalisée d'autonomie)</td>
                <td class="right">− <?= number_format((float)$q['montant_apa'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total">
                <td>NET À PAYER</td>
                <td class="right"><?= number_format((float)$q['montant_du_total'], 2, ',', ' ') ?> €</td>
            </tr>
        </tbody>
    </table>

    <?php if ($q['statut'] === 'payee'): ?>
    <div style="margin: 30px 0; padding: 15px; background: #d1e7dd; border: 1px solid #198754; border-radius: 4px;">
        <strong style="color: #198754;">✓ PAYÉE</strong> le <?= htmlspecialchars(date('d/m/Y', strtotime($q['date_paiement'] ?? $q['paid_at']))) ?>
        par <?= htmlspecialchars($q['mode_paiement'] ?? '—') ?>
        <?= !empty($q['reference_paiement']) ? ' — Réf : ' . htmlspecialchars($q['reference_paiement']) : '' ?>
    </div>
    <?php endif; ?>

    <div class="signature">
        Émise le <?= htmlspecialchars(date('d/m/Y', strtotime($q['emis_at']))) ?><br><br>
        <em>Le service comptable<br><?= htmlspecialchars($q['residence_nom'] ?? '') ?></em>
    </div>

    <div class="pied">
        <strong>Mentions légales :</strong>
        Cette quittance est délivrée pour servir et valoir ce que de droit. Conformément à l'article L. 134-3 du Code de la consommation,
        le bailleur ne peut refuser la délivrance d'une quittance dès lors que le locataire en fait la demande. La quittance porte le détail
        des sommes versées par le locataire en distinguant le loyer des charges.
        <br><br>
        <strong>⚠️ DOCUMENT PILOTE — NON CONTRACTUEL.</strong>
        Cette quittance est générée par le système Synd_Gest en mode pilote. Avant toute utilisation officielle (transmission CAF, déclaration d'impôt),
        veuillez la faire valider par le service comptable.
    </div>
</body>
</html>
