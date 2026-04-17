-- ====================================================================
-- Migration 004 : Module Restauration complet
-- ====================================================================
-- Tables pour le module restauration des résidences seniors :
-- - Catalogue de plats
-- - Menus du jour (composition)
-- - Produits & inventaire par résidence
-- - Mouvements de stock (traçabilité)
-- - Commandes fournisseurs (workflow complet)
-- - Services repas (traçabilité de chaque repas servi)
-- - Facturation repas (résidents, hôtes, passages)
-- - Comptabilité restauration (recettes/dépenses + TVA)
-- ====================================================================

-- ─────────────────────────────────────────────────────────────
-- Adapter les tables existantes
-- ─────────────────────────────────────────────────────────────

-- Ajouter le régime repas aux hôtes temporaires
ALTER TABLE hotes_temporaires
    ADD COLUMN IF NOT EXISTS regime_repas ENUM('aucun','petit_dejeuner','demi_pension','pension_complete') DEFAULT 'aucun' AFTER motif_sejour;

-- Ajouter la catégorie service aux factures fournisseurs
ALTER TABLE factures_fournisseurs
    ADD COLUMN IF NOT EXISTS categorie_service ENUM('restauration','menage','technique','jardinage','laverie','autre') DEFAULT 'autre' AFTER type_charge;

