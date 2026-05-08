-- Database: `nutrismart`

CREATE DATABASE IF NOT EXISTS `nutrismart` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `nutrismart`;

-- --------------------------------------------------------

--
-- Table structure for table `utilisateur`
--

CREATE TABLE `utilisateur` (
  `id_utilisateur` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `nom` varchar(120) NOT NULL,
  `email` varchar(255) NOT NULL,
  `mot_de_passe` varchar(255) NOT NULL,
  `role` varchar(50) NOT NULL DEFAULT 'utilisateur',
  `is_blocked` tinyint(1) NOT NULL DEFAULT 0,
  `login_attempts` int(11) NOT NULL DEFAULT 0,
  `reset_token` varchar(64) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `oauth_provider` varchar(50) DEFAULT NULL,
  `oauth_id` varchar(255) DEFAULT NULL,
  `facial_descriptor` text DEFAULT NULL,
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
-- Dumping data for table `utilisateur` (Admin account)
--
INSERT INTO `utilisateur` (`id_utilisateur`, `nom`, `email`, `mot_de_passe`, `role`) VALUES
(1, 'Admin Principal', 'admin@nutrismart.demo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin');
-- password is 'password'
