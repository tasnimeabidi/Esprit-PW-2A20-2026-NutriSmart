-- ============================================================
-- Migration : MCD PlanRepas (1) — (N) ProgrammeSportif
-- À exécuter sur une base « nutrismart » DÉJÀ installée (sans repasser par DROP DATABASE).
-- Supprime la contrainte « un seul programme par plan » pour coller au schéma pédagogique 1—N.
-- ============================================================

USE nutrismart;

ALTER TABLE programme_sportif
  DROP INDEX uq_programme_un_plan;

ALTER TABLE programme_sportif
  ADD KEY idx_programme_sportif_plan (id_plan);
