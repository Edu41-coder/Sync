<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Réservations',    'url' => BASE_URL . '/accueil/reservations?residence_id=' . (int)$reservation['residence_id']],
    ['icon' => 'fas fa-eye',            'text' => $reservation['titre'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$labelsStatut = [
    'en_attente' => ['couleur' => 'warning', 'libelle' => 'En attente de validation', 'icone' => 'fa-clock'],
    'confirmee'  => ['couleur' => 'success', 'libelle' => 'Confirmée',                'icone' => 'fa-check'],
    'refusee'    => ['couleur' => 'danger',  'libelle' => 'Refusée',                  'icone' => 'fa-times'],
    'annulee'    => ['couleur' => 'secondary','libelle' => 'Annulée',                  'icone' => 'fa-ban'],
    'realisee'   => ['couleur' => 'primary', 'libelle' => 'Réalisée',                 'icone' => 'fa-flag-checkered'],
];
$labelsType = [
    'salle'             => ['couleur' => 'info',    'libelle' => 'Salle commune',     'icone' => 'fa-door-open'],
    'equipement'        => ['couleur' => 'success', 'libelle' => 'Équipement prêté',  'icone' => 'fa-toolbox'],
    'service_personnel' => ['couleur' => 'primary', 'libelle' => 'Service personnel', 'icone' => 'fa-user-tie'],
];
$s = $labelsStatut[$reservation['statut']];
$t = $labelsType[$reservation['type_reservation']];
?>

<div class="container-fluid py-4">

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-<?= $s['couleur'] ?> text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas <?= $s['icone'] ?> me-2"></i><?= htmlspecialchars($reservation['titre']) ?></h5>
                    <span class="badge bg-light text-dark"><i class="fas <?= $t['icone'] ?> me-1"></i><?= htmlspecialchars($t['libelle']) ?></span>
                </div>
                <div class="card-body">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <strong>Statut :</strong>
                            <span class="badge bg-<?= $s['couleur'] ?>"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
                        </div>
                        <div class="col-md-6">
                            <strong>Résidence :</strong> <?= htmlspecialchars($reservation['residence_nom']) ?>
                        </div>

                        <div class="col-md-6">
                            <strong>Période :</strong><br>
                            <?= date('d/m/Y H:i', strtotime($reservation['date_debut'])) ?>
                            → <?= date('d/m/Y H:i', strtotime($reservation['date_fin'])) ?>
                        </div>
                        <div class="col-md-6">
                            <strong>Cible :</strong><br>
                            <?php if ($reservation['type_reservation'] === 'salle' && $reservation['salle_id']): ?>
                                <i class="fas fa-door-open text-info me-1"></i>
                                <?= htmlspecialchars($reservation['salle_nom']) ?>
                                <?php if ($reservation['salle_capacite']): ?>
                                <small class="text-muted">(<?= (int)$reservation['salle_capacite'] ?> pers.)</small>
                                <?php endif; ?>
                            <?php elseif ($reservation['type_reservation'] === 'equipement' && $reservation['equipement_id']): ?>
                                <i class="fas fa-toolbox text-success me-1"></i>
                                <?= htmlspecialchars($reservation['equipement_nom']) ?>
                                <small class="text-muted">(<?= htmlspecialchars($reservation['equipement_type']) ?>)</small>
                            <?php elseif ($reservation['type_reservation'] === 'service_personnel'): ?>
                                <i class="fas fa-user-tie text-primary me-1"></i>
                                <?= htmlspecialchars(ucfirst($reservation['type_service'])) ?>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <strong>Demandeur :</strong>
                            <?php if ($reservation['resident_id']): ?>
                                <i class="fas fa-user me-1 text-primary"></i>
                                <?= htmlspecialchars(($reservation['resident_prenom'] ?? '') . ' ' . ($reservation['resident_nom'] ?? '')) ?>
                                <span class="badge bg-info text-dark">Résident</span>
                                <?php if ($reservation['resident_tel']): ?>
                                <a href="tel:<?= htmlspecialchars($reservation['resident_tel']) ?>" class="ms-2"><i class="fas fa-phone"></i> <?= htmlspecialchars($reservation['resident_tel']) ?></a>
                                <?php endif; ?>
                            <?php elseif ($reservation['hote_id']): ?>
                                <i class="fas fa-suitcase-rolling me-1 text-warning"></i>
                                <?= htmlspecialchars(($reservation['hote_prenom'] ?? '') . ' ' . ($reservation['hote_nom'] ?? '')) ?>
                                <span class="badge bg-warning text-dark">Hôte temporaire</span>
                                <?php if ($reservation['hote_tel']): ?>
                                <a href="tel:<?= htmlspecialchars($reservation['hote_tel']) ?>" class="ms-2"><i class="fas fa-phone"></i> <?= htmlspecialchars($reservation['hote_tel']) ?></a>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($reservation['description'])): ?>
                        <div class="col-12">
                            <strong>Description :</strong>
                            <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($reservation['description'])) ?></p>
                        </div>
                        <?php endif; ?>

                        <?php if (!empty($reservation['notes'])): ?>
                        <div class="col-12">
                            <div class="alert alert-light mb-0">
                                <strong><i class="fas fa-sticky-note me-1"></i>Notes internes :</strong>
                                <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($reservation['notes'])) ?></p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($reservation['statut'] === 'refusee' && !empty($reservation['motif_refus'])): ?>
                        <div class="col-12">
                            <div class="alert alert-danger mb-0">
                                <strong><i class="fas fa-times-circle me-1"></i>Motif du refus :</strong>
                                <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($reservation['motif_refus'])) ?></p>
                                <?php if ($reservation['valide_par_id']): ?>
                                <small class="text-muted">Par <?= htmlspecialchars(($reservation['valide_par_prenom'] ?? '') . ' ' . ($reservation['valide_par_nom'] ?? '')) ?> le <?= date('d/m/Y H:i', strtotime($reservation['valide_le'])) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar : Actions + métadonnées -->
        <div class="col-lg-4">
            <?php if ($reservation['statut'] === 'en_attente' && $isManager): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-warning text-dark">
                    <strong><i class="fas fa-gavel me-2"></i>Validation</strong>
                </div>
                <div class="card-body d-grid gap-2">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/reservationValider/<?= (int)$reservation['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-success w-100" onclick="return confirm('Valider cette réservation ?')">
                            <i class="fas fa-check me-1"></i>Valider la réservation
                        </button>
                    </form>
                    <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#refuserModal">
                        <i class="fas fa-times me-1"></i>Refuser…
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <?php if (in_array($reservation['statut'], ['en_attente','confirmee'], true)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-cog me-2"></i>Autres actions</strong></div>
                <div class="card-body d-grid gap-2">
                    <?php if ($reservation['statut'] === 'confirmee' && $isManager): ?>
                    <form method="POST" action="<?= BASE_URL ?>/accueil/reservationRealiser/<?= (int)$reservation['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-primary w-100" onclick="return confirm('Marquer comme réalisée ?')">
                            <i class="fas fa-flag-checkered me-1"></i>Marquer réalisée
                        </button>
                    </form>
                    <?php endif; ?>
                    <form method="POST" action="<?= BASE_URL ?>/accueil/reservationAnnuler/<?= (int)$reservation['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-outline-secondary w-100" onclick="return confirm('Annuler cette réservation ?')">
                            <i class="fas fa-ban me-1"></i>Annuler
                        </button>
                    </form>
                    <?php if ($reservation['statut'] === 'en_attente'): ?>
                    <a href="<?= BASE_URL ?>/accueil/reservationForm/<?= (int)$reservation['id'] ?>" class="btn btn-outline-info">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="fas fa-info-circle me-2"></i>Métadonnées</strong></div>
                <div class="card-body small">
                    <p class="mb-1"><strong>Créée le :</strong> <?= date('d/m/Y H:i', strtotime($reservation['created_at'])) ?></p>
                    <?php if ($reservation['createur_prenom']): ?>
                    <p class="mb-1"><strong>Par :</strong> <?= htmlspecialchars($reservation['createur_prenom'] . ' ' . $reservation['createur_nom']) ?></p>
                    <?php endif; ?>
                    <?php if ($reservation['valide_le']): ?>
                    <hr class="my-2">
                    <p class="mb-1"><strong><?= $reservation['statut'] === 'refusee' ? 'Refusée' : 'Validée' ?> le :</strong> <?= date('d/m/Y H:i', strtotime($reservation['valide_le'])) ?></p>
                    <?php if ($reservation['valide_par_prenom']): ?>
                    <p class="mb-1"><strong>Par :</strong> <?= htmlspecialchars($reservation['valide_par_prenom'] . ' ' . $reservation['valide_par_nom']) ?></p>
                    <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($isManager): ?>
                    <hr class="my-2">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/reservationDelete/<?= (int)$reservation['id'] ?>" onsubmit="return confirm('Supprimer définitivement cette réservation ?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger w-100"><i class="fas fa-trash me-1"></i>Supprimer définitivement</button>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal refus -->
<?php if ($reservation['statut'] === 'en_attente' && $isManager): ?>
<div class="modal fade" id="refuserModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/accueil/reservationRefuser/<?= (int)$reservation['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-times-circle me-2"></i>Refuser la réservation</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Motif du refus <span class="text-danger">*</span></label>
                    <textarea name="motif_refus" class="form-control" rows="4" required maxlength="1000"
                              placeholder="Expliquer le motif (créneau indisponible, salle en travaux, conflit…)"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-times me-1"></i>Confirmer le refus</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
