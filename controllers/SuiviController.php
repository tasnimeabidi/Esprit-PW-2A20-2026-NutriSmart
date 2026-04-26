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

    public function getDb() {
        return $this->db;
    }

    public function listLogs($user_id = 1) {
        return $this->suivi->readAll($user_id);
    }

    public function addLog($data) {
        $user_id = $data['user_id'] ?? 1;
        $date = $data['date'] ?? date('Y-m-d');
        $type = $data['type'];
        $calories = abs((int)($data['calories'] ?? 0));
        $description = trim($data['description'] ?? 'Log');

        // Contrôle de saisie (Validation)
        if ($type === 'activity' || $type === 'meal') {
            if (strlen($description) < 3) {
                return ["status" => "error", "message" => "La description doit contenir au moins 3 caractères."];
            }
            $disallowed = ['voiture', 'car', 'avion', 'plane'];
            foreach ($disallowed as $word) {
                if (stripos($description, $word) !== false) {
                    return ["status" => "error", "message" => "Veuillez entrer un aliment ou une activité valide."];
                }
            }
        }

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
                $last_nid = $this->db->lastInsertId();
                // DYNAMIC IMPACT: Auto-create a weight record reflecting the calorie intake
                $lastWeight = $this->db->prepare("SELECT poids FROM journal_poids WHERE id_utilisateur = ? ORDER BY date_mesure DESC LIMIT 1");
                $lastWeight->execute([$active_uid]);
                $poidsBase = $lastWeight->fetchColumn();
                
                if ($poidsBase) {
                    $newPoids = $poidsBase + ($calories / 7700); 
                    $this->suivi->createWeight($active_uid, $date, $newPoids, null, $last_nid);
                }
                
                return ["status" => "success", "message" => "Repas ajouté" . ($poidsBase ? " (Poids actualisé)" : "")];
            }
        } elseif ($type === 'activity') {
            if ($this->suivi->createSport($active_uid, $date, $description, $calories)) {
                $last_sid = $this->db->lastInsertId();
                // DYNAMIC IMPACT: Auto-create a weight record reflecting the calorie burn
                $lastWeight = $this->db->prepare("SELECT poids FROM journal_poids WHERE id_utilisateur = ? ORDER BY date_mesure DESC LIMIT 1");
                $lastWeight->execute([$active_uid]);
                $poidsBase = $lastWeight->fetchColumn(); 
                
                if ($poidsBase) {
                    $newPoids = $poidsBase - ($calories / 7700); 
                    $this->suivi->createWeight($active_uid, $date, $newPoids, $last_sid, null);
                }

                return ["status" => "success", "message" => "Activité ajoutée" . ($poidsBase ? " (Poids actualisé)" : "")];
            }
        }
        elseif ($type === 'weight') {
            $poids = $data['weight'] ?? 0;
            
            // Get latest Sport ID for this user
            $sidRow = $this->db->prepare("SELECT id FROM journal_sport WHERE id_utilisateur = ? ORDER BY date_seance DESC LIMIT 1");
            $sidRow->execute([$active_uid]);
            $sid = $sidRow->fetchColumn() ?: null;

            // Get latest Nutrition ID for this user
            $nidRow = $this->db->prepare("SELECT id FROM journal_nutrition WHERE id_utilisateur = ? ORDER BY date_entree DESC LIMIT 1");
            $nidRow->execute([$active_uid]);
            $nid = $nidRow->fetchColumn() ?: null;

            if ($this->suivi->createWeight($active_uid, $date, $poids, $sid, $nid)) {
                return ["status" => "success", "message" => "Poids mis à jour (lié à l'activité et à la nutrition récentes)"];
            }
        }
        return ["status" => "error", "message" => "Erreur lors de l'ajout"];
    }

    public function deleteLog($id, $type) {
        if ($this->suivi->delete($id, $type)) {
            return ["status" => "success", "message" => "Log supprimé avec succès"];
        }
        return ["status" => "error", "message" => "Impossible de supprimer le log (ID: $id, Type: $type)"];
    }

    public function updateLog($data) {
        $id = $data['id'];
        $type = $data['type'];
        $calories = abs($data['calories'] ?? 0);
        $description = trim($data['description'] ?? '');

        // Contrôle de saisie (Validation)
        if ($type === 'activity' || $type === 'meal') {
            if (strlen($description) < 3) {
                return ["status" => "error", "message" => "La description doit contenir au moins 3 caractères."];
            }
            $disallowed = ['voiture', 'car', 'avion', 'plane'];
            foreach ($disallowed as $word) {
                if (stripos($description, $word) !== false) {
                    return ["status" => "error", "message" => "Veuillez entrer un aliment ou une activité valide."];
                }
            }
        }
        
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
