<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Mes Assemblées Générales', 'url' => BASE_URL . '/coproprietaire/assemblees'],
    ['icon' => 'fas fa-eye',            'text' => 'AG ' . date('d/m/Y', strtotime($ag['date_ag'])), 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';

$labelsStatut = [
    'convoquee' => ['couleur' => 'info',    'libelle' => 'Convoquée — à venir', 'icone' => 'fa-paper-plane'],
    'tenue'     => ['couleur' => 'success', 'libelle' => 'Tenue',                'icone' => 'fa-check'],
    'annulee'   => ['couleur' => 'danger',  'libelle' => 'Annulée',              'icone' => 'fa-ban'],
];
$labelsResultat = [
    'adopte'  => ['couleur' => 'success', 'libelle' => 'Adoptée',  'icone' => 'fa-check-circle'],
    'rejete'  => ['couleur' => 'danger',  'libelle' => 'Rejetée',  'icone' => 'fa-times-circle'],
    'reporte' => ['couleur' => 'warning', 'libelle' => 'Reportée', 'icone' => 'fa-clock'],
];
$s = $labelsStatut[$ag['statut']] ?? ['couleur' => 'secondary', 'libelle' => $ag['statut'], 'icone' => 'fa-circle'];
?>

<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-<?= $s['couleur'] ?> text-white">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h4 class="mb-0">
                        <i class="fas fa-gavel me-2"></i>
                        AG <?= $ag['type'] === 'extraordinaire' ? 'extraordinaire (AGE)' : 'ordinaire (AGO)' ?>
                    </h4>
                    <small><?= htmlspecialchars($ag['residence_nom']) ?> · <?= htmlspecialchars($ag['residence_ville']) ?></small>
                </div>
                <span class="badge bg-light text-dark fs-6"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <strong><i class="fas fa-calendar text-primary me-1"></i>Date :</strong><br>
                    <?= date('d/m/Y H:i', strtotime($ag['date_ag'])) ?>
                </div>
                <div class="col-md-3">
                    <strong><i class="fas fa-map-marker-alt text-primary me-1"></i>Lieu :</strong><br>
                    <?= htmlspecialchars($ag['lieu'] ?? '— à définir —') ?>
                </div>
                <div class="col-md-3">
                    <strong><i class="fas fa-video text-primary me-1"></i>Mode :</strong><br>
                    <span class="badge bg-light text-dark border"><?= htmlspecialchars(ucfirst($ag['mode'])) ?></span>
                </div>
                <div class="col-md-3">
                    <?php if ($ag['convocation_envoyee_le']): ?>
                    <strong><i class="fas fa-paper-plane text-info me-1"></i>Convocation :</strong><br>
                    <small>envoyée le <?= date('d/m/Y', strtotime($ag['convocation_envoyee_le'])) ?></small>
                    <?php endif; ?>
                </div>

                <?php if ($ag['statut'] === 'tenue'): ?>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-md-3">
                    <strong>Quorum :</strong><br>
                    <?php if ($ag['quorum_atteint']): ?>
                    <span class="badge bg-success">Atteint — délibérations valides</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">Non atteint</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <?php if ($ag['votants_total']): ?>
                    <strong>Votants :</strong><br>
                    <?= (int)$ag['votants_total'] ?> propriétaires
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <?php if ($ag['pres_prenom']): ?>
                    <strong>Président :</strong><br>
                    <?= htmlspecialchars($ag['pres_prenom'] . ' ' . $ag['pres_nom']) ?>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <?php if ($ag['sec_prenom']): ?>
                    <strong>Secrétaire :</strong><br>
                    <?= htmlspecialchars($ag['sec_prenom'] . ' ' . $ag['sec_nom']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Colonne principale -->
        <div class="col-lg-8">

            <!-- Ordre du jour -->
            <?php if (!empty($ag['ordre_du_jour'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-list-ol me-2"></i>Ordre du jour</strong></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($ag['ordre_du_jour']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Résolutions -->
            <?php if (!empty($resolutions)): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <strong><i class="fas fa-vote-yea me-2"></i>Résolutions & résultats des votes (<?= count($resolutions) ?>)</strong>
                </div>
                <div class="card-body p-0">
                    <ol class="list-group list-group-flush">
                        <?php foreach ($resolutions as $r):
                            $resultat = $r['resultat'] ? ($labelsResultat[$r['resultat']] ?? null) : null;
                            $totalVoix = (int)$r['votes_pour'] + (int)$r['votes_contre'] + (int)$r['abstentions'];
                            $totalTantiemes = (int)$r['tantiemes_pour'] + (int)$r['tantiemes_contre'];
                        ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <strong>Résolution #<?= (int)$r['ordre'] ?> — <?= htmlspecialchars($r['resolution']) ?></strong>
                                <?php if ($resultat): ?>
                                <span class="badge bg-<?= $resultat['couleur'] ?> ms-2"><i class="fas <?= $resultat['icone'] ?> me-1"></i><?= htmlspecialchars($resultat['libelle']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($r['description'])): ?>
                            <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                            <?php endif; ?>

                            <?php if ($totalVoix > 0 || $totalTantiemes > 0): ?>
                            <div class="row g-2 small">
                                <?php if ($totalVoix > 0): ?>
                                <div class="col-md-6">
                                    <div class="text-muted mb-1">Voix exprimées :</div>
                                    <span class="badge bg-success">Pour : <?= (int)$r['votes_pour'] ?></span>
                                    <span class="badge bg-danger">Contre : <?= (int)$r['votes_contre'] ?></span>
                                    <span class="badge bg-secondary">Abstentions : <?= (int)$r['abstentions'] ?></span>
                                </div>
                                <?php endif; ?>
                                <?php if ($totalTantiemes > 0): ?>
                                <div class="col-md-6">
                                    <div class="text-muted mb-1">Tantièmes :</div>
                                    <span class="badge bg-success">Pour : <?= (int)$r['tantiemes_pour'] ?></span>
                                    <span class="badge bg-danger">Contre : <?= (int)$r['tantiemes_contre'] ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                </div>
            </div>
            <?php endif; ?>

            <!-- PV (texte) -->
            <?php if (!empty($ag['proces_verbal'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-file-alt me-2"></i>Procès-verbal</strong></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($ag['proces_verbal']) ?></p>
                </div>
            </div>
            <?php endif; ?>

        </div>

        <!-- Sidebar documents -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-paperclip me-2"></i>Documents officiels</strong></div>
                <div class="card-body d-grid gap-2">
                    <?php if (!empty($ag['document_convocation'])): ?>
                    <a href="<?= BASE_URL ?>/coproprietaire/assembleeDownload/<?= (int)$ag['id'] ?>/convocation" target="_blank" class="btn btn-outline-info">
                        <i class="fas fa-download me-1"></i>Télécharger la convocation
                    </a>
                    <?php else: ?>
                    <small class="text-muted"><i class="fas fa-file-alt me-1"></i>Convocation : non disponible</small>
                    <?php endif; ?>

                    <?php if (!empty($ag['document_pv'])): ?>
                    <a href="<?= BASE_URL ?>/coproprietaire/assembleeDownload/<?= (int)$ag['id'] ?>/pv" target="_blank" class="btn btn-outline-success">
                        <i class="fas fa-download me-1"></i>Télécharger le procès-verbal
                    </a>
                    <?php else: ?>
                    <small class="text-muted"><i class="fas fa-file-signature me-1"></i>PV : non disponible</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chantiers liés -->
            <?php if (!empty($chantiersLies)): ?>
            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="fas fa-hammer me-2"></i>Chantiers votés (<?= count($chantiersLies) ?>)</strong></div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush small">
                        <?php foreach ($chantiersLies as $c): ?>
                        <li class="list-group-item">
                            <strong><?= htmlspecialchars($c['titre']) ?></strong>
                            <div class="text-muted">
                                Estimé : <?= number_format((float)$c['montant_estime'], 0, ',', ' ') ?> €
                                <?php if ($c['montant_engage']): ?>
                                · Engagé : <?= number_format((float)$c['montant_engage'], 0, ',', ' ') ?> €
                                <?php endif; ?>
                            </div>
                            <span class="badge bg-light text-dark border"><?= htmlspecialchars($c['statut']) ?></span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
