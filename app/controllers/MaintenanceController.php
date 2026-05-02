<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Maintenance Technique
 * ====================================================================
 * Module unifié : 6 spécialités (piscine, ascenseur, travaux, plomberie,
 * électricité, peinture) avec permissions granulaires user × spécialité.
 *
 * Rôles :
 *   - technicien_chef : tout (admin spécialités + interventions + chantiers)
 *   - technicien      : sections autorisées par ses spécialités assignées
 *   - admin / directeur_residence : accès lecture/supervision
 */
class MaintenanceController extends Controller {

    private const ROLES_ALL     = ['admin', 'directeur_residence', 'technicien_chef', 'technicien'];
    private const ROLES_MANAGER = ['admin', 'directeur_residence', 'technicien_chef'];
    private const UPLOAD_CERTIFS = '../uploads/maintenance/certifs/';
    private const UPLOAD_PHOTOS  = '../public/uploads/maintenance/photos/';

    /** Résidences accessibles au user connecté (admin = toutes, sinon via user_residence) */
    private function residencesAccessibles(): array {
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        if ($userRole === 'admin') {
            return $pdo->query("SELECT id FROM coproprietees WHERE actif = 1")->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = $pdo->prepare("SELECT residence_id FROM user_residence WHERE user_id = ? AND statut = 'actif'");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** IDs des spécialités du user connecté (vide = toutes les spécialités si manager) */
    private function specialitesIds(): array {
        $specModel = $this->model('Specialite');
        $rows = $specModel->getUserSpecialites($this->getUserId());
        return array_map(fn($r) => (int)$r['id'], $rows);
    }

    /**
     * Dashboard maintenance (squelette, enrichi en phase 2+)
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $specModel = $this->model('Specialite');
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';

        $userSpecialites = in_array($userRole, self::ROLES_MANAGER, true)
            ? $specModel->getAll()
            : $specModel->getUserSpecialites($userId);

        $certifsExpirantes = in_array($userRole, self::ROLES_MANAGER, true)
            ? $specModel->getCertificationsExpirantes(3)
            : [];

        $this->view('maintenance/index', [
            'title'            => 'Maintenance technique - ' . APP_NAME,
            'showNavbar'       => true,
            'userRole'         => $userRole,
            'userSpecialites'  => $userSpecialites,
            'certifsExpirantes' => $certifsExpirantes,
            'flash'            => $this->getFlash(),
        ], true);
    }

    // ─────────────────────────────────────────────────────────────
    //  MATRICE D'AFFECTATION USER × SPÉCIALITÉ (chef uniquement)
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /maintenance/specialites — matrice cochable
     */
    public function specialites() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $specModel = $this->model('Specialite');

        $this->view('maintenance/specialites', [
            'title'         => 'Affectation des spécialités - ' . APP_NAME,
            'showNavbar'    => true,
            'specialites'   => $specModel->getAll(),
            'matrice'       => $specModel->getMatriceTechniciens(),
            'niveaux'       => Specialite::NIVEAUX,
            'flash'         => $this->getFlash(),
        ], true);
    }

    /**
     * POST /maintenance/affecterSpecialite — coche / décoche / change niveau
     */
    public function affecterSpecialite() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $userId       = (int)($_POST['user_id'] ?? 0);
        $specialiteId = (int)($_POST['specialite_id'] ?? 0);
        $action       = $_POST['action'] ?? 'affecter'; // affecter | retirer
        $niveau       = $_POST['niveau'] ?? 'confirme';
        $notes        = trim($_POST['notes'] ?? '') ?: null;

        if (!$userId || !$specialiteId) {
            $this->setFlash('error', 'Paramètres invalides.');
            $this->redirect('maintenance/specialites'); return;
        }

        $specModel = $this->model('Specialite');

        try {
            if ($action === 'retirer') {
                $specModel->retirer($userId, $specialiteId);
                $this->setFlash('success', 'Spécialité retirée.');
            } else {
                $specModel->affecter($userId, $specialiteId, $niveau, $this->getUserId(), $notes);
                $this->setFlash('success', 'Spécialité affectée.');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/specialites');
    }

    // ─────────────────────────────────────────────────────────────
    //  CERTIFICATIONS PROFESSIONNELLES
    // ─────────────────────────────────────────────────────────────

    /**
     * GET /maintenance/certifications/{userId?}
     * - Sans userId : le user voit ses propres certifs
     * - Avec userId : le chef voit celles d'un technicien
     */
    public function certifications($userId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $sessionUserId = $this->getUserId();
        $userRole      = $_SESSION['user_role'] ?? '';
        $isManager     = in_array($userRole, self::ROLES_MANAGER, true);

        $targetUserId = $userId !== null ? (int)$userId : $sessionUserId;

        // Un technicien ne peut consulter que ses propres certifs
        if (!$isManager && $targetUserId !== $sessionUserId) {
            $this->setFlash('error', "Vous ne pouvez consulter que vos propres certifications.");
            $this->redirect('maintenance/certifications'); return;
        }

        $userModel = $this->model('User');
        $targetUser = $userModel->find($targetUserId);
        if (!$targetUser) {
            $this->setFlash('error', 'Utilisateur introuvable.');
            $this->redirect('maintenance/index'); return;
        }

        $specModel = $this->model('Specialite');

        $this->view('maintenance/certifications', [
            'title'         => 'Certifications - ' . APP_NAME,
            'showNavbar'    => true,
            'targetUser'    => $targetUser,
            'isOwnPage'     => $targetUserId === $sessionUserId,
            'isManager'     => $isManager,
            'specialites'   => $specModel->getAll(),
            'certifications' => $specModel->getCertifications($targetUserId, false),
            'flash'         => $this->getFlash(),
        ], true);
    }

    /**
     * POST /maintenance/certifications/create
     */
    public function createCertification() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $sessionUserId = $this->getUserId();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        $targetUserId = (int)($_POST['user_id'] ?? $sessionUserId);
        if (!$isManager && $targetUserId !== $sessionUserId) {
            $this->setFlash('error', "Action interdite.");
            $this->redirect('maintenance/certifications'); return;
        }

        try {
            $nom = trim($_POST['nom'] ?? '');
            $dateObtention = $_POST['date_obtention'] ?? '';
            if ($nom === '' || $dateObtention === '') {
                throw new Exception('Le nom et la date d\'obtention sont obligatoires.');
            }

            // Upload fichier preuve (optionnel)
            $cheminPreuve = null;
            if (!empty($_FILES['fichier_preuve']) && $_FILES['fichier_preuve']['error'] === UPLOAD_ERR_OK) {
                $cheminPreuve = $this->handleCertifUpload($_FILES['fichier_preuve'], $targetUserId);
            }

            $specModel = $this->model('Specialite');
            $specModel->createCertification([
                'user_id'           => $targetUserId,
                'specialite_id'     => !empty($_POST['specialite_id']) ? (int)$_POST['specialite_id'] : null,
                'nom'               => $nom,
                'organisme'         => trim($_POST['organisme'] ?? ''),
                'numero_certificat' => trim($_POST['numero_certificat'] ?? ''),
                'date_obtention'    => $dateObtention,
                'date_expiration'   => !empty($_POST['date_expiration']) ? $_POST['date_expiration'] : null,
                'fichier_preuve'    => $cheminPreuve,
                'notes'             => trim($_POST['notes'] ?? ''),
            ]);

            $this->setFlash('success', "Certification ajoutée.");
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }

        $redirectId = $isManager && $targetUserId !== $sessionUserId ? '/' . $targetUserId : '';
        $this->redirect('maintenance/certifications' . $redirectId);
    }

    /**
     * POST /maintenance/certifications/delete/{id}
     */
    public function deleteCertification($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $sessionUserId = $this->getUserId();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);
        $userIdOwner = (int)($_POST['user_id'] ?? $sessionUserId);

        try {
            $specModel = $this->model('Specialite');
            $cert = $specModel->findCertification((int)$id);
            if (!$cert) throw new Exception('Certification introuvable.');

            // Permission : le user supprime la sienne, ou le manager celle d'un technicien
            if (!$isManager && (int)$cert['user_id'] !== $sessionUserId) {
                throw new Exception("Action interdite.");
            }

            $cheminPreuve = $specModel->deleteCertification((int)$id, $isManager ? null : $sessionUserId);
            if ($cheminPreuve) {
                $full = self::UPLOAD_CERTIFS . ltrim($cheminPreuve, '/');
                if (file_exists($full)) @unlink($full);
            }
            $this->setFlash('success', 'Certification supprimée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }

        $redirectId = $isManager && $userIdOwner !== $sessionUserId ? '/' . $userIdOwner : '';
        $this->redirect('maintenance/certifications' . $redirectId);
    }

    /**
     * GET /maintenance/downloadCertif/{id} — stream sécurisé
     */
    public function downloadCertif($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $sessionUserId = $this->getUserId();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        $specModel = $this->model('Specialite');
        $cert = $specModel->findCertification((int)$id);
        if (!$cert || empty($cert['fichier_preuve'])) {
            http_response_code(404); exit('Fichier introuvable');
        }
        if (!$isManager && (int)$cert['user_id'] !== $sessionUserId) {
            http_response_code(403); exit('Accès refusé');
        }

        $chemin = self::UPLOAD_CERTIFS . ltrim($cert['fichier_preuve'], '/');
        if (!file_exists($chemin)) { http_response_code(404); exit('Fichier physique manquant'); }

        $mime = mime_content_type($chemin) ?: 'application/octet-stream';
        $nom = basename($cert['fichier_preuve']);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode($nom) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    // ─── HELPERS ─────────────────────────────────────────────────

    // ─────────────────────────────────────────────────────────────
    //  INTERVENTIONS (CRUD)
    // ─────────────────────────────────────────────────────────────

    /** GET /maintenance/interventions — liste filtrée */
    public function interventions() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';

        $iModel = $this->model('MaintenanceIntervention');
        $specModel = $this->model('Specialite');

        $filtres = [
            'statut'        => $_GET['statut']        ?? null,
            'specialite_id' => $_GET['specialite_id'] ?? null,
            'residence_id'  => $_GET['residence_id']  ?? null,
        ];

        $interventions = $iModel->getInterventions(
            $userId, $userRole,
            $this->specialitesIds(),
            $this->residencesAccessibles(),
            $filtres
        );

        $this->view('maintenance/interventions', [
            'title'         => 'Interventions - ' . APP_NAME,
            'showNavbar'    => true,
            'userRole'      => $userRole,
            'isManager'     => in_array($userRole, self::ROLES_MANAGER, true),
            'interventions' => $interventions,
            'specialites'   => $specModel->getAll(),
            'filtres'       => $filtres,
            'flash'         => $this->getFlash(),
        ], true);
    }

    /** GET /maintenance/interventionShow/{id} */
    public function interventionShow($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $iModel = $this->model('MaintenanceIntervention');
        $intervention = $iModel->findById((int)$id);
        if (!$intervention) {
            $this->setFlash('error', 'Intervention introuvable.');
            $this->redirect('maintenance/interventions'); return;
        }

        // Filtre accès : technicien doit avoir la spécialité OU être assigné OU avoir accès à la résidence
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $isManager = in_array($userRole, self::ROLES_MANAGER, true);
        if (!$isManager) {
            $hasSpec = in_array((int)$intervention['specialite_id'], $this->specialitesIds(), true);
            $isAssigne = (int)$intervention['user_assigne_id'] === $userId;
            if (!$hasSpec && !$isAssigne) {
                $this->setFlash('error', "Vous n'avez pas accès à cette intervention.");
                $this->redirect('maintenance/interventions'); return;
            }
        }

        $this->view('maintenance/intervention_show', [
            'title'        => 'Intervention #' . (int)$id . ' - ' . APP_NAME,
            'showNavbar'   => true,
            'intervention' => $intervention,
            'isManager'    => $isManager,
            'flash'        => $this->getFlash(),
        ], true);
    }

    /** GET|POST /maintenance/interventionForm/{id?} */
    public function interventionForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $iModel = $this->model('MaintenanceIntervention');
        $specModel = $this->model('Specialite');
        $userRole = $_SESSION['user_role'] ?? '';
        $isManager = in_array($userRole, self::ROLES_MANAGER, true);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id'      => (int)($_POST['residence_id'] ?? 0),
                    'specialite_id'     => (int)($_POST['specialite_id'] ?? 0),
                    'lot_id'            => !empty($_POST['lot_id']) ? (int)$_POST['lot_id'] : null,
                    'titre'             => trim($_POST['titre'] ?? ''),
                    'description'       => trim($_POST['description'] ?? '') ?: null,
                    'type_intervention' => $_POST['type_intervention'] ?? 'entretien_courant',
                    'priorite'          => $_POST['priorite'] ?? 'normale',
                    'statut'            => $_POST['statut'] ?? 'a_planifier',
                    'user_assigne_id'   => !empty($_POST['user_assigne_id']) ? (int)$_POST['user_assigne_id'] : null,
                    'prestataire_externe'     => trim($_POST['prestataire_externe'] ?? '') ?: null,
                    'prestataire_externe_tel' => trim($_POST['prestataire_externe_tel'] ?? '') ?: null,
                    'date_planifiee'    => !empty($_POST['date_planifiee']) ? $_POST['date_planifiee'] : null,
                    'date_realisee'     => !empty($_POST['date_realisee']) ? $_POST['date_realisee'] : null,
                    'duree_minutes'     => $_POST['duree_minutes'] ?? null,
                    'cout'              => $_POST['cout'] ?? null,
                    'notes'             => trim($_POST['notes'] ?? '') ?: null,
                ];
                if ($data['titre'] === '' || !$data['residence_id'] || !$data['specialite_id']) {
                    throw new Exception('Titre, résidence et spécialité sont obligatoires.');
                }

                if ($id) {
                    $iModel->update((int)$id, $data);
                    $this->setFlash('success', 'Intervention mise à jour.');
                    $this->redirect('maintenance/interventionShow/' . (int)$id);
                } else {
                    $data['created_by'] = $this->getUserId();
                    $newId = $iModel->create($data);
                    $this->setFlash('success', 'Intervention créée.');
                    $this->redirect('maintenance/interventionShow/' . $newId);
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'maintenance/interventionForm/' . (int)$id : 'maintenance/interventionForm');
                return;
            }
        }

        // GET — formulaire
        $intervention = $id ? $iModel->findById((int)$id) : null;
        if ($id && !$intervention) {
            $this->setFlash('error', 'Intervention introuvable.');
            $this->redirect('maintenance/interventions'); return;
        }

        // Données pour les sélecteurs : résidences, lots, techniciens, spécialités
        $pdo = Database::getInstance()->getConnection();
        $residencesIds = $this->residencesAccessibles();
        $residences = [];
        if (!empty($residencesIds)) {
            $ph = implode(',', array_fill(0, count($residencesIds), '?'));
            $stmt = $pdo->prepare("SELECT id, nom, ville FROM coproprietees WHERE id IN ($ph) AND actif = 1 ORDER BY nom");
            $stmt->execute($residencesIds);
            $residences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $techniciens = $pdo->query("SELECT id, prenom, nom, role FROM users WHERE role IN ('technicien','technicien_chef') AND actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('maintenance/intervention_form', [
            'title'        => ($id ? 'Modifier' : 'Nouvelle') . ' intervention - ' . APP_NAME,
            'showNavbar'   => true,
            'intervention' => $intervention,
            'specialites'  => $specModel->getAll(),
            'residences'   => $residences,
            'techniciens'  => $techniciens,
            'types'        => MaintenanceIntervention::TYPES,
            'priorites'    => MaintenanceIntervention::PRIORITES,
            'statuts'      => MaintenanceIntervention::STATUTS,
            'isManager'    => $isManager,
            'flash'        => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/interventionStatut/{id} — change statut rapide */
    public function interventionStatut($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, MaintenanceIntervention::STATUTS, true)) {
            $this->setFlash('error', 'Statut invalide.');
            $this->redirect('maintenance/interventions'); return;
        }

        $this->model('MaintenanceIntervention')->updateStatut((int)$id, $statut);
        $this->setFlash('success', 'Statut mis à jour.');
        $this->redirect('maintenance/interventionShow/' . (int)$id);
    }

    /** POST /maintenance/interventionPhoto/{id} — upload photo avant/après */
    public function interventionPhoto($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $champ = $_POST['champ'] ?? '';
        if (!in_array($champ, ['photo_avant', 'photo_apres'], true)) {
            $this->setFlash('error', 'Champ photo invalide.');
            $this->redirect('maintenance/interventionShow/' . (int)$id); return;
        }

        try {
            if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Aucune photo sélectionnée.');
            }
            $file = $_FILES['photo'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                throw new Exception('Format non autorisé (JPG/PNG/WEBP uniquement).');
            }
            if ($file['size'] > 5 * 1024 * 1024) {
                throw new Exception('Photo trop volumineuse (max 5 Mo).');
            }
            // MIME réel
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);
            if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                throw new Exception('Type MIME non autorisé.');
            }

            if (!is_dir(self::UPLOAD_PHOTOS) && !mkdir(self::UPLOAD_PHOTOS, 0755, true)) {
                throw new Exception('Impossible de créer le dossier de stockage.');
            }
            $nom = 'int_' . (int)$id . '_' . $champ . '_' . time() . '.' . $ext;
            $cible = self::UPLOAD_PHOTOS . $nom;
            if (!move_uploaded_file($file['tmp_name'], $cible)) {
                throw new Exception('Erreur d\'enregistrement.');
            }

            $this->model('MaintenanceIntervention')->updatePhoto((int)$id, $champ, 'uploads/maintenance/photos/' . $nom);
            $this->setFlash('success', 'Photo enregistrée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/interventionShow/' . (int)$id);
    }

    /** POST /maintenance/interventionDelete/{id} — manager only */
    public function interventionDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $this->model('MaintenanceIntervention')->deleteIntervention((int)$id);
        $this->setFlash('success', 'Intervention supprimée.');
        $this->redirect('maintenance/interventions');
    }

    // ─────────────────────────────────────────────────────────────
    //  PLANNING / CALENDRIER (TUI v1.15.3)
    // ─────────────────────────────────────────────────────────────

    /** GET /maintenance/planning */
    public function planning() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $specModel = $this->model('Specialite');

        $this->view('maintenance/planning', [
            'title'      => 'Planning maintenance - ' . APP_NAME,
            'showNavbar' => true,
            'specialites' => $specModel->getAll(),
            'isManager'  => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** AJAX */
    public function planningAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        header('Content-Type: application/json; charset=utf-8');

        $iModel = $this->model('MaintenanceIntervention');
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $specsIds = $this->specialitesIds();
        $resIds = $this->residencesAccessibles();

        try {
            switch ($action) {
                case 'getEvents':
                    $start = $_GET['start'] ?? date('Y-m-01');
                    $end   = $_GET['end']   ?? date('Y-m-t');
                    $events = $iModel->getEventsCalendrier($userId, $userRole, $specsIds, $resIds, $start, $end);
                    $formatted = array_map(function($e) {
                        $duree = 60; // minutes par défaut
                        $start = $e['date_planifiee'];
                        $end = (new DateTime($start))->modify("+$duree minutes")->format('Y-m-d H:i:s');
                        $titre = $e['titre'] . ' — ' . $e['residence_nom'];
                        $isReadOnly = $e['statut'] === 'terminee' || $e['statut'] === 'annulee';
                        return [
                            'id'                => (int)$e['id'],
                            'calendarId'        => $e['specialite_slug'],
                            'title'             => $titre,
                            'start'             => $start,
                            'end'               => $end,
                            'isAllDay'          => false,
                            'calendarColor'     => $e['couleur'],
                            'categoryColor'     => $e['bg_couleur'],
                            'categoryTextColor' => '#333',
                            'body'              => $e['description'] ?: '',
                            'isReadOnly'        => $isReadOnly,
                            'raw'               => [
                                'specialiteSlug' => $e['specialite_slug'],
                                'specialiteNom'  => $e['specialite_nom'],
                                'statut'         => $e['statut'],
                                'priorite'       => $e['priorite'],
                                'residenceNom'   => $e['residence_nom'],
                            ],
                        ];
                    }, $events);
                    echo json_encode($formatted);
                    break;

                case 'move':
                    if (!in_array($userRole, self::ROLES_MANAGER, true)) {
                        echo json_encode(['success' => false, 'message' => 'Drag & drop réservé au chef technique.']);
                        return;
                    }
                    $input = json_decode(file_get_contents('php://input'), true) ?: [];
                    $id = (int)($input['id'] ?? 0);
                    $newStart = $input['start'] ?? null;
                    if (!$id || !$newStart) {
                        echo json_encode(['success' => false, 'message' => 'Paramètres invalides.']);
                        return;
                    }
                    $iModel->updateDatePlanifiee($id, date('Y-m-d H:i:s', strtotime($newStart)));
                    echo json_encode(['success' => true]);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ─────────────────────────────────────────────────────────────
    //  STOCK : PRODUITS / INVENTAIRE / COMMANDES / FOURNISSEURS
    // ─────────────────────────────────────────────────────────────

    /** GET /maintenance/produits — catalogue (manager only) */
    public function produits() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $stockModel = $this->model('MaintenanceStock');
        $specModel  = $this->model('Specialite');

        $this->view('maintenance/produits', [
            'title'       => 'Catalogue produits & outils - ' . APP_NAME,
            'showNavbar'  => true,
            'produits'    => $stockModel->getProduits(null, false),
            'specialites' => $specModel->getAll(),
            'flash'       => $this->getFlash(),
        ], true);
    }

    /** GET|POST /maintenance/produitForm/{id?} (manager only) */
    public function produitForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $stockModel = $this->model('MaintenanceStock');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'nom'           => trim($_POST['nom'] ?? ''),
                    'specialite_id' => $_POST['specialite_id'] ?? null,
                    'categorie'     => $_POST['categorie'] ?? 'autre',
                    'type'          => $_POST['type'] ?? 'produit',
                    'unite'         => trim($_POST['unite'] ?? '') ?: null,
                    'prix_unitaire' => $_POST['prix_unitaire'] ?? null,
                    'fiche_securite' => trim($_POST['fiche_securite'] ?? '') ?: null,
                    'actif'         => isset($_POST['actif']) ? 1 : 0,
                    'notes'         => trim($_POST['notes'] ?? '') ?: null,
                ];
                if ($data['nom'] === '') throw new Exception('Le nom est obligatoire.');
                if ($id) $stockModel->updateProduit((int)$id, $data);
                else $stockModel->createProduit($data);
                $this->setFlash('success', 'Produit ' . ($id ? 'mis à jour' : 'créé') . '.');
                $this->redirect('maintenance/produits');
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'maintenance/produitForm/' . (int)$id : 'maintenance/produitForm');
                return;
            }
        }

        $produit = $id ? $stockModel->findProduit((int)$id) : null;
        $this->view('maintenance/produit_form', [
            'title'       => ($id ? 'Modifier' : 'Nouveau') . ' produit - ' . APP_NAME,
            'showNavbar'  => true,
            'produit'     => $produit,
            'specialites' => $this->model('Specialite')->getAll(),
            'categories'  => MaintenanceStock::CATEGORIES_PRODUIT,
            'types'       => MaintenanceStock::TYPES_PRODUIT,
            'flash'       => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/produitDelete/{id} */
    public function produitDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        try {
            $this->model('MaintenanceStock')->deleteProduit((int)$id);
            $this->setFlash('success', 'Produit supprimé.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Suppression impossible : produit utilisé en inventaire ou commande.');
        }
        $this->redirect('maintenance/produits');
    }

    /** GET /maintenance/inventaire?residence_id=N */
    public function inventaire() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $stockModel = $this->model('MaintenanceStock');
        $residences = $this->residencesAvecLibelles();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('maintenance/inventaire', [
                'title' => 'Inventaire - ' . APP_NAME, 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'inventaire' => [],
                'produitsHors' => [], 'isManager' => $isManager, 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('maintenance/inventaire'); return;
        }

        $alertesSeules = !empty($_GET['alertes']);

        $this->view('maintenance/inventaire', [
            'title'             => 'Inventaire - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'inventaire'        => $stockModel->getInventaire($residenceId, null, $alertesSeules),
            'produitsHors'      => $isManager ? $stockModel->getProduitsHorsInventaire($residenceId) : [],
            'alertesSeules'     => $alertesSeules,
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/inventaireAjouter */
    public function inventaireAjouter() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $residenceId = (int)($_POST['residence_id'] ?? 0);
        $produitId   = (int)($_POST['produit_id'] ?? 0);
        try {
            if (!$residenceId || !$produitId) throw new Exception('Résidence et produit obligatoires.');
            $this->model('MaintenanceStock')->ajouterAuInventaire(
                $residenceId, $produitId,
                !empty($_POST['seuil_alerte']) ? (float)$_POST['seuil_alerte'] : null,
                trim($_POST['emplacement'] ?? '') ?: null
            );
            $this->setFlash('success', 'Produit ajouté à l\'inventaire.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/inventaire?residence_id=' . $residenceId);
    }

    /** POST /maintenance/inventaireMouvement/{inventaireId} */
    public function inventaireMouvement($inventaireId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        try {
            $type     = $_POST['type'] ?? '';
            $quantite = (float)($_POST['quantite'] ?? 0);
            $motif    = $_POST['motif'] ?? 'autre';
            $notes    = trim($_POST['notes'] ?? '') ?: null;
            $this->model('MaintenanceStock')->mouvementStock(
                (int)$inventaireId, $type, $quantite, $motif, $this->getUserId(), null, null, $notes
            );
            $this->setFlash('success', 'Mouvement enregistré.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/inventaire?residence_id=' . (int)($_POST['residence_id'] ?? 0));
    }

    /** GET /maintenance/inventaireHistorique/{inventaireId} */
    public function inventaireHistorique($inventaireId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $stockModel = $this->model('MaintenanceStock');
        $item = $stockModel->findInventaireItem((int)$inventaireId);
        if (!$item) {
            $this->setFlash('error', 'Item inventaire introuvable.');
            $this->redirect('maintenance/inventaire'); return;
        }

        $this->view('maintenance/inventaire_historique', [
            'title'      => 'Historique stock — ' . $item['nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'item'       => $item,
            'mouvements' => $stockModel->getMouvements((int)$inventaireId, 200),
            'flash'      => $this->getFlash(),
        ], true);
    }

    // ─── COMMANDES ──────────────────────────────────────────────

    /** GET /maintenance/commandes?statut=X */
    public function commandes() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $resIds = $this->residencesAccessibles();
        $statut = $_GET['statut'] ?? null;

        $this->view('maintenance/commandes', [
            'title'      => 'Commandes fournisseurs - ' . APP_NAME,
            'showNavbar' => true,
            'commandes'  => $this->model('MaintenanceStock')->getCommandes($resIds, $statut),
            'statuts'    => MaintenanceStock::STATUTS_COMMANDE,
            'filtreStatut' => $statut,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** GET|POST /maintenance/commandeForm */
    public function commandeForm() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $stockModel = $this->model('MaintenanceStock');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id'         => (int)($_POST['residence_id'] ?? 0),
                    'fournisseur_id'       => (int)($_POST['fournisseur_id'] ?? 0),
                    'date_commande'        => $_POST['date_commande'] ?? date('Y-m-d'),
                    'date_livraison_prevue' => $_POST['date_livraison_prevue'] ?? null,
                    'statut'               => $_POST['statut'] ?? 'brouillon',
                    'notes'                => trim($_POST['notes'] ?? '') ?: null,
                    'created_by'           => $this->getUserId(),
                ];
                $lignes = [];
                foreach ($_POST['lignes'] ?? [] as $l) {
                    if (empty($l['designation']) || empty($l['quantite'])) continue;
                    $lignes[] = [
                        'produit_id'       => !empty($l['produit_id']) ? (int)$l['produit_id'] : null,
                        'designation'      => trim($l['designation']),
                        'quantite'         => (float)$l['quantite'],
                        'prix_unitaire_ht' => (float)($l['prix_unitaire_ht'] ?? 0),
                        'taux_tva'         => (float)($l['taux_tva'] ?? 20.00),
                    ];
                }
                if (!$data['residence_id'] || !$data['fournisseur_id']) throw new Exception('Résidence et fournisseur obligatoires.');
                if (empty($lignes)) throw new Exception('Au moins une ligne de commande requise.');
                $cmdId = $stockModel->createCommande($data, $lignes);
                $this->setFlash('success', 'Commande créée.');
                $this->redirect('maintenance/commandeShow/' . $cmdId);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect('maintenance/commandeForm');
                return;
            }
        }

        $this->view('maintenance/commande_form', [
            'title'        => 'Nouvelle commande - ' . APP_NAME,
            'showNavbar'   => true,
            'residences'   => $this->residencesAvecLibelles(),
            'fournisseurs' => $stockModel->getTousFournisseursActifs(),
            'produits'     => $stockModel->getProduits(),
            'flash'        => $this->getFlash(),
        ], true);
    }

    /** GET /maintenance/commandeShow/{id} */
    public function commandeShow($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $stockModel = $this->model('MaintenanceStock');
        $commande = $stockModel->findCommande((int)$id);
        if (!$commande) {
            $this->setFlash('error', 'Commande introuvable.');
            $this->redirect('maintenance/commandes'); return;
        }
        $this->view('maintenance/commande_show', [
            'title'      => 'Commande ' . $commande['numero_commande'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'commande'   => $commande,
            'lignes'     => $stockModel->getCommandeLignes((int)$id),
            'statuts'    => MaintenanceStock::STATUTS_COMMANDE,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/commandeStatut/{id} */
    public function commandeStatut($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $statut = $_POST['statut'] ?? '';
        if ($this->model('MaintenanceStock')->updateCommandeStatut((int)$id, $statut)) {
            $this->setFlash('success', 'Statut mis à jour.');
        } else {
            $this->setFlash('error', 'Statut invalide.');
        }
        $this->redirect('maintenance/commandeShow/' . (int)$id);
    }

    /** POST /maintenance/commandeReceptionner/{id} */
    public function commandeReceptionner($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        try {
            $quantites = $_POST['quantite_recue'] ?? [];
            $this->model('MaintenanceStock')->receptionnerCommande((int)$id, $quantites, $this->getUserId());
            $this->setFlash('success', 'Réception enregistrée. Stock mis à jour automatiquement.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/commandeShow/' . (int)$id);
    }

    /** POST /maintenance/commandeDelete/{id} */
    public function commandeDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $this->model('MaintenanceStock')->deleteCommande((int)$id);
        $this->setFlash('success', 'Commande supprimée/annulée.');
        $this->redirect('maintenance/commandes');
    }

    // ─── FOURNISSEURS ───────────────────────────────────────────

    /** GET /maintenance/fournisseurs */
    public function fournisseurs() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $resIds = $this->residencesAccessibles();
        $this->view('maintenance/fournisseurs', [
            'title'        => 'Fournisseurs - ' . APP_NAME,
            'showNavbar'   => true,
            'fournisseurs' => $this->model('MaintenanceStock')->getFournisseursAccessibles($resIds),
            'flash'        => $this->getFlash(),
        ], true);
    }

    /** Helper : résidences accessibles avec nom+ville */
    private function residencesAvecLibelles(): array {
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        if ($userRole === 'admin') {
            return $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville FROM coproprietees c JOIN user_residence ur ON ur.residence_id = c.id WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1 ORDER BY c.nom");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // ─────────────────────────────────────────────────────────────
    //  ÉQUIPE MAINTENANCE (manager only)
    // ─────────────────────────────────────────────────────────────

    /** GET /maintenance/equipe — vue de l'équipe technique groupée par résidence */
    public function equipe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $pdo = Database::getInstance()->getConnection();
        $userRole = $_SESSION['user_role'] ?? '';
        $userId = $this->getUserId();

        // Résidences visibles
        if ($userRole === 'admin') {
            $residences = $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville FROM coproprietees c JOIN user_residence ur ON ur.residence_id=c.id WHERE ur.user_id=? AND ur.statut='actif' AND c.actif=1 ORDER BY c.nom");
            $stmt->execute([$userId]);
            $residences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // Staff technique : technicien + technicien_chef + leurs résidences + spécialités
        $resIds = array_column($residences, 'id');
        $staff = [];
        if (!empty($resIds)) {
            $ph = implode(',', array_fill(0, count($resIds), '?'));
            $sql = "SELECT DISTINCT u.id, u.username, u.prenom, u.nom, u.email, u.telephone, u.role, u.actif, u.last_login, u.photo_profil,
                           GROUP_CONCAT(DISTINCT c.nom ORDER BY c.nom SEPARATOR ', ') AS residences,
                           (SELECT GROUP_CONCAT(s.nom ORDER BY s.ordre SEPARATOR '|') FROM user_specialites us2 JOIN specialites s ON s.id = us2.specialite_id WHERE us2.user_id = u.id) AS specialites,
                           (SELECT COUNT(*) FROM user_certifications WHERE user_id = u.id AND actif = 1) AS nb_certifs,
                           (SELECT COUNT(*) FROM user_certifications WHERE user_id = u.id AND actif = 1 AND date_expiration IS NOT NULL AND date_expiration <= DATE_ADD(CURDATE(), INTERVAL 3 MONTH) AND date_expiration >= CURDATE()) AS nb_certifs_expire_bientot
                    FROM users u
                    LEFT JOIN user_residence ur ON ur.user_id = u.id AND ur.statut = 'actif'
                    LEFT JOIN coproprietees c ON c.id = ur.residence_id
                    WHERE u.role IN ('technicien', 'technicien_chef')
                      AND u.actif = 1
                      AND ur.residence_id IN ($ph)
                    GROUP BY u.id
                    ORDER BY u.role DESC, u.nom, u.prenom";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($resIds);
            $staff = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->view('maintenance/equipe', [
            'title'      => 'Équipe maintenance technique - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'staff'      => $staff,
            'flash'      => $this->getFlash(),
        ], true);
    }

    // ─────────────────────────────────────────────────────────────
    //  COMPTABILITÉ MAINTENANCE (manager strict)
    // ─────────────────────────────────────────────────────────────

    /** GET /maintenance/comptabilite?annee=N */
    public function comptabilite() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER); // Strict : techniciens exclus

        $cModel = $this->model('MaintenanceComptabilite');
        $resIds = $this->residencesAccessibles();
        $annee = (int)($_GET['annee'] ?? date('Y'));

        $this->view('maintenance/comptabilite', [
            'title'           => 'Comptabilité maintenance ' . $annee . ' - ' . APP_NAME,
            'showNavbar'      => true,
            'annee'           => $annee,
            'totaux'          => $cModel->getTotauxAnnuels($resIds, $annee),
            'parSpecialite'   => $cModel->getVentilationParSpecialite($resIds, $annee),
            'parResidence'    => $cModel->getVentilationParResidence($resIds, $annee),
            'detailEcritures' => $cModel->getDetailEcritures($resIds, $annee, 200),
            'syntheseMensuelle' => $cModel->getSyntheseMensuelle($resIds, $annee),
            'flash'           => $this->getFlash(),
        ], true);
    }

    // ─────────────────────────────────────────────────────────────
    //  SECTION PISCINE
    // ─────────────────────────────────────────────────────────────

    private const UPLOAD_PISCINE_PV = '../uploads/maintenance/piscine_pv/';

    /** Vérifie l'accès piscine (manager OU spécialité piscine). */
    private function checkAccessPiscine(): bool {
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, self::ROLES_MANAGER, true)) return true;
        return $this->model('Specialite')->userHasSpecialite($this->getUserId(), 'piscine');
    }

    /** GET /maintenance/piscine?residence_id=N */
    public function piscine() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessPiscine()) {
            $this->setFlash('error', "Accès refusé : vous n'avez pas la spécialité piscine.");
            $this->redirect('maintenance/index'); return;
        }

        $piscineModel = $this->model('Piscine');
        $residences = $piscineModel->getResidencesAvecPiscine($this->getUserId(), $_SESSION['user_role'] ?? '');

        if (empty($residences)) {
            $this->view('maintenance/piscine', [
                'title'      => 'Piscine - ' . APP_NAME,
                'showNavbar' => true,
                'residences' => [],
                'residenceCourante' => null,
                'flash'      => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        // Vérifie que la résidence demandée est bien dans la liste accessible
        $residenceCourante = null;
        foreach ($residences as $r) {
            if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        }
        if (!$residenceCourante) {
            $this->setFlash('error', "Résidence non accessible.");
            $this->redirect('maintenance/piscine'); return;
        }

        $this->view('maintenance/piscine', [
            'title'             => 'Piscine ' . $residenceCourante['nom'] . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'derniereAnalyse'   => $piscineModel->getDerniereAnalyse($residenceId),
            'dernierArs'        => $piscineModel->getDernierControleArs($residenceId),
            'etatSaisonnier'    => $piscineModel->getEtatSaisonnier($residenceId),
            'alertes'           => $piscineModel->getAlertes($residenceId),
            'stats'             => $piscineModel->getStats($residenceId),
            'journal'           => $piscineModel->getJournal($residenceId, [], 50),
            'isManager'         => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/piscineEntree — enregistre une nouvelle entrée journal */
    public function piscineEntree() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        if (!$this->checkAccessPiscine()) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('maintenance/index'); return;
        }

        $residenceId = (int)($_POST['residence_id'] ?? 0);
        $typeEntree = $_POST['type_entree'] ?? '';

        try {
            if (!$residenceId || !in_array($typeEntree, Piscine::TYPES, true)) {
                throw new Exception('Résidence ou type invalide.');
            }
            $dateMesure = $_POST['date_mesure'] ?? date('Y-m-d H:i:s');

            // Upload PV si fourni
            $cheminPv = null;
            if (!empty($_FILES['fichier_pv']) && $_FILES['fichier_pv']['error'] === UPLOAD_ERR_OK) {
                $cheminPv = $this->handlePvUpload($_FILES['fichier_pv'], $residenceId);
            }

            $data = [
                'residence_id'        => $residenceId,
                'type_entree'         => $typeEntree,
                'date_mesure'         => $dateMesure,
                'ph'                  => !empty($_POST['ph']) ? (float)$_POST['ph'] : null,
                'chlore_libre_mg_l'   => !empty($_POST['chlore_libre_mg_l']) ? (float)$_POST['chlore_libre_mg_l'] : null,
                'chlore_total_mg_l'   => !empty($_POST['chlore_total_mg_l']) ? (float)$_POST['chlore_total_mg_l'] : null,
                'temperature'         => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
                'alcalinite_mg_l'     => !empty($_POST['alcalinite_mg_l']) ? (int)$_POST['alcalinite_mg_l'] : null,
                'stabilisant_mg_l'    => !empty($_POST['stabilisant_mg_l']) ? (int)$_POST['stabilisant_mg_l'] : null,
                'produit_utilise'     => trim($_POST['produit_utilise'] ?? '') ?: null,
                'quantite_produit_kg' => !empty($_POST['quantite_produit_kg']) ? (float)$_POST['quantite_produit_kg'] : null,
                'numero_pv'           => trim($_POST['numero_pv'] ?? '') ?: null,
                'conformite_ars'      => $_POST['conformite_ars'] ?? null,
                'fichier_pv'          => $cheminPv,
                'notes'               => trim($_POST['notes'] ?? '') ?: null,
                'mesure_par_user_id'  => $this->getUserId(),
            ];
            // conformite_ars valide uniquement pour controle_ars
            if ($typeEntree !== 'controle_ars') {
                $data['conformite_ars'] = null;
                $data['numero_pv'] = null;
            }

            $this->model('Piscine')->createEntree($data);
            $this->setFlash('success', 'Entrée enregistrée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/piscine?residence_id=' . $residenceId);
    }

    /** GET|POST /maintenance/piscineEntreeEdit/{id} — édition entrée journal piscine */
    public function piscineEntreeEdit($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessPiscine()) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('maintenance/index'); return;
        }

        $piscineModel = $this->model('Piscine');
        $entree = $piscineModel->findEntree((int)$id);
        if (!$entree) {
            $this->setFlash('error', 'Entrée introuvable.');
            $this->redirect('maintenance/piscine'); return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $typeEntree = $_POST['type_entree'] ?? '';
                if (!in_array($typeEntree, Piscine::TYPES, true)) {
                    throw new Exception('Type invalide.');
                }
                $data = [
                    'type_entree'         => $typeEntree,
                    'date_mesure'         => $_POST['date_mesure'] ?? date('Y-m-d H:i:s'),
                    'ph'                  => !empty($_POST['ph']) ? (float)$_POST['ph'] : null,
                    'chlore_libre_mg_l'   => !empty($_POST['chlore_libre_mg_l']) ? (float)$_POST['chlore_libre_mg_l'] : null,
                    'chlore_total_mg_l'   => !empty($_POST['chlore_total_mg_l']) ? (float)$_POST['chlore_total_mg_l'] : null,
                    'temperature'         => !empty($_POST['temperature']) ? (float)$_POST['temperature'] : null,
                    'alcalinite_mg_l'     => !empty($_POST['alcalinite_mg_l']) ? (int)$_POST['alcalinite_mg_l'] : null,
                    'stabilisant_mg_l'    => !empty($_POST['stabilisant_mg_l']) ? (int)$_POST['stabilisant_mg_l'] : null,
                    'produit_utilise'     => trim($_POST['produit_utilise'] ?? '') ?: null,
                    'quantite_produit_kg' => !empty($_POST['quantite_produit_kg']) ? (float)$_POST['quantite_produit_kg'] : null,
                    'numero_pv'           => trim($_POST['numero_pv'] ?? '') ?: null,
                    'conformite_ars'      => $_POST['conformite_ars'] ?? null,
                    'notes'               => trim($_POST['notes'] ?? '') ?: null,
                ];
                // conformite + numero_pv valides uniquement pour controle_ars
                if ($typeEntree !== 'controle_ars') {
                    $data['conformite_ars'] = null;
                    $data['numero_pv'] = null;
                }

                // Nouveau PV : remplace + supprime l'ancien
                if (!empty($_FILES['fichier_pv']) && $_FILES['fichier_pv']['error'] === UPLOAD_ERR_OK) {
                    $nouveauChemin = $this->handlePvUpload($_FILES['fichier_pv'], (int)$entree['residence_id']);
                    $data['fichier_pv'] = $nouveauChemin;
                    if (!empty($entree['fichier_pv'])) {
                        $ancien = self::UPLOAD_PISCINE_PV . ltrim($entree['fichier_pv'], '/');
                        if (file_exists($ancien)) @unlink($ancien);
                    }
                }
                // Suppression du PV existant
                elseif (!empty($_POST['supprimer_pv']) && !empty($entree['fichier_pv'])) {
                    $ancien = self::UPLOAD_PISCINE_PV . ltrim($entree['fichier_pv'], '/');
                    if (file_exists($ancien)) @unlink($ancien);
                    $pdo = Database::getInstance()->getConnection();
                    $pdo->prepare("UPDATE piscine_journal SET fichier_pv = NULL WHERE id = ?")->execute([(int)$id]);
                }

                $piscineModel->updateEntree((int)$id, $data);
                $this->setFlash('success', 'Entrée mise à jour.');
                $this->redirect('maintenance/piscine?residence_id=' . (int)$entree['residence_id']);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect('maintenance/piscineEntreeEdit/' . (int)$id);
                return;
            }
        }

        // GET → vue formulaire
        $this->view('maintenance/piscine_entree_form', [
            'title'      => 'Modifier entrée journal piscine - ' . APP_NAME,
            'showNavbar' => true,
            'entree'     => $entree,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/piscineEntreeDelete/{id} — manager only */
    public function piscineEntreeDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $entree = $this->model('Piscine')->findEntree((int)$id);
        if (!$entree) {
            $this->setFlash('error', 'Entrée introuvable.');
            $this->redirect('maintenance/piscine'); return;
        }
        $cheminPv = $this->model('Piscine')->deleteEntree((int)$id)['fichier_pv'] ?? null;
        if ($cheminPv) {
            $full = self::UPLOAD_PISCINE_PV . ltrim($cheminPv, '/');
            if (file_exists($full)) @unlink($full);
        }
        $this->setFlash('success', 'Entrée supprimée.');
        $this->redirect('maintenance/piscine?residence_id=' . (int)$entree['residence_id']);
    }

    /** GET /maintenance/piscinePv/{id} — stream sécurisé du PV ARS */
    public function piscinePv($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessPiscine()) {
            http_response_code(403); exit('Accès refusé');
        }

        $entree = $this->model('Piscine')->findEntree((int)$id);
        if (!$entree || empty($entree['fichier_pv'])) {
            http_response_code(404); exit('Fichier introuvable');
        }
        $chemin = self::UPLOAD_PISCINE_PV . ltrim($entree['fichier_pv'], '/');
        if (!file_exists($chemin)) {
            http_response_code(404); exit('Fichier physique manquant');
        }

        $mime = mime_content_type($chemin) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode(basename($entree['fichier_pv'])) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    private function handlePvUpload(array $file, int $residenceId): string {
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('PV trop volumineux (max 10 Mo).');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new Exception("Format non autorisé (.{$ext}). PDF / JPG / PNG acceptés.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw new Exception('Type MIME non autorisé.');
        }
        $dir = self::UPLOAD_PISCINE_PV . $residenceId . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('Impossible de créer le dossier de stockage.');
        }
        $nomSanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nomFichier = time() . '_' . bin2hex(random_bytes(4)) . '_' . substr($nomSanitize, 0, 80) . '.' . $ext;
        $cheminRelatif = $residenceId . '/' . $nomFichier;
        $cheminPhysique = self::UPLOAD_PISCINE_PV . $cheminRelatif;
        if (!move_uploaded_file($file['tmp_name'], $cheminPhysique)) {
            throw new Exception("Erreur d'enregistrement du PV.");
        }
        return $cheminRelatif;
    }

    // ─────────────────────────────────────────────────────────────
    //  SECTION ASCENSEUR
    // ─────────────────────────────────────────────────────────────

    private const UPLOAD_ASCENSEUR_PV = '../uploads/maintenance/ascenseur_pv/';

    /** Vérifie l'accès ascenseur (manager OU spécialité ascenseur). */
    private function checkAccessAscenseur(): bool {
        $role = $_SESSION['user_role'] ?? '';
        if (in_array($role, self::ROLES_MANAGER, true)) return true;
        return $this->model('Specialite')->userHasSpecialite($this->getUserId(), 'ascenseur');
    }

    /** GET /maintenance/ascenseurs?residence_id=N — liste des ascenseurs d'une résidence */
    public function ascenseurs() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessAscenseur()) {
            $this->setFlash('error', "Accès refusé : vous n'avez pas la spécialité ascenseur.");
            $this->redirect('maintenance/index'); return;
        }

        $ascModel = $this->model('Ascenseur');
        $residences = $ascModel->getResidencesAvecAscenseurs($this->getUserId(), $_SESSION['user_role'] ?? '');

        // Si admin et aucune résidence avec ascenseurs : on autorise l'admin à choisir N'IMPORTE QUELLE résidence
        // pour pouvoir créer le 1er ascenseur (qui activera le flag automatiquement)
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);
        $residencesPourCreation = [];
        if ($isManager) {
            $pdo = Database::getInstance()->getConnection();
            if (($_SESSION['user_role'] ?? '') === 'admin') {
                $residencesPourCreation = $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville FROM coproprietees c JOIN user_residence ur ON ur.residence_id=c.id WHERE ur.user_id=? AND ur.statut='actif' AND c.actif=1 ORDER BY c.nom");
                $stmt->execute([$this->getUserId()]);
                $residencesPourCreation = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        $residenceCourante = null;
        $ascenseursList = [];
        $echeances = [];

        if (!empty($residences)) {
            $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
            foreach ($residences as $r) {
                if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
            }
            if (!$residenceCourante) {
                $this->setFlash('error', "Résidence non accessible.");
                $this->redirect('maintenance/ascenseurs'); return;
            }
            $ascenseursList = $ascModel->getAscenseursParResidence($residenceId);
            $echeances = $ascModel->getEcheancesParAscenseur($residenceId);
        }

        $this->view('maintenance/ascenseurs', [
            'title'             => 'Ascenseurs - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residencesPourCreation' => $residencesPourCreation,
            'residenceCourante' => $residenceCourante,
            'ascenseurs'        => $ascenseursList,
            'echeances'         => $echeances,
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /maintenance/ascenseurForm/{id?} */
    public function ascenseurForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER); // Création/édition réservée aux chefs

        $ascModel = $this->model('Ascenseur');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id'              => (int)($_POST['residence_id'] ?? 0),
                    'nom'                       => trim($_POST['nom'] ?? ''),
                    'numero_serie'              => trim($_POST['numero_serie'] ?? '') ?: null,
                    'emplacement'               => trim($_POST['emplacement'] ?? '') ?: null,
                    'marque'                    => $_POST['marque'] ?? 'autre',
                    'modele'                    => trim($_POST['modele'] ?? '') ?: null,
                    'capacite_kg'               => $_POST['capacite_kg'] ?? null,
                    'capacite_personnes'        => $_POST['capacite_personnes'] ?? null,
                    'nombre_etages'             => $_POST['nombre_etages'] ?? null,
                    'date_mise_service'         => !empty($_POST['date_mise_service']) ? $_POST['date_mise_service'] : null,
                    'contrat_ascensoriste_nom'  => trim($_POST['contrat_ascensoriste_nom'] ?? '') ?: null,
                    'contrat_ascensoriste_tel'  => trim($_POST['contrat_ascensoriste_tel'] ?? '') ?: null,
                    'contrat_ascensoriste_email'=> trim($_POST['contrat_ascensoriste_email'] ?? '') ?: null,
                    'contrat_numero'            => trim($_POST['contrat_numero'] ?? '') ?: null,
                    'statut'                    => $_POST['statut'] ?? 'actif',
                    'notes'                     => trim($_POST['notes'] ?? '') ?: null,
                ];
                if ($data['nom'] === '' || !$data['residence_id']) {
                    throw new Exception('Nom et résidence sont obligatoires.');
                }

                if ($id) {
                    $ascModel->updateAscenseur((int)$id, $data);
                    $this->setFlash('success', 'Ascenseur mis à jour.');
                    $this->redirect('maintenance/ascenseurShow/' . (int)$id);
                } else {
                    $newId = $ascModel->createAscenseur($data);
                    $this->setFlash('success', 'Ascenseur créé.');
                    $this->redirect('maintenance/ascenseurShow/' . $newId);
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'maintenance/ascenseurForm/' . (int)$id : 'maintenance/ascenseurForm');
                return;
            }
        }

        // GET
        $asc = $id ? $ascModel->findAscenseur((int)$id) : null;
        if ($id && !$asc) {
            $this->setFlash('error', 'Ascenseur introuvable.');
            $this->redirect('maintenance/ascenseurs'); return;
        }

        // Résidences accessibles pour le sélecteur
        $pdo = Database::getInstance()->getConnection();
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            $residences = $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville FROM coproprietees c JOIN user_residence ur ON ur.residence_id=c.id WHERE ur.user_id=? AND ur.statut='actif' AND c.actif=1 ORDER BY c.nom");
            $stmt->execute([$this->getUserId()]);
            $residences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        $this->view('maintenance/ascenseur_form', [
            'title'          => ($id ? 'Modifier' : 'Nouvel') . ' ascenseur - ' . APP_NAME,
            'showNavbar'     => true,
            'ascenseur'      => $asc,
            'residences'     => $residences,
            'residencePreselectee' => (int)($_GET['residence_id'] ?? 0),
            'marques'        => Ascenseur::MARQUES,
            'statuts'        => Ascenseur::STATUTS,
            'flash'          => $this->getFlash(),
        ], true);
    }

    /** GET /maintenance/ascenseurShow/{id} — détail + journal */
    public function ascenseurShow($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessAscenseur()) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('maintenance/index'); return;
        }

        $ascModel = $this->model('Ascenseur');
        $asc = $ascModel->findAscenseur((int)$id);
        if (!$asc) {
            $this->setFlash('error', 'Ascenseur introuvable.');
            $this->redirect('maintenance/ascenseurs'); return;
        }

        // Vérifier que l'utilisateur a accès à la résidence
        if (($_SESSION['user_role'] ?? '') !== 'admin') {
            $pdo = Database::getInstance()->getConnection();
            $stmt = $pdo->prepare("SELECT 1 FROM user_residence WHERE user_id=? AND residence_id=? AND statut='actif'");
            $stmt->execute([$this->getUserId(), $asc['residence_id']]);
            if (!$stmt->fetchColumn()) {
                $this->setFlash('error', "Vous n'avez pas accès à cet ascenseur.");
                $this->redirect('maintenance/ascenseurs'); return;
            }
        }

        $this->view('maintenance/ascenseur_show', [
            'title'      => 'Ascenseur ' . $asc['nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'ascenseur'  => $asc,
            'journal'    => $ascModel->getJournal((int)$id),
            'stats'      => $ascModel->getStats((int)$id),
            'isManager'  => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/ascenseurEntree — ajout entrée journal */
    public function ascenseurEntree() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        if (!$this->checkAccessAscenseur()) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('maintenance/index'); return;
        }

        $ascenseurId = (int)($_POST['ascenseur_id'] ?? 0);
        $typeEntree = $_POST['type_entree'] ?? '';

        try {
            if (!$ascenseurId || !array_key_exists($typeEntree, Ascenseur::TYPES_JOURNAL)) {
                throw new Exception('Ascenseur ou type invalide.');
            }
            $cheminPv = null;
            if (!empty($_FILES['fichier_pv']) && $_FILES['fichier_pv']['error'] === UPLOAD_ERR_OK) {
                $cheminPv = $this->handleAscenseurPvUpload($_FILES['fichier_pv'], $ascenseurId);
            }

            $data = [
                'ascenseur_id'           => $ascenseurId,
                'type_entree'            => $typeEntree,
                'date_event'             => $_POST['date_event'] ?? date('Y-m-d H:i:s'),
                'organisme'              => trim($_POST['organisme'] ?? '') ?: null,
                'technicien_intervenant' => trim($_POST['technicien_intervenant'] ?? '') ?: null,
                'numero_pv'              => trim($_POST['numero_pv'] ?? '') ?: null,
                'conformite'             => $_POST['conformite'] ?? null,
                'fichier_pv'             => $cheminPv,
                'intervention_id'        => $_POST['intervention_id'] ?? null,
                'prochaine_echeance'     => !empty($_POST['prochaine_echeance']) ? $_POST['prochaine_echeance'] : null,
                'observations'           => trim($_POST['observations'] ?? '') ?: null,
                'cout'                   => $_POST['cout'] ?? null,
                'created_by'             => $this->getUserId(),
            ];
            $this->model('Ascenseur')->createEntree($data);
            $this->setFlash('success', 'Entrée enregistrée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('maintenance/ascenseurShow/' . $ascenseurId);
    }

    /** GET|POST /maintenance/ascenseurEntreeEdit/{id} — édition entrée journal */
    public function ascenseurEntreeEdit($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessAscenseur()) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('maintenance/index'); return;
        }

        $ascModel = $this->model('Ascenseur');
        $entree = $ascModel->findEntree((int)$id);
        if (!$entree) {
            $this->setFlash('error', 'Entrée introuvable.');
            $this->redirect('maintenance/ascenseurs'); return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                if (!array_key_exists($_POST['type_entree'] ?? '', Ascenseur::TYPES_JOURNAL)) {
                    throw new Exception('Type invalide.');
                }
                $data = [
                    'type_entree'            => $_POST['type_entree'],
                    'date_event'             => $_POST['date_event'] ?? date('Y-m-d H:i:s'),
                    'organisme'              => trim($_POST['organisme'] ?? '') ?: null,
                    'technicien_intervenant' => trim($_POST['technicien_intervenant'] ?? '') ?: null,
                    'numero_pv'              => trim($_POST['numero_pv'] ?? '') ?: null,
                    'conformite'             => $_POST['conformite'] ?? null,
                    'intervention_id'        => $_POST['intervention_id'] ?? null,
                    'prochaine_echeance'     => !empty($_POST['prochaine_echeance']) ? $_POST['prochaine_echeance'] : null,
                    'observations'           => trim($_POST['observations'] ?? '') ?: null,
                    'cout'                   => $_POST['cout'] ?? null,
                ];

                // Nouveau PV uploadé : on remplace + on supprime l'ancien
                if (!empty($_FILES['fichier_pv']) && $_FILES['fichier_pv']['error'] === UPLOAD_ERR_OK) {
                    $nouveauChemin = $this->handleAscenseurPvUpload($_FILES['fichier_pv'], (int)$entree['ascenseur_id']);
                    $data['fichier_pv'] = $nouveauChemin;
                    if (!empty($entree['fichier_pv'])) {
                        $ancien = self::UPLOAD_ASCENSEUR_PV . ltrim($entree['fichier_pv'], '/');
                        if (file_exists($ancien)) @unlink($ancien);
                    }
                }
                // Suppression du PV existant sans le remplacer (case à cocher)
                elseif (!empty($_POST['supprimer_pv']) && !empty($entree['fichier_pv'])) {
                    $ancien = self::UPLOAD_ASCENSEUR_PV . ltrim($entree['fichier_pv'], '/');
                    if (file_exists($ancien)) @unlink($ancien);
                    $data['fichier_pv'] = ''; // chaîne vide → SQL NULL via traitement
                    // Force NULL côté SQL
                    $pdo = Database::getInstance()->getConnection();
                    $pdo->prepare("UPDATE ascenseur_journal SET fichier_pv = NULL WHERE id = ?")->execute([(int)$id]);
                }

                $ascModel->updateEntree((int)$id, $data);
                $this->setFlash('success', 'Entrée mise à jour.');
                $this->redirect('maintenance/ascenseurShow/' . (int)$entree['ascenseur_id']);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect('maintenance/ascenseurEntreeEdit/' . (int)$id);
                return;
            }
        }

        // GET → vue formulaire
        $this->view('maintenance/ascenseur_entree_form', [
            'title'      => 'Modifier entrée journal - ' . APP_NAME,
            'showNavbar' => true,
            'entree'     => $entree,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /maintenance/ascenseurEntreeDelete/{id} — manager only */
    public function ascenseurEntreeDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $ascModel = $this->model('Ascenseur');
        $entree = $ascModel->findEntree((int)$id);
        if (!$entree) {
            $this->setFlash('error', 'Entrée introuvable.');
            $this->redirect('maintenance/ascenseurs'); return;
        }
        $deleted = $ascModel->deleteEntree((int)$id);
        if ($deleted && !empty($deleted['fichier_pv'])) {
            $full = self::UPLOAD_ASCENSEUR_PV . ltrim($deleted['fichier_pv'], '/');
            if (file_exists($full)) @unlink($full);
        }
        $this->setFlash('success', 'Entrée supprimée.');
        $this->redirect('maintenance/ascenseurShow/' . (int)$entree['ascenseur_id']);
    }

    /** POST /maintenance/ascenseurDelete/{id} — manager only */
    public function ascenseurDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $ascModel = $this->model('Ascenseur');
        $asc = $ascModel->findAscenseur((int)$id);
        if (!$asc) {
            $this->setFlash('error', 'Ascenseur introuvable.');
            $this->redirect('maintenance/ascenseurs'); return;
        }
        $ascModel->deleteAscenseur((int)$id);
        $this->setFlash('success', 'Ascenseur « ' . htmlspecialchars($asc['nom']) . ' » supprimé.');
        $this->redirect('maintenance/ascenseurs?residence_id=' . (int)$asc['residence_id']);
    }

    /** GET /maintenance/ascenseurPv/{id} — stream PV sécurisé */
    public function ascenseurPv($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        if (!$this->checkAccessAscenseur()) {
            http_response_code(403); exit('Accès refusé');
        }

        $entree = $this->model('Ascenseur')->findEntree((int)$id);
        if (!$entree || empty($entree['fichier_pv'])) {
            http_response_code(404); exit('Fichier introuvable');
        }
        $chemin = self::UPLOAD_ASCENSEUR_PV . ltrim($entree['fichier_pv'], '/');
        if (!file_exists($chemin)) {
            http_response_code(404); exit('Fichier physique manquant');
        }
        $mime = mime_content_type($chemin) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode(basename($entree['fichier_pv'])) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    private function handleAscenseurPvUpload(array $file, int $ascenseurId): string {
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('PV trop volumineux (max 10 Mo).');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new Exception("Format non autorisé (.{$ext}). PDF / JPG / PNG acceptés.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw new Exception('Type MIME non autorisé.');
        }
        $dir = self::UPLOAD_ASCENSEUR_PV . $ascenseurId . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('Impossible de créer le dossier de stockage.');
        }
        $nomSanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nomFichier = time() . '_' . bin2hex(random_bytes(4)) . '_' . substr($nomSanitize, 0, 80) . '.' . $ext;
        $cheminRelatif = $ascenseurId . '/' . $nomFichier;
        if (!move_uploaded_file($file['tmp_name'], self::UPLOAD_ASCENSEUR_PV . $cheminRelatif)) {
            throw new Exception("Erreur d'enregistrement du PV.");
        }
        return $cheminRelatif;
    }

    private function handleCertifUpload(array $file, int $userId): string {
        if ($file['size'] > 10 * 1024 * 1024) {
            throw new Exception('Fichier trop volumineux (max 10 Mo).');
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("Extension non autorisée (.{$ext}). PDF, JPG, PNG, WEBP acceptés.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        $allowedMimes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception("Type MIME non autorisé ({$mime}).");
        }

        $dir = self::UPLOAD_CERTIFS . $userId . '/';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception("Impossible de créer le dossier de stockage.");
        }
        $nomSanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nomSanitize = substr($nomSanitize, 0, 80);
        $nomFichier = time() . '_' . bin2hex(random_bytes(4)) . '_' . $nomSanitize . '.' . $ext;
        $cheminRelatif = $userId . '/' . $nomFichier;
        $cheminPhysique = self::UPLOAD_CERTIFS . $cheminRelatif;
        if (!move_uploaded_file($file['tmp_name'], $cheminPhysique)) {
            throw new Exception("Erreur lors de l'enregistrement du fichier.");
        }
        return $cheminRelatif;
    }
}
