/**
 * Planning Frontend - Gestion de l'interface TUI Calendar pour le planning staff
 * Adapté de CalendarFrontend, simplifié pour les shifts Domitys
 */
class PlanningFrontend {
    constructor(config) {
        this.config = config;
        this.backend = new PlanningBackend(config.baseUrl);
        this.calendar = null;
        this.modal = null;
        // Données pour filtrage croisé employé ↔ résidence
        this.employees = config.employees || [];
        this.residences = config.residences || [];
        this.userResidences = config.userResidences || [];

        document.addEventListener('DOMContentLoaded', () => this.initialize());
    }

    initialize() {
        if (typeof tui === 'undefined' || typeof tui.Calendar === 'undefined') {
            console.error('TUI Calendar non chargé');
            return;
        }

        // Préparer les données calendriers (1 par résidence, avec couleur catégorie)
        const calendarData = this.config.residences.map((r, i) => {
            const colors = ['#e91e63','#3f51b5','#4caf50','#ff9800','#9c27b0','#00bcd4','#795548','#607d8b'];
            return {
                id: r.id.toString(),
                name: r.nom,
                color: '#fff',
                bgColor: colors[i % colors.length],
                borderColor: colors[i % colors.length],
            };
        });

        this.calendar = new tui.Calendar('#calendar', {
            defaultView: 'week',
            taskView: false,
            scheduleView: ['time', 'allday'],
            useCreationPopup: false,
            useDetailPopup: false,
            calendars: calendarData,
            week: {
                hourStart: 6,
                hourEnd: 22,
                startDayOfWeek: 1,
                daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
            },
            month: {
                startDayOfWeek: 1,
                daynames: ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
            },
            template: {
                allday: (s) => s.title,
                alldayTitle: () => '<div style="text-align:center">Journée</div>',
                time: (s) => `<div style="padding:2px 4px;font-size:11px"><strong>${s.title}</strong></div>`,
            },
        });

        this.attachEventHandlers();
        this.initModal();
        this.updateHeader();
        this.updateViewButtons('week');
        this.reloadEvents();
    }

    attachEventHandlers() {
        // Navigation
        document.getElementById('prev-btn').addEventListener('click', () => { this.calendar.prev(); this.updateHeader(); this.reloadEvents(); });
        document.getElementById('next-btn').addEventListener('click', () => { this.calendar.next(); this.updateHeader(); this.reloadEvents(); });
        document.getElementById('today-btn').addEventListener('click', () => { this.calendar.today(); this.updateHeader(); this.reloadEvents(); });

        // Vues
        document.getElementById('day-view').addEventListener('click', () => this.changeView('day'));
        document.getElementById('week-view').addEventListener('click', () => this.changeView('week'));
        document.getElementById('month-view').addEventListener('click', () => this.changeView('month'));

        // Filtres résidence/employé
        const filterRes = document.getElementById('filterResidence');
        const filterEmp = document.getElementById('filterEmployee');
        if (filterRes) filterRes.addEventListener('change', () => this.reloadEvents());
        if (filterEmp) filterEmp.addEventListener('change', () => this.reloadEvents());

        // Création par sélection sur le calendrier
        this.calendar.on('beforeCreateSchedule', (e) => {
            const start = new Date(e.start);
            const end = new Date(e.end || new Date(start.getTime() + 3600000));
            const isAllDay = this.calendar.getViewName() === 'month' || e.isAllDay;
            this.openCreateModal(start, end, isAllDay);
        });

        // Drag & drop / resize
        this.calendar.on('beforeUpdateSchedule', (e) => {
            const { schedule, changes } = e;
            if (changes && (changes.start || changes.end)) {
                this.calendar.updateSchedule(schedule.id, schedule.calendarId, changes);
                this.backend.moveEvent({
                    id: schedule.id,
                    start: changes.start || schedule.start,
                    end: changes.end || schedule.end,
                }, () => {});
            }
        });

        // Double-clic pour éditer
        document.getElementById('calendar').addEventListener('dblclick', (e) => {
            const el = e.target.closest('.tui-full-calendar-time-schedule') ||
                       e.target.closest('.tui-full-calendar-weekday-schedule');
            if (!el) return;
            const id = el.getAttribute('data-schedule-id');
            if (!id) return;
            e.preventDefault();
            e.stopPropagation();

            this.backend.getEvent(id, (err, eventData) => {
                if (!err && eventData) this.openEditModal(eventData);
            });
        }, true);

        // Clic simple — ne rien faire
        this.calendar.on('clickSchedule', (e) => {});
    }

    changeView(view) {
        this.calendar.changeView(view);
        this.updateViewButtons(view);
        this.updateHeader();
        this.reloadEvents();
    }

