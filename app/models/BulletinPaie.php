<?php
/**
 * ====================================================================
 * SYND_GEST — Modèle BulletinPaie (Phase 4)
 * ====================================================================
 *
 * ⚠️ PILOTE VITRINE : les calculs sont réalistes mais non garantis
 * conformes à 100% au droit du travail français en évolution constante.
 * Le bulletin généré porte un watermark "PILOTE — Document non
 * contractuel". À ne pas remettre tel quel au salarié pour usage
 * légal/officiel sans validation expert-comptable.
 *
 * Architecture :
 *   - Snapshot des infos salarié au moment de la création (résiste aux
 *     modifs post-émission)
 *   - Workflow brouillon → valide → emis → annule
 *   - Calcul automatique brut / cotisations (sal+pat) / net
 *   - Heures importables depuis planning_shifts
 */

class BulletinPaie extends Model {

    // ─────────────────────────────────────────────────────────────────
    //  TAUX 2026 (à mettre à jour chaque année — janvier)
    // ─────────────────────────────────────────────────────────────────
    //
    // Source : URSSAF, AGIRC-ARRCO, BOSS (Bulletin Officiel Sécurité
    // Sociale). Valeurs simplifiées pour pilote — un système de paie
    // certifié distingue plus finement (plafonds, tranches, exonérations).

    /** Plafond mensuel de la Sécurité Sociale (PMSS) 2026. */
    public const PMSS_2026 = 3925.00;

    /** Heures mensuelles légales temps plein (35h × 52 / 12). */
    public const HEURES_MENSUELLES = 151.67;

    /** Taux cotisations salariales (% du brut sauf indication). */
    public const TAUX_SAL = [
        'maladie'             => 0.00,    // Cotisation maladie salariale supprimée depuis 2018 (sauf Alsace-Moselle)
        'vieillesse_dep'      => 0.40,    // Vieillesse déplafonnée
        'vieillesse_plaf'     => 6.90,    // Vieillesse plafonnée (sur PMSS)
        'csg_deductible'      => 6.80,    // CSG déductible (sur 98.25% du brut)
        'csg_non_deductible'  => 2.40,    // CSG non déductible (idem assiette)
        'crds'                => 0.50,    // CRDS (idem assiette)
        'agirc_arrco_t1'      => 4.15,    // AGIRC-ARRCO tranche 1 (jusqu'à PMSS)
        'agirc_arrco_t2'      => 9.86,    // AGIRC-ARRCO tranche 2 (PMSS à 8×PMSS)
    ];

    /** Taux cotisations patronales (% du brut sauf indication). */
    public const TAUX_PAT = [
        'maladie'              => 7.00,    // Maladie patronale (réduit si bas salaire — non géré ici)
        'vieillesse'           => 8.55,    // Vieillesse patronale (déplafonnée 1.90 + plafonnée 6.65)
        'alloc_familiales'     => 5.25,    // Allocations familiales
        'at_mp'                => 1.80,    // Accidents du travail (variable selon métier — moyenne services)
        'fnal'                 => 0.50,    // FNAL (Fonds national aide au logement) — taux <50 salariés
        'agirc_arrco_t1'       => 6.20,    // AGIRC-ARRCO patronal T1
        'agirc_arrco_t2'       => 14.57,   // AGIRC-ARRCO patronal T2
        'formation_pro'        => 0.55,    // Formation professionnelle <11 salariés
        'taxe_apprentissage'   => 0.68,    // Taxe d'apprentissage
    ];

    /** Coefficient assiette CSG/CRDS : 98.25% du brut + 100% des cot. patronales mutuelle/prévoyance. */
    public const ASSIETTE_CSG_COEF = 0.9825;

    public const STATUTS = [
        'brouillon' => 'Brouillon',
        'valide'    => 'Validé',
        'emis'      => 'Émis',
        'annule'    => 'Annulé',
    ];

    public const MOIS_LABELS = [
        1=>'Janvier',2=>'Février',3=>'Mars',4=>'Avril',5=>'Mai',6=>'Juin',
        7=>'Juillet',8=>'Août',9=>'Septembre',10=>'Octobre',11=>'Novembre',12=>'Décembre'
    ];

    // ─────────────────────────────────────────────────────────────────
    //  CRÉATION / CALCUL D'UN BULLETIN
    // ─────────────────────────────────────────────────────────────────

