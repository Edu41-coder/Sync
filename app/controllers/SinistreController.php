<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Sinistres
 * ====================================================================
 */

class SinistreController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Sinistres - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('sinistres/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Détails Sinistre - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('sinistres/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $data = ['title' => 'Déclarer un Sinistre - ' . APP_NAME, 'showNavbar' => true];
        $this->view('sinistres/create', $data, true);
    }
}
