<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur d'authentification
 * ====================================================================
 */

class AuthController extends Controller {
    
    private $userModel;
    
    public function __construct() {
        $this->userModel = $this->model('User');
    }
    
    /**
     * Page de connexion
     */
    public function login() {
        // Si déjà connecté, rediriger vers l'accueil
        if ($this->isLoggedIn()) {
            $this->redirect('home');
        }
        
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Vérifier le token CSRF
            $this->verifyCsrf();
            
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            
            // Validation
            if (empty($username) || empty($password)) {
                $this->setFlash('error', 'Veuillez remplir tous les champs.');
            } else {
                // Tentative de connexion
                $user = $this->userModel->findByUsername($username);
                
                if ($user && password_verify($password, $user->password_hash)) {
                    // Vérifier que le compte est actif
                    if (!$user->actif) {
                        Logger::logFailedLogin($username, 'Account disabled');
                        $this->setFlash('error', 'Votre compte est désactivé.');
                    } else {
                        // Connexion réussie
                        $_SESSION['user_id'] = $user->id;
                        $_SESSION['user_role'] = $user->role;
                        $_SESSION['user_username'] = $user->username;
                        $_SESSION['user_prenom'] = $user->prenom;
                        $_SESSION['user_nom'] = $user->nom;
                        
                        // Initialiser le timestamp de dernière activité
                        $_SESSION['last_activity'] = time();
                        
                        // Logger la connexion réussie
                        Logger::logSuccessfulLogin($user->id, $username, $user->role);
                        
                        // Régénérer l'ID de session (sécurité)
                        Security::regenerateSession();
                        
                        // Mettre à jour la dernière connexion
                        $this->userModel->updateLastLogin($user->id);
                        
                        $this->setFlash('success', 'Bienvenue ' . ($user->prenom ?? $user->username) . ' !');
                        $this->redirect('');
                    }
                } else {
                    // Logger la tentative de connexion échouée
                    Logger::logFailedLogin($username, 'Invalid credentials');
                    $this->setFlash('error', 'Identifiants incorrects.');
                }
            }
        }
        
        // Afficher la vue
        $data = [
            'title' => 'Connexion',
            'flash' => $this->getFlash()
        ];
        
        $this->view('auth/login', $data);
    }
    
    /**
     * Déconnexion
     */
    public function logout() {
        // Logger la déconnexion (avant de détruire la session)
        if (isset($_SESSION['user_id'])) {
            Logger::logSensitiveAction('MANUAL_LOGOUT', [
                'user_id' => $_SESSION['user_id'],
                'username' => $_SESSION['user_username'] ?? null
            ]);
        }
        
        // Détruire la session
        session_unset();
        session_destroy();
        
        // Redémarrer une nouvelle session pour le message flash
        session_start();
        $this->setFlash('success', 'Vous avez été déconnecté avec succès.');
        
        $this->redirect('auth/login');
    }
    
    /**
     * Page de création d'utilisateur (admin uniquement)
     */
    public function register() {
        // Vérifier les permissions
        $this->requireRole('admin');
        
        // Traitement du formulaire
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'username' => trim($_POST['username'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'nom' => trim($_POST['nom'] ?? ''),
                'prenom' => trim($_POST['prenom'] ?? ''),
                'role' => $_POST['role'] ?? 'employe_residence',
                'telephone' => trim($_POST['telephone'] ?? ''),
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];
            
            // Validation
            $errors = [];
            
            if (empty($data['username'])) {
                $errors[] = 'Le nom d\'utilisateur est requis.';
            }
            if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Un email valide est requis.';
            }
            if (empty($data['password']) || strlen($data['password']) < 6) {
                $errors[] = 'Le mot de passe doit contenir au moins 6 caractères.';
            }
            if ($data['password'] !== $data['password_confirm']) {
                $errors[] = 'Les mots de passe ne correspondent pas.';
            }
            if (empty($data['nom']) || empty($data['prenom'])) {
                $errors[] = 'Le nom et le prénom sont requis.';
            }
            
            // Vérifier si l'utilisateur existe déjà
            if ($this->userModel->findByUsername($data['username'])) {
                $errors[] = 'Ce nom d\'utilisateur existe déjà.';
            }
            if ($this->userModel->findByEmail($data['email'])) {
                $errors[] = 'Cet email est déjà utilisé.';
            }
            
            if (empty($errors)) {
                // Créer l'utilisateur
                if ($this->userModel->create($data)) {
                    $this->setFlash('success', 'Utilisateur créé avec succès.');
                    $this->redirect('home');
                } else {
                    $this->setFlash('error', 'Une erreur est survenue lors de la création.');
                }
            } else {
                $this->setFlash('error', implode('<br>', $errors));
            }
        }
        
        // Afficher la vue
        $viewData = [
            'title' => 'Créer un utilisateur',
            'flash' => $this->getFlash()
        ];
        
        $this->view('auth/register', $viewData);
    }
}
