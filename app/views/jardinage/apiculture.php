<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-cog', 'text' => 'Configuration apiculture', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$c = $config ?? [];
$typeRucherLabels = ['sedentaire' => 'Sédentaire', 'transhumant' => 'Transhumant'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">⚙️ Configuration apiculture</h2>
            <p class="text-muted mb-0">Paramètres réglementaires et opérationnels du rucher (déclaration préfecture, référent…).</p>
        </div>
        <?php if ($selectedResidence): ?>
        <a href="<?= BASE_URL ?>/jardinage/ruches?residence_id=<?= (int)$selectedResidence ?>" class="btn btn-outline-warning">🐝 Gérer les ruches</a>
        <?php endif; ?>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-auto"><label class="text-muted small mb-0">Résidence :</label></div>
                <div class="col-12 col-md-6">
                    <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="0">— Sélectionner —</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedResidence == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </form>
        </div>
    </div>

    <?php if (!$selectedResidence): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Sélectionnez une résidence pour configurer l'apiculture.</div>
    <?php else: ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-clipboard-list me-2"></i>Paramètres apiculture</h6>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/jardinage/apiculture/save">
                    <div class="card-body">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="residence_id" value="<?= (int)$selectedResidence ?>">

                        <fieldset <?= $isManager ? '' : 'disabled' ?>>
                        <?php if (!$isManager): ?>
                        <div class="alert alert-secondary small py-2 mb-3"><i class="fas fa-lock me-1"></i>Consultation seule — l'édition est réservée aux responsables (admin, directeur, jardinier_manager).</div>
                        <?php endif; ?>

                        <h6 class="text-muted text-uppercase small mb-3"><i class="fas fa-gavel me-1"></i>Déclaration préfecture</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Numéro NAPI</label>
                                <input type="text" name="numero_napi" class="form-control" maxlength="50"
                                       placeholder="Ex : NAPI-12345"
                                       value="<?= htmlspecialchars($c['numero_napi'] ?? '') ?>">
                                <small class="text-muted">Obligatoire pour toute exploitation apicole déclarée en France.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date de déclaration</label>
                                <input type="date" name="date_declaration_prefecture" class="form-control"
                                       value="<?= htmlspecialchars($c['date_declaration_prefecture'] ?? '') ?>">
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-3"><i class="fas fa-user-tie me-1"></i>Apiculteur référent</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label">Référent interne (user)</label>
                                <select name="apiculteur_referent_user_id" class="form-select">
                                    <option value="">— Aucun (externe ou non assigné) —</option>
                                    <?php foreach ($candidatsReferent as $u): ?>
                                    <option value="<?= $u['id'] ?>" <?= (!empty($c['apiculteur_referent_user_id']) && $c['apiculteur_referent_user_id'] == $u['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?> — <?= htmlspecialchars($u['role_nom'] ?? $u['role']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Liste restreinte aux responsables affectés à cette résidence.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Prestataire externe</label>
                                <input type="text" name="apiculteur_referent_externe" class="form-control" maxlength="200"
                                       placeholder="Ex : Entreprise Bee & Co, SIRET…"
                                       value="<?= htmlspecialchars($c['apiculteur_referent_externe'] ?? '') ?>">
                                <small class="text-muted">Si prestataire hors Synd_Gest (texte libre).</small>
                            </div>
                        </div>

                        <h6 class="text-muted text-uppercase small mb-3"><i class="fas fa-ruler-combined me-1"></i>Capacité & implantation</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label">Nombre max de ruches</label>
                                <input type="number" min="0" name="nombre_max_ruches" class="form-control"
                                       value="<?= htmlspecialchars((string)($c['nombre_max_ruches'] ?? '')) ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Type de rucher</label>
                                <select name="type_rucher" class="form-select">
                                    <?php foreach ($typeRucherLabels as $k => $l): ?>
                                    <option value="<?= $k ?>" <?= (($c['type_rucher'] ?? 'sedentaire') === $k) ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Distance habitations (m)</label>
                                <input type="number" min="0" name="distance_habitations_m" class="form-control"
                                       placeholder="Urbain FR : 400m min"
                                       value="<?= htmlspecialchars((string)($c['distance_habitations_m'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="3"
                                      placeholder="Voisinage, allergies résidents, arrêté municipal, spécificités du site…"><?= htmlspecialchars($c['notes'] ?? '') ?></textarea>
                        </div>
                        </fieldset>
                    </div>
                    <?php if ($isManager): ?>
                    <div class="card-footer d-flex justify-content-end">
                        <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer la configuration</button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide-mémoire</h6></div>
                <div class="card-body small">
                    <ul class="mb-2 ps-3">
                        <li><strong>NAPI</strong> — Numéro d'apiculteur fourni par la DDPP/DDCSPP, obligatoire pour toute détention d'abeilles (dès 1 ruche).</li>
                        <li><strong>Déclaration annuelle</strong> — À effectuer entre le 1<sup>er</sup> septembre et le 31 décembre sur mesdemarches.agriculture.gouv.fr.</li>
                        <li><strong>Distance habitations</strong> — 20m en agglomération selon arrêté préfectoral (parfois 100m voire 400m en zone dense).</li>
                        <li><strong>Transhumant</strong> — Rucher déplacé selon les miellées (ex : colza puis acacia puis tilleul).</li>
                    </ul>
                    <div class="alert alert-info small mb-0 py-2">
                        Les ruches individuelles se gèrent dans <a href="<?= BASE_URL ?>/jardinage/ruches?residence_id=<?= (int)$selectedResidence ?>">🐝 Ruches</a>.
                    </div>
                </div>
            </div>

            <?php if ($c && !empty($c['updated_at'])): ?>
            <div class="card shadow-sm">
                <div class="card-body small text-muted">
                    <i class="fas fa-history me-1"></i>Dernière mise à jour : <?= date('d/m/Y H:i', strtotime($c['updated_at'])) ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>
