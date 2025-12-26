-- ====================================================================
-- DONNÉES DE TEST - Résidences Domitys avec occupations
-- ====================================================================
-- À exécuter : mysql -u root synd_gest < donnees_test_residences.sql

USE synd_gest;

-- Insérer des résidences seniors de test si elles n'existent pas
INSERT IGNORE INTO coproprietees (id, nom, adresse, code_postal, ville, type_residence, exploitant_id) VALUES
(100, 'Résidence Les Acacias', '15 Avenue des Fleurs', '69001', 'Lyon', 'residence_seniors', 1),
(101, 'Résidence Le Jardin Fleuri', '42 Rue du Parc', '75015', 'Paris', 'residence_seniors', 1),
(102, 'Résidence Les Oliviers', '8 Boulevard du Soleil', '13008', 'Marseille', 'residence_seniors', 1),
(103, 'Résidence Bel Air', '23 Chemin des Vignes', '31000', 'Toulouse', 'residence_seniors', 1),
(104, 'Résidence Les Magnolias', '56 Avenue de la Liberté', '44000', 'Nantes', 'residence_seniors', 1);

-- Insérer des lots pour chaque résidence (10 lots par résidence)
-- Résidence Les Acacias (id 100)
INSERT IGNORE INTO lots (id, copropriete_id, numero_lot, type, surface, tantiemes_generaux) VALUES
(1000, 100, 'A101', 'appartement', 45.50, 450),
(1001, 100, 'A102', 'appartement', 52.00, 520),
(1002, 100, 'A103', 'appartement', 38.00, 380),
(1003, 100, 'A201', 'appartement', 48.00, 480),
(1004, 100, 'A202', 'appartement', 55.00, 550),
(1005, 100, 'A203', 'appartement', 42.00, 420),
(1006, 100, 'A301', 'appartement', 50.00, 500),
(1007, 100, 'A302', 'appartement', 47.00, 470),
(1008, 100, 'A303', 'appartement', 60.00, 600),
(1009, 100, 'A304', 'appartement', 45.00, 450);

-- Résidence Le Jardin Fleuri (id 101)
INSERT IGNORE INTO lots (id, copropriete_id, numero_lot, type, surface, tantiemes_generaux) VALUES
(1010, 101, 'B101', 'appartement', 40.00, 400),
(1011, 101, 'B102', 'appartement', 45.00, 450),
(1012, 101, 'B103', 'appartement', 50.00, 500),
(1013, 101, 'B201', 'appartement', 48.00, 480),
(1014, 101, 'B202', 'appartement', 52.00, 520),
(1015, 101, 'B203', 'appartement', 46.00, 460),
(1016, 101, 'B301', 'appartement', 55.00, 550),
(1017, 101, 'B302', 'appartement', 49.00, 490);

-- Résidence Les Oliviers (id 102)
INSERT IGNORE INTO lots (id, copropriete_id, numero_lot, type, surface, tantiemes_generaux) VALUES
(1020, 102, 'C101', 'appartement', 42.00, 420),
(1021, 102, 'C102', 'appartement', 48.00, 480),
(1022, 102, 'C103', 'appartement', 50.00, 500),
(1023, 102, 'C201', 'appartement', 45.00, 450),
(1024, 102, 'C202', 'appartement', 52.00, 520),
(1025, 102, 'C203', 'appartement', 58.00, 580);

-- Résidence Bel Air (id 103)
INSERT IGNORE INTO lots (id, copropriete_id, numero_lot, type, surface, tantiemes_generaux) VALUES
(1030, 103, 'D101', 'appartement', 44.00, 440),
(1031, 103, 'D102', 'appartement', 50.00, 500),
(1032, 103, 'D103', 'appartement', 46.00, 460),
(1033, 103, 'D201', 'appartement', 52.00, 520),
(1034, 103, 'D202', 'appartement', 48.00, 480),
(1035, 103, 'D203', 'appartement', 55.00, 550),
(1036, 103, 'D301', 'appartement', 60.00, 600);

-- Résidence Les Magnolias (id 104)
INSERT IGNORE INTO lots (id, copropriete_id, numero_lot, type, surface, tantiemes_generaux) VALUES
(1040, 104, 'E101', 'appartement', 43.00, 430),
(1041, 104, 'E102', 'appartement', 49.00, 490),
(1042, 104, 'E103', 'appartement', 51.00, 510),
(1043, 104, 'E201', 'appartement', 47.00, 470),
(1044, 104, 'E202', 'appartement', 53.00, 530);

