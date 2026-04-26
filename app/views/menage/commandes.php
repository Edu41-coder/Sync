<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'menage';
$moduleLabel = 'Ménage';
$moduleColor = 'info';

include __DIR__ . '/../partials/commandes_liste_module.php';