    reloadEvents() {
        const rangeStart = this.calendar.getDateRangeStart();
        const rangeEnd = this.calendar.getDateRangeEnd();
        let start = rangeStart._date ? new Date(rangeStart._date) : new Date(rangeStart);
        let end = rangeEnd._date ? new Date(rangeEnd._date) : new Date(rangeEnd);
        end.setDate(end.getDate() + 1);

        const filters = {
            residenceId: document.getElementById('filterResidence')?.value || '',
            userId: document.getElementById('filterEmployee')?.value || '',
        };

        this.backend.loadEvents(start, end, filters, (err, events) => {
            if (err) return;
            this.calendar.clear();
            if (events && events.length) {
                this.calendar.createSchedules(events.map(ev => this.transformEvent(ev)));
                this.calendar.render();
            }
        });
    }

    transformEvent(ev) {
        const isAllDay = ev.isAllDay === true || ev.isAllDay === 1 || ev.isAllDay === '1';
        return {
            id: ev.id,
            calendarId: ev.calendarId?.toString(),
            title: ev.title,
            start: new Date(ev.start),
            end: new Date(ev.end),
            isAllDay: isAllDay,
            category: isAllDay ? 'allday' : 'time',
            bgColor: ev.categoryColor || '#e8eaf6',
            borderColor: ev.calendarColor || '#3f51b5',
            color: ev.categoryTextColor || '#333',
            raw: ev.raw || {},
        };
    }

    // === Modal ===
    initModal() {
        this.modal = new bootstrap.Modal(document.getElementById('shiftModal'));

        // Filtrage croisé employé ↔ résidence
        document.getElementById('shiftEmployee').addEventListener('change', () => this.filterResidencesByEmployee());
        document.getElementById('shiftResidence').addEventListener('change', () => this.filterEmployeesByResidence());

        document.getElementById('saveShiftBtn').addEventListener('click', () => this.saveShift());

        document.getElementById('deleteShiftBtn').addEventListener('click', () => {
            if (confirm('Supprimer ce shift ?')) {
                const id = document.getElementById('shiftId').value;
                if (id) {
                    this.backend.deleteEvent(id, () => {
                        this.modal.hide();
                        this.reloadEvents();
                    });
                }
            }
        });
    }

    openCreateModal(start, end, isAllDay) {
        document.getElementById('shiftModalLabel').textContent = 'Nouveau shift';
        document.getElementById('deleteShiftBtn').classList.add('d-none');
        document.getElementById('shiftId').value = '';
        document.getElementById('shiftTitle').value = '';
        document.getElementById('shiftEmployee').value = '';
        document.getElementById('shiftResidence').value = '';
        document.getElementById('shiftCategory').value = '';
        document.getElementById('shiftType').value = 'travail';
        document.getElementById('shiftTypeHeures').value = 'normales';
        document.getElementById('shiftAllDay').checked = isAllDay;
        document.getElementById('shiftDescription').value = '';

        if (isAllDay) {
            start.setHours(0, 0, 0, 0);
            end.setHours(23, 59, 0, 0);
        }
        document.getElementById('shiftStart').value = this.formatForInput(start);
        document.getElementById('shiftEnd').value = this.formatForInput(end);

        this.modal.show();
    }

    openEditModal(eventData) {
        document.getElementById('shiftModalLabel').textContent = 'Modifier le shift';
        document.getElementById('deleteShiftBtn').classList.remove('d-none');
        document.getElementById('shiftId').value = eventData.id;
        document.getElementById('shiftTitle').value = eventData.title;
        document.getElementById('shiftEmployee').value = eventData.raw?.userId || '';
        document.getElementById('shiftResidence').value = eventData.raw?.residenceId || eventData.calendarId || '';
        document.getElementById('shiftCategory').value = eventData.raw?.categoryId || eventData.categoryId || '';
        document.getElementById('shiftType').value = eventData.raw?.typeShift || 'travail';
        document.getElementById('shiftTypeHeures').value = eventData.raw?.typeHeures || 'normales';
        document.getElementById('shiftAllDay').checked = eventData.isAllDay;
        document.getElementById('shiftStart').value = this.formatForInput(eventData.start);
        document.getElementById('shiftEnd').value = this.formatForInput(eventData.end);
        document.getElementById('shiftDescription').value = eventData.raw?.description || '';

        this.modal.show();
    }

    saveShift() {
        const data = {
            id: document.getElementById('shiftId').value || null,
            title: document.getElementById('shiftTitle').value,
            userId: document.getElementById('shiftEmployee').value,
            residenceId: document.getElementById('shiftResidence').value,
            categoryId: document.getElementById('shiftCategory').value || null,
            typeShift: document.getElementById('shiftType').value,
            typeHeures: document.getElementById('shiftTypeHeures').value,
            start: document.getElementById('shiftStart').value,
            end: document.getElementById('shiftEnd').value,
            isAllDay: document.getElementById('shiftAllDay').checked,
            description: document.getElementById('shiftDescription').value,
        };

        if (!data.title || !data.userId || !data.residenceId || !data.start || !data.end) {
            alert('Veuillez remplir les champs obligatoires : titre, employé, résidence et dates.');
            return;
        }

        this.backend.saveEvent(data, (err, response) => {
            if (err) {
                alert('Erreur : ' + err.message);
                return;
            }
            this.modal.hide();
            this.reloadEvents();
        });
    }

