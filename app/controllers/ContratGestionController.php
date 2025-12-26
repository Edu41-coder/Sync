<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Contrats de Gestion
 * ====================================================================
 * Gestion des baux commerciaux entre propriétaires investisseurs et Domitys
 */

class ContratGestionController extends Controller {
    
    /**
     * Liste des contrats de gestion
     * - Admin/Gestionnaire: tous les contrats
     * - Exploitant: tous les contrats de son entreprise
     * - Propriétaire: uniquement ses propres contrats
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant', 'proprietaire']);
        
        $userModel = $this->model('User');
        $db = $userModel->getDb();
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        // Construire la requête selon le rôle
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant,
                    c.nom as residence,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id";
        
        // Filtrer par propriétaire si role = proprietaire
        if ($userRole === 'proprietaire') {
            $sql .= " WHERE cp.user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$userId]);
        } else {
            $stmt = $db->query($sql);
        }
        
        $contrats = $stmt->fetchAll(PDO::FETCH_OBJ);
        
        $data = [
            'title' => 'Contrats de Gestion - ' . APP_NAME,
            'showNavbar' => true,
            'contrats' => $contrats,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ];
        
        $this->view('contrats/index', $data);
    }
    
    /**
     * Détails d'un contrat de gestion
     */
    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire', 'exploitant', 'proprietaire']);
        
        $userModel = $this->model('User');
        $db = $userModel->getDb();
        $userId = $_SESSION['user_id'];
        $userRole = $_SESSION['user_role'];
        
        // Récupérer le contrat
        $sql = "SELECT 
                    cg.*,
                    e.raison_sociale as exploitant, e.siret as exploitant_siret,
                    c.nom as residence, c.adresse, c.code_postal, c.ville,
                    cp.user_id as proprietaire_user_id,
                    CONCAT(cp.prenom, ' ', cp.nom) as proprietaire,
                    cp.email as proprietaire_email, cp.telephone as proprietaire_tel
                FROM contrats_gestion cg
                INNER JOIN exploitants e ON cg.exploitant_id = e.id
                INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id
                WHERE cg.id = ?";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([$id]);
        $contrat = $stmt->fetch(PDO::FETCH_OBJ);
        
        if (!$contrat) {
            $this->setFlash('error', 'Contrat introuvable');
            $this->redirect('contrat/index');
            return;
        }
        
        // Vérifier les droits d'accès (propriétaire ne voit que ses contrats)
        if ($userRole === 'proprietaire' && $contrat->proprietaire_user_id != $userId) {
            $this->setFlash('error', 'Accès non autorisé à ce contrat');
            $this->redirect('contrat/index');
            return;
        }
        
        // Récupérer l'historique des paiements
        $sqlPaiements = "SELECT * FROM paiements_loyers_exploitant 
                         WHERE contrat_gestion_id = ? 
                         ORDER BY date_paiement_prevue DESC 
                         LIMIT 12";
        $stmtPaiements = $db->prepare($sqlPaiements);
        $stmtPaiements->execute([$id]);
        $paiements = $stmtPaiements->fetchAll(PDO::FETCH_OBJ);
        
        $data = [
            'title' => 'Contrat de Gestion - ' . APP_NAME,
            'showNavbar' => true,
            'contrat' => $contrat,
            'paiements' => $paiements,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ];
        
        $this->view('contrats/details', $data);
    }
    
    /**
     * Créer un nouveau contrat de gestion
     * Uniquement accessible par admin et gestionnaire
     */
    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'gestionnaire']);
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            $db = $this->model('User')->getDb();
            
            // Récupérer les exploitants actifs (refactorisé)
            require_once '../app/models/Exploitant.php';
            $exploitantModel = new Exploitant();
            $exploitantsArray = $exploitantModel->getAllActive();
            $exploitants = array_map(function($e) { return (object)$e; }, $exploitantsArray);
            
            // Récupérer les résidences seniors (refactorisé avec Residence model)
            require_once '../app/models/Residence.php';
            $resModel = new Residence();
            $residencesArray = $resModel->getAllForSelect();
            $residences = array_map(function($r) { return (object)$r; }, $residencesArray);
            
            // Récupérer les propriétaires
            $proprietaires = $db->query("SELECT cp.*, CONCAT(cp.prenom, ' ', cp.nom) as nom_complet 
                                         FROM coproprietaires cp 
                                         ORDER BY cp.nom, cp.prenom")->fetchAll(PDO::FETCH_OBJ);
            
            $data = [
                'title' => 'Nouveau Contrat de Gestion - ' . APP_NAME,
                'showNavbar' => true,
                'exploitants' => $exploitants,
                'residences' => $residences,
                'proprietaires' => $proprietaires,
                'flash' => $this->getFlash()
            ];
            
            $this->view('contrats/create', $data);
        }
    }
    
    /**
     * Traiter la création d'un contrat
     */
    private function handleCreate() {
        require_once '../app/models/ContratGestion.php';
        $contratModel = new ContratGestion();
        
        try {
            $data = [
                'exploitant_id' => $_POST['exploitant_id'],
                'copropriete_id' => $_POST['copropriete_id'],
                'lot_id' => $_POST['lot_id'] ?? null,
                'coproprietaire_id' => $_POST['proprietaire_id'],
                'date_debut' => $_POST['date_debut'],
                'date_fin' => $_POST['date_fin'],
                'duree_annees' => $_POST['duree_annees'],
                'loyer_mensuel_exploitant' => $_POST['loyer_mensuel_exploitant'],
                'conditions_indexation' => $_POST['conditions_indexation'] ?? 'IRL',
                'clauses_particulieres' => $_POST['clauses_particulieres'] ?? null
            ];
            
            $contratId = $contratModel->create($data);
            
            if ($contratId) {
                $this->setFlash('success', 'Contrat de gestion créé avec succès');
                $this->redirect('contrat/index');
            } else {
                throw new Exception("Erreur lors de la création du contrat");
            }
            
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur lors de la création du contrat: ' . $e->getMessage());
            $this->redirect('contrat/create');
        }
    }
}
