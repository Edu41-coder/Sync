-- ====================================================================
-- Migration 029 : GED admin (dossiers + fichiers, global + par résidence)
-- ====================================================================
-- Périmètre :
--   - residence_id NULL → document global Domitys (HQ : modèles, contrats cadre, RGPD…)
--   - residence_id = X  → document spécifique à la résidence X (AG, factures archivées, sinistres clos…)
--
-- Permissions appliquées côté PHP :
--   - admin              : R/W partout (global + toutes résidences)
--   - directeur_residence: R/W sur ses résidences (user_residence), R-only sur global
--   - exploitant         : R/W sur ses résidences (exploitant_residences), R-only sur global
--   - comptable          : R-only sur ses résidences (user_residence) + R-only sur global
--   - autres rôles       : pas d'accès
--
-- Pas de quota en MVP. Limite 50 MB par fichier (vérifiée côté PHP).
-- Stockage hors public/ : uploads/admin/global/{dossier_id|0}/...
--                          uploads/admin/residences/{residence_id}/{dossier_id|0}/...
-- ====================================================================

-- --------------------------------------------------------------------
-- Dossiers (arborescence libre, par scope)
-- --------------------------------------------------------------------
CREATE TABLE admin_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT DEFAULT NULL COMMENT 'NULL = scope global Domitys',
    parent_id INT DEFAULT NULL COMMENT 'NULL = racine du scope',
    nom VARCHAR(255) NOT NULL,
    created_by INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_dossiers_residence
        FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_dossiers_parent
        FOREIGN KEY (parent_id) REFERENCES admin_dossiers(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_dossiers_creator
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_admin_dossiers_residence (residence_id),
    INDEX idx_admin_dossiers_parent (parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Fichiers
-- --------------------------------------------------------------------
CREATE TABLE admin_fichiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT DEFAULT NULL COMMENT 'NULL = scope global Domitys',
    dossier_id INT DEFAULT NULL COMMENT 'NULL = racine du scope',
    nom_original VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    taille_octets BIGINT DEFAULT NULL,
    description VARCHAR(500) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_admin_fichiers_residence
        FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    CONSTRAINT fk_admin_fichiers_dossier
        FOREIGN KEY (dossier_id) REFERENCES admin_dossiers(id) ON DELETE SET NULL,
    CONSTRAINT fk_admin_fichiers_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_admin_fichiers_residence (residence_id),
    INDEX idx_admin_fichiers_dossier (dossier_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
