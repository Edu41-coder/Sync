-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 12-12-2025 a las 02:06:57
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `synd_gest`
--

DELIMITER $$
--
-- Funciones
--
CREATE DEFINER=`root`@`localhost` FUNCTION `has_permission` (`p_role` VARCHAR(50), `p_module` VARCHAR(50), `p_action` VARCHAR(50)) RETURNS TINYINT(1) DETERMINISTIC BEGIN
    DECLARE has_access BOOLEAN DEFAULT FALSE;
    
    -- Admin a tous les droits
    IF p_role = 'admin' THEN
        RETURN TRUE;
    END IF;
    
    -- Vérifier permission spécifique
    SELECT allowed INTO has_access
    FROM permissions
    WHERE role = p_role 
      AND module = p_module 
      AND (action = p_action OR action = 'all')
    LIMIT 1;
    
    RETURN IFNULL(has_access, FALSE);
END$$

DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `appels_fonds`
--

CREATE TABLE `appels_fonds` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `exercice_id` int(11) NOT NULL,
  `trimestre` int(11) DEFAULT NULL CHECK (`trimestre` between 1 and 4),
  `date_emission` date NOT NULL,
  `date_echeance` date NOT NULL,
  `montant_total` decimal(12,2) NOT NULL DEFAULT 0.00,
  `type` enum('provision','regularisation','travaux','exceptionnel') NOT NULL DEFAULT 'provision',
  `statut` enum('brouillon','emis','cloture') NOT NULL DEFAULT 'brouillon',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `assemblees_generales`
--

CREATE TABLE `assemblees_generales` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `type` enum('ordinaire','extraordinaire') NOT NULL DEFAULT 'ordinaire',
  `date_ag` datetime NOT NULL,
  `lieu` varchar(255) DEFAULT NULL,
  `ordre_du_jour` text DEFAULT NULL,
  `proces_verbal` text DEFAULT NULL,
  `document_convocation` varchar(255) DEFAULT NULL,
  `document_pv` varchar(255) DEFAULT NULL,
  `statut` enum('planifiee','convoquee','tenue','annulee') NOT NULL DEFAULT 'planifiee',
  `quorum_atteint` tinyint(1) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `baux`
--

