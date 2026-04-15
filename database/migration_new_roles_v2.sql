-- ============================================================
-- MIGRATION v2.0 - Nouveaux rôles & structure multi-utilisateurs
-- Synd_Gest - 2026-04-05
-- ============================================================
-- Ordre d'exécution :
--   1. Modifier users (ENUM → VARCHAR + password_plain)
--   2. Créer table roles
--   3. Créer table user_residence
--   4. Modifier permissions (ENUM → VARCHAR)
--   5. Modifier role_descriptions (ENUM → VARCHAR)
--   6. Insérer données rôles
--   7. Insérer permissions de base pour nouveaux rôles
-- ============================================================

SET FOREIGN_KEY_CHECKS = 0;
SET sql_mode = '';

-- ============================================================
-- ÉTAPE 1 : TABLE users
-- ============================================================

-- 1a. Ajouter colonne password_plain (visible admin)
ALTER TABLE users
    ADD COLUMN password_plain VARCHAR(255) DEFAULT NULL
    AFTER password_hash;

-- 1b. Changer role ENUM → VARCHAR(50)
ALTER TABLE users
    MODIFY COLUMN role VARCHAR(50) NOT NULL DEFAULT 'locataire_permanent';

-- 1c. Migrer les anciens rôles vers les nouveaux slugs
UPDATE users SET role = 'directeur_residence' WHERE role = 'gestionnaire';
UPDATE users SET role = 'directeur_residence' WHERE role = 'exploitant';
UPDATE users SET role = 'locataire_permanent'  WHERE role = 'resident';
-- admin et proprietaire restent inchangés

-- ============================================================
-- ÉTAPE 2 : TABLE roles (référentiel centralisé)
-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id              INT(11)      NOT NULL AUTO_INCREMENT,
    slug            VARCHAR(50)  NOT NULL UNIQUE COMMENT 'Identifiant technique (utilisé dans users.role)',
    nom_affichage   VARCHAR(100) NOT NULL,
    description     TEXT         DEFAULT NULL,
    categorie       VARCHAR(50)  DEFAULT NULL COMMENT 'admin | direction | staff | resident | proprietaire',
    couleur         VARCHAR(20)  DEFAULT '#6c757d',
    icone           VARCHAR(50)  DEFAULT 'fa-user',
    ordre_affichage INT(11)      DEFAULT 0,
    actif           TINYINT(1)   DEFAULT 1,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_slug      (slug),
    KEY idx_categorie (categorie),
    KEY idx_actif     (actif)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de tous les rôles
INSERT INTO roles (slug, nom_affichage, description, categorie, couleur, icone, ordre_affichage) VALUES
-- Administration
('admin',                'Administrateur',            'Accès complet à tous les modules et paramètres du système.',                                              'admin',       '#dc3545', 'fa-user-shield',       1),
-- Direction
('directeur_residence',  'Directeur de Résidence',    'Gestion globale de la résidence : personnel, résidents, budget, reporting.',                              'direction',   '#0d6efd', 'fa-building-user',     2),
('proprietaire',         'Propriétaire',              'Consultation de ses biens, contrats, loyers, déclarations fiscales.',                                     'proprietaire','#fd7e14', 'fa-home',              3),
-- Staff
('employe_residence',    'Employé Résidence',         'Opérations courantes selon son service (accueil, administratif).',                                        'staff',       '#6f42c1', 'fa-id-badge',          4),
('technicien',           'Technicien',                'Maintenance technique : électricité, plomberie, ascenseur, chauffage, maçonnerie.',                       'staff',       '#20c997', 'fa-screwdriver-wrench',5),
('jardinier_manager',    'Jardinier-Paysagiste (Chef)','Responsable du service jardinage/paysagisme.',                                                          'staff',       '#198754', 'fa-tree',              6),
('jardinier_employe',    'Jardinier-Paysagiste',      'Entretien des espaces verts et aménagements paysagers.',                                                 'staff',       '#198754', 'fa-leaf',              7),
('entretien_manager',    'Responsable Entretien',     'Supervision des équipes ménage intérieur et extérieur.',                                                  'staff',       '#0dcaf0', 'fa-broom',             8),
('menage_interieur',     'Ménage Intérieur',          'Nettoyage des chambres et espaces intérieurs.',                                                           'staff',       '#0dcaf0', 'fa-bed',               9),
('menage_exterieur',     'Ménage Extérieur',          'Nettoyage des espaces communs extérieurs.',                                                               'staff',       '#0dcaf0', 'fa-spray-can',        10),
('restauration_manager', 'Responsable Restauration',  'Gestion du restaurant, fournisseurs, facturation.',                                                       'staff',       '#ffc107', 'fa-utensils',         11),
('restauration_serveur', 'Serveur/Serveuse',          'Service en salle, facturation résidents.',                                                                'staff',       '#ffc107', 'fa-concierge-bell',   12),
('restauration_cuisine', 'Cuisine',                   'Préparation des repas, gestion des denrées.',                                                             'staff',       '#ffc107', 'fa-kitchen-set',      13),
-- Résidents
('locataire_permanent',  'Résident Senior',           'Résident permanent (locataire longue durée). Accès à son espace personnel, services, animations.',       'resident',    '#6610f2', 'fa-user-circle',      14),
('locataire_temporel',   'Hôte Temporaire',           'Locataire court séjour. Accès limité aux informations pratiques et services pendant son séjour.',        'resident',    '#6610f2', 'fa-calendar-check',   15);

