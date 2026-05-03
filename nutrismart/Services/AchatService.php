<?php
require_once __DIR__ . '/../config/db_connect.php';

class AchatService {
    private $db;

    public function __construct() {
        $this->db = getConnection();
    }

    public function addPurchase($userId, $alimentId, $qty, $totalPrice) {
        $sql = "INSERT INTO user_achat (id_utilisateur, id_aliment, quantite, prix_total) VALUES (?, ?, ?, ?)";
        $result = $this->db->prepare($sql)->execute([$userId, $alimentId, $qty, $totalPrice]);

        if ($result) {
            require_once __DIR__ . '/BudgetService.php';
            $budgetService = new BudgetService();
            $budgetService->checkAndNotifyBudgetExceeded($userId);

            $this->sendPurchaseConfirmationEmail($userId, $alimentId, $qty, $totalPrice);
        }

        return $result;
    }

    public function deletePurchase($id) {
        $sql = "DELETE FROM user_achat WHERE id = ?";
        return $this->db->prepare($sql)->execute([$id]);
    }

    public function updatePurchase($id, $qty, $totalPrice) {
        $sql = "UPDATE user_achat SET quantite = ?, prix_total = ? WHERE id = ?";
        $result = $this->db->prepare($sql)->execute([$qty, $totalPrice, $id]);

        // Vérifier le dépassement de budget après la mise à jour
        if ($result) {
            // Récupérer l'ID utilisateur de l'achat
            $sql = "SELECT id_utilisateur FROM user_achat WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            $purchase = $stmt->fetch();

            if ($purchase) {
                require_once __DIR__ . '/BudgetService.php';
                $budgetService = new BudgetService();
                $budgetService->checkAndNotifyBudgetExceeded($purchase['id_utilisateur']);
            }
        }

        return $result;
    }

    private function sendPurchaseConfirmationEmail($userId, $alimentId, $qty, $totalPrice) {
        $sql = "SELECT u.nom as user_name, u.email as user_email, a.nom as aliment_nom
                FROM utilisateur u
                JOIN aliment a ON a.id = ?
                WHERE u.id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$alimentId, $userId]);
        $data = $stmt->fetch();

        if (!$data || empty($data['user_email'])) {
            return false;
        }

        require_once __DIR__ . '/EmailService.php';
        $emailService = new EmailService();
        return $emailService->sendPurchaseConfirmationEmail(
            $data['user_email'],
            $data['user_name'],
            $data['aliment_nom'],
            $qty,
            $totalPrice
        );
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
        $sql = "SELECT ua.id, ua.id_utilisateur, ua.id_aliment, ua.quantite, ua.prix_total, ua.date_achat, a.nom as aliment_nom 
                FROM user_achat ua 
                JOIN aliment a ON ua.id_aliment = a.id 
                WHERE ua.id_utilisateur = ? 
                ORDER BY ua.date_achat DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}