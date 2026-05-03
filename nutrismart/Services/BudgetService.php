<?php
require_once __DIR__ . '/../config/db_connect.php';
require_once __DIR__ . '/EmailService.php';

class BudgetService {
    public $db;
    private $emailService;

    public function __construct() {
        $this->db = getConnection();
        $this->emailService = new EmailService();
    }

    public function setBudget($userId, $montant) {
        $sql = "INSERT INTO budget (id_utilisateur, montant) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE montant = VALUES(montant)";
        $result = $this->db->prepare($sql)->execute([$userId, $montant]);

        // Vérifier si le budget est dépassé après la mise à jour
        $this->checkAndNotifyBudgetExceeded($userId);

        return $result;
    }

    public function getAllBudgets() {
        $sql = "SELECT u.nom as user_nom, b.*, 
                COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) as total_depense 
                FROM budget b 
                JOIN utilisateur u ON b.id_utilisateur = u.id_utilisateur 
                GROUP BY b.id_utilisateur
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

    public function deleteBudget($userId) {
        $sql = "DELETE FROM budget WHERE id_utilisateur = ?";
        return $this->db->prepare($sql)->execute([$userId]);
    }

    public function checkAndNotifyBudgetExceeded($userId) {
        $budget = $this->getBudgetByUserId($userId);
        if (!$budget) {
            return false;
        }

        require_once __DIR__ . '/AchatService.php';
        $achatService = new AchatService();
        $totalSpent = $achatService->getTotalDepensesByUserId($userId);

        if ($totalSpent > $budget['montant']) {
            // Vérifier si une notification a déjà été envoyée récemment (éviter le spam)
            if (!$this->hasRecentNotification($userId)) {
                return $this->sendBudgetExceededNotification($userId, $budget['montant'], $totalSpent);
            }
        }

        return false;
    }

    private function hasRecentNotification($userId) {
        // Cette méthode pourrait vérifier dans une table de notifications
        // Pour l'instant, on retourne false pour toujours envoyer
        // En production, implémenter une logique pour éviter le spam
        return false;
    }

    private function sendBudgetExceededNotification($userId, $budgetAmount, $totalSpent) {
        // Récupérer les informations de l'utilisateur
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        // Récupérer les achats de l'utilisateur
        require_once __DIR__ . '/AchatService.php';
        $achatService = new AchatService();
        $purchases = $achatService->getAchatsByUserId($userId);

        // Envoyer l'email
        $result = $this->emailService->sendBudgetExceededEmail(
            $user['email'],
            $user['nom'],
            $budgetAmount,
            $totalSpent,
            $purchases
        );

        // Enregistrer la notification (optionnel)
        if ($result) {
            $this->logNotification($userId, 'budget_exceeded', $totalSpent - $budgetAmount);
        }

        return $result;
    }

    private function getUserById($userId) {
        $sql = "SELECT * FROM utilisateur WHERE id_utilisateur = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    private function logNotification($userId, $type, $amount) {
        // Optionnel: enregistrer les notifications dans une table dédiée
        // Pour l'instant, juste logger
        error_log("Notification sent to user $userId: $type - Amount: $amount");
    }
}
