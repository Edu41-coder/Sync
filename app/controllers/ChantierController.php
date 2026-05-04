<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Chantiers (travaux planifiés)
 * ====================================================================
 * Workflow 9 phases avec auto-création garanties à la réception et
 * auto-flag necessite_ag selon seuil 5000 € HT.
 *
 * Accès :
 *   - admin / directeur_residence / technicien_chef : tout
 *   - technicien : lecture seule (sur ses spécialités/résidences)
 */
class ChantierController extends Controller {

    private const ROLES_ALL     = ['admin', 'directeur_residence', 'technicien_chef', 'technicien'];
    private const ROLES_MANAGER = ['admin', 'directeur_residence', 'technicien_chef'];
    private const UPLOAD_DOCS = '../uploads/maintenance/chantier_docs/';

    /** Résidences accessibles au user */
    private function residencesAccessibles(): array {
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        if ($userRole === 'admin') {
            return $pdo->query("SELECT id FROM coproprietees WHERE actif = 1")->fetchAll(PDO::FETCH_COLUMN);
        }
        $stmt = $pdo->prepare("SELECT residence_id FROM user_residence WHERE user_id = ? AND statut = 'actif'");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /** Vérifie l'accès à un chantier (résidence du user) */
    private function checkAccessChantier(array $chantier): bool {
        $userRole = $_SESSION['user_role'] ?? '';
        if ($userRole === 'admin') return true;
        return in_array((int)$chantier['residence_id'], $this->residencesAccessibles(), true);
    }

    // ─── LISTE ──────────────────────────────────────────────────

    /** GET /chantier/index */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $cModel = $this->model('Chantier');
        $userRole = $_SESSION['user_role'] ?? '';
        $resIds = $this->residencesAccessibles();

        $filtres = [
            'phase'        => $_GET['phase']        ?? null,
            'statut'       => $_GET['statut']       ?? null,
            'residence_id' => $_GET['residence_id'] ?? null,
            'categorie'    => $_GET['categorie']    ?? null,
        ];

        $chantiers = $cModel->getChantiers($this->getUserId(), $userRole, $resIds, $filtres);
        $stats = $cModel->getStats($resIds, $userRole);

        $this->view('chantiers/index', [
            'title'      => 'Chantiers - ' . APP_NAME,
            'showNavbar' => true,
            'chantiers'  => $chantiers,
            'stats'      => $stats,
            'filtres'    => $filtres,
            'phases'     => Chantier::PHASES,
            'phasesLabels' => Chantier::PHASES_LABELS,
            'isManager'  => in_array($userRole, self::ROLES_MANAGER, true),
            'flash'      => $this->getFlash(),
        ], true);
    }

    // ─── CRUD CHANTIER ──────────────────────────────────────────

