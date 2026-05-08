<?php
class AlimentDAO {
    private $conn;
    private $table_name = "aliment";

    public function __construct($db) {
        $this->conn = $db;
    }

    // List all available food items (the second entity)
    public function readAll() {
        $query = "SELECT * FROM " . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Find a specific food item
    public function readOne($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $aliment = new Aliment();
            $aliment->setId($row['id']);
            $aliment->setNom($row['nom']);
            $aliment->setCategorie($row['categorie']);
            $aliment->setCalories100g($row['calories_100g']);
            return $aliment;
        }
        return null;
    }
}
?>
