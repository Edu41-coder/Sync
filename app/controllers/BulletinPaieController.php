<?php
/**
 * ====================================================================
 * SYND_GEST — Contrôleur Bulletins de Paie (Phase 4)
 * ====================================================================
 *
 * ⚠️ PILOTE VITRINE : voir docblock du Model BulletinPaie.
 *
 * Permissions :
 *   - admin / comptable / directeur_residence : CRUD bulletins
 *   - Salarié lui-même : lecture de SES bulletins (statut valide ou emis)
 */

class BulletinPaieController extends Controller {

    private const ROLES_ADMIN = ['admin', 'comptable', 'directeur_residence'];

    // =================================================================
    //  ADMIN — Liste / création / fiche / workflow
    // =================================================================

    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $annee  = !empty($_GET['annee']) ? (int)$_GET['annee'] : null;
        $mois   = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $statut = !empty($_GET['statut']) ? $_GET['statut'] : null;

        $bulletins = $this->model('BulletinPaie')->listAll($annee, $mois, $statut);

        $this->view('bulletins/index', [
            'title'      => 'Bulletins de paie - ' . APP_NAME,
            'showNavbar' => true,
            'bulletins'  => $bulletins,
            'annee'      => $annee,
            'mois'       => $mois,
            'statut'     => $statut,
            'statuts'    => BulletinPaie::STATUTS,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * GET /bulletinPaie/create — Formulaire création (sélection user + période + heures).
     */
    public function create() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        // Liste des salariés ayant une fiche RH active
        $staff = $this->model('SalarieRh')->listAllStaff(null, true, true);

        // Pré-remplissage si paramètres URL
        $userId = !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null;
        $annee  = !empty($_GET['annee']) ? (int)$_GET['annee'] : (int)date('Y');
        $mois   = !empty($_GET['mois']) ? (int)$_GET['mois'] : (int)date('n');
        $heures = ['heures_normales' => BulletinPaie::HEURES_MENSUELLES, 'heures_sup_25' => 0, 'heures_sup_50' => 0];

        // Si user choisi → import auto heures planning
        if ($userId) {
            $heures = $this->model('BulletinPaie')->importHeuresPlanning($userId, $annee, $mois);
        }

        $this->view('bulletins/create', [
            'title'      => 'Nouveau bulletin de paie - ' . APP_NAME,
            'showNavbar' => true,
            'staff'      => $staff,
            'preUserId'  => $userId,
            'preAnnee'   => $annee,
            'preMois'    => $mois,
            'preHeures'  => $heures,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /bulletinPaie/store — Création effective.
     */
    public function store() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();

        $userId = (int)($_POST['user_id'] ?? 0);
        $annee  = (int)($_POST['annee'] ?? 0);
        $mois   = (int)($_POST['mois'] ?? 0);
        if (!$userId || !$annee || !$mois) {
            $this->setFlash('error', "Salarié, année et mois obligatoires.");
            $this->redirect('bulletinPaie/create');
            return;
        }

        $heures = [
            'heures_normales'         => (float)($_POST['heures_normales'] ?? BulletinPaie::HEURES_MENSUELLES),
            'heures_sup_25'           => (float)($_POST['heures_sup_25'] ?? 0),
            'heures_sup_50'           => (float)($_POST['heures_sup_50'] ?? 0),
            'heures_repos_compensateur' => (float)($_POST['heures_repos_compensateur'] ?? 0),
            'mode_heures_sup'         => $_POST['mode_heures_sup'] ?? 'paiement',
        ];
        $extras = [
            'primes'     => (float)($_POST['primes'] ?? 0),
            'indemnites' => (float)($_POST['indemnites'] ?? 0),
            'taux_pas'   => (float)($_POST['taux_pas'] ?? 0),
        ];

        try {
            $id = $this->model('BulletinPaie')->create($userId, $annee, $mois, $heures, $extras, (int)$_SESSION['user_id']);
            $this->setFlash('success', "Bulletin créé (statut brouillon). Vérifiez puis validez.");
            $this->redirect('bulletinPaie/show/' . $id);
        } catch (Throwable $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('bulletinPaie/create?user_id=' . $userId . '&annee=' . $annee . '&mois=' . $mois);
        }
    }

    /**
     * GET /bulletinPaie/show/{id} — Détail bulletin (vue admin).
     */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $bulletin = $this->model('BulletinPaie')->findById((int)$id);
        if (!$bulletin) {
            $this->setFlash('error', 'Bulletin introuvable');
            $this->redirect('bulletinPaie/index');
            return;
        }

        $this->view('bulletins/show', [
            'title'      => 'Bulletin ' . BulletinPaie::MOIS_LABELS[$bulletin['periode_mois']] . ' ' . $bulletin['periode_annee'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'bulletin'   => $bulletin,
            'statuts'    => BulletinPaie::STATUTS,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
            'isPrintable' => false,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * GET /bulletinPaie/print/{id} — Vue imprimable (admin ou propriétaire du bulletin).
     */
    public function printable($id) {
        $this->requireAuth();
        $bulletin = $this->model('BulletinPaie')->findById((int)$id);
        if (!$bulletin) {
            $this->setFlash('error', 'Bulletin introuvable');
            $this->redirect('bulletinPaie/index');
            return;
        }

        $userId = (int)$_SESSION['user_id'];
        $isAdmin = in_array($_SESSION['user_role'] ?? '', self::ROLES_ADMIN, true);
        $isOwner = (int)$bulletin['user_id'] === $userId;
        if (!$isAdmin && !$isOwner) {
            $this->setFlash('error', 'Accès refusé.');
            $this->redirect('home');
            return;
        }

        // Layout désactivé pour la vue imprimable (header/footer minimal)
        $this->view('bulletins/printable', [
            'title'      => 'Bulletin paie ' . $bulletin['snapshot_prenom'] . ' ' . $bulletin['snapshot_nom'],
            'bulletin'   => $bulletin,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
        ], false);
    }

    /**
     * POST /bulletinPaie/valider/{id}.
     */
    public function valider($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();
        if ($this->model('BulletinPaie')->valider((int)$id, (int)$_SESSION['user_id'])) {
            $this->setFlash('success', 'Bulletin validé.');
        } else {
            $this->setFlash('error', "Validation impossible (statut différent de 'brouillon' ?).");
        }
        $this->redirect('bulletinPaie/show/' . $id);
    }

    /**
     * POST /bulletinPaie/emettre/{id}.
     */
    public function emettre($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();
        if ($this->model('BulletinPaie')->emettre((int)$id)) {
            $this->setFlash('success', 'Bulletin émis. Le salarié peut maintenant le consulter.');
        } else {
            $this->setFlash('error', "Émission impossible (le bulletin doit être validé d'abord).");
        }
        $this->redirect('bulletinPaie/show/' . $id);
    }

    /**
     * POST /bulletinPaie/annuler/{id}.
     */
    public function annuler($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();
        $motif = trim($_POST['motif'] ?? 'Annulation manuelle');
        if ($this->model('BulletinPaie')->annuler((int)$id, $motif)) {
            $this->setFlash('warning', 'Bulletin annulé.');
        } else {
            $this->setFlash('error', "Annulation impossible.");
        }
        $this->redirect('bulletinPaie/show/' . $id);
    }

    /**
     * POST /bulletinPaie/delete/{id} — supprime un brouillon.
     */
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();
        if ($this->model('BulletinPaie')->deleteBrouillon((int)$id)) {
            $this->setFlash('success', 'Brouillon supprimé.');
        } else {
            $this->setFlash('error', "Suppression refusée (seuls les brouillons sont supprimables).");
        }
        $this->redirect('bulletinPaie/index');
    }

    // =================================================================
    //  USER — Mes bulletins
    // =================================================================

    /**
     * GET /bulletinPaie/mesBulletins — Page personnelle du salarié.
     */
    public function mesBulletins() {
        $this->requireAuth();
        $userId = (int)$_SESSION['user_id'];
        $bulletins = $this->model('BulletinPaie')->listByUser($userId);

        $this->view('bulletins/mes_bulletins', [
            'title'      => 'Mes bulletins de paie - ' . APP_NAME,
            'showNavbar' => true,
            'bulletins'  => $bulletins,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
            'flash'      => $this->getFlash(),
        ], true);
    }

    // =================================================================
    //  ASSISTANT IA PAIE (Phase 8)
    // =================================================================

    /** SMIC mensuel brut au 1er janvier 2026 (35h × 4.33 sem × 11.88€). */
    private const SMIC_MENSUEL_2026 = 1801.80;

    /** Durée hebdomadaire maximale légale (Code du travail art. L.3121-20). */
    private const DUREE_HEBDO_MAX = 48.0;

    /**
     * GET /bulletinPaie/assistant — page chat IA dédiée à la paie/RH.
     */
    public function assistant() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $annee = (int)($_GET['annee'] ?? date('Y'));
        $mois  = !empty($_GET['mois']) ? (int)$_GET['mois'] : (int)date('n');

        $this->view('bulletins/assistant', [
            'title'      => 'Assistant Paie IA - ' . APP_NAME,
            'showNavbar' => true,
            'annee'      => $annee,
            'mois'       => $mois,
            'moisLabels' => BulletinPaie::MOIS_LABELS,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /bulletinPaie/chat — endpoint AJAX JSON pour conversation Claude (mode paie).
     *
     * Body : { message, history, annee, mois }
     */
    public function chat() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->verifyCsrfHeader();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input       = json_decode(file_get_contents('php://input'), true) ?: [];
            $userMessage = trim((string)($input['message'] ?? ''));
            $history     = is_array($input['history'] ?? null) ? $input['history'] : [];
            $annee       = (int)($input['annee'] ?? date('Y'));
            $mois        = (int)($input['mois'] ?? date('n'));

            if ($userMessage === '') {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            $contexte     = $this->buildContextePaie($annee, $mois);
            $systemPrompt = $this->buildSystemPromptPaie($contexte);

            // Historique limité aux 12 derniers tours
            $messages = [];
            foreach (array_slice($history, -12) as $msg) {
                if (!isset($msg['role'], $msg['content'])) continue;
                $role = in_array($msg['role'], ['user', 'assistant'], true) ? $msg['role'] : 'user';
                $messages[] = ['role' => $role, 'content' => (string)$msg['content']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $claude = new ClaudeService(1500, 60);
            $result = $claude->chat($systemPrompt, $messages);

            echo json_encode(['success' => $result['success'], 'message' => $result['message']]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Construit le contexte paie : effectif, masse salariale, conventions, anomalies.
     * Volontairement compact pour rester sous 1000 tokens.
     */
    private function buildContextePaie(int $annee, int $mois): array {
        $pdo = Database::getInstance()->getConnection();
        $libellePeriode = (BulletinPaie::MOIS_LABELS[$mois] ?? '') . ' ' . $annee;

        // Effectif staff actif total + par catégorie de rôles
        $stmt = $pdo->query("SELECT role, COUNT(*) AS nb FROM users
                             WHERE actif = 1
                               AND role NOT IN ('proprietaire','locataire_permanent')
                             GROUP BY role
                             ORDER BY nb DESC");
        $parRole = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $effectifTotal = array_sum(array_column($parRole, 'nb'));

        // Couverture fiches RH
        $stmt = $pdo->query("SELECT COUNT(*) FROM salaries_rh s
                             JOIN users u ON u.id = s.user_id
                             WHERE u.actif = 1 AND s.date_sortie IS NULL");
        $nbFichesRh = (int)$stmt->fetchColumn();

        // Conventions collectives utilisées
        $stmt = $pdo->query("SELECT cc.nom, cc.idcc, COUNT(s.id) AS nb_salaries
                             FROM conventions_collectives cc
                             LEFT JOIN salaries_rh s ON s.convention_collective_id = cc.id
                             WHERE cc.actif = 1
                             GROUP BY cc.id
                             ORDER BY nb_salaries DESC, cc.nom");
        $conventions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Masse salariale du mois (bulletins émis OU validés)
        $stmt = $pdo->prepare("SELECT statut, COUNT(*) AS nb,
                                       COALESCE(SUM(total_brut),0) AS brut,
                                       COALESCE(SUM(net_a_payer),0) AS net,
                                       COALESCE(SUM(cout_employeur_total),0) AS cout
                                FROM bulletins_paie
                                WHERE periode_annee = ? AND periode_mois = ?
                                GROUP BY statut");
        $stmt->execute([$annee, $mois]);
        $bulletinsParStatut = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Bulletins brouillons anciens (> 30 jours)
        $stmt = $pdo->query("SELECT COUNT(*) FROM bulletins_paie
                             WHERE statut = 'brouillon'
                               AND created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
        $nbBrouillonsAnciens = (int)$stmt->fetchColumn();

        // Anomalies
        $anomalies = [];

        // 1. Salariés sans fiche RH
        $stmt = $pdo->query("SELECT u.id, u.username, u.role FROM users u
                             LEFT JOIN salaries_rh s ON s.user_id = u.id
                             WHERE u.actif = 1
                               AND u.role NOT IN ('admin','proprietaire','locataire_permanent','exploitant')
                               AND s.id IS NULL
                             LIMIT 5");
        $sansFiche = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sansFiche as $s) {
            $anomalies[] = sprintf("Salarié sans fiche RH : %s (%s)", $s['username'], $s['role']);
        }

        // 2. Salaires sous SMIC (sur fiches RH temps plein)
        $stmt = $pdo->prepare("SELECT u.username, s.salaire_brut_base
                                FROM salaries_rh s JOIN users u ON u.id = s.user_id
                                WHERE u.actif = 1
                                  AND s.date_sortie IS NULL
                                  AND s.temps_travail = 'temps_plein'
                                  AND s.salaire_brut_base IS NOT NULL
                                  AND s.salaire_brut_base < ?
                                LIMIT 5");
        $stmt->execute([self::SMIC_MENSUEL_2026]);
        $sousSmic = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($sousSmic as $s) {
            $anomalies[] = sprintf("Salaire <SMIC : %s à %s € (SMIC = %s €)",
                $s['username'],
                number_format((float)$s['salaire_brut_base'], 2, ',', ' '),
                number_format(self::SMIC_MENSUEL_2026, 2, ',', ' ')
            );
        }

        // 3. Heures hebdomadaires > 48h (durée légale max) sur le mois courant
        $debut = sprintf('%d-%02d-01', $annee, $mois);
        $fin   = date('Y-m-t', strtotime($debut));
        $stmt = $pdo->prepare("SELECT user_id, YEARWEEK(date_debut, 1) AS semaine,
                                      SUM(heures_calculees) AS total_h
                                FROM planning_shifts
                                WHERE date_debut BETWEEN ? AND ?
                                GROUP BY user_id, semaine
                                HAVING total_h > ?
                                ORDER BY total_h DESC LIMIT 5");
        $stmt->execute([$debut, $fin, self::DUREE_HEBDO_MAX]);
        $surHeures = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($surHeures as $sh) {
            $anomalies[] = sprintf("Dépassement durée légale : user #%d → %s h sur semaine %s (max %s h)",
                (int)$sh['user_id'],
                number_format((float)$sh['total_h'], 1, ',', ' '),
                $sh['semaine'],
                self::DUREE_HEBDO_MAX
            );
        }

        if ($nbBrouillonsAnciens > 0) {
            $anomalies[] = sprintf("%d bulletins en brouillon depuis plus de 30 jours", $nbBrouillonsAnciens);
        }

        // Construction du résumé textuel
        $lines = [];
        $lines[] = "PÉRIODE ANALYSÉE : $libellePeriode";
        $lines[] = "";
        $lines[] = "═══ EFFECTIF ═══";
        $lines[] = "• Total staff actif : $effectifTotal";
        $lines[] = "• Fiches RH renseignées : $nbFichesRh / $effectifTotal";
        if (!empty($parRole)) {
            $top = array_slice($parRole, 0, 6);
            $lines[] = "• Top rôles : " . implode(', ', array_map(
                fn($r) => $r['role'] . ' (' . $r['nb'] . ')',
                $top
            ));
        }

        $lines[] = "";
        $lines[] = "═══ CONVENTIONS COLLECTIVES ACTIVES ═══";
        foreach ($conventions as $c) {
            $lines[] = sprintf("• %s%s — %d salarié(s)",
                $c['nom'],
                $c['idcc'] ? ' (IDCC ' . $c['idcc'] . ')' : '',
                (int)$c['nb_salaries']
            );
        }

        $lines[] = "";
        $lines[] = "═══ MASSE SALARIALE $libellePeriode ═══";
        if (empty($bulletinsParStatut)) {
            $lines[] = "• Aucun bulletin créé pour cette période.";
        } else {
            foreach ($bulletinsParStatut as $bp) {
                $lines[] = sprintf("• %s : %d bulletins, brut %s €, net %s €, coût employeur %s €",
                    BulletinPaie::STATUTS[$bp['statut']] ?? $bp['statut'],
                    (int)$bp['nb'],
                    number_format((float)$bp['brut'], 2, ',', ' '),
                    number_format((float)$bp['net'], 2, ',', ' '),
                    number_format((float)$bp['cout'], 2, ',', ' ')
                );
            }
        }

        $lines[] = "";
        $lines[] = "═══ TAUX 2026 (référence) ═══";
        $lines[] = "• PMSS : " . number_format(BulletinPaie::PMSS_2026, 2, ',', ' ') . " €";
        $lines[] = "• SMIC mensuel brut (35h) : " . number_format(self::SMIC_MENSUEL_2026, 2, ',', ' ') . " €";
        $lines[] = "• Heures mensuelles légales : " . BulletinPaie::HEURES_MENSUELLES . " h";
        $lines[] = "• Cot. salariales totales ~22% (déplafonnée 0.40 + plaf 6.90 + CSG 9.20 + CRDS 0.50 + AGIRC-ARRCO T1 4.15)";
        $lines[] = "• Cot. patronales totales ~42% (maladie 7 + vieillesse 8.55 + AF 5.25 + AT/MP 1.80 + FNAL 0.50 + AGIRC-ARRCO T1 6.20 + autres 1.23)";

        if (!empty($anomalies)) {
            $lines[] = "";
            $lines[] = "═══ ANOMALIES DÉTECTÉES ═══";
            foreach ($anomalies as $a) {
                $lines[] = "⚠ $a";
            }
        }

        return [
            'resume'       => implode("\n", $lines),
            'libelle'      => $libellePeriode,
            'effectif'     => $effectifTotal,
            'nb_fiches_rh' => $nbFichesRh,
            'anomalies'    => $anomalies,
        ];
    }

    /**
     * Construit le system prompt en mode "responsable RH/paie".
     */
    private function buildSystemPromptPaie(array $contexte): string {
        return "Tu es un assistant RH/paie francophone spécialisé dans la gestion de la paie d'un parc de résidences seniors (Domitys). "
             . "Tu apportes des analyses pédagogiques sur la paie, les cotisations sociales, les conventions collectives applicables, "
             . "et tu aides à détecter des anomalies sur les bulletins et le planning de travail.\n\n"
             . "═══ CONTEXTE PAIE RÉEL ═══\n"
             . $contexte['resume']
             . "\n\n"
             . "═══ RÈGLES DE RÉPONSE ═══\n"
             . "1. Réponds STRICTEMENT en français, ton professionnel mais clair.\n"
             . "2. Cite les chiffres réels du contexte ci-dessus quand pertinent.\n"
             . "3. Quand tu cites un montant, utilise le format français (1 234,56 €).\n"
             . "4. Connais le Code du travail français : durée légale (35h hebdo, 48h max sauf accord, 220h sup/an), congés (2.5 jours/mois), période d'essai (CDI 2 mois employé / 4 mois cadre), préavis CDI démission (1 mois employé / 3 mois cadre).\n"
             . "5. Connais les bases des conventions collectives mentionnées (Services à la personne 3370, HCR 1979, Aide à domicile BAD 2941, Paysage 7018, Immobilier 1527, BTP).\n"
             . "6. Sur les cotisations 2026 : tu connais les taux du PMSS (3 925 €), CSG/CRDS (sur 98.25 % du brut), AGIRC-ARRCO T1/T2.\n"
             . "7. NE PRÉTENDS JAMAIS être un expert-paie agréé : précise toujours qu'une validation par un cabinet de paie ou expert-comptable est nécessaire avant transmission DSN.\n"
             . "8. Ne suggère JAMAIS d'optimisation sociale agressive (travail dissimulé, contournement de cotisations, etc.).\n"
             . "9. Format markdown léger : **gras** pour chiffres clés, listes à puces.\n"
             . "10. Limite tes réponses à 250 mots sauf demande explicite plus longue.\n\n"
             . "═══ DOMAINES DE COMPÉTENCE ═══\n"
             . "- Simulation brut → net (taux salariaux ~22%, patronaux ~42%)\n"
             . "- Calcul heures supplémentaires (majoration 25% sur 8 premières, 50% au-delà)\n"
             . "- Conformité : SMIC, durée légale, période d'essai, préavis, congés payés\n"
             . "- Lecture d'un bulletin : identification des cotisations majeures, plausibilité du net\n"
             . "- Anomalies : salaire sous SMIC, dépassement durée légale, bulletin manquant, fiche RH absente\n"
             . "- Préparation DSN, taxe d'apprentissage, formation professionnelle\n"
             . "- Conventions collectives : grille des salaires, congés conventionnels, indemnités spécifiques\n";
    }
}
