<?php
declare(strict_types=1);

final class RecommendationEngine
{
    /**
     * @param array<string, mixed> $analyse
     * @return list<array<string, string>>
     */
    public function recommander(array $analyse): array
    {
        /** @var array<string, mixed> $ind */
        $ind = isset($analyse['indicateurs']) && is_array($analyse['indicateurs']) ? $analyse['indicateurs'] : [];
        $reco = [];

        $ratio = (float) ($ind['ratioCharge'] ?? 0.0);
        if ($ratio > 1.0) {
            $reco[] = [
                'type' => 'charge',
                'priorite' => 'haute',
                'message' => "Réduire la charge sportive de 10% à 20% cette semaine pour limiter le risque de fatigue.",
            ];
        } elseif ($ratio < 0.55 && (int) ($ind['nombreSeances'] ?? 0) >= 1) {
            $reco[] = [
                'type' => 'progression',
                'priorite' => 'moyenne',
                'message' => "Augmenter progressivement la durée ou l'intensité (+5% à +10%) pour améliorer la progression.",
            ];
        }

        $objectif = mb_strtolower(trim((string) ($ind['objectifPlan'] ?? '')));
        $totalCalories = (int) ($ind['totalCaloriesRepas'] ?? 0);
        $nbRepas = max(1, (int) ($ind['nombreRepas'] ?? 0));
        $moyenneRepas = (int) round($totalCalories / $nbRepas);
        if (str_contains($objectif, 'perte') && $moyenneRepas > 900) {
            $reco[] = [
                'type' => 'nutrition',
                'priorite' => 'haute',
                'message' => "Objectif perte de poids: réduire la densité calorique moyenne des repas.",
            ];
        }
        if ((str_contains($objectif, 'masse') || str_contains($objectif, 'performance')) && $moyenneRepas < 450 && $totalCalories > 0) {
            $reco[] = [
                'type' => 'nutrition',
                'priorite' => 'moyenne',
                'message' => "Objectif performance/masse: renforcer l'apport énergétique (glucides complexes et protéines).",
            ];
        }

        if ((int) ($ind['nombreSeances'] ?? 0) === 0) {
            $reco[] = [
                'type' => 'planning',
                'priorite' => 'haute',
                'message' => "Ajouter au moins 2 séances planifiées pour activer la progression sportive.",
            ];
        }

        return $reco;
    }
}
