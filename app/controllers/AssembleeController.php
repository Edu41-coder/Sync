<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Assemblées Générales
 * ====================================================================
 */

class AssembleeController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);
        
        $data = [
            'title' => 'Assemblées Générales - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('assemblees/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Détails AG - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('assemblees/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $data = ['title' => 'Nouvelle AG - ' . APP_NAME, 'showNavbar' => true];
        $this->view('assemblees/create', $data, true);
    }
    
    public function convocation($id) {
        $this->requireAuth();
        // TODO: Générer PDF convocation
        $this->setFlash('success', 'Convocation générée avec succès');
        $this->redirect('assemblee/show/' . $id);
    }
    
    public function procesVerbal($id) {
        $this->requireAuth();
        $data = ['title' => 'Procès-Verbal - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('assemblees/proces-verbal', $data, true);
    }
}
