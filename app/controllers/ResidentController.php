<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Résidents Seniors
 * ====================================================================
 */

class ResidentController extends Controller {
    
    /**
     * Liste des résidents
     * Accessible par: admin, gestionnaire (lecture), exploitant (gestion complète)
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant']);
        
        // Utiliser le modèle ResidentSenior
        $residentModel = new ResidentSenior();
        $residentsArray = $residentModel->getAll();
        $residents = array_map(function($r) { return (object)$r; }, $residentsArray);
        
        $data = [
            'title' => 'Résidents Seniors - ' . APP_NAME,
            'showNavbar' => true,
            'residents' => $residents,
            'flash' => $this->getFlash()
        ];
        
        $this->view('residents/index', $data, true);
    }
    
    /**
     * Voir un résident
     */
    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'proprietaire']);
        
        // Utiliser les modèles
        $residentModel = new ResidentSenior();
        $occupationModel = new Occupation();
        
        $resident = $residentModel->findById($id);
        
        if (!$resident) {
            $this->setFlash('error', 'Résident introuvable');
            $this->redirect('resident/index');
            return;
        }
        
        // Récupérer l'occupation actuelle
        $occupation = $occupationModel->getCurrentByResident($id);
        
        // Récupérer l'historique des occupations
        $occupationHistory = $occupationModel->getHistoryByResident($id);
        
        // Déterminer le contexte du fil d'Ariane
        $fromLot = isset($_GET['from_lot']) ? $_GET['from_lot'] : null;
        $breadcrumbContext = null;
        
        if ($fromLot && $occupation) {
            // Contexte : vient d'un lot, récupérer les infos pour le breadcrumb
            $breadcrumbContext = [
                'residence_id' => $occupation['residence_id'] ?? null,
                'residence_nom' => $occupation['residence_nom'] ?? null,
                'lot_id' => $fromLot,
                'numero_lot' => $occupation['numero_lot'] ?? null
            ];
        }
        
        $data = [
            'title' => 'Résident: ' . $resident->prenom . ' ' . $resident->nom . ' - ' . APP_NAME,
            'showNavbar' => true,
            'resident' => $resident,
            'occupation' => $occupation,
            'occupationHistory' => $occupationHistory,
            'breadcrumbContext' => $breadcrumbContext,
            'userRole' => $_SESSION['user_role'] ?? 'guest',
            'flash' => $this->getFlash()
        ];
        
        $this->view('residents/show', $data, true);
    }
    
    /**
     * Alias pour details
     */
    public function show($id) {
        return $this->details($id);
    }

    /**
     * Désactiver un résident (soft delete) et son compte utilisateur lié.
     */
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'exploitant']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('resident/show/' . $id);
            return;
        }

        $this->verifyCsrf();

        $residentModel = new ResidentSenior();
        $resident = $residentModel->findById($id);

        if (!$resident) {
            $this->setFlash('error', 'Résident introuvable');
            $this->redirect('resident/index');
            return;
        }

        try {
            $result = $residentModel->syncActiveStatusByResidentId((int)$id, false);

            if (!$result) {
                throw new Exception('Erreur lors de la désactivation du résident');
            }

            $this->setFlash('success', 'Résident désactivé avec succès. Le compte utilisateur lié a été désactivé.');
            $this->redirect('resident/index');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur lors de la désactivation: ' . $e->getMessage());
            $this->redirect('resident/show/' . $id);
        }
    }

    /**
     * Réactiver un résident et son compte utilisateur lié.
     */
    public function activate($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'exploitant']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('resident/show/' . $id);
            return;
        }

        $this->verifyCsrf();

        $residentModel = new ResidentSenior();
        $resident = $residentModel->findById($id);

        if (!$resident) {
            $this->setFlash('error', 'Résident introuvable');
            $this->redirect('resident/index');
            return;
        }

        try {
            $result = $residentModel->syncActiveStatusByResidentId((int)$id, true);

            if (!$result) {
                throw new Exception('Erreur lors de la réactivation du résident');
            }

            $this->setFlash('success', 'Résident réactivé avec succès. Le compte utilisateur lié a été réactivé.');
            $this->redirect('resident/show/' . $id);
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur lors de la réactivation: ' . $e->getMessage());
            $this->redirect('resident/show/' . $id);
        }
    }
    
    /**
     * Modifier un résident
     */
    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'exploitant']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleUpdate($id);
        } else {
            $residentModel = new ResidentSenior();
            
            $resident = $residentModel->findById($id);
            
            if (!$resident) {
                $this->setFlash('error', 'Résident introuvable');
                $this->redirect('resident/index');
                return;
            }
            
            $data = [
                'title' => 'Modifier le résident - ' . APP_NAME,
                'showNavbar' => true,
                'resident' => $resident,
                'userRole' => $_SESSION['user_role'] ?? 'guest',
                'flash' => $this->getFlash()
            ];
            
            $this->view('residents/edit', $data, true);
        }
    }
    
    /**
     * Traiter la modification d'un résident
     */
    private function handleUpdate($id) {
        $this->verifyCsrf();
        
        $residentModel = new ResidentSenior();
        
        try {
            $data = [
                'civilite' => $_POST['civilite'],
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'nom_naissance' => !empty($_POST['nom_naissance']) ? $_POST['nom_naissance'] : null,
                'date_naissance' => $_POST['date_naissance'],
                'lieu_naissance' => !empty($_POST['lieu_naissance']) ? $_POST['lieu_naissance'] : null,
                'telephone_fixe' => !empty($_POST['telephone_fixe']) ? $_POST['telephone_fixe'] : null,
                'telephone_mobile' => !empty($_POST['telephone_mobile']) ? $_POST['telephone_mobile'] : null,
                'email' => !empty($_POST['email']) ? $_POST['email'] : null,
                'numero_cni' => !empty($_POST['numero_cni']) ? $_POST['numero_cni'] : null,
                'date_delivrance_cni' => !empty($_POST['date_delivrance_cni']) ? $_POST['date_delivrance_cni'] : null,
                'lieu_delivrance_cni' => !empty($_POST['lieu_delivrance_cni']) ? $_POST['lieu_delivrance_cni'] : null,
                'situation_familiale' => $_POST['situation_familiale'] ?? 'celibataire',
                'nombre_enfants' => $_POST['nombre_enfants'] ?? 0,
                'niveau_autonomie' => $_POST['niveau_autonomie'] ?? 'autonome',
                'besoin_assistance' => isset($_POST['besoin_assistance']) ? 1 : 0,
                'allergies' => !empty($_POST['allergies']) ? $_POST['allergies'] : null,
                'regime_alimentaire' => !empty($_POST['regime_alimentaire']) ? $_POST['regime_alimentaire'] : null,
                'medecin_traitant_nom' => !empty($_POST['medecin_traitant_nom']) ? $_POST['medecin_traitant_nom'] : null,
                'medecin_traitant_tel' => !empty($_POST['medecin_traitant_tel']) ? $_POST['medecin_traitant_tel'] : null,
                'num_securite_sociale' => !empty($_POST['num_securite_sociale']) ? $_POST['num_securite_sociale'] : null,
                'mutuelle' => !empty($_POST['mutuelle']) ? $_POST['mutuelle'] : null,
                'urgence_nom' => !empty($_POST['urgence_nom']) ? $_POST['urgence_nom'] : null,
                'urgence_lien' => !empty($_POST['urgence_lien']) ? $_POST['urgence_lien'] : null,
                'urgence_telephone' => !empty($_POST['urgence_telephone']) ? $_POST['urgence_telephone'] : null,
                'urgence_telephone_2' => !empty($_POST['urgence_telephone_2']) ? $_POST['urgence_telephone_2'] : null,
                'urgence_email' => !empty($_POST['urgence_email']) ? $_POST['urgence_email'] : null,
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null,
                'actif' => isset($_POST['actif']) ? 1 : 0
            ];
            
            $result = $residentModel->update($id, $data);
            
            if ($result) {
                // Aligner systématiquement le statut du compte utilisateur lié.
                $residentModel->syncActiveStatusByResidentId((int)$id, !empty($data['actif']));
                $this->setFlash('success', 'Résident modifié avec succès');
                $this->redirect('resident/show/' . $id);
            } else {
                $this->setFlash('error', 'Erreur lors de la modification');
                $this->redirect('resident/edit/' . $id);
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur: ' . $e->getMessage());
            $this->redirect('resident/edit/' . $id);
        }
    }
    
    /**
     * Créer un nouveau résident
     * Accessible uniquement par exploitant et admin
     */
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'exploitant']);

        // Rediriger vers le formulaire centralisé de création user avec rôle pré-sélectionné
        $this->redirect('admin/users/create?role=locataire_permanent');
    }
    
    /**
     * Traiter la création d'un résident
     */
    private function handleCreate() {
        $this->verifyCsrf();

        $residentModel = new ResidentSenior();
        
        try {
            $data = [
                'user_id' => !empty($_POST['user_id']) ? (int)$_POST['user_id'] : null,
                'civilite' => $_POST['civilite'],
                'nom' => $_POST['nom'],
                'prenom' => $_POST['prenom'],
                'nom_naissance' => !empty($_POST['nom_naissance']) ? $_POST['nom_naissance'] : null,
                'date_naissance' => $_POST['date_naissance'],
                'lieu_naissance' => !empty($_POST['lieu_naissance']) ? $_POST['lieu_naissance'] : null,
                'telephone_fixe' => !empty($_POST['telephone_fixe']) ? $_POST['telephone_fixe'] : null,
                'telephone_mobile' => !empty($_POST['telephone_mobile']) ? $_POST['telephone_mobile'] : null,
                'email' => !empty($_POST['email']) ? $_POST['email'] : null,
                'numero_cni' => !empty($_POST['numero_cni']) ? $_POST['numero_cni'] : null,
                'date_delivrance_cni' => !empty($_POST['date_delivrance_cni']) ? $_POST['date_delivrance_cni'] : null,
                'lieu_delivrance_cni' => !empty($_POST['lieu_delivrance_cni']) ? $_POST['lieu_delivrance_cni'] : null,
                'niveau_autonomie' => $_POST['niveau_autonomie'] ?? 'autonome',
                'notes' => !empty($_POST['notes']) ? $_POST['notes'] : null
            ];

            if (!empty($data['user_id'])) {
                $result = $residentModel->createForExistingUser($data['user_id'], $data);
            } else {
                $result = $residentModel->createWithUser($data);
            }
            
            if ($result['success']) {
                if (!empty($result['username'])) {
                    $this->setFlash('success', "Résident créé avec succès. Identifiants: {$result['username']} / {$result['password']}");
                } else {
                    $this->setFlash('success', "Fiche résident créée avec succès et liée au compte utilisateur existant.");
                }
                $this->redirect('resident/index');
            } else {
                throw new Exception($result['error'] ?? "Erreur lors de la création");
            }
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur lors de la création du résident: ' . $e->getMessage());
            $this->redirect('resident/create');
        }
    }
}
