-- ====================================================================
-- Migration 030 — Comptabilité unifiée (Phase 0)
-- ====================================================================
-- Refonte de l'architecture comptable :
--
--  - DROP de l'ancienne `ecritures_comptables` (style copro syndic, vide)
--  - CREATE de la nouvelle `ecritures_comptables` unifiée
--  - Enrichissement de `comptes_comptables` (type 'tiers', taux_tva_par_defaut)
--  - Seed PCG simplifié résidence-services
--
-- Phase 1 (migration suivante) prendra en charge la migration des données
-- existantes des 3 tables `jardin_comptabilite`, `menage_comptabilite`,
-- `rest_comptabilite` vers cette nouvelle table, puis leur DROP.
--
-- Maintenance n'a pas de table compta dédiée — elle agrège depuis
-- maintenance_interventions, chantiers, ascenseur_journal directement
-- (voir MaintenanceComptabilite model). À Phase 1 elle pourra optionnellement
-- pousser ses agrégats dans ecritures_comptables également.
-- ====================================================================

-- ─────────────────────────────────────────────────────────────────────
-- 1. SUPPRESSION ancienne ecritures_comptables (vide, vérifié)
-- ─────────────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS ecritures_comptables;

-- ─────────────────────────────────────────────────────────────────────
-- 2. ENRICHISSEMENT comptes_comptables
-- ─────────────────────────────────────────────────────────────────────

-- Ajout type 'tiers' (pour comptes 4xx clients/fournisseurs/personnel)
ALTER TABLE comptes_comptables
    MODIFY COLUMN `type` ENUM('actif','passif','charge','produit','tiers') NOT NULL;

-- Taux TVA par défaut associé au compte (utile pour CA3)
ALTER TABLE comptes_comptables
    ADD COLUMN IF NOT EXISTS taux_tva_par_defaut DECIMAL(5,2) DEFAULT NULL AFTER `actif`,
    ADD COLUMN IF NOT EXISTS code_module VARCHAR(40) DEFAULT NULL COMMENT 'Module suggéré pour ce compte' AFTER taux_tva_par_defaut;

-- ─────────────────────────────────────────────────────────────────────
-- 3. NOUVELLE TABLE ecritures_comptables — schéma unifié
-- ─────────────────────────────────────────────────────────────────────

