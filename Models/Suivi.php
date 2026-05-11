<?php
class Suivi {
    private $id;
    private $userId;
    private $type;
    private $date;
    private $calories;
    private $description;
    private $quantite;
    private $poids;
    private $idSport;
    private $idNutrition;
    private $idAliment;

    // Default constructor
    public function __construct() {}

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getUserId() { return $this->userId; }
    public function setUserId($userId) { $this->userId = $userId; }

    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }

    public function getDate() { return $this->date; }
    public function setDate($date) { $this->date = $date; }

    public function getCalories() { return $this->calories; }
    public function setCalories($calories) { $this->calories = $calories; }

    public function getDescription() { return $this->description; }
    public function setDescription($description) { $this->description = $description; }

    public function getQuantite() { return $this->quantite; }
    public function setQuantite($quantite) { $this->quantite = $quantite; }

    public function getPoids() { return $this->poids; }
    public function setPoids($poids) { $this->poids = $poids; }

    public function getIdSport() { return $this->idSport; }
    public function setIdSport($idSport) { $this->idSport = $idSport; }

    public function getIdNutrition() { return $this->idNutrition; }
    public function setIdNutrition($idNutrition) { $this->idNutrition = $idNutrition; }

    public function getIdAliment() { return $this->idAliment; }
    public function setIdAliment($idAliment) { $this->idAliment = $idAliment; }
}
?>
