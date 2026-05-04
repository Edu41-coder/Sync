<?php
$isEdit = !empty($reservation);
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Réservations',    'url' => BASE_URL . '/accueil/reservations'],
    ['icon' => $isEdit ? 'fas fa-edit' : 'fas fa-plus', 'text' => $isEdit ? 'Modifier' : 'Nouvelle réservation', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$typeSelected = $isEdit ? $reservation['type_reservation'] : 'salle';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-<?= $isEdit ? 'edit' : 'plus' ?> me-2"></i>
                        <?= $isEdit ? 'Modifier la réservation' : 'Nouvelle réservation' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/reservationForm<?= $isEdit ? '/' . (int)$reservation['id'] : '' ?>" id="resForm">
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
                                <label class="form-label">Type de réservation <span class="text-danger">*</span></label>
                                <select name="type_reservation" id="type_reservation" class="form-select" required <?= $isEdit ? 'disabled' : '' ?>>
                                    <option value="salle"             <?= $typeSelected === 'salle' ? 'selected' : '' ?>>🏠 Salle commune</option>
                                    <option value="equipement"        <?= $typeSelected === 'equipement' ? 'selected' : '' ?>>🛠️ Équipement prêtable</option>
                                    <option value="service_personnel" <?= $typeSelected === 'service_personnel' ? 'selected' : '' ?>>👤 Service personnel</option>
                                </select>
                                <?php if ($isEdit): ?>
                                <input type="hidden" name="type_reservation" value="<?= $typeSelected ?>">
                                <?php endif; ?>
                            </div>

                            <!-- Cible : Salle -->
                            <div class="col-md-6 cible-bloc cible-salle" style="display:<?= $typeSelected === 'salle' ? 'block' : 'none' ?>">
                                <label class="form-label">Salle <span class="text-danger">*</span></label>
                                <select name="salle_id" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($salles as $s): ?>
                                    <option value="<?= (int)$s['id'] ?>" <?= $isEdit && (int)$reservation['salle_id'] === (int)$s['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($s['nom']) ?>
                                        <?php if ($s['capacite_personnes']): ?>(<?= (int)$s['capacite_personnes'] ?> pers.)<?php endif; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($salles)): ?>
                                <small class="text-warning">Aucune salle active. <a href="<?= BASE_URL ?>/accueil/salles?residence_id=<?= (int)$residenceId ?>">Gérer le catalogue</a>.</small>
                                <?php endif; ?>
                            </div>

                            <!-- Cible : Équipement -->
                            <div class="col-md-6 cible-bloc cible-equipement" style="display:<?= $typeSelected === 'equipement' ? 'block' : 'none' ?>">
                                <label class="form-label">Équipement <span class="text-danger">*</span></label>
                                <select name="equipement_id" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($equipements as $e): ?>
                                    <option value="<?= (int)$e['id'] ?>" <?= $isEdit && (int)$reservation['equipement_id'] === (int)$e['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($e['nom']) ?> (<?= htmlspecialchars($e['type']) ?>)
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (empty($equipements)): ?>
                                <small class="text-warning">Aucun équipement actif. <a href="<?= BASE_URL ?>/accueil/equipements?residence_id=<?= (int)$residenceId ?>">Gérer le catalogue</a>.</small>
                                <?php endif; ?>
                            </div>

                            <!-- Cible : Service personnel -->
                            <div class="col-md-6 cible-bloc cible-service" style="display:<?= $typeSelected === 'service_personnel' ? 'block' : 'none' ?>">
                                <label class="form-label">Type de service <span class="text-danger">*</span></label>
                                <select name="type_service" class="form-select">
                                    <option value="">— Sélectionner —</option>
                                    <?php foreach ($typesService as $ts): ?>
                                    <option value="<?= $ts ?>" <?= $isEdit && $reservation['type_service'] === $ts ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(ucfirst($ts)) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Titre <span class="text-danger">*</span></label>
                                <input type="text" name="titre" class="form-control" required maxlength="255"
                                       value="<?= $isEdit ? htmlspecialchars($reservation['titre']) : '' ?>"
                                       placeholder="Ex: Anniversaire Mme Martin, Atelier informatique, RDV pédicure…">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date / heure début <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_debut" class="form-control" required
                                       value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($reservation['date_debut'])) : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Date / heure fin <span class="text-danger">*</span></label>
                                <input type="datetime-local" name="date_fin" class="form-control" required
                                       value="<?= $isEdit ? date('Y-m-d\TH:i', strtotime($reservation['date_fin'])) : '' ?>">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Résident bénéficiaire</label>
                                <select name="resident_id" class="form-select">
                                    <option value="">— Aucun —</option>
                                    <?php foreach ($residents as $rs): ?>
                                    <option value="<?= (int)$rs['id'] ?>" <?= $isEdit && (int)$reservation['resident_id'] === (int)$rs['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($rs['prenom'] . ' ' . $rs['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Hôte temporaire bénéficiaire</label>
                                <select name="hote_id" class="form-select">
                                    <option value="">— Aucun —</option>
                                    <?php foreach ($hotes as $h): ?>
                                    <option value="<?= (int)$h['id'] ?>" <?= $isEdit && (int)$reservation['hote_id'] === (int)$h['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted">Sélectionner un résident OU un hôte (au moins l'un des deux).</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" maxlength="1000"
                                          placeholder="Détails sur la demande, contexte…"><?= $isEdit ? htmlspecialchars($reservation['description'] ?? '') : '' ?></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Notes internes</label>
                                <textarea name="notes" class="form-control" rows="2" maxlength="1000"
                                          placeholder="Notes privées équipe accueil…"><?= $isEdit ? htmlspecialchars($reservation['notes'] ?? '') : '' ?></textarea>
                            </div>

                        </div>

                        <hr class="my-4">
                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/accueil/reservations?residence_id=<?= (int)$residenceId ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-1"></i>Annuler
                            </a>
                            <button type="submit" class="btn btn-info text-white">
                                <i class="fas fa-save me-1"></i><?= $isEdit ? 'Enregistrer' : 'Créer la réservation' ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('type_reservation').addEventListener('change', function() {
    const type = this.value;
    document.querySelectorAll('.cible-bloc').forEach(el => el.style.display = 'none');
    const map = { salle: '.cible-salle', equipement: '.cible-equipement', service_personnel: '.cible-service' };
    if (map[type]) document.querySelector(map[type]).style.display = 'block';
});
</script>
