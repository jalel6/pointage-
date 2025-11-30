-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1:3306
-- Généré le : dim. 30 nov. 2025 à 17:35
-- Version du serveur : 9.1.0
-- Version de PHP : 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `pointage_saboura`
--

-- --------------------------------------------------------

--
-- Structure de la table `conge`
--

DROP TABLE IF EXISTS `conge`;
CREATE TABLE IF NOT EXISTS `conge` (
  `idConge` int NOT NULL AUTO_INCREMENT,
  `employe_id` int NOT NULL,
  `dateDebut` date NOT NULL,
  `dateFin` date NOT NULL,
  `type_conge` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` text COLLATE utf8mb4_general_ci,
  `date_ajout` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idConge`),
  KEY `employe_id` (`employe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `conge`
--

INSERT INTO `conge` (`idConge`, `employe_id`, `dateDebut`, `dateFin`, `type_conge`, `description`, `date_ajout`) VALUES
(23, 90, '2025-06-22', '2025-06-27', 'Exceptionnel', ' Je demande un congé exceptionnel en raison d’un événement familial important nécessitant ma présenc', '2025-06-19 10:13:07');

-- --------------------------------------------------------

--
-- Structure de la table `demandeconge`
--

DROP TABLE IF EXISTS `demandeconge`;
CREATE TABLE IF NOT EXISTS `demandeconge` (
  `idDemande` int NOT NULL AUTO_INCREMENT,
  `employe_id` int NOT NULL,
  `dateSoumission` date DEFAULT NULL,
  `dateDebut` date DEFAULT NULL,
  `dateFin` date DEFAULT NULL,
  `statut` enum('en attente','approuvé','rejeté') COLLATE utf8mb4_general_ci DEFAULT 'en attente',
  `type_conge` enum('annuel','maladie','sans solde','Maternité','Paternité','Exceptionnel') COLLATE utf8mb4_general_ci DEFAULT NULL,
  `description` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `notification_vue` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`idDemande`),
  KEY `fk_employe` (`employe_id`)
) ENGINE=InnoDB AUTO_INCREMENT=109 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `demandeconge`
--

INSERT INTO `demandeconge` (`idDemande`, `employe_id`, `dateSoumission`, `dateDebut`, `dateFin`, `statut`, `type_conge`, `description`, `notification_vue`) VALUES
(85, 74, '2025-05-20', '2025-05-21', '2025-05-23', 'rejeté', 'maladie', 'Arrêt de travail prescrit par un médecin suite à une maladie', 1),
(90, 74, '2025-05-25', '2025-05-26', '2025-05-28', 'rejeté', 'maladie', 'Arrêt de travail prescrit par un médecin suite à une maladie', 1),
(93, 77, '2025-05-25', '2025-05-27', '2025-05-28', 'approuvé', 'maladie', 'Je souhaite demander un congé de maladie en raison d\'une grippe', 0),
(94, 74, '2025-05-25', '2025-05-27', '2025-05-30', 'approuvé', 'maladie', 'Repos médical recommandé après consultation pour cause de maladie.', 1),
(96, 74, '2025-05-25', '2025-05-30', '2025-05-31', 'rejeté', 'maladie', 'je suis malade', 1),
(98, 87, '2025-05-26', '2025-05-28', '2025-05-31', 'approuvé', '', 'Voyage ou projet personnel ', 1),
(99, 87, '2025-05-26', '2025-05-29', '2025-05-31', 'approuvé', 'annuel', 'Voyage ou projet personnel ', 1),
(101, 89, '2025-05-26', '2025-05-29', '2025-06-02', 'approuvé', 'Exceptionnel', 'Expérience professionnelle extérieure ', 0),
(102, 74, '2025-06-13', '2025-06-14', '2025-06-15', 'rejeté', 'Maternité', 'azertyuio', 1),
(103, 74, '2025-06-16', '2025-06-16', '2025-06-18', 'rejeté', 'maladie', 'je suis malade', 1),
(104, 74, '2025-06-16', '2025-06-17', '2025-06-18', 'rejeté', 'maladie', 'je suis malade\r\n', 1),
(105, 74, '2025-06-16', '2025-06-17', '2025-06-19', 'rejeté', 'maladie', 'je suis malade\r\n', 1),
(106, 87, '2025-06-19', '2025-06-19', '2025-06-21', 'rejeté', 'maladie', ' Je sollicite un congé de maladie en raison d’un état de santé nécessitant du repos et un suivi médi', 1),
(107, 90, '2025-06-19', '2025-06-22', '2025-06-27', 'approuvé', 'Exceptionnel', ' Je demande un congé exceptionnel en raison d’un événement familial important nécessitant ma présenc', 1),
(108, 90, '2025-06-19', '2025-06-20', '2025-06-29', 'en attente', 'Maternité', 'rtfgyhujioko', 0);

-- --------------------------------------------------------

--
-- Structure de la table `employes`
--

DROP TABLE IF EXISTS `employes`;
CREATE TABLE IF NOT EXISTS `employes` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `prenom` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `email` varchar(30) COLLATE utf8mb4_general_ci NOT NULL,
  `telephone` int NOT NULL,
  `cin` int NOT NULL,
  `adresse` varchar(15) COLLATE utf8mb4_general_ci NOT NULL,
  `sexe` varchar(5) COLLATE utf8mb4_general_ci NOT NULL,
  `age` int NOT NULL,
  `login` varchar(20) COLLATE utf8mb4_general_ci NOT NULL,
  `password` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `fonction` enum('admin','secrétaire','employé','') COLLATE utf8mb4_general_ci NOT NULL,
  `date_embauche` date DEFAULT NULL,
  `photo_profil` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `poste` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `solde_specifique` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login` (`login`),
  KEY `fk_poste` (`poste`)
) ENGINE=InnoDB AUTO_INCREMENT=93 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `employes`
--

