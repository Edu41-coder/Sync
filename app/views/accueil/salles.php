<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-door-open',      'text' => 'Salles communes', 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-door-open text-info me-2"></i>Salles communes</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · <?= count($salles) ?> salle<?= count($salles) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/accueil/salles">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($isManager && $residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/salleForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-info text-white">
                <i class="fas fa-plus me-1"></i>Nouvelle salle
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php elseif (empty($salles)): ?>
    <div class="alert alert-info">
        <i class="fas fa-info-circle me-2"></i>Aucune salle commune dans cette résidence.
        <?php if ($isManager): ?>
        <a href="<?= BASE_URL ?>/accueil/salleForm?residence_id=<?= (int)$residenceCourante['id'] ?>" class="alert-link">Créer la première</a>.
        <?php endif; ?>
    </div>

    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($salles as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100 <?= !$s['actif'] ? 'opacity-50' : '' ?>">
                <?php if (!empty($s['photo'])): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($s['photo']) ?>" class="card-img-top" alt="" style="height:180px;object-fit:cover">
                <?php else: ?>
                <div class="bg-light d-flex align-items-center justify-content-center" style="height:180px">
                    <i class="fas fa-door-open fa-3x text-muted opacity-50"></i>
                </div>
                <?php endif; ?>
                <div class="card-body">
                    <h5 class="mb-1"><?= htmlspecialchars($s['nom']) ?></h5>
                    <?php if (!$s['actif']): ?>
                    <span class="badge bg-secondary mb-2">Inactive</span>
                    <?php endif; ?>
                    <?php if ($s['capacite_personnes']): ?>
                    <p class="small mb-1"><i class="fas fa-users text-muted me-1"></i><?= (int)$s['capacite_personnes'] ?> personnes</p>
                    <?php endif; ?>
                    <?php if (!empty($s['description'])): ?>
                    <p class="small text-muted mb-2"><?= nl2br(htmlspecialchars($s['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($s['equipements_inclus'])): ?>
                    <div class="small">
                        <strong><i class="fas fa-couch me-1 text-secondary"></i>Équipements :</strong>
                        <?= nl2br(htmlspecialchars($s['equipements_inclus'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($isManager): ?>
                <div class="card-footer bg-white d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/accueil/salleForm/<?= (int)$s['id'] ?>" class="btn btn-sm btn-outline-secondary"><i class="fas fa-edit me-1"></i>Modifier</a>
                    <form method="POST" action="<?= BASE_URL ?>/accueil/salleDelete/<?= (int)$s['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer cette salle ?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
