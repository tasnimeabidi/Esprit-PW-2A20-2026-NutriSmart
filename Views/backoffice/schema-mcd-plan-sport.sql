-- Extrait MCD (plan repas + sport uniquement).
-- Schéma complet NutriSmart (utilisateur, profil, recette, FK) : ../../database/nutrismart.sql

CREATE TABLE plan_repas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL,
  objectif VARCHAR(255) NOT NULL DEFAULT '',
  statut VARCHAR(64) NOT NULL DEFAULT 'brouillon',
  PRIMARY KEY (id),
  KEY idx_plan_repas_utilisateur (id_utilisateur)
);

CREATE TABLE repas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_plan INT UNSIGNED NOT NULL,
  id_recette INT UNSIGNED NULL,
  type VARCHAR(64) NOT NULL,
  calories INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_repas_plan (id_plan),
  CONSTRAINT fk_repas_plan FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE programme_sportif (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_plan INT UNSIGNED NOT NULL,
  type_sport VARCHAR(128) NOT NULL DEFAULT '',
  niveau VARCHAR(64) NOT NULL DEFAULT '',
  intensite VARCHAR(64) NOT NULL DEFAULT '',
  date_seance DATE NOT NULL,
  duree_min SMALLINT UNSIGNED NOT NULL,
  statut VARCHAR(64) NOT NULL DEFAULT 'prevue',
  PRIMARY KEY (id),
  KEY idx_programme_sportif_plan (id_plan),
  CONSTRAINT fk_programme_plan FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE
);
