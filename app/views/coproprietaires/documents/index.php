<?php
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-folder-open', 'text' => 'Mes Documents', 'url' => BASE_URL . '/coproprietaireDocument/index'],
];
foreach ($breadcrumbDocs as $i => $d) {
    $isLast = ($i === count($breadcrumbDocs) - 1);
    $breadcrumb[] = [
        'icon' => 'fas fa-folder',
        'text' => $d['nom'],
        'url'  => $isLast ? null : BASE_URL . '/coproprietaireDocument/index/' . $d['id'],
    ];
}
include __DIR__ . '/../../partials/breadcrumb.php';

$csrfToken = Security::getToken();
$dossierCourantId = $dossierCourant['id'] ?? null;
$parentRetourId = $dossierCourant['parent_id'] ?? null;

function formatOctets(int $o): string {
    if ($o < 1024) return $o . ' o';
    if ($o < 1048576) return round($o / 1024, 1) . ' Ko';
    if ($o < 1073741824) return round($o / 1048576, 1) . ' Mo';
    return round($o / 1073741824, 2) . ' Go';
}

function iconeMime(?string $mime): string {
    if (!$mime) return 'fas fa-file text-muted';
    if (str_starts_with($mime, 'image/')) return 'fas fa-file-image text-info';
    if (str_starts_with($mime, 'video/')) return 'fas fa-file-video text-danger';
    if ($mime === 'application/pdf') return 'fas fa-file-pdf text-danger';
    if (str_contains($mime, 'word') || str_contains($mime, 'opendocument.text')) return 'fas fa-file-word text-primary';
    if (str_contains($mime, 'sheet') || str_contains($mime, 'excel')) return 'fas fa-file-excel text-success';
    if ($mime === 'application/zip') return 'fas fa-file-archive text-warning';
    return 'fas fa-file text-muted';
}

function estPreviewable(?string $mime): bool {
    if (!$mime) return false;
    return str_starts_with($mime, 'image/')
        || str_starts_with($mime, 'video/')
        || $mime === 'application/pdf';
}
?>

