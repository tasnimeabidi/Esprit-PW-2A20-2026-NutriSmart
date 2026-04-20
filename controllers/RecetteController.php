<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Recette.php';

class RecetteController {
    private $db;
    private $recette;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->recette = new Recette($this->db);
    }

    public function listRecettesByStatus($status = 'approved') {
        $stmt = $this->recette->readByStatus($status);
        $recettes = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $recettes[] = $row;
            }
        }
        return $recettes;
    }

    public function listRecettes() {
        return $this->listRecettesByStatus('approved');
    }

    public function getRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->readOne()) {
            return [
                "id" => $this->recette->id,
                "nom" => $this->recette->nom,
                "instructions" => $this->recette->instructions,
                "calories_totales" => $this->recette->calories_totales
            ];
        }
        return null;
    }

    public function createRecette($data, $status = 'pending') {
        $this->recette->nom = $data['nom_recette'] ?? ($data['nom'] ?? '');
        $this->recette->instructions = $data['instructions'] ?? '';
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;
        $this->recette->status = $status;

        if ($this->recette->create()) {
            return ["status" => "success", "message" => "Recette créée avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la création de la recette"];
    }

    public function updateRecette($data) {
        $this->recette->id = $data['id'];
        $this->recette->nom = $data['nom_recette'] ?? ($data['nom'] ?? '');
        $this->recette->instructions = $data['instructions'] ?? '';
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;

        if ($this->recette->update()) {
            return ["status" => "success", "message" => "Recette mise à jour avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la mise à jour de la recette"];
    }

    public function deleteRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->delete()) {
            return ["status" => "success", "message" => "Recette supprimée avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la suppression de la recette"];
    }

    public function approveRecette($id) {
        if ($this->recette->updateStatus($id, 'approved')) {
            return ["status" => "success", "message" => "Recette approuvée"];
        }
        return ["status" => "error", "message" => "Échec de l'approbation"];
    }

    public function rejectRecette($id) {
        return $this->deleteRecette($id);
    }
}

// Handle AJAX/API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $controller = new RecetteController();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        echo json_encode($controller->createRecette($_POST));
        exit;
    } elseif ($action === 'update') {
        echo json_encode($controller->updateRecette($_POST));
        exit;
    } elseif ($action === 'delete') {
        echo json_encode($controller->deleteRecette($_POST['id']));
        exit;
    }
}
?>
