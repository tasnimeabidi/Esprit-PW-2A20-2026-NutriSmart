<?php
class Budget {
    public $id_utilisateur;
    public $montant;
    public $date_creation;

    public function __construct($data) {
        $this->id_utilisateur = $data['id_utilisateur'];
        $this->montant = $data['montant'];
        $this->date_creation = $data['date_creation'] ?? null;
    }
}