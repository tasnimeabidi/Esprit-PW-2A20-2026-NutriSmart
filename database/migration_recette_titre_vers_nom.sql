-- Schéma complet de référence : nutrismart.sql (13 tables).
-- Renomme « titre » → « nom » pour coller au MCD (recette).
-- À exécuter une seule fois si la colonne s’appelle encore « titre ».
-- Erreur « Unknown column titre » : vous êtes déjà à jour (colonne « nom »).

ALTER TABLE recette
  CHANGE COLUMN titre nom VARCHAR(255) NOT NULL COMMENT 'MCD : nom de la recette';
