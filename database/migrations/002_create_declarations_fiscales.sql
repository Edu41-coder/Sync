-- ====================================================================
-- Migration 002 : Tables déclarations fiscales + documents
-- ====================================================================
-- Support du module déclaration fiscale guidée (CoproprietaireController)

CREATE TABLE IF NOT EXISTS declarations_fiscales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coproprietaire_id INT NOT NULL,
    annee_fiscale INT NOT NULL,
    statut ENUM('en_cours','terminee','validee') DEFAULT 'en_cours',
    donnees_extraites JSON DEFAULT NULL,
    recap_fiscal TEXT DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_proprio_annee (coproprietaire_id, annee_fiscale),
    FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS declaration_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    declaration_id INT NOT NULL,
    coproprietaire_id INT NOT NULL,
    type_document ENUM('releve_loyers','tableau_amortissement','taxe_fonciere','assurance_pno','autre') DEFAULT 'autre',
    nom_fichier VARCHAR(255) NOT NULL,
    chemin_fichier VARCHAR(500) NOT NULL,
    type_mime VARCHAR(100) DEFAULT NULL,
    taille INT DEFAULT NULL,
    statut ENUM('uploade','analyse','erreur') DEFAULT 'uploade',
    analyse_ia TEXT DEFAULT NULL,
    montant_extrait DECIMAL(12,2) DEFAULT NULL,
    donnees_extraites JSON DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (declaration_id) REFERENCES declarations_fiscales(id) ON DELETE CASCADE,
    FOREIGN KEY (coproprietaire_id) REFERENCES coproprietaires(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
