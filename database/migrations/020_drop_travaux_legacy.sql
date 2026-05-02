-- ====================================================================
-- Migration 020 : Suppression du module Travaux legacy
-- ====================================================================
-- Le module Travaux historique (table `travaux` + TravauxController stub)
-- n'a jamais été branché à du SQL applicatif (controller vide, 0 ligne en BDD).
-- Il est remplacé par le module Maintenance Technique (migration 019)
-- qui utilise la table `chantiers` + tables associées.
--
-- Cleanup :
--   1. drop FK travaux_id sur devis et documents (toujours NULL)
--   2. drop colonnes travaux_id (plus aucune référence applicative)
--   3. drop table travaux
-- ====================================================================

ALTER TABLE devis DROP FOREIGN KEY devis_ibfk_1;
ALTER TABLE devis DROP COLUMN travaux_id;

ALTER TABLE documents DROP FOREIGN KEY documents_ibfk_4;
ALTER TABLE documents DROP COLUMN travaux_id;

DROP TABLE IF EXISTS travaux;
