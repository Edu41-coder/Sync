-- ====================================================================
-- Migration des comptes "resident" vers residents_seniors
-- Date: 12 décembre 2025
-- ====================================================================
-- Ce script crée des profils complets pour tous les utilisateurs 
-- ayant le rôle "resident" qui n'ont pas encore de profil senior

-- Insertion des résidents manquants dans residents_seniors
INSERT INTO residents_seniors 
    (user_id, civilite, nom, prenom, date_naissance, telephone_mobile, email, 
     niveau_autonomie, actif, date_entree, created_at, updated_at)
SELECT 
    u.id as user_id,
    CASE 
        WHEN u.prenom IN ('Jeanne', 'Marie', 'Françoise', 'Monique', 'Jacqueline', 'Simone', 'Denise', 'Paulette') THEN 'Mme'
        WHEN u.prenom IN ('Mlle') THEN 'Mlle'
        ELSE 'M'
    END as civilite,
    u.nom,
    u.prenom,
    DATE_SUB(CURDATE(), INTERVAL (75 + (u.id % 15)) YEAR) as date_naissance, -- Âge entre 75 et 90 ans
    u.telephone as telephone_mobile,
    u.email,
    CASE (u.id % 3)
        WHEN 0 THEN 'autonome'
        WHEN 1 THEN 'semi_autonome'
        ELSE 'gir5'
    END as niveau_autonomie,
    1 as actif,
    DATE_SUB(CURDATE(), INTERVAL (30 + (u.id % 365)) DAY) as date_entree, -- Entrée dans les 13 derniers mois
    NOW() as created_at,
    NOW() as updated_at
FROM users u
LEFT JOIN residents_seniors rs ON u.id = rs.user_id
WHERE u.role = 'resident' 
  AND rs.id IS NULL  -- Seulement ceux qui n'ont pas encore de profil
ORDER BY u.id;

-- Vérification
SELECT 
    COUNT(*) as total_residents_seniors,
    COUNT(CASE WHEN user_id IS NOT NULL THEN 1 END) as avec_compte_utilisateur,
    COUNT(CASE WHEN user_id IS NULL THEN 1 END) as sans_compte_utilisateur
FROM residents_seniors;

-- Afficher les nouveaux résidents créés
SELECT 
    rs.id,
    rs.user_id,
    CONCAT(rs.civilite, ' ', rs.prenom, ' ', rs.nom) as nom_complet,
    rs.date_naissance,
    TIMESTAMPDIFF(YEAR, rs.date_naissance, CURDATE()) as age,
    rs.niveau_autonomie,
    rs.date_entree,
    u.username
FROM residents_seniors rs
INNER JOIN users u ON rs.user_id = u.id
WHERE u.role = 'resident'
ORDER BY rs.id;
