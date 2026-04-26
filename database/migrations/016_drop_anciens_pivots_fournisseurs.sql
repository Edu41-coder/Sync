-- ====================================================================
-- Migration 016 : Suppression des anciens pivots fournisseur↔résidence
-- ====================================================================
-- Phase 3 du refactor fournisseurs globaux (voir migrations 014, 015) :
-- maintenant que tout le code (Menage, Restauration, Jardinage + FournisseurController)
-- pointe sur `fournisseur_residence`, on peut supprimer les anciens pivots.
--
-- Les données étaient déjà migrées vers `fournisseur_residence` dans la migration 014.
-- ====================================================================

DROP TABLE IF EXISTS rest_fournisseur_residence;
DROP TABLE IF EXISTS jardin_fournisseur_residence;
