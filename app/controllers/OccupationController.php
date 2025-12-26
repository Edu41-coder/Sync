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
     * - Admin/Gestionnaire: toutes les occupations
     * - Exploitant: occupations de ses résidences
     * - Résident: uniquement sa propre occupation
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant', 'resident']);
        
        $userModel = $this->model('User');
        $db = $userModel->getDb();
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        // Utiliser le modèle Occupation
        require_once '../app/models/Occupation.php';
        $occupationModel = new Occupation();
        
        // Filtrer selon le rôle
        if ($userRole === 'resident') {
            require_once '../app/models/ResidentSenior.php';
            $residentModel = new ResidentSenior();
            $resident = $residentModel->findByUserId($userId);
            $occupationsArray = $resident ? $occupationModel->getHistoryByResident($resident->id) : [];
        } elseif ($userRole === 'exploitant') {
            require_once '../app/models/Exploitant.php';
            $exploitantModel = new Exploitant();
            $exploitant = $exploitantModel->findByUserId($userId);
            $occupationsArray = $exploitant ? $occupationModel->getByExploitant($exploitant['id']) : [];
        } else {
            $occupationsArray = $occupationModel->getAll();
        }
        
        $occupations = array_map(function($o) { return (object)$o; }, $occupationsArray);
        
        $data = [
            'title' => 'Occupations - ' . APP_NAME,
            'showNavbar' => true,
            'occupations' => $occupations,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ];
        
        $this->view('occupations/index', $data);
    }
    
    /**
     * Alias pour details (convention MVC standard)
     */
    public function show($id) {
        $this->details($id);
    }
    
    /**
     * Détails d'une occupation
     */
    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant', 'resident']);
        
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        // Utiliser le modèle Occupation
        require_once '../app/models/Occupation.php';
        $occupationModel = new Occupation();
        
        // Récupérer l'occupation avec tous les détails via le modèle
        $occupation = $occupationModel->findById($id);
        
        if (!$occupation) {
            $this->setFlash('error', 'Occupation introuvable');
            $this->redirect('occupation/index');
            return;
        }
        
        // Vérifier les droits d'accès
        if ($userRole === 'resident' && $occupation['resident_user_id'] != $userId) {
            $this->setFlash('error', 'Accès non autorisé');
            $this->redirect('occupation/index');
            return;
        }
        
        // Calculer le montant total mensuel
        $totalMensuel = $occupation['loyer_mensuel_resident'] + 
                       ($occupation['charges_mensuelles_resident'] ?? 0) + 
                       ($occupation['montant_services_sup'] ?? 0);
        
        $data = [
            'title' => 'Occupation - ' . $occupation['resident_nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'occupation' => $occupation,
            'totalMensuel' => $totalMensuel,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ];
        
        $this->view('occupations/show', $data, true);
    }
    
    /**
     * Créer une nouvelle occupation
     * Accessible uniquement par exploitant, gestionnaire et admin
     */
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            $db = $this->model('User')->getDb();
            $userRole = $_SESSION['user_role'];
            $userId = $_SESSION['user_id'];
            
            // Récupérer les résidents disponibles
            $residents = $db->query("SELECT id, CONCAT(prenom, ' ', nom) as nom_complet, age 
                                     FROM residents_seniors 
                                     WHERE actif = 1 
                                     ORDER BY nom, prenom")->fetchAll(PDO::FETCH_OBJ);
            
            // Récupérer les lots disponibles via model
            require_once '../app/models/Lot.php';
            $lotModel = new Lot();
            // Pour l'instant on récupère tous les lots (on pourrait filtrer par disponibilité)
            $lotsArray = $lotModel->getAllForSelect();
            $lots = array_map(function($l) { return (object)$l; }, $lotsArray);
            
            // Récupérer les exploitants
            if ($userRole === 'exploitant') {
                // Uniquement son exploitant
                $sqlExploitant = "SELECT * FROM exploitants WHERE user_id = ?";
                $stmtExploitant = $db->prepare($sqlExploitant);
                $stmtExploitant->execute([$userId]);
                $exploitants = [$stmtExploitant->fetch(PDO::FETCH_OBJ)];
            } else {
                $exploitants = $db->query("SELECT * FROM exploitants WHERE actif = 1")->fetchAll(PDO::FETCH_OBJ);
            }
            
            $data = [
                'title' => 'Nouvelle Occupation - ' . APP_NAME,
                'showNavbar' => true,
                'residents' => $residents,
                'lots' => $lots,
                'exploitants' => $exploitants,
                'userRole' => $userRole,
                'flash' => $this->getFlash()
            ];
            
            $this->view('occupations/create', $data);
        }
    }
    
    /**
     * Traiter la création d'une occupation
     */
    private function handleCreate() {
        $db = $this->model('User')->getDb();
        
        try {
            // Utiliser le modèle Occupation pour créer
            require_once '../app/models/Occupation.php';
            $occupationModel = new Occupation();
            
            // Vérifier que le lot n'est pas déjà occupé
            if ($occupationModel->isLotOccupied($_POST['lot_id'])) {
                throw new Exception("Ce lot est déjà occupé");
            }
            
            $data = [
                'resident_id' => $_POST['resident_id'],
                'lot_id' => $_POST['lot_id'],
                'exploitant_id' => $_POST['exploitant_id'],
                'date_entree' => $_POST['date_debut'],
                'loyer_mensuel_resident' => $_POST['loyer_mensuel_resident'],
                'charges_mensuelles_resident' => $_POST['charges_mensuelles'] ?? 0,
                'depot_garantie' => $_POST['depot_garantie'] ?? 0,
                'statut' => 'actif'
            ];
            
            $success = $occupationModel->create($data);
            
            if (!$success) {
                throw new Exception("Erreur lors de l'insertion");
            }
            
            $this->setFlash('success', 'Occupation créée avec succès');
            $this->redirect('occupation/index');
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur lors de la création: ' . $e->getMessage());
            $this->redirect('occupation/create');
        }
    }
    
    /**
     * Modifier une occupation
     */
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate($id);
        } else {
            // Utiliser le modèle Occupation
            require_once '../app/models/Occupation.php';
            $occupationModel = new Occupation();
            
            // Récupérer l'occupation via le modèle
            $occupation = $occupationModel->findById($id);
            
            if (!$occupation) {
                $this->setFlash('error', 'Occupation introuvable');
                $this->redirect('occupation/index');
                return;
            }
            
            $data = [
                'title' => 'Modifier l\'occupation - ' . APP_NAME,
                'showNavbar' => true,
                'occupation' => $occupation,
                'flash' => $this->getFlash()
            ];
            
            $this->view('occupations/edit', $data, true);
        }
    }
    
    /**
     * Traiter la mise à jour d'une occupation
     */
    private function handleUpdate($id) {
        try {
            $this->verifyCsrf();
            
            require_once '../app/models/Occupation.php';
            $occupationModel = new Occupation();
            
            // Encoder les services en JSON
            $servicesInclus = [];
            if (isset($_POST['services_inclus']) && is_array($_POST['services_inclus'])) {
                foreach ($_POST['services_inclus'] as $key => $value) {
                    $servicesInclus[$key] = true;
                }
            }
            
            $servicesSup = [];
            if (isset($_POST['services_supplementaires']) && is_array($_POST['services_supplementaires'])) {
                foreach ($_POST['services_supplementaires'] as $key => $value) {
                    $servicesSup[$key] = true;
                }
            }
            
            $data = [
                'loyer_mensuel_resident' => $_POST['loyer_mensuel_resident'],
                'charges_mensuelles_resident' => $_POST['charges_mensuelles_resident'] ?? 0,
                'services_inclus' => !empty($servicesInclus) ? json_encode($servicesInclus) : null,
                'services_supplementaires' => !empty($servicesSup) ? json_encode($servicesSup) : null,
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
        $this->requireRole(['admin', 'gestionnaire', 'exploitant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $db = $this->model('User')->getDb();
            
            try {
                $sql = "UPDATE occupations_residents SET
                        statut = 'termine',
                        date_fin = ?,
                        motif_fin = ?
                        WHERE id = ?";
                
                $stmt = $db->prepare($sql);
                $stmt->execute([
                    $_POST['date_fin'],
                    $_POST['motif_fin'] ?? null,
                    $id
                ]);
                
                $this->setFlash('success', 'Occupation terminée avec succès');
                $this->redirect('occupation/details/' . $id);
                
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur: ' . $e->getMessage());
                $this->redirect('occupation/details/' . $id);
            }
        }
    }
}
