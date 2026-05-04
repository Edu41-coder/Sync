<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Assemblées Générales
 * ====================================================================
 * Module gestion AG (admin/direction/comptable) :
 *   - CRUD AG + workflow planifiee → convoquee → tenue / annulee
 *   - Résolutions + saisie votes pondérés (tantièmes optionnels)
 *   - Upload convocation/PV stocké dans uploads/ag/{ag_id}/ (privé)
 *   - Liaison chantiers (chantier.ag_id pour les travaux > 5000 € HT)
 */
class AssembleeController extends Controller {

    private const ROLES_LECTURE = ['admin', 'directeur_residence', 'exploitant', 'comptable'];
    private const ROLES_GESTION = ['admin', 'directeur_residence', 'exploitant'];
    private const UPLOAD_DIR    = '../uploads/ag/';

    /** Résidences accessibles au user connecté */
    private function residencesAccessibles(): array {
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        if ($userRole === 'admin') {
            return $pdo->query("SELECT id, nom, ville FROM coproprietees WHERE actif = 1 ORDER BY nom")
                       ->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $pdo->prepare("SELECT c.id, c.nom, c.ville
                                FROM coproprietees c
                                JOIN user_residence ur ON ur.residence_id = c.id AND ur.statut = 'actif'
                                WHERE c.actif = 1 AND ur.user_id = ?
                                ORDER BY c.nom");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function residenceIds(): array {
        return array_column($this->residencesAccessibles(), 'id');
    }

    // ─── INDEX + DASHBOARD ──────────────────────────────────────

    /** GET /assemblee/index?residence_id=N&statut=X */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_LECTURE);

        $model = $this->model('Assemblee');
        $residences = $this->residencesAccessibles();
        $isManager = in_array($_SESSION['user_role'] ?? '', self::ROLES_GESTION, true);

        $residenceFilter = isset($_GET['residence_id']) && (int)$_GET['residence_id'] > 0
            ? [(int)$_GET['residence_id']]
            : array_column($residences, 'id');
        $residenceFilter = array_intersect($residenceFilter, array_column($residences, 'id'));

        $filtres = [
            'statut' => $_GET['statut'] ?? '',
            'type'   => $_GET['type']   ?? '',
            'annee'  => $_GET['annee']  ?? '',
        ];

        $this->view('assemblees/index', [
            'title'       => 'Assemblées Générales - ' . APP_NAME,
            'showNavbar'  => true,
            'residences'  => $residences,
            'residenceIdSelected' => isset($_GET['residence_id']) ? (int)$_GET['residence_id'] : 0,
            'ags'         => $model->getAGs(array_values($residenceFilter), $filtres),
            'stats'       => $model->getStats(array_column($residences, 'id')),
            'filtres'     => $filtres,
            'isManager'   => $isManager,
            'flash'       => $this->getFlash(),
        ], true);
    }