    // === Filtrage croisé ===
    // Mapping rôle employé → slug catégorie planning
    getRoleCategorySlug(role) {
        const map = {
            'menage_interieur':     'menage_interieur',
            'menage_exterieur':     'menage_exterieur',
            'restauration_manager': 'restauration',
            'restauration_serveur': 'restauration',
            'restauration_cuisine': 'cuisine',
            'technicien':           'technique',
            'jardinier_manager':    'jardinage',
            'jardinier_employe':    'jardinage',
            'entretien_manager':    'entretien',
            'employe_laverie':      'laverie',
            'employe_residence':    'accueil',
            'directeur_residence':  null,
            'comptable':            null,
        };
        return map[role] || null;
    }

    autoSelectCategory(empId) {
        if (!empId) return;
        const emp = this.employees.find(e => e.id == empId);
        if (!emp) return;
        const slug = this.getRoleCategorySlug(emp.role);
        if (!slug) return;
        // Trouver la catégorie correspondante dans le config
        const cat = this.config.categories?.find(c => c.slug === slug);
        if (cat) {
            document.getElementById('shiftCategory').value = cat.id;
        }
    }

    filterResidencesByEmployee() {
        const empId = document.getElementById('shiftEmployee').value;
        // Auto-sélectionner la catégorie correspondant au rôle
        this.autoSelectCategory(empId);
        const resSelect = document.getElementById('shiftResidence');
        const currentRes = resSelect.value;

        // Réinitialiser toutes les options
        resSelect.innerHTML = '<option value="">— Sélectionner —</option>';

        if (empId) {
            // Résidences liées à cet employé
            const linkedResIds = this.userResidences
                .filter(ur => ur.user_id == empId)
                .map(ur => ur.residence_id.toString());

            this.residences.forEach(r => {
                if (linkedResIds.includes(r.id.toString())) {
                    const opt = new Option(r.nom, r.id, false, r.id == currentRes);
                    resSelect.appendChild(opt);
                }
            });

            // Si une seule résidence, la sélectionner automatiquement
            if (linkedResIds.length === 1) {
                resSelect.value = linkedResIds[0];
            }
        } else {
            // Pas d'employé sélectionné → toutes les résidences
            this.residences.forEach(r => {
                const opt = new Option(r.nom, r.id, false, r.id == currentRes);
                resSelect.appendChild(opt);
            });
        }
    }

    filterEmployeesByResidence() {
        const resId = document.getElementById('shiftResidence').value;
        const empSelect = document.getElementById('shiftEmployee');
        const currentEmp = empSelect.value;

        empSelect.innerHTML = '<option value="">— Sélectionner —</option>';

        if (resId) {
            // Employés liés à cette résidence
            const linkedUserIds = this.userResidences
                .filter(ur => ur.residence_id == resId)
                .map(ur => ur.user_id.toString());

            this.employees.forEach(e => {
                if (linkedUserIds.includes(e.id.toString())) {
                    const opt = new Option(e.prenom + ' ' + e.nom + ' (' + e.role + ')', e.id, false, e.id == currentEmp);
                    empSelect.appendChild(opt);
                }
            });
        } else {
            // Pas de résidence sélectionnée → tous les employés
            this.employees.forEach(e => {
                const opt = new Option(e.prenom + ' ' + e.nom + ' (' + e.role + ')', e.id, false, e.id == currentEmp);
                empSelect.appendChild(opt);
            });
        }
    }

    // === UI ===
    updateHeader() {
        const d = this.calendar.getDate();
        const months = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
        document.getElementById('cal-header').textContent = months[d.getMonth()] + ' ' + d.getFullYear();
    }

    updateViewButtons(view) {
        ['day','week','month'].forEach(v => {
            document.getElementById(v + '-view').classList.toggle('active', v === view);
        });
    }

    formatForInput(dateValue) {
        if (!dateValue) return '';
        if (typeof dateValue === 'string') {
            return dateValue.includes('T') ? dateValue.substring(0, 16) : dateValue;
        }
        if (dateValue._date) dateValue = dateValue._date;
        if (!(dateValue instanceof Date)) dateValue = new Date(dateValue);
        const y = dateValue.getFullYear();
        const mo = String(dateValue.getMonth() + 1).padStart(2, '0');
        const d = String(dateValue.getDate()).padStart(2, '0');
        const h = String(dateValue.getHours()).padStart(2, '0');
        const mi = String(dateValue.getMinutes()).padStart(2, '0');
        return `${y}-${mo}-${d}T${h}:${mi}`;
    }
}
