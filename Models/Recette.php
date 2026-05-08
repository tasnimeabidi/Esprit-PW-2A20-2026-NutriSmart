<?php
class Recette {
    private $conn;
    private $table_name = "recette";

    public $id;
    public $nom;
    public $instructions;
    public $calories_totales;
    public $temps;
    public $status;
    public $user_id;
    public $user_name;
    public $video_url;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, instructions, video_url, calories_totales, temps, status, user_id, user_name) 
                  VALUES (:nom, :instructions, :video_url, :calories_totales, :temps, :status, :user_id, :user_name)";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':video_url', $this->video_url);
        $stmt->bindParam(':calories_totales', $this->calories_totales);
        $stmt->bindParam(':temps', $this->temps);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':user_name', $this->user_name);

        return $stmt->execute();
    }

    public function readAll() {
        $query = "SELECT id, nom, instructions, video_url, calories_totales, temps, status, user_id, user_name, created_at, updated_at FROM " . $this->table_name . " ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function readByStatus($status) {
        $query = "SELECT id, nom, instructions, video_url, calories_totales, temps, status, user_id, user_name, created_at, updated_at FROM " . $this->table_name . " WHERE status = :status ORDER BY id DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
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
            $this->nom = $row['nom'];
            $this->instructions = $row['instructions'];
            $this->video_url = $row['video_url'] ?? null;
            $this->calories_totales = $row['calories_totales'];
            $this->temps = $row['temps'] ?? null;
            $this->status = $row['status'] ?? 'pending';
            $this->user_id = $row['user_id'] ?? null;
            $this->user_name = $row['user_name'] ?? null;
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table_name . " 
                  SET nom = :nom, 
                      instructions = :instructions, 
                      video_url = :video_url,
                      calories_totales = :calories_totales,
                      temps = :temps,
                      status = :status,
                      user_id = :user_id,
                      user_name = :user_name 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':instructions', $this->instructions);
        $stmt->bindParam(':video_url', $this->video_url);
        $stmt->bindParam(':calories_totales', $this->calories_totales);
        $stmt->bindParam(':temps', $this->temps);
        $stmt->bindParam(':status', $this->status);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':user_name', $this->user_name);
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
