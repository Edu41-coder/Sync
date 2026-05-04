<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-shield-alt',     'text' => 'Sinistres',       'url' => BASE_URL . '/sinistre/index'],
    ['icon' => 'fas fa-eye',            'text' => '#' . (int)$sinistre['id'], 'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$typeLabels = [
    'degat_eaux' => 'Dégât des eaux', 'incendie' => 'Incendie', 'vol_cambriolage' => 'Vol/Cambriolage',
    'bris_glace' => 'Bris de glace', 'catastrophe_naturelle' => 'Catastrophe naturelle',
    'vandalisme' => 'Vandalisme', 'chute_resident' => 'Chute résident',
    'panne_equipement' => 'Panne équipement', 'autre' => 'Autre',
];
$statutLabels = [
    'declare' => 'Déclaré', 'transmis_assureur' => 'Transmis assureur',
    'expertise_en_cours' => 'Expertise en cours', 'en_reparation' => 'En réparation',
    'indemnise' => 'Indemnisé', 'clos' => 'Clos', 'refuse' => 'Refusé',
];
$statutColors = [
    'declare' => 'warning', 'transmis_assureur' => 'info', 'expertise_en_cours' => 'primary',
    'en_reparation' => 'primary', 'indemnise' => 'success', 'clos' => 'secondary', 'refuse' => 'danger',
];
$graviteLabels = ['mineur' => 'Mineur', 'modere' => 'Modéré', 'majeur' => 'Majeur', 'catastrophe' => 'Catastrophe'];
$graviteColors = ['mineur' => 'info', 'modere' => 'warning', 'majeur' => 'danger', 'catastrophe' => 'dark'];
$typeDocLabels = [
    'photo_avant' => 'Photo avant', 'photo_apres' => 'Photo après', 'constat_amiable' => 'Constat amiable',
    'devis' => 'Devis', 'facture' => 'Facture', 'rapport_expertise' => 'Rapport expertise',
    'courrier_assureur' => 'Courrier assureur', 'autre' => 'Autre',
];
$canUploadDoc = $userRole !== 'proprietaire'; // proprio = lecture seule sur docs
$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
?>

