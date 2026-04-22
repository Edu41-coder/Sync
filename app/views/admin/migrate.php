<div class="container-fluid py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>">Tableau de bord</a></li>
            <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/admin/index">Administration</a></li>
            <li class="breadcrumb-item active">Migrations DB</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-database me-2"></i>Migrations de base de données</h5>
                    <?php $nbPending = count($pending ?? []); ?>
                    <?php if ($nbPending > 0): ?>
                        <span class="badge bg-warning"><?= $nbPending ?> en attente</span>
                    <?php else: ?>
                        <span class="badge bg-success">À jour</span>
                    <?php endif; ?>
                </div>

<form method="POST" action="<?= BASE_URL ?>/admin/migrate" id="formMigrate"
                      onsubmit="return confirmMigrateSelected();">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="migrate">

                    <?php if (!empty($status)): ?>
                    <div class="p-3 border-bottom">
                        <input type="text" id="searchMigrations" class="form-control form-control-sm"
                               style="max-width:320px" placeholder="Rechercher une migration…">
                    </div>
                    <?php endif; ?>

                    <div class="card-body p-0">
                        <table class="table table-hover mb-0" id="migrationsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width:40px" data-no-sort>
                                        <input type="checkbox" class="form-check-input" id="checkAllPending"
                                               title="Cocher toutes les migrations en attente">
                                    </th>
                                    <th class="sortable" data-column="1">Migration</th>
                                    <th class="sortable text-center" data-column="2" style="width:120px">Statut</th>
                                    <th class="sortable text-center" data-column="3" data-type="number" style="width:80px">Batch</th>
                                    <th class="sortable" data-column="4" style="width:160px">Appliquée le</th>
                                    <th class="text-center" style="width:110px" data-no-sort>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($status)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-folder-open me-2"></i>Aucun fichier dans <code>database/migrations/</code>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($status as $m): ?>
                                    <tr class="<?= $m['applied'] ? '' : 'table-warning' ?>">
                                        <td class="text-center">
                                            <?php if (!$m['applied']): ?>
                                                <input type="checkbox" class="form-check-input chk-pending"
                                                       name="selected[]" value="<?= htmlspecialchars($m['name']) ?>">
                                            <?php endif; ?>
                                        </td>
                                        <td data-sort="<?= htmlspecialchars($m['name']) ?>">
                                            <code><?= htmlspecialchars($m['name']) ?></code>
                                        </td>
                                        <td class="text-center" data-sort="<?= $m['applied'] ? '1_ok' : '0_pending' ?>">
                                            <?php if ($m['applied']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-center" data-sort="<?= (int)($m['batch'] ?? 0) ?>">
                                            <?= $m['batch'] ?? '-' ?>
                                        </td>
                                        <td data-sort="<?= !empty($m['applied_at']) ? strtotime($m['applied_at']) : 0 ?>">
                                            <?= !empty($m['applied_at']) ? date('d/m/Y H:i', strtotime($m['applied_at'])) : '-' ?>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group btn-group-sm" role="group">
                                                <button type="button" class="btn btn-outline-info btn-view-sql"
                                                        data-name="<?= htmlspecialchars($m['name']) ?>"
                                                        title="Voir le SQL">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($m['applied']): ?>
                                                    <button type="button" class="btn btn-outline-danger btn-unmark"
                                                            data-name="<?= htmlspecialchars($m['name']) ?>"
                                                            title="Démarquer (retirer du suivi sans modifier la DB)">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-outline-success btn-mark"
                                                            data-name="<?= htmlspecialchars($m['name']) ?>"
                                                            title="Marquer comme appliquée (sans exécuter le SQL)">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <?php if (!empty($status)): ?>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                            <small class="text-muted" id="tableInfo">
                                Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(5, count($status)) ?></span>
                                sur <span id="totalEntries"><?= count($status) ?></span> migration(s)
                            </small>
                            <nav><ul class="pagination pagination-sm mb-0" id="pagination"></ul></nav>
                        </div>
                        <?php if ($nbPending > 0): ?>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2">
                            <small class="text-muted">
                                <span id="countSelected">0</span> migration(s) sélectionnée(s) sur <?= $nbPending ?> en attente
                            </small>
                            <button type="submit" class="btn btn-primary" id="btnApplySelected" disabled>
                                <i class="fas fa-play me-2"></i>Appliquer la sélection
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Résultats -->
            <?php if ($results !== null): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-terminal me-2"></i>Résultat de l'exécution</h6></div>
                <div class="card-body">
                    <?php if (!empty($results['applied'])): ?>
                        <div class="alert alert-success">
                            <strong>Appliquées :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($results['applied'] as $name): ?>
                                <li><code><?= htmlspecialchars($name) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($results['skipped'])): ?>
                        <div class="alert alert-info">
                            <strong>Ignorées (déjà appliquées) :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($results['skipped'] as $name): ?>
                                <li><code><?= htmlspecialchars($name) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($results['errors'])): ?>
                        <div class="alert alert-danger">
                            <strong>Erreurs :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($results['errors'] as $err): ?>
                                <li><code><?= htmlspecialchars($err) ?></code></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($results['applied']) && empty($results['errors']) && empty($results['skipped'])): ?>
                        <p class="text-muted mb-0">Rien à faire.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Guide -->
            <div class="card shadow-sm mb-4">
                <div class="card-header"><h6 class="mb-0"><i class="fas fa-book me-2"></i>Comment ça marche</h6></div>
                <div class="card-body small">
                    <p class="mb-2"><strong>Appliquer :</strong></p>
                    <ul class="ps-3 mb-3">
                        <li>Cocher les migrations à appliquer</li>
                        <li>Cliquer « Appliquer la sélection »</li>
                        <li>L'ordre suit le préfixe numérique</li>
                    </ul>
                    <p class="mb-2">
                        <span class="btn btn-sm btn-outline-info disabled me-1" style="pointer-events:none">
                            <i class="fas fa-eye"></i>
                        </span>
                        <strong>Voir le SQL :</strong>
                    </p>
                    <ul class="ps-3 mb-3">
                        <li>Ouvre le contenu du fichier <code>.sql</code> dans une fenêtre (lecture seule)</li>
                    </ul>

                    <p class="mb-2">
                        <span class="btn btn-sm btn-outline-success disabled me-1" style="pointer-events:none">
                            <i class="fas fa-check"></i>
                        </span>
                        <strong>Marquer OK :</strong>
                    </p>
                    <ul class="ps-3 mb-3">
                        <li>Insère dans la table <code>migrations</code> sans exécuter le SQL</li>
                        <li>À utiliser si la migration a déjà été appliquée hors système</li>
                    </ul>

                    <p class="mb-2">
                        <span class="btn btn-sm btn-outline-danger disabled me-1" style="pointer-events:none">
                            <i class="fas fa-undo"></i>
                        </span>
                        <strong>Démarquer :</strong>
                    </p>
                    <ul class="ps-3 mb-0">
                        <li>Retire l'entrée de la table <code>migrations</code></li>
                        <li class="text-danger">⚠️ N'annule PAS les changements SQL</li>
                        <li>Réappliquer ensuite peut échouer si le SQL n'a pas de garde (<code>IF NOT EXISTS</code>)</li>
                    </ul>
                </div>
            </div>

            <!-- Info -->
            <div class="card shadow-sm border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Source de vérité</h6>
                </div>
                <div class="card-body small">
                    <p class="mb-0">
                        Le statut est stocké dans la table <code>migrations</code> de la DB.
                        Il est donc <strong>spécifique à cet environnement</strong> — il ne suit pas git.
                        Les fichiers SQL, eux, sont versionnés dans <code>database/migrations/</code>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal : Aperçu SQL -->
