<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-seedling', 'text' => 'Jardinage', 'url' => BASE_URL . '/jardinage/index'],
    ['icon' => 'fas fa-hive', 'text' => 'Ruches', 'url' => BASE_URL . '/jardinage/ruches?residence_id=' . $ruche['residence_id']],
    ['icon' => 'fas fa-bug', 'text' => 'Ruche ' . $ruche['numero'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutLabels = ['active' => 'Active', 'essaim_capture' => 'Essaim capturé', 'inactive' => 'Inactive', 'morte' => 'Morte'];
$statutColors = ['active' => 'success', 'essaim_capture' => 'info', 'inactive' => 'secondary', 'morte' => 'danger'];
$interventionLabels = [
    'inspection' => 'Inspection', 'recolte' => 'Récolte', 'traitement' => 'Traitement',
    'nourrissement' => 'Nourrissement', 'changement_reine' => 'Changement de reine',
    'division' => 'Division', 'urgence' => 'Urgence', 'autre' => 'Autre'
];
$interventionIcons = [
    'inspection' => 'fa-search', 'recolte' => 'fa-tint', 'traitement' => 'fa-prescription-bottle',
    'nourrissement' => 'fa-utensils', 'changement_reine' => 'fa-crown',
    'division' => 'fa-code-branch', 'urgence' => 'fa-exclamation-triangle', 'autre' => 'fa-circle'
];
$couvainLabels = ['excellent' => 'Excellent', 'bon' => 'Bon', 'moyen' => 'Moyen', 'faible' => 'Faible', 'absent' => 'Absent'];

// Stats du carnet
$totalMiel = 0;
$nbRecoltes = 0;
$anneeEnCours = date('Y');
$mielAnneeEnCours = 0;
foreach ($visites as $v) {
    if ($v['type_intervention'] === 'recolte' && $v['quantite_miel_kg']) {
        $totalMiel += (float)$v['quantite_miel_kg'];
        $nbRecoltes++;
        if (date('Y', strtotime($v['date_visite'])) == $anneeEnCours) {
            $mielAnneeEnCours += (float)$v['quantite_miel_kg'];
        }
    }
}
$derniereVisite = $visites[0] ?? null;
$jourDepuisVisite = $derniereVisite ? floor((time() - strtotime($derniereVisite['date_visite'])) / 86400) : null;
$alerteVisite = $ruche['statut'] === 'active' && ($derniereVisite === null || $jourDepuisVisite > 30);
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-0">🐝 Ruche <?= htmlspecialchars($ruche['numero']) ?>
                <span class="badge bg-<?= $statutColors[$ruche['statut']] ?? 'secondary' ?> ms-2"><?= $statutLabels[$ruche['statut']] ?? $ruche['statut'] ?></span>
            </h2>
            <p class="text-muted mb-0"><?= htmlspecialchars($ruche['residence_nom']) ?><?php if ($ruche['espace_nom']): ?> — <?= htmlspecialchars($ruche['espace_nom']) ?><?php endif; ?></p>
        </div>
        <div>
            <a href="<?= BASE_URL ?>/jardinage/ruches?residence_id=<?= $ruche['residence_id'] ?>" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            <?php if (!empty($visites)): ?>
            <a href="<?= BASE_URL ?>/jardinage/ruches/exportCarnet/<?= (int)$ruche['id'] ?>" class="btn btn-outline-success" title="Export CSV du carnet de visite (réglementation FR)">
                <i class="fas fa-file-csv me-1"></i>Export carnet
            </a>
            <?php endif; ?>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalVisite"><i class="fas fa-plus me-1"></i>Nouvelle visite</button>
            <?php if ($isManager): ?>
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRucheEdit"><i class="fas fa-edit me-1"></i>Modifier ruche</button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($alerteVisite): ?>
    <div class="alert alert-warning"><i class="fas fa-exclamation-triangle me-2"></i>
        <?php if ($derniereVisite): ?>
        Dernière visite il y a <strong><?= $jourDepuisVisite ?> jours</strong> (seuil : 30 jours). Pensez à planifier une inspection.
        <?php else: ?>
        Aucune visite enregistrée pour cette ruche active.
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <?php if (!empty($ruche['photo'])): ?>
                <img src="<?= BASE_URL . '/' . htmlspecialchars($ruche['photo']) ?>" alt="" class="card-img-top" style="max-height:280px;object-fit:cover;cursor:zoom-in" ondblclick="showPhoto('<?= BASE_URL . '/' . htmlspecialchars($ruche['photo']) ?>', <?= htmlspecialchars(json_encode('Ruche ' . $ruche['numero']), ENT_QUOTES) ?>)" title="Double-clic pour agrandir">
                <?php else: ?>
                <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height:200px;font-size:4rem">🐝</div>
                <?php endif; ?>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5">Type</dt><dd class="col-7"><?= $ruche['type_ruche'] ? htmlspecialchars($ruche['type_ruche']) : '—' ?></dd>
                        <dt class="col-5">Race</dt><dd class="col-7"><?= $ruche['race_abeilles'] ? htmlspecialchars($ruche['race_abeilles']) : '—' ?></dd>
                        <dt class="col-5">Installée</dt><dd class="col-7"><?= $ruche['date_installation'] ? date('d/m/Y', strtotime($ruche['date_installation'])) : '—' ?></dd>
                        <dt class="col-5">Emplacement</dt><dd class="col-7"><?= $ruche['espace_nom'] ? htmlspecialchars($ruche['espace_nom']) : '—' ?></dd>
                        <?php if ($ruche['notes']): ?>
                        <dt class="col-12 mt-2">Notes</dt>
                        <dd class="col-12"><small class="text-muted"><?= nl2br(htmlspecialchars($ruche['notes'])) ?></small></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="row g-3 mb-3">
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-start border-info border-4">
                        <div class="card-body">
                            <h6 class="text-muted small mb-1">Visites total</h6>
                            <h3 class="mb-0"><?= count($visites) ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-start border-warning border-4">
                        <div class="card-body">
                            <h6 class="text-muted small mb-1">Récoltes</h6>
                            <h3 class="mb-0"><?= $nbRecoltes ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-start border-success border-4">
                        <div class="card-body">
                            <h6 class="text-muted small mb-1">Miel <?= $anneeEnCours ?></h6>
                            <h3 class="mb-0 text-success"><?= number_format($mielAnneeEnCours, 1, ',', ' ') ?> kg</h3>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card shadow-sm border-start border-secondary border-4">
                        <div class="card-body">
                            <h6 class="text-muted small mb-1">Miel total</h6>
                            <h3 class="mb-0"><?= number_format($totalMiel, 1, ',', ' ') ?> kg</h3>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-book-open me-2"></i>Carnet de visite (<?= count($visites) ?>)</h6></div>
                <div class="card-body p-0">
                    <?php if (empty($visites)): ?>
                    <p class="text-center text-muted p-4 mb-0">Aucune visite enregistrée. <a href="#" data-bs-toggle="modal" data-bs-target="#modalVisite">Ajouter la première</a>.</p>
                    <?php else: ?>
                    <div class="mb-2 p-2"><input type="text" id="searchInput" class="form-control form-control-sm" placeholder="Rechercher dans le carnet..."></div>
                    <table class="table table-hover table-sm mb-0" id="visitesTable">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Intervention</th>
                                <th>Couvain</th>
                                <th class="text-center">Reine</th>
                                <th class="text-end">Miel</th>
                                <th>Observations / traitement</th>
                                <th>Par</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($visites as $v): ?>
                            <tr>
                                <td class="small" data-sort="<?= strtotime($v['date_visite']) ?>"><?= date('d/m/Y', strtotime($v['date_visite'])) ?></td>
                                <td data-sort="<?= $v['type_intervention'] ?>"><i class="fas <?= $interventionIcons[$v['type_intervention']] ?? 'fa-circle' ?> me-1 text-warning"></i><small><?= $interventionLabels[$v['type_intervention']] ?? $v['type_intervention'] ?></small></td>
                                <td><?= $v['couvain_etat'] ? '<small>' . ($couvainLabels[$v['couvain_etat']] ?? $v['couvain_etat']) . '</small>' : '—' ?></td>
                                <td class="text-center" data-sort="<?= $v['reine_vue'] === null ? -1 : (int)$v['reine_vue'] ?>">
                                    <?php if ($v['reine_vue'] === null): ?>—<?php elseif ($v['reine_vue']): ?><i class="fas fa-check text-success"></i><?php else: ?><i class="fas fa-times text-muted"></i><?php endif; ?>
                                </td>
                                <td class="text-end" data-sort="<?= (float)($v['quantite_miel_kg'] ?? 0) ?>"><?= $v['quantite_miel_kg'] ? '<strong>' . number_format($v['quantite_miel_kg'], 1, ',', ' ') . '</strong> kg' : '—' ?></td>
                                <td class="small">
                                    <?php if ($v['traitement_produit']): ?><div><strong><?= htmlspecialchars($v['traitement_produit']) ?></strong></div><?php endif; ?>
                                    <?php if ($v['observations']): ?><div class="text-muted"><?= nl2br(htmlspecialchars($v['observations'])) ?></div><?php endif; ?>
                                </td>
                                <td class="small"><?= $v['user_prenom'] ? htmlspecialchars($v['user_prenom'] . ' ' . $v['user_nom']) : '—' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
                <?php if (!empty($visites)): ?>
                <div class="card-footer d-flex justify-content-between align-items-center">
                    <div class="text-muted small" id="tableInfo"></div>
                    <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
                </div>
                <?php endif; ?>
            </div>

            <!-- Traitements recommandés -->
            <?php if (!empty($traitementsRuche)):
                $priorColorsT = [1 => 'danger', 2 => 'warning', 3 => 'info'];
                $priorLabelsT = [1 => 'Critique', 2 => 'Recommandé', 3 => 'Optionnel'];
                $moisNomsT = ['', 'Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-shield-virus me-2"></i>Traitements recommandés <?= date('Y') ?></h6></div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0">
                        <thead class="table-light">
                            <tr><th>Traitement</th><th>Fenêtre</th><th>Priorité</th><th>Produit</th><th class="text-center">Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($traitementsRuche as $t):
                                $rowClass = '';
                                if ($t['fait']) $rowClass = 'table-success';
                                elseif ($t['en_fenetre']) $rowClass = 'table-danger';
                                $fen = $t['mois_debut'] === $t['mois_fin']
                                    ? $moisNomsT[$t['mois_debut']]
                                    : $moisNomsT[$t['mois_debut']] . ' → ' . $moisNomsT[$t['mois_fin']];
                            ?>
                            <tr class="<?= $rowClass ?>">
                                <td>
                                    <strong><?= htmlspecialchars($t['nom']) ?></strong>
                                    <?php if ($t['description']): ?><br><small class="text-muted"><?= htmlspecialchars(mb_strimwidth($t['description'], 0, 150, '…')) ?></small><?php endif; ?>
                                </td>
                                <td class="small"><?= $fen ?></td>
                                <td><span class="badge bg-<?= $priorColorsT[$t['priorite']] ?? 'secondary' ?>"><?= $priorLabelsT[$t['priorite']] ?></span></td>
                                <td class="small"><?= $t['produit_suggere'] ? htmlspecialchars($t['produit_suggere']) : '—' ?></td>
                                <td class="text-center">
                                    <?php if ($t['fait']): ?>
                                        <i class="fas fa-check-circle text-success"></i> <small>Fait <?= date('d/m/Y', strtotime($t['date_visite'])) ?></small>
                                        <?php if ($t['traitement_produit']): ?><br><small class="text-muted">(<?= htmlspecialchars($t['traitement_produit']) ?>)</small><?php endif; ?>
                                    <?php elseif ($t['en_fenetre']): ?>
                                        <span class="badge bg-danger"><i class="fas fa-exclamation-triangle me-1"></i>À faire maintenant</span>
                                    <?php else: ?>
                                        <small class="text-muted"><i class="fas fa-clock me-1"></i>Hors fenêtre</small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer small text-muted">
                    <i class="fas fa-info-circle me-1"></i>Pour marquer un traitement comme fait, enregistrez une visite de type <strong>Traitement</strong>.
                </div>
            </div>
            <?php endif; ?>

            <!-- Historique statut -->
            <?php
            $statutBadgeClass = ['active' => 'success', 'essaim_capture' => 'info', 'inactive' => 'secondary', 'morte' => 'danger'];
            $statutIcons = ['active' => 'fa-play-circle', 'essaim_capture' => 'fa-bug', 'inactive' => 'fa-pause-circle', 'morte' => 'fa-skull'];
            ?>
            <div class="card shadow-sm mt-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-history me-2"></i>Historique statut (<?= count($statutHistory) ?>)</h6></div>
                <div class="card-body">
                    <?php if (empty($statutHistory)): ?>
                    <p class="text-center text-muted mb-0">Aucun historique disponible.</p>
                    <?php else: ?>
                    <ul class="list-unstyled mb-0 timeline-statut">
                        <?php foreach ($statutHistory as $idx => $log):
                            $avant = $log['statut_avant'];
                            $apres = $log['statut_apres'];
                            $isCreation = $avant === null;
                        ?>
                        <li class="d-flex align-items-start mb-3<?= $idx === count($statutHistory) - 1 ? '' : ' pb-3 border-bottom' ?>">
                            <div class="me-3 text-center" style="min-width:80px">
                                <span class="badge bg-<?= $statutBadgeClass[$apres] ?? 'secondary' ?> p-2">
                                    <i class="fas <?= $statutIcons[$apres] ?? 'fa-circle' ?> me-1"></i><?= $statutLabels[$apres] ?? $apres ?>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <div>
                                    <?php if ($isCreation): ?>
                                        <strong class="text-success"><i class="fas fa-plus-circle me-1"></i>Création</strong>
                                        — statut initial : <strong><?= $statutLabels[$apres] ?? $apres ?></strong>
                                    <?php else: ?>
                                        <strong>Changement de statut</strong> :
                                        <span class="badge bg-<?= $statutBadgeClass[$avant] ?? 'secondary' ?> bg-opacity-25 text-<?= $statutBadgeClass[$avant] ?? 'secondary' ?>"><?= $statutLabels[$avant] ?? $avant ?></span>
                                        <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                        <span class="badge bg-<?= $statutBadgeClass[$apres] ?? 'secondary' ?>"><?= $statutLabels[$apres] ?? $apres ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($log['motif']): ?>
                                <div class="small text-muted mt-1"><i class="fas fa-comment me-1"></i><?= htmlspecialchars($log['motif']) ?></div>
                                <?php endif; ?>
                                <div class="small text-muted mt-1">
                                    <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($log['changed_at'])) ?>
                                    <?php if ($log['user_prenom']): ?>
                                    · <i class="fas fa-user me-1"></i><?= htmlspecialchars($log['user_prenom'] . ' ' . $log['user_nom']) ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal nouvelle visite (tous rôles) -->
<div class="modal fade" id="modalVisite" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/ruches/visite/<?= $ruche['id'] ?>">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title"><i class="fas fa-plus me-2"></i>Nouvelle visite — Ruche <?= htmlspecialchars($ruche['numero']) ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Date <span class="text-danger">*</span></label>
                            <input type="date" name="date_visite" class="form-control" required value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Type d'intervention <span class="text-danger">*</span></label>
                            <select name="type_intervention" id="fieldTypeIntervention" class="form-select" required onchange="toggleMielField()">
                                <?php foreach ($interventionLabels as $k => $l): ?>
                                <option value="<?= $k ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">État du couvain</label>
                            <select name="couvain_etat" class="form-select">
                                <option value="">— Non évalué —</option>
                                <?php foreach ($couvainLabels as $k => $l): ?>
                                <option value="<?= $k ?>"><?= $l ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reine vue</label>
                            <select name="reine_vue" class="form-select">
                                <option value="">— Non évalué —</option>
                                <option value="1">Oui</option>
                                <option value="0">Non</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="divMielField" style="display:none">
                            <label class="form-label">Quantité de miel récolté (kg)</label>
                            <input type="number" step="0.1" min="0" name="quantite_miel_kg" class="form-control" placeholder="Ex : 12.5">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Produit de traitement</label>
                            <input type="text" name="traitement_produit" class="form-control" maxlength="150" placeholder="Ex : Apivar, Apistan...">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Observations</label>
                            <textarea name="observations" class="form-control" rows="3" placeholder="Constat, interventions réalisées, anomalies..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer la visite</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if ($isManager): ?>
<!-- Modal édition ruche (manager) -->
<div class="modal fade" id="modalRucheEdit" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/jardinage/ruches/update/<?= $ruche['id'] ?>" enctype="multipart/form-data">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Modifier ruche</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="redirect_show" value="1">
                    <div class="row g-3">
                        <div class="col-md-4"><label class="form-label">Numéro <span class="text-danger">*</span></label>
                            <input type="text" name="numero" class="form-control" required maxlength="50" value="<?= htmlspecialchars($ruche['numero']) ?>"></div>
                        <div class="col-md-4"><label class="form-label">Type</label>
                            <input type="text" name="type_ruche" class="form-control" maxlength="100" value="<?= htmlspecialchars($ruche['type_ruche'] ?? '') ?>"></div>
                        <div class="col-md-4"><label class="form-label">Statut</label>
                            <select name="statut" id="fieldStatutEdit" class="form-select" data-original="<?= $ruche['statut'] ?>" onchange="toggleMotifStatut()">
                                <?php foreach ($statutLabels as $k => $l): ?>
                                <option value="<?= $k ?>" <?= $ruche['statut'] === $k ? 'selected' : '' ?>><?= $l ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-12" id="divMotifStatut" style="display:none">
                            <label class="form-label">Motif du changement de statut <small class="text-muted">(optionnel mais recommandé pour la traçabilité)</small></label>
                            <input type="text" name="motif_statut" class="form-control" maxlength="255"
                                   placeholder="Ex : Essaim capturé en ville, attaque de frelons, préparation hivernage, morte suite à varroase…">
                        </div>
                        <div class="col-md-6"><label class="form-label">Espace rucher</label>
                            <select name="espace_id" class="form-select">
                                <option value="">— Aucun —</option>
                                <?php foreach ($espacesRucher as $e): ?>
                                <option value="<?= $e['id'] ?>" <?= $ruche['espace_id'] == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['nom']) ?></option>
                                <?php endforeach; ?>
                            </select></div>
                        <div class="col-md-3"><label class="form-label">Date installation</label>
                            <input type="date" name="date_installation" class="form-control" value="<?= $ruche['date_installation'] ?? '' ?>"></div>
                        <div class="col-md-3"><label class="form-label">Race d'abeilles</label>
                            <input type="text" name="race_abeilles" class="form-control" maxlength="100" value="<?= htmlspecialchars($ruche['race_abeilles'] ?? '') ?>"></div>
                        <div class="col-12">
                            <label class="form-label">Photo <small class="text-muted">(JPG, PNG, WEBP · max 5 Mo)</small></label>
                            <?php if (!empty($ruche['photo'])): ?>
                            <div class="mb-2">
                                <img src="<?= BASE_URL . '/' . htmlspecialchars($ruche['photo']) ?>" alt="" class="rounded me-2" style="width:120px;height:90px;object-fit:cover">
                                <button type="submit" form="formDeletePhotoRuche" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
                            </div>
                            <?php endif; ?>
                            <input type="file" name="photo" class="form-control" accept="image/jpeg,image/png,image/webp">
                        </div>
                        <div class="col-12"><label class="form-label">Notes</label>
                            <textarea name="notes" class="form-control" rows="2"><?= htmlspecialchars($ruche['notes'] ?? '') ?></textarea></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>

            <?php if (!empty($ruche['photo'])): ?>
            <!-- Form séparé pour suppression photo ruche (HTML interdit le form nesting) -->
            <form id="formDeletePhotoRuche" method="POST" action="<?= BASE_URL ?>/jardinage/ruches/photoDelete/<?= (int)$ruche['id'] ?>" onsubmit="return confirm('Supprimer cette photo ?')" style="display:none">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal viewer photo -->
<div class="modal fade" id="photoViewer" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title" id="photoViewerTitle"></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body text-center p-0">
                <img id="photoViewerImg" src="" alt="" style="max-width:100%;max-height:80vh;object-fit:contain">
            </div>
        </div>
    </div>
</div>

<script>
function toggleMielField() {
    const type = document.getElementById('fieldTypeIntervention').value;
    document.getElementById('divMielField').style.display = (type === 'recolte') ? 'block' : 'none';
}
function toggleMotifStatut() {
    const sel = document.getElementById('fieldStatutEdit');
    if (!sel) return;
    const changed = sel.value !== sel.dataset.original;
    document.getElementById('divMotifStatut').style.display = changed ? 'block' : 'none';
}
function showPhoto(src, nom) {
    document.getElementById('photoViewerImg').src = src;
    document.getElementById('photoViewerTitle').textContent = nom;
    new bootstrap.Modal(document.getElementById('photoViewer')).show();
}
</script>

<?php if (!empty($visites)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('visitesTable', {
    rowsPerPage: 20,
    searchInputId: 'searchInput',
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>