<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h2 class="mb-1"><i class="fas fa-shield-alt me-2 text-danger"></i>Sinistre #<?= (int)$sinistre['id'] ?></h2>
            <h4 class="text-muted mb-0"><?= htmlspecialchars($sinistre['titre']) ?></h4>
        </div>
        <div class="d-flex gap-2 flex-wrap">
            <span class="badge bg-<?= $statutColors[$sinistre['statut']] ?? 'secondary' ?> fs-6 align-self-center">
                <?= $statutLabels[$sinistre['statut']] ?? htmlspecialchars($sinistre['statut']) ?>
            </span>
            <?php if ($canEdit): ?>
                <a href="<?= BASE_URL ?>/sinistre/edit/<?= (int)$sinistre['id'] ?>" class="btn btn-warning"><i class="fas fa-edit me-1"></i>Modifier</a>
            <?php endif; ?>
            <?php if ($canDelete): ?>
                <form method="POST" action="<?= BASE_URL ?>/sinistre/delete/<?= (int)$sinistre['id'] ?>" onsubmit="return confirm('Supprimer définitivement ce sinistre et tous ses documents ?');" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
                </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Colonne principale -->
        <div class="col-12 col-lg-8">
            <!-- Infos générales -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-info-circle me-2"></i>Informations</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Type</small>
                            <strong><?= $typeLabels[$sinistre['type_sinistre']] ?? htmlspecialchars($sinistre['type_sinistre']) ?></strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Gravité</small>
                            <span class="badge bg-<?= $graviteColors[$sinistre['gravite']] ?? 'secondary' ?>"><?= $graviteLabels[$sinistre['gravite']] ?? htmlspecialchars($sinistre['gravite']) ?></span>
                        </div>
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Date survenue</small>
                            <strong><?= date('d/m/Y H:i', strtotime($sinistre['date_survenue'])) ?></strong>
                        </div>
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Date constat</small>
                            <?= !empty($sinistre['date_constat']) ? date('d/m/Y H:i', strtotime($sinistre['date_constat'])) : '<em class="text-muted">non renseignée</em>' ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Date déclaration assureur</small>
                            <?= !empty($sinistre['date_declaration_assureur']) ? date('d/m/Y', strtotime($sinistre['date_declaration_assureur'])) : '<em class="text-muted">—</em>' ?>
                        </div>
                        <div class="col-12 col-md-4">
                            <small class="text-muted d-block">Date clôture</small>
                            <?= !empty($sinistre['date_cloture']) ? date('d/m/Y H:i', strtotime($sinistre['date_cloture'])) : '<em class="text-muted">—</em>' ?>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Lieu</small>
                            <strong>
                                <?php if (!empty($sinistre['lot_id'])): ?>
                                    <i class="fas fa-door-open text-info me-1"></i>Lot <?= htmlspecialchars($sinistre['numero_lot'] ?? '?') ?>
                                    <span class="text-muted">(<?= htmlspecialchars($sinistre['lot_type'] ?? '') ?>)</span>
                                <?php else: ?>
                                    <i class="fas fa-building text-secondary me-1"></i>Partie commune : <?= htmlspecialchars(ucfirst(str_replace('_', ' ', $sinistre['lieu_partie_commune'] ?? ''))) ?>
                                <?php endif; ?>
                                <span class="text-muted">— <?= htmlspecialchars($sinistre['residence_nom']) ?>, <?= htmlspecialchars($sinistre['residence_ville']) ?></span>
                            </strong>
                            <?php if (!empty($sinistre['description_lieu'])): ?>
                                <div class="small text-muted mt-1"><?= htmlspecialchars($sinistre['description_lieu']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-12">
                            <small class="text-muted d-block">Déclarant</small>
                            <?= htmlspecialchars(trim($sinistre['declarant_nom'] ?? '') ?: ($sinistre['declarant_username'] ?? '—')) ?>
                            <?php if (!empty($sinistre['declarant_role'])): ?>
                                <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($sinistre['declarant_role']) ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-align-left me-2"></i>Description</div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($sinistre['description']) ?></p>
                    <?php if (!empty($sinistre['notes'])): ?>
                        <hr>
                        <small class="text-muted d-block mb-1">Notes internes</small>
                        <p class="mb-0 text-muted" style="white-space: pre-wrap;"><?= htmlspecialchars($sinistre['notes']) ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Documents -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-paperclip me-2"></i>Documents (<?= count($documents) ?>)</span>
                    <?php if ($canUploadDoc): ?>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalUpload">
                        <i class="fas fa-upload me-1"></i>Ajouter
                    </button>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($documents)): ?>
                        <div class="p-3 text-muted text-center"><em>Aucun document joint</em></div>
                    <?php else: ?>
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nom</th>
                                    <th>Type</th>
                                    <th>Taille</th>
                                    <th>Ajouté par</th>
                                    <th>Date</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $d): ?>
                                <tr>
                                    <td>
                                        <i class="fas fa-file me-1 text-muted"></i>
                                        <?= htmlspecialchars($d['nom_original']) ?>
                                        <?php if (!empty($d['description'])): ?>
                                            <div class="small text-muted"><?= htmlspecialchars($d['description']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= $typeDocLabels[$d['type_document']] ?? htmlspecialchars($d['type_document']) ?></span></td>
                                    <td class="small"><?= number_format(($d['taille_octets'] ?? 0) / 1024, 0, ',', ' ') ?> Ko</td>
                                    <td class="small"><?= htmlspecialchars(trim($d['uploaded_by_nom'] ?? '')) ?: '—' ?></td>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($d['uploaded_at'])) ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/sinistre/document/download/<?= (int)$d['id'] ?>" class="btn btn-sm btn-outline-primary" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <?php if ($canManage || (int)($d['uploaded_by'] ?? 0) === (int)($_SESSION['user_id'] ?? 0)): ?>
                                            <form method="POST" action="<?= BASE_URL ?>/sinistre/document/delete/<?= (int)$d['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer ce document ?');">
                                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-times"></i></button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Chantiers de réparation liés -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <span><i class="fas fa-hammer me-2"></i>Chantiers de réparation (<?= count($chantiersLies ?? []) ?>)</span>
                    <?php if ($canManage): ?>
                    <a href="<?= BASE_URL ?>/chantier/form?sinistre_id=<?= (int)$sinistre['id'] ?>" class="btn btn-sm btn-warning">
                        <i class="fas fa-plus me-1"></i>Créer un chantier de réparation
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($chantiersLies)): ?>
                        <div class="p-3 text-muted text-center">
                            <em>Aucun chantier de réparation lié à ce sinistre.</em>
                            <?php if ($canManage): ?>
                                <div class="small mt-1">Cliquez sur « Créer un chantier de réparation » pour pré-remplir un chantier avec les infos du sinistre.</div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <?php
                        // Coût total = somme des montants payés (réel) ; engagé en référence
                        $totalEngage = 0; $totalPaye = 0;
                        foreach ($chantiersLies as $cl) {
                            $totalEngage += (float)($cl['montant_engage'] ?? 0);
                            $totalPaye   += (float)($cl['montant_paye'] ?? 0);
                        }
                        $indemnise = (float)($sinistre['montant_indemnise'] ?? 0);
                        ?>
                        <table class="table table-sm mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Chantier</th>
                                    <th>Spécialité</th>
                                    <th>Phase</th>
                                    <th>Statut</th>
                                    <th class="text-end">Engagé</th>
                                    <th class="text-end">Payé</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chantiersLies as $cl): ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($cl['titre']) ?></strong></td>
                                    <td>
                                        <?php if (!empty($cl['specialite_nom'])): ?>
                                            <span class="badge" style="background:<?= htmlspecialchars($cl['specialite_couleur'] ?? '#666') ?>;color:#fff;font-size:0.7rem">
                                                <?= htmlspecialchars($cl['specialite_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <small class="text-muted">—</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($cl['phase']) ?></span></td>
                                    <td><span class="badge bg-secondary"><?= htmlspecialchars($cl['statut']) ?></span></td>
                                    <td class="text-end"><?= $cl['montant_engage'] !== null ? number_format((float)$cl['montant_engage'], 2, ',', ' ') . ' €' : '—' ?></td>
                                    <td class="text-end"><?= $cl['montant_paye'] !== null ? number_format((float)$cl['montant_paye'], 2, ',', ' ') . ' €' : '—' ?></td>
                                    <td class="text-end">
                                        <a href="<?= BASE_URL ?>/chantier/show/<?= (int)$cl['id'] ?>" class="btn btn-sm btn-outline-primary" title="Voir le chantier">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-light">
                                <tr>
                                    <th colspan="4" class="text-end">Totaux :</th>
                                    <th class="text-end"><?= number_format($totalEngage, 2, ',', ' ') ?> €</th>
                                    <th class="text-end"><?= number_format($totalPaye, 2, ',', ' ') ?> €</th>
                                    <th></th>
                                </tr>
                                <?php if ($indemnise > 0): ?>
                                <tr>
                                    <td colspan="7" class="small">
                                        <?php $solde = $indemnise - $totalPaye; ?>
                                        <strong>Bilan financier :</strong>
                                        Indemnisation reçue <?= number_format($indemnise, 2, ',', ' ') ?> € · Coût réel <?= number_format($totalPaye, 2, ',', ' ') ?> €
                                        →
                                        <span class="<?= $solde >= 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                            <?= $solde >= 0 ? 'Excédent' : 'Déficit' ?> de <?= number_format(abs($solde), 2, ',', ' ') ?> €
                                        </span>
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Historique -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-history me-2"></i>Historique</div>
                <div class="card-body">
                    <?php if (empty($history)): ?>
                        <em class="text-muted">Aucun événement</em>
                    <?php else: ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach ($history as $h): ?>
                            <li class="mb-2 pb-2 border-bottom">
                                <small class="text-muted"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></small>
                                <strong class="ms-2">
                                    <?php
                                    $iconAction = [
                                        'creation' => 'fa-plus-circle text-success',
                                        'changement_statut' => 'fa-exchange-alt text-primary',
                                        'update' => 'fa-edit text-warning',
                                        'indemnisation' => 'fa-euro-sign text-success',
                                        'cloture' => 'fa-lock text-secondary',
                                        'document_ajoute' => 'fa-paperclip text-info',
                                        'document_supprime' => 'fa-times text-danger',
                                    ][$h['action']] ?? 'fa-circle text-muted';
                                    ?>
                                    <i class="fas <?= $iconAction ?> me-1"></i>
                                    <?= htmlspecialchars(str_replace('_', ' ', ucfirst($h['action']))) ?>
                                </strong>
                                <?php if ($h['statut_avant'] && $h['statut_apres']): ?>
                                    <span class="badge bg-light text-dark ms-1"><?= htmlspecialchars($h['statut_avant']) ?></span>
                                    →
                                    <span class="badge bg-info ms-1"><?= htmlspecialchars($h['statut_apres']) ?></span>
                                <?php endif; ?>
                                <div class="small text-muted ms-4">
                                    <?php if (!empty($h['details'])): ?><?= htmlspecialchars($h['details']) ?> · <?php endif; ?>
                                    par <?= htmlspecialchars(trim($h['user_nom'] ?? '') ?: ($h['user_username'] ?? 'système')) ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Colonne latérale -->
        <div class="col-12 col-lg-4">
            <!-- Assurance -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-file-contract me-2"></i>Assurance</div>
                <div class="card-body">
                    <small class="text-muted d-block">Assureur</small>
                    <strong><?= htmlspecialchars($sinistre['assureur_nom'] ?? '') ?: '<em class="text-muted">non renseigné</em>' ?></strong>

                    <hr class="my-2">
                    <small class="text-muted d-block">N° contrat</small>
                    <?= htmlspecialchars($sinistre['numero_contrat_assurance'] ?? '') ?: '<em class="text-muted">—</em>' ?>

                    <hr class="my-2">
                    <small class="text-muted d-block">N° dossier sinistre</small>
                    <?= htmlspecialchars($sinistre['numero_dossier_sinistre'] ?? '') ?: '<em class="text-muted">—</em>' ?>

                    <hr class="my-2">
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted d-block">Franchise</small>
                            <strong><?= $sinistre['franchise'] !== null ? number_format((float)$sinistre['franchise'], 2, ',', ' ') . ' €' : '—' ?></strong>
                        </div>
                        <div class="col-6">
                            <small class="text-muted d-block">Estimation</small>
                            <strong><?= $sinistre['montant_estime'] !== null ? number_format((float)$sinistre['montant_estime'], 2, ',', ' ') . ' €' : '—' ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Indemnisation -->
            <div class="card shadow-sm mb-3 <?= $sinistre['montant_indemnise'] !== null ? 'border-success' : '' ?>">
                <div class="card-header bg-light"><i class="fas fa-euro-sign me-2"></i>Indemnisation</div>
                <div class="card-body">
                    <?php if ($sinistre['montant_indemnise'] !== null): ?>
                        <div class="text-center">
                            <div class="h4 text-success mb-1"><?= number_format((float)$sinistre['montant_indemnise'], 2, ',', ' ') ?> €</div>
                            <small class="text-muted">reçue le <?= !empty($sinistre['date_indemnisation']) ? date('d/m/Y', strtotime($sinistre['date_indemnisation'])) : '—' ?></small>
                        </div>
                    <?php else: ?>
                        <em class="text-muted">Aucune indemnisation enregistrée</em>
                    <?php endif; ?>
                    <?php if ($canManage): ?>
                        <hr>
                        <button type="button" class="btn btn-sm btn-success w-100" data-bs-toggle="modal" data-bs-target="#modalIndemnisation">
                            <i class="fas fa-edit me-1"></i><?= $sinistre['montant_indemnise'] !== null ? 'Modifier' : 'Saisir' ?> l'indemnisation
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Workflow -->
            <?php if ($canManage): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><i class="fas fa-cog me-2"></i>Changer le statut</div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/sinistre/changeStatut/<?= (int)$sinistre['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <select name="statut" class="form-select form-select-sm mb-2" required>
                            <option value="">— Nouveau statut —</option>
                            <?php foreach ($statutLabels as $k => $v): ?>
                                <?php if ($k !== $sinistre['statut']): ?>
                                    <option value="<?= $k ?>"><?= $v ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <textarea name="details" class="form-control form-control-sm mb-2" rows="2" placeholder="Détails (optionnel)..."></textarea>
                        <button type="submit" class="btn btn-sm btn-primary w-100"><i class="fas fa-check me-1"></i>Mettre à jour</button>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Upload Document -->
<?php if ($canUploadDoc): ?>
<div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/sinistre/document/upload/<?= (int)$sinistre['id'] ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Ajouter un document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Type de document</label>
                        <select name="type_document" class="form-select" required>
                            <?php foreach ($typeDocLabels as $k => $v): ?>
                                <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Fichier <small class="text-muted">(max 50 MB)</small></label>
                        <input type="file" name="document" class="form-control" required>
                        <small class="text-muted">Formats acceptés : PDF, JPG, PNG, WEBP, GIF, MP4, MOV, DOC, DOCX, XLS, XLSX</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <small class="text-muted">(optionnel)</small></label>
                        <input type="text" name="description" class="form-control" maxlength="255" placeholder="Ex: photo prise depuis l'entrée du salon">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload me-1"></i>Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Modal Indemnisation -->
<?php if ($canManage): ?>
<div class="modal fade" id="modalIndemnisation" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/sinistre/saveIndemnisation/<?= (int)$sinistre['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-euro-sign me-2"></i>Indemnisation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Montant indemnisé (€) *</label>
                        <input type="number" step="0.01" min="0" name="montant_indemnise" class="form-control" value="<?= htmlspecialchars($sinistre['montant_indemnise'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date de versement *</label>
                        <input type="date" name="date_indemnisation" class="form-control" value="<?= htmlspecialchars($sinistre['date_indemnisation'] ?? date('Y-m-d')) ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
