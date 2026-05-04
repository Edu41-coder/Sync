<?php $title = "Planning Restauration"; ?>

<?php include ROOT_PATH . '/app/views/partials/tui_calendar_assets.php'; ?>
<style>
    #calendar { height: 700px; }
    .btn-group .btn.active { font-weight: bold; }
</style>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-calendar-alt', 'text' => 'Planning', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
$canManage = in_array($userRole, ['admin', 'restauration_manager']);
?>

<div class="container-fluid py-4">
    <!-- En-tête -->
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-calendar-alt text-warning"></i> Planning Restauration</h1>
            <?php if ($canManage): ?>
            <button class="btn btn-warning text-dark" onclick="openCreateModal(new Date(), new Date(Date.now()+3600000), false)">
                <i class="fas fa-plus me-1"></i>Nouveau shift
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Navigation + Filtres -->
    <?php
    $tuiToolbarColor = 'warning';
    ob_start();
    ?>
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
    <?php
    $tuiToolbarExtra = ob_get_clean();
    include ROOT_PATH . '/app/views/partials/tui_calendar_toolbar.php';
    ?>

    <!-- Calendrier -->
    <div class="card shadow">
        <div class="card-body p-0">
            <div id="calendar"></div>
        </div>
    </div>
</div>

<?php if ($canManage): ?>
<!-- Modal Shift -->
<div class="modal fade" id="shiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning text-dark">
                <h5 class="modal-title" id="shiftModalLabel"><i class="fas fa-calendar-plus me-2"></i>Nouveau shift</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="shiftId" value="">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shiftTitle" placeholder="Ex : Service midi">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Employé <span class="text-danger">*</span></label>
                        <select class="form-select" id="shiftEmployee">
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> (<?= $s['role_nom'] ?? $s['role'] ?>)</option>
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
                            <option value="<?= $c['id'] ?>" style="background-color:<?= $c['bg_couleur'] ?>"><?= htmlspecialchars($c['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
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
                        <textarea class="form-control" id="shiftDescription" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger d-none" id="deleteShiftBtn"><i class="fas fa-trash me-1"></i>Supprimer</button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-warning text-dark" id="saveShiftBtn"><i class="fas fa-save me-1"></i>Enregistrer</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
const BASE = '<?= BASE_URL ?>';
const CAN_MANAGE = <?= $canManage ? 'true' : 'false' ?>;
let calendar, modal;
const jsonHeaders = TuiCalHelpers.jsonHeaders;

document.addEventListener('DOMContentLoaded', function() {
    calendar = new tui.Calendar('#calendar', {
        defaultView: 'week',
        taskView: false,
        scheduleView: ['time', 'allday'],
        useCreationPopup: false,
        useDetailPopup: false,
        calendars: [],
        week: {
            hourStart: 6, hourEnd: 23, startDayOfWeek: 1,
            daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
        },
        month: {
            startDayOfWeek: 1,
            daynames: ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
        },
        template: {
            allday: s => s.title,
            alldayTitle: () => '<div style="text-align:center">Journée</div>',
            time: s => `<div style="padding:2px 4px;font-size:11px"><strong>${s.title}</strong></div>`,
        },
    });

    if (CAN_MANAGE) {
        modal = new bootstrap.Modal(document.getElementById('shiftModal'));

        // Création par clic
        calendar.on('beforeCreateSchedule', e => {
            const start = new Date(e.start);
            const end = new Date(e.end || new Date(start.getTime() + 3600000));
            const isAllDay = calendar.getViewName() === 'month' || e.isAllDay;
            openCreateModal(start, end, isAllDay);
        });

        // Drag & drop
        calendar.on('beforeUpdateSchedule', e => {
            const { schedule, changes } = e;
            if (changes && (changes.start || changes.end)) {
                calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
                fetch(BASE + '/restauration/planningAjax/move', {
                    method: 'POST', headers: jsonHeaders(),
                    body: JSON.stringify({ id: schedule.id, start: formatISO(changes.start || schedule.start), end: formatISO(changes.end || schedule.end) })
                });
            }
        });

        // Double-clic pour éditer
        document.getElementById('calendar').addEventListener('dblclick', e => {
            const el = e.target.closest('.tui-full-calendar-time-schedule') || e.target.closest('.tui-full-calendar-weekday-schedule');
            if (!el) return;
            const id = el.getAttribute('data-schedule-id');
            if (!id) return;
            e.preventDefault();
            fetch(BASE + '/restauration/planningAjax/getEvent?id=' + id)
                .then(r => r.json())
                .then(data => { if (data.success) openEditModal(data.event); });
        }, true);

        // Save
        document.getElementById('saveShiftBtn').addEventListener('click', saveShift);
        document.getElementById('deleteShiftBtn').addEventListener('click', () => {
            if (!confirm('Supprimer ce shift ?')) return;
            const id = document.getElementById('shiftId').value;
            fetch(BASE + '/restauration/planningAjax/delete', {
                method: 'POST', headers: jsonHeaders(),
                body: JSON.stringify({ id: parseInt(id) })
            }).then(() => { modal.hide(); reloadEvents(); });
        });
    }

    // Clic simple = rien
    calendar.on('clickSchedule', () => {});

    // Navigation
    document.getElementById('prev-btn').addEventListener('click', () => { calendar.prev(); updateHeader(); reloadEvents(); });
    document.getElementById('next-btn').addEventListener('click', () => { calendar.next(); updateHeader(); reloadEvents(); });
    document.getElementById('today-btn').addEventListener('click', () => { calendar.today(); updateHeader(); reloadEvents(); });
    document.getElementById('day-view').addEventListener('click', () => changeView('day'));
    document.getElementById('week-view').addEventListener('click', () => changeView('week'));
    document.getElementById('month-view').addEventListener('click', () => changeView('month'));
    document.getElementById('filterResidence').addEventListener('change', reloadEvents);
    document.getElementById('filterEmployee').addEventListener('change', reloadEvents);

    updateHeader();
    changeView('week');
});

function changeView(v) {
    calendar.changeView(v);
    ['day','week','month'].forEach(x => document.getElementById(x+'-view').classList.toggle('active', x===v));
    updateHeader();
    reloadEvents();
}

function updateHeader() {
    const d = calendar.getDate();
    const months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
    document.getElementById('cal-header').textContent = months[d.getMonth()] + ' ' + d.getFullYear();
}

function reloadEvents() {
    const rs = calendar.getDateRangeStart(), re = calendar.getDateRangeEnd();
    let start = rs._date ? new Date(rs._date) : new Date(rs);
    let end = re._date ? new Date(re._date) : new Date(re);
    end.setDate(end.getDate() + 1);

    const residenceId = document.getElementById('filterResidence').value;
    const userId = document.getElementById('filterEmployee').value;

    const params = new URLSearchParams({
        start: fmt(start), end: fmt(end),
        residence_id: residenceId, user_id: userId
    });

    fetch(BASE + '/restauration/planningAjax/getEvents?' + params)
        .then(r => r.json())
        .then(events => {
            calendar.clear();
            if (events.length) {
                calendar.createSchedules(events.map(ev => {
                    const isAllDay = ev.isAllDay === true || ev.isAllDay === 1;
                    return {
                        id: ev.id.toString(),
                        calendarId: ev.calendarId?.toString(),
                        title: ev.title,
                        start: new Date(ev.start),
                        end: new Date(ev.end),
                        isAllDay: isAllDay,
                        category: isAllDay ? 'allday' : 'time',
                        bgColor: ev.categoryColor || '#fff3cd',
                        borderColor: ev.calendarColor || '#ffc107',
                        color: ev.categoryTextColor || '#333',
                        raw: ev.raw || {},
                    };
                }));
                calendar.render();
            }
        });
}

function openCreateModal(start, end, isAllDay) {
    document.getElementById('shiftModalLabel').innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Nouveau shift';
    document.getElementById('deleteShiftBtn').classList.add('d-none');
    document.getElementById('shiftId').value = '';
    document.getElementById('shiftTitle').value = '';
    document.getElementById('shiftEmployee').value = '';
    document.getElementById('shiftResidence').value = '';
    document.getElementById('shiftCategory').value = '';
    document.getElementById('shiftTypeHeures').value = 'normales';
    document.getElementById('shiftAllDay').checked = isAllDay;
    document.getElementById('shiftDescription').value = '';
    if (isAllDay) { start.setHours(0,0,0,0); end.setHours(23,59,0,0); }
    document.getElementById('shiftStart').value = formatInput(start);
    document.getElementById('shiftEnd').value = formatInput(end);
    modal.show();
}

function openEditModal(eventData) {
    document.getElementById('shiftModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier le shift';
    document.getElementById('deleteShiftBtn').classList.remove('d-none');
    document.getElementById('shiftId').value = eventData.id;
    document.getElementById('shiftTitle').value = eventData.title;
    document.getElementById('shiftEmployee').value = eventData.raw?.userId || '';
    document.getElementById('shiftResidence').value = eventData.raw?.residenceId || eventData.calendarId || '';
    document.getElementById('shiftCategory').value = eventData.raw?.categoryId || '';
    document.getElementById('shiftTypeHeures').value = eventData.raw?.typeHeures || 'normales';
    document.getElementById('shiftAllDay').checked = eventData.isAllDay;
    document.getElementById('shiftStart').value = formatInput(eventData.start);
    document.getElementById('shiftEnd').value = formatInput(eventData.end);
    document.getElementById('shiftDescription').value = eventData.raw?.description || '';
    modal.show();
}

function saveShift() {
    const data = {
        id: document.getElementById('shiftId').value || null,
        title: document.getElementById('shiftTitle').value,
        userId: document.getElementById('shiftEmployee').value,
        residenceId: document.getElementById('shiftResidence').value,
        categoryId: document.getElementById('shiftCategory').value || null,
        typeShift: 'travail',
        typeHeures: document.getElementById('shiftTypeHeures').value,
        start: document.getElementById('shiftStart').value,
        end: document.getElementById('shiftEnd').value,
        isAllDay: document.getElementById('shiftAllDay').checked,
        description: document.getElementById('shiftDescription').value,
    };
    if (!data.title || !data.userId || !data.residenceId || !data.start || !data.end) {
        alert('Remplissez les champs obligatoires.');
        return;
    }
    fetch(BASE + '/restauration/planningAjax/save', {
        method: 'POST', headers: jsonHeaders(),
        body: JSON.stringify(data)
    }).then(r => r.json()).then(resp => {
        if (resp.success) { modal.hide(); reloadEvents(); }
        else alert(resp.message);
    });
}

function fmt(d) { return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function formatISO(d) {
    if (typeof d === 'string') return d;
    if (d._date) d = d._date;
    if (!(d instanceof Date)) d = new Date(d);
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')+'T'+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')+':00';
}
function formatInput(d) {
    if (typeof d === 'string') return d.includes('T') ? d.substring(0,16) : d;
    if (d._date) d = d._date;
    if (!(d instanceof Date)) d = new Date(d);
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')+'T'+String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0');
}
</script>
