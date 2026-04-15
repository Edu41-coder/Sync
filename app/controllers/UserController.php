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

        $this->view('users/profile', [
            'title' => 'Mon Profil - ' . APP_NAME,
            'showNavbar' => true,
            'user' => $user,
            'flash' => $this->getFlash(),
            'stats' => $userModel->getProfileStats($this->getUserId())
        ], true);
    }

    public function settings() {
        $this->requireAuth();

        $userModel = $this->model('User');
        $user = $userModel->find($this->getUserId());

        $parametreModel = $this->model('Parametre');
        $key = 'user_' . $this->getUserId() . '_prefs';
        $value = $parametreModel->getByKey($key);
        $prefs = $value ? json_decode($value, true) ?? [] : [];

        $this->view('users/settings', [
            'title' => 'Paramètres - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash(),
            'user' => $user,
            'prefs' => $prefs
        ], true);
    }

    public function updateSettings() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/settings');
            return;
        }

        $this->verifyCsrf();

        $userId = $this->getUserId();
        $section = $_POST['section'] ?? 'general';

        $parametreModel = $this->model('Parametre');
        $key = 'user_' . $userId . '_prefs';
        $value = $parametreModel->getByKey($key);
        $existing = $value ? json_decode($value, true) ?? [] : [];

        $prefs = $existing;
        if ($section === 'general') {
            $prefs['language'] = $_POST['language'] ?? ($prefs['language'] ?? 'fr');
            $prefs['timezone'] = $_POST['timezone'] ?? ($prefs['timezone'] ?? 'Europe/Paris');
            $prefs['date_format'] = $_POST['date_format'] ?? ($prefs['date_format'] ?? 'dd/mm/yyyy');
            $prefs['time_format'] = $_POST['time_format'] ?? ($prefs['time_format'] ?? '24h');
        } elseif ($section === 'notifications') {
            $prefs['email_new_message'] = isset($_POST['emailNewMessage']) ? 1 : 0;
            $prefs['email_appel_fonds'] = isset($_POST['emailAppelFonds']) ? 1 : 0;
            $prefs['email_travaux'] = isset($_POST['emailTravaux']) ? 1 : 0;
            $prefs['email_assemblee'] = isset($_POST['emailAssemblee']) ? 1 : 0;
            $prefs['app_notifications'] = isset($_POST['appNotifications']) ? 1 : 0;
            $prefs['app_sounds'] = isset($_POST['appSounds']) ? 1 : 0;
        } elseif ($section === 'appearance') {
            $prefs['theme'] = $_POST['theme'] ?? ($prefs['theme'] ?? 'light');
            $prefs['density'] = $_POST['density'] ?? ($prefs['density'] ?? 'comfortable');
            $prefs['show_animations'] = isset($_POST['showAnimations']) ? 1 : 0;
        } elseif ($section === 'privacy') {
            $prefs['profile_visibility'] = $_POST['profileVisibility'] ?? ($prefs['profile_visibility'] ?? 'public');
            $prefs['two_factor'] = isset($_POST['twoFactor']) ? 1 : 0;
            $prefs['login_notifications'] = isset($_POST['loginNotifications']) ? 1 : 0;
        }

        $valueToSave = json_encode($prefs, JSON_UNESCAPED_UNICODE);

        if ($parametreModel->upsert($key, $valueToSave, 'Préférences utilisateur', 'json')) {
            $this->setFlash('success', 'Paramètres sauvegardés avec succès');
        } else {
            $this->setFlash('error', 'Erreur lors de la sauvegarde des paramètres');
        }

        $anchor = $section === 'general' ? '#general' : ($section === 'notifications' ? '#notifications' : '');
        $this->redirect('user/settings' . ($anchor ? '/' . ltrim($anchor, '#') : ''));
    }

    public function updateProfile() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->verifyCsrf();

            $userModel = $this->model('User');

            $data = [
                'prenom' => trim($_POST['prenom'] ?? ''),
                'nom' => trim($_POST['nom'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'telephone' => trim($_POST['telephone'] ?? '')
            ];

            if (empty($data['prenom']) || empty($data['nom']) || empty($data['email'])) {
                $this->setFlash('error', 'Tous les champs obligatoires doivent être remplis');
                $this->redirect('user/profile');
                return;
            }

            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'Format d\'email invalide');
                $this->redirect('user/profile');
                return;
            }

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
            $this->verifyCsrf();

            $userModel = $this->model('User');
            $user = $userModel->find($this->getUserId());

            $currentPassword = $_POST['current_password'] ?? '';
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                $this->setFlash('error', 'Tous les champs sont obligatoires');
                $this->redirect('user/profile');
                return;
            }

            if (!password_verify($currentPassword, $user->password_hash)) {
                $this->setFlash('error', 'Le mot de passe actuel est incorrect');
                $this->redirect('user/profile');
                return;
            }

            if ($newPassword !== $confirmPassword) {
                $this->setFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                $this->redirect('user/profile');
                return;
            }

            if (strlen($newPassword) < 6) {
                $this->setFlash('error', 'Le mot de passe doit contenir au moins 6 caractères');
                $this->redirect('user/profile');
                return;
            }

            if ($userModel->updatePassword($this->getUserId(), $newPassword)) {
                $this->setFlash('success', 'Mot de passe modifié avec succès');
            } else {
                $this->setFlash('error', 'Erreur lors de la modification du mot de passe');
            }

            $this->redirect('user/profile');
        }
    }

    /**
     * Upload photo de profil
     */
    public function uploadPhoto() {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('user/profile');
            return;
        }
        $this->verifyCsrf();

        if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', 'Aucun fichier sélectionné.');
            $this->redirect('user/profile');
            return;
        }

        $file = $_FILES['photo'];
        $allowedExts = ['jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts)) {
            $this->setFlash('error', 'Format non autorisé. Acceptés : JPG, PNG, WEBP.');
            $this->redirect('user/profile');
            return;
        }
        if ($file['size'] > 2 * 1024 * 1024) {
            $this->setFlash('error', 'Fichier trop volumineux (max 2 Mo).');
            $this->redirect('user/profile');
            return;
        }

        $userId = $this->getUserId();
        $filename = 'user_' . $userId . '_' . time() . '.' . $ext;
        $uploadDir = '../public/uploads/photos/';
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $this->setFlash('error', "Erreur lors de l'upload.");
            $this->redirect('user/profile');
            return;
        }

        // Supprimer l'ancienne photo
        $db = $this->model('User')->getDb();
        $stmt = $db->prepare("SELECT photo_profil FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $oldPhoto = $stmt->fetchColumn();
        if ($oldPhoto) {
            $oldPath = '../public/' . $oldPhoto;
            if (file_exists($oldPath)) unlink($oldPath);
        }

        // Sauvegarder en BD
        $db->prepare("UPDATE users SET photo_profil=?, updated_at=NOW() WHERE id=?")
           ->execute(['uploads/photos/' . $filename, $userId]);

        $this->setFlash('success', 'Photo de profil mise à jour.');
        $this->redirect('user/profile');
    }

    /**
     * Supprimer la photo de profil
     */
    public function deletePhoto() {
        $this->requireAuth();

        $userId = $this->getUserId();
        $db = $this->model('User')->getDb();

        $stmt = $db->prepare("SELECT photo_profil FROM users WHERE id=?");
        $stmt->execute([$userId]);
        $photo = $stmt->fetchColumn();

        if ($photo) {
            $path = '../public/' . $photo;
            if (file_exists($path)) unlink($path);
            $db->prepare("UPDATE users SET photo_profil=NULL, updated_at=NOW() WHERE id=?")->execute([$userId]);
        }

        $this->setFlash('success', 'Photo supprimée.');
        $this->redirect('user/profile');
    }
}
