<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-id-badge',       'text' => 'Mes informations RH', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$dispVal = function($v, $fmt = 'text') {
    if ($v === null || $v === '') return '<span class="text-muted">—</span>';
    if ($fmt === 'date') return date('d/m/Y', strtotime($v));
    if ($fmt === 'eur')  return number_format((float)$v, 2, ',', ' ') . ' €';
    if ($fmt === 'pct')  return number_format((float)$v, 2, ',', ' ') . ' %';
    return htmlspecialchars((string)$v);
};
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-id-badge me-2 text-primary"></i>Mes informations RH</h2>

    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Vos informations contractuelles sont gérées par le service RH / comptabilité. Vous pouvez uniquement modifier vos coordonnées bancaires ci-dessous. Pour toute correction, contactez la direction.
    </div>

    <?php if (!$fiche): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Aucune fiche RH créée pour vous. Contactez la direction.
    </div>

    <!-- Quand même proposer le RIB pour préparer la fiche -->
    <div class="card shadow-sm mb-4">
        <div class="card-header"><h6 class="mb-0"><i class="fas fa-university me-2"></i>Mes coordonnées bancaires</h6></div>
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/salarie/updateRib">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label class="form-label">IBAN</label>
                        <input type="text" name="iban" class="form-control text-uppercase" maxlength="34" placeholder="FR76 ...">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">BIC</label>
                        <input type="text" name="bic" class="form-control text-uppercase" maxlength="11">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer mes coordonnées</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php else: ?>

    <div class="row g-4">
        <!-- Identité -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Identité administrative</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Nom complet</dt>
                        <dd class="col-7"><?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?></dd>
                        <dt class="col-5">Numéro Sécurité Sociale</dt>
                        <dd class="col-7"><code><?= $dispVal($fiche['numero_ss']) ?></code></dd>
                        <dt class="col-5">Date d'embauche</dt>
                        <dd class="col-7"><?= $dispVal($fiche['date_embauche'], 'date') ?></dd>
                        <?php if (!empty($fiche['date_sortie'])): ?>
                        <dt class="col-5">Date de sortie</dt>
                        <dd class="col-7 text-warning"><?= $dispVal($fiche['date_sortie'], 'date') ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Contrat -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Mon contrat</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Type de contrat</dt>
                        <dd class="col-7"><span class="badge bg-info"><?= htmlspecialchars($typesContrat[$fiche['type_contrat']] ?? $fiche['type_contrat']) ?></span></dd>
                        <dt class="col-5">Temps de travail</dt>
                        <dd class="col-7">
                            <?= htmlspecialchars($tempsTravail[$fiche['temps_travail']] ?? $fiche['temps_travail']) ?>
                            <?php if ($fiche['temps_travail'] === 'temps_partiel' && !empty($fiche['quotite_temps_partiel'])): ?>
                            <small class="text-muted">(<?= $dispVal($fiche['quotite_temps_partiel'], 'pct') ?>)</small>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-5">Catégorie</dt>
                        <dd class="col-7"><?= htmlspecialchars($categories[$fiche['categorie']] ?? $fiche['categorie']) ?></dd>
                        <dt class="col-5">Coefficient</dt>
                        <dd class="col-7"><?= $dispVal($fiche['coefficient']) ?></dd>
                        <dt class="col-5">Convention applicable</dt>
                        <dd class="col-7"><?= $dispVal($fiche['convention_nom']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Rémunération -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Ma rémunération</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-6">Salaire brut mensuel base</dt>
                        <dd class="col-6"><strong class="text-success"><?= $dispVal($fiche['salaire_brut_base'], 'eur') ?></strong></dd>
                        <dt class="col-6">Taux horaire normal</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_horaire_normal'], 'eur') ?> /h</dd>
                        <dt class="col-6">Heures sup 25%</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_majoration_25'], 'pct') ?></dd>
                        <dt class="col-6">Heures sup 50%</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_majoration_50'], 'pct') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- RIB MODIFIABLE -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100 border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-university me-2"></i>Mes coordonnées bancaires <small>(modifiable)</small></h6>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/salarie/updateRib">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control text-uppercase" value="<?= htmlspecialchars($fiche['iban'] ?? '') ?>" maxlength="34" placeholder="FR76 ...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">BIC</label>
                            <input type="text" name="bic" class="form-control text-uppercase" value="<?= htmlspecialchars($fiche['bic'] ?? '') ?>" maxlength="11">
                        </div>
                        <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save me-1"></i>Enregistrer mes coordonnées</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-3">
        <i class="fas fa-info-circle me-1"></i>Vos bulletins de paie seront accessibles depuis cette page à partir de la <strong>Phase 4</strong> du module Comptabilité.
    </p>
    <?php endif; ?>
</div>
