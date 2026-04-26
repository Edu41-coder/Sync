-- ====================================================================
-- Migration 014 : Refactor fournisseurs (globalisation + pivot unique)
-- ====================================================================
-- 1. Transforme `fournisseurs.type_service` VARCHAR → SET (multi-services module)
--    Valeurs : restauration, menage, jardinage, piscine, travaux_elec, travaux_plomberie, autre
-- 2. Crée le pivot global `fournisseur_residence`
-- 3. Migre les données depuis `rest_fournisseur_residence` et `jardin_fournisseur_residence`
-- 4. Conserve temporairement les anciens pivots (le temps du refactor modules)
-- ====================================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Migration du champ type_service (VARCHAR → SET)
-- ─────────────────────────────────────────────────────────────

ALTER TABLE fournisseurs
    ADD COLUMN type_service_new SET('restauration','menage','jardinage','piscine','travaux_elec','travaux_plomberie','autre') DEFAULT '' AFTER type_service;

-- Mapping des anciennes valeurs VARCHAR vers les nouveaux modules
UPDATE fournisseurs SET type_service_new = 'restauration'
    WHERE type_service IN ('alimentaire','surgeles','fruits_legumes','boissons');

UPDATE fournisseurs SET type_service_new = 'menage'
    WHERE type_service IN ('hygiene','nettoyant');

-- Fallback : tout ce qui n'est pas déjà mappé → 'autre'
UPDATE fournisseurs SET type_service_new = 'autre'
    WHERE (type_service_new IS NULL OR type_service_new = '')
      AND type_service IS NOT NULL AND type_service <> '';

-- Remplacer l'ancienne colonne par la nouvelle
ALTER TABLE fournisseurs DROP COLUMN type_service;
ALTER TABLE fournisseurs CHANGE COLUMN type_service_new type_service
    SET('restauration','menage','jardinage','piscine','travaux_elec','travaux_plomberie','autre') DEFAULT '';

-- ─────────────────────────────────────────────────────────────
-- 2. Pivot global fournisseur_residence
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS fournisseur_residence (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fournisseur_id INT NOT NULL,
    residence_id INT NOT NULL,
    statut ENUM('actif','inactif') DEFAULT 'actif',
    contact_local VARCHAR(150) DEFAULT NULL,
    telephone_local VARCHAR(30) DEFAULT NULL,
    jour_livraison VARCHAR(50) DEFAULT NULL,
    delai_livraison_jours INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_fournisseur_residence (fournisseur_id, residence_id),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_fournisseur_residence_statut ON fournisseur_residence(statut);

-- ─────────────────────────────────────────────────────────────
-- 3. Migration des données depuis les anciens pivots
-- ─────────────────────────────────────────────────────────────

INSERT IGNORE INTO fournisseur_residence
    (fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes, created_at)
SELECT
    fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes, created_at
FROM rest_fournisseur_residence;

INSERT IGNORE INTO fournisseur_residence
    (fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes, created_at)
SELECT
    fournisseur_id, residence_id, statut, contact_local, telephone_local, jour_livraison, delai_livraison_jours, notes, created_at
FROM jardin_fournisseur_residence;

-- Note : les tables rest_fournisseur_residence et jardin_fournisseur_residence sont CONSERVÉES
-- pour rétrocompatibilité pendant la phase 2 (refactor progressif des 3 modules).
-- Elles seront supprimées en phase 3 (nettoyage).
