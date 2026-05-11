-- Aligner une ancienne table `recette` (sans video_url, status, etc.) sur le module recettes vues PHP.
-- Base : nutrismart — à exécuter une fois dans phpMyAdmin si vos colonnes manquent.

USE nutrismart;

ALTER TABLE `recette`
  ADD COLUMN `video_url` varchar(255) DEFAULT NULL AFTER `instructions`,
  ADD COLUMN `temps` int(11) DEFAULT NULL COMMENT 'minutes' AFTER `calories_totales`,
  ADD COLUMN `status` enum('pending','approved','rejected') NOT NULL DEFAULT 'approved' AFTER `temps`,
  ADD COLUMN `user_id` int(11) DEFAULT NULL AFTER `status`,
  ADD COLUMN `user_name` varchar(255) DEFAULT NULL AFTER `user_id`,
  ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `user_name`,
  ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