    /**
     * Crée un bulletin (brouillon) pour un user × période.
     *
     * @param array $heures ['heures_normales' => 151.67, 'heures_sup_25' => 0, 'heures_sup_50' => 0,
     *                       'heures_repos_compensateur' => 0, 'mode_heures_sup' => 'paiement']
     * @param array $extras ['primes' => 0, 'indemnites' => 0, 'taux_pas' => 0]
     * @return int  ID du bulletin créé
     */
    public function create(int $userId, int $annee, int $mois, array $heures = [], array $extras = [], ?int $createdBy = null): int {
        $fiche = $this->getFicheRh($userId);
        if (!$fiche) {
            throw new InvalidArgumentException("Aucune fiche RH trouvée pour l'utilisateur ID $userId. Créer la fiche RH d'abord.");
        }

        // Préventif unicité
        $exists = $this->findByUserPeriode($userId, $annee, $mois);
        if ($exists) {
            throw new RuntimeException("Un bulletin existe déjà pour " . self::MOIS_LABELS[$mois] . " $annee (id={$exists['id']}).");
        }

        // Calcul complet
        $tauxHoraire = (float)($fiche['taux_horaire_normal'] ?? 0);
        if ($tauxHoraire <= 0 && !empty($fiche['salaire_brut_base'])) {
            $tauxHoraire = round((float)$fiche['salaire_brut_base'] / self::HEURES_MENSUELLES, 4);
        }

        $hNorm = (float)($heures['heures_normales'] ?? self::HEURES_MENSUELLES);
        $hSup25 = (float)($heures['heures_sup_25'] ?? 0);
        $hSup50 = (float)($heures['heures_sup_50'] ?? 0);
        $hRepos = (float)($heures['heures_repos_compensateur'] ?? 0);
        $modeSup = $heures['mode_heures_sup'] ?? 'paiement';
        $tauxMaj25 = (float)($fiche['taux_majoration_25'] ?? 25);
        $tauxMaj50 = (float)($fiche['taux_majoration_50'] ?? 50);

        $calcul = self::calculerBulletin([
            'taux_horaire'        => $tauxHoraire,
            'heures_normales'     => $hNorm,
            'heures_sup_25'       => $hSup25,
            'heures_sup_50'       => $hSup50,
            'taux_maj_25'         => $tauxMaj25,
            'taux_maj_50'         => $tauxMaj50,
            'mode_heures_sup'     => $modeSup,
            'primes'              => (float)($extras['primes'] ?? 0),
            'indemnites'          => (float)($extras['indemnites'] ?? 0),
            'mutuelle_taux_sal'   => (float)($fiche['mutuelle_taux_salarial'] ?? 1.5),
            'mutuelle_taux_pat'   => (float)($fiche['mutuelle_taux_patronal'] ?? 1.5),
            'prevoyance_taux_sal' => (float)($fiche['prevoyance_taux_salarial'] ?? 0.5),
            'prevoyance_taux_pat' => (float)($fiche['prevoyance_taux_patronal'] ?? 0.5),
            'taux_pas'            => (float)($extras['taux_pas'] ?? 0),
        ]);

        // Snapshot des infos salarié
        $userObj = $this->db->prepare("SELECT prenom, nom FROM users WHERE id = ?");
        $userObj->execute([$userId]);
        $u = $userObj->fetch(PDO::FETCH_ASSOC) ?: ['prenom' => '', 'nom' => ''];

        $dateDebut = sprintf('%d-%02d-01', $annee, $mois);
        $dateFin   = date('Y-m-t', strtotime($dateDebut));

        $sql = "INSERT INTO bulletins_paie (
            user_id, salarie_rh_id, periode_annee, periode_mois, date_debut, date_fin,
            snapshot_nom, snapshot_prenom, snapshot_numero_ss, snapshot_type_contrat,
            snapshot_convention_nom, snapshot_convention_idcc, snapshot_categorie, snapshot_coefficient, snapshot_iban,
            heures_normales, heures_sup_25, heures_sup_50, heures_repos_compensateur, mode_heures_sup,
            taux_horaire_normal, taux_majoration_25, taux_majoration_50,
            brut_salaire_base, brut_heures_sup_25, brut_heures_sup_50, brut_primes, brut_indemnites, total_brut,
            cot_sal_urssaf_maladie, cot_sal_urssaf_vieillesse_dep, cot_sal_urssaf_vieillesse_plaf,
            cot_sal_csg_deductible, cot_sal_csg_non_deductible, cot_sal_crds,
            cot_sal_agirc_arrco_t1, cot_sal_agirc_arrco_t2, cot_sal_mutuelle, cot_sal_prevoyance,
            total_cotisations_salariales,
            cot_pat_urssaf_maladie, cot_pat_urssaf_vieillesse, cot_pat_urssaf_alloc_familiales,
            cot_pat_urssaf_at_mp, cot_pat_urssaf_fnal,
            cot_pat_agirc_arrco_t1, cot_pat_agirc_arrco_t2,
            cot_pat_formation_pro, cot_pat_taxe_apprentissage,
            cot_pat_mutuelle, cot_pat_prevoyance,
            total_cotisations_patronales,
            net_imposable, prelevement_source, taux_pas, net_a_payer, cout_employeur_total,
            statut, created_by
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?,
            ?, ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?, ?,
            ?,
            ?, ?, ?, ?, ?,
            'brouillon', ?
        )";

