-- ====================================================================
-- Migration 029 : catégorie 'ag' pour calendrier propriétaire
-- ====================================================================
-- Permet d'afficher les Assemblées Générales dans le calendrier propriétaire
-- avec une couleur dédiée (violet, distincte de loyer/échéance/fiscal/etc.)
-- ====================================================================

INSERT INTO planning_proprio_categories (slug, nom, couleur, bg_couleur, icone, auto_genere, actif, ordre)
SELECT 'ag', 'Assemblée Générale', '#6610f2', '#e2d4f9', 'fas fa-gavel', 1, 1, 7
WHERE NOT EXISTS (SELECT 1 FROM planning_proprio_categories WHERE slug = 'ag');
