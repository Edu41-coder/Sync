<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Locataires
 * ====================================================================
 */

class LocataireController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Locataires - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('locataires/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Détails Locataire - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('locataires/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Nouveau Locataire - ' . APP_NAME,
            'showNavbar' => true
        ];
        
        $this->view('locataires/create', $data, true);
    }
    
    public function store() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->setFlash('success', 'Locataire créé avec succès');
            $this->redirect('locataire/index');
        }
    }
    
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Modifier Locataire - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('locataires/edit', $data, true);
    }
    
    public function update($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->setFlash('success', 'Locataire mis à jour avec succès');
            $this->redirect('locataire/show/' . $id);
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $this->setFlash('success', 'Locataire supprimé avec succès');
        $this->redirect('locataire/index');
    }
}
