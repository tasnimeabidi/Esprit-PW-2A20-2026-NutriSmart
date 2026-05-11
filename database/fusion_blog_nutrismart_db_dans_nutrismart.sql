-- =============================================================================
-- Fusion des données blog (export nutrismart_db / module Nassim) dans la base
-- « nutrismart » utilisée par le projet Esprit + ProjetNutrismart (config PDO).
--
-- PRÉREQUIS :
--   • Base cible : nutrismart (tables publication, commentaire, reaction,
--     notification déjà créées — voir database/nutrismart.sql ou
--     database/nassim_blog_compat_nutrismart.sql).
--   • Les id_utilisateur 6 à 11 ne doivent pas être réservés à d’autres comptes
--     que vous souhaitez conserver tels quels (sinon adaptez les id avant exécution).
--
-- EFFET : vide les lignes blog existantes puis réinsère utilisateurs 6–11,
-- publications, commentaires, notifications et réactions du dump du 2026-05-09.
--
-- IMAGES : les noms de fichiers (ex. 1777882904_post 1.png) doivent exister dans
--   htdocs/nassim/ProjetNutrismart/public/uploads/
--   (copiez-les depuis l’ancien serveur / export si besoin).
-- =============================================================================

USE nutrismart;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DELETE FROM reaction;
DELETE FROM notification;
DELETE FROM commentaire;
DELETE FROM publication;

INSERT INTO utilisateur (id_utilisateur, nom, email, mot_de_passe, role) VALUES
(6, 'adem cherif', 'adem@gmail.com', '$2y$10$K7E3UP7sq64G1fhkbfOMEeKlvrCxSM29kE2Ud6IkYBXr0X3R8IaEm', 'user'),
(7, 'nassim zitouni', 'nassim@gmail.com', '$2y$10$hhCjsD1AUxjJKMzmw7S7Fen6C47GVpxlxbKs0nGy.i/t6o6yg2R5u', 'user'),
(8, 'Admin', 'admin@nutrismart.com', '$2y$10$rdTwD.a63L57AlwjIA1o4.v7dyEIgXAMJunbllsTjp3f4XvpANaNK', 'admin'),
(9, 'Amenallah', 'amenallah@gmail.com', '$2y$10$ukbM6nHt.nVNqgmT88VN1.T0W1dnAz7jX6LzJPCJ3D7sO6U9ecvFq', 'user'),
(10, 'Fares benmessoud', 'fares@gmail.com', '$2y$10$NM9wPd3PkrUbLVmYl0gyeeJK1HdO.ftwssSznWcYzLJy6EN8VXyCC', 'user'),
(11, 'Aziz Yefrni', 'yefrni@gmail.com', '$2y$10$N7RwA4uZ78k8p5UdHENAXudaJGnnrRr4az1D09oWUK8cXTv6U2Q9y', 'user')
ON DUPLICATE KEY UPDATE
  nom = VALUES(nom),
  email = VALUES(email),
  mot_de_passe = VALUES(mot_de_passe),
  role = VALUES(role);

INSERT IGNORE INTO profil_nutritionnel (id_utilisateur, age, poids, taille, objectifs, preferences_alimentaires)
SELECT u.id_utilisateur, 25, 70.00, 175.00, 'Non renseigné', NULL
FROM utilisateur u
WHERE u.id_utilisateur BETWEEN 6 AND 11;

INSERT INTO publication (id_publication, id_utilisateur, titre, contenu, image, date_publication) VALUES
(41, 7, 'Les erreurs qui empêchent de perdre du poids', 'Beaucoup de personnes pensent que manger moins suffit pour perdre du poids, mais certaines erreurs ralentissent énormément les résultats. Sauter les repas peut augmenter les fringales et pousser à manger plus le soir. Les boissons sucrées comme les sodas ou les jus industriels apportent énormément de calories sans rassasier. Dormir peu influence aussi les hormones de la faim et augmente les envies de sucre. Enfin, ne pas manger assez de protéines peut provoquer une perte musculaire et diminuer le métabolisme. Pour une perte de poids durable, il faut un déficit calorique raisonnable, une alimentation équilibrée et une activité physique régulière.', '1777882904_post 1.png', '2026-05-04 09:21:44'),
(42, 6, 'Pourquoi boire de l’eau est important pour la santé', 'L’eau joue un rôle essentiel dans le fonctionnement du corps humain. Elle aide à transporter les nutriments, réguler la température du corps et améliorer la digestion. Une bonne hydratation peut aussi réduire la fatigue et améliorer la concentration pendant les études ou le travail. Beaucoup de personnes confondent parfois la faim et la soif, ce qui peut mener à une consommation excessive de nourriture. Boire suffisamment d’eau chaque jour aide également la peau à rester plus saine et améliore les performances sportives.', '1777882990_post2.jpg', '2026-05-04 09:23:10'),
(43, 9, 'Les protéines : un élément clé pour construire du muscle', 'Les protéines sont indispensables pour réparer et construire les muscles après l’entraînement. Les sources de protéines peuvent être animales comme le poulet, les œufs et le poisson, ou végétales comme les lentilles et les pois chiches. Consommer des protéines après une séance de sport aide à améliorer la récupération musculaire. Les sportifs ont généralement besoin de plus de protéines que les personnes sédentaires. Cependant, il est important de garder une alimentation équilibrée avec des glucides et des bonnes graisses.', '1777883095_protein.jpg', '2026-05-04 09:24:55'),
(44, 10, 'Les bienfaits des fruits et légumes au quotidien', 'Les fruits et légumes apportent des vitamines, des minéraux et des fibres essentiels pour rester en bonne santé. Ils aident le système immunitaire à fonctionner correctement et réduisent le risque de certaines maladies. Les fibres améliorent la digestion et augmentent la sensation de satiété, ce qui peut aider dans une perte de poids. Les légumes verts sont souvent faibles en calories mais riches en nutriments importants. Ajouter plusieurs couleurs dans son assiette permet d’obtenir une alimentation plus complète et équilibrée.', '1777883254_fruits.jpg', '2026-05-04 09:27:34');

