<?php
/**
 * ====================================================================
 * SYND_GEST — Contrôleur Comptabilité (dashboard central)
 * ====================================================================
 *
 * Phase 2 : agrège toutes les écritures de `ecritures_comptables` (issues
 * des modules Jardinage / Ménage / Restauration / Loyers / RH / etc.) +
 * agrégation séparée pour Maintenance (qui agrège elle-même depuis
 * maintenance_interventions / chantiers / ascenseur_journal).
 *
 * Permissions :
 *   - admin              : toutes résidences
 *   - directeur_residence : ses résidences via user_residence
 *   - comptable          : ses résidences via user_residence
 */

class ComptabiliteController extends Controller {

    private const ROLES = ['admin', 'directeur_residence', 'comptable'];

    /**
     * Résidences accessibles à l'utilisateur courant.
     */
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

    /**
     * Liste des résidences accessibles (id + nom) pour le sélecteur.
     */
    private function residencesPourSelecteur(): array {
        $ids = $this->residencesAccessibles();
        if (empty($ids)) return [];
        $pdo = Database::getInstance()->getConnection();
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, nom FROM coproprietees WHERE id IN ($ph) ORDER BY nom");
        $stmt->execute(array_map('intval', $ids));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // =================================================================
    //  DASHBOARD CENTRAL
    // =================================================================

    /**
     * GET /comptabilite/index — dashboard agrégé.
     *
     * Filtres URL : annee, mois, residence_id, modules[]
     */
    public function index() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $residences     = $this->residencesPourSelecteur();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        // Modules sélectionnés (par défaut tous sauf maintenance qui est traitée à part)
        $allModules     = array_keys(Ecriture::MODULES);
        $modulesNonMaint = array_values(array_diff($allModules, ['maintenance']));
        $modulesFilter  = $_GET['modules'] ?? null;
        if (is_array($modulesFilter) && !empty($modulesFilter)) {
            $modulesFilter = array_values(array_intersect($modulesFilter, $allModules));
        } else {
            $modulesFilter = $modulesNonMaint;
        }

        // Bornes de période
        if ($mois) {
            $dateMin = sprintf('%d-%02d-01', $annee, $mois);
            $dateMax = date('Y-m-t', strtotime($dateMin));
        } else {
            $dateMin = sprintf('%d-01-01', $annee);
            $dateMax = sprintf('%d-12-31', $annee);
        }

        // KPIs principaux (issus de ecritures_comptables) + comparaison N-1
        $eModel = new Ecriture();
        $totaux   = $eModel->getTotaux($filteredIds, $dateMin, $dateMax, $modulesFilter);
        $tvaRecap = $eModel->getRecapTva($filteredIds, $dateMin, $dateMax);
        $tva      = $this->aggregateTva($tvaRecap);

        if ($mois) {
            $dateMinNm1 = sprintf('%d-%02d-01', $annee - 1, $mois);
            $dateMaxNm1 = date('Y-m-t', strtotime($dateMinNm1));
        } else {
            $dateMinNm1 = sprintf('%d-01-01', $annee - 1);
            $dateMaxNm1 = sprintf('%d-12-31', $annee - 1);
        }
        $totauxNm1   = $eModel->getTotaux($filteredIds, $dateMinNm1, $dateMaxNm1, $modulesFilter);
        $tvaRecapNm1 = $eModel->getRecapTva($filteredIds, $dateMinNm1, $dateMaxNm1);
        $tvaNm1      = $this->aggregateTva($tvaRecapNm1);

        // Synthèse mensuelle pour Chart.js (12 mois année courante + N-1)
        $synthese     = $eModel->getSyntheseMensuelle($filteredIds, $annee, $modulesFilter);
        $syntheseNm1  = $eModel->getSyntheseMensuelle($filteredIds, $annee - 1, $modulesFilter);
        $chartLabels  = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $chartRecettes = array_fill(0, 12, 0.0);
        $chartDepenses = array_fill(0, 12, 0.0);
        $chartRecNm1   = array_fill(0, 12, 0.0);
        $chartDepNm1   = array_fill(0, 12, 0.0);
        foreach ($synthese as $s) {
            $chartRecettes[(int)$s['mois'] - 1] = (float)$s['recettes_ttc'];
            $chartDepenses[(int)$s['mois'] - 1] = (float)$s['depenses_ttc'];
        }
        foreach ($syntheseNm1 as $s) {
            $chartRecNm1[(int)$s['mois'] - 1] = (float)$s['recettes_ttc'];
            $chartDepNm1[(int)$s['mois'] - 1] = (float)$s['depenses_ttc'];
        }

        // Ventilations
        $parModule    = $eModel->getVentilationParModule($filteredIds, $dateMin, $dateMax);
        $parResidence = $eModel->getVentilationParResidence($filteredIds, $dateMin, $dateMax, $modulesFilter);

        // 10 dernières écritures
        $dernieresEcritures = $eModel->findFiltered([
            'residence_ids' => $filteredIds,
            'modules'       => $modulesFilter,
            'date_min'      => $dateMin,
            'date_max'      => $dateMax,
            'limit'         => 10,
        ]);

        // Section Maintenance — agrégation parallèle (pas dans ecritures_comptables)
        $mainStats = ['total' => 0, 'interventions' => 0, 'chantiers' => 0, 'ascenseurs' => 0];
        if (in_array('maintenance', $modulesFilter, true) || empty($modulesFilter)) {
            try {
                $mc = $this->model('MaintenanceComptabilite');
                $mt = $mc->getTotauxAnnuels($filteredIds, $annee);
                $mainStats = [
                    'total'         => (float)($mt['total'] ?? 0),
                    'interventions' => (float)($mt['interventions'] ?? 0),
                    'chantiers'     => (float)($mt['chantiers_payes'] ?? $mt['chantiers'] ?? 0),
                    'ascenseurs'    => (float)($mt['ascenseurs'] ?? 0),
                ];
            } catch (Throwable $e) {
                // Module Maintenance optionnel — on ignore si indisponible
            }
        }

        // Indicateurs de pilotage Phase 12 — actions à mener
        $todos = $this->getTodosWidgets($filteredIds);

        $this->view('comptabilite/index', [
            'title'              => 'Tableau de bord comptable ' . $annee . ' - ' . APP_NAME,
            'showNavbar'         => true,
            'residences'         => $residences,
            'selectedResidence'  => $sel,
            'annee'              => $annee,
            'mois'               => $mois,
            'modulesAll'         => Ecriture::MODULES,
            'moduleColors'       => Ecriture::MODULE_COLORS,
            'modulesFilter'      => $modulesFilter,
            'totaux'             => $totaux,
            'totauxNm1'          => $totauxNm1,
            'tva'                => $tva,
            'tvaNm1'             => $tvaNm1,
            'parModule'          => $parModule,
            'parResidence'       => $parResidence,
            'dernieresEcritures' => $dernieresEcritures,
            'mainStats'          => $mainStats,
            'todos'              => $todos,
            'chartLabels'        => json_encode($chartLabels),
            'chartRecettes'      => json_encode($chartRecettes),
            'chartDepenses'      => json_encode($chartDepenses),
            'chartRecNm1'        => json_encode($chartRecNm1),
            'chartDepNm1'        => json_encode($chartDepNm1),
            'flash'              => $this->getFlash(),
        ], true);
    }

