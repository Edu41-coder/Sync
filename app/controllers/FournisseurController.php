<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur des Fournisseurs (global)
 * ====================================================================
 * Gestion des fournisseurs communs à tous les modules.
 * Accès : admin + directeur_residence uniquement.
 */

class FournisseurController extends Controller {

    private const ROLES_MANAGER = ['admin', 'directeur_residence'];

    /**
     * Rôles autorisés à interroger la map produits-fournisseur d'un module donné.
     * Doit rester aligné avec les `commandes()` de chaque controller de module.
     */
    private const MODULE_ROLES = [
        'jardinage'    => ['admin', 'directeur_residence', 'jardinier_manager'],
        'menage'       => ['admin', 'directeur_residence', 'entretien_manager'],
        'restauration' => ['admin', 'restauration_manager'],
    ];

    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Fournisseur');
        $typeFilter = $_GET['type_service'] ?? null;
        $search     = $_GET['q'] ?? null;
        $actifsOnly = !isset($_GET['inclure_inactifs']);

        $this->view('fournisseurs/index', [
            'title'        => 'Fournisseurs - ' . APP_NAME,
            'showNavbar'   => true,
            'fournisseurs' => $model->getAll($typeFilter, $search, $actifsOnly),
            'typeFilter'   => $typeFilter,
            'search'       => $search,
            'actifsOnly'   => $actifsOnly,
            'flash'        => $this->getFlash()
        ], true);
    }

    /**
     * Export CSV de la liste des fournisseurs (respecte les filtres en cours).
     * Format français : BOM UTF-8, séparateur `;`, dates et nombres au format FR.
     */
    public function export() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Fournisseur');
        $typeFilter = $_GET['type_service'] ?? null;
        $search     = $_GET['q'] ?? null;
        $actifsOnly = !isset($_GET['inclure_inactifs']);
        $fournisseurs = $model->getAll($typeFilter, $search, $actifsOnly);

        $typesLabels = Fournisseur::TYPES_SERVICE;
        $suffix = $typeFilter ? '_' . $typeFilter : '';
        $filename = 'fournisseurs' . $suffix . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8 pour Excel
        fputcsv($out, [
            'Nom', 'SIRET', 'Services', 'Contact', 'Téléphone',
            'Email', 'Adresse', 'CP', 'Ville', 'IBAN',
            'Nb résidences liées', 'Statut'
        ], ';');

        foreach ($fournisseurs as $f) {
            $types = $f['type_service'] ? explode(',', $f['type_service']) : [];
            $servicesLabel = implode(', ', array_map(fn($t) => $typesLabels[$t] ?? $t, $types));
            fputcsv($out, [
                $f['nom'] ?? '',
                $f['siret'] ?? '',
                $servicesLabel,
                $f['contact_nom'] ?? '',
                $f['telephone'] ?? '',
                $f['email'] ?? '',
                $f['adresse'] ?? '',
                $f['code_postal'] ?? '',
                $f['ville'] ?? '',
                $f['iban'] ?? '',
                (int)($f['nb_residences'] ?? 0),
                $f['actif'] ? 'Actif' : 'Inactif'
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function show($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Fournisseur');
        $fournisseur = $model->get((int)$id);
        if (!$fournisseur) {
            $this->setFlash('error', 'Fournisseur introuvable');
            $this->redirect('fournisseur/index');
            return;
        }

        $residences = $model->getResidencesLiees((int)$id);
        $dejaIds = array_column($residences, 'id');
        $toutes = $model->getAllResidences();
        $residencesNonLiees = array_values(array_filter($toutes, fn($r) => !in_array((int)$r['id'], $dejaIds, true)));

        $this->view('fournisseurs/show', [
            'title'              => $fournisseur['nom'] . ' - ' . APP_NAME,
            'showNavbar'         => true,
            'fournisseur'        => $fournisseur,
            'residences'         => $residences,
            'residencesNonLiees' => $residencesNonLiees,
            'commandes'          => $model->getCommandesDuFournisseur((int)$id, 50),
            'produits'           => $model->getProduitsDuFournisseur((int)$id),
            'stats'              => $model->getStatsFournisseur((int)$id),
            'flash'              => $this->getFlash()
        ], true);
    }

    /**
     * Endpoint AJAX (GET, JSON) pour le formulaire de création de commande :
     * retourne la map des produits liés à ce fournisseur dans le module donné,
     * avec leur prix négocié et leur statut "préféré".
     *
     * URL : GET /fournisseur/produitsForCommande/{id}?module=jardinage
     * Output : { success: true, produits: { produit_id: {prix, prefere, ref}, ... } }
     */
    public function produitsForCommande($id) {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');

        $module = $_GET['module'] ?? '';
        if (!isset(self::MODULE_ROLES[$module])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Module invalide']);
            exit;
        }
        $this->requireRole(self::MODULE_ROLES[$module]);

        $produits = $this->model('Fournisseur')->getProduitsDuFournisseur((int)$id, $module);

        $map = [];
        foreach ($produits as $p) {
            $map[(int)$p['produit_id']] = [
                'prix'    => $p['prix_unitaire_specifique'] !== null ? (float)$p['prix_unitaire_specifique'] : null,
                'prefere' => (bool)($p['fournisseur_prefere'] ?? false),
                'ref'     => $p['reference_fournisseur'] ?? null,
            ];
        }
        echo json_encode(['success' => true, 'produits' => $map]);
        exit;
    }

    public function create() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $this->view('fournisseurs/form', [
            'title'       => 'Nouveau fournisseur - ' . APP_NAME,
            'showNavbar'  => true,
            'fournisseur' => null,
            'action'      => 'store',
            'flash'       => $this->getFlash()
        ], true);
    }

    public function store() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $model = $this->model('Fournisseur');
        try {
            if (empty(trim($_POST['nom'] ?? ''))) throw new Exception("Le nom est requis");
            $newId = $model->create($_POST);
            $this->setFlash('success', 'Fournisseur créé');
            $this->redirect('fournisseur/show/' . $newId);
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('fournisseur/create');
        }
    }

    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);

        $model = $this->model('Fournisseur');
        $fournisseur = $model->get((int)$id);
        if (!$fournisseur) {
            $this->setFlash('error', 'Fournisseur introuvable');
            $this->redirect('fournisseur/index');
            return;
        }

        // Liens fournisseur ↔ résidences (gérés directement depuis cette page d'édition)
        $residences = $model->getResidencesLiees((int)$id);
        $dejaIds = array_column($residences, 'id');
        $toutes = $model->getAllResidences();
        $residencesNonLiees = array_values(array_filter($toutes, fn($r) => !in_array((int)$r['id'], $dejaIds, true)));

        $this->view('fournisseurs/form', [
            'title'              => 'Modifier ' . $fournisseur['nom'] . ' - ' . APP_NAME,
            'showNavbar'         => true,
            'fournisseur'        => $fournisseur,
            'action'             => 'update/' . (int)$id,
            'residences'         => $residences,
            'residencesNonLiees' => $residencesNonLiees,
            'flash'              => $this->getFlash()
        ], true);
    }

    public function update($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $model = $this->model('Fournisseur');
        try {
            if (empty(trim($_POST['nom'] ?? ''))) throw new Exception("Le nom est requis");
            $model->update((int)$id, $_POST);
            $this->setFlash('success', 'Fournisseur modifié');
            $this->redirect('fournisseur/show/' . (int)$id);
        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('fournisseur/edit/' . (int)$id);
        }
    }

    public function delete($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->requirePostCsrf();

        $model = $this->model('Fournisseur');
        $nbActives = $model->countCommandesActives((int)$id);
        if ($nbActives > 0) {
            $this->setFlash('error', "Impossible de désactiver : $nbActives commande(s) active(s) chez ce fournisseur. Traitez-les d'abord.");
            $this->redirect('fournisseur/show/' . (int)$id);
            return;
        }
        $model->softDelete((int)$id);
        $this->setFlash('success', 'Fournisseur désactivé');
        $this->redirect('fournisseur/index');
    }

    public function activate($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->requirePostCsrf();
        $this->model('Fournisseur')->activate((int)$id);
        $this->setFlash('success', 'Fournisseur réactivé');
        $this->redirect('fournisseur/show/' . (int)$id);
    }

    // ─────────────────────────────────────────────────────────────
    //  Gestion des liens fournisseur ↔ résidence (depuis la page show)
    // ─────────────────────────────────────────────────────────────

    /** Retourne 'edit' ou 'show' selon le paramètre back= passé en POST */
    private function backTarget(): string {
        return ($_POST['back'] ?? $_GET['back'] ?? 'show') === 'edit' ? 'edit' : 'show';
    }

    public function lier($id) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $model = $this->model('Fournisseur');
        try {
            $residenceId = (int)($_POST['residence_id'] ?? 0);
            if (!$residenceId) throw new Exception("Résidence requise");
            $model->lier((int)$id, $residenceId, $_POST);
            $this->setFlash('success', 'Fournisseur lié à la résidence');
        } catch (Exception $e) { $this->setFlash('error', 'Erreur : ' . $e->getMessage()); }
        $this->redirect('fournisseur/' . $this->backTarget() . '/' . (int)$id);
    }

    public function updateLien($pivotId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->verifyCsrf();

        $model = $this->model('Fournisseur');
        $lien  = $model->getLien((int)$pivotId);
        if (!$lien) { $this->setFlash('error', 'Lien introuvable'); $this->redirect('fournisseur/index'); return; }
        $model->updateLien((int)$pivotId, $_POST);
        $this->setFlash('success', 'Lien modifié');
        $this->redirect('fournisseur/' . $this->backTarget() . '/' . (int)$lien['fournisseur_id']);
    }

    public function delier($pivotId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $this->requirePostCsrf();
        $model = $this->model('Fournisseur');
        $lien  = $model->getLien((int)$pivotId);
        if (!$lien) { $this->setFlash('error', 'Lien introuvable'); $this->redirect('fournisseur/index'); return; }
        $model->delier((int)$pivotId);
        $this->setFlash('success', 'Lien désactivé');
        $this->redirect('fournisseur/' . $this->backTarget() . '/' . (int)$lien['fournisseur_id']);
    }
}
