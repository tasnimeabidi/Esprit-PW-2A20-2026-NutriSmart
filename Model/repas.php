<?php
class Repas {
    private $id;
    private $id_plan;
    private $id_recette;
    private $type;
    private $catories;
 
    public function getId()               { return $this->id; }
    public function setId($id)            { $this->id = $id; }
 
    public function getIdPlan()           { return $this->id_plan; }
    public function setIdPlan($val)       { $this->id_plan = $val; }
 
    public function getIdRecette()        { return $this->id_recette; }
    public function setIdRecette($val)    { $this->id_recette = $val; }
 
    public function getType()             { return $this->type; }
    public function setType($val)         { $this->type = $val; }
 
    public function getCatories()         { return $this->catories; }
    public function setCatories($val)     { $this->catories = $val; }
}
?>
 