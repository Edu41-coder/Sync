-- ====================================================================
-- Migration 015 : Relations N:M polymorphes produit ↔ fournisseurs
-- ====================================================================
-- Pattern polymorphe (Laravel/Django style) :
-- - Une table unique `produit_fournisseurs` pour les 3 modules actuels
--   (restauration, menage, jardinage) ET les futurs (travaux, piscine,
--   entretien, laverie, autre).
-- - `produit_id` N'A PAS de FK native (pointe vers différentes tables
--   selon `produit_module`). L'intégrité est gérée au niveau Model :
--   le delete d'un produit purge d'abord les liens.
-- - Enrichi avec prix spécifique fournisseur, référence côté fournisseur,
--   flag "préféré" (1 seul préféré max par produit), notes.
--
-- Les étapes DROP FK et DROP COLUMN fournisseur_id dans les 3 tables
-- produits sont exécutées dynamiquement côté applicateur PHP (car elles
-- nécessitent des PREPARE/EXECUTE pour détecter le nom de la FK).
-- ====================================================================

CREATE TABLE IF NOT EXISTS produit_fournisseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_module ENUM('restauration','menage','jardinage','travaux','piscine','entretien','laverie','autre') NOT NULL,
    produit_id INT NOT NULL COMMENT 'FK virtuelle vers {module}_produits.id',
    fournisseur_id INT NOT NULL,
    prix_unitaire_specifique DECIMAL(8,2) DEFAULT NULL COMMENT 'Prix chez ce fournisseur si différent du prix produit',
    reference_fournisseur VARCHAR(100) DEFAULT NULL COMMENT 'Code article côté fournisseur',
    fournisseur_prefere TINYINT(1) DEFAULT 0 COMMENT '1 = fournisseur préféré (1 max par produit)',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_produit_fournisseur (produit_module, produit_id, fournisseur_id),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_prod_four_module_produit ON produit_fournisseurs(produit_module, produit_id);
CREATE INDEX idx_prod_four_fournisseur ON produit_fournisseurs(fournisseur_id);
CREATE INDEX idx_prod_four_prefere ON produit_fournisseurs(produit_module, produit_id, fournisseur_prefere);

-- Les fournisseurs définis actuellement sur chaque produit deviennent
-- "préférés" (fournisseur_prefere = 1) dans le nouveau pivot.
INSERT IGNORE INTO produit_fournisseurs
    (produit_module, produit_id, fournisseur_id, fournisseur_prefere)
SELECT 'menage', id, fournisseur_id, 1
FROM menage_produits
WHERE fournisseur_id IS NOT NULL;

INSERT IGNORE INTO produit_fournisseurs
    (produit_module, produit_id, fournisseur_id, fournisseur_prefere)
SELECT 'restauration', id, fournisseur_id, 1
FROM rest_produits
WHERE fournisseur_id IS NOT NULL;

INSERT IGNORE INTO produit_fournisseurs
    (produit_module, produit_id, fournisseur_id, fournisseur_prefere)
SELECT 'jardinage', id, fournisseur_id, 1
FROM jardin_produits
WHERE fournisseur_id IS NOT NULL;
