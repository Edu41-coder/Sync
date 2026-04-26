<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/jardinage/commandes'],
    ['icon' => 'fas fa-file-invoice', 'text' => $commande['numero_commande'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'jardinage';
$moduleLabel = 'Jardinage';
$moduleColor = 'success';

include __DIR__ . '/../partials/commande_show_module.php';
