-- ============================================================
-- Partie 1 du PDF (phpMyAdmin) — base « nutrismart », jointures MCD NutriSmart
-- Équivalence cours : Genre → plan_repas, Album → repas (+ programme_sportif)
-- Importer ce fichier dans phpMyAdmin (onglet Importer).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS nutrismart
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nutrismart;

CREATE TABLE IF NOT EXISTS utilisateur (
  id_utilisateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'utilisateur',
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uk_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recette (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL,
  instructions TEXT NULL,
  calories_totales INT UNSIGNED NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS plan_repas (
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

CREATE TABLE IF NOT EXISTS repas (
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

CREATE TABLE IF NOT EXISTS programme_sportif (
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

SET FOREIGN_KEY_CHECKS = 1;

-- Données de test (compte atelier)
INSERT INTO utilisateur (nom, email, mot_de_passe, role)
SELECT 'Workshop NutriSmart', 'workshop@nutrismart.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'utilisateur'
WHERE NOT EXISTS (SELECT 1 FROM utilisateur WHERE email = 'workshop@nutrismart.local');

SET @u = (SELECT id_utilisateur FROM utilisateur WHERE email = 'workshop@nutrismart.local' LIMIT 1);

INSERT INTO recette (nom, instructions, calories_totales)
SELECT 'Démo', 'Instructions.', 300
WHERE NOT EXISTS (SELECT 1 FROM recette WHERE nom = 'Démo' LIMIT 1);

SET @r = (SELECT id FROM recette WHERE nom = 'Démo' LIMIT 1);

INSERT INTO plan_repas (id_utilisateur, date_debut, date_fin, objectif, statut)
SELECT @u, '2026-04-01', '2026-04-30', 'Équilibre', 'actif'
WHERE NOT EXISTS (
  SELECT 1 FROM plan_repas pr JOIN utilisateur u ON u.id_utilisateur = pr.id_utilisateur
  WHERE u.email = 'workshop@nutrismart.local' AND pr.objectif = 'Équilibre' LIMIT 1
);

SET @p = (
  SELECT pr.id FROM plan_repas pr
  JOIN utilisateur u ON u.id_utilisateur = pr.id_utilisateur
  WHERE u.email = 'workshop@nutrismart.local'
  ORDER BY pr.id DESC LIMIT 1
);

INSERT INTO repas (id_plan, id_recette, type, calories)
SELECT @p, @r, 'Déjeuner', 500
WHERE @p IS NOT NULL AND NOT EXISTS (SELECT 1 FROM repas WHERE id_plan = @p AND type = 'Déjeuner' LIMIT 1);

INSERT INTO programme_sportif (id_plan, type_sport, niveau, intensite, date_seance, duree_min, statut)
SELECT @p, 'Course', 'débutant', 'légère', '2026-04-15', 30, 'prevue'
WHERE @p IS NOT NULL AND NOT EXISTS (
  SELECT 1 FROM programme_sportif WHERE id_plan = @p AND date_seance = '2026-04-15' LIMIT 1
);
