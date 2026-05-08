<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Suivi.php';

class SuiviController {
    private $db;
    private $suivi;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->suivi = new Suivi($this->db);
    }

    public function listLogs($user_id = 1) {
        return $this->suivi->readAll($user_id);
    }

    public function addLog($data) {
        $user_id = $data['user_id'] ?? 1;
        $date = $data['date'] ?? date('Y-m-d');
        $type = $data['type'];
        $calories = abs($data['calories'] ?? 0);

        // SMART LINK: Get the first available user ID
        $userRow = $this->db->query("SELECT id_utilisateur FROM utilisateur LIMIT 1")->fetch();
        if (!$userRow) {
            // Self-heal: create a user if the table is totally empty
            $this->db->query("INSERT INTO utilisateur (nom, email, mot_de_passe, role) 
                             VALUES ('Admin', 'admin@nutrismart.com', 'admin123', 'admin')");
            $active_uid = $this->db->lastInsertId();
        } else {
            $active_uid = $userRow['id_utilisateur'];
        }

        if ($type === 'meal') {
            $quantite = abs($data['quantite'] ?? 100);
            $aid = 1;
            $checkAliment = $this->db->query("SELECT id FROM aliment LIMIT 1")->fetch();
            if ($checkAliment) $aid = $checkAliment['id'];
            else {
                $this->db->query("INSERT INTO aliment (nom, categorie, calories_100g) VALUES ('Divers', 'autre', 0)");
                $aid = $this->db->lastInsertId();
            }

            if ($this->suivi->createNutrition($active_uid, $date, $calories, $quantite, $aid)) {
                return ["status" => "success", "message" => "Repas ajouté"];
            }
        } elseif ($type === 'activity') {
            $desc = $data['description'] ?? 'Activité';
            if ($this->suivi->createSport($active_uid, $date, $desc, $calories)) {
                return ["status" => "success", "message" => "Activité ajoutée"];
            }
        }
 elseif ($type === 'weight') {
            $poids = $data['weight'] ?? 0;
            if ($this->suivi->createWeight($user_id, $date, $poids)) {
                return ["status" => "success", "message" => "Poids mis à jour"];
            }
        }
        return ["status" => "error", "message" => "Erreur lors de l'ajout"];
    }

    public function deleteLog($id, $type) {
        if ($this->suivi->delete($id, $type)) {
            return ["status" => "success", "message" => "Log supprimé"];
        }
        return ["status" => "error", "message" => "Erreur lors de la suppression"];
    }

    public function updateLog($data) {
        $id = $data['id'];
        $type = $data['type'];
        $calories = abs($data['calories'] ?? 0);
        $description = $data['description'] ?? '';
        
        if ($this->suivi->updateLog($id, $type, $description, $calories)) {
            return ["status" => "success", "message" => "Log mis à jour"];
        }
        return ["status" => "error", "message" => "Erreur lors de la mise à jour"];
    }
}

// Handle requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $controller = new SuiviController();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        echo json_encode($controller->addLog($_POST));
    } elseif ($action === 'delete') {
        echo json_encode($controller->deleteLog($_POST['id'], $_POST['type']));
    } elseif ($action === 'update') {
        echo json_encode($controller->updateLog($_POST));
    }
}
?>
