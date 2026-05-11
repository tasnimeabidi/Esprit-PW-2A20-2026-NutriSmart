<?php
declare(strict_types=1);

final class OrchestrationMetierProgrammeSportifApiController
{
    public function traiter(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            JsonApi::erreur(405, 'Méthode non autorisée.');
            return;
        }

        $idProgramme = isset($_GET['idProgramme']) ? (int) $_GET['idProgramme'] : 0;
        if ($idProgramme < 1) {
            JsonApi::erreur(400, 'Paramètre idProgramme manquant ou invalide.');
            return;
        }

        try {
            $pdo = Database::getConnection();
            $planModel = new PlanRepas($pdo);
            $repasModel = new Repas($pdo);
            $sportModel = new ProgrammeSportif($pdo);

            $programme = $sportModel->getParIdApi($idProgramme);
            if ($programme === null) {
                JsonApi::erreur(404, 'Programme sportif introuvable.');
                return;
            }

            $idPlan = isset($programme['idPlan']) ? (int) $programme['idPlan'] : 0;
            if ($idPlan < 1) {
                JsonApi::erreur(400, 'Programme sportif sans plan associé.');
                return;
            }

            $plan = $planModel->getParIdApi($idPlan);
            if ($plan === null) {
                JsonApi::erreur(404, 'Plan repas associé introuvable.');
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
                ],
                'resumeMetier' => MetierAvancePlanRepas::resumerPourPlan($plan),
                'programme' => [
                    'id' => (string) ($programme['id'] ?? ''),
                    'idPlan' => (string) ($programme['idPlan'] ?? ''),
                    'typeSport' => (string) ($programme['typeSport'] ?? ''),
                    'niveau' => (string) ($programme['niveau'] ?? ''),
                    'intensite' => (string) ($programme['intensite'] ?? ''),
                    'dateSeance' => (string) ($programme['dateSeance'] ?? ''),
                    'dureeMin' => (string) ($programme['dureeMin'] ?? ''),
                    'statut' => (string) ($programme['statut'] ?? ''),
                ],
                'analyse' => $analyse,
                'recommandations' => $recommandations,
                'ajustementsProposes' => $ajustements,
            ]);
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
