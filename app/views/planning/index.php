<?php $title = "Planning Staff"; ?>

<!-- TUI Calendar CSS -->
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-calendar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-date-picker.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-time-picker.css">
<style>
    #calendar { height: 700px; }
    .btn-group .btn.active { font-weight: bold; }
    .filter-bar { background: #f8f9fa; border-radius: 8px; }
</style>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar-alt', 'text' => 'Planning Staff', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-dark"></i> Planning Staff</h1>
            <button class="btn btn-danger" onclick="window.planningApp.openCreateModal(new Date(), new Date(Date.now()+3600000), false)">
                <i class="fas fa-plus me-1"></i>Nouveau shift
            </button>
        </div>
    </div>

    <!-- Barre de filtres + navigation -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <!-- Navigation dates -->
                <div class="col-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" id="prev-btn"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-outline-primary" id="today-btn">Aujourd'hui</button>
                        <button class="btn btn-outline-secondary" id="next-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="col-auto">
                    <h5 class="mb-0" id="calendar-date-header">...</h5>
                </div>

                <!-- Vues -->
                <div class="col-auto ms-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-dark" id="day-view">Jour</button>
                        <button class="btn btn-outline-dark active" id="week-view">Semaine</button>
                        <button class="btn btn-outline-dark" id="month-view">Mois</button>
                    </div>
                </div>

                <!-- Filtres -->
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" id="filterResidence">
                        <option value="">Toutes résidences</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" id="filterEmployee">
                        <option value="">Tous employés</option>
                        <?php foreach ($employees as $e): ?>
                        <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Calendrier -->
    <div class="card shadow">
        <div class="card-body p-0">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<!-- Modal Shift -->
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="shiftModalLabel"><i class="fas fa-calendar-plus me-2"></i>Nouveau shift</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="shiftId" value="">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shiftTitle" placeholder="Ex : Ménage étage 2">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Employé <span class="text-danger">*</span></label>
                        <select class="form-select" id="shiftEmployee">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($employees as $e): ?>
                            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?> (<?= $e['role'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Résidence <span class="text-danger">*</span></label>
                        <select class="form-select" id="shiftResidence">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($residences as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" id="shiftCategory">
                            <option value="">— Aucune —</option>
                            <?php foreach ($categories as $c): ?>
                            <option value="<?= $c['id'] ?>" style="background-color:<?= $c['bg_couleur'] ?>;color:<?= $c['couleur'] ?>">
                                <?= htmlspecialchars($c['nom']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Type</label>
                        <select class="form-select" id="shiftType">
                            <option value="travail">Travail</option>
                            <option value="reunion">Réunion</option>
                            <option value="formation">Formation</option>
                            <option value="conge">Congé</option>
                            <option value="maladie">Maladie</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label">Type d'heures</label>
                        <select class="form-select" id="shiftTypeHeures">
                            <option value="normales">Normales</option>
                            <option value="supplementaires">Supplémentaires</option>
                            <option value="nuit">De nuit</option>
                            <option value="dimanche">Dimanche</option>
                            <option value="ferie">Jour férié</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Début <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="shiftStart">
                    </div>
                    <div class="col-12 col-md-5">
                        <label class="form-label">Fin <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="shiftEnd">
                    </div>
                    <div class="col-12 col-md-2">
                        <label class="form-label">Journée</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="shiftAllDay">
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="shiftDescription" rows="2" placeholder="Détails du shift..."></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger d-none" id="deleteShiftBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveShiftBtn">
                    <i class="fas fa-save me-1"></i>Enregistrer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- TUI Calendar JS -->
<script src="<?= BASE_URL ?>/assets/js/tui-code-snippet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-time-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-date-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-calendar.js"></script>

<!-- Planning JS -->
<script src="<?= BASE_URL ?>/assets/js/planning-backend.js"></script>
<script src="<?= BASE_URL ?>/assets/js/planning-frontend.js"></script>
<script>
window.planningApp = new PlanningFrontend({
    baseUrl: '<?= BASE_URL ?>',
    residences: <?= json_encode($residences) ?>,
    employees: <?= json_encode($employees) ?>,
    userResidences: <?= json_encode($userResidences) ?>,
    categories: <?= json_encode($categories) ?>,
});
</script>
