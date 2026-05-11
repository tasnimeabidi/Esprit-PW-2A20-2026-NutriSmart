-- ============================================================
-- NutriSmart — schéma MySQL / MariaDB (phpMyAdmin, XAMPP)
--
-- RÉFÉRENCE CANONIQUE : ce fichier définit TOUTES les tables de la base
-- « nutrismart ». Pour une nouvelle installation : l’importer tel quel
-- (la base existante est supprimée puis recréée — voir DROP DATABASE ci‑dessous).
--
-- INVENTAIRE DES TABLES (12)
--   recette              — id, nom, instructions, calories_totales
--   aliment              — id, nom, categorie, macros /100g, glucides, prix
--   recette_aliment      — liaison N—N recette ⟷ aliment (quantite_g)
--   utilisateur          — id_utilisateur, nom, email, mot_de_passe, role
--   profil_nutritionnel  — 1—1 utilisateur : age, poids, taille, objectifs, préférences
--   plan_repas           — période, objectif, statut (FK utilisateur)
--   repas                — type, calories (FK plan_repas, recette optionnelle)
--   programme_sportif    — N—1 avec plan_repas (fiche sport + date_seance, duree_min, statut)
--   journal_nutrition    — suivi consommation (FK utilisateur, aliment)
--   journal_sport        — suivi activité (FK utilisateur)
--   publication          — fil social : titre, contenu, image, date (FK utilisateur)
--   commentaire          — commentaires sur une publication (FK publication, utilisateur)
--
--   • migration_fusion_seance_dans_programme_sportif.sql — base existante : supprime seance_sport, étend programme_sportif
--
-- AUTRES FICHIERS dans database/ : migrations ou compléments (base déjà en place)
--   • mcd_tables_recette_aliment_journaux.sql — CREATE IF NOT EXISTS
--   • migration_phpmyadmin_ajout_suivi_aliments.sql — ajout aliment + journaux
--   • migration_aliments_recette.sql — étape legacy (recette enrichie + aliment)
--   • migration_journaux.sql — étape legacy (journaux + colonne prix)
--   • migration_recette_titre_vers_nom.sql — si ancienne colonne « titre » sur recette
--   • migration_publication_commentaire.sql — ajout publication + commentaire
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP DATABASE IF EXISTS nutrismart;
CREATE DATABASE nutrismart
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nutrismart;

