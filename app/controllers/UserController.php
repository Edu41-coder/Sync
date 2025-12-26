<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Profils Utilisateurs
 * ====================================================================
 */

class UserController extends Controller {
    
    public function profile() {
        $this->requireAuth();
        
        $userModel = $this->model('User');
        $user = $userModel->find($this->getUserId());
        
        // Récupérer les statistiques depuis la base de données
        $stats = $this->getProfileStats();
        
        $data = [
            'title' => 'Mon Profil - ' . APP_NAME,
            'showNavbar' => true,
            'user' => $user,
            'flash' => $this->getFlash(),
            'stats' => $stats
        ];
        
        $this->view('users/profile', $data, true);
    }
    
    /**
     * Récupérer les statistiques du profil utilisateur
     */
    private function getProfileStats() {
        $db = $this->model('User')->getDb();
        
        $countCoproprietes = 0;
        $countDocuments = 0;
        $countMessages = 0;
        
        try {
            // Compter les copropriétés
            $stmt = $db->query("SELECT COUNT(*) as count FROM coproprietes");
            $countCoproprietes = $stmt->fetch(PDO::FETCH_OBJ)->count ?? 0;
        } catch (PDOException $e) {
            // Table n'existe pas encore
        }
        
        try {
            // Compter les documents
            $stmt = $db->query("SELECT COUNT(*) as count FROM documents");
            $countDocuments = $stmt->fetch(PDO::FETCH_OBJ)->count ?? 0;
        } catch (PDOException $e) {
            // Table n'existe pas encore
        }
        
        try {
            // Compter les messages non lus pour cet utilisateur
            $stmt = $db->prepare("SELECT COUNT(*) as count FROM messages WHERE destinataire_id = ? AND lu = 0");
            $stmt->execute([$this->getUserId()]);
            $countMessages = $stmt->fetch(PDO::FETCH_OBJ)->count ?? 0;
        } catch (PDOException $e) {
            // Table n'existe pas encore
        }
        
        return [
            'coproprietes' => $countCoproprietes,
            'documents' => $countDocuments,
            'messages' => $countMessages
        ];
    }
    
    public function settings() {
        $this->requireAuth();
        
        $userModel = $this->model('User');
        $user = $userModel->find($this->getUserId());

        // Charger préférences utilisateur depuis la table parametres (refactorisé)
        require_once '../app/models/Parametre.php';
        $parametreModel = new Parametre();
        $key = 'user_' . $this->getUserId() . '_prefs';
        $value = $parametreModel->getByKey($key);
        $prefs = $value ? json_decode($value, true) ?? [] : [];

        $data = [
            'title' => 'Paramètres - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash(),
            'user' => $user,
            'prefs' => $prefs
        ];

        $this->view('users/settings', $data, true);
    }

