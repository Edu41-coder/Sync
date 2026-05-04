<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-file-invoice', 'text' => 'Mes bulletins de paie', 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <h2 class="mb-3"><i class="fas fa-file-invoice me-2 text-primary"></i>Mes bulletins de paie</h2>

    <div class="alert alert-info small">
        <i class="fas fa-info-circle me-1"></i>
        Vos bulletins sont disponibles dès qu'ils sont émis par le service comptabilité. Pour toute question, contactez la direction.
    </div>

    <?php if (empty($bulletins)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun bulletin disponible</h5>
            <p class="text-muted mb-0">Vos bulletins apparaîtront ici dès leur émission.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Période</th>
                        <th class="text-end">Salaire brut</th>
                        <th class="text-end">Net à payer</th>
                        <th class="text-center">Statut</th>
                        <th class="text-center">Émis le</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bulletins as $b):
                        $statutLabel = $b['statut'] === 'emis' ? 'Émis' : ($b['statut'] === 'valide' ? 'En cours' : $b['statut']);
                        $statutCol   = $b['statut'] === 'emis' ? 'success' : 'info';
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($moisLabels[$b['periode_mois']] ?? '') ?> <?= (int)$b['periode_annee'] ?></strong>
                        </td>
                        <td class="text-end"><?= number_format((float)$b['total_brut'], 2, ',', ' ') ?> €</td>
                        <td class="text-end"><strong class="text-success"><?= number_format((float)$b['net_a_payer'], 2, ',', ' ') ?> €</strong></td>
                        <td class="text-center"><span class="badge bg-<?= $statutCol ?>"><?= $statutLabel ?></span></td>
                        <td class="text-center small text-muted">
                            <?= !empty($b['emis_at']) ? date('d/m/Y', strtotime($b['emis_at'])) : '—' ?>
                        </td>
                        <td class="text-end">
                            <a href="<?= BASE_URL ?>/bulletinPaie/print/<?= (int)$b['id'] ?>" target="_blank" class="btn btn-sm btn-primary">
                                <i class="fas fa-eye me-1"></i>Voir / Imprimer
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>
