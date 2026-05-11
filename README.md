<<<<<<< HEAD
# Esprit-PW-2A20-2526-NutriSmart
# NutriSmart

## Présentation
NutriSmart est une plateforme web conçue pour aider les utilisateurs à gérer leur alimentation quotidienne et à améliorer leurs habitudes alimentaires.

L'application permet aux utilisateurs de suivre leurs apports nutritionnels, de découvrir des aliments et des recettes, et de générer des plans de repas personnalisés en fonction de leurs objectifs. Un programme sportif simple est également proposé avec le plan de repas.

Ce projet a été développé dans le cadre du **Projet Technologies Web (2A)** à l'**École d'Ingénieurs Esprit – Tunisie** durant l'année universitaire **2025-2026**.

## Fonctionnalités
-Authentification et gestion des comptes utilisateurs
-Gestion des profils utilisateurs (âge, poids, taille, objectif, préférences alimentaires)
-Base de données d'aliments et de recettes
-Suivi des apports nutritionnels (calories et macronutriments)
-Génération de plans de repas personnalisés
-Suggestion de programmes sportifs simples

## Technologies utilisées

### Frontend
- HTML
- CSS
- JavaScript

### Backend
- (ex. : PHP / Node.js / Laravel / Spring — indiquez ce que vous utilisez)

### Base de données
- MySQL

## Architecture
L'application suit une architecture modulaire :

- Module de gestion des utilisateurs

- Module de gestion aliments et de recettes 

- Module de gestion planification des repas et de sport

- Module de gestion recommandations sportivessuivi et statistiques

- Module de gestion recettes Collaboratives et Favoris

- Gestion de gestion Budget et Courses
    
## Contributeurs

- Tasnime Abidi
- Zeineb Trad
- Chahine Chaieb
- Rayen Bouchaa
- Nassim Zitouni
- Youssef Mejri

## Contexte académique
Développé à **Esprit School of Engineering – Tunisie**

Module : Projet Technologies Web (PW)

Classe : **2A20**

Année académique : **2025–2026**

## Premiers pas

1. Cloner le dépôt

```bash
git clone https://github.com/tasnimeabidi/Esprit-PW-2A20-2526-NutriSmart.git
=======
# NutriSmart - Plan de Repas

## Description
NutriSmart est une application web de planification de repas et de nutrition intelligente. Elle permet aux utilisateurs de créer des plans de repas personnalisés, de gérer des recettes, de suivre leur alimentation et de bénéficier de programmes sportifs adaptés.

## Fonctionnalités
- Création et gestion de plans de repas
- Gestion des recettes et aliments
- Suivi nutritionnel
- Programmes sportifs
- API pour intégration
- Authentification via OAuth
- Recherche de repas et recettes
- Scanner de repas (démo)

## Installation

### Prérequis
- PHP 7.4 ou supérieur
- Serveur web (Apache recommandé, via XAMPP)
- MySQL/MariaDB
- Composer (pour les dépendances PHP)

### Étapes d'installation
1. Clonez le dépôt dans le répertoire htdocs de XAMPP :
   ```
   git clone <url-du-depot> c:\xampp\htdocs\Esprit-PW-2A20-2026-NutriSmart-planRepas
   ```

2. Installez les dépendances PHP :
   ```
   composer install
   ```

3. Configurez la base de données :
   - Créez une base de données MySQL nommée `nutrismart`
   - Importez le fichier `nutrismart.sql` depuis le dossier `database/`

4. Configurez les fichiers de configuration :
   - Copiez `config/config.php` et ajustez les paramètres (base de données, email, etc.)
   - Configurez OAuth dans `config_oauth.php` si nécessaire

5. Démarrez XAMPP (Apache et MySQL)

6. Accédez à l'application via `http://localhost/Esprit-PW-2A20-2026-NutriSmart-planRepas`

## Utilisation
- La page d'accueil se trouve dans `Views/frontoffice/nutrismart-website.html`
- Utilisez les APIs via les endpoints dans le dossier `api/`
- Gérez les données via les contrôleurs dans `controllers/`

## APIs
- `/api/plan-repas.php` : Gestion des plans de repas
- `/api/repas.php` : Gestion des repas
- `/api/recettes.php` : Gestion des recettes
- `/api/programme-sportif.php` : Gestion des programmes sportifs
- Et autres endpoints dans le dossier `api/`

## Structure du projet
- `controllers/` : Contrôleurs pour la logique métier
- `Models/` : Modèles de données
- `Views/` : Vues HTML/CSS/JS
- `api/` : Endpoints API
- `database/` : Scripts SQL et migrations
- `config/` : Fichiers de configuration
- `Services/` : Services utilitaires (email, etc.)

## Technologies utilisées
- PHP
- MySQL
- HTML/CSS/JavaScript
- PHPMailer (pour les emails)
- Bootstrap (probablement pour le front-end)

## Contribution
1. Forkez le projet
2. Créez une branche pour votre fonctionnalité
3. Commitez vos changements
4. Poussez vers la branche
5. Ouvrez une Pull Request

## Licence
[Spécifiez la licence si applicable]

## Contact
[Informations de contact]
>>>>>>> 7736f4d (Initial commit: Add NutriSmart meal planning app)
