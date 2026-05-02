<?php
$isEdit = !empty($intervention);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-tools',          'text' => 'Interventions',   'url' => BASE_URL . '/maintenance/interventions'],
    ['icon' => 'fas fa-edit',           'text' => $isEdit ? 'Modifier #' . (int)$intervention['id'] : 'Nouvelle', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$action = BASE_URL . '/maintenance/interventionForm' . ($isEdit ? '/' . (int)$intervention['id'] : '');
$v = function($k, $def = '') use ($intervention, $isEdit) { return $isEdit ? htmlspecialchars($intervention[$k] ?? $def) : $def; };
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4">
        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> text-warning me-2"></i>
        <?= $isEdit ? 'Modifier l\'intervention' : 'Nouvelle intervention' ?>
    </h1>

    <form method="POST" action="<?= $action ?>" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Titre <span class="text-danger">*</span></label>
                            <input type="text" name="titre" class="form-control" required maxlength="255"
                                   value="<?= $v('titre') ?>" placeholder="Ex: Vérification chimie piscine">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($residences as $r): ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $isEdit && (int)$intervention['residence_id'] === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom']) ?> (<?= htmlspecialchars($r['ville']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Spécialité <span class="text-danger">*</span></label>
                            <select name="specialite_id" class="form-select" required>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($specialites as $s): ?>
                                <option value="<?= (int)$s['id'] ?>" <?= $isEdit && (int)$intervention['specialite_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($s['nom']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">N° Lot (optionnel)</label>
                            <input type="number" name="lot_id" class="form-control" value="<?= $v('lot_id') ?>" min="1">
                            <small class="text-muted">Si l'intervention concerne un lot précis.</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Type</label>
                            <select name="type_intervention" class="form-select">
                                <?php foreach ($types as $t): ?>
                                <option value="<?= $t ?>" <?= $isEdit && $intervention['type_intervention'] === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Priorité</label>
                            <select name="priorite" class="form-select">
                                <?php foreach ($priorites as $p): ?>
                                <option value="<?= $p ?>" <?= $isEdit && $intervention['priorite'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $isEdit && $intervention['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
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

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-user me-2"></i>Affectation</strong></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Technicien assigné</label>
                        <select name="user_assigne_id" class="form-select">
                            <option value="">— Aucun —</option>
                            <?php foreach ($techniciens as $t): ?>
                            <option value="<?= (int)$t['id'] ?>" <?= $isEdit && (int)($intervention['user_assigne_id'] ?? 0) === (int)$t['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['prenom'] . ' ' . $t['nom']) ?> (<?= $t['role'] === 'technicien_chef' ? 'Chef' : 'Tech' ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Prestataire externe (si sous-traité)</label>
                        <input type="text" name="prestataire_externe" class="form-control" value="<?= $v('prestataire_externe') ?>" placeholder="Nom de la société">
                    </div>
                    <div>
                        <label class="form-label">Téléphone prestataire</label>
                        <input type="text" name="prestataire_externe_tel" class="form-control" value="<?= $v('prestataire_externe_tel') ?>">
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-calendar me-2"></i>Planning &amp; coût</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Date planifiée</label>
                            <input type="datetime-local" name="date_planifiee" class="form-control"
                                   value="<?= $isEdit && !empty($intervention['date_planifiee']) ? date('Y-m-d\\TH:i', strtotime($intervention['date_planifiee'])) : '' ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date réalisée</label>
                            <input type="datetime-local" name="date_realisee" class="form-control"
                                   value="<?= $isEdit && !empty($intervention['date_realisee']) ? date('Y-m-d\\TH:i', strtotime($intervention['date_realisee'])) : '' ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Durée (minutes)</label>
                            <input type="number" name="duree_minutes" class="form-control" min="0" value="<?= $v('duree_minutes') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Coût (€)</label>
                            <input type="number" step="0.01" name="cout" class="form-control" min="0" value="<?= $v('cout') ?>">
                        </div>
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

        <div class="col-12 d-flex justify-content-end gap-2">
            <a href="<?= BASE_URL ?>/maintenance/interventions" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer l\'intervention' ?>
            </button>
        </div>
    </form>
</div>
