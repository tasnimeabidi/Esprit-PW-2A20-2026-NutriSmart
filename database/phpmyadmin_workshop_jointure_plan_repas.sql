-- ============================================================
-- Workshop jointure (année 2025-2026) — import phpMyAdmin
--
-- Équivalence avec le PDF « Genre / Album » :
--   • Table « parent » (comme Genre)     → plan_repas (clé primaire : id)
--   • Table « enfant » (comme Album)   → repas (clé étrangère : id_plan)
--   • Liaison (vue relationnelle PDF)  : repas.id_plan → plan_repas.id
--
-- Dépendances : repas référence aussi recette (id_recette optionnel) et
-- plan_repas référence utilisateur. Ce script crée le minimum nécessaire.
--
-- Utilisation dans phpMyAdmin : Importer ce fichier, ou copier-coller dans SQL.
-- Ne remplace pas nutrismart.sql complet : ici uniquement l’atelier jointure.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE IF NOT EXISTS nutrismart
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE nutrismart;

-- ------------------------------------------------------------
-- Utilisateur (requis par plan_repas.id_utilisateur)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS utilisateur (
  id_utilisateur INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(120) NOT NULL,
  email VARCHAR(255) NOT NULL,
  mot_de_passe VARCHAR(255) NOT NULL,
  role VARCHAR(50) NOT NULL DEFAULT 'utilisateur',
  PRIMARY KEY (id_utilisateur),
  UNIQUE KEY uk_utilisateur_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Recette (reprise par repas.id_recette, peut être NULL)
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS recette (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nom VARCHAR(255) NOT NULL COMMENT 'MCD : nom de la recette',
  instructions TEXT NULL,
  calories_totales INT UNSIGNED NULL COMMENT 'MCD : calories totales (kcal)',
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Plan repas — table « parent » (équivalent Genre)
-- ------------------------------------------------------------
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

-- ------------------------------------------------------------
-- Repas — table « enfant » (équivalent Album) + contrainte de jointure
-- repas.id_plan référence plan_repas.id (comme album.idGenre → genre.idGenre)
-- ------------------------------------------------------------
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

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- Données de démo (idempotentes : un compte dédié à l’atelier)
-- Mot de passe bcrypt : "password" (identique au fichier nutrismart.sql démo)
-- ------------------------------------------------------------
INSERT INTO utilisateur (nom, email, mot_de_passe, role)
SELECT
  'Atelier Jointure',
  'atelier.jointure@nutrismart.local',
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'utilisateur'
WHERE NOT EXISTS (
  SELECT 1 FROM utilisateur WHERE email = 'atelier.jointure@nutrismart.local'
);

SET @uid_atelier = (
  SELECT id_utilisateur FROM utilisateur
  WHERE email = 'atelier.jointure@nutrismart.local'
  LIMIT 1
);

INSERT INTO recette (nom, instructions, calories_totales)
SELECT 'Salade composée', 'Légumes, protéine, assaisonnement.', 380
WHERE NOT EXISTS (SELECT 1 FROM recette WHERE nom = 'Salade composée' LIMIT 1);

SET @rid_salade = (SELECT id FROM recette WHERE nom = 'Salade composée' LIMIT 1);

INSERT INTO plan_repas (id_utilisateur, date_debut, date_fin, objectif, statut)
SELECT @uid_atelier, '2026-04-01', '2026-04-30', 'Équilibre alimentaire', 'actif'
WHERE NOT EXISTS (
  SELECT 1 FROM plan_repas pr
  INNER JOIN utilisateur u ON u.id_utilisateur = pr.id_utilisateur
  WHERE u.email = 'atelier.jointure@nutrismart.local'
    AND pr.objectif = 'Équilibre alimentaire'
  LIMIT 1
);

SET @pid_atelier = (
  SELECT pr.id FROM plan_repas pr
  INNER JOIN utilisateur u ON u.id_utilisateur = pr.id_utilisateur
  WHERE u.email = 'atelier.jointure@nutrismart.local'
  ORDER BY pr.id DESC
  LIMIT 1
);

INSERT INTO repas (id_plan, id_recette, type, calories)
SELECT @pid_atelier, @rid_salade, 'Déjeuner', 380
WHERE @pid_atelier IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM repas WHERE id_plan = @pid_atelier AND type = 'Déjeuner' LIMIT 1
  );

INSERT INTO repas (id_plan, id_recette, type, calories)
SELECT @pid_atelier, NULL, 'Collation', 120
WHERE @pid_atelier IS NOT NULL
  AND NOT EXISTS (
    SELECT 1 FROM repas WHERE id_plan = @pid_atelier AND type = 'Collation' LIMIT 1
  );

-- Vérification rapide (optionnel : décommenter)
-- SELECT r.id, r.type, r.calories, p.objectif, p.date_debut, p.date_fin
-- FROM repas r INNER JOIN plan_repas p ON p.id = r.id_plan;
