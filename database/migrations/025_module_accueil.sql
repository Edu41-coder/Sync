-- ====================================================================
-- Migration 025 : Module Accueil — fondations
-- ====================================================================
-- Crée :
--   1. 2 rôles : accueil_manager + accueil_employe
--   2. accueil_salles            : catalogue salles communes par résidence
--   3. accueil_equipements       : catalogue équipements prêtables par résidence
--   4. accueil_reservations      : réservations multi-types (salle/équip/service perso)
--   5. accueil_animation_inscriptions : pivot résident ↔ shift animation
--   6. resident_notes_accueil    : notes texte libre sur résidents
--
-- Réutilise (pas de nouvelle table) :
--   - planning_shifts catégorie 'animation' pour les animations
--   - hotes_temporaires pour les hôtes (HoteController existant)
--   - messages pour la messagerie interne
-- ====================================================================

-- ────────────────────────────────────────────────────────────────────
-- 1. Rôles
-- ────────────────────────────────────────────────────────────────────
INSERT IGNORE INTO roles (slug, nom_affichage, description, categorie, couleur, icone, ordre_affichage, actif)
VALUES
    ('accueil_manager', 'Responsable Accueil',
     'Pilotage de l''accueil de la résidence : équipe, catalogues salles/équipements, réservations, animations, hôtes temporaires, messagerie groupée.',
     'staff', '#0dcaf0', 'fa-concierge-bell', 8, 1),
    ('accueil_employe', 'Employé Accueil',
     'Opérations courantes de l''accueil : réservations, inscriptions résidents aux animations, notes résidents, gestion hôtes temporaires.',
     'staff', '#0dcaf0', 'fa-bell-concierge', 9, 1);

-- ────────────────────────────────────────────────────────────────────
-- 2. Catalogue salles communes
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS accueil_salles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT NULL,
    capacite_personnes INT NULL,
    equipements_inclus TEXT NULL COMMENT 'Texte libre : Wi-Fi, projecteur, kitchenette…',
    photo VARCHAR(512) NULL COMMENT 'Stockage public/uploads/accueil/salles/',
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    INDEX idx_residence_actif (residence_id, actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 3. Catalogue équipements prêtables
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS accueil_equipements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    type ENUM('mobilite','informatique','loisirs','medical','autre') DEFAULT 'autre',
    numero_serie VARCHAR(100) NULL,
    statut ENUM('disponible','prete','hors_service','maintenance') DEFAULT 'disponible',
    notes TEXT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    INDEX idx_residence_statut (residence_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 4. Réservations multi-types
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS accueil_reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    type_reservation ENUM('salle','equipement','service_personnel') NOT NULL,

    -- Cibles selon le type (un seul des trois renseigné)
    salle_id INT NULL,
    equipement_id INT NULL,
    type_service VARCHAR(100) NULL COMMENT 'Pour service_personnel : coiffeur, pédicure, taxi, autre',

    -- Demandeur (résident ou hôte temporaire)
    resident_id INT NULL,
    hote_id INT NULL,

    titre VARCHAR(255) NOT NULL,
    description TEXT NULL,
    date_debut DATETIME NOT NULL,
    date_fin DATETIME NOT NULL,

    -- Workflow
    statut ENUM('en_attente','confirmee','refusee','annulee','realisee') DEFAULT 'en_attente',
    motif_refus TEXT NULL,
    valide_par_id INT NULL,
    valide_le DATETIME NULL,

    notes TEXT NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (salle_id) REFERENCES accueil_salles(id) ON DELETE SET NULL,
    FOREIGN KEY (equipement_id) REFERENCES accueil_equipements(id) ON DELETE SET NULL,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE SET NULL,
    FOREIGN KEY (hote_id) REFERENCES hotes_temporaires(id) ON DELETE SET NULL,
    FOREIGN KEY (valide_par_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_residence_statut (residence_id, statut),
    INDEX idx_dates (date_debut, date_fin),
    INDEX idx_resident (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 5. Inscriptions résidents aux animations (= shift staff catégorie animation)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS accueil_animation_inscriptions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shift_id INT NOT NULL COMMENT 'FK planning_shifts (catégorie animation)',
    resident_id INT NOT NULL,
    statut ENUM('inscrit','present','absent','annule') DEFAULT 'inscrit',
    inscrit_par_id INT NULL,
    inscrit_le DATETIME DEFAULT CURRENT_TIMESTAMP,
    notes TEXT NULL,
    UNIQUE KEY uniq_shift_resident (shift_id, resident_id),
    FOREIGN KEY (shift_id) REFERENCES planning_shifts(id) ON DELETE CASCADE,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (inscrit_par_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_shift (shift_id),
    INDEX idx_resident (resident_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 6. Notes texte libre sur résidents (vues par accueil + direction)
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS resident_notes_accueil (
    id INT AUTO_INCREMENT PRIMARY KEY,
    resident_id INT NOT NULL,
    contenu TEXT NOT NULL,
    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (resident_id) REFERENCES residents_seniors(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_resident_date (resident_id, created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
