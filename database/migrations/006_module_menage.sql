-- ====================================================================
-- Migration 006 : Module Ménage complet
-- ====================================================================
-- Tables pour le module ménage des résidences seniors :
-- - Zones extérieures configurables
-- - Tâches quotidiennes (intérieur/extérieur) avec checklist
-- - Affectations auto (distribution équitable)
-- - Produits & inventaire ménage (séparé de restauration)
-- - Demandes laverie + tarifs
-- - Comptabilité ménage
-- ====================================================================

-- ─────────────────────────────────────────────────────────────
-- Adapter les tables existantes
-- ─────────────────────────────────────────────────────────────

ALTER TABLE hotes_temporaires
    ADD COLUMN IF NOT EXISTS ne_pas_deranger TINYINT(1) DEFAULT 0 AFTER statut;

-- ─────────────────────────────────────────────────────────────
-- 1. ZONES EXTÉRIEURES (configurables par résidence)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_zones_exterieures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    type_zone ENUM('terrasse','parking','entree','local_poubelles','couloir','ascenseur','jardin','piscine','salle_commune','autre') NOT NULL DEFAULT 'autre',
    frequence ENUM('quotidien','hebdomadaire','bihebdomadaire','mensuel') DEFAULT 'quotidien',
    jour_semaine VARCHAR(50) DEFAULT NULL COMMENT 'lundi,mercredi,vendredi si hebdo/bihebdo',
    priorite INT DEFAULT 0 COMMENT 'Plus haut = plus prioritaire',
    description TEXT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. TÂCHES QUOTIDIENNES
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_taches_jour (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_tache DATE NOT NULL,
    type_tache ENUM('interieur','exterieur','laverie') NOT NULL,
    lot_id INT DEFAULT NULL COMMENT 'Pour ménage intérieur',
    zone_exterieure_id INT DEFAULT NULL COMMENT 'Pour ménage extérieur',
    hote_id INT DEFAULT NULL COMMENT 'Si chambre hôte',
    resident_id INT DEFAULT NULL COMMENT 'Si chambre résident',
    niveau_service ENUM('aucun','basique','premium') DEFAULT NULL,
    poids DECIMAL(3,1) DEFAULT 1.0 COMMENT '1.0=studio, 1.5=t2, 2.0=t3',
    employe_id INT DEFAULT NULL COMMENT 'Employé affecté',
    statut ENUM('a_faire','en_cours','termine','pas_deranger','annule') DEFAULT 'a_faire',
    heure_debut TIME DEFAULT NULL,
    heure_fin TIME DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    generated_auto TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE SET NULL,
    FOREIGN KEY (zone_exterieure_id) REFERENCES menage_zones_exterieures(id) ON DELETE SET NULL,
    FOREIGN KEY (hote_id) REFERENCES hotes_temporaires(id) ON DELETE SET NULL,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE SET NULL,
    FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. CHECKLIST PAR TÂCHE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_taches_checklist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tache_id INT NOT NULL,
    libelle VARCHAR(200) NOT NULL COMMENT 'Lit fait, Sol lavé, Salle de bain...',
    fait TINYINT(1) DEFAULT 0,
    heure_fait TIME DEFAULT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    FOREIGN KEY (tache_id) REFERENCES menage_taches_jour(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Checklist par défaut (templates par type de lot)
CREATE TABLE IF NOT EXISTS menage_checklist_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type_lot ENUM('studio','t2','t2_bis','t3','cave','parking') DEFAULT NULL COMMENT 'NULL = zone extérieure',
    type_zone ENUM('terrasse','parking','entree','local_poubelles','couloir','ascenseur','jardin','piscine','salle_commune','autre') DEFAULT NULL,
    libelle VARCHAR(200) NOT NULL,
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Templates intérieurs par défaut
INSERT INTO menage_checklist_templates (type_lot, libelle, ordre) VALUES
('studio', 'Lit fait et draps vérifiés', 1),
('studio', 'Sol aspiré et lavé', 2),
('studio', 'Salle de bain nettoyée', 3),
('studio', 'Poubelles vidées', 4),
('studio', 'Surfaces dépoussiérées', 5),
('t2', 'Chambre : lit fait et draps vérifiés', 1),
('t2', 'Chambre : sol aspiré', 2),
('t2', 'Salon : sol aspiré et lavé', 3),
('t2', 'Cuisine : nettoyée', 4),
('t2', 'Salle de bain nettoyée', 5),
('t2', 'Poubelles vidées', 6),
('t2', 'Surfaces dépoussiérées', 7),
('t2_bis', 'Chambre : lit fait et draps vérifiés', 1),
('t2_bis', 'Chambre : sol aspiré', 2),
('t2_bis', 'Salon : sol aspiré et lavé', 3),
('t2_bis', 'Cuisine : nettoyée', 4),
('t2_bis', 'Salle de bain nettoyée', 5),
('t2_bis', 'Poubelles vidées', 6),
('t2_bis', 'Surfaces dépoussiérées', 7),
('t3', 'Chambre 1 : lit fait et draps vérifiés', 1),
('t3', 'Chambre 2 : lit fait et draps vérifiés', 2),
('t3', 'Chambres : sols aspirés', 3),
('t3', 'Salon : sol aspiré et lavé', 4),
('t3', 'Cuisine : nettoyée', 5),
('t3', 'Salle de bain 1 nettoyée', 6),
('t3', 'Salle de bain 2 nettoyée', 7),
('t3', 'Poubelles vidées', 8),
('t3', 'Surfaces dépoussiérées', 9);

-- Templates extérieurs
INSERT INTO menage_checklist_templates (type_zone, libelle, ordre) VALUES
('entree', 'Sol lavé', 1),
('entree', 'Vitres nettoyées', 2),
('entree', 'Poubelles vidées', 3),
('parking', 'Sol balayé', 1),
('parking', 'Déchets ramassés', 2),
('terrasse', 'Sol lavé', 1),
('terrasse', 'Mobilier nettoyé', 2),
('terrasse', 'Plantes arrosées', 3),
('local_poubelles', 'Bacs vidés', 1),
('local_poubelles', 'Sol lavé et désinfecté', 2),
('local_poubelles', 'Tri vérifié', 3),
('local_poubelles', 'Désodorisant appliqué', 4),
('couloir', 'Sol aspiré et lavé', 1),
('couloir', 'Surfaces dépoussiérées', 2),
('ascenseur', 'Sol lavé', 1),
('ascenseur', 'Miroir nettoyé', 2),
('ascenseur', 'Boutons désinfectés', 3),
('piscine', 'Abords nettoyés', 1),
('piscine', 'Vestiaires nettoyés', 2),
('salle_commune', 'Sol aspiré et lavé', 1),
('salle_commune', 'Tables et chaises nettoyées', 2),
('salle_commune', 'Poubelles vidées', 3);

-- ─────────────────────────────────────────────────────────────
-- 4. AFFECTATIONS QUOTIDIENNES
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_affectations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_affectation DATE NOT NULL,
    employe_id INT NOT NULL,
    type_affectation ENUM('interieur','exterieur','laverie') NOT NULL,
    nb_taches INT DEFAULT 0,
    poids_total DECIMAL(5,1) DEFAULT 0.0,
    statut ENUM('planifie','en_cours','termine') DEFAULT 'planifie',
    generated_auto TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_affectation (residence_id, date_affectation, employe_id, type_affectation),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. PRODUITS & INVENTAIRE MÉNAGE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    categorie ENUM('nettoyant','desinfectant','lessive','materiel','sac_poubelle','papier','autre') NOT NULL DEFAULT 'autre',
    section ENUM('interieur','exterieur','laverie','commun') NOT NULL DEFAULT 'commun',
    unite ENUM('litre','ml','kg','unite','carton','rouleau','sachet','bidon','boite') NOT NULL DEFAULT 'unite',
    prix_reference DECIMAL(8,2) DEFAULT NULL,
    fournisseur_id INT DEFAULT NULL,
    marque VARCHAR(100) DEFAULT NULL,
    conditionnement VARCHAR(100) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menage_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    residence_id INT NOT NULL,
    quantite_stock DECIMAL(10,3) DEFAULT 0.000,
    seuil_alerte DECIMAL(10,3) DEFAULT 0.000,
    emplacement VARCHAR(100) DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_produit_residence (produit_id, residence_id),
    FOREIGN KEY (produit_id) REFERENCES menage_produits(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menage_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type_mouvement ENUM('entree','sortie','ajustement') NOT NULL,
    quantite DECIMAL(10,3) NOT NULL,
    motif ENUM('livraison','consommation','perte','casse','inventaire','autre') NOT NULL DEFAULT 'consommation',
    commande_id INT DEFAULT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES menage_inventaire(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. COMMANDES FOURNISSEURS MÉNAGE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_commandes (
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
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menage_commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL,
    quantite_commandee DECIMAL(10,3) NOT NULL,
    quantite_recue DECIMAL(10,3) DEFAULT NULL,
    prix_unitaire_ht DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 20.00 COMMENT 'TVA 20% produits ménage',
    montant_ligne_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_commandee * prix_unitaire_ht) STORED,
    FOREIGN KEY (commande_id) REFERENCES menage_commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES menage_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. LAVERIE — DEMANDES & TARIFS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_laverie_tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    type_linge ENUM('draps_1p','draps_2p','housse_couette','serviettes','peignoir','linge_personnel','autre') NOT NULL,
    libelle VARCHAR(100) NOT NULL,
    prix_unitaire DECIMAL(8,2) NOT NULL,
    actif TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_residence_type (residence_id, type_linge),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS menage_laverie_demandes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    resident_id INT NOT NULL,
    date_demande DATE NOT NULL,
    date_traitement DATE DEFAULT NULL,
    date_prete DATE DEFAULT NULL,
    date_livree DATE DEFAULT NULL,
    type_linge ENUM('draps_1p','draps_2p','housse_couette','serviettes','peignoir','linge_personnel','autre') NOT NULL,
    quantite INT DEFAULT 1,
    service_inclus TINYINT(1) DEFAULT 0 COMMENT '1 si résident a forfait laverie',
    prix_unitaire DECIMAL(8,2) DEFAULT 0.00,
    montant_total DECIMAL(8,2) DEFAULT 0.00,
    statut ENUM('demandee','en_cours','prete','livree','facturee','annulee') DEFAULT 'demandee',
    employe_id INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (employe_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. COMPTABILITÉ MÉNAGE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS menage_comptabilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,
    categorie ENUM('laverie_residents','achat_fournisseur','charge_personnel','autre') NOT NULL,
    reference_id INT DEFAULT NULL,
    reference_type ENUM('laverie_demande','commande_fournisseur','autre') DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    compte_comptable VARCHAR(20) DEFAULT NULL,
    mois INT DEFAULT NULL,
    annee INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 9. INDEX DE PERFORMANCE
-- ─────────────────────────────────────────────────────────────

CREATE INDEX idx_menage_taches_date ON menage_taches_jour(date_tache, residence_id);
CREATE INDEX idx_menage_taches_employe ON menage_taches_jour(employe_id, date_tache);
CREATE INDEX idx_menage_taches_statut ON menage_taches_jour(statut);
CREATE INDEX idx_menage_affectations_date ON menage_affectations(date_affectation, residence_id);
CREATE INDEX idx_menage_laverie_statut ON menage_laverie_demandes(statut);
CREATE INDEX idx_menage_laverie_resident ON menage_laverie_demandes(resident_id, date_demande);
CREATE INDEX idx_menage_commandes_statut ON menage_commandes(statut);
CREATE INDEX idx_menage_compta_mois ON menage_comptabilite(annee, mois, type_ecriture);
CREATE INDEX idx_menage_inventaire_alerte ON menage_inventaire(quantite_stock, seuil_alerte);
