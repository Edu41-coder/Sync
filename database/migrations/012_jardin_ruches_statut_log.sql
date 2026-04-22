-- ====================================================================
-- Migration 012 : Historique statut ruche (audit trail)
-- ====================================================================
-- Trace chaque changement de statut d'une ruche (active, essaim_capture,
-- inactive, morte). Une ligne par transition, avec horodatage, auteur
-- et motif optionnel. Également une ligne "création" avec statut_avant = NULL.
-- ====================================================================

CREATE TABLE IF NOT EXISTS jardin_ruches_statut_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ruche_id INT NOT NULL,
    statut_avant ENUM('active','essaim_capture','inactive','morte') DEFAULT NULL COMMENT 'NULL = création initiale',
    statut_apres ENUM('active','essaim_capture','inactive','morte') NOT NULL,
    motif VARCHAR(255) DEFAULT NULL,
    user_id INT DEFAULT NULL,
    changed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ruche_id) REFERENCES jardin_ruches(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_ruches_statut_log_ruche ON jardin_ruches_statut_log(ruche_id, changed_at);

-- Backfill : une ligne "création" pour chaque ruche existante (statut_avant = NULL)
INSERT INTO jardin_ruches_statut_log (ruche_id, statut_avant, statut_apres, motif, user_id, changed_at)
SELECT id, NULL, statut, 'Backfill — statut initial enregistré lors de la mise en place de l''historique',
       NULL, COALESCE(created_at, NOW())
FROM jardin_ruches
WHERE NOT EXISTS (
    SELECT 1 FROM jardin_ruches_statut_log l WHERE l.ruche_id = jardin_ruches.id
);
