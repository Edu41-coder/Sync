<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-tree', 'text' => 'Espaces', 'url' => BASE_URL . '/jardinage/espaces?residence_id=' . $espace['residence_id']],
    ['icon' => 'fas fa-tasks', 'text' => 'Tâches — ' . $espace['nom'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$frequenceLabels = [
    'quotidien' => 'Quotidien', 'hebdo' => 'Hebdomadaire', 'bi_mensuel' => 'Bi-mensuel',
    'mensuel' => 'Mensuel', 'saisonnier' => 'Saisonnier', 'ponctuel' => 'Ponctuel'
];
$saisonLabels = [
    'toutes' => 'Toutes saisons', 'printemps' => 'Printemps',
    'ete' => 'Été', 'automne' => 'Automne', 'hiver' => 'Hiver'
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-0"><i class="fas fa-tasks me-2 text-success"></i>Tâches récurrentes</h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($espace['nom']) ?> — <?= htmlspecialchars($espace['residence_nom']) ?></p>
        </div>
        <a href="<?= BASE_URL ?>/jardinage/espaces?residence_id=<?= $espace['residence_id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
    </div>

    <div class="row g-4">
        <div class="col-lg-<?= $isManager ? '7' : '12' ?>">
            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-list me-2"></i>Tâches affectées (<?= count($taches) ?>)</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($taches)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucune tâche récurrente définie.</p>
                    <?php else: ?>
                    <table class="table table-hover mb-0">
                        <thead class="table-light"><tr><th>Tâche</th><th>Fréquence</th><th>Saison</th><th class="text-end">Durée</th><?php if ($isManager): ?><th></th><?php endif; ?></tr></thead>
                        <tbody>
                            <?php foreach ($taches as $t): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($t['nom']) ?></strong><?php if ($t['notes']): ?><br><small class="text-muted"><?= htmlspecialchars($t['notes']) ?></small><?php endif; ?></td>
                                <td><span class="badge bg-info"><?= $frequenceLabels[$t['frequence']] ?? $t['frequence'] ?></span></td>
                                <td><small><?= $saisonLabels[$t['saison']] ?? $t['saison'] ?></small></td>
                                <td class="text-end"><?= $t['duree_estimee_min'] ? $t['duree_estimee_min'] . ' min' : '—' ?></td>
                                <?php if ($isManager): ?>
                                <td class="text-end">
                                    <form method="POST" class="d-inline" onsubmit="return confirm('Supprimer cette tâche ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <input type="hidden" name="delete_tache_id" value="<?= $t['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($isManager): ?>
        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white"><h6 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter une tâche</h6></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="mb-3">
                            <label class="form-label">Nom <span class="text-danger">*</span></label>
                            <input type="text" name="nom" class="form-control" required maxlength="200" placeholder="Ex : Arroser, Tailler...">
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Fréquence</label>
                                <select name="frequence" class="form-select">
                                    <?php foreach ($frequenceLabels as $k => $l): ?>
                                    <option value="<?= $k ?>" <?= $k === 'hebdo' ? 'selected' : '' ?>><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Saison</label>
                                <select name="saison" class="form-select">
                                    <?php foreach ($saisonLabels as $k => $l): ?>
                                    <option value="<?= $k ?>"><?= $l ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Durée estimée (min)</label>
                            <input type="number" min="0" name="duree_estimee_min" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                        <button type="submit" class="btn btn-success w-100"><i class="fas fa-plus me-1"></i>Ajouter</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
