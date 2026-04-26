<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'restauration';
$moduleLabel = 'Restauration';
$moduleIcon  = 'fa-utensils';
$moduleColor = 'warning';

include __DIR__ . '/../partials/fournisseurs_residence_module.php';
