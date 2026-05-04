<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord',        'url' => BASE_URL],
    ['icon' => 'fas fa-calculator',     'text' => 'Comptabilité',            'url' => BASE_URL . '/comptabilite/index'],
    ['icon' => 'fas fa-university',     'text' => 'Rapprochement bancaire', 'url' => BASE_URL . '/comptabilite/rapprochement'],
    ['icon' => 'fas fa-eye',            'text' => 'Import #' . (int)$import['id'], 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$badgeStatut = [
    'non_rapprochee' => 'warning',
    'rapprochee'     => 'success',
    'ignoree'        => 'secondary',
];
$labelsStatut = [
    'non_rapprochee' => 'À rapprocher',
    'rapprochee'     => 'Rapprochée',
    'ignoree'        => 'Ignorée',
];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-start mb-3">
        <div>
            <h2 class="mb-1">
                <i class="fas fa-university me-2 text-primary"></i>
                Import #<?= (int)$import['id'] ?>
                <small class="text-muted"><?= htmlspecialchars($import['nom_fichier']) ?></small>
            </h2>
            <p class="text-muted mb-0">
                <?= htmlspecialchars($import['residence_nom'] ?? '—') ?>
                <?php if ($import['periode_debut']): ?>
                · du <?= htmlspecialchars(date('d/m/Y', strtotime($import['periode_debut']))) ?>
                au <?= htmlspecialchars(date('d/m/Y', strtotime($import['periode_fin']))) ?>
                <?php endif; ?>
            </p>
        </div>
        <a href="<?= BASE_URL ?>/comptabilite/rapprochement" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-1"></i>Retour
        </a>
    </div>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-secondary">
                <div class="card-body text-center">
                    <div class="text-muted small">Total opérations</div>
                    <div class="h3 mb-0"><?= (int)$stats['total'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-success">
                <div class="card-body text-center">
                    <div class="text-muted small">Rapprochées</div>
                    <div class="h3 mb-0 text-success"><?= (int)$stats['rapprochees'] ?></div>
                    <small class="text-muted"><?= $stats['total'] > 0 ? round(100 * $stats['rapprochees'] / $stats['total']) : 0 ?>% du total</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-warning">
                <div class="card-body text-center">
                    <div class="text-muted small">À rapprocher</div>
                    <div class="h3 mb-0 text-warning"><?= (int)$stats['non_rapp'] ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-info">
                <div class="card-body text-center">
                    <div class="text-muted small">Total crédits / débits</div>
                    <div class="h6 mb-0">
                        <span class="text-success">+<?= number_format((float)$stats['total_credits'], 2, ',', ' ') ?> €</span>
                        /
                        <span class="text-danger">-<?= number_format((float)$stats['total_debits'], 2, ',', ' ') ?> €</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtres -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="btn-group btn-group-sm">
                <a class="btn btn-<?= !$statutFiltre ? 'primary' : 'outline-primary' ?>" href="<?= BASE_URL ?>/comptabilite/rapprochementShow/<?= (int)$import['id'] ?>">Toutes (<?= $stats['total'] ?>)</a>
                <a class="btn btn-<?= $statutFiltre === 'non_rapprochee' ? 'warning' : 'outline-warning' ?>" href="?statut=non_rapprochee">À rapprocher (<?= $stats['non_rapp'] ?>)</a>
                <a class="btn btn-<?= $statutFiltre === 'rapprochee' ? 'success' : 'outline-success' ?>" href="?statut=rapprochee">Rapprochées (<?= $stats['rapprochees'] ?>)</a>
                <a class="btn btn-<?= $statutFiltre === 'ignoree' ? 'secondary' : 'outline-secondary' ?>" href="?statut=ignoree">Ignorées (<?= $stats['ignorees'] ?>)</a>
            </div>
        </div>
    </div>

    <!-- Liste des opérations -->
    <?php if (empty($operations)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <p class="text-muted mb-0">Aucune opération avec ce filtre.</p>
        </div>
    </div>
    <?php else: ?>
    <?php foreach ($operations as $op):
        $statutClass = $badgeStatut[$op['statut']] ?? 'secondary';
        $sugs        = $suggestions[(int)$op['id']] ?? [];
    ?>
    <div class="card shadow-sm mb-2">
        <div class="card-body py-2">
            <div class="row align-items-center">
                <div class="col-md-1 text-center">
                    <small class="text-muted"><?= htmlspecialchars(date('d/m/Y', strtotime($op['date_operation']))) ?></small>
                </div>
                <div class="col-md-5">
                    <strong><?= htmlspecialchars(mb_substr($op['libelle'], 0, 80)) ?></strong>
                    <?php if (!empty($op['reference'])): ?>
                    <br><small class="text-muted">Réf : <?= htmlspecialchars($op['reference']) ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-end">
                    <?php if ((float)$op['montant'] > 0): ?>
                    <strong class="text-success">+<?= number_format((float)$op['montant'], 2, ',', ' ') ?> €</strong>
                    <?php else: ?>
                    <strong class="text-danger"><?= number_format((float)$op['montant'], 2, ',', ' ') ?> €</strong>
                    <?php endif; ?>
                </div>
                <div class="col-md-2 text-center">
                    <span class="badge bg-<?= $statutClass ?>"><?= htmlspecialchars($labelsStatut[$op['statut']] ?? $op['statut']) ?></span>
                </div>
                <div class="col-md-2 text-end">
                    <?php if ($op['statut'] === 'non_rapprochee'): ?>
                        <?php if (!empty($sugs)): ?>
                        <button class="btn btn-sm btn-success" data-bs-toggle="collapse" data-bs-target="#sugs-<?= (int)$op['id'] ?>">
                            <i class="fas fa-magic me-1"></i><?= count($sugs) ?> match(s)
                        </button>
                        <?php endif; ?>
                        <form method="POST" action="<?= BASE_URL ?>/comptabilite/rapprochementIgnorer/<?= (int)$op['id'] ?>" class="d-inline" onsubmit="return confirm('Marquer cette opération comme volontairement ignorée (frais bancaires hors compta, etc.) ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Ignorer">
                                <i class="fas fa-eye-slash"></i>
                            </button>
                        </form>
                    <?php elseif ($op['statut'] === 'rapprochee'): ?>
                        <small class="text-success">
                            <i class="fas fa-link"></i>
                            <?= htmlspecialchars($op['ecriture_libelle'] ?? 'écriture') ?>
                            <?= number_format((float)($op['ecriture_montant'] ?? 0), 2, ',', ' ') ?> €
                        </small>
                        <form method="POST" action="<?= BASE_URL ?>/comptabilite/rapprochementUnmatch/<?= (int)$op['id'] ?>" class="d-inline" onsubmit="return confirm('Annuler ce rapprochement ?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Défaire">
                                <i class="fas fa-unlink"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($op['statut'] === 'non_rapprochee' && !empty($sugs)): ?>
            <div class="collapse mt-2" id="sugs-<?= (int)$op['id'] ?>">
                <div class="border rounded p-2 bg-light">
                    <strong class="d-block mb-2 small"><i class="fas fa-magic me-1"></i>Écritures candidates :</strong>
                    <?php foreach ($sugs as $s):
                        $scoreColor = $s['score'] >= 80 ? 'success' : ($s['score'] >= 60 ? 'warning' : 'secondary');
                    ?>
                    <div class="d-flex align-items-center justify-content-between mb-1 p-1 border-bottom">
                        <div class="flex-grow-1">
                            <span class="badge bg-<?= $scoreColor ?> me-2" title="Score de similarité"><?= (int)$s['score'] ?>%</span>
                            <small><?= htmlspecialchars(date('d/m/Y', strtotime($s['date_ecriture']))) ?></small>
                            (<small class="text-muted">±<?= (int)$s['delta_jours'] ?>j</small>)
                            <strong class="mx-2"><?= number_format((float)$s['montant_ttc'], 2, ',', ' ') ?> €</strong>
                            (<small class="text-muted"><?= ($s['delta_montant'] >= 0 ? '+' : '') . number_format((float)$s['delta_montant'], 2, ',', ' ') ?> €</small>)
                            — <?= htmlspecialchars(mb_substr($s['libelle'], 0, 60)) ?>
                            <span class="badge bg-secondary ms-1"><?= htmlspecialchars(Ecriture::MODULES[$s['module_source']] ?? $s['module_source']) ?></span>
                        </div>
                        <form method="POST" action="<?= BASE_URL ?>/comptabilite/rapprochementMatch/<?= (int)$op['id'] ?>" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                            <input type="hidden" name="ecriture_id" value="<?= (int)$s['id'] ?>">
                            <input type="hidden" name="score" value="<?= (int)$s['score'] ?>">
                            <button type="submit" class="btn btn-sm btn-success">
                                <i class="fas fa-link me-1"></i>Rapprocher
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>
