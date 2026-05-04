<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-shield-alt',     'text' => 'Sinistres',       'url' => BASE_URL . '/sinistre/index'],
    ['icon' => 'fas fa-plus',           'text' => 'Déclarer',        'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
$sinistre = null; // pour le partial
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-shield-alt me-2 text-danger"></i>Déclarer un sinistre</h2>

    <?php if ($userRole === 'locataire_permanent'): ?>
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i>
            Vous déclarez un sinistre concernant votre logement. La direction de la résidence prendra en charge la suite (transmission à l'assureur, expertise, etc.).
        </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/sinistre/store">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <?php include __DIR__ . '/_form_fields.php'; ?>

                <hr>
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/sinistre/index" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-paper-plane me-1"></i>Déclarer le sinistre</button>
                </div>
            </form>
        </div>
    </div>
</div>
