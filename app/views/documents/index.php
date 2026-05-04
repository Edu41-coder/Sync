<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-folder-open',    'text' => 'Documents',       'url' => null],
];
include __DIR__ . '/../partials/breadcrumb.php';

$csrf = htmlspecialchars($_SESSION['csrf_token'] ?? '');
$isGlobal = ($scope === 'global');
$currentResidenceNom = '';
foreach ($residences as $r) {
    if ((int)$r['id'] === (int)($residenceId ?? 0)) { $currentResidenceNom = $r['nom']; break; }
}

$buildUrl = function(array $overrides = []) use ($scope, $residenceId, $dossierCourant) {
    $params = ['scope' => $scope];
    if ($scope === 'residence' && $residenceId) $params['residence_id'] = $residenceId;
    if (!empty($dossierCourant['id'])) $params['dossier'] = (int)$dossierCourant['id'];
    foreach ($overrides as $k => $v) {
        if ($v === null) unset($params[$k]); else $params[$k] = $v;
    }
    return BASE_URL . '/document/index?' . http_build_query($params);
};

$humanSize = function(int $octets): string {
    if ($octets >= 1073741824) return number_format($octets / 1073741824, 2, ',', ' ') . ' Go';
    if ($octets >= 1048576)    return number_format($octets / 1048576, 1, ',', ' ') . ' Mo';
    if ($octets >= 1024)       return number_format($octets / 1024, 0, ',', ' ') . ' Ko';
    return $octets . ' o';
};

