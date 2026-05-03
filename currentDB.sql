-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : lun. 13 avr. 2026 à 18:56
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `nutrismart`
--

-- --------------------------------------------------------

--
-- Structure de la table `aliment`
--

CREATE TABLE `aliment` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `categorie` varchar(32) NOT NULL DEFAULT 'autre',
  `calories_100g` decimal(8,2) DEFAULT NULL COMMENT 'kcal / 100 g',
  `proteines_100g` decimal(8,2) DEFAULT NULL COMMENT 'g / 100 g',
  `glucides_100g` decimal(8,2) DEFAULT NULL,
  `lipides_100g` decimal(8,2) DEFAULT NULL,
  `prix` decimal(10,2) DEFAULT NULL COMMENT 'prix unitaire (référence)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `aliment`
--

INSERT INTO `aliment` (`id`, `nom`, `categorie`, `calories_100g`, `proteines_100g`, `glucides_100g`, `lipides_100g`, `prix`) VALUES
(1, 'Flocons d\'avoine', 'feculent', 389.00, 16.90, 66.30, 6.90, 2.49),
(2, 'Poitrine de Poulet', 'viande', 165.00, 31.00, 0.00, 3.60, 12.50),
(3, 'Riz Complet', 'feculent', 350.00, 7.50, 72.00, 2.70, 1.80),
(4, 'Œufs (6pcs)', 'proteine', 155.00, 13.00, 1.10, 11.00, 2.10),
(5, 'Avocat', 'legume', 160.00, 2.00, 8.50, 15.00, 1.50),
(6, 'Beurre d\'amande', 'autre', 614.00, 21.00, 19.00, 54.00, 8.90),
(7, 'Banane', 'fruit', 89.00, 1.10, 23.00, 0.30, 0.40);

-- --------------------------------------------------------

--
-- Structure de la table `budget`
--

CREATE TABLE `budget` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `montant` decimal(10,2) NOT NULL COMMENT 'The total budget limit',
  `date_creation` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `budget`
--

INSERT INTO `budget` (`id_utilisateur`, `montant`, `date_creation`) VALUES
(1, 450.00, '2026-04-13 16:22:19'),
(2, 90.00, '2026-04-13 16:51:52');

-- --------------------------------------------------------

--
-- Structure de la table `commentaire`
--

CREATE TABLE `commentaire` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_publication` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `contenu` text NOT NULL,
  `date_commentaire` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `commentaire`
--

INSERT INTO `commentaire` (`id`, `id_publication`, `id_utilisateur`, `contenu`, `date_commentaire`) VALUES
(1, 1, 1, 'Premier commentaire de démo.', '2026-04-12 16:58:41');

-- --------------------------------------------------------

--
-- Structure de la table `journal_nutrition`
--

CREATE TABLE `journal_nutrition` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `id_aliment` int(10) UNSIGNED NOT NULL,
  `date_entree` date NOT NULL,
  `calories` int(10) UNSIGNED DEFAULT NULL,
  `quantite` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_nutrition`
--

INSERT INTO `journal_nutrition` (`id`, `id_utilisateur`, `id_aliment`, `date_entree`, `calories`, `quantite`) VALUES
(1, 1, 1, '2026-04-10', 234, 60.00);

-- --------------------------------------------------------

--
-- Structure de la table `journal_sport`
--

CREATE TABLE `journal_sport` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `date_seance` date NOT NULL,
  `type_sport` varchar(128) NOT NULL DEFAULT '',
  `duree_min` smallint(5) UNSIGNED NOT NULL,
  `calories_depensees` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `journal_sport`
--

INSERT INTO `journal_sport` (`id`, `id_utilisateur`, `date_seance`, `type_sport`, `duree_min`, `calories_depensees`) VALUES
(1, 1, '2026-04-10', 'Course', 30, 280);

-- --------------------------------------------------------

--
-- Structure de la table `plan_repas`
--

CREATE TABLE `plan_repas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `date_debut` date NOT NULL,
  `date_fin` date NOT NULL,
  `objectif` varchar(255) NOT NULL DEFAULT '',
  `statut` varchar(64) NOT NULL DEFAULT 'brouillon'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `plan_repas`
--

INSERT INTO `plan_repas` (`id`, `id_utilisateur`, `date_debut`, `date_fin`, `objectif`, `statut`) VALUES
(1, 1, '2026-04-01', '2026-04-30', 'Perte de poids', 'actif');

-- --------------------------------------------------------

--
-- Structure de la table `profil_nutritionnel`
--

CREATE TABLE `profil_nutritionnel` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `age` smallint(5) UNSIGNED NOT NULL,
  `poids` decimal(5,2) NOT NULL,
  `taille` decimal(5,2) NOT NULL,
  `objectifs` varchar(500) DEFAULT NULL,
  `preferences_alimentaires` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `profil_nutritionnel`
--

INSERT INTO `profil_nutritionnel` (`id_utilisateur`, `age`, `poids`, `taille`, `objectifs`, `preferences_alimentaires`) VALUES
(1, 30, 72.50, 175.00, 'Équilibre alimentaire', 'Sans porc'),
(1, 25, 85.50, 182.00, 'Bodybuilding / Physique Goals', 'High Protein');

-- --------------------------------------------------------

--
-- Structure de la table `programme_sportif`
--

CREATE TABLE `programme_sportif` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_plan` int(10) UNSIGNED NOT NULL,
  `type_sport` varchar(128) NOT NULL DEFAULT '',
  `niveau` varchar(64) NOT NULL DEFAULT '',
  `intensite` varchar(64) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `programme_sportif`
