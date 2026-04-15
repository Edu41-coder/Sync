<?php $title = "Mon Calendrier"; ?>

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-calendar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-date-picker.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-time-picker.css">
<style>
    #calendar { height: 650px; }
    .btn-group .btn.active { font-weight: bold; }
    .legend-dot { display:inline-block; width:12px; height:12px; border-radius:50%; margin-right:6px; }
</style>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar', 'text' => 'Mon Calendrier', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <?php if (!$proprietaire): ?>
    <div class="alert alert-warning">Aucun profil propriétaire associé.</div>
    <?php else: ?>

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3"><i class="fas fa-calendar-alt text-dark"></i> Mon Calendrier</h1>
            <button class="btn btn-danger" onclick="openCreateModal(new Date(), new Date(Date.now()+3600000), false)">
                <i class="fas fa-plus me-1"></i>Nouvel événement
            </button>
        </div>
    </div>

    <!-- Navigation + Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row g-2 align-items-center">
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
                <div class="col-auto ms-auto">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-dark" id="day-view">Jour</button>
                        <button class="btn btn-outline-dark" id="week-view">Semaine</button>
                        <button class="btn btn-outline-dark active" id="month-view">Mois</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Calendrier -->
        <div class="col-12 col-lg-9 mb-4">
            <div class="card shadow">
                <div class="card-body p-0">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

        <!-- Sidebar légende -->
        <div class="col-12 col-lg-3 mb-4">
            <div class="card shadow mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-palette me-2"></i>Légende</h6>
                </div>
                <div class="card-body small">
                    <?php foreach ($categories as $cat): ?>
                    <div class="mb-2 d-flex align-items-center">
                        <span class="legend-dot" style="background:<?= $cat['couleur'] ?>"></span>
                        <span>
                            <i class="<?= $cat['icone'] ?> me-1" style="color:<?= $cat['couleur'] ?>"></i>
                            <?= htmlspecialchars($cat['nom']) ?>
                            <?php if ($cat['auto_genere']): ?><small class="text-muted">(auto)</small><?php endif; ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0">
                        <li><strong>Loyers</strong> et <strong>échéances</strong> sont générés automatiquement</li>
                        <li>Cliquez sur une date pour créer un événement</li>
                        <li>Double-cliquez sur un événement pour le modifier</li>
                        <li>Les événements auto ne sont pas modifiables</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <?php endif; ?>
</div>

<!-- Modal événement -->
<div class="modal fade" id="eventModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:linear-gradient(135deg,#fd7e14,#e65100)">
                <h5 class="modal-title text-white" id="eventModalLabel"><i class="fas fa-calendar-plus me-2"></i>Nouvel événement</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="eventId" value="">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label">Titre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="eventTitle" placeholder="Ex: RDV gestionnaire">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Catégorie</label>
                        <select class="form-select" id="eventCategory">
                            <?php foreach ($categories as $cat): ?>
                            <?php if (!$cat['auto_genere']): ?>
                            <option value="<?= $cat['id'] ?>" style="color:<?= $cat['couleur'] ?>"><?= htmlspecialchars($cat['nom']) ?></option>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Journée entière</label>
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" id="eventAllDay">
                        </div>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Début <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="eventStart">
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Fin <span class="text-danger">*</span></label>
                        <input type="datetime-local" class="form-control" id="eventEnd">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" id="eventDescription" rows="2"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger d-none" id="deleteEventBtn">
                    <i class="fas fa-trash me-1"></i>Supprimer
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="saveEventBtn">
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

<script>
const BASE = '<?= BASE_URL ?>';
const CATEGORIES = <?= json_encode($categories) ?>;

// Calendriers TUI = 1 par catégorie
const calendarData = CATEGORIES.map(c => ({
    id: c.slug,
    name: c.nom,
    color: '#fff',
    bgColor: c.couleur,
    borderColor: c.couleur,
}));

let calendar, modal;

