<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Accès utilisateur minimal pour le module budget (Youssef Mejri).
 */
class UserService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllUsers(): array
    {
        $sql = 'SELECT * FROM utilisateur ORDER BY nom';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getUserById($id)
    {
        $sql = 'SELECT * FROM utilisateur WHERE id_utilisateur = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
