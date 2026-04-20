-- ====================================================================
-- Migration 007 : Section Laverie restauration
-- ====================================================================
-- Gestion du linge de salle (nappes, serviettes table, torchons,
-- tabliers cuisine, tenues service) en cycles d'envoi/retour.
-- Prestataire : laverie interne.
-- Pas de gestion de stock — uniquement traçabilité des envois/retours.
-- ====================================================================

CREATE TABLE IF NOT EXISTS rest_laverie (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT NOT NULL,
    type_linge ENUM('nappe', 'serviette_table', 'torchon', 'tablier_cuisine', 'tenue_service', 'autre') NOT NULL,
    quantite_envoyee INT NOT NULL,
    quantite_recue INT NULL,
    date_envoi DATETIME NOT NULL,
    date_retour DATETIME NULL,
    statut ENUM('envoye', 'recu', 'partiel', 'perdu') NOT NULL DEFAULT 'envoye',
    cout DECIMAL(8,2) DEFAULT 0.00,
    user_envoi_id INT NULL,
    user_reception_id INT NULL,
    notes TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    FOREIGN KEY (user_envoi_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (user_reception_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_residence_date (residence_id, date_envoi),
    INDEX idx_statut (statut)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
