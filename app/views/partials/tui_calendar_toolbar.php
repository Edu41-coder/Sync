<?php
/**
 * ====================================================================
 * Partial — Toolbar de navigation TUI Calendar
 * ====================================================================
 * Affiche : [‹ prev] [Aujourd'hui] [next ›]   <Mois Année>   [Jour|Semaine|Mois]
 *
 * Variables optionnelles à passer avant include :
 *   $tuiToolbarColor   = 'success' | 'info' | 'primary' | … (couleur boutons "today")
 *                         Par défaut : 'primary'.
 *   $tuiToolbarExtra   = HTML supplémentaire injecté à droite des boutons de vue
 *                         (ex: filtres résidence, employé, vue résidents/staff…).
 *                         Par défaut : '' (rien).
 *
 * Le JS doit appeler TuiCalHelpers.bindToolbar(cal, { headerId: 'cal-header', onChange: loadEvents })
 * pour brancher les boutons (cf. tui-calendar-helpers.js).
 *
 * IDs requis dans le DOM (créés par ce partial) :
 *   #prev-btn, #today-btn, #next-btn, #cal-header, #day-view, #week-view, #month-view
 * ====================================================================
 */
$tuiToolbarColor = $tuiToolbarColor ?? 'primary';
$tuiToolbarExtra = $tuiToolbarExtra ?? '';
?>
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <div class="row g-2 align-items-center">
            <div class="col-auto">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-secondary" id="prev-btn" type="button"><i class="fas fa-chevron-left"></i></button>
                    <button class="btn btn-outline-<?= htmlspecialchars($tuiToolbarColor) ?>" id="today-btn" type="button">Aujourd'hui</button>
                    <button class="btn btn-outline-secondary" id="next-btn" type="button"><i class="fas fa-chevron-right"></i></button>
                </div>
            </div>
            <div class="col-auto"><h5 class="mb-0" id="cal-header">…</h5></div>
            <div class="col-auto ms-auto">
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-dark" id="day-view"   type="button">Jour</button>
                    <button class="btn btn-outline-dark active" id="week-view" type="button">Semaine</button>
                    <button class="btn btn-outline-dark" id="month-view" type="button">Mois</button>
                </div>
            </div>
            <?php if (!empty($tuiToolbarExtra)): ?>
            <?= $tuiToolbarExtra ?>
            <?php endif; ?>
        </div>
    </div>
</div>