CREATE TABLE `baux` (
  `id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `locataire_id` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `loyer_mensuel` decimal(10,2) NOT NULL,
  `charges_mensuelles` decimal(10,2) DEFAULT 0.00,
  `depot_garantie` decimal(10,2) DEFAULT 0.00,
  `type_bail` enum('vide','meuble','commercial','professionnel') NOT NULL DEFAULT 'vide',
  `indexation` varchar(50) DEFAULT 'IRL',
  `etat` enum('actif','resilie','termine','suspendu') NOT NULL DEFAULT 'actif',
  `date_signature` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comptes_comptables`
--

CREATE TABLE `comptes_comptables` (
  `id` int(11) NOT NULL,
  `numero_compte` varchar(20) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `type` enum('actif','passif','charge','produit') NOT NULL,
  `parent_id` int(11) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `comptes_comptables`
--

INSERT INTO `comptes_comptables` (`id`, `numero_compte`, `libelle`, `type`, `parent_id`, `actif`, `created_at`) VALUES
(1, '512', 'Banque', 'actif', NULL, 1, '2025-11-28 23:39:25'),
(2, '531', 'Caisse', 'actif', NULL, 1, '2025-11-28 23:39:25'),
(3, '450', 'Copropriétaires - Appels de fonds', 'actif', NULL, 1, '2025-11-28 23:39:25'),
(4, '455', 'Copropriétaires - Avances', 'actif', NULL, 1, '2025-11-28 23:39:25'),
(5, '401', 'Fournisseurs', 'passif', NULL, 1, '2025-11-28 23:39:25'),
(6, '102', 'Fonds de travaux', 'passif', NULL, 1, '2025-11-28 23:39:25'),
(7, '120', 'Budget prévisionnel', 'passif', NULL, 1, '2025-11-28 23:39:25'),
(8, '606', 'Achats non stockés (eau, électricité)', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(9, '613', 'Locations', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(10, '614', 'Charges locatives', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(11, '615', 'Entretien et réparations', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(12, '616', 'Primes d\'assurance', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(13, '622', 'Honoraires', 'charge', NULL, 1, '2025-11-28 23:39:25'),
(14, '700', 'Appels de fonds', 'produit', NULL, 1, '2025-11-28 23:39:25'),
(15, '701', 'Produits exceptionnels', 'produit', NULL, 1, '2025-11-28 23:39:25');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `contrats_gestion`
--

CREATE TABLE `contrats_gestion` (
  `id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `exploitant_id` int(11) NOT NULL,
  `numero_contrat` varchar(50) NOT NULL,
  `type_contrat` enum('bail_commercial','bail_professionnel','mandat_gestion') NOT NULL DEFAULT 'bail_commercial',
  `date_signature` date NOT NULL,
  `date_effet` date NOT NULL,
  `date_fin` date DEFAULT NULL,
  `duree_initiale_annees` int(11) NOT NULL,
  `loyer_mensuel_garanti` decimal(10,2) NOT NULL,
  `indexation_type` enum('IRL','ICC','ILAT','fixe') DEFAULT 'IRL',
  `date_revision_annuelle` date DEFAULT NULL,
  `franchise_loyer_mois` int(11) DEFAULT 0,
  `charges_mensuelles` decimal(10,2) DEFAULT 0.00,
  `qui_paye_charges` enum('proprietaire','exploitant','partage') DEFAULT 'exploitant',
  `qui_paye_travaux` enum('proprietaire','exploitant','partage') DEFAULT 'partage',
  `qui_paye_taxe_fonciere` enum('proprietaire','exploitant') DEFAULT 'proprietaire',
  `dispositif_fiscal` enum('Censi-Bouvard','LMNP','LMP','Malraux','Pinel','Autre','Aucun') DEFAULT 'Censi-Bouvard',
  `reduction_impot_taux` decimal(5,2) DEFAULT NULL,
  `recuperation_tva` tinyint(1) DEFAULT 1,
  `statut_loueur` enum('LMNP','LMP','location_nue') DEFAULT 'LMNP',
  `garantie_loyer` tinyint(1) DEFAULT 1,
  `garantie_type` enum('garantie_loyer_impaye','caution_bancaire','depot_garantie','aucune') DEFAULT 'garantie_loyer_impaye',
  `montant_garantie` decimal(10,2) DEFAULT NULL,
  `meuble` tinyint(1) DEFAULT 1,
  `valeur_mobilier` decimal(10,2) DEFAULT NULL,
  `inventaire_mobilier` text DEFAULT NULL,
  `clause_resiliation` text DEFAULT NULL,
  `clause_renouvellement` text DEFAULT NULL,
  `conditions_particulieres` text DEFAULT NULL,
  `jour_paiement_loyer` int(11) DEFAULT 1,
  `mode_paiement` enum('virement','prelevement','cheque') DEFAULT 'virement',
  `iban_proprietaire` varchar(34) DEFAULT NULL,
  `statut` enum('projet','actif','resilie','termine','suspendu','en_litige') NOT NULL DEFAULT 'projet',
  `date_resiliation` date DEFAULT NULL,
  `motif_resiliation` text DEFAULT NULL,
  `document_bail` varchar(255) DEFAULT NULL,
  `document_etat_lieux` varchar(255) DEFAULT NULL,
  `document_inventaire` varchar(255) DEFAULT NULL,
  `document_mandat` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `contrats_gestion`
--

INSERT INTO `contrats_gestion` (`id`, `lot_id`, `coproprietaire_id`, `exploitant_id`, `numero_contrat`, `type_contrat`, `date_signature`, `date_effet`, `date_fin`, `duree_initiale_annees`, `loyer_mensuel_garanti`, `indexation_type`, `date_revision_annuelle`, `franchise_loyer_mois`, `charges_mensuelles`, `qui_paye_charges`, `qui_paye_travaux`, `qui_paye_taxe_fonciere`, `dispositif_fiscal`, `reduction_impot_taux`, `recuperation_tva`, `statut_loueur`, `garantie_loyer`, `garantie_type`, `montant_garantie`, `meuble`, `valeur_mobilier`, `inventaire_mobilier`, `clause_resiliation`, `clause_renouvellement`, `conditions_particulieres`, `jour_paiement_loyer`, `mode_paiement`, `iban_proprietaire`, `statut`, `date_resiliation`, `motif_resiliation`, `document_bail`, `document_etat_lieux`, `document_inventaire`, `document_mandat`, `notes`, `created_at`, `updated_at`) VALUES
(1, 150, 6, 1, 'DOM-MAR-2024-001', 'bail_commercial', '2024-02-15', '2024-05-01', NULL, 9, 850.00, 'IRL', NULL, 3, 120.00, 'exploitant', 'partage', 'proprietaire', 'Censi-Bouvard', 11.00, 1, 'LMNP', 1, 'garantie_loyer_impaye', NULL, 1, 15000.00, NULL, NULL, NULL, NULL, 5, 'virement', 'FR7630001007941234567890185', 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(2, 278, 7, 1, 'DOM-BOR-2024-002', 'bail_commercial', '2024-03-20', '2024-06-01', NULL, 9, 920.00, 'IRL', NULL, 3, 130.00, 'exploitant', 'partage', 'proprietaire', 'Censi-Bouvard', 11.00, 1, 'LMNP', 1, 'garantie_loyer_impaye', NULL, 1, 16000.00, NULL, NULL, NULL, NULL, 5, 'prelevement', NULL, 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(3, 285, 8, 1, 'DOM-LYO-2024-003', 'bail_commercial', '2024-01-10', '2024-04-01', NULL, 9, 880.00, 'IRL', NULL, 3, 125.00, 'exploitant', 'partage', 'proprietaire', 'Censi-Bouvard', 11.00, 1, 'LMNP', 1, 'garantie_loyer_impaye', NULL, 1, 15500.00, NULL, NULL, NULL, NULL, 5, 'virement', NULL, 'actif', NULL, NULL, NULL, NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coproprietaires`
--

CREATE TABLE `coproprietaires` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `civilite` enum('M','Mme','Mlle') NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `adresse_principale` varchar(255) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `telephone_mobile` varchar(20) DEFAULT NULL,
  `profession` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `coproprietaires`
--

INSERT INTO `coproprietaires` (`id`, `user_id`, `civilite`, `nom`, `prenom`, `date_naissance`, `adresse_principale`, `code_postal`, `ville`, `telephone`, `email`, `telephone_mobile`, `profession`, `notes`, `created_at`, `updated_at`) VALUES
(6, NULL, 'M', 'MARTIN', 'Pierre', '1965-03-15', '25 Avenue des Champs-Élysées', '75008', 'Paris', '01 42 65 78 90', 'pierre.martin@gmail.com', NULL, 'Chef d\'entreprise', NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(7, NULL, 'Mme', 'DUBOIS', 'Sophie', '1970-07-22', '18 Rue de la République', '69002', 'Lyon', '04 78 52 33 44', 'sophie.dubois@orange.fr', NULL, 'Médecin', NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(8, NULL, 'M', 'BERNARD', 'Jean', '1968-11-08', '42 Boulevard de la Croisette', '06400', 'Cannes', '04 93 68 45 67', 'jean.bernard@free.fr', NULL, 'Notaire', NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(9, NULL, 'Mme', 'LEFEBVRE', 'Marie', '1972-05-30', '12 Place Bellecour', '69002', 'Lyon', '04 72 41 22 33', 'marie.lefebvre@wanadoo.fr', NULL, 'Avocat', NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(10, NULL, 'M', 'MOREAU', 'Laurent', '1975-09-12', '88 Rue du Faubourg Saint-Honoré', '75008', 'Paris', '01 45 63 88 99', 'laurent.moreau@gmail.com', NULL, 'Consultant', NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `coproprietees`
--

CREATE TABLE `coproprietees` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `adresse` varchar(255) NOT NULL,
  `code_postal` varchar(10) NOT NULL,
  `ville` varchar(100) NOT NULL,
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `nombre_lots` int(11) DEFAULT 0,
  `nombre_batiments` int(11) DEFAULT 1,
  `type_residence` enum('copropriete_classique','residence_seniors','residence_etudiante','residence_tourisme','autre') DEFAULT 'copropriete_classique',
  `date_construction` date DEFAULT NULL,
  `syndic_id` int(11) DEFAULT NULL,
  `exploitant_id` int(11) DEFAULT NULL,
  `reglement_document` varchar(255) DEFAULT NULL,
  `immatriculation` varchar(50) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `coproprietees`
--

INSERT INTO `coproprietees` (`id`, `nom`, `adresse`, `code_postal`, `ville`, `latitude`, `longitude`, `nombre_lots`, `nombre_batiments`, `type_residence`, `date_construction`, `syndic_id`, `exploitant_id`, `reglement_document`, `immatriculation`, `description`, `created_at`, `updated_at`) VALUES
(61, 'Domitys - La Badiane', '45 Boulevard de la Corderie', '13007', 'Marseille', 43.28900000, 5.36400000, 85, 1, 'residence_seniors', '2018-06-15', 1, 1, NULL, 'DOM13001', 'Résidence services seniors avec piscine, restaurant et espaces bien-être. Proche du Vieux-Port.', '2025-11-30 02:19:25', '2025-12-11 00:02:20'),
(62, 'Domitys - Les Jardins d\'Arcadie', '12 Rue Sainte-Catherine', '33000', 'Bordeaux', 44.84100000, -0.57300000, 92, 1, 'residence_seniors', '2019-03-20', 1, 1, NULL, 'DOM33001', 'Résidence seniors en centre-ville, proche commerces et transports. Restaurant gastronomique.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(63, 'Domitys - Le Parc de Jade', '28 Avenue Jean Jaurès', '69007', 'Lyon', 45.75100000, 4.84200000, 110, 2, 'residence_seniors', '2017-09-10', 1, 1, NULL, 'DOM69001', 'Résidence services seniors avec jardin arboré, salle de sport et piscine couverte.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(64, 'Domitys - Les Portes de l\'Atlantique', '15 Boulevard Jules Verne', '44000', 'Nantes', 47.21800000, -1.55400000, 78, 1, 'residence_seniors', '2020-01-15', 1, 1, NULL, 'DOM44001', 'Résidence moderne avec vue sur la Loire. Animations culturelles et sorties organisées.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(65, 'Domitys - Le Capitole d\'Or', '33 Allée Jean Jaurès', '31000', 'Toulouse', 43.60450000, 1.44500000, 95, 1, 'residence_seniors', '2018-11-22', 1, 1, NULL, 'DOM31001', 'Résidence services seniors au cœur de la Ville Rose. Terrasses avec vue sur les toits.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(66, 'Domitys - Le Fil de Lumière', '18 Rue de la Liberté', '59000', 'Lille', 50.63100000, 3.05700000, 88, 1, 'residence_seniors', '2019-05-08', 1, 1, NULL, 'DOM59001', 'Résidence seniors avec salle de cinéma, bibliothèque et espace multimédia. Proche Grand Place.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(67, 'Domitys - Les Ponts d\'Or', '22 Avenue de la Paix', '67000', 'Strasbourg', 48.58200000, 7.76500000, 75, 1, 'residence_seniors', '2017-04-12', 1, 1, NULL, 'DOM67001', 'Résidence seniors proche Petite France. Architecture alsacienne traditionnelle. Restaurant avec spécialités locales.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(68, 'Domitys - Le Jardin des Arceaux', '45 Rue de l\'Aiguillerie', '34000', 'Montpellier', 43.61100000, 3.87400000, 102, 1, 'residence_seniors', '2020-06-30', 1, 1, NULL, 'DOM34001', 'Résidence moderne avec jardin méditerranéen, piscine chauffée et salle de fitness.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(69, 'Domitys - Les Oliviers d\'Azur', '8 Promenade des Anglais', '06000', 'Nice', 43.69500000, 7.26500000, 120, 2, 'residence_seniors', '2016-02-18', 1, 1, NULL, 'DOM06001', 'Résidence de prestige face à la Baie des Anges. Piscine, spa et restaurant panoramique.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(70, 'Domitys - La Fleur de Bretagne', '12 Place de la République', '35000', 'Rennes', 48.11000000, -1.67800000, 68, 1, 'residence_seniors', '2019-09-25', 1, 1, NULL, 'DOM35001', 'Résidence seniors en centre historique. Animations culturelles bretonnes et sorties découverte.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(71, 'Domitys - Le Galion d\'Or', '25 Avenue du Vieux Port', '17000', 'La Rochelle', 46.16000000, -1.15200000, 82, 1, 'residence_seniors', '2018-08-14', 1, 1, NULL, 'DOM17001', 'Résidence face au port. Terrasses avec vue mer, jardin maritime et restaurant fruits de mer.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(72, 'Domitys - Les Cimes Vertes', '30 Avenue Alsace-Lorraine', '38000', 'Grenoble', 45.18800000, 5.72400000, 72, 1, 'residence_seniors', '2017-12-05', 1, 1, NULL, 'DOM38001', 'Résidence seniors avec vue sur les montagnes. Salle de sport, piscine et activités de plein air.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(73, 'Domitys - Les Vignes d\'Or', '18 Rue de la Liberté', '21000', 'Dijon', 47.32200000, 5.04200000, 65, 1, 'residence_seniors', '2019-04-17', 1, 1, NULL, 'DOM21001', 'Résidence au cœur de la capitale bourguignonne. Cave à vins et dégustations œnologiques.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(74, 'Domitys - Les Jardins de Loire', '42 Boulevard du Roi René', '49000', 'Angers', 47.47200000, -0.55500000, 90, 1, 'residence_seniors', '2018-05-22', 1, 1, NULL, 'DOM49001', 'Résidence avec jardins potagers partagés. Proche du château. Animations culturelles.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(75, 'Domitys - La Calypso', '5 Rue de Siam', '29200', 'Brest', 48.39000000, -4.48600000, 76, 1, 'residence_seniors', '2020-02-10', 1, 1, NULL, 'DOM29001', 'Résidence maritime avec vue sur le port. Restaurant spécialités bretonnes et terrasses.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(76, 'Domitys - Les Colombages de Normandie', '20 Rue Jeanne d\'Arc', '76000', 'Rouen', 49.44300000, 1.09900000, 58, 1, 'residence_seniors', '2017-10-30', 1, 1, NULL, 'DOM76001', 'Résidence en centre historique. Architecture normande. Proche cathédrale et musées.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(77, 'Domitys - Les Volcans d\'Auvergne', '35 Avenue de la République', '63000', 'Clermont-Ferrand', 45.77700000, 3.08200000, 70, 1, 'residence_seniors', '2019-07-18', 1, 1, NULL, 'DOM63001', 'Résidence avec vue sur la chaîne des Puys. Piscine thermale et espace détente.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(78, 'Domitys - Les Châteaux de la Loire', '14 Rue Nationale', '37000', 'Tours', 47.39500000, 0.68900000, 84, 1, 'residence_seniors', '2018-03-25', 1, 1, NULL, 'DOM37001', 'Résidence élégante style Renaissance. Jardin à la française et sorties découverte châteaux.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(79, 'Domitys - Le Palmier du Roi', '28 Boulevard des Pyrénées', '64000', 'Pau', 43.29500000, -0.37000000, 66, 1, 'residence_seniors', '2020-09-12', 1, 1, NULL, 'DOM64001', 'Résidence avec vue panoramique sur les Pyrénées. Proche du château. Jardin exotique.', '2025-11-30 02:19:25', '2025-12-01 00:28:08'),
(80, 'Domitys - Les Fontaines d\'Azur', '50 Cours Mirabeau', '13100', 'Aix-en-Provence', 43.52600000, 5.44700000, 98, 1, 'residence_seniors', '2017-06-08', 1, 1, NULL, 'DOM13100', 'Résidence provençale avec fontaine centrale. Restaurant terrasse et jardin méditerranéen.', '2025-11-30 02:19:25', '2025-12-01 00:28:08');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `devis`
--

CREATE TABLE `devis` (
  `id` int(11) NOT NULL,
  `travaux_id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `numero_devis` varchar(50) DEFAULT NULL,
  `date_devis` date NOT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `montant_ttc` decimal(10,2) NOT NULL,
  `delai_execution` varchar(100) DEFAULT NULL,
  `validite` date DEFAULT NULL,
  `selectionne` tinyint(1) DEFAULT 0,
  `document` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `documents`
--

CREATE TABLE `documents` (
  `id` int(11) NOT NULL,
  `nom_fichier` varchar(255) NOT NULL,
  `nom_original` varchar(255) NOT NULL,
  `chemin_fichier` varchar(500) NOT NULL,
  `type_document` varchar(100) DEFAULT NULL,
  `categorie` enum('reglement','ag','contrat','facture','devis','plan','photo','correspondance','autre') NOT NULL DEFAULT 'autre',
  `copropriete_id` int(11) DEFAULT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `coproprietaire_id` int(11) DEFAULT NULL,
  `travaux_id` int(11) DEFAULT NULL,
  `sinistre_id` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `taille_fichier` int(11) DEFAULT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `date_upload` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ecritures_comptables`
--

CREATE TABLE `ecritures_comptables` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `exercice_id` int(11) NOT NULL,
  `date_ecriture` date NOT NULL,
  `compte_debit` int(11) NOT NULL,
  `compte_credit` int(11) NOT NULL,
  `montant` decimal(12,2) NOT NULL,
  `libelle` varchar(255) NOT NULL,
  `piece_justificative` varchar(255) DEFAULT NULL,
  `type_piece` enum('facture','paiement','od','an','avoir') NOT NULL DEFAULT 'facture',
  `numero_piece` varchar(50) DEFAULT NULL,
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exercices_comptables`
--

CREATE TABLE `exercices_comptables` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `budget_previsionnel` decimal(12,2) DEFAULT 0.00,
  `statut` enum('ouvert','cloture','archive') NOT NULL DEFAULT 'ouvert',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `exercices_comptables`
--

INSERT INTO `exercices_comptables` (`id`, `copropriete_id`, `annee`, `date_debut`, `date_fin`, `budget_previsionnel`, `statut`, `notes`, `created_at`) VALUES
(26, 61, 2025, '2025-01-01', '2025-12-31', 125000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(27, 62, 2025, '2025-01-01', '2025-12-31', 135000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(28, 63, 2025, '2025-01-01', '2025-12-31', 165000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(29, 64, 2025, '2025-01-01', '2025-12-31', 115000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(30, 65, 2025, '2025-01-01', '2025-12-31', 140000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(31, 66, 2025, '2025-01-01', '2025-12-31', 130000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(32, 67, 2025, '2025-01-01', '2025-12-31', 110000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(33, 68, 2025, '2025-01-01', '2025-12-31', 150000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(34, 69, 2025, '2025-01-01', '2025-12-31', 180000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(35, 70, 2025, '2025-01-01', '2025-12-31', 100000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(36, 71, 2025, '2025-01-01', '2025-12-31', 120000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(37, 72, 2025, '2025-01-01', '2025-12-31', 105000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(38, 73, 2025, '2025-01-01', '2025-12-31', 95000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(39, 74, 2025, '2025-01-01', '2025-12-31', 132000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(40, 75, 2025, '2025-01-01', '2025-12-31', 112000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(41, 76, 2025, '2025-01-01', '2025-12-31', 85000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(42, 77, 2025, '2025-01-01', '2025-12-31', 103000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(43, 78, 2025, '2025-01-01', '2025-12-31', 123000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(44, 79, 2025, '2025-01-01', '2025-12-31', 97000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(45, 80, 2025, '2025-01-01', '2025-12-31', 145000.00, 'ouvert', NULL, '2025-11-30 02:19:26'),
(46, 61, 2024, '2024-01-01', '2024-12-31', 118000.00, 'cloture', NULL, '2025-11-30 02:19:26'),
(47, 62, 2024, '2024-01-01', '2024-12-31', 128000.00, 'cloture', NULL, '2025-11-30 02:19:26'),
(48, 63, 2024, '2024-01-01', '2024-12-31', 158000.00, 'cloture', NULL, '2025-11-30 02:19:26'),
(49, 64, 2024, '2024-01-01', '2024-12-31', 110000.00, 'cloture', NULL, '2025-11-30 02:19:26'),
(50, 65, 2024, '2024-01-01', '2024-12-31', 133000.00, 'cloture', NULL, '2025-11-30 02:19:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `exploitants`
--

CREATE TABLE `exploitants` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `raison_sociale` varchar(200) NOT NULL,
  `forme_juridique` enum('SAS','SARL','SA','SCI','EURL','Autre') NOT NULL,
  `siret` varchar(14) NOT NULL,
  `siren` varchar(9) NOT NULL,
  `num_tva_intra` varchar(20) DEFAULT NULL,
  `adresse_siege` varchar(255) NOT NULL,
  `code_postal_siege` varchar(10) NOT NULL,
  `ville_siege` varchar(100) NOT NULL,
  `pays` varchar(100) DEFAULT 'France',
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `contact_principal_nom` varchar(200) DEFAULT NULL,
  `contact_principal_fonction` varchar(100) DEFAULT NULL,
  `contact_principal_email` varchar(100) DEFAULT NULL,
  `contact_principal_tel` varchar(20) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `bic` varchar(11) DEFAULT NULL,
  `banque` varchar(200) DEFAULT NULL,
  `assurance_responsabilite` varchar(200) DEFAULT NULL,
  `num_police_assurance` varchar(100) DEFAULT NULL,
  `date_validite_assurance` date DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_agrement` date DEFAULT NULL,
  `certifications` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `document_kbis` varchar(255) DEFAULT NULL,
  `document_assurance` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `exploitants`
--

INSERT INTO `exploitants` (`id`, `user_id`, `raison_sociale`, `forme_juridique`, `siret`, `siren`, `num_tva_intra`, `adresse_siege`, `code_postal_siege`, `ville_siege`, `pays`, `telephone`, `email`, `site_web`, `contact_principal_nom`, `contact_principal_fonction`, `contact_principal_email`, `contact_principal_tel`, `iban`, `bic`, `banque`, `assurance_responsabilite`, `num_police_assurance`, `date_validite_assurance`, `actif`, `date_agrement`, `certifications`, `notes`, `document_kbis`, `document_assurance`, `created_at`, `updated_at`) VALUES
(1, 7, 'DOMITYS SAS', 'SAS', '80377408400385', '803774084', 'FR82803774084', '3 Boulevard Romain Rolland', '75014', 'Paris', 'France', '01 41 18 29 29', 'contact@domitys.fr', 'https://www.domitys.fr', 'Service Propriétaires', 'Direction Investisseurs', 'proprietaires@domitys.fr', NULL, NULL, NULL, NULL, NULL, NULL, NULL, 1, '2010-01-15', '{\"AFNOR\": true, \"VISEHA\": true, \"ISO9001\": true}', NULL, NULL, NULL, '2025-11-30 02:15:32', '2025-11-30 22:24:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `factures_fournisseurs`
--

CREATE TABLE `factures_fournisseurs` (
  `id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `numero_facture` varchar(50) NOT NULL,
  `date_facture` date NOT NULL,
  `date_echeance` date DEFAULT NULL,
  `montant_ht` decimal(10,2) NOT NULL,
  `montant_tva` decimal(10,2) DEFAULT 0.00,
  `montant_ttc` decimal(10,2) NOT NULL,
  `type_charge` varchar(100) DEFAULT NULL,
  `statut` enum('attente','validee','payee','contestee','annulee') NOT NULL DEFAULT 'attente',
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` varchar(50) DEFAULT NULL,
  `document` varchar(255) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `factures_fournisseurs`
--
DELIMITER $$
CREATE TRIGGER `trg_facture_calc_ttc` BEFORE INSERT ON `factures_fournisseurs` FOR EACH ROW BEGIN
    IF NEW.montant_ttc IS NULL OR NEW.montant_ttc = 0 THEN
        SET NEW.montant_ttc = NEW.montant_ht + NEW.montant_tva;
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(200) NOT NULL,
  `siret` varchar(14) DEFAULT NULL,
  `adresse` varchar(255) DEFAULT NULL,
  `code_postal` varchar(10) DEFAULT NULL,
  `ville` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `contact_nom` varchar(100) DEFAULT NULL,
  `type_service` varchar(100) DEFAULT NULL,
  `iban` varchar(34) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lignes_appel_fonds`
--

CREATE TABLE `lignes_appel_fonds` (
  `id` int(11) NOT NULL,
  `appel_fonds_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `statut_paiement` enum('attente','partiel','paye','impaye','contentieux') NOT NULL DEFAULT 'attente',
  `montant_paye` decimal(10,2) DEFAULT 0.00,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` enum('virement','cheque','prelevement','especes','carte') DEFAULT NULL,
  `reference_paiement` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Disparadores `lignes_appel_fonds`
--
DELIMITER $$
CREATE TRIGGER `trg_update_appel_fonds_total` AFTER INSERT ON `lignes_appel_fonds` FOR EACH ROW BEGIN
    UPDATE appels_fonds 
    SET montant_total = (
        SELECT SUM(montant) 
        FROM lignes_appel_fonds 
        WHERE appel_fonds_id = NEW.appel_fonds_id
    )
    WHERE id = NEW.appel_fonds_id;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `locataires`
--

CREATE TABLE `locataires` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `civilite` enum('M','Mme','Mlle') NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `date_naissance` date DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `telephone_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `employeur` varchar(200) DEFAULT NULL,
  `revenus_mensuels` decimal(10,2) DEFAULT NULL,
  `garant_nom` varchar(200) DEFAULT NULL,
  `garant_telephone` varchar(20) DEFAULT NULL,
  `garant_adresse` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_name` varchar(50) DEFAULT NULL,
  `record_id` int(11) DEFAULT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `logs_activite`
--

INSERT INTO `logs_activite` (`id`, `user_id`, `action`, `table_name`, `record_id`, `details`, `ip_address`, `user_agent`, `created_at`) VALUES
(1, 7, 'user_status_changed', 'users', 7, 'Status changed from 1 to 0', NULL, NULL, '2025-12-10 23:53:07'),
(2, 7, 'user_status_changed', 'users', 7, 'Status changed from 0 to 1', NULL, NULL, '2025-12-10 23:53:11'),
(3, 12, 'user_status_changed', 'users', 12, 'Status changed from 1 to 0', NULL, NULL, '2025-12-11 14:30:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lots`
--

CREATE TABLE `lots` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `numero_lot` varchar(20) NOT NULL,
  `type` enum('appartement','parking','cave','commerce','bureau','local') NOT NULL DEFAULT 'appartement',
  `etage` int(11) DEFAULT NULL,
  `surface` decimal(10,2) DEFAULT NULL,
  `tantiemes_generaux` int(11) NOT NULL DEFAULT 0,
  `tantiemes_chauffage` int(11) DEFAULT 0,
  `tantiemes_ascenseur` int(11) DEFAULT 0,
  `nombre_pieces` int(11) DEFAULT NULL,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `lots`
--

INSERT INTO `lots` (`id`, `copropriete_id`, `numero_lot`, `type`, `etage`, `surface`, `tantiemes_generaux`, `tantiemes_chauffage`, `tantiemes_ascenseur`, `nombre_pieces`, `description`, `created_at`, `updated_at`) VALUES
(150, 61, 'A001', 'appartement', 0, 30.53, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(151, 61, 'A002', 'appartement', 0, 27.26, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(152, 61, 'A003', 'appartement', 0, 29.72, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(153, 61, 'A004', 'appartement', 0, 31.80, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(154, 61, 'A005', 'appartement', 0, 34.84, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(155, 61, 'A006', 'appartement', 0, 33.81, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(156, 61, 'A007', 'appartement', 0, 29.53, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(157, 61, 'A008', 'appartement', 0, 31.24, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(158, 61, 'A009', 'appartement', 0, 32.58, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(159, 61, 'A010', 'appartement', 1, 34.20, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(160, 61, 'A011', 'appartement', 1, 28.27, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(161, 61, 'A012', 'appartement', 1, 33.73, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(162, 61, 'A013', 'appartement', 1, 28.84, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(163, 61, 'A014', 'appartement', 1, 28.02, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(164, 61, 'A015', 'appartement', 1, 28.56, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(165, 61, 'A016', 'appartement', 1, 33.75, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(166, 61, 'A017', 'appartement', 1, 28.05, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(167, 61, 'A018', 'appartement', 1, 34.02, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(168, 61, 'A019', 'appartement', 2, 30.94, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(169, 61, 'A020', 'appartement', 2, 27.63, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(170, 61, 'A021', 'appartement', 2, 30.35, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(171, 61, 'A022', 'appartement', 2, 33.84, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(172, 61, 'A023', 'appartement', 2, 33.15, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(173, 61, 'A024', 'appartement', 2, 29.23, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(174, 61, 'A025', 'appartement', 2, 31.71, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(175, 61, 'A026', 'appartement', 2, 25.85, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(176, 61, 'A027', 'appartement', 2, 51.16, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(177, 61, 'A028', 'appartement', 3, 57.00, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(178, 61, 'A029', 'appartement', 3, 56.51, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(179, 61, 'A030', 'appartement', 3, 51.57, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(180, 61, 'A031', 'appartement', 3, 58.31, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(181, 61, 'A032', 'appartement', 3, 46.84, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(182, 61, 'A033', 'appartement', 3, 59.26, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(183, 61, 'A034', 'appartement', 3, 50.79, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(184, 61, 'A035', 'appartement', 3, 46.17, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(185, 61, 'A036', 'appartement', 3, 48.48, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(186, 61, 'A037', 'appartement', 4, 58.91, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(187, 61, 'A038', 'appartement', 4, 59.08, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(188, 61, 'A039', 'appartement', 4, 58.70, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(189, 61, 'A040', 'appartement', 4, 56.24, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(190, 61, 'A041', 'appartement', 4, 45.09, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(191, 61, 'A042', 'appartement', 4, 56.76, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(192, 61, 'A043', 'appartement', 4, 58.52, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(193, 61, 'A044', 'appartement', 4, 47.32, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(194, 61, 'A045', 'appartement', 4, 46.06, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(195, 61, 'A046', 'appartement', 5, 58.30, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(196, 61, 'A047', 'appartement', 5, 48.35, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(197, 61, 'A048', 'appartement', 5, 51.86, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(198, 61, 'A049', 'appartement', 5, 54.22, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(199, 61, 'A050', 'appartement', 5, 55.53, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(200, 61, 'A051', 'appartement', 5, 55.00, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(201, 61, 'A052', 'appartement', 5, 48.41, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(202, 61, 'A053', 'appartement', 5, 47.05, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(203, 61, 'A054', 'appartement', 5, 45.01, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(204, 61, 'A055', 'appartement', 6, 53.91, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(205, 61, 'A056', 'appartement', 6, 59.52, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(206, 61, 'A057', 'appartement', 6, 45.88, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(207, 61, 'A058', 'appartement', 6, 50.81, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(208, 61, 'A059', 'appartement', 6, 56.44, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(209, 61, 'A060', 'appartement', 6, 54.75, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(210, 61, 'A061', 'appartement', 6, 59.43, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(211, 61, 'A062', 'appartement', 6, 57.89, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(212, 61, 'A063', 'appartement', 6, 51.17, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(213, 61, 'A064', 'appartement', 7, 52.16, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(214, 61, 'A065', 'appartement', 7, 47.32, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(215, 61, 'A066', 'appartement', 7, 50.12, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(216, 61, 'A067', 'appartement', 7, 48.63, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(217, 61, 'A068', 'appartement', 7, 47.81, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(218, 61, 'A069', 'appartement', 7, 69.20, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(219, 61, 'A070', 'appartement', 7, 74.74, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(220, 61, 'A071', 'appartement', 7, 81.12, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(221, 61, 'A072', 'appartement', 7, 76.39, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(222, 61, 'A073', 'appartement', 8, 73.59, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(223, 61, 'A074', 'appartement', 8, 73.77, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(224, 61, 'A075', 'appartement', 8, 83.10, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(225, 61, 'A076', 'appartement', 8, 69.17, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(226, 61, 'A077', 'appartement', 8, 71.54, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(227, 61, 'A078', 'appartement', 8, 65.18, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(228, 61, 'A079', 'appartement', 8, 66.29, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(229, 61, 'A080', 'appartement', 8, 70.93, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(230, 61, 'A081', 'appartement', 8, 70.77, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(231, 61, 'A082', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(232, 61, 'A083', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(233, 61, 'A084', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(234, 61, 'A085', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(277, 62, 'B001', 'appartement', 0, 32.50, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(278, 62, 'B002', 'appartement', 0, 48.30, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(279, 62, 'B003', 'appartement', 1, 68.70, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(280, 62, 'B004', 'appartement', 1, 35.20, 110, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(281, 62, 'B005', 'appartement', 2, 52.80, 160, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(282, 62, 'P001', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(283, 62, 'P002', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(284, 63, 'L001', 'appartement', 0, 28.90, 95, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(285, 63, 'L002', 'appartement', 1, 46.50, 145, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(286, 63, 'L003', 'appartement', 2, 72.30, 210, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(287, 63, 'L004', 'appartement', 3, 38.40, 115, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(288, 63, 'P001', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(289, 64, 'N001', 'appartement', 0, 30.50, 100, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(290, 64, 'N002', 'appartement', 1, 50.20, 155, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(291, 64, 'N003', 'appartement', 2, 70.80, 205, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(292, 64, 'P001', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(293, 65, 'T001', 'appartement', 0, 33.20, 105, 0, 0, 1, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(294, 65, 'T002', 'appartement', 1, 48.90, 150, 0, 0, 2, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(295, 65, 'T003', 'appartement', 2, 68.50, 200, 0, 0, 3, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26'),
(296, 65, 'P001', 'parking', -1, 12.50, 50, 0, 0, NULL, NULL, '2025-11-30 02:19:26', '2025-11-30 02:19:26');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `messages`
--

CREATE TABLE `messages` (
  `id` int(11) NOT NULL,
  `expediteur_id` int(11) NOT NULL,
  `destinataire_id` int(11) NOT NULL,
  `copropriete_id` int(11) DEFAULT NULL,
  `sujet` varchar(255) NOT NULL,
  `contenu` text NOT NULL,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  `lu` tinyint(1) DEFAULT 0,
  `date_lecture` timestamp NULL DEFAULT NULL,
  `archive_expediteur` tinyint(1) DEFAULT 0,
  `archive_destinataire` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `type` enum('info','warning','error','success') NOT NULL DEFAULT 'info',
  `titre` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `lien` varchar(500) DEFAULT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_lecture` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `occupations_residents`
--

CREATE TABLE `occupations_residents` (
  `id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `resident_id` int(11) NOT NULL,
  `exploitant_id` int(11) NOT NULL,
  `date_entree` date NOT NULL,
  `date_sortie` date DEFAULT NULL,
  `type_sejour` enum('permanent','temporaire','essai','convalescence') DEFAULT 'permanent',
  `duree_prevue_mois` int(11) DEFAULT NULL,
  `loyer_mensuel_resident` decimal(10,2) NOT NULL,
  `charges_mensuelles_resident` decimal(10,2) DEFAULT 0.00,
  `services_inclus` text DEFAULT NULL,
  `services_supplementaires` text DEFAULT NULL,
  `montant_services_sup` decimal(10,2) DEFAULT 0.00,
  `forfait_type` enum('essentiel','serenite','confort','premium','personnalise') DEFAULT 'essentiel',
  `depot_garantie` decimal(10,2) DEFAULT 0.00,
  `date_versement_depot` date DEFAULT NULL,
  `depot_restitue` tinyint(1) DEFAULT 0,
  `date_restitution_depot` date DEFAULT NULL,
  `mode_paiement` enum('prelevement','virement','cheque','mandat_sepa') DEFAULT 'prelevement',
  `jour_prelevement` int(11) DEFAULT 5,
  `beneficie_apl` tinyint(1) DEFAULT 0,
  `montant_apl` decimal(10,2) DEFAULT NULL,
  `beneficie_apa` tinyint(1) DEFAULT 0,
  `montant_apa` decimal(10,2) DEFAULT NULL,
  `statut` enum('actif','preavis','termine','suspendu') DEFAULT 'actif',
  `contrat_sejour` varchar(255) DEFAULT NULL,
  `etat_lieux_entree` varchar(255) DEFAULT NULL,
  `etat_lieux_sortie` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `occupations_residents`
--

INSERT INTO `occupations_residents` (`id`, `lot_id`, `resident_id`, `exploitant_id`, `date_entree`, `date_sortie`, `type_sejour`, `duree_prevue_mois`, `loyer_mensuel_resident`, `charges_mensuelles_resident`, `services_inclus`, `services_supplementaires`, `montant_services_sup`, `forfait_type`, `depot_garantie`, `date_versement_depot`, `depot_restitue`, `date_restitution_depot`, `mode_paiement`, `jour_prelevement`, `beneficie_apl`, `montant_apl`, `beneficie_apa`, `montant_apa`, `statut`, `contrat_sejour`, `etat_lieux_entree`, `etat_lieux_sortie`, `notes`, `created_at`, `updated_at`) VALUES
(1, 150, 1, 1, '2024-05-15', NULL, 'permanent', NULL, 1450.00, 280.00, '{\"wifi\":true,\"telephone\":true,\"animations\":true,\"assistance_24h\":true,\"entretien_espaces_communs\":true}', NULL, 0.00, 'confort', 1450.00, '2024-05-01', 0, NULL, 'prelevement', 5, 1, 250.00, 0, NULL, 'actif', NULL, NULL, NULL, 'abcd', '2025-11-30 02:27:19', '2025-12-12 00:14:47'),
(2, 278, 2, 1, '2024-06-10', NULL, 'permanent', NULL, 1580.00, 300.00, '{\"wifi\": true, \"telephone\": true, \"animations\": true, \"assistance_24h\": true, \"entretien_espaces_communs\": true, \"restaurant_midi\": true}', NULL, 0.00, 'premium', 1580.00, '2024-05-25', 0, NULL, 'prelevement', 5, 0, 0.00, 0, NULL, 'actif', NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(3, 285, 3, 1, '2024-04-20', NULL, 'permanent', NULL, 1520.00, 290.00, '{\"wifi\": true, \"telephone\": true, \"animations\": true, \"assistance_24h\": true, \"entretien_espaces_communs\": true}', NULL, 0.00, 'serenite', 1520.00, '2024-04-10', 0, NULL, 'virement', 5, 0, NULL, 0, NULL, 'actif', NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19');

--
-- Disparadores `occupations_residents`
--
DELIMITER $$
CREATE TRIGGER `trg_validation_occupation` BEFORE INSERT ON `occupations_residents` FOR EACH ROW BEGIN
    DECLARE occupation_existante INT;
    
    SELECT COUNT(*) INTO occupation_existante
    FROM occupations_residents
    WHERE lot_id = NEW.lot_id 
      AND statut = 'actif'
      AND id != NEW.id;
    
    IF occupation_existante > 0 THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Ce lot est déjà occupé par un résident actif';
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `paiements_loyers_exploitant`
--

CREATE TABLE `paiements_loyers_exploitant` (
  `id` int(11) NOT NULL,
  `contrat_gestion_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `exploitant_id` int(11) NOT NULL,
  `annee` int(11) NOT NULL,
  `mois` int(11) NOT NULL CHECK (`mois` between 1 and 12),
  `date_echeance` date NOT NULL,
  `loyer_mensuel` decimal(10,2) NOT NULL,
  `charges` decimal(10,2) DEFAULT 0.00,
  `regularisation` decimal(10,2) DEFAULT 0.00,
  `montant_total` decimal(10,2) NOT NULL,
  `statut` enum('attente','paye','retard','impaye','litige') DEFAULT 'attente',
  `date_paiement` date DEFAULT NULL,
  `date_paiement_effectif` date DEFAULT NULL,
  `mode_paiement` enum('virement','prelevement','cheque','autre') DEFAULT 'virement',
  `reference_paiement` varchar(100) DEFAULT NULL,
  `jours_retard` int(11) DEFAULT 0,
  `penalite_retard` decimal(10,2) DEFAULT 0.00,
  `quittance` varchar(255) DEFAULT NULL,
  `facture` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `paiements_loyers_exploitant`
--

INSERT INTO `paiements_loyers_exploitant` (`id`, `contrat_gestion_id`, `coproprietaire_id`, `exploitant_id`, `annee`, `mois`, `date_echeance`, `loyer_mensuel`, `charges`, `regularisation`, `montant_total`, `statut`, `date_paiement`, `date_paiement_effectif`, `mode_paiement`, `reference_paiement`, `jours_retard`, `penalite_retard`, `quittance`, `facture`, `notes`, `created_at`, `updated_at`) VALUES
(1, 1, 6, 1, 2024, 5, '2024-05-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-05-05', '2024-05-05', 'virement', 'VIR-DOM-2024-05-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(2, 1, 6, 1, 2024, 6, '2024-06-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-06-05', '2024-06-05', 'virement', 'VIR-DOM-2024-06-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(3, 1, 6, 1, 2024, 7, '2024-07-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-07-05', '2024-07-05', 'virement', 'VIR-DOM-2024-07-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(4, 1, 6, 1, 2024, 8, '2024-08-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-08-05', '2024-08-05', 'virement', 'VIR-DOM-2024-08-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(5, 1, 6, 1, 2024, 9, '2024-09-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-09-05', '2024-09-05', 'virement', 'VIR-DOM-2024-09-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(6, 1, 6, 1, 2024, 10, '2024-10-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-10-05', '2024-10-05', 'virement', 'VIR-DOM-2024-10-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(7, 1, 6, 1, 2024, 11, '2024-11-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-11-05', '2024-11-05', 'virement', 'VIR-DOM-2024-11-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(8, 1, 6, 1, 2024, 12, '2024-12-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2024-12-05', '2024-12-05', 'virement', 'VIR-DOM-2024-12-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(16, 1, 6, 1, 2025, 1, '2025-01-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-01-05', '2025-01-05', 'virement', 'VIR-DOM-2025-01-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(17, 2, 7, 1, 2025, 1, '2025-01-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-01-05', '2025-01-05', 'virement', 'VIR-DOM-2025-01-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(18, 3, 8, 1, 2025, 1, '2025-01-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-01-05', '2025-01-05', 'virement', 'VIR-DOM-2025-01-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(19, 1, 6, 1, 2025, 2, '2025-02-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-02-05', '2025-02-05', 'virement', 'VIR-DOM-2025-02-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(20, 2, 7, 1, 2025, 2, '2025-02-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-02-05', '2025-02-05', 'virement', 'VIR-DOM-2025-02-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(21, 3, 8, 1, 2025, 2, '2025-02-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-02-05', '2025-02-05', 'virement', 'VIR-DOM-2025-02-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(22, 1, 6, 1, 2025, 3, '2025-03-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-03-05', '2025-03-05', 'virement', 'VIR-DOM-2025-03-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(23, 2, 7, 1, 2025, 3, '2025-03-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-03-05', '2025-03-05', 'virement', 'VIR-DOM-2025-03-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(24, 3, 8, 1, 2025, 3, '2025-03-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-03-05', '2025-03-05', 'virement', 'VIR-DOM-2025-03-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(25, 1, 6, 1, 2025, 4, '2025-04-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-04-05', '2025-04-05', 'virement', 'VIR-DOM-2025-04-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(26, 2, 7, 1, 2025, 4, '2025-04-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-04-05', '2025-04-05', 'virement', 'VIR-DOM-2025-04-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(27, 3, 8, 1, 2025, 4, '2025-04-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-04-05', '2025-04-05', 'virement', 'VIR-DOM-2025-04-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(28, 1, 6, 1, 2025, 5, '2025-05-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-05-05', '2025-05-05', 'virement', 'VIR-DOM-2025-05-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(29, 2, 7, 1, 2025, 5, '2025-05-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-05-05', '2025-05-05', 'virement', 'VIR-DOM-2025-05-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(30, 3, 8, 1, 2025, 5, '2025-05-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-05-05', '2025-05-05', 'virement', 'VIR-DOM-2025-05-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(31, 1, 6, 1, 2025, 6, '2025-06-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-06-05', '2025-06-05', 'virement', 'VIR-DOM-2025-06-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(32, 2, 7, 1, 2025, 6, '2025-06-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-06-05', '2025-06-05', 'virement', 'VIR-DOM-2025-06-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(33, 3, 8, 1, 2025, 6, '2025-06-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-06-05', '2025-06-05', 'virement', 'VIR-DOM-2025-06-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(34, 1, 6, 1, 2025, 7, '2025-07-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-07-05', '2025-07-05', 'virement', 'VIR-DOM-2025-07-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(35, 2, 7, 1, 2025, 7, '2025-07-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-07-05', '2025-07-05', 'virement', 'VIR-DOM-2025-07-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(36, 3, 8, 1, 2025, 7, '2025-07-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-07-05', '2025-07-05', 'virement', 'VIR-DOM-2025-07-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(37, 1, 6, 1, 2025, 8, '2025-08-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-08-05', '2025-08-05', 'virement', 'VIR-DOM-2025-08-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(38, 2, 7, 1, 2025, 8, '2025-08-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-08-05', '2025-08-05', 'virement', 'VIR-DOM-2025-08-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(39, 3, 8, 1, 2025, 8, '2025-08-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-08-05', '2025-08-05', 'virement', 'VIR-DOM-2025-08-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(40, 1, 6, 1, 2025, 9, '2025-09-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-09-05', '2025-09-05', 'virement', 'VIR-DOM-2025-09-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(41, 2, 7, 1, 2025, 9, '2025-09-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-09-05', '2025-09-05', 'virement', 'VIR-DOM-2025-09-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(42, 3, 8, 1, 2025, 9, '2025-09-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-09-05', '2025-09-05', 'virement', 'VIR-DOM-2025-09-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(43, 1, 6, 1, 2025, 10, '2025-10-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-10-05', '2025-10-05', 'virement', 'VIR-DOM-2025-10-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(44, 2, 7, 1, 2025, 10, '2025-10-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-10-05', '2025-10-05', 'virement', 'VIR-DOM-2025-10-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(45, 3, 8, 1, 2025, 10, '2025-10-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-10-05', '2025-10-05', 'virement', 'VIR-DOM-2025-10-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(46, 1, 6, 1, 2025, 11, '2025-11-05', 850.00, 120.00, 0.00, 970.00, 'paye', '2025-11-05', '2025-11-05', 'virement', 'VIR-DOM-2025-11-001', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(47, 2, 7, 1, 2025, 11, '2025-11-05', 920.00, 130.00, 0.00, 1050.00, 'paye', '2025-11-05', '2025-11-05', 'virement', 'VIR-DOM-2025-11-002', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(48, 3, 8, 1, 2025, 11, '2025-11-05', 880.00, 125.00, 0.00, 1005.00, 'paye', '2025-11-05', '2025-11-05', 'virement', 'VIR-DOM-2025-11-003', 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(49, 1, 6, 1, 2025, 12, '2025-12-05', 850.00, 120.00, 0.00, 970.00, 'attente', NULL, NULL, 'virement', NULL, 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(50, 2, 7, 1, 2025, 12, '2025-12-05', 920.00, 130.00, 0.00, 1050.00, 'attente', NULL, NULL, 'virement', NULL, 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(51, 3, 8, 1, 2025, 12, '2025-12-05', 880.00, 125.00, 0.00, 1005.00, 'attente', NULL, NULL, 'virement', NULL, 0, 0.00, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19');

--
-- Disparadores `paiements_loyers_exploitant`
--
DELIMITER $$
CREATE TRIGGER `trg_calcul_jours_retard` BEFORE UPDATE ON `paiements_loyers_exploitant` FOR EACH ROW BEGIN
    IF NEW.statut = 'paye' AND NEW.date_paiement_effectif IS NOT NULL THEN
        SET NEW.jours_retard = GREATEST(0, DATEDIFF(NEW.date_paiement_effectif, NEW.date_echeance));
    END IF;
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_calcul_montant_paiement` BEFORE INSERT ON `paiements_loyers_exploitant` FOR EACH ROW BEGIN
    SET NEW.montant_total = NEW.loyer_mensuel + NEW.charges + NEW.regularisation - NEW.penalite_retard;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle` varchar(100) NOT NULL,
  `valeur` text DEFAULT NULL,
  `description` text DEFAULT NULL,
  `type` enum('string','number','boolean','json','date') NOT NULL DEFAULT 'string',
  `categorie` varchar(50) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `parametres`
--

INSERT INTO `parametres` (`id`, `cle`, `valeur`, `description`, `type`, `categorie`, `updated_at`) VALUES
(1, 'nom_syndic', 'Synd_Gest', 'Nom du syndic', 'string', 'general', '2025-11-28 23:39:25'),
(2, 'email_contact', 'contact@syndgest.fr', 'Email de contact', 'string', 'general', '2025-11-28 23:39:25'),
(3, 'telephone_contact', '01 23 45 67 89', 'Téléphone de contact', 'string', 'general', '2025-11-28 23:39:25'),
(4, 'tva_defaut', '20', 'Taux de TVA par défaut', 'number', 'comptabilite', '2025-11-28 23:39:25'),
(5, 'delai_relance', '15', 'Délai en jours avant relance', 'number', 'paiement', '2025-11-28 23:39:25'),
(6, 'appels_fonds_auto', 'true', 'Génération automatique des appels de fonds', 'boolean', 'comptabilite', '2025-11-28 23:39:25'),
(7, 'mode_maintenance', 'false', 'Mode maintenance activé', 'boolean', 'systeme', '2025-11-28 23:39:25'),
(8, 'max_upload_size', '10', 'Taille max upload en Mo', 'number', 'systeme', '2025-11-28 23:39:25'),
(9, 'date_format', 'd/m/Y', 'Format de date', 'string', 'general', '2025-11-28 23:39:25'),
(10, 'devise', 'EUR', 'Devise', 'string', 'comptabilite', '2025-11-28 23:39:25'),
(11, 'user_1_prefs', '{\"language\":\"en\",\"timezone\":\"Europe\\/Paris\",\"date_format\":\"dd\\/mm\\/yyyy\",\"time_format\":\"12h\",\"theme\":\"light\",\"density\":\"comfortable\",\"show_animations\":1}', 'Préférences utilisateur', 'json', NULL, '2025-11-30 00:47:23'),
(12, 'user_9_prefs', '{\"theme\":\"light\",\"density\":\"comfortable\",\"show_animations\":0}', 'Préférences utilisateur', 'json', NULL, '2025-11-30 21:28:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `permissions`
--

CREATE TABLE `permissions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','gestionnaire','exploitant','proprietaire','resident') NOT NULL,
  `module` varchar(50) NOT NULL,
  `action` enum('create','read','update','delete','all') NOT NULL,
  `allowed` tinyint(1) DEFAULT 1,
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `permissions`
--

INSERT INTO `permissions` (`id`, `role`, `module`, `action`, `allowed`, `description`, `created_at`) VALUES
(94, 'admin', 'users', 'all', 1, 'Gestion complète des utilisateurs', '2025-11-30 02:22:58'),
(95, 'admin', 'coproprietees', 'all', 1, 'Gestion complète des copropriétés', '2025-11-30 02:22:58'),
(96, 'admin', 'lots', 'all', 1, 'Gestion complète des lots', '2025-11-30 02:22:58'),
(97, 'admin', 'exploitants', 'all', 1, 'Gestion complète des exploitants', '2025-11-30 02:22:58'),
(98, 'admin', 'contrats_gestion', 'all', 1, 'Gestion complète des contrats', '2025-11-30 02:22:58'),
(99, 'admin', 'residents', 'all', 1, 'Gestion complète des résidents', '2025-11-30 02:22:58'),
(100, 'admin', 'occupations', 'all', 1, 'Gestion complète des occupations', '2025-11-30 02:22:58'),
(101, 'admin', 'paiements', 'all', 1, 'Gestion complète des paiements', '2025-11-30 02:22:58'),
(102, 'admin', 'comptabilite', 'all', 1, 'Gestion complète de la comptabilité', '2025-11-30 02:22:58'),
(103, 'admin', 'travaux', 'all', 1, 'Gestion complète des travaux', '2025-11-30 02:22:58'),
(104, 'admin', 'fournisseurs', 'all', 1, 'Gestion complète des fournisseurs', '2025-11-30 02:22:58'),
(105, 'admin', 'documents', 'all', 1, 'Gestion complète des documents', '2025-11-30 02:22:58'),
(106, 'admin', 'parametres', 'all', 1, 'Accès aux paramètres système', '2025-11-30 02:22:58'),
(107, 'admin', 'logs', 'read', 1, 'Lecture des logs système', '2025-11-30 02:22:58'),
(108, 'gestionnaire', 'coproprietees', 'all', 1, 'Gestion des copropriétés', '2025-11-30 02:22:58'),
(109, 'gestionnaire', 'lots', 'all', 1, 'Gestion des lots', '2025-11-30 02:22:58'),
(110, 'gestionnaire', 'coproprietaires', 'all', 1, 'Gestion des copropriétaires', '2025-11-30 02:22:58'),
(111, 'gestionnaire', 'comptabilite', 'all', 1, 'Gestion comptable', '2025-11-30 02:22:58'),
(112, 'gestionnaire', 'appels_fonds', 'all', 1, 'Gestion des appels de fonds', '2025-11-30 02:22:58'),
(113, 'gestionnaire', 'travaux', 'all', 1, 'Gestion des travaux', '2025-11-30 02:22:58'),
(114, 'gestionnaire', 'fournisseurs', 'all', 1, 'Gestion des fournisseurs', '2025-11-30 02:22:58'),
(115, 'gestionnaire', 'assemblees', 'all', 1, 'Gestion des assemblées générales', '2025-11-30 02:22:58'),
(116, 'gestionnaire', 'documents', 'all', 1, 'Gestion documentaire', '2025-11-30 02:22:58'),
(117, 'gestionnaire', 'exploitants', 'read', 1, 'Consultation des exploitants', '2025-11-30 02:22:58'),
(118, 'gestionnaire', 'contrats_gestion', 'read', 1, 'Consultation des contrats', '2025-11-30 02:22:58'),
(119, 'gestionnaire', 'residents', 'read', 1, 'Consultation des résidents', '2025-11-30 02:22:58'),
(120, 'gestionnaire', 'occupations', 'read', 1, 'Consultation des occupations', '2025-11-30 02:22:58'),
(121, 'exploitant', 'residents', 'all', 1, 'Gestion complète des résidents seniors', '2025-11-30 02:22:58'),
(122, 'exploitant', 'occupations', 'all', 1, 'Gestion des occupations', '2025-11-30 02:22:58'),
(123, 'exploitant', 'contrats_gestion', 'read', 1, 'Consultation des contrats avec propriétaires', '2025-11-30 02:22:58'),
(124, 'exploitant', 'contrats_gestion', 'update', 1, 'Mise à jour statuts contrats', '2025-11-30 02:22:58'),
(125, 'exploitant', 'paiements_exploitant', 'all', 1, 'Gestion paiements vers propriétaires', '2025-11-30 02:22:58'),
(126, 'exploitant', 'factures_residents', 'all', 1, 'Facturation aux résidents', '2025-11-30 02:22:58'),
(127, 'exploitant', 'encaissements', 'all', 1, 'Encaissements résidents', '2025-11-30 02:22:58'),
(128, 'exploitant', 'coproprietees', 'read', 1, 'Consultation des résidences', '2025-11-30 02:22:58'),
(129, 'exploitant', 'lots', 'read', 1, 'Consultation des lots', '2025-11-30 02:22:58'),
(130, 'exploitant', 'lots', 'update', 1, 'Mise à jour état des lots', '2025-11-30 02:22:58'),
(131, 'exploitant', 'animations', 'all', 1, 'Gestion des animations', '2025-11-30 02:22:58'),
(132, 'exploitant', 'services', 'all', 1, 'Gestion des services', '2025-11-30 02:22:58'),
(133, 'exploitant', 'planning', 'all', 1, 'Gestion du planning', '2025-11-30 02:22:58'),
(134, 'exploitant', 'messages', 'all', 1, 'Messagerie avec résidents et propriétaires', '2025-11-30 02:22:58'),
(135, 'exploitant', 'notifications', 'all', 1, 'Envoi de notifications', '2025-11-30 02:22:58'),
(136, 'exploitant', 'documents', 'create', 1, 'Upload de documents', '2025-11-30 02:22:58'),
(137, 'exploitant', 'documents', 'read', 1, 'Consultation des documents', '2025-11-30 02:22:58'),
(138, 'exploitant', 'documents', 'update', 1, 'Modification de documents', '2025-11-30 02:22:58'),
(139, 'exploitant', 'statistiques', 'read', 1, 'Consultation statistiques occupation', '2025-11-30 02:22:58'),
(140, 'exploitant', 'reporting', 'read', 1, 'Rapports d\'activité', '2025-11-30 02:22:58'),
(141, 'proprietaire', 'lots', 'read', 1, 'Consultation de ses lots uniquement', '2025-11-30 02:22:58'),
(142, 'proprietaire', 'coproprietees', 'read', 1, 'Consultation de ses résidences', '2025-11-30 02:22:58'),
(143, 'proprietaire', 'contrats_gestion', 'read', 1, 'Consultation de ses contrats', '2025-11-30 02:22:58'),
(144, 'proprietaire', 'paiements_exploitant', 'read', 1, 'Consultation de ses loyers reçus', '2025-11-30 02:22:58'),
(145, 'proprietaire', 'revenus_fiscaux', 'read', 1, 'Consultation déclarations fiscales', '2025-11-30 02:22:58'),
(146, 'proprietaire', 'revenus_fiscaux', 'update', 1, 'Mise à jour infos fiscales', '2025-11-30 02:22:58'),
(147, 'proprietaire', 'documents', 'read', 1, 'Consultation de ses documents', '2025-11-30 02:22:58'),
(148, 'proprietaire', 'documents', 'create', 1, 'Upload de documents personnels', '2025-11-30 02:22:58'),
(149, 'proprietaire', 'occupations', 'read', 1, 'Consultation occupation de ses lots', '2025-11-30 02:22:58'),
(150, 'proprietaire', 'residents', 'read', 1, 'Infos basiques des résidents (nom, âge)', '2025-11-30 02:22:58'),
(151, 'proprietaire', 'messages', 'read', 1, 'Réception de messages', '2025-11-30 02:22:58'),
(152, 'proprietaire', 'messages', 'create', 1, 'Envoi messages à Domitys', '2025-11-30 02:22:58'),
(153, 'proprietaire', 'profil', 'update', 1, 'Mise à jour de son profil', '2025-11-30 02:22:58'),
(154, 'proprietaire', 'coordonnees_bancaires', 'update', 1, 'Mise à jour IBAN', '2025-11-30 02:22:58'),
(155, 'resident', 'profil', 'read', 1, 'Consultation de son profil', '2025-11-30 02:22:58'),
(156, 'resident', 'profil', 'update', 1, 'Mise à jour infos personnelles', '2025-11-30 02:22:58'),
(157, 'resident', 'factures', 'read', 1, 'Consultation de ses factures Domitys', '2025-11-30 02:22:58'),
(158, 'resident', 'paiements', 'read', 1, 'Historique de ses paiements', '2025-11-30 02:22:58'),
(159, 'resident', 'quittances', 'read', 1, 'Téléchargement de ses quittances', '2025-11-30 02:22:58'),
(160, 'resident', 'lot', 'read', 1, 'Infos sur son appartement', '2025-11-30 02:22:58'),
(161, 'resident', 'contrat_sejour', 'read', 1, 'Consultation contrat de séjour', '2025-11-30 02:22:58'),
(162, 'resident', 'animations', 'read', 1, 'Consultation programme animations', '2025-11-30 02:22:58'),
(163, 'resident', 'animations', 'create', 1, 'Inscription aux animations', '2025-11-30 02:22:58'),
(164, 'resident', 'services', 'read', 1, 'Consultation services disponibles', '2025-11-30 02:22:58'),
(165, 'resident', 'services', 'create', 1, 'Demande de services', '2025-11-30 02:22:58'),
(166, 'resident', 'restaurant', 'read', 1, 'Consultation menus restaurant', '2025-11-30 02:22:58'),
(167, 'resident', 'restaurant', 'create', 1, 'Réservation repas', '2025-11-30 02:22:58'),
(168, 'resident', 'messages', 'read', 1, 'Réception de messages', '2025-11-30 02:22:58'),
(169, 'resident', 'messages', 'create', 1, 'Envoi messages à Domitys', '2025-11-30 02:22:58'),
(170, 'resident', 'actualites', 'read', 1, 'Consultation actualités résidence', '2025-11-30 02:22:58'),
(171, 'resident', 'documents', 'read', 1, 'Consultation documents personnels', '2025-11-30 02:22:58'),
(172, 'resident', 'reglement', 'read', 1, 'Consultation règlement intérieur', '2025-11-30 02:22:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `possessions`
--

CREATE TABLE `possessions` (
  `id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `date_acquisition` date NOT NULL,
  `date_cession` date DEFAULT NULL,
  `pourcentage_possession` decimal(5,2) DEFAULT 100.00,
  `mandat_gestion` tinyint(1) DEFAULT 0,
  `actif` tinyint(1) DEFAULT 1,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `quittances`
--

CREATE TABLE `quittances` (
  `id` int(11) NOT NULL,
  `bail_id` int(11) NOT NULL,
  `mois` int(11) NOT NULL CHECK (`mois` between 1 and 12),
  `annee` int(11) NOT NULL,
  `montant_loyer` decimal(10,2) NOT NULL,
  `montant_charges` decimal(10,2) DEFAULT 0.00,
  `montant_total` decimal(10,2) NOT NULL,
  `date_paiement` date DEFAULT NULL,
  `statut` enum('attente','partiel','paye','impaye') NOT NULL DEFAULT 'attente',
  `document_pdf` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `relances`
--

CREATE TABLE `relances` (
  `id` int(11) NOT NULL,
  `type` enum('appel_fonds','quittance') NOT NULL,
  `reference_id` int(11) NOT NULL,
  `coproprietaire_id` int(11) DEFAULT NULL,
  `locataire_id` int(11) DEFAULT NULL,
  `niveau` int(11) NOT NULL DEFAULT 1,
  `date_relance` date NOT NULL,
  `montant_du` decimal(10,2) NOT NULL,
  `moyen` enum('email','courrier','telephone','sms') NOT NULL,
  `statut` enum('envoyee','en_attente','reglee') NOT NULL DEFAULT 'envoyee',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `residents_seniors`
--

CREATE TABLE `residents_seniors` (
  `id` int(11) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `civilite` enum('M','Mme','Mlle') NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `nom_naissance` varchar(100) DEFAULT NULL,
  `date_naissance` date NOT NULL,
  `age` int(11) DEFAULT NULL,
  `lieu_naissance` varchar(100) DEFAULT NULL,
  `telephone_fixe` varchar(20) DEFAULT NULL,
  `telephone_mobile` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `numero_cni` varchar(20) DEFAULT NULL COMMENT 'Numéro de carte nationale d''identité',
  `date_delivrance_cni` date DEFAULT NULL,
  `lieu_delivrance_cni` varchar(100) DEFAULT NULL,
  `urgence_nom` varchar(200) DEFAULT NULL,
  `urgence_lien` varchar(100) DEFAULT NULL,
  `urgence_telephone` varchar(20) DEFAULT NULL,
  `urgence_telephone_2` varchar(20) DEFAULT NULL,
  `urgence_email` varchar(100) DEFAULT NULL,
  `situation_familiale` enum('celibataire','marie','veuf','divorce','pacse','concubinage') DEFAULT 'celibataire',
  `nombre_enfants` int(11) DEFAULT 0,
  `niveau_autonomie` enum('autonome','semi_autonome','dependant','gir1','gir2','gir3','gir4','gir5','gir6') DEFAULT 'autonome',
  `besoin_assistance` tinyint(1) DEFAULT 0,
  `allergies` text DEFAULT NULL,
  `regime_alimentaire` enum('normal','sans_sel','diabetique','vegetarien','sans_gluten','autre') DEFAULT NULL,
  `medecin_traitant_nom` varchar(200) DEFAULT NULL,
  `medecin_traitant_tel` varchar(20) DEFAULT NULL,
  `num_securite_sociale` varchar(15) DEFAULT NULL,
  `mutuelle` varchar(200) DEFAULT NULL,
  `num_mutuelle` varchar(50) DEFAULT NULL,
  `animal_compagnie` tinyint(1) DEFAULT 0,
  `animal_type` varchar(100) DEFAULT NULL,
  `animal_nom` varchar(100) DEFAULT NULL,
  `centres_interet` text DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_entree` date DEFAULT NULL,
  `date_sortie` date DEFAULT NULL,
  `motif_sortie` enum('demenagement','deces','maison_retraite','famille','autre') DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `photo` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `residents_seniors`
--

INSERT INTO `residents_seniors` (`id`, `user_id`, `civilite`, `nom`, `prenom`, `nom_naissance`, `date_naissance`, `age`, `lieu_naissance`, `telephone_fixe`, `telephone_mobile`, `email`, `numero_cni`, `date_delivrance_cni`, `lieu_delivrance_cni`, `urgence_nom`, `urgence_lien`, `urgence_telephone`, `urgence_telephone_2`, `urgence_email`, `situation_familiale`, `nombre_enfants`, `niveau_autonomie`, `besoin_assistance`, `allergies`, `regime_alimentaire`, `medecin_traitant_nom`, `medecin_traitant_tel`, `num_securite_sociale`, `mutuelle`, `num_mutuelle`, `animal_compagnie`, `animal_type`, `animal_nom`, `centres_interet`, `actif`, `date_entree`, `date_sortie`, `motif_sortie`, `notes`, `photo`, `created_at`, `updated_at`) VALUES
(1, NULL, 'Mme', 'DUPONT', 'Jeanne', NULL, '1938-04-12', 87, 'Marseille', '04 91 45 67 89', '06 78 90 12 34', 'jeanne.dupont@gmail.com', NULL, NULL, NULL, 'Marie DUPONT-SIMON', 'Fille', '06 45 78 90 12', NULL, 'marie.simon@orange.fr', 'veuf', 2, 'autonome', 0, NULL, 'sans_sel', 'Dr. MARTINEZ', '04 91 33 44 55', '2380446012345', 'MGEN', 'MG123456789', 1, 'Chat', 'Minou', '{\"jardinage\": true, \"lecture\": true, \"bridge\": true, \"gymnastique\": true}', 1, '2024-05-15', NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(2, NULL, 'M', 'BLANC', 'Robert', NULL, '1940-08-25', 85, 'Bordeaux', '05 56 78 90 12', '06 89 01 23 45', 'robert.blanc@free.fr', NULL, NULL, NULL, 'Paul BLANC', 'Fils', '06 56 78 90 12', NULL, NULL, 'marie', 3, 'autonome', 0, NULL, 'diabetique', 'Dr. DURAND', '05 56 44 55 66', '1400867123456', 'Harmonie Mutuelle', NULL, 0, NULL, NULL, '{\"petanque\": true, \"echecs\": true, \"promenade\": true}', 1, '2024-06-10', NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19'),
(3, NULL, 'Mme', 'PETIT', 'Françoise', NULL, '1942-11-30', 83, 'Lyon', NULL, '06 77 88 99 00', 'francoise.petit@gmail.com', NULL, NULL, NULL, 'Sophie PETIT-MARTIN', 'Fille', '06 34 56 78 90', NULL, 'sophie.martin@wanadoo.fr', 'celibataire', 0, 'autonome', 0, NULL, 'normal', 'Dr. LAMBERT', '04 78 66 77 88', NULL, 'AG2R La Mondiale', NULL, 0, NULL, NULL, '{\"peinture\": true, \"yoga\": true, \"chorale\": true, \"cinema\": true}', 1, '2024-04-20', NULL, NULL, NULL, NULL, '2025-11-30 02:27:19', '2025-11-30 02:27:19');

--
-- Disparadores `residents_seniors`
--
DELIMITER $$
CREATE TRIGGER `trg_calcul_age_resident_insert` BEFORE INSERT ON `residents_seniors` FOR EACH ROW BEGIN
    SET NEW.age = YEAR(CURDATE()) - YEAR(NEW.date_naissance);
END
$$
DELIMITER ;
DELIMITER $$
CREATE TRIGGER `trg_calcul_age_resident_update` BEFORE UPDATE ON `residents_seniors` FOR EACH ROW BEGIN
    SET NEW.age = YEAR(CURDATE()) - YEAR(NEW.date_naissance);
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `revenus_fiscaux_proprietaires`
--

CREATE TABLE `revenus_fiscaux_proprietaires` (
  `id` int(11) NOT NULL,
  `coproprietaire_id` int(11) NOT NULL,
  `lot_id` int(11) NOT NULL,
  `contrat_gestion_id` int(11) NOT NULL,
  `annee_fiscale` int(11) NOT NULL,
  `revenus_bruts` decimal(10,2) NOT NULL,
  `charges_deductibles` decimal(10,2) DEFAULT 0.00,
  `interets_emprunt` decimal(10,2) DEFAULT 0.00,
  `travaux_deductibles` decimal(10,2) DEFAULT 0.00,
  `assurances_deductibles` decimal(10,2) DEFAULT 0.00,
  `taxe_fonciere_deductible` decimal(10,2) DEFAULT 0.00,
  `autres_charges_deductibles` decimal(10,2) DEFAULT 0.00,
  `revenus_nets` decimal(10,2) NOT NULL,
  `amortissement` decimal(10,2) DEFAULT 0.00,
  `regime_fiscal` enum('micro_bic','reel_simplifie','reel_normal') DEFAULT 'reel_simplifie',
  `statut_fiscal` enum('LMNP','LMP','location_nue') DEFAULT 'LMNP',
  `reduction_censi_bouvard` decimal(10,2) DEFAULT 0.00,
  `recuperation_tva` decimal(10,2) DEFAULT 0.00,
  `credit_impot` decimal(10,2) DEFAULT 0.00,
  `resultat_fiscal` decimal(10,2) NOT NULL,
  `impot_estime` decimal(10,2) DEFAULT NULL,
  `declaration_2044` varchar(255) DEFAULT NULL,
  `declaration_2042` varchar(255) DEFAULT NULL,
  `justificatifs` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `revenus_fiscaux_proprietaires`
--

INSERT INTO `revenus_fiscaux_proprietaires` (`id`, `coproprietaire_id`, `lot_id`, `contrat_gestion_id`, `annee_fiscale`, `revenus_bruts`, `charges_deductibles`, `interets_emprunt`, `travaux_deductibles`, `assurances_deductibles`, `taxe_fonciere_deductible`, `autres_charges_deductibles`, `revenus_nets`, `amortissement`, `regime_fiscal`, `statut_fiscal`, `reduction_censi_bouvard`, `recuperation_tva`, `credit_impot`, `resultat_fiscal`, `impot_estime`, `declaration_2044`, `declaration_2042`, `justificatifs`, `notes`, `created_at`) VALUES
(1, 6, 150, 1, 2024, 6800.00, 1200.00, 1800.00, 500.00, 350.00, 850.00, 0.00, 2100.00, 3500.00, 'reel_simplifie', 'LMNP', 18000.00, 5000.00, 0.00, -1400.00, 0.00, NULL, NULL, NULL, NULL, '2025-11-30 02:27:19');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `role_descriptions`
--

CREATE TABLE `role_descriptions` (
  `id` int(11) NOT NULL,
  `role` enum('admin','gestionnaire','exploitant','proprietaire','resident') NOT NULL,
  `nom_affichage` varchar(100) NOT NULL,
  `description` text NOT NULL,
  `couleur` varchar(20) DEFAULT '#6c757d',
  `icone` varchar(50) DEFAULT 'fa-user',
  `ordre_affichage` int(11) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `role_descriptions`
--

INSERT INTO `role_descriptions` (`id`, `role`, `nom_affichage`, `description`, `couleur`, `icone`, `ordre_affichage`, `created_at`) VALUES
(6, 'admin', 'Administrateur', 'Super utilisateur avec accès complet à tous les modules et paramètres du système. Gestion des utilisateurs, configuration globale, logs système.', '#dc3545', 'fa-user-shield', 1, '2025-11-30 02:22:58'),
(7, 'gestionnaire', 'Gestionnaire Syndic', 'Gestion classique de syndic de copropriété : comptabilité, appels de fonds, travaux, assemblées générales, fournisseurs.', '#0d6efd', 'fa-briefcase', 2, '2025-11-30 02:22:58'),
(8, 'exploitant', 'Exploitant Domitys', 'Gestion des résidents seniors, occupations, services, animations, paiements aux propriétaires. Vue complète sur l\'activité de la résidence.', '#198754', 'fa-building', 3, '2025-11-30 02:22:58'),
(9, 'proprietaire', 'Propriétaire Investisseur', 'Consultation de ses biens, contrats de gestion, loyers perçus, déclarations fiscales. Communication avec Domitys.', '#fd7e14', 'fa-home', 4, '2025-11-30 02:22:58'),
(10, 'resident', 'Résident Senior', 'Accès à son espace personnel : factures, animations, services, messages, informations pratiques sur la résidence.', '#6610f2', 'fa-user-circle', 5, '2025-11-30 02:22:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sinistres`
--

CREATE TABLE `sinistres` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `lot_id` int(11) DEFAULT NULL,
  `date_sinistre` datetime NOT NULL,
  `type` enum('degat_eaux','incendie','vol','vandalisme','naturel','autre') NOT NULL,
  `description` text NOT NULL,
  `montant_estime` decimal(10,2) DEFAULT NULL,
  `montant_indemnise` decimal(10,2) DEFAULT NULL,
  `assurance` varchar(200) DEFAULT NULL,
  `numero_dossier_assurance` varchar(100) DEFAULT NULL,
  `statut` enum('declare','expertise','indemnisation','travaux','clos','rejete') NOT NULL DEFAULT 'declare',
  `date_declaration` date DEFAULT NULL,
  `date_expertise` date DEFAULT NULL,
  `expert_nom` varchar(200) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `travaux`
--

CREATE TABLE `travaux` (
  `id` int(11) NOT NULL,
  `copropriete_id` int(11) NOT NULL,
  `titre` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('entretien','reparation','amelioration','urgent') NOT NULL DEFAULT 'entretien',
  `urgence` enum('basse','moyenne','haute','critique') NOT NULL DEFAULT 'moyenne',
  `budget_estime` decimal(10,2) DEFAULT NULL,
  `budget_vote` decimal(10,2) DEFAULT NULL,
  `cout_final` decimal(10,2) DEFAULT NULL,
  `date_vote_ag` date DEFAULT NULL,
  `statut` enum('demande','devis','vote','approuve','planifie','encours','termine','annule') NOT NULL DEFAULT 'demande',
  `date_debut` date DEFAULT NULL,
  `date_fin_prevue` date DEFAULT NULL,
  `date_fin_reelle` date DEFAULT NULL,
  `localisation` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role` enum('admin','gestionnaire','exploitant','proprietaire','resident') NOT NULL DEFAULT 'proprietaire',
  `prenom` varchar(100) DEFAULT NULL,
  `nom` varchar(100) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `last_login` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password_hash`, `role`, `prenom`, `nom`, `telephone`, `actif`, `created_at`, `updated_at`, `last_login`) VALUES
(1, 'admin', 'admin@syndgest.fr', '$2y$10$skKyIRaySFr3OVxpZ5SaK.jMitVa5DM/ZMp5Ljnv.E2rXLaA9GO9.', 'admin', 'Superin', 'Admin', '', 1, '2025-11-28 23:39:25', '2025-12-12 00:02:34', '2025-12-12 00:02:34'),
(2, 'user1', 'user1@syndgest.fr', '$2y$10$OR80bDqpBGT5FZ88zPUOEO2tO8m.zyawRdinjBBlramZaFVwIsBom', 'gestionnaire', 'Jean', 'Dupont', '01 23 45 67 01', 1, '2025-11-30 01:40:06', '2025-11-30 01:40:06', NULL),
(3, 'user2', 'user2@syndgest.fr', '$2y$10$ylFvZOHpJqRZpTH.KShAkuh36HeqtPL6CKnqc.6woC5Tll0ZPcUZm', 'gestionnaire', 'Marie', 'Martin', '01 23 45 67 02', 1, '2025-11-30 01:40:06', '2025-11-30 01:40:06', NULL),
(4, 'user3', 'user3@syndgest.fr', '$2y$10$B8s/JivkyoN.9L.glo.x0.MKXN/fiqBvCpVcEJLEDZyr4fqhYjd/u', 'gestionnaire', 'Pierre', 'Lefebvre', '01 23 45 67 03', 1, '2025-11-30 01:40:06', '2025-11-30 01:40:06', NULL),
(5, 'domitys', 'exploitant@domitys.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'exploitant', 'Gestionnaire', 'Domitys', '01 41 18 29 29', 1, '2025-11-30 02:19:59', '2025-11-30 02:19:59', NULL),
(7, 'gestionnaire1', 'gestionnaire1@syndgest.fr', '$2y$10$zh5lLpjuFgXdQ39QmWeS.ukBC/sAOd7A81s4Sm3FuKAV.hWd8orCK', 'gestionnaire', 'Marc', 'DURAND', '01 42 67 89 01', 1, '2025-11-30 02:23:39', '2025-12-10 23:53:11', '2025-11-30 21:28:59'),
(8, 'gestionnaire2', 'gestionnaire2@syndgest.fr', '$2y$10$G/v.zgnK3e/XyFYkNCIA3uEFjoFlAbnXNahkkZgJi5erRIL.yj9Iy', 'gestionnaire', 'Sophie', 'LAMBERT', '01 42 67 89 02', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(9, 'exploitant1', 'exploitant1@domitys.fr', '$2y$10$hyNEnh4Fr.j.kMVJUCWesuOIL46m1tXl1wMmpjYQ4fNe7ICIrDXGi', 'exploitant', 'Caroline', 'MOREAU', '01 41 18 29 01', 1, '2025-11-30 02:23:39', '2025-11-30 22:45:02', '2025-11-30 22:45:02'),
(10, 'exploitant2', 'exploitant2@domitys.fr', '$2y$10$WR.j7mLfwHMqSjubOX2o0urwMpGr1JQtazS9X2PVOaEzFqVh2nKGm', 'exploitant', 'Thomas', 'ROBERT', '01 41 18 29 02', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(11, 'proprietaire1', 'proprietaire1@syndgest.fr', '$2y$10$G5ZPVMpQXH8dNVKH8jpp5ORbxcyKUlIalFXn32Scm/OP58OKQbkP2', 'proprietaire', 'Alexandre', 'PETIT', '06 12 34 56 01', 1, '2025-11-30 02:23:39', '2025-11-30 22:53:45', '2025-11-30 22:53:45'),
(12, 'proprietaire2', 'proprietaire2@syndgest.fr', '$2y$10$g4JfgdPVziDDO9xO7ZkIMusuG5nm3qZX0hMoCEp.ai2.DDo20ZIZe', 'proprietaire', 'Isabelle', 'RICHARD', '06 12 34 56 02', 0, '2025-11-30 02:23:39', '2025-12-11 14:30:47', NULL),
(13, 'proprietaire3', 'proprietaire3@syndgest.fr', '$2y$10$HNrqiOoGMJRgT3Rgo/D0KOnCoVflD9BTwSTbhn8I2Aq9rZqRB7IP6', 'proprietaire', 'François', 'SIMON', '06 12 34 56 03', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(14, 'proprietaire4', 'proprietaire4@syndgest.fr', '$2y$10$8t8CzES9GiOdQAXlJN11Bep17bToZJHYQQtzVIjD946IuAmNTKZU.', 'proprietaire', 'Nathalie', 'LAURENT', '06 12 34 56 04', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(15, 'proprietaire5', 'proprietaire5@syndgest.fr', '$2y$10$UVnEv.Hppikb6GUKp/veYemMlNiYyeTIAXiumzWJA7d.tPyv6vRiK', 'proprietaire', 'Julien', 'GARCIA', '06 12 34 56 05', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(16, 'resident1', 'resident1@syndgest.fr', '$2y$10$Ubw2JxdNSRRg4AlM3Bil4eEnN6BabX63BT4/iIvtzqhLL7TaDaW0K', 'resident', 'Jeanne', 'MARTIN', '06 78 90 12 01', 1, '2025-11-30 02:23:39', '2025-11-30 22:50:53', '2025-11-30 22:50:53'),
(17, 'resident2', 'resident2@syndgest.fr', '$2y$10$lRW2vLE7dEAYnwCbEp3dMuOJCzwqtwTb16OuLWxZF2kBZaInVabCa', 'resident', 'Pierre', 'BERNARD', '06 78 90 12 02', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(18, 'resident3', 'resident3@syndgest.fr', '$2y$10$LsFujwDghN72lrgxZbgCBeU0/GFgFIf2/rCjFP1HbXY1RKvKGDOYK', 'resident', 'Marie', 'DUBOIS', '06 78 90 12 03', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(19, 'resident4', 'resident4@syndgest.fr', '$2y$10$yyZ4MsQLX6A/x.4F/bAey.z5nwXKPgICHjnWljkYtWsREN6q8SFo.', 'resident', 'Jean', 'THOMAS', '06 78 90 12 04', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(20, 'resident5', 'resident5@syndgest.fr', '$2y$10$KR7xT6YBiyeBz/Hn1tBcmefYrUXf0bYpJJmT.NQ7WrBwHaFFcU1fq', 'resident', 'Françoise', 'ROBERT', '06 78 90 12 05', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(21, 'resident6', 'resident6@syndgest.fr', '$2y$10$upy2Qm42dK4n6mDkxO3XJ.zturQ3f5T6tf1p1E7UOYpvjDcdzTVOO', 'resident', 'Claude', 'RICHARD', '06 78 90 12 06', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(22, 'resident7', 'resident7@syndgest.fr', '$2y$10$TbWYWG1WO3GwSHnRslVztOLkPUZvl.718Mq8A2L59uemof7XdNMsm', 'resident', 'Monique', 'PETIT', '06 78 90 12 07', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(23, 'resident8', 'resident8@syndgest.fr', '$2y$10$/yoslmA3Ph7YMA4FKa9AMeg/h4mCVUQwzT1BosWiBby5fV15xl5AO', 'resident', 'André', 'DURAND', '06 78 90 12 08', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(24, 'resident9', 'resident9@syndgest.fr', '$2y$10$PtkIXwkfsBAJ1FQKme1xG.tAk.gR8V5HuVWCtSTzWLAc.ERCChJ2W', 'resident', 'Jacqueline', 'LEROY', '06 78 90 12 09', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(25, 'resident10', 'resident10@syndgest.fr', '$2y$10$XuNdJ0T1i7bse7pdSEl5Pu7FBg28QnIzdRDi4zBeZihKm93x6ck8C', 'resident', 'Robert', 'MOREAU', '06 78 90 12 10', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(26, 'resident11', 'resident11@syndgest.fr', '$2y$10$hO2sbSesjyQ56WBcKBOhYe2GPpk3pY3b632.73KK8690ZEotEZlu6', 'resident', 'Simone', 'SIMON', '06 78 90 12 11', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(27, 'resident12', 'resident12@syndgest.fr', '$2y$10$9LRiTYzQ7wBx25G.6PxmnuVDCkUQPMUvUPz0dLCCIONdlFDcr3HqO', 'resident', 'Michel', 'LAURENT', '06 78 90 12 12', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(28, 'resident13', 'resident13@syndgest.fr', '$2y$10$J1jCgh7FpqElQEdg2fctuO0r5GgF8pQ12J0A0Um9CpTFzZCipwXNC', 'resident', 'Denise', 'LEFEBVRE', '06 78 90 12 13', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(29, 'resident14', 'resident14@syndgest.fr', '$2y$10$GnrHuWu/oGfFO6gFWoxD9.SyadY2bSQ4.ho5iME3hAlEveYNlQtHa', 'resident', 'Georges', 'ROUX', '06 78 90 12 14', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(30, 'resident15', 'resident15@syndgest.fr', '$2y$10$lm/qoxE4TTQYtv6ivdcPueDJtFNQPql8lRwJMT1WYrFUXCR95dJC2', 'resident', 'Paulette', 'VINCENT', '06 78 90 12 15', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(31, 'resident16', 'resident16@syndgest.fr', '$2y$10$L90QpSQrY0A53URMF69GDukb9buhj4Hdlr8j7wzkX7gMumSP2Vy1C', 'resident', 'Henri', 'FOURNIER', '06 78 90 12 16', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(32, 'resident17', 'resident17@syndgest.fr', '$2y$10$hbl9QkDSAKO0XVs7/Jxy..ql6PEk4C55TBWRSUzHsjP1yGwExqCCq', 'resident', 'Yvette', 'GIRARD', '06 78 90 12 17', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:46', NULL),
(33, 'resident18', 'resident18@syndgest.fr', '$2y$10$h/rOitTYqgM0VjQO.yaHD.bvexrnVeS.qKEIJYRH5k6xqmX2RYteS', 'resident', 'Maurice', 'BONNET', '06 78 90 12 18', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:47', NULL),
(34, 'resident19', 'resident19@syndgest.fr', '$2y$10$FwjeUGWy2p75.vxa2F6SiuRoQ96BL89.D54nJ6TVznetBG4JPTdeC', 'resident', 'Ginette', 'DUPUIS', '06 78 90 12 19', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:47', NULL),
(35, 'resident20', 'resident20@syndgest.fr', '$2y$10$XHPCoR9lxWvfZvsyzARIfOnkoCafkTMw/ZjXVySY7uvW9oQjt3QnK', 'resident', 'Raymond', 'LAMBERT', '06 78 90 12 20', 1, '2025-11-30 02:23:39', '2025-11-30 02:44:47', NULL);

--
-- Disparadores `users`
--
DELIMITER $$
CREATE TRIGGER `trg_log_user_changes` AFTER UPDATE ON `users` FOR EACH ROW BEGIN
    IF OLD.actif != NEW.actif THEN
        INSERT INTO logs_activite (user_id, action, table_name, record_id, details)
        VALUES (NEW.id, 'user_status_changed', 'users', NEW.id, 
                CONCAT('Status changed from ', OLD.actif, ' to ', NEW.actif));
    END IF;
END
$$
DELIMITER ;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `votes_ag`
--

CREATE TABLE `votes_ag` (
  `id` int(11) NOT NULL,
  `ag_id` int(11) NOT NULL,
  `resolution` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `ordre` int(11) DEFAULT NULL,
  `votes_pour` int(11) DEFAULT 0,
  `votes_contre` int(11) DEFAULT 0,
  `abstentions` int(11) DEFAULT 0,
  `tantiemes_pour` int(11) DEFAULT 0,
  `tantiemes_contre` int(11) DEFAULT 0,
  `resultat` enum('adopte','rejete','reporte') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_comptes_coproprietaires`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_comptes_coproprietaires` (
`coproprietaire_id` int(11)
,`coproprietaire` varchar(201)
,`copropriete_id` int(11)
,`copropriete` varchar(200)
,`nb_lots` bigint(21)
,`total_tantiemes` decimal(32,0)
,`nb_appels` bigint(21)
,`total_appele` decimal(32,2)
,`total_paye` decimal(32,2)
,`solde_du` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_lots_coproprietaires`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_lots_coproprietaires` (
`lot_id` int(11)
,`numero_lot` varchar(20)
,`type` enum('appartement','parking','cave','commerce','bureau','local')
,`surface` decimal(10,2)
,`copropriete_id` int(11)
,`copropriete_nom` varchar(200)
,`coproprietaire_id` int(11)
,`coproprietaire_nom` varchar(201)
,`pourcentage_possession` decimal(5,2)
,`date_acquisition` date
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_permissions_details`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_permissions_details` (
`role_nom` varchar(100)
,`role` enum('admin','gestionnaire','exploitant','proprietaire','resident')
,`module` varchar(50)
,`action` enum('create','read','update','delete','all')
,`allowed` tinyint(1)
,`description` text
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_permissions_summary`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_permissions_summary` (
`role` enum('admin','gestionnaire','exploitant','proprietaire','resident')
,`nom_affichage` varchar(100)
,`description` text
,`modules_accessibles` bigint(21)
,`liste_modules` mediumtext
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_residents_logements`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_residents_logements` (
`resident_id` int(11)
,`resident` varchar(201)
,`age` int(11)
,`telephone_mobile` varchar(20)
,`niveau_autonomie` enum('autonome','semi_autonome','dependant','gir1','gir2','gir3','gir4','gir5','gir6')
,`numero_lot` varchar(20)
,`type_lot` enum('appartement','parking','cave','commerce','bureau','local')
,`surface` decimal(10,2)
,`residence` varchar(200)
,`ville` varchar(100)
,`date_entree` date
,`loyer_mensuel_resident` decimal(10,2)
,`statut_occupation` enum('actif','preavis','termine','suspendu')
,`exploitant` varchar(200)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_revenus_proprietaires`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_revenus_proprietaires` (
`coproprietaire_id` int(11)
,`proprietaire` varchar(201)
,`nombre_contrats` bigint(21)
,`nombre_lots` bigint(21)
,`revenus_mensuels` decimal(32,2)
,`revenus_annuels` decimal(34,2)
,`loyer_moyen` decimal(14,6)
,`contrats_actifs` decimal(22,0)
,`avec_garantie` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_situation_appels_fonds`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_situation_appels_fonds` (
`appel_id` int(11)
,`copropriete_id` int(11)
,`copropriete_nom` varchar(200)
,`date_emission` date
,`date_echeance` date
,`montant_total` decimal(12,2)
,`type` enum('provision','regularisation','travaux','exceptionnel')
,`statut_appel` enum('brouillon','emis','cloture')
,`nb_lignes` bigint(21)
,`nb_payes` decimal(22,0)
,`total_appele` decimal(32,2)
,`total_paye` decimal(32,2)
,`total_impaye` decimal(33,2)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_suivi_paiements_exploitant`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_suivi_paiements_exploitant` (
`exploitant` varchar(200)
,`proprietaire` varchar(201)
,`numero_contrat` varchar(50)
,`numero_lot` varchar(20)
,`residence` varchar(200)
,`annee` int(11)
,`mois` int(11)
,`montant_total` decimal(10,2)
,`statut_paiement` enum('attente','paye','retard','impaye','litige')
,`date_echeance` date
,`date_paiement_effectif` date
,`jours_retard` int(11)
,`situation` varchar(27)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `v_taux_occupation`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `v_taux_occupation` (
`copropriete_id` int(11)
,`residence` varchar(200)
,`ville` varchar(100)
,`total_lots` bigint(21)
,`total_appartements` bigint(21)
,`appartements_occupes` bigint(21)
,`occupations_actives` bigint(21)
,`taux_occupation_pct` decimal(26,2)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `v_comptes_coproprietaires`
--
DROP TABLE IF EXISTS `v_comptes_coproprietaires`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_comptes_coproprietaires`  AS SELECT `cp`.`id` AS `coproprietaire_id`, concat(`cp`.`prenom`,' ',`cp`.`nom`) AS `coproprietaire`, `co`.`id` AS `copropriete_id`, `co`.`nom` AS `copropriete`, count(distinct `l`.`id`) AS `nb_lots`, sum(`l`.`tantiemes_generaux`) AS `total_tantiemes`, count(`laf`.`id`) AS `nb_appels`, coalesce(sum(`laf`.`montant`),0) AS `total_appele`, coalesce(sum(`laf`.`montant_paye`),0) AS `total_paye`, coalesce(sum(`laf`.`montant` - `laf`.`montant_paye`),0) AS `solde_du` FROM ((((`coproprietaires` `cp` left join `possessions` `p` on(`cp`.`id` = `p`.`coproprietaire_id` and `p`.`actif` = 1)) left join `lots` `l` on(`p`.`lot_id` = `l`.`id`)) left join `coproprietees` `co` on(`l`.`copropriete_id` = `co`.`id`)) left join `lignes_appel_fonds` `laf` on(`cp`.`id` = `laf`.`coproprietaire_id`)) GROUP BY `cp`.`id`, `co`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_lots_coproprietaires`
--
DROP TABLE IF EXISTS `v_lots_coproprietaires`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_lots_coproprietaires`  AS SELECT `l`.`id` AS `lot_id`, `l`.`numero_lot` AS `numero_lot`, `l`.`type` AS `type`, `l`.`surface` AS `surface`, `c`.`id` AS `copropriete_id`, `c`.`nom` AS `copropriete_nom`, `cp`.`id` AS `coproprietaire_id`, concat(`cp`.`prenom`,' ',`cp`.`nom`) AS `coproprietaire_nom`, `p`.`pourcentage_possession` AS `pourcentage_possession`, `p`.`date_acquisition` AS `date_acquisition` FROM (((`lots` `l` left join `coproprietees` `c` on(`l`.`copropriete_id` = `c`.`id`)) left join `possessions` `p` on(`l`.`id` = `p`.`lot_id` and `p`.`actif` = 1)) left join `coproprietaires` `cp` on(`p`.`coproprietaire_id` = `cp`.`id`)) ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_permissions_details`
--
DROP TABLE IF EXISTS `v_permissions_details`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_permissions_details`  AS SELECT `rd`.`nom_affichage` AS `role_nom`, `p`.`role` AS `role`, `p`.`module` AS `module`, `p`.`action` AS `action`, `p`.`allowed` AS `allowed`, `p`.`description` AS `description` FROM (`permissions` `p` join `role_descriptions` `rd` on(`p`.`role` = `rd`.`role`)) ORDER BY `rd`.`ordre_affichage` ASC, `p`.`module` ASC, `p`.`action` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_permissions_summary`
--
DROP TABLE IF EXISTS `v_permissions_summary`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_permissions_summary`  AS SELECT `rd`.`role` AS `role`, `rd`.`nom_affichage` AS `nom_affichage`, `rd`.`description` AS `description`, count(distinct `p`.`module`) AS `modules_accessibles`, group_concat(distinct `p`.`module` order by `p`.`module` ASC separator ', ') AS `liste_modules` FROM (`role_descriptions` `rd` left join `permissions` `p` on(`rd`.`role` = `p`.`role` and `p`.`allowed` = 1)) GROUP BY `rd`.`role` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_residents_logements`
--
DROP TABLE IF EXISTS `v_residents_logements`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_residents_logements`  AS SELECT `r`.`id` AS `resident_id`, concat(`r`.`prenom`,' ',`r`.`nom`) AS `resident`, `r`.`age` AS `age`, `r`.`telephone_mobile` AS `telephone_mobile`, `r`.`niveau_autonomie` AS `niveau_autonomie`, `l`.`numero_lot` AS `numero_lot`, `l`.`type` AS `type_lot`, `l`.`surface` AS `surface`, `c`.`nom` AS `residence`, `c`.`ville` AS `ville`, `o`.`date_entree` AS `date_entree`, `o`.`loyer_mensuel_resident` AS `loyer_mensuel_resident`, `o`.`statut` AS `statut_occupation`, `e`.`raison_sociale` AS `exploitant` FROM ((((`residents_seniors` `r` join `occupations_residents` `o` on(`r`.`id` = `o`.`resident_id`)) join `lots` `l` on(`o`.`lot_id` = `l`.`id`)) join `coproprietees` `c` on(`l`.`copropriete_id` = `c`.`id`)) left join `exploitants` `e` on(`o`.`exploitant_id` = `e`.`id`)) WHERE `o`.`statut` = 'actif' ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_revenus_proprietaires`
--
DROP TABLE IF EXISTS `v_revenus_proprietaires`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_revenus_proprietaires`  AS SELECT `cp`.`id` AS `coproprietaire_id`, concat(`cp`.`prenom`,' ',`cp`.`nom`) AS `proprietaire`, count(distinct `cg`.`id`) AS `nombre_contrats`, count(distinct `l`.`id`) AS `nombre_lots`, sum(`cg`.`loyer_mensuel_garanti`) AS `revenus_mensuels`, sum(`cg`.`loyer_mensuel_garanti` * 12) AS `revenus_annuels`, avg(`cg`.`loyer_mensuel_garanti`) AS `loyer_moyen`, sum(case when `cg`.`statut` = 'actif' then 1 else 0 end) AS `contrats_actifs`, sum(case when `cg`.`garantie_loyer` = 1 then 1 else 0 end) AS `avec_garantie` FROM ((`coproprietaires` `cp` left join `contrats_gestion` `cg` on(`cp`.`id` = `cg`.`coproprietaire_id`)) left join `lots` `l` on(`cg`.`lot_id` = `l`.`id`)) GROUP BY `cp`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_situation_appels_fonds`
--
DROP TABLE IF EXISTS `v_situation_appels_fonds`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_situation_appels_fonds`  AS SELECT `af`.`id` AS `appel_id`, `af`.`copropriete_id` AS `copropriete_id`, `co`.`nom` AS `copropriete_nom`, `af`.`date_emission` AS `date_emission`, `af`.`date_echeance` AS `date_echeance`, `af`.`montant_total` AS `montant_total`, `af`.`type` AS `type`, `af`.`statut` AS `statut_appel`, count(`laf`.`id`) AS `nb_lignes`, sum(case when `laf`.`statut_paiement` = 'paye' then 1 else 0 end) AS `nb_payes`, sum(`laf`.`montant`) AS `total_appele`, sum(`laf`.`montant_paye`) AS `total_paye`, sum(`laf`.`montant` - `laf`.`montant_paye`) AS `total_impaye` FROM ((`appels_fonds` `af` join `coproprietees` `co` on(`af`.`copropriete_id` = `co`.`id`)) left join `lignes_appel_fonds` `laf` on(`af`.`id` = `laf`.`appel_fonds_id`)) GROUP BY `af`.`id` ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_suivi_paiements_exploitant`
--
DROP TABLE IF EXISTS `v_suivi_paiements_exploitant`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_suivi_paiements_exploitant`  AS SELECT `e`.`raison_sociale` AS `exploitant`, concat(`cp`.`prenom`,' ',`cp`.`nom`) AS `proprietaire`, `cg`.`numero_contrat` AS `numero_contrat`, `l`.`numero_lot` AS `numero_lot`, `c`.`nom` AS `residence`, `p`.`annee` AS `annee`, `p`.`mois` AS `mois`, `p`.`montant_total` AS `montant_total`, `p`.`statut` AS `statut_paiement`, `p`.`date_echeance` AS `date_echeance`, `p`.`date_paiement_effectif` AS `date_paiement_effectif`, `p`.`jours_retard` AS `jours_retard`, CASE WHEN `p`.`statut` = 'paye' THEN 'À jour' WHEN `p`.`jours_retard` > 0 THEN concat('Retard de ',`p`.`jours_retard`,' jours') ELSE 'En attente' END AS `situation` FROM (((((`paiements_loyers_exploitant` `p` join `contrats_gestion` `cg` on(`p`.`contrat_gestion_id` = `cg`.`id`)) join `coproprietaires` `cp` on(`p`.`coproprietaire_id` = `cp`.`id`)) join `exploitants` `e` on(`p`.`exploitant_id` = `e`.`id`)) join `lots` `l` on(`cg`.`lot_id` = `l`.`id`)) join `coproprietees` `c` on(`l`.`copropriete_id` = `c`.`id`)) ORDER BY `p`.`date_echeance` DESC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `v_taux_occupation`
--
DROP TABLE IF EXISTS `v_taux_occupation`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_taux_occupation`  AS SELECT `c`.`id` AS `copropriete_id`, `c`.`nom` AS `residence`, `c`.`ville` AS `ville`, count(distinct `l`.`id`) AS `total_lots`, count(distinct case when `l`.`type` = 'appartement' then `l`.`id` end) AS `total_appartements`, count(distinct `o`.`id`) AS `appartements_occupes`, count(distinct case when `o`.`statut` = 'actif' then `o`.`id` end) AS `occupations_actives`, round(count(distinct case when `o`.`statut` = 'actif' then `o`.`id` end) * 100.0 / nullif(count(distinct case when `l`.`type` = 'appartement' then `l`.`id` end),0),2) AS `taux_occupation_pct` FROM ((`coproprietees` `c` left join `lots` `l` on(`c`.`id` = `l`.`copropriete_id`)) left join `occupations_residents` `o` on(`l`.`id` = `o`.`lot_id`)) WHERE `c`.`type_residence` = 'residence_seniors' GROUP BY `c`.`id` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `appels_fonds`
--
ALTER TABLE `appels_fonds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_exercice` (`exercice_id`),
  ADD KEY `idx_date_echeance` (`date_echeance`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indices de la tabla `assemblees_generales`
--
ALTER TABLE `assemblees_generales`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_date` (`date_ag`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indices de la tabla `baux`
--
ALTER TABLE `baux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_locataire` (`locataire_id`),
  ADD KEY `idx_etat` (`etat`),
  ADD KEY `idx_date_debut` (`date_debut`);

--
-- Indices de la tabla `comptes_comptables`
--
ALTER TABLE `comptes_comptables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_compte` (`numero_compte`),
  ADD KEY `idx_numero` (`numero_compte`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_parent` (`parent_id`);

--
-- Indices de la tabla `contrats_gestion`
--
ALTER TABLE `contrats_gestion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_contrat` (`numero_contrat`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_exploitant` (`exploitant_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_effet` (`date_effet`);

--
-- Indices de la tabla `coproprietaires`
--
ALTER TABLE `coproprietaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `coproprietees`
--
ALTER TABLE `coproprietees`
  ADD PRIMARY KEY (`id`),
  ADD KEY `syndic_id` (`syndic_id`),
  ADD KEY `idx_ville` (`ville`),
  ADD KEY `idx_code_postal` (`code_postal`),
  ADD KEY `exploitant_id` (`exploitant_id`);

--
-- Indices de la tabla `devis`
--
ALTER TABLE `devis`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_travaux` (`travaux_id`),
  ADD KEY `idx_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_selectionne` (`selectionne`);

--
-- Indices de la tabla `documents`
--
ALTER TABLE `documents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `coproprietaire_id` (`coproprietaire_id`),
  ADD KEY `travaux_id` (`travaux_id`),
  ADD KEY `sinistre_id` (`sinistre_id`),
  ADD KEY `uploaded_by` (`uploaded_by`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_categorie` (`categorie`),
  ADD KEY `idx_date` (`date_upload`);

--
-- Indices de la tabla `ecritures_comptables`
--
ALTER TABLE `ecritures_comptables`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_exercice` (`exercice_id`),
  ADD KEY `idx_date` (`date_ecriture`),
  ADD KEY `idx_compte_debit` (`compte_debit`),
  ADD KEY `idx_compte_credit` (`compte_credit`);

--
-- Indices de la tabla `exercices_comptables`
--
ALTER TABLE `exercices_comptables`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_exercice` (`copropriete_id`,`annee`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_annee` (`annee`);

--
-- Indices de la tabla `exploitants`
--
ALTER TABLE `exploitants`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `siret` (`siret`),
  ADD KEY `idx_raison_sociale` (`raison_sociale`),
  ADD KEY `idx_siret` (`siret`),
  ADD KEY `idx_actif` (`actif`),
  ADD KEY `fk_exploitant_user` (`user_id`);

--
-- Indices de la tabla `factures_fournisseurs`
--
ALTER TABLE `factures_fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_fournisseur` (`fournisseur_id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_facture` (`date_facture`);

--
-- Indices de la tabla `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `idx_type` (`type_service`),
  ADD KEY `idx_actif` (`actif`);

--
-- Indices de la tabla `lignes_appel_fonds`
--
ALTER TABLE `lignes_appel_fonds`
  ADD PRIMARY KEY (`id`),
  ADD KEY `lot_id` (`lot_id`),
  ADD KEY `idx_appel` (`appel_fonds_id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_statut` (`statut_paiement`);

--
-- Indices de la tabla `locataires`
--
ALTER TABLE `locataires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `user_id` (`user_id`),
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `idx_email` (`email`);

--
-- Indices de la tabla `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_action` (`action`),
  ADD KEY `idx_table` (`table_name`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indices de la tabla `lots`
--
ALTER TABLE `lots`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_lot` (`copropriete_id`,`numero_lot`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_copropriete` (`copropriete_id`);

--
-- Indices de la tabla `messages`
--
ALTER TABLE `messages`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_expediteur` (`expediteur_id`),
  ADD KEY `idx_destinataire` (`destinataire_id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_lu` (`lu`),
  ADD KEY `idx_date` (`date_envoi`);

--
-- Indices de la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_user` (`user_id`),
  ADD KEY `idx_lu` (`lu`),
  ADD KEY `idx_type` (`type`),
  ADD KEY `idx_date` (`created_at`);

--
-- Indices de la tabla `occupations_residents`
--
ALTER TABLE `occupations_residents`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_resident` (`resident_id`),
  ADD KEY `idx_exploitant` (`exploitant_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date_entree` (`date_entree`);

--
-- Indices de la tabla `paiements_loyers_exploitant`
--
ALTER TABLE `paiements_loyers_exploitant`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_paiement` (`contrat_gestion_id`,`annee`,`mois`),
  ADD KEY `exploitant_id` (`exploitant_id`),
  ADD KEY `idx_contrat` (`contrat_gestion_id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_periode` (`annee`,`mois`);

--
-- Indices de la tabla `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle` (`cle`),
  ADD KEY `idx_cle` (`cle`),
  ADD KEY `idx_categorie` (`categorie`);

--
-- Indices de la tabla `permissions`
--
ALTER TABLE `permissions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_permission` (`role`,`module`,`action`),
  ADD KEY `idx_role` (`role`),
  ADD KEY `idx_module` (`module`);

--
-- Indices de la tabla `possessions`
--
ALTER TABLE `possessions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_actif` (`actif`);

--
-- Indices de la tabla `quittances`
--
ALTER TABLE `quittances`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_quittance` (`bail_id`,`mois`,`annee`),
  ADD KEY `idx_bail` (`bail_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_periode` (`annee`,`mois`);

--
-- Indices de la tabla `relances`
--
ALTER TABLE `relances`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_type_ref` (`type`,`reference_id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_locataire` (`locataire_id`),
  ADD KEY `idx_statut` (`statut`);

--
-- Indices de la tabla `residents_seniors`
--
ALTER TABLE `residents_seniors`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_nom` (`nom`),
  ADD KEY `idx_date_naissance` (`date_naissance`),
  ADD KEY `idx_actif` (`actif`),
  ADD KEY `fk_resident_user` (`user_id`),
  ADD KEY `idx_numero_cni` (`numero_cni`);

--
-- Indices de la tabla `revenus_fiscaux_proprietaires`
--
ALTER TABLE `revenus_fiscaux_proprietaires`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_declaration` (`coproprietaire_id`,`lot_id`,`annee_fiscale`),
  ADD KEY `lot_id` (`lot_id`),
  ADD KEY `contrat_gestion_id` (`contrat_gestion_id`),
  ADD KEY `idx_coproprietaire` (`coproprietaire_id`),
  ADD KEY `idx_annee` (`annee_fiscale`);

--
-- Indices de la tabla `role_descriptions`
--
ALTER TABLE `role_descriptions`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `role` (`role`);

--
-- Indices de la tabla `sinistres`
--
ALTER TABLE `sinistres`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_lot` (`lot_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_date` (`date_sinistre`);

--
-- Indices de la tabla `travaux`
--
ALTER TABLE `travaux`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_copropriete` (`copropriete_id`),
  ADD KEY `idx_statut` (`statut`),
  ADD KEY `idx_urgence` (`urgence`);

--
-- Indices de la tabla `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_username` (`username`),
  ADD KEY `idx_role` (`role`);

--
-- Indices de la tabla `votes_ag`
--
ALTER TABLE `votes_ag`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ag` (`ag_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `appels_fonds`
--
ALTER TABLE `appels_fonds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `assemblees_generales`
--
ALTER TABLE `assemblees_generales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `baux`
--
ALTER TABLE `baux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comptes_comptables`
--
ALTER TABLE `comptes_comptables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=16;

--
-- AUTO_INCREMENT de la tabla `contrats_gestion`
--
ALTER TABLE `contrats_gestion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `coproprietaires`
--
ALTER TABLE `coproprietaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `coproprietees`
--
ALTER TABLE `coproprietees`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=81;

--
-- AUTO_INCREMENT de la tabla `devis`
--
ALTER TABLE `devis`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `documents`
--
ALTER TABLE `documents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ecritures_comptables`
--
ALTER TABLE `ecritures_comptables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `exercices_comptables`
--
ALTER TABLE `exercices_comptables`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=51;

--
-- AUTO_INCREMENT de la tabla `exploitants`
--
ALTER TABLE `exploitants`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `factures_fournisseurs`
--
ALTER TABLE `factures_fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `lignes_appel_fonds`
--
ALTER TABLE `lignes_appel_fonds`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `locataires`
--
ALTER TABLE `locataires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `lots`
--
ALTER TABLE `lots`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=297;

--
-- AUTO_INCREMENT de la tabla `messages`
--
ALTER TABLE `messages`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `occupations_residents`
--
ALTER TABLE `occupations_residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `paiements_loyers_exploitant`
--
ALTER TABLE `paiements_loyers_exploitant`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=52;

--
-- AUTO_INCREMENT de la tabla `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT de la tabla `permissions`
--
ALTER TABLE `permissions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=173;

--
-- AUTO_INCREMENT de la tabla `possessions`
--
ALTER TABLE `possessions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `quittances`
--
ALTER TABLE `quittances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `relances`
--
ALTER TABLE `relances`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `residents_seniors`
--
ALTER TABLE `residents_seniors`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `revenus_fiscaux_proprietaires`
--
ALTER TABLE `revenus_fiscaux_proprietaires`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `role_descriptions`
--
ALTER TABLE `role_descriptions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `sinistres`
--
ALTER TABLE `sinistres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `travaux`
--
ALTER TABLE `travaux`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT de la tabla `votes_ag`
--
ALTER TABLE `votes_ag`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `appels_fonds`
--
ALTER TABLE `appels_fonds`
  ADD CONSTRAINT `appels_fonds_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `appels_fonds_ibfk_2` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `assemblees_generales`
--
ALTER TABLE `assemblees_generales`
  ADD CONSTRAINT `assemblees_generales_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `baux`
--
ALTER TABLE `baux`
  ADD CONSTRAINT `baux_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `baux_ibfk_2` FOREIGN KEY (`locataire_id`) REFERENCES `locataires` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comptes_comptables`
--
ALTER TABLE `comptes_comptables`
  ADD CONSTRAINT `comptes_comptables_ibfk_1` FOREIGN KEY (`parent_id`) REFERENCES `comptes_comptables` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `contrats_gestion`
--
ALTER TABLE `contrats_gestion`
  ADD CONSTRAINT `contrats_gestion_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contrats_gestion_ibfk_2` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `contrats_gestion_ibfk_3` FOREIGN KEY (`exploitant_id`) REFERENCES `exploitants` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `coproprietaires`
--
ALTER TABLE `coproprietaires`
  ADD CONSTRAINT `coproprietaires_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `coproprietees`
--
ALTER TABLE `coproprietees`
  ADD CONSTRAINT `coproprietees_ibfk_1` FOREIGN KEY (`syndic_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `coproprietees_ibfk_2` FOREIGN KEY (`exploitant_id`) REFERENCES `exploitants` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `devis`
--
ALTER TABLE `devis`
  ADD CONSTRAINT `devis_ibfk_1` FOREIGN KEY (`travaux_id`) REFERENCES `travaux` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `devis_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `documents`
--
ALTER TABLE `documents`
  ADD CONSTRAINT `documents_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_2` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_3` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_4` FOREIGN KEY (`travaux_id`) REFERENCES `travaux` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_5` FOREIGN KEY (`sinistre_id`) REFERENCES `sinistres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `documents_ibfk_6` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `ecritures_comptables`
--
ALTER TABLE `ecritures_comptables`
  ADD CONSTRAINT `ecritures_comptables_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ecritures_comptables_ibfk_2` FOREIGN KEY (`exercice_id`) REFERENCES `exercices_comptables` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `ecritures_comptables_ibfk_3` FOREIGN KEY (`compte_debit`) REFERENCES `comptes_comptables` (`id`),
  ADD CONSTRAINT `ecritures_comptables_ibfk_4` FOREIGN KEY (`compte_credit`) REFERENCES `comptes_comptables` (`id`),
  ADD CONSTRAINT `ecritures_comptables_ibfk_5` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `exercices_comptables`
--
ALTER TABLE `exercices_comptables`
  ADD CONSTRAINT `exercices_comptables_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `exploitants`
--
ALTER TABLE `exploitants`
  ADD CONSTRAINT `fk_exploitant_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `factures_fournisseurs`
--
ALTER TABLE `factures_fournisseurs`
  ADD CONSTRAINT `factures_fournisseurs_ibfk_1` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `factures_fournisseurs_ibfk_2` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `lignes_appel_fonds`
--
ALTER TABLE `lignes_appel_fonds`
  ADD CONSTRAINT `lignes_appel_fonds_ibfk_1` FOREIGN KEY (`appel_fonds_id`) REFERENCES `appels_fonds` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_appel_fonds_ibfk_2` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `lignes_appel_fonds_ibfk_3` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `locataires`
--
ALTER TABLE `locataires`
  ADD CONSTRAINT `locataires_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `logs_activite_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `lots`
--
ALTER TABLE `lots`
  ADD CONSTRAINT `lots_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`expediteur_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`destinataire_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_ibfk_3` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `occupations_residents`
--
ALTER TABLE `occupations_residents`
  ADD CONSTRAINT `occupations_residents_ibfk_1` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `occupations_residents_ibfk_2` FOREIGN KEY (`resident_id`) REFERENCES `residents_seniors` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `occupations_residents_ibfk_3` FOREIGN KEY (`exploitant_id`) REFERENCES `exploitants` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `paiements_loyers_exploitant`
--
ALTER TABLE `paiements_loyers_exploitant`
  ADD CONSTRAINT `paiements_loyers_exploitant_ibfk_1` FOREIGN KEY (`contrat_gestion_id`) REFERENCES `contrats_gestion` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_loyers_exploitant_ibfk_2` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `paiements_loyers_exploitant_ibfk_3` FOREIGN KEY (`exploitant_id`) REFERENCES `exploitants` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `possessions`
--
ALTER TABLE `possessions`
  ADD CONSTRAINT `possessions_ibfk_1` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `possessions_ibfk_2` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `quittances`
--
ALTER TABLE `quittances`
  ADD CONSTRAINT `quittances_ibfk_1` FOREIGN KEY (`bail_id`) REFERENCES `baux` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `relances`
--
ALTER TABLE `relances`
  ADD CONSTRAINT `relances_ibfk_1` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `relances_ibfk_2` FOREIGN KEY (`locataire_id`) REFERENCES `locataires` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `residents_seniors`
--
ALTER TABLE `residents_seniors`
  ADD CONSTRAINT `fk_resident_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `revenus_fiscaux_proprietaires`
--
ALTER TABLE `revenus_fiscaux_proprietaires`
  ADD CONSTRAINT `revenus_fiscaux_proprietaires_ibfk_1` FOREIGN KEY (`coproprietaire_id`) REFERENCES `coproprietaires` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `revenus_fiscaux_proprietaires_ibfk_2` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `revenus_fiscaux_proprietaires_ibfk_3` FOREIGN KEY (`contrat_gestion_id`) REFERENCES `contrats_gestion` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `sinistres`
--
ALTER TABLE `sinistres`
  ADD CONSTRAINT `sinistres_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sinistres_ibfk_2` FOREIGN KEY (`lot_id`) REFERENCES `lots` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `travaux`
--
ALTER TABLE `travaux`
  ADD CONSTRAINT `travaux_ibfk_1` FOREIGN KEY (`copropriete_id`) REFERENCES `coproprietees` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `votes_ag`
--
ALTER TABLE `votes_ag`
  ADD CONSTRAINT `votes_ag_ibfk_1` FOREIGN KEY (`ag_id`) REFERENCES `assemblees_generales` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
