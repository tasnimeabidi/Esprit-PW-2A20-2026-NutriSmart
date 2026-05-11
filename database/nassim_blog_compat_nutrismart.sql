-- =============================================================================
-- Compatibilité blog ProjetNutrismart (Nassim) sur une base `nutrismart` déjà
-- importée avec l’ancien schéma (PK `id` sur publication / commentaire).
-- À exécuter UNE FOIS dans phpMyAdmin sur la base `nutrismart`.
-- Si les colonnes s’appellent déjà id_publication / id_commentaire, ignorez les ALTER.
-- =============================================================================

SET FOREIGN_KEY_CHECKS = 0;

ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_publication;
ALTER TABLE commentaire DROP FOREIGN KEY fk_commentaire_utilisateur;

ALTER TABLE publication CHANGE COLUMN id id_publication INT UNSIGNED NOT NULL AUTO_INCREMENT;
ALTER TABLE commentaire CHANGE COLUMN id id_commentaire INT UNSIGNED NOT NULL AUTO_INCREMENT;

ALTER TABLE commentaire
  ADD CONSTRAINT fk_commentaire_publication
    FOREIGN KEY (id_publication) REFERENCES publication (id_publication)
    ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT fk_commentaire_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE;

SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE IF NOT EXISTS reaction (
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

CREATE TABLE IF NOT EXISTS notification (
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
