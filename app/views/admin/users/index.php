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

    <!-- Statistiques totaux -->
    <div class="row g-3 mb-3">
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-left-primary shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-primary mb-1 fw-bold small">Total utilisateurs</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['total'] ?></h3>
                        </div>
                        <i class="fas fa-users fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-left-success shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-success mb-1 fw-bold small">Actifs</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['actifs'] ?></h3>
                        </div>
                        <i class="fas fa-check-circle fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-left-info shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-info mb-1 fw-bold small">Résidents permanents</h6>
                            <h3 class="mb-0 text-gray-800"><?= $stats['locataire_permanent'] ?? 0 ?></h3>
                        </div>
                        <i class="fas fa-user-circle fa-3x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3">
            <div class="card border-left-warning shadow h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="text-warning mb-1 fw-bold small">Staff</h6>
                            <?php
                            $staffRoles = ['directeur_residence','employe_residence','technicien','jardinier_manager',
                                           'jardinier_employe','entretien_manager','menage_interieur','menage_exterieur',
                                           'restauration_manager','restauration_serveur','restauration_cuisine'];
                            $staffCount = array_sum(array_map(fn($r) => $stats[$r] ?? 0, $staffRoles));
                            ?>
                            <h3 class="mb-0 text-gray-800"><?= $staffCount ?></h3>
                        </div>
                        <i class="fas fa-id-badge fa-3x text-gray-300"></i>
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
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r['nom_affichage']) ?>">
                                <?= htmlspecialchars($r['nom_affichage']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-12 col-md-3">
                    <label for="filterStatus" class="form-label small">Statut</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">Tous les statuts</option>
                        <option value="Actif">Actif</option>
                        <option value="Inactif">Inactif</option>
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
                            <th class="sortable" data-column="id" data-type="number" style="width: 50px;">ID</th>
                            <th class="sortable" data-column="nom">Nom complet</th>
                            <th class="sortable" data-column="email">Email / Username</th>
                            <th class="sortable text-center" data-column="role">Rôle</th>
                            <th class="sortable text-center" data-column="statut">Statut</th>
                            <th data-no-sort>Mot de passe</th>
                            <th class="sortable text-center" data-column="last_login">Dernière connexion</th>
                            <th class="text-end" style="width: 120px;" data-no-sort>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Indexer les rôles par slug pour affichage rapide
                        $rolesMap = [];
                        foreach ($roles as $r) { $rolesMap[$r['slug']] = $r; }
                        ?>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Aucun utilisateur trouvé
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                                <?php
                                $roleInfo = $rolesMap[$user['role']] ?? null;
                                $roleBadgeStyle = $roleInfo ? 'background-color:' . htmlspecialchars($roleInfo['couleur']) . ';' : '';
                                $roleIcon = $roleInfo ? htmlspecialchars($roleInfo['icone']) : 'fa-user';
                                $roleLabel = $roleInfo ? htmlspecialchars($roleInfo['nom_affichage']) : htmlspecialchars($user['role']);
                                ?>
                                <tr>
                                    <td data-sort="<?= $user['id'] ?>"><?= $user['id'] ?></td>
                                    <td data-sort="<?= htmlspecialchars($user['nom'] . ' ' . $user['prenom']) ?>">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle avatar-sm me-2">
                                                <?= strtoupper(substr($user['prenom'] ?? '?', 0, 1) . substr($user['nom'] ?? '?', 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                                <br><small class="text-muted"><code><?= htmlspecialchars($user['username']) ?></code></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td data-sort="<?= htmlspecialchars($user['email']) ?>">
                                        <a href="mailto:<?= htmlspecialchars($user['email']) ?>" class="text-decoration-none small">
                                            <?= htmlspecialchars($user['email']) ?>
                                        </a>
                                    </td>
                                    <td class="text-center" data-sort="<?= htmlspecialchars($user['role']) ?>">
                                        <span class="badge" style="<?= $roleBadgeStyle ?>">
                                            <i class="fas <?= $roleIcon ?> me-1"></i><?= $roleLabel ?>
                                        </span>
                                    </td>
                                    <td class="text-center" data-sort="<?= $user['actif'] ? '1' : '0' ?>" data-filter="<?= $user['actif'] ? '1' : '0' ?>">
                                        <?php if ($user['actif']): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Actif</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><i class="fas fa-times-circle me-1"></i>Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small">
                                        <?php if (!empty($user['password_plain'])): ?>
                                            <span class="font-monospace text-warning"><?= htmlspecialchars($user['password_plain']) ?></span>
                                        <?php else: ?>
                                            <em class="text-muted">—</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center small text-muted" data-sort="<?= !empty($user['last_login']) ? strtotime($user['last_login']) : 0 ?>">
                                        <?php if (!empty($user['last_login'])): ?>
                                            <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($user['last_login'])) ?>
                                        <?php else: ?>
                                            <em>Jamais</em>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <a href="<?= BASE_URL ?>/admin/users/show/<?= $user['id'] ?>"
                                               class="btn btn-outline-info" title="Voir détails" data-bs-toggle="tooltip">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="<?= BASE_URL ?>/admin/users/edit/<?= $user['id'] ?>"
                                               class="btn btn-outline-primary" title="Modifier" data-bs-toggle="tooltip">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['role'] === 'locataire_permanent'): ?>
                                                <?php if (!empty($user['resident_id'])): ?>
                                                    <a href="<?= BASE_URL ?>/resident/show/<?= $user['resident_id'] ?>"
                                                       class="btn btn-outline-success" title="Voir profil résident" data-bs-toggle="tooltip">
                                                        <i class="fas fa-user-check"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?= BASE_URL ?>/resident/create?user_id=<?= $user['id'] ?>"
                                                       class="btn btn-outline-warning" title="Créer profil résident" data-bs-toggle="tooltip">
                                                        <i class="fas fa-user-plus"></i>
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="button"
                                                        class="btn btn-outline-warning"
                                                        title="<?= $user['actif'] ? 'Désactiver' : 'Activer' ?>"
                                                        data-bs-toggle="tooltip"
                                                        onclick="toggleUserStatus(<?= $user['id'] ?>, '<?= htmlspecialchars($user['username']) ?>', '<?= htmlspecialchars($user['role']) ?>', <?= $user['actif'] ?>)">
                                                    <i class="fas fa-<?= $user['actif'] ? 'ban' : 'check' ?>"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="button" class="btn btn-outline-secondary" disabled
                                                        title="Votre propre compte" data-bs-toggle="tooltip">
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
            { id: 'filterRole', column: 3 },      // Colonne Rôle
            { id: 'filterStatus', column: 4 }     // Colonne Statut
        ],
        paginationId: 'pagination',
        infoId: 'tableInfo'
    });
    
    console.log('DataTable initialisé pour le tableau des utilisateurs');
});

// Basculer le statut actif/inactif
function toggleUserStatus(userId, username, role, currentStatus) {
    const action = currentStatus ? 'désactiver' : 'activer';
    let message = `Voulez-vous vraiment ${action} l'utilisateur <strong>${username}</strong> ?`;

    if (role === 'resident') {
        message = `Voulez-vous vraiment ${action} le résident <strong>${username}</strong> ?<br><small class="text-muted">Cette action synchronise le statut du compte utilisateur et de la fiche résident.</small>`;
    }
    
    document.getElementById('toggleStatusMessage').innerHTML = message;
    document.getElementById('toggleStatusForm').action = '<?= BASE_URL ?>/admin/users/toggle/' + userId;
    
    const modal = new bootstrap.Modal(document.getElementById('toggleStatusModal'));
    modal.show();
}
</script>
