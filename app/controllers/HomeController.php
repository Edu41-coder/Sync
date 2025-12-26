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
        // Vérifier que l'utilisateur est connecté
        $this->requireAuth();
        
        // Récupérer rôle et user ID
        $role = $_SESSION['user_role'] ?? 'proprietaire';
        $userId = $_SESSION['user_id'];
        
        // Charger le modèle User
        $userModel = $this->model('User');
        $user = $userModel->find($userId);
        
        // Récupérer les stats selon le rôle
        $stats = $this->getStatsByRole($role, $userId);
        
        // Récupérer les activités récentes
        $recentActivities = $this->getRecentActivities($role, $userId);
        
        // Préparer les données pour la vue
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
        
        // Charger la vue avec le layout
        $this->view('dashboard/index', $data, true);
    }
    
    /**
     * Récupère les statistiques selon le rôle
     */
    private function getStatsByRole($role, $userId) {
        // Obtenir connexion PDO
        $db = $this->getDbConnection();
        $stats = [];
        
        switch ($role) {
            case 'admin':
                // Admin : Vue globale (refactorisé avec Residence model)
                require_once '../app/models/Residence.php';
                $resModel = new Residence();
                $totalResidences = $resModel->countAll();
                
                $stmt = $db->query("
                    SELECT 
                        (SELECT COUNT(*) FROM users WHERE actif = 1) as total_users,
                        (SELECT COUNT(*) FROM contrats_gestion WHERE statut = 'actif') as total_contrats,
                        (SELECT COUNT(*) FROM residents_seniors WHERE actif = 1) as total_residents,
                        (SELECT COALESCE(SUM(loyer_mensuel_garanti), 0) FROM contrats_gestion WHERE statut = 'actif') as revenus_mensuels,
                        (SELECT COALESCE(AVG(taux_occupation_pct), 0) FROM v_taux_occupation) as taux_occupation_moyen
                ");
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
                $stats['total_residences'] = $totalResidences;
                break;
                
            case 'exploitant':
                // Exploitant : Ses résidences Domitys (refactorisé avec Residence model)
                require_once '../app/models/Residence.php';
                $resModel = new Residence();
                $mesResidences = $resModel->countByExploitant($_SESSION['user_id']);
                
                // Récupérer l'exploitant_id depuis user_id
                require_once '../app/models/Exploitant.php';
                $exploitantModel = new Exploitant();
                $exploitant = $exploitantModel->findByUserId($userId);
                $exploitantId = $exploitant ? $exploitant['id'] : 0;
                
                // Utiliser le modèle Occupation pour les stats
                require_once '../app/models/Occupation.php';
                $occupationModel = new Occupation();
                $occupationsActives = $occupationModel->getByExploitant($exploitantId, 'actif');
                
                $stats = [
                    'mes_residents' => count(array_unique(array_column($occupationsActives, 'resident_id'))),
                    'occupations_actives' => count($occupationsActives),
                    'revenus_residents_mois' => array_sum(array_column($occupationsActives, 'loyer_mensuel_resident')),
                    'paiements_proprietaires_mois' => 0  // À calculer depuis contrats_gestion
                ];
                $stats['mes_residences'] = $mesResidences;
                
                // Calculer la marge
                if ($stats) {
                    $stats['marge_mois'] = $stats['revenus_residents_mois'] - $stats['paiements_proprietaires_mois'];
                }
                break;
                
            case 'gestionnaire':
                // Gestionnaire : Ses copropriétés
                $stmt = $db->prepare("
                    SELECT 
                        (SELECT COUNT(*) FROM coproprietees WHERE syndic_id = :user_id1) as mes_coproprietees,
                        (SELECT COUNT(DISTINCT cp.id) 
                         FROM coproprietaires cp 
                         JOIN possessions p ON cp.id = p.coproprietaire_id 
                         JOIN lots l ON p.lot_id = l.id 
                         JOIN coproprietees c ON l.copropriete_id = c.id 
                         WHERE c.syndic_id = :user_id2) as mes_coproprietaires,
                        (SELECT COUNT(*) 
                         FROM appels_fonds af 
                         JOIN coproprietees c ON af.copropriete_id = c.id 
                         WHERE c.syndic_id = :user_id3 AND af.statut = 'emis') as appels_en_cours,
                        (SELECT COALESCE(SUM(laf.montant - laf.montant_paye), 0)
                         FROM lignes_appel_fonds laf
                         JOIN appels_fonds af ON laf.appel_fonds_id = af.id
                         JOIN coproprietees c ON af.copropriete_id = c.id
                         WHERE c.syndic_id = :user_id4 AND laf.statut_paiement != 'paye') as total_impayes
                ");
                $stmt->execute([
                    'user_id1' => $userId,
                    'user_id2' => $userId,
                    'user_id3' => $userId,
                    'user_id4' => $userId
                ]);
                $stats = $stmt->fetch(PDO::FETCH_ASSOC);
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
        $db = $this->getDbConnection();
        $activities = [];
        
        switch ($role) {
            case 'admin':
                // Derniers contrats créés (refactorisé avec ContratGestion model)
                require_once '../app/models/ContratGestion.php';
                $contratModel = new ContratGestion();
                $activities['contrats'] = $contratModel->getRecent(5);
                break;
                
            case 'exploitant':
                // Dernières occupations (refactorisé avec Occupation model)
                require_once '../app/models/Exploitant.php';
                require_once '../app/models/Occupation.php';
                $exploitantModel = new Exploitant();
                $occupationModel = new Occupation();
                
                $exploitant = $exploitantModel->findByUserId($userId);
                if ($exploitant) {
                    $occupations = $occupationModel->getByExploitant($exploitant['id']);
                    $activities['occupations'] = array_slice($occupations, 0, 5);
                } else {
                    $activities['occupations'] = [];
                }
                
                // Paiements en attente (refactorisé avec PaiementLoyerExploitant model)
                require_once '../app/models/PaiementLoyerExploitant.php';
                $paiementModel = new PaiementLoyerExploitant();
                $activities['paiements'] = $paiementModel->getPendingByUserId($userId, 5);
                break;
                
            case 'gestionnaire':
                // Appels de fonds récents (refactorisé avec AppelFonds model)
                require_once '../app/models/AppelFonds.php';
                $appelFondsModel = new AppelFonds();
                $activities['appels_fonds'] = $appelFondsModel->getRecentByGestionnaire($userId, 5);
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
