-- Exécuter dans phpMyAdmin (base `nutrismart`) si la base existe déjà
-- Compte : tradzeineb1@gmail.com / mot de passe : 552005

USE `nutrismart`;

INSERT INTO `utilisateur` (`nom`, `email`, `mot_de_passe`, `role`, `is_verified`, `verification_token`)
VALUES (
  'Trad Zeineb',
  'tradzeineb1@gmail.com',
  '$2y$10$qgYjel83V1JWP.Ci1Hlbg.G6Av1P0fA0J8qp.f9HRSDBJgf/3d4xq',
  'utilisateur',
  1,
  NULL
)
ON DUPLICATE KEY UPDATE
  `nom` = VALUES(`nom`),
  `mot_de_passe` = VALUES(`mot_de_passe`),
  `role` = VALUES(`role`),
  `is_verified` = 1,
  `verification_token` = NULL,
  `login_attempts` = 0,
  `is_blocked` = 0,
  `blocked_at` = NULL;
