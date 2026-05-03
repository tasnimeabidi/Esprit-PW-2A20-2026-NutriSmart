<?php
class Aliment {
    private $conn;
    private $table_name = "aliment";

    public $id;
    public $nom;
    public $categorie;
    public $calories;
    public $proteines;
    public $glucides;
    public $lipides;
    public $prix;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, categorie, calories, proteines, glucides, lipides, prix) 
                  VALUES (:nom, :categorie, :calories, :proteines, :glucides, :lipides, :prix)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':categorie', $this->categorie);
        $stmt->bindParam(':calories', $this->calories);
        $stmt->bindParam(':proteines', $this->proteines);
        $stmt->bindParam(':glucides', $this->glucides);
        $stmt->bindParam(':lipides', $this->lipides);
        $stmt->bindParam(':prix', $this->prix);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->nom = $row['nom'];
            $this->categorie = $row['categorie'];
            $this->calories = $row['calories'];
            $this->proteines = $row['proteines'];
            $this->glucides = $row['glucides'];
            $this->lipides = $row['lipides'];
            $this->prix = $row['prix'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom = :nom, 
                      categorie = :categorie, 
                      calories = :calories, 
                      proteines = :proteines, 
                      glucides = :glucides, 
                      lipides = :lipides,
                      prix = :prix
                  WHERE id = :id";
        
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

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>
