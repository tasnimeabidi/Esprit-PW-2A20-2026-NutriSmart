<?php
declare(strict_types=1);

final class RiskScoringService
{
    /** @var array<string, float> */
    private const COEF_INTENSITE = [
        'très légère' => 1.0,
        'légère' => 1.2,
        'modérée' => 1.5,
        'soutenue' => 1.8,
        'très soutenue' => 2.1,
        'récupération' => 0.8,
    ];

    /** @var array<string, float> */
    private const SEUIL_NIVEAU = [
        'débutant' => 450.0,
        'intermédiaire' => 700.0,
        'avancé' => 950.0,
        'expert' => 1200.0,
    ];

    /**
     * @param array<string, mixed> $plan
     * @param list<array<string, mixed>> $repas
     * @param list<array<string, mixed>> $programmes
     * @return array<string, mixed>
     */
    public function evaluer(array $plan, array $repas, array $programmes): array
    {
        $totalCalories = 0;
        foreach ($repas as $r) {
            $cal = isset($r['calories']) ? (string) $r['calories'] : '';
            if ($cal !== '' && ctype_digit($cal)) {
                $totalCalories += (int) $cal;
            }
        }

        $chargeTotale = 0.0;
        $maxNiveau = 'débutant';
        foreach ($programmes as $p) {
            $duree = isset($p['dureeMin']) ? (int) $p['dureeMin'] : 0;
            $intensite = mb_strtolower(trim((string) ($p['intensite'] ?? '')));
            $niveau = mb_strtolower(trim((string) ($p['niveau'] ?? '')));
            if ($niveau !== '' && array_key_exists($niveau, self::SEUIL_NIVEAU)) {
                $maxNiveau = $niveau;
            }
            $coef = self::COEF_INTENSITE[$intensite] ?? 1.0;
            $chargeTotale += max(0, $duree) * $coef;
        }

        $seuil = self::SEUIL_NIVEAU[$maxNiveau] ?? self::SEUIL_NIVEAU['débutant'];
        $ratioCharge = $seuil > 0 ? ($chargeTotale / $seuil) : 0.0;

        $score = 100.0;
        if ($ratioCharge > 1.0) {
            $score -= min(45.0, ($ratioCharge - 1.0) * 70.0);
        }
        if ($totalCalories <= 0) {
            $score -= 20.0;
        }
        if (count($programmes) === 0) {
            $score -= 25.0;
        }
        $score = max(0.0, min(100.0, $score));

        $niveauRisque = 'faible';
        if ($ratioCharge >= 1.2) {
            $niveauRisque = 'élevé';
        } elseif ($ratioCharge >= 0.95) {
            $niveauRisque = 'modéré';
        }

        return [
            'scoreGlobal' => (int) round($score),
            'risque' => $niveauRisque,
            'indicateurs' => [
                'chargeTotale' => round($chargeTotale, 2),
                'seuilChargeNiveau' => $seuil,
                'ratioCharge' => round($ratioCharge, 3),
                'totalCaloriesRepas' => $totalCalories,
                'nombreRepas' => count($repas),
                'nombreSeances' => count($programmes),
                'niveauReference' => $maxNiveau,
                'objectifPlan' => (string) ($plan['objectif'] ?? ''),
            ],
        ];
    }
}
