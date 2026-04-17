<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-broom', 'text' => 'Ménage', 'url' => BASE_URL . '/menage/index'],
    ['icon' => 'fas fa-bed', 'text' => 'Intérieur', 'url' => BASE_URL . '/menage/interieur?residence_id=' . $tache['residence_id'] . '&date=' . $tache['date_tache']],
    ['icon' => 'fas fa-tasks', 'text' => 'Tâche #' . $tache['id'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutColors = ['a_faire'=>'warning','en_cours'=>'primary','termine'=>'success','pas_deranger'=>'secondary','annule'=>'danger'];
$statutLabels = ['a_faire'=>'À faire','en_cours'=>'En cours','termine'=>'Terminé','pas_deranger'=>'Pas déranger','annule'=>'Annulé'];
$totalItems = count($tache['checklist']);
$itemsFaits = count(array_filter($tache['checklist'], fn($i) => $i['fait']));
$pct = $totalItems > 0 ? round(($itemsFaits / $totalItems) * 100) : 0;
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- En-tête tâche -->
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center bg-<?= $statutColors[$tache['statut']] ?? 'secondary' ?> <?= in_array($tache['statut'], ['warning','a_faire']) ? 'text-dark' : 'text-white' ?>">
                    <h5 class="mb-0">
                        <i class="fas fa-bed me-2"></i>
                        Lot <?= htmlspecialchars($tache['numero_lot'] ?? '-') ?>
                        <small>(<?= $tache['lot_type'] ?? '' ?><?= $tache['etage'] ? ', étage ' . $tache['etage'] : '' ?>)</small>
                    </h5>
                    <span class="badge bg-dark fs-6"><?= $statutLabels[$tache['statut']] ?? $tache['statut'] ?></span>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr><td class="text-muted">Résidence</td><td><strong><?= htmlspecialchars($tache['residence_nom']) ?></strong></td></tr>
                                <tr><td class="text-muted">Occupant</td><td>
                                    <?= htmlspecialchars($tache['resident_nom'] ?? $tache['hote_nom'] ?? 'Vide') ?>
                                    <?php if ($tache['hote_id']): ?><span class="badge bg-info">hôte</span><?php endif; ?>
                                </td></tr>
                                <tr><td class="text-muted">Service</td><td><span class="badge bg-<?= $tache['niveau_service'] === 'premium' ? 'warning text-dark' : 'info' ?>"><?= $tache['niveau_service'] ?? '-' ?></span></td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <table class="table table-sm mb-0">
                                <tr><td class="text-muted">Assigné à</td><td><strong><?= $tache['employe_prenom'] ? htmlspecialchars($tache['employe_prenom'] . ' ' . $tache['employe_nom']) : '<span class="text-danger">Non assigné</span>' ?></strong></td></tr>
                                <tr><td class="text-muted">Poids</td><td><?= $tache['poids'] ?></td></tr>
                                <tr><td class="text-muted">Horaires</td><td><?= $tache['heure_debut'] ?? '-' ?> → <?= $tache['heure_fin'] ?? '-' ?></td></tr>
                            </table>
                        </div>
                    </div>

                    <!-- Barre de progression -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-1">
                            <small class="text-muted">Progression checklist</small>
                            <small><strong><?= $itemsFaits ?>/<?= $totalItems ?></strong> (<?= $pct ?>%)</small>
                        </div>
                        <div class="progress" style="height:12px">
                            <div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div>
                        </div>
                    </div>

                    <!-- Boutons d'action -->
                    <div class="mt-3 d-flex gap-2 flex-wrap">
                        <?php if ($isMine || $isManager): ?>
                            <?php if ($tache['statut'] === 'a_faire'): ?>
                            <a href="<?= BASE_URL ?>/menage/interieur/demarrer/<?= $tache['id'] ?>" class="btn btn-primary"><i class="fas fa-play me-2"></i>Démarrer</a>
                            <?php endif; ?>
                            <?php if ($tache['statut'] === 'en_cours'): ?>
                            <a href="<?= BASE_URL ?>/menage/interieur/terminer/<?= $tache['id'] ?>" class="btn btn-success" onclick="return confirm('Terminer cette tâche ?')"><i class="fas fa-check me-2"></i>Terminer</a>
                            <?php endif; ?>
                            <?php if (in_array($tache['statut'], ['a_faire', 'en_cours']) && $tache['hote_id']): ?>
                            <a href="<?= BASE_URL ?>/menage/interieur/pasDeranger/<?= $tache['id'] ?>" class="btn btn-secondary"><i class="fas fa-moon me-2"></i>Pas déranger</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Checklist interactive -->
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-clipboard-check me-2"></i>Checklist</h6></div>
                <div class="card-body">
                    <?php if (empty($tache['checklist'])): ?>
                        <p class="text-muted text-center">Aucun élément dans la checklist.</p>
                    <?php else: ?>
                        <?php $canCheck = ($isMine || $isManager) && in_array($tache['statut'], ['en_cours', 'a_faire']); ?>
                        <div class="list-group">
                            <?php foreach ($tache['checklist'] as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center <?= $item['fait'] ? 'list-group-item-success' : '' ?>">
                                <div class="form-check">
                                    <input class="form-check-input checklist-item" type="checkbox"
                                           id="item_<?= $item['id'] ?>" data-id="<?= $item['id'] ?>"
                                           <?= $item['fait'] ? 'checked' : '' ?>
                                           <?= !$canCheck ? 'disabled' : '' ?>>
                                    <label class="form-check-label <?= $item['fait'] ? 'text-decoration-line-through' : '' ?>" for="item_<?= $item['id'] ?>">
                                        <?= htmlspecialchars($item['libelle']) ?>
                                    </label>
                                </div>
                                <?php if ($item['heure_fait']): ?>
                                <small class="text-muted"><i class="fas fa-clock me-1"></i><?= $item['heure_fait'] ?></small>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Réaffectation (manager) -->
            <?php if ($isManager && !empty($staff)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Réaffecter</h6></div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/menage/interieur/reassigner/<?= $tache['id'] ?>" class="row g-2 align-items-end">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <div class="col-md-8">
                            <select name="employe_id" class="form-select form-select-sm" required>
                                <option value="">-- Nouvel employé --</option>
                                <?php foreach ($staff as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $s['id'] == $tache['employe_id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['prenom'] . ' ' . $s['nom']) ?> (<?= $s['role_nom'] ?? $s['role'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4"><button type="submit" class="btn btn-sm btn-outline-primary w-100"><i class="fas fa-exchange-alt me-1"></i>Réaffecter</button></div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <a href="<?= BASE_URL ?>/menage/interieur?residence_id=<?= $tache['residence_id'] ?>&date=<?= $tache['date_tache'] ?>" class="btn btn-secondary"><i class="fas fa-arrow-left me-2"></i>Retour</a>
        </div>
    </div>
</div>

<script>
// AJAX checklist toggle
document.querySelectorAll('.checklist-item').forEach(cb => {
    cb.addEventListener('change', function() {
        const itemId = this.dataset.id;
        const fait = this.checked;

        fetch('<?= BASE_URL ?>/menage/interieur/cocherItem', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ item_id: parseInt(itemId), fait: fait })
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const label = this.nextElementSibling;
                const parent = this.closest('.list-group-item');
                if (fait) {
                    label.classList.add('text-decoration-line-through');
                    parent.classList.add('list-group-item-success');
                } else {
                    label.classList.remove('text-decoration-line-through');
                    parent.classList.remove('list-group-item-success');
                }
                // Mettre à jour la barre de progression
                const total = document.querySelectorAll('.checklist-item').length;
                const done = document.querySelectorAll('.checklist-item:checked').length;
                const pct = Math.round((done / total) * 100);
                document.querySelector('.progress-bar').style.width = pct + '%';
                document.querySelector('.progress-bar').parentElement.previousElementSibling.querySelector('small strong').textContent = done + '/' + total;
            }
        });
    });
});
</script>
