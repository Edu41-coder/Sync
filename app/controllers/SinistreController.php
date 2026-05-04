<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Sinistres (MVP)
 * ====================================================================
 * Routes :
 *   GET  /sinistre/index                        → liste filtrée selon rôle
 *   GET  /sinistre/show/{id}                    → détail + timeline + docs
 *   GET  /sinistre/create                       → formulaire création
 *   POST /sinistre/store                        → enregistrement
 *   GET  /sinistre/edit/{id}                    → modifiable si statut = 'declare'
 *   POST /sinistre/update/{id}                  → MAJ
 *   POST /sinistre/changeStatut/{id}            → manager only
 *   POST /sinistre/saveIndemnisation/{id}       → manager only
 *   POST /sinistre/document/upload/{id}         → upload doc GED
 *   GET  /sinistre/document/download/{docId}    → download streamé
 *   POST /sinistre/document/delete/{docId}
 *   POST /sinistre/delete/{id}                  → admin only (hard delete)
 */

class SinistreController extends Controller {

    private const ROLES_ACCESS = [
        'admin', 'directeur_residence', 'exploitant',
        'employe_residence', 'technicien_chef', 'technicien',
        'locataire_permanent', 'proprietaire'
    ];

    private const ROLES_DECLARANT = [
        'admin', 'directeur_residence', 'exploitant',
        'employe_residence', 'technicien_chef', 'technicien',
        'locataire_permanent'
    ];

    private const ALLOWED_MIMES = [
        'application/pdf',
        'image/jpeg', 'image/png', 'image/webp', 'image/gif',
        'video/mp4', 'video/quicktime',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];
    private const ALLOWED_EXTS = ['pdf','jpg','jpeg','png','webp','gif','mp4','mov','doc','docx','xls','xlsx'];
    private const MAX_FILE_SIZE = 52428800; // 50 MB

    /** @var Sinistre */
    private $model;

    public function __construct() {
        $this->model = $this->model('Sinistre');
    }

    private function uploadBaseDir(): string {
        return ROOT_PATH . '/uploads/sinistres';
    }

