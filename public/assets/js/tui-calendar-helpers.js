/**
 * ====================================================================
 * Helpers TUI Calendar partagés
 * ====================================================================
 * Fonctions communes utilisées par toutes les pages TUI Calendar
 * (planning staff, calendriers personnels, planning accueil…).
 *
 * Inclus automatiquement par partials/tui_calendar_assets.php.
 * Expose un objet global window.TuiCalHelpers.
 * ====================================================================
 */
(function (global) {
    'use strict';

    /** Formate une Date en 'YYYY-MM-DD' */
    function formatDate(d) {
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate());
    }

    /** Formate une Date en 'YYYY-MM-DD HH:MM:SS' (compat MySQL DATETIME) */
    function formatDT(d) {
        var pad = function (n) { return String(n).padStart(2, '0'); };
        return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
             + ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':00';
    }

    /** Headers POST AJAX avec CSRF token automatique (depuis <meta name="csrf-token">) */
    function jsonHeaders(contentType) {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return {
            'Content-Type': contentType || 'application/json',
            'X-CSRF-Token': meta ? meta.content : ''
        };
    }

    /** Headers POST en x-www-form-urlencoded (alternative à JSON) */
    function formHeaders() {
        return jsonHeaders('application/x-www-form-urlencoded');
    }

    /**
     * Branche les boutons standards de navigation (prev/today/next + day/week/month).
     *
     * @param {tui.Calendar} cal   - instance TUI Calendar
     * @param {Object}       opts  - { headerId, onChange?, onViewChange? }
     *                                headerId    : id du <h5> qui affiche le mois courant
     *                                onChange()  : callback appelé après prev/today/next/changeView
     *                                onViewChange(view): callback appelé sur changement de vue
     *
     * Boutons attendus dans le DOM (par convention) :
     *   #prev-btn, #today-btn, #next-btn
     *   #day-view, #week-view, #month-view
     */
    function bindToolbar(cal, opts) {
        opts = opts || {};
        var onChange = opts.onChange || function () {};
        var onViewChange = opts.onViewChange || function () {};

        function updateHeader() {
            if (!opts.headerId) return;
            var d = cal.getDate().toDate();
            var el = document.getElementById(opts.headerId);
            if (el) el.textContent = d.toLocaleDateString('fr-FR', { year: 'numeric', month: 'long' });
        }

        var prev  = document.getElementById('prev-btn');
        var today = document.getElementById('today-btn');
        var next  = document.getElementById('next-btn');
        if (prev)  prev.onclick  = function () { cal.prev();  updateHeader(); onChange(); };
        if (today) today.onclick = function () { cal.today(); updateHeader(); onChange(); };
        if (next)  next.onclick  = function () { cal.next();  updateHeader(); onChange(); };

        function setActive(btn) {
            ['day-view', 'week-view', 'month-view'].forEach(function (id) {
                var b = document.getElementById(id);
                if (b) b.classList.remove('active');
            });
            if (btn) btn.classList.add('active');
        }

        function changeView(view, btnId) {
            return function () {
                cal.changeView(view);
                setActive(document.getElementById(btnId));
                updateHeader();
                onViewChange(view);
                onChange();
            };
        }

        var dayBtn   = document.getElementById('day-view');
        var weekBtn  = document.getElementById('week-view');
        var monthBtn = document.getElementById('month-view');
        if (dayBtn)   dayBtn.onclick   = changeView('day',   'day-view');
        if (weekBtn)  weekBtn.onclick  = changeView('week',  'week-view');
        if (monthBtn) monthBtn.onclick = changeView('month', 'month-view');

        updateHeader();
        return { updateHeader: updateHeader };
    }

    /** Configuration TUI Calendar par défaut Synd_Gest (dark theme, FR, lun-dim) */
    function defaultConfig(extra) {
        var base = {
            defaultView: 'week',
            taskView: false,
            useDetailPopup: true,
            useCreationPopup: false,
            week:  { startDayOfWeek: 1, hourStart: 7, hourEnd: 22 },
            month: { startDayOfWeek: 1 },
            template: {
                time:   function (s) { return '<span style="font-size:11px">' + s.title + '</span>'; },
                allday: function (s) { return '<span style="font-size:11px">' + s.title + '</span>'; }
            }
        };
        if (extra) {
            for (var k in extra) if (Object.prototype.hasOwnProperty.call(extra, k)) base[k] = extra[k];
        }
        return base;
    }

    global.TuiCalHelpers = {
        formatDate:    formatDate,
        formatDT:      formatDT,
        jsonHeaders:   jsonHeaders,
        formHeaders:   formHeaders,
        bindToolbar:   bindToolbar,
        defaultConfig: defaultConfig
    };
})(window);
