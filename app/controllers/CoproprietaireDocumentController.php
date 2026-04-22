<?php
/**
 * ====================================================================
 * SYND_GEST - GED personnelle du propriétaire (dossiers + fichiers)
 * ====================================================================
 * URL d'accès : coproprietaireDocument/{method}
 */

class CoproprietaireDocumentController extends Controller {

    private const UPLOAD_BASE = '../uploads/coproprietaires/';

    /**
     * Arborescence : liste dossiers + fichiers d'un niveau
     * URL: coproprietaireDocument/index/{dossierId?}
     */
    public function index($dossierId = null) {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $proprio = $this->getProprio();
        if (!$proprio) {
            $this->setFlash('error', "Aucun profil propriétaire associé à votre compte.");
            $this->redirect('home');
            return;
        }

        $model = $this->model('CoproprietaireDocument');
        $dossierId = $dossierId !== null ? (int)$dossierId : null;

        $dossierCourant = null;
        if ($dossierId !== null) {
            $dossierCourant = $model->findDossier((int)$proprio['id'], $dossierId);
            if (!$dossierCourant) {
                $this->setFlash('error', "Dossier introuvable.");
                $this->redirect('coproprietaireDocument/index');
                return;
            }
        }

        $this->view('coproprietaires/documents/index', [
            'title'          => 'Mes Documents - ' . APP_NAME,
            'showNavbar'     => true,
            'proprio'        => $proprio,
            'dossierCourant' => $dossierCourant,
            'dossiers'       => $model->getDossiers((int)$proprio['id'], $dossierId),
            'fichiers'       => $model->getFichiers((int)$proprio['id'], $dossierId),
            'breadcrumbDocs' => $model->getBreadcrumb((int)$proprio['id'], $dossierId),
            'stats'          => $model->getStats((int)$proprio['id']),
            'flash'          => $this->getFlash(),
        ], true);
    }

