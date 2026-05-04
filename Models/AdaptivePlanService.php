<?php
declare(strict_types=1);

final class AdaptivePlanService
{
    /**
     * @param list<array<string, string>> $recommandations
     * @return list<array<string, string>>
     */
    public function proposerAjustements(array $recommandations): array
    {
        $actions = [];
        foreach ($recommandations as $r) {
            $type = $r['type'] ?? '';
            if ($type === 'charge') {
                $actions[] = [
                    'axe' => 'programme_sportif',
                    'action' => 'Réduire les séances très soutenues et ajouter une séance récupération.',
                ];
            } elseif ($type === 'nutrition') {
                $actions[] = [
                    'axe' => 'repas',
                    'action' => 'Ajuster les calories cibles par repas selon objectif du plan.',
                ];
            } elseif ($type === 'planning') {
                $actions[] = [
                    'axe' => 'programme_sportif',
                    'action' => 'Créer un planning hebdomadaire minimal (2 à 3 séances).',
                ];
            } elseif ($type === 'progression') {
                $actions[] = [
                    'axe' => 'programme_sportif',
                    'action' => "Appliquer une progression progressive de charge (max +10% par semaine).",
                ];
            }
        }
        return $actions;
    }
}
