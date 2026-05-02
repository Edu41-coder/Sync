<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Occupations Résidents
 * ====================================================================
 * Gestion des occupations de logements par les résidents seniors
 */

class OccupationController extends Controller {

    /**
     * Liste des occupations
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'locataire_permanent']);

        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        $occupationModel = $this->model('Occupation');

        if ($userRole === 'locataire_permanent') {
            $residentModel = $this->model('ResidentSenior');
            $resident = $residentModel->findByUserId($userId);
            $occupationsArray = $resident ? $occupationModel->getHistoryByResident($resident->id) : [];
        } elseif ($userRole === 'exploitant') {
            $exploitantModel = $this->model('Exploitant');
            $exploitant = $exploitantModel->findByUserId($userId);
            $occupationsArray = $exploitant ? $occupationModel->getByExploitant($exploitant['id']) : [];
        } else {
            $occupationsArray = $occupationModel->getAll();
        }

        $occupations = array_map(function($o) { return (object)$o; }, $occupationsArray);

        $this->view('occupations/index', [
            'title' => 'Occupations - ' . APP_NAME,
            'showNavbar' => true,
            'occupations' => $occupations,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ]);
    }

    /**
     * Alias public de details() — auth dupliquée pour defense in depth
     * (un refactor de details() ne doit pas pouvoir exposer cette route).
     */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'locataire_permanent']);
        $this->details($id);
    }

    /**
     * Détails d'une occupation
     */
    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'locataire_permanent']);

        $occupationModel = $this->model('Occupation');
        $occupation = $occupationModel->findById($id);

        if (!$occupation) {
            $this->setFlash('error', 'Occupation introuvable');
            $this->redirect('occupation/index');
            return;
        }

        // Vérifier les droits d'accès
        if ($_SESSION['user_role'] === 'locataire_permanent' && $occupation['resident_user_id'] != $_SESSION['user_id']) {
            $this->setFlash('error', 'Accès non autorisé');
            $this->redirect('occupation/index');
            return;
        }

        $totalMensuel = $occupation['loyer_mensuel_resident'] +
                       ($occupation['charges_mensuelles_resident'] ?? 0) +
                       ($occupation['montant_services_sup'] ?? 0);

        $serviceModel = $this->model('Service');

        $this->view('occupations/show', [
            'title' => 'Occupation - ' . $occupation['resident_nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'occupation' => $occupation,
            'services' => $serviceModel->getByOccupation($occupation['id']),
            'totalMensuel' => $totalMensuel,
            'userRole' => $_SESSION['user_role'],
            'flash' => $this->getFlash()
        ], true);
    }

    /**
     * Créer une nouvelle occupation
     */
    public function create($lotId = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
            return;
        }

        $occupationModel = $this->model('Occupation');
        $lotId = $lotId ? (int)$lotId : null;

        // Si un lot est pré-sélectionné, vérifier qu'il n'est pas occupé
        $preselectedLot = null;
        if ($lotId) {
            $lotModel = $this->model('Lot');
            if ($lotModel->isOccupied($lotId)) {
                $this->setFlash('error', "Ce lot est déjà occupé.");
                $this->redirect('lot/show/' . $lotId);
                return;
            }
            $preselectedLot = $lotModel->findWithDetails($lotId);
        }

        $lotType = $preselectedLot['type'] ?? null;
        $serviceModel = $this->model('Service');

        $this->view('occupations/create', [
            'title'          => 'Nouvelle Occupation - ' . APP_NAME,
            'showNavbar'     => true,
            'residents'      => $occupationModel->getResidentsDisponibles($lotType),
            'lots'           => $occupationModel->getLotsLibres(),
            'preselectedLot' => $preselectedLot,
            'lotId'          => $lotId,
            'services'       => $serviceModel->getAllActifs(),
            'flash'          => $this->getFlash()
        ], true);
    }

    /**
     * Traiter la création d'une occupation
     */
    private function handleCreate() {
        $this->verifyCsrf();

        try {
            $occupationModel = $this->model('Occupation');
            $lotId = (int)($_POST['lot_id'] ?? 0);
            $residentId = (int)($_POST['resident_id'] ?? 0);

            if (!$lotId || !$residentId) {
                throw new Exception("Lot et résident sont requis.");
            }

            if ($occupationModel->isLotOccupied($lotId)) {
                throw new Exception("Ce lot est déjà occupé.");
            }

            $exploitantId = $occupationModel->getLotExploitant($lotId);

            $data = [
                'resident_id' => $residentId,
                'lot_id' => $lotId,
                'exploitant_id' => $exploitantId,
                'date_entree' => $_POST['date_debut'] ?? date('Y-m-d'),
                'loyer_mensuel_resident' => !empty($_POST['loyer_mensuel_resident']) ? (float)$_POST['loyer_mensuel_resident'] : 0,
                'charges_mensuelles_resident' => $_POST['charges_mensuelles'] ?? 0,
                'depot_garantie' => $_POST['depot_garantie'] ?? 0,
                'forfait_type' => $_POST['forfait_type'] ?? 'essentiel',
                'mode_paiement' => $_POST['mode_paiement'] ?? 'prelevement',
                'notes' => trim($_POST['notes'] ?? '') ?: null,
                'statut' => 'actif'
            ];

            $success = $occupationModel->create($data);

            if (!$success) {
                throw new Exception("Erreur lors de l'insertion.");
            }

            // Sauvegarder les services sélectionnés
            $newOccId = $occupationModel->getDb()->lastInsertId();
            if ($newOccId) {
                $serviceModel = $this->model('Service');
                $selectedServices = [];
                foreach ($_POST['services'] ?? [] as $svcId => $prix) {
                    $selectedServices[(int)$svcId] = (float)$prix;
                }
                $serviceModel->syncOccupationServices((int)$newOccId, $selectedServices);
            }

            $this->setFlash('success', 'Occupation créée avec succès');
            $this->redirect('lot/show/' . $lotId);

        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('occupation/create' . ($lotId ? '/' . $lotId : ''));
        }
    }

    /**
     * Modifier une occupation
     */
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate($id);
            return;
        }

        $occupationModel = $this->model('Occupation');
        $occupation = $occupationModel->findById($id);

        if (!$occupation) {
            $this->setFlash('error', 'Occupation introuvable');
            $this->redirect('occupation/index');
            return;
        }

        $occLotType = $occupationModel->getOccupationLotType($id);
        $serviceModel = $this->model('Service');

        $this->view('occupations/edit', [
            'title' => 'Modifier l\'occupation - ' . APP_NAME,
            'showNavbar' => true,
            'occupation' => $occupation,
            'availableResidents' => $occupationModel->getResidentsDisponibles($occLotType, (int)$id),
            'services' => $serviceModel->getByOccupation($occupation['id']),
            'flash' => $this->getFlash()
        ], true);
    }

    /**
     * Traiter la mise à jour d'une occupation
     */
    private function handleUpdate($id) {
        try {
            $this->verifyCsrf();
            $occupationModel = $this->model('Occupation');

            $newResidentId = (int)($_POST['resident_id'] ?? 0);

            $data = [
                'resident_id' => $newResidentId ?: null,
                'loyer_mensuel_resident' => $_POST['loyer_mensuel_resident'],
                'charges_mensuelles_resident' => $_POST['charges_mensuelles_resident'] ?? 0,
                'montant_services_sup' => $_POST['montant_services_sup'] ?? 0,
                'forfait_type' => $_POST['forfait_type'] ?? 'essentiel',
                'mode_paiement' => $_POST['mode_paiement'] ?? 'prelevement',
                'jour_prelevement' => $_POST['jour_prelevement'] ?? 5,
                'beneficie_apl' => isset($_POST['beneficie_apl']) ? 1 : 0,
                'montant_apl' => !empty($_POST['montant_apl']) ? $_POST['montant_apl'] : null,
                'beneficie_apa' => isset($_POST['beneficie_apa']) ? 1 : 0,
                'montant_apa' => !empty($_POST['montant_apa']) ? $_POST['montant_apa'] : null,
                'statut' => $_POST['statut'] ?? 'actif',
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null
            ];

            $success = $occupationModel->updateOccupation($id, $data);

            if ($success) {
                $serviceModel = $this->model('Service');
                $selectedServices = [];
                foreach ($_POST['services'] ?? [] as $svcId => $prix) {
                    $selectedServices[(int)$svcId] = (float)$prix;
                }
                $serviceModel->syncOccupationServices((int)$id, $selectedServices);

                $this->setFlash('success', 'Occupation modifiée avec succès');
                $this->redirect('occupation/show/' . $id);
            } else {
                throw new Exception("Erreur lors de la mise à jour");
            }

        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('occupation/edit/' . $id);
        }
    }

    /**
     * Terminer une occupation
     */
    public function terminer($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant']);
        $this->requirePostCsrf();

        $occupationModel = $this->model('Occupation');

        if ($occupationModel->terminerOccupation($id, $_POST['date_fin'], $_POST['motif_fin'] ?? null)) {
            $this->setFlash('success', 'Occupation terminée avec succès');
        } else {
            $this->setFlash('error', 'Erreur lors de la terminaison');
        }

        $this->redirect('occupation/details/' . $id);
    }
}
