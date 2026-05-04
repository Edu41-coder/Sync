-- ====================================================================
-- Migration 035 — Rapprochement bancaire (Phase 10 Comptabilité)
-- ====================================================================
-- Permet d'importer un relevé bancaire CSV et de rapprocher les opérations
-- bancaires avec les écritures comptables (`ecritures_comptables`).
--
-- 2 tables :
--   - bank_imports : un import = un fichier CSV uploadé pour une résidence
--   - bank_operations : les lignes individuelles, FK vers ecritures_comptables
--                        si rapprochées (matched manuellement ou auto)
-- ====================================================================

CREATE TABLE IF NOT EXISTS bank_imports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(500) NULL COMMENT 'Chemin relatif sous uploads/bank/',
    nb_operations INT NOT NULL DEFAULT 0,
    nb_rapprochees INT NOT NULL DEFAULT 0,
    periode_debut DATE NULL,
    periode_fin DATE NULL,
    notes TEXT NULL,
    imported_by INT NULL,
    imported_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    KEY idx_residence_periode (residence_id, periode_debut, periode_fin),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (imported_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS bank_operations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    import_id INT NOT NULL,
    residence_id INT NOT NULL COMMENT 'Dénormalisé pour filtrage rapide',
    date_operation DATE NOT NULL,
    date_valeur DATE NULL,
    libelle VARCHAR(500) NOT NULL,
    montant DECIMAL(12,2) NOT NULL COMMENT 'Signé : positif = crédit (entrée), négatif = débit (sortie)',
    reference VARCHAR(100) NULL,
    statut ENUM('non_rapprochee','rapprochee','ignoree') NOT NULL DEFAULT 'non_rapprochee',
    ecriture_id INT NULL COMMENT 'FK vers ecritures_comptables si rapprochée',
    matched_by INT NULL,
    matched_at DATETIME NULL,
    matched_score TINYINT NULL COMMENT 'Score de matching auto (0-100), NULL si manuel',

    KEY idx_import (import_id),
    KEY idx_residence_date (residence_id, date_operation),
    KEY idx_statut (statut),
    KEY idx_ecriture (ecriture_id),

    FOREIGN KEY (import_id) REFERENCES bank_imports(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (ecriture_id) REFERENCES ecritures_comptables(id) ON DELETE SET NULL,
    FOREIGN KEY (matched_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
