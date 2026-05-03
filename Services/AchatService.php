<?php
require_once __DIR__ . '/../config/db_connect.php';

class AchatService {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function addPurchase($userId, $alimentId, $qty, $totalPrice) {
        $sql = "INSERT INTO user_achat (id_utilisateur, id_aliment, quantite, prix_total) VALUES (?, ?, ?, ?)";
        return $this->db->prepare($sql)->execute([$userId, $alimentId, $qty, $totalPrice]);
    }

    public function deletePurchase($id) {
        $sql = "DELETE FROM user_achat WHERE id = ?";
        return $this->db->prepare($sql)->execute([$id]);
    }

    public function getUserHistory($userId) {
        $sql = "SELECT ua.*, a.nom as aliment_nom 
                FROM user_achat ua 
                JOIN aliment a ON ua.id_aliment = a.id 
                WHERE ua.id_utilisateur = ? 
                ORDER BY ua.date_achat DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function getTotalDepensesByUserId($userId) {
        $sql = "SELECT COALESCE(SUM(prix_total), 0) as total FROM user_achat WHERE id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return floatval($result['total']);
    }

    public function getAchatsByUserId($userId) {
        $sql = "SELECT ua.*, a.nom as nom_aliment 
                FROM user_achat ua 
                JOIN aliment a ON ua.id_aliment = a.id 
                WHERE ua.id_utilisateur = ? 
                ORDER BY ua.date_achat DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}