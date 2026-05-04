<?php
$isEdit = !empty($resolution);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Assemblées Générales', 'url' => BASE_URL . '/assemblee/index'],
    ['icon' => 'fas fa-eye',            'text' => 'AG ' . date('d/m/Y', strtotime($ag['date_ag'])), 'url' => BASE_URL . '/assemblee/show/' . (int)$ag['id']],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier résolution' : 'Nouvelle résolution', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-vote-yea me-2"></i>
                        <?= $isEdit ? 'Modifier la résolution' : 'Nouvelle résolution' ?>
                        <small>· AG <?= htmlspecialchars($ag['type']) ?> du <?= date('d/m/Y', strtotime($ag['date_ag'])) ?></small>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/assemblee/resolutionForm/<?= (int)$ag['id'] . ($isEdit ? '/' . (int)$resolution['id'] : '') ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div class="row g-3">

                            <div class="col-md-2">
                                <label class="form-label">N° ordre</label>
                                <input type="number" name="ordre" class="form-control" min="1" value="<?= (int)$nextOrdre ?>">
                            </div>

                            <div class="col-md-10">
                                <label class="form-label">Intitulé de la résolution <span class="text-danger">*</span></label>
                                <input type="text" name="resolution" class="form-control" required maxlength="255"
                                       value="<?= $isEdit ? htmlspecialchars($resolution['resolution']) : '' ?>"
                                       placeholder="Ex: Approbation des comptes 2025, Vote du budget prévisionnel…">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description / contexte</label>
                                <textarea name="description" class="form-control" rows="3" maxlength="2000"
                                          placeholder="Détails de la résolution, montants concernés, contexte…"><?= $isEdit ? htmlspecialchars($resolution['description'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="col-12"><hr><h6 class="text-primary"><i class="fas fa-vote-yea me-1"></i>Résultats du vote</h6></div>

                            <div class="col-md-4">
                                <label class="form-label">Voix POUR</label>
                                <input type="number" name="votes_pour" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$resolution['votes_pour'] : 0 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Voix CONTRE</label>
                                <input type="number" name="votes_contre" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$resolution['votes_contre'] : 0 ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Abstentions</label>
                                <input type="number" name="abstentions" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$resolution['abstentions'] : 0 ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Tantièmes POUR <small class="text-muted">(optionnel)</small></label>
                                <input type="number" name="tantiemes_pour" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$resolution['tantiemes_pour'] : 0 ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tantièmes CONTRE <small class="text-muted">(optionnel)</small></label>
                                <input type="number" name="tantiemes_contre" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$resolution['tantiemes_contre'] : 0 ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Résultat</label>
                                <select name="resultat" class="form-select">
                                    <option value="">— Calcul automatique selon les votes —</option>
                                    <option value="adopte"  <?= $isEdit && $resolution['resultat'] === 'adopte'  ? 'selected' : '' ?>>✅ Adoptée</option>
                                    <option value="rejete"  <?= $isEdit && $resolution['resultat'] === 'rejete'  ? 'selected' : '' ?>>❌ Rejetée</option>
                                    <option value="reporte" <?= $isEdit && $resolution['resultat'] === 'reporte' ? 'selected' : '' ?>>⏳ Reportée</option>
                                </select>
                                <small class="text-muted">Auto : adoptée si tantièmes_pour > tantièmes_contre (sinon fallback sur les voix).</small>
                            </div>
                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/assemblee/show/<?= (int)$ag['id'] ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Ajouter la résolution' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
