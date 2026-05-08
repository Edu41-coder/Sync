-- ====================================================================
-- Migration 037 — Quittances résidents + Gestion des impayés (Phase 13)
-- ====================================================================
-- Drop des 2 tables legacy syndic non utilisées (0 ligne, 0 référence
-- code) et création de 2 nouvelles tables adaptées au modèle Domitys :
--   - quittances_residents : 1 snapshot mensuel par occupation
--   - impayes_relances     : workflow 3 niveaux (FK polymorphe vers
--                             quittance résident OU paiement proprio
--                             OU facture fournisseur)
--
-- Les quittances propriétaires utilisent la table existante
-- `paiements_loyers_exploitant` (44 lignes en BDD) — pas besoin de
-- nouvelle table, juste un endpoint printable.
-- ====================================================================

-- 1. DROP des tables legacy (0 ligne, 0 ref code — vérifié)
DROP TABLE IF EXISTS quittances;
DROP TABLE IF EXISTS relances;

-- 2. Quittances résidents (snapshot mensuel par occupation)
CREATE TABLE quittances_residents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    occupation_id INT NOT NULL,
    residence_id INT NOT NULL COMMENT 'Dénormalisé pour filtrage rapide',
    resident_id INT NOT NULL COMMENT 'Dénormalisé pour audit RGPD',

    -- Période
    periode_mois TINYINT NOT NULL COMMENT '1-12',
    periode_annee SMALLINT NOT NULL,

    -- Identifiant unique externe (affiché sur le PDF)
    numero_quittance VARCHAR(40) NOT NULL UNIQUE COMMENT 'Format: QR-YYYY-MM-{residence}-{seq}',

    -- Snapshot des montants au moment de l'émission (figés légalement)
    loyer_mensuel DECIMAL(10,2) NOT NULL,
    charges_mensuelles DECIMAL(10,2) NOT NULL DEFAULT 0,
    services_supp DECIMAL(10,2) NOT NULL DEFAULT 0,
    montant_apl DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Aide perso logement',
    montant_apa DECIMAL(10,2) NOT NULL DEFAULT 0 COMMENT 'Allocation perso autonomie',
    montant_du_total DECIMAL(10,2) NOT NULL COMMENT 'Net à payer = loyer + charges + services - APL - APA',

    -- Workflow paiement
    statut ENUM('emise', 'partiellement_payee', 'payee', 'impayee', 'annulee') NOT NULL DEFAULT 'emise',
    date_paiement DATE NULL,
    montant_paye DECIMAL(10,2) NULL COMMENT 'NULL si pas payé, partiel si != montant_du_total',
    mode_paiement ENUM('prelevement', 'virement', 'cheque', 'mandat_sepa', 'especes', 'autre') NULL,
    reference_paiement VARCHAR(100) NULL,

    -- Documents
    pdf_path VARCHAR(500) NULL COMMENT 'Chemin relatif sous uploads/quittances/',

    -- Audit
    notes TEXT NULL,
    emis_by INT NULL COMMENT 'User qui a généré la quittance',
    emis_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    paid_by INT NULL,
    paid_at DATETIME NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_occupation_periode (occupation_id, periode_annee, periode_mois),
    KEY idx_residence_periode (residence_id, periode_annee, periode_mois),
    KEY idx_resident (resident_id, statut),
    KEY idx_statut_periode (statut, periode_annee, periode_mois),
    KEY idx_numero (numero_quittance),

    FOREIGN KEY (occupation_id) REFERENCES occupations_residents(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (emis_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (paid_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Relances impayés (FK polymorphe vers 3 sources)
CREATE TABLE impayes_relances (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- FK polymorphe : quel impayé est ciblé ?
    source_type ENUM('quittance_resident', 'paiement_proprio', 'facture_fournisseur') NOT NULL,
    source_id INT NOT NULL COMMENT 'ID dans quittances_residents OU paiements_loyers_exploitant OU factures_fournisseurs',
    residence_id INT NOT NULL COMMENT 'Dénormalisé pour filtrage par résidence',

    -- Niveau de la relance (1=amiable, 2=ferme, 3=mise en demeure)
    niveau TINYINT NOT NULL COMMENT '1, 2 ou 3',
    template_utilise VARCHAR(50) NULL COMMENT 'ex: amiable_resident, ferme_proprio',

    -- Contenu envoyé
    sujet VARCHAR(255) NULL,
    corps_message TEXT NULL,
    montant_du DECIMAL(10,2) NOT NULL COMMENT 'Snapshot du montant dû à la date d''envoi',

    -- Canal et destinataire
    moyen ENUM('messagerie', 'email', 'courrier', 'sms', 'telephone') NOT NULL DEFAULT 'messagerie',
    destinataire_user_id INT NULL COMMENT 'Pour résident/proprio (a un user_id)',
    destinataire_externe VARCHAR(255) NULL COMMENT 'Pour fournisseur (email externe)',
    message_id INT NULL COMMENT 'FK vers messages.id si envoyé par messagerie interne',

    -- Workflow
    statut ENUM('envoyee', 'reglee', 'escaladee', 'annulee') NOT NULL DEFAULT 'envoyee',
    date_envoi DATETIME DEFAULT CURRENT_TIMESTAMP,
    sent_by INT NULL,
    notes TEXT NULL,

    KEY idx_source (source_type, source_id),
    KEY idx_residence (residence_id),
    KEY idx_statut_niveau (statut, niveau),
    KEY idx_destinataire (destinataire_user_id),

    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (destinataire_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (sent_by) REFERENCES users(id) ON DELETE SET NULL
    -- Pas de FK sur source_id (polymorphe) — gérée applicativement
    -- Pas de FK sur message_id pour éviter cascade si message supprimé
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