    /** GET|POST /chantier/form/{id?} */
    public function form($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $cModel = $this->model('Chantier');
        $specModel = $this->model('Specialite');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $data = [
                    'residence_id'      => (int)($_POST['residence_id'] ?? 0),
                    'specialite_id'     => $_POST['specialite_id'] ?? null,
                    'titre'             => trim($_POST['titre'] ?? ''),
                    'description'       => trim($_POST['description'] ?? '') ?: null,
                    'categorie'         => $_POST['categorie'] ?? 'autre',
                    'phase'             => $_POST['phase'] ?? 'diagnostic',
                    'statut'            => $_POST['statut'] ?? 'actif',
                    'priorite'          => $_POST['priorite'] ?? 'normale',
                    'necessite_ag_force' => isset($_POST['necessite_ag_force']) ? $_POST['necessite_ag_force'] : null,
                    'ag_id'             => $_POST['ag_id'] ?? null,
                    'sinistre_id'       => $_POST['sinistre_id'] ?? null, // chantier issu d'un sinistre
                    'date_debut_prevue' => $_POST['date_debut_prevue'] ?? null,
                    'date_fin_prevue'   => $_POST['date_fin_prevue'] ?? null,
                    'date_debut_reelle' => $_POST['date_debut_reelle'] ?? null,
                    'date_fin_reelle'   => $_POST['date_fin_reelle'] ?? null,
                    'montant_estime'    => $_POST['montant_estime'] ?? null,
                    'montant_engage'    => $_POST['montant_engage'] ?? null,
                    'montant_paye'      => $_POST['montant_paye'] ?? null,
                    'notes'             => trim($_POST['notes'] ?? '') ?: null,
                ];
                if ($data['titre'] === '' || !$data['residence_id']) {
                    throw new Exception('Titre et résidence sont obligatoires.');
                }
                if ($id) {
                    $cModel->updateChantier((int)$id, $data);
                    $this->setFlash('success', 'Chantier mis à jour.');
                    $this->redirect('chantier/show/' . (int)$id);
                } else {
                    $data['created_by'] = $this->getUserId();
                    $newId = $cModel->createChantier($data);
                    $this->setFlash('success', 'Chantier créé.');
                    $this->redirect('chantier/show/' . $newId);
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'chantier/form/' . (int)$id : 'chantier/form');
                return;
            }
        }

        // GET
        $chantier = $id ? $cModel->findChantier((int)$id) : null;
        if ($id && !$chantier) {
            $this->setFlash('error', 'Chantier introuvable.');
            $this->redirect('chantier/index'); return;
        }

        // Pré-remplissage depuis un sinistre (?sinistre_id=X) à la création
        // Permet le bouton "Créer un chantier de réparation" sur la fiche sinistre.
        $sinistrePrefill = null;
        if (!$id && !empty($_GET['sinistre_id'])) {
            $sinistreModel = $this->model('Sinistre');
            $sinistreId = (int)$_GET['sinistre_id'];
            $userId   = (int)$this->getUserId();
            $userRoleSession = $_SESSION['user_role'] ?? '';
            if ($sinistreModel->userCanAccess($sinistreId, $userId, $userRoleSession)) {
                $sinistre = $sinistreModel->findWithDetails($sinistreId);
                if ($sinistre) {
                    $sinistrePrefill = [
                        'sinistre_id'    => $sinistreId,
                        'sinistre_titre' => $sinistre['titre'],
                        'sinistre_type'  => $sinistre['type_sinistre'],
                        'residence_id'   => (int)$sinistre['residence_id'],
                        'titre'          => 'Réparation : ' . $sinistre['titre'],
                        'description'    => "Chantier de réparation suite au sinistre #{$sinistreId} (" . $sinistre['type_sinistre'] . ").\n\n" . $sinistre['description'],
                        'montant_estime' => $sinistre['montant_estime'] ?? null,
                    ];
                }
            }
        }

        // Résidences accessibles
        $pdo = Database::getInstance()->getConnection();
        $userRole = $_SESSION['user_role'] ?? '';
        if ($userRole === 'admin') {
            $residences = $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville FROM coproprietees c JOIN user_residence ur ON ur.residence_id=c.id WHERE ur.user_id=? AND ur.statut='actif' AND c.actif=1 ORDER BY c.nom");
            $stmt->execute([$this->getUserId()]);
            $residences = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // AGs de la résidence sélectionnée
        $ags = [];
        $resForAg = $chantier['residence_id'] ?? $sinistrePrefill['residence_id'] ?? (int)($_GET['residence_id'] ?? 0);
        if ($resForAg) $ags = $cModel->getAGsResidence((int)$resForAg);

        $this->view('chantiers/form', [
            'title'           => ($id ? 'Modifier' : 'Nouveau') . ' chantier - ' . APP_NAME,
            'showNavbar'      => true,
            'chantier'        => $chantier,
            'residences'      => $residences,
            'specialites'     => $specModel->getAll(),
            'ags'             => $ags,
            'sinistrePrefill' => $sinistrePrefill,
            'categories'      => Chantier::CATEGORIES,
            'phases'          => Chantier::PHASES,
            'phasesLabels'    => Chantier::PHASES_LABELS,
            'statuts'         => Chantier::STATUTS,
            'priorites'       => Chantier::PRIORITES,
            'seuilAg'         => Chantier::SEUIL_AG_HT,
            'flash'           => $this->getFlash(),
        ], true);
    }

    /** GET /chantier/show/{id} */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);

        $cModel = $this->model('Chantier');
        $chantier = $cModel->findChantier((int)$id);
        if (!$chantier) {
            $this->setFlash('error', 'Chantier introuvable.');
            $this->redirect('chantier/index'); return;
        }
        if (!$this->checkAccessChantier($chantier)) {
            $this->setFlash('error', "Vous n'avez pas accès à ce chantier.");
            $this->redirect('chantier/index'); return;
        }

        $this->view('chantiers/show', [
            'title'        => 'Chantier ' . $chantier['titre'] . ' - ' . APP_NAME,
            'showNavbar'   => true,
            'chantier'     => $chantier,
            'devis'        => $cModel->getDevis((int)$id),
            'jalons'       => $cModel->getJalons((int)$id),
            'documents'    => $cModel->getDocuments((int)$id),
            'receptions'   => $cModel->getReceptions((int)$id),
            'garanties'    => $cModel->getGaranties((int)$id),
            'lotsImpactes' => $cModel->getLotsImpactes((int)$id),
            'quoteParts'   => $cModel->getQuotePartProprietaires((int)$id),
            'lotsResidence' => $cModel->getLotsResidence((int)$chantier['residence_id']),
            'fournisseurs' => $this->getFournisseursActifs(),
            'phases'       => Chantier::PHASES,
            'phasesLabels' => Chantier::PHASES_LABELS,
            'typesDoc'     => Chantier::TYPES_DOC,
            'isManager'    => in_array($_SESSION['user_role'] ?? '', self::ROLES_MANAGER, true),
            'flash'        => $this->getFlash(),
        ], true);
    }

    /** POST /chantier/delete/{id} */
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $this->model('Chantier')->deleteChantier((int)$id);
        $this->setFlash('success', 'Chantier supprimé.');
        $this->redirect('chantier/index');
    }

    /** POST /chantier/phase/{id} — transition de phase */
    public function phase($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $nouvellePhase = $_POST['phase'] ?? '';
        if ($this->model('Chantier')->transitionPhase((int)$id, $nouvellePhase)) {
            $this->setFlash('success', 'Phase changée → ' . ($nouvellePhase));
        } else {
            $this->setFlash('error', 'Phase invalide.');
        }
        $this->redirect('chantier/show/' . (int)$id);
    }

    // ─── DEVIS ──────────────────────────────────────────────────

    /** POST /chantier/devisCreate */
    public function devisCreate() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        try {
            $data = [
                'chantier_id'           => $chantierId,
                'fournisseur_id'        => (int)($_POST['fournisseur_id'] ?? 0),
                'reference'             => trim($_POST['reference'] ?? '') ?: null,
                'date_devis'            => $_POST['date_devis'] ?? date('Y-m-d'),
                'date_validite'         => $_POST['date_validite'] ?? null,
                'montant_ht'            => (float)($_POST['montant_ht'] ?? 0),
                'tva_pourcentage'       => (float)($_POST['tva_pourcentage'] ?? 20.00),
                'delai_execution_jours' => $_POST['delai_execution_jours'] ?? null,
                'statut'                => 'recu',
                'notes'                 => trim($_POST['notes'] ?? '') ?: null,
            ];
            if (!$data['fournisseur_id'] || $data['montant_ht'] <= 0) {
                throw new Exception('Fournisseur et montant HT obligatoires.');
            }
            $this->model('Chantier')->createDevis($data);
            $this->setFlash('success', 'Devis ajouté.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('chantier/show/' . $chantierId);
    }

    /** POST /chantier/devisRetenir/{devisId} */
    public function devisRetenir($devisId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $cModel = $this->model('Chantier');
        $devis = $cModel->findDevis((int)$devisId);
        if (!$devis) { $this->redirect('chantier/index'); return; }

        if ($cModel->retenirDevis((int)$devisId)) {
            $this->setFlash('success', 'Devis retenu. Montant engagé mis à jour.');
        } else {
            $this->setFlash('error', 'Erreur lors de la sélection du devis.');
        }
        $this->redirect('chantier/show/' . (int)$devis['chantier_id']);
    }

    /** POST /chantier/devisDelete/{devisId} */
    public function devisDelete($devisId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $cModel = $this->model('Chantier');
        $devis = $cModel->findDevis((int)$devisId);
        if (!$devis) { $this->redirect('chantier/index'); return; }
        $cModel->deleteDevis((int)$devisId);
        $this->setFlash('success', 'Devis supprimé.');
        $this->redirect('chantier/show/' . (int)$devis['chantier_id']);
    }

    // ─── JALONS ─────────────────────────────────────────────────

    /** POST /chantier/jalonSave */
    public function jalonSave() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        $jalonId    = !empty($_POST['jalon_id']) ? (int)$_POST['jalon_id'] : null;
        try {
            $data = [
                'chantier_id'            => $chantierId,
                'nom'                    => trim($_POST['nom'] ?? ''),
                'description'            => trim($_POST['description'] ?? '') ?: null,
                'date_prevue'            => $_POST['date_prevue'] ?? null,
                'date_realisee'          => $_POST['date_realisee'] ?? null,
                'pourcentage_avancement' => (int)($_POST['pourcentage_avancement'] ?? 0),
                'ordre'                  => (int)($_POST['ordre'] ?? 0),
                'notes'                  => trim($_POST['notes'] ?? '') ?: null,
            ];
            if ($data['nom'] === '') throw new Exception('Nom du jalon obligatoire.');
            if ($jalonId) $this->model('Chantier')->updateJalon($jalonId, $data);
            else $this->model('Chantier')->createJalon($data);
            $this->setFlash('success', 'Jalon enregistré.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('chantier/show/' . $chantierId);
    }

    /** POST /chantier/jalonDelete/{id} */
    public function jalonDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        $this->model('Chantier')->deleteJalon((int)$id);
        $this->setFlash('success', 'Jalon supprimé.');
        $this->redirect('chantier/show/' . $chantierId);
    }

    // ─── DOCUMENTS ──────────────────────────────────────────────

    /** POST /chantier/documentUpload */
    public function documentUpload() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        try {
            if (empty($_FILES['fichier']) || $_FILES['fichier']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception('Aucun fichier sélectionné.');
            }
            $type = $_POST['type'] ?? 'autre';
            if (!in_array($type, Chantier::TYPES_DOC, true)) {
                throw new Exception('Type de document invalide.');
            }
            $file = $_FILES['fichier'];
            if ($file['size'] > 50 * 1024 * 1024) {
                throw new Exception('Fichier trop volumineux (max 50 Mo).');
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $extOk = ['pdf','jpg','jpeg','png','webp','doc','docx','xls','xlsx','dwg','zip'];
            if (!in_array($ext, $extOk, true)) {
                throw new Exception("Extension non autorisée (.{$ext}).");
            }
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime = $finfo->file($file['tmp_name']);

            $dir = self::UPLOAD_DOCS . $chantierId . '/';
            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                throw new Exception('Impossible de créer le dossier de stockage.');
            }
            $sanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
            $nom = time() . '_' . bin2hex(random_bytes(4)) . '_' . substr($sanitize, 0, 80) . '.' . $ext;
            $cheminRel = $chantierId . '/' . $nom;
            if (!move_uploaded_file($file['tmp_name'], self::UPLOAD_DOCS . $cheminRel)) {
                throw new Exception("Erreur d'enregistrement.");
            }
            $this->model('Chantier')->createDocument([
                'chantier_id'     => $chantierId,
                'type'            => $type,
                'nom_fichier'     => $file['name'],
                'chemin_stockage' => $cheminRel,
                'mime_type'       => $mime,
                'taille_octets'   => (int)$file['size'],
                'description'     => trim($_POST['description'] ?? '') ?: null,
                'uploaded_by'     => $this->getUserId(),
            ]);
            $this->setFlash('success', 'Document ajouté.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('chantier/show/' . $chantierId);
    }

    /** GET /chantier/documentDownload/{id} */
    public function documentDownload($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ALL);
        $doc = $this->model('Chantier')->findDocument((int)$id);
        if (!$doc) { http_response_code(404); exit('Introuvable'); }
        $chemin = self::UPLOAD_DOCS . ltrim($doc['chemin_stockage'], '/');
        if (!file_exists($chemin)) { http_response_code(404); exit('Fichier physique manquant'); }
        $mime = $doc['mime_type'] ?: (mime_content_type($chemin) ?: 'application/octet-stream');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($chemin));
        header('Content-Disposition: inline; filename="' . rawurlencode($doc['nom_fichier']) . '"');
        header('X-Content-Type-Options: nosniff');
        readfile($chemin);
        exit;
    }

    /** POST /chantier/documentDelete/{id} */
    public function documentDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        $cModel = $this->model('Chantier');
        $deleted = $cModel->deleteDocument((int)$id);
        if ($deleted && !empty($deleted['chemin_stockage'])) {
            $f = self::UPLOAD_DOCS . ltrim($deleted['chemin_stockage'], '/');
            if (file_exists($f)) @unlink($f);
        }
        $this->setFlash('success', 'Document supprimé.');
        $this->redirect('chantier/show/' . $chantierId);
    }

    // ─── RÉCEPTION (auto-création garanties) ───────────────────

    /** POST /chantier/receptionCreate */
    public function receptionCreate() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        try {
            $data = [
                'chantier_id'          => $chantierId,
                'date_reception'       => $_POST['date_reception'] ?? date('Y-m-d'),
                'avec_reserves'        => !empty($_POST['avec_reserves']) ? 1 : 0,
                'reserves_description' => trim($_POST['reserves_description'] ?? '') ?: null,
                'pv_pdf'               => null, // TODO upload PV séparément si besoin
                'signe_par_id'         => $this->getUserId(),
                'fournisseur_id'       => !empty($_POST['fournisseur_id']) ? (int)$_POST['fournisseur_id'] : null,
            ];
            $this->model('Chantier')->createReception($data);
            $this->setFlash('success', 'Réception enregistrée. 3 garanties auto-créées (parfait achèvement, biennale, décennale).');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('chantier/show/' . $chantierId);
    }

    /** POST /chantier/reserveLevee/{id} */
    public function reserveLevee($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();
        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        $this->model('Chantier')->leverReserves((int)$id, $_POST['date_levee'] ?? date('Y-m-d'));
        $this->setFlash('success', 'Réserves levées.');
        $this->redirect('chantier/show/' . $chantierId);
    }

    // ─── LOTS IMPACTÉS ──────────────────────────────────────────

    /** POST /chantier/lotsImpactes */
    public function lotsImpactes() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $chantierId = (int)($_POST['chantier_id'] ?? 0);
        $lots = [];
        if (!empty($_POST['lots']) && is_array($_POST['lots'])) {
            foreach ($_POST['lots'] as $row) {
                if (empty($row['lot_id']) || !isset($row['quote_part_pourcentage'])) continue;
                $lots[] = ['lot_id' => (int)$row['lot_id'], 'quote_part_pourcentage' => (float)$row['quote_part_pourcentage']];
            }
        }
        $this->model('Chantier')->setLotsImpactes($chantierId, $lots);
        $this->setFlash('success', 'Lots impactés mis à jour.');
        $this->redirect('chantier/show/' . $chantierId);
    }

    // ─── HELPERS ────────────────────────────────────────────────

    private function getFournisseursActifs(): array {
        $pdo = Database::getInstance()->getConnection();
        return $pdo->query("SELECT id, nom, email FROM fournisseurs ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
    }
}