    /**
     * Indicateurs "actions à mener" affichés sur le dashboard (Phase 12).
     * Compte les éléments qui demandent l'attention du comptable :
     *   - Bulletins de paie en brouillon
     *   - Déclarations TVA en brouillon (non transmises au SIE)
     *   - Opérations bancaires non rapprochées
     *   - Salariés actifs sans fiche RH
     *   - Écritures sans compte comptable affecté
     */
    private function getTodosWidgets(array $residenceIds): array {
        $defaults = [
            'bulletins_brouillon' => 0,
            'tva_brouillon'       => 0,
            'bank_non_rapp'       => 0,
            'rh_manquantes'       => 0,
            'ecritures_tbd'       => 0,
        ];
        if (empty($residenceIds)) return $defaults;

        try {
            $pdo = Database::getInstance()->getConnection();

            // Bulletins en brouillon (toutes résidences)
            $r = $pdo->query("SELECT COUNT(*) FROM bulletins_paie WHERE statut = 'brouillon'")->fetchColumn();
            $defaults['bulletins_brouillon'] = (int)$r;

            // Déclarations TVA en brouillon (filtrées par résidences accessibles)
            $resPh = implode(',', array_fill(0, count($residenceIds), '?'));
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM declarations_tva
                                   WHERE residence_id IN ($resPh) AND statut = 'brouillon'");
            $stmt->execute(array_map('intval', $residenceIds));
            $defaults['tva_brouillon'] = (int)$stmt->fetchColumn();

            // Opérations bancaires non rapprochées
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bank_operations
                                   WHERE residence_id IN ($resPh) AND statut = 'non_rapprochee'");
            $stmt->execute(array_map('intval', $residenceIds));
            $defaults['bank_non_rapp'] = (int)$stmt->fetchColumn();

            // Salariés actifs sans fiche RH (count global, pas filtré résidence
            // car les staff peuvent être affectés à plusieurs résidences)
            $r = $pdo->query("SELECT COUNT(*) FROM users u
                              LEFT JOIN salaries_rh s ON s.user_id = u.id
                              WHERE u.actif = 1
                                AND u.role NOT IN ('admin','proprietaire','locataire_permanent','exploitant')
                                AND s.id IS NULL")->fetchColumn();
            $defaults['rh_manquantes'] = (int)$r;

            // Écritures sans compte comptable affecté (limite légale potentielle)
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ecritures_comptables
                                   WHERE residence_id IN ($resPh)
                                     AND compte_comptable_id IS NULL
                                     AND statut != 'brouillon'");
            $stmt->execute(array_map('intval', $residenceIds));
            $defaults['ecritures_tbd'] = (int)$stmt->fetchColumn();
        } catch (Throwable $e) {
            // Silence — les widgets sont best-effort
        }

        return $defaults;
    }

    /**
     * Réduit le récap TVA par taux × type en totaux globaux.
     */
    private function aggregateTva(array $recap): array {
        $r = ['collectee' => 0.0, 'deductible' => 0.0, 'a_reverser' => 0.0];
        foreach ($recap as $line) {
            if ($line['type_ecriture'] === 'recette') {
                $r['collectee'] += (float)$line['total_tva'];
            } elseif ($line['type_ecriture'] === 'depense') {
                $r['deductible'] += (float)$line['total_tva'];
            }
        }
        $r['a_reverser'] = $r['collectee'] - $r['deductible'];
        return $r;
    }

    // =================================================================
    //  ÉCRITURES DÉTAILLÉES
    // =================================================================

    /**
     * GET /comptabilite/ecritures — table détaillée filtrable.
     */
    public function ecritures() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $residences     = $this->residencesPourSelecteur();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $module         = $_GET['module'] ?? null;
        $type           = $_GET['type'] ?? null;
        $search         = trim((string)($_GET['q'] ?? ''));

        if ($mois) {
            $dateMin = sprintf('%d-%02d-01', $annee, $mois);
            $dateMax = date('Y-m-t', strtotime($dateMin));
        } else {
            $dateMin = sprintf('%d-01-01', $annee);
            $dateMax = sprintf('%d-12-31', $annee);
        }

        $eModel = new Ecriture();
        $filters = [
            'residence_ids' => $filteredIds,
            'date_min'      => $dateMin,
            'date_max'      => $dateMax,
            'limit'         => 1000,
        ];
        if ($module) { $filters['modules'] = [$module]; }
        if ($type)   { $filters['type_ecriture'] = $type; }
        if ($search !== '') { $filters['search'] = $search; }
        $ecritures = $eModel->findFiltered($filters);

        $this->view('comptabilite/ecritures', [
            'title'             => 'Écritures comptables ' . $annee . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $residences,
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'mois'              => $mois,
            'module'            => $module,
            'type'              => $type,
            'search'            => $search,
            'modulesAll'        => Ecriture::MODULES,
            'moduleColors'      => Ecriture::MODULE_COLORS,
            'ecritures'         => $ecritures,
            'flash'             => $this->getFlash(),
        ], true);
    }

    // =================================================================
    //  EXPORTS COMPTABLES (Phase 5)
    // =================================================================

    /**
     * Mapping module → code journal FEC (3 lettres standard).
     */
    private const JOURNAL_CODES = [
        'jardinage'      => 'JAR',
        'menage'         => 'MEN',
        'restauration'   => 'RES',
        'maintenance'    => 'MAI',
        'loyer_proprio'  => 'LPR',
        'loyer_resident' => 'LRE',
        'services'       => 'SER',
        'hote'           => 'HOT',
        'rh_paie'        => 'PAY',
        'admin'          => 'ADM',
        'sinistre'       => 'SIN',
        'autre'          => 'OD',
    ];

    private const JOURNAL_LIBS = [
        'jardinage'      => 'Journal Jardinage',
        'menage'         => 'Journal Ménage',
        'restauration'   => 'Journal Restauration',
        'maintenance'    => 'Journal Maintenance',
        'loyer_proprio'  => 'Journal Loyers propriétaires',
        'loyer_resident' => 'Journal Loyers résidents',
        'services'       => 'Journal Services résidents',
        'hote'           => 'Journal Hôtes temporaires',
        'rh_paie'        => 'Journal Paie',
        'admin'          => 'Journal Admin',
        'sinistre'       => 'Journal Sinistres',
        'autre'          => 'Opérations diverses',
    ];

