<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Travaux
 * ====================================================================
 */

class TravauxController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Travaux - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('travaux/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Détails Travaux - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('travaux/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $data = ['title' => 'Nouveaux Travaux - ' . APP_NAME, 'showNavbar' => true];
        $this->view('travaux/create', $data, true);
    }
    
    public function devis($id) {
        $this->requireAuth();
        $data = ['title' => 'Devis Travaux - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('travaux/devis', $data, true);
    }
    
    public function planning() {
        $this->requireAuth();
        $data = ['title' => 'Planning Travaux - ' . APP_NAME, 'showNavbar' => true];
        $this->view('travaux/planning', $data, true);
    }
}
