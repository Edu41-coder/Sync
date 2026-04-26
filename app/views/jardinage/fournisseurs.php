<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'jardinage';
$moduleLabel = 'Jardinage';
$moduleIcon  = 'fa-seedling';
$moduleColor = 'success';

include __DIR__ . '/../partials/fournisseurs_residence_module.php';
