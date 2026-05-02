/**
 * Planning Backend - Communication AJAX avec le serveur
 * Adapté de CalendarBackend pour le MVC Synd_Gest
 */
class PlanningBackend {
    constructor(baseUrl) {
        this.baseUrl = baseUrl || '/Synd_Gest/public';
    }

    /**
     * Headers JSON + token CSRF (X-CSRF-Token) lu depuis <meta name="csrf-token">.
     * Requis par le serveur sur les endpoints state-changing (save/move/delete).
     */
    jsonHeaders() {
        const meta = document.querySelector('meta[name="csrf-token"]');
        return {
            'Content-Type': 'application/json',
            'X-CSRF-Token': meta ? meta.content : ''
        };
    }

    /**
     * Charger les shifts pour une période
     */
    loadEvents(start, end, filters, callback) {
        const params = new URLSearchParams({
            start: this.formatDate(start),
            end: this.formatDate(end),
        });
        if (filters.residenceId) params.set('residence_id', filters.residenceId);
        if (filters.userId) params.set('user_id', filters.userId);

        fetch(`${this.baseUrl}/planning/ajax/getEvents?${params}`)
            .then(r => r.json())
            .then(events => callback(null, events))
            .catch(err => callback(err, null));
    }

    /**
     * Récupérer un shift par ID
     */
    getEvent(id, callback) {
        fetch(`${this.baseUrl}/planning/ajax/getEvent?id=${id}`)
            .then(r => r.json())
            .then(data => {
                if (data.success) callback(null, data.event);
                else callback(new Error(data.message), null);
            })
            .catch(err => callback(err, null));
    }

    /**
     * Créer ou modifier un shift
     */
    saveEvent(eventData, callback) {
        const formatted = {
            id: eventData.id || null,
            title: eventData.title,
            start: this.formatISO(eventData.start),
            end: this.formatISO(eventData.end),
            isAllDay: eventData.isAllDay || false,
            userId: eventData.userId,
            residenceId: eventData.residenceId,
            calendarId: eventData.residenceId,
            categoryId: eventData.categoryId,
            typeShift: eventData.typeShift || 'travail',
            typeHeures: eventData.typeHeures || 'normales',
            description: eventData.description || '',
            notes: eventData.notes || '',
            raw: eventData.raw || {},
        };

        fetch(`${this.baseUrl}/planning/ajax/save`, {
            method: 'POST',
            headers: this.jsonHeaders(),
            body: JSON.stringify(formatted)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) callback(null, data);
            else callback(new Error(data.message), null);
        })
        .catch(err => callback(err, null));
    }

    /**
     * Déplacer un shift (drag & drop)
     */
    moveEvent(moveData, callback) {
        const formatted = {
            id: moveData.id,
            start: this.formatISO(moveData.start),
            end: this.formatISO(moveData.end),
        };

        fetch(`${this.baseUrl}/planning/ajax/move`, {
            method: 'POST',
            headers: this.jsonHeaders(),
            body: JSON.stringify(formatted)
        })
        .then(r => r.json())
        .then(data => callback(null, data))
        .catch(err => callback(err, null));
    }

    /**
     * Supprimer un shift
     */
    deleteEvent(id, callback) {
        fetch(`${this.baseUrl}/planning/ajax/delete`, {
            method: 'POST',
            headers: this.jsonHeaders(),
            body: JSON.stringify({ id: id })
        })
        .then(r => r.json())
        .then(data => callback(null, data))
        .catch(err => callback(err, null));
    }

    formatDate(date) {
        if (typeof date === 'string') return date;
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    formatISO(date) {
        if (typeof date === 'string') return date;
        if (date && date._date) date = date._date;
        if (!(date instanceof Date)) date = new Date(date);
        const y = date.getFullYear();
        const mo = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        const h = String(date.getHours()).padStart(2, '0');
        const mi = String(date.getMinutes()).padStart(2, '0');
        const s = String(date.getSeconds()).padStart(2, '0');
        return `${y}-${mo}-${d}T${h}:${mi}:${s}`;
    }
}
