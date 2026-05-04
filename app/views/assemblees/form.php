<?php
$isEdit = !empty($ag);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Assemblées Générales', 'url' => BASE_URL . '/assemblee/index'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier AG' : 'Nouvelle AG', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i><?= $isEdit ? 'Modifier l\'AG' : 'Nouvelle Assemblée Générale' ?></h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/assemblee/form<?= $isEdit ? '/' . (int)$ag['id'] : '' ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">

                        <div class="row g-3">

                            <div class="col-md-6">
                                <label class="form-label">Résidence <span class="text-danger">*</span></label>
                                <select name="copropriete_id" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($residences as $r): ?>
                                    <option value="<?= (int)$r['id'] ?>" <?= (int)$r['id'] === (int)$residenceId ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($r['nom']) ?> <small>(<?= htmlspecialchars($r['ville']) ?>)</small>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if ($isEdit): ?>
                                <input type="hidden" name="copropriete_id" value="<?= (int)$ag['copropriete_id'] ?>">
                                <small class="text-muted">La résidence ne peut pas être modifiée.</small>
                                <?php endif; ?>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-select" required>
                                    <option value="ordinaire"      <?= $isEdit && $ag['type'] === 'ordinaire' ? 'selected' : '' ?>>Ordinaire (AGO)</option>
                                    <option value="extraordinaire" <?= $isEdit && $ag['type'] === 'extraordinaire' ? 'selected' : '' ?>>Extraordinaire (AGE)</option>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Mode</label>
                                <select name="mode" class="form-select">
                                    <option value="presentiel" <?= !$isEdit || $ag['mode'] === 'presentiel' ? 'selected' : '' ?>>Présentiel</option>
                                    <option value="visio"      <?= $isEdit && $ag['mode'] === 'visio' ? 'selected' : '' ?>>Visioconférence</option>
                                    <option value="mixte"      <?= $isEdit && $ag['mode'] === 'mixte' ? 'selected' : '' ?>>Mixte</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date / heure <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_ag" class="form-control" required
                                       value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($ag['date_ag'])) : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Lieu</label>
                                <input type="text" name="lieu" class="form-control" maxlength="255"
                                       value="<?= $isEdit ? htmlspecialchars($ag['lieu'] ?? '') : '' ?>"
                                       placeholder="Ex: Salon La Badiane, salle communale, ou lien visio">
                            </div>

                            <div class="col-12">
                                <label class="form-label">Ordre du jour</label>
                                <textarea name="ordre_du_jour" class="form-control" rows="6" maxlength="5000"
                                          placeholder="1. Approbation des comptes 2025&#10;2. Vote du budget prévisionnel 2026&#10;3. Travaux ravalement façade…"><?= $isEdit ? htmlspecialchars($ag['ordre_du_jour'] ?? '') : '' ?></textarea>
                                <small class="text-muted">Une résolution par ligne. Les votes seront saisis individuellement après la séance.</small>
                            </div>

                            <?php if ($isEdit && in_array($ag['statut'], ['convoquee','tenue'], true)): ?>
                            <hr>
                            <div class="col-12"><h6 class="text-primary"><i class="fas fa-users me-1"></i>Bureau de séance & quorum</h6></div>

                            <div class="col-md-6">
                                <label class="form-label">Président de séance</label>
                                <select name="president_seance_id" class="form-select">
                                    <option value="">— À désigner —</option>
                                    <?php foreach ($candidats as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= (int)$ag['president_seance_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?> <small>(<?= htmlspecialchars($u['role']) ?>)</small>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Secrétaire</label>
                                <select name="secretaire_id" class="form-select">
                                    <option value="">— À désigner —</option>
                                    <?php foreach ($candidats as $u): ?>
                                    <option value="<?= (int)$u['id'] ?>" <?= (int)$ag['secretaire_id'] === (int)$u['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Quorum requis</label>
                                <input type="number" name="quorum_requis" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$ag['quorum_requis'] : '' ?>" placeholder="ex: 500">
                                <small class="text-muted">En tantièmes ou voix</small>
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Quorum présent</label>
                                <input type="number" name="quorum_present" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$ag['quorum_present'] : '' ?>">
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">Total votants</label>
                                <input type="number" name="votants_total" class="form-control" min="0"
                                       value="<?= $isEdit ? (int)$ag['votants_total'] : '' ?>"
                                       placeholder="Présents + représentés">
                            </div>

                            <div class="col-md-3 d-flex align-items-end">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" name="quorum_atteint" id="quorum_atteint" value="1"
                                           <?= $isEdit && $ag['quorum_atteint'] ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="quorum_atteint"><strong>Quorum atteint</strong></label>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Procès-verbal (texte)</label>
                                <textarea name="proces_verbal" class="form-control" rows="6" maxlength="20000"
                                          placeholder="Compte-rendu détaillé de la séance…"><?= $isEdit ? htmlspecialchars($ag['proces_verbal'] ?? '') : '' ?></textarea>
                                <small class="text-muted">Pour saisir le PV en texte. Le PDF officiel s'upload via le bouton "Marquer tenue".</small>
                            </div>
                            <?php endif; ?>

                            <div class="col-12">
                                <label class="form-label">Notes internes</label>
                                <textarea name="notes_internes" class="form-control" rows="2" maxlength="2000"
                                          placeholder="Notes privées non visibles des propriétaires…"><?= $isEdit ? htmlspecialchars($ag['notes_internes'] ?? '') : '' ?></textarea>
                            </div>

                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/assemblee/<?= $isEdit ? 'show/' . (int)$ag['id'] : 'index' ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer l\'AG' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
