<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-music',          'text' => 'Animations',      'url' => BASE_URL . '/accueil/animations?residence_id=' . (int)$animation['residence_id']],
    ['icon' => 'fas fa-eye',            'text' => $animation['titre'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();

$isPast = strtotime($animation['date_fin']) < time();
$nbInscrits = count(array_filter($inscriptions, fn($i) => $i['statut'] !== 'annule'));
$nbPresents = count(array_filter($inscriptions, fn($i) => $i['statut'] === 'present'));
$nbAbsents  = count(array_filter($inscriptions, fn($i) => $i['statut'] === 'absent'));

$labelsStatutInsc = [
    'inscrit' => ['couleur' => 'info',      'libelle' => 'Inscrit',  'icone' => 'fa-check'],
    'present' => ['couleur' => 'success',   'libelle' => 'Présent',  'icone' => 'fa-check-circle'],
    'absent'  => ['couleur' => 'danger',    'libelle' => 'Absent',   'icone' => 'fa-times-circle'],
    'annule'  => ['couleur' => 'secondary', 'libelle' => 'Annulé',   'icone' => 'fa-ban'],
];
?>

<div class="container-fluid py-4">

    <div class="row g-3">
        <!-- Détail animation -->
        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-music me-2"></i><?= htmlspecialchars($animation['titre']) ?></h5>
                    <?php if ($isPast): ?>
                    <span class="badge bg-secondary">Passé</span>
                    <?php else: ?>
                    <span class="badge bg-success">À venir</span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <p class="mb-1"><strong><i class="fas fa-calendar text-info me-1"></i>Date :</strong>
                        <?= date('d/m/Y', strtotime($animation['date_debut'])) ?>
                    </p>
                    <p class="mb-1"><strong><i class="fas fa-clock text-info me-1"></i>Horaire :</strong>
                        <?= date('H:i', strtotime($animation['date_debut'])) ?>
                        → <?= date('H:i', strtotime($animation['date_fin'])) ?>
                    </p>
                    <p class="mb-1"><strong><i class="fas fa-building text-info me-1"></i>Résidence :</strong>
                        <?= htmlspecialchars($animation['residence_nom']) ?>
                    </p>
                    <p class="mb-2"><strong><i class="fas fa-user-circle text-info me-1"></i>Animateur :</strong>
                        <?php if ($animation['animateur_prenom']): ?>
                        <?= htmlspecialchars($animation['animateur_prenom'] . ' ' . $animation['animateur_nom']) ?>
                        <?php if ($animation['animateur_email']): ?>
                        <a href="mailto:<?= htmlspecialchars($animation['animateur_email']) ?>" class="ms-2 small"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="text-muted">— Non assigné —</span>
                        <?php endif; ?>
                    </p>

                    <?php if (!empty($animation['description'])): ?>
                    <hr>
                    <p class="mb-0"><strong>Description :</strong></p>
                    <p class="mb-0"><?= nl2br(htmlspecialchars($animation['description'])) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($animation['notes'])): ?>
                    <div class="alert alert-light mt-2 mb-0">
                        <strong><i class="fas fa-sticky-note me-1"></i>Notes internes :</strong>
                        <p class="mb-0"><?= nl2br(htmlspecialchars($animation['notes'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if ($isManager): ?>
                <div class="card-footer d-flex justify-content-end gap-2">
                    <a href="<?= BASE_URL ?>/accueil/animationForm/<?= (int)$animation['id'] ?>" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-edit me-1"></i>Modifier
                    </a>
                    <form method="POST" action="<?= BASE_URL ?>/accueil/animationDelete/<?= (int)$animation['id'] ?>" onsubmit="return confirm('Supprimer cette animation ? Toutes les inscriptions seront retirées.')">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash me-1"></i>Supprimer</button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Statistiques rapides -->
            <div class="row g-2">
                <div class="col-4">
                    <div class="card border-info shadow-sm text-center"><div class="card-body p-2">
                        <h4 class="text-info mb-0"><?= $nbInscrits ?></h4>
                        <small class="text-muted">Inscrits</small>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card border-success shadow-sm text-center"><div class="card-body p-2">
                        <h4 class="text-success mb-0"><?= $nbPresents ?></h4>
                        <small class="text-muted">Présents</small>
                    </div></div>
                </div>
                <div class="col-4">
                    <div class="card border-danger shadow-sm text-center"><div class="card-body p-2">
                        <h4 class="text-danger mb-0"><?= $nbAbsents ?></h4>
                        <small class="text-muted">Absents</small>
                    </div></div>
                </div>
            </div>
        </div>

        <!-- Inscriptions + ajout -->
        <div class="col-lg-7">
            <?php if (!empty($residentsNonInscrits) && !$isPast): ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-primary text-white">
                    <strong><i class="fas fa-user-plus me-2"></i>Inscrire un résident</strong>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/inscriptionCreate" class="row g-2">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="shift_id" value="<?= (int)$animation['id'] ?>">
                        <div class="col-md-6">
                            <select name="resident_id" class="form-select" required>
                                <option value="">— Sélectionner un résident —</option>
                                <?php foreach ($residentsNonInscrits as $r): ?>
                                <option value="<?= (int)$r['id'] ?>">
                                    <?= htmlspecialchars($r['prenom'] . ' ' . $r['nom']) ?>
                                    <?php if (!empty($r['allergies'])): ?>⚠ allergies<?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <input type="text" name="notes" class="form-control" maxlength="200" placeholder="Notes (optionnel)">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100"><i class="fas fa-plus"></i></button>
                        </div>
                    </form>
                </div>
            </div>
            <?php elseif ($isPast && empty($inscriptions)): ?>
            <div class="alert alert-warning"><i class="fas fa-info-circle me-2"></i>Animation passée — pas d'inscriptions enregistrées.</div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-users me-2"></i>Inscriptions (<?= count($inscriptions) ?>)</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($inscriptions)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-user-slash fa-2x opacity-50 mb-2 d-block"></i>
                        Aucune inscription pour cette animation.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($inscriptions as $i):
                            $st = $labelsStatutInsc[$i['statut']];
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="flex-grow-1">
                                    <strong><?= htmlspecialchars(($i['civilite'] ?? '') . ' ' . $i['resident_prenom'] . ' ' . $i['resident_nom']) ?></strong>
                                    <span class="badge bg-<?= $st['couleur'] ?> ms-2"><i class="fas <?= $st['icone'] ?> me-1"></i><?= htmlspecialchars($st['libelle']) ?></span>
                                    <?php if (!empty($i['allergies'])): ?>
                                    <span class="badge bg-warning text-dark ms-1" title="<?= htmlspecialchars($i['allergies']) ?>">⚠ allergies</span>
                                    <?php endif; ?>
                                    <?php if (!empty($i['niveau_autonomie'])): ?>
                                    <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars($i['niveau_autonomie']) ?></span>
                                    <?php endif; ?>
                                    <?php if (!empty($i['notes'])): ?>
                                    <div class="small text-muted mt-1"><i class="fas fa-sticky-note me-1"></i><?= htmlspecialchars($i['notes']) ?></div>
                                    <?php endif; ?>
                                    <small class="text-muted">
                                        <?php if ($i['inscrit_par_prenom']): ?>
                                        Inscrit par <?= htmlspecialchars($i['inscrit_par_prenom'] . ' ' . $i['inscrit_par_nom']) ?>
                                        <?php endif; ?>
                                        · <?= date('d/m/Y H:i', strtotime($i['inscrit_le'])) ?>
                                    </small>
                                </div>
                                <div class="d-flex gap-1 align-items-center">
                                    <?php if ($isPast || strtotime($animation['date_debut']) <= time()): ?>
                                    <!-- Pointage présent/absent (animation en cours ou passée) -->
                                    <form method="POST" action="<?= BASE_URL ?>/accueil/inscriptionStatut/<?= (int)$i['id'] ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="statut" value="present">
                                        <button type="submit" class="btn btn-sm <?= $i['statut'] === 'present' ? 'btn-success' : 'btn-outline-success' ?>" title="Présent"><i class="fas fa-check"></i></button>
                                    </form>
                                    <form method="POST" action="<?= BASE_URL ?>/accueil/inscriptionStatut/<?= (int)$i['id'] ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="statut" value="absent">
                                        <button type="submit" class="btn btn-sm <?= $i['statut'] === 'absent' ? 'btn-danger' : 'btn-outline-danger' ?>" title="Absent"><i class="fas fa-times"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (!$isPast && $i['statut'] !== 'annule'): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/accueil/inscriptionStatut/<?= (int)$i['id'] ?>" class="d-inline">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <input type="hidden" name="statut" value="annule">
                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Annuler" onclick="return confirm('Annuler l\'inscription ?')"><i class="fas fa-ban"></i></button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if ($isManager): ?>
                                    <form method="POST" action="<?= BASE_URL ?>/accueil/inscriptionDelete/<?= (int)$i['id'] ?>" class="d-inline" onsubmit="return confirm('Supprimer définitivement cette inscription ?')">
                                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
