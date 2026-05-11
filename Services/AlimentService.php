<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

/**
 * Catalogue aliments pour la boutique (logique Youssef Mejri).
 */
class AlimentService
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    public function getAllAliments(): array
    {
        $sql = 'SELECT * FROM aliment ORDER BY nom ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getAlimentById($id)
    {
        $sql = 'SELECT * FROM aliment WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(int) $id]);

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getAlimentsByCategory($categorie): array
    {
        $sql = 'SELECT * FROM aliment WHERE categorie = ? ORDER BY nom ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([(string) $categorie]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function searchAliments($search): array
    {
        $sql = 'SELECT * FROM aliment WHERE nom LIKE ? ORDER BY nom ASC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['%' . $search . '%']);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
