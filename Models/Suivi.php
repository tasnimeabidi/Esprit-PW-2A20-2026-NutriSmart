<?php
class Suivi {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all logs (JOINing nutrition with aliment entity and sport)
    public function readAll($user_id) {
        $query = "SELECT n.id, n.id_utilisateur as user_id, 'meal' as type, n.date_entree as date, n.calories, COALESCE(a.nom, 'Aliment Inconnu') as description 
                  FROM journal_nutrition n
                  LEFT JOIN aliment a ON n.id_aliment = a.id
                  WHERE n.id_utilisateur = :uid
                  UNION
                  SELECT s.id, s.id_utilisateur as user_id, 'activity' as type, s.date_seance as date, s.calories_depensees as calories, s.type_sport as description 
                  FROM journal_sport s
                  WHERE s.id_utilisateur = :uid
                  UNION
                  SELECT w.id, w.id_utilisateur as user_id, 'weight' as type, w.date_mesure as date, 0 as calories, 
                         CONCAT(w.poids, ' kg (Sport: ', COALESCE(s.type_sport, 'N/A'), ', Cal: ', COALESCE(n.calories, '0'), ')') as description 
                  FROM journal_poids w
                  LEFT JOIN journal_sport s ON w.id_sport = s.id
                  LEFT JOIN journal_nutrition n ON w.id_nutrition = n.id
                  WHERE w.id_utilisateur = :uid
                  ORDER BY date DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->execute();
        return $stmt;
    }

    // Create Nutrition Log
    public function createNutrition($user_id, $date, $calories, $quantite, $id_aliment = 1) {
        $query = "INSERT INTO journal_nutrition (id_utilisateur, id_aliment, date_entree, calories, quantite) 
                  VALUES (:uid, :aid, :date, :cal, :qty)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':aid', $id_aliment);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':cal', $calories);
        $stmt->bindParam(':qty', $quantite);
        return $stmt->execute();
    }

    // Create Sport Log
    public function createSport($user_id, $date, $type_sport, $calories, $duration = 30) {
        $query = "INSERT INTO journal_sport (id_utilisateur, date_seance, type_sport, duree_min, calories_depensees) 
                  VALUES (:uid, :date, :type, :dur, :cal)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':type', $type_sport);
        $stmt->bindParam(':dur', $duration);
        $stmt->bindParam(':cal', $calories);
        return $stmt->execute();
    }

    // Create Weight Log (as a junction table)
    public function createWeight($user_id, $date, $poids, $id_sport = null, $id_nutrition = null) {
        $query = "INSERT INTO journal_poids (id_utilisateur, poids, date_mesure, id_sport, id_nutrition) 
                  VALUES (:uid, :poids, :date, :sid, :nid)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':poids', $poids);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':sid', $id_sport);
        $stmt->bindParam(':nid', $id_nutrition);
        return $stmt->execute();
    }

    // Delete Log
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

    public function updateLog($id, $type, $desc, $cal) {
        if ($type === 'meal') {
            // For meals in this app, update calories and a default quantity
            $stmt = $this->conn->prepare("UPDATE journal_nutrition SET calories = :cal, quantite = 100 WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal]);
        } elseif ($type === 'weight') {
            $stmt = $this->conn->prepare("UPDATE journal_poids SET poids = :poids WHERE id = :id");
            return $stmt->execute(['id' => $id, 'poids' => $cal]);
        } else {
            $stmt = $this->conn->prepare("UPDATE journal_sport SET calories_depensees = :cal, type_sport = :desc WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal, 'desc' => $desc]);
        }
    }
}
?>
