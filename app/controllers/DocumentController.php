<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Documents (GED)
 * ====================================================================
 */

class DocumentController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $data = [
            'title' => 'Documents - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('documents/index', $data, true);
    }
    
    public function upload() {
        $this->requireAuth();
        $data = ['title' => 'Upload Document - ' . APP_NAME, 'showNavbar' => true];
        $this->view('documents/upload', $data, true);
    }
    
    public function download($id) {
        $this->requireAuth();
        // TODO: Télécharger le fichier
        $this->setFlash('success', 'Document téléchargé');
        $this->redirect('document/index');
    }
    
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);
        $this->setFlash('success', 'Document supprimé');
        $this->redirect('document/index');
    }
}
