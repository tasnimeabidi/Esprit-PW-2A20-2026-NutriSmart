<?php
class User {
    public $id_utilisateur;
    public $nom;
    public $email;
    public $date_inscription;

    public function __construct($data) {
        $this->id_utilisateur = $data['id_utilisateur'] ?? null;
        $this->nom = $data['nom'] ?? '';
        $this->email = $data['email'] ?? '';
        $this->date_inscription = $data['date_inscription'] ?? null;
    }
}
