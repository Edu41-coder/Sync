-- ====================================================================
-- Migration 005 : Table association fournisseur ↔ résidence
-- ====================================================================
-- Un fournisseur peut livrer plusieurs résidences
-- Une résidence peut avoir plusieurs fournisseurs
-- Chaque lien a un statut et des notes spécifiques

CREATE TABLE IF NOT EXISTS rest_fournisseur_residence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    residence_id INT NOT NULL,
    statut ENUM('actif','suspendu','termine') DEFAULT 'actif',
    contact_local VARCHAR(200) DEFAULT NULL COMMENT 'Contact spécifique pour cette résidence',
    telephone_local VARCHAR(20) DEFAULT NULL,
    jour_livraison VARCHAR(100) DEFAULT NULL COMMENT 'Ex: lundi,jeudi',
    delai_livraison_jours INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_fournisseur_residence (fournisseur_id, residence_id),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Index
CREATE INDEX idx_fr_residence ON rest_fournisseur_residence(residence_id, statut);

-- Données initiales : lier tous les fournisseurs existants à La Badiane (61)
INSERT IGNORE INTO rest_fournisseur_residence (fournisseur_id, residence_id, statut, jour_livraison)
SELECT id, 61, 'actif',
    CASE
        WHEN type_service = 'fruits_legumes' THEN 'lundi,mercredi,vendredi'
        WHEN type_service = 'surgeles' THEN 'mardi'
        WHEN type_service = 'boissons' THEN 'jeudi'
        ELSE 'mardi,vendredi'
    END
FROM fournisseurs WHERE actif = 1;
