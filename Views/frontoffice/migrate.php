<?php
/**
 * =========================================================================
 * OUTIL DE MIGRATION GLOBAL (BASE DE DONNÉES NUTRISMART)
 * =========================================================================
 * Ce fichier regroupe toutes les modifications apportées à la base de données 
 * depuis la création du projet. 
 * Il suffit d'exécuter ce fichier (ex: php migrate.php) pour mettre à jour
 * la table "utilisateur" avec toutes les nouvelles colonnes si vous
 * recréez la base de données depuis zéro (sans utiliser le nutrismart.sql 
 * qui est lui, déjà à jour).
 */

require_once __DIR__ . '/../../config.php';

echo "Démarrage des migrations...\n";

// Liste des requêtes SQL de migration
$migrations = [
    "ALTER TABLE utilisateur ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0;" => "Colonne 'is_blocked' (Bannissement des utilisateurs)",
    "ALTER TABLE utilisateur ADD COLUMN login_attempts INT DEFAULT 0;" => "Colonne 'login_attempts' (Protection brute-force)",
    "ALTER TABLE utilisateur ADD COLUMN reset_token VARCHAR(64) DEFAULT NULL;" => "Colonne 'reset_token' (Mot de passe oublié)",
    "ALTER TABLE utilisateur ADD COLUMN reset_expires DATETIME DEFAULT NULL;" => "Colonne 'reset_expires' (Expiration du Reset)",
    "ALTER TABLE utilisateur ADD COLUMN oauth_provider VARCHAR(50) DEFAULT NULL;" => "Colonne 'oauth_provider' (Réseaux sociaux, ex: Google, FB)",
    "ALTER TABLE utilisateur ADD COLUMN oauth_id VARCHAR(255) DEFAULT NULL;" => "Colonne 'oauth_id' (Identifiant social unique)",
    "ALTER TABLE utilisateur ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0;" => "Colonne 'is_verified' (Validation par email)",
    "ALTER TABLE utilisateur ADD COLUMN verification_token VARCHAR(64) DEFAULT NULL;" => "Colonne 'verification_token' (Lien d'activation)"
];

$ajoutCount = 0;
$existCount = 0;

foreach ($migrations as $sql => $description) {
    try {
        $pdo->exec($sql);
        echo "✅ MIGRATION RÉUSSIE : " . $description . "\n";
        $ajoutCount++;
    } catch (PDOException $e) {
        // Le code SQLSTATE '42S21' correspond à "Duplicate column name"
        if (strpos($e->getMessage(), 'Duplicate column name') !== false || $e->getCode() == '42S21') {
            echo "ℹ️ DÉJÀ PRÉSENT : " . $description . "\n";
            $existCount++;
        } else {
            echo "❌ ERREUR MIGRATION : " . $description . " -> " . $e->getMessage() . "\n";
        }
    }
}

echo "\n============================================\n";
echo "Bilan : $ajoutCount ajouts, $existCount déjà présents.\n";
echo "La base de données est parfaitement à jour ! \n";
echo "============================================\n";

?>
