-- ============================================================
-- Schéma complet de référence : nutrismart.sql (13 tables).
-- NutriSmart — MCD : Recette, Aliment, Recette-Aliment,
--                    JournalNutrition, JournalSport
-- Exécuter dans phpMyAdmin avec la base « nutrismart » sélectionnée.
-- Ne modifie pas plan_repas ni repas.
-- Prérequis : table utilisateur (pour les clés étrangères des journaux).
-- Les tables déjà présentes sont ignorées (CREATE IF NOT EXISTS).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- USE nutrismart;

-- MCD Recette : id-recette, nom, calories-totales, instructions
CREATE TABLE IF NOT EXISTS recette (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL COMMENT 'MCD : nom',
  instructions TEXT NULL,
  calories_totales INT UNSIGNED NULL COMMENT 'MCD : calories totales (kcal)',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MCD Aliment : nom, categorie, calories, proteines, lipides, Prix (+ glucides utiles)
CREATE TABLE IF NOT EXISTS aliment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL,
  categorie VARCHAR(32) NOT NULL DEFAULT 'autre',
  calories_100g DECIMAL(8,2) NULL COMMENT 'MCD : calories (kcal/100 g)',
  proteines_100g DECIMAL(8,2) NULL COMMENT 'MCD : proteines (g/100 g)',
  glucides_100g DECIMAL(8,2) NULL,
  lipides_100g DECIMAL(8,2) NULL COMMENT 'MCD : lipides (g/100 g)',
  prix DECIMAL(10,2) NULL COMMENT 'MCD : Prix',
  PRIMARY KEY (id),
  KEY idx_aliment_nom (nom),
  KEY idx_aliment_categorie (categorie)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MCD Recette-Aliment : id-recette, id-aliment, Quantite
CREATE TABLE IF NOT EXISTS recette_aliment (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_recette INT UNSIGNED NOT NULL,
  id_aliment INT UNSIGNED NOT NULL,
  quantite_g DECIMAL(10,2) NOT NULL COMMENT 'MCD : Quantite (g)',
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

-- MCD JournalNutrition
CREATE TABLE IF NOT EXISTS journal_nutrition (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  id_aliment INT UNSIGNED NOT NULL,
  date_entree DATE NOT NULL,
  calories INT UNSIGNED NULL,
  quantite DECIMAL(10,2) NOT NULL,
  PRIMARY KEY (id),
  KEY idx_jn_utilisateur_date (id_utilisateur, date_entree),
  KEY idx_jn_aliment (id_aliment),
  CONSTRAINT fk_jn_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_jn_aliment
    FOREIGN KEY (id_aliment) REFERENCES aliment (id)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MCD JournalSport (lié à utilisateur ; pas de FK vers journal_nutrition)
CREATE TABLE IF NOT EXISTS journal_sport (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  date_seance DATE NOT NULL,
  type_sport VARCHAR(128) NOT NULL DEFAULT '',
  duree_min SMALLINT UNSIGNED NOT NULL,
  calories_depensees INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_js_utilisateur_date (id_utilisateur, date_seance),
  CONSTRAINT fk_js_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
