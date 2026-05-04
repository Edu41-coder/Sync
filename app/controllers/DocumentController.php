<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Documents (GED admin/staff direction)
 * ====================================================================
 * Routes :
 *   GET  /document/index?scope=global|residence[&residence_id=Y][&dossier=X]
 *   POST /document/createDossier
 *   POST /document/renameDossier/{id}
 *   POST /document/deleteDossier/{id}
 *   POST /document/upload
 *   GET  /document/download/{id}
 *   GET  /document/preview/{id}
 *   POST /document/deleteFichier/{id}
 *
 * Permissions cf AdminDocument (ROLES_LECTURE, canRead, canWrite).
 */

class DocumentController extends Controller {

    /** @var AdminDocument */
    private $model;

    public function __construct() {
        $this->model = $this->model('AdminDocument');
    }

    // ────────────────────────────────────────────────
    //  HELPERS internes
    // ────────────────────────────────────────────────

    private function uploadBaseDir(string $scope, ?int $residenceId): string {
        if ($scope === 'global') {
            return ROOT_PATH . '/uploads/admin/global';
        }
        return ROOT_PATH . '/uploads/admin/residences/' . (int)$residenceId;
    }

    private function relativeBase(string $scope, ?int $residenceId): string {
        return $scope === 'global'
            ? 'admin/global'
            : 'admin/residences/' . (int)$residenceId;
    }

    /**
     * Récupère le scope demandé. Refuse l'accès si non autorisé.
     * Renvoie [scope, residenceId] normalisés.
     */
    private function resolveScopeOrAbort(): array {
        $role = $_SESSION['user_role'] ?? '';
        $userId = (int)$_SESSION['user_id'];
        $scope = $_GET['scope'] ?? $_POST['scope'] ?? 'global';
        if (!in_array($scope, ['global', 'residence'], true)) {
            $scope = 'global';
        }
        $residenceId = null;
        if ($scope === 'residence') {
            $residenceId = (int)($_GET['residence_id'] ?? $_POST['residence_id'] ?? 0);
            if ($residenceId <= 0) {
                $this->setFlash('error', 'Résidence non spécifiée.');
                $this->redirect('document/index?scope=global');
                exit;
            }
        }
        if (!$this->model->canRead($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Accès refusé à ce périmètre.");
            $this->redirect('document/index?scope=global');
            exit;
        }
        return [$scope, $residenceId];
    }

    /**
     * Lance un redirect vers l'index avec les params scope+residence+dossier conservés.
     */
    private function redirectIndex(string $scope, ?int $residenceId, ?int $dossierId = null): void {
        $url = 'document/index?scope=' . $scope;
        if ($scope === 'residence' && $residenceId) $url .= '&residence_id=' . $residenceId;
        if ($dossierId) $url .= '&dossier=' . $dossierId;
        $this->redirect($url);
    }

    // ────────────────────────────────────────────────
    //  INDEX (arborescence)
    // ────────────────────────────────────────────────

    public function index() {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];
        [$scope, $residenceId] = $this->resolveScopeOrAbort();

        $dossierId = !empty($_GET['dossier']) ? (int)$_GET['dossier'] : null;
        $dossierCourant = null;
        if ($dossierId !== null) {
            $dossierCourant = $this->model->findDossier($dossierId);
            if (!$dossierCourant) {
                $this->setFlash('error', 'Dossier introuvable.');
                $this->redirectIndex($scope, $residenceId);
                return;
            }
            $belongsToScope = ($scope === 'global' && $dossierCourant['residence_id'] === null)
                           || ($scope === 'residence' && (int)$dossierCourant['residence_id'] === $residenceId);
            if (!$belongsToScope) {
                $this->setFlash('error', "Ce dossier n'appartient pas au périmètre sélectionné.");
                $this->redirectIndex($scope, $residenceId);
                return;
            }
        }

        $this->view('documents/index', [
            'title'          => 'Documents - ' . APP_NAME,
            'showNavbar'     => true,
            'scope'          => $scope,
            'residenceId'    => $residenceId,
            'residences'     => $this->model->getResidencesForUser($role, $userId),
            'dossierCourant' => $dossierCourant,
            'dossiers'       => $this->model->getDossiers($scope, $residenceId, $dossierId),
            'fichiers'       => $this->model->getFichiers($scope, $residenceId, $dossierId),
            'breadcrumbDocs' => $this->model->getBreadcrumb($dossierId),
            'stats'          => $this->model->getStats($scope, $residenceId),
            'canWrite'       => $this->model->canWrite($role, $userId, $scope, $residenceId),
            'flash'          => $this->getFlash(),
        ], true);
    }

