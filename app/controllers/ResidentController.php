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
     * Voir un résident.
     *
     * RGPD art. 9 : la fiche expose des données de santé (allergies, GIR, médecin
     * traitant) et d'identité forte (CNI, n° SS). Le rôle `proprietaire` est volontairement
     * EXCLU — un propriétaire n'a pas de base légale pour accéder à ces données médicales.
     * S'il a besoin d'identifier le résident d'un lot qu'il possède, c'est via une vue
     * sommaire (nom + prénom uniquement, déjà présente dans l'espace propriétaire).
     */
    public function details($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'locataire_permanent']);

        // Utiliser les modèles
        $residentModel = new ResidentSenior();
        $occupationModel = new Occupation();

        $resident = $residentModel->findById($id);

        // Un résident ne peut consulter que SA PROPRE fiche (RGPD art. 9)
        if (($_SESSION['user_role'] ?? null) === 'locataire_permanent') {
            if (!$resident || (int)$resident->user_id !== (int)($_SESSION['user_id'] ?? 0)) {
                $this->setFlash('error', "Vous n'avez pas accès à cette fiche.");
                $this->redirect('resident/monEspace');
                return;
            }
        }
        
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
     * Alias public de details() — auth dupliquée pour defense in depth
     * (un refactor de details() ne doit pas pouvoir exposer cette route
     * vers les données santé/CNI/SS protégées par RGPD art. 9).
     * Voir details() pour la justification de l'exclusion du rôle `proprietaire`.
     */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'exploitant', 'locataire_permanent']);
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

    // ─────────────────────────────────────────────────────────────────
    //  ESPACE PERSONNEL DU RÉSIDENT (rôle locataire_permanent)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Récupère le profil résident lié à l'utilisateur connecté.
     * Redirige vers le tableau de bord si aucun profil trouvé.
     */
    private function currentResident(): ?array {
        $residentModel = new ResidentSenior();
        $resident = $residentModel->findByUserId($_SESSION['user_id'] ?? 0);
        return $resident ? (array)$resident : null;
    }

    /**
     * Tableau de bord personnel du résident
     * GET /resident/monEspace
     */
    public function monEspace() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) {
            $this->setFlash('error', "Aucun profil résident n'est associé à votre compte. Contactez l'administrateur.");
            $this->redirect('');
            return;
        }

        $occupationModel = new Occupation();
        $occupationsActives = $occupationModel->getActivesByResident($resident['id']);

        $totalLoyer = array_sum(array_map(fn($o) => (float)($o['loyer_mensuel_resident'] ?? 0), $occupationsActives));
        $residencesUniques = [];
        foreach ($occupationsActives as $o) {
            $residencesUniques[$o['residence_id']] = $o['residence_nom'];
        }

        $this->view('residents/mon_espace', [
            'title'              => 'Mon espace - ' . APP_NAME,
            'showNavbar'         => true,
            'resident'           => $resident,
            'occupationsActives' => $occupationsActives,
            'totalLoyer'         => $totalLoyer,
            'nbResidences'       => count($residencesUniques),
            'flash'              => $this->getFlash()
        ], true);
    }

    /**
     * Liste des lots du résident
     * GET /resident/mesLots
     */
    public function mesLots() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) { $this->redirect(''); return; }

        $occupationModel = new Occupation();
        $occupations = $occupationModel->getActivesByResident($resident['id']);

        $this->view('residents/mes_lots', [
            'title'       => 'Mes lots - ' . APP_NAME,
            'showNavbar'  => true,
            'resident'    => $resident,
            'occupations' => $occupations,
            'flash'       => $this->getFlash()
        ], true);
    }

    /**
     * Liste des occupations actives + historique
     * GET /resident/mesOccupations
     */
    public function mesOccupations() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) { $this->redirect(''); return; }

        $occupationModel = new Occupation();

        $this->view('residents/mes_occupations', [
            'title'              => 'Mes occupations - ' . APP_NAME,
            'showNavbar'         => true,
            'resident'           => $resident,
            'occupationsActives' => $occupationModel->getActivesByResident($resident['id']),
            'occupationsHistorique' => $occupationModel->getHistoryByResident($resident['id']),
            'flash'              => $this->getFlash()
        ], true);
    }

    /**
     * Vitrine des résidences Domitys (carte Leaflet + liste, lecture seule)
     * GET /resident/mesResidences
     */
    public function mesResidences() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) { $this->redirect(''); return; }

        $residenceModel = $this->model('Residence');
        $residences = $residenceModel->getAll();

        $occupationModel = new Occupation();
        $mesOccupations = $occupationModel->getActivesByResident($resident['id']);
        $mesResidencesIds = array_unique(array_column($mesOccupations, 'residence_id'));

        $this->view('residents/mes_residences', [
            'title'            => 'Résidences Domitys - ' . APP_NAME,
            'showNavbar'       => true,
            'resident'         => $resident,
            'residences'       => $residences,
            'mesResidencesIds' => $mesResidencesIds,
            'flash'            => $this->getFlash()
        ], true);
    }

    /**
     * Profil utilisateur résident (lecture seule + changement mot de passe)
     * GET /resident/profile
     */
    public function profile() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $userModel = $this->model('User');
        $user = $userModel->find($this->getUserId());
        if (!$user) { $this->redirect(''); return; }

        $resident = $this->currentResident();

        $this->view('residents/profile', [
            'title'      => 'Mon profil - ' . APP_NAME,
            'showNavbar' => true,
            'user'       => $user,
            'resident'   => $resident,
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Changement de mot de passe — endpoint résident
     * POST /resident/changePassword
     */
    public function changePassword() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('resident/profile');
            return;
        }
        $this->verifyCsrf();

        $userModel = $this->model('User');
        $user = $userModel->find($this->getUserId());

        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if ($current === '' || $new === '' || $confirm === '') {
            $this->setFlash('error', 'Tous les champs sont obligatoires.');
            $this->redirect('resident/profile'); return;
        }
        if (!password_verify($current, $user->password_hash)) {
            $this->setFlash('error', 'Le mot de passe actuel est incorrect.');
            $this->redirect('resident/profile'); return;
        }
        if ($new !== $confirm) {
            $this->setFlash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            $this->redirect('resident/profile'); return;
        }
        if (strlen($new) < 6) {
            $this->setFlash('error', 'Le mot de passe doit contenir au moins 6 caractères.');
            $this->redirect('resident/profile'); return;
        }

        if ($userModel->updatePassword($this->getUserId(), $new)) {
            // Synchronise password_plain pour conserver l'aide-mémoire affiché au résident
            // (cohérent avec la convention « password_plain visible admin » du projet).
            try {
                $userModel->getDb()->prepare("UPDATE users SET password_plain = ? WHERE id = ?")
                    ->execute([$new, $this->getUserId()]);
            } catch (Exception $e) { /* non bloquant */ }
            $this->setFlash('success', 'Mot de passe modifié avec succès.');
        } else {
            $this->setFlash('error', 'Erreur lors de la modification du mot de passe.');
        }
        $this->redirect('resident/profile');
    }

    // ─────────────────────────────────────────────────────────────────
    //  CALENDRIER RÉSIDENT
    // ─────────────────────────────────────────────────────────────────

    /**
     * Vue calendrier
     * GET /resident/calendrier
     */
    public function calendrier() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) { $this->redirect(''); return; }

        $calModel = $this->model('ResidentCalendar');

        $this->view('residents/calendrier', [
            'title'      => 'Mon calendrier - ' . APP_NAME,
            'showNavbar' => true,
            'resident'   => $resident,
            'categories' => $calModel->getPlanningCategories(),
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Endpoint AJAX du calendrier résident
     * POST/GET /resident/calendarAjax/{action}
     */
    public function calendarAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        header('Content-Type: application/json; charset=utf-8');

        $resident = $this->currentResident();
        if (!$resident) {
            echo json_encode(['success' => false, 'message' => 'Profil résident introuvable']);
            exit;
        }
        $residentId = (int)$resident['id'];
        $calModel = $this->model('ResidentCalendar');

        try {
            switch ($action) {
                case 'getEvents':
                    $this->calGetEvents($calModel, $residentId);
                    break;
                case 'save':
                    $this->verifyCsrfHeader();
                    $this->calSaveEvent($calModel, $residentId);
                    break;
                case 'move':
                    $this->verifyCsrfHeader();
                    $this->calMoveEvent($calModel, $residentId);
                    break;
                case 'delete':
                    $this->verifyCsrfHeader();
                    $this->calDeleteEvent($calModel, $residentId);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function calGetEvents(ResidentCalendar $model, int $residentId) {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end   = $_GET['end']   ?? date('Y-m-t');

        // Manuels
        $events = $model->getEvenements($residentId, $start, $end);

        // Auto-gen : occupations actives du résident
        $occupationModel = $this->model('Occupation');
        $occupations = $occupationModel->getActivesByResident($residentId);

        $catLoyer  = $model->getCategorieBySlug('loyer');
        $catFiscal = $model->getCategorieBySlug('fiscal');
        $catAnim   = $model->getCategorieBySlug('animation');

        $startDate = new DateTime($start);
        $endDate   = new DateTime($end);
        $autoId    = 100000;

        // 1. Loyers mensuels (jour de prélèvement = jour_prelevement de l'occupation, défaut 5)
        foreach ($occupations as $occ) {
            $jour = max(1, min(28, (int)($occ['jour_prelevement'] ?? 5)));
            $current = clone $startDate;
            while ($current <= $endDate) {
                $dateLoyer = new DateTime($current->format('Y-m-') . str_pad((string)$jour, 2, '0', STR_PAD_LEFT));
                if ($dateLoyer >= $startDate && $dateLoyer <= $endDate) {
                    $events[] = [
                        'id'              => 'auto_loyer_' . ($autoId++),
                        'titre'           => 'Loyer ' . number_format((float)$occ['loyer_mensuel_resident'], 0, ',', ' ') . '€ — Lot ' . $occ['numero_lot'],
                        'description'     => $occ['residence_nom'],
                        'date_debut'      => $dateLoyer->format('Y-m-d 00:00:00'),
                        'date_fin'        => $dateLoyer->format('Y-m-d 23:59:00'),
                        'journee_entiere' => 1,
                        'auto_genere'     => 1,
                        'cat_slug'        => 'loyer',
                        'couleur'         => $catLoyer['couleur']    ?? '#198754',
                        'bg_couleur'      => $catLoyer['bg_couleur'] ?? '#d1e7dd',
                        'category_id'     => $catLoyer['id']         ?? null,
                    ];
                }
                $current->modify('first day of next month');
            }
        }

        // 2. Animations des résidences où le résident a une occupation active
        $animations = $model->getAnimationsResidences($residentId, $start, $end);
        foreach ($animations as $anim) {
            $events[] = [
                'id'              => 'auto_anim_' . (int)$anim['id'],
                'titre'           => '🎵 ' . $anim['titre'] . ' (' . $anim['residence_nom'] . ')',
                'description'     => $anim['description'] ?? '',
                'date_debut'      => $anim['date_debut'],
                'date_fin'        => $anim['date_fin'],
                'journee_entiere' => (int)$anim['journee_entiere'],
                'auto_genere'     => 1,
                'cat_slug'        => 'animation',
                'couleur'         => $catAnim['couleur']    ?? '#0dcaf0',
                'bg_couleur'      => $catAnim['bg_couleur'] ?? '#cff4fc',
                'category_id'     => $catAnim['id']         ?? null,
            ];
        }

        // 3. Rappels fiscaux (avril ouverture / mai date limite)
        $current = clone $startDate;
        while ($current <= $endDate) {
            $y = (int)$current->format('Y');
            $m = (int)$current->format('m');
            if ($m === 4) {
                $events[] = [
                    'id'              => 'auto_fisc_avr_' . $y,
                    'titre'           => 'Ouverture déclaration revenus ' . ($y - 1),
                    'description'     => 'Pensez à préparer vos justificatifs (pension, aides APL/ASH, services à la personne).',
                    'date_debut'      => "$y-04-10 00:00:00",
                    'date_fin'        => "$y-04-10 23:59:00",
                    'journee_entiere' => 1,
                    'auto_genere'     => 1,
                    'cat_slug'        => 'fiscal',
                    'couleur'         => $catFiscal['couleur']    ?? '#0d6efd',
                    'bg_couleur'      => $catFiscal['bg_couleur'] ?? '#cfe2ff',
                    'category_id'     => $catFiscal['id']         ?? null,
                ];
            }
            if ($m === 5) {
                $events[] = [
                    'id'              => 'auto_fisc_mai_' . $y,
                    'titre'           => 'Date limite déclaration revenus ' . ($y - 1),
                    'description'     => 'Dernière date pour la déclaration en ligne.',
                    'date_debut'      => "$y-05-25 00:00:00",
                    'date_fin'        => "$y-05-25 23:59:00",
                    'journee_entiere' => 1,
                    'auto_genere'     => 1,
                    'cat_slug'        => 'fiscal',
                    'couleur'         => $catFiscal['couleur']    ?? '#0d6efd',
                    'bg_couleur'      => $catFiscal['bg_couleur'] ?? '#cfe2ff',
                    'category_id'     => $catFiscal['id']         ?? null,
                ];
            }
            $current->modify('first day of next month');
        }

        // Format TUI Calendar
        $formatted = array_map(function($e) {
            $isAuto = !empty($e['auto_genere']);
            return [
                'id'                => $e['id'],
                'calendarId'        => $e['cat_slug'] ?? 'autre',
                'title'             => $e['titre'],
                'start'             => $e['date_debut'],
                'end'               => $e['date_fin'],
                'isAllDay'          => (bool)($e['journee_entiere'] ?? false),
                'calendarColor'     => $e['couleur']    ?? '#6c757d',
                'categoryColor'     => $e['bg_couleur'] ?? '#e9ecef',
                'categoryTextColor' => '#333',
                'body'              => $e['description'] ?? '',
                'isReadOnly'        => $isAuto,
                'raw'               => [
                    'autoGenere'  => $isAuto,
                    'categoryId'  => $e['category_id'] ?? null,
                    'catSlug'     => $e['cat_slug']    ?? 'autre',
                    'description' => $e['description'] ?? '',
                ],
            ];
        }, $events);

        echo json_encode($formatted);
    }

    private function calSaveEvent(ResidentCalendar $model, int $residentId) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $titre = trim($input['title'] ?? '');
        $start = $input['start'] ?? '';
        $end   = $input['end']   ?? '';

        if (!$titre || !$start || !$end) {
            echo json_encode(['success' => false, 'message' => 'Titre et dates requis']);
            return;
        }

        $id = $model->saveEvenement($residentId, [
            'id'          => $input['id'] ?? null,
            'categoryId'  => (int)($input['categoryId'] ?? 0) ?: null,
            'title'       => $titre,
            'description' => trim($input['description'] ?? ''),
            'start'       => $start,
            'end'         => $end,
            'isAllDay'    => $input['isAllDay'] ?? false,
        ]);

        echo json_encode(['success' => true, 'id' => $id]);
    }

    private function calMoveEvent(ResidentCalendar $model, int $residentId) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = $input['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Événement auto — non modifiable']);
            return;
        }
        $model->moveEvenement($residentId, (int)$id, $input['start'], $input['end']);
        echo json_encode(['success' => true]);
    }

    private function calDeleteEvent(ResidentCalendar $model, int $residentId) {
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Événement auto — non supprimable']);
            return;
        }
        $model->deleteEvenement($residentId, $id);
        echo json_encode(['success' => true]);
    }

    // ─────────────────────────────────────────────────────────────────
    //  COMPTABILITÉ RÉSIDENT + IA
    // ─────────────────────────────────────────────────────────────────

    /**
     * Calcul du budget mensuel à partir des occupations actives.
     */
    private function calculerBudget(int $residentId): array {
        $occupationModel = new Occupation();
        $occupations = $occupationModel->getActivesByResident($residentId);

        $totalLoyer    = 0.0;
        $totalCharges  = 0.0;
        $totalServices = 0.0;
        $totalApl      = 0.0;
        $totalApa      = 0.0;

        foreach ($occupations as $o) {
            $totalLoyer    += (float)($o['loyer_mensuel_resident']     ?? 0);
            $totalCharges  += (float)($o['charges_mensuelles_resident'] ?? 0);
            $totalServices += (float)($o['montant_services_sup']        ?? 0);
            if (!empty($o['beneficie_apl'])) $totalApl += (float)($o['montant_apl'] ?? 0);
            if (!empty($o['beneficie_apa'])) $totalApa += (float)($o['montant_apa'] ?? 0);
        }

        $totalDepenses   = $totalLoyer + $totalCharges + $totalServices;
        $totalAides      = $totalApl + $totalApa;
        $resteACharge    = $totalDepenses - $totalAides;

        return [
            'occupations'        => $occupations,
            'total_loyer'        => $totalLoyer,
            'total_charges'      => $totalCharges,
            'total_services'     => $totalServices,
            'total_depenses'     => $totalDepenses,
            'total_apl'          => $totalApl,
            'total_apa'          => $totalApa,
            'total_aides'        => $totalAides,
            'reste_a_charge'     => $resteACharge,
            'depenses_annuelles' => $totalDepenses * 12,
            'aides_annuelles'    => $totalAides * 12,
            'reste_annuel'       => $resteACharge * 12,
        ];
    }

    /**
     * Construit un contexte texte décrivant la situation du résident,
     * passé en system prompt à Claude.
     */
    private function buildResidentContext(array $resident, array $budget): string {
        $lignes = [];
        $lignes[] = "Résident : " . trim(($resident['civilite'] ?? '') . ' ' . ($resident['prenom'] ?? '') . ' ' . ($resident['nom'] ?? ''));
        if (!empty($resident['date_naissance'])) {
            $age = (int)((time() - strtotime($resident['date_naissance'])) / (365.25 * 86400));
            $lignes[] = "Âge : ~{$age} ans";
        }
        if (!empty($resident['niveau_autonomie'])) $lignes[] = "Niveau d'autonomie : " . $resident['niveau_autonomie'];
        if (!empty($resident['situation_familiale'])) $lignes[] = "Situation familiale : " . $resident['situation_familiale'];

        $lignes[] = "";
        $lignes[] = "BUDGET MENSUEL (calculé à partir des occupations actives) :";
        $lignes[] = "  - Loyer : " . number_format($budget['total_loyer'], 2, ',', ' ') . " €";
        $lignes[] = "  - Charges : " . number_format($budget['total_charges'], 2, ',', ' ') . " €";
        $lignes[] = "  - Services supplémentaires : " . number_format($budget['total_services'], 2, ',', ' ') . " €";
        $lignes[] = "  - Total dépenses : " . number_format($budget['total_depenses'], 2, ',', ' ') . " € / mois";
        $lignes[] = "  - APL perçue : " . number_format($budget['total_apl'], 2, ',', ' ') . " €";
        $lignes[] = "  - APA perçue : " . number_format($budget['total_apa'], 2, ',', ' ') . " €";
        $lignes[] = "  - Reste à charge : " . number_format($budget['reste_a_charge'], 2, ',', ' ') . " € / mois";
        $lignes[] = "  - Reste à charge annuel : " . number_format($budget['reste_annuel'], 2, ',', ' ') . " €";

        if (!empty($budget['occupations'])) {
            $lignes[] = "";
            $lignes[] = "OCCUPATIONS ACTIVES :";
            foreach ($budget['occupations'] as $o) {
                $lignes[] = "  - " . $o['residence_nom'] . " — Lot " . $o['numero_lot']
                          . " (" . $o['lot_type'] . "), loyer "
                          . number_format((float)$o['loyer_mensuel_resident'], 0, ',', ' ') . " €/mois";
            }
        }

        return implode("\n", $lignes);
    }

    /**
     * Tableau de bord comptabilité résident (budget mensuel)
     * GET /resident/comptabilite
     */
    public function comptabilite() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) {
            $this->setFlash('error', "Aucun profil résident associé à votre compte.");
            $this->redirect('');
            return;
        }

        $budget = $this->calculerBudget((int)$resident['id']);

        $this->view('residents/comptabilite', [
            'title'      => 'Ma comptabilité - ' . APP_NAME,
            'showNavbar' => true,
            'resident'   => $resident,
            'budget'     => $budget,
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Chat IA budget/fiscal (AJAX, JSON)
     * POST /resident/chat
     */
    public function chat() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrfHeader();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $userMessage = trim($input['message'] ?? '');
            $history     = $input['history'] ?? [];
            $mode        = $input['mode'] ?? 'budget'; // 'budget' ou 'fiscal'

            if ($userMessage === '') {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            $resident = $this->currentResident();
            if (!$resident) {
                echo json_encode(['success' => false, 'message' => 'Profil résident introuvable']);
                exit;
            }

            $budget   = $this->calculerBudget((int)$resident['id']);
            $contexte = $this->buildResidentContext($resident, $budget);

            $systemPrompt = $mode === 'fiscal'
                ? "Tu es un conseiller fiscal français spécialisé dans la fiscalité des résidents seniors en résidences services (Domitys).\n\n"
                  . "CONTEXTE DU RÉSIDENT :\n{$contexte}\n\n"
                  . "RÈGLES :\n"
                  . "- Réponds en français, ton bienveillant et pédagogique adapté à un public senior.\n"
                  . "- Couvre : crédit d'impôt services à la personne (art. 199 sexdecies CGI, 50% plafonné), "
                  . "réduction d'impôt hébergement en EHPAD/résidence services médicalisée (art. 199 quindecies, 25% plafond 10 000€), "
                  . "déclaration des aides (APL non imposable, ASH non imposable, APA non imposable), "
                  . "déclaration de la pension de retraite (case 1AS/1BS du formulaire 2042).\n"
                  . "- Précise toujours que ces informations sont indicatives et qu'un expert-comptable peut être consulté.\n"
                  . "- Si l'utilisateur joint un avis d'imposition ou un justificatif, analyse-le et extrais les montants pertinents."
                : "Tu es un conseiller bienveillant qui aide un résident senior à comprendre et optimiser son budget mensuel.\n\n"
                  . "CONTEXTE DU RÉSIDENT :\n{$contexte}\n\n"
                  . "RÈGLES :\n"
                  . "- Réponds en français, ton chaleureux et clair adapté à un public senior.\n"
                  . "- Aide à comprendre les postes de dépense (loyer, charges, services), les aides perçues (APL, APA), "
                  . "le reste à charge.\n"
                  . "- Suggère des pistes d'économies si pertinent (services optionnels, démarches d'aides supplémentaires comme l'ASH).\n"
                  . "- Peut expliquer comment demander l'APL/APA si non perçue, l'ASH (aide sociale à l'hébergement).\n"
                  . "- Précise que tu es un assistant et que pour les démarches officielles, l'équipe Domitys ou un travailleur social peut accompagner.";

            $messages = [];
            foreach ($history as $msg) {
                $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $claude = new ClaudeService(1024, 30);
            $result = $claude->chat($systemPrompt, $messages);

            echo json_encode(['success' => $result['success'], 'message' => $result['message']]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Vue déclaration fiscale guidée
     * GET /resident/declarationFiscale
     */
    public function declarationFiscale() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);

        $resident = $this->currentResident();
        if (!$resident) {
            $this->setFlash('error', "Aucun profil résident associé à votre compte.");
            $this->redirect('');
            return;
        }

        $budget = $this->calculerBudget((int)$resident['id']);
        $annee = (int)($_GET['annee'] ?? (date('Y') - 1));

        $this->view('residents/declaration_fiscale', [
            'title'      => 'Déclaration fiscale ' . $annee . ' - ' . APP_NAME,
            'showNavbar' => true,
            'resident'   => $resident,
            'budget'     => $budget,
            'annee'      => $annee,
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Chat IA déclaration fiscale avec analyse de fichiers (PDF/images)
     * POST /resident/chatDeclaration
     */
    public function chatDeclaration() {
        $this->requireAuth();
        $this->requireRole(['locataire_permanent']);
        $this->verifyCsrfHeader();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true) ?: [];
            $userMessage = trim($input['message'] ?? '');
            $history     = $input['history'] ?? [];
            $fichierIds  = $input['fichierIds'] ?? []; // IDs dans la GED résident

            if ($userMessage === '') {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            $resident = $this->currentResident();
            if (!$resident) {
                echo json_encode(['success' => false, 'message' => 'Profil résident introuvable']);
                exit;
            }

            $budget   = $this->calculerBudget((int)$resident['id']);
            $contexte = $this->buildResidentContext($resident, $budget);

            // Récupérer les chemins physiques des fichiers GED sélectionnés
            $filePaths = [];
            if (!empty($fichierIds)) {
                $docModel = $this->model('ResidentDocument');
                foreach ($fichierIds as $fid) {
                    $f = $docModel->findFichier((int)$resident['id'], (int)$fid);
                    if (!$f) continue;
                    $full = '../uploads/residents/' . ltrim($f['chemin_stockage'], '/');
                    if (file_exists($full)) $filePaths[] = $full;
                }
            }

            $systemPrompt = "Tu es un conseiller fiscal français spécialisé dans la fiscalité des résidents seniors en résidences services (Domitys).\n\n"
                . "CONTEXTE DU RÉSIDENT :\n{$contexte}\n\n"
                . "RÈGLES :\n"
                . "- Réponds en français, ton bienveillant adapté à un public senior.\n"
                . "- Tu aides à préparer la déclaration de revenus (formulaire 2042) :\n"
                . "  * Pension de retraite : case 1AS/1BS\n"
                . "  * Crédit d'impôt services à la personne (art. 199 sexdecies) : 50% plafond 12 000€/an, case 7DB\n"
                . "  * Réduction d'impôt hébergement résidence services médicalisée (art. 199 quindecies) : 25% plafond 10 000€/an, case 7CD\n"
                . "  * APL / APA / ASH : non imposables — ne pas déclarer\n"
                . "- Si des documents sont joints (avis d'imposition, justificatifs aides, factures services), analyse-les et extrais les montants pertinents.\n"
                . "- Précise toujours que ces conseils sont indicatifs.\n"
                . "- Mentionne quand un travailleur social ou un expert-comptable peut accompagner.";

            $claude = new ClaudeService(2048, 60);
            if (!empty($filePaths)) {
                $result = $claude->chatWithFiles($systemPrompt, $history, $userMessage, $filePaths);
            } else {
                $messages = [];
                foreach ($history as $msg) $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                $messages[] = ['role' => 'user', 'content' => $userMessage];
                $result = $claude->chat($systemPrompt, $messages);
            }

            echo json_encode([
                'success'       => $result['success'],
                'message'       => $result['message'],
                'extractedData' => $result['extractedData'] ?? null
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }
}
