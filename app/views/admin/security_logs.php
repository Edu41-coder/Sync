<?php $title = "Logs de Sécurité"; ?>

<div class="container-fluid py-4">
    
    <!-- Fil d'Ariane -->
    <?php
    $breadcrumb = [
        ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
        ['icon' => 'fas fa-shield-alt', 'text' => 'Logs de Sécurité', 'url' => null]
    ];
    include __DIR__ . '/../partials/breadcrumb.php';
    ?>
    
    <!-- En-tête -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-shield-alt text-danger"></i> Logs de Sécurité
        </h1>
        <a href="<?= BASE_URL ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Retour
        </a>
    </div>

    <!-- Statistiques -->
    <div class="row mb-4">
        <div class="col-12 col-md-3 mb-3">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body">
                    <div class="text-danger fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">
                        Tentatives Bloquées
                    </div>
                    <div class="h5 mb-0 fw-bold text-gray-800">
                        <?= $stats['unauthorized'] ?? 0 ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-3 mb-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body">
                    <div class="text-warning fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">
                        Échecs Connexion
                    </div>
                    <div class="h5 mb-0 fw-bold text-gray-800">
                        <?= $stats['failed_logins'] ?? 0 ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-3 mb-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body">
                    <div class="text-info fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">
                        Violations CSRF
                    </div>
                    <div class="h5 mb-0 fw-bold text-gray-800">
                        <?= $stats['csrf_violations'] ?? 0 ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-md-3 mb-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body">
                    <div class="text-primary fw-bold text-uppercase mb-1" style="font-size: 0.7rem;">
                        IPs Bloquées
                    </div>
                    <div class="h5 mb-0 fw-bold text-gray-800">
                        <?= $stats['blocked_ips'] ?? 0 ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des logs -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 fw-bold text-primary">
                <i class="fas fa-list"></i> Derniers événements de sécurité
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($logs)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Aucun log de sécurité pour le moment.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Date/Heure</th>
                                <th>Type</th>
                                <th>Détails</th>
                                <th>Utilisateur</th>
                                <th>IP</th>
                                <th>User Agent</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                            <tr>
                                <td class="text-nowrap">
                                    <?= date('d/m/Y H:i:s', strtotime($log['timestamp'])) ?>
                                </td>
                                <td>
                                    <?php
                                    $badgeClass = 'secondary';
                                    $icon = 'fas fa-circle';
                                    
                                    switch ($log['type']) {
                                        case 'UNAUTHORIZED_ACCESS':
                                            $badgeClass = 'danger';
                                            $icon = 'fas fa-ban';
                                            break;
                                        case 'FAILED_LOGIN':
                                            $badgeClass = 'warning';
                                            $icon = 'fas fa-exclamation-triangle';
                                            break;
                                        case 'CSRF_VIOLATION':
                                            $badgeClass = 'info';
                                            $icon = 'fas fa-shield-alt';
                                            break;
                                        case 'RATE_LIMIT_EXCEEDED':
                                            $badgeClass = 'dark';
                                            $icon = 'fas fa-clock';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?= $badgeClass ?>">
                                        <i class="<?= $icon ?>"></i>
                                        <?= str_replace('_', ' ', $log['type']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['type'] === 'UNAUTHORIZED_ACCESS'): ?>
                                        <small>
                                            <strong>Ressource:</strong> <?= htmlspecialchars($log['resource'] ?? '-') ?><br>
                                            <strong>Raison:</strong> <?= htmlspecialchars($log['reason'] ?? '-') ?>
                                        </small>
                                    <?php elseif ($log['type'] === 'FAILED_LOGIN'): ?>
                                        <small>
                                            <strong>Username:</strong> <?= htmlspecialchars($log['username'] ?? '-') ?><br>
                                            <strong>Raison:</strong> <?= htmlspecialchars($log['reason'] ?? '-') ?>
                                        </small>
                                    <?php elseif ($log['type'] === 'CSRF_VIOLATION'): ?>
                                        <small>
                                            <strong>URL:</strong> <?= htmlspecialchars($log['url'] ?? '-') ?>
                                        </small>
                                    <?php elseif ($log['type'] === 'RATE_LIMIT_EXCEEDED'): ?>
                                        <small>
                                            <strong>Requêtes:</strong> <?= htmlspecialchars($log['request_count'] ?? '-') ?>
                                        </small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (isset($log['user_id']) && $log['user_id'] !== 'anonymous'): ?>
                                        <span class="badge bg-secondary">
                                            <i class="fas fa-user"></i> 
                                            ID: <?= htmlspecialchars($log['user_id']) ?>
                                        </span>
                                        <?php if (isset($log['role'])): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($log['role']) ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">Anonyme</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <code><?= htmlspecialchars($log['ip'] ?? '-') ?></code>
                                </td>
                                <td>
                                    <small class="text-muted" style="max-width: 200px; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                        <?= htmlspecialchars(substr($log['user_agent'] ?? '-', 0, 50)) ?>
                                    </small>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- IPs Bloquées -->
    <?php if (!empty($blockedIps)): ?>
    <div class="card shadow">
        <div class="card-header py-3 bg-danger text-white">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-ban"></i> IPs Bloquées Temporairement
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>IP</th>
                            <th>Raison</th>
                            <th>Bloquée le</th>
                            <th>Expire le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($blockedIps as $ip => $data): ?>
                        <tr>
                            <td><code><?= htmlspecialchars($ip) ?></code></td>
                            <td><?= htmlspecialchars($data['reason'] ?? '-') ?></td>
                            <td><?= date('d/m/Y H:i:s', $data['blocked_at']) ?></td>
                            <td><?= date('d/m/Y H:i:s', $data['expires_at']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>
