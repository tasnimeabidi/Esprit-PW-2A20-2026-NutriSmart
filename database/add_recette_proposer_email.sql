-- Optionnel : exécuter une fois dans phpMyAdmin si la colonne n’existe pas encore.
-- Permet d’envoyer la notification même si user_id n’a pas été enregistré (anciennes lignes).

ALTER TABLE `recette`
  ADD COLUMN `proposer_email` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Email saisi à la proposition' AFTER `user_name`;
