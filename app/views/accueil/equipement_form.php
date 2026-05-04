<?php
$isEdit = !empty($equipement);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-toolbox',        'text' => 'Équipements',     'url' => BASE_URL . '/accueil/equipements'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier' : 'Nouvel équipement', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$residenceIdSelected = $isEdit ? (int)$equipement['residence_id'] : ($residencePreselectee ?? 0);

$libellesType = [
    'mobilite'     => 'Mobilité (fauteuil, déambulateur…)',
    'informatique' => 'Informatique (tablette, ordinateur…)',
    'loisirs'      => 'Loisirs (jeux, livres, sport…)',
    'medical'      => 'Médical (tensiomètre, oxymètre…)',
    'autre'        => 'Autre',
];
$libellesStatut = [
    'disponible'   => 'Disponible',
    'prete'        => 'Prêté',
    'hors_service' => 'Hors service',
    'maintenance'  => 'En maintenance',
];
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $isEdit ? 'Modifier l\'équipement' : 'Nouvel équipement prêtable' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/equipementForm<?= $isEdit ? '/' . (int)$equipement['id'] : '' ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Résidence <span class="text-danger">*</span></label>
                                <select name="residence_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($residences as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === $residenceIdSelected ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($isEdit): ?>
                                <input type="hidden" name="residence_id" value="<?= (int)$equipement['residence_id'] ?>">
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nom <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" required maxlength="150"
                                       value="<?= $isEdit ? htmlspecialchars($equipement['nom']) : '' ?>"
                                       placeholder="Ex: Fauteuil roulant n°3, Tablette iPad…">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <?php foreach ($types as $t): ?>
                                    <option value="<?= $t ?>" <?= $isEdit && $equipement['type'] === $t ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($libellesType[$t] ?? $t) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Statut <span class="text-danger">*</span></label>
                                <select name="statut" class="form-select" required>
                                    <?php foreach ($statuts as $s): ?>
                                    <option value="<?= $s ?>" <?= $isEdit && $equipement['statut'] === $s ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($libellesStatut[$s] ?? $s) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Numéro de série / inventaire</label>
                                <input type="text" name="numero_serie" class="form-control" maxlength="100"
                                       value="<?= $isEdit ? htmlspecialchars($equipement['numero_serie'] ?? '') : '' ?>"
                                       placeholder="Optionnel">
                            </div>

                            <div class="col-md-6 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="actif" id="actif" value="1"
                                           <?= !$isEdit || !empty($equipement['actif']) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="actif">Équipement actif</label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" maxlength="1000"
                                          placeholder="État, conditions de prêt, restrictions…"><?= $isEdit ? htmlspecialchars($equipement['notes'] ?? '') : '' ?></textarea>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/accueil/equipements<?= $residenceIdSelected ? '?residence_id=' . $residenceIdSelected : '' ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer l\'équipement' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