    /** GET /assemblee/show/{id} */
    public function show($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_LECTURE);

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Assemblée introuvable ou non accessible.');
            $this->redirect('assemblee/index'); return;
        }

        $this->view('assemblees/show', [
            'title'             => 'AG ' . htmlspecialchars($ag['type']) . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'ag'                => $ag,
            'resolutions'       => $model->getResolutions((int)$id),
            'chantiersLies'     => $model->getChantiersLies((int)$id),
            'chantiersEnAttente'=> $model->getChantiersEnAttente((int)$ag['copropriete_id']),
            'candidats'         => $model->getPresidencesCandidats((int)$ag['copropriete_id']),
            'isManager'         => in_array($_SESSION['user_role'] ?? '', self::ROLES_GESTION, true),
            'flash'             => $this->getFlash(),
        ], true);
    }

    /** GET|POST /assemblee/form/{id?} */
    public function form($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);

        $model = $this->model('Assemblee');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $residenceId = (int)($_POST['copropriete_id'] ?? 0);
                if (!in_array($residenceId, $this->residenceIds(), true)) {
                    throw new Exception('Résidence non accessible.');
                }
                if (!in_array($_POST['type'] ?? '', Assemblee::TYPES, true)) throw new Exception('Type invalide.');
                if (!in_array($_POST['mode'] ?? '', Assemblee::MODES, true)) throw new Exception('Mode invalide.');
                if (empty($_POST['date_ag'])) throw new Exception('Date obligatoire.');

                $data = [
                    'copropriete_id'      => $residenceId,
                    'type'                => $_POST['type'],
                    'date_ag'             => $_POST['date_ag'],
                    'lieu'                => trim($_POST['lieu'] ?? '') ?: null,
                    'mode'                => $_POST['mode'],
                    'ordre_du_jour'       => trim($_POST['ordre_du_jour'] ?? '') ?: null,
                    'proces_verbal'       => trim($_POST['proces_verbal'] ?? '') ?: null,
                    'notes_internes'      => trim($_POST['notes_internes'] ?? '') ?: null,
                    'president_seance_id' => $_POST['president_seance_id'] ?? null,
                    'secretaire_id'       => $_POST['secretaire_id']       ?? null,
                    'quorum_requis'       => $_POST['quorum_requis']  ?? null,
                    'quorum_present'      => $_POST['quorum_present'] ?? null,
                    'votants_total'       => $_POST['votants_total']  ?? null,
                    'quorum_atteint'      => isset($_POST['quorum_atteint']) ? 1 : 0,
                    'created_by'          => $this->getUserId(),
                ];

                if ($id) {
                    $model->updateAG((int)$id, $data);
                    $this->setFlash('success', 'AG mise à jour.');
                    $this->redirect('assemblee/show/' . (int)$id);
                } else {
                    $newId = $model->createAG($data);
                    $this->setFlash('success', 'AG créée.');
                    $this->redirect('assemblee/show/' . $newId);
                }
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id ? 'assemblee/form/' . (int)$id : 'assemblee/form');
                return;
            }
        }

        $ag = $id ? $model->findAG((int)$id) : null;
        if ($id && (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true))) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }

        $residenceId = $ag ? (int)$ag['copropriete_id'] : (int)($_GET['residence_id'] ?? 0);

        $this->view('assemblees/form', [
            'title'      => ($id ? 'Modifier AG' : 'Nouvelle AG') . ' - ' . APP_NAME,
            'showNavbar' => true,
            'ag'         => $ag,
            'residences' => $this->residencesAccessibles(),
            'residenceId'=> $residenceId,
            'candidats'  => $residenceId ? $model->getPresidencesCandidats($residenceId) : [],
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /assemblee/delete/{id} */
    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);
        $this->verifyCsrf();

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }
        $model->deleteAG((int)$id);
        $dir = self::UPLOAD_DIR . (int)$id;
        if (is_dir($dir)) {
            foreach (glob($dir . '/*') as $f) @unlink($f);
            @rmdir($dir);
        }
        $this->setFlash('success', 'AG supprimée.');
        $this->redirect('assemblee/index?residence_id=' . (int)$ag['copropriete_id']);
    }

    // ─── WORKFLOW ───────────────────────────────────────────────

    /** POST /assemblee/convoquer/{id} */
    public function convoquer($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);
        $this->verifyCsrf();

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }
        if ($ag['statut'] !== 'planifiee') {
            $this->setFlash('error', 'Cette AG ne peut plus être convoquée (statut actuel : ' . $ag['statut'] . ').');
            $this->redirect('assemblee/show/' . (int)$id); return;
        }

        try {
            $chemin = null;
            if (!empty($_FILES['document_convocation']) && $_FILES['document_convocation']['error'] === UPLOAD_ERR_OK) {
                $chemin = $this->handleUpload((int)$id, $_FILES['document_convocation'], 'convocation');
            }
            $model->convoquer((int)$id, $chemin);
            $this->setFlash('success', 'AG convoquée — la date d\'envoi a été enregistrée.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('assemblee/show/' . (int)$id);
    }

    /** POST /assemblee/tenir/{id} */
    public function tenir($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);
        $this->verifyCsrf();

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }
        if (!in_array($ag['statut'], ['planifiee', 'convoquee'], true)) {
            $this->setFlash('error', 'Statut invalide pour cette action.');
            $this->redirect('assemblee/show/' . (int)$id); return;
        }

        try {
            $documentPv = null;
            if (!empty($_FILES['document_pv']) && $_FILES['document_pv']['error'] === UPLOAD_ERR_OK) {
                $documentPv = $this->handleUpload((int)$id, $_FILES['document_pv'], 'pv');
            }
            $model->tenir((int)$id, [
                'proces_verbal'       => trim($_POST['proces_verbal'] ?? '') ?: null,
                'document_pv'         => $documentPv,
                'quorum_atteint'      => isset($_POST['quorum_atteint']) ? 1 : 0,
                'quorum_present'      => $_POST['quorum_present'] ?? null,
                'votants_total'       => $_POST['votants_total']  ?? null,
                'president_seance_id' => $_POST['president_seance_id'] ?? null,
                'secretaire_id'       => $_POST['secretaire_id']       ?? null,
            ]);
            $this->setFlash('success', 'AG marquée comme tenue. Pensez à saisir les résultats des votes.');
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
        }
        $this->redirect('assemblee/show/' . (int)$id);
    }

    /** POST /assemblee/annuler/{id} */
    public function annuler($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);
        $this->verifyCsrf();

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }
        $model->annuler((int)$id);
        $this->setFlash('success', 'AG annulée.');
        $this->redirect('assemblee/show/' . (int)$id);
    }

    // ─── DOWNLOAD PIÈCES JOINTES ────────────────────────────────

    /** GET /assemblee/download/{id}/{type} — type = convocation ou pv */
    public function download($id, $type = '') {
        $this->requireAuth();
        $this->requireRole(self::ROLES_LECTURE);

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$id);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            http_response_code(404); echo 'Introuvable'; return;
        }
        $champ = $type === 'pv' ? 'document_pv' : 'document_convocation';
        if (empty($ag[$champ])) {
            http_response_code(404); echo 'Aucun document'; return;
        }
        $file = self::UPLOAD_DIR . ltrim($ag[$champ], '/');
        if (!file_exists($file)) {
            http_response_code(404); echo 'Fichier introuvable'; return;
        }
        $nom = basename($file);
        header('Content-Type: ' . (mime_content_type($file) ?: 'application/octet-stream'));
        header('Content-Disposition: inline; filename="' . $nom . '"');
        header('Content-Length: ' . filesize($file));
        readfile($file);
    }

    /** Helper upload : PDF + image, max 10 Mo */
    private function handleUpload(int $agId, array $file, string $prefix): string {
        if ($file['size'] > 10 * 1024 * 1024) throw new Exception('Fichier trop volumineux (max 10 Mo).');
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'], true)) {
            throw new Exception("Extension non autorisée (.$ext). PDF/JPG/PNG acceptés.");
        }
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->file($file['tmp_name']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            throw new Exception('Type MIME non autorisé.');
        }
        $dir = self::UPLOAD_DIR . $agId;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new Exception('Impossible de créer le dossier de stockage.');
        }
        $sanitize = preg_replace('/[^a-zA-Z0-9._-]/', '_', pathinfo($file['name'], PATHINFO_FILENAME));
        $nom = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '_' . substr($sanitize, 0, 60) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nom)) {
            throw new Exception("Erreur d'enregistrement.");
        }
        return $agId . '/' . $nom;
    }

    // ─── RÉSOLUTIONS / VOTES ────────────────────────────────────

    /** GET|POST /assemblee/resolutionForm/{agId}/{id?} */
    public function resolutionForm($agId, $id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);

        $model = $this->model('Assemblee');
        $ag = $model->findAG((int)$agId);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'AG introuvable.');
            $this->redirect('assemblee/index'); return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();
            try {
                $titre = trim($_POST['resolution'] ?? '');
                if ($titre === '') throw new Exception('Intitulé de résolution obligatoire.');

                $data = [
                    'ag_id'            => (int)$agId,
                    'resolution'       => $titre,
                    'description'      => trim($_POST['description'] ?? '') ?: null,
                    'ordre'            => $_POST['ordre']            ?? null,
                    'votes_pour'       => $_POST['votes_pour']       ?? 0,
                    'votes_contre'     => $_POST['votes_contre']     ?? 0,
                    'abstentions'      => $_POST['abstentions']      ?? 0,
                    'tantiemes_pour'   => $_POST['tantiemes_pour']   ?? 0,
                    'tantiemes_contre' => $_POST['tantiemes_contre'] ?? 0,
                ];

                $resultat = $_POST['resultat'] ?? '';
                if (!in_array($resultat, ['adopte', 'rejete', 'reporte'], true)) {
                    $totalVotes = (int)$data['votes_pour'] + (int)$data['votes_contre']
                                + (int)$data['tantiemes_pour'] + (int)$data['tantiemes_contre'];
                    $resultat = $totalVotes > 0 ? $model->calculerResultat($data) : null;
                }
                $data['resultat'] = $resultat;

                if ($id) {
                    $model->updateResolution((int)$id, $data);
                    $this->setFlash('success', 'Résolution mise à jour.');
                } else {
                    $model->createResolution($data);
                    $this->setFlash('success', 'Résolution ajoutée.');
                }
                $this->redirect('assemblee/show/' . (int)$agId);
                return;
            } catch (Exception $e) {
                $this->setFlash('error', 'Erreur : ' . $e->getMessage());
                $this->redirect($id
                    ? 'assemblee/resolutionForm/' . (int)$agId . '/' . (int)$id
                    : 'assemblee/resolutionForm/' . (int)$agId);
                return;
            }
        }

        $resolution = $id ? $model->findResolution((int)$id) : null;
        if ($id && (!$resolution || (int)$resolution['ag_id'] !== (int)$agId)) {
            $this->setFlash('error', 'Résolution introuvable.');
            $this->redirect('assemblee/show/' . (int)$agId); return;
        }

        $this->view('assemblees/resolution_form', [
            'title'      => ($id ? 'Modifier' : 'Nouvelle') . ' résolution - ' . APP_NAME,
            'showNavbar' => true,
            'ag'         => $ag,
            'resolution' => $resolution,
            'nextOrdre'  => $resolution ? (int)$resolution['ordre'] : $model->nextOrdre((int)$agId),
            'flash'      => $this->getFlash(),
        ], true);
    }

    /** POST /assemblee/resolutionDelete/{id} */
    public function resolutionDelete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_GESTION);
        $this->verifyCsrf();

        $model = $this->model('Assemblee');
        $r = $model->findResolution((int)$id);
        if (!$r) {
            $this->setFlash('error', 'Résolution introuvable.');
            $this->redirect('assemblee/index'); return;
        }
        $ag = $model->findAG((int)$r['ag_id']);
        if (!$ag || !in_array((int)$ag['copropriete_id'], $this->residenceIds(), true)) {
            $this->setFlash('error', 'Accès refusé.');
            $this->redirect('assemblee/index'); return;
        }
        $model->deleteResolution((int)$id);
        $this->setFlash('success', 'Résolution supprimée.');
        $this->redirect('assemblee/show/' . (int)$r['ag_id']);
    }
}
