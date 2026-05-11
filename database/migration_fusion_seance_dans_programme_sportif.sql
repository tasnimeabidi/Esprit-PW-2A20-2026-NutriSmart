-- ============================================================
-- Migration : fusion de seance_sport dans programme_sportif
-- Contexte : une ligne programme_sportif = fiche sport + date / durée / statut de séance.
-- À exécuter sur une base nutrismart existante (phpMyAdmin : importer ce fichier).
-- Ne pas utiliser sur une base déjà migrée (colonnes déjà présentes → erreur ALTER).
-- ============================================================

USE nutrismart;

SET FOREIGN_KEY_CHECKS = 0;

-- 1. Nouvelles colonnes (nullable le temps de recopier les données)
ALTER TABLE programme_sportif
  ADD COLUMN date_seance DATE NULL AFTER intensite,
  ADD COLUMN duree_min SMALLINT UNSIGNED NULL AFTER date_seance,
  ADD COLUMN statut VARCHAR(64) NOT NULL DEFAULT 'prevue' AFTER duree_min;

-- 2. Identifiants des programmes qui avaient au moins une séance (lignes « parents » à retirer après copie)
CREATE TEMPORARY TABLE tmp_prog_avec_seance AS
SELECT DISTINCT id_programme AS id FROM seance_sport;

-- 3. Une ligne programme_sportif par ancienne séance (même plan, type, niveau, intensité)
INSERT INTO programme_sportif (id_plan, type_sport, niveau, intensite, date_seance, duree_min, statut)
SELECT p.id_plan, p.type_sport, p.niveau, p.intensite, s.date_seance, s.duree_min, s.statut
FROM programme_sportif p
INNER JOIN seance_sport s ON s.id_programme = p.id;

DROP TABLE IF EXISTS seance_sport;

DELETE p FROM programme_sportif p
INNER JOIN tmp_prog_avec_seance t ON p.id = t.id;

DROP TEMPORARY TABLE tmp_prog_avec_seance;

-- 4. Programmes sans ancienne séance : valeurs par défaut pour les colonnes encore NULL
UPDATE programme_sportif
SET
  date_seance = COALESCE(date_seance, CURDATE()),
  duree_min = COALESCE(duree_min, 30),
  statut = CASE
    WHEN statut IS NULL OR TRIM(statut) = '' THEN 'prevue'
    ELSE statut
  END
WHERE date_seance IS NULL OR duree_min IS NULL;

-- 5. Contraintes finales
ALTER TABLE programme_sportif
  MODIFY date_seance DATE NOT NULL,
  MODIFY duree_min SMALLINT UNSIGNED NOT NULL,
  MODIFY statut VARCHAR(64) NOT NULL DEFAULT 'prevue';

SET FOREIGN_KEY_CHECKS = 1;