    /**
     * GET /comptabilite/export — interface de choix du format.
     */
    public function export() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $this->view('comptabilite/export', [
            'title'      => 'Exports comptables - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $this->residencesPourSelecteur(),
            'modulesAll' => Ecriture::MODULES,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * GET /comptabilite/exportFec
     *
     * Format FEC officiel (DGFIP) — art. A.47 A-1 LPF.
     * 18 colonnes obligatoires, séparateur tabulation, encodage UTF-8.
     * Nom de fichier réglementaire : SIRENFECYYYYMMDD.txt
     * (ici on génère un nom court car SIREN multi-résidences pas pertinent).
     */
    public function exportFec() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $filters = $this->parseExportFilters();
        $eModel  = new Ecriture();
        $rows    = $eModel->getEcrituresPourExport($filters);

        $filename = 'FEC_' . date('Ymd') . '_' . $filters['date_min'] . '_au_' . $filters['date_max'] . '.txt';

        Logger::audit('export_fec', 'ecritures_comptables', null, [
            'periode'        => $filters['date_min'] . ' → ' . $filters['date_max'],
            'residences'     => $filters['residence_ids'] ?? [],
            'modules'        => $filters['modules']       ?? null,
            'nb_ecritures'   => count($rows),
            'fichier'        => $filename,
            'note'           => 'Export FEC DGFIP — traçabilité fiscale obligatoire',
        ]);

        // En-tête FEC : 18 colonnes obligatoires DGFIP
        $headers = [
            'JournalCode', 'JournalLib', 'EcritureNum', 'EcritureDate',
            'CompteNum', 'CompteLib', 'CompAuxNum', 'CompAuxLib',
            'PieceRef', 'PieceDate', 'EcritureLib',
            'Debit', 'Credit', 'EcritureLet', 'DateLet',
            'ValidDate', 'Montantdevise', 'Idevise',
        ];

        $this->sendDownloadHeaders($filename, 'text/plain; charset=UTF-8');
        $out = fopen('php://output', 'w');
        // BOM UTF-8 pour compatibilité éditeurs Windows
        fwrite($out, "\xEF\xBB\xBF");
        // Tabulation + saut de ligne LF (norme FEC)
        fputcsv($out, $headers, "\t", '"', "\\");

        foreach ($rows as $r) {
            $module      = $r['module_source'];
            $journalCode = self::JOURNAL_CODES[$module] ?? 'OD';
            $journalLib  = self::JOURNAL_LIBS[$module] ?? 'Opérations diverses';

            $dateEcrFec  = str_replace('-', '', (string)$r['date_ecriture']);  // YYYYMMDD
            $datePiece   = $dateEcrFec;
            $dateValid   = $dateEcrFec;

            // Montants : recette = crédit, dépense = débit (vue simplifiée 1 ligne par écriture)
            $montant     = number_format((float)$r['montant_ttc'], 2, ',', '');
            $debit       = $r['type_ecriture'] === 'depense' ? $montant : '0,00';
            $credit      = $r['type_ecriture'] === 'recette' ? $montant : '0,00';

            $compteNum   = $r['numero_compte'] ?: '471000';  // 471 = compte d'attente si pas de compte FK
            $compteLib   = $r['compte_libelle'] ?: 'Compte d\'attente';
            $pieceRef    = $r['piece_justificative'] ?: ('ECR-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT));
            $libelle     = $this->cleanFecText($r['libelle']);

            fputcsv($out, [
                $journalCode,
                $journalLib,
                'E' . str_pad((string)$r['id'], 8, '0', STR_PAD_LEFT),
                $dateEcrFec,
                $compteNum,
                $compteLib,
                '',  // CompAuxNum (pas géré pour pilote)
                '',  // CompAuxLib
                $pieceRef,
                $datePiece,
                $libelle,
                $debit,
                $credit,
                '',  // EcritureLet
                '',  // DateLet
                $dateValid,
                '',  // Montantdevise
                '',  // Idevise
            ], "\t", '"', "\\");
        }

        fclose($out);
        exit;
    }

    /**
     * GET /comptabilite/exportCsv
     *
     * CSV Excel-friendly (séparateur ;, BOM UTF-8 pour accents).
     */
    public function exportCsv() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $filters = $this->parseExportFilters();
        $eModel  = new Ecriture();
        $rows    = $eModel->getEcrituresPourExport($filters);

        $filename = 'ecritures_' . $filters['date_min'] . '_au_' . $filters['date_max'] . '.csv';

        Logger::audit('export_csv', 'ecritures_comptables', null, [
            'periode'      => $filters['date_min'] . ' → ' . $filters['date_max'],
            'residences'   => $filters['residence_ids'] ?? [],
            'nb_ecritures' => count($rows),
            'fichier'      => $filename,
        ]);

        $this->sendDownloadHeaders($filename, 'text/csv; charset=UTF-8');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'Date', 'Résidence', 'Module', 'Journal', 'Pièce',
            'Compte', 'Libellé compte', 'Type', 'Catégorie', 'Libellé',
            'Montant HT', 'Taux TVA', 'Montant TVA', 'Montant TTC', 'Statut',
        ], ';', '"', "\\");

        foreach ($rows as $r) {
            $module = $r['module_source'];
            fputcsv($out, [
                date('d/m/Y', strtotime((string)$r['date_ecriture'])),
                $r['residence_nom'] ?? '',
                Ecriture::MODULES[$module] ?? $module,
                self::JOURNAL_CODES[$module] ?? 'OD',
                $r['piece_justificative'] ?: ('ECR-' . str_pad((string)$r['id'], 6, '0', STR_PAD_LEFT)),
                $r['numero_compte'] ?? '',
                $r['compte_libelle'] ?? '',
                $r['type_ecriture'] === 'recette' ? 'Recette' : 'Dépense',
                $r['categorie'],
                $r['libelle'],
                number_format((float)$r['montant_ht'], 2, ',', ' '),
                $r['taux_tva'] !== null ? number_format((float)$r['taux_tva'], 2, ',', '') . '%' : '',
                number_format((float)$r['montant_tva'], 2, ',', ' '),
                number_format((float)$r['montant_ttc'], 2, ',', ' '),
                $r['statut'],
            ], ';', '"', "\\");
        }

