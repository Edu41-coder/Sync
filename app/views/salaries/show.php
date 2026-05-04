<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-users',          'text' => 'Salariés',        'url' => BASE_URL . '/salarie/index'],
    ['icon' => 'fas fa-id-card',        'text' => htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))), 'url' => null],
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
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0">
            <i class="fas fa-id-card me-2 text-primary"></i><?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?>
            <small class="text-muted fs-6">@<?= htmlspecialchars($user['username'] ?? '') ?></small>
            <span class="badge bg-secondary ms-2"><?= htmlspecialchars($user['role'] ?? '') ?></span>
        </h2>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/salarie/index" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <a href="<?= BASE_URL ?>/salarie/edit/<?= (int)$user['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Modifier la fiche</a>
        </div>
    </div>

    <?php if (!$fiche): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Aucune fiche RH créée pour cet utilisateur.
        <a href="<?= BASE_URL ?>/salarie/edit/<?= (int)$user['id'] ?>" class="alert-link">Créer maintenant →</a>
    </div>
    <?php else: ?>

    <div class="row g-4">
        <!-- Identité administrative -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Identité administrative</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Numéro Sécurité Sociale</dt><dd class="col-7"><?= $dispVal($fiche['numero_ss']) ?></dd>
                        <dt class="col-5">Date d'embauche</dt><dd class="col-7"><?= $dispVal($fiche['date_embauche'], 'date') ?></dd>
                        <dt class="col-5">Date de sortie</dt><dd class="col-7"><?= $dispVal($fiche['date_sortie'], 'date') ?></dd>
                        <?php if (!empty($fiche['date_sortie'])): ?>
                        <dt class="col-5">Motif sortie</dt><dd class="col-7"><?= $dispVal($fiche['motif_sortie']) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Contrat -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Contrat</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Type de contrat</dt><dd class="col-7"><span class="badge bg-info"><?= htmlspecialchars($typesContrat[$fiche['type_contrat']] ?? $fiche['type_contrat']) ?></span></dd>
                        <?php if ($fiche['type_contrat'] === 'CDD'): ?>
                        <dt class="col-5">Motif CDD</dt><dd class="col-7"><?= $dispVal($fiche['motif_cdd']) ?></dd>
                        <dt class="col-5">Fin CDD</dt><dd class="col-7"><?= $dispVal($fiche['cdd_date_fin'], 'date') ?></dd>
                        <?php endif; ?>
                        <dt class="col-5">Temps de travail</dt><dd class="col-7"><?= htmlspecialchars($tempsTravail[$fiche['temps_travail']] ?? $fiche['temps_travail']) ?>
                            <?php if ($fiche['temps_travail'] === 'temps_partiel' && !empty($fiche['quotite_temps_partiel'])): ?>
                            <small class="text-muted">(<?= $dispVal($fiche['quotite_temps_partiel'], 'pct') ?>)</small>
                            <?php endif; ?>
                        </dd>
                        <dt class="col-5">Catégorie</dt><dd class="col-7"><?= htmlspecialchars($categories[$fiche['categorie']] ?? $fiche['categorie']) ?></dd>
                        <dt class="col-5">Coefficient</dt><dd class="col-7"><?= $dispVal($fiche['coefficient']) ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Convention collective -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Convention collective</h6></div>
                <div class="card-body">
                    <?php if (!empty($fiche['convention_nom'])): ?>
                    <strong><?= htmlspecialchars($fiche['convention_nom']) ?></strong>
                    <?php if (!empty($fiche['convention_idcc'])): ?>
                    <br><small class="text-muted">IDCC : <code><?= htmlspecialchars($fiche['convention_idcc']) ?></code></small>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-muted">Aucune convention assignée</span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Rémunération -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Rémunération</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-6">Salaire brut mensuel base</dt>
                        <dd class="col-6"><strong class="text-success"><?= $dispVal($fiche['salaire_brut_base'], 'eur') ?></strong></dd>
                        <dt class="col-6">Taux horaire normal</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_horaire_normal'], 'eur') ?> /h</dd>
                        <dt class="col-6">Majoration heures sup 25%</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_majoration_25'], 'pct') ?></dd>
                        <dt class="col-6">Majoration heures sup 50%</dt>
                        <dd class="col-6"><?= $dispVal($fiche['taux_majoration_50'], 'pct') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Coordonnées bancaires -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-university me-2"></i>Coordonnées bancaires</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-3">IBAN</dt><dd class="col-9"><code><?= $dispVal($fiche['iban']) ?></code></dd>
                        <dt class="col-3">BIC</dt><dd class="col-9"><code><?= $dispVal($fiche['bic']) ?></code></dd>
                    </dl>
                </div>
            </div>
        </div>

        <!-- Mutuelle / Prévoyance -->
        <div class="col-lg-6">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Mutuelle &amp; Prévoyance</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-7">Mutuelle (taux salarial)</dt><dd class="col-5"><?= $dispVal($fiche['mutuelle_taux_salarial'], 'pct') ?></dd>
                        <dt class="col-7">Mutuelle (taux patronal)</dt><dd class="col-5"><?= $dispVal($fiche['mutuelle_taux_patronal'], 'pct') ?></dd>
                        <dt class="col-7">Prévoyance (taux salarial)</dt><dd class="col-5"><?= $dispVal($fiche['prevoyance_taux_salarial'], 'pct') ?></dd>
                        <dt class="col-7">Prévoyance (taux patronal)</dt><dd class="col-5"><?= $dispVal($fiche['prevoyance_taux_patronal'], 'pct') ?></dd>
                    </dl>
                </div>
            </div>
        </div>

        <?php if (!empty($fiche['notes'])): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes</h6></div>
                <div class="card-body"><?= nl2br(htmlspecialchars($fiche['notes'])) ?></div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <p class="text-muted small mt-3">
        <i class="fas fa-info-circle me-1"></i>Bulletins de paie disponibles à partir de la <strong>Phase 4</strong> du module Comptabilité.
    </p>
    <?php endif; ?>
</div>