<div class="container-fluid py-4">

    <!-- En-tête + quota -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <h1 class="h3 mb-1"><i class="fas fa-folder-open text-primary me-2"></i> Mes Documents</h1>
            <p class="text-muted mb-0">Espace de stockage personnel — PDF, images, vidéos, bureautique, archives.</p>
        </div>
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between small mb-1">
                        <span><i class="fas fa-database text-muted me-1"></i> Quota utilisé</span>
                        <strong><?= formatOctets($stats['quota_utilise']) ?> / <?= formatOctets($stats['quota_total']) ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <?php
                            $pct = $stats['pourcentage_utilise'];
                            $barClass = $pct >= 90 ? 'bg-danger' : ($pct >= 70 ? 'bg-warning' : 'bg-success');
                        ?>
                        <div class="progress-bar <?= $barClass ?>" style="width: <?= min(100, $pct) ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between small mt-1 text-muted">
                        <span><?= $pct ?>% utilisé</span>
                        <span><?= $stats['nb_fichiers'] ?> fichier(s) • <?= $stats['nb_dossiers'] ?> dossier(s)</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center justify-content-between">
            <div>
                <?php if ($dossierCourant): ?>
                    <a href="<?= BASE_URL ?>/coproprietaireDocument/index<?= $parentRetourId ? '/' . $parentRetourId : '' ?>"
                       class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Retour
                    </a>
                    <span class="ms-2 fw-semibold">
                        <i class="fas fa-folder text-warning me-1"></i>
                        <?= htmlspecialchars($dossierCourant['nom']) ?>
                    </span>
                <?php else: ?>
                    <span class="fw-semibold">
                        <i class="fas fa-home text-primary me-1"></i> Racine
                    </span>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNouveauDossier">
                    <i class="fas fa-folder-plus me-1"></i> Nouveau dossier
                </button>
                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#modalUpload">
                    <i class="fas fa-upload me-1"></i> Uploader un fichier
                </button>
            </div>
        </div>
    </div>

    <!-- Liste : dossiers + fichiers -->
    <div class="card shadow-sm">
        <div class="card-body p-0">
            <?php if (empty($dossiers) && empty($fichiers)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-folder-open fa-3x mb-3 d-block opacity-50"></i>
                    <h6>Ce dossier est vide</h6>
                    <p class="small mb-0">Utilisez les boutons ci-dessus pour ajouter un dossier ou un fichier.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0" id="docsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="sortable" data-column="0">Nom</th>
                                <th class="sortable text-center" data-column="1">Type</th>
                                <th class="sortable text-end" data-column="2" data-type="number">Taille</th>
                                <th class="sortable text-center" data-column="3">Date</th>
                                <th class="text-center" data-no-sort>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dossiers as $d): ?>
                                <tr>
                                    <td data-sort="0_<?= htmlspecialchars($d['nom']) ?>">
                                        <a href="<?= BASE_URL ?>/coproprietaireDocument/index/<?= $d['id'] ?>" class="text-decoration-none">
                                            <i class="fas fa-folder text-warning me-2"></i>
                                            <strong><?= htmlspecialchars($d['nom']) ?></strong>
                                        </a>
                                        <?php if ($d['nb_sous_dossiers'] > 0 || $d['nb_fichiers'] > 0): ?>
                                            <small class="text-muted ms-2">
                                                (<?= $d['nb_sous_dossiers'] ?> dossier(s), <?= $d['nb_fichiers'] ?> fichier(s))
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center" data-sort="dossier">
                                        <span class="badge bg-warning text-dark">Dossier</span>
                                    </td>
                                    <td class="text-end text-muted" data-sort="0">—</td>
                                    <td class="text-center" data-sort="<?= $d['created_at'] ?>">
                                        <small><?= date('d/m/Y', strtotime($d['created_at'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <a href="<?= BASE_URL ?>/coproprietaireDocument/index/<?= $d['id'] ?>"
                                               class="btn btn-outline-primary" title="Ouvrir">
                                                <i class="fas fa-folder-open"></i>
                                            </a>
                                            <button class="btn btn-outline-secondary btn-renameDossier"
                                                    data-id="<?= $d['id'] ?>" data-nom="<?= htmlspecialchars($d['nom']) ?>" title="Renommer">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-outline-danger btn-deleteDossier"
                                                    data-id="<?= $d['id'] ?>" data-nom="<?= htmlspecialchars($d['nom']) ?>" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                            <?php foreach ($fichiers as $f): ?>
                                <tr>
                                    <td data-sort="1_<?= htmlspecialchars($f['nom_original']) ?>">
                                        <i class="<?= iconeMime($f['mime_type']) ?> me-2"></i>
                                        <?= htmlspecialchars($f['nom_original']) ?>
                                    </td>
                                    <td class="text-center" data-sort="<?= htmlspecialchars($f['mime_type'] ?? '') ?>">
                                        <small class="text-muted"><?= htmlspecialchars(pathinfo($f['nom_original'], PATHINFO_EXTENSION)) ?></small>
                                    </td>
                                    <td class="text-end text-muted" data-sort="<?= $f['taille_octets'] ?>">
                                        <?= formatOctets((int)$f['taille_octets']) ?>
                                    </td>
                                    <td class="text-center" data-sort="<?= $f['uploaded_at'] ?>">
                                        <small><?= date('d/m/Y H:i', strtotime($f['uploaded_at'])) ?></small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm">
                                            <?php if (estPreviewable($f['mime_type'])): ?>
                                                <button class="btn btn-outline-info btn-preview"
                                                        data-url="<?= BASE_URL ?>/coproprietaireDocument/preview/<?= $f['id'] ?>"
                                                        data-mime="<?= htmlspecialchars($f['mime_type']) ?>"
                                                        data-nom="<?= htmlspecialchars($f['nom_original']) ?>"
                                                        title="Aperçu">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="<?= BASE_URL ?>/coproprietaireDocument/download/<?= $f['id'] ?>"
                                               class="btn btn-outline-primary" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </a>
                                            <button class="btn btn-outline-danger btn-deleteFichier"
                                                    data-id="<?= $f['id'] ?>" data-nom="<?= htmlspecialchars($f['nom_original']) ?>" title="Supprimer">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        <?php $totalLignes = count($dossiers) + count($fichiers); if ($totalLignes > 10): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
            <div class="text-muted small" id="tableInfo">
                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, $totalLignes) ?></span>
                sur <span id="totalEntries"><?= $totalLignes ?></span> éléments
            </div>
            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal : Nouveau dossier -->
<div class="modal fade" id="modalNouveauDossier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/coproprietaireDocument/createDossier">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-folder-plus text-primary me-2"></i> Nouveau dossier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="parent_id" value="<?= $dossierCourantId ?? '' ?>">
                    <label class="form-label">Nom du dossier</label>
                    <input type="text" name="nom" class="form-control" required maxlength="255" autofocus
                           placeholder="Ex: Contrats 2026">
                    <small class="text-muted">Caractères interdits : / \ &lt; &gt; : " | ? *</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Créer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Upload fichier -->
<div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/coproprietaireDocument/upload" enctype="multipart/form-data">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-upload text-primary me-2"></i> Uploader un fichier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="dossier_id" value="<?= $dossierCourantId ?? '' ?>">
                    <label class="form-label">Fichier <span class="text-danger">*</span></label>
                    <input type="file" name="fichier" class="form-control" required id="inputFichierUpload">
                    <div class="form-text">
                        Taille max : <strong>50 Mo</strong> — Quota disponible :
                        <strong><?= formatOctets($stats['quota_disponible']) ?></strong>
                    </div>
                    <div class="mt-3 small text-muted">
                        <strong>Types autorisés :</strong><br>
                        PDF, DOC, DOCX, XLS, XLSX, ODT, ODS<br>
                        JPG, PNG, WEBP, GIF<br>
                        MP4, MOV, WEBM<br>
                        ZIP
                    </div>
                    <div id="uploadAlerteTaille" class="alert alert-warning mt-3 d-none small mb-0"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="btnSubmitUpload">
                        <i class="fas fa-upload me-1"></i> Uploader
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Renommer dossier -->
<div class="modal fade" id="modalRenameDossier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formRenameDossier">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit text-primary me-2"></i> Renommer le dossier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="retour_id" value="<?= $dossierCourantId ?? '' ?>">
                    <label class="form-label">Nouveau nom</label>
                    <input type="text" name="nom" class="form-control" required maxlength="255" id="inputRenameNom">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Renommer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Confirmer suppression dossier -->
<div class="modal fade" id="modalDeleteDossier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formDeleteDossier">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Supprimer le dossier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="retour_id" value="<?= $dossierCourantId ?? '' ?>">
                    <p>Êtes-vous sûr de vouloir supprimer le dossier <strong id="nomDossierDelete"></strong> ?</p>
                    <div class="alert alert-danger mb-0 small">
                        <i class="fas fa-exclamation-circle me-1"></i>
                        Cette action supprimera <strong>tous les fichiers et sous-dossiers</strong> qu'il contient.
                        Action irréversible.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Confirmer suppression fichier -->
<div class="modal fade" id="modalDeleteFichier" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="formDeleteFichier">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i> Supprimer le fichier</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                    <input type="hidden" name="retour_id" value="<?= $dossierCourantId ?? '' ?>">
                    <p>Supprimer <strong id="nomFichierDelete"></strong> ?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger"><i class="fas fa-trash me-1"></i> Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Aperçu -->
<div class="modal fade" id="modalPreview" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="previewTitle"><i class="fas fa-eye me-2"></i> Aperçu</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0" id="previewBody" style="min-height: 500px;"></div>
        </div>
    </div>
</div>

<?php if ((count($dossiers) + count($fichiers)) > 3): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<?php if ((count($dossiers) + count($fichiers)) > 10): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<?php endif; ?>
<script>
<?php if ((count($dossiers) + count($fichiers)) > 10): ?>
new DataTableWithPagination('docsTable', {
    rowsPerPage: 10,
    excludeColumns: [4],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
<?php else: ?>
new DataTable('docsTable', { excludeColumns: [4] });
<?php endif; ?>
</script>
<?php endif; ?>

<script>
(function() {
    const BASE = '<?= BASE_URL ?>';
    const TAILLE_MAX = <?= CoproprietaireDocument::TAILLE_MAX_FICHIER ?>;
    const QUOTA_DISPO = <?= $stats['quota_disponible'] ?>;

    // Vérif taille avant upload
    const inputFichier = document.getElementById('inputFichierUpload');
    const alerteTaille = document.getElementById('uploadAlerteTaille');
    const btnSubmit = document.getElementById('btnSubmitUpload');
    if (inputFichier) {
        inputFichier.addEventListener('change', function() {
            alerteTaille.classList.add('d-none');
            btnSubmit.disabled = false;
            if (!this.files[0]) return;
            const f = this.files[0];
            if (f.size > TAILLE_MAX) {
                alerteTaille.textContent = 'Fichier trop volumineux (' + (f.size / 1048576).toFixed(1) + ' Mo). Max : 50 Mo.';
                alerteTaille.classList.remove('d-none');
                btnSubmit.disabled = true;
            } else if (f.size > QUOTA_DISPO) {
                alerteTaille.textContent = 'Quota insuffisant. Il reste ' + (QUOTA_DISPO / 1048576).toFixed(1) + ' Mo disponibles.';
                alerteTaille.classList.remove('d-none');
                btnSubmit.disabled = true;
            }
        });
    }

    // Renommer dossier
    document.querySelectorAll('.btn-renameDossier').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nom = this.dataset.nom;
            document.getElementById('inputRenameNom').value = nom;
            document.getElementById('formRenameDossier').action = BASE + '/coproprietaireDocument/renameDossier/' + id;
            new bootstrap.Modal(document.getElementById('modalRenameDossier')).show();
        });
    });

    // Supprimer dossier
    document.querySelectorAll('.btn-deleteDossier').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nom = this.dataset.nom;
            document.getElementById('nomDossierDelete').textContent = nom;
            document.getElementById('formDeleteDossier').action = BASE + '/coproprietaireDocument/deleteDossier/' + id;
            new bootstrap.Modal(document.getElementById('modalDeleteDossier')).show();
        });
    });

    // Supprimer fichier
    document.querySelectorAll('.btn-deleteFichier').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nom = this.dataset.nom;
            document.getElementById('nomFichierDelete').textContent = nom;
            document.getElementById('formDeleteFichier').action = BASE + '/coproprietaireDocument/deleteFichier/' + id;
            new bootstrap.Modal(document.getElementById('modalDeleteFichier')).show();
        });
    });

    // Aperçu (image / vidéo / PDF)
    document.querySelectorAll('.btn-preview').forEach(btn => {
        btn.addEventListener('click', function() {
            const url = this.dataset.url;
            const mime = this.dataset.mime;
            const nom = this.dataset.nom;
            const body = document.getElementById('previewBody');
            document.getElementById('previewTitle').innerHTML = '<i class="fas fa-eye me-2"></i> ' + nom;

            if (mime.startsWith('image/')) {
                body.innerHTML = '<img src="' + url + '" class="img-fluid" style="max-height: 80vh;" alt="">';
            } else if (mime.startsWith('video/')) {
                body.innerHTML = '<video src="' + url + '" controls class="w-100" style="max-height: 80vh;"></video>';
            } else if (mime === 'application/pdf') {
                body.innerHTML = '<iframe src="' + url + '" style="width:100%; height:80vh; border:0;"></iframe>';
            }
            new bootstrap.Modal(document.getElementById('modalPreview')).show();
        });
    });
})();
</script>