<div class="modal fade" id="modalViewSql" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-code me-2"></i>
                    <code id="viewSqlTitle">migration.sql</code>
                    <small class="text-muted ms-2" id="viewSqlSize"></small>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <div id="viewSqlLoading" class="text-center py-5 text-muted">
                    <i class="fas fa-spinner fa-spin fa-2x mb-2 d-block"></i>
                    Chargement…
                </div>
                <div id="viewSqlError" class="alert alert-danger m-3 d-none"></div>
                <pre id="viewSqlContent" class="mb-0 p-3 d-none"
                     style="background:#1e1e1e;color:#d4d4d4;max-height:70vh;overflow:auto;font-size:.85rem;line-height:1.5;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCopySql">
                    <i class="fas fa-copy me-1"></i>Copier
                </button>
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal : Marquer OK -->
<div class="modal fade" id="modalMark" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/migrate">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-check text-success me-2"></i>Marquer comme appliquée</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="mark_applied">
                    <input type="hidden" name="name" id="markName" value="">
                    <p>Marquer <code id="markLabel"></code> comme appliquée <strong>sans exécuter</strong> le SQL ?</p>
                    <div class="alert alert-info mb-0 small">
                        À utiliser si cette migration a déjà été appliquée hors système (SQL direct, dump, etc.).
                        Action réversible via « Démarquer ».
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-check me-1"></i>Marquer OK</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal : Démarquer -->
<div class="modal fade" id="modalUnmark" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/admin/migrate">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle me-2"></i>Démarquer la migration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                    <input type="hidden" name="action" value="unmark_applied">
                    <input type="hidden" name="name" id="unmarkName" value="">
                    <p>Démarquer <code id="unmarkLabel"></code> ?</p>
                    <div class="alert alert-warning small">
                        <strong>⚠️ Cette action ne réverse PAS le SQL.</strong>
                        Elle retire juste l'entrée du suivi. Les tables / colonnes créées restent en place.
                    </div>
                    <div class="alert alert-danger small mb-3">
                        <strong>Si vous ré-appliquez ensuite</strong> cette migration via « Appliquer la sélection » :
                        <ul class="mb-0 mt-1 ps-3">
                            <li>Safe si le SQL utilise <code>CREATE TABLE IF NOT EXISTS</code>, <code>ADD COLUMN IF NOT EXISTS</code>…</li>
                            <li>❌ Échec probable si le SQL fait <code>CREATE TABLE</code>, <code>ALTER ADD</code> sans garde, ou <code>INSERT</code> sans contrôle</li>
                        </ul>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="confirmUnmark" required>
                        <label for="confirmUnmark" class="form-check-label">
                            Je comprends les risques et confirme.
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger" id="btnConfirmUnmark" disabled>
                        <i class="fas fa-undo me-1"></i>Démarquer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (!empty($status)): ?>
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
new DataTableWithPagination('migrationsTable', {
    rowsPerPage: 5,
    searchInputId: 'searchMigrations',
    excludeColumns: [0, 5],
    paginationId: 'pagination',
    infoId: 'tableInfo'
});
</script>
<?php endif; ?>