    // ─────────────────────────────────────────────────────────────
    //  LISTE / DÉTAIL
    // ─────────────────────────────────────────────────────────────

    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ACCESS);

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        $filters = [
            'statut'       => $_GET['statut']       ?? null,
            'type'         => $_GET['type']         ?? null,
            'gravite'      => $_GET['gravite']      ?? null,
            'residence_id' => $_GET['residence_id'] ?? null,
            'search'       => $_GET['search']       ?? null,
        ];

        $sinistres   = $this->model->getList($userId, $role, $filters);
        $residences  = $this->model->getResidencesPourFormulaire($userId, $role);
        $stats       = $this->model->getDashboardStats($userId, $role);
        $isReadOnly  = in_array($role, ['locataire_permanent', 'proprietaire'], true);
        $canDeclare  = in_array($role, self::ROLES_DECLARANT, true);

        $this->view('sinistres/index', [
            'title'       => 'Sinistres - ' . APP_NAME,
            'showNavbar'  => true,
            'sinistres'   => $sinistres,
            'residences'  => $residences,
            'stats'       => $stats,
            'filters'     => $filters,
            'userRole'    => $role,
            'isReadOnly'  => $isReadOnly,
            'canDeclare'  => $canDeclare,
            'flash'       => $this->getFlash(),
        ], true);
    }

    public function show($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ACCESS);

        $id = (int)$id;
        if ($id <= 0) { $this->redirect('sinistre/index'); return; }

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($id, $userId, $role)) {
            $this->setFlash('error', "Vous n'avez pas accès à ce sinistre.");
            $this->redirect('sinistre/index');
            return;
        }

        $sinistre = $this->model->findWithDetails($id);
        if (!$sinistre) {
            $this->setFlash('error', 'Sinistre introuvable');
            $this->redirect('sinistre/index');
            return;
        }

        $this->view('sinistres/show', [
            'title'         => 'Sinistre #' . $id . ' - ' . APP_NAME,
            'showNavbar'    => true,
            'sinistre'      => $sinistre,
            'documents'     => $this->model->getDocuments($id),
            'history'       => $this->model->getHistory($id),
            'chantiersLies' => $this->model->getChantiersLies($id),
            'userRole'      => $role,
            'canEdit'       => $this->model->userCanEdit($id, $role),
            'canManage'     => in_array($role, Sinistre::ROLES_MANAGER, true),
            'canDelete'     => $role === 'admin',
            'flash'         => $this->getFlash(),
        ], true);
    }

    // ─────────────────────────────────────────────────────────────
    //  CRÉATION
    // ─────────────────────────────────────────────────────────────

    public function create() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_DECLARANT);

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        $residences = $this->model->getResidencesPourFormulaire($userId, $role);
        if (empty($residences)) {
            $this->setFlash('error', "Aucune résidence accessible pour déclarer un sinistre.");
            $this->redirect('sinistre/index');
            return;
        }

        $this->view('sinistres/create', [
            'title'            => 'Déclarer un sinistre - ' . APP_NAME,
            'showNavbar'       => true,
            'residences'       => $residences,
            'lotsByResidence'  => $this->model->getLotsGroupesParResidence($userId, $role),
            'userRole'         => $role,
            'flash'            => $this->getFlash(),
        ], true);
    }

    public function store() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_DECLARANT);
        $this->requirePostCsrf();

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        // Validation minimale
        $required = ['residence_id', 'type_sinistre', 'date_survenue', 'titre', 'description'];
        foreach ($required as $f) {
            if (empty($_POST[$f])) {
                $this->setFlash('error', "Champ obligatoire manquant : $f");
                $this->redirect('sinistre/create');
                return;
            }
        }

        // Vérif que la résidence est dans le périmètre
        $residenceIds = $this->model->getResidenceIdsAccessibles($userId, $role);
        if (!in_array((int)$_POST['residence_id'], $residenceIds, true)) {
            $this->setFlash('error', "Résidence non autorisée.");
            $this->redirect('sinistre/create');
            return;
        }

        // Vérif XOR lot vs partie commune (exactement l'un des deux doit être renseigné)
        $lotId = !empty($_POST['lot_id']) ? (int)$_POST['lot_id'] : null;
        $piece = !empty($_POST['lieu_partie_commune']) ? $_POST['lieu_partie_commune'] : null;
        if (($lotId !== null) === ($piece !== null)) {
            $this->setFlash('error', "Précisez soit un lot, soit une partie commune (pas les deux, pas aucun).");
            $this->redirect('sinistre/create');
            return;
        }

        // Restriction résident : ne peut déclarer que sur SES lots
        if ($role === 'locataire_permanent') {
            if ($piece !== null) {
                $this->setFlash('error', "Vous ne pouvez déclarer que pour votre logement, pas pour les parties communes.");
                $this->redirect('sinistre/create');
                return;
            }
            $lotIds = $this->model->getLotIdsAccessibles($userId, $role);
            if (!in_array($lotId, $lotIds, true)) {
                $this->setFlash('error', "Lot non autorisé.");
                $this->redirect('sinistre/create');
                return;
            }
        }

        $data = [
            'residence_id'             => (int)$_POST['residence_id'],
            'lot_id'                   => $lotId,
            'lieu_partie_commune'      => $piece,
            'description_lieu'         => trim($_POST['description_lieu'] ?? '') ?: null,
            'type_sinistre'            => $_POST['type_sinistre'],
            'gravite'                  => $_POST['gravite'] ?? 'modere',
            'date_survenue'            => $_POST['date_survenue'],
            'date_constat'             => !empty($_POST['date_constat']) ? $_POST['date_constat'] : null,
            'titre'                    => trim($_POST['titre']),
            'description'              => trim($_POST['description']),
            'assureur_nom'             => trim($_POST['assureur_nom'] ?? '') ?: null,
            'numero_contrat_assurance' => trim($_POST['numero_contrat_assurance'] ?? '') ?: null,
            'numero_dossier_sinistre'  => trim($_POST['numero_dossier_sinistre'] ?? '') ?: null,
            'franchise'                => $_POST['franchise'] ?? null,
            'montant_estime'           => $_POST['montant_estime'] ?? null,
            'notes'                    => trim($_POST['notes'] ?? '') ?: null,
        ];

        $newId = $this->model->createSinistre($data, $userId);
        if ($newId === false) {
            $this->setFlash('error', "Erreur lors de la création du sinistre.");
            $this->redirect('sinistre/create');
            return;
        }

        $this->setFlash('success', "Sinistre #$newId déclaré avec succès.");
        $this->redirect('sinistre/show/' . $newId);
    }

    // ─────────────────────────────────────────────────────────────
    //  MODIFICATION (manager + statut='declare' uniquement)
    // ─────────────────────────────────────────────────────────────

    public function edit($id = null) {
        $this->requireAuth();
        $this->requireRole(Sinistre::ROLES_MANAGER);

        $id = (int)$id;
        if ($id <= 0) { $this->redirect('sinistre/index'); return; }

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($id, $userId, $role)) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('sinistre/index');
            return;
        }
        if (!$this->model->userCanEdit($id, $role)) {
            $this->setFlash('error', "Ce sinistre n'est plus modifiable (statut figé après transmission).");
            $this->redirect('sinistre/show/' . $id);
            return;
        }

        $sinistre = $this->model->findWithDetails($id);
        if (!$sinistre) { $this->redirect('sinistre/index'); return; }

        $residences = $this->model->getResidencesPourFormulaire($userId, $role);

        $this->view('sinistres/edit', [
            'title'      => "Modifier sinistre #$id - " . APP_NAME,
            'showNavbar' => true,
            'sinistre'   => $sinistre,
            'residences' => $residences,
            'userRole'   => $role,
            'flash'      => $this->getFlash(),
        ], true);
    }

    public function update($id = null) {
        $this->requireAuth();
        $this->requireRole(Sinistre::ROLES_MANAGER);
        $this->requirePostCsrf();

        $id = (int)$id;
        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($id, $userId, $role) || !$this->model->userCanEdit($id, $role)) {
            $this->setFlash('error', "Modification refusée.");
            $this->redirect('sinistre/index');
            return;
        }

        $lotId = !empty($_POST['lot_id']) ? (int)$_POST['lot_id'] : null;
        $piece = !empty($_POST['lieu_partie_commune']) ? $_POST['lieu_partie_commune'] : null;
        if (($lotId !== null) === ($piece !== null)) {
            $this->setFlash('error', "Précisez soit un lot, soit une partie commune.");
            $this->redirect('sinistre/edit/' . $id);
            return;
        }

        $data = [
            'lot_id'                   => $lotId,
            'lieu_partie_commune'      => $piece,
            'description_lieu'         => trim($_POST['description_lieu'] ?? '') ?: null,
            'type_sinistre'            => $_POST['type_sinistre'],
            'gravite'                  => $_POST['gravite'] ?? 'modere',
            'date_survenue'            => $_POST['date_survenue'],
            'date_constat'             => !empty($_POST['date_constat']) ? $_POST['date_constat'] : null,
            'titre'                    => trim($_POST['titre']),
            'description'              => trim($_POST['description']),
            'assureur_nom'             => trim($_POST['assureur_nom'] ?? '') ?: null,
            'numero_contrat_assurance' => trim($_POST['numero_contrat_assurance'] ?? '') ?: null,
            'numero_dossier_sinistre'  => trim($_POST['numero_dossier_sinistre'] ?? '') ?: null,
            'franchise'                => $_POST['franchise'] ?? null,
            'montant_estime'           => $_POST['montant_estime'] ?? null,
            'notes'                    => trim($_POST['notes'] ?? '') ?: null,
        ];

        if ($this->model->updateSinistre($id, $data, $userId)) {
            $this->setFlash('success', 'Sinistre modifié avec succès.');
        } else {
            $this->setFlash('error', 'Erreur lors de la modification.');
        }
        $this->redirect('sinistre/show/' . $id);
    }

    // ─────────────────────────────────────────────────────────────
    //  CHANGEMENT DE STATUT + INDEMNISATION
    // ─────────────────────────────────────────────────────────────

    public function changeStatut($id = null) {
        $this->requireAuth();
        $this->requireRole(Sinistre::ROLES_MANAGER);
        $this->requirePostCsrf();

        $id = (int)$id;
        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($id, $userId, $role)) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('sinistre/index');
            return;
        }

        $newStatut = $_POST['statut'] ?? '';
        $details   = trim($_POST['details'] ?? '') ?: null;

        if ($this->model->changeStatut($id, $newStatut, $userId, $details)) {
            $this->setFlash('success', 'Statut mis à jour : ' . $newStatut);
        } else {
            $this->setFlash('error', 'Statut invalide.');
        }
        $this->redirect('sinistre/show/' . $id);
    }

    public function saveIndemnisation($id = null) {
        $this->requireAuth();
        $this->requireRole(Sinistre::ROLES_MANAGER);
        $this->requirePostCsrf();

        $id = (int)$id;
        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($id, $userId, $role)) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('sinistre/index');
            return;
        }

        $montant = (float)($_POST['montant_indemnise'] ?? 0);
        $date    = $_POST['date_indemnisation'] ?? '';
        if ($montant <= 0 || empty($date)) {
            $this->setFlash('error', 'Montant et date requis.');
            $this->redirect('sinistre/show/' . $id);
            return;
        }

        if ($this->model->saveIndemnisation($id, $montant, $date, $userId)) {
            $this->setFlash('success', 'Indemnisation enregistrée.');
        } else {
            $this->setFlash('error', "Erreur lors de l'enregistrement de l'indemnisation.");
        }
        $this->redirect('sinistre/show/' . $id);
    }

    public function delete($id = null) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->requirePostCsrf();

        $id = (int)$id;
        if ($this->model->deleteSinistre($id)) {
            $this->deleteSinistreUploadDir($id);
            $this->setFlash('success', "Sinistre #$id supprimé.");
        } else {
            $this->setFlash('error', 'Erreur lors de la suppression.');
        }
        $this->redirect('sinistre/index');
    }

    // ─────────────────────────────────────────────────────────────
    //  GED — UPLOAD / DOWNLOAD / DELETE
    // ─────────────────────────────────────────────────────────────
    // Routes : /sinistre/document/upload/{id}, /document/download/{docId}, /document/delete/{docId}

    public function document($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ACCESS);

        switch ($action) {
            case 'upload':   $this->documentUpload((int)$id); return;
            case 'download': $this->documentDownload((int)$id); return;
            case 'delete':   $this->documentDelete((int)$id); return;
            default:         $this->redirect('sinistre/index');
        }
    }

    private function documentUpload(int $sinistreId): void {
        $this->requirePostCsrf();

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        if (!$this->model->userCanAccess($sinistreId, $userId, $role)) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('sinistre/index');
            return;
        }
        // Lecture seule pour propriétaire
        if ($role === 'proprietaire') {
            $this->setFlash('error', "Lecture seule pour les propriétaires.");
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        if (empty($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
            $this->setFlash('error', "Aucun fichier valide reçu.");
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        $file = $_FILES['document'];

        // 1. Taille
        if ($file['size'] > self::MAX_FILE_SIZE) {
            $this->setFlash('error', 'Fichier trop volumineux (max 50 MB).');
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        // 2. Extension
        $origName = $file['name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
        if (!in_array($ext, self::ALLOWED_EXTS, true)) {
            $this->setFlash('error', 'Extension non autorisée : ' . htmlspecialchars($ext));
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        // 3. MIME réel via finfo (anti-polyglot)
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, self::ALLOWED_MIMES, true)) {
            $this->setFlash('error', 'Type de fichier non autorisé : ' . htmlspecialchars($mime));
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        // 4. Stockage hors public/
        $dir = $this->uploadBaseDir() . '/' . $sinistreId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
            $this->setFlash('error', "Impossible de créer le dossier de stockage.");
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        $sanitized = preg_replace('/[^a-zA-Z0-9._-]/', '_', $origName);
        $filename  = time() . '_' . bin2hex(random_bytes(4)) . '_' . $sanitized;
        $relPath   = 'sinistres/' . $sinistreId . '/' . $filename;
        $absPath   = ROOT_PATH . '/uploads/' . $relPath;

        if (!move_uploaded_file($file['tmp_name'], $absPath)) {
            $this->setFlash('error', "Erreur lors de l'enregistrement du fichier.");
            $this->redirect('sinistre/show/' . $sinistreId);
            return;
        }

        $docId = $this->model->addDocument($sinistreId, [
            'type_document'   => $_POST['type_document'] ?? 'autre',
            'nom_original'    => $origName,
            'chemin_stockage' => $relPath,
            'mime_type'       => $mime,
            'taille_octets'   => (int)$file['size'],
            'description'     => trim($_POST['description'] ?? '') ?: null,
        ], $userId);

        if ($docId === false) {
            @unlink($absPath);
            $this->setFlash('error', "Erreur lors de l'enregistrement en base.");
        } else {
            $this->setFlash('success', "Document ajouté.");
        }
        $this->redirect('sinistre/show/' . $sinistreId);
    }

    private function documentDownload(int $documentId): void {
        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        $doc = $this->model->findDocument($documentId);
        if (!$doc) { http_response_code(404); exit('Document introuvable'); }

        if (!$this->model->userCanAccess((int)$doc['sinistre_id'], $userId, $role)) {
            http_response_code(403); exit('Accès refusé');
        }

        $absPath = ROOT_PATH . '/uploads/' . $doc['chemin_stockage'];
        if (!file_exists($absPath)) { http_response_code(404); exit('Fichier physique introuvable'); }

        header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
        header('Content-Length: ' . filesize($absPath));
        header('Content-Disposition: attachment; filename="' . basename($doc['nom_original']) . '"');
        header('Cache-Control: private, no-cache, must-revalidate');
        header('Pragma: no-cache');
        readfile($absPath);
        exit;
    }

    private function documentDelete(int $documentId): void {
        $this->requirePostCsrf();

        $userId = (int)$_SESSION['user_id'];
        $role   = $_SESSION['user_role'];

        $doc = $this->model->findDocument($documentId);
        if (!$doc) { $this->redirect('sinistre/index'); return; }

        // Suppression : manager OU uploader (résident peut supprimer ce qu'il a uploadé)
        $isUploader = (int)$doc['uploaded_by'] === $userId;
        $isManager  = in_array($role, Sinistre::ROLES_MANAGER, true);
        if (!$isManager && !$isUploader) {
            $this->setFlash('error', "Suppression non autorisée.");
            $this->redirect('sinistre/show/' . (int)$doc['sinistre_id']);
            return;
        }
        if (!$this->model->userCanAccess((int)$doc['sinistre_id'], $userId, $role)) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('sinistre/index');
            return;
        }

        if ($this->model->deleteDocument($documentId, $userId)) {
            $absPath = ROOT_PATH . '/uploads/' . $doc['chemin_stockage'];
            if (file_exists($absPath)) @unlink($absPath);
            $this->setFlash('success', 'Document supprimé.');
        } else {
            $this->setFlash('error', 'Erreur lors de la suppression.');
        }
        $this->redirect('sinistre/show/' . (int)$doc['sinistre_id']);
    }

    /**
     * Suppression du dossier d'un sinistre (utilisé après hard delete).
     */
    private function deleteSinistreUploadDir(int $sinistreId): void {
        $dir = $this->uploadBaseDir() . '/' . $sinistreId;
        if (!is_dir($dir)) return;
        foreach (scandir($dir) ?: [] as $f) {
            if ($f === '.' || $f === '..') continue;
            @unlink($dir . '/' . $f);
        }
        @rmdir($dir);
    }
}
