<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-users-cog',      'text' => 'Équipe',          'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$rolesMeta = [
    'directeur_residence' => ['libelle' => 'Directeur Résidence', 'couleur' => 'danger',  'icone' => 'fa-crown'],
    'accueil_manager'     => ['libelle' => 'Responsable Accueil', 'couleur' => 'info',    'icone' => 'fa-concierge-bell'],
    'accueil_employe'     => ['libelle' => 'Employé Accueil',     'couleur' => 'primary', 'icone' => 'fa-user-tie'],
];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0"><i class="fas fa-users-cog text-info me-2"></i>Équipe Accueil</h1>
            <?php if ($residenceCourante): ?>
            <p class="text-muted mb-0"><?= htmlspecialchars($residenceCourante['nom']) ?> · <?= count($equipe) ?> membre<?= count($equipe) > 1 ? 's' : '' ?></p>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if (count($residences) > 1): ?>
            <form method="GET" action="<?= BASE_URL ?>/accueil/equipe">
                <select name="residence_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <?php foreach ($residences as $r): ?>
                    <option value="<?= (int)$r['id'] ?>" <?= $residenceCourante && (int)$residenceCourante['id'] === (int)$r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nom']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <?php endif; ?>
            <?php if ($residenceCourante): ?>
            <a href="<?= BASE_URL ?>/accueil/messageGroupe?residence_id=<?= (int)$residenceCourante['id'] ?>" class="btn btn-info text-white">
                <i class="fas fa-envelope me-1"></i>Message groupé
            </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (empty($residences)): ?>
    <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucune résidence accessible.</div>

    <?php elseif (empty($equipe)): ?>
    <div class="alert alert-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>Aucun membre de l'équipe Accueil affecté à cette résidence.
        <a href="<?= BASE_URL ?>/admin/users" class="alert-link">Gérer les affectations</a>.
    </div>

    <?php else: ?>
    <div class="row g-3">
        <?php foreach ($equipe as $u):
            $r = $rolesMeta[$u['role']] ?? ['libelle' => $u['role'], 'couleur' => 'secondary', 'icone' => 'fa-user'];
            $initiales = strtoupper(mb_substr($u['prenom'] ?? '?', 0, 1) . mb_substr($u['nom'] ?? '?', 0, 1));
        ?>
        <div class="col-md-6 col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-body text-center">
                    <?php if (!empty($u['photo_profil']) && file_exists(ROOT_PATH . '/public/uploads/photos/' . $u['photo_profil'])): ?>
                    <img src="<?= BASE_URL ?>/uploads/photos/<?= htmlspecialchars($u['photo_profil']) ?>"
                         class="rounded-circle mb-2" style="width:80px;height:80px;object-fit:cover" alt="">
                    <?php else: ?>
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-2 bg-<?= $r['couleur'] ?> text-white fw-bold"
                         style="width:80px;height:80px;font-size:1.8rem">
                        <?= htmlspecialchars($initiales) ?>
                    </div>
                    <?php endif; ?>

                    <h5 class="mb-1"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></h5>
                    <span class="badge bg-<?= $r['couleur'] ?> mb-2"><i class="fas <?= $r['icone'] ?> me-1"></i><?= htmlspecialchars($r['libelle']) ?></span>

                    <div class="small text-muted text-start mt-3">
                        <?php if ($u['email']): ?>
                        <div><i class="fas fa-envelope me-2 text-info"></i><a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="text-decoration-none"><?= htmlspecialchars($u['email']) ?></a></div>
                        <?php endif; ?>
                        <?php if ($u['telephone']): ?>
                        <div><i class="fas fa-phone me-2 text-info"></i><a href="tel:<?= htmlspecialchars($u['telephone']) ?>" class="text-decoration-none"><?= htmlspecialchars($u['telephone']) ?></a></div>
                        <?php endif; ?>
                        <div class="mt-1"><i class="fas fa-at me-2 text-muted"></i><small><?= htmlspecialchars($u['username']) ?></small></div>
                    </div>
                </div>
                <div class="card-footer bg-white d-flex justify-content-end gap-1">
                    <a href="<?= BASE_URL ?>/message/compose?to=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-info" title="Message"><i class="fas fa-envelope"></i></a>
                    <?php if ($u['email']): ?>
                    <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="btn btn-sm btn-outline-secondary" title="Email externe"><i class="fas fa-paper-plane"></i></a>
                    <?php endif; ?>
                    <?php if ($u['telephone']): ?>
                    <a href="tel:<?= htmlspecialchars($u['telephone']) ?>" class="btn btn-sm btn-outline-success" title="Téléphone"><i class="fas fa-phone"></i></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
