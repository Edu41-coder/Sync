<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Ménage
 * ====================================================================
 * Module ménage pour les résidences seniors.
 * Sections : intérieur, extérieur, laverie
 * Rôles : entretien_manager, menage_interieur, menage_exterieur, employe_laverie
 *
 * Phase 1 : Dashboard, planning, zones, navbar
 */

class MenageController extends Controller {

    private const ROLES_ALL = ['admin', 'directeur_residence', 'entretien_manager', 'menage_interieur', 'menage_exterieur', 'employe_laverie'];
    private const ROLES_MANAGER = ['admin', 'directeur_residence', 'entretien_manager'];

    /**
     * Helper : résidences de l'utilisateur (ou toutes si admin)
     */
    private function getUserResidences(): array {
        $model = $this->model('Menage');
        if ($_SESSION['user_role'] === 'admin') {
            return $this->model('Residence')->getAllSimple();
        }
        return $model->getResidencesByUser((int)$_SESSION['user_id']);
    }

    /**
     * Helper : section autorisée pour l'employé
     */
    private function getUserSection(): ?string {
        $role = $_SESSION['user_role'];
        if (in_array($role, ['admin', 'directeur_residence', 'entretien_manager'])) return null; // toutes sections
        if ($role === 'menage_interieur') return 'interieur';
        if ($role === 'menage_exterieur') return 'exterieur';
        if ($role === 'employe_laverie') return 'laverie';
        return null;
    }

    // =================================================================
    //  DASHBOARD
    // =================================================================

    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $model = $this->model('Menage');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;

        $userSection = $this->getUserSection();
        $isManager = in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager']);