$mimeIcon = function(?string $mime, string $fallback = 'fa-file'): string {
    if (!$mime) return $fallback;
    if (str_starts_with($mime, 'image/')) return 'fa-file-image';
    if (str_starts_with($mime, 'video/')) return 'fa-file-video';
    if ($mime === 'application/pdf')      return 'fa-file-pdf';
    if (str_contains($mime, 'word') || str_contains($mime, 'opendocument.text')) return 'fa-file-word';
    if (str_contains($mime, 'excel') || str_contains($mime, 'spreadsheetml') || str_contains($mime, 'opendocument.spreadsheet')) return 'fa-file-excel';
    if ($mime === 'application/zip') return 'fa-file-archive';
    if ($mime === 'text/csv' || $mime === 'text/plain') return 'fa-file-alt';
    return $fallback;
};
$canPreview = function(?string $mime): bool {
    return $mime && (str_starts_with($mime, 'image/') || str_starts_with($mime, 'video/') || $mime === 'application/pdf');
};
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <h2 class="mb-0"><i class="fas fa-folder-open text-warning me-2"></i>Documents</h2>
        <?php if ($canWrite): ?>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalCreateDossier">
                <i class="fas fa-folder-plus me-1"></i>Nouveau dossier
            </button>
            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#modalUpload">
                <i class="fas fa-upload me-1"></i>Téléverser un fichier
            </button>
        </div>
        <?php endif; ?>
    </div>

    <!-- Onglets Global / Par résidence -->
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= $isGlobal ? 'active' : '' ?>" href="<?= BASE_URL ?>/document/index?scope=global">
                <i class="fas fa-globe-europe me-1"></i>Documents globaux Domitys
            </a>
        </li>
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle <?= !$isGlobal ? 'active' : '' ?>" href="#" data-bs-toggle="dropdown" role="button">
                <i class="fas fa-building me-1"></i>Par résidence
                <?php if (!$isGlobal && $currentResidenceNom): ?>
                    : <strong><?= htmlspecialchars($currentResidenceNom) ?></strong>
                <?php endif; ?>
            </a>
            <ul class="dropdown-menu">
                <?php if (empty($residences)): ?>
                    <li><span class="dropdown-item-text text-muted small"><em>Aucune résidence accessible</em></span></li>
                <?php else: foreach ($residences as $r): ?>
                    <li>
                        <a class="dropdown-item <?= !$isGlobal && (int)$r['id'] === (int)$residenceId ? 'active' : '' ?>"
                           href="<?= BASE_URL ?>/document/index?scope=residence&amp;residence_id=<?= (int)$r['id'] ?>">
                            <?= htmlspecialchars($r['nom']) ?>
                            <small class="text-muted">— <?= htmlspecialchars($r['ville']) ?></small>
                        </a>
                    </li>
                <?php endforeach; endif; ?>
            </ul>
        </li>
    </ul>

    <!-- Stats du scope courant -->
    <div class="row g-3 mb-3">
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body py-2">
                <small class="text-muted text-uppercase d-block">Dossiers</small>
                <strong class="h5 mb-0"><?= (int)($stats['nb_dossiers'] ?? 0) ?></strong>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body py-2">
                <small class="text-muted text-uppercase d-block">Fichiers</small>
                <strong class="h5 mb-0"><?= (int)($stats['nb_fichiers'] ?? 0) ?></strong>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body py-2">
                <small class="text-muted text-uppercase d-block">Taille totale</small>
                <strong class="h5 mb-0"><?= $humanSize((int)($stats['taille_totale'] ?? 0)) ?></strong>
            </div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card shadow-sm h-100"><div class="card-body py-2">
                <small class="text-muted text-uppercase d-block">Périmètre</small>
                <strong class="h6 mb-0">
                    <?php if ($isGlobal): ?>
                        <i class="fas fa-globe-europe text-info me-1"></i>Global Domitys
                    <?php else: ?>
                        <i class="fas fa-building text-warning me-1"></i><?= htmlspecialchars($currentResidenceNom ?: '—') ?>
                    <?php endif; ?>
                </strong>
            </div></div>
        </div>
    </div>

    <!-- Breadcrumb arborescence -->
    <?php if (!empty($breadcrumbDocs)): ?>
    <nav class="mb-3"><ol class="breadcrumb">
        <li class="breadcrumb-item">
            <a href="<?= htmlspecialchars($buildUrl(['dossier' => null])) ?>"><i class="fas fa-home"></i> Racine</a>
        </li>
        <?php foreach ($breadcrumbDocs as $i => $crumb):
            $isLast = $i === count($breadcrumbDocs) - 1;
        ?>
            <?php if ($isLast): ?>
                <li class="breadcrumb-item active"><?= htmlspecialchars($crumb['nom']) ?></li>
            <?php else: ?>
                <li class="breadcrumb-item">
                    <a href="<?= htmlspecialchars($buildUrl(['dossier' => (int)$crumb['id']])) ?>">
                        <?= htmlspecialchars($crumb['nom']) ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ol></nav>
    <?php endif; ?>

    <?php if (!$canWrite): ?>
    <div class="alert alert-info small">
        <i class="fas fa-eye me-2"></i>Vous êtes en mode <strong>lecture seule</strong> sur ce périmètre.
    </div>
    <?php endif; ?>

    <!-- Dossiers -->
    <?php if (!empty($dossiers)): ?>
    <h5 class="mt-4 mb-2 text-muted"><i class="fas fa-folder me-2"></i>Dossiers</h5>
    <div class="row g-2 mb-4">
        <?php foreach ($dossiers as $d): ?>
        <div class="col-6 col-md-4 col-lg-3">
            <div class="card shadow-sm h-100">
                <div class="card-body p-3">
                    <a href="<?= htmlspecialchars($buildUrl(['dossier' => (int)$d['id']])) ?>" class="text-decoration-none text-dark d-flex align-items-center">
                        <i class="fas fa-folder fa-2x text-warning me-2"></i>
                        <div class="flex-grow-1 text-truncate">
                            <strong class="d-block text-truncate" title="<?= htmlspecialchars($d['nom']) ?>"><?= htmlspecialchars($d['nom']) ?></strong>
                            <small class="text-muted">
                                <?= (int)$d['nb_sous_dossiers'] ?> sous-dossier(s) · <?= (int)$d['nb_fichiers'] ?> fichier(s)
                            </small>
                        </div>
                    </a>
                    <?php if ($canWrite): ?>
                    <div class="mt-2 d-flex gap-1 justify-content-end">
                        <button class="btn btn-sm btn-outline-secondary" title="Renommer"
                                onclick="renameDossierPrompt(<?= (int)$d['id'] ?>, <?= htmlspecialchars(json_encode($d['nom']), ENT_QUOTES) ?>)">
                            <i class="fas fa-edit"></i>
                        </button>
                        <form method="POST" action="<?= BASE_URL ?>/document/deleteDossier/<?= (int)$d['id'] ?>" class="d-inline"
                              onsubmit="return confirm('Supprimer le dossier « <?= htmlspecialchars(addslashes($d['nom']), ENT_QUOTES) ?> » et TOUT son contenu ?');">
                            <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Fichiers -->
    <h5 class="mt-4 mb-2 text-muted"><i class="fas fa-file me-2"></i>Fichiers</h5>
    <?php if (empty($fichiers)): ?>
        <div class="alert alert-info small mb-0">
            <i class="fas fa-info-circle me-1"></i>Aucun fichier dans ce dossier.
            <?php if ($canWrite): ?>Cliquez sur « Téléverser un fichier » pour ajouter le premier.<?php endif; ?>
        </div>
    <?php else: ?>
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <table class="table table-hover mb-0" id="fichiersTable">
                <thead class="table-light">
                    <tr>
                        <th>Nom</th>
                        <th>Description</th>
                        <th>Taille</th>
                        <th>Ajouté par</th>
                        <th>Date</th>
                        <th class="no-sort text-end" style="width:140px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fichiers as $f): ?>
                    <tr>
                        <td>
                            <i class="fas <?= $mimeIcon($f['mime_type']) ?> me-2 text-muted"></i>
                            <?= htmlspecialchars($f['nom_original']) ?>
                        </td>
                        <td class="small text-muted"><?= htmlspecialchars($f['description'] ?? '') ?: '—' ?></td>
                        <td class="small" data-sort="<?= (int)($f['taille_octets'] ?? 0) ?>"><?= $humanSize((int)($f['taille_octets'] ?? 0)) ?></td>
                        <td class="small"><?= htmlspecialchars(trim($f['uploaded_by_nom'] ?? '')) ?: '—' ?></td>
                        <td class="small" data-sort="<?= strtotime($f['uploaded_at']) ?>"><?= date('d/m/Y H:i', strtotime($f['uploaded_at'])) ?></td>
                        <td class="text-end">
                            <?php if ($canPreview($f['mime_type'])): ?>
                            <a href="<?= BASE_URL ?>/document/preview/<?= (int)$f['id'] ?>" target="_blank" class="btn btn-sm btn-outline-info" title="Aperçu">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>/document/download/<?= (int)$f['id'] ?>" class="btn btn-sm btn-outline-primary" title="Télécharger">
                                <i class="fas fa-download"></i>
                            </a>
                            <?php if ($canWrite): ?>
                            <form method="POST" action="<?= BASE_URL ?>/document/deleteFichier/<?= (int)$f['id'] ?>" class="d-inline"
                                  onsubmit="return confirm('Supprimer ce fichier définitivement ?');">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer"><i class="fas fa-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (count($fichiers) > 10): ?>
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
                <small class="text-muted" id="tableInfo"></small>
                <ul class="pagination pagination-sm mb-0" id="pagination"></ul>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php if ($canWrite): ?>
