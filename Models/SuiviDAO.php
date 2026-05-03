<?php
class SuiviDAO {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all logs (JOINing nutrition with aliment entity and sport) with Search and Sort
    public function readAll($user_id = null, $search = '', $sort = 'date DESC') {
        // Base query using a subquery to allow sorting and filtering on the union
        $query = "SELECT * FROM (
                    SELECT n.id, n.id_utilisateur as user_id, 'meal' as type, n.date_entree as date, n.calories, COALESCE(a.nom, 'Aliment Inconnu') as description 
                    FROM journal_nutrition n
                    LEFT JOIN aliment a ON n.id_aliment = a.id
                    UNION
                    SELECT s.id, s.id_utilisateur as user_id, 'activity' as type, s.date_seance as date, s.calories_depensees as calories, s.type_sport as description 
                    FROM journal_sport s
                    UNION
                    SELECT w.id, w.id_utilisateur as user_id, 'weight' as type, w.date_mesure as date, 0 as calories, 
                           CONCAT(w.poids, ' kg (Sport: ', COALESCE(s.type_sport, 'N/A'), ', Cal: ', COALESCE(n.calories, '0'), ')') as description 
                    FROM journal_poids w
                    LEFT JOIN journal_sport s ON w.id_sport = s.id
                    LEFT JOIN journal_nutrition n ON w.id_nutrition = n.id
                  ) AS combined_logs
                  WHERE 1=1";
        
        $params = [];
        if ($user_id) {
            $query .= " AND user_id = :uid";
            $params[':uid'] = $user_id;
        }

        if (!empty($search)) {
            $query .= " AND description LIKE :search";
            $params[':search'] = "%$search%";
        }

        // Whitelist sorting to prevent SQL injection
        $allowedSorts = ['date DESC', 'date ASC', 'calories DESC', 'calories ASC', 'type ASC', 'type DESC'];
        if (!in_array($sort, $allowedSorts)) {
            $sort = 'date DESC';
        }
        
        $query .= " ORDER BY " . $sort;

