<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Fournisseurs
 * ====================================================================
 */

class FournisseurController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Fournisseurs - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('fournisseurs/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Détails Fournisseur - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('fournisseurs/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $data = ['title' => 'Nouveau Fournisseur - ' . APP_NAME, 'showNavbar' => true];
        $this->view('fournisseurs/create', $data, true);
    }
    
    public function factures($id) {
        $this->requireAuth();
        $data = ['title' => 'Factures Fournisseur - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('fournisseurs/factures', $data, true);
    }
}
