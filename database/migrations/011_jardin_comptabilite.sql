-- ====================================================================
-- Migration 011 : Comptabilité jardinerie
-- ====================================================================
-- Table d'écritures comptables du module jardinerie.
-- Pattern similaire à menage_comptabilite, mais avec un champ `espace_id`
-- pour permettre le calcul du coût par espace jardin.
--
-- Catégories :
--  - achat_fournisseur : achat produit/outil via commande jardin_commandes
--  - recolte_miel      : recette miel (liée à une visite de ruche recolte)
--  - charge_personnel  : main d'œuvre (optionnel, saisie manuelle)
--  - autre_recette     : autres produits (vente surplus plants, etc.)
--  - autre_depense     : autres (carburant, consommables non catalogués)
-- ====================================================================

CREATE TABLE IF NOT EXISTS jardin_comptabilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,
    categorie ENUM('achat_fournisseur','recolte_miel','charge_personnel','autre_recette','autre_depense') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'FK vers commande, visite, etc.',
    reference_type ENUM('commande_fournisseur','ruche_visite','manuel','autre') DEFAULT 'manuel',
    espace_id INT DEFAULT NULL COMMENT 'Pour imputation coût par espace jardin',
    libelle VARCHAR(255) NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    compte_comptable VARCHAR(20) DEFAULT NULL,
    mois INT DEFAULT NULL,
    annee INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_jardin_compta_periode ON jardin_comptabilite(annee, mois, type_ecriture);
CREATE INDEX idx_jardin_compta_residence ON jardin_comptabilite(residence_id, annee, mois);
CREATE INDEX idx_jardin_compta_reference ON jardin_comptabilite(reference_type, reference_id);
CREATE INDEX idx_jardin_compta_espace ON jardin_comptabilite(espace_id, annee);
