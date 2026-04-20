<?php
require_once "../config.php";
require_once "../Model/planRepas.php";
require_once "../Model/repas.php";
require_once "../Model/programmeSportif.php";
 
class PlanRepasC {
 
    // Afficher tous les plans repas
    public function afficherPlans() {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare("SELECT * FROM PlanRepas");
            $query->execute();
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
 
    // Afficher les repas d'un plan (jointure PlanRepas -> Repas)
    public function afficherRepas($idPlan) {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare("SELECT * FROM Repas WHERE id_plan = :id");
            $query->execute(['id' => $idPlan]);
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
 
    // Afficher les programmes sportifs d'un plan (jointure PlanRepas -> ProgrammeSportif)
    public function afficherProgrammes($idPlan) {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare("SELECT * FROM ProgrammeSportif WHERE id_plan = :id");
            $query->execute(['id' => $idPlan]);
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
 
    // Ajouter un plan repas
    public function ajouterPlan($id_utilisateur, $date_debut, $date_fin, $objectif, $statut) {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare(
                "INSERT INTO PlanRepas (id_utilisateur, date_debut, date_fin, objectif, statut)
                 VALUES (:id_utilisateur, :date_debut, :date_fin, :objectif, :statut)"
            );
            $query->execute([
                'id_utilisateur' => $id_utilisateur,
                'date_debut'     => $date_debut,
                'date_fin'       => $date_fin,
                'objectif'       => $objectif,
                'statut'         => $statut,
            ]);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
}
?>