    /**
     * Sauvegarder les paramètres utilisateur (section spécifique)
     */
    public function updateSettings() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/settings');
            return;
        }

        // Vérifier le token CSRF
        $this->verifyCsrf();

        $userId = $this->getUserId();
        $section = $_POST['section'] ?? 'general';

        // Lire préférences existantes (refactorisé)
        require_once '../app/models/Parametre.php';
        $parametreModel = new Parametre();
        $key = 'user_' . $userId . '_prefs';
        $value = $parametreModel->getByKey($key);
        $existing = $value ? json_decode($value, true) ?? [] : [];

        // Récupérer les champs soumis selon la section
        $prefs = $existing;
        if ($section === 'general') {
            $prefs['language'] = $_POST['language'] ?? ($prefs['language'] ?? 'fr');
            $prefs['timezone'] = $_POST['timezone'] ?? ($prefs['timezone'] ?? 'Europe/Paris');
            $prefs['date_format'] = $_POST['date_format'] ?? ($prefs['date_format'] ?? 'dd/mm/yyyy');
            $prefs['time_format'] = $_POST['time_format'] ?? ($prefs['time_format'] ?? '24h');
        } elseif ($section === 'notifications') {
            // Email notifications
            $prefs['email_new_message'] = isset($_POST['emailNewMessage']) ? 1 : 0;
            $prefs['email_appel_fonds'] = isset($_POST['emailAppelFonds']) ? 1 : 0;
            $prefs['email_travaux'] = isset($_POST['emailTravaux']) ? 1 : 0;
            $prefs['email_assemblee'] = isset($_POST['emailAssemblee']) ? 1 : 0;
            // App notifications
            $prefs['app_notifications'] = isset($_POST['appNotifications']) ? 1 : 0;
            $prefs['app_sounds'] = isset($_POST['appSounds']) ? 1 : 0;
        } elseif ($section === 'appearance') {
            // Theme et affichage
            $prefs['theme'] = $_POST['theme'] ?? ($prefs['theme'] ?? 'light');
            $prefs['density'] = $_POST['density'] ?? ($prefs['density'] ?? 'comfortable');
            $prefs['show_animations'] = isset($_POST['showAnimations']) ? 1 : 0;
        } elseif ($section === 'privacy') {
            // Confidentialité et sécurité
            $prefs['profile_visibility'] = $_POST['profileVisibility'] ?? ($prefs['profile_visibility'] ?? 'public');
            $prefs['two_factor'] = isset($_POST['twoFactor']) ? 1 : 0;
            $prefs['login_notifications'] = isset($_POST['loginNotifications']) ? 1 : 0;
        }

        // Encoder et upsert dans parametres (refactorisé)
        $valueToSave = json_encode($prefs, JSON_UNESCAPED_UNICODE);
        
        if ($parametreModel->upsert($key, $valueToSave, 'Préférences utilisateur', 'json')) {
            $this->setFlash('success', 'Paramètres sauvegardés avec succès');
        } else {
            $this->setFlash('error', 'Erreur lors de la sauvegarde des paramètres');
        }

        // Rediriger vers la section soumise
        $anchor = $section === 'general' ? '#general' : ($section === 'notifications' ? '#notifications' : '');
        $this->redirect('user/settings' . ($anchor ? '/' . ltrim($anchor, '#') : ''));
    }
    
    public function updateProfile() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Vérifier le token CSRF
            $this->verifyCsrf();
            
            $userModel = $this->model('User');
            
            // Validation des données
            $data = [
                'prenom' => trim($_POST['prenom'] ?? ''),
                'nom' => trim($_POST['nom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telephone' => trim($_POST['telephone'] ?? '')
            ];
            
            // Vérifier que les champs obligatoires sont remplis
            if (empty($data['prenom']) || empty($data['nom']) || empty($data['email'])) {
                $this->setFlash('error', 'Tous les champs obligatoires doivent être remplis');
                $this->redirect('user/profile');
                return;
            }
            
            // Vérifier le format email
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'Format d\'email invalide');
                $this->redirect('user/profile');
                return;
            }
            
            // Mettre à jour le profil
            if ($userModel->update($this->getUserId(), $data)) {
                $this->setFlash('success', 'Profil mis à jour avec succès');
            } else {
                $this->setFlash('error', 'Erreur lors de la mise à jour du profil');
            }
            
            $this->redirect('user/profile');
        }
    }
    
    public function changePassword() {
        $this->requireAuth();
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // Vérifier le token CSRF
            $this->verifyCsrf();
            
            $userModel = $this->model('User');
            $user = $userModel->find($this->getUserId());
            
            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Vérifier que tous les champs sont remplis
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->setFlash('error', 'Tous les champs sont obligatoires');
                $this->redirect('user/profile');
                return;
            }
            
            // Vérifier que le mot de passe actuel est correct
            if (!password_verify($currentPassword, $user->password_hash)) {
                $this->setFlash('error', 'Le mot de passe actuel est incorrect');
                $this->redirect('user/profile');
                return;
            }
            
            // Vérifier que les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $this->setFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                $this->redirect('user/profile');
                return;
            }
            
            // Vérifier la longueur du mot de passe
            if (strlen($newPassword) < 6) {
                $this->setFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                $this->redirect('user/profile');
                return;
            }
            
            // Mettre à jour le mot de passe
            if ($userModel->updatePassword($this->getUserId(), $newPassword)) {
                $this->setFlash('success', 'Mot de passe modifié avec succès');
            } else {
                $this->setFlash('error', 'Erreur lors de la modification du mot de passe');
            }
            
            $this->redirect('user/profile');
        }
    }
}
