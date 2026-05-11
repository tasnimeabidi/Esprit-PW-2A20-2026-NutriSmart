-- ============================================================
-- Schéma complet de référence : nutrismart.sql (13 tables).
-- Ajout : publication, commentaire (base nutrismart existante).
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- USE nutrismart;

CREATE TABLE publication (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  titre VARCHAR(255) NOT NULL DEFAULT '',
  contenu TEXT NULL,
  image VARCHAR(255) NULL COMMENT 'chemin ou URL image',
  date_publication DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_publication_utilisateur (id_utilisateur),
  KEY idx_publication_date (date_publication),
  CONSTRAINT fk_publication_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE commentaire (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_publication INT UNSIGNED NOT NULL,
  id_utilisateur INT UNSIGNED NOT NULL,
  contenu TEXT NOT NULL,
  date_commentaire DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_commentaire_publication (id_publication),
  KEY idx_commentaire_utilisateur (id_utilisateur),
  CONSTRAINT fk_commentaire_publication
    FOREIGN KEY (id_publication) REFERENCES publication (id)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_commentaire_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
