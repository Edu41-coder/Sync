<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Copropriétaires (Propriétaires)
 * ====================================================================
 */

class CoproprietaireController extends Controller {

    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'comptable']);

        $model = $this->model('Coproprietaire');
        $proprietaires = $model->getAllWithStats();

        $stats = [
            'total'          => count($proprietaires),
            'avec_contrat'   => count(array_filter($proprietaires, fn($p) => $p['nb_contrats_actifs'] > 0)),
            'revenus_total'  => array_sum(array_column($proprietaires, 'revenus_mensuels')),
        ];

        $this->view('coproprietaires/index', [
            'title'          => 'Propriétaires - ' . APP_NAME,
            'showNavbar'     => true,
            'proprietaires'  => $proprietaires,
            'stats'          => $stats,
            'flash'          => $this->getFlash()
        ], true);
    }

    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'comptable']);

        $model = $this->model('Coproprietaire');
        $proprietaire = $model->findWithUser($id);

        if (!$proprietaire) {
            $this->setFlash('error', 'Propriétaire introuvable');
            $this->redirect('coproprietaire/index');
            return;
        }

        $this->view('coproprietaires/show', [
            'title'        => $proprietaire['prenom'] . ' ' . $proprietaire['nom'] . ' - ' . APP_NAME,
            'showNavbar'   => true,
            'proprietaire' => $proprietaire,
            'contrats'     => $model->getContrats($id),
            'fiscalite'    => $model->getFiscalite($id),
            'lots'         => $model->getLotsDisponibles(),
            'exploitants'  => $model->getExploitantsActifs(),
            'flash'        => $this->getFlash()
        ], true);
    }

    /**
     * Mes lots (propriétaire connecté)
     */
    public function mesLots() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprioId = $model->getIdByUserId($_SESSION['user_id']);

        $this->view('coproprietaires/mes_lots', [
            'title'      => 'Mes Lots - ' . APP_NAME,
            'showNavbar' => true,
            'lots'       => $proprioId ? $model->getMesLots($proprioId) : [],
            'flash'      => $this->getFlash()
        ], true);
    }

    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin']);
        $this->redirect('admin/users/create?role=proprietaire');
    }

    /**
     * Calendrier propriétaire
     */
    public function calendrier() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprietaire = $model->findByUserId($_SESSION['user_id']);

        $this->view('coproprietaires/calendrier', [
            'title'        => 'Mon Calendrier - ' . APP_NAME,
            'showNavbar'   => true,
            'proprietaire' => $proprietaire,
            'categories'   => $model->getPlanningCategories(),
            'contrats'     => $proprietaire ? $model->getContratsActifs($proprietaire['id']) : [],
            'flash'        => $this->getFlash()
        ], true);
    }

    /**
     * AJAX calendrier propriétaire
     */
    public function calendarAjax($action = null) {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);
        header('Content-Type: application/json; charset=utf-8');

        $model = $this->model('Coproprietaire');
        $proprioId = $model->getIdByUserId($_SESSION['user_id']);

        if (!$proprioId) {
            echo json_encode(['success' => false, 'message' => 'Profil non trouvé']);
            exit;
        }

        try {
            switch ($action) {
                case 'getEvents':
                    $this->calGetEvents($model, $proprioId);
                    break;
                case 'save':
                    $this->calSaveEvent($model, $proprioId);
                    break;
                case 'move':
                    $this->calMoveEvent($model, $proprioId);
                    break;
                case 'delete':
                    $this->calDeleteEvent($model, $proprioId);
                    break;
                default:
                    echo json_encode(['success' => false, 'message' => 'Action inconnue']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    private function calGetEvents(Coproprietaire $model, int $proprioId) {
        $start = $_GET['start'] ?? date('Y-m-01');
        $end = $_GET['end'] ?? date('Y-m-t');

        $events = $model->getEvenements($proprioId, $start, $end);
        $contrats = $model->getContratsActifs($proprioId);

        $catLoyer = $model->getCategorieBySlug('loyer');
        $catFiscal = $model->getCategorieBySlug('fiscal');
        $catEcheance = $model->getCategorieBySlug('echeance');

        $startDate = new DateTime($start);
        $endDate = new DateTime($end);
        $autoId = 100000;

        // Événements auto-générés
        foreach ($contrats as $contrat) {
            // Loyers mensuels
            $current = clone $startDate;
            while ($current <= $endDate) {
                $day1 = new DateTime($current->format('Y-m-01'));
                $events[] = [
                    'id' => 'auto_loyer_' . ($autoId++),
                    'titre' => 'Loyer ' . number_format($contrat['loyer_mensuel_garanti'], 0, ',', ' ') . '€ — Lot ' . $contrat['numero_lot'],
                    'description' => $contrat['residence_nom'],
                    'date_debut' => $day1->format('Y-m-d 00:00:00'),
                    'date_fin' => $day1->format('Y-m-d 23:59:00'),
                    'journee_entiere' => 1,
                    'auto_genere' => 1,
                    'cat_slug' => 'loyer',
                    'couleur' => $catLoyer['couleur'] ?? '#198754',
                    'bg_couleur' => $catLoyer['bg_couleur'] ?? '#d1e7dd',
                    'category_id' => $catLoyer['id'] ?? null,
                ];
                $current->modify('+1 month');
            }

            // Échéance contrat
            if (!empty($contrat['date_fin'])) {
                $dateFin = new DateTime($contrat['date_fin']);
                if ($dateFin >= $startDate && $dateFin <= $endDate) {
                    $events[] = [
                        'id' => 'auto_ech_' . ($autoId++),
                        'titre' => 'Échéance contrat — Lot ' . $contrat['numero_lot'],
                        'description' => $contrat['residence_nom'],
                        'date_debut' => $dateFin->format('Y-m-d 00:00:00'),
                        'date_fin' => $dateFin->format('Y-m-d 23:59:00'),
                        'journee_entiere' => 1,
                        'auto_genere' => 1,
                        'cat_slug' => 'echeance',
                        'couleur' => $catEcheance['couleur'] ?? '#dc3545',
                        'bg_couleur' => $catEcheance['bg_couleur'] ?? '#f8d7da',
                        'category_id' => $catEcheance['id'] ?? null,
                    ];
                }
            }
        }

        // Rappels fiscaux (avril-mai)
        $current = clone $startDate;
        while ($current <= $endDate) {
            $y = (int)$current->format('Y');
            $m = (int)$current->format('m');
            if ($m == 4) {
                $events[] = [
                    'id' => 'auto_fisc_' . ($autoId++),
                    'titre' => 'Ouverture déclaration revenus ' . ($y - 1),
                    'description' => 'Préparer les formulaires 2042-C-PRO / 2031',
                    'date_debut' => "$y-04-10 00:00:00",
                    'date_fin' => "$y-04-10 23:59:00",
                    'journee_entiere' => 1,
                    'auto_genere' => 1,
                    'cat_slug' => 'fiscal',
                    'couleur' => $catFiscal['couleur'] ?? '#0d6efd',
                    'bg_couleur' => $catFiscal['bg_couleur'] ?? '#cfe2ff',
                    'category_id' => $catFiscal['id'] ?? null,
                ];
            }
            if ($m == 5) {
                $events[] = [
                    'id' => 'auto_fisc_' . ($autoId++),
                    'titre' => 'Date limite déclaration revenus ' . ($y - 1),
                    'description' => 'Dernière date pour la déclaration en ligne',
                    'date_debut' => "$y-05-25 00:00:00",
                    'date_fin' => "$y-05-25 23:59:00",
                    'journee_entiere' => 1,
                    'auto_genere' => 1,
                    'cat_slug' => 'fiscal',
                    'couleur' => $catFiscal['couleur'] ?? '#0d6efd',
                    'bg_couleur' => $catFiscal['bg_couleur'] ?? '#cfe2ff',
                    'category_id' => $catFiscal['id'] ?? null,
                ];
            }
            $current->modify('+1 month');
        }

        // Formater pour TUI Calendar
        $formatted = array_map(function($e) {
            $isAuto = !empty($e['auto_genere']);
            return [
                'id' => $e['id'],
                'calendarId' => $e['cat_slug'] ?? 'note',
                'title' => $e['titre'],
                'start' => $e['date_debut'],
                'end' => $e['date_fin'],
                'isAllDay' => (bool)($e['journee_entiere'] ?? false),
                'calendarColor' => $e['couleur'] ?? '#6c757d',
                'categoryColor' => $e['bg_couleur'] ?? '#e9ecef',
                'categoryTextColor' => '#333',
                'body' => $e['description'] ?? '',
                'isReadOnly' => $isAuto,
                'raw' => [
                    'autoGenere' => $isAuto,
                    'categoryId' => $e['category_id'] ?? null,
                    'catSlug' => $e['cat_slug'] ?? 'note',
                    'description' => $e['description'] ?? '',
                    'notes' => $e['notes'] ?? '',
                ],
            ];
        }, $events);

        echo json_encode($formatted);
    }

    private function calSaveEvent(Coproprietaire $model, int $proprioId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $titre = trim($input['title'] ?? '');
        $dateDebut = $input['start'] ?? '';
        $dateFin = $input['end'] ?? '';

        if (!$titre || !$dateDebut || !$dateFin) {
            echo json_encode(['success' => false, 'message' => 'Titre et dates requis']);
            return;
        }

        $id = $model->saveEvenement($proprioId, [
            'id'          => $input['id'] ?? null,
            'categoryId'  => (int)($input['categoryId'] ?? 0) ?: null,
            'title'       => $titre,
            'description' => trim($input['description'] ?? ''),
            'start'       => $dateDebut,
            'end'         => $dateFin,
            'isAllDay'    => $input['isAllDay'] ?? false,
        ]);

        echo json_encode(['success' => true, 'id' => $id]);
    }

    private function calMoveEvent(Coproprietaire $model, int $proprioId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? null;
        if (!$id || !is_numeric($id)) {
            echo json_encode(['success' => false, 'message' => 'Événement auto — non modifiable']);
            return;
        }
        $model->moveEvenement($proprioId, (int)$id, $input['start'], $input['end']);
        echo json_encode(['success' => true]);
    }

    private function calDeleteEvent(Coproprietaire $model, int $proprioId) {
        $input = json_decode(file_get_contents('php://input'), true);
        $id = (int)($input['id'] ?? 0);
        if (!$id) {
            echo json_encode(['success' => false, 'message' => 'Événement auto — non supprimable']);
            return;
        }
        $model->deleteEvenement($proprioId, $id);
        echo json_encode(['success' => true]);
    }

    /**
     * Mes résidences (propriétaire connecté)
     */
    public function mesResidences() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprioId = $model->getIdByUserId($_SESSION['user_id']);

        $this->view('coproprietaires/mes_residences', [
            'title'      => 'Mes Résidences - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $proprioId ? $model->getMesResidences($proprioId) : [],
            'flash'      => $this->getFlash()
        ], true);
    }

    /**
     * Comptabilité propriétaire
     */
    public function comptabilite() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprietaire = $model->findByUserId($_SESSION['user_id']);

        $contrats = [];
        $fiscalite = [];
        $totaux = ['revenus_mensuels' => 0, 'revenus_annuels' => 0, 'total_charges' => 0, 'resultat_fiscal' => 0];

        if ($proprietaire) {
            $contrats = $model->getContratsComptabilite($proprietaire['id']);

            $actifs = array_filter($contrats, fn($c) => $c['statut'] === 'actif');
            $totaux['revenus_mensuels'] = array_sum(array_column($actifs, 'loyer_mensuel_garanti'));
            $totaux['revenus_annuels'] = $totaux['revenus_mensuels'] * 12;

            $fiscalite = $model->getFiscalite($proprietaire['id']);

            if (!empty($fiscalite)) {
                $derniere = $fiscalite[0];
                $totaux['total_charges'] = ($derniere['charges_deductibles'] ?? 0) + ($derniere['interets_emprunt'] ?? 0)
                    + ($derniere['travaux_deductibles'] ?? 0) + ($derniere['assurances_deductibles'] ?? 0)
                    + ($derniere['taxe_fonciere_deductible'] ?? 0) + ($derniere['autres_charges_deductibles'] ?? 0);
                $totaux['resultat_fiscal'] = $derniere['resultat_fiscal'] ?? 0;
            }
        }

        $this->view('coproprietaires/comptabilite', [
            'title'        => 'Ma Comptabilité - ' . APP_NAME,
            'showNavbar'   => true,
            'proprietaire' => $proprietaire,
            'contrats'     => $contrats,
            'fiscalite'    => $fiscalite,
            'totaux'       => $totaux,
            'flash'        => $this->getFlash()
        ], true);
    }

    /**
     * Chat fiscal avec Claude AI (AJAX)
     */
    public function chat() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userMessage = trim($input['message'] ?? '');
            $history = $input['history'] ?? [];

            if (empty($userMessage)) {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            $model = $this->model('Coproprietaire');
            $proprio = $model->findByUserId($_SESSION['user_id']);
            $contexte = $proprio ? $model->buildFiscalContext($proprio) : 'Aucune donnée propriétaire.';

            $systemPrompt = "Tu es un expert-comptable fiscal français spécialisé LMNP/LMP et résidences seniors Domitys.
CONTEXTE : {$contexte}
RÈGLES : Réponds en français, clair et pédagogique. Explique les formulaires (2042, 2042-C-PRO, 2031). Guide selon le régime (micro-BIC/réel). Précise que ces infos sont indicatives.";

            $messages = [];
            foreach ($history as $msg) $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
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
     * Espace personnel du propriétaire connecté (lecture seule)
     */
    public function monEspace() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprietaire = $model->findByUserId($_SESSION['user_id']);

        if (!$proprietaire) {
            $this->setFlash('error', "Aucun profil propriétaire associé à votre compte.");
            $this->view('coproprietaires/espace', [
                'title'        => 'Mon Espace - ' . APP_NAME,
                'showNavbar'   => true,
                'proprietaire' => null,
                'contrats'     => [],
                'fiscalite'    => [],
                'lotsDetails'  => [],
                'flash'        => $this->getFlash()
            ], true);
            return;
        }

        $this->view('coproprietaires/espace', [
            'title'         => 'Mon Espace Propriétaire - ' . APP_NAME,
            'showNavbar'    => true,
            'proprietaire'  => $proprietaire,
            'contrats'      => $model->getContratsDetailles($proprietaire['id']),
            'fiscalite'     => $model->getFiscalite($proprietaire['id']),
            'mesResidences' => $model->getMesResidencesDetaillees($proprietaire['id']),
            'flash'         => $this->getFlash()
        ], true);
    }

    /**
     * Déclaration fiscale guidée avec IA
     */
    public function declarationFiscale() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        $model = $this->model('Coproprietaire');
        $proprietaire = $model->findByUserId($_SESSION['user_id']);

        $annee = (int)($_GET['annee'] ?? date('Y') - 1);
        $declaration = null;
        $documents = [];
        $contrats = [];

        if ($proprietaire) {
            $declaration = $model->getOrCreateDeclaration($proprietaire['id'], $annee);
            $documents = $model->getDeclarationDocuments($declaration['id']);
            $contrats = $model->getContratsFiscaux($proprietaire['id']);
        }

        $this->view('coproprietaires/declaration_fiscale', [
            'title'        => 'Déclaration Fiscale ' . $annee . ' - ' . APP_NAME,
            'showNavbar'   => true,
            'proprietaire' => $proprietaire,
            'declaration'  => $declaration,
            'documents'    => $documents,
            'contrats'     => $contrats,
            'annee'        => $annee,
            'flash'        => $this->getFlash()
        ], true);
    }

    /**
     * Upload d'un document fiscal (AJAX)
     */
    public function uploadDocument() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        header('Content-Type: application/json; charset=utf-8');

        try {
            if (empty($_FILES['document'])) {
                throw new Exception("Aucun fichier envoyé.");
            }

            $file = $_FILES['document'];
            $declarationId = (int)($_POST['declaration_id'] ?? 0);
            $typeDocument = $_POST['type_document'] ?? 'autre';

            if (!$declarationId) throw new Exception("Déclaration non trouvée.");

            // Validation fichier
            $allowedExts = ['pdf', 'jpg', 'jpeg', 'png', 'webp', 'xlsx', 'xls'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowedExts)) {
                throw new Exception("Type de fichier non autorisé. Acceptés : PDF, JPG, PNG, XLSX");
            }
            if ($file['size'] > 10 * 1024 * 1024) {
                throw new Exception("Fichier trop volumineux (max 10 Mo).");
            }

            $model = $this->model('Coproprietaire');
            $proprioId = $model->getIdByUserId($_SESSION['user_id']);

            $filename = 'decl_' . $declarationId . '_' . $typeDocument . '_' . time() . '.' . $ext;
            $uploadDir = '../public/uploads/declarations/';
            $filepath = $uploadDir . $filename;

            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception("Erreur lors de l'upload.");
            }

            $docId = $model->createDocument($declarationId, $proprioId, [
                'type_document' => $typeDocument,
                'nom_fichier'   => $file['name'],
                'chemin_fichier' => 'uploads/declarations/' . $filename,
                'type_mime'     => $file['type'],
                'taille'        => $file['size'],
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Document uploadé avec succès.',
                'document' => [
                    'id' => $docId,
                    'nom' => $file['name'],
                    'type' => $typeDocument,
                    'chemin' => BASE_URL . '/uploads/declarations/' . $filename,
                    'ext' => $ext
                ]
            ]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    /**
     * Chat fiscal avec analyse de documents (AJAX)
     */
    public function chatDeclaration() {
        $this->requireAuth();
        $this->requireRole(['proprietaire']);

        header('Content-Type: application/json; charset=utf-8');

        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $userMessage = trim($input['message'] ?? '');
            $history = $input['history'] ?? [];
            $documentIds = $input['documentIds'] ?? [];
            $declarationId = (int)($input['declarationId'] ?? 0);

            if (empty($userMessage)) {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            $model = $this->model('Coproprietaire');
            $proprio = $model->findByUserId($_SESSION['user_id']);

            $contexte = $proprio ? $model->buildFiscalContext($proprio) : 'Aucune donnée propriétaire.';

            // Fichiers à analyser
            $filePaths = [];
            if (!empty($documentIds)) {
                $docs = $model->getDocumentsByIds($documentIds, $proprio['id']);
                foreach ($docs as $doc) {
                    $fullPath = '../public/' . $doc['chemin_fichier'];
                    if (file_exists($fullPath)) $filePaths[] = $fullPath;
                }
            }

            $systemPrompt = $model->buildFiscalPrompt($declarationId, $contexte);

            // Appel API via ClaudeService
            $claude = new ClaudeService(2048, 60);
            if (!empty($filePaths)) {
                $result = $claude->chatWithFiles($systemPrompt, $history, $userMessage, $filePaths);
            } else {
                $messages = [];
                foreach ($history as $msg) $messages[] = ['role' => $msg['role'], 'content' => $msg['content']];
                $messages[] = ['role' => 'user', 'content' => $userMessage];
                $result = $claude->chat($systemPrompt, $messages);
            }

            if (!$result['success']) {
                throw new Exception($result['message']);
            }

            $assistantMessage = $result['message'];
            $extractedData = $result['extractedData'];

            // Stocker les montants extraits
            if (!empty($documentIds)) {
                foreach ($documentIds as $docId) {
                    if ($extractedData) {
                        $montant = $extractedData['montant_total'] ?? $extractedData['interets_annuels'] ?? $extractedData['prime_annuelle'] ?? null;
                        $model->updateDocumentAnalyse($docId, $assistantMessage, $montant, json_encode($extractedData));
                    } else {
                        $model->updateDocumentAnalyse($docId, $assistantMessage);
                    }
                }
                if ($extractedData && $declarationId) {
                    $model->updateDeclarationDonnees($declarationId, $extractedData['type'] ?? 'autre', $extractedData);
                }
            }

            echo json_encode(['success' => true, 'message' => $assistantMessage]);

        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Erreur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Créer un contrat de gestion pour un propriétaire
     */
    public function storeContrat($proprietaireId) {
        $this->requireAuth();
        $this->requireRole(['admin', 'comptable']);
        $this->verifyCsrf();

        try {
            $model = $this->model('Coproprietaire');
            $lotId = (int)($_POST['lot_id'] ?? 0);
            $exploitantId = (int)($_POST['exploitant_id'] ?? 0);

            if (!$lotId || !$exploitantId) {
                throw new Exception("Lot et exploitant sont requis.");
            }

            if ($model->lotHasContratActif($lotId)) {
                throw new Exception("Ce lot a déjà un contrat actif ou en projet. Résiliez d'abord le contrat existant.");
            }

            $otherOwner = $model->getAutreProprietaireLot($lotId, $proprietaireId);
            if ($otherOwner) {
                throw new Exception("Ce lot est déjà sous contrat avec " . $otherOwner['prenom'] . ' ' . $otherOwner['nom'] . ".");
            }

            $numero = $model->createContrat($proprietaireId, $_POST);

            $this->setFlash('success', "Contrat $numero créé avec succès");
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->redirect('coproprietaire/show/' . $proprietaireId);
    }

    /**
     * Ajouter une année fiscale pour un propriétaire
     */
    public function storeFiscalite($proprietaireId) {
        $this->requireAuth();
        $this->requireRole(['admin', 'comptable']);
        $this->verifyCsrf();

        try {
            $model = $this->model('Coproprietaire');
            $annee = (int)($_POST['annee_fiscale'] ?? date('Y') - 1);
            $lotId = (int)($_POST['lot_id'] ?? 0) ?: null;

            if ($model->fiscaliteExists($proprietaireId, $annee, $lotId)) {
                throw new Exception("Une fiche fiscale existe déjà pour l'année $annee" . ($lotId ? " (lot #$lotId)" : "") . ".");
            }

            $model->createFiscalite($proprietaireId, $_POST);

            $this->setFlash('success', "Données fiscales $annee ajoutées avec succès");
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }

        $this->redirect('coproprietaire/show/' . $proprietaireId);
    }
}
