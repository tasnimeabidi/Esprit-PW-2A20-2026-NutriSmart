<?php
class Recette {
    private $conn;
    private $table_name = "recette";

    public $id;
    public $nom;
    public $instructions;
    public $calories_totales;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, instructions, calories_totales, status) 
                  VALUES (:nom, :instructions, :calories_totales, :status)";
        
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':calories_totales', $this->calories_totales);
        $stmt->bindParam(':status', $this->status);

        return $stmt->execute();
    }

    public function readByStatus($status) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE status = :status ORDER BY id DESC";
        if (!$this->conn) {
            return null;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        return $stmt;
    }

    public function updateStatus($id, $status) {
        $query = "UPDATE " . $this->table_name . " SET status = :status WHERE id = :id";
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        return $stmt->execute();
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->nom = $row['nom'];
            $this->instructions = $row['instructions'];
            $this->calories_totales = $row['calories_totales'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom = :nom, 
                      instructions = :instructions, 
                      calories_totales = :calories_totales 
                  WHERE id = :id";
        
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':calories_totales', $this->calories_totales);
        $stmt->bindParam(':id', $this->id);

        return $stmt->execute();
    }

    public function delete() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        if (!$this->conn) {
            return false;
        }
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>
