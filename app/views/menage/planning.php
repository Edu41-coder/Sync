<?php $title = "Planning Ménage"; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-calendar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-date-picker.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-time-picker.css">
<style>#calendar { height: 700px; } .btn-group .btn.active { font-weight: bold; }</style>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-calendar-alt', 'text' => 'Planning', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-calendar-alt text-info"></i> Planning Ménage</h1>
            <?php if ($canManage): ?>
            <button class="btn btn-info text-white" onclick="openCreateModal(new Date(), new Date(Date.now()+3600000), false)">
                <i class="fas fa-plus me-1"></i>Nouveau shift
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
                <div class="col-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary" id="prev-btn"><i class="fas fa-chevron-left"></i></button>
                        <button class="btn btn-outline-info" id="today-btn">Aujourd'hui</button>
                        <button class="btn btn-outline-secondary" id="next-btn"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
                <div class="col-auto"><h5 class="mb-0" id="calendar-date-header">...</h5></div>
                <div class="col-auto ms-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-dark" id="day-view">Jour</button>
                        <button class="btn btn-outline-dark active" id="week-view">Semaine</button>
                        <button class="btn btn-outline-dark" id="month-view">Mois</button>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" id="filterResidence">
                        <option value="0">Toutes résidences</option>
                        <?php foreach ($residences as $r): ?>
                        <option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <select class="form-select form-select-sm" id="filterEmployee">
                        <option value="0">Tout le staff</option>
                        <?php foreach ($staff as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> (<?= $s['role_nom'] ?? $s['role'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow"><div class="card-body p-0"><div id="calendar"></div></div></div>
</div>

<?php if ($canManage): ?>
<!-- Modal Shift -->
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="shiftModalLabel"><i class="fas fa-calendar-plus me-2"></i>Nouveau shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="shiftId" value="">
                <div class="row g-3">
                    <div class="col-12"><label class="form-label">Titre <span class="text-danger">*</span></label><input type="text" class="form-control" id="shiftTitle" placeholder="Ex : Ménage étage 2"></div>
                    <div class="col-md-6"><label class="form-label">Employé <span class="text-danger">*</span></label>
                        <select class="form-select" id="shiftEmployee"><option value="">— Sélectionner —</option>
                            <?php foreach ($staff as $s): ?><option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> (<?= $s['role_nom'] ?? $s['role'] ?>)</option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Résidence <span class="text-danger">*</span></label>
                        <select class="form-select" id="shiftResidence"><option value="">— Sélectionner —</option>
                            <?php foreach ($residences as $r): ?><option value="<?= $r['id'] ?>"><?= htmlspecialchars($r['nom']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Catégorie</label>
                        <select class="form-select" id="shiftCategory"><option value="">— Aucune —</option>
                            <?php foreach ($categories as $c): ?><option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nom']) ?></option><?php endforeach; ?>
                        </select></div>
                    <div class="col-md-6"><label class="form-label">Type d'heures</label>
                        <select class="form-select" id="shiftTypeHeures">
                            <option value="normales">Normales</option><option value="supplementaires">Supplémentaires</option>
                            <option value="nuit">De nuit</option><option value="dimanche">Dimanche</option><option value="ferie">Jour férié</option>
                        </select></div>
                    <div class="col-md-5"><label class="form-label">Début</label><input type="datetime-local" class="form-control" id="shiftStart"></div>
                    <div class="col-md-5"><label class="form-label">Fin</label><input type="datetime-local" class="form-control" id="shiftEnd"></div>
                    <div class="col-md-2 d-flex align-items-end"><div class="form-check"><input type="checkbox" class="form-check-input" id="shiftAllDay"><label class="form-check-label">Journée</label></div></div>
                    <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" id="shiftDescription" rows="2"></textarea></div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-outline-danger d-none" id="btnDeleteShift" onclick="deleteShift()"><i class="fas fa-trash me-1"></i>Supprimer</button>
                <button class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button class="btn btn-info text-white" id="btnSaveShift" onclick="saveShift()"><i class="fas fa-save me-1"></i>Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="<?= BASE_URL ?>/assets/js/tui-code-snippet.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-time-picker.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-date-picker.min.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-calendar.min.js"></script>
<script>
const BASE = '<?= BASE_URL ?>';
const AJAX_URL = BASE + '/menage/planningAjax';
const canManage = <?= $canManage ? 'true' : 'false' ?>;
const currentUserId = <?= $userId ?>;

function jsonHeaders() {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return { 'Content-Type': 'application/json', 'X-CSRF-Token': meta ? meta.content : '' };
}

const cal = new tui.Calendar('#calendar', {
    defaultView: 'week',
    taskView: false,
    useDetailPopup: !canManage,
    useCreationPopup: false,
    week: { startDayOfWeek: 1, hourStart: 6, hourEnd: 22 },
    month: { startDayOfWeek: 1 },
    template: {
        time: function(schedule) { return '<span style="font-size:11px">' + schedule.title + '</span>'; }
    }
});

function formatDate(d) { return d.toISOString().slice(0, 10); }
function formatDT(d) { return d.toISOString().slice(0, 16); }
function updateHeader() {
    const d = cal.getDate().toDate();
    document.getElementById('calendar-date-header').textContent = d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' });
}

function loadEvents() {
    const range = cal.getDateRangeStart().toDate();
    const rangeEnd = cal.getDateRangeEnd().toDate();
    const params = new URLSearchParams({
        start: formatDate(range), end: formatDate(rangeEnd),
        residence_id: document.getElementById('filterResidence').value,
        user_id: document.getElementById('filterEmployee').value
    });
    fetch(AJAX_URL + '/getEvents?' + params).then(r => r.json()).then(events => {
        cal.clear();
        events.forEach(e => cal.createEvents([{
            id: e.id, calendarId: String(e.calendarId), title: e.title,
            start: e.start, end: e.end, isAllday: e.isAllDay,
            backgroundColor: e.categoryColor, color: e.calendarColor, borderColor: e.calendarColor,
            isReadOnly: !canManage, raw: e.raw
        }]));
    });
}

document.getElementById('prev-btn').onclick = () => { cal.prev(); loadEvents(); updateHeader(); };
document.getElementById('next-btn').onclick = () => { cal.next(); loadEvents(); updateHeader(); };
document.getElementById('today-btn').onclick = () => { cal.today(); loadEvents(); updateHeader(); };
document.getElementById('day-view').onclick = function() { cal.changeView('day'); setActiveView(this); loadEvents(); updateHeader(); };
document.getElementById('week-view').onclick = function() { cal.changeView('week'); setActiveView(this); loadEvents(); updateHeader(); };
document.getElementById('month-view').onclick = function() { cal.changeView('month'); setActiveView(this); loadEvents(); updateHeader(); };
function setActiveView(btn) { document.querySelectorAll('#day-view,#week-view,#month-view').forEach(b => b.classList.remove('active')); btn.classList.add('active'); }
document.getElementById('filterResidence').onchange = loadEvents;
document.getElementById('filterEmployee').onchange = loadEvents;

<?php if ($canManage): ?>
function openCreateModal(start, end, isAllDay) {
    document.getElementById('shiftId').value = '';
    document.getElementById('shiftTitle').value = '';
    document.getElementById('shiftEmployee').value = '';
    document.getElementById('shiftResidence').value = '';
    document.getElementById('shiftCategory').value = '';
    document.getElementById('shiftTypeHeures').value = 'normales';
    document.getElementById('shiftStart').value = formatDT(start);
    document.getElementById('shiftEnd').value = formatDT(end);
    document.getElementById('shiftAllDay').checked = isAllDay;
    document.getElementById('shiftDescription').value = '';
    document.getElementById('shiftModalLabel').innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Nouveau shift';
    document.getElementById('btnDeleteShift').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('shiftModal')).show();
}

cal.on('beforeCreateEvent', e => openCreateModal(e.start.toDate(), e.end.toDate(), e.isAllday));
cal.on('clickEvent', ({event}) => {
    if (!canManage) return;
    fetch(AJAX_URL + '/getEvent?id=' + event.id).then(r => r.json()).then(data => {
        if (!data.success) return;
        const ev = data.event;
        document.getElementById('shiftId').value = ev.id;
        document.getElementById('shiftTitle').value = ev.title;
        document.getElementById('shiftEmployee').value = ev.raw.userId;
        document.getElementById('shiftResidence').value = ev.raw.residenceId;
        document.getElementById('shiftCategory').value = ev.raw.categoryId || '';
        document.getElementById('shiftTypeHeures').value = ev.raw.typeHeures || 'normales';
        document.getElementById('shiftStart').value = ev.start.replace(' ', 'T').slice(0, 16);
        document.getElementById('shiftEnd').value = ev.end.replace(' ', 'T').slice(0, 16);
        document.getElementById('shiftAllDay').checked = ev.isAllDay;
        document.getElementById('shiftDescription').value = ev.raw.description || '';
        document.getElementById('shiftModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier shift';
        document.getElementById('btnDeleteShift').classList.remove('d-none');
        new bootstrap.Modal(document.getElementById('shiftModal')).show();
    });
});
cal.on('beforeUpdateEvent', ({event, changes}) => {
    fetch(AJAX_URL + '/move', { method: 'POST', headers: jsonHeaders(),
        body: JSON.stringify({ id: event.id, start: formatDT(changes.start?.toDate() || event.start.toDate()), end: formatDT(changes.end?.toDate() || event.end.toDate()) })
    }).then(() => loadEvents());
});

function saveShift() {
    const body = {
        id: document.getElementById('shiftId').value || null,
        title: document.getElementById('shiftTitle').value,
        userId: parseInt(document.getElementById('shiftEmployee').value),
        residenceId: parseInt(document.getElementById('shiftResidence').value),
        categoryId: parseInt(document.getElementById('shiftCategory').value) || null,
        typeHeures: document.getElementById('shiftTypeHeures').value,
        start: document.getElementById('shiftStart').value.replace('T', ' '),
        end: document.getElementById('shiftEnd').value.replace('T', ' '),
        isAllDay: document.getElementById('shiftAllDay').checked,
        typeShift: 'travail',
        description: document.getElementById('shiftDescription').value
    };
    fetch(AJAX_URL + '/save', { method: 'POST', headers: jsonHeaders(), body: JSON.stringify(body) })
        .then(r => r.json()).then(data => { bootstrap.Modal.getInstance(document.getElementById('shiftModal')).hide(); loadEvents(); });
}

function deleteShift() {
    if (!confirm('Supprimer ce shift ?')) return;
    const id = document.getElementById('shiftId').value;
    fetch(AJAX_URL + '/delete', { method: 'POST', headers: jsonHeaders(), body: JSON.stringify({ id: parseInt(id) }) })
        .then(() => { bootstrap.Modal.getInstance(document.getElementById('shiftModal')).hide(); loadEvents(); });
}
<?php endif; ?>

loadEvents();
updateHeader();
</script>
