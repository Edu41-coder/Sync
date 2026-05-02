-- ====================================================================
-- Migration 022 : Ascenseurs (entité dédiée + journal de bord)
-- ====================================================================
-- Une résidence peut avoir 0..N ascenseurs.
-- Chaque ascenseur a son propre journal (maintenances, visites, contrôles).
--
-- Le flag coproprietees.ascenseur (ajouté en migration 019) est désormais
-- auto-maintenu par 3 triggers (INSERT, UPDATE, DELETE) :
--   = 1 si ≥ 1 ascenseur de statut 'actif' sur la résidence, 0 sinon.
-- ====================================================================

-- ────────────────────────────────────────────────────────────────────
-- 1. Table ascenseurs
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ascenseurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,

    -- Identité
    nom VARCHAR(100) NOT NULL COMMENT 'Ex: Ascenseur A, Bâtiment Nord, Service',
    numero_serie VARCHAR(100) NULL,
    emplacement VARCHAR(255) NULL COMMENT 'Ex: Hall principal, étages 0-5',

    -- Caractéristiques techniques
    marque ENUM('schindler','otis','kone','thyssenkrupp','mitsubishi','orona','autre') DEFAULT 'autre',
    modele VARCHAR(100) NULL,
    capacite_kg INT NULL,
    capacite_personnes INT NULL,
    nombre_etages INT NULL,
    date_mise_service DATE NULL,

    -- Contrat ascensoriste
    contrat_ascensoriste_nom VARCHAR(200) NULL,
    contrat_ascensoriste_tel VARCHAR(50) NULL,
    contrat_ascensoriste_email VARCHAR(200) NULL,
    contrat_numero VARCHAR(100) NULL,

    -- Cycle de vie
    statut ENUM('actif','hors_service','depose') DEFAULT 'actif',
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    INDEX idx_residence_statut (residence_id, statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 2. Journal de bord par ascenseur
-- ────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS ascenseur_journal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ascenseur_id INT NOT NULL,
    type_entree ENUM('maintenance_preventive','visite_annuelle','controle_quinquennal','panne','intervention','autre') NOT NULL,
    date_event DATETIME NOT NULL,

    -- Intervenants
    organisme VARCHAR(200) NULL COMMENT 'Bureau de contrôle / ascensoriste',
    technicien_intervenant VARCHAR(200) NULL,

    -- Conformité (visite annuelle, contrôle quinquennal)
    numero_pv VARCHAR(100) NULL,
    conformite ENUM('conforme','non_conforme','avec_reserves') NULL,
    fichier_pv VARCHAR(512) NULL COMMENT 'Stockage uploads/maintenance/ascenseur_pv/',

    -- Lien optionnel vers intervention (cf. décision : entrée journal type=panne ↔ intervention)
    intervention_id INT NULL,

    -- Échéance suivante (pour alertes)
    prochaine_echeance DATE NULL,

    -- Détails
    observations TEXT NULL,
    cout DECIMAL(10,2) NULL,

    created_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (ascenseur_id) REFERENCES ascenseurs(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_id) REFERENCES maintenance_interventions(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_ascenseur_date (ascenseur_id, date_event DESC),
    INDEX idx_echeance (prochaine_echeance)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ────────────────────────────────────────────────────────────────────
-- 3. Triggers : auto-maintien coproprietees.ascenseur
-- ────────────────────────────────────────────────────────────────────
DROP TRIGGER IF EXISTS trg_ascenseurs_after_insert;
CREATE TRIGGER trg_ascenseurs_after_insert AFTER INSERT ON ascenseurs
FOR EACH ROW
    UPDATE coproprietees
    SET ascenseur = (SELECT COUNT(*) > 0 FROM ascenseurs WHERE residence_id = NEW.residence_id AND statut = 'actif')
    WHERE id = NEW.residence_id;

DROP TRIGGER IF EXISTS trg_ascenseurs_after_update;
CREATE TRIGGER trg_ascenseurs_after_update AFTER UPDATE ON ascenseurs
FOR EACH ROW
    UPDATE coproprietees
    SET ascenseur = (SELECT COUNT(*) > 0 FROM ascenseurs WHERE residence_id = NEW.residence_id AND statut = 'actif')
    WHERE id = NEW.residence_id;

DROP TRIGGER IF EXISTS trg_ascenseurs_after_delete;
CREATE TRIGGER trg_ascenseurs_after_delete AFTER DELETE ON ascenseurs
FOR EACH ROW
    UPDATE coproprietees
    SET ascenseur = (SELECT COUNT(*) > 0 FROM ascenseurs WHERE residence_id = OLD.residence_id AND statut = 'actif')
    WHERE id = OLD.residence_id;

-- ────────────────────────────────────────────────────────────────────
-- 4. Resync initiale du flag (au cas où des données existeraient)
-- ────────────────────────────────────────────────────────────────────
UPDATE coproprietees c
SET ascenseur = (SELECT COUNT(*) > 0 FROM ascenseurs WHERE residence_id = c.id AND statut = 'actif');