-- ------------------------------------------------------------
-- Recettes (référencées par repas.id_recette)
-- ------------------------------------------------------------
CREATE TABLE recette (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL COMMENT 'MCD : nom de la recette',
  instructions TEXT NULL,
  calories_totales INT UNSIGNED NULL COMMENT 'MCD : calories totales (kcal)',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Aliments (base nutritionnelle, liaison N—N avec recette)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Ingrédients d'une recette (quantité en grammes)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Utilisateurs
-- ------------------------------------------------------------
CREATE TABLE utilisateur (
  id_utilisateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'utilisateur',
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uk_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Profil nutritionnel (1:1 avec utilisateur)
-- ------------------------------------------------------------
CREATE TABLE profil_nutritionnel (
  id_utilisateur INT UNSIGNED NOT NULL,
  age SMALLINT UNSIGNED NOT NULL,
  poids DECIMAL(5,2) NOT NULL COMMENT 'kg',
  taille DECIMAL(5,2) NOT NULL COMMENT 'cm',
  objectifs VARCHAR(500) DEFAULT NULL,
  preferences_alimentaires TEXT DEFAULT NULL,
  ai_recommendations LONGTEXT NULL DEFAULT NULL COMMENT 'cache JSON recommandations IA Conseil',
  ai_last_generated DATETIME NULL DEFAULT NULL COMMENT 'dernière génération IA',
  PRIMARY KEY (id_utilisateur),
  CONSTRAINT fk_profil_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Plan repas (MCD NutriSmart)
-- ------------------------------------------------------------
CREATE TABLE plan_repas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  date_debut DATE NOT NULL,
  date_fin DATE NOT NULL,
  objectif VARCHAR(255) NOT NULL DEFAULT '',
  statut VARCHAR(64) NOT NULL DEFAULT 'brouillon',
  PRIMARY KEY (id),
  KEY idx_plan_repas_utilisateur (id_utilisateur),
  CONSTRAINT fk_plan_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Repas
-- ------------------------------------------------------------
CREATE TABLE repas (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_plan INT UNSIGNED NOT NULL,
  id_recette INT UNSIGNED NULL,
  type VARCHAR(64) NOT NULL,
  calories INT UNSIGNED NULL,
  PRIMARY KEY (id),
  KEY idx_repas_plan (id_plan),
  CONSTRAINT fk_repas_plan
    FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_repas_recette
    FOREIGN KEY (id_recette) REFERENCES recette (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Programme sportif (N:1 avec plan_repas — plusieurs lignes possibles pour un même plan)
-- ------------------------------------------------------------
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
  CONSTRAINT fk_programme_plan
    FOREIGN KEY (id_plan) REFERENCES plan_repas (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Journal nutrition (suivi consommation par utilisateur)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Journal sport (activités par utilisateur)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Publications & commentaires (fil social — CRUD Nassim / équipe)
-- Colonnes PK alignées sur le module ProjetNutrismart (id_publication, id_commentaire).
-- ------------------------------------------------------------
CREATE TABLE publication (
  id_publication INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  titre VARCHAR(255) NOT NULL DEFAULT '',
  contenu TEXT NULL,
  image VARCHAR(255) NULL COMMENT 'chemin ou URL image',
  date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_publication),
  KEY idx_publication_utilisateur (id_utilisateur),
  KEY idx_publication_date (date_publication),
  CONSTRAINT fk_publication_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commentaire (
  id_commentaire INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_publication INT UNSIGNED NOT NULL,
  id_utilisateur INT UNSIGNED NOT NULL,
  contenu TEXT NOT NULL,
  date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_commentaire),
  KEY idx_commentaire_publication (id_publication),
  KEY idx_commentaire_utilisateur (id_utilisateur),
  CONSTRAINT fk_commentaire_publication
    FOREIGN KEY (id_publication) REFERENCES publication (id_publication)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_commentaire_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Réactions & notifications (module blog Nassim)
CREATE TABLE reaction (
  id_reaction INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  id_publication INT UNSIGNED NULL,
  id_commentaire INT UNSIGNED NULL,
  type_reaction VARCHAR(20) NOT NULL,
  date_reaction DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_reaction),
  KEY idx_reaction_user (id_utilisateur),
  KEY idx_reaction_post (id_publication),
  KEY idx_reaction_comment (id_commentaire),
  CONSTRAINT fk_reaction_user
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reaction_publication
    FOREIGN KEY (id_publication) REFERENCES publication (id_publication)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_reaction_commentaire
    FOREIGN KEY (id_commentaire) REFERENCES commentaire (id_commentaire)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE notification (
  id_notification INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisatuer INT UNSIGNED NOT NULL COMMENT 'orthographe conservée (code Nassim)',
  message TEXT NOT NULL,
  is_read TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  date_notification DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_notification),
  KEY idx_notification_user (id_utilisatuer),
  CONSTRAINT fk_notification_user
    FOREIGN KEY (id_utilisatuer) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Données de démo (compte + plan + repas + sport)
-- Mot de passe bcrypt : "password" (à changer en production)
-- ------------------------------------------------------------
INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES
('Démo NutriSmart', 'demo@nutrismart.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
SET @uid = LAST_INSERT_ID();

INSERT INTO profil_nutritionnel (id_utilisateur, age, poids, taille, objectifs, preferences_alimentaires)
VALUES (@uid, 30, 72.50, 175.00, 'Équilibre alimentaire', 'Sans porc');

INSERT INTO recette (nom, instructions, calories_totales)
VALUES ('Petit-déjeuner équilibré', 'Flocons d''avoine au lait, fruit.', 420);
SET @rid = LAST_INSERT_ID();

INSERT INTO aliment (nom, categorie, calories_100g, proteines_100g, glucides_100g, lipides_100g, prix)
VALUES ('Flocons d''avoine', 'feculent', 389.00, 16.90, 66.30, 6.90, 2.49);
SET @aid = LAST_INSERT_ID();

INSERT INTO recette_aliment (id_recette, id_aliment, quantite_g) VALUES (@rid, @aid, 60.00);

INSERT INTO plan_repas (id_utilisateur, date_debut, date_fin, objectif, statut)
VALUES (@uid, '2026-04-01', '2026-04-30', 'Perte de poids', 'actif');
SET @pid = LAST_INSERT_ID();

INSERT INTO repas (id_plan, id_recette, type, calories) VALUES (@pid, @rid, 'Petit-déjeuner', 420);

INSERT INTO programme_sportif (id_plan, type_sport, niveau, intensite, date_seance, duree_min, statut)
VALUES (@pid, 'Cardio', 'intermédiaire', 'modérée', '2026-04-09', 45, 'prevue');

INSERT INTO journal_nutrition (id_utilisateur, id_aliment, date_entree, calories, quantite)
VALUES (@uid, @aid, '2026-04-10', 234, 60.00);

INSERT INTO journal_sport (id_utilisateur, date_seance, type_sport, duree_min, calories_depensees)
VALUES (@uid, '2026-04-10', 'Course', 30, 280);

INSERT INTO publication (id_utilisateur, titre, contenu, image)
VALUES (@uid, 'Bienvenue sur NutriSmart', 'Première publication de démonstration.', NULL);
SET @pubid = LAST_INSERT_ID();

INSERT INTO commentaire (id_publication, id_utilisateur, contenu)
VALUES (@pubid, @uid, 'Premier commentaire de démo.');

SET FOREIGN_KEY_CHECKS = 1;