        try {
            $this->db->prepare($sql)->execute([
                $userId, (int)$fiche['id'], $annee, $mois, $dateDebut, $dateFin,
                $u['nom'], $u['prenom'], $fiche['numero_ss'], $fiche['type_contrat'],
                $fiche['convention_nom'] ?? null, $fiche['convention_idcc'] ?? null,
                $fiche['categorie'], $fiche['coefficient'], $fiche['iban'],
                $hNorm, $hSup25, $hSup50, $hRepos, $modeSup,
                $tauxHoraire, $tauxMaj25, $tauxMaj50,
                $calcul['brut_salaire_base'], $calcul['brut_heures_sup_25'], $calcul['brut_heures_sup_50'],
                $calcul['brut_primes'], $calcul['brut_indemnites'], $calcul['total_brut'],
                $calcul['cot_sal_urssaf_maladie'], $calcul['cot_sal_urssaf_vieillesse_dep'], $calcul['cot_sal_urssaf_vieillesse_plaf'],
                $calcul['cot_sal_csg_deductible'], $calcul['cot_sal_csg_non_deductible'], $calcul['cot_sal_crds'],
                $calcul['cot_sal_agirc_arrco_t1'], $calcul['cot_sal_agirc_arrco_t2'],
                $calcul['cot_sal_mutuelle'], $calcul['cot_sal_prevoyance'],
                $calcul['total_cotisations_salariales'],
                $calcul['cot_pat_urssaf_maladie'], $calcul['cot_pat_urssaf_vieillesse'], $calcul['cot_pat_urssaf_alloc_familiales'],
                $calcul['cot_pat_urssaf_at_mp'], $calcul['cot_pat_urssaf_fnal'],
                $calcul['cot_pat_agirc_arrco_t1'], $calcul['cot_pat_agirc_arrco_t2'],
                $calcul['cot_pat_formation_pro'], $calcul['cot_pat_taxe_apprentissage'],
                $calcul['cot_pat_mutuelle'], $calcul['cot_pat_prevoyance'],
                $calcul['total_cotisations_patronales'],
                $calcul['net_imposable'], $calcul['prelevement_source'], $calcul['taux_pas'], $calcul['net_a_payer'], $calcul['cout_employeur_total'],
                $createdBy,
            ]);
            $newId = (int)$this->db->lastInsertId();
            Logger::audit('bulletin_create', 'bulletins_paie', $newId, [
                'user_id'        => $userId,
                'periode'        => sprintf('%04d-%02d', $annee, $mois),
                'total_brut'     => (float)$calcul['total_brut'],
                'net_a_payer'    => (float)$calcul['net_a_payer'],
                'cout_employeur' => (float)$calcul['cout_employeur_total'],
            ], $createdBy);
            return $newId;
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), $sql);
            throw new RuntimeException("Erreur création bulletin : " . $e->getMessage());
        }
    }

    /**
     * Cœur du calcul de paie (statique, testable indépendamment).
     * Tous les montants en €, taux en %.
     */
    public static function calculerBulletin(array $p): array {
        $r = [];

        // ── BRUT ──
        $r['brut_salaire_base']    = round($p['heures_normales'] * $p['taux_horaire'], 2);
        $r['brut_heures_sup_25']   = $p['mode_heures_sup'] === 'paiement'
            ? round($p['heures_sup_25'] * $p['taux_horaire'] * (1 + $p['taux_maj_25'] / 100), 2)
            : 0.0;
        $r['brut_heures_sup_50']   = $p['mode_heures_sup'] === 'paiement'
            ? round($p['heures_sup_50'] * $p['taux_horaire'] * (1 + $p['taux_maj_50'] / 100), 2)
            : 0.0;
        $r['brut_primes']          = round($p['primes'] ?? 0, 2);
        $r['brut_indemnites']      = round($p['indemnites'] ?? 0, 2);
        $r['total_brut'] = round(
            $r['brut_salaire_base'] + $r['brut_heures_sup_25'] + $r['brut_heures_sup_50']
            + $r['brut_primes'] + $r['brut_indemnites'],
            2
        );

        $brut = $r['total_brut'];
        $brutPlafonne = min($brut, self::PMSS_2026);
        $brutDepasse  = max($brut - self::PMSS_2026, 0);

        // ── COTISATIONS SALARIALES ──
        $r['cot_sal_urssaf_maladie']       = round($brut * self::TAUX_SAL['maladie'] / 100, 2);
        $r['cot_sal_urssaf_vieillesse_dep'] = round($brut * self::TAUX_SAL['vieillesse_dep'] / 100, 2);
        $r['cot_sal_urssaf_vieillesse_plaf'] = round($brutPlafonne * self::TAUX_SAL['vieillesse_plaf'] / 100, 2);

        // CSG/CRDS sur 98.25% du brut + 100% des cotisations patronales prévoyance/mutuelle (simplifié)
        $assietteCsg = $brut * self::ASSIETTE_CSG_COEF;
        $r['cot_sal_csg_deductible']     = round($assietteCsg * self::TAUX_SAL['csg_deductible'] / 100, 2);
        $r['cot_sal_csg_non_deductible'] = round($assietteCsg * self::TAUX_SAL['csg_non_deductible'] / 100, 2);
        $r['cot_sal_crds']               = round($assietteCsg * self::TAUX_SAL['crds'] / 100, 2);

        $r['cot_sal_agirc_arrco_t1'] = round($brutPlafonne * self::TAUX_SAL['agirc_arrco_t1'] / 100, 2);
        $r['cot_sal_agirc_arrco_t2'] = round($brutDepasse * self::TAUX_SAL['agirc_arrco_t2'] / 100, 2);

        $r['cot_sal_mutuelle']    = round($brut * ($p['mutuelle_taux_sal'] ?? 0) / 100, 2);
        $r['cot_sal_prevoyance']  = round($brut * ($p['prevoyance_taux_sal'] ?? 0) / 100, 2);

        $r['total_cotisations_salariales'] = round(
            $r['cot_sal_urssaf_maladie'] + $r['cot_sal_urssaf_vieillesse_dep'] + $r['cot_sal_urssaf_vieillesse_plaf']
            + $r['cot_sal_csg_deductible'] + $r['cot_sal_csg_non_deductible'] + $r['cot_sal_crds']
            + $r['cot_sal_agirc_arrco_t1'] + $r['cot_sal_agirc_arrco_t2']
            + $r['cot_sal_mutuelle'] + $r['cot_sal_prevoyance'],
            2
        );

        // ── COTISATIONS PATRONALES ──
        $r['cot_pat_urssaf_maladie']         = round($brut * self::TAUX_PAT['maladie'] / 100, 2);
        $r['cot_pat_urssaf_vieillesse']      = round($brut * self::TAUX_PAT['vieillesse'] / 100, 2);
        $r['cot_pat_urssaf_alloc_familiales'] = round($brut * self::TAUX_PAT['alloc_familiales'] / 100, 2);
        $r['cot_pat_urssaf_at_mp']           = round($brut * self::TAUX_PAT['at_mp'] / 100, 2);
        $r['cot_pat_urssaf_fnal']            = round($brut * self::TAUX_PAT['fnal'] / 100, 2);
        $r['cot_pat_agirc_arrco_t1']         = round($brutPlafonne * self::TAUX_PAT['agirc_arrco_t1'] / 100, 2);
        $r['cot_pat_agirc_arrco_t2']         = round($brutDepasse * self::TAUX_PAT['agirc_arrco_t2'] / 100, 2);
        $r['cot_pat_formation_pro']          = round($brut * self::TAUX_PAT['formation_pro'] / 100, 2);
        $r['cot_pat_taxe_apprentissage']     = round($brut * self::TAUX_PAT['taxe_apprentissage'] / 100, 2);
        $r['cot_pat_mutuelle']               = round($brut * ($p['mutuelle_taux_pat'] ?? 0) / 100, 2);
        $r['cot_pat_prevoyance']             = round($brut * ($p['prevoyance_taux_pat'] ?? 0) / 100, 2);

        $r['total_cotisations_patronales'] = round(
            $r['cot_pat_urssaf_maladie'] + $r['cot_pat_urssaf_vieillesse'] + $r['cot_pat_urssaf_alloc_familiales']
            + $r['cot_pat_urssaf_at_mp'] + $r['cot_pat_urssaf_fnal']
            + $r['cot_pat_agirc_arrco_t1'] + $r['cot_pat_agirc_arrco_t2']
            + $r['cot_pat_formation_pro'] + $r['cot_pat_taxe_apprentissage']
            + $r['cot_pat_mutuelle'] + $r['cot_pat_prevoyance'],
            2
        );

        // ── NET ──
        // Net imposable = brut - cot.salariales déductibles (sans CSG non déductible ni CRDS)
        $cotDeductibles = $r['total_cotisations_salariales']
            - $r['cot_sal_csg_non_deductible']
            - $r['cot_sal_crds'];
        $r['net_imposable'] = round($brut - $cotDeductibles, 2);

        // Prélèvement à la source (taux personnalisé donné par l'admin fiscal)
        $r['taux_pas']            = (float)($p['taux_pas'] ?? 0);
        $r['prelevement_source']  = round($r['net_imposable'] * $r['taux_pas'] / 100, 2);

        // Net à payer = brut - cotisations salariales - PAS
        $r['net_a_payer'] = round($brut - $r['total_cotisations_salariales'] - $r['prelevement_source'], 2);

        // Coût employeur = brut + cotisations patronales
        $r['cout_employeur_total'] = round($brut + $r['total_cotisations_patronales'], 2);

        return $r;
    }

    // ─────────────────────────────────────────────────────────────────
    //  IMPORT HEURES depuis planning_shifts
    // ─────────────────────────────────────────────────────────────────

    /**
     * Calcule les heures travaillées du user pour un mois donné depuis planning_shifts.
     * Retourne ['heures_normales', 'heures_sup_25', 'heures_sup_50'].
     *
     * Règle simplifiée (loi 35h) : au-delà de 35h/semaine = sup 25% jusqu'à 43h, puis 50% au-delà.
     * Pour la simplicité du pilote on agrège mensuellement, sans découpage hebdomadaire fin.
     */
    public function importHeuresPlanning(int $userId, int $annee, int $mois): array {
        $dateMin = sprintf('%d-%02d-01', $annee, $mois);
        $dateMax = date('Y-m-t', strtotime($dateMin));
        try {
            // type_heures déjà rempli par le module Planning : 'normales' ou 'supplementaires'
            $sql = "SELECT type_heures, COALESCE(SUM(heures_calculees), 0) AS total
                    FROM planning_shifts
                    WHERE user_id = ?
                      AND DATE(date_debut) BETWEEN ? AND ?
                    GROUP BY type_heures";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $dateMin, $dateMax]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $hNorm = 0.0; $hSup = 0.0;
            foreach ($rows as $r) {
                if ($r['type_heures'] === 'supplementaires') $hSup += (float)$r['total'];
                else $hNorm += (float)$r['total'];
            }
            // Répartition simple sup : 8 premières heures à 25% (35→43), reste à 50% (>43)
            // En base mensuelle : 8h × 4.33 sem ≈ 35h sup à 25% max, le reste à 50%
            $cap25 = 35.0; // simplification mensuelle
            $hSup25 = min($hSup, $cap25);
            $hSup50 = max($hSup - $cap25, 0);

            return [
                'heures_normales' => round($hNorm, 2),
                'heures_sup_25'   => round($hSup25, 2),
                'heures_sup_50'   => round($hSup50, 2),
            ];
        } catch (PDOException $e) {
            $this->logError($e->getMessage(), '', [$userId, $annee, $mois]);
            return ['heures_normales' => self::HEURES_MENSUELLES, 'heures_sup_25' => 0, 'heures_sup_50' => 0];
        }
    }

    // ─────────────────────────────────────────────────────────────────
    //  WORKFLOW
    // ─────────────────────────────────────────────────────────────────

    public function valider(int $bulletinId, int $userId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE bulletins_paie SET statut='valide', valide_par=?, valide_at=NOW() WHERE id=? AND statut='brouillon'");
            $stmt->execute([$userId, $bulletinId]);
            $changed = $stmt->rowCount() > 0;
            if ($changed) {
                Logger::audit('bulletin_valide', 'bulletins_paie', $bulletinId, [], $userId);
            }
            return $changed;
        } catch (PDOException $e) { return false; }
    }

    public function emettre(int $bulletinId): bool {
        try {
            $stmt = $this->db->prepare("UPDATE bulletins_paie SET statut='emis', emis_at=NOW() WHERE id=? AND statut='valide'");
            $stmt->execute([$bulletinId]);
            $changed = $stmt->rowCount() > 0;
            if ($changed) {
                Logger::audit('bulletin_emis', 'bulletins_paie', $bulletinId, [
                    'note' => 'Bulletin visible désormais par le salarié',
                ]);
            }
            return $changed;
        } catch (PDOException $e) { return false; }
    }

    public function annuler(int $bulletinId, string $motif): bool {
        try {
            $stmt = $this->db->prepare("UPDATE bulletins_paie SET statut='annule', annule_at=NOW(), annule_motif=? WHERE id=? AND statut != 'annule'");
            $stmt->execute([$motif, $bulletinId]);
            $changed = $stmt->rowCount() > 0;
            if ($changed) {
                Logger::audit('bulletin_annule', 'bulletins_paie', $bulletinId, [
                    'motif' => mb_substr($motif, 0, 200),
                ]);
            }
            return $changed;
        } catch (PDOException $e) { return false; }
    }

    public function deleteBrouillon(int $bulletinId): bool {
        try {
            $stmt = $this->db->prepare("DELETE FROM bulletins_paie WHERE id=? AND statut='brouillon'");
            $stmt->execute([$bulletinId]);
            $deleted = $stmt->rowCount() > 0;
            if ($deleted) {
                Logger::audit('bulletin_delete_brouillon', 'bulletins_paie', $bulletinId, []);
            }
            return $deleted;
        } catch (PDOException $e) { return false; }
    }

    // ─────────────────────────────────────────────────────────────────
    //  LECTURE
    // ─────────────────────────────────────────────────────────────────

    public function findById(int $id): ?array {
        try {
            $stmt = $this->db->prepare("SELECT b.*, u.email FROM bulletins_paie b LEFT JOIN users u ON u.id = b.user_id WHERE b.id = ?");
            $stmt->execute([$id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) { return null; }
    }

    public function findByUserPeriode(int $userId, int $annee, int $mois): ?array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bulletins_paie WHERE user_id = ? AND periode_annee = ? AND periode_mois = ?");
            $stmt->execute([$userId, $annee, $mois]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) { return null; }
    }

    public function listAll(?int $annee = null, ?int $mois = null, ?string $statut = null): array {
        $sql = "SELECT b.*, u.username FROM bulletins_paie b LEFT JOIN users u ON u.id = b.user_id WHERE 1=1";
        $params = [];
        if ($annee)  { $sql .= " AND b.periode_annee = ?";  $params[] = $annee; }
        if ($mois)   { $sql .= " AND b.periode_mois = ?";   $params[] = $mois; }
        if ($statut) { $sql .= " AND b.statut = ?";          $params[] = $statut; }
        $sql .= " ORDER BY b.periode_annee DESC, b.periode_mois DESC, b.snapshot_nom, b.snapshot_prenom";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    public function listByUser(int $userId): array {
        try {
            $stmt = $this->db->prepare("SELECT * FROM bulletins_paie WHERE user_id = ? AND statut IN ('valide','emis') ORDER BY periode_annee DESC, periode_mois DESC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) { return []; }
    }

    /**
     * Charge la fiche RH d'un user (jointe à conventions_collectives) — utilisée à la création.
     */
    private function getFicheRh(int $userId): ?array {
        try {
            $stmt = $this->db->prepare(
                "SELECT s.*, cc.nom AS convention_nom, cc.idcc AS convention_idcc
                 FROM salaries_rh s
                 LEFT JOIN conventions_collectives cc ON cc.id = s.convention_collective_id
                 WHERE s.user_id = ? LIMIT 1"
            );
            $stmt->execute([$userId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) { return null; }
    }
}
