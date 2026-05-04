<?php include ROOT_PATH . '/app/views/partials/tui_calendar_assets.php'; ?>
<style>
    #calendar { height: 720px; }
    .vue-tab.active { background: #0dcaf0; color: #fff; border-color: #0dcaf0; }
    .legende-bullet { display:inline-block; width:14px; height:14px; border-radius:3px; vertical-align:middle; margin-right:6px; }
</style>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-calendar-alt',   'text' => 'Planning',        'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-calendar-alt text-info me-2"></i>Planning Accueil</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <select id="residenceSelect" class="form-select form-select-sm">
                <?php foreach ($residences as $r): ?>
                <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <?php if ($isManager && $residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/animationForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-sm btn-info text-white">
                <i class="fas fa-plus me-1"></i>Animation
            </a>
            <a href="<?= BASE_URL ?>/accueil/reservationForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-sm btn-outline-info">
                <i class="fas fa-plus me-1"></i>Réservation
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php else: ?>

    <!-- Barre de contrôle -->
    <?php
    $tuiToolbarColor = 'info';
    $tuiToolbarExtra = '
        <div class="col-12 col-md-auto">
            <div class="btn-group btn-group-sm" role="group" aria-label="Vue">
                <button class="btn btn-outline-info vue-tab active" data-vue="residents"><i class="fas fa-users me-1"></i>Résidents</button>
                <button class="btn btn-outline-secondary vue-tab" data-vue="staff"><i class="fas fa-user-tie me-1"></i>Staff</button>
                <button class="btn btn-outline-dark vue-tab" data-vue="tout"><i class="fas fa-layer-group me-1"></i>Tout</button>
            </div>
        </div>';
    include ROOT_PATH . '/app/views/partials/tui_calendar_toolbar.php';
    ?>

    <!-- Légende -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 small">
            <strong>Légende :</strong>
            <span class="ms-3"><span class="legende-bullet" style="background:#0dcaf0"></span>🎵 Animation</span>
            <span class="ms-3"><span class="legende-bullet" style="background:#0aa2c0"></span>🏠 Salle</span>
            <span class="ms-3"><span class="legende-bullet" style="background:#198754"></span>🛠️ Équipement</span>
            <span class="ms-3"><span class="legende-bullet" style="background:#6610f2"></span>👤 Service personnel</span>
            <span class="ms-3"><span class="legende-bullet" style="background:#6c757d"></span>👷 Staff</span>
            <span class="ms-3"><span class="legende-bullet" style="background:#fd7e14"></span>🧳 Hôte temporaire</span>
        </div>
    </div>

    <!-- Calendrier -->
    <div class="card shadow"><div class="card-body p-0"><div id="calendar"></div></div></div>

    <?php endif; ?>
</div>

<script>
const BASE = '<?= BASE_URL ?>';
const AJAX = BASE + '/accueil/planningAjax';
const isManager = <?= $isManager ? 'true' : 'false' ?>;
let residenceId = <?= isset($residenceCourante) ? (int)$residenceCourante['id'] : 0 ?>;
let vueActive = 'residents';

const cal = new tui.Calendar('#calendar', TuiCalHelpers.defaultConfig({
    calendars: [
        { id: 'animation',     name: 'Animation',     color: '#fff', backgroundColor: '#0dcaf0', borderColor: '#0aa2c0' },
        { id: 'res_salle',     name: 'Salle',         color: '#fff', backgroundColor: '#0aa2c0', borderColor: '#055160' },
        { id: 'res_equipement',name: 'Équipement',    color: '#fff', backgroundColor: '#198754', borderColor: '#0f5132' },
        { id: 'res_service',   name: 'Service perso', color: '#fff', backgroundColor: '#6610f2', borderColor: '#3d0a8e' },
        { id: 'staff',         name: 'Staff',         color: '#fff', backgroundColor: '#6c757d', borderColor: '#495057' },
        { id: 'hote',          name: 'Hôte',          color: '#fff', backgroundColor: '#fd7e14', borderColor: '#a94d00' }
    ]
}));

function loadEvents() {
    if (!residenceId) return;
    const start = cal.getDateRangeStart().toDate();
    const end   = cal.getDateRangeEnd().toDate();
    const params = new URLSearchParams({
        residence_id: residenceId,
        vue: vueActive,
        start: TuiCalHelpers.formatDate(start) + ' 00:00:00',
        end:   TuiCalHelpers.formatDate(end)   + ' 23:59:59'
    });
    fetch(AJAX + '/getEvents?' + params)
        .then(r => r.json())
        .then(events => {
            cal.clear();
            if (Array.isArray(events)) {
                cal.createEvents(events.map(e => ({
                    id: e.id,
                    calendarId: e.calendarId,
                    category: e.category || 'time',
                    title: e.title,
                    body: e.body || '',
                    start: e.start,
                    end: e.end,
                    isReadOnly: e.isReadOnly !== false && !(e.raw && e.raw.type === 'animation' && isManager),
                    raw: e.raw || {}
                })));
            }
        })
        .catch(err => console.error('Erreur chargement events', err));
}

TuiCalHelpers.bindToolbar(cal, { headerId: 'cal-header', onChange: loadEvents });

document.querySelectorAll('.vue-tab').forEach(btn => btn.addEventListener('click', function() {
    document.querySelectorAll('.vue-tab').forEach(b => b.classList.remove('active'));
    this.classList.add('active');
    vueActive = this.dataset.vue;
    loadEvents();
}));

const sel = document.getElementById('residenceSelect');
if (sel) sel.addEventListener('change', function() { residenceId = parseInt(this.value, 10); loadEvents(); });

// Drag & drop : uniquement animations + manager
cal.on('beforeUpdateEvent', function(ev) {
    const event = ev.event;
    const changes = ev.changes;
    if (!isManager) { return; }
    if (!event.raw || event.raw.type !== 'animation') {
        alert('Seules les animations peuvent être déplacées.');
        return;
    }
    const newStart = changes.start ? changes.start.toDate() : event.start.toDate();
    const newEnd   = changes.end   ? changes.end.toDate()   : event.end.toDate();
    const rawId = String(event.id).replace('anim_', '');
    const meta = document.querySelector('meta[name="csrf-token"]');
    const body = new URLSearchParams({
        id: rawId,
        date_debut: TuiCalHelpers.formatDT(newStart),
        date_fin:   TuiCalHelpers.formatDT(newEnd),
        csrf_token: meta ? meta.content : ''
    });
    fetch(AJAX + '/moveAnimation', { method: 'POST', headers: TuiCalHelpers.formHeaders(), body: body.toString() })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                cal.updateEvent(event.id, event.calendarId, { start: newStart, end: newEnd });
            } else {
                alert(data.error || 'Erreur');
                loadEvents();
            }
        });
});

// Click → ouvrir détail
cal.on('clickEvent', function(ev) {
    const e = ev.event;
    if (!e.raw) return;
    const id = String(e.id);
    if (id.startsWith('anim_'))   window.location.href = BASE + '/accueil/animationShow/' + id.replace('anim_', '');
    else if (id.startsWith('res_')) window.location.href = BASE + '/accueil/reservationShow/' + id.replace('res_', '');
    else if (id.startsWith('hote_')) window.location.href = BASE + '/hote/show/' + id.replace('hote_', '');
});

loadEvents();
</script>
