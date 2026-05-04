-- ====================================================================
-- Migration 034 — Table declarations_tva (Phase 6 Comptabilité)
-- ====================================================================
-- Archive les déclarations TVA calculées et soumises à l'administration
-- fiscale (CERFA n°3310-CA3 mensuel/trimestriel ou n°3517-S CA12 annuel).
--
-- Snapshot des montants au moment du calcul : assiettes HT par taux,
-- TVA collectée, TVA déductible, TVA à payer ou crédit à reporter.
--
-- Le brouillon préparé peut être archivé une fois la déclaration
-- réellement transmise au SIE — passage statut 'brouillon' → 'declaree'.
-- ====================================================================

CREATE TABLE IF NOT EXISTS declarations_tva (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    regime ENUM('CA3_mensuel', 'CA3_trimestriel', 'CA12_annuel') NOT NULL,
    periode_debut DATE NOT NULL,
    periode_fin DATE NOT NULL,

    -- Assiettes HT par taux (lignes 01 à 04 du CA3)
    ca_ht_20 DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 01 : opérations à 20%',
    ca_ht_10 DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 02 : opérations à 10%',
    ca_ht_55 DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 03 : opérations à 5,5%',
    ca_ht_21 DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 04 : opérations à 2,1%',
    ca_ht_exonere DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 05/06 : opérations exonérées ou hors champ',

    -- TVA collectée (lignes 08, 09, 9B du CA3)
    tva_collectee_20 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tva_collectee_10 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tva_collectee_55 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tva_collectee_21 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    tva_collectee_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 16 : total TVA brute due',

    -- TVA déductible (lignes 19, 20 du CA3)
    tva_deductible_biens_services DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 19 : ABS et acquisitions',
    tva_deductible_immobilisations DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 20 : immobilisations',
    tva_deductible_total DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 22 : total TVA déductible',

    -- Soldes (lignes 25, 28, 32 du CA3)
    credit_tva_anterieur DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 25 : crédit reporté de la déclaration précédente',
    tva_a_payer DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 28 : TVA nette due',
    credit_a_reporter DECIMAL(12,2) NOT NULL DEFAULT 0.00 COMMENT 'Ligne 32 : crédit de TVA à reporter',

    -- Workflow
    statut ENUM('brouillon', 'declaree', 'annulee') NOT NULL DEFAULT 'brouillon',
    declared_at DATETIME NULL COMMENT 'Date de transmission au SIE',
    declared_by INT NULL COMMENT 'User ayant validé la déclaration',
    pdf_path VARCHAR(500) NULL COMMENT 'PDF brouillon archivé',
    notes TEXT NULL,

    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_residence_periode_regime (residence_id, regime, periode_debut, periode_fin),
    KEY idx_residence_periode (residence_id, periode_debut, periode_fin),
    KEY idx_statut (statut),

    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (declared_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
