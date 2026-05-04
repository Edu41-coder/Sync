<?php
$isEdit = !empty($chantier);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-hammer',         'text' => 'Chantiers',       'url' => BASE_URL . '/chantier/index'],
    ['icon' => 'fas fa-edit',           'text' => $isEdit ? 'Modifier #' . (int)$chantier['id'] : 'Nouveau', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$action = BASE_URL . '/chantier/form' . ($isEdit ? '/' . (int)$chantier['id'] : '');
$prefill = $sinistrePrefill ?? null;
// Helper : valeur d'un champ — priorité chantier (édition) → prefill sinistre (création) → default
$v = function($k, $def = '') use ($chantier, $isEdit, $prefill) {
    if ($isEdit) return htmlspecialchars((string)($chantier[$k] ?? $def));
    if ($prefill && isset($prefill[$k]) && $prefill[$k] !== null) return htmlspecialchars((string)$prefill[$k]);
    return htmlspecialchars((string)$def);
};
$linkedSinistreId = $isEdit ? (int)($chantier['sinistre_id'] ?? 0) : (int)($prefill['sinistre_id'] ?? 0);
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> text-warning me-2"></i>
        <?= $isEdit ? 'Modifier le chantier' : 'Nouveau chantier' ?>
    </h1>

    <?php if ($prefill): ?>
        <div class="alert alert-warning d-flex align-items-center justify-content-between">
            <div>
                <i class="fas fa-shield-alt me-2"></i>
                <strong>Chantier de réparation issu d'un sinistre.</strong>
                Le titre, la description, la résidence et le montant ont été pré-remplis depuis le sinistre
                <a href="<?= BASE_URL ?>/sinistre/show/<?= (int)$prefill['sinistre_id'] ?>" class="alert-link">#<?= (int)$prefill['sinistre_id'] ?> — <?= htmlspecialchars($prefill['sinistre_titre']) ?></a>.
                Vous pouvez les ajuster avant validation.
            </div>
            <a href="<?= BASE_URL ?>/sinistre/show/<?= (int)$prefill['sinistre_id'] ?>" class="btn btn-sm btn-outline-warning ms-3"><i class="fas fa-times me-1"></i>Annuler</a>
        </div>
    <?php elseif ($isEdit && $linkedSinistreId): ?>
        <div class="alert alert-info">
            <i class="fas fa-shield-alt me-2"></i>
            Ce chantier est lié au sinistre
            <a href="<?= BASE_URL ?>/sinistre/show/<?= $linkedSinistreId ?>" class="alert-link">#<?= $linkedSinistreId ?><?= !empty($chantier['sinistre_titre']) ? ' — ' . htmlspecialchars($chantier['sinistre_titre']) : '' ?></a>.
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= $action ?>" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
        <?php if ($linkedSinistreId): ?>
        <input type="hidden" name="sinistre_id" value="<?= $linkedSinistreId ?>">
        <?php endif; ?>

        <!-- Identité & contexte -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Identité &amp; contexte</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" name="titre" class="form-control" required maxlength="255" value="<?= $v('titre') ?>" placeholder="Ex: Rénovation hall d'entrée">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <?php
                            $selectedResidenceId = $isEdit
                                ? (int)$chantier['residence_id']
                                : (int)($prefill['residence_id'] ?? 0);
                            ?>
                            <select name="residence_id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $selectedResidenceId === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom']) ?> (<?= htmlspecialchars($r['ville']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Spécialité</label>
                            <select name="specialite_id" class="form-select">
                                <option value="">— Aucune —</option>
                                <?php foreach ($specialites as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= $isEdit && (int)($chantier['specialite_id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Catégorie</label>
                            <select name="categorie" class="form-select">
                                <?php foreach ($categories as $c): ?>
                                <option value="<?= $c ?>" <?= $isEdit && $chantier['categorie'] === $c ? 'selected' : '' ?>><?= $c ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= $v('description') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- État & priorité -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-tasks me-2"></i>Phase &amp; statut</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Phase</label>
                            <select name="phase" class="form-select">
                                <?php foreach ($phases as $p): ?>
                                <option value="<?= $p ?>" <?= $isEdit && $chantier['phase'] === $p ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($phasesLabels[$p] ?? $p) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $isEdit && $chantier['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Priorité</label>
                            <select name="priorite" class="form-select">
                                <?php foreach ($priorites as $p): ?>
                                <option value="<?= $p ?>" <?= $isEdit && $chantier['priorite'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Budget & AG -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-euro-sign me-2"></i>Budget &amp; AG</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Montant estimé HT (€)</label>
                            <input type="number" step="0.01" min="0" name="montant_estime" class="form-control" value="<?= $v('montant_estime') ?>" placeholder="Ex: 15000.00">
                            <small class="text-muted">Au-delà de <?= number_format($seuilAg, 0, ',', ' ') ?> € HT, le vote AG est auto-coché.</small>
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="col-md-6">
                            <label class="form-label">Engagé (€)</label>
                            <input type="number" step="0.01" min="0" name="montant_engage" class="form-control" value="<?= $v('montant_engage') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payé (€)</label>
                            <input type="number" step="0.01" min="0" name="montant_paye" class="form-control" value="<?= $v('montant_paye') ?>">
                        </div>
                        <?php endif; ?>
                        <div class="col-12">
                            <label class="form-label">Vote AG nécessaire ?</label>
                            <select name="necessite_ag_force" class="form-select">
                                <option value="">Auto (selon montant > <?= (int)$seuilAg ?> € HT)</option>
                                <option value="1" <?= $isEdit && (int)($chantier['necessite_ag'] ?? 0) === 1 ? 'selected' : '' ?>>Oui (forcer)</option>
                                <option value="0" <?= $isEdit && (int)($chantier['necessite_ag'] ?? -1) === 0 ? 'selected' : '' ?>>Non (forcer)</option>
                            </select>
                        </div>
                        <?php if (!empty($ags)): ?>
                        <div class="col-12">
                            <label class="form-label">AG associée (si vote requis)</label>
                            <select name="ag_id" class="form-select">
                                <option value="">— Aucune AG associée —</option>
                                <?php foreach ($ags as $a): ?>
                                <option value="<?= (int)$a['id'] ?>" <?= $isEdit && (int)($chantier['ag_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>>
                                    AG <?= htmlspecialchars($a['type']) ?> du <?= date('d/m/Y', strtotime($a['date_ag'])) ?> (<?= htmlspecialchars($a['statut']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dates -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-calendar me-2"></i>Dates</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Début prévu</label>
                            <input type="date" name="date_debut_prevue" class="form-control" value="<?= $isEdit && !empty($chantier['date_debut_prevue']) ? htmlspecialchars($chantier['date_debut_prevue']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fin prévue</label>
                            <input type="date" name="date_fin_prevue" class="form-control" value="<?= $isEdit && !empty($chantier['date_fin_prevue']) ? htmlspecialchars($chantier['date_fin_prevue']) : '' ?>">
                        </div>
                        <?php if ($isEdit): ?>
                        <div class="col-md-3">
                            <label class="form-label">Début réel</label>
                            <input type="date" name="date_debut_reelle" class="form-control" value="<?= !empty($chantier['date_debut_reelle']) ? htmlspecialchars($chantier['date_debut_reelle']) : '' ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Fin réelle</label>
                            <input type="date" name="date_fin_reelle" class="form-control" value="<?= !empty($chantier['date_fin_reelle']) ? htmlspecialchars($chantier['date_fin_reelle']) : '' ?>">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2"><?= $v('notes') ?></textarea>
                </div>
            </div>
        </div>

        <div class="col-12 d-flex justify-content-between gap-2">
            <a href="<?= BASE_URL ?>/chantier/index" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer le chantier' ?>
            </button>
        </div>
    </form>
</div>
