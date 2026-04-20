<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur d'Administration
 * ====================================================================
 */

class AdminController extends Controller {

    /**
     * Géocoder une adresse via api-adresse.data.gouv.fr
     */
    private function geocodeAddress(string $adresse, string $codePostal, string $ville): ?array {
        $q = trim("$adresse $codePostal $ville");
        $url = 'https://api-adresse.data.gouv.fr/search/?' . http_build_query([
            'q' => $q,
            'postcode' => $codePostal,
            'limit' => 1
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $json = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$json || $httpCode !== 200) return null;

        $data = json_decode($json, true);
        if (!empty($data['features'][0])) {
            $feature = $data['features'][0];
            $score = $feature['properties']['score'] ?? 0;
            if ($score < 0.4) return null;
            $coords = $feature['geometry']['coordinates'];
            return ['lat' => round($coords[1], 6), 'lng' => round($coords[0], 6)];
        }
        return null;
    }

    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $this->view('admin/index', [
            'title' => 'Administration - ' . APP_NAME,
            'showNavbar' => true,
            'flash' => $this->getFlash()
        ], true);
    }

    /**
     * Migrations de base de données
     */
    public function migrate() {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $migration = new Migration();

        $results = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $action = $_POST['action'] ?? 'migrate';

            if ($action === 'migrate') {
                $results = $migration->migrate();
                if (!empty($results['applied'])) {
                    $this->setFlash('success', count($results['applied']) . ' migration(s) appliquée(s) avec succès.');
                } elseif (!empty($results['errors'])) {
                    $this->setFlash('error', 'Erreur lors de la migration.');
                } else {
                    $this->setFlash('info', 'Aucune migration en attente.');
                }
            } elseif ($action === 'mark_initial') {
                $migration->markAsApplied('001_initial_schema');
                $this->setFlash('success', 'Schéma initial marqué comme appliqué.');
            }
        }

