-- ====================================================================
-- Migration 010 : Commandes fournisseurs jardinerie
-- ====================================================================
-- Pattern identique à menage_commandes / menage_commande_lignes.
-- Workflow : brouillon → envoyee → livree_partiel → livree → facturee
-- La réception déclenche un mouvement d'entrée automatique dans
-- jardin_inventaire (transaction).
-- ====================================================================

CREATE TABLE IF NOT EXISTS jardin_commandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
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

CREATE TABLE IF NOT EXISTS jardin_commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite_commandee DECIMAL(10,3) NOT NULL,
    quantite_recue DECIMAL(10,3) DEFAULT NULL,
    prix_unitaire_ht DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 20.00 COMMENT 'TVA 20% par défaut',
    montant_ligne_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_commandee * prix_unitaire_ht) STORED,
    FOREIGN KEY (commande_id) REFERENCES jardin_commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES jardin_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_jardin_commandes_statut ON jardin_commandes(statut);
CREATE INDEX idx_jardin_commandes_residence ON jardin_commandes(residence_id, date_commande);
CREATE INDEX idx_jardin_commandes_fournisseur ON jardin_commandes(fournisseur_id, date_commande);
CREATE INDEX idx_jardin_commande_lignes_commande ON jardin_commande_lignes(commande_id);
