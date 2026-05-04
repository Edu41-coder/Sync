-- ====================================================================
-- Migration 028 : Module Assemblées Générales — extensions
-- ====================================================================
-- Les tables `assemblees_generales` et `votes_ag` existent déjà
-- mais étaient inutilisées (aucun controller/modèle).
-- Cette migration ajoute les colonnes manquantes pour le workflow
-- complet : convocation, quorum, président/secrétaire, mode séance.
-- ====================================================================

ALTER TABLE assemblees_generales
    ADD COLUMN convocation_envoyee_le DATETIME NULL AFTER document_convocation,
    ADD COLUMN mode ENUM('presentiel','visio','mixte') NOT NULL DEFAULT 'presentiel' AFTER lieu,
    ADD COLUMN quorum_requis INT NULL COMMENT 'Quorum requis (en tantièmes ou nb voix selon règlement)' AFTER quorum_atteint,
    ADD COLUMN quorum_present INT NULL COMMENT 'Quorum effectivement présent' AFTER quorum_requis,
    ADD COLUMN votants_total INT NULL COMMENT 'Nombre de propriétaires présents/représentés' AFTER quorum_present,
    ADD COLUMN president_seance_id INT NULL AFTER votants_total,
    ADD COLUMN secretaire_id INT NULL AFTER president_seance_id,
    ADD COLUMN notes_internes TEXT NULL COMMENT 'Notes privées équipe gestion (non publiées aux propriétaires)' AFTER proces_verbal,
    ADD COLUMN created_by INT NULL AFTER notes_internes,
    ADD CONSTRAINT fk_ag_president  FOREIGN KEY (president_seance_id) REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ag_secretaire FOREIGN KEY (secretaire_id)       REFERENCES users(id) ON DELETE SET NULL,
    ADD CONSTRAINT fk_ag_createur   FOREIGN KEY (created_by)          REFERENCES users(id) ON DELETE SET NULL,
    ADD INDEX idx_residence_statut (copropriete_id, statut),
    ADD INDEX idx_date_ag (date_ag);
