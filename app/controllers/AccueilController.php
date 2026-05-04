<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Accueil
 * ====================================================================
 * Module Accueil de la résidence : gestion résidents (notes), réservations,
 * animations, hôtes temporaires, planning, messagerie groupée.
 *
 * Phase 1 : squelette + dashboard + résidents avec notes texte libre.
 */
class AccueilController extends Controller {

    private const ROLES_ALL     = ['admin', 'directeur_residence', 'accueil_manager', 'accueil_employe'];
    private const ROLES_MANAGER = ['admin', 'directeur_residence', 'accueil_manager'];

    /** Helper : résidences accessibles au user connecté */
    private function residences(): array {
        return $this->model('Accueil')->getResidencesAccessibles($this->getUserId(), $_SESSION['user_role'] ?? '');
    }

    private function residenceIds(): array {
        return array_column($this->residences(), 'id');
    }

    // ─── DASHBOARD ──────────────────────────────────────────────

    /** GET /accueil/index */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $stats = $accueilModel->getDashboardStats(array_column($residences, 'id'));

        $this->view('accueil/index', [
            'title'       => 'Accueil - ' . APP_NAME,
            'showNavbar'  => true,
            'residences'  => $residences,
            'stats'       => $stats,
            'isManager'   => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'       => $this->getFlash(),
        ], true);
    }

    // ─── RÉSIDENTS ──────────────────────────────────────────────

    /** GET /accueil/residents?residence_id=N */
    public function residents() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();

        if (empty($residences)) {
            $this->view('accueil/residents', [
                'title' => 'Résidents - ' . APP_NAME, 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'residents' => [],
                'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) {
            if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        }
        if (!$residenceCourante) {
            $this->setFlash('error', "Résidence non accessible.");
            $this->redirect('accueil/residents'); return;
        }

        $this->view('accueil/residents', [
            'title'             => 'Résidents - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'residents'         => $accueilModel->getResidentsParResidence($residenceId),
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET /accueil/residentNotes/{id} — fiche détaillée + historique notes */
    public function residentNotes($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residentId = (int)$id;

        if (!$accueilModel->residentEstAccessible($residentId, $this->residenceIds())) {
            $this->setFlash('error', "Vous n'avez pas accès à ce résident.");
            $this->redirect('accueil/residents'); return;
        }

        $resident = $accueilModel->findResident($residentId);

        $this->view('accueil/resident_notes', [
            'title'      => 'Notes — ' . $resident['prenom'] . ' ' . $resident['nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'resident'   => $resident,
            'notes'      => $accueilModel->getNotes($residentId),
            'isManager'  => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'userId'     => $this->getUserId(),
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/noteCreate */
    public function noteCreate() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $accueilModel = $this->model('Accueil');
        $residentId = (int)($_POST['resident_id'] ?? 0);
        $contenu = trim($_POST['contenu'] ?? '');

        if (!$residentId || $contenu === '') {
            $this->setFlash('error', 'Note vide ou résident invalide.');
            $this->redirect('accueil/residents'); return;
        }
        if (!$accueilModel->residentEstAccessible($residentId, $this->residenceIds())) {
            $this->setFlash('error', "Accès refusé.");
            $this->redirect('accueil/residents'); return;
        }

        $accueilModel->createNote($residentId, $contenu, $this->getUserId());
        $this->setFlash('success', 'Note ajoutée.');
        $this->redirect('accueil/residentNotes/' . $residentId);
    }

    // ─── SALLES COMMUNES (manager only pour CRUD, lecture pour tous) ──

    private const UPLOAD_SALLES = '../public/uploads/accueil/salles/';

    /** GET /accueil/salles?residence_id=N */
    public function salles() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('accueil/salles', [
                'title' => 'Salles communes', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'salles' => [],
                'isManager' => $isManager, 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/salles'); return;
        }

        $this->view('accueil/salles', [
            'title'             => 'Salles communes - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'salles'            => $accueilModel->getSalles($residenceId, false),
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /accueil/salleForm/{id?} (manager only) */
    public function salleForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $accueilModel = $this->model('Accueil');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id'       => (int)($_POST['residence_id'] ?? 0),
                    'nom'                => trim($_POST['nom'] ?? ''),
                    'description'        => trim($_POST['description'] ?? '') ?: null,
                    'capacite_personnes' => $_POST['capacite_personnes'] ?? null,
                    'equipements_inclus' => trim($_POST['equipements_inclus'] ?? '') ?: null,
                    'actif'              => isset($_POST['actif']) ? 1 : 0,
                ];
                if ($data['nom'] === '' || !$data['residence_id']) {
                    throw new Exception('Nom et résidence obligatoires.');
                }

                // Upload photo si fournie
                if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                    $data['photo'] = $this->handleSallePhotoUpload($_FILES['photo']);
                }

                if ($id) {
                    $accueilModel->updateSalle((int)$id, $data);
                    $this->setFlash('success', 'Salle mise à jour.');
                } else {
                    $newId = $accueilModel->createSalle($data);
                    $this->setFlash('success', 'Salle créée.');
                    $id = $newId;
                }
                $this->redirect('accueil/salles?residence_id=' . $data['residence_id']);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'accueil/salleForm/' . (int)$id : 'accueil/salleForm');
                return;
            }
        }

        $salle = $id ? $accueilModel->findSalle((int)$id) : null;
        if ($id && !$salle) {
            $this->setFlash('error', 'Salle introuvable.');
            $this->redirect('accueil/salles'); return;
        }

        $this->view('accueil/salle_form', [
            'title'      => ($id ? 'Modifier' : 'Nouvelle') . ' salle - ' . APP_NAME,
            'showNavbar' => true,
            'salle'      => $salle,
            'residences' => $this->residences(),
            'residencePreselectee' => (int)($_GET['residence_id'] ?? 0),
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/salleDelete/{id} (manager only) */
    public function salleDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $accueilModel = $this->model('Accueil');
        $deleted = $accueilModel->deleteSalle((int)$id);
        if ($deleted && !empty($deleted['photo'])) {
            $f = self::UPLOAD_SALLES . ltrim($deleted['photo'], '/');
            if (file_exists($f)) @unlink($f);
        }
        $this->setFlash('success', 'Salle supprimée.');
        $this->redirect('accueil/salles' . ($deleted ? '?residence_id=' . (int)$deleted['residence_id'] : ''));
    }

    private function handleSallePhotoUpload(array $file): string {
        if ($file['size'] > 5 * 1024 * 1024) throw new Exception('Photo trop volumineuse (max 5 Mo).');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            throw new Exception("Extension non autorisée (.{$ext}). JPG/PNG/WEBP acceptés.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            throw new Exception('Type MIME non autorisé.');
        }
        if (!is_dir(self::UPLOAD_SALLES) && !mkdir(self::UPLOAD_SALLES, 0755, true) && !is_dir(self::UPLOAD_SALLES)) {
            throw new Exception('Impossible de créer le dossier de stockage.');
        }
        $sanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nom = 'salle_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . substr($sanitize, 0, 60) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], self::UPLOAD_SALLES . $nom)) {
            throw new Exception("Erreur d'enregistrement.");
        }
        return 'uploads/accueil/salles/' . $nom;
    }

    // ─── ÉQUIPEMENTS PRÊTABLES ──────────────────────────────────

    /** GET /accueil/equipements?residence_id=N */
    public function equipements() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('accueil/equipements', [
                'title' => 'Équipements', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'equipements' => [],
                'isManager' => $isManager, 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/equipements'); return;
        }

        $this->view('accueil/equipements', [
            'title'             => 'Équipements prêtables - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'equipements'       => $accueilModel->getEquipements($residenceId, false),
            'types'             => Accueil::TYPES_EQUIPEMENT,
            'statuts'           => Accueil::STATUTS_EQUIPEMENT,
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /accueil/equipementForm/{id?} (manager only) */
    public function equipementForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $accueilModel = $this->model('Accueil');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id' => (int)($_POST['residence_id'] ?? 0),
                    'nom'          => trim($_POST['nom'] ?? ''),
                    'type'         => $_POST['type'] ?? 'autre',
                    'numero_serie' => trim($_POST['numero_serie'] ?? '') ?: null,
                    'statut'       => $_POST['statut'] ?? 'disponible',
                    'notes'        => trim($_POST['notes'] ?? '') ?: null,
                    'actif'        => isset($_POST['actif']) ? 1 : 0,
                ];
                if ($data['nom'] === '' || !$data['residence_id']) {
                    throw new Exception('Nom et résidence obligatoires.');
                }
                if ($id) {
                    $accueilModel->updateEquipement((int)$id, $data);
                    $this->setFlash('success', 'Équipement mis à jour.');
                } else {
                    $accueilModel->createEquipement($data);
                    $this->setFlash('success', 'Équipement créé.');
                }
                $this->redirect('accueil/equipements?residence_id=' . $data['residence_id']);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'accueil/equipementForm/' . (int)$id : 'accueil/equipementForm');
                return;
            }
        }

        $equipement = $id ? $accueilModel->findEquipement((int)$id) : null;
        if ($id && !$equipement) {
            $this->setFlash('error', 'Équipement introuvable.');
            $this->redirect('accueil/equipements'); return;
        }

        $this->view('accueil/equipement_form', [
            'title'      => ($id ? 'Modifier' : 'Nouvel') . ' équipement - ' . APP_NAME,
            'showNavbar' => true,
            'equipement' => $equipement,
            'residences' => $this->residences(),
            'residencePreselectee' => (int)($_GET['residence_id'] ?? 0),
            'types'      => Accueil::TYPES_EQUIPEMENT,
            'statuts'    => Accueil::STATUTS_EQUIPEMENT,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/equipementDelete/{id} (manager only) */
    public function equipementDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $accueilModel = $this->model('Accueil');
        $eq = $accueilModel->findEquipement((int)$id);
        if (!$eq) {
            $this->setFlash('error', 'Équipement introuvable.');
            $this->redirect('accueil/equipements'); return;
        }
        $accueilModel->deleteEquipement((int)$id);
        $this->setFlash('success', 'Équipement supprimé.');
        $this->redirect('accueil/equipements?residence_id=' . (int)$eq['residence_id']);
    }

    // ─── RÉSERVATIONS (multi-types) ─────────────────────────────

    /** GET /accueil/reservations?residence_id=N&statut=X&type=Y */
    public function reservations() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('accueil/reservations', [
                'title' => 'Réservations', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'reservations' => [],
                'isManager' => $isManager, 'filtres' => [], 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/reservations'); return;
        }

        $filtres = [
            'statut' => $_GET['statut'] ?? '',
            'type'   => $_GET['type']   ?? '',
        ];

        $this->view('accueil/reservations', [
            'title'             => 'Réservations - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'reservations'      => $accueilModel->getReservations($residenceId, $filtres),
            'isManager'         => $isManager,
            'filtres'           => $filtres,
            'userId'            => $this->getUserId(),
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /accueil/reservationForm/{id?} */
    public function reservationForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $type = $_POST['type_reservation'] ?? '';
                if (!in_array($type, Accueil::TYPES_RESERVATION, true)) {
                    throw new Exception('Type de réservation invalide.');
                }

                $residenceId = (int)($_POST['residence_id'] ?? 0);
                if (!in_array($residenceId, $this->residenceIds(), true)) {
                    throw new Exception('Résidence non accessible.');
                }

                $debut = trim($_POST['date_debut'] ?? '');
                $fin   = trim($_POST['date_fin'] ?? '');
                $titre = trim($_POST['titre'] ?? '');
                if ($titre === '' || $debut === '' || $fin === '') {
                    throw new Exception('Titre, date de début et de fin obligatoires.');
                }
                if (strtotime($fin) <= strtotime($debut)) {
                    throw new Exception('La date de fin doit être postérieure au début.');
                }

                $data = [
                    'residence_id'     => $residenceId,
                    'type_reservation' => $type,
                    'titre'            => $titre,
                    'description'      => trim($_POST['description'] ?? '') ?: null,
                    'date_debut'       => $debut,
                    'date_fin'         => $fin,
                    'salle_id'         => $type === 'salle' ? (int)($_POST['salle_id'] ?? 0) : null,
                    'equipement_id'    => $type === 'equipement' ? (int)($_POST['equipement_id'] ?? 0) : null,
                    'type_service'     => $type === 'service_personnel' ? trim($_POST['type_service'] ?? '') ?: null : null,
                    'resident_id'      => !empty($_POST['resident_id']) ? (int)$_POST['resident_id'] : null,
                    'hote_id'          => !empty($_POST['hote_id']) ? (int)$_POST['hote_id'] : null,
                    'notes'            => trim($_POST['notes'] ?? '') ?: null,
                    'created_by'       => $this->getUserId(),
                ];

                // Validation cible obligatoire selon le type
                if ($type === 'salle' && !$data['salle_id'])           throw new Exception('Sélectionner une salle.');
                if ($type === 'equipement' && !$data['equipement_id']) throw new Exception('Sélectionner un équipement.');
                if ($type === 'service_personnel' && !$data['type_service']) throw new Exception('Préciser le type de service.');
                if (!$data['resident_id'] && !$data['hote_id'])        throw new Exception('Sélectionner un résident ou un hôte.');

                // Anti-chevauchement (salles + équipements uniquement)
                if (in_array($type, ['salle','equipement'], true)) {
                    $cible = $type === 'salle' ? $data['salle_id'] : $data['equipement_id'];
                    if ($accueilModel->checkChevauchement($type, $cible, $debut, $fin, $id ? (int)$id : null)) {
                        throw new Exception('Conflit : la cible est déjà réservée sur cette plage horaire.');
                    }
                }

                if ($id) {
                    $accueilModel->updateReservation((int)$id, $data);
                    $this->setFlash('success', 'Réservation mise à jour.');
                } else {
                    $accueilModel->createReservation($data);
                    $this->setFlash('success', 'Réservation créée — en attente de validation.');
                }
                $this->redirect('accueil/reservations?residence_id=' . $residenceId);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'accueil/reservationForm/' . (int)$id : 'accueil/reservationForm');
                return;
            }
        }

        $reservation = $id ? $accueilModel->findReservation((int)$id) : null;
        if ($id && (!$reservation || !in_array((int)$reservation['residence_id'], $this->residenceIds(), true))) {
            $this->setFlash('error', 'Réservation introuvable ou non accessible.');
            $this->redirect('accueil/reservations'); return;
        }

        $residenceId = $reservation
            ? (int)$reservation['residence_id']
            : (int)($_GET['residence_id'] ?? ($this->residenceIds()[0] ?? 0));

        $this->view('accueil/reservation_form', [
            'title'       => ($id ? 'Modifier' : 'Nouvelle') . ' réservation - ' . APP_NAME,
            'showNavbar'  => true,
            'reservation' => $reservation,
            'residences'  => $this->residences(),
            'residenceId' => $residenceId,
            'salles'      => $residenceId ? $accueilModel->getSalles($residenceId, true) : [],
            'equipements' => $residenceId ? $accueilModel->getEquipements($residenceId, true) : [],
            'residents'   => $residenceId ? $accueilModel->getResidentsParResidence($residenceId) : [],
            'hotes'       => $residenceId ? $accueilModel->getHotesActuels($residenceId) : [],
            'typesService'=> Accueil::TYPES_SERVICE_PERSONNEL,
            'flash'       => $this->getFlash(),
        ], true);
    }

    /** GET /accueil/reservationShow/{id} */
    public function reservationShow($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $reservation = $accueilModel->findReservation((int)$id);

        if (!$reservation || !in_array((int)$reservation['residence_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Réservation introuvable ou non accessible.');
            $this->redirect('accueil/reservations'); return;
        }

        $this->view('accueil/reservation_show', [
            'title'       => 'Réservation - ' . APP_NAME,
            'showNavbar'  => true,
            'reservation' => $reservation,
            'isManager'   => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'       => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/reservationValider/{id} (manager only) */
    public function reservationValider($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        try {
            $this->model('Accueil')->validerReservation((int)$id, $this->getUserId());
            $this->setFlash('success', 'Réservation validée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('accueil/reservationShow/' . (int)$id);
    }

    /** POST /accueil/reservationRefuser/{id} (manager only) */
    public function reservationRefuser($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $motif = trim($_POST['motif_refus'] ?? '');
        if ($motif === '') {
            $this->setFlash('error', 'Motif de refus obligatoire.');
            $this->redirect('accueil/reservationShow/' . (int)$id); return;
        }
        $this->model('Accueil')->refuserReservation((int)$id, $this->getUserId(), $motif);
        $this->setFlash('success', 'Réservation refusée.');
        $this->redirect('accueil/reservationShow/' . (int)$id);
    }

    /** POST /accueil/reservationAnnuler/{id} */
    public function reservationAnnuler($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();
        $this->model('Accueil')->annulerReservation((int)$id);
        $this->setFlash('success', 'Réservation annulée.');
        $this->redirect('accueil/reservationShow/' . (int)$id);
    }

    /** POST /accueil/reservationRealiser/{id} (manager only) */
    public function reservationRealiser($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $this->model('Accueil')->realiserReservation((int)$id);
        $this->setFlash('success', 'Réservation marquée comme réalisée.');
        $this->redirect('accueil/reservationShow/' . (int)$id);
    }

    /** POST /accueil/reservationDelete/{id} (manager only) */
    public function reservationDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $r = $this->model('Accueil')->findReservation((int)$id);
        if (!$r || !in_array((int)$r['residence_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Réservation introuvable.');
            $this->redirect('accueil/reservations'); return;
        }
        $this->model('Accueil')->deleteReservation((int)$id);
        $this->setFlash('success', 'Réservation supprimée.');
        $this->redirect('accueil/reservations?residence_id=' . (int)$r['residence_id']);
    }

    // ─── ANIMATIONS ─────────────────────────────────────────────

    /** GET /accueil/animations?residence_id=N&periode=X */
    public function animations() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('accueil/animations', [
                'title' => 'Animations', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'animations' => [],
                'periode' => 'futures', 'isManager' => $isManager, 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/animations'); return;
        }

        $periode = $_GET['periode'] ?? 'futures';
        $dateMin = $periode === 'futures' ? date('Y-m-d 00:00:00') : null;
        $dateMax = null;

        $this->view('accueil/animations', [
            'title'             => 'Animations - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'animations'        => $accueilModel->getAnimations($residenceId, $dateMin, $dateMax),
            'periode'           => $periode,
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /accueil/animationForm/{id?} (manager only) */
    public function animationForm($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $accueilModel = $this->model('Accueil');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $residenceId = (int)($_POST['residence_id'] ?? 0);
                if (!in_array($residenceId, $this->residenceIds(), true)) {
                    throw new Exception('Résidence non accessible.');
                }
                $titre = trim($_POST['titre'] ?? '');
                $debut = trim($_POST['date_debut'] ?? '');
                $fin   = trim($_POST['date_fin'] ?? '');
                if ($titre === '' || $debut === '' || $fin === '') {
                    throw new Exception('Titre, début et fin obligatoires.');
                }
                if (strtotime($fin) <= strtotime($debut)) {
                    throw new Exception('La fin doit être postérieure au début.');
                }

                $data = [
                    'residence_id' => $residenceId,
                    'user_id'      => !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null,
                    'titre'        => $titre,
                    'description'  => trim($_POST['description'] ?? '') ?: null,
                    'date_debut'   => $debut,
                    'date_fin'     => $fin,
                    'notes'        => trim($_POST['notes'] ?? '') ?: null,
                ];

                if ($id) {
                    $accueilModel->updateAnimation((int)$id, $data);
                    $this->setFlash('success', 'Animation mise à jour.');
                    $this->redirect('accueil/animationShow/' . (int)$id);
                } else {
                    $newId = $accueilModel->createAnimation($data);
                    $this->setFlash('success', 'Animation créée.');
                    $this->redirect('accueil/animationShow/' . $newId);
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'accueil/animationForm/' . (int)$id : 'accueil/animationForm');
                return;
            }
        }

        $animation = $id ? $accueilModel->findAnimation((int)$id) : null;
        if ($id && (!$animation || !in_array((int)$animation['residence_id'], $this->residenceIds(), true))) {
            $this->setFlash('error', 'Animation introuvable ou non accessible.');
            $this->redirect('accueil/animations'); return;
        }

        $residenceId = $animation
            ? (int)$animation['residence_id']
            : (int)($_GET['residence_id'] ?? ($this->residenceIds()[0] ?? 0));

        $this->view('accueil/animation_form', [
            'title'      => ($id ? 'Modifier' : 'Nouvelle') . ' animation - ' . APP_NAME,
            'showNavbar' => true,
            'animation'  => $animation,
            'residences' => $this->residences(),
            'residenceId'=> $residenceId,
            'animateurs' => $residenceId ? $accueilModel->getAnimateursCandidats($residenceId) : [],
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** GET /accueil/animationShow/{id} — détail + inscrits + ajout */
    public function animationShow($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $animation = $accueilModel->findAnimation((int)$id);

        if (!$animation || !in_array((int)$animation['residence_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Animation introuvable ou non accessible.');
            $this->redirect('accueil/animations'); return;
        }

        $inscriptions = $accueilModel->getInscriptions((int)$id);
        $residentsAll = $accueilModel->getResidentsParResidence((int)$animation['residence_id']);

        // Filtrer les résidents non encore inscrits
        $inscritsIds = array_map(fn($i) => (int)$i['resident_id'], array_filter($inscriptions, fn($i) => $i['statut'] !== 'annule'));
        $residentsNonInscrits = array_filter($residentsAll, fn($r) => !in_array((int)$r['id'], $inscritsIds, true));

        $this->view('accueil/animation_show', [
            'title'                => 'Animation - ' . APP_NAME,
            'showNavbar'           => true,
            'animation'            => $animation,
            'inscriptions'         => $inscriptions,
            'residentsNonInscrits' => $residentsNonInscrits,
            'isManager'            => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'                => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/animationDelete/{id} (manager only) */
    public function animationDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $a = $this->model('Accueil')->findAnimation((int)$id);
        if (!$a || !in_array((int)$a['residence_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Animation introuvable.');
            $this->redirect('accueil/animations'); return;
        }
        $this->model('Accueil')->deleteAnimation((int)$id);
        $this->setFlash('success', 'Animation supprimée (toutes les inscriptions ont été retirées).');
        $this->redirect('accueil/animations?residence_id=' . (int)$a['residence_id']);
    }

    /** POST /accueil/inscriptionCreate */
    public function inscriptionCreate() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $accueilModel = $this->model('Accueil');
        $shiftId    = (int)($_POST['shift_id'] ?? 0);
        $residentId = (int)($_POST['resident_id'] ?? 0);
        $notes      = trim($_POST['notes'] ?? '') ?: null;

        if (!$shiftId || !$residentId) {
            $this->setFlash('error', 'Animation et résident obligatoires.');
            $this->redirect('accueil/animations'); return;
        }

        $animation = $accueilModel->findAnimation($shiftId);
        if (!$animation || !in_array((int)$animation['residence_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Animation non accessible.');
            $this->redirect('accueil/animations'); return;
        }
        if (!$accueilModel->residentEstAccessible($residentId, $this->residenceIds())) {
            $this->setFlash('error', 'Résident non accessible.');
            $this->redirect('accueil/animationShow/' . $shiftId); return;
        }

        try {
            $accueilModel->inscrire($shiftId, $residentId, $this->getUserId(), $notes);
            $this->setFlash('success', 'Résident inscrit.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('accueil/animationShow/' . $shiftId);
    }

    /** POST /accueil/inscriptionStatut/{id} — passer présent/absent/annulé */
    public function inscriptionStatut($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $accueilModel = $this->model('Accueil');
        $insc = $accueilModel->findInscription((int)$id);
        if (!$insc) {
            $this->setFlash('error', 'Inscription introuvable.');
            $this->redirect('accueil/animations'); return;
        }
        $statut = $_POST['statut'] ?? '';
        if (!in_array($statut, Accueil::STATUTS_INSCRIPTION, true)) {
            $this->setFlash('error', 'Statut invalide.');
            $this->redirect('accueil/animationShow/' . (int)$insc['shift_id']); return;
        }
        $accueilModel->setStatutInscription((int)$id, $statut);
        $this->setFlash('success', 'Statut mis à jour.');
        $this->redirect('accueil/animationShow/' . (int)$insc['shift_id']);
    }

    /** POST /accueil/inscriptionDelete/{id} (manager only) */
    public function inscriptionDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $accueilModel = $this->model('Accueil');
        $insc = $accueilModel->findInscription((int)$id);
        if (!$insc) {
            $this->setFlash('error', 'Inscription introuvable.');
            $this->redirect('accueil/animations'); return;
        }
        $shiftId = (int)$insc['shift_id'];
        $accueilModel->deleteInscription((int)$id);
        $this->setFlash('success', 'Inscription supprimée.');
        $this->redirect('accueil/animationShow/' . $shiftId);
    }

    // ─── PLANNING (TUI Calendar — double vue) ───────────────────

    /** GET /accueil/planning?residence_id=N */
    public function planning() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);

        if (empty($residences)) {
            $this->view('accueil/planning', [
                'title' => 'Planning', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null,
                'isManager' => $isManager, 'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/planning'); return;
        }

        $this->view('accueil/planning', [
            'title'             => 'Planning - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'isManager'         => $isManager,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** AJAX endpoint : /accueil/planningAjax/{action} */
    public function planningAjax($action = '') {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        header('Content-Type: application/json; charset=utf-8');

        $accueilModel = $this->model('Accueil');

        try {
            switch ($action) {
                case 'getEvents':
                    $residenceId = (int)($_GET['residence_id'] ?? 0);
                    $vue         = $_GET['vue'] ?? 'residents';
                    $debut       = $_GET['start'] ?? date('Y-m-d 00:00:00');
                    $fin         = $_GET['end']   ?? date('Y-m-d 23:59:59', strtotime('+30 days'));

                    if (!in_array($residenceId, $this->residenceIds(), true)) {
                        echo json_encode(['error' => 'Résidence non accessible']); return;
                    }

                    $events = [];
                    if ($vue === 'residents' || $vue === 'tout') {
                        $events = array_merge($events, $accueilModel->getCalendarEventsResidents($residenceId, $debut, $fin));
                        $events = array_merge($events, $accueilModel->getCalendarEventsHotes($residenceId, $debut, $fin));
                    }
                    if ($vue === 'staff' || $vue === 'tout') {
                        $events = array_merge($events, $accueilModel->getCalendarEventsStaff($residenceId, $debut, $fin));
                    }
                    echo json_encode($events);
                    return;

                case 'moveAnimation':
                    $this->verifyCsrf();
                    if (!in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Réservé aux managers']); return;
                    }
                    $shiftId = (int)($_POST['id'] ?? 0);
                    $debut   = $_POST['date_debut'] ?? '';
                    $fin     = $_POST['date_fin']   ?? '';
                    if (!$shiftId || !$debut || !$fin) {
                        echo json_encode(['error' => 'Paramètres incomplets']); return;
                    }
                    $animation = $accueilModel->findAnimation($shiftId);
                    if (!$animation || !in_array((int)$animation['residence_id'], $this->residenceIds(), true)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Animation non accessible']); return;
                    }
                    $accueilModel->moveAnimation($shiftId, $debut, $fin);
                    echo json_encode(['success' => true]);
                    return;

                default:
                    http_response_code(400);
                    echo json_encode(['error' => 'Action inconnue']);
                    return;
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    // ─── ÉQUIPE ─────────────────────────────────────────────────

    /** GET /accueil/equipe?residence_id=N (manager only) */
    public function equipe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();

        if (empty($residences)) {
            $this->view('accueil/equipe', [
                'title' => 'Équipe Accueil', 'showNavbar' => true,
                'residences' => [], 'residenceCourante' => null, 'equipe' => [],
                'flash' => $this->getFlash(),
            ], true);
            return;
        }

        $residenceId = (int)($_GET['residence_id'] ?? $residences[0]['id']);
        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/equipe'); return;
        }

        $this->view('accueil/equipe', [
            'title'             => 'Équipe Accueil - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'equipe'            => $accueilModel->getEquipeAccueil($residenceId),
            'flash'             => $this->getFlash(),
        ], true);
    }

    // ─── MESSAGERIE GROUPÉE ─────────────────────────────────────

    /** GET /accueil/messageGroupe?residence_id=N&ag_id=X (manager only) */
    public function messageGroupe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $accueilModel = $this->model('Accueil');
        $residences = $this->residences();

        if (empty($residences)) {
            $this->setFlash('error', 'Aucune résidence accessible.');
            $this->redirect('accueil/index'); return;
        }

        // Si ag_id fourni : pré-remplir le formulaire pour convocation AG
        $agContext = null;
        if (!empty($_GET['ag_id'])) {
            $agModel = $this->model('Assemblee');
            $ag = $agModel->findAG((int)$_GET['ag_id']);
            if ($ag && in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
                $agContext = $ag;
            }
        }

        // Si AG fournie, on force la résidence à celle de l'AG
        $residenceId = $agContext
            ? (int)$agContext['copropriete_id']
            : (int)($_GET['residence_id'] ?? $residences[0]['id']);

        $residenceCourante = null;
        foreach ($residences as $r) if ((int)$r['id'] === $residenceId) { $residenceCourante = $r; break; }
        if (!$residenceCourante) {
            $this->setFlash('error', 'Résidence non accessible.');
            $this->redirect('accueil/messageGroupe'); return;
        }

        // Préfill convocation AG : sujet + corps + IDs propriétaires à pré-cocher
        $prefill = ['sujet' => '', 'contenu' => '', 'destinataires_precoches' => [], 'tab_actif' => 'tabRes'];
        if ($agContext) {
            $typeLib = $agContext['type'] === 'extraordinaire' ? 'extraordinaire (AGE)' : 'ordinaire (AGO)';
            $dateLib = date('d/m/Y à H\hi', strtotime($agContext['date_ag']));
            $prefill['sujet']   = "Convocation Assemblée Générale $typeLib du $dateLib";
            $prefill['contenu'] =
                "Madame, Monsieur,\n\n" .
                "Vous êtes convoqué(e) à l'Assemblée Générale $typeLib qui se tiendra le " . $dateLib .
                ($agContext['lieu'] ? " — " . $agContext['lieu'] : '') .
                " (mode : " . $agContext['mode'] . ").\n\n" .
                ($agContext['ordre_du_jour']
                    ? "Ordre du jour :\n" . $agContext['ordre_du_jour'] . "\n\n"
                    : '') .
                "Vous trouverez la convocation officielle et l'ordre du jour détaillé en pièce jointe " .
                "(à télécharger depuis votre espace propriétaire — rubrique « Mes AG »).\n\n" .
                "Cordialement,\nLa direction de la résidence";
            // Pré-cocher tous les propriétaires de la résidence
            $dest = $accueilModel->getDestinatairesPossibles($residenceId);
            $prefill['destinataires_precoches'] = array_column($dest['proprietaires'], 'id');
            $prefill['tab_actif'] = 'tabProp';
        }

        $this->view('accueil/message_groupe', [
            'title'             => 'Message groupé - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'residenceCourante' => $residenceCourante,
            'destinataires'     => $accueilModel->getDestinatairesPossibles($residenceId),
            'agContext'         => $agContext,
            'prefill'           => $prefill,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** POST /accueil/noteDelete/{id} — auteur ou manager uniquement */
    public function noteDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $this->verifyCsrf();

        $accueilModel = $this->model('Accueil');
        $note = $accueilModel->findNote((int)$id);
        if (!$note) {
            $this->setFlash('error', 'Note introuvable.');
            $this->redirect('accueil/residents'); return;
        }

        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true);
        $isAuteur = (int)$note['created_by'] === $this->getUserId();
        if (!$isManager && !$isAuteur) {
            $this->setFlash('error', "Vous ne pouvez supprimer que vos propres notes.");
            $this->redirect('accueil/residentNotes/' . (int)$note['resident_id']); return;
        }

        $accueilModel->deleteNote((int)$id);
        $this->setFlash('success', 'Note supprimée.');
        $this->redirect('accueil/residentNotes/' . (int)$note['resident_id']);
    }
}
