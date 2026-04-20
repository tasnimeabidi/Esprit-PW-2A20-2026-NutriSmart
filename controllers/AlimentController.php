<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Aliment.php';

class AlimentController {
    private $db;
    private $aliment;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->aliment = new Aliment($this->db);
    }

    public function listAliments() {
        $stmt = $this->aliment->readAll();
        $aliments = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                // Compatibilité avec la vue aliments.php
                $row['id'] = $row['id_aliment'];
                $row['calories_100g'] = $row['calories'];
                $aliments[] = $row;
            }
        }
        return $aliments;
    }

    public function getAliment($id) {
        $this->aliment->id = $id;
        if ($this->aliment->readOne()) {
            return [
                "id" => $this->aliment->id,
                "nom" => $this->aliment->nom,
                "categorie" => $this->aliment->categorie,
                "calories" => $this->aliment->calories,
                "proteines" => $this->aliment->proteines,
                "glucides" => $this->aliment->glucides,
                "lipides" => $this->aliment->lipides,
                "prix" => $this->aliment->prix
            ];
        }
        return null;
    }

    public function createAliment($data) {
        $this->aliment->nom = $data['nom_aliment'] ?? ($data['nom'] ?? '');
        $this->aliment->categorie = $data['categorie'] ?? 'autre';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->create()) {
            return ["status" => "success", "message" => "Aliment créé avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la création de l'aliment"];
    }

    public function updateAliment($data) {
        $this->aliment->id = $data['id'] ?? $data['id_aliment'];
        $this->aliment->nom = $data['nom_aliment'] ?? ($data['nom'] ?? '');
        $this->aliment->categorie = $data['categorie'] ?? 'autre';
        $this->aliment->calories = $data['calories'] ?? 0;
        $this->aliment->proteines = $data['proteines'] ?? 0;
        $this->aliment->glucides = $data['glucides'] ?? 0;
        $this->aliment->lipides = $data['lipides'] ?? 0;
        $this->aliment->prix = $data['prix'] ?? 0;

        if ($this->aliment->update()) {
            return ["status" => "success", "message" => "Aliment mis à jour avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la mise à jour de l'aliment"];
    }

    public function deleteAliment($id) {
        $this->aliment->id = $id;
        if ($this->aliment->delete()) {
            return ["status" => "success", "message" => "Aliment supprimé avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la suppression de l'aliment"];
    }
}

// Handle AJAX/API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    // Only intercept if the action is meant for the API endpoint
    if (in_array($action, ['create', 'update', 'delete', 'list'])) {
        header('Content-Type: application/json');
        
        // Ensure no output before this
        ob_clean();
        
        $controller = new AlimentController();

        if ($action === 'list') {
            echo json_encode(["status" => "success", "data" => $controller->listAliments()]);
            exit;
        } elseif ($action === 'create') {
            echo json_encode($controller->createAliment($_POST));
            exit;
        } elseif ($action === 'update') {
            echo json_encode($controller->updateAliment($_POST));
            exit;
        } elseif ($action === 'delete') {
            echo json_encode($controller->deleteAliment($_POST['id']));
            exit;
        }
    }
}
