-- ============================================================
-- Schéma complet de référence : nutrismart.sql (13 tables).
-- NutriSmart — migration : journal_nutrition, journal_sport, prix (aliment)
-- À exécuter sur une base existante sans ces objets.
-- Inutile après import complet de nutrismart.sql à jour.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- USE nutrismart;

-- Si "Duplicate column name 'prix'" : supprimez ce ALTER.
ALTER TABLE aliment
  ADD COLUMN prix DECIMAL(10,2) NULL COMMENT 'prix unitaire (référence)' AFTER lipides_100g;

CREATE TABLE journal_nutrition (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  id_aliment INT UNSIGNED NOT NULL,
  date_entree DATE NOT NULL,
  calories INT UNSIGNED NULL COMMENT 'kcal pour cette entrée',
  quantite DECIMAL(10,2) NOT NULL COMMENT 'quantité consommée (ex. g)',
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

CREATE TABLE journal_sport (
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
