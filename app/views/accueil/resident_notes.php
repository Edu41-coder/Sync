<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-concierge-bell', 'text' => 'Accueil',         'url' => BASE_URL . '/accueil/index'],
    ['icon' => 'fas fa-users',          'text' => 'Résidents',       'url' => BASE_URL . '/accueil/residents'],
    ['icon' => 'fas fa-comment-medical','text' => 'Notes — ' . $resident['prenom'] . ' ' . $resident['nom'], 'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
?>

<div class="container-fluid py-4">

    <div class="row g-3">
        <!-- Sidebar : fiche résident résumée -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                         style="width:80px;height:80px;background:#0dcaf0;color:#fff;font-size:2rem">
                        <?= strtoupper(mb_substr($resident['prenom'] ?? '?', 0, 1) . mb_substr($resident['nom'] ?? '?', 0, 1)) ?>
                    </div>
                    <h5 class="mb-1"><?= htmlspecialchars(trim(($resident['civilite'] ?? '') . ' ' . $resident['prenom'] . ' ' . $resident['nom'])) ?></h5>
                    <?php if ($resident['date_naissance']): ?>
                    <p class="text-muted small mb-2">Né(e) le <?= date('d/m/Y', strtotime($resident['date_naissance'])) ?> · <?= (int)$resident['age'] ?> ans</p>
                    <?php endif; ?>
                </div>
                <div class="card-body border-top small">
                    <?php if ($resident['telephone_mobile']): ?>
                    <div><i class="fas fa-phone text-info me-1"></i><a href="tel:<?= htmlspecialchars($resident['telephone_mobile']) ?>"><?= htmlspecialchars($resident['telephone_mobile']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($resident['email']): ?>
                    <div><i class="fas fa-envelope text-info me-1"></i><a href="mailto:<?= htmlspecialchars($resident['email']) ?>"><?= htmlspecialchars($resident['email']) ?></a></div>
                    <?php endif; ?>
                    <?php if ($resident['niveau_autonomie']): ?>
                    <div class="mt-2"><strong>Autonomie :</strong> <span class="badge bg-secondary"><?= htmlspecialchars($resident['niveau_autonomie']) ?></span></div>
                    <?php endif; ?>
                    <?php if ($resident['regime_alimentaire']): ?>
                    <div class="mt-1"><strong>Régime :</strong> <?= htmlspecialchars($resident['regime_alimentaire']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($resident['allergies'])): ?>
                    <div class="mt-1 alert alert-warning small mb-0"><strong>⚠ Allergies :</strong> <?= htmlspecialchars($resident['allergies']) ?></div>
                    <?php endif; ?>
                    <?php if ($resident['urgence_nom']): ?>
                    <hr class="my-2">
                    <div><strong>Contact urgence :</strong></div>
                    <div><?= htmlspecialchars($resident['urgence_nom']) ?>
                    <?php if ($resident['urgence_lien']): ?> <em>(<?= htmlspecialchars($resident['urgence_lien']) ?>)</em><?php endif; ?>
                    </div>
                    <?php if ($resident['urgence_telephone']): ?>
                    <div><i class="fas fa-phone text-danger me-1"></i><a href="tel:<?= htmlspecialchars($resident['urgence_telephone']) ?>"><?= htmlspecialchars($resident['urgence_telephone']) ?></a></div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
                <div class="card-footer text-center">
                    <a href="<?= BASE_URL ?>/accueil/residents" class="btn btn-sm btn-outline-secondary"><i class="fas fa-arrow-left me-1"></i>Retour liste</a>
                    <?php if (!empty($resident['user_id'])): ?>
                    <a href="<?= BASE_URL ?>/message/compose?to=<?= (int)$resident['user_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="fas fa-envelope me-1"></i>Message</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Notes -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Ajouter une note</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= BASE_URL ?>/accueil/noteCreate">
                        <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                        <input type="hidden" name="resident_id" value="<?= (int)$resident['id'] ?>">
                        <textarea name="contenu" class="form-control mb-2" rows="3" required placeholder="Note libre : observation, demande, événement à signaler…"></textarea>
                        <div class="text-end">
                            <button type="submit" class="btn btn-info text-white"><i class="fas fa-save me-1"></i>Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <strong><i class="fas fa-history me-2"></i>Historique notes (<?= count($notes) ?>)</strong>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notes)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-comment-slash fa-2x opacity-50 mb-2 d-block"></i>
                        Aucune note pour ce résident.
                    </div>
                    <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notes as $n):
                            $isAuteur = (int)$n['created_by'] === (int)$userId;
                            $peutSupprimer = $isManager || $isAuteur;
                        ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($n['contenu'])) ?></p>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i><?= htmlspecialchars(($n['auteur_prenom'] ?? '') . ' ' . ($n['auteur_nom'] ?? '')) ?: 'Auteur supprimé' ?>
                                        <?php if (!empty($n['auteur_role'])): ?>
                                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($n['auteur_role']) ?></span>
                                        <?php endif; ?>
                                        · <i class="far fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?>
                                    </small>
                                </div>
                                <?php if ($peutSupprimer): ?>
                                <form method="POST" action="<?= BASE_URL ?>/accueil/noteDelete/<?= (int)$n['id'] ?>" class="ms-2" onsubmit="return confirm('Supprimer cette note ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
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
