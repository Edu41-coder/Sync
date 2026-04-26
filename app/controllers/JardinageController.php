<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Jardinage
 * ====================================================================
 * Module jardinage + apiculture (ruches) pour les résidences seniors.
 *
 * Phase 1 : Dashboard, équipe, planning, espaces, produits, inventaire, ruches (lecture)
 * Phase 2 : commandes fournisseurs, fournisseurs, comptabilité, ruches UI complète
 *
 * Rôles : jardinier_manager (chef), jardinier_employe (de base)
 * La comptabilité (phase 2) sera strictement réservée à ROLES_MANAGER.
 */

class JardinageController extends Controller {

    private const ROLES_ALL     = ['admin', 'directeur_residence', 'jardinier_manager', 'jardinier_employe'];
    private const ROLES_MANAGER = ['admin', 'directeur_residence', 'jardinier_manager'];

    /**
     * Résidences visibles pour l'utilisateur connecté
     */
    private function getUserResidences(): array {
        $model = $this->model('Jardinage');
        if (($_SESSION['user_role'] ?? '') === 'admin') {
            return $model->getAllResidencesSimple();
        }
        return $model->getResidencesByUser((int)$_SESSION['user_id']);
    }

    // =================================================================
    //  DASHBOARD
    // =================================================================

    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $model = $this->model('Jardinage');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;

        $isManager = in_array($_SESSION['user_role'], self::ROLES_MANAGER);

        // IDs des résidences avec ruches pour limiter l'alerte traitements aux bons périmètres
        $idsRuches = array_column(array_filter($residences, fn($r) => !empty($r['ruches'])), 'id');
        $idsRuchesFiltres = $selectedResidence
            ? (in_array($selectedResidence, $idsRuches) ? [$selectedResidence] : [])
            : $idsRuches;

