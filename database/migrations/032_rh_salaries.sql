-- ====================================================================
-- Migration 032 — RH Salariés (Phase 3 du module Comptabilité)
-- ====================================================================
-- Création des fondations pour le module Bulletins de paie (Phase 4).
--
-- Tables créées :
--   - conventions_collectives : référentiel des CCN françaises (IDCC)
--   - salaries_rh : fiche RH 1:1 par user staff (numéro SS, contrat, salaire,
--     IBAN, taux mutuelle/prévoyance, etc.)
--
-- Périmètre :
--   - Une fiche par user (relation 1:1 stricte via UNIQUE KEY user_id)
--   - Multi-conventions activé (Q2 du brief Phase 0)
--   - Choix repos compensateur ou paiement (Q3) : géré côté bulletin Phase 4
-- ====================================================================

-- ─────────────────────────────────────────────────────────────────────
-- 1. RÉFÉRENTIEL CONVENTIONS COLLECTIVES (IDCC France)
-- ─────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS conventions_collectives (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE COMMENT 'Slug interne (ex: services_personne, hcr)',
    nom VARCHAR(200) NOT NULL,
    idcc VARCHAR(10) DEFAULT NULL COMMENT 'Identifiant Convention Collective officiel',
    description TEXT DEFAULT NULL,
    -- Modules principaux concernés (CSV pour filtrage UI)
    modules_concernes VARCHAR(200) DEFAULT NULL COMMENT 'CSV: restauration,menage,jardinage,maintenance,admin',
    -- Heures sup conventionnelles (% au-dessus du légal 25/50)
    seuil_majoration_25 INT DEFAULT 8 COMMENT 'Heures à 25% au-delà de N heures hebdo',
    seuil_majoration_50 INT DEFAULT 43 COMMENT 'Heures à 50% au-delà de N heures hebdo',
    -- Charges sociales totales approximatives (info indicative pour calculs rapides)
    taux_charges_patronales_total DECIMAL(5,2) DEFAULT 42.00 COMMENT 'Taux global patronal moyen',
    taux_charges_salariales_total DECIMAL(5,2) DEFAULT 22.00 COMMENT 'Taux global salarial moyen',
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed des conventions usuelles pour résidences seniors
INSERT IGNORE INTO conventions_collectives
    (code, nom, idcc, description, modules_concernes, seuil_majoration_25, seuil_majoration_50, taux_charges_patronales_total, taux_charges_salariales_total)
VALUES
    ('services_personne',   'Services à la personne',                  '3370', 'CCN entreprises de services à la personne — applicable au personnel d''aide aux résidents seniors', 'menage,services,admin', 35, 43, 42.00, 22.50),
    ('hcr',                 'Hôtels Cafés Restaurants (HCR)',          '1979', 'CCN HCR — applicable au personnel restauration des résidences services', 'restauration', 35, 43, 43.00, 22.00),
    ('aide_domicile',       'Aide à domicile (BAD)',                   '2941', 'Branche aide à domicile — pour intervenants d''aide aux personnes', 'menage,services', 35, 43, 41.50, 22.00),
    ('jardinage_paysage',   'Entreprises du paysage',                  '7018', 'CCN ouvriers du paysage — applicable jardiniers résidence', 'jardinage', 35, 43, 42.50, 22.50),
    ('immobilier',          'Immobilier (gestion immobilière)',        '1527', 'CCN de l''immobilier — applicable au personnel administratif et direction résidence', 'admin,services', 35, 43, 42.00, 22.00),
    ('btp_ouvriers',        'BTP — Ouvriers (-10 salariés)',           '1596', 'CCN bâtiment — applicable techniciens maintenance/travaux', 'maintenance', 35, 43, 43.50, 22.50),
    ('cadres_btp',          'BTP — ETAM et Cadres',                    '2609', 'Encadrement BTP — pour responsables techniques', 'maintenance', 35, 43, 44.00, 22.50),
    ('default',             'Convention par défaut (générique)',        NULL, 'Convention de repli si aucune CCN spécifique applicable', NULL, 35, 43, 42.00, 22.00);

-- ─────────────────────────────────────────────────────────────────────
-- 2. TABLE FICHE RH SALARIÉ (1:1 avec users)
-- ─────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS salaries_rh (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE COMMENT 'Relation 1:1 avec users',

    -- Identité administrative
    numero_ss VARCHAR(20) DEFAULT NULL COMMENT '15 chiffres + clé (norme française)',
    date_embauche DATE DEFAULT NULL,
    date_sortie DATE DEFAULT NULL,
    motif_sortie VARCHAR(100) DEFAULT NULL,

    -- Contrat
    type_contrat ENUM('CDI', 'CDD', 'Apprentissage', 'Stage', 'Interim', 'Intermittent', 'Autre') DEFAULT 'CDI',
    motif_cdd VARCHAR(255) DEFAULT NULL,
    cdd_date_fin DATE DEFAULT NULL,
    temps_travail ENUM('temps_plein', 'temps_partiel') DEFAULT 'temps_plein',
    quotite_temps_partiel DECIMAL(5,2) DEFAULT NULL COMMENT '% du temps plein si temps partiel (ex: 80.00)',

    -- Convention collective
    convention_collective_id INT DEFAULT NULL,
    coefficient VARCHAR(20) DEFAULT NULL COMMENT 'Niveau / coefficient hiérarchique CCN',
    categorie ENUM('ouvrier', 'employe', 'agent_maitrise', 'cadre') DEFAULT 'employe',

    -- Rémunération
    salaire_brut_base DECIMAL(10,2) DEFAULT NULL COMMENT 'Base mensuelle brute pour temps plein',
    taux_horaire_normal DECIMAL(8,4) DEFAULT NULL COMMENT 'Taux horaire de base — auto-calculé si NULL : salaire_brut / 151.67h',
    taux_majoration_25 DECIMAL(5,2) DEFAULT 25.00 COMMENT 'Majoration heures sup 25%',
    taux_majoration_50 DECIMAL(5,2) DEFAULT 50.00 COMMENT 'Majoration heures sup 50%',

    -- Coordonnées bancaires (modifiables par le salarié lui-même)
    iban VARCHAR(34) DEFAULT NULL,
    bic VARCHAR(11) DEFAULT NULL,

    -- Mutuelle / Prévoyance (taux par défaut, surchargeable au bulletin)
    mutuelle_taux_salarial DECIMAL(5,2) DEFAULT 1.50,
    mutuelle_taux_patronal DECIMAL(5,2) DEFAULT 1.50,
    prevoyance_taux_salarial DECIMAL(5,2) DEFAULT 0.50,
    prevoyance_taux_patronal DECIMAL(5,2) DEFAULT 0.50,

    -- Métadonnées
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (convention_collective_id) REFERENCES conventions_collectives(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE INDEX idx_salaries_rh_contrat ON salaries_rh(type_contrat, date_sortie);
CREATE INDEX idx_salaries_rh_convention ON salaries_rh(convention_collective_id);

-- ─────────────────────────────────────────────────────────────────────
-- 3. ENREGISTREMENT migration
-- ─────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO migrations (migration, batch, applied_at)
VALUES ('032_rh_salaries', (SELECT COALESCE(MAX(b)+1, 1) FROM (SELECT MAX(batch) AS b FROM migrations) AS x), NOW());
