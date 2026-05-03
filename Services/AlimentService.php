<?php
require_once __DIR__ . '/../config/db_connect.php';

class AlimentService {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAllAliments() {
        $sql = "SELECT * FROM aliment ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getAlimentById($id) {
        $sql = "SELECT * FROM aliment WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getAlimentsByCategory($categorie) {
        $sql = "SELECT * FROM aliment WHERE categorie = ? ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$categorie]);
        return $stmt->fetchAll();
    }

    public function searchAliments($search) {
        $sql = "SELECT * FROM aliment WHERE nom LIKE ? ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%{$search}%"]);
        return $stmt->fetchAll();
    }
}