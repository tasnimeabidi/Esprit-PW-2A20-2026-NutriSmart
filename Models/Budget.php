<?php
declare(strict_types=1);

/**
 * Budget utilisateur (logique Youssef Mejri — table `budget`).
 */
class Budget
{
    private PDO $conn;
    private string $table_name = 'budget';

    public $id_utilisateur;
    public $montant;
    public $date_creation;

    public function __construct(PDO $db)
    {
        $this->conn = $db;
    }

    public function setBudget($montant): bool
    {
        $query = 'INSERT INTO ' . $this->table_name . '
                  (id_utilisateur, montant, date_creation)
                  VALUES (:id_utilisateur, :montant, NOW())
                  ON DUPLICATE KEY UPDATE montant = VALUES(montant), date_creation = NOW()';

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);
        $stmt->bindParam(':montant', $montant);

        return $stmt->execute();
    }

    public function getByUserId($userId)
    {
        $query = 'SELECT * FROM ' . $this->table_name . '
                  WHERE id_utilisateur = :id_utilisateur LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAllWithUserDetails(): array
    {
        $query = 'SELECT u.nom as user_nom, b.*,
                  COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) as total_depense
                  FROM ' . $this->table_name . ' b
                  JOIN utilisateur u ON b.id_utilisateur = u.id_utilisateur
                  ORDER BY u.nom ASC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function readAll(): array
    {
        $query = 'SELECT * FROM ' . $this->table_name . ' ORDER BY date_creation DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function deleteByUserId(): bool
    {
        $query = 'DELETE FROM ' . $this->table_name . ' WHERE id_utilisateur = :id_utilisateur';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);

        return $stmt->execute();
    }

    public function exists(): bool
    {
        $query = 'SELECT COUNT(*) as count FROM ' . $this->table_name . '
                  WHERE id_utilisateur = :id_utilisateur';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return isset($row['count']) && (int) $row['count'] > 0;
    }

    public function getBudgetStatus()
    {
        $query = 'SELECT b.montant,
                  COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0) as total_depense,
                  (b.montant - COALESCE((SELECT SUM(prix_total) FROM user_achat WHERE id_utilisateur = b.id_utilisateur), 0)) as remaining
                  FROM ' . $this->table_name . ' b
                  WHERE b.id_utilisateur = :id_utilisateur
                  LIMIT 1';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id_utilisateur', $this->id_utilisateur, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
