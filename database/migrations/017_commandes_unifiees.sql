-- ====================================================================
-- Migration 017 : Unification des tables commandes (polymorphe)
-- ====================================================================
-- Fusion des 3 tables identiques (menage_commandes, rest_commandes,
-- jardin_commandes) en une seule table `commandes` polymorphe avec une
-- colonne `module` ENUM, puis idem pour les lignes.
--
-- Pattern : identique à `produit_fournisseurs` (migration 015).
-- Les tables sont vides actuellement → migration sans risque.
-- Extensible aux modules futurs (travaux, piscine, entretien, laverie).
--
-- DROP des 6 anciennes tables en fin de migration.
-- ====================================================================

-- ─────────────────────────────────────────────────────────────
-- 1. Table commandes unifiée
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module ENUM('restauration','menage','jardinage','travaux','piscine','entretien','laverie','autre') NOT NULL,
    residence_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    numero_commande VARCHAR(50) NOT NULL,
    date_commande DATE NOT NULL,
    date_livraison_prevue DATE DEFAULT NULL,
    date_livraison_effective DATE DEFAULT NULL,
    statut ENUM('brouillon','envoyee','livree_partiel','livree','facturee','annulee') DEFAULT 'brouillon',
    montant_total_ht DECIMAL(12,2) DEFAULT 0.00,
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_total_ttc DECIMAL(12,2) DEFAULT 0.00,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_numero_commande (numero_commande),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_commandes_module ON commandes(module, statut);
CREATE INDEX idx_commandes_residence ON commandes(residence_id, date_commande);
CREATE INDEX idx_commandes_fournisseur ON commandes(fournisseur_id, date_commande);

-- ─────────────────────────────────────────────────────────────
-- 2. Table commande_lignes unifiée
-- ─────────────────────────────────────────────────────────────
-- produit_id = FK virtuelle vers {module}_produits.id, le module étant
-- accessible via la commande parente.

CREATE TABLE IF NOT EXISTS commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL COMMENT 'FK virtuelle vers {module}_produits.id (module via commande parente)',
    designation VARCHAR(255) NOT NULL,
    quantite_commandee DECIMAL(10,3) NOT NULL,
    quantite_recue DECIMAL(10,3) DEFAULT NULL,
    prix_unitaire_ht DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 20.00,
    montant_ligne_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_commandee * prix_unitaire_ht) STORED,
    FOREIGN KEY (commande_id) REFERENCES commandes(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_commande_lignes_commande ON commande_lignes(commande_id);

-- ─────────────────────────────────────────────────────────────
-- 3. Migration des données depuis les 3 anciennes tables
-- ─────────────────────────────────────────────────────────────
-- (Tables actuellement vides, mais on garde les INSERT pour idempotence
-- au cas où des données apparaîtraient avant exécution.)

INSERT IGNORE INTO commandes
    (id, module, residence_id, fournisseur_id, numero_commande, date_commande,
     date_livraison_prevue, date_livraison_effective, statut,
     montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at)
SELECT id, 'menage', residence_id, fournisseur_id, numero_commande, date_commande,
       date_livraison_prevue, date_livraison_effective, statut,
       montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at
FROM menage_commandes;

INSERT IGNORE INTO commandes
    (module, residence_id, fournisseur_id, numero_commande, date_commande,
     date_livraison_prevue, date_livraison_effective, statut,
     montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at)
SELECT 'restauration', residence_id, fournisseur_id, numero_commande, date_commande,
       date_livraison_prevue, date_livraison_effective, statut,
       montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at
FROM rest_commandes;

INSERT IGNORE INTO commandes
    (module, residence_id, fournisseur_id, numero_commande, date_commande,
     date_livraison_prevue, date_livraison_effective, statut,
     montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at)
SELECT 'jardinage', residence_id, fournisseur_id, numero_commande, date_commande,
       date_livraison_prevue, date_livraison_effective, statut,
       montant_total_ht, montant_tva, montant_total_ttc, notes, created_by, created_at, updated_at
FROM jardin_commandes;

-- Note : pour les lignes, on ne peut pas faire un simple INSERT car les commande_id
-- des anciennes tables référencent différentes tables. Comme les tables sont vides,
-- on laisse ce cas pour le script PHP d'application (plus complexe à faire en SQL pur).
-- Les tables vides → aucune ligne à migrer.

-- ─────────────────────────────────────────────────────────────
-- 4. DROP des 6 anciennes tables
-- ─────────────────────────────────────────────────────────────

DROP TABLE IF EXISTS menage_commande_lignes;
DROP TABLE IF EXISTS rest_commande_lignes;
DROP TABLE IF EXISTS jardin_commande_lignes;
DROP TABLE IF EXISTS menage_commandes;
DROP TABLE IF EXISTS rest_commandes;
DROP TABLE IF EXISTS jardin_commandes;
