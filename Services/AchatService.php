<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Achats alimentaires — table `user_achat` (logique Youssef Mejri).
 */
class AchatService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function addPurchase($userId, $alimentId, $qty, $totalPrice): bool
    {
        $sql = 'INSERT INTO user_achat (id_utilisateur, id_aliment, quantite, prix_total) VALUES (?, ?, ?, ?)';
        $result = $this->db->prepare($sql)->execute([(int) $userId, (int) $alimentId, $qty, $totalPrice]);

        if ($result) {
            require_once __DIR__ . '/BudgetService.php';
            $budgetService = new BudgetService();
            $budgetService->checkAndNotifyBudgetExceeded((int) $userId);

            $this->sendPurchaseConfirmationEmail((int) $userId, (int) $alimentId, $qty, $totalPrice);
        }

        return $result;
    }

    private function sendPurchaseConfirmationEmail(int $userId, int $alimentId, $qty, $totalPrice): void
    {
        $sql = 'SELECT u.nom as user_name, u.email as user_email, a.nom as aliment_nom
                FROM utilisateur u
                JOIN aliment a ON a.id = ?
                WHERE u.id_utilisateur = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$alimentId, $userId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$data) {
            return;
        }

        require_once __DIR__ . '/EmailService.php';
        $emailService = new EmailService();
        $emailService->sendPurchaseConfirmationEmail(
            (string) ($data['user_email'] ?? ''),
            (string) ($data['user_name'] ?? 'Utilisateur'),
            (string) $data['aliment_nom'],
            $qty,
            $totalPrice
        );
    }

    public function deletePurchase($id): bool
    {
        $sql = 'DELETE FROM user_achat WHERE id = ?';

        return $this->db->prepare($sql)->execute([(int) $id]);
    }

    public function updatePurchase($id, $qty, $totalPrice): bool
    {
        $sql = 'UPDATE user_achat SET quantite = ?, prix_total = ? WHERE id = ?';
        $result = $this->db->prepare($sql)->execute([$qty, $totalPrice, (int) $id]);

        if ($result) {
            $sql = 'SELECT id_utilisateur FROM user_achat WHERE id = ?';
            $stmt = $this->db->prepare($sql);
            $stmt->execute([(int) $id]);
            $purchase = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($purchase) {
                require_once __DIR__ . '/BudgetService.php';
                $budgetService = new BudgetService();
                $budgetService->checkAndNotifyBudgetExceeded((int) $purchase['id_utilisateur']);
            }
        }

        return $result;
    }

    public function getTotalDepensesByUserId($userId): float
    {
        $sql = 'SELECT COALESCE(SUM(prix_total), 0) as total FROM user_achat WHERE id_utilisateur = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return floatval($result['total'] ?? 0);
    }

    public function getAchatsByUserId($userId): array
    {
        $sql = 'SELECT ua.id, ua.id_utilisateur, ua.id_aliment, ua.quantite, ua.prix_total, ua.date_achat, a.nom as aliment_nom
                FROM user_achat ua
                JOIN aliment a ON ua.id_aliment = a.id
                WHERE ua.id_utilisateur = ?
                ORDER BY ua.date_achat DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
