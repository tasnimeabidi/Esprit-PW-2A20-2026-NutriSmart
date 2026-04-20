<?php
class ProgrammeSportif {
    private $id;
    private $id_plan;
    private $type_sport;
    private $niveau;
    private $intensite;
    private $date_seance;
    private $duree_min;
    private $statut;
 
    public function getId()               { return $this->id; }
    public function setId($id)            { $this->id = $id; }
 
    public function getIdPlan()           { return $this->id_plan; }
    public function setIdPlan($val)       { $this->id_plan = $val; }
 
    public function getTypeSport()        { return $this->type_sport; }
    public function setTypeSport($val)    { $this->type_sport = $val; }
 
    public function getNiveau()           { return $this->niveau; }
    public function setNiveau($val)       { $this->niveau = $val; }
 
    public function getIntensite()        { return $this->intensite; }
    public function setIntensite($val)    { $this->intensite = $val; }
 
    public function getDateSeance()       { return $this->date_seance; }
    public function setDateSeance($val)   { $this->date_seance = $val; }
 
    public function getDureeMin()         { return $this->duree_min; }
    public function setDureeMin($val)     { $this->duree_min = $val; }
 
    public function getStatut()           { return $this->statut; }
    public function setStatut($val)       { $this->statut = $val; }
}
?>
 