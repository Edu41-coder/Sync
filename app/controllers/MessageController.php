<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur de Messagerie
 * ====================================================================
 */

class MessageController extends Controller {
    
    public function index() {
        $this->requireAuth();
        
        $data = [
            'title' => 'Messagerie - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ];
        
        $this->view('messages/index', $data, true);
    }
    
    public function inbox() {
        $this->requireAuth();
        $data = ['title' => 'Boîte de réception - ' . APP_NAME, 'showNavbar' => true];
        $this->view('messages/inbox', $data, true);
    }
    
    public function send() {
        $this->requireAuth();
        $data = ['title' => 'Nouveau message - ' . APP_NAME, 'showNavbar' => true];
        $this->view('messages/send', $data, true);
    }
    
    public function show($id) {
        $this->requireAuth();
        $data = ['title' => 'Message - ' . APP_NAME, 'showNavbar' => true, 'id' => $id];
        $this->view('messages/show', $data, true);
    }
}
