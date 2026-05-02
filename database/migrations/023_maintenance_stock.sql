-- ====================================================================
-- Migration 023 : Stock + commandes module Maintenance Technique
-- ====================================================================
-- Étend les ENUMs existants pour inclure 'maintenance' :
--   - commandes.module
--   - produit_fournisseurs.produit_module
--
-- Pas de nouvelles tables : on utilise l'infrastructure unifiée
-- (commandes, commande_lignes, fournisseur_residence, produit_fournisseurs)
-- en pointant vers maintenance_produits (créée en migration 019).
-- ====================================================================

ALTER TABLE commandes
    MODIFY COLUMN module ENUM('restauration','menage','jardinage','travaux','piscine','entretien','laverie','maintenance','autre') NOT NULL;

ALTER TABLE produit_fournisseurs
    MODIFY COLUMN produit_module ENUM('restauration','menage','jardinage','travaux','piscine','entretien','laverie','maintenance','autre') NOT NULL;
