<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Contrats de Gestion
 * ====================================================================
 * Gestion des baux commerciaux entre propriétaires investisseurs et Domitys
 */

class ContratGestionController extends Controller {

    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'proprietaire']);

        $userRole = $_SESSION['user_role'];
        $contratModel = $this->model('ContratGestion');

        // Construire la requête selon le rôle
        if ($userRole === 'proprietaire') {
            $contrats = $contratModel->getByProprietaireUserId($_SESSION['user_id']);
        } else {
            $db = $this->model('User')->getDb();
            $sql = "SELECT
                        cg.*,
                        e.raison_sociale as exploitant,
                        c.nom as residence,
                        CONCAT(cp.prenom, ' ', cp.nom) as proprietaire
                    FROM contrats_gestion cg
                    INNER JOIN exploitants e ON cg.exploitant_id = e.id
                    INNER JOIN coproprietees c ON cg.copropriete_id = c.id
                    LEFT JOIN coproprietaires cp ON cg.proprietaire_id = cp.id";
            $contrats = $db->query($sql)->fetchAll(PDO::FETCH_OBJ);
        }

        $this->view('contrats/index', [
            'title' => 'Contrats de Gestion - ' . APP_NAME,
            'showNavbar' => true,
            'contrats' => $contrats,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ]);
    }

    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'proprietaire']);

        $contratModel = $this->model('ContratGestion');
        $contrat = $contratModel->findById($id);

        if (!$contrat) {
            $this->setFlash('error', 'Contrat introuvable');
            $this->redirect('contrat/index');
            return;
        }

        $userRole = $_SESSION['user_role'];
        if ($userRole === 'proprietaire' && $contrat->proprietaire_user_id != $_SESSION['user_id']) {
            $this->setFlash('error', 'Accès non autorisé à ce contrat');
            $this->redirect('contrat/index');
            return;
        }

        $paiements = $contratModel->getPaiements($id, 12);

        $this->view('contrats/details', [
            'title' => 'Contrat de Gestion - ' . APP_NAME,
            'showNavbar' => true,
            'contrat' => $contrat,
            'paiements' => $paiements,
            'userRole' => $userRole,
            'flash' => $this->getFlash()
        ]);
    }

    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            $exploitantModel = $this->model('Exploitant');
            $resModel = $this->model('Residence');
            $contratModel = $this->model('ContratGestion');

            $exploitants = array_map(fn($e) => (object)$e, $exploitantModel->getAllActive());
            $residences = array_map(fn($r) => (object)$r, $resModel->getAllForSelect());

            $this->view('contrats/create', [
                'title' => 'Nouveau Contrat de Gestion - ' . APP_NAME,
                'showNavbar' => true,
                'exploitants' => $exploitants,
                'residences' => $residences,
                'proprietaires' => $contratModel->getProprietairesList(),
                'flash' => $this->getFlash()
            ]);
        }
    }

    private function handleCreate() {
        $contratModel = $this->model('ContratGestion');

        try {
            $contratId = $contratModel->create([
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
            ]);

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
