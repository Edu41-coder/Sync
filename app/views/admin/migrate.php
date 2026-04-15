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
                <div class="card-body p-0">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width:50px">#</th>
                                <th>Migration</th>
                                <th class="text-center" style="width:100px">Statut</th>
                                <th style="width:100px">Batch</th>
                                <th style="width:170px">Appliquée le</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($status)): ?>
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="fas fa-folder-open me-2"></i>Aucun fichier de migration trouvé dans <code>database/migrations/</code>
                                </td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($status as $i => $m): ?>
                                <tr class="<?= $m['applied'] ? '' : 'table-warning' ?>">
                                    <td class="text-muted"><?= $i + 1 ?></td>
                                    <td>
                                        <code><?= htmlspecialchars($m['name']) ?></code>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($m['applied']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check me-1"></i>OK</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>En attente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= $m['batch'] ?? '-' ?>
                                    </td>
                                    <td>
                                        <?= !empty($m['applied_at']) ? date('d/m/Y H:i', strtotime($m['applied_at'])) : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($nbPending > 0): ?>
                <div class="card-footer">
                    <form method="POST" action="<?= BASE_URL ?>/admin/migrate" onsubmit="return confirm('Appliquer <?= $nbPending ?> migration(s) en attente ?');">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="action" value="migrate">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-play me-2"></i>Appliquer <?= $nbPending ?> migration(s)
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>

            <!-- Résultats de la dernière exécution -->
            <?php if ($results !== null): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-terminal me-2"></i>Résultat de l'exécution</h6>
                </div>
                <div class="card-body">
                    <?php if (!empty($results['applied'])): ?>
                        <div class="alert alert-success">
                            <strong>Migrations appliquées :</strong>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($results['applied'] as $name): ?>
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

                    <?php if (empty($results['applied']) && empty($results['errors'])): ?>
                        <p class="text-muted mb-0">Aucune migration en attente.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Marquer le schéma initial -->
            <?php
                $initialApplied = false;
                foreach ($status as $m) {
                    if ($m['name'] === '001_initial_schema' && $m['applied']) $initialApplied = true;
                }
            ?>
            <?php if (!$initialApplied): ?>
            <div class="card shadow-sm mb-4 border-info">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Première utilisation</h6>
                </div>
                <div class="card-body">
                    <p class="small">Le schéma initial (<code>synd_gest.sql</code>) est déjà en place dans votre base de données.</p>
                    <p class="small mb-3">Cliquez ci-dessous pour le marquer comme appliqué, puis lancez les migrations suivantes.</p>
                    <form method="POST" action="<?= BASE_URL ?>/admin/migrate">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                        <input type="hidden" name="action" value="mark_initial">
                        <button type="submit" class="btn btn-info btn-sm w-100">
                            <i class="fas fa-check me-2"></i>Marquer le schéma initial
                        </button>
                    </form>
                </div>
            </div>
            <?php endif; ?>

            <!-- Guide -->
            <div class="card shadow-sm mb-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-book me-2"></i>Comment ça marche</h6>
                </div>
                <div class="card-body small">
                    <p><strong>Créer une migration :</strong></p>
                    <ol class="ps-3">
                        <li>Créer un fichier SQL dans <code>database/migrations/</code></li>
                        <li>Nommer avec un préfixe numérique : <code>004_description.sql</code></li>
                        <li>Écrire les instructions SQL (ALTER, CREATE, INSERT...)</li>
                        <li>Venir ici et cliquer "Appliquer"</li>
                    </ol>
                    <p class="mb-1"><strong>Règles :</strong></p>
                    <ul class="ps-3 mb-0">
                        <li>Chaque migration est exécutée <strong>une seule fois</strong></li>
                        <li>L'ordre est déterminé par le préfixe numérique</li>
                        <li>En cas d'erreur, l'exécution s'arrête</li>
                        <li>Les migrations sont versionnées dans git</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
