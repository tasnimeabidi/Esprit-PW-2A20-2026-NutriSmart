<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

class SuiviController {
    private PDO $db;
    private SuiviDAO $suiviDAO;

    public function __construct() {
        $this->db = Database::getConnection();
        $this->suiviDAO = new SuiviDAO($this->db);
    }

    public function getDb(): PDO {
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

    /**
     * Appel HTTP POST vers l’API Ollama (évite doublons curl / timeouts).
     *
     * @return array{response: string|false, curl_error: string, http_code: int, decoded: array|null}
     */
    private function ollamaPost(string $path, array $payload, int $timeoutSeconds = 300): array {
        $ch = curl_init(OLLAMA_BASE_URL . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => $timeoutSeconds,
        ]);
        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        $decoded = null;
        if (is_string($response) && $response !== '') {
            $tmp = json_decode($response, true);
            $decoded = is_array($tmp) ? $tmp : null;
        }

        return [
            'response' => $response,
            'curl_error' => $curlError,
            'http_code' => $httpCode,
            'decoded' => $decoded,
        ];
    }

    /** Erreur du type « model 'x' not found » renvoyée par Ollama. */
    private function ollamaIsModelNotFoundError(?array $decoded): bool {
        $err = (string) ($decoded['error'] ?? '');
        return $err !== '' && stripos($err, 'not found') !== false;
    }

