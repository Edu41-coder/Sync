<?php $title = "Séjour #" . $hote['id']; ?>

<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-calendar-check', 'text' => 'Hôtes temporaires', 'url' => BASE_URL . '/hote/index'],
    ['icon' => 'fas fa-eye', 'text' => 'Séjour #' . $hote['id'], 'url' => null]
];
include __DIR__ . '/../partials/breadcrumb.php';

$statutLabels = ['reserve'=>'Réservé','en_cours'=>'En cours','termine'=>'Terminé','annule'=>'Annulé'];
$statutColors = ['reserve'=>'warning','en_cours'=>'success','termine'=>'secondary','annule'=>'danger'];
$paiementLabels = ['en_attente'=>'En attente','partiel'=>'Partiel','paye'=>'Payé','rembourse'=>'Remboursé'];
$paiementColors = ['en_attente'=>'warning','partiel'=>'info','paye'=>'success','rembourse'=>'secondary'];
$motifLabels = ['vacances'=>'Vacances','famille'=>'Visite famille','medical'=>'Médical','affaires'=>'Affaires','convalescence'=>'Convalescence','autre'=>'Autre'];
$pieceLabels = ['cni'=>'CNI','passeport'=>'Passeport','titre_sejour'=>'Titre de séjour','permis'=>'Permis de conduire','autre'=>'Autre'];
?>

<div class="container-fluid py-4">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <div>
                <h1 class="h3 mb-1">
                    <i class="fas fa-eye text-dark"></i>
                    <?= htmlspecialchars($hote['civilite'] . ' ' . $hote['prenom'] . ' ' . $hote['nom']) ?>
                </h1>
                <span class="badge bg-<?= $statutColors[$hote['statut']] ?? 'secondary' ?> me-2"><?= $statutLabels[$hote['statut']] ?? $hote['statut'] ?></span>
                <span class="badge bg-<?= $paiementColors[$hote['statut_paiement']] ?? 'secondary' ?>"><?= $paiementLabels[$hote['statut_paiement']] ?? $hote['statut_paiement'] ?></span>
            </div>
            <div>
                <a href="<?= BASE_URL ?>/hote/edit/<?= $hote['id'] ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Modifier</a>
                <a href="<?= BASE_URL ?>/hote/index" class="btn btn-secondary"><i class="fas fa-arrow-left me-1"></i>Retour</a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Identité -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>Identité</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <label class="text-muted small">Nom complet</label>
                            <div class="fw-bold"><?= htmlspecialchars($hote['civilite'] . ' ' . $hote['prenom'] . ' ' . $hote['nom']) ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Nationalité</label>
                            <div><?= htmlspecialchars($hote['nationalite'] ?? '-') ?></div>
                        </div>
                        <?php if ($hote['date_naissance']): ?>
                        <div class="col-6">
                            <label class="text-muted small">Date de naissance</label>
                            <div><?= date('d/m/Y', strtotime($hote['date_naissance'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <label class="text-muted small">Email</label>
                            <div><?= $hote['email'] ? '<a href="mailto:' . htmlspecialchars($hote['email']) . '">' . htmlspecialchars($hote['email']) . '</a>' : '-' ?></div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted small">Téléphone</label>
                            <div><?= htmlspecialchars($hote['telephone_mobile'] ?? $hote['telephone'] ?? '-') ?></div>
                        </div>
                        <?php if ($hote['adresse_domicile']): ?>
                        <div class="col-12">
                            <label class="text-muted small">Adresse domicile</label>
                            <div><?= htmlspecialchars($hote['adresse_domicile']) ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if ($hote['type_piece_identite']): ?>
                        <div class="col-12 mt-2">
                            <label class="text-muted small">Pièce d'identité</label>
                            <div><i class="fas fa-id-card text-dark me-1"></i><?= $pieceLabels[$hote['type_piece_identite']] ?? $hote['type_piece_identite'] ?> — <?= htmlspecialchars($hote['numero_piece_identite'] ?? '-') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Séjour -->
        <div class="col-12 col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0"><i class="fas fa-bed me-2"></i>Séjour</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="text-muted small">Résidence</label>
                            <div class="fw-bold">
                                <i class="fas fa-building text-dark me-1"></i>
                                <?= htmlspecialchars($hote['residence_nom'] ?? '-') ?>
                                <?php if ($hote['residence_ville']): ?><span class="text-muted">(<?= htmlspecialchars($hote['residence_ville']) ?>)</span><?php endif; ?>
                            </div>
                        </div>
                        <?php if ($hote['numero_lot']): ?>
                        <div class="col-6">
                            <label class="text-muted small">Lot / Chambre</label>
                            <div><i class="fas fa-door-open text-dark me-1"></i><?= htmlspecialchars($hote['numero_lot']) ?> (<?= htmlspecialchars($hote['lot_type']) ?>)</div>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <label class="text-muted small">Motif</label>
                            <div><?= $motifLabels[$hote['motif_sejour']] ?? $hote['motif_sejour'] ?></div>
                        </div>
                        <div class="col-4">
                            <label class="text-muted small">Arrivée</label>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($hote['date_arrivee'])) ?></div>
                        </div>
                        <div class="col-4">
                            <label class="text-muted small">Départ prévu</label>
                            <div class="fw-bold"><?= date('d/m/Y', strtotime($hote['date_depart_prevue'])) ?></div>
                        </div>
                        <div class="col-4">
                            <label class="text-muted small">Nuits</label>
                            <div class="fw-bold"><?= $hote['nb_nuits'] ?></div>
                        </div>
                        <?php if ($hote['date_depart_effective']): ?>
                        <div class="col-6">
                            <label class="text-muted small">Départ effectif</label>
                            <div><?= date('d/m/Y', strtotime($hote['date_depart_effective'])) ?></div>
                        </div>
                        <?php endif; ?>
                        <div class="col-6">
                            <label class="text-muted small">Nb personnes</label>
                            <div><?= $hote['nb_personnes'] ?></div>
                        </div>

                        <hr>
                        <div class="col-4">
                            <label class="text-muted small">Prix / nuit</label>
                            <div class="fw-bold"><?= $hote['prix_nuit'] ? number_format($hote['prix_nuit'], 2, ',', ' ') . ' €' : '-' ?></div>
                        </div>
                        <div class="col-4">
                            <label class="text-muted small">Montant total</label>
                            <div class="fw-bold text-primary"><?= $hote['montant_total'] ? number_format($hote['montant_total'], 2, ',', ' ') . ' €' : '-' ?></div>
                        </div>
                        <div class="col-4">
                            <label class="text-muted small">Paiement</label>
                            <div><span class="badge bg-<?= $paiementColors[$hote['statut_paiement']] ?? 'secondary' ?>"><?= $paiementLabels[$hote['statut_paiement']] ?? $hote['statut_paiement'] ?></span></div>
                        </div>

                        <?php if ($hote['notes']): ?>
                        <div class="col-12">
                            <label class="text-muted small">Notes</label>
                            <div class="small"><?= nl2br(htmlspecialchars($hote['notes'])) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