CREATE TABLE ecritures_comptables (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Périmètre
    residence_id INT NOT NULL,
    exercice_id INT DEFAULT NULL COMMENT 'NULL si exercice non créé pour cette année',

    -- Module source — discrimine le dashboard
    module_source ENUM(
        'jardinage','menage','restauration','maintenance',
        'loyer_proprio','loyer_resident','services','hote',
        'rh_paie','admin','sinistre','autre'
    ) NOT NULL,

    -- Catégorie business (libre, dépend du module — ex: achat_fournisseur, recolte_miel, salaire_brut)
    categorie VARCHAR(80) NOT NULL,

    -- Type d'écriture
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,

    -- Montants (séparés pour gestion TVA et CA3)
    montant_ht DECIMAL(12,2) NOT NULL,
    taux_tva DECIMAL(5,2) DEFAULT NULL COMMENT '5.5 / 10 / 20 / NULL si exonéré',
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL,

    -- Plan comptable PCG (FK vers comptes_comptables)
    compte_comptable_id INT DEFAULT NULL,

    -- Référence externe polymorphe (vers commande, visite, bulletin, etc.)
    reference_externe_type VARCHAR(40) DEFAULT NULL,
    reference_externe_id INT DEFAULT NULL,

    -- Imputation analytique optionnelle (par espace jardin, par salarié, par chantier, etc.)
    imputation_type VARCHAR(40) DEFAULT NULL,
    imputation_id INT DEFAULT NULL,

    -- Métadonnées
    libelle VARCHAR(255) NOT NULL,
    notes TEXT DEFAULT NULL,
    piece_justificative VARCHAR(500) DEFAULT NULL COMMENT 'Chemin fichier PDF (uploads/comptabilite/)',

    -- Origine et workflow
    auto_genere TINYINT(1) DEFAULT 0 COMMENT '1 = créée automatiquement par un module, 0 = saisie manuelle',
    statut ENUM('brouillon','validee','cloturee') DEFAULT 'validee' COMMENT 'cloturee = exercice clos = lecture seule',

    -- Audit
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,

    -- FKs
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (exercice_id) REFERENCES exercices_comptables(id) ON DELETE SET NULL,
    FOREIGN KEY (compte_comptable_id) REFERENCES comptes_comptables(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ─────────────────────────────────────────────────────────────────────
-- 4. INDEX de performance pour les requêtes dashboard
-- ─────────────────────────────────────────────────────────────────────

CREATE INDEX idx_ec_residence_date ON ecritures_comptables(residence_id, date_ecriture);
CREATE INDEX idx_ec_module_date    ON ecritures_comptables(module_source, date_ecriture);
CREATE INDEX idx_ec_type_date      ON ecritures_comptables(type_ecriture, date_ecriture);
CREATE INDEX idx_ec_reference      ON ecritures_comptables(reference_externe_type, reference_externe_id);
CREATE INDEX idx_ec_imputation     ON ecritures_comptables(imputation_type, imputation_id);
CREATE INDEX idx_ec_exercice       ON ecritures_comptables(exercice_id, statut);
CREATE INDEX idx_ec_compte         ON ecritures_comptables(compte_comptable_id, date_ecriture);

-- ─────────────────────────────────────────────────────────────────────
-- 5. SEED PCG simplifié résidence-services seniors
-- (idempotent — INSERT IGNORE sur numero_compte unique)
-- ─────────────────────────────────────────────────────────────────────

ALTER TABLE comptes_comptables ADD UNIQUE KEY IF NOT EXISTS uk_numero_compte (numero_compte);

INSERT IGNORE INTO comptes_comptables (numero_compte, libelle, type, parent_id, actif, taux_tva_par_defaut, code_module) VALUES
-- ─── Classe 1 — Capitaux ───
('101', 'Capital social',                           'passif',  NULL, 1, NULL, NULL),
('120', 'Résultat de l''exercice (bénéfice)',       'passif',  NULL, 1, NULL, NULL),
('129', 'Résultat de l''exercice (perte)',          'actif',   NULL, 1, NULL, NULL),

-- ─── Classe 4 — Tiers ───
('401', 'Fournisseurs',                              'tiers',   NULL, 1, NULL, NULL),
('411', 'Clients (résidents seniors)',               'tiers',   NULL, 1, NULL, 'loyer_resident'),
('421', 'Personnel — rémunérations dues',            'tiers',   NULL, 1, NULL, 'rh_paie'),
('431', 'URSSAF',                                    'tiers',   NULL, 1, NULL, 'rh_paie'),
('437', 'AGIRC-ARRCO / autres organismes sociaux',   'tiers',   NULL, 1, NULL, 'rh_paie'),
('44566', 'TVA déductible sur autres biens',         'actif',   NULL, 1, NULL, NULL),
('44571', 'TVA collectée',                           'passif',  NULL, 1, NULL, NULL),
('455', 'Associés / Propriétaires',                  'tiers',   NULL, 1, NULL, 'loyer_proprio'),

-- ─── Classe 5 — Trésorerie ───
('512', 'Banque',                                    'actif',   NULL, 1, NULL, NULL),
('531', 'Caisse',                                    'actif',   NULL, 1, NULL, NULL),

-- ─── Classe 6 — Charges ───
('601', 'Achats de matières premières (restauration)','charge', NULL, 1, 5.50,  'restauration'),
('602', 'Achats stockés — fournitures (jardinage, ménage)','charge', NULL, 1, 20.00, NULL),
('606', 'Achats non stockés (eau, électricité)',     'charge',  NULL, 1, 20.00, 'admin'),
('611', 'Sous-traitance générale',                   'charge',  NULL, 1, 20.00, NULL),
('613', 'Locations',                                 'charge',  NULL, 1, 20.00, NULL),
('615', 'Entretien et réparations',                  'charge',  NULL, 1, 20.00, 'maintenance'),
('616', 'Primes d''assurance',                       'charge',  NULL, 1, NULL,  'admin'),
('618', 'Documentation, séminaires',                 'charge',  NULL, 1, 20.00, NULL),
('621', 'Personnel extérieur (intérim)',             'charge',  NULL, 1, 20.00, 'rh_paie'),
('622', 'Honoraires (avocats, comptable)',           'charge',  NULL, 1, 20.00, 'admin'),
('625', 'Déplacements, missions, réceptions',        'charge',  NULL, 1, 20.00, NULL),
('626', 'Frais postaux et télécommunications',       'charge',  NULL, 1, 20.00, 'admin'),
('631', 'Impôts et taxes (taxe foncière)',           'charge',  NULL, 1, NULL,  'admin'),
('641', 'Rémunérations du personnel',                'charge',  NULL, 1, NULL,  'rh_paie'),
('645', 'Charges de sécurité sociale et prévoyance', 'charge',  NULL, 1, NULL,  'rh_paie'),
('647', 'Autres charges sociales (mutuelle, formation)','charge',NULL, 1, NULL, 'rh_paie'),

-- ─── Classe 7 — Produits ───
('701', 'Vente de produits finis',                   'produit', NULL, 1, 20.00, NULL),
('706', 'Prestations de services (résidents)',       'produit', NULL, 1, 10.00, 'services'),
('7061', 'Loyers résidents (logement)',              'produit', NULL, 1, NULL,  'loyer_resident'),
('7062', 'Forfaits services résidents',              'produit', NULL, 1, 10.00, 'services'),
('7063', 'Repas résidents',                          'produit', NULL, 1, 10.00, 'restauration'),
('7064', 'Repas hôtes / passages',                   'produit', NULL, 1, 10.00, 'restauration'),
('7065', 'Laverie résidents',                        'produit', NULL, 1, 20.00, 'menage'),
('7066', 'Hébergement court séjour (hôtes)',         'produit', NULL, 1, 10.00, 'hote'),
('7067', 'Récolte miel (jardinage)',                 'produit', NULL, 1,  5.50, 'jardinage'),
('708', 'Produits annexes',                          'produit', NULL, 1, 20.00, 'autre'),
('758', 'Produits exceptionnels (sinistres encaissés)','produit',NULL, 1, NULL, 'sinistre');

-- ─────────────────────────────────────────────────────────────────────
-- 6. ENREGISTREMENT migration dans la table de suivi
-- ─────────────────────────────────────────────────────────────────────

INSERT IGNORE INTO migrations (migration, batch, applied_at)
VALUES ('030_compta_unifiee', (SELECT COALESCE(MAX(b)+1, 1) FROM (SELECT MAX(batch) AS b FROM migrations) AS x), NOW());
