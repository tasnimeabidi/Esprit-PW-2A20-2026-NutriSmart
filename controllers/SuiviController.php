<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Suivi.php';
include_once __DIR__ . '/../Models/SuiviDAO.php';

class SuiviController {
    private $db;
    private $suiviDAO;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->suiviDAO = new SuiviDAO($this->db);
    }

    public function getDb() {
        return $this->db;
    }

    public function listLogs($user_id = 1, $search = '', $sort = 'date DESC') {
        return $this->suiviDAO->readAll($user_id, $search, $sort);
    }

    public function addLog($data) {
        $user_id = $data['user_id'] ?? 1;
        $date = $data['date'] ?? date('Y-m-d');
        $type = $data['type'];
        $calories = abs((int)($data['calories'] ?? 0));
        $description = trim($data['description'] ?? 'Log');

        // ANOMALY DETECTION (Advanced Business Logic)
        if ($calories > 5000) {
            $description = "[SUSPECT] " . $description;
        }

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

        // Populate the Entity with request data
        $log = new Suivi();
        $log->setUserId($active_uid);
        $log->setDate($date);
        $log->setType($type);
        $log->setCalories($calories);
        $log->setDescription($description);

        if ($type === 'meal') {
            $quantite = abs($data['quantite'] ?? 100);
            $log->setQuantite($quantite);

            // Fetch or create the specific aliment based on the user's description
            $searchDesc = strtolower($description);
            $stmtAid = $this->db->prepare("SELECT id FROM aliment WHERE LOWER(nom) = ? LIMIT 1");
            $stmtAid->execute([$searchDesc]);
            $checkAliment = $stmtAid->fetch();
            
            if ($checkAliment) {
                $aid = $checkAliment['id'];
            } else {
                // Determine category roughly (just 'autre' as fallback)
                $stmtInsertA = $this->db->prepare("INSERT INTO aliment (nom, categorie, calories_100g) VALUES (?, 'autre', ?)");
                // Estimate calories per 100g based on what they gave for the qty
                $cal100 = $quantite > 0 ? ($calories / $quantite) * 100 : 0;
                $stmtInsertA->execute([$description, $cal100]);
                $aid = $this->db->lastInsertId();
            }
            $log->setIdAliment($aid);

            if ($this->suiviDAO->createNutrition($log)) {
                $last_nid = $this->db->lastInsertId();
                // DYNAMIC IMPACT: Auto-create a weight record reflecting the calorie intake
                $lastWeight = $this->db->prepare("SELECT poids FROM journal_poids WHERE id_utilisateur = ? ORDER BY date_mesure DESC LIMIT 1");
                $lastWeight->execute([$active_uid]);
                $poidsBase = $lastWeight->fetchColumn();
                
                if ($poidsBase) {
                    $newPoids = $poidsBase + ($calories / 7700); 
                    
                    $weightLog = new Suivi();
                    $weightLog->setUserId($active_uid);
                    $weightLog->setDate($date);
                    $weightLog->setPoids($newPoids);
                    $weightLog->setIdNutrition($last_nid);
                    
                    $this->suiviDAO->createWeight($weightLog);
                }
                
                return ["status" => "success", "message" => "Repas ajouté" . ($poidsBase ? " (Poids actualisé)" : "")];
            }
        } elseif ($type === 'activity') {
            if ($this->suiviDAO->createSport($log)) {
                $last_sid = $this->db->lastInsertId();
                // DYNAMIC IMPACT: Auto-create a weight record reflecting the calorie burn
                $lastWeight = $this->db->prepare("SELECT poids FROM journal_poids WHERE id_utilisateur = ? ORDER BY date_mesure DESC LIMIT 1");
                $lastWeight->execute([$active_uid]);
                $poidsBase = $lastWeight->fetchColumn(); 
                
                if ($poidsBase) {
                    $newPoids = $poidsBase - ($calories / 7700); 
                    
                    $weightLog = new Suivi();
                    $weightLog->setUserId($active_uid);
                    $weightLog->setDate($date);
                    $weightLog->setPoids($newPoids);
                    $weightLog->setIdSport($last_sid);
                    
                    $this->suiviDAO->createWeight($weightLog);
                }

                return ["status" => "success", "message" => "Activité ajoutée" . ($poidsBase ? " (Poids actualisé)" : "")];
            }
        }
        elseif ($type === 'weight') {
            $poids = $data['weight'] ?? 0;
            $log->setPoids($poids);
            
            // Get latest Sport ID for this user
            $sidRow = $this->db->prepare("SELECT id FROM journal_sport WHERE id_utilisateur = ? ORDER BY date_seance DESC LIMIT 1");
            $sidRow->execute([$active_uid]);
            $sid = $sidRow->fetchColumn() ?: null;
            $log->setIdSport($sid);

            // Get latest Nutrition ID for this user
            $nidRow = $this->db->prepare("SELECT id FROM journal_nutrition WHERE id_utilisateur = ? ORDER BY date_entree DESC LIMIT 1");
            $nidRow->execute([$active_uid]);
            $nid = $nidRow->fetchColumn() ?: null;
            $log->setIdNutrition($nid);

            if ($this->suiviDAO->createWeight($log)) {
                return ["status" => "success", "message" => "Poids mis à jour (lié à l'activité et à la nutrition récentes)"];
            }
        }
        return ["status" => "error", "message" => "Erreur lors de l'ajout"];
    }

    public function deleteLog($id, $type) {
        if ($this->suiviDAO->delete($id, $type)) {
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
        
        $log = new Suivi();
        $log->setId($id);
        $log->setType($type);
        $log->setCalories($calories);
        $log->setDescription($description);
        $log->setPoids($calories); // as seen in previous logic, weight passes through calories field
        
        if ($this->suiviDAO->updateLog($log)) {
            return ["status" => "success", "message" => "Log mis à jour"];
        }
        return ["status" => "error", "message" => "Erreur lors de la mise à jour"];
    }

    public function getStatistics($user_id = null) {
        if (!$user_id) {
            $userRow = $this->db->query("SELECT id_utilisateur FROM utilisateur LIMIT 1")->fetch();
            $user_id = $userRow ? $userRow['id_utilisateur'] : 1;
        }
        $stats = $this->suiviDAO->getStatistics($user_id);
        return ["status" => "success", "data" => $stats];
    }

    public function askAI($message, $user_id = 1) {
        $stats = $this->suiviDAO->getStatistics($user_id);
        
        $currentTime = date('H:i');
        $currentDate = date('d/m/Y');
        
        $systemPrompt = "Tu es NutriBot, l'expert en nutrition de NutriSmart. 
        Contexte actuel : Nous sommes le {$currentDate} et il est {$currentTime}.
        
        Voici les données de l'utilisateur :
        - Consommé : {$stats['consumed']} kcal
        - Brûlé : {$stats['burned']} kcal
        - Solde : {$stats['balance']} kcal
        - Poids : {$stats['weight']} kg
        - Objectif : {$stats['dynamicGoal']} kcal
        
        Réponds de manière naturelle, concise et en français. Si l'utilisateur pose une question générale (comme l'heure), réponds-lui poliment en utilisant le contexte fourni, tout en restant prêt à l'aider pour sa nutrition.";

        $data = [
            'model' => 'llama3',
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message]
            ],
            'stream' => false
        ];

        $ch = curl_init('http://localhost:11434/api/chat');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["status" => "error", "answer" => "Ollama non détecté. Assurez-vous d'avoir lancé 'ollama run llama3' dans votre terminal."];
        }

        $result = json_decode($response, true);
        $answer = $result['message']['content'] ?? "Désolé, je rencontre une difficulté avec le serveur local Ollama.";
        
        return ["status" => "success", "answer" => $answer];
    }

    public function analyzeVision($image_base64) {
        // Remove data:image/png;base64, prefix if present
        $image_data = preg_replace('#^data:image/\w+;base64,#i', '', $image_base64);

        $prompt = "Tu es un expert en nutrition. Analyse cette image et identifie l'aliment présent. 
        Même s'il s'agit d'un fruit simple ou d'une image sur un écran, tu DOIS obligatoirement donner une estimation calorique.
        Réponds UNIQUEMENT au format suivant, sans aucune autre phrase : 
        'Nom du produit | Nombre kcal'
        Ne donne aucune explication, juste ce format.";

        $data = [
            'model' => 'llava',
            'prompt' => $prompt,
            'images' => [$image_data],
            'stream' => false,
            'options' => [
                'num_predict' => 20, // Keep it short and fast
                'temperature' => 0.1 // More deterministic
            ],
            'keep_alive' => 0 // Force fresh context for each scan
        ];

        $ch = curl_init('http://localhost:11434/api/generate');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ["status" => "error", "message" => "Ollama Vision non détecté."];
        }

        $result = json_decode($response, true);
        $output = $result['response'] ?? "Inconnu | 0 kcal";
        
        // More robust parsing for LLaVA output
        // Try to find a number in the string
        preg_match('/(\d+)/', $output, $matches);
        $cals = isset($matches[1]) ? (int)$matches[1] : 0;
        
        // If the output is "Apple 150 calorie", use that as name but extract cals
        $name = trim(preg_replace('/\d+.*$/', '', $output)); // Remove number and everything after for name
        if (empty($name)) $name = "Plat identifié";

        return ["status" => "success", "food" => $name, "calories" => $cals];
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
    } elseif ($action === 'chat') {
        echo json_encode($controller->askAI($_POST['message'] ?? ''));
    } elseif ($action === 'vision') {
        echo json_encode($controller->analyzeVision($_POST['image'] ?? ''));
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    if ($action === 'stats') {
        header('Content-Type: application/json');
        $controller = new SuiviController();
        echo json_encode($controller->getStatistics($_GET['user_id'] ?? null));
    }
}
?>
