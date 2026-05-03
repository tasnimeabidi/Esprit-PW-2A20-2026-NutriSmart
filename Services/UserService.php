<?php
require_once __DIR__ . '/../config/db_connect.php';

class UserService {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function getAllUsers() {
        $sql = "SELECT * FROM utilisateur ORDER BY nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getUserById($id) {
        $sql = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}