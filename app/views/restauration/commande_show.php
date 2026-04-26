<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-truck', 'text' => 'Commandes', 'url' => BASE_URL . '/restauration/commandes'],
    ['icon' => 'fas fa-file-invoice', 'text' => $commande['numero_commande'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$modulePath  = 'restauration';
$moduleLabel = 'Restauration';
$moduleColor = 'warning';

include __DIR__ . '/../partials/commande_show_module.php';
