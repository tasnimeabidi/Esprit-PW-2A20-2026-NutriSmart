<?php
declare(strict_types=1);

/**
 * Aliments — recherche par ingrédient (liaison recette_aliment).
 * Schéma compatible id ou id_aliment (détection PK à l’exécution).
 */
class Aliment
{
    private $conn;
    private $table_name = 'aliment';

    /** Nom réel de la colonne PRIMARY KEY (évite id vs id_aliment selon les bases). */
    public static function primaryKeyColumn(PDO $conn): string
    {
        static $cache = [];
        $schema = $conn->query('SELECT DATABASE()')->fetchColumn();
        if (!$schema) {
            return 'id';
        }
        $key = $schema . ':aliment';
        if (isset($cache[$key])) {
            return $cache[$key];
        }
        $stmt = $conn->prepare(
            'SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_KEY = \'PRI\'
             ORDER BY ORDINAL_POSITION ASC LIMIT 1'
        );
        $stmt->execute([$schema, 'aliment']);
        $col = $stmt->fetchColumn();
        if (!$col || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $col)) {
            $show = $conn->query('SHOW COLUMNS FROM `aliment`');
            foreach ($show->fetchAll(PDO::FETCH_ASSOC) as $row) {
                if (($row['Key'] ?? '') === 'PRI') {
                    $col = $row['Field'];
                    break;
                }
            }
        }
        if (!$col || !preg_match('/^[a-zA-Z0-9_]+$/', (string) $col)) {
            $col = 'id';
        }
        $cache[$key] = $col;
        return $col;
    }

    public $id;
    public $nom;
    public $categorie;
    public $calories;
    public $proteines;
    public $glucides;
    public $lipides;
    public $prix;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        if (!$this->conn) {
            return false;
        }

        try {
            $stmt = $this->conn->prepare(
                'INSERT INTO ' . $this->table_name . '
                (nom, categorie, calories_100g, proteines_100g, glucides_100g, lipides_100g, prix)
                VALUES (:nom, :categorie, :calories, :proteines, :glucides, :lipides, :prix)'
            );
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':categorie', $this->categorie);
            $stmt->bindParam(':calories', $this->calories);
            $stmt->bindParam(':proteines', $this->proteines);
            $stmt->bindParam(':glucides', $this->glucides);
            $stmt->bindParam(':lipides', $this->lipides);
            $stmt->bindParam(':prix', $this->prix);
            return $stmt->execute();
        } catch (Throwable $e) {
            $stmt = $this->conn->prepare(
                'INSERT INTO ' . $this->table_name . '
                (nom, categorie, calories, proteines, glucides, lipides, prix)
                VALUES (:nom, :categorie, :calories, :proteines, :glucides, :lipides, :prix)'
            );
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':categorie', $this->categorie);
            $stmt->bindParam(':calories', $this->calories);
            $stmt->bindParam(':proteines', $this->proteines);
            $stmt->bindParam(':glucides', $this->glucides);
            $stmt->bindParam(':lipides', $this->lipides);
            $stmt->bindParam(':prix', $this->prix);
            return $stmt->execute();
        }
    }

    public function readAll()
    {
        $pk = self::primaryKeyColumn($this->conn);
        $query = 'SELECT * FROM ' . $this->table_name . ' ORDER BY `' . $pk . '` DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne()
    {
        $pk = self::primaryKeyColumn($this->conn);
        $query = 'SELECT * FROM ' . $this->table_name . ' WHERE `' . $pk . '` = :id LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $this->id = $row['id_aliment'] ?? $row['id'];
            $this->nom = $row['nom'];
            $this->categorie = $row['categorie'];
            $this->calories = $row['calories'] ?? $row['calories_100g'] ?? null;
            $this->proteines = $row['proteines'] ?? $row['proteines_100g'] ?? null;
            $this->glucides = $row['glucides'] ?? $row['glucides_100g'] ?? null;
            $this->lipides = $row['lipides'] ?? $row['lipides_100g'] ?? null;
            $this->prix = $row['prix'];
            return true;
        }
        return false;
    }

    public function update()
    {
        $pk = self::primaryKeyColumn($this->conn);
        try {
            $query = 'UPDATE ' . $this->table_name . '
                  SET nom = :nom,
                      categorie = :categorie,
                      calories_100g = :calories,
                      proteines_100g = :proteines,
                      glucides_100g = :glucides,
                      lipides_100g = :lipides,
                      prix = :prix
                  WHERE `' . $pk . '` = :id';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':categorie', $this->categorie);
            $stmt->bindParam(':calories', $this->calories);
            $stmt->bindParam(':proteines', $this->proteines);
            $stmt->bindParam(':glucides', $this->glucides);
            $stmt->bindParam(':lipides', $this->lipides);
            $stmt->bindParam(':prix', $this->prix);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        } catch (Throwable $e) {
            $query = 'UPDATE ' . $this->table_name . '
                  SET nom = :nom,
                      categorie = :categorie,
                      calories = :calories,
                      proteines = :proteines,
                      glucides = :glucides,
                      lipides = :lipides,
                      prix = :prix
                  WHERE `' . $pk . '` = :id';
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':nom', $this->nom);
            $stmt->bindParam(':categorie', $this->categorie);
            $stmt->bindParam(':calories', $this->calories);
            $stmt->bindParam(':proteines', $this->proteines);
            $stmt->bindParam(':glucides', $this->glucides);
            $stmt->bindParam(':lipides', $this->lipides);
            $stmt->bindParam(':prix', $this->prix);
            $stmt->bindParam(':id', $this->id);
            return $stmt->execute();
        }
    }

    public function delete()
    {
        $pk = self::primaryKeyColumn($this->conn);
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE `' . $pk . '` = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
