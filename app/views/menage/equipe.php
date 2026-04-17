<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-user-friends', 'text' => 'Équipe', 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-4"><i class="fas fa-user-friends me-2 text-info"></i>Équipe Ménage</h2>

    <?php
    $staffByResidence = [];
    foreach ($staff as $s) { $staffByResidence[$s['residence_nom']][] = $s; }
    ?>

    <?php if (empty($staff)): ?>
        <div class="alert alert-info"><i class="fas fa-info-circle me-2"></i>Aucun personnel ménage affecté à vos résidences.</div>
    <?php else: ?>
        <?php foreach ($staffByResidence as $residenceNom => $members): ?>
        <div class="card shadow-sm mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="fas fa-building me-2"></i><?= htmlspecialchars($residenceNom) ?> — <?= count($members) ?> membre(s)</h6></div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Nom</th><th>Rôle</th><th>Contact</th><th>Dernière connexion</th><th class="text-center">Statut</th></tr></thead>
                    <tbody>
                        <?php foreach ($members as $m): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($m['prenom'] . ' ' . $m['nom']) ?></strong></td>
                            <td><span class="badge" style="background-color:<?= $m['role_couleur'] ?? '#6c757d' ?>"><i class="fas <?= $m['role_icone'] ?? 'fa-user' ?> me-1"></i><?= htmlspecialchars($m['role_nom'] ?? $m['role']) ?></span></td>
                            <td>
                                <?php if ($m['email']): ?><a href="mailto:<?= htmlspecialchars($m['email']) ?>" class="text-decoration-none me-2"><i class="fas fa-envelope"></i></a><?php endif; ?>
                                <?php if ($m['telephone']): ?><a href="tel:<?= htmlspecialchars($m['telephone']) ?>" class="text-decoration-none"><i class="fas fa-phone"></i></a><?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= $m['last_login'] ? date('d/m/Y H:i', strtotime($m['last_login'])) : 'Jamais' ?></td>
                            <td class="text-center"><span class="badge bg-<?= $m['actif'] ? 'success' : 'danger' ?>"><?= $m['actif'] ? 'Actif' : 'Inactif' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