        $stmt = $this->conn->prepare($query);
        foreach ($params as $key => &$val) {
            $stmt->bindParam($key, $val);
        }
        $stmt->execute();
        return $stmt;
    }

    // Create Nutrition Log
    public function createNutrition(Suivi $suivi) {
        $query = "INSERT INTO journal_nutrition (id_utilisateur, id_aliment, date_entree, calories, quantite) 
                  VALUES (:uid, :aid, :date, :cal, :qty)";
        $stmt = $this->conn->prepare($query);

        $uid = $suivi->getUserId();
        $aid = $suivi->getIdAliment();
        $date = $suivi->getDate();
        $cal = $suivi->getCalories();
        $qty = $suivi->getQuantite();

        $stmt->bindParam(':uid', $uid);
        $stmt->bindParam(':aid', $aid);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':cal', $cal);
        $stmt->bindParam(':qty', $qty);
        return $stmt->execute();
    }

    // Create Sport Log
    public function createSport(Suivi $suivi) {
        $query = "INSERT INTO journal_sport (id_utilisateur, date_seance, type_sport, duree_min, calories_depensees) 
                  VALUES (:uid, :date, :type, :dur, :cal)";
        $stmt = $this->conn->prepare($query);

        $uid = $suivi->getUserId();
        $date = $suivi->getDate();
        $type = $suivi->getDescription();
        $dur = 30; // default duration
        $cal = $suivi->getCalories();

        $stmt->bindParam(':uid', $uid);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':type', $type);
        $stmt->bindParam(':dur', $dur);
        $stmt->bindParam(':cal', $cal);
        return $stmt->execute();
    }

    // Create Weight Log (as a junction table)
    public function createWeight(Suivi $suivi) {
        $query = "INSERT INTO journal_poids (id_utilisateur, poids, date_mesure, id_sport, id_nutrition) 
                  VALUES (:uid, :poids, :date, :sid, :nid)";
        $stmt = $this->conn->prepare($query);

        $uid = $suivi->getUserId();
        $poids = $suivi->getPoids();
        $date = $suivi->getDate();
        $sid = $suivi->getIdSport();
        $nid = $suivi->getIdNutrition();

        $stmt->bindParam(':uid', $uid);
        $stmt->bindParam(':poids', $poids);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':sid', $sid);
        $stmt->bindParam(':nid', $nid);
        return $stmt->execute();
    }

    // Delete Log
    public function delete($id, $type) {
        $table = "";
        if ($type === 'meal') $table = "journal_nutrition";
        elseif ($type === 'activity' || $type === 'sport') $table = "journal_sport";
        elseif ($type === 'weight') $table = "journal_poids";
        
        if (empty($table)) return false;

        $query = "DELETE FROM $table WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $id]);
    }

    public function updateLog(Suivi $suivi) {
        $type = $suivi->getType();
        $id = $suivi->getId();
        $cal = $suivi->getCalories();
        $desc = $suivi->getDescription();
        $poids = $suivi->getPoids();

        if ($type === 'meal') {
            // Find or create the aliment based on description
            $searchDesc = strtolower($desc);
            $stmtAid = $this->conn->prepare("SELECT id FROM aliment WHERE LOWER(nom) = ? LIMIT 1");
            $stmtAid->execute([$searchDesc]);
            $aidRow = $stmtAid->fetch();
            
            if ($aidRow) {
                $aid = $aidRow['id'];
            } else {
                $stmtInsert = $this->conn->prepare("INSERT INTO aliment (nom, categorie, calories_100g) VALUES (?, 'autre', ?)");
                $stmtInsert->execute([$desc, 0]);
                $aid = $this->conn->lastInsertId();
            }

            $stmt = $this->conn->prepare("UPDATE journal_nutrition SET calories = :cal, id_aliment = :aid WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal, 'aid' => $aid]);
        } elseif ($type === 'weight') {
            $stmt = $this->conn->prepare("UPDATE journal_poids SET poids = :poids WHERE id = :id");
            return $stmt->execute(['id' => $id, 'poids' => $poids]);
        } else {
            $stmt = $this->conn->prepare("UPDATE journal_sport SET calories_depensees = :cal, type_sport = :desc WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal, 'desc' => $desc]);
        }
    }

    // ADVANCED BUSINESS LOGIC: Calculate daily statistics, BMI, and balance, plus predictive and gamification models
    public function getStatistics($user_id) {
        // Use the most reliable date: the one from the database
        $today = $this->conn->query("SELECT CURDATE()")->fetchColumn();
        
        // Total Consumed
        $stmtIn = $this->conn->prepare("SELECT SUM(calories) as total FROM journal_nutrition WHERE id_utilisateur = ? AND DATE(date_entree) = ?");
        $stmtIn->execute([$user_id, $today]);
        $consumed = (int) $stmtIn->fetchColumn();

        // If consumed is 0, maybe it's a timezone issue? Let's try the last 24 hours as fallback
        if ($consumed === 0) {
            $stmtInFallback = $this->conn->prepare("SELECT SUM(calories) as total FROM journal_nutrition WHERE id_utilisateur = ? AND date_entree >= DATE_SUB(NOW(), INTERVAL 1 DAY)");
            $stmtInFallback->execute([$user_id]);
            $consumed = (int) $stmtInFallback->fetchColumn();
        }

        // Total Burned (More robust query)
        $stmtOut = $this->conn->prepare("SELECT SUM(calories_depensees) as total FROM journal_sport WHERE id_utilisateur = ? AND (DATE(date_seance) = ? OR date_seance = ?)");
        $stmtOut->execute([$user_id, $today, $today]);
        $burned = (int) $stmtOut->fetchColumn();

        // 1. Current Weight & History (For Predictive Math)
        $stmtWeights = $this->conn->prepare("SELECT poids, date_mesure FROM journal_poids WHERE id_utilisateur = ? ORDER BY date_mesure ASC");
        $stmtWeights->execute([$user_id]);
        $weightsHistory = $stmtWeights->fetchAll(PDO::FETCH_ASSOC);
        
        $weight = 70; // Default weight to prevent 0 goals
        if (count($weightsHistory) > 0) {
            $lastEntry = end($weightsHistory);
            $weight = (float) $lastEntry['poids'];
        }

        // 2. Stable Health Goal (Harris-Benedict)
        $height_cm = 175;
        $age = 30;
        $bmr = 88.362 + (13.397 * $weight) + (4.799 * $height_cm) - (5.677 * $age);
        
        // The goal is your DAILY TARGET (fixed based on metabolism)
        // We use a standard activity factor of 1.375 (moderate)
        $dynamicGoal = round($bmr * 1.375);
        
        // Safety: ensure goal is realistic (1500 to 3000 kcal)
        if ($dynamicGoal < 1500) $dynamicGoal = 2000;
        if ($dynamicGoal > 3000) $dynamicGoal = 2800;

        // 3. BMI Calculation
        $height_m = $height_cm / 100; 
        $bmi = round($weight / ($height_m * $height_m), 2);

        $balance = $consumed - $burned;

        // 4. Predictive Weight Math (Linear Regression)
        $predictionText = "Pas assez de données pour une prédiction.";
        if (count($weightsHistory) >= 3) {
            $n = count($weightsHistory);
            $sumX = 0; $sumY = 0; $sumXY = 0; $sumXX = 0;
            $firstDate = strtotime($weightsHistory[0]['date_mesure']);
            
            foreach ($weightsHistory as $w) {
                $x = (strtotime($w['date_mesure']) - $firstDate) / (60 * 60 * 24); // days
                $y = (float) $w['poids'];
                $sumX += $x; $sumY += $y; $sumXY += $x * $y; $sumXX += $x * $x;
            }
            
            // Calculate slope (m)
            $denominator = (($n * $sumXX) - ($sumX * $sumX));
            if ($denominator != 0) {
                $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
                
                if ($slope < -0.01) {
                    $targetWeight = $weight - 2; // Arbitrary 2kg target
                    $daysToTarget = abs($targetWeight - $weight) / abs($slope);
                    $targetDate = date('d M Y', strtotime("+" . round($daysToTarget) . " days"));
                    $predictionText = "Tendance baissière (-" . round(abs($slope)*7, 2) . "kg/semaine). Objectif de " . $targetWeight . "kg prévu le " . $targetDate . ".";
                } elseif ($slope > 0.01) {
                    $predictionText = "Tendance haussière détectée (+" . round($slope*7, 2) . "kg/semaine).";
                } else {
                    $predictionText = "Poids stable. Maintien parfait !";
                }
            }
        }

        // 5. Food Recommendation Engine
        $recommendedFood = null;
        $remaining = $dynamicGoal - $consumed;
        if ($remaining > 100 && $remaining < 800) {
            $stmtFood = $this->conn->prepare("SELECT nom, calories_100g FROM aliment WHERE calories_100g > 0 AND calories_100g <= ? ORDER BY RAND() LIMIT 1");
            $stmtFood->execute([$remaining]);
            $recommendedFood = $stmtFood->fetch(PDO::FETCH_ASSOC);
        }

        // 6. Gamification / Streaks
        $stmtStreak = $this->conn->prepare("SELECT COUNT(DISTINCT date_entree) FROM journal_nutrition WHERE id_utilisateur = ? AND date_entree >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
        $stmtStreak->execute([$user_id]);
        $streakDays = (int) $stmtStreak->fetchColumn();

        // Smart Advice
        $advice = "Maintenez vos efforts !";
        if ($bmi > 25) {
            $advice = "Essayez de maintenir un déficit calorique pour perdre du poids.";
            if ($consumed > $dynamicGoal) $advice .= " Aujourd'hui vous êtes en surplus.";
            else $advice .= " Bon travail sur votre déficit aujourd'hui !";
        } elseif ($bmi > 0 && $bmi < 18.5) {
            $advice = "Vous êtes en sous-poids, essayez d'augmenter votre apport calorique.";
        }

        return [
            'consumed' => $consumed,
            'burned' => $burned,
            'balance' => $balance,
            'weight' => $weight,
            'bmi' => $bmi,
            'advice' => $advice,
            'dynamicGoal' => $dynamicGoal,
            'predictionText' => $predictionText,
            'recommendedFood' => $recommendedFood,
            'streakDays' => $streakDays
        ];
    }
}
?>