    /** Échec chargement inférence (RAM / VRAM insuffisante, runner terminé, etc.). */
    private function ollamaIsVisionResourceError(?array $decoded): bool {
        $err = strtolower((string) ($decoded['error'] ?? ''));
        if ($err === '') {
            return false;
        }
        foreach (['allocate', 'memory', 'terminated', 'panic', 'buffer', 'oom', 'cuda', 'gpu memory'] as $needle) {
            if (strpos($err, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    private function ollamaVisionUserMessage(string $technicalError): string {
        $lower = strtolower($technicalError);
        if (
            strpos($lower, 'allocate') !== false
            || strpos($lower, 'buffer') !== false
            || strpos($lower, 'memory') !== false
            || strpos($lower, 'terminated') !== false
            || strpos($lower, 'panic') !== false
        ) {
            return 'Mémoire insuffisante pour le modèle vision (Ollama). '
                . 'Dans le fichier .env du projet, essayez : OLLAMA_VISION_MODEL=moondream et '
                . 'OLLAMA_VISION_NUM_CTX=512 (puis redémarrez Ollama). '
                . 'Vous pouvez aussi exécuter : ollama pull moondream';
        }

        return 'Ollama Vision : ' . $technicalError;
    }

    /** Télécharge un modèle via POST /api/pull (équivalent à `ollama pull`). */
    private function ollamaPullModel(string $model): array {
        @set_time_limit(0);
        $exec = $this->ollamaPost('/api/pull', [
            'name' => $model,
            'stream' => false,
        ], 3600);
        if (($exec['decoded'] ?? null) === null && is_string($exec['response']) && $exec['response'] !== '') {
            $lines = preg_split('/\r?\n/', trim($exec['response']));
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }
                $tmp = json_decode($line, true);
                if (is_array($tmp)) {
                    $exec['decoded'] = $tmp;
                    break;
                }
            }
        }
        return $exec;
    }

    /**
     * Charge le modèle dans Ollama (best-effort) pour réduire la latence du 1er message.
     */
    public function warmChatModel(): array {
        $chatOptions = ['num_predict' => 1, 'num_ctx' => 256, 'temperature' => 0.1];
        $chatNumGpu = defined('OLLAMA_CHAT_NUM_GPU') ? OLLAMA_CHAT_NUM_GPU : 0;
        if ($chatNumGpu >= 0) {
            $chatOptions['num_gpu'] = $chatNumGpu;
        }
        $data = [
            'model' => OLLAMA_CHAT_MODEL,
            'messages' => [['role' => 'user', 'content' => '.']],
            'stream' => false,
            'options' => $chatOptions,
        ];
        $exec = $this->ollamaPost('/api/chat', $data, 120);

        return ['status' => 'ok', 'warmed' => ($exec['curl_error'] === '' && isset($exec['decoded']) && !isset($exec['decoded']['error']))];
    }

    public function askAI($message, $user_id = 1) {
        $trimmedForFast = trim((string) $message);
        if ($trimmedForFast !== '' && preg_match('/^(bonjour|salut|coucou|hello|hi|hey|bonsoir|bonne\s+journée)\s*[!.?…]*$/iu', $trimmedForFast)) {
            return [
                'status' => 'success',
                'answer' => 'Bonjour ! Je suis NutriBot — que souhaitez-vous savoir sur votre nutrition ou vos calories aujourd’hui ?',
            ];
        }

        $stats = $this->suiviDAO->getStatistics($user_id);
        
        $currentTime = date('H:i');
        $currentDate = date('d/m/Y');
        
        $systemPrompt = "NutriBot (NutriSmart). {$currentDate} {$currentTime}. "
            . "Consommé {$stats['consumed']} kcal, brûlé {$stats['burned']} kcal, solde {$stats['balance']} kcal, "
            . "poids {$stats['weight']} kg, objectif {$stats['dynamicGoal']} kcal. Réponses courtes en français.";

        $trimmedUser = trim((string) $message);
        $len = mb_strlen($trimmedUser);
        if ($len <= 30) {
            $numPredict = 48;
        } elseif ($len <= 100) {
            $numPredict = 120;
        } else {
            $numPredict = 200;
        }

        $chatOptions = [
            'num_predict' => $numPredict,
            'num_ctx' => 768,
            'temperature' => 0.42,
            'top_k' => 20,
            'top_p' => 0.78,
        ];
        $chatNumGpu = defined('OLLAMA_CHAT_NUM_GPU') ? OLLAMA_CHAT_NUM_GPU : 0;
        if ($chatNumGpu >= 0) {
            $chatOptions['num_gpu'] = $chatNumGpu;
        }

        $data = [
            'model' => OLLAMA_CHAT_MODEL,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $message],
            ],
            'stream' => false,
            'keep_alive' => '45m',
            'options' => $chatOptions,
        ];

        $result = null;
        for ($attempt = 0; $attempt < 2; $attempt++) {
            $exec = $this->ollamaPost('/api/chat', $data);

            if ($exec['response'] === false || $exec['curl_error'] !== '') {
                return [
                    "status" => "error",
                    "answer" => "Connexion à Ollama impossible (" . OLLAMA_BASE_URL . "). Démarrez l’application Ollama (Windows : menu Démarrer → Ollama). "
                        . "Si le problème continue, définissez OLLAMA_BASE_URL dans le fichier .env. Détail : " . ($exec['curl_error'] ?: 'réponse vide'),
                ];
            }

            $result = $exec['decoded'] ?? [];
            if (!isset($result['error'])) {
                break;
            }

            if ($attempt === 0 && $this->ollamaIsModelNotFoundError($result)) {
                $pullExec = $this->ollamaPullModel(OLLAMA_CHAT_MODEL);
                $pullDecoded = $pullExec['decoded'] ?? [];
                if ($pullExec['response'] === false || $pullExec['curl_error'] !== '' || isset($pullDecoded['error'])) {
                    return [
                        "status" => "error",
                        "answer" => "Ollama : téléchargement du modèle « " . OLLAMA_CHAT_MODEL . " » impossible. "
                            . ($pullDecoded['error'] ?? $pullExec['curl_error'] ?: 'réponse vide'),
                    ];
                }
                continue;
            }

            return [
                "status" => "error",
                "answer" => "Ollama : " . $result['error'],
            ];
        }

        if ($result === null || isset($result['error'])) {
            return [
                "status" => "error",
                "answer" => "Ollama : " . ($result['error'] ?? 'réponse invalide'),
            ];
        }

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

        $visionCtx = defined('OLLAMA_VISION_NUM_CTX') ? OLLAMA_VISION_NUM_CTX : 512;
        $visionOptions = [
            'num_predict' => 48,
            'temperature' => 0.1,
            'num_ctx' => $visionCtx,
            'num_batch' => 1,
        ];
        $visionNumGpu = defined('OLLAMA_VISION_NUM_GPU') ? OLLAMA_VISION_NUM_GPU : 0;
        if ($visionNumGpu >= 0) {
            $visionOptions['num_gpu'] = $visionNumGpu;
        }

        $fallbackVision = 'moondream';
        $modelsQueue = [OLLAMA_VISION_MODEL];
        if (OLLAMA_VISION_MODEL !== $fallbackVision) {
            $modelsQueue[] = $fallbackVision;
        }
        $modelsQueue = array_values(array_unique($modelsQueue));

        $result = null;
        foreach ($modelsQueue as $modelIndex => $modelName) {
            $data = [
                'model' => $modelName,
                'prompt' => $prompt,
                'images' => [$image_data],
                'stream' => false,
                'options' => $visionOptions,
                'keep_alive' => 0,
            ];

            for ($attempt = 0; $attempt < 2; $attempt++) {
                $exec = $this->ollamaPost('/api/generate', $data, 300);

                if ($exec['response'] === false || $exec['curl_error'] !== '') {
                    return [
                        'status' => 'error',
                        'message' => 'Connexion à Ollama impossible (' . OLLAMA_BASE_URL . '). Détail : '
                            . ($exec['curl_error'] ?: 'réponse vide'),
                    ];
                }

                $decoded = $exec['decoded'] ?? [];
                if (!isset($decoded['error'])) {
                    $result = $decoded;
                    break 2;
                }

                if ($attempt === 0 && $this->ollamaIsModelNotFoundError($decoded)) {
                    $pullExec = $this->ollamaPullModel($modelName);
                    $pullDecoded = $pullExec['decoded'] ?? [];
                    if ($pullExec['response'] === false || $pullExec['curl_error'] !== '' || isset($pullDecoded['error'])) {
                        return [
                            'status' => 'error',
                            'message' => 'Ollama Vision : téléchargement du modèle « ' . $modelName . ' » impossible. '
                                . ($pullDecoded['error'] ?? $pullExec['curl_error'] ?: 'réponse vide'),
                        ];
                    }
                    continue;
                }

                $errMsg = (string) ($decoded['error'] ?? '');
                $hasNextModel = isset($modelsQueue[$modelIndex + 1]);
                if ($this->ollamaIsVisionResourceError($decoded) && $hasNextModel) {
                    break;
                }

                return [
                    'status' => 'error',
                    'message' => $this->ollamaVisionUserMessage($errMsg),
                ];
            }
        }

        if ($result === null || isset($result['error'])) {
            return [
                'status' => 'error',
                'message' => $this->ollamaVisionUserMessage((string) ($result['error'] ?? 'réponse invalide')),
            ];
        }
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

// API AJAX (fetch depuis suivi-statistiques.php)
if (
    isset($_SERVER['SCRIPT_FILENAME'])
    && basename((string) $_SERVER['SCRIPT_FILENAME']) === 'SuiviController.php'
) {
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
        } elseif ($action === 'warm_chat') {
            echo json_encode($controller->warmChatModel());
        } elseif ($action === 'vision') {
            echo json_encode($controller->analyzeVision($_POST['image'] ?? ''));
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        if ($action === 'stats') {
            header('Content-Type: application/json');
            $controller = new SuiviController();
            echo json_encode($controller->getStatistics(isset($_GET['user_id']) ? (int) $_GET['user_id'] : null));
        }
    }
}
