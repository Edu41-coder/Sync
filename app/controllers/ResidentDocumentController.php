<?php
/**
 * ====================================================================
 * SYND_GEST - GED personnelle du résident senior (dossiers + fichiers)
 * ====================================================================
 * URL d'accès : residentDocument/{method}
 * Quota 500 MB par résident, 50 MB par fichier.
 */

class ResidentDocumentController extends Controller {

    private const UPLOAD_BASE = '../uploads/residents/';

    /**
     * Arborescence : liste dossiers + fichiers d'un niveau
     * URL: residentDocument/index/{dossierId?}
     */
    public function index($dossierId = null) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->getResident();
        if (!$resident) {
            $this->setFlash('error', "Aucun profil résident associé à votre compte.");
            $this->redirect('home');
            return;
        }

        $model = $this->model('ResidentDocument');
        $dossierId = $dossierId !== null ? (int)$dossierId : null;

        $dossierCourant = null;
        if ($dossierId !== null) {
            $dossierCourant = $model->findDossier((int)$resident['id'], $dossierId);
            if (!$dossierCourant) {
                $this->setFlash('error', "Dossier introuvable.");
                $this->redirect('residentDocument/index');
                return;
            }
        }

        $this->view('residents/documents/index', [
            'title'          => 'Mes Documents - ' . APP_NAME,
            'showNavbar'     => true,
            'resident'       => $resident,
            'dossierCourant' => $dossierCourant,
            'dossiers'       => $model->getDossiers((int)$resident['id'], $dossierId),
            'fichiers'       => $model->getFichiers((int)$resident['id'], $dossierId),
            'breadcrumbDocs' => $model->getBreadcrumb((int)$resident['id'], $dossierId),
            'stats'          => $model->getStats((int)$resident['id']),
            'flash'          => $this->getFlash(),
        ], true);
    }

    /**
     * Créer un dossier (POST)
     */
    public function createDossier() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrf();

        $resident = $this->getResident();
        if (!$resident) { $this->redirect('home'); return; }

        $nom = trim($_POST['nom'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        try {
            if ($nom === '' || mb_strlen($nom) > 255) {
                throw new Exception("Nom de dossier invalide (1 à 255 caractères).");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nom)) {
                throw new Exception("Caractères interdits dans le nom (/ \\ < > : \" | ? *).");
            }

            $model = $this->model('ResidentDocument');
            $model->createDossier((int)$resident['id'], $nom, $parentId);
            $this->setFlash('success', "Dossier « {$nom} » créé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($parentId ? 'residentDocument/index/' . $parentId : 'residentDocument/index');
    }

    /**
     * Renommer un dossier (POST)
     */
    public function renameDossier($dossierId) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrf();

        $resident = $this->getResident();
        if (!$resident) { $this->redirect('home'); return; }

        $nouveauNom = trim($_POST['nom'] ?? '');
        $retourId = $_POST['retour_id'] ?? null;

        try {
            if ($nouveauNom === '' || mb_strlen($nouveauNom) > 255) {
                throw new Exception("Nom invalide.");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nouveauNom)) {
                throw new Exception("Caractères interdits dans le nom.");
            }
            $model = $this->model('ResidentDocument');
            $dossier = $model->findDossier((int)$resident['id'], (int)$dossierId);
            if (!$dossier) throw new Exception("Dossier introuvable.");

            $model->renameDossier((int)$resident['id'], (int)$dossierId, $nouveauNom);
            $this->setFlash('success', "Dossier renommé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'residentDocument/index/' . (int)$retourId : 'residentDocument/index');
    }

    /**
     * Supprimer un dossier (cascade fichiers + sous-dossiers) (POST)
     */
    public function deleteDossier($dossierId) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrf();

        $resident = $this->getResident();
        if (!$resident) { $this->redirect('home'); return; }

        $retourId = $_POST['retour_id'] ?? null;

        try {
            $model = $this->model('ResidentDocument');
            $dossier = $model->findDossier((int)$resident['id'], (int)$dossierId);
            if (!$dossier) throw new Exception("Dossier introuvable.");

            $cheminsPhysiques = $model->deleteDossierCascade((int)$resident['id'], (int)$dossierId);

            foreach ($cheminsPhysiques as $chemin) {
                $full = self::UPLOAD_BASE . ltrim($chemin, '/');
                if (file_exists($full)) @unlink($full);
            }

            $this->setFlash('success', "Dossier « " . htmlspecialchars($dossier['nom']) . " » supprimé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'residentDocument/index/' . (int)$retourId : 'residentDocument/index');
    }

    /**
     * Upload d'un fichier (POST multipart)
     */
    public function upload() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrf();

        $resident = $this->getResident();
        if (!$resident) { $this->redirect('home'); return; }

        $dossierId = !empty($_POST['dossier_id']) ? (int)$_POST['dossier_id'] : null;

        try {
            if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->uploadErrorMessage($_FILES['fichier']['error'] ?? UPLOAD_ERR_NO_FILE));
            }

            $file = $_FILES['fichier'];
            $model = $this->model('ResidentDocument');

            if ($file['size'] > ResidentDocument::TAILLE_MAX_FICHIER) {
                throw new Exception("Fichier trop volumineux (max 50 MB).");
            }

            if (!$model->quotaSuffisant((int)$resident['id'], (int)$file['size'])) {
                throw new Exception("Quota de 500 MB dépassé. Supprimez des fichiers avant d'en ajouter.");
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ResidentDocument::EXT_AUTORISEES, true)) {
                throw new Exception("Extension non autorisée (.{$ext}).");
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReel = $finfo->file($file['tmp_name']);
            if (!in_array($mimeReel, ResidentDocument::MIME_AUTORISES, true)) {
                throw new Exception("Type de fichier non autorisé ({$mimeReel}).");
            }

            if ($dossierId !== null && !$model->findDossier((int)$resident['id'], $dossierId)) {
                throw new Exception("Dossier cible invalide.");
            }

            $userId = (int)$_SESSION['user_id'];
            $sousDir = $dossierId !== null ? (string)$dossierId : 'racine';
            $dirRelatif = $userId . '/' . $sousDir;
            $dirPhysique = self::UPLOAD_BASE . $dirRelatif;

            if (!is_dir($dirPhysique)) {
                if (!mkdir($dirPhysique, 0755, true) && !is_dir($dirPhysique)) {
                    throw new Exception("Impossible de créer le dossier de stockage.");
                }
            }

            $nomSanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $nomSanitize = substr($nomSanitize, 0, 100);
            $nomFichier = time() . '_' . bin2hex(random_bytes(4)) . '_' . $nomSanitize . '.' . $ext;
            $cheminRelatif = $dirRelatif . '/' . $nomFichier;
            $cheminPhysique = self::UPLOAD_BASE . $cheminRelatif;

            if (!move_uploaded_file($file['tmp_name'], $cheminPhysique)) {
                throw new Exception("Erreur lors de l'enregistrement du fichier.");
            }

            $model->createFichier((int)$resident['id'], $dossierId, [
                'nom_original'    => $file['name'],
                'chemin_stockage' => $cheminRelatif,
                'mime_type'       => $mimeReel,
                'taille_octets'   => (int)$file['size'],
            ]);

            $this->setFlash('success', "Fichier « " . htmlspecialchars($file['name']) . " » uploadé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($dossierId ? 'residentDocument/index/' . $dossierId : 'residentDocument/index');
    }

    /**
     * Téléchargement d'un fichier (stream sécurisé)
     */
    public function download($fichierId) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->getResident();
        if (!$resident) { http_response_code(403); exit('Accès refusé'); }

        $model = $this->model('ResidentDocument');
        $fichier = $model->findFichier((int)$resident['id'], (int)$fichierId);

        if (!$fichier) { http_response_code(404); exit('Fichier introuvable'); }

        $chemin = self::UPLOAD_BASE . ltrim($fichier['chemin_stockage'], '/');
        if (!file_exists($chemin)) { http_response_code(404); exit('Fichier physique manquant'); }

        header('Content-Type: ' . ($fichier['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: attachment; filename="' . rawurlencode($fichier['nom_original']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    /**
     * Aperçu inline (image, vidéo, PDF) — pour preview sans download
     */
    public function preview($fichierId) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->getResident();
        if (!$resident) { http_response_code(403); exit('Accès refusé'); }

        $model = $this->model('ResidentDocument');
        $fichier = $model->findFichier((int)$resident['id'], (int)$fichierId);

        if (!$fichier) { http_response_code(404); exit('Fichier introuvable'); }

        $chemin = self::UPLOAD_BASE . ltrim($fichier['chemin_stockage'], '/');
        if (!file_exists($chemin)) { http_response_code(404); exit('Fichier physique manquant'); }

        header('Content-Type: ' . ($fichier['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode($fichier['nom_original']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    /**
     * Supprimer un fichier (POST)
     */
    public function deleteFichier($fichierId) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrf();

        $resident = $this->getResident();
        if (!$resident) { $this->redirect('home'); return; }

        $retourId = $_POST['retour_id'] ?? null;

        try {
            $model = $this->model('ResidentDocument');
            $chemin = $model->deleteFichier((int)$resident['id'], (int)$fichierId);
            if ($chemin === null) throw new Exception("Fichier introuvable.");

            $full = self::UPLOAD_BASE . ltrim($chemin, '/');
            if (file_exists($full)) @unlink($full);

            $this->setFlash('success', "Fichier supprimé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'residentDocument/index/' . (int)$retourId : 'residentDocument/index');
    }

    // ─── HELPERS ─────────────────────────────────────────────────

    private function getResident(): ?array {
        $model = $this->model('ResidentSenior');
        $resident = $model->findByUserId($_SESSION['user_id'] ?? 0);
        return $resident ? (array)$resident : null;
    }

    private function uploadErrorMessage(int $code): string {
        return match($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "Fichier trop volumineux.",
            UPLOAD_ERR_PARTIAL    => "Upload incomplet.",
            UPLOAD_ERR_NO_FILE    => "Aucun fichier envoyé.",
            UPLOAD_ERR_NO_TMP_DIR => "Dossier temporaire manquant sur le serveur.",
            UPLOAD_ERR_CANT_WRITE => "Écriture sur le disque impossible.",
            default               => "Erreur lors de l'upload.",
        };
    }
}
