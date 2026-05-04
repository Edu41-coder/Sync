<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-shield-alt',     'text' => 'Sinistres',       'url' => BASE_URL . '/sinistre/index'],
    ['icon' => 'fas fa-eye',            'text' => '#' . (int)$sinistre['id'], 'url' => BASE_URL . '/sinistre/show/' . (int)$sinistre['id']],
    ['icon' => 'fas fa-edit',           'text' => 'Modifier',        'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

// Préparer lotsByResidence : pour la modification, on n'autorise pas de changer la résidence
// donc on charge les lots de la résidence courante uniquement.
$model = new Sinistre();
$lotsByResidence = $model->getLotsGroupesParResidence((int)$_SESSION['user_id'], $userRole);

$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-edit me-2 text-warning"></i>Modifier le sinistre #<?= (int)$sinistre['id'] ?></h2>

    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        La modification n'est possible que tant que le sinistre est au statut <strong>« Déclaré »</strong>.
        Une fois transmis à l'assureur, son contenu est figé (seuls les changements de statut, l'indemnisation et les documents restent éditables).
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="<?= BASE_URL ?>/sinistre/update/<?= (int)$sinistre['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <?php include __DIR__ . '/_form_fields.php'; ?>

                <hr>
                <div class="d-flex justify-content-between">
                    <a href="<?= BASE_URL ?>/sinistre/show/<?= (int)$sinistre['id'] ?>" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Annuler</a>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
