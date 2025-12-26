<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Copropriétaires
 * ====================================================================
 */

class CoproprietaireController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Copropriétaires - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('coproprietaires/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Détails Copropriétaire - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('coproprietaires/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Nouveau Copropriétaire - ' . APP_NAME,
            'showNavbar' => true
        ];
        
        $this->view('coproprietaires/create', $data, true);
    }
    
    public function store() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->setFlash('success', 'Copropriétaire créé avec succès');
            $this->redirect('coproprietaire/index');
        }
    }
    
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Modifier Copropriétaire - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('coproprietaires/edit', $data, true);
    }
    
    public function update($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->setFlash('success', 'Copropriétaire mis à jour avec succès');
            $this->redirect('coproprietaire/show/' . $id);
        }
    }
    
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        $this->setFlash('success', 'Copropriétaire supprimé avec succès');
        $this->redirect('coproprietaire/index');
    }
}
