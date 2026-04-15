-- ====================================================================
-- SYND_GEST - Migration stricte residents <-> users
-- ====================================================================
-- Objectif:
-- 1) Chaque residents_seniors doit avoir user_id non NULL
-- 2) Chaque users.role = 'resident' doit avoir une fiche residents_seniors
-- 3) Verrouiller la coherence via contraintes SQL + triggers
--
-- Important:
-- - Mot de passe temporaire pour comptes auto-crees: TempResident123!
-- - Pensez a forcer le changement du mot de passe ensuite.
-- ====================================================================

START TRANSACTION;

-- --------------------------------------------------------------------
-- ETAPE 1: Creer des comptes users pour les residents sans user_id
-- --------------------------------------------------------------------
INSERT INTO users (
    username,
    email,
    password_hash,
    role,
    prenom,
    nom,
    telephone,
    actif,
    created_at,
    updated_at
)
SELECT
    CONCAT('resident_rs_', rs.id) AS username,
    COALESCE(NULLIF(rs.email, ''), CONCAT('resident.rs', rs.id, '@syndgest.local')) AS email,
    '$2y$10$s8dpzMwnak34.xbx1A53o.O50zI2kFbcKFACDOABcalCbvo2pXTlC' AS password_hash,
    'proprietaire' AS role,
    rs.prenom,
    rs.nom,
    rs.telephone_mobile,
    rs.actif,
    NOW(),
    NOW()
FROM residents_seniors rs
LEFT JOIN users u_existing ON u_existing.username = CONCAT('resident_rs_', rs.id)
WHERE rs.user_id IS NULL
  AND u_existing.id IS NULL;

-- Lier les fiches residents nouvellement dotees d'un compte
UPDATE residents_seniors rs
INNER JOIN users u ON u.username = CONCAT('resident_rs_', rs.id)
SET rs.user_id = u.id
WHERE rs.user_id IS NULL;

-- --------------------------------------------------------------------
-- ETAPE 2: Creer une fiche residents_seniors pour users.role='resident'
--          qui n'en ont pas encore
-- --------------------------------------------------------------------
INSERT INTO residents_seniors (
    user_id,
    civilite,
    nom,
    prenom,
    date_naissance,
    telephone_mobile,
    email,
    niveau_autonomie,
    actif,
    date_entree,
    created_at,
    updated_at
)
SELECT
    u.id,
    'M' AS civilite,
    COALESCE(NULLIF(u.nom, ''), 'INCONNU') AS nom,
    COALESCE(NULLIF(u.prenom, ''), 'Resident') AS prenom,
    '1950-01-01' AS date_naissance,
    NULLIF(u.telephone, '') AS telephone_mobile,
    u.email,
    'autonome' AS niveau_autonomie,
    u.actif,
    CURDATE() AS date_entree,
    NOW(),
    NOW()
FROM users u
LEFT JOIN residents_seniors rs ON rs.user_id = u.id
WHERE u.role = 'resident'
  AND rs.id IS NULL;

-- --------------------------------------------------------------------
-- ETAPE 3: Tout user lie a une fiche resident doit avoir role='resident'
-- --------------------------------------------------------------------
UPDATE users u
INNER JOIN residents_seniors rs ON rs.user_id = u.id
SET u.role = 'resident',
    u.updated_at = NOW()
WHERE u.role <> 'resident';

-- --------------------------------------------------------------------
-- ETAPE 4: Contraintes structurelles sur residents_seniors.user_id
-- --------------------------------------------------------------------
ALTER TABLE residents_seniors
  DROP FOREIGN KEY fk_resident_user;

ALTER TABLE residents_seniors
  MODIFY user_id int(11) NOT NULL;

ALTER TABLE residents_seniors
  ADD UNIQUE KEY uq_residents_seniors_user_id (user_id);

ALTER TABLE residents_seniors
  ADD CONSTRAINT fk_resident_user
  FOREIGN KEY (user_id) REFERENCES users(id)
  ON DELETE RESTRICT
  ON UPDATE CASCADE;

-- --------------------------------------------------------------------
-- ETAPE 5: Trigger minimum de coherence
-- --------------------------------------------------------------------
-- On autorise la creation directe des users residents depuis le module Residents.
-- La coherence reste garantie par:
-- - residents_seniors.user_id NOT NULL
-- - UNIQUE(residents_seniors.user_id)
-- - FK residents_seniors.user_id -> users.id
-- - validation applicative du module Admin (pas de creation resident)

DROP TRIGGER IF EXISTS trg_users_block_direct_resident_insert;

DROP TRIGGER IF EXISTS trg_users_require_profile_before_resident_role;
DELIMITER $$
CREATE TRIGGER trg_users_require_profile_before_resident_role
BEFORE UPDATE ON users
FOR EACH ROW
BEGIN
    IF NEW.role = 'resident' AND OLD.role <> 'resident' THEN
        IF (SELECT COUNT(*) FROM residents_seniors WHERE user_id = NEW.id) = 0 THEN
            SIGNAL SQLSTATE '45000'
                SET MESSAGE_TEXT = 'Impossible de passer role=resident sans fiche residents_seniors liee';
        END IF;
    END IF;
END$$
DELIMITER ;

COMMIT;

-- --------------------------------------------------------------------
-- Verifications post-migration
-- --------------------------------------------------------------------
SELECT COUNT(*) AS total_residents FROM residents_seniors;
SELECT COUNT(*) AS total_users_resident FROM users WHERE role = 'resident';
SELECT COUNT(*) AS residents_without_user FROM residents_seniors WHERE user_id IS NULL;
SELECT COUNT(*) AS resident_users_without_profile
FROM users u
LEFT JOIN residents_seniors rs ON rs.user_id = u.id
WHERE u.role = 'resident' AND rs.id IS NULL;
