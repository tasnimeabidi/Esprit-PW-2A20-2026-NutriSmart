-- Débloquer seulement (sans changer le mot de passe)
-- Préférez fix_compte_tradzeineb1.sql si la connexion échoue encore.

USE `nutrismart`;

UPDATE `utilisateur`
SET `login_attempts` = 0,
    `is_blocked` = 0,
    `blocked_at` = NULL
WHERE `email` = 'tradzeineb1@gmail.com';
