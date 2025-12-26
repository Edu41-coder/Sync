<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Copropriétés
 * ====================================================================
 */

class CoproprieteController extends Controller {
    
    /**
     * Liste des copropriétés
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // TODO: Charger le modèle Copropriete et récupérer les données
        
        $data = [
            'title' => 'Copropriétés - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('coproprietes/index', $data, true);
    }
    
    /**
     * Afficher les détails d'une copropriété
     */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // TODO: Charger la copropriété depuis le modèle
        
        $data = [
            'title' => 'Détails Copropriété - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('coproprietes/show', $data, true);
    }
    
    /**
     * Formulaire de création d'une copropriété
     */
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        $data = [
            'title' => 'Nouvelle Copropriété - ' . APP_NAME,
            'showNavbar' => true
        ];
        
        $this->view('coproprietes/create', $data, true);
    }
    
    /**
     * Enregistrer une nouvelle copropriété
     */
    public function store() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // TODO: Validation et insertion en BDD
            
            $this->setFlash('success', 'Copropriété créée avec succès');
            $this->redirect('copropriete/index');
        }
    }
    
    /**
     * Formulaire d'édition d'une copropriété
     */
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        // TODO: Charger la copropriété
        
        $data = [
            'title' => 'Modifier Copropriété - ' . APP_NAME,
            'showNavbar' => true,
            'id' => $id
        ];
        
        $this->view('coproprietes/edit', $data, true);
    }
    
    /**
     * Mettre à jour une copropriété
     */
    public function update($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            // TODO: Validation et mise à jour en BDD
            
            $this->setFlash('success', 'Copropriété mise à jour avec succès');
            $this->redirect('copropriete/show/' . $id);
        }
    }
    
    /**
     * Supprimer une copropriété
     */
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        
        // TODO: Supprimer de la BDD
        
        $this->setFlash('success', 'Copropriété supprimée avec succès');
        $this->redirect('copropriete/index');
    }
}
