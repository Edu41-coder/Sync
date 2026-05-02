-- ====================================================================
-- Migration 019 : Module Maintenance Technique
-- ====================================================================
-- Module unifié couvrant 6 spécialités techniques :
--   piscine, ascenseur, travaux, plomberie, electricite, peinture
--
-- Structure :
--   - rôle technicien_chef (manager du module)
--   - referentiels : specialites, user_specialites, user_certifications
--   - interventions courantes (entretien_courant) : maintenance_interventions
--   - chantiers planifiés : chantiers + tables associées (devis, jalons,
--     documents, receptions, garanties, lots_impactes)
--   - inventaire dédié : maintenance_produits / inventaire / mouvements
--
-- Note : la table legacy `travaux` (vide, stub) est laissée intacte pour
-- compatibilité ; le nouveau module utilise `chantiers`.
-- ====================================================================

-- ────────────────────────────────────────────────────────────────────
-- 1. Rôle technicien_chef
-- ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO roles (slug, nom_affichage, description, categorie, couleur, icone, ordre_affichage, actif)
VALUES (
    'technicien_chef',
    'Chef Technique',
    'Responsable maintenance technique : pilotage interventions, chantiers, prestataires externes, certifications.',
    'staff',
    '#fd7e14',
    'fa-hard-hat',
    7,
    1
);

-- ────────────────────────────────────────────────────────────────────
-- 2. Flags coproprietees : piscine / ascenseur (pour masquer sections inutiles)
-- ────────────────────────────────────────────────────────────────────
ALTER TABLE coproprietees
    ADD COLUMN piscine TINYINT(1) DEFAULT 0 COMMENT '1 si la résidence dispose d''une piscine' AFTER ruches,
    ADD COLUMN ascenseur TINYINT(1) DEFAULT 0 COMMENT '1 si la résidence dispose d''un ascenseur' AFTER piscine;

-- ────────────────────────────────────────────────────────────────────
-- 3. Référentiel des spécialités
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS specialites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    icone VARCHAR(50) DEFAULT 'fas fa-wrench',
    couleur VARCHAR(7) DEFAULT '#fd7e14',
    bg_couleur VARCHAR(7) DEFAULT '#ffe5d0',
    certif_obligatoire TINYINT(1) DEFAULT 0 COMMENT '1 si une certification est légalement requise',
    organisme_recommande VARCHAR(150) NULL COMMENT 'ex: ARS pour piscine, COFRAC pour ascenseur',
    ordre INT DEFAULT 0,
    actif TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO specialites (slug, nom, description, icone, couleur, bg_couleur, certif_obligatoire, organisme_recommande, ordre) VALUES
('piscine',     'Piscine',     'Traitement chimique, hivernage, contrôles ARS', 'fas fa-swimming-pool', '#0dcaf0', '#cff4fc', 1, 'ARS / Préfecture', 1),
('ascenseur',   'Ascenseur',   'Maintenance préventive, visite annuelle obligatoire', 'fas fa-elevator', '#6c757d', '#e2e3e5', 1, 'COFRAC / Bureau de contrôle', 2),
('travaux',     'Travaux',     'Chantiers planifiés, gros œuvre, rénovation', 'fas fa-hammer', '#fd7e14', '#ffe5d0', 0, NULL, 3),
('plomberie',   'Plomberie',   'Réparations, robinetterie, sanitaires, chauffe-eau', 'fas fa-faucet', '#0d6efd', '#cfe2ff', 0, NULL, 4),
('electricite', 'Électricité', 'Luminaires, prises, tableaux, BAES, conformité', 'fas fa-bolt', '#ffc107', '#fff3cd', 1, 'CONSUEL / habilitation', 5),
('peinture',    'Peinture',    'Rafraîchissement parties communes, finitions', 'fas fa-paint-roller', '#198754', '#d1e7dd', 0, NULL, 6);