INSERT INTO `employes` (`id`, `nom`, `prenom`, `email`, `telephone`, `cin`, `adresse`, `sexe`, `age`, `login`, `password`, `fonction`, `date_embauche`, `photo_profil`, `poste`, `solde_specifique`) VALUES
(74, 'mariem', 'mnafki', 'mariem@gmail.com', 25845987, 15229911, 'jelma', 'Femme', 22, 'mariem', 'maryem@123', 'employé', '2025-05-16', 'uploads/pp.jpg', 'developpeur', NULL),
(77, 'Jamel Eddine', 'saidi', 'Jamel@gmail.com', 87459854, 12587711, 'sidi bouzid', 'Homme', 35, 'jamel', 'jamel123', 'admin', '2025-05-15', 'uploads/adm.jpg', 'developpeur', NULL),
(86, 'thabet', 'rachdi', 'thabet@gmail.com', 58785879, 15448899, 'jelma', 'Homme', 45, 'thabet', 'thabet123', 'employé', '2025-05-25', 'uploads/1748170033_tt.jpg', 'developpeur', NULL),
(87, 'hazar', 'rachdi', 'hazar@gmail.com', 25458897, 14584482, 'jelma', 'Femme', 22, 'hazar', 'hazar123', 'secrétaire', '2025-05-23', 'uploads/1748170568_ss.jpg', 'developpeur', NULL),
(89, 'sara', 'mnafki', 'sara@gmail.com', 22598745, 14584489, 'jelma', 'Femme', 27, 'sara', 'a37536de1dbabc9bdb00fa4f482fcde0', 'employé', '2025-05-23', 'uploads/1748170963_sss.jpg', 'developpeur', NULL),
(90, 'amna', 'mnafki', 'amna@gmail.com', 23254896, 15859677, 'sidi bouzid', 'Femme', 25, 'amna', '7b75a82d7b9c6ed96fec4e4f5a838eb5', 'employé', '2025-05-20', 'uploads/1748171592_med.jpg', 'sales', NULL),
(91, 'hamza', 'zayeni', 'hamza@gmail.com', 92722352, 15478833, 'jelma', 'Homme', 23, 'hamza', '6a6ee0ad77aaff902a4ece82c7a74496', 'employé', '2025-06-07', 'uploads/1749315347_1000012631.jpg', 'sales', NULL),
(92, 'bouazizi', 'Jalel', 'jalelbouazizi6@gmail.com', 53499785, 15261622, '9100 sidi bouzi', 'Homme', 22, 'jalel', '011034abcbf91a4f65947ac5319469e3', 'employé', '2025-11-30', 'uploads/1764522594_Screenshot_2025-01-22_203320.png', 'developpeur', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `horaire`
--

DROP TABLE IF EXISTS `horaire`;
CREATE TABLE IF NOT EXISTS `horaire` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_periode` int NOT NULL,
  `type` enum('matin','apres_midi') COLLATE utf8mb4_general_ci NOT NULL,
  `heure_debut` time NOT NULL,
  `heure_fin` time NOT NULL,
  `limite_retard` int DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_periode` (`id_periode`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `horaire`
--

INSERT INTO `horaire` (`id`, `id_periode`, `type`, `heure_debut`, `heure_fin`, `limite_retard`) VALUES
(15, 14, 'matin', '08:30:00', '12:00:00', 30),
(16, 14, 'apres_midi', '13:00:00', '16:00:00', 30),
(17, 15, 'matin', '09:00:00', '12:00:00', 30),
(18, 15, 'apres_midi', '14:00:00', '16:30:00', 30);

-- --------------------------------------------------------

--
-- Structure de la table `jours_feries`
--

DROP TABLE IF EXISTS `jours_feries`;
CREATE TABLE IF NOT EXISTS `jours_feries` (
  `id` int NOT NULL AUTO_INCREMENT,
  `date_ferie` date NOT NULL,
  `nom_ferie` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `type_ferie` enum('standard','personnalise') COLLATE utf8mb4_general_ci DEFAULT 'personnalise',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=227 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `jours_feries`
--

INSERT INTO `jours_feries` (`id`, `date_ferie`, `nom_ferie`, `type_ferie`) VALUES
(62, '2025-01-01', 'Jour de l\\\'An', 'standard'),
(63, '2026-01-01', 'Jour de l\\\'An', 'standard'),
(64, '2027-01-01', 'Jour de l\\\'An', 'standard'),
(65, '2028-01-01', 'Jour de l\\\'An', 'standard'),
(66, '2029-01-01', 'Jour de l\\\'An', 'standard'),
(67, '2030-01-01', 'Jour de l\\\'An', 'standard'),
(68, '2031-01-01', 'Jour de l\\\'An', 'standard'),
(69, '2032-01-01', 'Jour de l\\\'An', 'standard'),
(70, '2033-01-01', 'Jour de l\\\'An', 'standard'),
(71, '2034-01-01', 'Jour de l\\\'An', 'standard'),
(72, '2035-01-01', 'Jour de l\\\'An', 'standard'),
(73, '2025-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(74, '2026-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(75, '2027-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(76, '2028-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(77, '2029-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(78, '2030-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(79, '2031-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(80, '2032-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(81, '2033-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(82, '2034-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(83, '2035-01-11', 'Manifeste de l\\\'indépendance', 'standard'),
(84, '2025-05-01', 'Fête du Travail', 'standard'),
(85, '2026-05-01', 'Fête du Travail', 'standard'),
(86, '2027-05-01', 'Fête du Travail', 'standard'),
(87, '2028-05-01', 'Fête du Travail', 'standard'),
(88, '2029-05-01', 'Fête du Travail', 'standard'),
(89, '2030-05-01', 'Fête du Travail', 'standard'),
(90, '2031-05-01', 'Fête du Travail', 'standard'),
(91, '2032-05-01', 'Fête du Travail', 'standard'),
(92, '2033-05-01', 'Fête du Travail', 'standard'),
(93, '2034-05-01', 'Fête du Travail', 'standard'),
(94, '2035-05-01', 'Fête du Travail', 'standard'),
(95, '2025-07-30', 'Fête du Trône', 'standard'),
(96, '2026-07-30', 'Fête du Trône', 'standard'),
(97, '2027-07-30', 'Fête du Trône', 'standard'),
(98, '2028-07-30', 'Fête du Trône', 'standard'),
(99, '2029-07-30', 'Fête du Trône', 'standard'),
(100, '2030-07-30', 'Fête du Trône', 'standard'),
(101, '2031-07-30', 'Fête du Trône', 'standard'),
(102, '2032-07-30', 'Fête du Trône', 'standard'),
(103, '2033-07-30', 'Fête du Trône', 'standard'),
(104, '2034-07-30', 'Fête du Trône', 'standard'),
(105, '2035-07-30', 'Fête du Trône', 'standard'),
(106, '2025-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(107, '2026-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(108, '2027-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(109, '2028-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(110, '2029-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(111, '2030-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(112, '2031-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(113, '2032-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(114, '2033-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(115, '2034-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(116, '2035-08-14', 'Allégeance de Oued Ed-Dahab', 'standard'),
(117, '2025-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(118, '2026-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(119, '2027-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(120, '2028-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(121, '2029-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(122, '2030-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(123, '2031-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(124, '2032-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(125, '2033-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(126, '2034-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(127, '2035-08-20', 'Révolution du Roi et du Peuple', 'standard'),
(128, '2025-08-21', 'Fête de la Jeunesse', 'standard'),
(129, '2026-08-21', 'Fête de la Jeunesse', 'standard'),
(130, '2027-08-21', 'Fête de la Jeunesse', 'standard'),
(131, '2028-08-21', 'Fête de la Jeunesse', 'standard'),
(132, '2029-08-21', 'Fête de la Jeunesse', 'standard'),
(133, '2030-08-21', 'Fête de la Jeunesse', 'standard'),
(134, '2031-08-21', 'Fête de la Jeunesse', 'standard'),
(135, '2032-08-21', 'Fête de la Jeunesse', 'standard'),
(136, '2033-08-21', 'Fête de la Jeunesse', 'standard'),
(137, '2034-08-21', 'Fête de la Jeunesse', 'standard'),
(138, '2035-08-21', 'Fête de la Jeunesse', 'standard'),
(139, '2025-11-06', 'Fête de la Marche Verte', 'standard'),
(140, '2026-11-06', 'Fête de la Marche Verte', 'standard'),
(141, '2027-11-06', 'Fête de la Marche Verte', 'standard'),
(142, '2028-11-06', 'Fête de la Marche Verte', 'standard'),
(143, '2029-11-06', 'Fête de la Marche Verte', 'standard'),
(144, '2030-11-06', 'Fête de la Marche Verte', 'standard'),
(145, '2031-11-06', 'Fête de la Marche Verte', 'standard'),
(146, '2032-11-06', 'Fête de la Marche Verte', 'standard'),
(147, '2033-11-06', 'Fête de la Marche Verte', 'standard'),
(148, '2034-11-06', 'Fête de la Marche Verte', 'standard'),
(149, '2035-11-06', 'Fête de la Marche Verte', 'standard'),
(150, '2025-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(151, '2026-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(152, '2027-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(153, '2028-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(154, '2029-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(155, '2030-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(156, '2031-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(157, '2032-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(158, '2033-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(159, '2034-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(160, '2035-11-18', 'Fête de l\\\'Indépendance', 'standard'),
(161, '2025-03-29', 'Aïd al-Fitr', 'standard'),
(162, '2026-03-29', 'Aïd al-Fitr', 'standard'),
(163, '2027-03-29', 'Aïd al-Fitr', 'standard'),
(164, '2028-03-29', 'Aïd al-Fitr', 'standard'),
(165, '2029-03-29', 'Aïd al-Fitr', 'standard'),
(166, '2030-03-29', 'Aïd al-Fitr', 'standard'),
(167, '2031-03-29', 'Aïd al-Fitr', 'standard'),
(168, '2032-03-29', 'Aïd al-Fitr', 'standard'),
(169, '2033-03-29', 'Aïd al-Fitr', 'standard'),
(170, '2034-03-29', 'Aïd al-Fitr', 'standard'),
(171, '2035-03-29', 'Aïd al-Fitr', 'standard'),
(172, '2025-04-01', 'Aïd al-Fitr', 'standard'),
(173, '2026-04-01', 'Aïd al-Fitr', 'standard'),
(174, '2027-04-01', 'Aïd al-Fitr', 'standard'),
(175, '2028-04-01', 'Aïd al-Fitr', 'standard'),
(176, '2029-04-01', 'Aïd al-Fitr', 'standard'),
(177, '2030-04-01', 'Aïd al-Fitr', 'standard'),
(178, '2031-04-01', 'Aïd al-Fitr', 'standard'),
(179, '2032-04-01', 'Aïd al-Fitr', 'standard'),
(180, '2033-04-01', 'Aïd al-Fitr', 'standard'),
(181, '2034-04-01', 'Aïd al-Fitr', 'standard'),
(182, '2035-04-01', 'Aïd al-Fitr', 'standard'),
(183, '2025-06-06', 'Aïd al-Adha', 'standard'),
(184, '2026-06-06', 'Aïd al-Adha', 'standard'),
(185, '2027-06-06', 'Aïd al-Adha', 'standard'),
(186, '2028-06-06', 'Aïd al-Adha', 'standard'),
(187, '2029-06-06', 'Aïd al-Adha', 'standard'),
(188, '2030-06-06', 'Aïd al-Adha', 'standard'),
(189, '2031-06-06', 'Aïd al-Adha', 'standard'),
(190, '2032-06-06', 'Aïd al-Adha', 'standard'),
(191, '2033-06-06', 'Aïd al-Adha', 'standard'),
(192, '2034-06-06', 'Aïd al-Adha', 'standard'),
(193, '2035-06-06', 'Aïd al-Adha', 'standard'),
(194, '2025-06-07', 'Aïd al-Adha', 'standard'),
(195, '2026-06-07', 'Aïd al-Adha', 'standard'),
(196, '2027-06-07', 'Aïd al-Adha', 'standard'),
(197, '2028-06-07', 'Aïd al-Adha', 'standard'),
(198, '2029-06-07', 'Aïd al-Adha', 'standard'),
(199, '2030-06-07', 'Aïd al-Adha', 'standard'),
(200, '2031-06-07', 'Aïd al-Adha', 'standard'),
(201, '2032-06-07', 'Aïd al-Adha', 'standard'),
(202, '2033-06-07', 'Aïd al-Adha', 'standard'),
(203, '2034-06-07', 'Aïd al-Adha', 'standard'),
(204, '2035-06-07', 'Aïd al-Adha', 'standard'),
(205, '2025-06-21', '1er Moharram ', 'standard'),
(206, '2026-06-21', '1er Moharram ', 'standard'),
(207, '2027-06-21', '1er Moharram ', 'standard'),
(208, '2028-06-21', '1er Moharram ', 'standard'),
(209, '2029-06-21', '1er Moharram ', 'standard'),
(210, '2030-06-21', '1er Moharram ', 'standard'),
(211, '2031-06-21', '1er Moharram ', 'standard'),
(212, '2032-06-21', '1er Moharram ', 'standard'),
(213, '2033-06-21', '1er Moharram ', 'standard'),
(214, '2034-06-21', '1er Moharram ', 'standard'),
(215, '2035-06-21', '1er Moharram ', 'standard'),
(216, '2025-09-15', 'Anniversaire du Prophète ', 'standard'),
(217, '2026-09-15', 'Anniversaire du Prophète ', 'standard'),
(218, '2027-09-15', 'Anniversaire du Prophète ', 'standard'),
(219, '2028-09-15', 'Anniversaire du Prophète ', 'standard'),
(220, '2029-09-15', 'Anniversaire du Prophète ', 'standard'),
(221, '2030-09-15', 'Anniversaire du Prophète ', 'standard'),
(222, '2031-09-15', 'Anniversaire du Prophète ', 'standard'),
(223, '2032-09-15', 'Anniversaire du Prophète ', 'standard'),
(224, '2033-09-15', 'Anniversaire du Prophète ', 'standard'),
(225, '2034-09-15', 'Anniversaire du Prophète ', 'standard'),
(226, '2035-09-15', 'Anniversaire du Prophète ', 'standard');

-- --------------------------------------------------------

--
-- Structure de la table `messages`
--

DROP TABLE IF EXISTS `messages`;
CREATE TABLE IF NOT EXISTS `messages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `id_expediteur` int NOT NULL,
  `id_destinataire` int NOT NULL,
  `message` text COLLATE utf8mb4_general_ci NOT NULL,
  `date_envoi` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `id_expediteur` (`id_expediteur`),
  KEY `id_destinataire` (`id_destinataire`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `periode`
--

DROP TABLE IF EXISTS `periode`;
CREATE TABLE IF NOT EXISTS `periode` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nom_periode` varchar(100) COLLATE utf8mb4_general_ci NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `periode`
--

INSERT INTO `periode` (`id`, `nom_periode`, `date_debut`, `date_fin`) VALUES
(14, 'Eté', '2025-05-25', '2025-09-30'),
(15, 'hiver', '2024-12-30', '2025-02-28');

-- --------------------------------------------------------

--
-- Structure de la table `photos`
--

DROP TABLE IF EXISTS `photos`;
CREATE TABLE IF NOT EXISTS `photos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pointage_id` int DEFAULT NULL,
  `photo_path` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `date_prise` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `pointage_id` (`pointage_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Structure de la table `pointages`
--

DROP TABLE IF EXISTS `pointages`;
CREATE TABLE IF NOT EXISTS `pointages` (
  `id` int NOT NULL AUTO_INCREMENT,
  `employe_id` int NOT NULL,
  `heure_arrivee` datetime DEFAULT NULL,
  `heure_depart` datetime DEFAULT NULL,
  `statut` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `photo` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=345 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `pointages`
--

INSERT INTO `pointages` (`id`, `employe_id`, `heure_arrivee`, `heure_depart`, `statut`, `photo`) VALUES
(317, 77, '2025-05-26 11:38:23', NULL, 'arrivé_matin', 'photo_1748255903.png'),
(319, 87, '2025-05-31 08:39:39', NULL, 'arrivé_matin', 'photo_1748677179.png'),
(320, 74, '2025-05-31 08:42:45', NULL, 'arrivé_matin', 'photo_1748677365.png'),
(321, 77, '2025-05-31 09:07:11', NULL, 'arrivé_matin', 'photo_1748678831.png'),
(322, 90, '2025-05-31 09:08:55', NULL, 'arrivé_matin', 'photo_1748678935.png'),
(323, 87, '2025-06-04 08:25:56', '2025-06-04 11:32:57', 'parti_matin', 'photo_1749021956.png'),
(324, 74, '2025-06-09 11:52:28', '2025-06-09 11:52:30', 'parti_matin', 'photo_1749466348.png'),
(325, 74, '2025-06-09 11:54:50', '2025-06-09 11:54:53', 'parti_soir', 'photo_1749466490.png'),
(326, 74, '2025-06-13 11:18:10', NULL, 'arrivé_matin', 'photo_1749809890.png'),
(327, 74, '2025-06-16 07:53:48', NULL, 'arrivé_matin', 'photo_1750056828.png'),
(328, 87, '2025-06-16 07:56:26', '2025-06-16 15:31:54', 'parti_matin', 'photo_1750056986.png'),
(329, 89, '2025-06-16 07:57:43', NULL, 'arrivé_matin', 'photo_1750057063.png'),
(330, 90, '2025-06-16 07:58:43', NULL, 'arrivé_matin', 'photo_1750057123.png'),
(331, 86, '2025-06-16 07:59:17', NULL, 'arrivé_matin', 'photo_1750057157.png'),
(334, 74, '2025-06-18 08:22:03', '2025-06-18 11:58:57', 'parti_matin', 'photo_1750231323.png'),
(335, 90, '2025-06-18 08:22:33', '2025-06-18 11:59:12', 'parti_matin', 'photo_1750231353.png'),
(336, 87, '2025-06-18 08:23:22', '2025-06-18 11:59:46', 'parti_matin', 'photo_1750231402.png'),
(337, 77, '2025-06-18 08:29:01', '2025-06-18 11:58:36', 'parti_matin', 'photo_1750231741.png'),
(338, 87, '2025-06-19 06:30:03', NULL, 'arrivé_matin', 'photo_1750311003.png'),
(339, 74, '2025-06-19 06:30:59', NULL, 'arrivé_matin', 'photo_1750311059.png'),
(340, 90, '2025-06-19 06:34:26', NULL, 'arrivé_matin', 'photo_1750311266.png'),
(341, 88, '2025-06-19 06:37:04', NULL, 'arrivé_matin', 'photo_1750311424.png'),
(342, 77, '2025-06-19 11:19:50', NULL, 'arrivé_matin', 'photo_1750328390.png'),
(343, 74, '2025-11-30 16:57:48', '2025-11-30 17:37:45', 'parti_matin', 'photo_1764518268.png'),
(344, 74, '2025-11-30 17:38:05', '2025-11-30 17:39:05', 'parti_soir', 'photo_1764520685.png');

-- --------------------------------------------------------

--
-- Structure de la table `soldeconge`
--

DROP TABLE IF EXISTS `soldeconge`;
CREATE TABLE IF NOT EXISTS `soldeconge` (
  `id` int NOT NULL AUTO_INCREMENT,
  `poste` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `solde` int NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `poste` (`poste`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `soldeconge`
--

INSERT INTO `soldeconge` (`id`, `poste`, `solde`) VALUES
(2, 'sales', 21),
(8, 'developpeur', 15);

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `conge`
--
ALTER TABLE `conge`
  ADD CONSTRAINT `conge_ibfk_1` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `demandeconge`
--
ALTER TABLE `demandeconge`
  ADD CONSTRAINT `fk_employe` FOREIGN KEY (`employe_id`) REFERENCES `employes` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `employes`
--
ALTER TABLE `employes`
  ADD CONSTRAINT `fk_poste` FOREIGN KEY (`poste`) REFERENCES `soldeconge` (`poste`) ON DELETE CASCADE;

--
-- Contraintes pour la table `horaire`
--
ALTER TABLE `horaire`
  ADD CONSTRAINT `horaire_ibfk_1` FOREIGN KEY (`id_periode`) REFERENCES `periode` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `messages`
--
ALTER TABLE `messages`
  ADD CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`id_expediteur`) REFERENCES `employes` (`id`),
  ADD CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`id_destinataire`) REFERENCES `employes` (`id`);

--
-- Contraintes pour la table `photos`
--
ALTER TABLE `photos`
  ADD CONSTRAINT `photos_ibfk_1` FOREIGN KEY (`pointage_id`) REFERENCES `pointages` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
