-- ====================================================================
-- Migration 033 — Bulletins de paie (Phase 4 du module Comptabilité)
-- ====================================================================
-- ⚠️ PILOTE VITRINE : les bulletins générés portent un watermark
-- "PILOTE — Document non contractuel". À ne pas remettre tels quels au
-- salarié pour usage légal/officiel. Les calculs sont réalistes mais
-- nécessitent validation expert-comptable + intégration logiciel paie
-- certifié pour usage en production.
--
-- Tables créées :
--   - bulletins_paie : un bulletin par salarié × période (mois)
--
-- Workflow : brouillon → valide (par comptable) → emis (envoyé salarié) → annule
--
-- Stockage : pas de PDF physique pour l'instant (HTML imprimable côté navigateur).
-- Si DomPDF ajouté en Phase 4b : uploads/bulletins/{user_id}/{YYYY-MM}.pdf
-- ====================================================================

CREATE TABLE IF NOT EXISTS bulletins_paie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'Salarié concerné',
    salarie_rh_id INT NOT NULL COMMENT 'FK vers fiche RH (snapshot au moment de la création)',

    -- Période
    periode_annee INT NOT NULL,
    periode_mois INT NOT NULL CHECK (periode_mois BETWEEN 1 AND 12),
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,

    -- Snapshot infos salarié (résiste aux modifications post-émission)
    snapshot_nom VARCHAR(150) NOT NULL,
    snapshot_prenom VARCHAR(150) NOT NULL,
    snapshot_numero_ss VARCHAR(20) DEFAULT NULL,
    snapshot_type_contrat VARCHAR(30) DEFAULT NULL,
    snapshot_convention_nom VARCHAR(200) DEFAULT NULL,
    snapshot_convention_idcc VARCHAR(10) DEFAULT NULL,
    snapshot_categorie VARCHAR(30) DEFAULT NULL,
    snapshot_coefficient VARCHAR(20) DEFAULT NULL,
    snapshot_iban VARCHAR(34) DEFAULT NULL,

    -- Heures travaillées
    heures_normales DECIMAL(7,2) DEFAULT 0 COMMENT 'Heures de base (151.67 pour temps plein)',
    heures_sup_25 DECIMAL(7,2) DEFAULT 0 COMMENT 'Heures sup à 25%',
    heures_sup_50 DECIMAL(7,2) DEFAULT 0 COMMENT 'Heures sup à 50%',
    heures_repos_compensateur DECIMAL(7,2) DEFAULT 0 COMMENT 'Si repos compensateur au lieu de paiement (Q B3)',
    -- Choix repos OU paiement (par bulletin selon souhait salarié)
    mode_heures_sup ENUM('paiement', 'repos_compensateur') DEFAULT 'paiement',

    -- Taux horaires utilisés (snapshot)
    taux_horaire_normal DECIMAL(8,4) NOT NULL,
    taux_majoration_25 DECIMAL(5,2) DEFAULT 25.00,
    taux_majoration_50 DECIMAL(5,2) DEFAULT 50.00,

    -- Brut
    brut_salaire_base DECIMAL(10,2) DEFAULT 0,
    brut_heures_sup_25 DECIMAL(10,2) DEFAULT 0,
    brut_heures_sup_50 DECIMAL(10,2) DEFAULT 0,
    brut_primes DECIMAL(10,2) DEFAULT 0 COMMENT 'Primes diverses (anciennete, perf, etc.)',
    brut_indemnites DECIMAL(10,2) DEFAULT 0 COMMENT 'Indemnites (transport, repas, etc.)',
    total_brut DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Cotisations salariales (à déduire du brut)
    cot_sal_urssaf_maladie DECIMAL(10,2) DEFAULT 0,
    cot_sal_urssaf_vieillesse_dep DECIMAL(10,2) DEFAULT 0,
    cot_sal_urssaf_vieillesse_plaf DECIMAL(10,2) DEFAULT 0,
    cot_sal_csg_deductible DECIMAL(10,2) DEFAULT 0,
    cot_sal_csg_non_deductible DECIMAL(10,2) DEFAULT 0,
    cot_sal_crds DECIMAL(10,2) DEFAULT 0,
    cot_sal_agirc_arrco_t1 DECIMAL(10,2) DEFAULT 0,
    cot_sal_agirc_arrco_t2 DECIMAL(10,2) DEFAULT 0,
    cot_sal_mutuelle DECIMAL(10,2) DEFAULT 0,
    cot_sal_prevoyance DECIMAL(10,2) DEFAULT 0,
    total_cotisations_salariales DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Cotisations patronales (à charge employeur, pas déduites du brut)
    cot_pat_urssaf_maladie DECIMAL(10,2) DEFAULT 0,
    cot_pat_urssaf_vieillesse DECIMAL(10,2) DEFAULT 0,
    cot_pat_urssaf_alloc_familiales DECIMAL(10,2) DEFAULT 0,
    cot_pat_urssaf_at_mp DECIMAL(10,2) DEFAULT 0 COMMENT 'Accidents du travail / maladies pro',
    cot_pat_urssaf_fnal DECIMAL(10,2) DEFAULT 0,
    cot_pat_agirc_arrco_t1 DECIMAL(10,2) DEFAULT 0,
    cot_pat_agirc_arrco_t2 DECIMAL(10,2) DEFAULT 0,
    cot_pat_formation_pro DECIMAL(10,2) DEFAULT 0,
    cot_pat_taxe_apprentissage DECIMAL(10,2) DEFAULT 0,
    cot_pat_mutuelle DECIMAL(10,2) DEFAULT 0,
    cot_pat_prevoyance DECIMAL(10,2) DEFAULT 0,
    total_cotisations_patronales DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Net
    net_imposable DECIMAL(10,2) NOT NULL DEFAULT 0,
    prelevement_source DECIMAL(10,2) DEFAULT 0,
    taux_pas DECIMAL(6,3) DEFAULT 0 COMMENT 'Taux du Prélèvement à la source',
    net_a_payer DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Coût employeur
    cout_employeur_total DECIMAL(10,2) NOT NULL DEFAULT 0,

    -- Workflow
    statut ENUM('brouillon', 'valide', 'emis', 'annule') DEFAULT 'brouillon',
    valide_par INT DEFAULT NULL,
    valide_at DATETIME DEFAULT NULL,
    emis_at DATETIME DEFAULT NULL,
    annule_at DATETIME DEFAULT NULL,
    annule_motif VARCHAR(255) DEFAULT NULL,

    -- Métadonnées
    notes TEXT DEFAULT NULL,
    pdf_path VARCHAR(500) DEFAULT NULL COMMENT 'Chemin PDF si genere (Phase 4b)',
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- Contraintes
    UNIQUE KEY uk_user_periode (user_id, periode_annee, periode_mois),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (salarie_rh_id) REFERENCES salaries_rh(id) ON DELETE CASCADE,
    FOREIGN KEY (valide_par) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_bulletins_periode ON bulletins_paie(periode_annee, periode_mois);
CREATE INDEX idx_bulletins_statut ON bulletins_paie(statut, periode_annee);
CREATE INDEX idx_bulletins_user_statut ON bulletins_paie(user_id, statut);

-- ─────────────────────────────────────────────────────────────────────
-- Enregistrement migration
-- ─────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO migrations (migration, batch, applied_at)
VALUES ('033_bulletins_paie', (SELECT COALESCE(MAX(b)+1, 1) FROM (SELECT MAX(batch) AS b FROM migrations) AS x), NOW());
