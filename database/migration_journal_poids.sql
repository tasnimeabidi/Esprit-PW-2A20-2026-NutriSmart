-- NutriSmart — table journal_poids (suivi statistiques / SuiviDAO)
-- À exécuter sur la base `nutrismart` si la page suivi-statistiques.php signale l'absence de cette table.

USE nutrismart;

CREATE TABLE IF NOT EXISTS journal_poids (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  poids DECIMAL(6,2) NOT NULL COMMENT 'kg',
  date_mesure DATE NOT NULL,
  id_sport INT UNSIGNED NULL COMMENT 'optionnel — lien dernier sport',
  id_nutrition INT UNSIGNED NULL COMMENT 'optionnel — lien dernier repas',
  PRIMARY KEY (id),
  KEY idx_jp_utilisateur_date (id_utilisateur, date_mesure),
  CONSTRAINT fk_jp_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_jp_sport
    FOREIGN KEY (id_sport) REFERENCES journal_sport (id)
    ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_jp_nutrition
    FOREIGN KEY (id_nutrition) REFERENCES journal_nutrition (id)
    ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
