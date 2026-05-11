<?php
declare(strict_types=1);

final class OrchestrationMetierApiController
{
    public function traiter(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            JsonApi::erreur(405, 'Méthode non autorisée.');
            return;
        }

        $idPlan = isset($_GET['idPlan']) ? (int) $_GET['idPlan'] : 0;
        if ($idPlan < 1) {
            JsonApi::erreur(400, 'Paramètre idPlan manquant ou invalide.');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $planModel = new PlanRepas($pdo);
            $repasModel = new Repas($pdo);
            $sportModel = new ProgrammeSportif($pdo);

            $plan = $planModel->getParIdApi($idPlan);
            if ($plan === null) {
                JsonApi::erreur(404, 'Plan repas introuvable.');
                return;
            }

            $repas = $repasModel->listerParIdPlanEnJointure($idPlan);
            $seances = $sportModel->listerParIdPlan($idPlan);

            $scoring = new RiskScoringService();
            $analyse = $scoring->evaluer($plan, $repas, $seances);

            $engine = new RecommendationEngine();
            $recommandations = $engine->recommander($analyse);

            $adaptive = new AdaptivePlanService();
            $ajustements = $adaptive->proposerAjustements($recommandations);

            JsonApi::envoyer(200, [
                'plan' => [
                    'id' => (string) ($plan['id'] ?? ''),
                    'objectif' => (string) ($plan['objectif'] ?? ''),
                    'statut' => (string) ($plan['statut'] ?? ''),
                    'dateDebut' => (string) ($plan['dateDebut'] ?? ''),
                    'dateFin' => (string) ($plan['dateFin'] ?? ''),
                ],
                'resumeMetier' => MetierAvancePlanRepas::resumerPourPlan($plan),
                'analyse' => $analyse,
                'recommandations' => $recommandations,
                'ajustementsProposes' => $ajustements,
            ]);
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
