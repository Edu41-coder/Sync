<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-warehouse',      'text' => 'Inventaire',      'url' => BASE_URL . '/maintenance/inventaire'],
    ['icon' => 'fas fa-history',        'text' => $item['nom'],      'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$badgeType = ['entree'=>'success','sortie'=>'danger','ajustement'=>'warning'];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h1 class="h4 mb-0"><i class="fas fa-history text-secondary me-2"></i>Historique : <?= htmlspecialchars($item['nom']) ?></h1>
            <p class="text-muted mb-0">Stock actuel : <strong><?= rtrim(rtrim(number_format((float)$item['quantite_actuelle'], 3, ',', ' '), '0'), ',') ?> <?= htmlspecialchars($item['unite'] ?? '') ?></strong></p>
        </div>
        <a href="<?= BASE_URL ?>/maintenance/inventaire?residence_id=<?= (int)$item['residence_id'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour inventaire</a>
    </div>

    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($mouvements)): ?>
            <div class="text-center py-5 text-muted">Aucun mouvement enregistré.</div>
            <?php else: ?>
            <table class="table table-hover mb-0">
                <thead class="table-light"><tr><th>Date</th><th>Type</th><th class="text-end">Quantité</th><th>Motif</th><th>Utilisateur</th><th>Notes</th></tr></thead>
                <tbody>
                    <?php foreach ($mouvements as $m): ?>
                    <tr>
                        <td><small><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></small></td>
                        <td><span class="badge bg-<?= $badgeType[$m['type_mouvement']] ?? 'secondary' ?>"><?= $m['type_mouvement'] ?></span></td>
                        <td class="text-end">
                            <strong><?= $m['type_mouvement'] === 'sortie' ? '−' : ($m['type_mouvement'] === 'entree' ? '+' : '=') ?> <?= rtrim(rtrim(number_format((float)$m['quantite'], 3, ',', ' '), '0'), ',') ?></strong>
                        </td>
                        <td><small><?= htmlspecialchars($m['motif']) ?></small></td>
                        <td><small><?= htmlspecialchars(($m['user_prenom'] ?? '') . ' ' . ($m['user_nom'] ?? '')) ?: '—' ?></small></td>
                        <td><small class="text-muted"><?= htmlspecialchars($m['notes'] ?? '—') ?></small></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
