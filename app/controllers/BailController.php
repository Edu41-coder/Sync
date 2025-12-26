<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Baux
 * ====================================================================
 */

class BailController extends Controller {
    
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Baux - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('baux/index', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Détails Bail - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('baux/show', $data, true);
    }
    
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        $data = ['title' => 'Nouveau Bail - ' . APP_NAME, 'showNavbar' => true];
        $this->view('baux/create', $data, true);
    }
    
    public function store() {
        $this->requireAuth();
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $this->setFlash('success', 'Bail créé avec succès');
            $this->redirect('bail/index');
        }
    }
    
    public function edit($id) {
        $this->requireAuth();
        $data = ['title' => 'Modifier Bail - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('baux/edit', $data, true);
    }
    
    public function genererQuittance($id) {
        $this->requireAuth();
        // TODO: Générer PDF de quittance
        $this->setFlash('success', 'Quittance générée avec succès');
        $this->redirect('bail/show/' . $id);
    }
}