    // ────────────────────────────────────────────────
    //  DOSSIERS
    // ────────────────────────────────────────────────

    public function createDossier() {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->requirePostCsrf();

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];
        [$scope, $residenceId] = $this->resolveScopeOrAbort();

        if (!$this->model->canWrite($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Vous n'avez pas les droits d'écriture sur ce périmètre.");
            $this->redirectIndex($scope, $residenceId);
            return;
        }

        $nom = trim($_POST['nom'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        try {
            if ($nom === '' || mb_strlen($nom) > 255) {
                throw new Exception("Nom de dossier invalide (1 à 255 caractères).");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nom)) {
                throw new Exception('Caractères interdits dans le nom (/ \\ < > : " | ? *).');
            }
            $this->model->createDossier($scope, $residenceId, $parentId, $nom, $userId);
            $this->setFlash('success', "Dossier « $nom » créé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirectIndex($scope, $residenceId, $parentId);
    }

    public function renameDossier($dossierId = null) {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->requirePostCsrf();

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];

        $dossierId = (int)$dossierId;
        $dossier = $this->model->findDossier($dossierId);
        if (!$dossier) {
            $this->setFlash('error', 'Dossier introuvable.');
            $this->redirect('document/index'); return;
        }
        $scope = $dossier['residence_id'] === null ? 'global' : 'residence';
        $residenceId = $dossier['residence_id'] !== null ? (int)$dossier['residence_id'] : null;

        if (!$this->model->canWrite($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Renommage refusé.");
            $this->redirectIndex($scope, $residenceId);
            return;
        }

        $nouveauNom = trim($_POST['nom'] ?? '');
        try {
            if ($nouveauNom === '' || mb_strlen($nouveauNom) > 255) {
                throw new Exception("Nom de dossier invalide.");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nouveauNom)) {
                throw new Exception('Caractères interdits dans le nom.');
            }
            $this->model->renameDossier($dossierId, $nouveauNom);
            $this->setFlash('success', "Dossier renommé en « $nouveauNom ».");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirectIndex($scope, $residenceId, $dossier['parent_id'] !== null ? (int)$dossier['parent_id'] : null);
    }

    public function deleteDossier($dossierId = null) {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->requirePostCsrf();

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];

        $dossierId = (int)$dossierId;
        $dossier = $this->model->findDossier($dossierId);
        if (!$dossier) {
            $this->setFlash('error', 'Dossier introuvable.');
            $this->redirect('document/index'); return;
        }
        $scope = $dossier['residence_id'] === null ? 'global' : 'residence';
        $residenceId = $dossier['residence_id'] !== null ? (int)$dossier['residence_id'] : null;

        if (!$this->model->canWrite($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Suppression refusée.");
            $this->redirectIndex($scope, $residenceId);
            return;
        }

        $cheminsAffected = $this->model->deleteDossierCascade($dossierId);
        foreach ($cheminsAffected as $rel) {
            $abs = ROOT_PATH . '/uploads/' . $rel;
            if (file_exists($abs)) @unlink($abs);
        }
        $this->setFlash('success', 'Dossier et son contenu supprimés.');
        $parentId = $dossier['parent_id'] !== null ? (int)$dossier['parent_id'] : null;
        $this->redirectIndex($scope, $residenceId, $parentId);
    }

    // ────────────────────────────────────────────────
    //  FICHIERS — UPLOAD / DOWNLOAD / PREVIEW / DELETE
    // ────────────────────────────────────────────────

    public function upload() {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->requirePostCsrf();

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];
        [$scope, $residenceId] = $this->resolveScopeOrAbort();

        if (!$this->model->canWrite($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Upload refusé sur ce périmètre.");
            $this->redirectIndex($scope, $residenceId);
            return;
        }

        $dossierId = !empty($_POST['dossier_id']) ? (int)$_POST['dossier_id'] : null;
        if ($dossierId !== null) {
            $d = $this->model->findDossier($dossierId);
            $belongs = $d && (
                ($scope === 'global' && $d['residence_id'] === null)
                || ($scope === 'residence' && (int)$d['residence_id'] === $residenceId)
            );
            if (!$belongs) {
                $this->setFlash('error', "Dossier hors périmètre.");
                $this->redirectIndex($scope, $residenceId);
                return;
            }
        }

        if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', "Aucun fichier valide reçu.");
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        $file = $_FILES['fichier'];

        // 1. Taille
        if ($file['size'] > AdminDocument::TAILLE_MAX_FICHIER) {
            $this->setFlash('error', 'Fichier trop volumineux (max 50 MB).');
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        // 2. Extension
        $origName = $file['name'];
        $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, AdminDocument::EXT_AUTORISEES, true)) {
            $this->setFlash('error', 'Extension non autorisée : ' . htmlspecialchars($ext));
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        // 3. MIME réel via finfo (anti-polyglot)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, AdminDocument::MIME_AUTORISES, true)) {
            $this->setFlash('error', 'Type de fichier non autorisé : ' . htmlspecialchars($mime));
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        // 4. Stockage hors public/
        $subdir = $dossierId ? (string)$dossierId : 'racine';
        $dir = $this->uploadBaseDir($scope, $residenceId) . '/' . $subdir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->setFlash('error', "Impossible de créer le dossier de stockage.");
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $filename  = time() . '_' . bin2hex(random_bytes(4)) . '_' . $sanitized;
        $relPath   = $this->relativeBase($scope, $residenceId) . '/' . $subdir . '/' . $filename;
        $absPath   = ROOT_PATH . '/uploads/' . $relPath;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            $this->setFlash('error', "Erreur lors de l'enregistrement du fichier.");
            $this->redirectIndex($scope, $residenceId, $dossierId);
            return;
        }

        $this->model->createFichier($scope, $residenceId, $dossierId, [
            'nom_original'    => $origName,
            'chemin_stockage' => $relPath,
            'mime_type'       => $mime,
            'taille_octets'   => (int)$file['size'],
            'description'     => trim($_POST['description'] ?? '') ?: null,
        ], $userId);

        $this->setFlash('success', "Fichier « $origName » téléversé.");
        $this->redirectIndex($scope, $residenceId, $dossierId);
    }

    public function download($fichierId = null) {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->streamFichier((int)$fichierId, false);
    }

    public function preview($fichierId = null) {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->streamFichier((int)$fichierId, true);
    }

    private function streamFichier(int $fichierId, bool $inline): void {
        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];

        $f = $this->model->findFichier($fichierId);
        if (!$f) { http_response_code(404); exit('Fichier introuvable'); }

        $scope = $f['residence_id'] === null ? 'global' : 'residence';
        $residenceId = $f['residence_id'] !== null ? (int)$f['residence_id'] : null;

        if (!$this->model->canRead($role, $userId, $scope, $residenceId)) {
            http_response_code(403); exit('Accès refusé');
        }

        $absPath = ROOT_PATH . '/uploads/' . $f['chemin_stockage'];
        if (!file_exists($absPath)) {
            http_response_code(404); exit('Fichier physique introuvable');
        }

        $disposition = $inline ? 'inline' : 'attachment';
        header('Content-Type: ' . ($f['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($absPath));
        header('Content-Disposition: ' . $disposition . '; filename="' . basename($f['nom_original']) . '"');
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($absPath);
        exit;
    }

    public function deleteFichier($fichierId = null) {
        $this->requireAuth();
        $this->requireRole(AdminDocument::ROLES_LECTURE);
        $this->requirePostCsrf();

        $role   = $_SESSION['user_role'];
        $userId = (int)$_SESSION['user_id'];

        $fichierId = (int)$fichierId;
        $f = $this->model->findFichier($fichierId);
        if (!$f) {
            $this->setFlash('error', 'Fichier introuvable.');
            $this->redirect('document/index'); return;
        }
        $scope = $f['residence_id'] === null ? 'global' : 'residence';
        $residenceId = $f['residence_id'] !== null ? (int)$f['residence_id'] : null;

        if (!$this->model->canWrite($role, $userId, $scope, $residenceId)) {
            $this->setFlash('error', "Suppression refusée.");
            $this->redirectIndex($scope, $residenceId);
            return;
        }

        $rel = $this->model->deleteFichier($fichierId);
        if ($rel !== null) {
            $abs = ROOT_PATH . '/uploads/' . $rel;
            if (file_exists($abs)) @unlink($abs);
        }
        $this->setFlash('success', "Fichier supprimé.");
        $dossierId = $f['dossier_id'] !== null ? (int)$f['dossier_id'] : null;
        $this->redirectIndex($scope, $residenceId, $dossierId);
    }
}
