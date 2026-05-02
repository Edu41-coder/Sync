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
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-calendar-alt',   'text' => 'Planning',        'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-warning me-2"></i>Planning maintenance</h1>
            <a href="<?= BASE_URL ?>/maintenance/interventionForm" class="btn btn-warning">
                <i class="fas fa-plus me-1"></i>Nouvelle intervention
            </a>
        </div>
    </div>

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
        <div class="col-12 col-lg-9 mb-4">
            <div class="card shadow"><div class="card-body p-0"><div id="calendar"></div></div></div>
        </div>

        <div class="col-12 col-lg-3 mb-4">
            <div class="card shadow mb-3">
                <div class="card-header bg-dark text-white">
                    <h6 class="mb-0"><i class="fas fa-palette me-2"></i>Spécialités</h6>
                </div>
                <div class="card-body small">
                    <?php foreach ($specialites as $s): ?>
                    <div class="mb-2 d-flex align-items-center">
                        <span class="legend-dot" style="background:<?= htmlspecialchars($s['couleur']) ?>"></span>
                        <i class="<?= htmlspecialchars($s['icone']) ?> me-1" style="color:<?= htmlspecialchars($s['couleur']) ?>"></i>
                        <?= htmlspecialchars($s['nom']) ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Aide</h6>
                </div>
                <div class="card-body small">
                    <ul class="mb-0 ps-3">
                        <li>Cliquez sur un événement pour voir les détails</li>
                        <?php if ($isManager): ?>
                        <li>Glissez-déposez pour replanifier (chef seulement)</li>
                        <?php endif; ?>
                        <li>Les interventions <em>terminées</em> ou <em>annulées</em> sont en lecture seule</li>
                        <li>1 couleur = 1 spécialité</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/assets/js/tui-code-snippet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-time-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-date-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-calendar.js"></script>

<script>
const BASE = '<?= BASE_URL ?>';
const SPECIALITES = <?= json_encode($specialites, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const IS_MANAGER = <?= $isManager ? 'true' : 'false' ?>;

const calendarData = SPECIALITES.map(s => ({
    id: s.slug,
    name: s.nom,
    color: '#fff',
    bgColor: s.couleur,
    borderColor: s.couleur,
}));

let calendar;

document.addEventListener('DOMContentLoaded', function() {
    calendar = new tui.Calendar('#calendar', {
        defaultView: 'month',
        taskView: false,
        scheduleView: ['time', 'allday'],
        useCreationPopup: false,
        useDetailPopup: false,
        calendars: calendarData,
        month: { startDayOfWeek: 1, daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'] },
        week:  { startDayOfWeek: 1, hourStart: 7, hourEnd: 20, daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'] },
        template: {
            allday: s => s.title,
            time:   s => `<div style="padding:2px 4px;font-size:11px"><strong>${s.title}</strong></div>`,
        },
    });

    document.getElementById('prev-btn').addEventListener('click',  () => { calendar.prev();  updateHeader(); reloadEvents(); });
    document.getElementById('next-btn').addEventListener('click',  () => { calendar.next();  updateHeader(); reloadEvents(); });
    document.getElementById('today-btn').addEventListener('click', () => { calendar.today(); updateHeader(); reloadEvents(); });
    document.getElementById('day-view').addEventListener('click',  () => changeView('day'));
    document.getElementById('week-view').addEventListener('click', () => changeView('week'));
    document.getElementById('month-view').addEventListener('click',() => changeView('month'));

    // Drag & drop : manager uniquement
    calendar.on('beforeUpdateSchedule', e => {
        const { schedule, changes } = e;
        if (!IS_MANAGER) return;
        if (schedule.isReadOnly) return;
        if (changes && changes.start) {
            calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
            fetch(BASE + '/maintenance/planningAjax/move', {
                method: 'POST', headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    id: schedule.id,
                    start: formatISO(changes.start)
                })
            });
        }
    });

    // Click → ouvre la fiche détaillée
    calendar.on('clickSchedule', e => {
        if (e.schedule && e.schedule.id) {
            window.location.href = BASE + '/maintenance/interventionShow/' + e.schedule.id;
        }
    });

    updateHeader();
    changeView('month');
});

function changeView(v) {
    calendar.changeView(v);
    ['day','week','month'].forEach(x => document.getElementById(x+'-view').classList.toggle('active', x === v));
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
    let end   = re._date ? new Date(re._date) : new Date(re);
    end.setDate(end.getDate() + 1);

    fetch(BASE + '/maintenance/planningAjax/getEvents?start=' + fmt(start) + '&end=' + fmt(end))
        .then(r => r.json())
        .then(events => {
            calendar.clear();
            if (Array.isArray(events) && events.length) {
                calendar.createSchedules(events.map(ev => ({
                    id:          ev.id.toString(),
                    calendarId:  ev.calendarId,
                    title:       ev.title,
                    start:       new Date(ev.start),
                    end:         new Date(ev.end),
                    isAllDay:    ev.isAllDay,
                    category:    ev.isAllDay ? 'allday' : 'time',
                    isReadOnly:  ev.isReadOnly || false,
                    bgColor:     ev.categoryColor,
                    borderColor: ev.calendarColor,
                    color:       ev.categoryTextColor || '#333',
                    raw:         ev.raw || {},
                })));
                calendar.render();
            }
        })
        .catch(err => console.error('Erreur chargement events:', err));
}

function fmt(d) { return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0'); }
function formatISO(d) {
    if (typeof d === 'string') return d;
    if (d._date) d = d._date;
    if (!(d instanceof Date)) d = new Date(d);
    return d.getFullYear()+'-'+String(d.getMonth()+1).padStart(2,'0')+'-'+String(d.getDate()).padStart(2,'0')+'T'
         + String(d.getHours()).padStart(2,'0')+':'+String(d.getMinutes()).padStart(2,'0')+':00';
}
</script>
