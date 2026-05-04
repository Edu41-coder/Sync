<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-users',          'text' => 'Salariés',        'url' => BASE_URL . '/salarie/index'],
    ['icon' => 'fas fa-edit',           'text' => 'Éditer',          'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$f = $fiche ?? [];
$val = function($key, $default = '') use ($f) {
    return htmlspecialchars((string)($f[$key] ?? $default));
};
$selected = function($key, $option) use ($f) {
    $v = $f[$key] ?? null;
    return $v === $option ? 'selected' : '';
};
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0">
            <i class="fas fa-edit me-2 text-primary"></i><?= $fiche ? 'Modifier' : 'Créer' ?> la fiche RH
            <small class="text-muted fs-6">— <?= htmlspecialchars(trim(($user['prenom'] ?? '') . ' ' . ($user['nom'] ?? ''))) ?></small>
        </h2>
        <a href="<?= BASE_URL ?>/salarie/<?= $fiche ? 'show' : 'index' ?><?= $fiche ? '/' . (int)$user['id'] : '' ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i>Annuler
        </a>
    </div>

    <form method="POST" action="<?= BASE_URL ?>/salarie/update/<?= (int)$user['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">

        <div class="row g-4">
            <!-- Identité administrative -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Identité administrative</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Numéro Sécurité Sociale</label>
                            <input type="text" name="numero_ss" class="form-control" value="<?= $val('numero_ss') ?>" maxlength="20" placeholder="1 85 06 75 056 222 33">
                            <small class="text-muted">Format : 15 chiffres (1 ou 2 + ...)</small>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Date d'embauche</label>
                                <input type="date" name="date_embauche" class="form-control" value="<?= $val('date_embauche') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de sortie</label>
                                <input type="date" name="date_sortie" class="form-control" value="<?= $val('date_sortie') ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motif de sortie (si applicable)</label>
                                <input type="text" name="motif_sortie" class="form-control" value="<?= $val('motif_sortie') ?>" maxlength="100" placeholder="Démission, fin CDD, licenciement…">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contrat -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-file-signature me-2"></i>Contrat</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Type de contrat <span class="text-danger">*</span></label>
                                <select name="type_contrat" class="form-select" required>
                                    <?php foreach ($typesContrat as $k => $l): ?>
                                    <option value="<?= $k ?>" <?= $selected('type_contrat', $k) ?>><?= htmlspecialchars($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Catégorie</label>
                                <select name="categorie" class="form-select">
                                    <?php foreach ($categories as $k => $l): ?>
                                    <option value="<?= $k ?>" <?= $selected('categorie', $k) ?>><?= htmlspecialchars($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Motif CDD (si CDD)</label>
                                <input type="text" name="motif_cdd" class="form-control" value="<?= $val('motif_cdd') ?>" maxlength="255">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date fin CDD</label>
                                <input type="date" name="cdd_date_fin" class="form-control" value="<?= $val('cdd_date_fin') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Coefficient</label>
                                <input type="text" name="coefficient" class="form-control" value="<?= $val('coefficient') ?>" maxlength="20">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Temps de travail</label>
                                <select name="temps_travail" class="form-select">
                                    <?php foreach ($tempsTravail as $k => $l): ?>
                                    <option value="<?= $k ?>" <?= $selected('temps_travail', $k) ?>><?= htmlspecialchars($l) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Quotité temps partiel (%)</label>
                                <input type="number" step="0.01" min="0" max="100" name="quotite_temps_partiel" class="form-control" value="<?= $val('quotite_temps_partiel') ?>" placeholder="80.00">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Convention -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-gavel me-2"></i>Convention collective</h6></div>
                    <div class="card-body">
                        <label class="form-label">Convention collective applicable</label>
                        <select name="convention_collective_id" class="form-select">
                            <option value="">— Aucune —</option>
                            <?php foreach ($conventions as $cc): ?>
                            <option value="<?= $cc['id'] ?>" <?= !empty($f['convention_collective_id']) && $f['convention_collective_id'] == $cc['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cc['nom']) ?><?= $cc['idcc'] ? ' (IDCC ' . htmlspecialchars($cc['idcc']) . ')' : '' ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted">Détermine les règles d'heures sup, charges et calculs paie.</small>
                    </div>
                </div>
            </div>

            <!-- Rémunération -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-euro-sign me-2"></i>Rémunération</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Salaire brut mensuel base (€)</label>
                                <input type="number" step="0.01" min="0" name="salaire_brut_base" class="form-control" value="<?= $val('salaire_brut_base') ?>" placeholder="2000.00">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Taux horaire normal (€)</label>
                                <input type="number" step="0.0001" min="0" name="taux_horaire_normal" class="form-control" value="<?= $val('taux_horaire_normal') ?>" placeholder="Auto si vide">
                                <small class="text-muted">Si vide : salaire / 151,67h</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Majoration heures sup 25% (%)</label>
                                <input type="number" step="0.01" name="taux_majoration_25" class="form-control" value="<?= $val('taux_majoration_25', '25.00') ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Majoration heures sup 50% (%)</label>
                                <input type="number" step="0.01" name="taux_majoration_50" class="form-control" value="<?= $val('taux_majoration_50', '50.00') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RIB -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-university me-2"></i>Coordonnées bancaires</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">IBAN</label>
                            <input type="text" name="iban" class="form-control text-uppercase" value="<?= $val('iban') ?>" maxlength="34" placeholder="FR76 ...">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">BIC</label>
                            <input type="text" name="bic" class="form-control text-uppercase" value="<?= $val('bic') ?>" maxlength="11">
                        </div>
                        <p class="small text-info mb-0"><i class="fas fa-info-circle me-1"></i>Le salarié peut modifier ses coordonnées depuis son espace personnel.</p>
                    </div>
                </div>
            </div>

            <!-- Mutuelle / Prévoyance -->
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-shield-alt me-2"></i>Mutuelle &amp; Prévoyance</h6></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-6">
                                <label class="form-label">Mutuelle salarial (%)</label>
                                <input type="number" step="0.01" name="mutuelle_taux_salarial" class="form-control" value="<?= $val('mutuelle_taux_salarial', '1.50') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Mutuelle patronal (%)</label>
                                <input type="number" step="0.01" name="mutuelle_taux_patronal" class="form-control" value="<?= $val('mutuelle_taux_patronal', '1.50') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Prévoyance salarial (%)</label>
                                <input type="number" step="0.01" name="prevoyance_taux_salarial" class="form-control" value="<?= $val('prevoyance_taux_salarial', '0.50') ?>">
                            </div>
                            <div class="col-6">
                                <label class="form-label">Prévoyance patronal (%)</label>
                                <input type="number" step="0.01" name="prevoyance_taux_patronal" class="form-control" value="<?= $val('prevoyance_taux_patronal', '0.50') ?>">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header"><h6 class="mb-0"><i class="fas fa-sticky-note me-2"></i>Notes internes</h6></div>
                    <div class="card-body">
                        <textarea name="notes" class="form-control" rows="3"><?= htmlspecialchars($f['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-between mt-4">
            <a href="<?= BASE_URL ?>/salarie/index" class="btn btn-outline-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
            <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Enregistrer la fiche</button>
        </div>
    </form>
</div>