    /**
     * Créer un dossier (POST)
     */
    public function createDossier() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);
        $this->verifyCsrf();

        $proprio = $this->getProprio();
        if (!$proprio) { $this->redirect('home'); return; }

        $nom = trim($_POST['nom'] ?? '');
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        try {
            if ($nom === '' || mb_strlen($nom) > 255) {
                throw new Exception("Nom de dossier invalide (1 à 255 caractères).");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nom)) {
                throw new Exception("Caractères interdits dans le nom (/ \\ < > : \" | ? *).");
            }

            $model = $this->model('CoproprietaireDocument');
            $model->createDossier((int)$proprio['id'], $nom, $parentId);
            $this->setFlash('success', "Dossier « {$nom} » créé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($parentId ? 'coproprietaireDocument/index/' . $parentId : 'coproprietaireDocument/index');
    }

    /**
     * Renommer un dossier (POST)
     */
    public function renameDossier($dossierId) {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);
        $this->verifyCsrf();

        $proprio = $this->getProprio();
        if (!$proprio) { $this->redirect('home'); return; }

        $nouveauNom = trim($_POST['nom'] ?? '');
        $retourId = $_POST['retour_id'] ?? null;

        try {
            if ($nouveauNom === '' || mb_strlen($nouveauNom) > 255) {
                throw new Exception("Nom invalide.");
            }
            if (preg_match('/[\/\\\\<>:"|?*]/', $nouveauNom)) {
                throw new Exception("Caractères interdits dans le nom.");
            }
            $model = $this->model('CoproprietaireDocument');
            $dossier = $model->findDossier((int)$proprio['id'], (int)$dossierId);
            if (!$dossier) throw new Exception("Dossier introuvable.");

            $model->renameDossier((int)$proprio['id'], (int)$dossierId, $nouveauNom);
            $this->setFlash('success', "Dossier renommé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'coproprietaireDocument/index/' . (int)$retourId : 'coproprietaireDocument/index');
    }

    /**
     * Supprimer un dossier (cascade fichiers + sous-dossiers) (POST)
     */
    public function deleteDossier($dossierId) {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);
        $this->verifyCsrf();

        $proprio = $this->getProprio();
        if (!$proprio) { $this->redirect('home'); return; }

        $retourId = $_POST['retour_id'] ?? null;

        try {
            $model = $this->model('CoproprietaireDocument');
            $dossier = $model->findDossier((int)$proprio['id'], (int)$dossierId);
            if (!$dossier) throw new Exception("Dossier introuvable.");

            $cheminsPhysiques = $model->deleteDossierCascade((int)$proprio['id'], (int)$dossierId);

            foreach ($cheminsPhysiques as $chemin) {
                $full = self::UPLOAD_BASE . ltrim($chemin, '/');
                if (file_exists($full)) @unlink($full);
            }

            $this->setFlash('success', "Dossier « " . htmlspecialchars($dossier['nom']) . " » supprimé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'coproprietaireDocument/index/' . (int)$retourId : 'coproprietaireDocument/index');
    }

    /**
     * Upload d'un fichier (POST multipart)
     */
    public function upload() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);
        $this->verifyCsrf();

        $proprio = $this->getProprio();
        if (!$proprio) { $this->redirect('home'); return; }

        $dossierId = !empty($_POST['dossier_id']) ? (int)$_POST['dossier_id'] : null;

        try {
            if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception($this->uploadErrorMessage($_FILES['fichier']['error'] ?? UPLOAD_ERR_NO_FILE));
            }

            $file = $_FILES['fichier'];
            $model = $this->model('CoproprietaireDocument');

            // Validation taille fichier
            if ($file['size'] > CoproprietaireDocument::TAILLE_MAX_FICHIER) {
                throw new Exception("Fichier trop volumineux (max 50 MB).");
            }

            // Vérif quota
            if (!$model->quotaSuffisant((int)$proprio['id'], (int)$file['size'])) {
                throw new Exception("Quota de 1 GB dépassé. Supprimez des fichiers avant d'en ajouter.");
            }

            // Validation extension
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, CoproprietaireDocument::EXT_AUTORISEES, true)) {
                throw new Exception("Extension non autorisée (.{$ext}).");
            }

            // Validation MIME réel (finfo)
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mimeReel = $finfo->file($file['tmp_name']);
            if (!in_array($mimeReel, CoproprietaireDocument::MIME_AUTORISES, true)) {
                throw new Exception("Type de fichier non autorisé ({$mimeReel}).");
            }

            // Vérif cohérence dossier appartient au propriétaire
            if ($dossierId !== null && !$model->findDossier((int)$proprio['id'], $dossierId)) {
                throw new Exception("Dossier cible invalide.");
            }

            // Construction du chemin de stockage
            $userId = (int)$_SESSION['user_id'];
            $sousDir = $dossierId !== null ? (string)$dossierId : 'racine';
            $dirRelatif = $userId . '/' . $sousDir;
            $dirPhysique = self::UPLOAD_BASE . $dirRelatif;

            if (!is_dir($dirPhysique)) {
                if (!mkdir($dirPhysique, 0755, true) && !is_dir($dirPhysique)) {
                    throw new Exception("Impossible de créer le dossier de stockage.");
                }
            }

            // Nom sanitizé + préfixe unique
            $nomSanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $nomSanitize = substr($nomSanitize, 0, 100);
            $nomFichier = time() . '_' . bin2hex(random_bytes(4)) . '_' . $nomSanitize . '.' . $ext;
            $cheminRelatif = $dirRelatif . '/' . $nomFichier;
            $cheminPhysique = self::UPLOAD_BASE . $cheminRelatif;

            if (!move_uploaded_file($file['tmp_name'], $cheminPhysique)) {
                throw new Exception("Erreur lors de l'enregistrement du fichier.");
            }

            $model->createFichier((int)$proprio['id'], $dossierId, [
                'nom_original'    => $file['name'],
                'chemin_stockage' => $cheminRelatif,
                'mime_type'       => $mimeReel,
                'taille_octets'   => (int)$file['size'],
            ]);

            $this->setFlash('success', "Fichier « " . htmlspecialchars($file['name']) . " » uploadé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($dossierId ? 'coproprietaireDocument/index/' . $dossierId : 'coproprietaireDocument/index');
    }

    /**
     * Téléchargement d'un fichier (stream sécurisé)
     * URL: coproprietaireDocument/download/{fichierId}
     */
    public function download($fichierId) {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $proprio = $this->getProprio();
        if (!$proprio) { http_response_code(403); exit('Accès refusé'); }

        $model = $this->model('CoproprietaireDocument');
        $fichier = $model->findFichier((int)$proprio['id'], (int)$fichierId);

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
        $this->requireRole(['proprietaire']);

        $proprio = $this->getProprio();
        if (!$proprio) { http_response_code(403); exit('Accès refusé'); }

        $model = $this->model('CoproprietaireDocument');
        $fichier = $model->findFichier((int)$proprio['id'], (int)$fichierId);

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
        $this->requireRole(['proprietaire']);
        $this->verifyCsrf();

        $proprio = $this->getProprio();
        if (!$proprio) { $this->redirect('home'); return; }

        $retourId = $_POST['retour_id'] ?? null;

        try {
            $model = $this->model('CoproprietaireDocument');
            $chemin = $model->deleteFichier((int)$proprio['id'], (int)$fichierId);
            if ($chemin === null) throw new Exception("Fichier introuvable.");

            $full = self::UPLOAD_BASE . ltrim($chemin, '/');
            if (file_exists($full)) @unlink($full);

            $this->setFlash('success', "Fichier supprimé.");
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect($retourId ? 'coproprietaireDocument/index/' . (int)$retourId : 'coproprietaireDocument/index');
    }

    // ─── HELPERS ─────────────────────────────────────────────────

    private function getProprio(): ?array {
        $model = $this->model('Coproprietaire');
        return $model->findByUserId($_SESSION['user_id']);
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
