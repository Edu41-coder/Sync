<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-users',          'text' => 'Équipe',          'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-users text-warning me-2"></i>Équipe maintenance technique</h1>
            <p class="text-muted mb-0"><?= count($staff) ?> technicien<?= count($staff) > 1 ? 's' : '' ?> sur <?= count($residences) ?> résidence<?= count($residences) > 1 ? 's' : '' ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/maintenance/specialites" class="btn btn-outline-primary">
                <i class="fas fa-users-cog me-1"></i>Affecter spécialités
            </a>
        </div>
    </div>

    <?php if (empty($staff)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>
        Aucun technicien affecté à vos résidences.
    </div>

    <?php else: ?>

    <!-- KPI -->
    <?php
    $nbChefs = count(array_filter($staff, fn($s) => $s['role'] === 'technicien_chef'));
    $nbExec  = count(array_filter($staff, fn($s) => $s['role'] === 'technicien'));
    $nbAlertes = array_sum(array_column($staff, 'nb_certifs_expire_bientot'));
    $totalCertifs = array_sum(array_column($staff, 'nb_certifs'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-md-3 col-6">
            <div class="card border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-warning small fw-bold mb-1">Chefs techniques</h6>
                    <h3 class="mb-0"><?= $nbChefs ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-secondary border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-secondary small fw-bold mb-1">Techniciens</h6>
                    <h3 class="mb-0"><?= $nbExec ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-info border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-info small fw-bold mb-1">Certifications actives</h6>
                    <h3 class="mb-0"><?= $totalCertifs ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="card border-start border-danger border-4 shadow-sm h-100">
                <div class="card-body p-3 text-center">
                    <h6 class="text-danger small fw-bold mb-1">⚠️ Expirent &lt; 3 mois</h6>
                    <h3 class="mb-0"><?= $nbAlertes ?></h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Cartes équipe -->
    <div class="row g-3">
        <?php foreach ($staff as $s):
            $isChef = $s['role'] === 'technicien_chef';
            $specs = !empty($s['specialites']) ? explode('|', $s['specialites']) : [];
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 <?= $isChef ? 'border-warning border-2' : '' ?>">
                <div class="card-body">
                    <div class="d-flex align-items-start mb-3">
                        <?php if (!empty($s['photo_profil'])): ?>
                        <img src="<?= BASE_URL . '/' . htmlspecialchars($s['photo_profil']) ?>" alt=""
                             class="rounded-circle me-3" style="width:60px;height:60px;object-fit:cover">
                        <?php else: ?>
                        <div class="rounded-circle d-flex align-items-center justify-content-center me-3"
                             style="width:60px;height:60px;background:<?= $isChef ? '#fd7e14' : '#6c757d' ?>;color:#fff;font-size:1.4rem">
                            <?= strtoupper(substr($s['prenom'] ?? '?', 0, 1) . substr($s['nom'] ?? '?', 0, 1)) ?>
                        </div>
                        <?php endif; ?>
                        <div class="flex-grow-1">
                            <h5 class="mb-0"><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?></h5>
                            <small class="text-muted">@<?= htmlspecialchars($s['username']) ?></small>
                            <div class="mt-1">
                                <?php if ($isChef): ?>
                                <span class="badge bg-warning text-dark"><i class="fas fa-hard-hat me-1"></i>Chef Technique</span>
                                <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-user me-1"></i>Technicien</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Spécialités -->
                    <div class="mb-2">
                        <small class="text-muted fw-bold d-block mb-1">Spécialités</small>
                        <?php if (empty($specs)): ?>
                        <small class="text-muted fst-italic">Aucune spécialité affectée</small>
                        <?php else: foreach ($specs as $sp): ?>
                        <span class="badge bg-light text-dark border me-1 mb-1"><?= htmlspecialchars($sp) ?></span>
                        <?php endforeach; endif; ?>
                    </div>

                    <!-- Certifications -->
                    <div class="mb-2">
                        <small class="text-muted fw-bold d-block mb-1">Certifications</small>
                        <span class="badge bg-info"><?= (int)$s['nb_certifs'] ?> active<?= (int)$s['nb_certifs'] > 1 ? 's' : '' ?></span>
                        <?php if ((int)$s['nb_certifs_expire_bientot'] > 0): ?>
                        <span class="badge bg-warning text-dark"><i class="fas fa-exclamation-triangle me-1"></i><?= (int)$s['nb_certifs_expire_bientot'] ?> expire&nbsp;&lt; 3 mois</span>
                        <?php endif; ?>
                    </div>

                    <!-- Résidences -->
                    <?php if (!empty($s['residences'])): ?>
                    <div class="mb-2">
                        <small class="text-muted fw-bold d-block mb-1">Résidences</small>
                        <small><?= htmlspecialchars($s['residences']) ?></small>
                    </div>
                    <?php endif; ?>

                    <!-- Contact -->
                    <hr class="my-2">
                    <div class="small">
                        <?php if (!empty($s['email'])): ?>
                        <div><i class="fas fa-envelope text-muted me-1"></i><a href="mailto:<?= htmlspecialchars($s['email']) ?>"><?= htmlspecialchars($s['email']) ?></a></div>
                        <?php endif; ?>
                        <?php if (!empty($s['telephone'])): ?>
                        <div><i class="fas fa-phone text-muted me-1"></i><a href="tel:<?= htmlspecialchars($s['telephone']) ?>"><?= htmlspecialchars($s['telephone']) ?></a></div>
                        <?php endif; ?>
                        <?php if (!empty($s['last_login'])): ?>
                        <div class="text-muted"><i class="far fa-clock me-1"></i>Dernière connexion : <?= date('d/m/Y H:i', strtotime($s['last_login'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-footer bg-light d-flex gap-2">
                    <a href="<?= BASE_URL ?>/maintenance/certifications/<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-warning flex-grow-1">
                        <i class="fas fa-certificate me-1"></i>Certifs
                    </a>
                    <a href="<?= BASE_URL ?>/message/compose?to=<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-primary" title="Contacter">
                        <i class="fas fa-envelope"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php endif; ?>
</div>