        $this->view('admin/migrate', [
            'title'    => 'Migrations DB - ' . APP_NAME,
            'showNavbar' => true,
            'status'   => $migration->getStatus(),
            'pending'  => $migration->getPending(),
            'results'  => $results,
            'flash'    => $this->getFlash()
        ], true);
    }

    /**
     * Route intelligente pour la gestion des utilisateurs
     */
    public function users($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin']);

        switch ($action) {
            case 'create':  return $this->createUser();
            case 'store':   return $this->storeUser();
            case 'edit':    return $this->editUser($id);
            case 'update':  return $this->updateUser($id);
            case 'toggle':  return $this->toggleUser($id);
            case 'delete':  return $this->deleteUser($id);
            case 'show':    return $this->showUser($id);
            default:        return $this->listUsers();
        }
    }

    /**
     * Fiche détaillée d'un utilisateur (lecture seule)
     */
    private function showUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $userModel = $this->model('User');
        $userData = $userModel->find($id);
        $user = $userData ? (array)$userData : null;

        if (!$user) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
            return;
        }

        $roles = $userModel->getAllRoles();
        $rolesMap = [];
        foreach ($roles as $r) $rolesMap[$r['slug']] = $r;

        // Profils liés via le modèle User
        $proprietaire = ($user['role'] === 'proprietaire') ? $userModel->getProprietaireProfile($id) : null;

        $resident = null;
        $occupations = [];
        if ($user['role'] === 'locataire_permanent') {
            $resident = $userModel->getResidentProfile($id);
            if ($resident) {
                $occupations = $userModel->getOccupationsByResident($resident['id']);
            }
        }

        $exploitant = ($user['role'] === 'exploitant') ? $userModel->getExploitantProfile($id) : null;
        $userResidences = $userModel->getUserResidencesList($id);
        $contrats = $proprietaire ? $userModel->getContratsByProprietaire($proprietaire['id']) : [];

        $this->view('admin/users/show', [
            'title'          => $user['prenom'] . ' ' . $user['nom'] . ' - ' . APP_NAME,
            'showNavbar'     => true,
            'user'           => $user,
            'roleInfo'       => $rolesMap[$user['role']] ?? null,
            'proprietaire'   => $proprietaire,
            'resident'       => $resident,
            'occupations'    => $occupations,
            'exploitant'     => $exploitant,
            'userResidences' => $userResidences,
            'contrats'       => $contrats,
            'flash'          => $this->getFlash()
        ], true);
    }

    /**
     * Liste des utilisateurs
     */
    private function listUsers() {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $userModel = $this->model('User');
        $users     = $userModel->getAllWithResidentProfile();
        $roles     = $userModel->getAllRoles();

        $roleCounts = [];
        foreach ($users as $u) {
            $roleCounts[$u['role']] = ($roleCounts[$u['role']] ?? 0) + 1;
        }
        $stats = array_merge($roleCounts, [
            'total'  => count($users),
            'actifs' => count(array_filter($users, fn($u) => $u['actif'] == 1))
        ]);

        $this->view('admin/users/index', [
            'title'    => 'Gestion des Utilisateurs - ' . APP_NAME,
            'showNavbar' => true,
            'users'    => $users,
            'stats'    => $stats,
            'roles'    => $roles,
            'flash'    => $this->getFlash()
        ], true);
    }

    /**
     * Liste des contrats de gestion
     */
    public function contrats() {
        $this->requireAuth();
        $this->requireRole(['admin', 'comptable', 'proprietaire']);

        $contratModel = $this->model('ContratGestion');
        $contrats = $contratModel->getAll();

        // Propriétaire : filtrer ses contrats uniquement
        $currentRole = $_SESSION['user_role'] ?? '';
        if ($currentRole === 'proprietaire') {
            $coproModel = $this->model('Coproprietaire');
            $proprioId = $coproModel->getIdByUserId($_SESSION['user_id']);
            if ($proprioId) {
                $contrats = array_values(array_filter($contrats, fn($c) => ($c['coproprietaire_id'] ?? null) == $proprioId));
            } else {
                $contrats = [];
            }
        }

        $actifs = count(array_filter($contrats, fn($c) => $c['statut'] === 'actif'));
        $totalLoyer = array_sum(array_column(array_filter($contrats, fn($c) => $c['statut'] === 'actif'), 'loyer_mensuel_garanti'));
        $totalMarge = array_sum(array_column(array_filter($contrats, fn($c) => $c['statut'] === 'actif' && $c['marge_mensuelle'] !== null), 'marge_mensuelle'));

        $this->view('admin/contrats', [
            'title'    => 'Contrats de Gestion - ' . APP_NAME,
            'showNavbar' => true,
            'contrats' => $contrats,
            'stats'    => [
                'total'      => count($contrats),
                'actifs'     => $actifs,
                'loyer_total'=> $totalLoyer,
                'marge_total'=> $totalMarge
            ],
            'flash'    => $this->getFlash()
        ], true);
    }

    public function parametres() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->view('admin/parametres', ['title' => 'Paramètres - ' . APP_NAME, 'showNavbar' => true], true);
    }

    /**
     * Gestion des services (catalogue)
     */
    public function services($action = null, $id = null) {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $serviceModel = $this->model('Service');

        if ($action === 'store' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', trim($_POST['nom'] ?? '')));
            if ($serviceModel->slugExists($slug)) {
                $this->setFlash('error', "Un service avec ce slug existe déjà.");
                $this->redirect('admin/services');
                return;
            }
            $result = $serviceModel->createService([
                'nom' => trim($_POST['nom']), 'slug' => $slug,
                'categorie' => $_POST['categorie'] ?? 'inclus',
                'prix_defaut' => (float)($_POST['prix_defaut'] ?? 0),
                'description' => trim($_POST['description'] ?? '') ?: null,
                'icone' => trim($_POST['icone'] ?? '') ?: 'fas fa-concierge-bell',
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
                'actif' => isset($_POST['actif']) ? 1 : 0,
            ]);
            $this->setFlash($result ? 'success' : 'error', $result ? 'Service créé avec succès' : 'Erreur lors de la création');
            $this->redirect('admin/services');
            return;
        }

        if ($action === 'update' && $id && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            $slug = trim($_POST['slug'] ?? '');
            if (empty($slug)) $slug = strtolower(preg_replace('/[^a-z0-9]+/', '_', trim($_POST['nom'] ?? '')));
            if ($serviceModel->slugExists($slug, (int)$id)) {
                $this->setFlash('error', "Un service avec ce slug existe déjà.");
                $this->redirect('admin/services');
                return;
            }
            $result = $serviceModel->updateService((int)$id, [
                'nom' => trim($_POST['nom']), 'slug' => $slug,
                'categorie' => $_POST['categorie'] ?? 'inclus',
                'prix_defaut' => (float)($_POST['prix_defaut'] ?? 0),
                'description' => trim($_POST['description'] ?? '') ?: null,
                'icone' => trim($_POST['icone'] ?? '') ?: 'fas fa-concierge-bell',
                'ordre_affichage' => (int)($_POST['ordre_affichage'] ?? 0),
                'actif' => isset($_POST['actif']) ? 1 : 0,
            ]);
            $this->setFlash($result ? 'success' : 'error', $result ? 'Service modifié avec succès' : 'Erreur lors de la modification');
            $this->redirect('admin/services');
            return;
        }

        if ($action === 'delete' && $id) {
            $serviceModel->deleteService((int)$id);
            $this->setFlash('success', 'Service désactivé.');
            $this->redirect('admin/services');
            return;
        }

        $this->view('admin/services', [
            'title' => 'Gestion des Services - ' . APP_NAME,
            'showNavbar' => true,
            'services' => $serviceModel->getAll(),
            'flash' => $this->getFlash()
        ], true);
    }

    public function logs() {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $logs = Logger::getRecentSecurityLogs(100);

        $stats = [
            'unauthorized' => count(array_filter($logs, fn($l) => $l['type'] === 'UNAUTHORIZED_ACCESS')),
            'failed_logins' => count(array_filter($logs, fn($l) => $l['type'] === 'FAILED_LOGIN')),
            'csrf_violations' => count(array_filter($logs, fn($l) => $l['type'] === 'CSRF_VIOLATION')),
            'rate_limit' => count(array_filter($logs, fn($l) => $l['type'] === 'RATE_LIMIT_EXCEEDED'))
        ];

        $blockFile = '../logs/blocked_ips.json';
        $blockedIps = [];
        if (file_exists($blockFile)) {
            $blocked = json_decode(file_get_contents($blockFile), true) ?? [];
            foreach ($blocked as $ip => $data) {
                if ($data['expires_at'] > time()) {
                    $blockedIps[$ip] = $data;
                }
            }
            $stats['blocked_ips'] = count($blockedIps);
        } else {
            $stats['blocked_ips'] = 0;
        }

        $this->view('admin/security_logs', [
            'title' => 'Logs de Sécurité - ' . APP_NAME,
            'showNavbar' => true,
            'logs' => $logs,
            'stats' => $stats,
            'blockedIps' => $blockedIps,
            'flash' => $this->getFlash()
        ], true);
    }

    public function backup() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->setFlash('success', 'Backup créé avec succès');
        $this->redirect('admin/index');
    }

    /**
     * Liste des résidences seniors
     */
    public function residences() {
        $this->requireAuth();
        $allowedRoles = ['admin', 'directeur_residence', 'exploitant', 'proprietaire'];
        if (!$this->hasRole($allowedRoles)) {
            $this->requireRole($allowedRoles);
        }

        $currentRole = $_SESSION['user_role'] ?? null;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        $resModel = $this->model('Residence');

        $filters = [];
        if ($currentRole === 'exploitant') {
            $exploitantModel = $this->model('Exploitant');
            $exploitant = $exploitantModel->findByUserId($currentUserId);
            if ($exploitant && isset($exploitant->id)) {
                $filters['exploitant'] = (string)$exploitant->id;
            } else {
                $this->setFlash('warning', "Aucun profil exploitant lié à votre compte.");
                $filters['exploitant'] = '-1';
            }
        }

        $residences = $resModel->getAll($filters);
        $exploitants = $resModel->getExploitantsList();

        $this->view('residences/residences', [
            'title' => 'Résidences Seniors - ' . APP_NAME,
            'residences' => $residences,
            'exploitants' => $exploitants,
            'currentRole' => $currentRole,
            'canManageResidences' => $currentRole === 'admin',
            'canExportResidences' => in_array($currentRole, ['admin', 'directeur_residence']),
            'canCreateResidence' => $currentRole === 'admin',
            'canSeeMap' => in_array($currentRole, ['admin', 'directeur_residence']),
            'showNavbar' => true
        ], true);
    }

    public function exportResidencesExcel() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence']);

        $filters = [
            'search' => $_GET['search'] ?? '', 'ville' => $_GET['ville'] ?? '',
            'exploitant' => $_GET['exploitant'] ?? '', 'taux_min' => $_GET['taux_min'] ?? ''
        ];

        $resModel = $this->model('Residence');
        $result = $resModel->search($filters, 1, 999999);
        $residences = $result['rows'] ?? [];

        $filename = 'residences_' . date('Y-m-d_His') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        fputcsv($output, ['ID','Nom','Adresse','Code Postal','Ville','Exploitant','Total Lots','Appartements','Occupés','Taux Occupation (%)','Date Création'], ';');

        foreach ($residences as $residence) {
            fputcsv($output, [
                $residence['id'] ?? '', $residence['nom'] ?? '', $residence['adresse'] ?? '',
                $residence['code_postal'] ?? '', $residence['ville'] ?? '', $residence['exploitant_nom'] ?? 'Non défini',
                $residence['total_lots'] ?? 0, $residence['total_studios'] ?? 0, $residence['studios_occupes'] ?? 0,
                number_format($residence['taux_occupation_pct'] ?? 0, 2, ',', ' '),
                date('d/m/Y', strtotime($residence['created_at'] ?? 'now'))
            ], ';');
        }
        fclose($output);
        exit;
    }

    public function createResidence() {
        $this->requireAuth();
        $this->requireRole('admin');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $data = [
                'nom' => $_POST['nom'] ?? '', 'adresse' => $_POST['adresse'] ?? '',
                'code_postal' => $_POST['code_postal'] ?? '',
                'ville' => ucfirst(mb_strtolower(trim($_POST['ville'] ?? ''), 'UTF-8')),
                'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'exploitant_id' => $_POST['exploitant_id'] ?? null,
                'description' => $_POST['description'] ?? '',
                'type_residence' => 'residence_seniors'
            ];

            $errors = [];
            if (empty($data['nom'])) $errors[] = "Le nom est requis";
            if (empty($data['adresse'])) $errors[] = "L'adresse est requise";
            if (empty($data['code_postal'])) $errors[] = "Le code postal est requis";
            if (empty($data['ville'])) $errors[] = "La ville est requise";

            if (empty($data['latitude']) && !empty($data['adresse']) && !empty($data['ville'])) {
                $coords = $this->geocodeAddress($data['adresse'], $data['code_postal'], $data['ville']);
                if ($coords) { $data['latitude'] = $coords['lat']; $data['longitude'] = $coords['lng']; }
            }

            if (empty($errors)) {
                $resModel = $this->model('Residence');
                $newId = $resModel->create($data);
                if ($newId) {
                    $exploitantPourcentages = $_POST['exploitant_pourcentages'] ?? [];
                    if (!empty($exploitantPourcentages)) {
                        $exploitantModel = $this->model('Exploitant');
                        $result = $exploitantModel->syncResidenceExploitants((int)$newId, $exploitantPourcentages);
                        if ($result !== true) {
                            $this->setFlash('warning', "Résidence créée mais erreur exploitants : $result");
                            $this->redirect('admin/residences');
                            return;
                        }
                    }
                    $this->setFlash('success', "Résidence créée avec succès");
                    $this->redirect('admin/residences');
                } else {
                    $this->setFlash('error', "Erreur lors de la création de la résidence");
                }
            } else {
                foreach ($errors as $error) $this->setFlash('error', $error);
            }
        }

        $exploitantModel = $this->model('Exploitant');
        $this->view('residences/residence_create', [
            'title' => 'Nouvelle Résidence - ' . APP_NAME,
            'exploitants' => $exploitantModel->getAllActive(),
            'showNavbar' => true
        ], true);
    }

    public function viewResidence($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'proprietaire']);

        $currentRole = $_SESSION['user_role'] ?? null;
        $currentUserId = (int)($_SESSION['user_id'] ?? 0);

        $resModel = $this->model('Residence');
        $residence = $resModel->findWithExploitant($id);

        if (!$residence) {
            $this->setFlash('error', "Résidence non trouvée");
            $this->redirect('admin/residences');
            return;
        }

        if ($currentRole === 'exploitant') {
            $exploitantModel = $this->model('Exploitant');
            $exploitant = $exploitantModel->findByUserId($currentUserId);
            if (!$exploitant || !$resModel->hasExploitantAccess((int)$id, (int)$exploitant->id)) {
                $this->setFlash('error', "Accès refusé à cette résidence.");
                $this->redirect('admin/residences');
                return;
            }
        }

        $lotModel = $this->model('Lot');
        $lots = $lotModel->getByResidence($id);

        // Propriétaire : filtrer pour ne montrer que ses lots
        if ($currentRole === 'proprietaire') {
            $coproModel = $this->model('Coproprietaire');
            $proprioId = $coproModel->getIdByUserId($currentUserId);
            if ($proprioId) {
                $mesLotIds = $coproModel->getLotIdsByProprietaire($proprioId);
                $lots = array_values(array_filter($lots, fn($l) => in_array($l['id'], $mesLotIds)));
            } else {
                $lots = [];
            }
        }

        $this->view('residences/residence_view', [
            'title' => $residence['nom'] . ' - ' . APP_NAME,
            'residence' => $residence,
            'lots' => $lots,
            'stats' => $lotModel->getResidenceStats($id),
            'showNavbar' => true
        ], true);
    }

    public function editResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');

        $resModel = $this->model('Residence');
        $residence = $resModel->find($id);

        if (!$residence) {
            $this->setFlash('error', "Résidence non trouvée");
            $this->redirect('admin/residences');
            return;
        }

        if (is_object($residence)) $residence = (array)$residence;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $data = [
                'nom' => $_POST['nom'] ?? '', 'adresse' => $_POST['adresse'] ?? '',
                'code_postal' => $_POST['code_postal'] ?? '',
                'ville' => ucfirst(mb_strtolower(trim($_POST['ville'] ?? ''), 'UTF-8')),
                'latitude' => !empty($_POST['latitude']) ? (float)$_POST['latitude'] : null,
                'longitude' => !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null,
                'exploitant_id' => $_POST['exploitant_id'] ?? null,
                'description' => $_POST['description'] ?? ''
            ];

            $errors = [];
            if (empty($data['nom'])) $errors[] = "Le nom est requis";
            if (empty($data['adresse'])) $errors[] = "L'adresse est requise";
            if (empty($data['code_postal'])) $errors[] = "Le code postal est requis";
            if (empty($data['ville'])) $errors[] = "La ville est requise";

            if (empty($data['latitude']) && !empty($data['adresse']) && !empty($data['ville'])) {
                $coords = $this->geocodeAddress($data['adresse'], $data['code_postal'], $data['ville']);
                if ($coords) { $data['latitude'] = $coords['lat']; $data['longitude'] = $coords['lng']; }
            }

            if (empty($errors)) {
                if ($resModel->updateResidence($id, $data)) {
                    $exploitantPourcentages = $_POST['exploitant_pourcentages'] ?? [];
                    if (!empty($exploitantPourcentages)) {
                        $exploitantModel = $this->model('Exploitant');
                        $result = $exploitantModel->syncResidenceExploitants((int)$id, $exploitantPourcentages);
                        if ($result !== true) {
                            $this->setFlash('warning', "Résidence modifiée mais erreur exploitants : $result");
                            $this->redirect('admin/residences');
                            return;
                        }
                    }
                    $this->setFlash('success', "Résidence modifiée avec succès");
                    $this->redirect('admin/residences');
                } else {
                    $this->setFlash('error', "Erreur lors de la modification");
                }
            } else {
                foreach ($errors as $error) $this->setFlash('error', $error);
            }
        }

        $exploitantModel = $this->model('Exploitant');
        $this->view('residences/residence_edit', [
            'title'               => 'Modifier ' . $residence['nom'] . ' - ' . APP_NAME,
            'residence'           => $residence,
            'exploitants'         => $exploitantModel->getAllActive(),
            'exploitantsResidence'=> $exploitantModel->getByResidence((int)$id),
            'showNavbar'          => true
        ], true);
    }

    public function deleteResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');

        $resModel = $this->model('Residence');
        $residence = $resModel->find($id);
        if (!$residence) {
            $this->setFlash('error', "Résidence non trouvée");
            $this->redirect('admin/residences');
            return;
        }

        $counts = $resModel->getLinkedCounts($id);
        $isVierge = ($counts['lots'] + $counts['users']) === 0;

        if ($isVierge) {
            if ($resModel->hardDeleteResidence($id)) {
                $this->setFlash('success', "Résidence supprimée définitivement.");
            } else {
                $this->setFlash('error', "Erreur lors de la suppression.");
            }
        } else {
            if ($resModel->deleteResidence($id)) {
                $details = [];
                if ($counts['lots'] > 0) $details[] = "{$counts['lots']} lot(s)";
                if ($counts['users'] > 0) $details[] = "{$counts['users']} utilisateur(s) lié(s)";
                $this->setFlash('success', "Résidence désactivée (données associées : " . implode(', ', $details) . "). Elle peut être réactivée.");
            } else {
                $this->setFlash('error', "Erreur lors de la désactivation.");
            }
        }

        $this->redirect('admin/residences');
    }

    public function restoreResidence($id) {
        $this->requireAuth();
        $this->requireRole('admin');

        $resModel = $this->model('Residence');
        $this->setFlash($resModel->restoreResidence($id) ? 'success' : 'error',
            $resModel->restoreResidence($id) ? "Résidence réactivée avec succès." : "Erreur lors de la réactivation.");
        $this->redirect('admin/residences');
    }

    public function carteResidences() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'proprietaire']);

        $resModel = $this->model('Residence');
        $residences = $resModel->getAllForMap();

        $totalResidences = count($residences);
        $totalLots = array_sum(array_column($residences, 'nb_lots'));
        $totalOccupations = array_sum(array_column($residences, 'nb_occupations'));
        $tauxGlobal = $totalLots > 0 ? round(($totalOccupations / $totalLots) * 100, 2) : 0;

        $this->view('residences/carte_residences', [
            'title' => 'Carte des Résidences - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'stats' => ['total' => $totalResidences, 'lots' => $totalLots, 'occupations' => $totalOccupations, 'taux' => $tauxGlobal],
            'flash' => $this->getFlash()
        ], true);
    }

    public function carteResidence($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'proprietaire']);

        $resModel = $this->model('Residence');
        $allForMap = $resModel->getAllForMap();
        $residences = array_values(array_filter($allForMap, fn($r) => (int)$r['id'] === (int)$id));

        if (empty($residences)) {
            $this->setFlash('error', "Résidence non trouvée ou sans coordonnées GPS.");
            $this->redirect('admin/residences');
            return;
        }

        $res = $residences[0];
        $this->view('residences/carte_residences', [
            'title' => 'Carte — ' . $res['nom'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'singleMode' => true,
            'stats' => [
                'total' => 1, 'lots' => $res['nb_lots'] ?? 0, 'occupations' => $res['nb_occupations'] ?? 0,
                'taux' => ($res['nb_lots'] ?? 0) > 0 ? round((($res['nb_occupations'] ?? 0) / $res['nb_lots']) * 100, 2) : 0
            ],
            'flash' => $this->getFlash()
        ], true);
    }

    /**
     * Formulaire de création d'un utilisateur
     */
    private function createUser() {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $userModel = $this->model('User');
        $residenceModel = $this->model('Residence');
        $lotModel = $this->model('Lot');
        $exploitantModel = $this->model('Exploitant');

        $this->view('admin/users/create', [
            'title'                  => 'Créer un utilisateur - ' . APP_NAME,
            'showNavbar'             => true,
            'roles'                  => $userModel->getAllRoles(),
            'residencesForAssignment'=> $residenceModel->getAllForAssignment(),
            'residences'             => $residenceModel->getAllSimple(),
            'lots'                   => $lotModel->getAllGroupedByResidence(),
            'exploitants'            => $exploitantModel->getAllActive(),
            'selectedResidenceIds'   => [],
            'preselectedRole'        => $_GET['role'] ?? '',
            'flash'                  => $this->getFlash()
        ], true);
    }

    /**
     * Enregistrement d'un nouvel utilisateur
     */
    private function storeUser() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->verifyCsrf();

        $errors = [];
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;

        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($prenom)) $errors[] = "Le prénom est requis";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if (strlen($username) < 3) $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";
        if (empty($password)) $errors[] = "Le mot de passe est requis";
        if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
        if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas";

        $userModel = $this->model('User');
        $validRoles = array_column($userModel->getAllRoles(), 'slug');
        if (!in_array($role, $validRoles)) $errors[] = "Rôle invalide";

        $staffRoles = ['directeur_residence','employe_residence','technicien',
                       'jardinier_manager','jardinier_employe','entretien_manager',
                       'menage_interieur','menage_exterieur',
                       'restauration_manager','restauration_serveur','restauration_cuisine',
                       'comptable','employe_laverie'];

        if ($role === 'locataire_permanent') {
            if (empty($_POST['rs_date_naissance'])) $errors[] = "La date de naissance est requise pour un résident senior.";
            if (empty($_POST['rs_civilite'])) $errors[] = "La civilité est requise.";
        }
        if ($role === 'exploitant') {
            if (empty(trim($_POST['exp_raison_sociale'] ?? ''))) $errors[] = "La raison sociale est requise pour un exploitant.";
            if (empty(trim($_POST['exp_siret'] ?? ''))) $errors[] = "Le SIRET est requis pour un exploitant.";
        }

        if ($userModel->emailExists($email)) $errors[] = "Cet email est déjà utilisé";
        if ($userModel->usernameExists($username)) $errors[] = "Ce nom d'utilisateur est déjà utilisé";

        if (!empty($errors)) {
            foreach ($errors as $error) $this->setFlash('error', $error);
            $this->redirect('admin/users/create');
        }

        try {
            $db = $userModel->getDb();
            $db->beginTransaction();

            $userId = $userModel->createUser([
                'nom' => $nom, 'prenom' => $prenom, 'email' => $email,
                'username' => $username, 'password' => $password,
                'role' => $role, 'actif' => $actif
            ]);
            if (!$userId) throw new Exception("Erreur lors de la création du compte");

            // STAFF : affecter résidence(s)
            if (in_array($role, $staffRoles)) {
                $staffResidenceIds = array_values(array_unique(
                    array_filter(array_map('intval', $_POST['staff_residence_ids'] ?? []), fn($id) => $id > 0)
                ));
                $userModel->assignToResidences($userId, $staffResidenceIds, $role);
            }

            // PROPRIÉTAIRE : créer fiche
            if ($role === 'proprietaire') {
                $userModel->createProprietaireProfile($userId, [
                    'civilite' => $_POST['prop_civilite'] ?? 'M',
                    'nom' => $nom, 'prenom' => $prenom,
                    'date_naissance' => $_POST['prop_date_naissance'] ?: null,
                    'adresse' => trim($_POST['prop_adresse'] ?? '') ?: null,
                    'code_postal' => trim($_POST['prop_code_postal'] ?? '') ?: null,
                    'ville' => trim($_POST['prop_ville'] ?? '') ?: null,
                    'telephone' => trim($_POST['prop_telephone'] ?? '') ?: null,
                    'email' => $email,
                    'telephone_mobile' => trim($_POST['prop_telephone_mobile'] ?? '') ?: null,
                    'profession' => trim($_POST['prop_profession'] ?? '') ?: null,
                    'notes' => trim($_POST['prop_notes'] ?? '') ?: null,
                ]);
            }

            // RÉSIDENT SENIOR : créer fiche
            if ($role === 'locataire_permanent') {
                $residentModel = $this->model('ResidentSenior');
                $rsData = [
                    'user_id' => $userId, 'civilite' => $_POST['rs_civilite'],
                    'nom' => $nom, 'prenom' => $prenom,
                    'date_naissance' => $_POST['rs_date_naissance'],
                    'lieu_naissance' => trim($_POST['rs_lieu_naissance'] ?? '') ?: null,
                    'telephone_mobile' => trim($_POST['telephone'] ?? '') ?: null,
                    'email' => $email,
                    'numero_cni' => trim($_POST['rs_numero_cni'] ?? '') ?: null,
                    'date_delivrance_cni' => $_POST['rs_date_delivrance_cni'] ?: null,
                    'lieu_delivrance_cni' => trim($_POST['rs_lieu_delivrance_cni'] ?? '') ?: null,
                    'date_entree' => $_POST['rs_date_entree'] ?: null,
                    'niveau_autonomie' => $_POST['rs_niveau_autonomie'] ?: 'autonome',
                    'besoin_assistance' => (int)($_POST['rs_besoin_assistance'] ?? 0),
                    'situation_familiale' => $_POST['rs_situation_familiale'] ?: null,
                    'nombre_enfants' => (int)($_POST['rs_nombre_enfants'] ?? 0),
                    'num_securite_sociale' => trim($_POST['rs_num_securite_sociale'] ?? '') ?: null,
                    'urgence_nom' => trim($_POST['rs_urgence_nom'] ?? '') ?: null,
                    'urgence_lien' => trim($_POST['rs_urgence_lien'] ?? '') ?: null,
                    'urgence_telephone' => trim($_POST['rs_urgence_telephone'] ?? '') ?: null,
                    'urgence_telephone_2' => trim($_POST['rs_urgence_telephone_2'] ?? '') ?: null,
                    'urgence_email' => trim($_POST['rs_urgence_email'] ?? '') ?: null,
                    'medecin_traitant_nom' => trim($_POST['rs_medecin_traitant_nom'] ?? '') ?: null,
                    'medecin_traitant_tel' => trim($_POST['rs_medecin_traitant_tel'] ?? '') ?: null,
                    'regime_alimentaire' => $_POST['rs_regime_alimentaire'] ?? 'normal',
                    'allergies' => trim($_POST['rs_allergies'] ?? '') ?: null,
                    'mutuelle' => trim($_POST['rs_mutuelle'] ?? '') ?: null,
                    'num_mutuelle' => trim($_POST['rs_num_mutuelle'] ?? '') ?: null,
                    'animal_compagnie' => (int)($_POST['rs_animal_compagnie'] ?? 0),
                    'animal_type' => trim($_POST['rs_animal_type'] ?? '') ?: null,
                    'animal_nom' => trim($_POST['rs_animal_nom'] ?? '') ?: null,
                    'centres_interet' => trim($_POST['rs_centres_interet'] ?? '') ?: null,
                    'actif' => $actif,
                    'notes' => trim($_POST['rs_notes'] ?? '') ?: null,
                ];
                $residentId = $residentModel->create($rsData);
                if (!$residentId) throw new Exception("Erreur lors de la création de la fiche résident senior");

                $lotId = (int)($_POST['rs_lot_id'] ?? 0);
                if ($lotId > 0) {
                    $lotModel = $this->model('Lot');
                    if ($lotModel->isOccupied($lotId)) {
                        throw new Exception("Le lot sélectionné est déjà occupé par un résident permanent.");
                    }
                    $userModel->createOccupationForResident($residentId, $lotId, $_POST['rs_date_entree'] ?: date('Y-m-d'));
                }
            }

            // EXPLOITANT : créer fiche + lier résidences
            if ($role === 'exploitant') {
                $exploitantModel = $this->model('Exploitant');
                $siret = trim($_POST['exp_siret'] ?? '');
                $exploitantId = $exploitantModel->create([
                    'user_id' => $userId,
                    'raison_sociale' => trim($_POST['exp_raison_sociale']),
                    'siret' => $siret, 'siren' => substr($siret, 0, 9),
                    'forme_juridique' => $_POST['exp_forme_juridique'] ?: 'SAS',
                    'adresse_siege' => trim($_POST['exp_adresse'] ?? '') ?: '-',
                    'code_postal_siege' => trim($_POST['exp_code_postal'] ?? '') ?: '00000',
                    'ville_siege' => trim($_POST['exp_ville'] ?? '') ?: '-',
                    'telephone' => trim($_POST['exp_telephone'] ?? '') ?: null,
                    'email' => trim($_POST['exp_email'] ?? $email),
                ]);
                if (!$exploitantId) throw new Exception("Erreur lors de la création du profil exploitant");

                $expResidenceIds = array_values(array_unique(
                    array_filter(array_map('intval', $_POST['staff_residence_ids'] ?? []), fn($id) => $id > 0)
                ));
                $userModel->createExploitantResidenceLinks($userId, $exploitantId, $expResidenceIds);
            }

            $db->commit();

            Logger::logSensitiveAction('USER_CREATED', [
                'username' => $username, 'role' => $role, 'created_by' => $_SESSION['user_id']
            ]);

            $this->setFlash('success', "Utilisateur créé avec succès" .
                ($role === 'proprietaire' ? " (fiche propriétaire créée)" : "") .
                ($role === 'locataire_permanent' ? " (fiche résident senior liée)" : "") .
                ($role === 'exploitant' ? " (profil exploitant créé)" : "")
            );
            $this->redirect('admin/users');

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            Logger::logSensitiveAction('USER_CREATE_ERROR', [
                'error' => $e->getMessage(), 'user_id' => $_SESSION['user_id'], 'trace' => $e->getTraceAsString()
            ]);
            $this->setFlash('error', "Erreur lors de la création de l'utilisateur: " . $e->getMessage());
            $this->redirect('admin/users/create');
        }
    }

    private function editUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);

        $userModel = $this->model('User');
        $userData = $userModel->find($id);
        $user = $userData ? (array)$userData : null;

        if (!$user) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }

        $residentProfile = null;
        if ($user['role'] === 'locataire_permanent') {
            $hasResidentProfile = $userModel->hasResidentProfile($id);
            if ($hasResidentProfile) $residentProfile = ['id' => true];
        }

        $residenceModel = $this->model('Residence');
        $selectedResidenceIds = [];

        if (($user['role'] ?? null) === 'exploitant') {
            $exploitantModel = $this->model('Exploitant');
            $exploitant = $exploitantModel->findByUserId((int)$id);
            if ($exploitant && isset($exploitant->id)) {
                $selectedResidenceIds = $residenceModel->getResidenceIdsByExploitant((int)$exploitant->id);
            }
        }

        $this->view('admin/users/edit', [
            'title'                   => 'Modifier un utilisateur - ' . APP_NAME,
            'showNavbar'              => true,
            'user'                    => $user,
            'residentProfile'         => $residentProfile,
            'roles'                   => $userModel->getAllRoles(),
            'residencesForAssignment' => $residenceModel->getAllForAssignment(),
            'selectedResidenceIds'    => $selectedResidenceIds,
            'flash'                   => $this->getFlash()
        ], true);
    }

    private function updateUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->verifyCsrf();

        $userModel = $this->model('User');
        $existingUserData = $userModel->find($id);
        $existingUser = $existingUserData ? (array)$existingUserData : null;

        if (!$existingUser) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }

        $errors = [];
        $nom = trim($_POST['nom'] ?? '');
        $prenom = trim($_POST['prenom'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $password_confirm = $_POST['password_confirm'] ?? '';
        $role = $_POST['role'] ?? '';
        $actif = isset($_POST['actif']) ? 1 : 0;
        $residenceIds = array_values(array_unique(array_filter(array_map('intval', $_POST['residence_ids'] ?? []), fn($rid) => $rid > 0)));

        $oldRole = $existingUser['role'];

        if ($oldRole === 'admin') { $actif = 1; $role = 'admin'; }

        $rolesAvecFiche = ['proprietaire', 'locataire_permanent', 'exploitant'];

        if (!in_array($oldRole, $rolesAvecFiche) && in_array($role, $rolesAvecFiche)) {
            $this->setFlash('error', "Impossible d'assigner ce rôle ici. Créez un nouveau compte avec le rôle souhaité.", 24000);
            $this->redirect('admin/users/edit/' . $id);
            return;
        }

        if (in_array($oldRole, $rolesAvecFiche) && $role !== $oldRole) {
            if ($userModel->hasLinkedProfile($id, $oldRole)) {
                $roleLabels = ['proprietaire'=>'propriétaire','locataire_permanent'=>'résident senior','exploitant'=>'exploitant'];
                $label = $roleLabels[$oldRole] ?? $oldRole;
                $this->setFlash('error', "Impossible de changer le rôle d'un $label ayant une fiche liée. Créez un nouveau compte si nécessaire.", 24000);
                $this->redirect('admin/users/edit/' . $id);
                return;
            }
        }

        if (empty($nom)) $errors[] = "Le nom est requis";
        if (empty($prenom)) $errors[] = "Le prénom est requis";
        if (empty($email)) $errors[] = "L'email est requis";
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email invalide";
        if (empty($username)) $errors[] = "Le nom d'utilisateur est requis";
        if (strlen($username) < 3) $errors[] = "Le nom d'utilisateur doit contenir au moins 3 caractères";

        if (!empty($password)) {
            if (strlen($password) < 8) $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
            if ($password !== $password_confirm) $errors[] = "Les mots de passe ne correspondent pas";
        }

        $validRoles = array_column($userModel->getAllRoles(), 'slug');
        if (!in_array($role, $validRoles)) $errors[] = "Rôle invalide";

        if ($userModel->emailExists($email, $id)) $errors[] = "Cet email est déjà utilisé";
        if ($userModel->usernameExists($username, $id)) $errors[] = "Ce nom d'utilisateur est déjà utilisé";

        if (!empty($errors)) {
            foreach ($errors as $error) $this->setFlash('error', $error);
            $this->redirect('admin/users/edit/' . $id);
        }

        try {
            $db = $userModel->getDb();
            $db->beginTransaction();

            $updateData = ['nom' => $nom, 'prenom' => $prenom, 'email' => $email, 'username' => $username, 'role' => $role, 'actif' => $actif];
            if (!empty($password)) $updateData['password'] = $password;

            $success = $userModel->updateUser($id, $updateData, !empty($password));
            if (!$success) throw new Exception("Erreur lors de la mise à jour");

            if ($role === 'locataire_permanent') {
                $residentModel = $this->model('ResidentSenior');
                if (!$residentModel->syncActiveStatusByUserId((int)$id, !empty($actif))) {
                    throw new Exception("Erreur de synchronisation du statut résident");
                }
            }

            if ($role === 'exploitant') {
                $exploitantModel = $this->model('Exploitant');
                $residenceModel = $this->model('Residence');

                $exploitant = $exploitantModel->findByUserId((int)$id);
                $exploitantId = $exploitant->id ?? null;

                if (!$exploitantId) {
                    $generatedSiret = str_pad((string)$id, 14, '0', STR_PAD_LEFT);
                    $exploitantId = $exploitantModel->create([
                        'user_id' => (int)$id, 'raison_sociale' => trim($prenom . ' ' . $nom),
                        'siret' => $generatedSiret, 'siren' => substr($generatedSiret, 0, 9),
                        'forme_juridique' => 'SAS', 'adresse_siege' => '-',
                        'code_postal_siege' => '00000', 'ville_siege' => '-',
                        'telephone' => null, 'email' => $email
                    ]);
                }
                if (!$exploitantId) throw new Exception("Impossible de créer le profil exploitant lié");

                if (!$residenceModel->syncExploitantResidences((int)$exploitantId, $residenceIds)) {
                    throw new Exception("Impossible d'affecter les résidences à l'exploitant");
                }
            }

            $db->commit();

            Logger::logSensitiveAction('USER_UPDATED', ['username' => $username, 'user_id' => $id, 'updated_by' => $_SESSION['user_id']]);
            $this->setFlash('success', "Utilisateur mis à jour avec succès");
            $this->redirect('admin/users');

        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) $db->rollBack();
            Logger::logSensitiveAction('USER_UPDATE_ERROR', ['error' => $e->getMessage(), 'user_id' => $id, 'updated_by' => $_SESSION['user_id']]);
            $this->setFlash('error', "Erreur lors de la mise à jour de l'utilisateur");
            $this->redirect('admin/users/edit/' . $id);
        }
    }

    private function deleteUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->verifyCsrf();

        if ($id == $_SESSION['user_id']) {
            $this->setFlash('error', "Vous ne pouvez pas supprimer votre propre compte");
            $this->redirect('admin/users');
        }

        $userModel = $this->model('User');
        $userData = $userModel->find($id);
        $user = $userData ? (array)$userData : null;

        if (!$user) {
            $this->setFlash('error', "Utilisateur introuvable");
            $this->redirect('admin/users');
        }

        try {
            if (($user['role'] ?? '') === 'locataire_permanent') {
                $residentModel = $this->model('ResidentSenior');
                $success = $residentModel->syncActiveStatusByUserId((int)$id, false);
            } else {
                $success = $userModel->softDelete($id);
            }
            if (!$success) throw new Exception("Erreur lors de la désactivation");

            Logger::logSensitiveAction('USER_DELETED', ['username' => $user['username'], 'user_id' => $id, 'deleted_by' => $_SESSION['user_id']]);

            if (($user['role'] ?? '') === 'locataire_permanent') {
                $this->setFlash('success', "Résident désactivé avec succès (fiche résident + compte utilisateur synchronisés)");
            } else {
                $this->setFlash('success', "Utilisateur désactivé avec succès");
            }

        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_DELETE_ERROR', ['error' => $e->getMessage(), 'user_id' => $id, 'deleted_by' => $_SESSION['user_id']]);
            $this->setFlash('error', "Erreur lors de la suppression de l'utilisateur");
        }
        $this->redirect('admin/users');
    }

    private function toggleUser($id) {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->verifyCsrf();

        if ($id == $_SESSION['user_id']) {
            $this->setFlash('error', "Vous ne pouvez pas désactiver votre propre compte");
            $this->redirect('admin/users');
        }

        $userModel = $this->model('User');

        try {
            $userData = $userModel->find($id);
            $user = $userData ? (array)$userData : null;

            if (!$user) {
                $this->setFlash('error', "Utilisateur introuvable");
                $this->redirect('admin/users');
            }

            $newStatus = $user['actif'] ? 0 : 1;

            if (($user['role'] ?? '') === 'locataire_permanent') {
                $residentModel = $this->model('ResidentSenior');
                $success = $residentModel->syncActiveStatusByUserId((int)$id, (bool)$newStatus);
                if (!$success) throw new Exception("Erreur de synchronisation du statut résident");
            } else {
                $success = $userModel->toggleStatus($id);
            }
            if (!$success) throw new Exception("Erreur lors du changement de statut");

            $statusText = $newStatus ? 'activé' : 'désactivé';
            Logger::logSensitiveAction('USER_TOGGLE_ACTIVE', ['username' => $user['username'], 'user_id' => $id, 'new_status' => $statusText, 'toggled_by' => $_SESSION['user_id']]);

            if (($user['role'] ?? '') === 'locataire_permanent') {
                $this->setFlash('success', "Résident $statusText avec succès (fiche résident + compte utilisateur synchronisés)");
            } else {
                $this->setFlash('success', "Utilisateur $statusText avec succès");
            }

        } catch (Exception $e) {
            Logger::logSensitiveAction('USER_TOGGLE_ERROR', ['error' => $e->getMessage(), 'user_id' => $id, 'toggled_by' => $_SESSION['user_id']]);
            $this->setFlash('error', "Erreur lors du changement de statut");
        }
        $this->redirect('admin/users');
    }
}
