<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur d'Administration
 * ====================================================================
 */

class AdminController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $data = [
            'title' => 'Administration - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('admin/index', $data, true);
    }
    
    /**
     * Route intelligente pour la gestion des utilisateurs
     * Gère: /admin/users, /admin/users/create, /admin/users/edit/X, etc.
     */
    public function users($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Router les actions
        switch ($action) {
            case 'create':
                return $this->createUser();
            case 'store':
                return $this->storeUser();
            case 'edit':
                return $this->editUser($id);
            case 'update':
                return $this->updateUser($id);
            case 'toggle':
                return $this->toggleUser($id);
            case 'delete':
                return $this->deleteUser($id);
            default:
                return $this->listUsers();
        }
    }
    
    /**
     * Liste des utilisateurs
     */
    private function listUsers() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Récupérer tous les utilisateurs avec profil senior (via modèle)
        $userModel = $this->model('User');
        $users = $userModel->getAllWithResidentProfile();
        
        // Statistiques par rôle
        $stats = [
            'admin' => count(array_filter($users, fn($u) => $u['role'] === 'admin')),
            'gestionnaire' => count(array_filter($users, fn($u) => $u['role'] === 'gestionnaire')),
            'exploitant' => count(array_filter($users, fn($u) => $u['role'] === 'exploitant')),
            'proprietaire' => count(array_filter($users, fn($u) => $u['role'] === 'proprietaire')),
            'resident' => count(array_filter($users, fn($u) => $u['role'] === 'resident')),
            'total' => count($users),
            'actifs' => count(array_filter($users, fn($u) => $u['actif'] == 1))
        ];
        
        $data = [
            'title' => 'Gestion des Utilisateurs - ' . APP_NAME,
            'showNavbar' => true,
            'users' => $users,
            'stats' => $stats,
            'flash' => $this->getFlash()
        ];
        
        $this->view('admin/users/index', $data, true);
    }
    
    public function parametres() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $data = ['title' => 'Paramètres - ' . APP_NAME, 'showNavbar' => true];
        $this->view('admin/parametres', $data, true);
    }
    
    public function logs() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Récupérer les 100 derniers logs de sécurité
        $logs = Logger::getRecentSecurityLogs(100);
        
        // Calculer les statistiques
        $stats = [
            'unauthorized' => count(array_filter($logs, fn($l) => $l['type'] === 'UNAUTHORIZED_ACCESS')),
            'failed_logins' => count(array_filter($logs, fn($l) => $l['type'] === 'FAILED_LOGIN')),
            'csrf_violations' => count(array_filter($logs, fn($l) => $l['type'] === 'CSRF_VIOLATION')),
            'rate_limit' => count(array_filter($logs, fn($l) => $l['type'] === 'RATE_LIMIT_EXCEEDED'))
        ];
        
        // Récupérer les IPs bloquées
        $blockFile = '../logs/blocked_ips.json';
        $blockedIps = [];
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
            // Filtrer les IPs encore bloquées
            foreach ($blocked as $ip => $data) {
                if ($data['expires_at'] > time()) {
                    $blockedIps[$ip] = $data;
                }
            }
            $stats['blocked_ips'] = count($blockedIps);
        } else {
            $stats['blocked_ips'] = 0;
        }
        
        $data = [
            'title' => 'Logs de Sécurité - ' . APP_NAME,
            'showNavbar' => true,
            'logs' => $logs,
            'stats' => $stats,
            'blockedIps' => $blockedIps,
            'flash' => $this->getFlash()
        ];
        
        $this->view('admin/security_logs', $data, true);
    }
    
    public function backup() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        // TODO: Créer backup
        $this->setFlash('success', 'Backup créé avec succès');
        $this->redirect('admin/index');
    }
    
    /**
     * Liste des résidences seniors (refactorisée en utilisant le modèle Residence)
     */
    public function residences() {
        $this->requireAuth();
        $this->requireRole('admin');

        // Récupérer les filtres depuis la requête
        $filters = [
            'search' => $_GET['search'] ?? '',
            'ville' => $_GET['ville'] ?? '',
            'exploitant' => $_GET['exploitant'] ?? '',
            'taux_min' => $_GET['taux_min'] ?? ''
        ];

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 10;

        /** @var Residence $resModel */
        $resModel = $this->model('Residence');
        $result = $resModel->search($filters, $page, $perPage);

        $residences = $result['rows'] ?? [];
        $totalRecords = $result['total'] ?? 0;
        $totalPages = $totalRecords ? ceil($totalRecords / $perPage) : 0;

        $villes = $resModel->getCities();
        $exploitants = $resModel->getExploitantsList();

        $data = [
            'title' => 'Résidences Seniors - ' . APP_NAME,
            'residences' => $residences,
            'villes' => $villes,
            'exploitants' => $exploitants,
            'search' => $filters['search'],
            'ville' => $filters['ville'],
            'exploitant' => $filters['exploitant'],
            'taux_min' => $filters['taux_min'],
            'page' => $page,
            'perPage' => $perPage,
            'totalRecords' => $totalRecords,
            'totalPages' => $totalPages,
            'showNavbar' => true
        ];

        $this->view('residences/residences', $data, true);
    }
    
    /**
     * Export Excel des résidences
     */
    public function exportResidencesExcel() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Récupérer les filtres (mêmes que la liste)
        $filters = [
            'search' => $_GET['search'] ?? '',
            'ville' => $_GET['ville'] ?? '',
            'exploitant' => $_GET['exploitant'] ?? '',
            'taux_min' => $_GET['taux_min'] ?? ''
        ];
        
        /** @var Residence $resModel */
        $resModel = $this->model('Residence');
        
        // Récupérer TOUTES les résidences (sans pagination)
        $result = $resModel->search($filters, 1, 999999);
        $residences = $result['rows'] ?? [];
        
        // Nom du fichier avec date
        $filename = 'residences_' . date('Y-m-d_His') . '.csv';
        
        // Headers pour téléchargement CSV
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Ouvrir le flux de sortie
        $output = fopen('php://output', 'w');
        
        // BOM UTF-8 pour Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // En-têtes du CSV
        fputcsv($output, [
            'ID',
            'Nom',
            'Adresse',
            'Code Postal',
            'Ville',
            'Exploitant',
            'Total Lots',
            'Appartements',
            'Occupés',
            'Taux Occupation (%)',
            'Date Création'
        ], ';');
        
        // Données
        foreach ($residences as $residence) {
            fputcsv($output, [
                $residence['id'] ?? '',
                $residence['nom'] ?? '',
                $residence['adresse'] ?? '',
                $residence['code_postal'] ?? '',
                $residence['ville'] ?? '',
                $residence['exploitant_nom'] ?? 'Non défini',
                $residence['total_lots'] ?? 0,
                $residence['total_appartements'] ?? 0,
                $residence['appartements_occupes'] ?? 0,
                number_format($residence['taux_occupation_pct'] ?? 0, 2, ',', ' '),
                date('d/m/Y', strtotime($residence['created_at'] ?? 'now'))
            ], ';');
        }
        
        fclose($output);
        exit;
    }
    
    /**
     * Créer une nouvelle résidence
     */
    public function createResidence() {
        $this->requireAuth();
        $this->requireRole('admin');
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifier le token CSRF
            $this->verifyCsrf();
            
            // Récupérer les données du formulaire
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'adresse' => $_POST['adresse'] ?? '',
                'code_postal' => $_POST['code_postal'] ?? '',
                'ville' => $_POST['ville'] ?? '',
                'exploitant_id' => $_POST['exploitant_id'] ?? null,
                'description' => $_POST['description'] ?? '',
                'type_residence' => 'residence_seniors'
            ];
            
            // Validation
            $errors = [];
            if (empty($data['nom'])) $errors[] = "Le nom est requis";
            if (empty($data['adresse'])) $errors[] = "L'adresse est requise";
            if (empty($data['code_postal'])) $errors[] = "Le code postal est requis";
            if (empty($data['ville'])) $errors[] = "La ville est requise";
            
            if (empty($errors)) {
                $resModel = $this->model('Residence');
                $newId = $resModel->create($data);
                
                if ($newId) {
                    $this->setFlash('success', "Résidence créée avec succès");
                    $this->redirect('admin/residences');
                } else {
                    $this->setFlash('error', "Erreur lors de la création de la résidence");
                }
            } else {
                foreach ($errors as $error) {
                    $this->setFlash('error', $error);
                }
            }
        }
        
        // Récupérer les exploitants pour le select
        $resModel = $this->model('Residence');
        $exploitants = $resModel->getExploitantsList();
        
        $data = [
            'title' => 'Nouvelle Résidence - ' . APP_NAME,
            'exploitants' => $exploitants,
            'showNavbar' => true
        ];
        
        $this->view('residences/residence_create', $data, true);
    }
    
    /**
     * Voir les détails d'une résidence (refactorisé avec Residence model)
     */
    public function viewResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');
        
        $resModel = $this->model('Residence');
        $residence = $resModel->findWithExploitant($id);
        
        if (!$residence) {
            $this->setFlash('error', "Résidence non trouvée");
            $this->redirect('admin/residences');
            return;
        }
        
        // Récupérer les lots avec occupation et résident
        require_once '../app/models/Lot.php';
        $lotModel = new Lot();
        $lots = $lotModel->getByResidence($id);
        
        // Statistiques de la résidence
        $stats = $lotModel->getResidenceStats($id);
        
        $data = [
            'title' => $residence['nom'] . ' - ' . APP_NAME,
            'residence' => $residence,
            'lots' => $lots,
            'stats' => $stats,
            'showNavbar' => true
        ];
        
        $this->view('residences/residence_view', $data, true);
    }
    
    /**
     * Modifier une résidence (refactorisé avec Residence model)
     */
    public function editResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');
        
        $resModel = $this->model('Residence');
        $residence = $resModel->find($id);
        
        if (!$residence) {
            $this->setFlash('error', "Résidence non trouvée");
            $this->redirect('admin/residences');
            return;
        }
        
        // Convertir objet en array si nécessaire
        if (is_object($residence)) {
            $residence = (array)$residence;
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifier le token CSRF
            $this->verifyCsrf();
            
            $data = [
                'nom' => $_POST['nom'] ?? '',
                'adresse' => $_POST['adresse'] ?? '',
                'code_postal' => $_POST['code_postal'] ?? '',
                'ville' => $_POST['ville'] ?? '',
                'exploitant_id' => $_POST['exploitant_id'] ?? null,
                'description' => $_POST['description'] ?? ''
            ];
            
            $errors = [];
            if (empty($data['nom'])) $errors[] = "Le nom est requis";
            if (empty($data['adresse'])) $errors[] = "L'adresse est requise";
            if (empty($data['code_postal'])) $errors[] = "Le code postal est requis";
            if (empty($data['ville'])) $errors[] = "La ville est requise";
            
            if (empty($errors)) {
                if ($resModel->updateResidence($id, $data)) {
                    $this->setFlash('success', "Résidence modifiée avec succès");
                    $this->redirect('admin/residences');
                } else {
                    $this->setFlash('error', "Erreur lors de la modification");
                }
            } else {
                foreach ($errors as $error) {
                    $this->setFlash('error', $error);
                }
            }
        }
        
        // Récupérer les exploitants
        $exploitants = $resModel->getExploitantsList();
        
        $data = [
            'title' => 'Modifier ' . $residence['nom'] . ' - ' . APP_NAME,
            'residence' => $residence,
            'exploitants' => $exploitants,
            'showNavbar' => true
        ];
        
        $this->view('residences/residence_edit', $data, true);
    }
    
    /**
     * Supprimer une résidence (refactorisé avec Residence model)
     */
    public function deleteResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');
        
        // Vérifier si la résidence a des lots
        require_once '../app/models/Lot.php';
        $lotModel = new Lot();
        $lots = $lotModel->getByResidence($id);
        
        if (count($lots) > 0) {
            $this->setFlash('error', "Impossible de supprimer cette résidence car elle contient des lots");
            $this->redirect('admin/residences');
            return;
        }
        
        $resModel = $this->model('Residence');
        if ($resModel->deleteResidence($id)) {
            $this->setFlash('success', "Résidence supprimée avec succès");
        } else {
            $this->setFlash('error', "Erreur lors de la suppression");
        }
        
        $this->redirect('admin/residences');
    }
    
    /**
     * Carte interactive des résidences seniors Leaflet.js (refactorisé avec Residence model)
     */
    public function carteResidences() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $resModel = $this->model('Residence');
        $residences = $resModel->getAllForMap();
        
        // Statistiques globales
        $totalResidences = count($residences);
        $totalLots = array_sum(array_column($residences, 'nb_lots'));
        $totalOccupations = array_sum(array_column($residences, 'nb_occupations'));
        $tauxGlobal = $totalLots > 0 ? round(($totalOccupations / $totalLots) * 100, 2) : 0;
        
        $data = [
            'title' => 'Carte des Résidences - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'stats' => [
                'total' => $totalResidences,
                'lots' => $totalLots,
                'occupations' => $totalOccupations,
                'taux' => $tauxGlobal
            ],
            'flash' => $this->getFlash()
        ];
        
        $this->view('residences/carte_residences', $data, true);
    }
    
    /**
     * Création d'un nouvel utilisateur (formulaire)
     */
    /**
     * Formulaire de création d'un utilisateur
     */
    private function createUser() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $data = [
            'title' => 'Créer un utilisateur - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('admin/users/create', $data, true);
    }
    
    /**
     * Enregistrement d'un nouvel utilisateur
     */
    private function storeUser() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Validation CSRF
        $this->verifyCsrf();
        
        // Validation des champs
        $errors = [];
        
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($prenom)) $errors[] = "Le prénom est requis";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if (strlen($username) < 3) $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        if (empty($password)) $errors[] = "Le mot de passe est requis";
        if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas";
        if (!in_array($role, ['admin', 'gestionnaire', 'exploitant', 'proprietaire', 'resident'])) {
            $errors[] = "Rôle invalide";
        }
        
        // Vérifier unicité email/username avec modèle
        $userModel = $this->model('User');
        
        if ($userModel->emailExists($email)) {
            $errors[] = "Cet email est déjà utilisé";
        }
        
        if ($userModel->usernameExists($username)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé";
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            $this->redirect('admin/users/create');
        }
        
        // Insertion avec modèle
        try {
            $userId = $userModel->createUser([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'username' => $username,
                'password' => $password,
                'role' => $role,
                'actif' => $actif
            ]);
            
            if (!$userId) {
                throw new Exception("Erreur lors de la création");
            }
            
            // Log
            Logger::logSensitiveAction('USER_CREATED', [
                'username' => $username,
                'role' => $role,
                'created_by' => $_SESSION['user_id']
            ]);
            
            // Si c'est un résident, proposer de créer le profil senior
            if ($role === 'resident') {
                $this->setFlash('success', "Utilisateur créé avec succès. Vous pouvez maintenant créer son profil senior.");
                $_SESSION['new_resident_user_id'] = $userId; // Pour pré-remplir le formulaire
            } else {
                $this->setFlash('success', "Utilisateur créé avec succès");
            }
            $this->redirect('admin/users');
            
        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_CREATE_ERROR', [
                'error' => $e->getMessage(),
                'user_id' => $_SESSION['user_id']
            ]);
            $this->setFlash('error', "Erreur lors de la création de l'utilisateur");
            $this->redirect('admin/users/create');
        }
    }
    
    /**
     * Édition d'un utilisateur (formulaire)
     */
    private function editUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $userModel = $this->model('User');
        $userData = $userModel->find($id);
        $user = $userData ? (array)$userData : null;
        
        if (!$user) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }
        
        // Détecter si un profil senior existe
        $residentProfile = null;
        if ($user['role'] === 'resident') {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM residents_seniors WHERE user_id = ?");
            $stmt->execute([$id]);
            $residentProfile = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $data = [
            'title' => 'Modifier un utilisateur - ' . APP_NAME,
            'showNavbar' => true,
            'user' => $user,
            'residentProfile' => $residentProfile,
            'flash' => $this->getFlash()
        ];
        
        $this->view('admin/users/edit', $data, true);
    }
    
    /**
     * Mise à jour d'un utilisateur
     */
    private function updateUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Validation CSRF
        $this->verifyCsrf();
        
        $userModel = $this->model('User');
        
        // Vérifier que l'utilisateur existe
        $existingUserData = $userModel->find($id);
        $existingUser = $existingUserData ? (array)$existingUserData : null;
        
        if (!$existingUser) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }
        
        // Validation des champs
        $errors = [];
        
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        
        // ⚠️ PROTECTION CONTRE CHANGEMENT DE RÔLE RESIDENT
        $oldRole = $existingUser['role'];
        
        // Vérifier si profil senior existe
        $hasResidentProfile = false;
        if ($oldRole === 'resident') {
            $db = Database::getInstance()->getConnection();
            $stmt = $db->prepare("SELECT id FROM residents_seniors WHERE user_id = ?");
            $stmt->execute([$id]);
            $hasResidentProfile = $stmt->fetch() !== false;
        }
        
        // RÈGLE 1 : Un non-resident ne peut PAS devenir resident
        if ($oldRole !== 'resident' && $role === 'resident') {
            $this->setFlash('error', "Il n'est pas possible de changer le statut de l'utilisateur vers resident_senior. Veuillez créer un nouveau utilisateur avec le statut de resident_senior.", 12000);
            $this->redirect('admin/users/edit/' . $id);
            return;
        }
        
        // RÈGLE 2 : Un resident avec profil ne peut PAS devenir autre chose
        if ($oldRole === 'resident' && $role !== 'resident' && $hasResidentProfile) {
            $this->setFlash('error', "Il n'est pas possible de changer le statut de l'utilisateur autre que resident_senior. Veuillez créer un nouveau utilisateur avec un autre statut différent de resident_senior.", 12000);
            $this->redirect('admin/users/edit/' . $id);
            return;
        }
        
        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($prenom)) $errors[] = "Le prénom est requis";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if (strlen($username) < 3) $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        
        // Mot de passe optionnel en édition
        if (!empty($password)) {
            if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
            if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas";
        }
        
        if (!in_array($role, ['admin', 'gestionnaire', 'exploitant', 'proprietaire', 'resident'])) {
            $errors[] = "Rôle invalide";
        }
        
        // Vérifier unicité email/username (sauf pour l'utilisateur actuel)
        if ($userModel->emailExists($email, $id)) {
            $errors[] = "Cet email est déjà utilisé";
        }
        
        if ($userModel->usernameExists($username, $id)) {
            $errors[] = "Ce nom d'utilisateur est déjà utilisé";
        }
        
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->setFlash('error', $error);
            }
            $this->redirect('admin/users/edit/' . $id);
        }
        
        // Mise à jour avec modèle
        try {
            $updateData = [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'username' => $username,
                'role' => $role,
                'actif' => $actif
            ];
            
            if (!empty($password)) {
                $updateData['password'] = $password;
            }
            
            $success = $userModel->updateUser($id, $updateData, !empty($password));
            
            if (!$success) {
                throw new Exception("Erreur lors de la mise à jour");
            }
            
            // Log
            Logger::logSensitiveAction('USER_UPDATED', [
                'username' => $username,
                'user_id' => $id,
                'updated_by' => $_SESSION['user_id']
            ]);
            
            $this->setFlash('success', "Utilisateur mis à jour avec succès");
            $this->redirect('admin/users');
            
        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_UPDATE_ERROR', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'updated_by' => $_SESSION['user_id']
            ]);
            $this->setFlash('error', "Erreur lors de la mise à jour de l'utilisateur");
            $this->redirect('admin/users/edit/' . $id);
        }
    }
    
    /**
     * Suppression (soft delete) d'un utilisateur
     */
    private function deleteUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Validation CSRF
        $this->verifyCsrf();
        
        // Empêcher la suppression de soi-même
        if ($id == $_SESSION['user_id']) {
            $this->setFlash('error', "Vous ne pouvez pas supprimer votre propre compte");
            $this->redirect('admin/users');
        }
        
        $userModel = $this->model('User');
        
        // Vérifier que l'utilisateur existe
        $userData = $userModel->find($id);
        $user = $userData ? (array)$userData : null;
        
        if (!$user) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }
        
        try {
            // Soft delete: désactiver au lieu de supprimer
            $success = $userModel->softDelete($id);
            
            if (!$success) {
                throw new Exception("Erreur lors de la désactivation");
            }
            
            // Log
            Logger::logSensitiveAction('USER_DELETED', [
                'username' => $user['username'],
                'user_id' => $id,
                'deleted_by' => $_SESSION['user_id']
            ]);
            
            $this->setFlash('success', "Utilisateur désactivé avec succès");
            
        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_DELETE_ERROR', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'deleted_by' => $_SESSION['user_id']
            ]);
            $this->setFlash('error', "Erreur lors de la suppression de l'utilisateur");
        }
        
        $this->redirect('admin/users');
    }
    
    /**
     * Basculer le statut actif/inactif
     */
    /**
     * Basculer le statut actif/inactif d'un utilisateur
     */
    private function toggleUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // Validation CSRF
        $this->verifyCsrf();
        
        // Empêcher la désactivation de soi-même
        if ($id == $_SESSION['user_id']) {
            $this->setFlash('error', "Vous ne pouvez pas désactiver votre propre compte");
            $this->redirect('admin/users');
        }
        
        $userModel = $this->model('User');
        
        try {
            $userData = $userModel->find($id);
            $user = $userData ? (array)$userData : null;
            
            if (!$user) {
                $this->setFlash('error', "Utilisateur introuvable");
                $this->redirect('admin/users');
            }
            
            $success = $userModel->toggleStatus($id);
            
            if (!$success) {
                throw new Exception("Erreur lors du changement de statut");
            }
            
            $newStatus = $user['actif'] ? 0 : 1;
            $statusText = $newStatus ? 'activé' : 'désactivé';
            
            // Log
            Logger::logSensitiveAction('USER_TOGGLE_ACTIVE', [
                'username' => $user['username'],
                'user_id' => $id,
                'new_status' => $statusText,
                'toggled_by' => $_SESSION['user_id']
            ]);
            
            $this->setFlash('success', "Utilisateur $statusText avec succès");
            
        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_TOGGLE_ERROR', [
                'error' => $e->getMessage(),
                'user_id' => $id,
                'toggled_by' => $_SESSION['user_id']
            ]);
            $this->setFlash('error', "Erreur lors du changement de statut");
        }
        
        $this->redirect('admin/users');
    }
}
