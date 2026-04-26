<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Restauration
 * ====================================================================
 * Module restauration pour les résidences seniors.
 * Rôles : restauration_manager, restauration_serveur, restauration_cuisine
 *
 * Phase 1 : Dashboard, planning, résidents
 */

class RestaurationController extends Controller {

    private const ROLES_RESTO = ['admin', 'restauration_manager', 'restauration_serveur', 'restauration_cuisine'];
    private const ROLES_MANAGER = ['admin', 'restauration_manager'];

    /**
     * Dashboard restauration
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_RESTO);

        $model = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        $residenceIds = array_column($residences, 'id');

        // Admin voit toutes les résidences
        if ($userRole === 'admin') {
            $resModel = $this->model('Residence');
            $residences = $resModel->getAllSimple();
            $residenceIds = array_column($residences, 'id');
        }

        // Filtre résidence si sélectionné
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;

        $statsJour = $model->getStatsDuJour($filteredIds);
        $statsMois = $model->getStatsDuMois($filteredIds);
        $menuDuJour = [];
        if ($selectedResidence) {
            $menuDuJour = $model->getMenuDuJour($selectedResidence);
        } elseif (count($residenceIds) === 1) {
            $menuDuJour = $model->getMenuDuJour($residenceIds[0]);
        }

        $this->view('restauration/dashboard', [
            'title'              => 'Restauration - ' . APP_NAME,
            'showNavbar'         => true,
            'userRole'           => $userRole,
            'residences'         => $residences,
            'selectedResidence'  => $selectedResidence,
            'statsJour'          => $statsJour,
            'statsMois'          => $statsMois,
            'menuDuJour'         => $this->groupMenuByService($menuDuJour),
            'repasRecents'       => $model->getRepasRecents($filteredIds),
            'alertesStock'       => $model->getAlertesStock($filteredIds),
            'isManager'          => in_array($userRole, ['admin', 'restauration_manager']),
            'flash'              => $this->getFlash()
        ], true);
    }

    /**
     * Liste des résidents (accessible à tous les rôles resto)
     */
    public function residents() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_RESTO);

        $model = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        $residenceIds = array_column($residences, 'id');

        if ($userRole === 'admin') {
            $resModel = $this->model('Residence');
            $residences = $resModel->getAllSimple();
            $residenceIds = array_column($residences, 'id');
        }

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;

        $this->view('restauration/residents', [
            'title'             => 'Résidents - Restauration - ' . APP_NAME,
            'showNavbar'        => true,
            'userRole'          => $userRole,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'residents'         => $model->getResidentsByResidences($filteredIds),
            'flash'             => $this->getFlash()
        ], true);
    }

    /**
     * Équipe restauration (manager uniquement)
     */
    public function equipe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        $residenceIds = array_column($residences, 'id');

        if ($userRole === 'admin') {
            $resModel = $this->model('Residence');
            $residences = $resModel->getAllSimple();
            $residenceIds = array_column($residences, 'id');
        }

        $this->view('restauration/equipe', [
            'title'      => 'Équipe Restauration - ' . APP_NAME,
            'showNavbar' => true,
            'userRole'   => $userRole,
            'residences' => $residences,
            'staff'      => $model->getStaffByResidences($residenceIds),
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Planning restauration (TUI Calendar — shifts du staff resto)
     */
    public function planning() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_RESTO);

        $model = $this->model('Restauration');
        $planningModel = $this->model('Planning');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        $residenceIds = array_column($residences, 'id');

        if ($userRole === 'admin') {
            $resModel = $this->model('Residence');
            $residences = $resModel->getAllSimple();
            $residenceIds = array_column($residences, 'id');
        }

        // Staff restauration pour les filtres
        $staff = $model->getStaffByResidences($residenceIds);

        $this->view('restauration/planning', [
            'title'          => 'Planning Restauration - ' . APP_NAME,
            'showNavbar'     => true,
            'userRole'       => $userRole,
            'userId'         => $userId,
            'residences'     => $residences,
            'staff'          => $staff,
            'categories'     => $planningModel->getCategories(),
            'flash'          => $this->getFlash()
        ], true);
    }

    /**
     * AJAX : shifts du planning restauration
     */
    public function planningAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_RESTO);

        header('Content-Type: application/json; charset=utf-8');

        $planningModel = $this->model('Planning');
        $restoModel = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];

        // Vérifier que l'utilisateur a accès à la résidence demandée
        $residenceIds = $restoModel->getResidenceIdsByUser($userId);
        if ($_SESSION['user_role'] === 'admin') $residenceIds = null; // admin voit tout

        try {
            switch ($action) {
                case 'getEvents':
                    $start = $_GET['start'] ?? date('Y-m-01');
                    $end = $_GET['end'] ?? date('Y-m-t');
                    $residenceId = (int)($_GET['residence_id'] ?? 0);
                    $filterUserId = (int)($_GET['user_id'] ?? 0);

                    // Filtrer uniquement les shifts restauration des résidences autorisées
                    $shifts = $planningModel->getShifts($start, $end, $residenceId, $filterUserId);

                    // Ne garder que les shifts du staff restauration
                    $restoRoles = ['restauration_manager', 'restauration_serveur', 'restauration_cuisine'];
                    $shifts = array_filter($shifts, function($s) use ($restoRoles, $residenceIds) {
                        $roleOk = in_array($s['employe_role'], $restoRoles);
                        $residenceOk = $residenceIds === null || in_array($s['residence_id'], $residenceIds);
                        return $roleOk && $residenceOk;
                    });

                    // Formater pour TUI Calendar
                    $events = array_values(array_map(function($s) {
                        return [
                            'id' => $s['id'],
                            'calendarId' => $s['residence_id'],
                            'title' => $s['employe_prenom'] . ' ' . $s['employe_nom'] . ' — ' . $s['titre'],
                            'start' => $s['date_debut'],
                            'end' => $s['date_fin'],
                            'isAllDay' => (bool)$s['journee_entiere'],
                            'calendarColor' => $s['couleur'] ?? '#ffc107',
                            'categoryColor' => $s['bg_couleur'] ?? '#fff3cd',
                            'categoryTextColor' => '#333',
                            'body' => $s['description'] ?? '',
                            'raw' => [
                                'userId' => $s['user_id'],
                                'residenceId' => $s['residence_id'],
                                'categoryId' => $s['category_id'],
                                'typeShift' => $s['type_shift'],
                                'typeHeures' => $s['type_heures'],
                                'statut' => $s['statut'],
                                'heures' => $s['heures_calculees'],
                                'employeNom' => $s['employe_prenom'] . ' ' . $s['employe_nom'],
                                'residenceNom' => $s['residence_nom'],
                            ]
                        ];
                    }, $shifts));

                    echo json_encode($events);
                    break;

                case 'getEvent':
                    $id = (int)($_GET['id'] ?? 0);
                    if (!$id) {
                        echo json_encode(['success' => false, 'message' => 'ID requis']);
                        break;
                    }
                    $shift = $planningModel->getShift($id);
                    if (!$shift) {
                        echo json_encode(['success' => false, 'message' => 'Shift introuvable']);
                        break;
                    }
                    echo json_encode([
                        'success' => true,
                        'event'   => [
                            'id'          => $shift['id'],
                            'calendarId'  => $shift['residence_id'],
                            'title'       => $shift['titre'],
                            'start'       => $shift['date_debut'],
                            'end'         => $shift['date_fin'],
                            'isAllDay'    => (bool)$shift['journee_entiere'],
                            'raw'         => [
                                'userId'       => $shift['user_id'],
                                'residenceId'  => $shift['residence_id'],
                                'categoryId'   => $shift['category_id'],
                                'typeShift'    => $shift['type_shift'],
                                'typeHeures'   => $shift['type_heures'],
                                'statut'       => $shift['statut'],
                                'description'  => $shift['description'],
                                'notes'        => $shift['notes'],
                            ]
                        ]
                    ]);
                    break;

                case 'save':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) {
                        echo json_encode(['success' => false, 'message' => 'Données invalides']);
                        break;
                    }
                    $saveUserId      = (int)($input['userId'] ?? 0);
                    $saveResidenceId = (int)($input['residenceId'] ?? 0);
                    $saveTitre       = trim($input['title'] ?? '');
                    $saveDateDebut   = $input['start'] ?? '';
                    $saveDateFin     = $input['end'] ?? '';

                    if (!$saveUserId || !$saveResidenceId || !$saveTitre || !$saveDateDebut || !$saveDateFin) {
                        echo json_encode(['success' => false, 'message' => 'Champs requis : employé, résidence, titre, dates']);
                        break;
                    }
                    $savedId = $planningModel->saveShift([
                        'id'          => $input['id'] ?? null,
                        'userId'      => $saveUserId,
                        'residenceId' => $saveResidenceId,
                        'categoryId'  => (int)($input['categoryId'] ?? 0) ?: null,
                        'title'       => $saveTitre,
                        'start'       => $saveDateDebut,
                        'end'         => $saveDateFin,
                        'isAllDay'    => $input['isAllDay'] ?? false,
                        'typeShift'   => $input['typeShift'] ?? 'travail',
                        'typeHeures'  => $input['typeHeures'] ?? 'normales',
                        'description' => trim($input['description'] ?? ''),
                        'notes'       => trim($input['notes'] ?? ''),
                    ]);
                    $msg = ($input['id'] ?? null) ? 'Shift modifié' : 'Shift créé';
                    echo json_encode(['success' => true, 'message' => $msg, 'id' => $savedId]);
                    break;

                case 'move':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input || empty($input['id'])) {
                        echo json_encode(['success' => false, 'message' => 'Données invalides']);
                        break;
                    }
                    $planningModel->moveShift((int)$input['id'], $input['start'], $input['end']);
                    echo json_encode(['success' => true, 'message' => 'Shift déplacé']);
                    break;

                case 'delete':
                    $this->requireRole(self::ROLES_MANAGER);
                    $input = json_decode(file_get_contents('php://input'), true);
                    $deleteId = (int)($input['id'] ?? 0);
                    if (!$deleteId) {
                        echo json_encode(['success' => false, 'message' => 'ID requis']);
                        break;
                    }
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
    //  PLATS — CATALOGUE (manager only)
    // =================================================================

    /**
     * Liste des plats
     */
    public function plats($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Restauration');

        switch ($action) {
            case 'create':
                return $this->storePlat($model);
            case 'edit':
                return $this->editPlat($model, $id);
            case 'update':
                return $this->updatePlat($model, $id);
            case 'delete':
                return $this->deletePlat($model, $id);
            default:
                return $this->listPlats($model);
        }
    }

    private function listPlats(Restauration $model) {
        $categorie = $_GET['categorie'] ?? null;
        $typeService = $_GET['type_service'] ?? null;

        $this->view('restauration/plats', [
            'title'     => 'Catalogue des Plats - ' . APP_NAME,
            'showNavbar' => true,
            'plats'     => $model->getAllPlats($categorie, $typeService),
            'stats'     => $model->getPlatsStats(),
            'filtreCategorie' => $categorie,
            'filtreService'   => $typeService,
            'flash'     => $this->getFlash()
        ], true);
    }

    private function storePlat(Restauration $model) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('restauration/plats');
            return;
        }
        $this->verifyCsrf();

        try {
            $model->createPlat($_POST);
            $this->setFlash('success', 'Plat créé avec succès');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('restauration/plats');
    }

    private function editPlat(Restauration $model, $id) {
        $plat = $model->getPlat($id);
        if (!$plat) {
            $this->setFlash('error', 'Plat introuvable');
            $this->redirect('restauration/plats');
            return;
        }

        $this->view('restauration/plat_edit', [
            'title'     => 'Modifier le plat - ' . APP_NAME,
            'showNavbar' => true,
            'plat'      => $plat,
            'flash'     => $this->getFlash()
        ], true);
    }

    private function updatePlat(Restauration $model, $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('restauration/plats');
            return;
        }
        $this->verifyCsrf();

        try {
            $model->updatePlat($id, $_POST);
            $this->setFlash('success', 'Plat modifié avec succès');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('restauration/plats');
    }

    private function deletePlat(Restauration $model, $id) {
        $model->deletePlat($id);
        $this->setFlash('success', 'Plat désactivé');
        $this->redirect('restauration/plats');
    }

    // =================================================================
    //  MENUS — GESTION QUOTIDIENNE (manager only)
    // =================================================================

    /**
     * Liste des menus / création
     */
    public function menus($action = null, $id = null) {
        $this->requireAuth();

        // Lecture pour tous, écriture pour manager
        $isReadOnly = !in_array($_SESSION['user_role'], ['admin', 'restauration_manager']);

        $model = $this->model('Restauration');

        switch ($action) {
            case 'create':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->createMenu($model);
            case 'store':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->storeMenu($model);
            case 'show':
                return $this->showMenu($model, $id);
            case 'edit':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->editMenu($model, $id);
            case 'update':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->updateMenuAction($model, $id);
            case 'delete':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->deleteMenuAction($model, $id);
            case 'duplicate':
                if ($isReadOnly) { $this->redirect('restauration/menus'); return; }
                return $this->duplicateMenuAction($model, $id);
            default:
                return $this->listMenus($model, $isReadOnly);
        }
    }

    private function listMenus(Restauration $model, bool $isReadOnly) {
        $restoModel = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $restoModel->getResidencesByUser($userId);
        if ($userRole === 'admin') {
            $residences = $this->model('Residence')->getAllSimple();
        }

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) {
            $selectedResidence = $residences[0]['id'];
        }

        // Semaine courante par défaut
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-d', strtotime('monday this week'));
        $dateFin = $_GET['date_fin'] ?? date('Y-m-d', strtotime('sunday this week'));

        $menus = $selectedResidence ? $model->getMenus($selectedResidence, $dateDebut, $dateFin) : [];

        // Grouper par date
        $menusByDate = [];
        foreach ($menus as $m) {
            $menusByDate[$m['date_menu']][] = $m;
        }

        $this->view('restauration/menus', [
            'title'             => 'Menus - Restauration - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'dateDebut'         => $dateDebut,
            'dateFin'           => $dateFin,
            'menusByDate'       => $menusByDate,
            'isReadOnly'        => $isReadOnly,
            'flash'             => $this->getFlash()
        ], true);
    }

    private function createMenu(Restauration $model) {
        $this->requireRole(self::ROLES_MANAGER);

        $restoModel = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $residences = $_SESSION['user_role'] === 'admin'
            ? $this->model('Residence')->getAllSimple()
            : $restoModel->getResidencesByUser($userId);

        $typeService = $_GET['type_service'] ?? 'dejeuner';
        $platsDisponibles = $model->getPlatsForService($typeService);

        // Grouper par catégorie
        $platsByCategorie = [];
        foreach ($platsDisponibles as $p) {
            $platsByCategorie[$p['categorie']][] = $p;
        }

        $this->view('restauration/menu_form', [
            'title'            => 'Nouveau Menu - ' . APP_NAME,
            'showNavbar'       => true,
            'menu'             => null,
            'residences'       => $residences,
            'platsByCategorie' => $platsByCategorie,
            'typeService'      => $typeService,
            'dateMenu'         => $_GET['date'] ?? date('Y-m-d'),
            'flash'            => $this->getFlash()
        ], true);
    }

    private function storeMenu(Restauration $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        try {
            $menuId = $model->createMenu([
                'residence_id' => (int)$_POST['residence_id'],
                'date_menu'    => $_POST['date_menu'],
                'type_service' => $_POST['type_service'],
                'nom'          => trim($_POST['nom'] ?? ''),
                'prix_menu'    => $_POST['prix_menu'] ?? null,
                'notes'        => trim($_POST['notes'] ?? ''),
                'created_by'   => $_SESSION['user_id']
            ]);

            // Synchroniser les plats
            $plats = $this->parsePlatsFromPost();
            $model->syncMenuPlats($menuId, $plats);

            $this->setFlash('success', 'Menu créé avec succès');
            $this->redirect('restauration/menus?residence_id=' . $_POST['residence_id']);
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('restauration/menus/create');
        }
    }

    private function showMenu(Restauration $model, $id) {
        $menu = $model->getMenu($id);
        if (!$menu) {
            $this->setFlash('error', 'Menu introuvable');
            $this->redirect('restauration/menus');
            return;
        }

        // Grouper les plats par catégorie
        $platsByCategorie = [];
        foreach ($menu['plats'] as $p) {
            $platsByCategorie[$p['categorie_plat']][] = $p;
        }

        $this->view('restauration/menu_show', [
            'title'            => 'Menu du ' . date('d/m/Y', strtotime($menu['date_menu'])) . ' - ' . APP_NAME,
            'showNavbar'       => true,
            'menu'             => $menu,
            'platsByCategorie' => $platsByCategorie,
            'flash'            => $this->getFlash()
        ], true);
    }

    private function editMenu(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);

        $menu = $model->getMenu($id);
        if (!$menu) {
            $this->setFlash('error', 'Menu introuvable');
            $this->redirect('restauration/menus');
            return;
        }

        $restoModel = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $residences = $_SESSION['user_role'] === 'admin'
            ? $this->model('Residence')->getAllSimple()
            : $restoModel->getResidencesByUser($userId);

        $platsByCategorie = [];
        foreach ($model->getPlatsForService($menu['type_service']) as $p) {
            $platsByCategorie[$p['categorie']][] = $p;
        }

        $this->view('restauration/menu_form', [
            'title'            => 'Modifier Menu - ' . APP_NAME,
            'showNavbar'       => true,
            'menu'             => $menu,
            'residences'       => $residences,
            'platsByCategorie' => $platsByCategorie,
            'typeService'      => $menu['type_service'],
            'dateMenu'         => $menu['date_menu'],
            'flash'            => $this->getFlash()
        ], true);
    }

    private function updateMenuAction(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        try {
            $model->updateMenu($id, [
                'date_menu'    => $_POST['date_menu'],
                'type_service' => $_POST['type_service'],
                'nom'          => trim($_POST['nom'] ?? ''),
                'prix_menu'    => $_POST['prix_menu'] ?? null,
                'notes'        => trim($_POST['notes'] ?? ''),
                'actif'        => isset($_POST['actif']) ? 1 : 0
            ]);

            $plats = $this->parsePlatsFromPost();
            $model->syncMenuPlats($id, $plats);

            $this->setFlash('success', 'Menu modifié avec succès');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('restauration/menus/show/' . $id);
    }

    private function deleteMenuAction(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);
        $model->deleteMenu($id);
        $this->setFlash('success', 'Menu supprimé');
        $this->redirect('restauration/menus');
    }

    private function duplicateMenuAction(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);

        $newDate = $_GET['date'] ?? $_POST['date'] ?? date('Y-m-d', strtotime('+1 day'));
        $newId = $model->duplicateMenu($id, $newDate);

        if ($newId) {
            $this->setFlash('success', "Menu dupliqué vers le " . date('d/m/Y', strtotime($newDate)));
            $this->redirect('restauration/menus/show/' . $newId);
        } else {
            $this->setFlash('error', 'Erreur lors de la duplication');
            $this->redirect('restauration/menus');
        }
    }

    // =================================================================
    //  HELPERS
    // =================================================================

    /**
     * Parser les plats depuis le POST (format: plats[0][plat_id], plats[0][categorie_plat], plats[0][ordre])
     */
    private function parsePlatsFromPost(): array {
        $plats = [];
        foreach ($_POST['plats'] ?? [] as $p) {
            if (!empty($p['plat_id'])) {
                $plats[] = [
                    'plat_id' => (int)$p['plat_id'],
                    'categorie_plat' => $p['categorie_plat'] ?? 'plat',
                    'ordre' => (int)($p['ordre'] ?? 0)
                ];
            }
        }
        return $plats;
    }

    /**
     * Grouper les plats du menu par type de service puis catégorie
     */
    private function groupMenuByService(array $menuPlats): array {
        $grouped = [];
        foreach ($menuPlats as $item) {
            $service = $item['type_service'];
            $categorie = $item['categorie_plat'];
            $grouped[$service][$categorie][] = $item;
        }
        return $grouped;
    }

    // =================================================================
    //  SERVICE REPAS (manager + serveur)
    // =================================================================

    /**
     * Page d'enregistrement des repas servis
     */
    public function service($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'restauration_manager', 'restauration_serveur']);

        $model = $this->model('Restauration');

        switch ($action) {
            case 'enregistrer': return $this->enregistrerRepas($model);
            case 'supprimer':   return $this->supprimerRepas($model, $id);
            default:            return $this->pageService($model);
        }
    }

    private function pageService(Restauration $model) {
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        if ($userRole === 'admin') $residences = $this->model('Residence')->getAllSimple();

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $typeService = $_GET['type_service'] ?? null;
        $date = $_GET['date'] ?? date('Y-m-d');

        $residents = []; $hotes = []; $repasJour = []; $tarifs = []; $menuDuJour = []; $pensionIds = [];

        if ($selectedResidence) {
            $residenceIds = [$selectedResidence];
            $residents = $model->getResidentsByResidences($residenceIds);
            $hotes = $model->getHotesEnCours($selectedResidence);
            $repasJour = $model->getRepasJour($selectedResidence, $date, $typeService);
            $tarifs = $model->getTarifs($selectedResidence);
            $menuDuJour = $model->getMenuDuJour($selectedResidence, $date);
            $pensionIds = $model->getResidentsPensionComplete($selectedResidence);
        }

        // Indexer les tarifs par type_service
        $tarifsMap = [];
        foreach ($tarifs as $t) $tarifsMap[$t['type_service']] = $t;

        $this->view('restauration/service', [
            'title'             => 'Service Repas - ' . APP_NAME,
            'showNavbar'        => true,
            'userRole'          => $userRole,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'typeService'       => $typeService,
            'date'              => $date,
            'residents'         => $residents,
            'hotes'             => $hotes,
            'repasJour'         => $repasJour,
            'tarifsMap'         => $tarifsMap,
            'menuDuJour'        => $menuDuJour,
            'pensionIds'        => $pensionIds,
            'flash'             => $this->getFlash()
        ], true);
    }

    private function enregistrerRepas(Restauration $model) {
        $this->verifyCsrf();

        try {
            $residenceId = (int)$_POST['residence_id'];
            $typeClient = $_POST['type_client'];
            $typeService = $_POST['type_service'];
            $modeFacturation = $_POST['mode_facturation'] ?? 'menu';

            $model->enregistrerRepas([
                'residence_id'    => $residenceId,
                'date_service'    => $_POST['date_service'] ?? date('Y-m-d'),
                'type_service'    => $typeService,
                'type_client'     => $typeClient,
                'resident_id'     => $typeClient === 'resident' ? (int)$_POST['resident_id'] : null,
                'hote_id'         => $typeClient === 'hote' ? (int)$_POST['hote_id'] : null,
                'nom_passage'     => $typeClient === 'passage' ? trim($_POST['nom_passage'] ?? '') : null,
                'menu_id'         => $_POST['menu_id'] ?? null,
                'mode_facturation'=> $modeFacturation,
                'nb_couverts'     => (int)($_POST['nb_couverts'] ?? 1),
                'montant'         => (float)($_POST['montant'] ?? 0),
                'notes'           => trim($_POST['notes'] ?? ''),
                'serveur_id'      => $_SESSION['user_id'],
            ]);

            $this->setFlash('success', 'Repas enregistré');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->redirect('restauration/service?residence_id=' . ($_POST['residence_id'] ?? '') . '&type_service=' . ($_POST['type_service'] ?? ''));
    }

    private function supprimerRepas(Restauration $model, $id) {
        $model->supprimerRepas($id);
        $this->setFlash('success', 'Repas supprimé');
        $this->redirect('restauration/service?residence_id=' . ($_GET['residence_id'] ?? ''));
    }

    // =================================================================
    //  FACTURES
    // =================================================================

    public function factures($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'restauration_manager', 'restauration_serveur']);

        $model = $this->model('Restauration');

        switch ($action) {
            case 'create': return $this->createFacture($model);
            case 'store':  return $this->storeFacture($model);
            case 'show':   return $this->showFacture($model, $id);
            case 'payer':  return $this->payerFacture($model, $id);
            default:       return $this->listFactures($model);
        }
    }

    private function listFactures(Restauration $model) {
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        if ($userRole === 'admin') $residences = $this->model('Residence')->getAllSimple();
        $residenceIds = array_column($residences, 'id');

        $statut = $_GET['statut'] ?? null;
        $dateDebut = $_GET['date_debut'] ?? date('Y-m-01');
        $dateFin = $_GET['date_fin'] ?? date('Y-m-t');

        $this->view('restauration/factures', [
            'title'      => 'Factures Restauration - ' . APP_NAME,
            'showNavbar' => true,
            'userRole'   => $userRole,
            'factures'   => $model->getFactures($residenceIds, $statut, $dateDebut, $dateFin),
            'stats'      => $model->getFacturationStats($residenceIds),
            'dateDebut'  => $dateDebut,
            'dateFin'    => $dateFin,
            'statut'     => $statut,
            'isManager'  => in_array($userRole, ['admin', 'restauration_manager']),
            'flash'      => $this->getFlash()
        ], true);
    }

    private function createFacture(Restauration $model) {
        $this->requireRole(self::ROLES_MANAGER);

        $userId = (int)$_SESSION['user_id'];
        $residences = $model->getResidencesByUser($userId);
        if ($_SESSION['user_role'] === 'admin') $residences = $this->model('Residence')->getAllSimple();

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $residents = []; $hotes = []; $repasNonFactures = []; $tarifs = [];
        if ($selectedResidence) {
            $residents = $model->getResidentsByResidences([$selectedResidence]);
            $hotes = $model->getHotesEnCours($selectedResidence);
            $residentId = (int)($_GET['resident_id'] ?? 0);
            $hoteId = (int)($_GET['hote_id'] ?? 0);
            $repasNonFactures = $model->getRepasNonFactures($selectedResidence, $residentId ?: null, $hoteId ?: null);
            $tarifs = $model->getTarifs($selectedResidence);
        }

        $this->view('restauration/facture_form', [
            'title'              => 'Nouvelle Facture - ' . APP_NAME,
            'showNavbar'         => true,
            'residences'         => $residences,
            'selectedResidence'  => $selectedResidence,
            'residents'          => $residents,
            'hotes'              => $hotes,
            'repasNonFactures'   => $repasNonFactures,
            'tarifs'             => $tarifs,
            'flash'              => $this->getFlash()
        ], true);
    }

    private function storeFacture(Restauration $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        try {
            $lignes = [];
            foreach ($_POST['lignes'] ?? [] as $l) {
                if (!empty($l['designation']) && !empty($l['prix_unitaire'])) {
                    $lignes[] = [
                        'service_repas_id' => $l['service_repas_id'] ?? null,
                        'designation'      => $l['designation'],
                        'type_ligne'       => $l['type_ligne'] ?? 'menu_complet',
                        'quantite'         => (int)($l['quantite'] ?? 1),
                        'prix_unitaire'    => (float)$l['prix_unitaire'],
                    ];
                }
            }

            if (empty($lignes)) throw new Exception("Au moins une ligne est requise.");

            $factureId = $model->createFacture([
                'residence_id'  => (int)$_POST['residence_id'],
                'type_client'   => $_POST['type_client'],
                'resident_id'   => $_POST['type_client'] === 'resident' ? (int)$_POST['client_id'] : null,
                'hote_id'       => $_POST['type_client'] === 'hote' ? (int)$_POST['client_id'] : null,
                'nom_passage'   => $_POST['type_client'] === 'passage' ? trim($_POST['nom_passage'] ?? '') : null,
                'taux_tva'      => (float)($_POST['taux_tva'] ?? 10),
                'mode_paiement' => $_POST['mode_paiement'] ?? null,
                'statut'        => $_POST['statut'] ?? 'emise',
                'notes'         => trim($_POST['notes'] ?? ''),
                'created_by'    => $_SESSION['user_id'],
            ], $lignes);

            $this->setFlash('success', 'Facture créée avec succès');
            $this->redirect('restauration/factures/show/' . $factureId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('restauration/factures/create');
        }
    }

    private function showFacture(Restauration $model, $id) {
        $facture = $model->getFacture($id);
        if (!$facture) {
            $this->setFlash('error', 'Facture introuvable');
            $this->redirect('restauration/factures');
            return;
        }

        $this->view('restauration/facture_show', [
            'title'   => 'Facture ' . $facture['numero_facture'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'facture' => $facture,
            'isManager' => in_array($_SESSION['user_role'], ['admin', 'restauration_manager']),
            'flash'   => $this->getFlash()
        ], true);
    }

    private function payerFacture(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);
        $modePaiement = $_GET['mode'] ?? $_POST['mode_paiement'] ?? 'cb';
        $model->updateFactureStatut($id, 'payee', $modePaiement);
        $this->setFlash('success', 'Facture marquée comme payée');
        $this->redirect('restauration/factures/show/' . $id);
    }

    // =================================================================
    //  PRODUITS — CATALOGUE (manager only)
    // =================================================================

    public function produits($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Restauration');
        switch ($action) {
            case 'create':  return $this->storeProduit($model);
            case 'edit':    return $this->editProduit($model, $id);
            case 'update':  return $this->updateProduitAction($model, $id);
            case 'delete':  $model->deleteProduit($id); $this->setFlash('success','Produit désactivé'); $this->redirect('restauration/produits'); return;
            default:        return $this->listProduits($model);
        }
    }

    private function listProduits(Restauration $model) {
        $fm = new Fournisseur();
        $this->view('restauration/produits', [
            'title'        => 'Produits - Restauration - ' . APP_NAME,
            'showNavbar'   => true,
            'produits'     => $model->getAllProduits($_GET['categorie'] ?? null),
            'fournisseurs' => $model->getFournisseursList(),
            'produitFournisseurs'     => [],
            'fournisseursDisponibles' => $fm->getAll('restauration', null, true),
            'flash'        => $this->getFlash()
        ], true);
    }

    private function storeProduit(Restauration $model) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('restauration/produits'); return; }
        $this->verifyCsrf();
        try { $model->createProduit($_POST); $this->setFlash('success','Produit créé'); }
        catch (Exception $e) { $this->setFlash('error','Erreur : '.$e->getMessage()); }
        $this->redirect('restauration/produits');
    }

    private function editProduit(Restauration $model, $id) {
        $produit = $model->getProduit($id);
        if (!$produit) { $this->setFlash('error','Produit introuvable'); $this->redirect('restauration/produits'); return; }
        $fm = new Fournisseur();
        $this->view('restauration/produit_edit', [
            'title'=>'Modifier produit - '.APP_NAME, 'showNavbar'=>true,
            'produit'=>$produit, 'fournisseurs'=>$model->getFournisseursList(),
            'produitFournisseurs'     => $fm->getFournisseursDuProduit('restauration', (int)$id),
            'fournisseursDisponibles' => $fm->getAll('restauration', null, true),
            'flash'=>$this->getFlash()
        ], true);
    }

    private function updateProduitAction(Restauration $model, $id) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { $this->redirect('restauration/produits'); return; }
        $this->verifyCsrf();
        try { $model->updateProduit($id, $_POST); $this->setFlash('success','Produit modifié'); }
        catch (Exception $e) { $this->setFlash('error','Erreur : '.$e->getMessage()); }
        $this->redirect('restauration/produits');
    }

    // =================================================================
    //  INVENTAIRE — STOCK PAR RÉSIDENCE (manager + cuisine)
    // =================================================================

    public function inventaire($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin','restauration_manager','restauration_cuisine']);

        $model = $this->model('Restauration');
        switch ($action) {
            case 'ajouter':       return $this->ajouterInventaire($model);
            case 'modifier':      return $this->modifierInventaire($model, $id);
            case 'mouvement':     return $this->mouvementInventaire($model, $id);
            case 'historique':    return $this->historiqueInventaire($model, $id);
            default:              return $this->listInventaire($model);
        }
    }

    private function listInventaire(Restauration $model) {
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        $residences = $model->getResidencesByUser($userId);
        if ($userRole === 'admin') $residences = $this->model('Residence')->getAllSimple();

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $inventaire = []; $alertes = 0; $produitsHors = [];
        if ($selectedResidence) {
            $categorie = $_GET['categorie'] ?? null;
            $alertesOnly = isset($_GET['alertes']);
            $inventaire = $model->getInventaire($selectedResidence, $categorie, $alertesOnly);
            $alertes = count(array_filter($inventaire, fn($i) => $i['seuil_alerte'] > 0 && $i['quantite_stock'] <= $i['seuil_alerte']));
            $produitsHors = $model->getProduitsHorsInventaire($selectedResidence);
        }

        $this->view('restauration/inventaire', [
            'title'=>'Inventaire - Restauration - '.APP_NAME, 'showNavbar'=>true,
            'userRole'=>$userRole, 'residences'=>$residences,
            'selectedResidence'=>$selectedResidence,
            'inventaire'=>$inventaire, 'alertes'=>$alertes,
            'produitsHors'=>$produitsHors,
            'isManager'=>in_array($userRole, ['admin','restauration_manager']),
            'flash'=>$this->getFlash()
        ], true);
    }

    private function ajouterInventaire(Restauration $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        try {
            $model->addToInventaire((int)$_POST['produit_id'], (int)$_POST['residence_id'], (float)($_POST['seuil_alerte'] ?? 0), $_POST['emplacement'] ?? null);
            $this->setFlash('success','Produit ajouté à l\'inventaire');
        } catch (Exception $e) { $this->setFlash('error','Erreur : '.$e->getMessage()); }
        $this->redirect('restauration/inventaire?residence_id='.$_POST['residence_id']);
    }

    private function modifierInventaire(Restauration $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        try { $model->updateInventaireItem($id, $_POST); $this->setFlash('success','Inventaire mis à jour'); }
        catch (Exception $e) { $this->setFlash('error','Erreur : '.$e->getMessage()); }
        $this->redirect('restauration/inventaire?residence_id='.($_POST['residence_id'] ?? ''));
    }

    private function mouvementInventaire(Restauration $model, $id) {
        $this->verifyCsrf();
        $type = $_POST['type_mouvement'] ?? 'sortie';
        $quantite = (float)($_POST['quantite'] ?? 0);
        $motif = $_POST['motif'] ?? 'consommation';
        if ($quantite <= 0) { $this->setFlash('error','Quantité invalide'); $this->redirect('restauration/inventaire'); return; }

        if ($model->mouvementStock($id, $type, $quantite, $motif, null, $_POST['notes'] ?? null)) {
            $this->setFlash('success', ucfirst($type).' de '.$quantite.' enregistré(e)');
        } else {
            $this->setFlash('error','Erreur lors du mouvement');
        }
        $this->redirect('restauration/inventaire?residence_id='.($_POST['residence_id'] ?? ''));
    }

    private function historiqueInventaire(Restauration $model, $id) {
        $item = $model->getInventaireItem($id);
        if (!$item) { $this->setFlash('error','Produit introuvable'); $this->redirect('restauration/inventaire'); return; }

        $this->view('restauration/inventaire_historique', [
            'title'=>'Historique stock - '.APP_NAME, 'showNavbar'=>true,
            'item'=>$item, 'mouvements'=>$model->getMouvements($id, 50), 'flash'=>$this->getFlash()
        ], true);
    }

    // =================================================================
    //  COMMANDES FOURNISSEURS (manager only)
    // =================================================================

    public function commandes($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $cm = new Commande();
        $model = $this->model('Restauration');
        $fm    = new Fournisseur();
        $modulePath = 'restauration';

        switch ($action) {
            case 'create':
                $userId = (int)$_SESSION['user_id'];
                $residences = $_SESSION['user_role'] === 'admin' ? $this->model('Residence')->getAllSimple() : $model->getResidencesByUser($userId);
                $residenceId = (int)($_GET['residence_id'] ?? 0);
                if (!$residenceId && count($residences) === 1) $residenceId = (int)$residences[0]['id'];
                $this->view($modulePath . '/commande_form', [
                    'title'             => 'Nouvelle commande - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $residenceId,
                    'fournisseurs'      => $residenceId ? $fm->getFournisseursResidence($residenceId, 'restauration') : [],
                    'produits'          => $model->getAllProduits(null, true),
                    'flash'             => $this->getFlash()
                ], true);
                return;

            case 'store':
                $this->verifyCsrf();
                try {
                    $lignes = $this->buildLignesFromPost($_POST['lignes'] ?? [], $model, 5.5);
                    if (empty($lignes)) throw new Exception("Au moins une ligne est requise");
                    $cmdId = $cm->create('restauration', [
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
                if (!$cmd || $cmd['module'] !== 'restauration') { $this->setFlash('error', 'Commande introuvable'); $this->redirect($modulePath . '/commandes'); return; }
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
                $userId = (int)$_SESSION['user_id'];
                $residences = $_SESSION['user_role'] === 'admin' ? $this->model('Residence')->getAllSimple() : $model->getResidencesByUser($userId);
                $this->view($modulePath . '/commandes', [
                    'title'      => 'Commandes Restauration - ' . APP_NAME,
                    'showNavbar' => true,
                    'commandes'  => $cm->getAll('restauration', array_column($residences, 'id'), $_GET['statut'] ?? null),
                    'statut'     => $_GET['statut'] ?? null,
                    'flash'      => $this->getFlash()
                ], true);
        }
    }

    private function buildLignesFromPost(array $postLignes, $model, float $tvaDefault = 20.0): array {
        $lignes = [];
        foreach ($postLignes as $l) {
            if (empty($l['produit_id']) || empty($l['quantite_commandee'])) continue;
            $p = $model->getProduit((int)$l['produit_id']);
            if (!$p) continue;
            $lignes[] = [
                'produit_id'         => (int)$l['produit_id'],
                'designation'        => trim($l['designation'] ?? ($p['nom'] ?? '')),
                'quantite_commandee' => (float)$l['quantite_commandee'],
                'prix_unitaire_ht'   => (float)($l['prix_unitaire_ht'] ?? $p['prix_reference'] ?? $p['prix_unitaire'] ?? 0),
                'taux_tva'           => (float)($l['taux_tva'] ?? $tvaDefault),
            ];
        }
        return $lignes;
    }

    // =================================================================
    //  COMPTABILITÉ RESTAURATION (manager only)
    // =================================================================

    public function comptabilite($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Restauration');
        switch ($action) {
            case 'ecriture':  return $this->storeEcriture($model);
            case 'export':    return $this->exportComptabilite($model);
            default:          return $this->dashboardComptabilite($model);
        }
    }

    private function dashboardComptabilite(Restauration $model) {
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        $residences = $model->getResidencesByUser($userId);
        if ($userRole === 'admin') $residences = $this->model('Residence')->getAllSimple();
        $residenceIds = array_column($residences, 'id');

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;

        $annee = (int)($_GET['annee'] ?? date('Y'));
        $mois = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        $totaux = $model->getTotauxAnnuels($filteredIds, $annee);
        $synthese = $model->getSyntheseMensuelle($filteredIds, $annee);
        $parCategorie = $model->getSyntheseParCategorie($filteredIds, $annee, $mois);
        $tva = $model->getTVA($filteredIds, $annee, $mois);
        $ecritures = $model->getEcritures($filteredIds, $annee, $mois);
        $depensesFournisseurs = $model->getDepensesParFournisseur($filteredIds, $annee, $mois);

        // Séparer recettes et dépenses par catégorie
        $recettesCat = []; $depensesCat = [];
        foreach ($parCategorie as $c) {
            if ($c['type_ecriture'] === 'recette') $recettesCat[] = $c;
            else $depensesCat[] = $c;
        }

        // Préparer données graphique mensuel
        $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        $recettesData = array_fill(0, 12, 0);
        $depensesData = array_fill(0, 12, 0);
        foreach ($synthese as $s) {
            $idx = $s['mois'] - 1;
            $recettesData[$idx] = (float)$s['recettes_ttc'];
            $depensesData[$idx] = (float)$s['depenses_ttc'];
        }

        $this->view('restauration/comptabilite', [
            'title'             => 'Comptabilité Restauration - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'annee'             => $annee,
            'mois'              => $mois,
            'totaux'            => $totaux,
            'tva'               => $tva,
            'recettesCat'       => $recettesCat,
            'depensesCat'       => $depensesCat,
            'ecritures'         => $ecritures,
            'depensesFournisseurs' => $depensesFournisseurs,
            'moisLabels'        => json_encode($moisLabels),
            'recettesData'      => json_encode($recettesData),
            'depensesData'      => json_encode($depensesData),
            'flash'             => $this->getFlash()
        ], true);
    }

    private function storeEcriture(Restauration $model) {
        $this->verifyCsrf();
        try {
            $montantHt = (float)$_POST['montant_ht'];
            $tauxTva = (float)($_POST['taux_tva'] ?? 0);
            $montantTva = round($montantHt * $tauxTva / 100, 2);

            $model->createEcriture([
                'residence_id'   => (int)$_POST['residence_id'],
                'date_ecriture'  => $_POST['date_ecriture'] ?? date('Y-m-d'),
                'type_ecriture'  => $_POST['type_ecriture'],
                'categorie'      => $_POST['categorie'],
                'reference_id'   => !empty($_POST['reference_id']) ? (int)$_POST['reference_id'] : null,
                'reference_type' => $_POST['reference_type'] ?? null,
                'libelle'        => trim($_POST['libelle']),
                'montant_ht'     => $montantHt,
                'montant_tva'    => $montantTva,
                'montant_ttc'    => $montantHt + $montantTva,
                'compte_comptable' => trim($_POST['compte_comptable'] ?? '') ?: null,
                'mois'           => (int)date('m', strtotime($_POST['date_ecriture'] ?? 'now')),
                'annee'          => (int)date('Y', strtotime($_POST['date_ecriture'] ?? 'now')),
                'notes'          => trim($_POST['notes'] ?? ''),
            ]);
            $this->setFlash('success', 'Écriture comptable enregistrée');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('restauration/comptabilite?residence_id=' . ($_POST['residence_id'] ?? '') . '&annee=' . ($_POST['annee'] ?? date('Y')));
    }

    private function exportComptabilite(Restauration $model) {
        $userId = (int)$_SESSION['user_id'];
        $residences = $model->getResidencesByUser($userId);
        if ($_SESSION['user_role'] === 'admin') $residences = $this->model('Residence')->getAllSimple();
        $residenceIds = array_column($residences, 'id');

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;
        $annee = (int)($_GET['annee'] ?? date('Y'));
        $mois = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        $ecritures = $model->getEcrituresExport($filteredIds, $annee, $mois);

        $filename = 'comptabilite_resto_' . $annee . ($mois ? '_' . str_pad($mois, 2, '0', STR_PAD_LEFT) : '') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
        fputcsv($output, ['Date', 'Compte', 'Libellé', 'Type', 'Catégorie', 'Montant HT', 'TVA', 'Montant TTC', 'Résidence', 'Référence'], ';');

        foreach ($ecritures as $e) {
            fputcsv($output, [
                $e['date_ecriture'], $e['compte_comptable'] ?? '', $e['libelle'],
                $e['type_ecriture'], str_replace('_', ' ', $e['categorie']),
                number_format($e['montant_ht'], 2, ',', ''),
                number_format($e['montant_tva'], 2, ',', ''),
                number_format($e['montant_ttc'], 2, ',', ''),
                $e['residence_nom'], ($e['reference_type'] ?? '') . '#' . ($e['reference_id'] ?? '')
            ], ';');
        }
        fclose($output);
        exit;
    }

    // =================================================================
    //  FOURNISSEURS RESTAURATION (manager only)
    // =================================================================

    public function fournisseurs($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Restauration');
        $fm    = new Fournisseur();

        switch ($action) {
            // Legacy : anciennes routes redirigées vers la page globale
            case 'show':
                $this->redirect('fournisseur/show/' . (int)$id);
                return;
            case 'edit':
                $this->redirect('fournisseur/edit/' . (int)$id);
                return;

            case 'lier':
                $this->verifyCsrf();
                try {
                    $fournId = (int)($_POST['fournisseur_id'] ?? 0);
                    $resId   = (int)($_POST['residence_id'] ?? 0);
                    if (!$fournId || !$resId) throw new Exception("Fournisseur et résidence requis");
                    $fm->lier($fournId, $resId, $_POST);
                    $this->setFlash('success', 'Fournisseur lié à la résidence');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('restauration/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'update':
                $this->verifyCsrf();
                try {
                    $fm->updateLien((int)$id, $_POST);
                    $this->setFlash('success', 'Lien fournisseur modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('restauration/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? $_GET['residence_id'] ?? ''));
                return;

            case 'delier':
                $fm->delier((int)$id);
                $this->setFlash('success', 'Lien fournisseur désactivé');
                $this->redirect('restauration/fournisseurs?residence_id=' . ($_GET['residence_id'] ?? ''));
                return;

            default:
                $userId = (int)$_SESSION['user_id'];
                $userRole = $_SESSION['user_role'];
                $residences = $model->getResidencesByUser($userId);
                if ($userRole === 'admin') $residences = $this->model('Residence')->getAllSimple();
                $selectedResidence = (int)($_GET['residence_id'] ?? 0);
                if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

                $this->view('restauration/fournisseurs', [
                    'title'             => 'Fournisseurs - Restauration - ' . APP_NAME,
                    'showNavbar'        => true,
                    'residences'        => $residences,
                    'selectedResidence' => $selectedResidence,
                    'fournisseurs'      => $selectedResidence ? $model->getFournisseursResidence($selectedResidence) : [],
                    'disponibles'       => $selectedResidence ? $fm->getFournisseursDisponibles($selectedResidence, 'restauration') : [],
                    'flash'             => $this->getFlash()
                ], true);
        }
    }

    /**
     * Laverie restauration — cycles d'envoi/retour (linge de salle, prestataire interne)
     * Accès : admin, restauration_manager, restauration_cuisine
     */
    public function laverie($action = null, $id = null) {
        $this->requireAuth();
        $rolesLaverie = ['admin', 'restauration_manager', 'restauration_cuisine'];
        $this->requireRole($rolesLaverie);

        $model = $this->model('Restauration');
        $userId = (int)$_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];

        $residences = $model->getResidencesByUser($userId);
        $residenceIds = array_column($residences, 'id');
        if ($userRole === 'admin') {
            $resModel = $this->model('Residence');
            $residences = $resModel->getAllSimple();
            $residenceIds = array_column($residences, 'id');
        }

        // POST actions
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            if ($action === 'create') {
                $residenceId = (int)($_POST['residence_id'] ?? 0);
                if (!in_array($residenceId, $residenceIds, true)) {
                    $this->setFlash('error', 'Résidence non autorisée');
                    $this->redirect('restauration/laverie');
                    return;
                }
                $newId = $model->createLaverieCycle([
                    'residence_id'     => $residenceId,
                    'type_linge'       => $_POST['type_linge'] ?? 'autre',
                    'quantite_envoyee' => max(1, (int)($_POST['quantite_envoyee'] ?? 1)),
                    'date_envoi'       => $_POST['date_envoi'] ?: date('Y-m-d H:i:s'),
                    'cout'             => (float)($_POST['cout'] ?? 0),
                    'user_envoi_id'    => $userId,
                    'notes'            => trim($_POST['notes'] ?? '') ?: null,
                ]);
                $this->setFlash($newId ? 'success' : 'error', $newId ? 'Envoi enregistré' : 'Erreur lors de la création');
                $this->redirect('restauration/laverie?residence_id=' . $residenceId);
                return;
            }

            if ($action === 'reception' && $id) {
                $cycle = $model->getLaverieCycle((int)$id);
                if (!$cycle || !in_array((int)$cycle['residence_id'], $residenceIds, true)) {
                    $this->setFlash('error', 'Cycle introuvable ou non autorisé');
                    $this->redirect('restauration/laverie');
                    return;
                }
                $ok = $model->receptionnerLaverieCycle(
                    (int)$id,
                    (int)($_POST['quantite_recue'] ?? 0),
                    $userId,
                    trim($_POST['notes'] ?? '') ?: null
                );
                $this->setFlash($ok ? 'success' : 'error', $ok ? 'Réception enregistrée' : 'Erreur lors de la réception');
                $this->redirect('restauration/laverie?residence_id=' . $cycle['residence_id']);
                return;
            }

            if ($action === 'delete' && $id) {
                $cycle = $model->getLaverieCycle((int)$id);
                if (!$cycle || !in_array((int)$cycle['residence_id'], $residenceIds, true)) {
                    $this->setFlash('error', 'Cycle introuvable ou non autorisé');
                    $this->redirect('restauration/laverie');
                    return;
                }
                if (!in_array($userRole, self::ROLES_MANAGER, true)) {
                    $this->setFlash('error', 'Suppression réservée au manager');
                    $this->redirect('restauration/laverie?residence_id=' . $cycle['residence_id']);
                    return;
                }
                $ok = $model->deleteLaverieCycle((int)$id);
                $this->setFlash($ok ? 'success' : 'error', $ok ? 'Cycle supprimé' : 'Erreur suppression');
                $this->redirect('restauration/laverie?residence_id=' . $cycle['residence_id']);
                return;
            }
        }

        // GET — affichage liste
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;
        $statutFilter = $_GET['statut'] ?? null;
        $typeFilter   = $_GET['type_linge'] ?? null;

        $cycles = $model->getLaverieCycles($filteredIds, $statutFilter ?: null, $typeFilter ?: null);
        $stats  = $model->getLaverieStats($filteredIds, (int)date('Y'));

        $this->view('restauration/laverie', [
            'title'             => 'Laverie - Restauration - ' . APP_NAME,
            'showNavbar'        => true,
            'userRole'          => $userRole,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'cycles'            => $cycles,
            'stats'             => $stats,
            'isManager'         => in_array($userRole, self::ROLES_MANAGER, true),
            'flash'             => $this->getFlash(),
        ], true);
    }
}
