<?php
$isEdit = !empty($ascenseur);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-elevator',       'text' => 'Ascenseurs',      'url' => BASE_URL . '/maintenance/ascenseurs'],
    ['icon' => 'fas fa-edit',           'text' => $isEdit ? 'Modifier' : 'Nouvel ascenseur', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$action = BASE_URL . '/maintenance/ascenseurForm' . ($isEdit ? '/' . (int)$ascenseur['id'] : '');
$v = function($k, $def = '') use ($ascenseur, $isEdit) { return $isEdit ? htmlspecialchars((string)($ascenseur[$k] ?? $def)) : $def; };
?>

<div class="container-fluid py-4">

    <h1 class="h3 mb-4">
        <i class="fas fa-elevator text-secondary me-2"></i>
        <?= $isEdit ? 'Modifier l\'ascenseur' : 'Nouvel ascenseur' ?>
    </h1>

    <form method="POST" action="<?= $action ?>" class="row g-3">
        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

        <!-- Identité -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-light"><strong><i class="fas fa-id-card me-2"></i>Identité</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Résidence <span class="text-danger">*</span></label>
                            <select name="residence_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                <option value="">— Sélectionner —</option>
                                <?php foreach ($residences as $r):
                                    $sel = $isEdit ? (int)$ascenseur['residence_id'] : $residencePreselectee;
                                ?>
                                <option value="<?= (int)$r['id'] ?>" <?= $sel === (int)$r['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($r['nom']) ?> (<?= htmlspecialchars($r['ville']) ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if ($isEdit): ?>
                            <input type="hidden" name="residence_id" value="<?= (int)$ascenseur['residence_id'] ?>">
                            <small class="text-muted">La résidence ne peut pas être modifiée. Pour ça, supprime et recrée l'ascenseur.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required maxlength="100"
                                   value="<?= $v('nom') ?>" placeholder="Ex: Ascenseur A">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statut</label>
                            <select name="statut" class="form-select">
                                <?php foreach ($statuts as $s): ?>
                                <option value="<?= $s ?>" <?= $isEdit && $ascenseur['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° série</label>
                            <input type="text" name="numero_serie" class="form-control" maxlength="100" value="<?= $v('numero_serie') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Emplacement</label>
                            <input type="text" name="emplacement" class="form-control" maxlength="255" value="<?= $v('emplacement') ?>" placeholder="Ex: Hall principal, étages 0-5">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Caractéristiques techniques -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-cogs me-2"></i>Caractéristiques techniques</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Marque</label>
                            <select name="marque" class="form-select">
                                <?php foreach ($marques as $m): ?>
                                <option value="<?= $m ?>" <?= $isEdit && $ascenseur['marque'] === $m ? 'selected' : '' ?>><?= ucfirst($m) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Modèle</label>
                            <input type="text" name="modele" class="form-control" maxlength="100" value="<?= $v('modele') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capacité (kg)</label>
                            <input type="number" name="capacite_kg" class="form-control" min="0" value="<?= $v('capacite_kg') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Capacité (pers.)</label>
                            <input type="number" name="capacite_personnes" class="form-control" min="0" value="<?= $v('capacite_personnes') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Nb étages</label>
                            <input type="number" name="nombre_etages" class="form-control" min="0" value="<?= $v('nombre_etages') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Date mise en service</label>
                            <input type="date" name="date_mise_service" class="form-control"
                                   value="<?= $isEdit && !empty($ascenseur['date_mise_service']) ? htmlspecialchars($ascenseur['date_mise_service']) : '' ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contrat ascensoriste -->
        <div class="col-md-6">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-light"><strong><i class="fas fa-file-contract me-2"></i>Contrat ascensoriste</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Société ascensoriste</label>
                            <input type="text" name="contrat_ascensoriste_nom" class="form-control" maxlength="200"
                                   value="<?= $v('contrat_ascensoriste_nom') ?>" placeholder="Ex: Otis France">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone</label>
                            <input type="text" name="contrat_ascensoriste_tel" class="form-control" maxlength="50" value="<?= $v('contrat_ascensoriste_tel') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="contrat_ascensoriste_email" class="form-control" maxlength="200" value="<?= $v('contrat_ascensoriste_email') ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">N° contrat</label>
                            <input type="text" name="contrat_numero" class="form-control" maxlength="100" value="<?= $v('contrat_numero') ?>">
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

        <div class="col-12 d-flex justify-content-between gap-2">
            <a href="<?= BASE_URL ?>/maintenance/ascenseurs<?= $isEdit ? '?residence_id='.(int)$ascenseur['residence_id'] : '' ?>" class="btn btn-secondary">Annuler</a>
            <button type="submit" class="btn btn-warning">
                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer l\'ascenseur' ?>
            </button>
        </div>
    </form>
</div>
