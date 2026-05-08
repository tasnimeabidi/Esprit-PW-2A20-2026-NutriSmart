<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id_user;
    public $nom;
    public $prenom;
    public $email;
    public $password;
    public $role;
    public $date_inscription;
    public $poids;
    public $taille;
    public $age;
    public $objectif;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table_name . "
                  (nom, prenom, email, password, role)
                  VALUES (:nom, :prenom, :email, :password, :role)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':prenom', $this->prenom);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);
        $stmt->bindParam(':role', $this->role);

        if ($stmt->execute()) {
            $this->id_user = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    public function readOne() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id_user = :id LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id_user);
        $stmt->execute();
        return $stmt;
    }

    public function findByEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        return $stmt;
    }
}
?>