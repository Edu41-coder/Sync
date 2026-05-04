<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-gavel',          'text' => 'Assemblées Générales', 'url' => BASE_URL . '/assemblee/index'],
    ['icon' => 'fas fa-eye',            'text' => 'AG ' . date('d/m/Y', strtotime($ag['date_ag'])), 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$labelsStatut = [
    'planifiee' => ['couleur' => 'secondary', 'libelle' => 'Planifiée', 'icone' => 'fa-calendar'],
    'convoquee' => ['couleur' => 'info',      'libelle' => 'Convoquée', 'icone' => 'fa-paper-plane'],
    'tenue'     => ['couleur' => 'success',   'libelle' => 'Tenue',     'icone' => 'fa-check'],
    'annulee'   => ['couleur' => 'danger',    'libelle' => 'Annulée',   'icone' => 'fa-ban'],
];
$labelsResultat = [
    'adopte'  => ['couleur' => 'success', 'libelle' => 'Adoptée'],
    'rejete'  => ['couleur' => 'danger',  'libelle' => 'Rejetée'],
    'reporte' => ['couleur' => 'warning', 'libelle' => 'Reportée'],
];
$s = $labelsStatut[$ag['statut']];
?>

<div class="container-fluid py-4">

    <!-- En-tête -->
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-<?= $s['couleur'] ?> text-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h4 class="mb-0">
                    <i class="fas fa-gavel me-2"></i>
                    AG <?= $ag['type'] === 'extraordinaire' ? 'extraordinaire (AGE)' : 'ordinaire (AGO)' ?>
                </h4>
                <small><?= htmlspecialchars($ag['residence_nom']) ?> · <?= htmlspecialchars($ag['residence_ville']) ?></small>
            </div>
            <span class="badge bg-light text-dark fs-6"><i class="fas <?= $s['icone'] ?> me-1"></i><?= htmlspecialchars($s['libelle']) ?></span>
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
                    <strong><i class="fas fa-paper-plane text-info me-1"></i>Convocation envoyée :</strong><br>
                    <?= date('d/m/Y H:i', strtotime($ag['convocation_envoyee_le'])) ?>
                    <?php endif; ?>
                </div>

                <?php if ($ag['statut'] === 'tenue'): ?>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-md-3">
                    <strong>Quorum :</strong><br>
                    <?php if ($ag['quorum_atteint']): ?>
                    <span class="badge bg-success">Atteint</span>
                    <?php else: ?>
                    <span class="badge bg-warning text-dark">Non atteint</span>
                    <?php endif; ?>
                    <?php if ($ag['quorum_present'] && $ag['quorum_requis']): ?>
                    <small class="text-muted d-block"><?= (int)$ag['quorum_present'] ?> / <?= (int)$ag['quorum_requis'] ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-3">
                    <strong>Votants :</strong><br>
                    <?= (int)($ag['votants_total'] ?? 0) ?> propriétaires
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

            <!-- Résolutions / votes -->
            <div class="card shadow-sm mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-vote-yea me-2"></i>Résolutions & votes (<?= count($resolutions) ?>)</strong>
                    <?php if ($isManager && $ag['statut'] !== 'annulee'): ?>
                    <a href="<?= BASE_URL ?>/assemblee/resolutionForm/<?= (int)$ag['id'] ?>" class="btn btn-sm btn-primary"><i class="fas fa-plus me-1"></i>Ajouter</a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($resolutions)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-list-ol fa-2x opacity-50 mb-2 d-block"></i>
                        Aucune résolution enregistrée.
                    </div>
                    <?php else: ?>
                    <ol class="list-group list-group-flush">
                        <?php foreach ($resolutions as $r):
                            $resultat = $r['resultat'] ? ($labelsResultat[$r['resultat']] ?? null) : null;
                        ?>
                        <li class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <strong>Résolution #<?= (int)$r['ordre'] ?> — <?= htmlspecialchars($r['resolution']) ?></strong>
                                    <?php if ($resultat): ?>
                                    <span class="badge bg-<?= $resultat['couleur'] ?> ms-2"><?= htmlspecialchars($resultat['libelle']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($r['description'])): ?>
                                    <p class="mb-1 small text-muted mt-1"><?= nl2br(htmlspecialchars($r['description'])) ?></p>
                                    <?php endif; ?>
                                    <div class="small mt-2">
                                        <span class="badge bg-success">Pour : <?= (int)$r['votes_pour'] ?></span>
                                        <span class="badge bg-danger">Contre : <?= (int)$r['votes_contre'] ?></span>
                                        <span class="badge bg-secondary">Abstentions : <?= (int)$r['abstentions'] ?></span>
                                        <?php if ((int)$r['tantiemes_pour'] || (int)$r['tantiemes_contre']): ?>
                                        <span class="ms-2 text-muted">| Tantièmes :
                                            <span class="text-success"><?= (int)$r['tantiemes_pour'] ?></span> /
                                            <span class="text-danger"><?= (int)$r['tantiemes_contre'] ?></span>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php if ($isManager && $ag['statut'] !== 'annulee'): ?>
                                <div class="d-flex gap-1 ms-2">
                                    <a href="<?= BASE_URL ?>/assemblee/resolutionForm/<?= (int)$ag['id'] ?>/<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Modifier"><i class="fas fa-edit"></i></a>
                                    <form method="POST" action="<?= BASE_URL ?>/assemblee/resolutionDelete/<?= (int)$r['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer cette résolution ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                                <?php endif; ?>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endif; ?>
                </div>
            </div>

            <!-- PV -->
            <?php if (!empty($ag['proces_verbal'])): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-file-alt me-2"></i>Procès-verbal</strong></div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($ag['proces_verbal']) ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Notes internes (manager only) -->
            <?php if ($isManager && !empty($ag['notes_internes'])): ?>
            <div class="card shadow-sm mb-3 border-warning">
                <div class="card-header bg-warning bg-opacity-25">
                    <strong><i class="fas fa-sticky-note me-2"></i>Notes internes</strong>
                    <small class="text-muted">(non visibles des propriétaires)</small>
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($ag['notes_internes']) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar actions -->
        <div class="col-lg-4">

            <!-- Workflow -->
            <?php if ($isManager): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white"><strong><i class="fas fa-cogs me-2"></i>Workflow</strong></div>
                <div class="card-body d-grid gap-2">

                    <?php if ($ag['statut'] === 'planifiee'): ?>
                    <button type="button" class="btn btn-info text-white" data-bs-toggle="modal" data-bs-target="#convoquerModal">
                        <i class="fas fa-paper-plane me-1"></i>Convoquer (changer statut)
                    </button>
                    <?php endif; ?>

                    <?php if (in_array($ag['statut'], ['planifiee','convoquee'], true)): ?>
                    <a href="<?= BASE_URL ?>/accueil/messageGroupe?ag_id=<?= (int)$ag['id'] ?>" class="btn btn-warning">
                        <i class="fas fa-envelope-open-text me-1"></i>Envoyer convocation par messagerie
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#tenirModal">
                        <i class="fas fa-check me-1"></i>Marquer comme tenue
                    </button>
                    <?php endif; ?>

                    <?php if ($ag['statut'] !== 'annulee'): ?>
                    <a href="<?= BASE_URL ?>/assemblee/form/<?= (int)$ag['id'] ?>" class="btn btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <form method="POST" action="<?= BASE_URL ?>/assemblee/annuler/<?= (int)$ag['id'] ?>" onsubmit="return confirm('Annuler cette AG ?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-outline-warning w-100"><i class="fas fa-ban me-1"></i>Annuler</button>
                    </form>
                    <?php endif; ?>

                    <form method="POST" action="<?= BASE_URL ?>/assemblee/delete/<?= (int)$ag['id'] ?>" onsubmit="return confirm('Supprimer définitivement cette AG ?')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-outline-danger w-100 btn-sm"><i class="fas fa-trash me-1"></i>Supprimer définitivement</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Documents -->
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-paperclip me-2"></i>Documents</strong></div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong>Convocation :</strong>
                        <?php if (!empty($ag['document_convocation'])): ?>
                        <a href="<?= BASE_URL ?>/assemblee/download/<?= (int)$ag['id'] ?>/convocation" class="btn btn-sm btn-outline-info" target="_blank">
                            <i class="fas fa-download me-1"></i>Télécharger
                        </a>
                        <?php else: ?>
                        <small class="text-muted">— non uploadé —</small>
                        <?php endif; ?>
                    </div>
                    <div>
                        <strong>Procès-verbal :</strong>
                        <?php if (!empty($ag['document_pv'])): ?>
                        <a href="<?= BASE_URL ?>/assemblee/download/<?= (int)$ag['id'] ?>/pv" class="btn btn-sm btn-outline-success" target="_blank">
                            <i class="fas fa-download me-1"></i>Télécharger
                        </a>
                        <?php else: ?>
                        <small class="text-muted">— non uploadé —</small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Chantiers liés -->
            <div class="card shadow-sm mb-3">
                <div class="card-header"><strong><i class="fas fa-hammer me-2"></i>Chantiers liés (<?= count($chantiersLies) ?>)</strong></div>
                <div class="card-body">
                    <?php if (empty($chantiersLies)): ?>
                    <small class="text-muted">Aucun chantier rattaché à cette AG.</small>
                    <?php else: ?>
                    <ul class="list-unstyled mb-0 small">
                        <?php foreach ($chantiersLies as $c): ?>
                        <li class="mb-1">
                            <a href="<?= BASE_URL ?>/chantier/show/<?= (int)$c['id'] ?>" class="text-decoration-none">
                                <i class="fas fa-hammer text-secondary me-1"></i><?= htmlspecialchars($c['titre']) ?>
                            </a>
                            <small class="text-muted d-block">
                                <?= number_format((float)$c['montant_estime'], 0, ',', ' ') ?> € · <?= htmlspecialchars($c['statut']) ?>
                            </small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <?php if ($isManager && !empty($chantiersEnAttente)): ?>
                    <hr>
                    <small class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i><strong><?= count($chantiersEnAttente) ?> chantier(s)</strong> en attente d'AG dans cette résidence :</small>
                    <ul class="list-unstyled mb-0 small mt-1">
                        <?php foreach ($chantiersEnAttente as $c): ?>
                        <li>
                            <a href="<?= BASE_URL ?>/chantier/show/<?= (int)$c['id'] ?>" class="text-warning text-decoration-none">
                                · <?= htmlspecialchars($c['titre']) ?>
                            </a>
                            <small class="text-muted">(<?= number_format((float)$c['montant_estime'], 0, ',', ' ') ?> €)</small>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Métadonnées -->
            <div class="card shadow-sm">
                <div class="card-header"><strong><i class="fas fa-info-circle me-2"></i>Métadonnées</strong></div>
                <div class="card-body small">
                    <?php if ($ag['createur_prenom']): ?>
                    <p class="mb-1"><strong>Créée par :</strong> <?= htmlspecialchars($ag['createur_prenom'] . ' ' . $ag['createur_nom']) ?></p>
                    <?php endif; ?>
                    <p class="mb-1"><strong>Créée le :</strong> <?= date('d/m/Y H:i', strtotime($ag['created_at'])) ?></p>
                    <p class="mb-0"><strong>Mise à jour :</strong> <?= date('d/m/Y H:i', strtotime($ag['updated_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal convoquer -->
<?php if ($isManager && $ag['statut'] === 'planifiee'): ?>
<div class="modal fade" id="convoquerModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="<?= BASE_URL ?>/assemblee/convoquer/<?= (int)$ag['id'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title"><i class="fas fa-paper-plane me-2"></i>Convoquer l'AG</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Envoyer la convocation aux propriétaires ? La date d'envoi sera enregistrée.</p>
                    <label class="form-label">Document de convocation (PDF/JPG/PNG, 10 Mo max)</label>
                    <input type="file" name="document_convocation" class="form-control" accept="application/pdf,image/jpeg,image/png">
                    <small class="text-muted">Optionnel — peut être ajouté plus tard.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-info text-white"><i class="fas fa-paper-plane me-1"></i>Convoquer</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- Modal tenir -->
<?php if ($isManager && in_array($ag['statut'], ['planifiee','convoquee'], true)): ?>
<div class="modal fade" id="tenirModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="<?= BASE_URL ?>/assemblee/tenir/<?= (int)$ag['id'] ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><i class="fas fa-check me-2"></i>Marquer comme tenue</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Quorum présent</label>
                            <input type="number" name="quorum_present" class="form-control" min="0" placeholder="Tantièmes ou voix">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Total votants (présents + représentés)</label>
                            <input type="number" name="votants_total" class="form-control" min="0">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Président de séance</label>
                            <select name="president_seance_id" class="form-select">
                                <option value="">— À désigner plus tard —</option>
                                <?php foreach (($candidats ?? []) as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Secrétaire</label>
                            <select name="secretaire_id" class="form-select">
                                <option value="">— À désigner plus tard —</option>
                                <?php foreach (($candidats ?? []) as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars($u['prenom'] . ' ' . $u['nom']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="quorum_atteint" id="quorum_atteint_modal" value="1">
                                <label class="form-check-label" for="quorum_atteint_modal"><strong>Quorum atteint</strong> (les délibérations sont valides)</label>
                            </div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Procès-verbal (texte)</label>
                            <textarea name="proces_verbal" class="form-control" rows="6" placeholder="Compte-rendu détaillé…"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">PV signé (PDF/JPG/PNG, 10 Mo max)</label>
                            <input type="file" name="document_pv" class="form-control" accept="application/pdf,image/jpeg,image/png">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Confirmer la tenue</button>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>
