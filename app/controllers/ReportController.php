<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur de Reporting
 * ====================================================================
 */

class ReportController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Rapports - ' . APP_NAME,
            'showNavbar' => true
        ];
        
        $this->view('reporting/index', $data, true);
    }
    
    public function financier() {
        $this->requireAuth();
        $data = ['title' => 'Rapport Financier - ' . APP_NAME, 'showNavbar' => true];
        $this->view('reporting/financier', $data, true);
    }
    
    public function impayes() {
        $this->requireAuth();
        $data = ['title' => 'Rapport Impayés - ' . APP_NAME, 'showNavbar' => true];
        $this->view('reporting/impayes', $data, true);
    }
    
    public function travaux() {
        $this->requireAuth();
        $data = ['title' => 'Rapport Travaux - ' . APP_NAME, 'showNavbar' => true];
        $this->view('reporting/travaux', $data, true);
    }
    
    public function export() {
        $this->requireAuth();
        // TODO: Générer export Excel/PDF
        $this->setFlash('success', 'Export généré avec succès');
        $this->redirect('report/index');
    }
}
