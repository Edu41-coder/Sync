<?php
// Fil d'Ariane
$breadcrumb = [
    ['icon' => 'fas fa-tachometer-alt', 'text' => 'Tableau de bord', 'url' => BASE_URL],
    ['icon' => 'fas fa-users', 'text' => 'Gestion des utilisateurs', 'url' => null]
];
include __DIR__ . '/../../partials/breadcrumb.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="h3 mb-1">
                <i class="fas fa-users text-dark"></i>
                Gestion des Utilisateurs
            </h1>
        </div>
    </div>

    <!-- Statistiques par rôle -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold small">Total</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                        </div>
                        <i class="fas fa-users fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-danger shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-danger mb-1 fw-bold small">Admins</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['admin'] ?></h3>
                        </div>
                        <i class="fas fa-user-shield fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold small">Gestionnaires</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['gestionnaire'] ?></h3>
                        </div>
                        <i class="fas fa-user-tie fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold small">Exploitants</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['exploitant'] ?></h3>
                        </div>
                        <i class="fas fa-building fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold small">Propriétaires</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['proprietaire'] ?></h3>
                        </div>
                        <i class="fas fa-home fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-12 col-sm-6 col-lg-2">
            <div class="card border-left-secondary shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-secondary mb-1 fw-bold small">Résidents</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['resident'] ?></h3>
                        </div>
                        <i class="fas fa-user fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Table des utilisateurs -->
    <div class="card shadow">
        <div class="card-header bg-white border-bottom">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Liste des Utilisateurs
                        <span class="badge bg-primary ms-2"><?= $stats['actifs'] ?> actifs</span>
                    </h5>
                </div>
                <div class="col-12 col-md-auto ms-auto">
                    <div class="btn-stack-mobile">
                        <a href="<?= BASE_URL ?>/admin/users/create" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i>
                            <span class="d-none d-sm-inline">Nouvel </span>Utilisateur
                        </a>
                        <button class="btn btn-secondary" onclick="window.print()">
                            <i class="fas fa-print me-1"></i>
                            <span class="d-none d-sm-inline">Imprimer</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Filtres -->
            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <label for="filterRole" class="form-label small">Rôle</label>
                    <select id="filterRole" class="form-select form-select-sm">
                        <option value="">Tous les rôles</option>
                        <option value="admin">Admin</option>
                        <option value="gestionnaire">Gestionnaire</option>
                        <option value="exploitant">Exploitant</option>
                        <option value="proprietaire">Propriétaire</option>
                        <option value="resident">Résident</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="filterStatus" class="form-label small">Statut</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="1">Actif</option>
                        <option value="0">Inactif</option>
                    </select>
                </div>
                
                <div class="col-12 col-md-6">
                    <label for="searchUser" class="form-label small">Recherche</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" id="searchUser" class="form-control" placeholder="Nom, email, username...">
                    </div>
                </div>
            </div>
            
            <!-- Table responsive -->
            <div class="table-container-mobile">
                <table id="usersTable" class="table table-hover table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="sortable" data-column="id" data-type="number" style="width: 50px;">#</th>
                            <th class="sortable" data-column="nom">Nom complet</th>
                            <th class="sortable" data-column="email">Email</th>
                            <th class="sortable" data-column="username">Username</th>
                            <th class="sortable text-center" data-column="role">Rôle</th>
                            <th class="sortable text-center" data-column="statut">Statut</th>
                            <th class="sortable text-center" data-column="last_login">Dernière connexion</th>
                            <th class="text-end" style="width: 150px;" data-no-sort>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td data-sort="<?= $user['id'] ?>"><?= htmlspecialchars($user['id']) ?></td>
                                    <td data-sort="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-sort="<?= htmlspecialchars($user['email']) ?>">
                                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none">
                                            <i class="fas fa-envelope me-1"></i>
                                            <?= htmlspecialchars($user['email']) ?>
                                        </a>
                                    </td>
                                    <td data-sort="<?= htmlspecialchars($user['username']) ?>">
                                        <code><?= htmlspecialchars($user['username']) ?></code>
                                    </td>
                                    <td class="text-center" data-sort="<?= htmlspecialchars($user['role']) ?>">
                                        <?php
                                        $roleColors = [
                                            'admin' => 'danger',
                                            'gestionnaire' => 'info',
                                            'exploitant' => 'warning',
                                            'proprietaire' => 'success',
                                            'resident' => 'secondary'
                                        ];
                                        $roleIcons = [
                                            'admin' => 'user-shield',
                                            'gestionnaire' => 'user-tie',
                                            'exploitant' => 'building',
                                            'proprietaire' => 'home',
                                            'resident' => 'user'
                                        ];
                                        $color = $roleColors[$user['role']] ?? 'secondary';
                                        $icon = $roleIcons[$user['role']] ?? 'user';
                                        ?>
                                        <span class="badge bg-<?= $color ?>">
                                            <i class="fas fa-<?= $icon ?> me-1"></i>
                                            <?= ucfirst(htmlspecialchars($user['role'])) ?>
                                        </span>
                                    </td>
                                    <td class="text-center" data-sort="<?= $user['actif'] ? '1' : '0' ?>" data-filter="<?= $user['actif'] ? '1' : '0' ?>">
                                        <?php if ($user['actif']): ?>
                                            <span class="badge bg-success">
                                                <i class="fas fa-check-circle me-1"></i>1
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-times-circle me-1"></i>0
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small text-muted" data-sort="<?= !empty($user['last_login']) ? strtotime($user['last_login']) : 0 ?>">
                                        <?php if (!empty($user['last_login'])): ?>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <em>Jamais connecté</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= BASE_URL ?>/admin/users/edit/<?= $user['id'] ?>" 
                                               class="btn btn-outline-primary" 
                                               title="Modifier"
                                               data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <?php if ($user['role'] === 'resident'): ?>
                                                <?php if (!empty($user['resident_id'])): ?>
                                                    <a href="<?= BASE_URL ?>/resident/show/<?= $user['resident_id'] ?>" 
                                                       class="btn btn-outline-success" 
                                                       title="Voir le profil senior"
                                                       data-bs-toggle="tooltip">
                                                        <i class="fas fa-user-check"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>/resident/create?user_id=<?= $user['id'] ?>" 
                                                       class="btn btn-outline-warning" 
                                                       title="Créer le profil senior"
                                                       data-bs-toggle="tooltip">
                                                        <i class="fas fa-user-plus"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button" 
                                                        class="btn btn-outline-warning" 
                                                        title="<?= $user['actif'] ? 'Désactiver' : 'Activer' ?>"
                                                        data-bs-toggle="tooltip"
                                                        onclick="toggleUserStatus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', <?= $user['actif'] ?>)">
                                                    <i class="fas fa-<?= $user['actif'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" 
                                                        class="btn btn-outline-secondary" 
                                                        disabled
                                                        title="Vous ne pouvez pas modifier votre propre compte"
                                                        data-bs-toggle="tooltip">
                                                    <i class="fas fa-lock"></i>
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

            <!-- Pagination info -->
            <div class="d-flex justify-content-between align-items-center mt-3">
                <div class="text-muted" id="tableInfo">
                    Affichage de <span id="startEntry">1</span> à <span id="endEntry"><?= min(10, count($users)) ?></span> 
                    sur <span id="totalEntries"><?= count($users) ?></span> utilisateurs
                </div>
                <nav>
                    <ul class="pagination mb-0" id="pagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmation pour basculer le statut -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="toggleStatusMessage"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="toggleStatusForm" method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?= Security::getToken() ?>">
                    <button type="submit" class="btn btn-warning">Confirmer</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="<?= BASE_URL ?>/assets/js/datatable.js"></script>
<script src="<?= BASE_URL ?>/assets/js/datatable-pagination.js"></script>
<script>
// Initialiser les tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Initialiser le DataTable avec tri, filtres et pagination
    const usersTable = new DataTableWithPagination('usersTable', {
        sortable: true,
        excludeColumns: [7], // Actions non triables
        rowsPerPage: 10,
        searchInputId: 'searchUser',
        filters: [
            { id: 'filterRole', column: 4 },      // Colonne Rôle
            { id: 'filterStatus', column: 5 }     // Colonne Statut
        ],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
    
    console.log('DataTable initialisé pour le tableau des utilisateurs');
});

// Basculer le statut actif/inactif
function toggleUserStatus(userId, username, currentStatus) {
    const action = currentStatus ? 'désactiver' : 'activer';
    const message = `Voulez-vous vraiment ${action} l'utilisateur <strong>${username}</strong> ?`;
    
    document.getElementById('toggleStatusMessage').innerHTML = message;
    document.getElementById('toggleStatusForm').action = '<?= BASE_URL ?>/admin/users/toggle/' + userId;
    
    const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    modal.show();
}
</script>
