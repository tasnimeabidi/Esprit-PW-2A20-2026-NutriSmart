<?php
class Aliment {
    private $id;
    private $nom;
    private $categorie;
    private $calories_100g;

    public function __construct() {}

    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getNom() { return $this->nom; }
    public function setNom($nom) { $this->nom = $nom; }

    public function getCategorie() { return $this->categorie; }
    public function setCategorie($categorie) { $this->categorie = $categorie; }

    public function getCalories100g() { return $this->calories_100g; }
    public function setCalories100g($calories_100g) { $this->calories_100g = $calories_100g; }
}
?>
