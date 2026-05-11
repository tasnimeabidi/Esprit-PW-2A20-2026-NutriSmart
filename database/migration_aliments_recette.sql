-- ============================================================
-- Schéma complet de référence : nutrismart.sql (13 tables).
-- NutriSmart — migration : aliments, recette enrichie, liaison
-- À exécuter sur une base DÉJÀ créée sans ces tables/colonnes.
-- Ne pas lancer après import complet de nutrismart.sql (déjà inclus).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Décommentez si la session n’a pas de base sélectionnée :
-- USE nutrismart;

-- Si MySQL renvoie "Duplicate column name", supprimez ce bloc ALTER
-- (les colonnes existent déjà).
ALTER TABLE recette
  ADD COLUMN instructions TEXT NULL AFTER titre,
  ADD COLUMN calories_totales INT UNSIGNED NULL COMMENT 'kcal pour la recette complète' AFTER instructions;

CREATE TABLE aliment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL,
  categorie VARCHAR(32) NOT NULL DEFAULT 'autre',
  calories_100g DECIMAL(8,2) NULL COMMENT 'kcal / 100 g',
  proteines_100g DECIMAL(8,2) NULL COMMENT 'g / 100 g',
  glucides_100g DECIMAL(8,2) NULL,
  lipides_100g DECIMAL(8,2) NULL,
  prix DECIMAL(10,2) NULL COMMENT 'prix unitaire (référence)',
  PRIMARY KEY (id),
  KEY idx_aliment_nom (nom),
  KEY idx_aliment_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE recette_aliment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_recette INT UNSIGNED NOT NULL,
  id_aliment INT UNSIGNED NOT NULL,
  quantite_g DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_recette_aliment (id_recette, id_aliment),
  KEY idx_ra_recette (id_recette),
  KEY idx_ra_aliment (id_aliment),
  CONSTRAINT fk_ra_recette
    FOREIGN KEY (id_recette) REFERENCES recette (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_ra_aliment
    FOREIGN KEY (id_aliment) REFERENCES aliment (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
