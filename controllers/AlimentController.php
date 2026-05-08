<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Aliment.php';

class AlimentController {
    private $db;
    private $aliment;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->aliment = new Aliment($this->db);
    }

    public function listAliments() {
        $stmt = $this->aliment->readAll();
        $aliments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $aliments[] = $row;
        }
        return $aliments;
    }

    public function getAliment($id) {
        $this->aliment->id = $id;
        if ($this->aliment->readOne()) {
            return [
                "id_aliment" => $this->aliment->id,
                "nom" => $this->aliment->nom,
                "categorie" => $this->aliment->categorie,
                "calories" => $this->aliment->calories,
                "proteines" => $this->aliment->proteines,
                "glucides" => $this->aliment->glucides,
                "lipides" => $this->aliment->lipides,
                "prix" => $this->aliment->prix
            ];
        }
        return null;
    }

    public function createAliment($data) {
        $this->aliment->nom = $data['nom_aliment'] ?? '';
        $this->aliment->categorie = $data['categorie'] ?? '';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->create()) {
            return ["status" => "success", "message" => "Aliment créé avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la création de l'aliment"];
    }

    public function updateAliment($data) {
        $this->aliment->id = $data['id'];
        $this->aliment->nom = $data['nom_aliment'] ?? '';
        $this->aliment->categorie = $data['categorie'] ?? '';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->update()) {
            return ["status" => "success", "message" => "Aliment mis à jour avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la mise à jour de l'aliment"];
    }

    public function deleteAliment($id) {
        $this->aliment->id = $id;
        if ($this->aliment->delete()) {
            return ["status" => "success", "message" => "Aliment supprimé avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la suppression de l'aliment"];
    }
}
?>
