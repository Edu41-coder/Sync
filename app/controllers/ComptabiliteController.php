<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur de Comptabilité
 * ====================================================================
 */

class ComptabiliteController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);
        
        $data = [
            'title' => 'Comptabilité - ' . APP_NAME,
            'showNavbar' => true
        ];
        
        $this->view('comptabilite/index', $data, true);
    }
    
    public function ecritures() {
        $this->requireAuth();
        $data = ['title' => 'Écritures Comptables - ' . APP_NAME, 'showNavbar' => true];
        $this->view('comptabilite/ecritures', $data, true);
    }
    
    public function balance() {
        $this->requireAuth();
        $data = ['title' => 'Balance - ' . APP_NAME, 'showNavbar' => true];
        $this->view('comptabilite/balance', $data, true);
    }
    
    public function grandLivre() {
        $this->requireAuth();
        $data = ['title' => 'Grand Livre - ' . APP_NAME, 'showNavbar' => true];
        $this->view('comptabilite/grand-livre', $data, true);
    }
    
    public function exercices() {
        $this->requireAuth();
        $data = ['title' => 'Exercices Comptables - ' . APP_NAME, 'showNavbar' => true];
        $this->view('comptabilite/exercices', $data, true);
    }
}
