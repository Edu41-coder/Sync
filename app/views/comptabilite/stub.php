<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',    'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-clock',          'text' => $page,             'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card shadow-sm border-warning">
                <div class="card-body text-center py-5">
                    <i class="fas fa-tools fa-4x text-warning mb-3"></i>
                    <h3 class="mb-3"><?= htmlspecialchars($page) ?></h3>
                    <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
                    <a href="<?= BASE_URL ?>/comptabilite/index" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-1"></i>Retour au dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
