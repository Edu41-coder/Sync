-- ====================================================================
-- Migration 013 : Calendrier-type des traitements apicoles (FR)
-- ====================================================================
-- Référentiel des traitements recommandés par saison.
-- Une entrée avec residence_id = NULL = template système (applicable
-- à toutes les résidences). Les managers peuvent aussi ajouter des
-- traitements spécifiques à une résidence donnée.
--
-- La logique d'alerte : pour chaque ruche active, si le mois courant
-- tombe dans la fenêtre [mois_debut..mois_fin] d'un traitement actif,
-- et qu'aucune visite type='traitement' n'a été enregistrée dans cette
-- fenêtre de l'année courante → alerte.
-- ====================================================================

CREATE TABLE IF NOT EXISTS jardin_traitements_calendrier (
    id INT AUTO_INCREMENT PRIMARY KEY,
    residence_id INT DEFAULT NULL COMMENT 'NULL = template système pour toutes les résidences',
    nom VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    mois_debut TINYINT NOT NULL COMMENT '1-12',
    mois_fin TINYINT NOT NULL COMMENT '1-12 (>= mois_debut pour fenêtre interne à l''année)',
    priorite TINYINT NOT NULL DEFAULT 2 COMMENT '1=critique, 2=recommandé, 3=optionnel',
    produit_suggere VARCHAR(150) DEFAULT NULL,
    actif TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_traitements_periode ON jardin_traitements_calendrier(mois_debut, mois_fin, actif);
CREATE INDEX idx_traitements_residence ON jardin_traitements_calendrier(residence_id, actif);

-- ─────────────────────────────────────────────────────────────
-- Seed : 6 templates système (calendrier apicole FR typique)
-- ─────────────────────────────────────────────────────────────

INSERT INTO jardin_traitements_calendrier
    (residence_id, nom, description, mois_debut, mois_fin, priorite, produit_suggere)
VALUES
    (NULL, 'Contrôle hivernal',
     'Vérification du poids de la colonie, état de la grappe, réserves alimentaires. Pas d''ouverture prolongée si froid.',
     1, 2, 2, NULL),

    (NULL, 'Visite de printemps',
     'Première inspection complète après hivernage : état de la reine, couvain, provisions, nettoyage du plancher.',
     3, 4, 2, NULL),

    (NULL, 'Pose hausses / suivi essaimage',
     'Pose des hausses, surveillance des cellules royales, prévention de l''essaimage (division si nécessaire).',
     4, 5, 3, NULL),

    (NULL, 'Traitement varroa été',
     'Traitement anti-varroa post-récolte d''été (Apivar, Apitraz, MAQS selon protocole). Essentiel pour la survie hivernale.',
     8, 9, 1, 'Apivar'),

    (NULL, 'Nourrissement automnal',
     'Complément de réserves pour l''hiver (sirop 1:1 puis candi). Quantité selon poids de la colonie.',
     10, 11, 1, 'Sirop 50/50'),

    (NULL, 'Traitement varroa hiver (acide oxalique)',
     'Traitement hors couvain à l''acide oxalique par dégouttement ou sublimation. Très efficace sur varroas phorétiques.',
     12, 12, 1, 'Acide oxalique');
