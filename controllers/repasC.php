<?php
require_once "../config.php";
 
class RepasC {
 
    public function afficherTousRepas() {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare(
                "SELECT Repas.*, PlanRepas.objectif AS objectifPlan
                 FROM Repas
                 JOIN PlanRepas ON Repas.id_plan = PlanRepas.id"
            );
            $query->execute();
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
 
    public function ajouterRepas($id_plan, $id_recette, $type, $catories) {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare(
                "INSERT INTO Repas (id_plan, id_recette, type, catories)
                 VALUES (:id_plan, :id_recette, :type, :catories)"
            );
            $query->execute([
                'id_plan'    => $id_plan,
                'id_recette' => $id_recette,
                'type'       => $type,
                'catories'   => $catories,
            ]);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
}
?>
 