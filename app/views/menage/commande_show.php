<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/menage/commandes'],
    ['icon' => 'fas fa-file-invoice', 'text' => $commande['numero_commande'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'menage';
$moduleLabel = 'Ménage';
$moduleColor = 'info';

include __DIR__ . '/../partials/commande_show_module.php';