-- ─────────────────────────────────────────────────────────────
-- 1. CATALOGUE DE PLATS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    description TEXT DEFAULT NULL,
    categorie ENUM('entree','plat','dessert','boisson','snack','petit_dejeuner','autre') NOT NULL DEFAULT 'plat',
    type_service ENUM('petit_dejeuner','dejeuner','gouter','diner','snack_bar','tous') NOT NULL DEFAULT 'tous',
    prix_unitaire DECIMAL(8,2) DEFAULT 0.00,
    allergenes VARCHAR(500) DEFAULT NULL COMMENT 'Séparés par virgule: gluten,lactose,arachides...',
    regime ENUM('normal','vegetarien','vegan','sans_gluten','sans_lactose','halal','autre') DEFAULT 'normal',
    calories INT DEFAULT NULL,
    photo VARCHAR(500) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    ordre_affichage INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. MENUS DU JOUR
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_menus (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_menu DATE NOT NULL,
    type_service ENUM('petit_dejeuner','dejeuner','gouter','diner') NOT NULL,
    nom VARCHAR(200) DEFAULT NULL COMMENT 'Ex: Menu du Chef, Menu Tradition...',
    prix_menu DECIMAL(8,2) DEFAULT NULL COMMENT 'Prix du menu complet',
    notes TEXT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_residence_date_service (residence_id, date_menu, type_service),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rest_menu_plats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    menu_id INT NOT NULL,
    plat_id INT NOT NULL,
    categorie_plat ENUM('entree','plat','dessert','accompagnement','boisson') NOT NULL,
    ordre INT DEFAULT 0 COMMENT '1=choix A, 2=choix B...',
    FOREIGN KEY (menu_id) REFERENCES rest_menus(id) ON DELETE CASCADE,
    FOREIGN KEY (plat_id) REFERENCES rest_plats(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. PRODUITS (catalogue global) & INVENTAIRE (stock par résidence)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    categorie ENUM('fruits_legumes','viandes','poissons','laitier','boulangerie','epicerie_seche','boissons','surgeles','condiments','non_alimentaire','autre') NOT NULL DEFAULT 'autre',
    unite ENUM('kg','g','litre','cl','unite','barquette','carton','sachet','boite','bouteille') NOT NULL DEFAULT 'unite',
    prix_reference DECIMAL(8,2) DEFAULT NULL COMMENT 'Prix unitaire indicatif',
    code_barre VARCHAR(50) DEFAULT NULL,
    fournisseur_id INT DEFAULT NULL COMMENT 'Fournisseur habituel',
    marque VARCHAR(100) DEFAULT NULL,
    conditionnement VARCHAR(100) DEFAULT NULL COMMENT 'Ex: pack de 6, carton de 12',
    actif TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rest_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    residence_id INT NOT NULL,
    quantite_stock DECIMAL(10,3) DEFAULT 0.000,
    seuil_alerte DECIMAL(10,3) DEFAULT 0.000 COMMENT 'Seuil en dessous duquel alerter',
    emplacement VARCHAR(100) DEFAULT NULL COMMENT 'Chambre froide, réserve sèche...',
    date_peremption DATE DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_produit_residence (produit_id, residence_id),
    FOREIGN KEY (produit_id) REFERENCES rest_produits(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rest_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type_mouvement ENUM('entree','sortie','ajustement') NOT NULL,
    quantite DECIMAL(10,3) NOT NULL,
    motif ENUM('livraison','consommation','perte','casse','inventaire','retour','autre') NOT NULL DEFAULT 'consommation',
    commande_id INT DEFAULT NULL COMMENT 'Référence commande si livraison',
    notes VARCHAR(500) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES rest_inventaire(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. COMMANDES FOURNISSEURS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_commandes (
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

CREATE TABLE IF NOT EXISTS rest_commande_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    commande_id INT NOT NULL,
    produit_id INT NOT NULL,
    designation VARCHAR(255) NOT NULL COMMENT 'Copie du nom produit au moment de la commande',
    quantite_commandee DECIMAL(10,3) NOT NULL,
    quantite_recue DECIMAL(10,3) DEFAULT NULL,
    prix_unitaire_ht DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 5.50 COMMENT 'TVA alimentaire 5.5% par défaut',
    montant_ligne_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite_commandee * prix_unitaire_ht) STORED,
    FOREIGN KEY (commande_id) REFERENCES rest_commandes(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES rest_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. SERVICES REPAS (traçabilité de chaque repas servi)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_services_repas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_service DATE NOT NULL,
    type_service ENUM('petit_dejeuner','dejeuner','gouter','diner','snack_bar') NOT NULL,
    type_client ENUM('resident','hote','passage') NOT NULL,
    resident_id INT DEFAULT NULL,
    hote_id INT DEFAULT NULL,
    nom_passage VARCHAR(200) DEFAULT NULL COMMENT 'Nom du client de passage',
    menu_id INT DEFAULT NULL COMMENT 'Si menu complet',
    mode_facturation ENUM('pension_complete','menu','carte') NOT NULL DEFAULT 'menu',
    nb_couverts INT DEFAULT 1,
    montant DECIMAL(8,2) DEFAULT 0.00,
    notes VARCHAR(500) DEFAULT NULL,
    serveur_id INT DEFAULT NULL COMMENT 'Serveur qui a enregistré',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE SET NULL,
    FOREIGN KEY (hote_id) REFERENCES hotes_temporaires(id) ON DELETE SET NULL,
    FOREIGN KEY (menu_id) REFERENCES rest_menus(id) ON DELETE SET NULL,
    FOREIGN KEY (serveur_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. FACTURATION REPAS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    numero_facture VARCHAR(50) NOT NULL,
    type_client ENUM('resident','hote','passage') NOT NULL,
    resident_id INT DEFAULT NULL,
    hote_id INT DEFAULT NULL,
    nom_passage VARCHAR(200) DEFAULT NULL,
    date_facture DATE NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    taux_tva DECIMAL(4,2) DEFAULT 10.00 COMMENT 'TVA restauration sur place 10%',
    montant_tva DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    statut ENUM('brouillon','emise','payee','annulee') DEFAULT 'brouillon',
    mode_paiement ENUM('especes','cb','prelevement','pension','cheque','virement') DEFAULT NULL,
    date_paiement DATE DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE SET NULL,
    FOREIGN KEY (hote_id) REFERENCES hotes_temporaires(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rest_facture_lignes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    facture_id INT NOT NULL,
    service_repas_id INT DEFAULT NULL COMMENT 'Lien vers le repas servi',
    designation VARCHAR(255) NOT NULL,
    type_ligne ENUM('menu_complet','entree','plat','dessert','boisson','snack','supplement','autre') NOT NULL DEFAULT 'menu_complet',
    quantite INT DEFAULT 1,
    prix_unitaire DECIMAL(8,2) NOT NULL,
    taux_tva DECIMAL(4,2) DEFAULT 10.00,
    montant_ht DECIMAL(10,2) GENERATED ALWAYS AS (quantite * prix_unitaire) STORED,
    FOREIGN KEY (facture_id) REFERENCES rest_factures(id) ON DELETE CASCADE,
    FOREIGN KEY (service_repas_id) REFERENCES rest_services_repas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. COMPTABILITÉ RESTAURATION
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_comptabilite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    date_ecriture DATE NOT NULL,
    type_ecriture ENUM('recette','depense') NOT NULL,
    categorie ENUM('repas_residents','repas_hotes','repas_passages','achat_fournisseur','charge_personnel','autre') NOT NULL,
    reference_id INT DEFAULT NULL COMMENT 'ID facture ou commande',
    reference_type ENUM('facture_repas','commande_fournisseur','facture_fournisseur','autre') DEFAULT NULL,
    libelle VARCHAR(255) NOT NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    montant_tva DECIMAL(12,2) DEFAULT 0.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    compte_comptable VARCHAR(20) DEFAULT NULL COMMENT '706100=repas, 601100=achats alimentaires...',
    mois INT DEFAULT NULL,
    annee INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. TARIFS PAR DÉFAUT (configuration résidence)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS rest_tarifs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    type_service ENUM('petit_dejeuner','dejeuner','gouter','diner','snack_bar') NOT NULL,
    prix_menu DECIMAL(8,2) NOT NULL COMMENT 'Prix menu complet',
    prix_entree DECIMAL(8,2) DEFAULT NULL COMMENT 'Prix entrée à la carte',
    prix_plat DECIMAL(8,2) DEFAULT NULL,
    prix_dessert DECIMAL(8,2) DEFAULT NULL,
    prix_boisson DECIMAL(8,2) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    UNIQUE KEY uk_residence_service (residence_id, type_service),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 9. INDEX DE PERFORMANCE
-- ─────────────────────────────────────────────────────────────

CREATE INDEX idx_rest_menus_date ON rest_menus(date_menu);
CREATE INDEX idx_rest_services_date ON rest_services_repas(date_service, type_service);
CREATE INDEX idx_rest_services_resident ON rest_services_repas(resident_id, date_service);
CREATE INDEX idx_rest_factures_date ON rest_factures(date_facture);
CREATE INDEX idx_rest_factures_statut ON rest_factures(statut);
CREATE INDEX idx_rest_inventaire_alerte ON rest_inventaire(quantite_stock, seuil_alerte);
CREATE INDEX idx_rest_commandes_statut ON rest_commandes(statut);
CREATE INDEX idx_rest_compta_mois ON rest_comptabilite(annee, mois, type_ecriture);
CREATE INDEX idx_rest_produits_categorie ON rest_produits(categorie, actif);