        $this->view('jardinage/dashboard', [
            'title'             => 'Jardinage - ' . APP_NAME,
            'showNavbar'        => true,
            'userRole'          => $_SESSION['user_role'],
            'isManager'         => $isManager,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'stats'             => $model->getDashboardStats($filteredIds),
            'alertesStock'      => $isManager ? $model->getAlertesStock($filteredIds) : [],
            'ruchesSansVisite'  => $model->getRuchesSansVisite($filteredIds, 30),
            'alertesTraitements' => $model->getAlertesTraitements($idsRuchesFiltres),
            'mouvements'        => $model->getMouvementsRecents($filteredIds),
            'contactsRapides'   => $model->getContactsRapides($filteredIds, (int)$_SESSION['user_id']),
            'hasRuches'         => count($idsRuches) > 0,
            'flash'             => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  ÉQUIPE (manager uniquement)
    // =================================================================

    public function equipe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Jardinage');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $this->view('jardinage/equipe', [
            'title'      => 'Équipe Jardinage - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'staff'      => $model->getStaffByResidences($residenceIds),
            'flash'      => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  PLANNING (TUI Calendar — catégorie jardinage)
    // =================================================================

    public function planning() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $model = $this->model('Jardinage');
        $planningModel = $this->model('Planning');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $staff = $model->getStaffByResidences($residenceIds);

        $this->view('jardinage/planning', [
            'title'      => 'Planning Jardinage - ' . APP_NAME,
            'showNavbar' => true,
            'userRole'   => $_SESSION['user_role'],
            'userId'     => (int)$_SESSION['user_id'],
            'residences' => $residences,
            'staff'      => $staff,
            'categories' => $planningModel->getCategories(),
            'canManage'  => in_array($_SESSION['user_role'], self::ROLES_MANAGER),
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * AJAX planning jardinage (filtré sur rôles jardinier_*)
     */
    public function planningAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        header('Content-Type: application/json; charset=utf-8');

        $planningModel = $this->model('Planning');
        $jardinModel = $this->model('Jardinage');
        $residenceIds = $jardinModel->getResidenceIdsByUser((int)$_SESSION['user_id']);
        if ($_SESSION['user_role'] === 'admin') $residenceIds = null;

        try {
            switch ($action) {
                case 'getEvents':
                    $start = $_GET['start'] ?? date('Y-m-01');
                    $end = $_GET['end'] ?? date('Y-m-t');
                    $residenceId = (int)($_GET['residence_id'] ?? 0);
                    $filterUserId = (int)($_GET['user_id'] ?? 0);

                    $shifts = $planningModel->getShifts($start, $end, $residenceId, $filterUserId);

                    $jardinRoles = ['jardinier_manager', 'jardinier_employe'];
                    $shifts = array_filter($shifts, function($s) use ($jardinRoles, $residenceIds) {
                        $roleOk = in_array($s['employe_role'], $jardinRoles);
                        $residenceOk = $residenceIds === null || in_array($s['residence_id'], $residenceIds);
                        return $roleOk && $residenceOk;
                    });

                    $events = array_values(array_map(function($s) {
                        return [
                            'id' => $s['id'],
                            'calendarId' => $s['residence_id'],
                            'title' => $s['employe_prenom'] . ' ' . $s['employe_nom'] . ' — ' . $s['titre'],
                            'start' => $s['date_debut'],
                            'end' => $s['date_fin'],
                            'isAllDay' => (bool)$s['journee_entiere'],
                            'calendarColor' => $s['couleur'] ?? '#198754',
                            'categoryColor' => $s['bg_couleur'] ?? '#d1e7dd',
                            'categoryTextColor' => '#333',
                            'body' => $s['description'] ?? '',
                            'raw' => [
                                'userId' => $s['user_id'], 'residenceId' => $s['residence_id'],
                                'categoryId' => $s['category_id'], 'typeShift' => $s['type_shift'],
                                'typeHeures' => $s['type_heures'], 'statut' => $s['statut'],
                                'heures' => $s['heures_calculees'],
                                'employeNom' => $s['employe_prenom'] . ' ' . $s['employe_nom'],
                                'residenceNom' => $s['residence_nom'],
                            ]
                        ];
                    }, $shifts));
                    echo json_encode($events);
                    break;

                case 'save':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) { echo json_encode(['success' => false, 'message' => 'Données invalides']); break; }
                    $saveUserId = (int)($input['userId'] ?? 0);
                    $saveResidenceId = (int)($input['residenceId'] ?? 0);
                    $saveTitre = trim($input['title'] ?? '');
                    if (!$saveUserId || !$saveResidenceId || !$saveTitre) {
                        echo json_encode(['success' => false, 'message' => 'Champs requis manquants']); break;
                    }
                    $savedId = $planningModel->saveShift([
                        'id' => $input['id'] ?? null,
                        'userId' => $saveUserId,
                        'residenceId' => $saveResidenceId,
                        'categoryId' => (int)($input['categoryId'] ?? 0) ?: 6, // défaut jardinage
                        'title' => $saveTitre,
                        'start' => $input['start'],
                        'end' => $input['end'],
                        'isAllDay' => $input['isAllDay'] ?? false,
                        'typeShift' => $input['typeShift'] ?? 'travail',
                        'typeHeures' => $input['typeHeures'] ?? 'normales',
                        'description' => trim($input['description'] ?? ''),
                        'notes' => trim($input['notes'] ?? ''),
                    ]);
                    echo json_encode(['success' => true, 'message' => ($input['id'] ?? null) ? 'Shift modifié' : 'Shift créé', 'id' => $savedId]);
                    break;

                case 'move':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input || empty($input['id'])) { echo json_encode(['success' => false, 'message' => 'Données invalides']); break; }
                    $planningModel->moveShift((int)$input['id'], $input['start'], $input['end']);
                    echo json_encode(['success' => true, 'message' => 'Shift déplacé']);
                    break;

                case 'delete':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    $deleteId = (int)($input['id'] ?? 0);
                    if (!$deleteId) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
                    $planningModel->deleteShift($deleteId);
                    echo json_encode(['success' => true, 'message' => 'Shift supprimé']);
                    break;

                default:
                    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // =================================================================
    //  ESPACES JARDIN (CRUD manager)
    // =================================================================

    public function espaces($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL); // lecture pour tous
        $model = $this->model('Jardinage');

        switch ($action) {
            case 'create':
                $this->requireRole(self::ROLES_MANAGER);
                $this->verifyCsrf();
                try {
                    $newId = $model->createEspace($_POST);
                    // Upload photo si fournie
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $path = $this->handleEspacePhotoUpload($_FILES['photo'], $newId);
                        $model->updateEspacePhoto($newId, $path);
                    }
                    $this->setFlash('success', 'Espace créé');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/espaces?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'update':
                $this->requireRole(self::ROLES_MANAGER);
                $this->verifyCsrf();
                try {
                    $model->updateEspace((int)$id, $_POST);
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $old = $model->getEspacePhoto((int)$id);
                        $path = $this->handleEspacePhotoUpload($_FILES['photo'], (int)$id);
                        $model->updateEspacePhoto((int)$id, $path);
                        if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                    }
                    $this->setFlash('success', 'Espace modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/espaces?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'photoDelete':
                $this->requireRole(self::ROLES_MANAGER);
                $old = $model->getEspacePhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->updateEspacePhoto((int)$id, null);
                $this->setFlash('success', 'Photo supprimée');
                $this->redirect('jardinage/espaces?residence_id=' . ($_GET['residence_id'] ?? ''));
                return;

            case 'delete':
                $this->requireRole(self::ROLES_MANAGER);
                // Supprimer aussi le fichier photo associé
                $old = $model->getEspacePhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->deleteEspace((int)$id);
                $this->setFlash('success', 'Espace désactivé');
                $this->redirect('jardinage/espaces?residence_id=' . ($_GET['residence_id'] ?? ''));
                return;

            case 'taches':
                return $this->tachesEspace($model, (int)$id);

            default:
                return $this->listEspaces($model);
        }
    }

    private function handleEspacePhotoUpload(array $file, int $espaceId): string {
        return $this->handlePhotoUpload($file, 'espaces', 'espace', $espaceId);
    }

    private function handleRuchePhotoUpload(array $file, int $rucheId): string {
        return $this->handlePhotoUpload($file, 'ruches', 'ruche', $rucheId);
    }

    private function handleProduitPhotoUpload(array $file, int $produitId): string {
        return $this->handlePhotoUpload($file, 'produits', 'produit', $produitId);
    }

    /**
     * Upload générique d'une photo jardinage.
     * Whitelist MIME (jpeg/png/webp), 5 Mo max, vérification image réelle (anti-polyglot).
     * @throws Exception si validation ou move échoue.
     */
    private function handlePhotoUpload(array $file, string $subdir, string $prefix, int $id): string {
        $allowedExts  = ['jpg', 'jpeg', 'png', 'webp'];
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts, true)) {
            throw new Exception("Format non autorisé (acceptés : JPG, PNG, WEBP)");
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            throw new Exception("Fichier trop volumineux (max 5 Mo)");
        }
        $mime = function_exists('mime_content_type') ? mime_content_type($file['tmp_name']) : null;
        if ($mime && !in_array($mime, $allowedMimes, true)) {
            throw new Exception("Type MIME non autorisé : $mime");
        }
        if (!@getimagesize($file['tmp_name'])) {
            throw new Exception("Le fichier n'est pas une image valide");
        }

        $uploadDir = '../public/uploads/jardinage/' . $subdir . '/';
        if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0755, true); }

        $filename = $prefix . '_' . $id . '_' . time() . '.' . $ext;
        $filepath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception("Échec de l'enregistrement du fichier");
        }
        return 'uploads/jardinage/' . $subdir . '/' . $filename;
    }

    private function listEspaces(Jardinage $model) {
        $residences = $this->getUserResidences();
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $isManager = in_array($_SESSION['user_role'], self::ROLES_MANAGER);

        $this->view('jardinage/espaces', [
            'title'             => 'Espaces Jardin - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'espaces'           => $selectedResidence ? $model->getEspaces($selectedResidence, false) : [],
            'isManager'         => $isManager,
            'flash'             => $this->getFlash()
        ], true);
    }

    private function tachesEspace(Jardinage $model, int $espaceId) {
        $espace = $model->getEspace($espaceId);
        if (!$espace) { $this->setFlash('error', 'Espace introuvable'); $this->redirect('jardinage/espaces'); return; }

        // POST = création ou suppression d'une tâche
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->requireRole(self::ROLES_MANAGER);
            $this->verifyCsrf();
            try {
                if (!empty($_POST['delete_tache_id'])) {
                    $model->deleteTache((int)$_POST['delete_tache_id']);
                    $this->setFlash('success', 'Tâche supprimée');
                } else {
                    $model->createTache(array_merge($_POST, ['espace_id' => $espaceId]));
                    $this->setFlash('success', 'Tâche ajoutée');
                }
            } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
            $this->redirect('jardinage/espaces/taches/' . $espaceId);
            return;
        }

        $isManager = in_array($_SESSION['user_role'], self::ROLES_MANAGER);

        $this->view('jardinage/espace_taches', [
            'title'      => 'Tâches — ' . $espace['nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'espace'     => $espace,
            'taches'     => $model->getTachesByEspace($espaceId),
            'isManager'  => $isManager,
            'flash'      => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  PRODUITS & OUTILS (CRUD manager)
    // =================================================================

    public function produits($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Jardinage');

        switch ($action) {
            case 'create':
                $this->verifyCsrf();
                try {
                    $newId = $model->createProduit($_POST);
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $path = $this->handleProduitPhotoUpload($_FILES['photo'], $newId);
                        $model->updateProduitPhoto($newId, $path);
                    }
                    $this->setFlash('success', 'Produit créé');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/produits');
                return;

            case 'edit':
                $produit = $model->getProduit((int)$id);
                if (!$produit) { $this->redirect('jardinage/produits'); return; }
                $fm = new Fournisseur();
                $this->view('jardinage/produit_edit', [
                    'title'        => 'Modifier produit - ' . APP_NAME,
                    'showNavbar'   => true,
                    'produit'      => $produit,
                    'fournisseurs' => $model->getFournisseursList(),
                    'produitFournisseurs'     => $fm->getFournisseursDuProduit('jardinage', (int)$id),
                    'fournisseursDisponibles' => $fm->getAll('jardinage', null, true),
                    'flash'        => $this->getFlash()
                ], true);
                return;

            case 'update':
                $this->verifyCsrf();
                try {
                    $model->updateProduit((int)$id, $_POST);
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $old = $model->getProduitPhoto((int)$id);
                        $path = $this->handleProduitPhotoUpload($_FILES['photo'], (int)$id);
                        $model->updateProduitPhoto((int)$id, $path);
                        if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                    }
                    $this->setFlash('success', 'Produit modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/produits');
                return;

            case 'photoDelete':
                $old = $model->getProduitPhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->updateProduitPhoto((int)$id, null);
                $this->setFlash('success', 'Photo supprimée');
                $this->redirect('jardinage/produits/edit/' . (int)$id);
                return;

            case 'delete':
                $old = $model->getProduitPhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->updateProduitPhoto((int)$id, null);
                $model->deleteProduit((int)$id);
                $this->setFlash('success', 'Produit désactivé');
                $this->redirect('jardinage/produits');
                return;

            default:
                $fm = new Fournisseur();
                $this->view('jardinage/produits', [
                    'title'        => 'Produits Jardinage - ' . APP_NAME,
                    'showNavbar'   => true,
                    'produits'     => $model->getAllProduits($_GET['categorie'] ?? null, $_GET['type'] ?? null),
                    'fournisseurs' => $model->getFournisseursList(),
                    'produitFournisseurs'     => [],
                    'fournisseursDisponibles' => $fm->getAll('jardinage', null, true),
                    'filtreCategorie' => $_GET['categorie'] ?? null,
                    'filtreType'      => $_GET['type'] ?? null,
                    'flash'        => $this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  INVENTAIRE (tous — mouvement permis à tous)
    // =================================================================

    public function inventaire($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $model = $this->model('Jardinage');

        switch ($action) {
            case 'ajouter':
                $this->requireRole(self::ROLES_MANAGER);
                $this->verifyCsrf();
                try {
                    $model->addToInventaire(
                        (int)$_POST['produit_id'],
                        (int)$_POST['residence_id'],
                        (float)($_POST['seuil_alerte'] ?? 0),
                        $_POST['emplacement'] ?? null
                    );
                    $this->setFlash('success', 'Produit ajouté à l\'inventaire');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/inventaire?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'mouvement':
                $this->verifyCsrf();
                try {
                    $model->mouvementStock(
                        (int)$id,
                        $_POST['type_mouvement'] ?? 'sortie',
                        (float)($_POST['quantite'] ?? 0),
                        $_POST['motif'] ?? 'usage',
                        !empty($_POST['espace_id']) ? (int)$_POST['espace_id'] : null,
                        $_POST['notes'] ?? null,
                        (int)$_SESSION['user_id']
                    );
                    $this->setFlash('success', 'Mouvement enregistré');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/inventaire?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'historique':
                $item = $model->getInventaireItem((int)$id);
                if (!$item) { $this->redirect('jardinage/inventaire'); return; }
                $this->view('jardinage/inventaire_historique', [
                    'title'      => 'Historique stock - ' . APP_NAME,
                    'showNavbar' => true,
                    'item'       => $item,
                    'mouvements' => $model->getMouvements((int)$id, 100),
                    'flash'      => $this->getFlash()
                ], true);
                return;

            default:
                $residences = $this->getUserResidences();
                $sel = (int)($_GET['residence_id'] ?? 0);
                if (!$sel && count($residences) === 1) $sel = $residences[0]['id'];

                $inv = $sel ? $model->getInventaire($sel, $_GET['categorie'] ?? null, isset($_GET['alertes'])) : [];
                $alertes = count(array_filter($inv, fn($i) => $i['seuil_alerte'] > 0 && $i['quantite_actuelle'] <= $i['seuil_alerte']));

                $this->view('jardinage/inventaire', [
                    'title'             => 'Inventaire Jardinage - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $sel,
                    'inventaire'        => $inv,
                    'alertes'           => $alertes,
                    'produitsHors'      => $sel ? $model->getProduitsHorsInventaire($sel) : [],
                    'espaces'           => $sel ? $model->getEspaces($sel) : [],
                    'isManager'         => in_array($_SESSION['user_role'], self::ROLES_MANAGER),
                    'flash'             => $this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  CALENDRIER TRAITEMENTS APICOLES (MANAGER)
    // =================================================================

    public function traitements($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Jardinage');

        switch ($action) {
            case 'create':
                $this->verifyCsrf();
                try {
                    $model->createTraitementCalendrier($_POST);
                    $this->setFlash('success', 'Traitement ajouté au calendrier');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/traitements');
                return;

            case 'update':
                $this->verifyCsrf();
                try {
                    $model->updateTraitementCalendrier((int)$id, $_POST);
                    $this->setFlash('success', 'Traitement modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/traitements');
                return;

            case 'delete':
                $model->deleteTraitementCalendrier((int)$id);
                $this->setFlash('success', 'Traitement désactivé');
                $this->redirect('jardinage/traitements');
                return;

            default:
                $residences = $this->getUserResidences();
                $residencesRuches = array_values(array_filter($residences, fn($r) => !empty($r['ruches'])));
                $residenceIds = array_column($residencesRuches, 'id');

                $this->view('jardinage/traitements', [
                    'title'       => 'Calendrier traitements - ' . APP_NAME,
                    'showNavbar'  => true,
                    'traitements' => $model->getCalendrierTraitements(null, false),
                    'alertes'     => $model->getAlertesTraitements($residenceIds),
                    'residences'  => $residencesRuches,
                    'flash'       => $this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  RUCHES (apiculture) — visible si coproprietees.ruches = 1
    // =================================================================

    public function ruches($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $model = $this->model('Jardinage');

        switch ($action) {
            case 'create':
                $this->requireRole(self::ROLES_MANAGER);
                $this->verifyCsrf();
                try {
                    $newId = $model->createRuche($_POST, (int)$_SESSION['user_id']);
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $path = $this->handleRuchePhotoUpload($_FILES['photo'], $newId);
                        $model->updateRuchePhoto($newId, $path);
                    }
                    $this->setFlash('success', 'Ruche créée');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/ruches?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'update':
                $this->requireRole(self::ROLES_MANAGER);
                $this->verifyCsrf();
                try {
                    $model->updateRuche((int)$id, $_POST, (int)$_SESSION['user_id']);
                    if (!empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
                        $old = $model->getRuchePhoto((int)$id);
                        $path = $this->handleRuchePhotoUpload($_FILES['photo'], (int)$id);
                        $model->updateRuchePhoto((int)$id, $path);
                        if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                    }
                    $this->setFlash('success', 'Ruche modifiée');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $redir = !empty($_POST['redirect_show']) ? 'jardinage/ruches/show/' . (int)$id : 'jardinage/ruches?residence_id=' . ($_POST['residence_id'] ?? '');
                $this->redirect($redir);
                return;

            case 'photoDelete':
                $this->requireRole(self::ROLES_MANAGER);
                $old = $model->getRuchePhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->updateRuchePhoto((int)$id, null);
                $this->setFlash('success', 'Photo supprimée');
                $back = $_GET['residence_id'] ?? '';
                $this->redirect($back ? 'jardinage/ruches?residence_id=' . $back : 'jardinage/ruches/show/' . (int)$id);
                return;

            case 'delete':
                $this->requireRole(self::ROLES_MANAGER);
                $old = $model->getRuchePhoto((int)$id);
                if ($old) { $oldPath = '../public/' . $old; if (file_exists($oldPath)) @unlink($oldPath); }
                $model->updateRuchePhoto((int)$id, null);
                $model->setRucheStatut((int)$id, 'inactive', 'Désactivation via liste ruches', (int)$_SESSION['user_id']);
                $this->setFlash('success', 'Ruche désactivée');
                $this->redirect('jardinage/ruches?residence_id=' . ($_GET['residence_id'] ?? ''));
                return;

            case 'show':
                return $this->showRuche($model, (int)$id);

            case 'exportCarnet':
                return $this->exportCarnetRuche($model, (int)$id);

            case 'visite':
                $this->verifyCsrf();
                try {
                    $model->createVisite(array_merge($_POST, [
                        'ruche_id' => (int)$id,
                        'user_id'  => (int)$_SESSION['user_id'],
                    ]));
                    $this->setFlash('success', 'Visite enregistrée');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/ruches/show/' . (int)$id);
                return;

            default:
                return $this->listRuches($model);
        }
    }

    private function listRuches(Jardinage $model) {
        $residences = $this->getUserResidences();
        $residencesRuches = array_values(array_filter($residences, fn($r) => !empty($r['ruches'])));

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residencesRuches) === 1) $selectedResidence = $residencesRuches[0]['id'];

        if ($selectedResidence) {
            $ok = false;
            foreach ($residencesRuches as $r) { if ((int)$r['id'] === $selectedResidence) { $ok = true; break; } }
            if (!$ok) { $this->setFlash('warning', "Cette résidence n'a pas l'option apiculture activée."); $selectedResidence = 0; }
        }

        $ruches = $selectedResidence ? $model->getRuchesByResidence($selectedResidence) : [];
        // Pré-calculer les alertes traitement par ruche pour les badges dans la liste
        $alertesParRuche = [];
        foreach ($ruches as $r) {
            if ($r['statut'] === 'active') {
                $alertesParRuche[(int)$r['id']] = $model->countAlertesRuche((int)$r['id'], $selectedResidence);
            }
        }

        $this->view('jardinage/ruches', [
            'title'             => 'Ruches - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residencesRuches,
            'selectedResidence' => $selectedResidence,
            'ruches'            => $ruches,
            'alertesParRuche'   => $alertesParRuche,
            'espacesRucher'     => $selectedResidence ? $model->getEspacesRucher($selectedResidence) : [],
            'isManager'         => in_array($_SESSION['user_role'], self::ROLES_MANAGER),
            'flash'             => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  COMPTABILITÉ JARDINERIE — MANAGER STRICT
    // =================================================================

    public function comptabilite($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Jardinage');

        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');
        $sel = (int)($_GET['residence_id'] ?? $_POST['residence_id'] ?? 0);
        $filteredIds = $sel ? [$sel] : $residenceIds;
        $annee = (int)($_GET['annee'] ?? $_POST['annee'] ?? date('Y'));
        $mois  = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        switch ($action) {
            case 'ecriture':
                $this->verifyCsrf();
                try {
                    $ht  = (float)($_POST['montant_ht'] ?? 0);
                    $tva = (float)($_POST['montant_tva'] ?? 0);
                    $model->createEcriture(array_merge($_POST, [
                        'montant_ht'  => $ht,
                        'montant_tva' => $tva,
                        'montant_ttc' => round($ht + $tva, 2),
                        'created_by'  => (int)$_SESSION['user_id'],
                    ]));
                    $this->setFlash('success', 'Écriture enregistrée');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/comptabilite?residence_id=' . ($_POST['residence_id'] ?? '') . '&annee=' . ($_POST['annee'] ?? date('Y')));
                return;

            case 'recolte':
                // Comptabilise une récolte de miel : crée une écriture type recette liée à la visite
                $this->verifyCsrf();
                try {
                    $visiteId = (int)$_POST['visite_id'];
                    $qteKg    = (float)$_POST['quantite_kg'];
                    $prixKg   = (float)$_POST['prix_kg'];
                    $resId    = (int)$_POST['residence_id'];
                    if ($qteKg <= 0 || $prixKg <= 0) throw new Exception("Quantité et prix doivent être > 0");
                    $total = round($qteKg * $prixKg, 2);
                    $date  = $_POST['date_ecriture'] ?? date('Y-m-d');
                    $model->createEcriture([
                        'residence_id'   => $resId,
                        'date_ecriture'  => $date,
                        'type_ecriture'  => 'recette',
                        'categorie'      => 'recolte_miel',
                        'reference_id'   => $visiteId,
                        'reference_type' => 'ruche_visite',
                        'libelle'        => 'Récolte miel ruche ' . ($_POST['ruche_numero'] ?? '') . ' — ' . $qteKg . ' kg × ' . number_format($prixKg, 2, ',', ' ') . ' €/kg',
                        'montant_ht'     => $total,
                        'montant_tva'    => 0,
                        'montant_ttc'    => $total,
                        'notes'          => 'Auto-généré depuis le carnet de visite',
                        'created_by'     => (int)$_SESSION['user_id'],
                    ]);
                    $this->setFlash('success', 'Récolte comptabilisée (' . number_format($total, 2, ',', ' ') . ' €)');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/comptabilite?residence_id=' . ($_POST['residence_id'] ?? '') . '&annee=' . ($_POST['annee'] ?? date('Y')));
                return;

            case 'delete':
                $model->deleteEcriture((int)$id);
                $this->setFlash('success', 'Écriture supprimée');
                $this->redirect('jardinage/comptabilite?residence_id=' . ($_GET['residence_id'] ?? '') . '&annee=' . ($_GET['annee'] ?? date('Y')));
                return;

            case 'export':
                return $this->exportComptaCsv($model, $filteredIds, $annee, $mois);

            default:
                // Vue principale
                $totaux   = $model->getTotauxAnnuels($filteredIds, $annee);
                $synthese = $model->getSyntheseMensuelle($filteredIds, $annee);
                $coutEspaces = $model->getCoutParEspace($filteredIds, $annee);
                $depFourn = $model->getDepensesParFournisseur($filteredIds, $annee, $mois);
                $ecritures = $model->getEcritures($filteredIds, $annee, $mois);
                $recoltesNonCo = $sel ? $model->getRecoltesNonComptabilisees([$sel], $annee) : $model->getRecoltesNonComptabilisees($residenceIds, $annee);

                $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
                $recettesData = array_fill(0, 12, 0.0);
                $depensesData = array_fill(0, 12, 0.0);
                foreach ($synthese as $s) {
                    $recettesData[(int)$s['mois'] - 1] = (float)$s['recettes_ttc'];
                    $depensesData[(int)$s['mois'] - 1] = (float)$s['depenses_ttc'];
                }

                $this->view('jardinage/comptabilite', [
                    'title'             => 'Comptabilité Jardinage - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $sel,
                    'annee'             => $annee,
                    'mois'              => $mois,
                    'totaux'            => $totaux,
                    'coutEspaces'       => $coutEspaces,
                    'depensesFournisseurs' => $depFourn,
                    'ecritures'         => $ecritures,
                    'recoltesNonCo'     => $recoltesNonCo,
                    'espacesResidence'  => $sel ? $model->getEspaces($sel) : [],
                    'moisLabels'        => json_encode($moisLabels),
                    'recettesData'      => json_encode($recettesData),
                    'depensesData'      => json_encode($depensesData),
                    'flash'             => $this->getFlash()
                ], true);
        }
    }

    private function exportComptaCsv(Jardinage $model, array $filteredIds, int $annee, ?int $mois) {
        $ecritures = $model->getEcrituresExport($filteredIds, $annee, $mois);
        $filename = 'comptabilite_jardinage_' . $annee . ($mois ? '_' . str_pad((string)$mois, 2, '0', STR_PAD_LEFT) : '') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel
        fputcsv($out, ['Date', 'Compte', 'Libellé', 'Type', 'Catégorie', 'Espace', 'Montant HT', 'TVA', 'Montant TTC', 'Résidence'], ';');
        foreach ($ecritures as $e) {
            fputcsv($out, [
                $e['date_ecriture'],
                $e['compte_comptable'] ?? '',
                $e['libelle'],
                $e['type_ecriture'],
                str_replace('_', ' ', $e['categorie']),
                $e['espace_nom'] ?? '',
                number_format($e['montant_ht'], 2, ',', ''),
                number_format($e['montant_tva'], 2, ',', ''),
                number_format($e['montant_ttc'], 2, ',', ''),
                $e['residence_nom']
            ], ';');
        }
        fclose($out);
        exit;
    }

    // =================================================================
    //  COMMANDES FOURNISSEURS
    // =================================================================

    public function commandes($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $cm = new Commande();
        $modulePath = 'jardinage';

        switch ($action) {
            case 'create':
                $residences = $this->getUserResidences();
                $residenceId = (int)($_GET['residence_id'] ?? 0);
                if (!$residenceId && count($residences) === 1) $residenceId = (int)$residences[0]['id'];
                $this->view($modulePath . '/commande_form', [
                    'title'             => 'Nouvelle commande - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $residenceId,
                    'fournisseurs'      => $residenceId ? $this->model('Jardinage')->getFournisseursActifsResidence($residenceId) : [],
                    'produits'          => $this->model('Jardinage')->getAllProduits(null, null, true),
                    'flash'             => $this->getFlash()
                ], true);
                return;

            case 'store':
                $this->verifyCsrf();
                try {
                    $lignes = $this->buildLignesFromPost($_POST['lignes'] ?? [], $this->model('Jardinage'));
                    if (empty($lignes)) throw new Exception("Au moins une ligne est requise");
                    $cmdId = $cm->create('jardinage', [
                        'residence_id'          => (int)$_POST['residence_id'],
                        'fournisseur_id'        => (int)$_POST['fournisseur_id'],
                        'date_commande'         => $_POST['date_commande'] ?? date('Y-m-d'),
                        'date_livraison_prevue' => $_POST['date_livraison_prevue'] ?? null,
                        'statut'                => $_POST['statut'] ?? 'brouillon',
                        'notes'                 => $_POST['notes'] ?? '',
                        'created_by'            => (int)$_SESSION['user_id'],
                    ], $lignes);
                    $this->setFlash('success', 'Commande créée');
                    $this->redirect($modulePath . '/commandes/show/' . $cmdId);
                } catch (Exception $e) {
                    $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                    $this->redirect($modulePath . '/commandes/create?residence_id=' . ($_POST['residence_id'] ?? ''));
                }
                return;

            case 'show':
                $cmd = $cm->get((int)$id);
                if (!$cmd || $cmd['module'] !== 'jardinage') { $this->setFlash('error', 'Commande introuvable'); $this->redirect($modulePath . '/commandes'); return; }
                $this->view($modulePath . '/commande_show', [
                    'title' => 'Commande ' . $cmd['numero_commande'] . ' - ' . APP_NAME,
                    'showNavbar' => true, 'commande' => $cmd, 'flash' => $this->getFlash()
                ], true);
                return;

            case 'envoyer':
                $cm->updateStatut((int)$id, 'envoyee');
                $this->setFlash('success', 'Commande envoyée au fournisseur');
                $this->redirect($modulePath . '/commandes/show/' . (int)$id);
                return;

            case 'receptionner':
                $this->verifyCsrf();
                try {
                    $qtes = array_map('floatval', $_POST['quantites_recues'] ?? []);
                    $cm->receptionner((int)$id, $qtes, (int)$_SESSION['user_id']);
                    $this->setFlash('success', 'Commande réceptionnée — stock mis à jour');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect($modulePath . '/commandes/show/' . (int)$id);
                return;

            case 'facturer':
                $cm->updateStatut((int)$id, 'facturee');
                $this->setFlash('success', 'Commande marquée comme facturée');
                $this->redirect($modulePath . '/commandes/show/' . (int)$id);
                return;

            case 'delete':
                $result = $cm->deleteOrCancel((int)$id);
                $this->setFlash('success', $result === 'deleted' ? 'Commande supprimée' : 'Commande annulée');
                $this->redirect($modulePath . '/commandes');
                return;

            default:
                $residences = $this->getUserResidences();
                $this->view($modulePath . '/commandes', [
                    'title'      => 'Commandes Jardinage - ' . APP_NAME,
                    'showNavbar' => true,
                    'commandes'  => $cm->getAll('jardinage', array_column($residences, 'id'), $_GET['statut'] ?? null),
                    'statut'     => $_GET['statut'] ?? null,
                    'flash'      => $this->getFlash()
                ], true);
        }
    }

    private function buildLignesFromPost(array $postLignes, $model): array {
        $lignes = [];
        foreach ($postLignes as $l) {
            if (empty($l['produit_id']) || empty($l['quantite_commandee'])) continue;
            $p = $model->getProduit((int)$l['produit_id']);
            if (!$p) continue;
            $lignes[] = [
                'produit_id'         => (int)$l['produit_id'],
                'designation'        => trim($l['designation'] ?? ($p['nom'] ?? '')),
                'quantite_commandee' => (float)$l['quantite_commandee'],
                'prix_unitaire_ht'   => (float)($l['prix_unitaire_ht'] ?? $p['prix_unitaire'] ?? $p['prix_reference'] ?? 0),
                'taux_tva'           => (float)($l['taux_tva'] ?? 20),
            ];
        }
        return $lignes;
    }

    // =================================================================
    //  FOURNISSEURS JARDINAGE (lecture-seule, pivot global fournisseur_residence)
    //  CRUD (lier/modifier/délier) centralisé dans /fournisseur/show/<id>
    // =================================================================

    public function fournisseurs($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Jardinage');
        $fm    = new Fournisseur();

        switch ($action) {
            case 'lier':
                $this->verifyCsrf();
                try {
                    $fournId = (int)($_POST['fournisseur_id'] ?? 0);
                    $resId   = (int)($_POST['residence_id'] ?? 0);
                    if (!$fournId || !$resId) throw new Exception("Fournisseur et résidence requis");
                    $fm->lier($fournId, $resId, $_POST);
                    $this->setFlash('success', 'Fournisseur lié à la résidence');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'update':
                $this->verifyCsrf();
                try {
                    $fm->updateLien((int)$id, $_POST);
                    $this->setFlash('success', 'Lien fournisseur modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('jardinage/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? $_GET['residence_id'] ?? ''));
                return;

            case 'delier':
                $fm->delier((int)$id);
                $this->setFlash('success', 'Lien fournisseur désactivé');
                $this->redirect('jardinage/fournisseurs?residence_id=' . ($_GET['residence_id'] ?? ''));
                return;

            default:
                $residences = $this->getUserResidences();
                $selectedResidence = (int)($_GET['residence_id'] ?? 0);
                if (!$selectedResidence && count($residences) === 1) $selectedResidence = (int)$residences[0]['id'];

                $this->view('jardinage/fournisseurs', [
                    'title'             => 'Fournisseurs Jardinage - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $selectedResidence,
                    'fournisseurs'      => $selectedResidence ? $model->getFournisseursResidence($selectedResidence) : [],
                    'disponibles'       => $selectedResidence ? $fm->getFournisseursDisponibles($selectedResidence, 'jardinage') : [],
                    'flash'             => $this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  CONFIG APICULTURE (table coproprietees_apiculture, 1:1)
    // =================================================================

    public function apiculture($action = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $model = $this->model('Jardinage');

        // Résidence ciblée (query string ou POST)
        $residenceId = (int)($_GET['residence_id'] ?? $_POST['residence_id'] ?? 0);

        // Liste des résidences accessibles avec ruches=1
        $residences = $this->getUserResidences();
        $residencesRuches = array_values(array_filter($residences, fn($r) => !empty($r['ruches'])));

        if (empty($residencesRuches)) {
            $this->setFlash('info', "Aucune de vos résidences n'a l'option apiculture activée.");
            $this->redirect('jardinage/index');
            return;
        }

        if (!$residenceId && count($residencesRuches) === 1) $residenceId = (int)$residencesRuches[0]['id'];

        // Vérifier l'accès à la résidence sélectionnée
        if ($residenceId) {
            $ok = false;
            foreach ($residencesRuches as $r) { if ((int)$r['id'] === $residenceId) { $ok = true; break; } }
            if (!$ok) {
                $this->setFlash('warning', "Cette résidence n'a pas l'option apiculture activée ou n'est pas accessible.");
                $this->redirect('jardinage/apiculture');
                return;
            }
        }

        // Action save (POST) — MANAGER only
        if ($action === 'save') {
            $this->requireRole(self::ROLES_MANAGER);
            $this->verifyCsrf();
            try {
                $model->upsertApiculture($residenceId, $_POST);
                $this->setFlash('success', 'Configuration apiculture enregistrée');
            } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
            $this->redirect('jardinage/apiculture?residence_id=' . $residenceId);
            return;
        }

        $this->view('jardinage/apiculture', [
            'title'             => 'Configuration apiculture - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residencesRuches,
            'selectedResidence' => $residenceId,
            'config'            => $residenceId ? $model->getApiculture($residenceId) : null,
            'candidatsReferent' => $residenceId ? $model->getApiculteursCandidats($residenceId) : [],
            'isManager'         => in_array($_SESSION['user_role'], self::ROLES_MANAGER),
            'flash'             => $this->getFlash()
        ], true);
    }

    /**
     * Export CSV du carnet de visite d'une ruche (conforme pratique apiculture FR).
     * Contient en en-tête : n° ruche, résidence, NAPI, apiculteur référent.
     * Colonnes : Date, Type, Couvain, Reine, Miel (kg), Traitement, Observations, Apiculteur.
     * Accessible à tous les rôles jardinage (ROLES_ALL).
     */
    private function exportCarnetRuche(Jardinage $model, int $id) {
        $ruche = $model->getRuche($id);
        if (!$ruche) { $this->setFlash('error', 'Ruche introuvable'); $this->redirect('jardinage/ruches'); return; }
        if (empty($ruche['residence_ruches'])) {
            $this->setFlash('warning', "Cette résidence n'a pas l'option apiculture activée.");
            $this->redirect('jardinage/ruches'); return;
        }
        // Vérif accès résidence (non admin)
        if ($_SESSION['user_role'] !== 'admin') {
            $userRes = $model->getResidenceIdsByUser((int)$_SESSION['user_id']);
            if (!in_array((int)$ruche['residence_id'], $userRes)) {
                $this->setFlash('error', 'Accès refusé à cette ruche');
                $this->redirect('jardinage/ruches'); return;
            }
        }

        $visites = $model->getVisitesByRuche($id);
        $apiculture = $model->getApiculture((int)$ruche['residence_id']);

        $interventionLabels = [
            'inspection' => 'Inspection', 'recolte' => 'Récolte', 'traitement' => 'Traitement',
            'nourrissement' => 'Nourrissement', 'changement_reine' => 'Changement de reine',
            'division' => 'Division', 'urgence' => 'Urgence', 'autre' => 'Autre'
        ];
        $couvainLabels = ['excellent' => 'Excellent', 'bon' => 'Bon', 'moyen' => 'Moyen', 'faible' => 'Faible', 'absent' => 'Absent'];

        $filename = 'carnet_ruche_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $ruche['numero']) . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel

        // En-tête du document (métadonnées traçabilité)
        fputcsv($out, ['Carnet de visite — réglementation apiculture FR'], ';');
        fputcsv($out, ['Généré le', date('d/m/Y H:i')], ';');
        fputcsv($out, ['Ruche', $ruche['numero']], ';');
        fputcsv($out, ['Résidence', $ruche['residence_nom']], ';');
        fputcsv($out, ['Type de ruche', $ruche['type_ruche'] ?? '—'], ';');
        fputcsv($out, ['Race d\'abeilles', $ruche['race_abeilles'] ?? '—'], ';');
        fputcsv($out, ['Date d\'installation', $ruche['date_installation'] ? date('d/m/Y', strtotime($ruche['date_installation'])) : '—'], ';');
        fputcsv($out, ['Emplacement', $ruche['espace_nom'] ?? '—'], ';');
        fputcsv($out, ['Statut actuel', $ruche['statut']], ';');
        if ($apiculture) {
            fputcsv($out, ['Numéro NAPI', $apiculture['numero_napi'] ?? '—'], ';');
            fputcsv($out, ['Déclaration préfecture', $apiculture['date_declaration_prefecture'] ? date('d/m/Y', strtotime($apiculture['date_declaration_prefecture'])) : '—'], ';');
            $referent = '—';
            if (!empty($apiculture['referent_prenom'])) {
                $referent = $apiculture['referent_prenom'] . ' ' . $apiculture['referent_nom'];
            } elseif (!empty($apiculture['apiculteur_referent_externe'])) {
                $referent = $apiculture['apiculteur_referent_externe'] . ' (externe)';
            }
            fputcsv($out, ['Apiculteur référent', $referent], ';');
            fputcsv($out, ['Type de rucher', $apiculture['type_rucher'] ?? '—'], ';');
        }
        fputcsv($out, [''], ';'); // ligne vide avant le tableau

        // En-tête des colonnes de visites
        fputcsv($out, [
            'Date', 'Type intervention', 'État couvain', 'Reine vue',
            'Miel récolté (kg)', 'Produit traitement', 'Observations', 'Apiculteur'
        ], ';');

        foreach ($visites as $v) {
            $reineVue = $v['reine_vue'] === null ? '' : ($v['reine_vue'] ? 'Oui' : 'Non');
            $apiculteur = $v['user_prenom'] ? $v['user_prenom'] . ' ' . $v['user_nom'] : '';
            fputcsv($out, [
                date('d/m/Y', strtotime($v['date_visite'])),
                $interventionLabels[$v['type_intervention']] ?? $v['type_intervention'],
                $v['couvain_etat'] ? ($couvainLabels[$v['couvain_etat']] ?? $v['couvain_etat']) : '',
                $reineVue,
                $v['quantite_miel_kg'] ? number_format((float)$v['quantite_miel_kg'], 2, ',', '') : '',
                $v['traitement_produit'] ?? '',
                $v['observations'] ?? '',
                $apiculteur
            ], ';');
        }

        fclose($out);
        exit;
    }

    private function showRuche(Jardinage $model, int $id) {
        $ruche = $model->getRuche($id);
        if (!$ruche) { $this->setFlash('error', 'Ruche introuvable'); $this->redirect('jardinage/ruches'); return; }
        if (empty($ruche['residence_ruches'])) {
            $this->setFlash('warning', "Cette résidence n'a pas l'option apiculture activée.");
            $this->redirect('jardinage/ruches'); return;
        }
        if ($_SESSION['user_role'] !== 'admin') {
            $userRes = $model->getResidenceIdsByUser((int)$_SESSION['user_id']);
            if (!in_array((int)$ruche['residence_id'], $userRes)) {
                $this->setFlash('error', 'Accès refusé à cette ruche');
                $this->redirect('jardinage/ruches'); return;
            }
        }

        $this->view('jardinage/ruche_show', [
            'title'             => 'Ruche ' . $ruche['numero'] . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'ruche'             => $ruche,
            'visites'           => $model->getVisitesByRuche($id),
            'statutHistory'     => $model->getStatutHistory($id),
            'traitementsRuche'  => $model->getTraitementsPourRuche($id, (int)$ruche['residence_id']),
            'espacesRucher'     => $model->getEspacesRucher((int)$ruche['residence_id']),
            'isManager'         => in_array($_SESSION['user_role'], self::ROLES_MANAGER),
            'userId'            => (int)$_SESSION['user_id'],
            'flash'             => $this->getFlash()
        ], true);
    }
}
