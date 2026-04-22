-- ====================================================================
-- Migration 009 : Configuration apiculture par résidence
-- ====================================================================
-- Table 1:1 avec coproprietees (remplie seulement si coproprietees.ruches = 1).
-- Stocke les paramètres réglementaires et opérationnels de l'apiculture
-- (NAPI, référent, déclaration préfecture, distance habitations, etc.).
--
-- Phase 2 — groupe A (hygiène admin)
-- ====================================================================

CREATE TABLE IF NOT EXISTS coproprietees_apiculture (
    residence_id INT PRIMARY KEY,
    numero_napi VARCHAR(50) DEFAULT NULL COMMENT 'Numéro NAPI (déclaration préfecture)',
    date_declaration_prefecture DATE DEFAULT NULL,
    nombre_max_ruches INT DEFAULT NULL COMMENT 'Capacité maximale autorisée sur le site',
    apiculteur_referent_user_id INT DEFAULT NULL COMMENT 'User référent (jardinier_manager ou direction)',
    apiculteur_referent_externe VARCHAR(200) DEFAULT NULL COMMENT 'Si prestataire externe (texte libre)',
    type_rucher ENUM('sedentaire','transhumant') DEFAULT 'sedentaire',
    distance_habitations_m INT DEFAULT NULL COMMENT 'Distance minimale aux habitations (FR urbain = 400m)',
    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (apiculteur_referent_user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_apiculture_referent ON coproprietees_apiculture(apiculteur_referent_user_id);
