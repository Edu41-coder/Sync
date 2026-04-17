<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-utensils', 'text' => 'Restauration', 'url' => BASE_URL . '/restauration/index'],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => BASE_URL . '/restauration/fournisseurs?residence_id=' . $residenceId],
    ['icon' => 'fas fa-edit', 'text' => $lien['fournisseur_nom'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Modifier : <?= htmlspecialchars($lien['fournisseur_nom']) ?>
                        <small class="text-muted ms-2">— <?= htmlspecialchars($lien['residence_nom']) ?></small>
                    </h5>
                </div>
                <form method="POST" action="<?= BASE_URL ?>/restauration/fournisseurs/update/<?= $lien['fournisseur_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="residence_id" value="<?= $residenceId ?>">
                    <div class="card-body">
                        <!-- Infos fournisseur (lecture seule) -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Informations fournisseur</h6>
                                <table class="table table-sm">
                                    <tr><td class="text-muted">Nom</td><td><strong><?= htmlspecialchars($lien['fournisseur_nom']) ?></strong></td></tr>
                                    <?php if ($lien['siret']): ?><tr><td class="text-muted">SIRET</td><td><code><?= htmlspecialchars($lien['siret']) ?></code></td></tr><?php endif; ?>
                                    <?php if ($lien['type_service']): ?><tr><td class="text-muted">Activité</td><td><?= htmlspecialchars($lien['type_service']) ?></td></tr><?php endif; ?>
                                    <?php if ($lien['telephone']): ?><tr><td class="text-muted">Tél. général</td><td><?= htmlspecialchars($lien['telephone']) ?></td></tr><?php endif; ?>
                                    <?php if ($lien['email']): ?><tr><td class="text-muted">Email</td><td><?= htmlspecialchars($lien['email']) ?></td></tr><?php endif; ?>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted mb-2">Résidence</h6>
                                <p><i class="fas fa-building me-2"></i><strong><?= htmlspecialchars($lien['residence_nom']) ?></strong></p>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="fas fa-sliders-h me-2"></i>Paramètres pour cette résidence</h6>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Contact local</label>
                                <input type="text" name="contact_local" class="form-control" value="<?= htmlspecialchars($lien['contact_local'] ?? '') ?>" placeholder="Nom du commercial dédié">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone local</label>
                                <input type="text" name="telephone_local" class="form-control" value="<?= htmlspecialchars($lien['telephone_local'] ?? '') ?>" placeholder="Ligne directe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jours de livraison</label>
                                <input type="text" name="jour_livraison" class="form-control" value="<?= htmlspecialchars($lien['jour_livraison'] ?? '') ?>" placeholder="lundi,mercredi,vendredi">
                                <small class="text-muted">Séparés par des virgules</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Délai de livraison (jours)</label>
                                <input type="number" name="delai_livraison_jours" class="form-control" value="<?= $lien['delai_livraison_jours'] ?? '' ?>" min="0">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notes</label>
                                <textarea name="notes" class="form-control" rows="3" placeholder="Conditions particulières, remarques..."><?= htmlspecialchars($lien['notes_residence'] ?? $lien['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex justify-content-between">
                        <a href="<?= BASE_URL ?>/restauration/fournisseurs?residence_id=<?= $residenceId ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Retour
                        </a>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
