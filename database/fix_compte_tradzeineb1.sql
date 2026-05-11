-- À exécuter UNE FOIS dans phpMyAdmin (onglet SQL), base : nutrismart
-- Rétablit le mot de passe 552005 (hash bcrypt), débloque les tentatives et active le compte.

USE `nutrismart`;

UPDATE `utilisateur`
SET
  `mot_de_passe` = '$2y$10$qgYjel83V1JWP.Ci1Hlbg.G6Av1P0fA0J8qp.f9HRSDBJgf/3d4xq',
  `login_attempts` = 0,
  `is_blocked` = 0,
  `blocked_at` = NULL,
  `is_verified` = 1,
  `verification_token` = NULL
WHERE `email` = 'tradzeineb1@gmail.com';
