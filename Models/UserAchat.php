<?php
class UserAchat {
    public $id;
    public $id_utilisateur;
    public $id_aliment;
    public $quantite;
    public $prix_total;
    public $date_achat;

    public function __construct($data) {
        $this->id = $data['id'] ?? null;
        $this->id_utilisateur = $data['id_utilisateur'];
        $this->id_aliment = $data['id_aliment'];
        $this->quantite = $data['quantite'];
        $this->prix_total = $data['prix_total'];
        $this->date_achat = $data['date_achat'] ?? null;
    }
}