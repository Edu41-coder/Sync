<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur d'accueil
 * ====================================================================
 */

class HomeController extends Controller {

    /**
     * Page d'accueil / Tableau de bord
     */
    public function index() {
        $this->requireAuth();

        $role = $_SESSION['user_role'] ?? 'proprietaire';
        $userId = $_SESSION['user_id'];

        $userModel = $this->model('User');
        $user = $userModel->find($userId);

        $stats = $this->getStatsByRole($role, $userId);
        $recentActivities = $this->getRecentActivities($role, $userId);

        $data = [
            'title' => 'Tableau de bord - ' . APP_NAME,
            'user' => $user,
            'role' => $role,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'flash' => $this->getFlash(),
            'showNavbar' => true,
            'bodyClass' => 'dashboard'
        ];

        // Données supplémentaires pour le propriétaire
        if ($role === 'proprietaire') {
            $coproModel = $this->model('Coproprietaire');
            $proprioId = $coproModel->getIdByUserId($userId);

            if ($proprioId) {
                $data['propContrats'] = $coproModel->getContratsDetailles($proprioId);
                $data['propResidences'] = $coproModel->getMesResidencesDetaillees($proprioId);
            } else {
                $data['propContrats'] = [];
                $data['propResidences'] = [];
            }
        }

        $this->view('dashboard/index', $data, true);
    }

    /**
     * Récupère les statistiques selon le rôle
     */
    private function getStatsByRole($role, $userId) {
        $stats = [];

        switch ($role) {
            case 'admin':
                $userModel = $this->model('User');
                $resModel = $this->model('Residence');
                $stats = $userModel->getAdminDashboardStats();
                $stats['total_residences'] = $resModel->countAll();
                break;

            case 'exploitant':
                $resModel = $this->model('Residence');
                $mesResidences = $resModel->countByExploitant($userId);

                $exploitantModel = $this->model('Exploitant');
                $exploitant = $exploitantModel->findByUserId($userId);
                $exploitantId = $exploitant ? $exploitant->id : 0;

                $occupationModel = $this->model('Occupation');
                $occupationsActives = $occupationModel->getByExploitant($exploitantId, 'actif');

                $stats = [
                    'mes_residents' => count(array_unique(array_column($occupationsActives, 'resident_id'))),
                    'occupations_actives' => count($occupationsActives),
                    'revenus_residents_mois' => array_sum(array_column($occupationsActives, 'loyer_mensuel_resident')),
                    'paiements_proprietaires_mois' => 0,
                    'mes_residences' => $mesResidences,
                ];
                $stats['marge_mois'] = $stats['revenus_residents_mois'] - $stats['paiements_proprietaires_mois'];
                break;

            case 'proprietaire':
                $coproModel = $this->model('Coproprietaire');
                $proprioId = $coproModel->getIdByUserId($userId);
                $stats = $proprioId ? $coproModel->getProprioStats($proprioId) : [
                    'total_contrats'=>0,'contrats_actifs'=>0,'total_lots'=>0,
                    'revenus_mensuels'=>0,'revenus_annuels'=>0,'total_residences'=>0
                ];
                break;

            default:
                $stats = [];
        }

        return $stats;
    }

    /**
     * Récupère les activités récentes selon le rôle
     */
    private function getRecentActivities($role, $userId) {
        $activities = [];

        switch ($role) {
            case 'admin':
                $contratModel = $this->model('ContratGestion');
                $activities['contrats'] = $contratModel->getRecent(5);
                break;

            case 'exploitant':
                $exploitantModel = $this->model('Exploitant');
                $occupationModel = $this->model('Occupation');

                $exploitant = $exploitantModel->findByUserId($userId);
                if ($exploitant) {
                    $occupations = $occupationModel->getByExploitant($exploitant->id ?? $exploitant['id']);
                    $activities['occupations'] = array_slice($occupations, 0, 5);
                } else {
                    $activities['occupations'] = [];
                }

                $paiementModel = $this->model('PaiementLoyerExploitant');
                $activities['paiements'] = $paiementModel->getPendingByUserId($userId, 5);
                break;
        }

        return $activities;
    }

    /**
     * Test de connexion à la base de données
     */
    public function test() {
        echo "<h1>Test MVC</h1>";
        echo "<p>✅ Le routeur fonctionne !</p>";
        echo "<p>✅ Le contrôleur HomeController fonctionne !</p>";
        echo "<p>URL: http://localhost/Synd_Gest/public/home/test</p>";
        echo "<p><a href='" . BASE_URL . "'>Retour à l'accueil</a></p>";
        echo "<p><a href='" . BASE_URL . "/auth/login'>Page de connexion</a></p>";
    }
}