<!-- Modal création dossier -->
<div class="modal fade" id="modalCreateDossier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/document/createDossier?scope=<?= $scope ?><?= $isGlobal ? '' : '&residence_id=' . (int)$residenceId ?>">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="scope" value="<?= $scope ?>">
                <?php if (!$isGlobal): ?><input type="hidden" name="residence_id" value="<?= (int)$residenceId ?>"><?php endif; ?>
                <?php if (!empty($dossierCourant['id'])): ?>
                <input type="hidden" name="parent_id" value="<?= (int)$dossierCourant['id'] ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus me-2"></i>Nouveau dossier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nom du dossier</label>
                    <input type="text" name="nom" class="form-control" required maxlength="255" autofocus placeholder="Ex: Contrats fournisseurs 2026">
                    <small class="text-muted">Caractères interdits : / \ &lt; &gt; : " | ? *</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal upload fichier -->
<div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/document/upload?scope=<?= $scope ?><?= $isGlobal ? '' : '&residence_id=' . (int)$residenceId ?>" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <input type="hidden" name="scope" value="<?= $scope ?>">
                <?php if (!$isGlobal): ?><input type="hidden" name="residence_id" value="<?= (int)$residenceId ?>"><?php endif; ?>
                <?php if (!empty($dossierCourant['id'])): ?>
                <input type="hidden" name="dossier_id" value="<?= (int)$dossierCourant['id'] ?>">
                <?php endif; ?>
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload me-2"></i>Téléverser un fichier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Fichier <small class="text-muted">(max 50 Mo)</small></label>
                        <input type="file" name="fichier" class="form-control" required>
                        <small class="text-muted">Formats : PDF, DOC/DOCX, XLS/XLSX, ODT/ODS, JPG/PNG/WEBP/GIF, MP4/MOV/WEBM, ZIP, CSV, TXT</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <small class="text-muted">(optionnel)</small></label>
                        <input type="text" name="description" class="form-control" maxlength="500" placeholder="Ex: contrat 2026 signé Domitys/Veolia">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-warning"><i class="fas fa-upload me-1"></i>Téléverser</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal renommer dossier -->
<div class="modal fade" id="modalRenameDossier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="" id="formRenameDossier">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Renommer le dossier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Nouveau nom</label>
                    <input type="text" name="nom" id="renameDossierInput" class="form-control" required maxlength="255">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i>Renommer</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function renameDossierPrompt(id, currentName) {
    const form = document.getElementById('formRenameDossier');
    form.action = '<?= BASE_URL ?>/document/renameDossier/' + id;
    document.getElementById('renameDossierInput').value = currentName;
    new bootstrap.Modal(document.getElementById('modalRenameDossier')).show();
}
</script>
<?php endif; ?>

<?php if (!empty($fichiers) && count($fichiers) > 10): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('fichiersTable', {
    rowsPerPage: 15,
    excludeColumns: [5],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php elseif (!empty($fichiers)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script>
new DataTable('fichiersTable', { excludeColumns: [5] });
</script>
<?php endif; ?>
