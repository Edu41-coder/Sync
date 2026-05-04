-- ====================================================================
-- Migration 027 : Lien chantiers ↔ sinistres
-- ====================================================================
-- Un chantier de Maintenance peut être déclenché par un sinistre (origine
-- = sinistre). Ajout d'une FK NULLABLE pour matérialiser cette relation 1→N
-- (un sinistre → 0 ou N chantiers ; un chantier → 0 ou 1 sinistre).
--
-- ON DELETE SET NULL : si le sinistre est supprimé, on conserve le chantier
-- (qui peut avoir été facturé/payé) en perdant juste la traçabilité d'origine.
-- ====================================================================

ALTER TABLE chantiers
    ADD COLUMN sinistre_id INT DEFAULT NULL COMMENT 'Si chantier déclenché par un sinistre' AFTER ag_id,
    ADD CONSTRAINT fk_chantiers_sinistre
        FOREIGN KEY (sinistre_id) REFERENCES sinistres(id) ON DELETE SET NULL,
    ADD INDEX idx_chantiers_sinistre (sinistre_id);