-- ────────────────────────────────────────────────────────────────────
-- 4. Affectation user × spécialité
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_specialites (
    user_id INT NOT NULL,
    specialite_id INT NOT NULL,
    niveau ENUM('debutant', 'confirme', 'expert') DEFAULT 'confirme',
    notes TEXT NULL,
    affecte_par INT NULL COMMENT 'user_id du chef qui a affecté',
    affecte_le DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, specialite_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialite_id) REFERENCES specialites(id) ON DELETE CASCADE,
    FOREIGN KEY (affecte_par) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 5. Certifications professionnelles (avec validité, traçabilité)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS user_certifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    specialite_id INT NULL COMMENT 'NULL = certification générique non liée à une spécialité',
    nom VARCHAR(200) NOT NULL COMMENT 'Ex: Habilitation B1V, BNSSA, CACES R486',
    organisme VARCHAR(200) NULL COMMENT 'Ex: AFNOR, INRS, APAVE, ARS',
    numero_certificat VARCHAR(100) NULL,
    date_obtention DATE NOT NULL,
    date_expiration DATE NULL COMMENT 'NULL = sans expiration',
    fichier_preuve VARCHAR(512) NULL COMMENT 'Chemin relatif vers PDF/image dans uploads/maintenance/certifs/',
    actif TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (specialite_id) REFERENCES specialites(id) ON DELETE SET NULL,
    INDEX idx_user_actif (user_id, actif),
    INDEX idx_expiration (date_expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 6. Interventions de maintenance (entretien courant + petites réparations)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS maintenance_interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    specialite_id INT NOT NULL,
    lot_id INT NULL COMMENT 'Si intervention sur un lot précis',
    titre VARCHAR(255) NOT NULL,
    description TEXT NULL,
    type_intervention ENUM('entretien_courant', 'reparation', 'controle_reglementaire', 'urgence') DEFAULT 'entretien_courant',
    priorite ENUM('basse', 'normale', 'haute', 'urgente') DEFAULT 'normale',
    statut ENUM('a_planifier', 'planifiee', 'en_cours', 'terminee', 'annulee') DEFAULT 'a_planifier',
    user_assigne_id INT NULL,
    prestataire_externe VARCHAR(200) NULL COMMENT 'Si intervention sous-traitée',
    prestataire_externe_tel VARCHAR(50) NULL,
    date_signalement DATETIME DEFAULT CURRENT_TIMESTAMP,
    date_planifiee DATETIME NULL,
    date_realisee DATETIME NULL,
    duree_minutes INT NULL,
    cout DECIMAL(10,2) NULL,
    photo_avant VARCHAR(512) NULL,
    photo_apres VARCHAR(512) NULL,
    notes TEXT NULL,
    created_by INT NULL,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (specialite_id) REFERENCES specialites(id) ON DELETE RESTRICT,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE SET NULL,
    FOREIGN KEY (user_assigne_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_residence_statut (residence_id, statut),
    INDEX idx_specialite_statut (specialite_id, statut),
    INDEX idx_assigne (user_assigne_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 7. Chantiers (workflow complet : diagnostic → garanties)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    specialite_id INT NULL COMMENT 'Spécialité principale (peut être NULL pour chantiers multi-specialites)',
    titre VARCHAR(255) NOT NULL,
    description TEXT NULL,
    categorie ENUM('gros_oeuvre','second_oeuvre','plomberie','electricite','chauffage','peinture','toiture','facade','ascenseur','piscine','mise_aux_normes','amenagement','autre') NOT NULL,
    phase ENUM('diagnostic','cahier_charges','devis','decision','commande','execution','reception','garantie','cloture') DEFAULT 'diagnostic',
    statut ENUM('actif','suspendu','termine','annule') DEFAULT 'actif',
    priorite ENUM('basse','normale','haute','urgente') DEFAULT 'normale',
    necessite_ag TINYINT(1) DEFAULT 0 COMMENT 'Vote AG requis (auto si > 5000€ HT, forçable manuellement)',
    ag_id INT NULL,
    date_debut_prevue DATE NULL,
    date_fin_prevue DATE NULL,
    date_debut_reelle DATE NULL,
    date_fin_reelle DATE NULL,
    montant_estime DECIMAL(12,2) NULL,
    montant_engage DECIMAL(12,2) DEFAULT 0,
    montant_paye DECIMAL(12,2) DEFAULT 0,
    notes TEXT NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (specialite_id) REFERENCES specialites(id) ON DELETE SET NULL,
    FOREIGN KEY (ag_id) REFERENCES assemblees_generales(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_residence_phase (residence_id, phase),
    INDEX idx_statut (statut, phase)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 8. Devis multi-prestataires sur un chantier
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_devis (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    fournisseur_id INT NOT NULL,
    reference VARCHAR(100) NULL,
    date_devis DATE NOT NULL,
    date_validite DATE NULL,
    montant_ht DECIMAL(12,2) NOT NULL,
    tva_pourcentage DECIMAL(5,2) DEFAULT 20.00,
    montant_ttc DECIMAL(12,2) NOT NULL,
    delai_execution_jours INT NULL,
    fichier_pdf VARCHAR(512) NULL,
    statut ENUM('recu','analyse','retenu','refuse') DEFAULT 'recu',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE RESTRICT,
    INDEX idx_chantier_statut (chantier_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 9. Jalons / phases d'avancement
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_jalons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    nom VARCHAR(200) NOT NULL,
    description TEXT NULL,
    date_prevue DATE NULL,
    date_realisee DATE NULL,
    pourcentage_avancement INT DEFAULT 0,
    ordre INT DEFAULT 0,
    notes TEXT NULL,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    INDEX idx_chantier_ordre (chantier_id, ordre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 10. Documents associés au chantier (devis signés, plans, photos, factures)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    type ENUM('devis_signe','plan','photo_avant','photo_apres','photo_chantier','pv_reception','facture','garantie','attestation','autre') NOT NULL,
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) NULL,
    taille_octets BIGINT DEFAULT 0,
    description TEXT NULL,
    uploaded_by INT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_chantier_type (chantier_id, type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 11. Réceptions (PV de réception avec/sans réserves)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_receptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    date_reception DATE NOT NULL,
    avec_reserves TINYINT(1) DEFAULT 0,
    reserves_description TEXT NULL,
    reserves_levees DATE NULL,
    pv_pdf VARCHAR(512) NULL,
    signe_par_id INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (signe_par_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 12. Garanties (parfait achèvement / biennale / décennale)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_garanties (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chantier_id INT NOT NULL,
    type ENUM('parfait_achevement','biennale','decennale') NOT NULL,
    date_debut DATE NOT NULL,
    date_fin DATE NOT NULL,
    fournisseur_id INT NULL,
    notes TEXT NULL,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (fournisseur_id) REFERENCES fournisseurs(id) ON DELETE SET NULL,
    INDEX idx_fin (date_fin)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 13. Lots impactés par un chantier (calcul quote-part propriétaire)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS chantier_lots_impactes (
    chantier_id INT NOT NULL,
    lot_id INT NOT NULL,
    quote_part_pourcentage DECIMAL(5,2) DEFAULT 0,
    PRIMARY KEY (chantier_id, lot_id),
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE CASCADE,
    FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 14. Inventaire dédié maintenance technique
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS maintenance_produits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(200) NOT NULL,
    specialite_id INT NULL COMMENT 'Spécialité associée (NULL = générique)',
    categorie ENUM('consommable','piece_detachee','outillage_main','outillage_motorise','produit_chimique','epi','autre') NOT NULL,
    type ENUM('produit','outil') NOT NULL DEFAULT 'produit',
    unite VARCHAR(20) NULL,
    prix_unitaire DECIMAL(8,2) NULL,
    fiche_securite VARCHAR(512) NULL COMMENT 'PDF FDS si produit chimique',
    actif TINYINT(1) DEFAULT 1,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (specialite_id) REFERENCES specialites(id) ON DELETE SET NULL,
    INDEX idx_specialite_actif (specialite_id, actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_inventaire (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    produit_id INT NOT NULL,
    quantite_actuelle DECIMAL(10,3) DEFAULT 0,
    seuil_alerte DECIMAL(10,3) NULL,
    emplacement VARCHAR(150) NULL,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_residence_produit (residence_id, produit_id),
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (produit_id) REFERENCES maintenance_produits(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS maintenance_inventaire_mouvements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    inventaire_id INT NOT NULL,
    type_mouvement ENUM('entree','sortie','ajustement') NOT NULL,
    quantite DECIMAL(10,3) NOT NULL,
    motif ENUM('livraison','usage','perte','casse','inventaire','intervention','autre') NOT NULL,
    user_id INT NULL,
    intervention_id INT NULL COMMENT 'Si sortie liée à une intervention',
    chantier_id INT NULL COMMENT 'Si sortie liée à un chantier',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (inventaire_id) REFERENCES maintenance_inventaire(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (intervention_id) REFERENCES maintenance_interventions(id) ON DELETE SET NULL,
    FOREIGN KEY (chantier_id) REFERENCES chantiers(id) ON DELETE SET NULL,
    INDEX idx_inventaire_date (inventaire_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
