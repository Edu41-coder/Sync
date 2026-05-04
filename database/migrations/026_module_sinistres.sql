-- ====================================================================
-- Migration 025 : Module Sinistres (MVP)
-- ====================================================================
-- Périmètre MVP :
--   - Déclaration / suivi de sinistres (lot OU partie commune)
--   - GED documents (constat, photos, expertise, courriers assureur)
--   - Audit trail (changements de statut, indemnisation, documents)
--
-- HORS MVP (V2) : indemnisations multiples, lien chantier maintenance,
-- intervenants pivot (assureur/expert/courtier), alertes délais légaux,
-- intégration comptabilité (écriture auto).
--
-- Règles métier :
--   - Lieu = soit lot_id, soit lieu_partie_commune (XOR via CHECK)
--   - Modification figée dès que statut != 'declare' (contrôlé côté PHP)
--   - Résident peut déclarer pour son lot mais ne peut pas modifier
--   - Propriétaire = lecture seule sur ses lots, jamais déclarant
-- ====================================================================

-- --------------------------------------------------------------------
-- Cleanup legacy : table `sinistres` du schéma initial
-- --------------------------------------------------------------------
-- Le schéma 001 (générique syndic copropriété) avait une table sinistres
-- avec 17 colonnes différentes (copropriete_id, type ENUM réduit, etc.).
-- Elle n'a jamais été branchée à du code applicatif (SinistreController = stub).
-- Vérifié : 0 ligne dans sinistres, 0 ligne avec documents.sinistre_id non NULL.
--
-- On drop la FK + colonne sur `documents` puis on drop l'ancienne table.
-- Pattern identique à migration 020 (drop_travaux_legacy).

ALTER TABLE documents DROP FOREIGN KEY documents_ibfk_5;
ALTER TABLE documents DROP COLUMN sinistre_id;

DROP TABLE IF EXISTS sinistres;

-- --------------------------------------------------------------------
-- Table principale : sinistres (MVP module Synd_Gest)
-- --------------------------------------------------------------------
CREATE TABLE sinistres (
    id INT AUTO_INCREMENT PRIMARY KEY,

    -- Lieu
    residence_id INT NOT NULL,
    lot_id INT DEFAULT NULL,
    lieu_partie_commune ENUM(
        'parking','ascenseur','hall','couloir','cage_escalier',
        'jardin','salle_commune','local_technique','toiture','facade','autre'
    ) DEFAULT NULL,
    description_lieu VARCHAR(255) DEFAULT NULL,

    -- Caractérisation
    type_sinistre ENUM(
        'degat_eaux','incendie','vol_cambriolage','bris_glace',
        'catastrophe_naturelle','vandalisme','chute_resident',
        'panne_equipement','autre'
    ) NOT NULL,
    gravite ENUM('mineur','modere','majeur','catastrophe') NOT NULL DEFAULT 'modere',

    -- Dates clés
    date_survenue DATETIME NOT NULL,
    date_constat DATETIME DEFAULT NULL,
    date_declaration_assureur DATE DEFAULT NULL,
    date_cloture DATETIME DEFAULT NULL,

    -- Contenu
    -- declarant_user_id nullable : on conserve l'historique du sinistre même si
    -- le compte qui l'a déclaré est supprimé (cohérent avec les autres tables d'audit).
    declarant_user_id INT DEFAULT NULL,
    titre VARCHAR(150) NOT NULL,
    description TEXT NOT NULL,

    -- Assurance (texte libre en MVP, table assureurs en V2)
    assureur_nom VARCHAR(150) DEFAULT NULL,
    numero_contrat_assurance VARCHAR(100) DEFAULT NULL,
    numero_dossier_sinistre VARCHAR(100) DEFAULT NULL,
    franchise DECIMAL(10,2) DEFAULT NULL,
    montant_estime DECIMAL(10,2) DEFAULT NULL,
    montant_indemnise DECIMAL(10,2) DEFAULT NULL,
    date_indemnisation DATE DEFAULT NULL,

    -- Workflow
    statut ENUM(
        'declare','transmis_assureur','expertise_en_cours',
        'en_reparation','indemnise','clos','refuse'
    ) NOT NULL DEFAULT 'declare',

    notes TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_sinistres_residence
        FOREIGN KEY (residence_id) REFERENCES coproprietees(id) ON DELETE CASCADE,
    CONSTRAINT fk_sinistres_lot
        FOREIGN KEY (lot_id) REFERENCES lots(id) ON DELETE SET NULL,
    CONSTRAINT fk_sinistres_declarant
        FOREIGN KEY (declarant_user_id) REFERENCES users(id) ON DELETE SET NULL,

    CONSTRAINT chk_sinistres_lieu_xor CHECK (
        (lot_id IS NOT NULL AND lieu_partie_commune IS NULL) OR
        (lot_id IS NULL AND lieu_partie_commune IS NOT NULL)
    ),

    INDEX idx_sinistres_residence_statut (residence_id, statut),
    INDEX idx_sinistres_declarant (declarant_user_id),
    INDEX idx_sinistres_lot (lot_id),
    INDEX idx_sinistres_date_survenue (date_survenue)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- GED documents
-- --------------------------------------------------------------------
CREATE TABLE sinistres_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinistre_id INT NOT NULL,
    type_document ENUM(
        'photo_avant','photo_apres','constat_amiable','devis',
        'facture','rapport_expertise','courrier_assureur','autre'
    ) NOT NULL DEFAULT 'autre',
    nom_original VARCHAR(255) NOT NULL,
    chemin_stockage VARCHAR(512) NOT NULL,
    mime_type VARCHAR(100) DEFAULT NULL,
    taille_octets BIGINT DEFAULT NULL,
    description VARCHAR(255) DEFAULT NULL,
    uploaded_by INT DEFAULT NULL,
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sinistres_documents_sinistre
        FOREIGN KEY (sinistre_id) REFERENCES sinistres(id) ON DELETE CASCADE,
    CONSTRAINT fk_sinistres_documents_uploader
        FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_sinistres_documents_sinistre (sinistre_id),
    INDEX idx_sinistres_documents_type (type_document)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- Audit trail
-- --------------------------------------------------------------------
CREATE TABLE sinistres_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinistre_id INT NOT NULL,
    action ENUM(
        'creation','changement_statut','update','indemnisation',
        'cloture','document_ajoute','document_supprime'
    ) NOT NULL,
    statut_avant VARCHAR(50) DEFAULT NULL,
    statut_apres VARCHAR(50) DEFAULT NULL,
    details TEXT DEFAULT NULL,
    user_id INT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_sinistres_log_sinistre
        FOREIGN KEY (sinistre_id) REFERENCES sinistres(id) ON DELETE CASCADE,
    CONSTRAINT fk_sinistres_log_user
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,

    INDEX idx_sinistres_log_sinistre_date (sinistre_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