-- ============================================================
-- ÉTAPE 3 : TABLE user_residence (lien user ↔ résidence)
-- ============================================================
-- Remplace la logique implicite de exploitant_residences pour le staff.
-- Permet de savoir dans quelle(s) résidence(s) un user opère.

CREATE TABLE IF NOT EXISTS user_residence (
    id           INT(11)                         NOT NULL AUTO_INCREMENT,
    user_id      INT(11)                         NOT NULL,
    residence_id INT(11)                         NOT NULL,
    role         VARCHAR(50)                     NOT NULL COMMENT 'Rôle de l\'user dans cette résidence',
    date_debut   DATE                            DEFAULT NULL,
    date_fin     DATE                            DEFAULT NULL,
    statut       ENUM('actif','inactif')         NOT NULL DEFAULT 'actif',
    notes        TEXT                            DEFAULT NULL,
    created_at   TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP                       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY  unique_user_residence_role (user_id, residence_id, role),
    KEY         idx_user_id      (user_id),
    KEY         idx_residence_id (residence_id),
    KEY         idx_role         (role),
    KEY         idx_statut       (statut),
    CONSTRAINT fk_ur_user      FOREIGN KEY (user_id)      REFERENCES users         (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_ur_residence FOREIGN KEY (residence_id) REFERENCES coproprietees (id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- ÉTAPE 4 : TABLE permissions — ENUM → VARCHAR
-- ============================================================

-- 4a. Supprimer l'index unique avant modification
ALTER TABLE permissions DROP INDEX unique_permission;

-- 4b. Changer le type ENUM → VARCHAR
ALTER TABLE permissions
    MODIFY COLUMN role VARCHAR(50) NOT NULL;

-- 4c. Re-créer l'index unique
ALTER TABLE permissions
    ADD UNIQUE KEY unique_permission (role, module, action);

-- 4d. Les anciennes entrées gestionnaire/exploitant/resident restent
--     comme archive. Elles seront remplacées au fur et à mesure
--     que les nouveaux contrôleurs sont développés.

-- Permissions de base pour les nouveaux rôles
INSERT IGNORE INTO permissions (role, module, action, allowed, description) VALUES
-- directeur_residence
('directeur_residence', 'users',           'read',   1, 'Voir le personnel de sa résidence'),
('directeur_residence', 'residents',       'all',    1, 'Gestion complète des résidents'),
('directeur_residence', 'occupations',     'all',    1, 'Gestion des occupations'),
('directeur_residence', 'paiements',       'all',    1, 'Suivi des paiements'),
('directeur_residence', 'comptabilite',    'read',   1, 'Lecture comptabilité'),
('directeur_residence', 'restauration',    'all',    1, 'Module restauration'),
('directeur_residence', 'piscine',         'all',    1, 'Module piscine'),
('directeur_residence', 'menage_entretien','all',    1, 'Module ménage/entretien'),
('directeur_residence', 'jardinerie',      'all',    1, 'Module jardinerie'),
('directeur_residence', 'travaux',         'all',    1, 'Module travaux'),
('directeur_residence', 'planning',        'all',    1, 'Planning/Calendrier'),
('directeur_residence', 'documents',       'all',    1, 'Gestion documents'),
('directeur_residence', 'fournisseurs',    'all',    1, 'Gestion fournisseurs'),
('directeur_residence', 'reporting',       'read',   1, 'Reporting'),
-- employe_residence
('employe_residence',   'residents',       'read',   1, 'Consulter les résidents'),
('employe_residence',   'planning',        'read',   1, 'Consulter le planning'),
('employe_residence',   'documents',       'read',   1, 'Consulter les documents'),
('employe_residence',   'messages',        'all',    1, 'Messagerie interne'),
-- technicien
('technicien',          'travaux',         'read',   1, 'Voir les travaux assignés'),
('technicien',          'travaux',         'update', 1, 'Mettre à jour statut travaux'),
('technicien',          'piscine',         'all',    1, 'Module piscine'),
('technicien',          'planning',        'read',   1, 'Consulter le planning'),
('technicien',          'fournisseurs',    'read',   1, 'Consulter fournisseurs'),
-- jardinier_manager
('jardinier_manager',   'jardinerie',      'all',    1, 'Module jardinerie complet'),
('jardinier_manager',   'fournisseurs',    'read',   1, 'Consulter fournisseurs jardinerie'),
('jardinier_manager',   'planning',        'all',    1, 'Gérer le planning jardinerie'),
-- jardinier_employe
('jardinier_employe',   'jardinerie',      'read',   1, 'Consulter tâches jardinerie'),
('jardinier_employe',   'jardinerie',      'update', 1, 'Mettre à jour tâches'),
('jardinier_employe',   'planning',        'read',   1, 'Consulter le planning'),
-- entretien_manager
('entretien_manager',   'menage_entretien','all',    1, 'Gestion complète ménage/entretien'),
('entretien_manager',   'fournisseurs',    'read',   1, 'Consulter fournisseurs entretien'),
('entretien_manager',   'planning',        'all',    1, 'Gérer le planning entretien'),
-- menage_interieur
('menage_interieur',    'menage_entretien','read',   1, 'Voir les tâches ménage intérieur'),
('menage_interieur',    'menage_entretien','update', 1, 'Mettre à jour tâches'),
('menage_interieur',    'planning',        'read',   1, 'Consulter le planning'),
-- menage_exterieur
('menage_exterieur',    'menage_entretien','read',   1, 'Voir les tâches ménage extérieur'),
('menage_exterieur',    'menage_entretien','update', 1, 'Mettre à jour tâches'),
('menage_exterieur',    'planning',        'read',   1, 'Consulter le planning'),
-- restauration_manager
('restauration_manager','restauration',    'all',    1, 'Gestion complète restauration'),
('restauration_manager','fournisseurs',    'all',    1, 'Gestion fournisseurs restauration'),
('restauration_manager','planning',        'all',    1, 'Gérer le planning restauration'),
-- restauration_serveur
('restauration_serveur','restauration',    'read',   1, 'Voir les commandes/tables'),
('restauration_serveur','restauration',    'create', 1, 'Créer des commandes'),
('restauration_serveur','restauration',    'update', 1, 'Mettre à jour commandes'),
('restauration_serveur','planning',        'read',   1, 'Consulter le planning'),
-- restauration_cuisine
('restauration_cuisine','restauration',    'read',   1, 'Voir les commandes cuisine'),
('restauration_cuisine','restauration',    'update', 1, 'Mettre à jour statut plats'),
('restauration_cuisine','planning',        'read',   1, 'Consulter le planning'),
-- locataire_permanent
('locataire_permanent', 'profil',          'update', 1, 'Modifier son profil'),
('locataire_permanent', 'messages',        'all',    1, 'Messagerie'),
('locataire_permanent', 'restauration',    'read',   1, 'Consulter le menu/réserver'),
('locataire_permanent', 'planning',        'read',   1, 'Consulter les animations'),
('locataire_permanent', 'paiements',       'read',   1, 'Voir ses propres paiements'),
('locataire_permanent', 'documents',       'read',   1, 'Consulter ses documents'),
-- locataire_temporel
('locataire_temporel',  'profil',          'read',   1, 'Voir son profil'),
('locataire_temporel',  'planning',        'read',   1, 'Consulter les activités'),
('locataire_temporel',  'restauration',    'read',   1, 'Consulter le menu'),
-- proprietaire
('proprietaire',        'contrats_gestion','read',   1, 'Consulter ses contrats'),
('proprietaire',        'lots',            'read',   1, 'Consulter ses lots'),
('proprietaire',        'paiements',       'read',   1, 'Voir ses paiements reçus'),
('proprietaire',        'documents',       'read',   1, 'Consulter ses documents'),
('proprietaire',        'messages',        'all',    1, 'Messagerie'),
('proprietaire',        'profil',          'update', 1, 'Modifier son profil'),
('proprietaire',        'revenus_fiscaux', 'read',   1, 'Consulter revenus fiscaux');

-- ============================================================
-- ÉTAPE 5 : TABLE role_descriptions — ENUM → VARCHAR
-- ============================================================

-- 5a. Supprimer l'index unique avant modification
ALTER TABLE role_descriptions DROP INDEX role;

-- 5b. Changer le type ENUM → VARCHAR
ALTER TABLE role_descriptions
    MODIFY COLUMN role VARCHAR(50) NOT NULL;

-- 5c. Re-créer l'index unique
ALTER TABLE role_descriptions
    ADD UNIQUE KEY uq_role (role);

-- 5d. Nettoyer les anciens rôles devenus obsolètes
DELETE FROM role_descriptions WHERE role IN ('gestionnaire', 'exploitant', 'resident');

-- 5e. Insérer les nouvelles descriptions (admin et proprietaire déjà présents)
INSERT IGNORE INTO role_descriptions (role, nom_affichage, description, couleur, icone, ordre_affichage) VALUES
('directeur_residence',  'Directeur de Résidence',    'Gestion globale de la résidence : personnel, résidents, budget, reporting.',                         '#0d6efd', 'fa-building-user',      2),
('employe_residence',    'Employé Résidence',         'Opérations courantes selon son service (accueil, administratif).',                                   '#6f42c1', 'fa-id-badge',           4),
('technicien',           'Technicien',                'Maintenance technique : électricité, plomberie, ascenseur, chauffage, maçonnerie.',                  '#20c997', 'fa-screwdriver-wrench', 5),
('jardinier_manager',    'Jardinier-Paysagiste (Chef)','Responsable du service jardinage/paysagisme.',                                                     '#198754', 'fa-tree',               6),
('jardinier_employe',    'Jardinier-Paysagiste',      'Entretien des espaces verts et aménagements paysagers.',                                            '#198754', 'fa-leaf',               7),
('entretien_manager',    'Responsable Entretien',     'Supervision des équipes ménage intérieur et extérieur.',                                             '#0dcaf0', 'fa-broom',              8),
('menage_interieur',     'Ménage Intérieur',          'Nettoyage des chambres et espaces intérieurs.',                                                      '#0dcaf0', 'fa-bed',                9),
('menage_exterieur',     'Ménage Extérieur',          'Nettoyage des espaces communs extérieurs.',                                                          '#0dcaf0', 'fa-spray-can',         10),
('restauration_manager', 'Responsable Restauration',  'Gestion du restaurant, fournisseurs, facturation.',                                                  '#ffc107', 'fa-utensils',          11),
('restauration_serveur', 'Serveur/Serveuse',          'Service en salle, facturation résidents.',                                                           '#ffc107', 'fa-concierge-bell',    12),
('restauration_cuisine', 'Cuisine',                   'Préparation des repas, gestion des denrées.',                                                        '#ffc107', 'fa-kitchen-set',       13),
('locataire_permanent',  'Résident Senior',           'Résident permanent. Accès à son espace personnel, services, animations.',                            '#6610f2', 'fa-user-circle',       14),
('locataire_temporel',   'Hôte Temporaire',           'Locataire court séjour. Accès limité aux informations pratiques.',                                   '#6610f2', 'fa-calendar-check',    15);

-- ============================================================
-- ÉTAPE 6 : INDEX supplémentaires pour la concurrence
-- ============================================================

-- Index sur user_id dans residents_seniors (déjà présent, vérification)
-- Index sur updated_at pour les requêtes de sync
ALTER TABLE users             ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);
ALTER TABLE residents_seniors ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);
ALTER TABLE coproprietees     ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);
ALTER TABLE lots              ADD INDEX IF NOT EXISTS idx_updated_at (updated_at);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- VÉRIFICATION FINALE
-- ============================================================
SELECT 'users.role column' AS check_name, COLUMN_TYPE AS result
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = 'synd_gest' AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'
UNION ALL
SELECT 'Total roles', COUNT(*) FROM roles
UNION ALL
SELECT 'Total new permissions', COUNT(*) FROM permissions WHERE role NOT IN ('admin','gestionnaire','exploitant','proprietaire','resident')
UNION ALL
SELECT 'Tables user_residence', IF(COUNT(*)>0,'OK','MISSING') FROM information_schema.TABLES
WHERE TABLE_SCHEMA='synd_gest' AND TABLE_NAME='user_residence';
