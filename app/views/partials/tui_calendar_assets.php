<?php
/**
 * ====================================================================
 * Partial — Assets TUI Calendar v1.15.3
 * ====================================================================
 * Inclut les CSS + JS de la lib TUI Calendar.
 * À inclure UNE FOIS par page utilisant le calendrier.
 *
 * Usage :
 *   <?php include ROOT_PATH . '/app/views/partials/tui_calendar_assets.php'; ?>
 *
 * Inclut aussi le helper JS partagé (formatDate, jsonHeaders, etc.).
 * Voir public/assets/js/tui-calendar-helpers.js
 * ====================================================================
 */
?>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-calendar.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-date-picker.css">
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/tui-time-picker.css">
<script src="<?= BASE_URL ?>/assets/js/tui-code-snippet.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-time-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-date-picker.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-calendar.js"></script>
<script src="<?= BASE_URL ?>/assets/js/tui-calendar-helpers.js"></script>
