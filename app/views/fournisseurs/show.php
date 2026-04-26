<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-truck-loading', 'text' => 'Fournisseurs', 'url' => BASE_URL . '/fournisseur/index'],
    ['icon' => 'fas fa-building', 'text' => htmlspecialchars($fournisseur['nom']), 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$typesLabels = Fournisseur::TYPES_SERVICE;
$typeColors = [
    'restauration' => 'warning', 'menage' => 'info', 'jardinage' => 'success',
    'piscine' => 'primary', 'travaux_elec' => 'danger', 'travaux_plomberie' => 'secondary', 'autre' => 'dark'
];
$types = $fournisseur['type_service'] ? explode(',', $fournisseur['type_service']) : [];

$moduleLabels = ['restauration' => 'Restauration', 'menage' => 'Ménage', 'jardinage' => 'Jardinage'];
$statutLabels = [
    'brouillon' => 'Brouillon', 'envoyee' => 'Envoyée',
    'livree_partiel' => 'Livrée partiel', 'livree' => 'Livrée',
    'facturee' => 'Facturée', 'annulee' => 'Annulée'
];
$statutColors = [
    'brouillon' => 'secondary', 'envoyee' => 'info',
    'livree_partiel' => 'warning', 'livree' => 'success',
    'facturee' => 'primary', 'annulee' => 'dark'
];

$residencesNonLiees = $residencesNonLiees ?? [];
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0"><?= htmlspecialchars($fournisseur['nom']) ?>
                <span class="badge bg-<?= $fournisseur['actif'] ? 'success' : 'secondary' ?> ms-2"><?= $fournisseur['actif'] ? 'Actif' : 'Inactif' ?></span>
            </h2>
            <div class="mt-1">
                <?php foreach ($types as $t): if (!$t) continue; ?>
                    <span class="badge bg-<?= $typeColors[$t] ?? 'secondary' ?> me-1"><?= $typesLabels[$t] ?? $t ?></span>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= BASE_URL ?>/fournisseur/index" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <a href="<?= BASE_URL ?>/fournisseur/edit/<?= (int)$fournisseur['id'] ?>" class="btn btn-primary"><i class="fas fa-edit me-1"></i>Modifier</a>
            <?php if ($fournisseur['actif']): ?>
            <a href="<?= BASE_URL ?>/fournisseur/delete/<?= (int)$fournisseur['id'] ?>" class="btn btn-outline-danger" onclick="return confirm('Désactiver ce fournisseur ?')"><i class="fas fa-times me-1"></i>Désactiver</a>
            <?php else: ?>
            <a href="<?= BASE_URL ?>/fournisseur/activate/<?= (int)$fournisseur['id'] ?>" class="btn btn-outline-success"><i class="fas fa-check me-1"></i>Réactiver</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-address-card me-2"></i>Identité & contact</h6></div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">SIRET</dt><dd class="col-7"><?= $fournisseur['siret'] ? htmlspecialchars($fournisseur['siret']) : '—' ?></dd>
                        <dt class="col-5">Adresse</dt><dd class="col-7"><?= $fournisseur['adresse'] ? htmlspecialchars($fournisseur['adresse']) : '—' ?></dd>
                        <dt class="col-5">Code postal</dt><dd class="col-7"><?= $fournisseur['code_postal'] ?: '—' ?></dd>
                        <dt class="col-5">Ville</dt><dd class="col-7"><?= $fournisseur['ville'] ? htmlspecialchars($fournisseur['ville']) : '—' ?></dd>
                        <dt class="col-5">Contact</dt><dd class="col-7"><?= $fournisseur['contact_nom'] ? htmlspecialchars($fournisseur['contact_nom']) : '—' ?></dd>
                        <dt class="col-5">Téléphone</dt><dd class="col-7"><?= $fournisseur['telephone'] ? '<a href="tel:' . htmlspecialchars($fournisseur['telephone']) . '" class="text-decoration-none">' . htmlspecialchars($fournisseur['telephone']) . '</a>' : '—' ?></dd>
                        <dt class="col-5">Email</dt><dd class="col-7"><?= $fournisseur['email'] ? '<a href="mailto:' . htmlspecialchars($fournisseur['email']) . '" class="text-decoration-none">' . htmlspecialchars($fournisseur['email']) . '</a>' : '—' ?></dd>
                        <dt class="col-5">IBAN</dt><dd class="col-7"><small class="text-muted"><?= $fournisseur['iban'] ? htmlspecialchars($fournisseur['iban']) : '—' ?></small></dd>
                        <?php if ($fournisseur['notes']): ?>
                        <dt class="col-12 mt-2">Notes</dt>
                        <dd class="col-12"><small class="text-muted"><?= nl2br(htmlspecialchars($fournisseur['notes'])) ?></small></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-building me-2"></i>Résidences liées (<?= count(array_filter($residences, fn($r) => $r['statut'] === 'actif')) ?> actives / <?= count($residences) ?> total)</h6>
                    <?php if (!empty($residencesNonLiees)): ?>
                    <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalLier">
                        <i class="fas fa-plus me-1"></i>Lier une résidence
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($residences)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucune résidence liée.</p>
                    <?php else: ?>
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Résidence</th><th>Contact local</th><th>Livraison</th><th class="text-center">Statut</th><th class="text-end">Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($residences as $r): ?>
                            <tr class="<?= $r['statut'] === 'actif' ? '' : 'text-muted' ?>">
                                <td>
                                    <strong><?= htmlspecialchars($r['nom']) ?></strong>
                                    <?php if ($r['ville']): ?><br><small class="text-muted"><?= htmlspecialchars($r['ville']) ?></small><?php endif; ?>
                                </td>
                                <td class="small">
                                    <?= $r['contact_local'] ? htmlspecialchars($r['contact_local']) : '—' ?>
                                    <?php if ($r['telephone_local']): ?><br><i class="fas fa-phone me-1 text-muted"></i><?= htmlspecialchars($r['telephone_local']) ?><?php endif; ?>
                                </td>
                                <td class="small">
                                    <?php if ($r['jour_livraison']): ?><span class="badge bg-secondary"><?= htmlspecialchars($r['jour_livraison']) ?></span><?php endif; ?>
                                    <?php if ($r['delai_livraison_jours']): ?><br><small class="text-muted"><?= (int)$r['delai_livraison_jours'] ?> j délai</small><?php endif; ?>
                                </td>
                                <td class="text-center"><span class="badge bg-<?= $r['statut'] === 'actif' ? 'success' : 'secondary' ?>"><?= $r['statut'] ?></span></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary" onclick='editLien(<?= json_encode($r) ?>)' title="Modifier"><i class="fas fa-edit"></i></button>
                                    <?php if ($r['statut'] === 'actif'): ?>
                                    <a href="<?= BASE_URL ?>/fournisseur/delier/<?= (int)$r['pivot_id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Délier ce fournisseur de la résidence ?')" title="Délier"><i class="fas fa-unlink"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Commandes passées (multi-modules) -->
    <div class="card shadow-sm">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-truck me-2"></i>Historique commandes (tous modules) — <?= count($commandes) ?> dernières</h6>
        </div>
        <div class="card-body p-0">
            <?php if (empty($commandes)): ?>
            <p class="text-center text-muted p-4 mb-0">Aucune commande enregistrée pour ce fournisseur.</p>
            <?php else: ?>
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light"><tr><th>Date</th><th>N°</th><th>Module</th><th>Résidence</th><th>Statut</th><th class="text-end">Montant TTC</th></tr></thead>
                <tbody>
                    <?php foreach ($commandes as $c): ?>
                    <tr>
                        <td class="small"><?= date('d/m/Y', strtotime($c['date_commande'])) ?></td>
                        <td><strong><?= htmlspecialchars($c['numero_commande']) ?></strong></td>
                        <td><span class="badge bg-<?= $typeColors[$c['module']] ?? 'secondary' ?>"><?= $moduleLabels[$c['module']] ?? $c['module'] ?></span></td>
                        <td class="small"><?= htmlspecialchars($c['residence_nom']) ?></td>
                        <td><span class="badge bg-<?= $statutColors[$c['statut']] ?? 'secondary' ?>"><?= $statutLabels[$c['statut']] ?? $c['statut'] ?></span></td>
                        <td class="text-end"><strong><?= number_format((float)$c['montant_total_ttc'], 2, ',', ' ') ?> €</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal lier une résidence -->
<?php if (!empty($residencesNonLiees)): ?>
<div class="modal fade" id="modalLier" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/fournisseur/lier/<?= (int)$fournisseur['id'] ?>">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-link me-2"></i>Lier une résidence</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="mb-3">
                        <label class="form-label">Résidence <span class="text-danger">*</span></label>
                        <select name="residence_id" class="form-select" required>
                            <option value="">— Sélectionner —</option>
                            <?php foreach ($residencesNonLiees as $r): ?>
                            <option value="<?= (int)$r['id'] ?>"><?= htmlspecialchars($r['nom']) ?><?= $r['ville'] ? ' — ' . htmlspecialchars($r['ville']) : '' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact local</label>
                            <input type="text" name="contact_local" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone local</label>
                            <input type="text" name="telephone_local" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Jour(s) de livraison</label>
                            <input type="text" name="jour_livraison" class="form-control" maxlength="50" placeholder="Lundi, Jeudi...">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Délai (jours)</label>
                            <input type="number" min="0" name="delai_livraison_jours" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-link me-1"></i>Lier</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal édition lien -->
<div class="modal fade" id="modalEditLien" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" id="formEditLien">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier le lien — <span id="editResNom"></span></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Contact local</label>
                            <input type="text" name="contact_local" id="editContactLocal" class="form-control" maxlength="150">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Téléphone local</label>
                            <input type="text" name="telephone_local" id="editTelLocal" class="form-control" maxlength="30">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Jour(s) de livraison</label>
                            <input type="text" name="jour_livraison" id="editJourLiv" class="form-control" maxlength="50">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Délai (jours)</label>
                            <input type="number" min="0" name="delai_livraison_jours" id="editDelai" class="form-control">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function editLien(r) {
    document.getElementById('formEditLien').action = '<?= BASE_URL ?>/fournisseur/updateLien/' + r.pivot_id;
    document.getElementById('editResNom').textContent = r.nom;
    document.getElementById('editContactLocal').value = r.contact_local || '';
    document.getElementById('editTelLocal').value = r.telephone_local || '';
    document.getElementById('editJourLiv').value = r.jour_livraison || '';
    document.getElementById('editDelai').value = r.delai_livraison_jours || '';
    document.getElementById('editNotes').value = r.pivot_notes || '';
    new bootstrap.Modal(document.getElementById('modalEditLien')).show();
}
</script>
