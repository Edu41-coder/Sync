<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-tools',          'text' => 'Interventions',   'url' => BASE_URL . '/maintenance/interventions'],
    ['icon' => 'fas fa-eye',            'text' => '#' . (int)$intervention['id'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$badgeStatut = ['a_planifier'=>'secondary','planifiee'=>'info','en_cours'=>'warning','terminee'=>'success','annulee'=>'dark'];
$badgePrio   = ['basse'=>'secondary','normale'=>'primary','haute'=>'warning','urgente'=>'danger'];
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-start mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1">
                <span class="badge me-2" style="background:<?= htmlspecialchars($intervention['specialite_couleur']) ?>;color:#fff">
                    <i class="<?= htmlspecialchars($intervention['specialite_icone']) ?> me-1"></i><?= htmlspecialchars($intervention['specialite_nom']) ?>
                </span>
                <?= htmlspecialchars($intervention['titre']) ?>
            </h1>
            <p class="text-muted mb-0">
                <span class="badge bg-<?= $badgePrio[$intervention['priorite']] ?? 'secondary' ?>"><?= $intervention['priorite'] ?></span>
                <span class="badge bg-<?= $badgeStatut[$intervention['statut']] ?? 'secondary' ?>"><?= $intervention['statut'] ?></span>
                <small class="ms-2">Type : <?= htmlspecialchars($intervention['type_intervention']) ?></small>
            </p>
        </div>
        <div class="d-flex gap-2">
            <?php if ($isManager): ?>
            <a href="<?= BASE_URL ?>/maintenance/interventionForm/<?= (int)$intervention['id'] ?>" class="btn btn-outline-secondary">
                <i class="fas fa-edit me-1"></i>Modifier
            </a>
            <form method="POST" action="<?= BASE_URL ?>/maintenance/interventionDelete/<?= (int)$intervention['id'] ?>" class="d-inline"
                  onsubmit="return confirm('Supprimer cette intervention ?')">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <div class="row g-3">
        <!-- Détails -->
        <div class="col-12 col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-info-circle me-2"></i>Détails</strong></div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Résidence</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($intervention['residence_nom']) ?></dd>

                        <?php if ($intervention['numero_lot']): ?>
                        <dt class="col-sm-4">Lot</dt>
                        <dd class="col-sm-8"><?= htmlspecialchars($intervention['numero_lot']) ?> (<?= htmlspecialchars($intervention['lot_type']) ?>)</dd>
                        <?php endif; ?>

                        <dt class="col-sm-4">Date signalement</dt>
                        <dd class="col-sm-8"><?= !empty($intervention['date_signalement']) ? date('d/m/Y H:i', strtotime($intervention['date_signalement'])) : '—' ?></dd>

                        <dt class="col-sm-4">Date planifiée</dt>
                        <dd class="col-sm-8"><?= !empty($intervention['date_planifiee']) ? date('d/m/Y H:i', strtotime($intervention['date_planifiee'])) : '<em class="text-muted">à planifier</em>' ?></dd>

                        <?php if (!empty($intervention['date_realisee'])): ?>
                        <dt class="col-sm-4">Date réalisée</dt>
                        <dd class="col-sm-8"><?= date('d/m/Y H:i', strtotime($intervention['date_realisee'])) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($intervention['duree_minutes'])): ?>
                        <dt class="col-sm-4">Durée</dt>
                        <dd class="col-sm-8"><?= (int)$intervention['duree_minutes'] ?> min</dd>
                        <?php endif; ?>

                        <?php if (!empty($intervention['cout'])): ?>
                        <dt class="col-sm-4">Coût</dt>
                        <dd class="col-sm-8"><?= number_format((float)$intervention['cout'], 2, ',', ' ') ?> €</dd>
                        <?php endif; ?>

                        <?php if (!empty($intervention['description'])): ?>
                        <dt class="col-sm-4">Description</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($intervention['description'])) ?></dd>
                        <?php endif; ?>

                        <?php if (!empty($intervention['notes'])): ?>
                        <dt class="col-sm-4">Notes</dt>
                        <dd class="col-sm-8"><?= nl2br(htmlspecialchars($intervention['notes'])) ?></dd>
                        <?php endif; ?>
                    </dl>
                </div>
            </div>

            <!-- Photos avant/après -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-camera me-2"></i>Photos avant / après</strong></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php foreach (['photo_avant'=>'Avant', 'photo_apres'=>'Après'] as $champ => $label): ?>
                        <div class="col-md-6">
                            <h6><?= $label ?></h6>
                            <?php if (!empty($intervention[$champ])): ?>
                            <img src="<?= BASE_URL . '/' . htmlspecialchars($intervention[$champ]) ?>"
                                 class="img-fluid rounded shadow-sm mb-2" style="max-height:300px">
                            <?php else: ?>
                            <div class="border rounded p-4 text-center text-muted bg-light mb-2">
                                <i class="fas fa-image fa-2x mb-2 d-block opacity-50"></i>
                                <small>Pas encore de photo</small>
                            </div>
                            <?php endif; ?>
                            <form method="POST" action="<?= BASE_URL ?>/maintenance/interventionPhoto/<?= (int)$intervention['id'] ?>"
                                  enctype="multipart/form-data" class="d-flex gap-2">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="champ" value="<?= $champ ?>">
                                <input type="file" name="photo" accept=".jpg,.jpeg,.png,.webp" class="form-control form-control-sm" required>
                                <button type="submit" class="btn btn-sm btn-primary">
                                    <i class="fas fa-upload"></i>
                                </button>
                            </form>
                            <small class="text-muted">JPG/PNG/WEBP — max 5 Mo</small>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-12 col-lg-4">
            <!-- Affectation -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-user me-2"></i>Affectation</strong></div>
                <div class="card-body">
                    <?php if (!empty($intervention['assigne_prenom'])): ?>
                    <div class="mb-2">
                        <i class="fas fa-user-circle text-info me-1"></i>
                        <strong><?= htmlspecialchars($intervention['assigne_prenom'] . ' ' . $intervention['assigne_nom']) ?></strong>
                        <?php if (!empty($intervention['assigne_email'])): ?>
                        <br><small><a href="mailto:<?= htmlspecialchars($intervention['assigne_email']) ?>"><i class="fas fa-envelope me-1"></i><?= htmlspecialchars($intervention['assigne_email']) ?></a></small>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($intervention['prestataire_externe'])): ?>
                    <div class="alert alert-info small mb-0">
                        <strong><i class="fas fa-external-link-alt me-1"></i>Prestataire externe :</strong><br>
                        <?= htmlspecialchars($intervention['prestataire_externe']) ?>
                        <?php if (!empty($intervention['prestataire_externe_tel'])): ?>
                        <br><i class="fas fa-phone me-1"></i><?= htmlspecialchars($intervention['prestataire_externe_tel']) ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (empty($intervention['assigne_prenom']) && empty($intervention['prestataire_externe'])): ?>
                    <small class="text-muted">Aucune affectation.</small>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Changement statut rapide -->
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-light"><strong><i class="fas fa-tasks me-2"></i>Changer le statut</strong></div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/maintenance/interventionStatut/<?= (int)$intervention['id'] ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <select name="statut" class="form-select form-select-sm mb-2">
                            <?php foreach (['a_planifier','planifiee','en_cours','terminee','annulee'] as $s): ?>
                            <option value="<?= $s ?>" <?= $intervention['statut'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-check me-1"></i>Mettre à jour
                        </button>
                    </form>
                </div>
            </div>

            <!-- Métadonnées -->
            <div class="card shadow-sm">
                <div class="card-body small text-muted">
                    <div><i class="fas fa-user-plus me-1"></i>Créé par : <?= htmlspecialchars(($intervention['createur_prenom'] ?? '') . ' ' . ($intervention['createur_nom'] ?? '')) ?></div>
                    <div><i class="far fa-clock me-1"></i>Le <?= date('d/m/Y H:i', strtotime($intervention['date_signalement'])) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>
