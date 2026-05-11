<?php
declare(strict_types=1);

/**
 * NewModel - Modèle MVC générique.
 * Remplacez par le nom réel du modèle et ajustez les propriétés et méthodes selon vos besoins.
 */
class NewModel
{
    private $conn;
    private $table_name = 'new_table'; // Remplacez par le nom de votre table

    // Propriétés du modèle
    public $id;
    public $nom; // Exemple de propriété, ajustez selon vos besoins
    // Ajoutez d'autres propriétés ici

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Méthode pour créer un nouvel enregistrement
    public function create()
    {
        if (!$this->conn) {
            return false;
        }

        $query = 'INSERT INTO ' . $this->table_name . ' (nom) VALUES (:nom)'; // Ajustez les colonnes
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $this->nom);
        // Liez d'autres paramètres ici

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return true;
        }
        return false;
    }

    // Méthode pour lire tous les enregistrements
    public function readAll()
    {
        $query = 'SELECT * FROM ' . $this->table_name;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    // Méthode pour lire un enregistrement par ID
    public function readOne()
    {
        $query = 'SELECT * FROM ' . $this->table_name . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $this->nom = $row['nom'];
            // Assignez d'autres propriétés ici
        }
    }

    // Méthode pour mettre à jour un enregistrement
    public function update()
    {
        $query = 'UPDATE ' . $this->table_name . ' SET nom = :nom WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':nom', $this->nom);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }

    // Méthode pour supprimer un enregistrement
    public function delete()
    {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id = :id';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        return $stmt->execute();
    }
}
?>