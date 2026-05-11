-- Database: `nutrismart`
-- Pour repartir de zéro sur les tables utilisateur / profil : exécuter ce fichier entier dans phpMyAdmin (base nutrismart).

CREATE DATABASE IF NOT EXISTS `nutrismart` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nutrismart`;

SET FOREIGN_KEY_CHECKS = 0;

-- Table dépendante en premier
DROP TABLE IF EXISTS `profil_nutritionnel`;
DROP TABLE IF EXISTS `utilisateur`;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
--
-- Table structure for table `utilisateur`
-- (aligné sur ton schéma + colonnes inscription : verification_token, is_verified)
--
CREATE TABLE `utilisateur` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'utilisateur',
  `verification_token` varchar(64) DEFAULT NULL,
  `is_verified` tinyint(1) NOT NULL DEFAULT 0,
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  `facial_descriptor` text DEFAULT NULL,
  `genre` varchar(50) DEFAULT NULL,
  `blocked_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
--
-- Table structure for table `profil_nutritionnel`
--
CREATE TABLE `profil_nutritionnel` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL,
  `age` smallint(5) UNSIGNED NOT NULL,
  `poids` decimal(5,2) NOT NULL COMMENT 'kg',
  `taille` decimal(5,2) NOT NULL COMMENT 'cm',
  `objectifs` varchar(500) DEFAULT NULL,
  `preferences_alimentaires` text DEFAULT NULL,
  PRIMARY KEY (`id_utilisateur`),
  CONSTRAINT `fk_profil_utilisateur` FOREIGN KEY (`id_utilisateur`) REFERENCES `utilisateur` (`id_utilisateur`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Données : admin démo (mot de passe : password)
--
INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `email`, `mot_de_passe`, `role`, `is_verified`) VALUES
(1, 'Admin Principal', 'admin@nutrismart.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 1);

--
-- Compte test : tradzeineb1@gmail.com / 552005
--
INSERT INTO `utilisateur` (`nom`, `email`, `mot_de_passe`, `role`, `is_verified`, `verification_token`)
VALUES (
  'Trad Zeineb',
  'tradzeineb1@gmail.com',
  '$2y$10$qgYjel83V1JWP.Ci1Hlbg.G6Av1P0fA0J8qp.f9HRSDBJgf/3d4xq',
  'utilisateur',
  1,
  NULL
);
