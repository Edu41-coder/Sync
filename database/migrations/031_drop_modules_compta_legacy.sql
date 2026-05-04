-- ====================================================================
-- Migration 031 — DROP des tables compta des modules (Phase 1)
-- ====================================================================
-- Suite à la refonte Phase 1 du module Comptabilité, les écritures sont
-- centralisées dans `ecritures_comptables` avec discrimination via
-- `module_source`. Les 3 tables historiques sont supprimées.
--
-- Tables supprimées :
--   - jardin_comptabilite (était vide en BDD avant DROP — vérifié)
--   - menage_comptabilite (idem)
--   - rest_comptabilite   (idem)
--
-- Maintenance n'a pas de table compta dédiée — elle continue d'agréger
-- depuis maintenance_interventions, chantiers, ascenseur_journal.
--
-- Models adaptés (Phase 1) :
--   - app/models/Jardinage.php    : createEcriture/getEcritures/getTotauxAnnuels/...
--   - app/models/Menage.php       : idem
--   - app/models/Restauration.php : idem
--   API publique conservée à l'identique → controllers inchangés.
-- ====================================================================

DROP TABLE IF EXISTS jardin_comptabilite;
DROP TABLE IF EXISTS menage_comptabilite;
DROP TABLE IF EXISTS rest_comptabilite;

-- Enregistrement
INSERT IGNORE INTO migrations (migration, batch, applied_at)
VALUES ('031_drop_modules_compta_legacy', (SELECT COALESCE(MAX(b)+1, 1) FROM (SELECT MAX(batch) AS b FROM migrations) AS x), NOW());
