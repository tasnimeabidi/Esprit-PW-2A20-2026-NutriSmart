<?php
class PlanRepas {
    private $id;
    private $id_utilisateur;
    private $date_debut;
    private $date_fin;
    private $objectif;
    private $statut;
 
    public function getId()               { return $this->id; }
    public function setId($id)            { $this->id = $id; }
 
    public function getIdUtilisateur()            { return $this->id_utilisateur; }
    public function setIdUtilisateur($val)        { $this->id_utilisateur = $val; }
 
    public function getDateDebut()                { return $this->date_debut; }
    public function setDateDebut($val)            { $this->date_debut = $val; }
 
    public function getDateFin()                  { return $this->date_fin; }
    public function setDateFin($val)              { $this->date_fin = $val; }
 
    public function getObjectif()                 { return $this->objectif; }
    public function setObjectif($val)             { $this->objectif = $val; }
 
    public function getStatut()                   { return $this->statut; }
    public function setStatut($val)               { $this->statut = $val; }
}
?>
 