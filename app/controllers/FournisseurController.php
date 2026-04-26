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
            'flash'              => $this->getFlash()
        ], true);
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

        $this->view('fournisseurs/form', [
            'title'       => 'Modifier ' . $fournisseur['nom'] . ' - ' . APP_NAME,
            'showNavbar'  => true,
            'fournisseur' => $fournisseur,
            'action'      => 'update/' . (int)$id,
            'flash'       => $this->getFlash()
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
        $this->model('Fournisseur')->activate((int)$id);
        $this->setFlash('success', 'Fournisseur réactivé');
        $this->redirect('fournisseur/show/' . (int)$id);
    }

    // ─────────────────────────────────────────────────────────────
    //  Gestion des liens fournisseur ↔ résidence (depuis la page show)
    // ─────────────────────────────────────────────────────────────

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
        $this->redirect('fournisseur/show/' . (int)$id);
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
        $this->redirect('fournisseur/show/' . (int)$lien['fournisseur_id']);
    }

    public function delier($pivotId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_MANAGER);
        $model = $this->model('Fournisseur');
        $lien  = $model->getLien((int)$pivotId);
        if (!$lien) { $this->setFlash('error', 'Lien introuvable'); $this->redirect('fournisseur/index'); return; }
        $model->delier((int)$pivotId);
        $this->setFlash('success', 'Lien désactivé');
        $this->redirect('fournisseur/show/' . (int)$lien['fournisseur_id']);
    }
}