INSERT INTO commentaire (id_commentaire, id_publication, id_utilisateur, contenu, date_commentaire) VALUES
(30, 41, 9, 'Mais c''est pas facile . faut etre discipliné', '2026-05-04 09:30:25');

INSERT INTO notification (id_notification, id_utilisatuer, message, is_read, date_notification) VALUES
(11, 11, 'Amenallah a réagi 👍 à votre publication : "Tros bon"', 1, '2026-05-03 13:41:22'),
(12, 10, 'Amenallah a commenté votre publication : "C''est trop nuls"', 1, '2026-05-03 14:04:11'),
(13, 11, 'Amenallah a commenté votre publication : "Tros bon"', 1, '2026-05-03 14:10:24'),
(14, 11, 'Amenallah a réagi 👎 à votre publication : "Tros bon"', 1, '2026-05-03 14:10:31'),
(15, 10, 'Amenallah a réagi 👍 à votre publication : "C''est trop nuls"', 1, '2026-05-03 14:40:54'),
(16, 11, 'nassim zitouni a réagi 👍 à votre publication : "Tros bon"', 1, '2026-05-03 16:34:14'),
(17, 10, 'nassim zitouni a réagi 👍 à votre publication : J''ai pas', 1, '2026-05-03 16:54:44'),
(18, 7, '⚠️ Avertissement de l''administrateur : False info', 1, '2026-05-03 23:32:24'),
(19, 9, 'Fares benmessoud a réagi 👍 à votre publication : "Les protéines : un élément clé pour cons"', 1, '2026-05-04 09:27:51'),
(20, 9, 'nassim zitouni a réagi 👍 à votre publication : "Les protéines : un élément clé pour cons"', 1, '2026-05-04 09:28:04'),
(21, 9, 'adem cherif a réagi 👍 à votre publication : "Les protéines : un élément clé pour cons"', 1, '2026-05-04 09:28:18'),
(22, 6, 'nassim zitouni a réagi 👍 à votre publication : "Pourquoi boire de l’eau est important po"', 0, '2026-05-04 09:28:34'),
(23, 6, 'Amenallah a réagi 👍 à votre publication : "Pourquoi boire de l’eau est important po"', 0, '2026-05-04 09:28:47'),
(24, 7, 'Amenallah a commenté votre publication : "Les erreurs qui empêchent de perdre du p"', 1, '2026-05-04 09:30:25'),
(25, 9, 'nassim zitouni a réagi 👍 à votre commentaire.', 0, '2026-05-04 09:30:45'),
(26, 10, 'nassim zitouni a réagi 👍 à votre publication : "Les bienfaits des fruits et légumes au q"', 1, '2026-05-04 10:29:01'),
(27, 10, 'nassim zitouni a réagi 👎 à votre publication : "Les bienfaits des fruits et légumes au q"', 1, '2026-05-04 10:29:02'),
(28, 9, 'nassim zitouni a réagi 👎 à votre commentaire.', 0, '2026-05-04 10:29:05'),
(29, 9, 'nassim zitouni a réagi 👍 à votre commentaire.', 0, '2026-05-04 10:29:05'),
(30, 10, '⚠️ Avertissement de l''administrateur : FALSE INFORMATION', 1, '2026-05-04 10:30:13'),
(31, 10, 'nassim zitouni a réagi 👍 à votre publication : "Les bienfaits des fruits et légumes au q"', 1, '2026-05-04 11:17:25'),
(32, 10, 'nassim zitouni a réagi 👎 à votre publication : "Les bienfaits des fruits et légumes au q"', 1, '2026-05-04 11:17:26'),
(33, 10, 'nassim zitouni a réagi 👍 à votre publication : "Les bienfaits des fruits et légumes au q"', 1, '2026-05-04 11:17:27');

INSERT INTO reaction (id_reaction, id_utilisateur, id_publication, id_commentaire, type_reaction, date_reaction) VALUES
(15, 10, 43, NULL, 'like', '2026-05-04 09:27:51'),
(16, 7, 43, NULL, 'like', '2026-05-04 09:28:04'),
(17, 6, 43, NULL, 'like', '2026-05-04 09:28:18'),
(18, 7, 42, NULL, 'like', '2026-05-04 09:28:34'),
(19, 9, 42, NULL, 'like', '2026-05-04 09:28:47'),
(20, 7, 41, 30, 'like', '2026-05-04 10:29:05'),
(21, 7, 44, NULL, 'like', '2026-05-04 11:17:27');

SET FOREIGN_KEY_CHECKS = 1;

ALTER TABLE utilisateur AUTO_INCREMENT = 12;
ALTER TABLE publication AUTO_INCREMENT = 45;
ALTER TABLE commentaire AUTO_INCREMENT = 31;
ALTER TABLE notification AUTO_INCREMENT = 34;
ALTER TABLE reaction AUTO_INCREMENT = 22;
