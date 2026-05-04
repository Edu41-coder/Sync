<?php
$isEdit = !empty($salle);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-door-open',      'text' => 'Salles communes', 'url' => BASE_URL . '/accueil/salles'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier' : 'Nouvelle salle', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$residenceIdSelected = $isEdit ? (int)$salle['residence_id'] : ($residencePreselectee ?? 0);
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $isEdit ? 'Modifier la salle' : 'Nouvelle salle commune' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/salleForm<?= $isEdit ? '/' . (int)$salle['id'] : '' ?>" enctype="multipart/form-data">
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
                                <input type="hidden" name="residence_id" value="<?= (int)$salle['residence_id'] ?>">
                                <small class="text-muted">La résidence ne peut pas être modifiée.</small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Nom de la salle <span class="text-danger">*</span></label>
                                <input type="text" name="nom" class="form-control" required maxlength="150"
                                       value="<?= $isEdit ? htmlspecialchars($salle['nom']) : '' ?>"
                                       placeholder="Ex: Salon, Bibliothèque, Salle d'animation…">
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Capacité (personnes)</label>
                                <input type="number" name="capacite_personnes" class="form-control" min="0" max="500"
                                       value="<?= $isEdit ? (int)$salle['capacite_personnes'] : '' ?>"
                                       placeholder="Ex: 30">
                            </div>

                            <div class="col-md-8">
                                <label class="form-label">
                                    <input type="checkbox" name="actif" value="1" <?= !$isEdit || !empty($salle['actif']) ? 'checked' : '' ?>>
                                    Salle active (réservable)
                                </label>
                                <small class="d-block text-muted">Décocher pour désactiver temporairement (travaux, fermeture saisonnière…).</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" maxlength="1000"
                                          placeholder="Description courte de la salle, son usage habituel…"><?= $isEdit ? htmlspecialchars($salle['description'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Équipements inclus</label>
                                <textarea name="equipements_inclus" class="form-control" rows="3" maxlength="1000"
                                          placeholder="Ex: Tables, chaises, vidéoprojecteur, écran, sono, kitchenette…"><?= $isEdit ? htmlspecialchars($salle['equipements_inclus'] ?? '') : '' ?></textarea>
                                <small class="text-muted">Une ligne par équipement, ou liste libre.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Photo de la salle</label>
                                <?php if ($isEdit && !empty($salle['photo'])): ?>
                                <div class="mb-2">
                                    <img src="<?= BASE_URL . '/' . htmlspecialchars($salle['photo']) ?>" alt="" style="max-height:150px;border-radius:.5rem">
                                    <small class="d-block text-muted mt-1">Photo actuelle. Charger un nouveau fichier pour la remplacer.</small>
                                </div>
                                <?php endif; ?>
                                <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                                <small class="text-muted">JPG / PNG / WEBP, 5 Mo max.</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/accueil/salles<?= $residenceIdSelected ? '?residence_id=' . $residenceIdSelected : '' ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer la salle' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
