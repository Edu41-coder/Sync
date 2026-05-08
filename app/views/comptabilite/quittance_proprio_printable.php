<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Quittance loyer propriétaire') ?></title>
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
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; border-bottom: 2px solid #198754; padding-bottom: 15px; }
        .titre-doc { font-size: 18pt; font-weight: bold; color: #198754; }
        .numero-doc { font-size: 14pt; color: #6c757d; }
        .residence-block { text-align: right; font-size: 10pt; }
        .destinataire-block { margin: 30px 0; padding: 15px; background: #f8f9fc; border-left: 4px solid #198754; }
        .periode-block { margin: 20px 0; font-size: 12pt; font-weight: bold; }
        table.detail { width: 100%; border-collapse: collapse; margin: 20px 0; }
        table.detail th, table.detail td { padding: 10px; border-bottom: 1px solid #dee2e6; }
        table.detail th { background: #f8f9fc; text-align: left; font-weight: bold; }
        table.detail td.right { text-align: right; }
        table.detail tr.total { background: #198754; color: #fff; font-weight: bold; font-size: 12pt; }
        .pied { margin-top: 50px; padding-top: 20px; border-top: 1px solid #dee2e6; font-size: 9pt; color: #6c757d; }
        .signature { margin-top: 60px; text-align: right; }
        .actions-noprint { padding: 20px; background: #fff3cd; border-bottom: 2px solid #ffc107; text-align: center; }
        @media print { .actions-noprint { display: none; } }
    </style>
</head>
<body>
    <div class="actions-noprint">
        <button onclick="window.print()" style="padding: 10px 20px; background: #198754; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px;">
            🖨️ Imprimer ou Enregistrer en PDF
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #6c757d; color: #fff; border: 0; border-radius: 4px; cursor: pointer; font-size: 14px; margin-left: 10px;">
            ✕ Fermer
        </button>
    </div>

    <div class="header">
        <div>
            <div class="titre-doc">QUITTANCE DE LOYER GARANTI</div>
            <div class="numero-doc">Période <?= str_pad((string)$p['mois'], 2, '0', STR_PAD_LEFT) ?>/<?= (int)$p['annee'] ?></div>
        </div>
        <div class="residence-block">
            <strong><?= htmlspecialchars($p['exploitant_raison'] ?? 'Domitys') ?></strong><br>
            <small>Exploitant — Résidence-services seniors</small>
        </div>
    </div>

    <div class="destinataire-block">
        <strong>Quittance émise au profit de :</strong><br>
        <?= htmlspecialchars($p['civilite'] ?? '') ?> <?= htmlspecialchars(($p['prenom'] ?? '') . ' ' . ($p['nom'] ?? '')) ?><br>
        <?= htmlspecialchars($p['adresse_principale'] ?? '') ?><br>
        <?= htmlspecialchars($p['proprio_cp'] ?? '') ?> <?= htmlspecialchars($p['proprio_ville'] ?? '') ?>
        <br><br>
        <strong>Concernant le bien :</strong><br>
        <?= htmlspecialchars($p['residence_nom'] ?? '') ?><br>
        <?= htmlspecialchars($p['residence_adresse'] ?? '') ?>, <?= htmlspecialchars($p['code_postal'] ?? '') ?> <?= htmlspecialchars($p['ville'] ?? '') ?>
    </div>

    <div class="periode-block">
        Période : <?= str_pad((string)$p['mois'], 2, '0', STR_PAD_LEFT) ?>/<?= (int)$p['annee'] ?>
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
                <td>Loyer garanti mensuel</td>
                <td class="right"><?= number_format((float)$p['loyer_mensuel'], 2, ',', ' ') ?></td>
            </tr>
            <?php if ((float)$p['charges'] > 0): ?>
            <tr>
                <td>Charges récupérables</td>
                <td class="right"><?= number_format((float)$p['charges'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <?php if ((float)$p['regularisation'] != 0.0): ?>
            <tr>
                <td>Régularisation</td>
                <td class="right"><?= number_format((float)$p['regularisation'], 2, ',', ' ') ?></td>
            </tr>
            <?php endif; ?>
            <tr class="total">
                <td>MONTANT TOTAL VERSÉ</td>
                <td class="right"><?= number_format((float)$p['montant_total'], 2, ',', ' ') ?> €</td>
            </tr>
        </tbody>
    </table>

    <?php if ($p['statut'] === 'paye'): ?>
    <div style="margin: 30px 0; padding: 15px; background: #d1e7dd; border: 1px solid #198754; border-radius: 4px;">
        <strong style="color: #198754;">✓ VERSÉ</strong>
        le <?= htmlspecialchars(date('d/m/Y', strtotime($p['date_paiement_effectif'] ?? $p['date_paiement'] ?? 'now'))) ?>
        par <?= htmlspecialchars($p['mode_paiement'] ?? 'virement') ?>
        <?= !empty($p['reference_paiement']) ? ' — Réf : ' . htmlspecialchars($p['reference_paiement']) : '' ?>
    </div>
    <?php endif; ?>

    <div class="signature">
        Émise le <?= htmlspecialchars(date('d/m/Y')) ?><br><br>
        <em>L'exploitant<br><?= htmlspecialchars($p['exploitant_raison'] ?? 'Domitys') ?></em>
    </div>

    <div class="pied">
        <strong>Mentions légales :</strong>
        Quittance émise dans le cadre du contrat de bail commercial liant l'exploitant et le propriétaire.
        Le loyer garanti est versé indépendamment de l'occupation effective du bien (clause de garantie locative).
        <br><br>
        <strong>⚠️ DOCUMENT PILOTE — NON CONTRACTUEL.</strong>
        Document généré par le système Synd_Gest en mode pilote. Avant utilisation officielle (déclaration revenus fonciers,
        transmission expert-comptable), faire valider par le service comptable.
    </div>
</body>
</html>
