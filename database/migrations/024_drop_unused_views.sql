-- ====================================================================
-- Migration 024 : Suppression des 10 vues SQL
-- ====================================================================
-- Audit : sur 10 vues définies en BDD, 9 ne sont jamais référencées
-- par le code PHP, et la 10ème (`v_taux_occupation`) contenait un filtre
-- obsolète (`l.type = 'appartement'`) alors que l'ENUM lots.type vaut
-- aujourd'hui ('studio','t2','t2_bis','t3','parking','cave').
-- Conséquence : le dashboard admin retournait `taux_occupation_moyen = 0`
-- en permanence, sans que personne ne s'en aperçoive.
--
-- Décision : supprimer les 10 vues. Le calcul du taux d'occupation est
-- maintenant fait inline dans User::getAdminDashboardStats() avec le bon
-- filtre sur les lots habitables (studio/t2/t2_bis/t3, exclusion parking/cave).
--
-- Avantages :
--   - 1 seule source de vérité (le PHP) au lieu de 2 (PHP + view SQL)
--   - Pas de drift silencieux entre l'ENUM applicatif et le filtre SQL
--   - Plus simple à auditer / faire évoluer
-- ====================================================================

DROP VIEW IF EXISTS v_taux_occupation;
DROP VIEW IF EXISTS v_comptes_coproprietaires;
DROP VIEW IF EXISTS v_lots_coproprietaires;
DROP VIEW IF EXISTS v_permissions_details;
DROP VIEW IF EXISTS v_permissions_summary;
DROP VIEW IF EXISTS v_residents_logements;
DROP VIEW IF EXISTS v_revenus_proprietaires;
DROP VIEW IF EXISTS v_situation_appels_fonds;
DROP VIEW IF EXISTS v_suivi_paiements_exploitant;
DROP VIEW IF EXISTS vw_residence_exploitants;
