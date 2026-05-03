<?php
require_once __DIR__ . '/../config/db_connect.php';

class BudgetService {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function setBudget($userId, $montant) {
        $sql = "INSERT INTO budget (id_utilisateur, montant) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE montant = VALUES(montant)";
        return $this->db->prepare($sql)->execute([$userId, $montant]);
    }

    public function getAllBudgets() {
        $sql = "SELECT u.nom as user_nom, b.*, 
                COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) as total_depense 
                FROM budget b 
                JOIN utilisateur u ON b.id_utilisateur = u.id_utilisateur 
                ORDER BY u.nom";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBudgetByUserId($userId) {
        $sql = "SELECT * FROM budget WHERE id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
}