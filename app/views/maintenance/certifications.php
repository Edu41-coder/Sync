<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-hard-hat',       'text' => 'Maintenance',     'url' => BASE_URL . '/maintenance/index'],
    ['icon' => 'fas fa-certificate',    'text' => 'Certifications',  'url' => null]
];
include ROOT_PATH . '/app/views/partials/breadcrumb.php';
$csrf = Security::getToken();
$today = new DateTime();
?>

<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="fas fa-certificate text-warning me-2"></i>
                <?= $isOwnPage ? 'Mes certifications' : 'Certifications de ' . htmlspecialchars($targetUser->prenom . ' ' . $targetUser->nom) ?>
            </h1>
            <?php if (!$isOwnPage): ?>
            <p class="text-muted mb-0">@<?= htmlspecialchars($targetUser->username) ?></p>
            <?php endif; ?>
        </div>
        <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalCertif">
            <i class="fas fa-plus me-1"></i>Ajouter une certification
        </button>
    </div>

    <?php if (empty($certifications)): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-certificate fa-3x mb-3 d-block opacity-50"></i>
            <h6>Aucune certification enregistrée</h6>
            <p class="small mb-0">Cliquez sur "Ajouter une certification" ci-dessus.</p>
        </div>
    </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Nom</th>
                            <th>Spécialité</th>
                            <th>Organisme</th>
                            <th>Obtention</th>
                            <th>Expiration</th>
                            <th class="text-center">Preuve</th>
                            <th class="text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($certifications as $c):
                            $expirationStatus = '';
                            $rowClass = '';
                            if (!empty($c['date_expiration'])) {
                                $exp = new DateTime($c['date_expiration']);
                                $diff = (int)$today->diff($exp)->format('%r%a'); // signed days
                                if ($diff < 0) { $expirationStatus = 'expiree'; $rowClass = 'table-danger'; }
                                elseif ($diff <= 90) { $expirationStatus = 'bientot'; $rowClass = 'table-warning'; }
                            }
                            if (!$c['actif']) $rowClass = 'text-muted';
                        ?>
                        <tr class="<?= $rowClass ?>">
                            <td>
                                <strong><?= htmlspecialchars($c['nom']) ?></strong>
                                <?php if (!empty($c['numero_certificat'])): ?>
                                <br><small class="text-muted">N° <?= htmlspecialchars($c['numero_certificat']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($c['specialite_nom']): ?>
                                <span class="badge bg-secondary"><?= htmlspecialchars($c['specialite_nom']) ?></span>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($c['organisme'] ?? '—') ?></td>
                            <td><?= date('d/m/Y', strtotime($c['date_obtention'])) ?></td>
                            <td>
                                <?php if (empty($c['date_expiration'])): ?>
                                    <span class="badge bg-success">Sans expiration</span>
                                <?php else: ?>
                                    <?= date('d/m/Y', strtotime($c['date_expiration'])) ?>
                                    <?php if ($expirationStatus === 'expiree'): ?>
                                        <span class="badge bg-danger d-block mt-1">Expirée</span>
                                    <?php elseif ($expirationStatus === 'bientot'): ?>
                                        <span class="badge bg-warning text-dark d-block mt-1">≤ 3 mois</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!empty($c['fichier_preuve'])): ?>
                                <a href="<?= BASE_URL ?>/maintenance/downloadCertif/<?= (int)$c['id'] ?>"
                                   target="_blank" class="btn btn-sm btn-outline-info" title="Voir le fichier">
                                    <i class="fas fa-file-alt"></i>
                                </a>
                                <?php else: ?>
                                <small class="text-muted">—</small>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($isOwnPage || $isManager): ?>
                                <form method="POST" action="<?= BASE_URL ?>/maintenance/deleteCertification/<?= (int)$c['id'] ?>" class="d-inline"
                                      onsubmit="return confirm('Supprimer cette certification ?')">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                    <input type="hidden" name="user_id" value="<?= (int)$targetUser->id ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- Modal nouvelle certification -->
<div class="modal fade" id="modalCertif" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/maintenance/createCertification" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-certificate text-warning me-2"></i>Nouvelle certification</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="user_id" value="<?= (int)$targetUser->id ?>">

                    <div class="mb-3">
                        <label class="form-label">Nom <span class="text-danger">*</span></label>
                        <input type="text" name="nom" class="form-control" required maxlength="200"
                               placeholder="Ex: Habilitation B1V, BNSSA, CACES R486">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Spécialité associée</label>
                        <select name="specialite_id" class="form-select">
                            <option value="">— Générique (pas de spécialité) —</option>
                            <?php foreach ($specialites as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Organisme</label>
                            <input type="text" name="organisme" class="form-control" maxlength="200"
                                   placeholder="Ex: AFNOR, INRS, APAVE, ARS">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">N° certificat</label>
                            <input type="text" name="numero_certificat" class="form-control" maxlength="100">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'obtention <span class="text-danger">*</span></label>
                            <input type="date" name="date_obtention" class="form-control" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Date d'expiration</label>
                            <input type="date" name="date_expiration" class="form-control">
                            <small class="text-muted">Vide = sans expiration</small>
                        </div>
                    </div>

                    <div class="mb-3 mt-3">
                        <label class="form-label">Fichier preuve (PDF / image)</label>
                        <input type="file" name="fichier_preuve" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                        <small class="text-muted">Max 10 Mo. Stocké de manière sécurisée (accès via lien authentifié uniquement).</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-save me-1"></i>Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>