<script>
(function() {
    const countEl = document.getElementById('countSelected');
    const btnApply = document.getElementById('btnApplySelected');
    const checkAll = document.getElementById('checkAllPending');
    const tableEl = document.getElementById('migrationsTable');

    function allChks() {
        return tableEl ? tableEl.querySelectorAll('.chk-pending') : [];
    }

    function updateCount() {
        if (!countEl) return;
        let n = 0;
        allChks().forEach(c => { if (c.checked) n++; });
        countEl.textContent = n;
        if (btnApply) btnApply.disabled = (n === 0);
    }

    if (tableEl) {
        tableEl.addEventListener('change', function(e) {
            if (e.target && e.target.classList.contains('chk-pending')) {
                updateCount();
            }
        });
    }

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            allChks().forEach(c => { c.checked = this.checked; });
            updateCount();
        });
    }

    // Confirmation avant soumission
    window.confirmMigrateSelected = function() {
        const n = document.querySelectorAll('.chk-pending:checked').length;
        if (n === 0) return false;
        const noms = Array.from(document.querySelectorAll('.chk-pending:checked'))
            .map(c => c.value).join('\n  - ');
        return confirm('Appliquer ' + n + ' migration(s) ?\n\n  - ' + noms);
    };

    // Éléments du modal SQL
    const viewLoading = document.getElementById('viewSqlLoading');
    const viewError = document.getElementById('viewSqlError');
    const viewContent = document.getElementById('viewSqlContent');
    const viewTitle = document.getElementById('viewSqlTitle');
    const viewSize = document.getElementById('viewSqlSize');
    const btnCopy = document.getElementById('btnCopySql');
    const confirmUnmark = document.getElementById('confirmUnmark');
    const btnConfirmUnmark = document.getElementById('btnConfirmUnmark');

    if (confirmUnmark) {
        confirmUnmark.addEventListener('change', function() {
            btnConfirmUnmark.disabled = !this.checked;
        });
    }

    function formatSize(o) {
        if (o < 1024) return o + ' o';
        if (o < 1048576) return (o / 1024).toFixed(1) + ' Ko';
        return (o / 1048576).toFixed(1) + ' Mo';
    }

    async function openViewSql(name) {
        viewTitle.textContent = name + '.sql';
        viewSize.textContent = '';
        viewLoading.classList.remove('d-none');
        viewError.classList.add('d-none');
        viewContent.classList.add('d-none');
        viewContent.textContent = '';

        new bootstrap.Modal(document.getElementById('modalViewSql')).show();

        const url = '<?= BASE_URL ?>/admin/migrationSql/' + encodeURIComponent(name);
        try {
            const res = await fetch(url, {
                credentials: 'same-origin',
                cache: 'no-store',
                headers: { 'Accept': 'application/json' }
            });
            const text = await res.text();
            viewLoading.classList.add('d-none');

            if (!res.ok) {
                viewError.innerHTML = '<strong>HTTP ' + res.status + '</strong> — ' + res.statusText
                    + '<br><small><code>' + text.substring(0, 300).replace(/</g, '&lt;') + '</code></small>';
                viewError.classList.remove('d-none');
                return;
            }
            let data;
            try { data = JSON.parse(text); }
            catch (err) {
                viewError.innerHTML = '<strong>Réponse invalide</strong><br><small><code>'
                    + text.substring(0, 300).replace(/</g, '&lt;') + '</code></small>';
                viewError.classList.remove('d-none');
                return;
            }
            if (!data.success) {
                viewError.textContent = data.message || 'Erreur inconnue.';
                viewError.classList.remove('d-none');
                return;
            }
            viewContent.textContent = data.content;
            viewContent.classList.remove('d-none');
            viewSize.textContent = formatSize(data.size);
        } catch (e) {
            viewLoading.classList.add('d-none');
            viewError.textContent = 'Erreur réseau : ' + e.message;
            viewError.classList.remove('d-none');
        }
    }

    // Event delegation sur le tbody — gère les 3 types de boutons même après pagination
    const tbody = document.querySelector('#migrationsTable tbody');
    if (tbody) {
        tbody.addEventListener('click', function(e) {
            const btn = e.target.closest('.btn-view-sql, .btn-mark, .btn-unmark');
            if (!btn) return;
            const name = btn.dataset.name;

            if (btn.classList.contains('btn-view-sql')) {
                openViewSql(name);
            } else if (btn.classList.contains('btn-mark')) {
                document.getElementById('markName').value = name;
                document.getElementById('markLabel').textContent = name;
                new bootstrap.Modal(document.getElementById('modalMark')).show();
            } else if (btn.classList.contains('btn-unmark')) {
                document.getElementById('unmarkName').value = name;
                document.getElementById('unmarkLabel').textContent = name;
                if (confirmUnmark) {
                    confirmUnmark.checked = false;
                    btnConfirmUnmark.disabled = true;
                }
                new bootstrap.Modal(document.getElementById('modalUnmark')).show();
            }
        });
    }

    if (btnCopy) {
        btnCopy.addEventListener('click', function() {
            const txt = viewContent.textContent;
            if (!txt) return;
            navigator.clipboard.writeText(txt).then(() => {
                const orig = btnCopy.innerHTML;
                btnCopy.innerHTML = '<i class="fas fa-check me-1"></i>Copié !';
                setTimeout(() => { btnCopy.innerHTML = orig; }, 1500);
            });
        });
    }
})();
</script>
