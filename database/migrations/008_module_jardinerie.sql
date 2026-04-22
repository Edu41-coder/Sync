-- ====================================================================
-- Migration 008 : Module Jardinerie (Phase 1)
-- ====================================================================
-- Tables pour le module jardinerie des résidences seniors :
-- - Espaces jardin personnalisables par résidence (potager, parterre, rucher...)
-- - Tâches récurrentes par espace (arrosage, taille, désherbage)
-- - Catalogue produits & outils
-- - Inventaire + mouvements (traçabilité)
-- - Ruches (apiculture) conditionnelle via colonne coproprietees.ruches
-- - Carnet de visite ruches
-- - Pivot fournisseurs / résidences
--
-- Phase 2 (plus tard) : commandes fournisseurs, comptabilité jardinerie
-- ====================================================================

-- ─────────────────────────────────────────────────────────────
-- 0. ADAPTER TABLE coproprietees : flag ruches
-- ─────────────────────────────────────────────────────────────

ALTER TABLE coproprietees
    ADD COLUMN IF NOT EXISTS ruches TINYINT(1) DEFAULT 0 COMMENT '1 si la résidence dispose de ruches (module apiculture)' AFTER actif;

-- ─────────────────────────────────────────────────────────────
-- 1. ESPACES JARDIN (configurables par résidence)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_espaces (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL COMMENT 'Potager nord, Parterre entrée, Rucher sud...',
    type ENUM('potager','parterre_fleuri','pelouse','haie','arbre_fruitier','serre','verger','rocaille','bassin','compost','rucher','autre') NOT NULL DEFAULT 'autre',
    surface_m2 DECIMAL(10,2) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    photo VARCHAR(500) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 2. TÂCHES RÉCURRENTES PAR ESPACE
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_taches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    espace_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL COMMENT 'Arroser, Tailler, Désherber...',
    frequence ENUM('quotidien','hebdo','bi_mensuel','mensuel','saisonnier','ponctuel') NOT NULL DEFAULT 'hebdo',
    saison ENUM('toutes','printemps','ete','automne','hiver') DEFAULT 'toutes',
    duree_estimee_min INT DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 3. CATALOGUE PRODUITS & OUTILS
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    categorie ENUM('engrais','terreau','semence','plant','phytosanitaire','outillage_main','outillage_motorise','arrosage','protection','consommable','autre') NOT NULL DEFAULT 'autre',
    type ENUM('produit','outil') NOT NULL DEFAULT 'produit' COMMENT 'produit = stockable, outil = immobilisé',
    unite ENUM('kg','g','litre','ml','sac','piece','rouleau','bidon','autre') NOT NULL DEFAULT 'piece',
    prix_unitaire DECIMAL(8,2) DEFAULT NULL,
    fournisseur_id INT DEFAULT NULL,
    marque VARCHAR(100) DEFAULT NULL,
    bio TINYINT(1) DEFAULT 0,
    danger TEXT DEFAULT NULL COMMENT 'Pictogrammes / mentions sécurité',
    photo VARCHAR(500) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 4. INVENTAIRE (stock par résidence)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    produit_id INT NOT NULL,
    residence_id INT NOT NULL,
    quantite_actuelle DECIMAL(10,3) DEFAULT 0.000,
    seuil_alerte DECIMAL(10,3) DEFAULT 0.000,
    emplacement VARCHAR(150) DEFAULT NULL COMMENT 'Cabane jardin, Réserve cave...',
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_produit_residence (produit_id, residence_id),
    FOREIGN KEY (produit_id) REFERENCES jardin_produits(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 5. MOUVEMENTS INVENTAIRE (traçabilité)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type_mouvement ENUM('entree','sortie','ajustement') NOT NULL,
    quantite DECIMAL(10,3) NOT NULL,
    motif ENUM('livraison','usage','perte','casse','inventaire','autre') NOT NULL DEFAULT 'usage',
    espace_id INT DEFAULT NULL COMMENT 'Pour les sorties : espace jardin affecté (calcul coût par espace)',
    user_id INT DEFAULT NULL,
    notes VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES jardin_inventaire(id) ON DELETE CASCADE,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 6. RUCHES (apiculture) — visibles si coproprietees.ruches = 1
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_ruches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    espace_id INT DEFAULT NULL COMMENT 'FK vers jardin_espaces (type=rucher)',
    numero VARCHAR(50) NOT NULL COMMENT 'Ruche A1, R-2026-03...',
    type_ruche VARCHAR(100) DEFAULT NULL COMMENT 'Dadant, Warré, Langstroth...',
    date_installation DATE DEFAULT NULL,
    race_abeilles VARCHAR(100) DEFAULT NULL COMMENT 'Buckfast, Carnica, Noire...',
    statut ENUM('active','essaim_capture','inactive','morte') DEFAULT 'active',
    notes TEXT DEFAULT NULL,
    photo VARCHAR(500) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (espace_id) REFERENCES jardin_espaces(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS jardin_ruches_visites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruche_id INT NOT NULL,
    date_visite DATE NOT NULL,
    user_id INT NOT NULL,
    type_intervention ENUM('inspection','recolte','traitement','nourrissement','changement_reine','division','urgence','autre') NOT NULL DEFAULT 'inspection',
    couvain_etat ENUM('excellent','bon','moyen','faible','absent') DEFAULT NULL,
    reine_vue TINYINT(1) DEFAULT NULL,
    quantite_miel_kg DECIMAL(6,2) DEFAULT NULL,
    traitement_produit VARCHAR(150) DEFAULT NULL,
    observations TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruche_id) REFERENCES jardin_ruches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 7. PIVOT FOURNISSEURS ↔ RÉSIDENCES (jardinerie)
-- ─────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS jardin_fournisseur_residence (
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
    UNIQUE KEY uk_fournisseur_residence (fournisseur_id, residence_id),
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ─────────────────────────────────────────────────────────────
-- 8. INDEX DE PERFORMANCE
-- ─────────────────────────────────────────────────────────────

CREATE INDEX idx_jardin_espaces_residence ON jardin_espaces(residence_id, actif);
CREATE INDEX idx_jardin_taches_espace ON jardin_taches(espace_id, actif);
CREATE INDEX idx_jardin_produits_categorie ON jardin_produits(categorie, type, actif);
CREATE INDEX idx_jardin_inventaire_alerte ON jardin_inventaire(quantite_actuelle, seuil_alerte);
CREATE INDEX idx_jardin_mouvements_inv ON jardin_inventaire_mouvements(inventaire_id, created_at);
CREATE INDEX idx_jardin_ruches_residence ON jardin_ruches(residence_id, statut);
CREATE INDEX idx_jardin_visites_ruche ON jardin_ruches_visites(ruche_id, date_visite);

-- ─────────────────────────────────────────────────────────────
-- 9. SEED — Activer les ruches sur environ 50% des résidences
-- ─────────────────────────────────────────────────────────────
-- La Badiane (id 61) doit obligatoirement avoir des ruches.
-- On active aussi les résidences à profil "vert" (jardins, parc, oliviers...)

UPDATE coproprietees SET ruches = 1 WHERE id IN (
    61,  -- La Badiane (obligatoire)
    62,  -- Les Jardins d'Arcadie
    63,  -- Le Parc de Jade
    66,  -- Le Fil de Lumière
    68,  -- Le Jardin des Arceaux
    69,  -- Les Oliviers d'Azur
    70,  -- La Fleur de Bretagne
    72,  -- Les Cimes Vertes
    73,  -- Les Vignes d'Or
    74,  -- Les Jardins de Loire
    79,  -- Le Palmier du Roi
    93   -- Le Puy des Olivines
);
