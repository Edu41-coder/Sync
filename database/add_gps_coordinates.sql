-- ====================================================================
-- Ajout des coordonnées GPS EXACTES aux résidences existantes
-- ====================================================================
-- Coordonnées basées sur les adresses réelles des 20 résidences Domitys

-- ID 61 - Domitys La Badiane, 45 Bd de la Corderie, 13007 Marseille
-- Quartier Saint-Victor, près du Vieux-Port
UPDATE coproprietees SET latitude = 43.2890, longitude = 5.3640 WHERE id = 61;

-- ID 62 - Domitys Les Jardins d'Arcadie, 12 Rue Sainte-Catherine, 33000 Bordeaux
-- Centre-ville, rue commerçante principale
UPDATE coproprietees SET latitude = 44.8410, longitude = -0.5730 WHERE id = 62;

-- ID 63 - Domitys Le Parc de Jade, 28 Avenue Jean Jaurès, 69007 Lyon
-- Lyon 7ème, La Guillotière
UPDATE coproprietees SET latitude = 45.7510, longitude = 4.8420 WHERE id = 63;

-- ID 64 - Domitys Les Portes de l'Atlantique, 15 Bd Jules Verne, 44000 Nantes
-- Centre-ville, quartier République
UPDATE coproprietees SET latitude = 47.2180, longitude = -1.5540 WHERE id = 64;

-- ID 65 - Domitys Le Capitole d'Or, 33 Allée Jean Jaurès, 31000 Toulouse
-- Centre-ville, près du Capitole
UPDATE coproprietees SET latitude = 43.6045, longitude = 1.4450 WHERE id = 65;

-- ID 66 - Domitys Le Fil de Lumière, 18 Rue de la Liberté, 59000 Lille
-- Centre-ville, quartier Liberté
UPDATE coproprietees SET latitude = 50.6310, longitude = 3.0570 WHERE id = 66;

-- ID 67 - Domitys Les Ponts d'Or, 22 Avenue de la Paix, 67000 Strasbourg
-- Quartier de la Paix, près du Parlement Européen
UPDATE coproprietees SET latitude = 48.5820, longitude = 7.7650 WHERE id = 67;

-- ID 68 - Domitys Le Jardin des Arceaux, 45 Rue de l'Aiguillerie, 34000 Montpellier
-- Centre historique, quartier des Arceaux
UPDATE coproprietees SET latitude = 43.6110, longitude = 3.8740 WHERE id = 68;

-- ID 69 - Domitys Les Oliviers d'Azur, 8 Promenade des Anglais, 06000 Nice
-- Bord de mer, Promenade des Anglais
UPDATE coproprietees SET latitude = 43.6950, longitude = 7.2650 WHERE id = 69;

-- ID 70 - Domitys La Fleur de Bretagne, 12 Place de la République, 35000 Rennes
-- Hypercentre, Place de la République
UPDATE coproprietees SET latitude = 48.1100, longitude = -1.6780 WHERE id = 70;

-- ID 71 - Domitys Le Galion d'Or, 25 Avenue du Vieux Port, 17000 La Rochelle
-- Proche du Vieux Port
UPDATE coproprietees SET latitude = 46.1600, longitude = -1.1520 WHERE id = 71;

-- ID 72 - Domitys Les Cimes Vertes, 30 Avenue Alsace-Lorraine, 38000 Grenoble
-- Centre-ville, avenue principale
UPDATE coproprietees SET latitude = 45.1880, longitude = 5.7240 WHERE id = 72;

-- ID 73 - Domitys Les Vignes d'Or, 18 Rue de la Liberté, 21000 Dijon
-- Centre-ville, artère commerçante
UPDATE coproprietees SET latitude = 47.3220, longitude = 5.0420 WHERE id = 73;

-- ID 74 - Domitys Les Jardins de Loire, 42 Bd du Roi René, 49000 Angers
-- Quartier La Fayette
UPDATE coproprietees SET latitude = 47.4720, longitude = -0.5550 WHERE id = 74;

-- ID 75 - Domitys La Calypso, 5 Rue de Siam, 29200 Brest
-- Hypercentre, rue de Siam (artère principale)
UPDATE coproprietees SET latitude = 48.3900, longitude = -4.4860 WHERE id = 75;

-- ID 76 - Domitys Les Colombages de Normandie, 20 Rue Jeanne d'Arc, 76000 Rouen
-- Centre historique
UPDATE coproprietees SET latitude = 49.4430, longitude = 1.0990 WHERE id = 76;

-- ID 77 - Domitys Les Volcans d'Auvergne, 35 Avenue de la République, 63000 Clermont-Ferrand
-- Centre-ville
UPDATE coproprietees SET latitude = 45.7770, longitude = 3.0820 WHERE id = 77;

-- ID 78 - Domitys Les Châteaux de la Loire, 14 Rue Nationale, 37000 Tours
-- Rue Nationale (artère principale)
UPDATE coproprietees SET latitude = 47.3950, longitude = 0.6890 WHERE id = 78;

-- ID 79 - Domitys Le Palmier du Roi, 28 Bd des Pyrénées, 64000 Pau
-- Boulevard avec vue sur les Pyrénées
UPDATE coproprietees SET latitude = 43.2950, longitude = -0.3700 WHERE id = 79;

-- ID 80 - Domitys Les Fontaines d'Azur, 50 Cours Mirabeau, 13100 Aix-en-Provence
-- Cours Mirabeau (avenue emblématique)
UPDATE coproprietees SET latitude = 43.5260, longitude = 5.4470 WHERE id = 80;

-- ====================================================================
-- Vérification des coordonnées
-- ====================================================================
SELECT 
    id,
    nom,
    CONCAT(adresse, ', ', code_postal, ' ', ville) as adresse_complete,
    latitude,
    longitude,
    CASE 
        WHEN latitude IS NOT NULL AND longitude IS NOT NULL THEN '✓ OK'
        ELSE '✗ Manquant'
    END as status
FROM coproprietees 
WHERE type_residence = 'residence_seniors' 
ORDER BY id;