        fclose($out);
        exit;
    }

    /**
     * GET /comptabilite/exportCegid
     *
     * Format CSV simplifié compatible import Cegid Quadratus / Loop.
     * Champs : JournalCode|Date|NumPiece|Compte|Libellé|Sens|Montant
     * Séparateur point-virgule, encodage UTF-8.
     */
    public function exportCegid() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $filters = $this->parseExportFilters();
        $eModel  = new Ecriture();
        $rows    = $eModel->getEcrituresPourExport($filters);

        $filename = 'cegid_' . $filters['date_min'] . '_au_' . $filters['date_max'] . '.csv';

        Logger::audit('export_cegid', 'ecritures_comptables', null, [
            'periode'      => $filters['date_min'] . ' → ' . $filters['date_max'],
            'residences'   => $filters['residence_ids'] ?? [],
            'nb_ecritures' => count($rows),
            'fichier'      => $filename,
        ]);

        $this->sendDownloadHeaders($filename, 'text/csv; charset=UTF-8');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");

        fputcsv($out, [
            'JournalCode', 'DateEcriture', 'NumPiece', 'CompteNum', 'CompteLib',
            'LibelleEcriture', 'Sens', 'Montant', 'TauxTVA', 'Reference',
        ], ';', '"', "\\");

        foreach ($rows as $r) {
            $module = $r['module_source'];
            fputcsv($out, [
                self::JOURNAL_CODES[$module] ?? 'OD',
                date('d/m/Y', strtotime((string)$r['date_ecriture'])),
                'E' . str_pad((string)$r['id'], 8, '0', STR_PAD_LEFT),
                $r['numero_compte'] ?? '471000',
                $r['compte_libelle'] ?? 'Compte attente',
                $r['libelle'],
                $r['type_ecriture'] === 'depense' ? 'D' : 'C',
                number_format((float)$r['montant_ttc'], 2, ',', ''),
                $r['taux_tva'] !== null ? number_format((float)$r['taux_tva'], 2, ',', '') : '',
                $r['piece_justificative'] ?? '',
            ], ';', '"', "\\");
        }

        fclose($out);
        exit;
    }

    /**
     * Lit les filtres d'export depuis la query string et résout les résidences accessibles.
     */
    private function parseExportFilters(): array {
        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $modules        = $_GET['modules'] ?? null;

        if ($mois) {
            $dateMin = sprintf('%d-%02d-01', $annee, $mois);
            $dateMax = date('Y-m-t', strtotime($dateMin));
        } else {
            $dateMin = sprintf('%d-01-01', $annee);
            $dateMax = sprintf('%d-12-31', $annee);
        }

        $filters = [
            'residence_ids' => $filteredIds,
            'date_min'      => $dateMin,
            'date_max'      => $dateMax,
        ];
        if (is_array($modules) && !empty($modules)) {
            $filters['modules'] = array_values(array_intersect($modules, array_keys(Ecriture::MODULES)));
        }
        return $filters;
    }

    /**
     * Nettoie un libellé pour FEC : retire CR/LF, tabulations (séparateur réservé)
     * et limite à 200 caractères (recommandation DGFIP).
     */
    private function cleanFecText(?string $text): string {
        $t = (string)$text;
        $t = str_replace(["\t", "\r", "\n"], ' ', $t);
        $t = preg_replace('/\s+/', ' ', $t);
        return mb_substr(trim($t), 0, 200);
    }

    /**
     * Envoie les headers HTTP pour forcer le download d'un fichier.
     */
    private function sendDownloadHeaders(string $filename, string $contentType): void {
        if (ob_get_level()) { ob_end_clean(); }
        header('Content-Type: ' . $contentType);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
    }

    // =================================================================
    //  ASSISTANT IA COMPTABLE (Phase 7)
    // =================================================================

    /**
     * GET /comptabilite/assistant — page chat avec IA pour analyse comptable.
     */
    public function assistant() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $sel            = (int)($_GET['residence_id'] ?? 0);
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;

        $this->view('comptabilite/assistant', [
            'title'             => 'Assistant comptable IA - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'mois'              => $mois,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/chat — endpoint AJAX JSON pour conversation avec Claude.
     *
     * Body JSON : { message, history, residence_id, annee, mois }
     * Réponse   : { success, message, contexte? }
     */
    public function chat() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrfHeader();
        header('Content-Type: application/json; charset=utf-8');

        try {
            $input       = json_decode(file_get_contents('php://input'), true) ?: [];
            $userMessage = trim((string)($input['message'] ?? ''));
            $history     = is_array($input['history'] ?? null) ? $input['history'] : [];
            $residenceId = (int)($input['residence_id'] ?? 0);
            $annee       = (int)($input['annee'] ?? date('Y'));
            $mois        = !empty($input['mois']) ? (int)$input['mois'] : null;

            if ($userMessage === '') {
                echo json_encode(['success' => false, 'message' => 'Message vide']);
                exit;
            }

            // Sécurité : vérifier que la résidence est accessible
            $resAccessibles = $this->residencesAccessibles();
            if ($residenceId && !in_array($residenceId, $resAccessibles, true)) {
                echo json_encode(['success' => false, 'message' => 'Résidence non accessible.']);
                exit;
            }

            $filteredIds = $residenceId ? [$residenceId] : $resAccessibles;
            $contexte    = $this->buildContexteComptable($filteredIds, $annee, $mois);

            $systemPrompt = $this->buildSystemPromptComptable($contexte);

            // Limite l'historique aux 12 derniers tours pour éviter l'explosion de contexte
            $historyLimited = array_slice($history, -12);
            $messages = [];
            foreach ($historyLimited as $msg) {
                if (!isset($msg['role'], $msg['content'])) continue;
                $role = in_array($msg['role'], ['user', 'assistant'], true) ? $msg['role'] : 'user';
                $messages[] = ['role' => $role, 'content' => (string)$msg['content']];
            }
            $messages[] = ['role' => 'user', 'content' => $userMessage];

            $claude = new ClaudeService(1500, 60);
            $result = $claude->chat($systemPrompt, $messages);

            echo json_encode([
                'success' => $result['success'],
                'message' => $result['message'],
            ]);
        } catch (Throwable $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur serveur : ' . $e->getMessage()]);
        }
        exit;
    }

    /**
     * Construit le contexte comptable enrichi (chiffres clés) injecté dans le prompt système.
     * Volontairement compact (< 4000 caractères pour rester sous les limites tokens).
     *
     * @return array  Données structurées + résumé textuel
     */
    private function buildContexteComptable(array $residenceIds, int $annee, ?int $mois): array {
        if (empty($residenceIds)) {
            return ['resume' => 'Aucune résidence accessible.', 'data' => []];
        }

        if ($mois) {
            $dateMin = sprintf('%d-%02d-01', $annee, $mois);
            $dateMax = date('Y-m-t', strtotime($dateMin));
            $libelle = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'][$mois - 1] . ' ' . $annee;
        } else {
            $dateMin = sprintf('%d-01-01', $annee);
            $dateMax = sprintf('%d-12-31', $annee);
            $libelle = 'Année ' . $annee;
        }

        $eModel = new Ecriture();
        $totaux       = $eModel->getTotaux($residenceIds, $dateMin, $dateMax);
        $parModule    = $eModel->getVentilationParModule($residenceIds, $dateMin, $dateMax);
        $parResidence = $eModel->getVentilationParResidence($residenceIds, $dateMin, $dateMax);
        $tva          = $eModel->getDetailTva($residenceIds, $dateMin, $dateMax);

        // Comparaison N-1
        $dateMinN1 = $mois ? sprintf('%d-%02d-01', $annee - 1, $mois) : sprintf('%d-01-01', $annee - 1);
        $dateMaxN1 = $mois ? date('Y-m-t', strtotime($dateMinN1)) : sprintf('%d-12-31', $annee - 1);
        $totauxNm1 = $eModel->getTotaux($residenceIds, $dateMinN1, $dateMaxN1);

        // 5 plus grosses dépenses de la période
        $topDepenses = $eModel->findFiltered([
            'residence_ids' => $residenceIds,
            'date_min'      => $dateMin,
            'date_max'      => $dateMax,
            'type_ecriture' => 'depense',
            'limit'         => 50,
        ]);
        usort($topDepenses, function($a, $b) {
            return (float)$b['montant_ttc'] <=> (float)$a['montant_ttc'];
        });
        $topDepenses = array_slice($topDepenses, 0, 5);

        // Détection d'anomalies basiques : dépense > 3× moyenne du module
        $anomalies = [];
        foreach ($parModule as $pm) {
            if ($pm['nb'] >= 3 && $pm['depenses'] > 0) {
                $moyDep = $pm['depenses'] / max(1, $pm['nb']);
                foreach ($topDepenses as $td) {
                    if ($td['module_source'] === $pm['module_source'] && (float)$td['montant_ttc'] > 3 * $moyDep) {
                        $anomalies[] = sprintf(
                            "Module %s : « %s » à %s € (3× la moyenne du module à %s €)",
                            $pm['module_source'],
                            mb_substr($td['libelle'], 0, 60),
                            number_format((float)$td['montant_ttc'], 2, ',', ' '),
                            number_format($moyDep, 2, ',', ' ')
                        );
                        if (count($anomalies) >= 5) break 2;
                    }
                }
            }
        }

        // Construction du résumé textuel pour Claude
        $lines = [];
        $lines[] = "PÉRIODE ANALYSÉE : $libelle";
        $lines[] = sprintf("PÉRIMÈTRE : %d résidence(s)", count($residenceIds));
        $lines[] = "";
        $lines[] = "═══ TOTAUX ═══";
        $lines[] = sprintf("• Recettes TTC : %s €", number_format($totaux['recettes_ttc'], 2, ',', ' '));
        $lines[] = sprintf("• Dépenses TTC : %s €", number_format($totaux['depenses_ttc'], 2, ',', ' '));
        $lines[] = sprintf("• Résultat net : %s €", number_format($totaux['resultat'], 2, ',', ' '));
        $lines[] = sprintf("• Nb écritures : %d", $totaux['nb_ecritures']);
        if ($totauxNm1['nb_ecritures'] > 0) {
            $varRes = $totaux['resultat'] - $totauxNm1['resultat'];
            $lines[] = sprintf("• N-1 : recettes %s €, dépenses %s €, résultat %s € (variation : %+.2f €)",
                number_format($totauxNm1['recettes_ttc'], 2, ',', ' '),
                number_format($totauxNm1['depenses_ttc'], 2, ',', ' '),
                number_format($totauxNm1['resultat'], 2, ',', ' '),
                $varRes
            );
        }

        if (!empty($parModule)) {
            $lines[] = "";
            $lines[] = "═══ VENTILATION PAR MODULE ═══";
            foreach ($parModule as $pm) {
                $modLabel = Ecriture::MODULES[$pm['module_source']] ?? $pm['module_source'];
                $lines[] = sprintf("• %s : R %s € / D %s € (%d écr.)",
                    $modLabel,
                    number_format((float)$pm['recettes'], 2, ',', ' '),
                    number_format((float)$pm['depenses'], 2, ',', ' '),
                    (int)$pm['nb']
                );
            }
        }

        if (count($parResidence) > 1) {
            $lines[] = "";
            $lines[] = "═══ TOP RÉSIDENCES (par volume) ═══";
            foreach (array_slice($parResidence, 0, 5) as $pr) {
                $lines[] = sprintf("• %s : R %s € / D %s €",
                    $pr['residence_nom'] ?? 'Inconnue',
                    number_format((float)$pr['recettes'], 2, ',', ' '),
                    number_format((float)$pr['depenses'], 2, ',', ' ')
                );
            }
        }

        if (!empty($topDepenses)) {
            $lines[] = "";
            $lines[] = "═══ TOP 5 DÉPENSES ═══";
            foreach ($topDepenses as $td) {
                $modLabel = Ecriture::MODULES[$td['module_source']] ?? $td['module_source'];
                $lines[] = sprintf("• %s — %s : %s € (%s)",
                    $td['date_ecriture'],
                    $modLabel,
                    number_format((float)$td['montant_ttc'], 2, ',', ' '),
                    mb_substr((string)$td['libelle'], 0, 80)
                );
            }
        }

        $lines[] = "";
        $lines[] = "═══ TVA ═══";
        $lines[] = sprintf("• Collectée : %s € — Déductible : %s €",
            number_format($tva['tva_collectee']['total'], 2, ',', ' '),
            number_format($tva['tva_deductible']['total'], 2, ',', ' ')
        );
        if ($tva['tva_a_payer'] > 0) {
            $lines[] = sprintf("• À payer (solde) : %s €", number_format($tva['tva_a_payer'], 2, ',', ' '));
        } elseif ($tva['credit_a_reporter'] > 0) {
            $lines[] = sprintf("• Crédit à reporter : %s €", number_format($tva['credit_a_reporter'], 2, ',', ' '));
        }

        if (!empty($anomalies)) {
            $lines[] = "";
            $lines[] = "═══ ANOMALIES POTENTIELLES ═══";
            foreach ($anomalies as $a) {
                $lines[] = "⚠ $a";
            }
        }

        return [
            'resume'      => implode("\n", $lines),
            'libelle'     => $libelle,
            'totaux'      => $totaux,
            'tva'         => $tva,
            'anomalies'   => $anomalies,
        ];
    }

    /**
     * Construit le system prompt pour le mode "expert-comptable".
     */
    private function buildSystemPromptComptable(array $contexte): string {
        return "Tu es un assistant comptable francophone spécialisé dans la gestion d'un parc de résidences seniors (Domitys). "
             . "Tu analyses les chiffres comptables réels du système Synd_Gest et apportes des analyses pédagogiques et opérationnelles.\n\n"
             . "═══ CONTEXTE COMPTABLE RÉEL ═══\n"
             . $contexte['resume']
             . "\n\n"
             . "═══ RÈGLES DE RÉPONSE ═══\n"
             . "1. Réponds STRICTEMENT en français, ton professionnel mais accessible.\n"
             . "2. Cite les chiffres réels du contexte ci-dessus quand pertinent (€, %, écritures).\n"
             . "3. Quand tu cites un chiffre, utilise le format français (1 234,56 €).\n"
             . "4. Sois CONCRET : suggère des actions, pose des questions précises.\n"
             . "5. Si la question dépasse les données du contexte (ex: prévisions long terme), précise-le clairement.\n"
             . "6. Connais les bases du PCG français, de la TVA (taux 20/10/5,5), de la fiscalité des résidences services seniors (BIC professionnel, Censi-Bouvard, LMNP).\n"
             . "7. NE PRÉTENDS JAMAIS être un expert-comptable agréé : précise toujours qu'une validation par un expert est nécessaire pour les décisions importantes.\n"
             . "8. Ne suggère JAMAIS d'optimisation fiscale agressive ou de pratique douteuse.\n"
             . "9. Format markdown léger : **gras** pour les chiffres clés, listes à puces pour énumérations.\n"
             . "10. Limite tes réponses à 250 mots sauf demande explicite plus longue.\n\n"
             . "═══ DOMAINES DE COMPÉTENCE ═══\n"
             . "- Analyse de tendances (recettes/dépenses N vs N-1, ratios marge, variations)\n"
             . "- Détection d'anomalies (dépense atypique, doublon suspect, oubli de saisie)\n"
             . "- Suggestions d'imputation comptable (compte PCG, module Synd_Gest)\n"
             . "- Préparation de déclarations TVA CA3/CA12\n"
             . "- Optimisation budgétaire par résidence ou par module (jardinage, ménage, restauration, etc.)\n"
             . "- Vulgarisation : expliquer un terme ou une notion comptable au directeur de résidence\n";
    }

    // =================================================================
    //  TVA — DÉCLARATIONS CA3 / CA12 (Phase 6)
    // =================================================================

    /**
     * GET /comptabilite/tva — liste des déclarations TVA archivées + accès calcul.
     */
    public function tva() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = !empty($_GET['annee']) ? (int)$_GET['annee'] : null;
        $statut         = $_GET['statut'] ?? null;
        $regime         = $_GET['regime'] ?? null;

        $dModel = new DeclarationTva();
        $declarations = $dModel->listFiltered($filteredIds, $annee, $statut, $regime);

        $this->view('comptabilite/tva_index', [
            'title'             => 'Déclarations TVA - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'statut'            => $statut,
            'regime'            => $regime,
            'regimes'           => DeclarationTva::REGIMES,
            'statuts'           => DeclarationTva::STATUTS,
            'declarations'      => $declarations,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * GET|POST /comptabilite/tvaCalculer — calcule + affiche un brouillon.
     * En GET : formulaire de paramétrage. En POST : preview + bouton archivage.
     */
    public function tvaCalculer() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $residences     = $this->residencesPourSelecteur();

        $calcul    = null;
        $params    = [
            'residence_id' => 0,
            'regime'       => 'CA3_mensuel',
            'annee'        => (int)date('Y'),
            'mois'         => (int)date('n'),
            'trimestre'    => (int)ceil((int)date('n') / 3),
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->verifyCsrf();

            $params['residence_id'] = (int)($_POST['residence_id'] ?? 0);
            $params['regime']       = $_POST['regime'] ?? 'CA3_mensuel';
            $params['annee']        = (int)($_POST['annee'] ?? date('Y'));
            $params['mois']         = (int)($_POST['mois'] ?? 1);
            $params['trimestre']    = (int)($_POST['trimestre'] ?? 1);

            if (!in_array($params['residence_id'], $resAccessibles, true)) {
                $this->setFlash('error', "Résidence non accessible.");
                $this->redirect('comptabilite/tvaCalculer');
                return;
            }

            try {
                $idx = $params['regime'] === 'CA3_trimestriel' ? $params['trimestre'] : $params['mois'];
                $bornes = DeclarationTva::bornesPeriode($params['regime'], $params['annee'], $idx);

                $dModel  = new DeclarationTva();
                $credit  = $dModel->getCreditAnterieur($params['residence_id'], $params['regime'], $bornes['debut']);
                $calcul  = $dModel->calculer(
                    $params['residence_id'],
                    $params['regime'],
                    $bornes['debut'],
                    $bornes['fin'],
                    $credit
                );
                $calcul['libelle_periode'] = $bornes['libelle'];
                $calcul['residence_nom']   = $this->getResidenceNom($params['residence_id']);
            } catch (Throwable $e) {
                $this->setFlash('error', 'Erreur calcul TVA : ' . $e->getMessage());
            }
        }

        $this->view('comptabilite/tva_calculer', [
            'title'      => 'Calcul TVA - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $residences,
            'regimes'    => DeclarationTva::REGIMES,
            'params'     => $params,
            'calcul'     => $calcul,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/tvaArchiver — persiste un brouillon calculé.
     */
    public function tvaArchiver() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('comptabilite/tva');
            return;
        }

        $resAccessibles = $this->residencesAccessibles();
        $residenceId    = (int)($_POST['residence_id'] ?? 0);
        if (!in_array($residenceId, $resAccessibles, true)) {
            $this->setFlash('error', "Résidence non accessible.");
            $this->redirect('comptabilite/tva');
            return;
        }

        try {
            $regime  = $_POST['regime'] ?? 'CA3_mensuel';
            $idx     = $regime === 'CA3_trimestriel' ? (int)($_POST['trimestre'] ?? 1) : (int)($_POST['mois'] ?? 1);
            $annee   = (int)($_POST['annee'] ?? date('Y'));
            $bornes  = DeclarationTva::bornesPeriode($regime, $annee, $idx);

            $dModel  = new DeclarationTva();
            $credit  = $dModel->getCreditAnterieur($residenceId, $regime, $bornes['debut']);
            $calcul  = $dModel->calculer($residenceId, $regime, $bornes['debut'], $bornes['fin'], $credit);
            $notes   = trim((string)($_POST['notes'] ?? ''));

            $newId = $dModel->archive($calcul, $this->getUserId(), $notes !== '' ? $notes : null);
            $this->setFlash('success', 'Brouillon TVA archivé (#' . $newId . ').');
            $this->redirect('comptabilite/tvaShow/' . $newId);
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
            $this->redirect('comptabilite/tvaCalculer');
        } catch (Throwable $e) {
            $this->setFlash('error', 'Erreur archivage : ' . $e->getMessage());
            $this->redirect('comptabilite/tvaCalculer');
        }
    }

    /**
     * GET /comptabilite/tvaShow/{id} — détail d'une déclaration archivée + mapping CERFA.
     */
    public function tvaShow($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $id = (int)$id;
        if (!$id) { $this->redirect('comptabilite/tva'); return; }

        $dModel = new DeclarationTva();
        $decl   = $dModel->findById($id);

        if (!$decl || !in_array((int)$decl['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Déclaration introuvable ou non accessible.");
            $this->redirect('comptabilite/tva');
            return;
        }

        $bornes = DeclarationTva::bornesPeriode($decl['regime'], (int)date('Y', strtotime($decl['periode_debut'])),
            $decl['regime'] === 'CA3_trimestriel'
                ? (int)ceil((int)date('n', strtotime($decl['periode_debut'])) / 3)
                : (int)date('n', strtotime($decl['periode_debut']))
        );

        $this->view('comptabilite/tva_show', [
            'title'      => 'Déclaration TVA #' . $id . ' - ' . APP_NAME,
            'showNavbar' => true,
            'decl'       => $decl,
            'libellePeriode' => $bornes['libelle'],
            'regimes'    => DeclarationTva::REGIMES,
            'statuts'    => DeclarationTva::STATUTS,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/tvaMarquerDeclaree/{id}
     */
    public function tvaMarquerDeclaree($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $id = (int)$id;
        $dModel = new DeclarationTva();
        $decl   = $dModel->findById($id);
        if (!$decl || !in_array((int)$decl['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Déclaration introuvable ou non accessible.");
            $this->redirect('comptabilite/tva');
            return;
        }
        if ($dModel->markAsDeclaree($id, $this->getUserId())) {
            $this->setFlash('success', "Déclaration marquée comme transmise au SIE.");
        } else {
            $this->setFlash('error', "Action impossible (déjà déclarée ou annulée).");
        }
        $this->redirect('comptabilite/tvaShow/' . $id);
    }

    /**
     * POST /comptabilite/tvaAnnuler/{id}
     */
    public function tvaAnnuler($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $id = (int)$id;
        $dModel = new DeclarationTva();
        $decl   = $dModel->findById($id);
        if (!$decl || !in_array((int)$decl['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Déclaration introuvable ou non accessible.");
            $this->redirect('comptabilite/tva');
            return;
        }
        if ($dModel->annuler($id)) {
            $this->setFlash('success', "Déclaration annulée.");
        } else {
            $this->setFlash('error', "Annulation impossible.");
        }
        $this->redirect('comptabilite/tvaShow/' . $id);
    }

    /**
     * POST /comptabilite/tvaDelete/{id} — supprime un brouillon (réservé brouillons).
     */
    public function tvaDelete($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $id = (int)$id;
        $dModel = new DeclarationTva();
        $decl   = $dModel->findById($id);
        if (!$decl || !in_array((int)$decl['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Déclaration introuvable ou non accessible.");
            $this->redirect('comptabilite/tva');
            return;
        }
        if ($dModel->deleteBrouillon($id)) {
            $this->setFlash('success', "Brouillon supprimé.");
        } else {
            $this->setFlash('error', "Suppression impossible (uniquement les brouillons).");
        }
        $this->redirect('comptabilite/tva');
    }

    /**
     * Récupère le nom d'une résidence (helper interne).
     */
    private function getResidenceNom(int $residenceId): string {
        $stmt = Database::getInstance()->getConnection()->prepare("SELECT nom FROM coproprietees WHERE id = ?");
        $stmt->execute([$residenceId]);
        return (string)($stmt->fetchColumn() ?: '');
    }

    // =================================================================
    //  AUDIT TRAIL (Phase 11)
    // =================================================================

    /**
     * GET /comptabilite/auditTrail — page de consultation des logs métier
     * (table `logs_activite`, alimentée par Logger::audit() depuis les models compta).
     *
     * Filtres : user_id, action (LIKE), table, date_min, date_max, search.
     */
    public function auditTrail() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $filters = [
            'user_id'  => !empty($_GET['user_id']) ? (int)$_GET['user_id'] : null,
            'action'   => trim((string)($_GET['action']   ?? '')),
            'table'    => trim((string)($_GET['table']    ?? '')),
            'date_min' => trim((string)($_GET['date_min'] ?? '')),
            'date_max' => trim((string)($_GET['date_max'] ?? '')),
            'search'   => trim((string)($_GET['search']   ?? '')),
        ];
        // Vide les filtres vides
        $filters = array_filter($filters, fn($v) => $v !== '' && $v !== null);
        // Whitelist stricte : page compta limitée aux actions à préfixe comptable
        $filters['actions_prefixes'] = Logger::COMPTA_ACTION_PREFIXES;

        $entries = Logger::getAuditTrail($filters, 500);

        // Liste des users staff/admin pour le sélecteur
        $pdo = Database::getInstance()->getConnection();
        $users = $pdo->query("SELECT id, username, role FROM users WHERE actif=1 ORDER BY username ASC")->fetchAll(PDO::FETCH_ASSOC);

        $this->view('comptabilite/audit_trail', [
            'title'      => 'Audit trail comptable - ' . APP_NAME,
            'showNavbar' => true,
            'filters'    => $filters,
            'entries'    => $entries,
            'users'      => $users,
            'actions'    => Logger::getAuditActions(Logger::COMPTA_ACTION_PREFIXES),
            'tables'     => Logger::getAuditTables(Logger::COMPTA_ACTION_PREFIXES),
            'flash'      => $this->getFlash(),
        ], true);
    }

    // =================================================================
    //  RAPPROCHEMENT BANCAIRE (Phase 10)
    // =================================================================

    /** Limites upload CSV bancaire. */
    private const BANK_CSV_MAX_SIZE = 5 * 1024 * 1024; // 5 Mo
    private const BANK_CSV_DIR      = '/uploads/bank/';

    /**
     * GET /comptabilite/rapprochement — liste des imports + accès création.
     */
    public function rapprochement() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $rModel  = new RapprochementBancaire();
        $imports = $rModel->listImports($resAccessibles);

        $this->view('comptabilite/rapprochement_index', [
            'title'      => 'Rapprochement bancaire - ' . APP_NAME,
            'showNavbar' => true,
            'residences' => $this->residencesPourSelecteur(),
            'imports'    => $imports,
            'flash'      => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/rapprochementImport — upload CSV + parse + création import.
     */
    public function rapprochementImport() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        $resAccessibles = $this->residencesAccessibles();
        $residenceId    = (int)($_POST['residence_id'] ?? 0);
        if (!in_array($residenceId, $resAccessibles, true)) {
            $this->setFlash('error', "Résidence non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        if (empty($_FILES['csv_file']) || ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $this->setFlash('error', "Fichier CSV manquant ou erreur d'upload.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        $file = $_FILES['csv_file'];
        if ($file['size'] > self::BANK_CSV_MAX_SIZE) {
            $this->setFlash('error', "Fichier trop volumineux (max " . (self::BANK_CSV_MAX_SIZE / 1024 / 1024) . " Mo).");
            $this->redirect('comptabilite/rapprochement');
            return;
        }
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['csv', 'txt'], true)) {
            $this->setFlash('error', "Extension non autorisée (CSV ou TXT uniquement).");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        // Stockage hors public/
        $dir = ROOT_PATH . self::BANK_CSV_DIR . $residenceId;
        if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
        $stored = sprintf('bank_%d_%s_%s.%s', $residenceId, date('YmdHis'), bin2hex(random_bytes(4)), $ext);
        $fullPath = $dir . '/' . $stored;
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $this->setFlash('error', "Erreur lors de l'enregistrement du fichier.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        try {
            $rModel = new RapprochementBancaire();
            $operations = $rModel->parseCsv($fullPath);
            $cheminRel  = self::BANK_CSV_DIR . $residenceId . '/' . $stored;
            $importId   = $rModel->createImport(
                $residenceId,
                $file['name'],
                $cheminRel,
                $operations,
                $this->getUserId(),
                trim((string)($_POST['notes'] ?? '')) ?: null
            );
            $this->setFlash('success', "Import #$importId créé : " . count($operations) . " opérations parsées.");
            $this->redirect('comptabilite/rapprochementShow/' . $importId);
        } catch (Throwable $e) {
            @unlink($fullPath);
            $this->setFlash('error', "Erreur parsing CSV : " . $e->getMessage());
            $this->redirect('comptabilite/rapprochement');
        }
    }

    /**
     * GET /comptabilite/rapprochementShow/{id} — détail import + opérations + suggestions.
     */
    public function rapprochementShow($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $id = (int)$id;
        $rModel = new RapprochementBancaire();
        $import = $rModel->findImport($id);

        if (!$import || !in_array((int)$import['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Import introuvable ou non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        $statutFiltre = $_GET['statut'] ?? null;
        $operations   = $rModel->listOperations($id, $statutFiltre);
        $stats        = $rModel->getStatsImport($id);

        // Pré-calcule les suggestions pour les opérations non rapprochées (limite 3 par op)
        $suggestions = [];
        foreach ($operations as $op) {
            if ($op['statut'] === 'non_rapprochee') {
                $suggestions[(int)$op['id']] = $rModel->suggererMatches((int)$op['id'], 3);
            }
        }

        $this->view('comptabilite/rapprochement_show', [
            'title'        => 'Rapprochement #' . $id . ' - ' . APP_NAME,
            'showNavbar'   => true,
            'import'       => $import,
            'operations'   => $operations,
            'stats'        => $stats,
            'suggestions'  => $suggestions,
            'statutFiltre' => $statutFiltre,
            'flash'        => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/rapprochementMatch/{operationId} — rapproche une opération avec une écriture.
     */
    public function rapprochementMatch($operationId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $operationId = (int)$operationId;
        $ecritureId  = (int)($_POST['ecriture_id'] ?? 0);
        $score       = !empty($_POST['score']) ? (int)$_POST['score'] : null;

        if (!$operationId || !$ecritureId) {
            $this->setFlash('error', "Paramètres manquants.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        // Vérification ownership
        $stmt = Database::getInstance()->getConnection()->prepare(
            "SELECT bo.import_id, bo.residence_id FROM bank_operations bo WHERE bo.id = ?"
        );
        $stmt->execute([$operationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !in_array((int)$row['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Opération non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        try {
            $rModel = new RapprochementBancaire();
            $rModel->rapprocher($operationId, $ecritureId, $this->getUserId(), $score);
            $this->setFlash('success', "Opération rapprochée.");
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('comptabilite/rapprochementShow/' . (int)$row['import_id']);
    }

    /**
     * POST /comptabilite/rapprochementUnmatch/{operationId} — défait un rapprochement.
     */
    public function rapprochementUnmatch($operationId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $operationId = (int)$operationId;
        $stmt = Database::getInstance()->getConnection()->prepare(
            "SELECT bo.import_id, bo.residence_id FROM bank_operations bo WHERE bo.id = ?"
        );
        $stmt->execute([$operationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !in_array((int)$row['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Opération non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        $rModel = new RapprochementBancaire();
        $rModel->defaire($operationId);
        $this->setFlash('success', "Rapprochement annulé.");
        $this->redirect('comptabilite/rapprochementShow/' . (int)$row['import_id']);
    }

    /**
     * POST /comptabilite/rapprochementIgnorer/{operationId}
     */
    public function rapprochementIgnorer($operationId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $operationId = (int)$operationId;
        $stmt = Database::getInstance()->getConnection()->prepare(
            "SELECT bo.import_id, bo.residence_id FROM bank_operations bo WHERE bo.id = ?"
        );
        $stmt->execute([$operationId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !in_array((int)$row['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Opération non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        $rModel = new RapprochementBancaire();
        if ($rModel->ignorer($operationId)) {
            $this->setFlash('success', "Opération marquée comme ignorée.");
        } else {
            $this->setFlash('error', "Action impossible.");
        }
        $this->redirect('comptabilite/rapprochementShow/' . (int)$row['import_id']);
    }

    /**
     * POST /comptabilite/rapprochementDelete/{importId} — supprime un import + ses opérations.
     */
    public function rapprochementDelete($importId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $importId = (int)$importId;
        $rModel = new RapprochementBancaire();
        $import = $rModel->findImport($importId);
        if (!$import || !in_array((int)$import['residence_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Import non accessible.");
            $this->redirect('comptabilite/rapprochement');
            return;
        }

        // Supprime aussi le fichier physique
        if (!empty($import['chemin_stockage'])) {
            $fullPath = ROOT_PATH . $import['chemin_stockage'];
            if (file_exists($fullPath)) @unlink($fullPath);
        }
        $rModel->deleteImport($importId);
        $this->setFlash('success', "Import supprimé.");
        $this->redirect('comptabilite/rapprochement');
    }

    // =================================================================
    //  BILAN / SIG / BALANCE / GRAND LIVRE / EXERCICES (Phase 9)
    // =================================================================

    /**
     * Helper : résout une période (résidence + année + mois) en bornes de date.
     * Si une résidence et une année sont passées, tente d'aligner sur l'exercice
     * comptable correspondant (sinon année civile).
     */
    private function resoudrePeriode(int $residenceId, int $annee, ?int $mois = null): array {
        if ($mois) {
            $debut = sprintf('%d-%02d-01', $annee, $mois);
            $fin   = date('Y-m-t', strtotime($debut));
            return ['debut' => $debut, 'fin' => $fin, 'libelle' => sprintf('%02d/%d', $mois, $annee)];
        }
        // Tenter de récupérer l'exercice
        if ($residenceId > 0) {
            $stmt = Database::getInstance()->getConnection()->prepare(
                "SELECT date_debut, date_fin FROM exercices_comptables WHERE copropriete_id = ? AND annee = ? LIMIT 1"
            );
            $stmt->execute([$residenceId, $annee]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row) {
                return ['debut' => $row['date_debut'], 'fin' => $row['date_fin'], 'libelle' => 'Exercice ' . $annee];
            }
        }
        return [
            'debut'   => sprintf('%d-01-01', $annee),
            'fin'     => sprintf('%d-12-31', $annee),
            'libelle' => 'Année ' . $annee,
        ];
    }

    /**
     * GET /comptabilite/balance — balance comptable agrégée par compte.
     */
    public function balance() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $periode        = $this->resoudrePeriode($sel, $annee, $mois);

        $eModel  = new Ecriture();
        $balance = $eModel->getBalance($filteredIds, $periode['debut'], $periode['fin']);

        // Totaux balance (vérification équilibre débit = crédit)
        $totalDebit  = array_sum(array_column($balance, 'total_debit'));
        $totalCredit = array_sum(array_column($balance, 'total_credit'));

        $this->view('comptabilite/balance', [
            'title'             => 'Balance comptable - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'mois'              => $mois,
            'periode'           => $periode,
            'balance'           => $balance,
            'totalDebit'        => $totalDebit,
            'totalCredit'       => $totalCredit,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * GET /comptabilite/grandLivre/{compteId?} — détail des écritures d'un compte.
     */
    public function grandLivre($compteId = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $mois           = !empty($_GET['mois']) ? (int)$_GET['mois'] : null;
        $periode        = $this->resoudrePeriode($sel, $annee, $mois);
        $compteId       = $compteId !== null ? (int)$compteId : null;

        $eModel    = new Ecriture();
        $comptes   = $eModel->getComptesActifs();
        $compteSel = null;
        if ($compteId !== null) {
            foreach ($comptes as $c) {
                if ((int)$c['id'] === $compteId) { $compteSel = $c; break; }
            }
        }

        $ecritures = $compteId !== null
            ? $eModel->getGrandLivre($filteredIds, $compteId, $periode['debut'], $periode['fin'])
            : [];

        $this->view('comptabilite/grand_livre', [
            'title'             => 'Grand livre - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'mois'              => $mois,
            'periode'           => $periode,
            'comptes'           => $comptes,
            'compteSel'         => $compteSel,
            'compteSelId'       => $compteId,
            'ecritures'         => $ecritures,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * GET /comptabilite/bilan — bilan actif/passif simplifié.
     */
    public function bilan() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $periode        = $this->resoudrePeriode($sel, $annee);

        $eModel = new Ecriture();
        $bilan  = $eModel->getBilan($filteredIds, $periode['debut'], $periode['fin']);

        $this->view('comptabilite/bilan', [
            'title'             => 'Bilan ' . $annee . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'periode'           => $periode,
            'bilan'             => $bilan,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * GET /comptabilite/sig — Soldes Intermédiaires de Gestion.
     */
    public function sig() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = (int)($_GET['annee'] ?? date('Y'));
        $periode        = $this->resoudrePeriode($sel, $annee);

        $eModel = new Ecriture();
        $sig    = $eModel->getSIG($filteredIds, $periode['debut'], $periode['fin']);

        $this->view('comptabilite/sig', [
            'title'             => 'SIG ' . $annee . ' - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'periode'           => $periode,
            'sig'               => $sig,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * GET /comptabilite/exercices — liste des exercices comptables.
     */
    public function exercices() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);

        $resAccessibles = $this->residencesAccessibles();
        $sel            = (int)($_GET['residence_id'] ?? 0);
        $filteredIds    = $sel ? [$sel] : $resAccessibles;
        $annee          = !empty($_GET['annee']) ? (int)$_GET['annee'] : null;
        $statut         = $_GET['statut'] ?? null;

        $eModel     = new Exercice();
        $exercices  = $eModel->listFiltered($filteredIds, $annee, $statut);

        // Enrichir chaque exercice avec ses stats
        foreach ($exercices as &$ex) {
            $ex['stats'] = $eModel->getStats((int)$ex['id']);
        }

        $this->view('comptabilite/exercices_index', [
            'title'             => 'Exercices comptables - ' . APP_NAME,
            'showNavbar'        => true,
            'residences'        => $this->residencesPourSelecteur(),
            'selectedResidence' => $sel,
            'annee'             => $annee,
            'statut'            => $statut,
            'statuts'           => Exercice::STATUTS,
            'exercices'         => $exercices,
            'flash'             => $this->getFlash(),
        ], true);
    }

    /**
     * POST /comptabilite/exerciceCreer — création d'un exercice annuel.
     */
    public function exerciceCreer() {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $resAccessibles = $this->residencesAccessibles();
        $residenceId    = (int)($_POST['residence_id'] ?? 0);
        $annee          = (int)($_POST['annee'] ?? 0);
        $budget         = (float)($_POST['budget_previsionnel'] ?? 0);
        $notes          = trim((string)($_POST['notes'] ?? ''));

        if (!in_array($residenceId, $resAccessibles, true) || $annee < 2020 || $annee > 2050) {
            $this->setFlash('error', 'Paramètres invalides.');
            $this->redirect('comptabilite/exercices');
            return;
        }

        try {
            $eModel = new Exercice();
            $newId  = $eModel->create($residenceId, $annee, null, null, $budget, $notes !== '' ? $notes : null);
            $this->setFlash('success', "Exercice $annee créé (#$newId).");
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('comptabilite/exercices');
    }

    /**
     * POST /comptabilite/exerciceCloturer/{id}
     */
    public function exerciceCloturer($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $id = (int)$id;
        $eModel = new Exercice();
        $exercice = $eModel->findById($id);
        if (!$exercice || !in_array((int)$exercice['copropriete_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Exercice introuvable ou non accessible.");
            $this->redirect('comptabilite/exercices');
            return;
        }

        try {
            $eModel->cloturer($id);
            $this->setFlash('success', "Exercice clôturé. Les écritures sont gelées.");
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('comptabilite/exercices');
    }

    /**
     * POST /comptabilite/exerciceReouvrir/{id} — admin uniquement.
     */
    public function exerciceReouvrir($id = null) {
        $this->requireAuth();
        $this->requireRole(['admin']); // exception : seul l'admin peut ré-ouvrir
        $this->verifyCsrf();

        $id = (int)$id;
        $eModel = new Exercice();
        $exercice = $eModel->findById($id);
        if (!$exercice) {
            $this->setFlash('error', "Exercice introuvable.");
            $this->redirect('comptabilite/exercices');
            return;
        }

        try {
            $eModel->reouvrir($id);
            $this->setFlash('success', "Exercice ré-ouvert. Les écritures sont dégelées.");
        } catch (RuntimeException $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('comptabilite/exercices');
    }

    /**
     * POST /comptabilite/exerciceArchiver/{id}
     */
    public function exerciceArchiver($id = null) {
        $this->requireAuth();
        $this->requireRole(self::ROLES);
        $this->verifyCsrf();

        $id = (int)$id;
        $eModel = new Exercice();
        $exercice = $eModel->findById($id);
        if (!$exercice || !in_array((int)$exercice['copropriete_id'], $this->residencesAccessibles(), true)) {
            $this->setFlash('error', "Exercice introuvable ou non accessible.");
            $this->redirect('comptabilite/exercices');
            return;
        }
        if ($eModel->archiver($id)) {
            $this->setFlash('success', "Exercice archivé.");
        } else {
            $this->setFlash('error', "Archivage impossible (l'exercice doit être clôturé d'abord).");
        }
        $this->redirect('comptabilite/exercices');
    }
}
