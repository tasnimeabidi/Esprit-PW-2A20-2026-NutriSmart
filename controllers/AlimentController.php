<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

class AlimentController
{
    private PDO $db;
    private Aliment $aliment;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->aliment = new Aliment($this->db);
    }

    /** @return list<array<string, mixed>> */
    public function listAliments(): array
    {
        $stmt = $this->aliment->readAll();
        $aliments = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $pkVal = $row['id_aliment'] ?? $row['id'] ?? null;
            if ($pkVal === null) {
                continue;
            }
            $row['id'] = $pkVal;
            $row['id_aliment'] = $pkVal;
            $row['calories'] = $row['calories'] ?? $row['calories_100g'] ?? null;
            $row['calories_100g'] = $row['calories_100g'] ?? $row['calories'] ?? null;
            $row['proteines'] = $row['proteines'] ?? $row['proteines_100g'] ?? null;
            $row['glucides'] = $row['glucides'] ?? $row['glucides_100g'] ?? null;
            $row['lipides'] = $row['lipides'] ?? $row['lipides_100g'] ?? null;
            $aliments[] = $row;
        }
        return $aliments;
    }

    /** @return array<string, mixed>|null */
    public function getAliment($id)
    {
        $this->aliment->id = $id;
        if ($this->aliment->readOne()) {
            return [
                'id' => $this->aliment->id,
                'id_aliment' => $this->aliment->id,
                'nom' => $this->aliment->nom,
                'categorie' => $this->aliment->categorie,
                'calories' => $this->aliment->calories,
                'proteines' => $this->aliment->proteines,
                'glucides' => $this->aliment->glucides,
                'lipides' => $this->aliment->lipides,
                'prix' => $this->aliment->prix,
            ];
        }
        return null;
    }

    /** @param array<string, mixed> $data */
    public function createAliment(array $data): array
    {
        $this->aliment->nom = $data['nom_aliment'] ?? ($data['nom'] ?? '');
        $this->aliment->categorie = $data['categorie'] ?? 'autre';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->create()) {
            return ['status' => 'success', 'message' => 'Aliment créé avec succès'];
        }
        return ['status' => 'error', 'message' => "Échec de la création de l'aliment"];
    }

    /** @param array<string, mixed> $data */
    public function updateAliment(array $data): array
    {
        $this->aliment->id = (int) ($data['id'] ?? $data['id_aliment'] ?? 0);
        $this->aliment->nom = $data['nom_aliment'] ?? ($data['nom'] ?? '');
        $this->aliment->categorie = $data['categorie'] ?? 'autre';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->update()) {
            return ['status' => 'success', 'message' => 'Aliment mis à jour avec succès'];
        }
        return ['status' => 'error', 'message' => "Échec de la mise à jour de l'aliment"];
    }

    public function deleteAliment($id): array
    {
        $this->aliment->id = $id;
        if ($this->aliment->delete()) {
            return ['status' => 'success', 'message' => 'Aliment supprimé avec succès'];
        }
        return ['status' => 'error', 'message' => "Échec de la suppression de l'aliment"];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if (in_array($action, ['create', 'update', 'delete', 'list'], true)) {
        header('Content-Type: application/json; charset=utf-8');
        if (ob_get_level() > 0) {
            ob_clean();
        }

        $controller = new AlimentController();

        if ($action === 'list') {
            echo json_encode(['status' => 'success', 'data' => $controller->listAliments()]);
            exit;
        }
        if ($action === 'create') {
            echo json_encode($controller->createAliment($_POST));
            exit;
        }
        if ($action === 'update') {
            echo json_encode($controller->updateAliment($_POST));
            exit;
        }
        if ($action === 'delete') {
            echo json_encode($controller->deleteAliment($_POST['id'] ?? null));
            exit;
        }
    }
}
