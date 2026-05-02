-- ====================================================================
-- Migration 018 : Espace résident senior
-- ====================================================================
-- Tables nécessaires pour l'espace personnel du résident senior :
--   1. planning_resident_categories + planning_resident   → calendrier
--   2. resident_dossiers + resident_fichiers              → GED 500 MB
--
-- Quota GED : 500 MB par résident, fichier max 50 MB
-- Stockage physique : uploads/residents/{user_id}/{timestamp_uuid}_{nom}.{ext}
-- ====================================================================

-- ────────────────────────────────────────────────────────────────────
-- 1. Calendrier résident
-- ────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS planning_resident_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    couleur VARCHAR(20) DEFAULT '#6c757d',
    bg_couleur VARCHAR(20) DEFAULT '#e9ecef',
    icone VARCHAR(50) DEFAULT 'fas fa-calendar',
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO planning_resident_categories (slug, nom, couleur, bg_couleur, icone, ordre) VALUES
('loyer',     'Loyers',          '#198754', '#d1e7dd', 'fas fa-euro-sign',         1),
('animation', 'Animations',      '#0dcaf0', '#cff4fc', 'fas fa-music',             2),
('medical',   'Rendez-vous médicaux', '#dc3545', '#f8d7da', 'fas fa-stethoscope',  3),
('famille',   'Famille / Sorties', '#fd7e14', '#ffe5d0', 'fas fa-users',           4),
('fiscal',    'Fiscal',          '#0d6efd', '#cfe2ff', 'fas fa-file-invoice',      5),
('autre',     'Autres',          '#6c757d', '#e9ecef', 'fas fa-sticky-note',       6);

CREATE TABLE IF NOT EXISTS planning_resident (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    journee_entiere TINYINT(1) DEFAULT 0,
    auto_genere TINYINT(1) DEFAULT 0 COMMENT '1 = événement auto-généré (loyer, animation, fiscal)',
    source_ref VARCHAR(100) DEFAULT NULL COMMENT 'Référence source pour dédoublonnage auto-gen (ex: loyer-2026-04, animation-shift-123)',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES planning_resident_categories(id) ON DELETE SET NULL,
    INDEX idx_resident_dates (resident_id, date_debut, date_fin),
    UNIQUE KEY uniq_resident_source (resident_id, source_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 2. GED résident (500 MB / résident, 50 MB / fichier)
-- ────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS resident_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    parent_id INT NULL,
    nom VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES resident_dossiers(id) ON DELETE CASCADE,
    INDEX idx_resident_parent (resident_id, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS resident_fichiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    dossier_id INT NULL,
    nom_original VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) NULL,
    taille_octets BIGINT NOT NULL DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (dossier_id) REFERENCES resident_dossiers(id) ON DELETE SET NULL,
    INDEX idx_resident_dossier (resident_id, dossier_id),
    INDEX idx_resident_taille (resident_id, taille_octets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