document.addEventListener('DOMContentLoaded', function() {
    calendar = new tui.Calendar('#calendar', {
        defaultView: 'month',
        taskView: false,
        scheduleView: ['time', 'allday'],
        useCreationPopup: false,
        useDetailPopup: false,
        calendars: calendarData,
        month: { startDayOfWeek: 1, daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'] },
        week: { startDayOfWeek: 1, hourStart: 7, hourEnd: 20, daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'] },
        template: {
            allday: s => s.title,
            alldayTitle: () => '<div style="text-align:center">Journée</div>',
            time: s => `<div style="padding:2px 4px;font-size:11px"><strong>${s.title}</strong></div>`,
        },
    });

    modal = new bootstrap.Modal(document.getElementById('eventModal'));

    // Navigation
    document.getElementById('prev-btn').addEventListener('click', () => { calendar.prev(); updateHeader(); reloadEvents(); });
    document.getElementById('next-btn').addEventListener('click', () => { calendar.next(); updateHeader(); reloadEvents(); });
    document.getElementById('today-btn').addEventListener('click', () => { calendar.today(); updateHeader(); reloadEvents(); });
    document.getElementById('day-view').addEventListener('click', () => changeView('day'));
    document.getElementById('week-view').addEventListener('click', () => changeView('week'));
    document.getElementById('month-view').addEventListener('click', () => changeView('month'));

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
        if (schedule.raw?.autoGenere) return; // Auto = pas modifiable
        if (changes && (changes.start || changes.end)) {
            calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
            fetch(BASE + '/coproprietaire/calendarAjax/move', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ id: schedule.id, start: formatISO(changes.start || schedule.start), end: formatISO(changes.end || schedule.end) })
            });
        }
    });

    // Clic simple = rien
    calendar.on('clickSchedule', () => {});

    // Double-clic = éditer
    document.getElementById('calendar').addEventListener('dblclick', e => {
        const el = e.target.closest('.tui-full-calendar-time-schedule') || e.target.closest('.tui-full-calendar-weekday-schedule');
        if (!el) return;
        const id = el.getAttribute('data-schedule-id');
        if (!id) return;

        // Trouver l'événement
        for (const cat of CATEGORIES) {
            const sched = calendar.getSchedule(id, cat.slug);
            if (sched) {
                if (sched.raw?.autoGenere) return; // Auto = pas modifiable
                openEditModal(sched);
                return;
            }
        }
    }, true);

    // Save
    document.getElementById('saveEventBtn').addEventListener('click', saveEvent);
    // Delete
    document.getElementById('deleteEventBtn').addEventListener('click', () => {
        if (!confirm('Supprimer cet événement ?')) return;
        const id = document.getElementById('eventId').value;
        fetch(BASE + '/coproprietaire/calendarAjax/delete', {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ id: parseInt(id) })
        }).then(() => { modal.hide(); reloadEvents(); });
    });

    updateHeader();
    changeView('month');
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
    document.getElementById('calendar-date-header').textContent = months[d.getMonth()] + ' ' + d.getFullYear();
}

function reloadEvents() {
    const rs = calendar.getDateRangeStart(), re = calendar.getDateRangeEnd();
    let start = rs._date ? new Date(rs._date) : new Date(rs);
    let end = re._date ? new Date(re._date) : new Date(re);
    end.setDate(end.getDate() + 1);

    fetch(BASE + '/coproprietaire/calendarAjax/getEvents?start=' + fmt(start) + '&end=' + fmt(end))
        .then(r => r.json())
        .then(events => {
            calendar.clear();
            if (events.length) {
                calendar.createSchedules(events.map(ev => {
                    const isAllDay = ev.isAllDay === true || ev.isAllDay === 1;
                    return {
                        id: ev.id.toString(),
                        calendarId: ev.calendarId,
                        title: ev.title,
                        start: new Date(ev.start),
                        end: new Date(ev.end),
                        isAllDay: isAllDay,
                        category: isAllDay ? 'allday' : 'time',
                        isReadOnly: ev.isReadOnly || false,
                        bgColor: ev.categoryColor,
                        borderColor: ev.calendarColor,
                        color: ev.categoryTextColor || '#333',
                        raw: ev.raw || {},
                    };
                }));
                calendar.render();
            }
        });
}

function openCreateModal(start, end, isAllDay) {
    document.getElementById('eventModalLabel').innerHTML = '<i class="fas fa-calendar-plus me-2"></i>Nouvel événement';
    document.getElementById('deleteEventBtn').classList.add('d-none');
    document.getElementById('eventId').value = '';
    document.getElementById('eventTitle').value = '';
    document.getElementById('eventDescription').value = '';
    document.getElementById('eventAllDay').checked = isAllDay;
    if (isAllDay) { start.setHours(0,0,0,0); end.setHours(23,59,0,0); }
    document.getElementById('eventStart').value = formatInput(start);
    document.getElementById('eventEnd').value = formatInput(end);
    modal.show();
}

function openEditModal(sched) {
    document.getElementById('eventModalLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Modifier';
    document.getElementById('deleteEventBtn').classList.remove('d-none');
    document.getElementById('eventId').value = sched.id;
    document.getElementById('eventTitle').value = sched.title;
    document.getElementById('eventCategory').value = sched.raw?.categoryId || '';
    document.getElementById('eventAllDay').checked = sched.isAllDay;
    document.getElementById('eventStart').value = formatInput(sched.start);
    document.getElementById('eventEnd').value = formatInput(sched.end);
    document.getElementById('eventDescription').value = sched.raw?.description || '';
    modal.show();
}

function saveEvent() {
    const data = {
        id: document.getElementById('eventId').value || null,
        title: document.getElementById('eventTitle').value,
        categoryId: document.getElementById('eventCategory').value,
        start: document.getElementById('eventStart').value,
        end: document.getElementById('eventEnd').value,
        isAllDay: document.getElementById('eventAllDay').checked,
        description: document.getElementById('eventDescription').value,
    };
    if (!data.title || !data.start || !data.end) { alert('Titre et dates requis'); return; }

    fetch(BASE + '/coproprietaire/calendarAjax/save', {
        method: 'POST', headers: {'Content-Type':'application/json'},
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
