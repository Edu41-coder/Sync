-- ====================================================================
-- Migration 024 : Suppression de la table role_descriptions (legacy)
-- ====================================================================
-- Cette table était l'ancêtre de `roles` (créée le 2025-11-30, avant
-- migration 001). Elle duplique 6 colonnes de `roles` (role, nom_affichage,
-- description, couleur, icone, ordre_affichage) mais sans les colonnes
-- importantes (slug, categorie, actif).
--
-- Vérifs faites avant suppression :
--   - 0 FK entrante (aucune table ne la référence)
--   - 1 seule lecture dans le code : User::getRoleDescription() qui
--     n'est appelée NULLE PART
--   - Données obsolètes (description technicien encore "chauffage,
--     maçonnerie" alors que `roles` est à jour)
--
-- La méthode User::getRoleDescription() sera également supprimée (code mort).
-- ====================================================================

DROP TABLE IF EXISTS role_descriptions;
