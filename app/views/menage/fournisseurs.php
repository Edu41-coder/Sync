<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

// Paramètres pour le partial commun
$modulePath  = 'menage';
$moduleLabel = 'Ménage';
$moduleIcon  = 'fa-broom';
$moduleColor = 'info';

include __DIR__ . '/../partials/fournisseurs_residence_module.php';
