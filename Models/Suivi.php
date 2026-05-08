<?php
class Suivi {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    // Read all logs (JOINing nutrition and sport)
    public function readAll($user_id) {
        $query = "SELECT id, id_utilisateur as user_id, 'meal' as type, date_entree as date, calories, 'Aliment' as description FROM journal_nutrition WHERE id_utilisateur = :uid
                  UNION
                  SELECT id, id_utilisateur as user_id, 'activity' as type, date_seance as date, calories_depensees as calories, type_sport as description FROM journal_sport WHERE id_utilisateur = :uid
                  UNION
                  SELECT id, id_utilisateur as user_id, 'weight' as type, date_mesure as date, 0 as calories, CONCAT(poids, ' kg') as description FROM journal_poids WHERE id_utilisateur = :uid
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

    // Create Weight Log
    public function createWeight($user_id, $date, $poids) {
        $query = "INSERT INTO journal_poids (id_utilisateur, poids, date_mesure) VALUES (:uid, :poids, :date)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':uid', $user_id);
        $stmt->bindParam(':poids', $poids);
        $stmt->bindParam(':date', $date);
        return $stmt->execute();
    }

    // Delete Log
    public function delete($id, $type) {
        $table = ($type == 'meal') ? "journal_nutrition" : (($type == 'activity') ? "journal_sport" : "journal_poids");
        if(!$table) return false;
        $query = "DELETE FROM $table WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        return $stmt->execute(['id' => $id]);
    }

    public function updateLog($id, $type, $desc, $cal) {
        if ($type === 'meal') {
            $stmt = $this->conn->prepare("UPDATE journal_nutrition SET calories = :cal WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal]);
        } else {
            $stmt = $this->conn->prepare("UPDATE journal_sport SET calories_depensees = :cal, type_sport = :desc WHERE id = :id");
            return $stmt->execute(['id' => $id, 'cal' => $cal, 'desc' => $desc]);
        }
    }
}
?>