--

INSERT INTO `programme_sportif` (`id`, `id_plan`, `type_sport`, `niveau`, `intensite`) VALUES
(1, 1, 'Cardio', 'intermédiaire', 'modérée');

-- --------------------------------------------------------

--
-- Structure de la table `publication`
--

CREATE TABLE `publication` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `titre` varchar(255) NOT NULL DEFAULT '',
  `contenu` text DEFAULT NULL,
  `image` varchar(255) DEFAULT NULL,
  `date_publication` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `publication`
--

INSERT INTO `publication` (`id`, `id_utilisateur`, `titre`, `contenu`, `image`, `date_publication`) VALUES
(1, 1, 'Bienvenue sur NutriSmart', 'Première publication de démonstration.', NULL, '2026-04-12 16:58:41');

-- --------------------------------------------------------

--
-- Structure de la table `recette`
--

CREATE TABLE `recette` (
  `id` int(10) UNSIGNED NOT NULL,
  `nom` varchar(255) NOT NULL,
  `instructions` text DEFAULT NULL,
  `calories_totales` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `recette`
--

INSERT INTO `recette` (`id`, `nom`, `instructions`, `calories_totales`) VALUES
(1, 'Petit-déjeuner équilibré', 'Flocons d\'avoine au lait, fruit.', 420);

-- --------------------------------------------------------

--
-- Structure de la table `recette_aliment`
--

CREATE TABLE `recette_aliment` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_recette` int(10) UNSIGNED NOT NULL,
  `id_aliment` int(10) UNSIGNED NOT NULL,
  `quantite_g` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `recette_aliment`
--

INSERT INTO `recette_aliment` (`id`, `id_recette`, `id_aliment`, `quantite_g`) VALUES
(1, 1, 1, 60.00);

-- --------------------------------------------------------

--
-- Structure de la table `repas`
--

CREATE TABLE `repas` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_plan` int(10) UNSIGNED NOT NULL,
  `id_recette` int(10) UNSIGNED DEFAULT NULL,
  `type` varchar(64) NOT NULL,
  `calories` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `repas`
--

INSERT INTO `repas` (`id`, `id_plan`, `id_recette`, `type`, `calories`) VALUES
(1, 1, 1, 'Petit-déjeuner', 420);

-- --------------------------------------------------------

--
-- Structure de la table `seance_sport`
--

CREATE TABLE `seance_sport` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_programme` int(10) UNSIGNED NOT NULL,
  `date_seance` date NOT NULL,
  `duree_min` smallint(5) UNSIGNED NOT NULL,
  `statut` varchar(64) NOT NULL DEFAULT 'prevue'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `seance_sport`
--

INSERT INTO `seance_sport` (`id`, `id_programme`, `date_seance`, `duree_min`, `statut`) VALUES
(1, 1, '2026-04-09', 45, 'prevue');

-- --------------------------------------------------------

--
-- Structure de la table `user_achat`
--

CREATE TABLE `user_achat` (
  `id` int(10) UNSIGNED NOT NULL,
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `id_aliment` int(10) UNSIGNED NOT NULL,
  `quantite` decimal(10,2) NOT NULL,
  `prix_total` decimal(10,2) NOT NULL,
  `date_achat` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `user_achat`
--

INSERT INTO `user_achat` (`id`, `id_utilisateur`, `id_aliment`, `quantite`, `prix_total`, `date_achat`) VALUES
(13, 1, 7, 1.00, 0.40, '2026-04-13 17:39:34'),
(14, 1, 4, 4.00, 8.40, '2026-04-13 17:40:06'),
(15, 1, 7, 4.00, 1.60, '2026-04-13 17:44:50'),
(16, 1, 6, 1.00, 8.90, '2026-04-13 17:51:50');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `nom` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'utilisateur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateur`
--

INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'Démo NutriSmart', 'demo@nutrismart.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
(2, 'User Overbudget', 'test@overbudget.com', 'password123', 'utilisateur');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `aliment`
--
ALTER TABLE `aliment`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `budget`
--
ALTER TABLE `budget`
  ADD PRIMARY KEY (`id_utilisateur`);

--
-- Index pour la table `user_achat`
--
ALTER TABLE `user_achat`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_utilisateur` (`id_utilisateur`),
  ADD KEY `id_aliment` (`id_aliment`);

--
-- Index pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  ADD PRIMARY KEY (`id_utilisateur`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `aliment`
--
ALTER TABLE `aliment`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT pour la table `user_achat`
--
ALTER TABLE `user_achat`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT pour la table `utilisateur`
--
ALTER TABLE `utilisateur`
  MODIFY `id_utilisateur` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `budget`
--
ALTER TABLE `budget`
  ADD CONSTRAINT `budget_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE;

--
-- Contraintes pour la table `user_achat`
--
ALTER TABLE `user_achat`
  ADD CONSTRAINT `user_achat_ibfk_1` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE,
  ADD CONSTRAINT `user_achat_ibfk_2` FOREIGN KEY (`id_aliment`) REFERENCES `aliment` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
