-- Module budget & courses (intégration Youssef Mejri) — à exécuter sur la base NutriSmart existante.
-- Requiert les tables `utilisateur` et `aliment` déjà présentes.

CREATE TABLE IF NOT EXISTS budget (
  id_utilisateur INT UNSIGNED NOT NULL,
  montant DECIMAL(10,2) NOT NULL COMMENT 'Plafond budget',
  date_creation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_utilisateur),
  CONSTRAINT fk_budget_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_achat (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_utilisateur INT UNSIGNED NOT NULL,
  id_aliment INT UNSIGNED NOT NULL,
  quantite DECIMAL(10,2) NOT NULL,
  prix_total DECIMAL(10,2) NOT NULL,
  date_achat DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_user_achat_user (id_utilisateur),
  KEY idx_user_achat_aliment (id_aliment),
  CONSTRAINT fk_user_achat_utilisateur
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateur (id_utilisateur)
    ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_user_achat_aliment
    FOREIGN KEY (id_aliment) REFERENCES aliment (id)
    ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
