<?php
$isEdit = !empty($animation);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-music',          'text' => 'Animations',      'url' => BASE_URL . '/accueil/animations'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier' : 'Nouvelle animation', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $isEdit ? 'Modifier l\'animation' : 'Nouvelle animation' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/animationForm<?= $isEdit ? '/' . (int)$animation['id'] : '' ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="residence_id" value="<?= (int)$residenceId ?>">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Résidence</label>
                                <input type="text" class="form-control" disabled value="<?php
                                    foreach ($residences as $r) { if ((int)$r['id'] === (int)$residenceId) { echo htmlspecialchars($r['nom']); break; } }
                                ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Animateur</label>
                                <select name="user_id" class="form-select">
                                    <option value="">— Aucun (à assigner) —</option>
                                    <?php foreach ($animateurs as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= $isEdit && (int)$animation['user_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                        <small>(<?= htmlspecialchars($u['role']) ?>)</small>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Staff de la résidence ou admin.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="titre" class="form-control" required maxlength="255"
                                       value="<?= $isEdit ? htmlspecialchars($animation['titre']) : '' ?>"
                                       placeholder="Ex: Atelier mémoire, Loto, Visite musée, Café littéraire…">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date / heure début <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_debut" class="form-control" required
                                       value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($animation['date_debut'])) : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date / heure fin <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_fin" class="form-control" required
                                       value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($animation['date_fin'])) : '' ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description (visible des résidents)</label>
                                <textarea name="description" class="form-control" rows="3" maxlength="1000"
                                          placeholder="Présentation de l'animation, public visé, matériel à apporter…"><?= $isEdit ? htmlspecialchars($animation['description'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes internes</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                                          placeholder="Notes équipe (logistique, intervenants externes…)"><?= $isEdit ? htmlspecialchars($animation['notes'] ?? '') : '' ?></textarea>
                            </div>

                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/accueil/animations?residence_id=<?= (int)$residenceId ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer l\'animation' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
