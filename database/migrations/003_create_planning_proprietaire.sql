-- ====================================================================
-- Migration 003 : Tables planning propriétaire
-- ====================================================================
-- Support du calendrier propriétaire (CoproprietaireController::calendrier)

CREATE TABLE IF NOT EXISTS planning_proprio_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    couleur VARCHAR(20) DEFAULT '#6c757d',
    bg_couleur VARCHAR(20) DEFAULT '#e9ecef',
    icone VARCHAR(50) DEFAULT 'fas fa-calendar',
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Catégories par défaut
INSERT IGNORE INTO planning_proprio_categories (slug, nom, couleur, bg_couleur, icone, ordre) VALUES
('loyer', 'Loyers', '#198754', '#d1e7dd', 'fas fa-euro-sign', 1),
('fiscal', 'Fiscal', '#0d6efd', '#cfe2ff', 'fas fa-file-invoice', 2),
('echeance', 'Échéances', '#dc3545', '#f8d7da', 'fas fa-exclamation-triangle', 3),
('travaux', 'Travaux', '#fd7e14', '#ffe5d0', 'fas fa-tools', 4),
('note', 'Notes', '#6c757d', '#e9ecef', 'fas fa-sticky-note', 5);

CREATE TABLE IF NOT EXISTS planning_proprietaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coproprietaire_id INT NOT NULL,
    category_id INT DEFAULT NULL,
    titre VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,
    journee_entiere TINYINT(1) DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES planning_proprio_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
