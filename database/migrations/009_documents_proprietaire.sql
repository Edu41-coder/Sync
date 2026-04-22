-- ====================================================================
-- Migration 009 : GED personnelle du propriétaire
-- ====================================================================
-- Espace de stockage personnel pour chaque propriétaire :
--   - Arborescence de dossiers libres (parent_id nullable = racine)
--   - Fichiers uploadés (PDF, images, vidéos, bureautique, archives)
--   - Quota 1 GB par propriétaire, fichier max 50 MB
--   - Stockage physique : uploads/coproprietaires/{user_id}/{timestamp_uuid}_{nom}.{ext}
-- ====================================================================

CREATE TABLE IF NOT EXISTS coproprietaire_dossiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT NOT NULL,
    parent_id INT NULL,
    nom VARCHAR(255) NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES coproprietaire_dossiers(id) ON DELETE CASCADE,
    INDEX idx_prop_parent (proprietaire_id, parent_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coproprietaire_fichiers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    proprietaire_id INT NOT NULL,
    dossier_id INT NULL,
    nom_original VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) NULL,
    taille_octets BIGINT NOT NULL DEFAULT 0,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (proprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE,
    FOREIGN KEY (dossier_id) REFERENCES coproprietaire_dossiers(id) ON DELETE SET NULL,
    INDEX idx_prop_dossier (proprietaire_id, dossier_id),
    INDEX idx_prop_taille (proprietaire_id, taille_octets)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
