<?php
/**
 * ====================================================================
 * SYND_GEST - Contrôleur Hôtes Temporaires
 * ====================================================================
 * Gestion des séjours temporaires (réservations court séjour)
 */

class HoteController extends Controller {

    public function index() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'employe_residence', 'comptable']);

        $model = $this->model('HoteTemporaire');
        $hotes = $model->getAll();

        $stats = [
            'total'     => count($hotes),
            'reserves'  => count(array_filter($hotes, fn($h) => $h['statut'] === 'reserve')),
            'en_cours'  => count(array_filter($hotes, fn($h) => $h['statut'] === 'en_cours')),
            'termines'  => count(array_filter($hotes, fn($h) => $h['statut'] === 'termine')),
            'ca_total'  => array_sum(array_column(array_filter($hotes, fn($h) => $h['statut_paiement'] === 'paye'), 'montant_total')),
        ];

        $this->view('hotes/index', [
            'title'    => 'Hôtes Temporaires - ' . APP_NAME,
            'showNavbar' => true,
            'hotes'    => $hotes,
            'stats'    => $stats,
            'flash'    => $this->getFlash()
        ], true);
    }

    public function create() {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'employe_residence']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->store();
            return;
        }

        $model = $this->model('HoteTemporaire');

        $this->view('hotes/create', [
            'title'      => 'Nouvelle Réservation - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $model->getResidences(),
            'lots'       => json_encode($model->getLots()),
            'flash'      => $this->getFlash()
        ], true);
    }

    private function store() {
        $this->verifyCsrf();

        try {
            $dateArrivee = $_POST['date_arrivee'] ?? '';
            $dateDepart  = $_POST['date_depart_prevue'] ?? '';
            $prixNuit    = !empty($_POST['prix_nuit']) ? (float)$_POST['prix_nuit'] : null;
            $nbNuits     = (strtotime($dateDepart) - strtotime($dateArrivee)) / 86400;
            $montantTotal = ($prixNuit && $nbNuits > 0) ? $prixNuit * $nbNuits : null;
            $lotId       = (int)($_POST['lot_id'] ?? 0) ?: null;
            $residenceId = (int)($_POST['residence_id'] ?? 0) ?: null;

            // Vérifier disponibilité du lot
            if ($lotId) {
                $lotModel = $this->model('Lot');
                if ($lotModel->isOccupied($lotId)) {
                    throw new Exception("Ce lot est déjà occupé par un résident permanent.");
                }
                if ($lotModel->isReservedByHote($lotId, $dateArrivee, $dateDepart)) {
                    throw new Exception("Ce lot est déjà réservé sur ces dates.");
                }
            }

            $model = $this->model('HoteTemporaire');
            $model->createHote([
                'civilite'              => $_POST['civilite'] ?? 'M',
                'nom'                   => trim($_POST['nom']),
                'prenom'                => trim($_POST['prenom']),
                'date_naissance'        => $_POST['date_naissance'] ?: null,
                'nationalite'           => trim($_POST['nationalite'] ?? '') ?: null,
                'type_piece_identite'   => $_POST['type_piece_identite'] ?: null,
                'numero_piece_identite' => trim($_POST['numero_piece_identite'] ?? '') ?: null,
                'telephone'             => trim($_POST['telephone'] ?? '') ?: null,
                'telephone_mobile'      => trim($_POST['telephone_mobile'] ?? '') ?: null,
                'email'                 => trim($_POST['email'] ?? '') ?: null,
                'adresse_domicile'      => trim($_POST['adresse_domicile'] ?? '') ?: null,
                'lot_id'                => $lotId,
                'residence_id'          => $residenceId,
                'date_arrivee'          => $dateArrivee,
                'date_depart_prevue'    => $dateDepart,
                'nb_personnes'          => (int)($_POST['nb_personnes'] ?? 1),
                'motif_sejour'          => $_POST['motif_sejour'] ?? 'vacances',
                'prix_nuit'             => $prixNuit,
                'montant_total'         => $montantTotal,
                'statut_paiement'       => 'en_attente',
                'statut'                => 'reserve',
                'notes'                 => trim($_POST['notes'] ?? '') ?: null,
            ]);

            $this->setFlash('success', 'Réservation créée avec succès');
            $this->redirect('hote/index');

        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('hote/create');
        }
    }

    public function show($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'employe_residence', 'comptable']);

        $model = $this->model('HoteTemporaire');
        $hote = $model->findById($id);

        if (!$hote) {
            $this->setFlash('error', 'Séjour introuvable');
            $this->redirect('hote/index');
            return;
        }

        $this->view('hotes/show', [
            'title'    => 'Séjour #' . $hote['id'] . ' - ' . APP_NAME,
            'showNavbar' => true,
            'hote'     => $hote,
            'flash'    => $this->getFlash()
        ], true);
    }

    public function edit($id) {
        $this->requireAuth();
        $this->requireRole(['admin', 'directeur_residence', 'employe_residence']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->update($id);
            return;
        }

        $model = $this->model('HoteTemporaire');
        $hote = $model->findForEdit($id);

        if (!$hote) {
            $this->setFlash('error', 'Séjour introuvable');
            $this->redirect('hote/index');
            return;
        }

        $this->view('hotes/edit', [
            'title'      => 'Modifier Séjour - ' . APP_NAME,
            'showNavbar' => true,
            'hote'       => $hote,
            'residences' => $model->getResidences(),
            'lots'       => json_encode($model->getLots()),
            'flash'      => $this->getFlash()
        ], true);
    }

    private function update($id) {
        $this->verifyCsrf();

        try {
            $dateArrivee = $_POST['date_arrivee'] ?? '';
            $dateDepart  = $_POST['date_depart_prevue'] ?? '';
            $prixNuit    = !empty($_POST['prix_nuit']) ? (float)$_POST['prix_nuit'] : null;
            $nbNuits     = (strtotime($dateDepart) - strtotime($dateArrivee)) / 86400;
            $montantTotal = ($prixNuit && $nbNuits > 0) ? $prixNuit * $nbNuits : null;

            $model = $this->model('HoteTemporaire');
            $model->updateHote($id, [
                'civilite'              => $_POST['civilite'] ?? 'M',
                'nom'                   => trim($_POST['nom']),
                'prenom'                => trim($_POST['prenom']),
                'date_naissance'        => $_POST['date_naissance'] ?: null,
                'nationalite'           => trim($_POST['nationalite'] ?? '') ?: null,
                'type_piece_identite'   => $_POST['type_piece_identite'] ?: null,
                'numero_piece_identite' => trim($_POST['numero_piece_identite'] ?? '') ?: null,
                'telephone'             => trim($_POST['telephone'] ?? '') ?: null,
                'telephone_mobile'      => trim($_POST['telephone_mobile'] ?? '') ?: null,
                'email'                 => trim($_POST['email'] ?? '') ?: null,
                'adresse_domicile'      => trim($_POST['adresse_domicile'] ?? '') ?: null,
                'lot_id'                => (int)($_POST['lot_id'] ?? 0) ?: null,
                'residence_id'          => (int)($_POST['residence_id'] ?? 0) ?: null,
                'date_arrivee'          => $dateArrivee,
                'date_depart_prevue'    => $dateDepart,
                'date_depart_effective' => $_POST['date_depart_effective'] ?: null,
                'nb_personnes'          => (int)($_POST['nb_personnes'] ?? 1),
                'motif_sejour'          => $_POST['motif_sejour'] ?? 'vacances',
                'prix_nuit'             => $prixNuit,
                'montant_total'         => $montantTotal,
                'statut_paiement'       => $_POST['statut_paiement'] ?? 'en_attente',
                'statut'                => $_POST['statut'] ?? 'reserve',
                'notes'                 => trim($_POST['notes'] ?? '') ?: null,
            ]);

            $this->setFlash('success', 'Séjour modifié avec succès');
            $this->redirect('hote/show/' . $id);

        } catch (Exception $e) {
            $this->setFlash('error', 'Erreur : ' . $e->getMessage());
            $this->redirect('hote/edit/' . $id);
        }
    }
}
