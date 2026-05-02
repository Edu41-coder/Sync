<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-truck-loading',  'text' => 'Fournisseurs',    'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-truck-loading text-primary me-2"></i>Fournisseurs</h1>
            <p class="text-muted mb-0">Fournisseurs liés à vos résidences (<?= count($fournisseurs) ?>)</p>
        </div>
        <a href="<?= BASE_URL ?>/fournisseur/index" class="btn btn-outline-primary">
            <i class="fas fa-cogs me-1"></i>Gestion globale fournisseurs
        </a>
    </div>

    <?php if (empty($fournisseurs)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucun fournisseur lié à vos résidences. Allez dans la <a href="<?= BASE_URL ?>/fournisseur/index" class="alert-link">gestion globale</a> pour ajouter et lier des fournisseurs.
    </div>
    <?php else: ?>

    <div class="row g-3">
        <?php foreach ($fournisseurs as $f):
            $services = !empty($f['type_service']) ? explode(',', $f['type_service']) : [];
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h5 class="mb-2"><i class="fas fa-building text-primary me-2"></i><?= htmlspecialchars($f['nom']) ?></h5>
                    <?php if (!empty($services)): ?>
                    <div class="mb-2">
                        <?php foreach ($services as $s): ?>
                        <span class="badge bg-light text-dark border me-1"><?= htmlspecialchars($s) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($f['contact_nom'])): ?>
                    <div class="small mb-1"><i class="fas fa-user text-muted me-1"></i><?= htmlspecialchars($f['contact_nom']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($f['email'])): ?>
                    <div class="small mb-1"><i class="fas fa-envelope text-muted me-1"></i><a href="mailto:<?= htmlspecialchars($f['email']) ?>"><?= htmlspecialchars($f['email']) ?></a></div>
                    <?php endif; ?>
                    <?php if (!empty($f['telephone'])): ?>
                    <div class="small mb-1"><i class="fas fa-phone text-muted me-1"></i><a href="tel:<?= htmlspecialchars($f['telephone']) ?>"><?= htmlspecialchars($f['telephone']) ?></a></div>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="small text-muted">
                        <div><i class="fas fa-building me-1"></i><?= htmlspecialchars($f['residences']) ?></div>
                        <div><i class="fas fa-truck me-1"></i><?= (int)$f['nb_commandes'] ?> commande<?= (int)$f['nb_commandes'] > 1 ? 's' : '' ?> maintenance</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
