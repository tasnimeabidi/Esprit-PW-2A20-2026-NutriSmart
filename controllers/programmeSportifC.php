<?php
require_once "../config.php";
 
class ProgrammeSportifC {
 
    public function afficherTousProgrammes() {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare(
                "SELECT ProgrammeSportif.*, PlanRepas.objectif AS objectifPlan
                 FROM ProgrammeSportif
                 JOIN PlanRepas ON ProgrammeSportif.id_plan = PlanRepas.id"
            );
            $query->execute();
            return $query->fetchAll();
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    /**
     * Programmes sportifs d’un plan (jointure programme_sportif ⟷ plan_repas).
     *
     * @return array<int, array<string, mixed>>
     */
    public function afficherProgrammesSportifs(int $idPlan): array
    {
        try {
            $pdo = Database::getConnection();
            $st = $pdo->prepare(
                'SELECT ps.*, p.objectif AS objectifPlan
                 FROM programme_sportif ps
                 INNER JOIN plan_repas p ON p.id = ps.id_plan
                 WHERE ps.id_plan = ?
                 ORDER BY ps.id ASC'
            );
            $st->execute([$idPlan]);

            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            echo $e->getMessage();

            return [];
        }
    }
 
    public function ajouterProgramme($id_plan, $type_sport, $niveau, $intensite, $date_seance, $duree_min, $statut) {
        try {
            $pdo = config::getConnexion();
            $query = $pdo->prepare(
                "INSERT INTO ProgrammeSportif (id_plan, type_sport, niveau, intensite, date_seance, duree_min, statut)
                 VALUES (:id_plan, :type_sport, :niveau, :intensite, :date_seance, :duree_min, :statut)"
            );
            $query->execute([
                'id_plan'     => $id_plan,
                'type_sport'  => $type_sport,
                'niveau'      => $niveau,
                'intensite'   => $intensite,
                'date_seance' => $date_seance,
                'duree_min'   => $duree_min,
                'statut'      => $statut,
            ]);
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
}
?>
 