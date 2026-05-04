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

    /** @return array<int, array<string, mixed>> */
    public function listerPourApi() {
        $query = "SELECT id, nom, instructions, calories_totales FROM " . $this->table_name . " ORDER BY id";
        if (!$this->conn) {
            return [];
        }
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$row) {
            $row['id'] = isset($row['id']) ? (string) $row['id'] : '';
            $row['nom'] = isset($row['nom']) ? (string) $row['nom'] : '';
            $row['instructions'] = isset($row['instructions']) ? (string) $row['instructions'] : '';
            $row['calories_totales'] = isset($row['calories_totales']) && $row['calories_totales'] !== null
                ? (string) $row['calories_totales'] : '';
            $row['caloriesTotales'] = $row['calories_totales'];
        }
        unset($row);
        return $rows;
    }

    /** @param array<string, mixed> $data */
    public function creerPourApi(array $data): array {
        if (!$this->conn) {
            throw new RuntimeException('Connexion base indisponible.');
        }
        $nom = isset($data['nom']) ? trim((string) $data['nom']) : '';
        if ($nom === '') {
            throw new InvalidArgumentException('Nom recette obligatoire.');
        }
        $instructions = isset($data['instructions']) ? trim((string) $data['instructions']) : '';
        $calories = isset($data['calories_totales']) ? trim((string) $data['calories_totales']) : '';
        $caloriesInt = $calories !== '' && ctype_digit($calories) ? (int) $calories : null;
        $status = isset($data['status']) ? trim((string) $data['status']) : 'active';
        if ($status === '') $status = 'active';

        // Compatibilité schémas: certaines bases n'ont pas la colonne "status" sur recette.
        try {
            $st = $this->conn->prepare(
                'INSERT INTO recette (nom, instructions, calories_totales, status) VALUES (?, ?, ?, ?)'
            );
            $st->execute([$nom, $instructions, $caloriesInt, $status]);
        } catch (Throwable $e) {
            $st = $this->conn->prepare(
                'INSERT INTO recette (nom, instructions, calories_totales) VALUES (?, ?, ?)'
            );
            $st->execute([$nom, $instructions, $caloriesInt]);
        }

        $id = (int) $this->conn->lastInsertId();
        $st2 = $this->conn->prepare(
            'SELECT id, nom, instructions, calories_totales FROM recette WHERE id = ? LIMIT 1'
        );
        $st2->execute([$id]);
        $row = $st2->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new RuntimeException('Recette créée mais introuvable.');
        }
        $row['id'] = isset($row['id']) ? (string) $row['id'] : '';
        $row['nom'] = isset($row['nom']) ? (string) $row['nom'] : '';
        $row['instructions'] = isset($row['instructions']) ? (string) $row['instructions'] : '';
        $row['calories_totales'] = isset($row['calories_totales']) && $row['calories_totales'] !== null
            ? (string) $row['calories_totales'] : '';
        $row['caloriesTotales'] = $row['calories_totales'];
        return $row;
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
