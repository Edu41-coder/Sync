<?php
/**
 * ====================================================================
 * SYND_GEST — Contrôleur Salaries (fiches RH)
 * ====================================================================
 *
 * Phase 3 du module Comptabilité.
 * Gestion CRUD des fiches RH par admin/comptable + vue lecture+RIB pour
 * le salarié lui-même.
 */

class SalarieController extends Controller {

    private const ROLES_ADMIN = ['admin', 'comptable', 'directeur_residence'];

    // =================================================================
    //  ADMIN — Liste, fiche, édition
    // =================================================================

    /**
     * GET /salarie/index — Liste des staff salariés.
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $model = $this->model('SalarieRh');
        $residenceId    = !empty($_GET['residence_id']) ? (int)$_GET['residence_id'] : null;
        $onlyWithFiche  = !empty($_GET['only_with_fiche']);
        $onlyActive     = !isset($_GET['inclure_sortis']);

        $staff = $model->listAllStaff($residenceId, $onlyWithFiche, $onlyActive);

        // Pour le filtre résidence
        $residences = $this->residencesAccessibles();

        $this->view('salaries/index', [
            'title'           => 'Salariés - ' . APP_NAME,
            'showNavbar'      => true,
            'staff'           => $staff,
            'residences'      => $residences,
            'selectedResidence' => $residenceId,
            'onlyWithFiche'   => $onlyWithFiche,
            'onlyActive'      => $onlyActive,
            'flash'           => $this->getFlash(),
        ], true);
    }

    /**
     * GET /salarie/show/{userId} — Fiche RH en lecture (admin).
     */
    public function show($userId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $userId = (int)$userId;
        $userObj = $this->model('User')->find($userId);
        if (!$userObj) {
            $this->setFlash('error', 'Utilisateur introuvable');
            $this->redirect('salarie/index');
            return;
        }

        $model = $this->model('SalarieRh');
        $fiche = $model->findByUserId($userId);

        $this->view('salaries/show', [
            'title'      => 'Fiche RH - ' . APP_NAME,
            'showNavbar' => true,
            'user'       => (array)$userObj,
            'fiche'      => $fiche,
            'typesContrat' => SalarieRh::TYPES_CONTRAT,
            'categories'   => SalarieRh::CATEGORIES,
            'tempsTravail' => SalarieRh::TEMPS_TRAVAIL,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * GET /salarie/edit/{userId} — Formulaire d'édition.
     */
    public function edit($userId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);

        $userId  = (int)$userId;
        $userObj = $this->model('User')->find($userId);
        if (!$userObj) {
            $this->setFlash('error', 'Utilisateur introuvable');
            $this->redirect('salarie/index');
            return;
        }

        $model       = $this->model('SalarieRh');
        $fiche       = $model->findByUserId($userId);
        $conventions = $model->getConventions();

        $this->view('salaries/edit', [
            'title'        => 'Éditer fiche RH - ' . APP_NAME,
            'showNavbar'   => true,
            'user'         => (array)$userObj,
            'fiche'        => $fiche,
            'conventions'  => $conventions,
            'typesContrat' => SalarieRh::TYPES_CONTRAT,
            'categories'   => SalarieRh::CATEGORIES,
            'tempsTravail' => SalarieRh::TEMPS_TRAVAIL,
            'flash'        => $this->getFlash(),
        ], true);
    }

    /**
     * POST /salarie/update/{userId} — Sauvegarde fiche RH.
     */
    public function update($userId) {
        $this->requireAuth();
        $this->requireRole(self::ROLES_ADMIN);
        $this->requirePostCsrf();

        $userId = (int)$userId;
        $userObj = $this->model('User')->find($userId);
        if (!$userObj) {
            $this->setFlash('error', 'Utilisateur introuvable');
            $this->redirect('salarie/index');
            return;
        }

        // Validations basiques
        if (!SalarieRh::validateNumeroSs($_POST['numero_ss'] ?? null)) {
            $this->setFlash('error', "Numéro de Sécurité Sociale invalide (15 chiffres attendus, commence par 1 ou 2).");
            $this->redirect('salarie/edit/' . $userId);
            return;
        }
        if (!SalarieRh::validateIban($_POST['iban'] ?? null)) {
            $this->setFlash('error', "IBAN invalide (format attendu : 2 lettres pays + 2 chiffres + ...)");
            $this->redirect('salarie/edit/' . $userId);
            return;
        }

        try {
            $this->model('SalarieRh')->upsert($userId, $_POST, (int)$_SESSION['user_id']);
            $this->setFlash('success', 'Fiche RH enregistrée.');
            $this->redirect('salarie/show/' . $userId);
        } catch (Throwable $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('salarie/edit/' . $userId);
        }
    }

    // =================================================================
    //  USER — Mes infos RH (lecture + édition RIB)
    // =================================================================

    /**
     * GET /salarie/mesInfos — Page personnelle du salarié.
     */
    public function mesInfos() {
        $this->requireAuth();

        $userId = (int)$_SESSION['user_id'];
        $userObj = $this->model('User')->find($userId);
        $fiche   = $this->model('SalarieRh')->findByUserId($userId);

        $this->view('salaries/mes_infos', [
            'title'      => 'Mes informations RH - ' . APP_NAME,
            'showNavbar' => true,
            'user'       => (array)$userObj,
            'fiche'      => $fiche,
            'typesContrat' => SalarieRh::TYPES_CONTRAT,
            'categories'   => SalarieRh::CATEGORIES,
            'tempsTravail' => SalarieRh::TEMPS_TRAVAIL,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /salarie/updateRib — Le salarié met à jour SES coordonnées bancaires.
     */
    public function updateRib() {
        $this->requireAuth();
        $this->requirePostCsrf();

        $userId = (int)$_SESSION['user_id'];
        $iban   = trim((string)($_POST['iban'] ?? ''));
        $bic    = trim((string)($_POST['bic'] ?? ''));

        if (!SalarieRh::validateIban($iban !== '' ? $iban : null)) {
            $this->setFlash('error', "IBAN invalide.");
            $this->redirect('salarie/mesInfos');
            return;
        }
        if ($this->model('SalarieRh')->updateRib($userId, $iban !== '' ? $iban : null, $bic !== '' ? $bic : null)) {
            $this->setFlash('success', 'Coordonnées bancaires mises à jour.');
        } else {
            $this->setFlash('error', "Erreur lors de la mise à jour.");
        }
        $this->redirect('salarie/mesInfos');
    }

    // =================================================================
    //  HELPERS PRIVÉS
    // =================================================================

    /**
     * Liste des résidences accessibles (réutilisé dans le filtre).
     */
    private function residencesAccessibles(): array {
        $userId = $this->getUserId();
        $userRole = $_SESSION['user_role'] ?? '';
        $pdo = Database::getInstance()->getConnection();
        if ($userRole === 'admin') {
            return $pdo->query("SELECT id, nom FROM coproprietees WHERE actif = 1 ORDER BY nom")->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $pdo->prepare("SELECT c.id, c.nom FROM coproprietees c JOIN user_residence ur ON ur.residence_id = c.id WHERE ur.user_id = ? AND ur.statut = 'actif' AND c.actif = 1 ORDER BY c.nom");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
