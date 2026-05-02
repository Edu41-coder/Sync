-- ====================================================================
-- Migration 021 : Section Piscine — journal de bord
-- ====================================================================
-- Table unifiée pour le suivi piscine d'une résidence :
--   - analyses chimiques (pH, chlore, etc.) — relevés quotidiens
--   - contrôles ARS (PV, conformité) — réglementaires
--   - hivernage / mise en service — saisonniers
--   - autres événements (vidange, intervention technique, etc.)
--
-- Colonnes spécifiques nullables selon le `type_entree`.
-- Conditionné à coproprietees.piscine = 1 (flag ajouté en migration 019).
-- ====================================================================

CREATE TABLE IF NOT EXISTS piscine_journal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    type_entree ENUM('analyse', 'controle_ars', 'hivernage', 'mise_en_service', 'vidange', 'autre') NOT NULL,
    date_mesure DATETIME NOT NULL,

    -- Analyses chimiques (NULL pour les autres types)
    ph DECIMAL(3,1) NULL COMMENT 'Idéal 7.0-7.4',
    chlore_libre_mg_l DECIMAL(4,2) NULL COMMENT 'Idéal 1-3 mg/L',
    chlore_total_mg_l DECIMAL(4,2) NULL,
    temperature DECIMAL(4,1) NULL COMMENT '°C',
    alcalinite_mg_l INT NULL COMMENT 'Idéal 80-120 mg/L (TAC)',
    stabilisant_mg_l INT NULL COMMENT 'Idéal 30-50 mg/L (acide cyanurique)',

    -- Produit utilisé (analyses ou intervention)
    produit_utilise VARCHAR(200) NULL,
    quantite_produit_kg DECIMAL(6,2) NULL,

    -- Contrôle ARS spécifique
    numero_pv VARCHAR(100) NULL,
    conformite_ars ENUM('conforme', 'non_conforme', 'avertissement') NULL,
    fichier_pv VARCHAR(512) NULL COMMENT 'Chemin uploads/maintenance/piscine_pv/',

    -- Métadonnées
    notes TEXT NULL,
    mesure_par_user_id INT NULL,
    intervention_id INT NULL COMMENT 'Optionnel : lié à une intervention maintenance',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (mesure_par_user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (intervention_id) REFERENCES maintenance_interventions(id) ON DELETE SET NULL,

    INDEX idx_residence_date (residence_id, date_mesure DESC),
    INDEX idx_type (type_entree, date_mesure DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
