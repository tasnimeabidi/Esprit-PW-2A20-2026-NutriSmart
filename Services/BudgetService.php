<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Budget + alerte dépassement (logique Youssef Mejri).
 */
class BudgetService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function setBudget($userId, $montant): bool
    {
        $sql = 'INSERT INTO budget (id_utilisateur, montant) VALUES (?, ?)
                ON DUPLICATE KEY UPDATE montant = VALUES(montant), date_creation = CURRENT_TIMESTAMP';
        $result = $this->db->prepare($sql)->execute([(int) $userId, $montant]);

        if ($result) {
            $this->checkAndNotifyBudgetExceeded((int) $userId);
        }

        return $result;
    }

    public function getAllBudgets(): array
    {
        $sql = 'SELECT u.nom as user_nom, b.*,
                COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) as total_depense
                FROM budget b
                JOIN utilisateur u ON b.id_utilisateur = u.id_utilisateur
                ORDER BY u.nom';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getBudgetByUserId($userId)
    {
        $sql = 'SELECT * FROM budget WHERE id_utilisateur = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getBudgetInfo($userId)
    {
        $sql = 'SELECT b.*,
                COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) AS total_depense
                FROM budget b
                WHERE b.id_utilisateur = ?
                LIMIT 1';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function deleteBudget($userId): bool
    {
        $sql = 'DELETE FROM budget WHERE id_utilisateur = ?';

        return $this->db->prepare($sql)->execute([(int) $userId]);
    }

    public function checkAndNotifyBudgetExceeded($userId): bool
    {
        $budget = $this->getBudgetByUserId($userId);
        if (!$budget) {
            return false;
        }

        require_once __DIR__ . '/AchatService.php';
        $achatService = new AchatService();
        $totalSpent = $achatService->getTotalDepensesByUserId($userId);

        if ($totalSpent > (float) $budget['montant']) {
            if (!$this->hasRecentNotification($userId)) {
                return $this->sendBudgetExceededNotification($userId, (float) $budget['montant'], $totalSpent);
            }
        }

        return false;
    }

    private function hasRecentNotification($userId): bool
    {
        return false;
    }

    private function sendBudgetExceededNotification($userId, $budgetAmount, $totalSpent): bool
    {
        $user = $this->getUserById($userId);
        if (!$user) {
            return false;
        }

        require_once __DIR__ . '/AchatService.php';
        $achatService = new AchatService();
        $purchases = $achatService->getAchatsByUserId($userId);

        require_once __DIR__ . '/EmailService.php';
        $emailService = new EmailService();
        $result = $emailService->sendBudgetExceededEmail(
            $user['email'],
            $user['nom'],
            $budgetAmount,
            $totalSpent,
            $purchases
        );

        if ($result) {
            $this->logNotification($userId, 'budget_exceeded', $totalSpent - $budgetAmount);
        }

        return $result;
    }

    private function getUserById($userId)
    {
        $sql = 'SELECT * FROM utilisateur WHERE id_utilisateur = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $userId]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function logNotification($userId, $type, $amount): void
    {
        error_log('Notification budget user ' . $userId . ': ' . $type . ' — ' . $amount);
    }
}
