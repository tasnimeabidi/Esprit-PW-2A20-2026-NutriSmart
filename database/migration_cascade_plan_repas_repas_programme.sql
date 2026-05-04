-- NutriSmart
-- Migration: activer/normaliser la suppression en cascade
-- Modules concernés: plan_repas, repas, programme_sportif
--
-- Objectif:
--  - Supprimer un plan_repas => supprime automatiquement ses repas
--  - Supprimer un plan_repas => supprime automatiquement ses programme_sportif
--  - Supprimer un utilisateur => supprime automatiquement ses plan_repas (et donc leurs enfants)
--
-- Compatible MySQL 5.7+/8.x

USE nutrismart;

SET @schema_name := DATABASE();

-- Helper: supprime une FK si elle existe (sans erreur si absente)
DROP PROCEDURE IF EXISTS drop_fk_if_exists;
DELIMITER $$
CREATE PROCEDURE drop_fk_if_exists(IN p_table VARCHAR(64), IN p_fk VARCHAR(64))
BEGIN
    IF EXISTS (
        SELECT 1
        FROM information_schema.REFERENTIAL_CONSTRAINTS rc
        WHERE rc.CONSTRAINT_SCHEMA = @schema_name
          AND rc.TABLE_NAME = p_table
          AND rc.CONSTRAINT_NAME = p_fk
    ) THEN
        SET @sql := CONCAT('ALTER TABLE `', p_table, '` DROP FOREIGN KEY `', p_fk, '`');
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$
DELIMITER ;

-- 1) Nettoyage des anciennes FK (si déjà présentes)
CALL drop_fk_if_exists('plan_repas', 'fk_plan_utilisateur');
CALL drop_fk_if_exists('repas', 'fk_repas_plan');
CALL drop_fk_if_exists('programme_sportif', 'fk_programme_plan');

-- 2) Recréation des FK en ON DELETE CASCADE
ALTER TABLE plan_repas
    ADD CONSTRAINT fk_plan_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE repas
    ADD CONSTRAINT fk_repas_plan
    FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE programme_sportif
    ADD CONSTRAINT fk_programme_plan
    FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE;

-- 3) Nettoyage helper
DROP PROCEDURE IF EXISTS drop_fk_if_exists;
