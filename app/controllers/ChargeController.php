<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Charges et Appels de Fonds
 * ====================================================================
 */

class ChargeController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);
        
        $data = [
            'title' => 'Appels de Fonds - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('charges/index', $data, true);
    }
    
    public function generer() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);
        $data = ['title' => 'Générer Appel de Fonds - ' . APP_NAME, 'showNavbar' => true];
        $this->view('charges/generer', $data, true);
    }
    
    public function repartition($id) {
        $this->requireAuth();
        $data = ['title' => 'Répartition Charges - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('charges/repartition', $data, true);
    }
    
    public function paiements() {
        $this->requireAuth();
        $data = ['title' => 'Paiements - ' . APP_NAME, 'showNavbar' => true];
        $this->view('charges/paiements', $data, true);
    }
}
