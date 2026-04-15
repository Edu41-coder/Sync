<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Planning Staff
 * ====================================================================
 * Gestion du planning des employés avec TUI Calendar
 * Routes AJAX pour le CRUD des shifts
 */

class PlanningController extends Controller {

    /**
     * Vue principale du planning
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'comptable']);

        $model = $this->model('Planning');

        $this->view('planning/index', [
            'title'          => 'Planning Staff - ' . APP_NAME,
            'showNavbar'     => true,
            'categories'     => $model->getCategories(),
            'employees'      => $model->getStaffEmployees(),
            'residences'     => $model->getResidences(),
            'userResidences' => $model->getUserResidences(),
            'flash'          => $this->getFlash()
        ], true);
    }

    /**
     * Point d'entrée AJAX — dispatche selon le paramètre ?action=
     */
    public function ajax($action = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'comptable']);

        header('Content-Type: application/json; charset=utf-8');

        $model = $this->model('Planning');

        try {
            switch ($action) {
                case 'getEvents':
                    $this->ajaxGetEvents($model);
                    break;
                case 'getEvent':
                    $this->ajaxGetEvent($model);
                    break;
                case 'save':
                    $this->ajaxSave($model);
                    break;
                case 'move':
                    $this->ajaxMove($model);
                    break;
                case 'delete':
                    $this->ajaxDelete($model);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Récupérer les shifts pour une période
     */
    private function ajaxGetEvents(Planning $model) {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');
        $residenceId = (int)($_GET['residence_id'] ?? 0);
        $userId = (int)($_GET['user_id'] ?? 0);

        $shifts = $model->getShifts($start, $end, $residenceId, $userId);

        // Formater pour TUI Calendar
        $events = array_map(function($s) {
            return [
                'id'            => $s['id'],
                'calendarId'    => $s['residence_id'],
                'title'         => $s['employe_prenom'] . ' ' . $s['employe_nom'] . ' — ' . $s['titre'],
                'start'         => $s['date_debut'],
                'end'           => $s['date_fin'],
                'isAllDay'      => (bool)$s['journee_entiere'],
                'categoryId'    => $s['category_id'],
                'calendarColor' => $s['couleur'] ?? '#3f51b5',
                'categoryColor' => $s['bg_couleur'] ?? '#e8eaf6',
                'categoryTextColor' => '#333',
                'body'          => $s['description'] ?? '',
                'raw'           => [
                    'userId'       => $s['user_id'],
                    'residenceId'  => $s['residence_id'],
                    'categoryId'   => $s['category_id'],
                    'typeShift'    => $s['type_shift'],
                    'typeHeures'   => $s['type_heures'],
                    'statut'       => $s['statut'],
                    'heures'       => $s['heures_calculees'],
                    'employeNom'   => $s['employe_prenom'] . ' ' . $s['employe_nom'],
                    'residenceNom' => $s['residence_nom'],
                    'categorieNom' => $s['categorie_nom'],
                ]
            ];
        }, $shifts);

        echo json_encode($events);
    }

    /**
     * Récupérer un shift par ID
     */
    private function ajaxGetEvent(Planning $model) {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            return;
        }

        $shift = $model->getShift($id);

        if (!$shift) {
            echo json_encode(['success' => false, 'message' => 'Shift introuvable']);
            return;
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
                'categoryId'  => $shift['category_id'],
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
    }

    /**
     * Créer ou modifier un shift
     */
    private function ajaxSave(Planning $model) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            return;
        }

        $userId      = (int)($input['userId'] ?? $input['raw']['userId'] ?? 0);
        $residenceId = (int)($input['residenceId'] ?? $input['calendarId'] ?? $input['raw']['residenceId'] ?? 0);
        $titre       = trim($input['title'] ?? '');
        $dateDebut   = $input['start'] ?? '';
        $dateFin     = $input['end'] ?? '';

        if (!$userId || !$residenceId || !$titre || !$dateDebut || !$dateFin) {
            echo json_encode(['success' => false, 'message' => 'Champs requis : employé, résidence, titre, dates']);
            return;
        }

        $id = $model->saveShift([
            'id'          => $input['id'] ?? null,
            'userId'      => $userId,
            'residenceId' => $residenceId,
            'categoryId'  => (int)($input['categoryId'] ?? $input['raw']['categoryId'] ?? 0) ?: null,
            'title'       => $titre,
            'start'       => $dateDebut,
            'end'         => $dateFin,
            'isAllDay'    => $input['isAllDay'] ?? false,
            'typeShift'   => $input['typeShift'] ?? $input['raw']['typeShift'] ?? 'travail',
            'typeHeures'  => $input['typeHeures'] ?? $input['raw']['typeHeures'] ?? 'normales',
            'description' => trim($input['description'] ?? $input['raw']['description'] ?? ''),
            'notes'       => trim($input['notes'] ?? $input['raw']['notes'] ?? ''),
        ]);

        $message = ($input['id'] ?? null) ? 'Shift modifié' : 'Shift créé';
        echo json_encode(['success' => true, 'message' => $message, 'id' => $id]);
    }

    /**
     * Déplacer un shift (drag & drop)
     */
    private function ajaxMove(Planning $model) {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || empty($input['id'])) {
            echo json_encode(['success' => false, 'message' => 'Données invalides']);
            return;
        }

        $model->moveShift((int)$input['id'], $input['start'], $input['end']);
        echo json_encode(['success' => true, 'message' => 'Shift déplacé']);
    }

    /**
     * Supprimer un shift
     */
    private function ajaxDelete(Planning $model) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'ID requis']);
            return;
        }

        $model->deleteShift($id);
        echo json_encode(['success' => true, 'message' => 'Shift supprimé']);
    }
}