        $this->view('menage/dashboard', [
            'title'              => 'Ménage - ' . APP_NAME,
            'showNavbar'         => true,
            'userRole'           => $_SESSION['user_role'],
            'userSection'        => $userSection,
            'isManager'          => $isManager,
            'residences'         => $residences,
            'selectedResidence'  => $selectedResidence,
            'statsJour'          => $model->getStatsDuJour($filteredIds),
            'statsSection'       => $model->getStatsParSection($filteredIds),
            'statsMois'          => $model->getStatsMois($filteredIds),
            'tachesRecentes'     => $model->getTachesRecentes($filteredIds),
            'alertesStock'       => $isManager ? $model->getAlertesStock($filteredIds) : [],
            'laverieEnAttente'   => $isManager || $userSection === 'laverie' ? $model->getLaverieEnAttente($filteredIds) : [],
            'flash'              => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  ÉQUIPE
    // =================================================================

    public function equipe() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Menage');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $this->view('menage/equipe', [
            'title'      => 'Équipe Ménage - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'staff'      => $model->getStaffByResidences($residenceIds),
            'flash'      => $this->getFlash()
        ], true);
    }

    // =================================================================
    //  PLANNING (shifts TUI Calendar)
    // =================================================================

    public function planning() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $model = $this->model('Menage');
        $planningModel = $this->model('Planning');
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');

        $staff = $model->getStaffByResidences($residenceIds);

        $this->view('menage/planning', [
            'title'      => 'Planning Ménage - ' . APP_NAME,
            'showNavbar' => true,
            'userRole'   => $_SESSION['user_role'],
            'userId'     => (int)$_SESSION['user_id'],
            'residences' => $residences,
            'staff'      => $staff,
            'categories' => $planningModel->getCategories(),
            'canManage'  => in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager']),
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * AJAX planning ménage
     */
    public function planningAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        header('Content-Type: application/json; charset=utf-8');

        $planningModel = $this->model('Planning');
        $menageModel = $this->model('Menage');
        $residenceIds = $menageModel->getResidenceIdsByUser((int)$_SESSION['user_id']);
        if ($_SESSION['user_role'] === 'admin') $residenceIds = null;

        try {
            switch ($action) {
                case 'getEvents':
                    $start = $_GET['start'] ?? date('Y-m-01');
                    $end = $_GET['end'] ?? date('Y-m-t');
                    $residenceId = (int)($_GET['residence_id'] ?? 0);
                    $filterUserId = (int)($_GET['user_id'] ?? 0);

                    $shifts = $planningModel->getShifts($start, $end, $residenceId, $filterUserId);

                    $menageRoles = ['entretien_manager', 'menage_interieur', 'menage_exterieur', 'employe_laverie'];
                    $shifts = array_filter($shifts, function($s) use ($menageRoles, $residenceIds) {
                        $roleOk = in_array($s['employe_role'], $menageRoles);
                        $residenceOk = $residenceIds === null || in_array($s['residence_id'], $residenceIds);
                        return $roleOk && $residenceOk;
                    });

                    $events = array_values(array_map(function($s) {
                        return [
                            'id' => $s['id'], 'calendarId' => $s['residence_id'],
                            'title' => $s['employe_prenom'] . ' ' . $s['employe_nom'] . ' — ' . $s['titre'],
                            'start' => $s['date_debut'], 'end' => $s['date_fin'],
                            'isAllDay' => (bool)$s['journee_entiere'],
                            'calendarColor' => $s['couleur'] ?? '#0dcaf0',
                            'categoryColor' => $s['bg_couleur'] ?? '#cff4fc',
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

                case 'getEvent':
                    $id = (int)($_GET['id'] ?? 0);
                    if (!$id) { echo json_encode(['success' => false, 'message' => 'ID requis']); break; }
                    $shift = $planningModel->getShift($id);
                    if (!$shift) { echo json_encode(['success' => false, 'message' => 'Shift introuvable']); break; }
                    echo json_encode(['success' => true, 'event' => [
                        'id' => $shift['id'], 'calendarId' => $shift['residence_id'],
                        'title' => $shift['titre'], 'start' => $shift['date_debut'], 'end' => $shift['date_fin'],
                        'isAllDay' => (bool)$shift['journee_entiere'],
                        'raw' => ['userId' => $shift['user_id'], 'residenceId' => $shift['residence_id'],
                            'categoryId' => $shift['category_id'], 'typeShift' => $shift['type_shift'],
                            'typeHeures' => $shift['type_heures'], 'statut' => $shift['statut'],
                            'description' => $shift['description'], 'notes' => $shift['notes']]
                    ]]);
                    break;

                case 'save':
                    $this->requireRole(self::ROLES_MANAGER);
                    $this->verifyCsrfHeader();
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input) { echo json_encode(['success' => false, 'message' => 'Données invalides']); break; }
                    $saveUserId = (int)($input['userId'] ?? 0);
                    $saveResidenceId = (int)($input['residenceId'] ?? 0);
                    $saveTitre = trim($input['title'] ?? '');
                    if (!$saveUserId || !$saveResidenceId || !$saveTitre) {
                        echo json_encode(['success' => false, 'message' => 'Champs requis']); break;
                    }
                    $savedId = $planningModel->saveShift([
                        'id' => $input['id'] ?? null, 'userId' => $saveUserId, 'residenceId' => $saveResidenceId,
                        'categoryId' => (int)($input['categoryId'] ?? 0) ?: null, 'title' => $saveTitre,
                        'start' => $input['start'], 'end' => $input['end'], 'isAllDay' => $input['isAllDay'] ?? false,
                        'typeShift' => $input['typeShift'] ?? 'travail', 'typeHeures' => $input['typeHeures'] ?? 'normales',
                        'description' => trim($input['description'] ?? ''), 'notes' => trim($input['notes'] ?? ''),
                    ]);
                    echo json_encode(['success' => true, 'message' => ($input['id'] ?? null) ? 'Shift modifié' : 'Shift créé', 'id' => $savedId]);
                    break;

                case 'move':
                    $this->requireRole(self::ROLES_MANAGER);
                    $this->verifyCsrfHeader();
                    $input = json_decode(file_get_contents('php://input'), true);
                    if (!$input || empty($input['id'])) { echo json_encode(['success' => false, 'message' => 'Données invalides']); break; }
                    $planningModel->moveShift((int)$input['id'], $input['start'], $input['end']);
                    echo json_encode(['success' => true, 'message' => 'Shift déplacé']);
                    break;

                case 'delete':
                    $this->requireRole(self::ROLES_MANAGER);
                    $this->verifyCsrfHeader();
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
    //  ZONES EXTÉRIEURES (CRUD - manager only)
    // =================================================================

    public function zones($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Menage');
        switch ($action) {
            case 'create':  return $this->storeZone($model);
            case 'update':  return $this->updateZoneAction($model, $id);
            case 'delete':  $this->requirePostCsrf(); $model->deleteZone($id); $this->setFlash('success', 'Zone désactivée'); $this->redirect('menage/zones'); return;
            default:        return $this->listZones($model);
        }
    }

    private function listZones(Menage $model) {
        $residences = $this->getUserResidences();
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $this->view('menage/zones', [
            'title'             => 'Zones Extérieures - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'zones'             => $selectedResidence ? $model->getZones($selectedResidence) : [],
            'flash'             => $this->getFlash()
        ], true);
    }

    private function storeZone(Menage $model) {
        $this->verifyCsrf();
        try {
            $model->createZone($_POST);
            $this->setFlash('success', 'Zone créée');
        } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
        $this->redirect('menage/zones?residence_id=' . ($_POST['residence_id'] ?? ''));
    }

    private function updateZoneAction(Menage $model, $id) {
        $this->verifyCsrf();
        try {
            $model->updateZone($id, $_POST);
            $this->setFlash('success', 'Zone modifiée');
        } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
        $this->redirect('menage/zones?residence_id=' . ($_POST['residence_id'] ?? ''));
    }

    // =================================================================
    //  MÉNAGE INTÉRIEUR
    // =================================================================

    public function interieur($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'entretien_manager', 'menage_interieur']);

        $model = $this->model('Menage');
        switch ($action) {
            case 'generer':     return $this->genererTaches($model);
            case 'distribuer':  return $this->distribuerTaches($model);
            case 'tache':       return $this->voirTache($model, $id);
            case 'demarrer':    return $this->demarrerTacheAction($model, $id);
            case 'terminer':    return $this->terminerTacheAction($model, $id);
            case 'pasDeranger': return $this->pasDeranger($model, $id);
            case 'reassigner':  return $this->reassignerTacheAction($model, $id);
            case 'cocherItem':  return $this->cocherItemAction($model);
            default:            return $this->pageInterieur($model);
        }
    }

    private function pageInterieur(Menage $model) {
        $residences = $this->getUserResidences();
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];

        $date = $_GET['date'] ?? date('Y-m-d');
        $isManager = in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager']);
        $userId = (int)$_SESSION['user_id'];

        $taches = []; $affectations = []; $mesTaches = [];
        $dejaGenere = false;

        if ($selectedResidence) {
            $dejaGenere = $model->tachesDejaGenerees($selectedResidence, $date, 'interieur');

            if ($isManager) {
                $taches = $model->getTachesDuJour($selectedResidence, $date, 'interieur');
                $affectations = $model->getAffectationsDuJour($selectedResidence, $date, 'interieur');
            }

            // Mes tâches (pour l'employé ou le manager veut voir)
            $mesTaches = $model->getMesTaches($userId, $date);
            // Filtrer pour ne garder que l'intérieur
            $mesTaches = array_filter($mesTaches, fn($t) => $t['type_tache'] === 'interieur');
        }

        // Staff pour réaffectation (manager)
        $staff = $isManager && $selectedResidence ? $model->getStaffByResidences([$selectedResidence], 'interieur') : [];

        $this->view('menage/interieur', [
            'title'             => 'Ménage Intérieur - ' . APP_NAME,
            'showNavbar'        => true,
            'userRole'          => $_SESSION['user_role'],
            'isManager'         => $isManager,
            'residences'        => $residences,
            'selectedResidence' => $selectedResidence,
            'date'              => $date,
            'dejaGenere'        => $dejaGenere,
            'taches'            => $taches,
            'mesTaches'         => array_values($mesTaches),
            'affectations'      => $affectations,
            'staff'             => $staff,
            'flash'             => $this->getFlash()
        ], true);
    }

    private function genererTaches(Menage $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $residenceId = (int)($_POST['residence_id'] ?? $_GET['residence_id'] ?? 0);
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');

        $count = $model->genererTachesInterieur($residenceId, $date);
        if ($count > 0) {
            $this->setFlash('success', "$count tâche(s) générée(s) pour le " . date('d/m/Y', strtotime($date)));
        } else {
            $this->setFlash('info', 'Tâches déjà générées ou aucun lot éligible.');
        }
        $this->redirect("menage/interieur?residence_id=$residenceId&date=$date");
    }

    private function distribuerTaches(Menage $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $residenceId = (int)($_POST['residence_id'] ?? $_GET['residence_id'] ?? 0);
        $date = $_POST['date'] ?? $_GET['date'] ?? date('Y-m-d');

        $result = $model->distribuerTachesInterieur($residenceId, $date);
        if (isset($result['error'])) {
            $this->setFlash('error', $result['error']);
        } else {
            $this->setFlash('success', $result['distributed'] . ' tâche(s) distribuée(s) entre ' . count($result['employes']) . ' employé(s)');
        }
        $this->redirect("menage/interieur?residence_id=$residenceId&date=$date");
    }

    private function voirTache(Menage $model, $id) {
        $tache = $model->getTache($id);
        if (!$tache) { $this->setFlash('error', 'Tâche introuvable'); $this->redirect('menage/interieur'); return; }

        $isManager = in_array($_SESSION['user_role'], ['admin', 'directeur_residence', 'entretien_manager']);
        $staff = $isManager ? $model->getStaffByResidences([$tache['residence_id']], 'interieur') : [];

        $this->view('menage/tache', [
            'title'     => 'Tâche #' . $id . ' - ' . APP_NAME,
            'showNavbar' => true,
            'tache'     => $tache,
            'isManager' => $isManager,
            'isMine'    => $tache['employe_id'] == $_SESSION['user_id'],
            'staff'     => $staff,
            'flash'     => $this->getFlash()
        ], true);
    }

    private function demarrerTacheAction(Menage $model, $id) {
        $model->demarrerTache($id);
        $this->setFlash('success', 'Tâche démarrée');
        $this->redirect('menage/interieur/tache/' . $id);
    }

    private function terminerTacheAction(Menage $model, $id) {
        $this->requirePostCsrf();
        $result = $model->terminerTache($id);
        $this->setFlash($result['success'] ? 'success' : 'error', $result['success'] ? 'Tâche terminée' : $result['message']);
        $this->redirect('menage/interieur/tache/' . $id);
    }

    private function pasDeranger(Menage $model, $id) {
        $model->marquerPasDeranger($id);
        $this->setFlash('info', 'Tâche marquée "Pas déranger"');
        $this->redirect('menage/interieur/tache/' . $id);
    }

    private function reassignerTacheAction(Menage $model, $id) {
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $employeId = (int)$_POST['employe_id'];
        $model->reassignerTache($id, $employeId);
        $this->setFlash('success', 'Tâche réassignée');
        $this->redirect('menage/interieur/tache/' . $id);
    }

    /**
     * AJAX : cocher/décocher un item de checklist
     */
    private function cocherItemAction(Menage $model) {
        $this->verifyCsrfHeader();
        header('Content-Type: application/json');
        $input = json_decode(file_get_contents('php://input'), true);
        $itemId = (int)($input['item_id'] ?? 0);
        $fait = (bool)($input['fait'] ?? false);

        if ($itemId && $model->cocherChecklistItem($itemId, $fait)) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Erreur']);
        }
        exit;
    }

    // =================================================================
    //  MÉNAGE EXTÉRIEUR
    // =================================================================

    public function exterieur($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'entretien_manager', 'menage_exterieur']);

        $model = $this->model('Menage');
        switch ($action) {
            case 'generer':     return $this->genererTachesExt($model);
            case 'distribuer':  return $this->distribuerTachesExt($model);
            case 'tache':       return $this->voirTache($model, $id);  // réutilise la même vue tache
            case 'demarrer':    $model->demarrerTache($id); $this->setFlash('success','Tâche démarrée'); $this->redirect('menage/exterieur/tache/'.$id); return;
            case 'terminer':    $this->requirePostCsrf(); $r = $model->terminerTache($id); $this->setFlash($r['success']?'success':'error', $r['success']?'Terminé':$r['message']); $this->redirect('menage/exterieur/tache/'.$id); return;
            case 'reassigner':  $this->requireRole(self::ROLES_MANAGER); $this->verifyCsrf(); $model->reassignerTache($id,(int)$_POST['employe_id']); $this->setFlash('success','Réassigné'); $this->redirect('menage/exterieur/tache/'.$id); return;
            case 'cocherItem':  return $this->cocherItemAction($model);
            default:            return $this->pageExterieur($model);
        }
    }

    private function pageExterieur(Menage $model) {
        $residences = $this->getUserResidences();
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];
        $date = $_GET['date'] ?? date('Y-m-d');
        $isManager = in_array($_SESSION['user_role'], ['admin','directeur_residence','entretien_manager']);
        $userId = (int)$_SESSION['user_id'];

        $taches = []; $affectations = []; $mesTaches = []; $dejaGenere = false;
        if ($selectedResidence) {
            $dejaGenere = $model->tachesDejaGenerees($selectedResidence, $date, 'exterieur');
            if ($isManager) {
                $taches = $model->getTachesDuJour($selectedResidence, $date, 'exterieur');
                $affectations = $model->getAffectationsDuJour($selectedResidence, $date, 'exterieur');
            }
            $mesTaches = array_values(array_filter($model->getMesTaches($userId, $date), fn($t) => $t['type_tache'] === 'exterieur'));
        }
        $staff = $isManager && $selectedResidence ? $model->getStaffByResidences([$selectedResidence], 'exterieur') : [];

        $this->view('menage/exterieur', [
            'title'=>'Ménage Extérieur - '.APP_NAME, 'showNavbar'=>true,
            'userRole'=>$_SESSION['user_role'], 'isManager'=>$isManager,
            'residences'=>$residences, 'selectedResidence'=>$selectedResidence,
            'date'=>$date, 'dejaGenere'=>$dejaGenere,
            'taches'=>$taches, 'mesTaches'=>$mesTaches,
            'affectations'=>$affectations, 'staff'=>$staff,
            'flash'=>$this->getFlash()
        ], true);
    }

    private function genererTachesExt(Menage $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $rid = (int)($_POST['residence_id'] ?? 0); $date = $_POST['date'] ?? date('Y-m-d');
        $count = $model->genererTachesExterieur($rid, $date);
        $this->setFlash($count > 0 ? 'success' : 'info', $count > 0 ? "$count zone(s) planifiée(s)" : 'Déjà généré ou aucune zone éligible');
        $this->redirect("menage/exterieur?residence_id=$rid&date=$date");
    }

    private function distribuerTachesExt(Menage $model) {
        $this->requireRole(self::ROLES_MANAGER);
        $rid = (int)($_POST['residence_id'] ?? 0); $date = $_POST['date'] ?? date('Y-m-d');
        $result = $model->distribuerTachesExterieur($rid, $date);
        $this->setFlash(isset($result['error']) ? 'error' : 'success', $result['error'] ?? $result['distributed'].' tâche(s) distribuée(s)');
        $this->redirect("menage/exterieur?residence_id=$rid&date=$date");
    }

    // =================================================================
    //  LAVERIE
    // =================================================================

    public function laverie($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin','directeur_residence','entretien_manager','employe_laverie']);

        $model = $this->model('Menage');
        switch ($action) {
            case 'demande':    return $this->creerDemande($model);
            case 'statut':     return $this->changerStatutLaverie($model, $id);
            default:           return $this->pageLaverie($model);
        }
    }

    private function pageLaverie(Menage $model) {
        $residences = $this->getUserResidences();
        $residenceIds = array_column($residences, 'id');
        $selectedResidence = (int)($_GET['residence_id'] ?? 0);
        if (!$selectedResidence && count($residences) === 1) $selectedResidence = $residences[0]['id'];
        $filteredIds = $selectedResidence ? [$selectedResidence] : $residenceIds;
        $statut = $_GET['statut'] ?? null;

        $isManager = in_array($_SESSION['user_role'], ['admin','directeur_residence','entretien_manager']);

        $this->view('menage/laverie', [
            'title'=>'Laverie - '.APP_NAME, 'showNavbar'=>true,
            'userRole'=>$_SESSION['user_role'], 'isManager'=>$isManager,
            'residences'=>$residences, 'selectedResidence'=>$selectedResidence,
            'demandes'=>$model->getLaverieDemandes($filteredIds, $statut),
            'stats'=>$model->getLaverieStats($filteredIds),
            'tarifs'=>$selectedResidence ? $model->getLaverieTarifs($selectedResidence) : [],
            'residents'=>$selectedResidence ? $model->getResidentsResidence($selectedResidence) : [],
            'statut'=>$statut,
            'flash'=>$this->getFlash()
        ], true);
    }

    private function creerDemande(Menage $model) {
        $this->verifyCsrf();
        try {
            $model->creerDemandeLaverie($_POST);
            $this->setFlash('success', 'Demande créée');
        } catch (Exception $e) { $this->setFlash('error', 'Erreur : '.$e->getMessage()); }
        $this->redirect('menage/laverie?residence_id='.($_POST['residence_id'] ?? ''));
    }

    private function changerStatutLaverie(Menage $model, $id) {
        $statut = $_GET['statut'] ?? $_POST['statut'] ?? '';
        $employeId = ($_SESSION['user_role'] === 'employe_laverie') ? (int)$_SESSION['user_id'] : null;
        $model->updateLaverieStatut($id, $statut, $employeId);
        $labels = ['en_cours'=>'Prise en charge','prete'=>'Prêt','livree'=>'Livré','facturee'=>'Facturé','annulee'=>'Annulé'];
        $this->setFlash('success', $labels[$statut] ?? 'Statut mis à jour');
        $this->redirect('menage/laverie?residence_id='.($_GET['residence_id'] ?? ''));
    }

    // =================================================================
    //  PRODUITS MÉNAGE (manager)
    // =================================================================

    public function produits($action = null, $id = null) {
        $this->requireAuth(); $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Menage');
        switch ($action) {
            case 'create':  $this->verifyCsrf(); $model->createProduit($_POST); $this->setFlash('success','Produit créé'); $this->redirect('menage/produits'); return;
            case 'edit':
                $p = $model->getProduit($id); if (!$p) { $this->redirect('menage/produits'); return; }
                $fm = new Fournisseur();
                $this->view('menage/produit_edit', [
                    'title'=>'Modifier produit - '.APP_NAME,'showNavbar'=>true,'produit'=>$p,
                    'fournisseurs'=>$model->getFournisseursList(), // rétrocompat (non utilisé par le widget)
                    'produitFournisseurs' => $fm->getFournisseursDuProduit('menage', (int)$id),
                    'fournisseursDisponibles' => $fm->getAll('menage', null, true),
                    'flash'=>$this->getFlash()
                ], true); return;
            case 'update':  $this->verifyCsrf(); $model->updateProduit($id, $_POST); $this->setFlash('success','Produit modifié'); $this->redirect('menage/produits'); return;
            case 'delete':  $this->requirePostCsrf(); $model->deleteProduit($id); $this->setFlash('success','Désactivé'); $this->redirect('menage/produits'); return;
            default:
                $fm = new Fournisseur();
                $this->view('menage/produits', [
                    'title'=>'Produits Ménage - '.APP_NAME,'showNavbar'=>true,
                    'produits'=>$model->getAllProduits($_GET['categorie'] ?? null, $_GET['section'] ?? null),
                    'fournisseurs'=>$model->getFournisseursList(), // rétrocompat
                    'produitFournisseurs' => [], // modal création : aucun lien au départ
                    'fournisseursDisponibles' => $fm->getAll('menage', null, true),
                    'flash'=>$this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  INVENTAIRE MÉNAGE (manager + employés pour sortie stock)
    // =================================================================

    public function inventaire($action = null, $id = null) {
        $this->requireAuth(); $this->requireRole(self::ROLES_ALL);
        $model = $this->model('Menage');
        switch ($action) {
            case 'ajouter':    $this->requireRole(self::ROLES_MANAGER); $this->verifyCsrf(); $model->addToInventaire((int)$_POST['produit_id'],(int)$_POST['residence_id'],(float)($_POST['seuil_alerte']??0),$_POST['emplacement']??null); $this->setFlash('success','Ajouté'); $this->redirect('menage/inventaire?residence_id='.$_POST['residence_id']); return;
            case 'mouvement':  $this->verifyCsrf(); $model->mouvementStock($id,$_POST['type_mouvement'],(float)$_POST['quantite'],$_POST['motif'],null,$_POST['notes']??null); $this->setFlash('success','Mouvement enregistré'); $this->redirect('menage/inventaire?residence_id='.($_POST['residence_id']??'')); return;
            case 'historique':
                $item = $model->getInventaireItem($id); if (!$item) { $this->redirect('menage/inventaire'); return; }
                $this->view('menage/inventaire_historique', ['title'=>'Historique - '.APP_NAME,'showNavbar'=>true,'item'=>$item,'mouvements'=>$model->getMouvements($id,50),'flash'=>$this->getFlash()], true); return;
            default:
                $residences = $this->getUserResidences(); $sel = (int)($_GET['residence_id'] ?? 0);
                if (!$sel && count($residences) === 1) $sel = $residences[0]['id'];
                $inv = $sel ? $model->getInventaire($sel, $_GET['section'] ?? null, isset($_GET['alertes'])) : [];
                $this->view('menage/inventaire', ['title'=>'Inventaire Ménage - '.APP_NAME,'showNavbar'=>true,'residences'=>$residences,'selectedResidence'=>$sel,'inventaire'=>$inv,
                    'alertes'=>count(array_filter($inv, fn($i)=>$i['seuil_alerte']>0 && $i['quantite_stock']<=$i['seuil_alerte'])),
                    'produitsHors'=>$sel ? $model->getProduitsHorsInventaire($sel) : [],
                    'isManager'=>in_array($_SESSION['user_role'],['admin','directeur_residence','entretien_manager']),'flash'=>$this->getFlash()], true);
        }
    }

    // =================================================================
    //  COMMANDES FOURNISSEURS MÉNAGE (manager)
    // =================================================================

    public function commandes($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $cm = new Commande();
        $model = $this->model('Menage');
        $fm    = new Fournisseur();
        $modulePath = 'menage';

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
                    'fournisseurs'      => $residenceId ? $fm->getFournisseursResidence($residenceId, 'menage') : [],
                    'produits'          => $model->getAllProduits(null, null, true),
                    'flash'             => $this->getFlash()
                ], true);
                return;

            case 'store':
                $this->verifyCsrf();
                try {
                    $lignes = $this->buildLignesFromPost($_POST['lignes'] ?? [], $model);
                    if (empty($lignes)) throw new Exception("Au moins une ligne est requise");
                    $cmdId = $cm->create('menage', [
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
                if (!$cmd || $cmd['module'] !== 'menage') { $this->setFlash('error', 'Commande introuvable'); $this->redirect($modulePath . '/commandes'); return; }
                $this->view($modulePath . '/commande_show', [
                    'title' => 'Commande ' . $cmd['numero_commande'] . ' - ' . APP_NAME,
                    'showNavbar' => true, 'commande' => $cmd, 'flash' => $this->getFlash()
                ], true);
                return;

            case 'envoyer':
                $this->requirePostCsrf();
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
                $this->requirePostCsrf();
                $cm->updateStatut((int)$id, 'facturee');
                $this->setFlash('success', 'Commande marquée comme facturée');
                $this->redirect($modulePath . '/commandes/show/' . (int)$id);
                return;

            case 'delete':
                $this->requirePostCsrf();
                $result = $cm->deleteOrCancel((int)$id);
                $this->setFlash('success', $result === 'deleted' ? 'Commande supprimée' : 'Commande annulée');
                $this->redirect($modulePath . '/commandes');
                return;

            default:
                $residences = $this->getUserResidences();
                $this->view($modulePath . '/commandes', [
                    'title'      => 'Commandes Ménage - ' . APP_NAME,
                    'showNavbar' => true,
                    'commandes'  => $cm->getAll('menage', array_column($residences, 'id'), $_GET['statut'] ?? null),
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
                'prix_unitaire_ht'   => (float)($l['prix_unitaire_ht'] ?? $p['prix_reference'] ?? 0),
                'taux_tva'           => (float)($l['taux_tva'] ?? 20),
            ];
        }
        return $lignes;
    }

    // =================================================================
    //  FOURNISSEURS MÉNAGE (lecture-seule, pivot global fournisseur_residence)
    //  Le CRUD (lier/modifier/délier un fournisseur à une résidence) est
    //  centralisé dans /fournisseur/show/<id> — FournisseurController.
    // =================================================================

    public function fournisseurs($action = null, $id = null) {
        $this->requireAuth(); $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Menage');
        $fm    = new Fournisseur();

        switch ($action) {
            case 'lier':
                $this->verifyCsrf();
                try {
                    $fm->lier((int)$_POST['fournisseur_id'], (int)$_POST['residence_id'], $_POST);
                    $this->setFlash('success', 'Fournisseur lié à la résidence');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('menage/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            case 'update':
                $this->verifyCsrf();
                try {
                    $fm->updateLien((int)$id, $_POST);
                    $this->setFlash('success', 'Lien modifié');
                } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
                $this->redirect('menage/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? $_GET['residence_id'] ?? ''));
                return;

            case 'delier':
                $this->requirePostCsrf();
                $fm->delier((int)$id);
                $this->setFlash('success', 'Lien désactivé');
                $this->redirect('menage/fournisseurs?residence_id=' . ($_POST['residence_id'] ?? ''));
                return;

            default:
                $residences = $this->getUserResidences(); $sel = (int)($_GET['residence_id'] ?? 0);
                if (!$sel && count($residences) === 1) $sel = $residences[0]['id'];
                $this->view('menage/fournisseurs', [
                    'title'              => 'Fournisseurs Ménage - ' . APP_NAME,
                    'showNavbar'         => true,
                    'residences'         => $residences,
                    'selectedResidence'  => $sel,
                    'fournisseurs'       => $sel ? $model->getFournisseursResidence($sel) : [],
                    'disponibles'        => $sel ? $fm->getFournisseursDisponibles($sel, 'menage') : [],
                    'flash'              => $this->getFlash()
                ], true);
        }
    }

    // =================================================================
    //  COMPTABILITÉ MÉNAGE (manager)
    // =================================================================

    public function comptabilite($action = null) {
        $this->requireAuth(); $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Menage');

        if ($action === 'ecriture') { return $this->storeEcritureMenage($model); }
        if ($action === 'export') { return $this->exportComptaMenage($model); }

        $residences = $this->getUserResidences(); $residenceIds = array_column($residences,'id');
        $sel = (int)($_GET['residence_id'] ?? 0); $filteredIds = $sel ? [$sel] : $residenceIds;
        $annee = (int)($_GET['annee'] ?? date('Y')); $mois = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        $totaux = $model->getTotauxAnnuels($filteredIds, $annee);
        $synthese = $model->getSyntheseMensuelle($filteredIds, $annee);
        $depensesFourn = $model->getDepensesParFournisseur($filteredIds, $annee, $mois);
        $ecritures = $model->getEcritures($filteredIds, $annee, $mois);

        $moisLabels = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
        $recettesData = array_fill(0,12,0); $depensesData = array_fill(0,12,0);
        foreach ($synthese as $s) { $recettesData[$s['mois']-1] = (float)$s['recettes_ttc']; $depensesData[$s['mois']-1] = (float)$s['depenses_ttc']; }

        $this->view('menage/comptabilite', [
            'title'=>'Comptabilité Ménage - '.APP_NAME,'showNavbar'=>true,
            'residences'=>$residences,'selectedResidence'=>$sel,'annee'=>$annee,'mois'=>$mois,
            'totaux'=>$totaux,'depensesFournisseurs'=>$depensesFourn,'ecritures'=>$ecritures,
            'moisLabels'=>json_encode($moisLabels),'recettesData'=>json_encode($recettesData),'depensesData'=>json_encode($depensesData),
            'flash'=>$this->getFlash()
        ], true);
    }

    private function storeEcritureMenage(Menage $model) {
        $this->verifyCsrf();
        try {
            $ht = (float)$_POST['montant_ht']; $tva = round($ht * (float)($_POST['taux_tva']??0) / 100, 2);
            $model->createEcriture(['residence_id'=>(int)$_POST['residence_id'],'date_ecriture'=>$_POST['date_ecriture']??date('Y-m-d'),'type_ecriture'=>$_POST['type_ecriture'],'categorie'=>$_POST['categorie'],'libelle'=>trim($_POST['libelle']),'montant_ht'=>$ht,'montant_tva'=>$tva,'montant_ttc'=>$ht+$tva,'compte_comptable'=>trim($_POST['compte_comptable']??'')?:null,'mois'=>(int)date('m',strtotime($_POST['date_ecriture']??'now')),'annee'=>(int)date('Y',strtotime($_POST['date_ecriture']??'now')),'notes'=>trim($_POST['notes']??'')]);
            $this->setFlash('success','Écriture enregistrée');
        } catch (Exception $e) { $this->setFlash('error','Erreur : '.$e->getMessage()); }
        $this->redirect('menage/comptabilite?residence_id='.($_POST['residence_id']??'').'&annee='.($_POST['annee']??date('Y')));
    }

    private function exportComptaMenage(Menage $model) {
        $residences = $this->getUserResidences(); $residenceIds = array_column($residences,'id');
        $sel = (int)($_GET['residence_id'] ?? 0); $filteredIds = $sel ? [$sel] : $residenceIds;
        $annee = (int)($_GET['annee'] ?? date('Y')); $mois = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $ecritures = $model->getEcrituresExport($filteredIds, $annee, $mois);

        $filename = 'comptabilite_menage_'.$annee.($mois ? '_'.str_pad($mois,2,'0',STR_PAD_LEFT) : '').'.csv';
        header('Content-Type: text/csv; charset=UTF-8'); header('Content-Disposition: attachment; filename="'.$filename.'"');
        $output = fopen('php://output', 'w'); fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['Date','Compte','Libellé','Type','Catégorie','Montant HT','TVA','Montant TTC','Résidence'], ';');
        foreach ($ecritures as $e) { fputcsv($output, [$e['date_ecriture'],$e['compte_comptable']??'',$e['libelle'],$e['type_ecriture'],str_replace('_',' ',$e['categorie']),number_format($e['montant_ht'],2,',',''),number_format($e['montant_tva'],2,',',''),number_format($e['montant_ttc'],2,',',''),$e['residence_nom']], ';'); }
        fclose($output); exit;
    }
}