-- Insérer des résidents de test
INSERT IGNORE INTO residents_seniors (id, civilite, nom, prenom, date_naissance, telephone_mobile, email, urgence_nom, urgence_telephone, urgence_lien, niveau_autonomie, actif) VALUES
(1, 'Mme', 'Dupont', 'Marie', '1945-03-15', '0601020304', 'marie.dupont@email.com', 'Pierre Dupont', '0605060708', 'Fils', 'autonome', 1),
(2, 'M', 'Martin', 'Jean', '1940-07-22', '0602030405', 'jean.martin@email.com', 'Sophie Martin', '0606070809', 'Fille', 'autonome', 1),
(3, 'Mme', 'Bernard', 'Françoise', '1948-11-08', '0603040506', 'francoise.bernard@email.com', 'Luc Bernard', '0607080910', 'Fils', 'semi_autonome', 1),
(4, 'M', 'Dubois', 'Robert', '1942-05-18', '0604050607', 'robert.dubois@email.com', 'Anne Dubois', '0608091011', 'Fille', 'autonome', 1),
(5, 'Mme', 'Laurent', 'Jeanne', '1946-09-25', '0605060708', 'jeanne.laurent@email.com', 'Marc Laurent', '0609101112', 'Fils', 'dependant', 1),
(6, 'M', 'Simon', 'Pierre', '1944-12-30', '0606070809', 'pierre.simon@email.com', 'Claire Simon', '0610111213', 'Fille', 'autonome', 1),
(7, 'Mme', 'Michel', 'Yvonne', '1947-04-12', '0607080910', 'yvonne.michel@email.com', 'Paul Michel', '0611121314', 'Fils', 'semi_autonome', 1),
(8, 'M', 'Lefebvre', 'André', '1943-08-07', '0608091011', 'andre.lefebvre@email.com', 'Marie Lefebvre', '0612131415', 'Fille', 'autonome', 1),
(9, 'Mme', 'Leroy', 'Suzanne', '1949-01-20', '0609101112', 'suzanne.leroy@email.com', 'Jean Leroy', '0613141516', 'Fils', 'autonome', 1),
(10, 'M', 'Moreau', 'Georges', '1941-06-15', '0610111213', 'georges.moreau@email.com', 'Nicole Moreau', '0614151617', 'Fille', 'semi_autonome', 1),
(11, 'Mme', 'Girard', 'Marcelle', '1950-02-28', '0611121314', 'marcelle.girard@email.com', 'Philippe Girard', '0615161718', 'Fils', 'autonome', 1),
(12, 'M', 'Roux', 'Henri', '1945-10-10', '0612131415', 'henri.roux@email.com', 'Isabelle Roux', '0616171819', 'Fille', 'autonome', 1);

-- Insérer des occupations (80% d'occupation moyenne)
INSERT IGNORE INTO occupations_residents (id, resident_id, lot_id, date_entree, date_sortie, loyer_mensuel_resident, loyer_mensuel_proprietaire, depot_garantie, forfait_services, statut) VALUES
-- Résidence Les Acacias (8 sur 10 occupés)
(1, 1, 1000, '2023-01-15', NULL, 1450.00, 850.00, 2900.00, 'confort', 'actif'),
(2, 2, 1001, '2023-02-01', NULL, 1550.00, 900.00, 3100.00, 'premium', 'actif'),
(3, 3, 1002, '2023-03-10', NULL, 1350.00, 800.00, 2700.00, 'essentiel', 'actif'),
(4, 4, 1003, '2023-04-05', NULL, 1450.00, 850.00, 2900.00, 'serenite', 'actif'),
(5, 5, 1005, '2023-05-20', NULL, 1750.00, 1000.00, 3500.00, 'premium', 'actif'),
(6, 6, 1006, '2023-06-15', NULL, 1450.00, 850.00, 2900.00, 'confort', 'actif'),
(7, 7, 1007, '2023-07-01', NULL, 1400.00, 820.00, 2800.00, 'serenite', 'actif'),
(8, 8, 1009, '2023-08-10', NULL, 1450.00, 850.00, 2900.00, 'confort', 'actif'),
-- Résidence Le Jardin Fleuri (6 sur 8 occupés)
(9, 9, 1010, '2023-02-15', NULL, 1500.00, 880.00, 3000.00, 'serenite', 'actif'),
(10, 10, 1012, '2023-03-20', NULL, 1550.00, 900.00, 3100.00, 'confort', 'actif'),
(11, 11, 1013, '2023-04-10', NULL, 1450.00, 850.00, 2900.00, 'essentiel', 'actif'),
(12, 12, 1015, '2023-05-05', NULL, 1500.00, 880.00, 3000.00, 'serenite', 'actif'),
-- Résidence Les Oliviers (4 sur 6 occupés - 66%)
(13, 1, 1020, '2023-06-01', NULL, 1400.00, 820.00, 2800.00, 'essentiel', 'actif'),
(14, 2, 1022, '2023-07-15', NULL, 1550.00, 900.00, 3100.00, 'premium', 'actif');

-- Afficher un résumé
SELECT 
    c.nom as residence,
    COUNT(DISTINCT l.id) as total_lots,
    COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) as lots_occupes,
    ROUND((COUNT(DISTINCT CASE WHEN o.statut = 'actif' THEN o.id END) / COUNT(DISTINCT l.id)) * 100, 1) as taux_occupation
FROM coproprietees c
LEFT JOIN lots l ON c.id = l.copropriete_id
LEFT JOIN occupations_residents o ON l.id = o.lot_id AND o.statut = 'actif'
WHERE c.type_residence = 'residence_seniors'
GROUP BY c.id
ORDER BY c.nom;

SELECT '✅ Données de test insérées avec succès!' as message;
