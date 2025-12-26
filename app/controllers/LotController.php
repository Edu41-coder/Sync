<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Lots
 * ====================================================================
 */

class LotController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Lots - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('lots/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // Charger les données du lot
        $lotModel = $this->model('Lot');
        $lot = $lotModel->findWithDetails($id);
        
        if (!$lot) {
            $this->setFlash('error', 'Lot introuvable');
            $this->redirect('admin/residences');
        }
        
        $data = [
            'title' => 'Lot ' . $lot['numero_lot'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'lot' => $lot
        ];
        
        $this->view('lots/show', $data, true);
    }
    
    public function create($residenceId = null) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // Si pas d'ID de résidence dans l'URL, rediriger vers la liste des résidences
        if (!$residenceId) {
            $this->setFlash('error', 'Identifiant de résidence manquant');
            $this->redirect('admin/residences');
        }
        
        // Récupérer les informations de la résidence
        $residenceModel = $this->model('Residence');
        $residence = $residenceModel->find($residenceId);
        
        if (!$residence) {
            $this->setFlash('error', 'Résidence introuvable');
            $this->redirect('admin/residences');
        }
        
        $data = [
            'title' => 'Nouveau Lot - ' . APP_NAME,
            'showNavbar' => true,
            'residence' => $residence
        ];
        
        $this->view('lots/create', $data, true);
    }
    
    public function store() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        $this->verifyCsrf();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $residenceId = $_POST['copropriete_id'] ?? null;
            
            // Validation
            $errors = [];
            
            if (empty($_POST['numero'])) {
                $errors[] = 'Le numéro de lot est obligatoire';
            }
            
            if (empty($_POST['type'])) {
                $errors[] = 'Le type de lot est obligatoire';
            }
            
            if (!$residenceId) {
                $errors[] = 'L\'identifiant de résidence est manquant';
            }
            
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('lot/create/' . $residenceId);
            }
            
            // Préparer les données
            $lotData = [
                'copropriete_id' => $residenceId,
                'numero_lot' => trim($_POST['numero']),
                'type' => $_POST['type'],
                'etage' => !empty($_POST['etage']) ? (int)$_POST['etage'] : null,
                'surface' => !empty($_POST['surface']) ? (float)$_POST['surface'] : null,
                'nombre_pieces' => !empty($_POST['nombre_pieces']) ? (int)$_POST['nombre_pieces'] : null,
                'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
                'tantiemes_generaux' => !empty($_POST['tantiemes_generaux']) ? (int)$_POST['tantiemes_generaux'] : 0,
                'tantiemes_chauffage' => !empty($_POST['tantiemes_chauffage']) ? (int)$_POST['tantiemes_chauffage'] : 0,
                'tantiemes_ascenseur' => !empty($_POST['tantiemes_ascenseur']) ? (int)$_POST['tantiemes_ascenseur'] : 0
            ];
            
            // Créer le lot
            $lotModel = $this->model('Lot');
            $success = $lotModel->create($lotData);
            
            if ($success) {
                $this->setFlash('success', 'Lot créé avec succès');
            } else {
                $this->setFlash('error', 'Erreur lors de la création du lot');
            }
            
            $this->redirect('admin/viewResidence/' . $residenceId);
        }
    }
    
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // Récupérer le lot avec ses détails
        $lotModel = $this->model('Lot');
        $lot = $lotModel->findWithDetails($id);
        
        if (!$lot) {
            $this->setFlash('error', 'Lot introuvable');
            $this->redirect('admin/residences');
        }
        
        // Récupérer la résidence
        $residenceModel = $this->model('Residence');
        $residence = $residenceModel->find($lot['copropriete_id']);
        
        $data = [
            'title' => 'Modifier Lot - ' . APP_NAME,
            'showNavbar' => true,
            'lot' => [
                'id' => $lot['id'],
                'numero' => $lot['numero_lot'],
                'type' => $lot['type'],
                'etage' => $lot['etage'],
                'surface' => $lot['surface'],
                'nombre_pieces' => $lot['nombre_pieces'],
                'description' => $lot['description'],
                'tantiemes_generaux' => $lot['tantiemes_generaux'],
                'tantiemes_chauffage' => $lot['tantiemes_chauffage'],
                'tantiemes_ascenseur' => $lot['tantiemes_ascenseur'],
                'created_at' => $lot['created_at'] ?? null,
                'updated_at' => $lot['updated_at'] ?? null
            ],
            'residence' => $residence
        ];
        
        $this->view('lots/edit', $data, true);
    }
    
    public function update($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        $this->verifyCsrf();
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $residenceId = $_POST['copropriete_id'] ?? null;
            
            // Validation
            $errors = [];
            
            if (empty($_POST['numero'])) {
                $errors[] = 'Le numéro de lot est obligatoire';
            }
            
            if (empty($_POST['type'])) {
                $errors[] = 'Le type de lot est obligatoire';
            }
            
            if (!empty($errors)) {
                $this->setFlash('error', implode('<br>', $errors));
                $this->redirect('lot/edit/' . $id);
            }
            
            // Préparer les données
            $lotData = [
                'numero_lot' => trim($_POST['numero']),
                'type' => $_POST['type'],
                'etage' => !empty($_POST['etage']) ? (int)$_POST['etage'] : null,
                'surface' => !empty($_POST['surface']) ? (float)$_POST['surface'] : null,
                'nombre_pieces' => !empty($_POST['nombre_pieces']) ? (int)$_POST['nombre_pieces'] : null,
                'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
                'tantiemes_generaux' => !empty($_POST['tantiemes_generaux']) ? (int)$_POST['tantiemes_generaux'] : 0,
                'tantiemes_chauffage' => !empty($_POST['tantiemes_chauffage']) ? (int)$_POST['tantiemes_chauffage'] : 0,
                'tantiemes_ascenseur' => !empty($_POST['tantiemes_ascenseur']) ? (int)$_POST['tantiemes_ascenseur'] : 0
            ];
            
            // Mettre à jour le lot
            $lotModel = $this->model('Lot');
            $success = $lotModel->updateLot($id, $lotData);
            
            if ($success) {
                $this->setFlash('success', 'Lot modifié avec succès');
            } else {
                $this->setFlash('error', 'Erreur lors de la modification du lot');
            }
            
            $this->redirect('admin/viewResidence/' . $residenceId);
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $this->setFlash('success', 'Lot supprimé avec succès');
        $this->redirect('lot/index');
    }
}
