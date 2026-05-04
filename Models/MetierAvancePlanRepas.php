<?php
/**
 * Référentiel métier : plan repas « niveau pro » (logique diététicien·ne du sport).
 * Données exploitables par l’API et les vues — sans dépendance PDO.
 */
declare(strict_types=1);

final class MetierAvancePlanRepas
{
    public const REFERENCE_METIER = 'Diététicien·ne du sport';

    /**
     * Spécification complète pour affichage site ou export JSON.
     *
     * @return array<string, mixed>
     */
    public static function specification(): array
    {
        return [
            'referenceMetier' => self::REFERENCE_METIER,
            'accroche' => 'Des menus pensés comme un plan nutritionnel, pas comme une simple liste de plats.',
            'objectifProduit' => 'Aligner apports énergétiques et macronutriments sur l’activité, les contraintes et la durée du plan.',
            'fonctionnalites' => self::fonctionnalites(),
            'phases' => self::phases(),
            'criteresEntree' => self::criteresEntree(),
            'livrables' => self::livrables(),
            'apiLiee' => [
                'orchestrationPlan' => 'api/orchestration-metier.php?idPlan={id}',
                'description' => 'Analyse charge/calories, recommandations et ajustements proposés pour un idPlan donné.',
            ],
        ];
    }

    /**
     * @return list<array{id: string, titre: string, description: string}>
     */
    public static function fonctionnalites(): array
    {
        return [
            [
                'id' => 'profil_objectifs',
                'titre' => 'Profil & objectifs',
                'description' => 'Objectif (santé, composition, performance), contraintes temps, budget, goûts et allergies.',
            ],
            [
                'id' => 'besoins_calcules',
                'titre' => 'Besoins calculés',
                'description' => 'Apports énergétiques et macronutriments cibles selon la charge d’entraînement et le profil.',
            ],
            [
                'id' => 'menus_structure',
                'titre' => 'Menus structurés',
                'description' => 'Répartition cohérente sur la semaine (pas une collection de repas décorrélés).',
            ],
            [
                'id' => 'periodisation',
                'titre' => 'Périodisation nutritionnelle',
                'description' => 'Adapter les repas aux jours forts, modérés, de récupération ou de compétition.',
            ],
            [
                'id' => 'ajustements',
                'titre' => 'Ajustements continu',
                'description' => 'Révision du plan selon fatigue, résultats, poids ou retour utilisateur.',
            ],
            [
                'id' => 'education',
                'titre' => 'Éducation nutritionnelle',
                'description' => 'Expliciter les choix pour favoriser l’autonomie et la fidélisation.',
            ],
        ];
    }

    /**
     * @return list<array{code: string, libelle: string, activites: list<string>}>
     */
    public static function phases(): array
    {
        return [
            [
                'code' => 'evaluer',
                'libelle' => 'Évaluer',
                'activites' => [
                    'Anamnèse objectifs et historique',
                    'Inventaire contraintes (allergies, horaires, matériel culinaire)',
                ],
            ],
            [
                'code' => 'calculer',
                'libelle' => 'Calculer',
                'activites' => [
                    'Estimation des besoins (énergie, protéines, répartition)',
                    'Hydratation et timing des repas autour des séances',
                ],
            ],
            [
                'code' => 'concevoir',
                'libelle' => 'Concevoir',
                'activites' => [
                    'Construction du calendrier de repas',
                    'Variété et faisabilité (courses, préparation)',
                ],
            ],
            [
                'code' => 'piloter',
                'libelle' => 'Piloter',
                'activites' => [
                    'Suivi des indicateurs (régularité, équilibre)',
                    'Itération sur le plan en cours',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function criteresEntree(): array
    {
        return [
            'Objectif clair et mesurable',
            'Période couverte (date début / fin)',
            'Niveau d’activité ou programme sportif associé si disponible',
            'Contraintes alimentaires documentées',
        ];
    }

    /**
     * @return list<string>
     */
    public static function livrables(): array
    {
        return [
            'Plan hebdomadaire ou période avec repas ordonnés',
            'Synthèse des apports cibles vs réalisés (si données saisies)',
            'Recommandations d’ajustement (via orchestration métier NutriSmart)',
        ];
    }

    /**
     * Synthèse « métier » à partir d’une ligne plan API (camelCase).
     *
     * @param array<string, mixed> $plan
     * @return array<string, mixed>
     */
    public static function resumerPourPlan(array $plan): array
    {
        $objectif = isset($plan['objectif']) ? trim((string) $plan['objectif']) : '';
        $statut = isset($plan['statut']) ? trim((string) $plan['statut']) : '';
        $priorites = [];
        if ($statut === 'brouillon') {
            $priorites[] = 'Finaliser le profil et valider les contraintes avant verrouillage du menu.';
        }
        if ($objectif !== '') {
            $priorites[] = 'Vérifier que chaque journée à forte charge est couverte nutritionnellement.';
        }
        if ($priorites === []) {
            $priorites[] = 'Maintenir la cohérence objectif / repas sur toute la période du plan.';
        }

        return [
            'planId' => isset($plan['id']) ? (string) $plan['id'] : '',
            'objectif' => $objectif,
            'statut' => $statut,
            'prioritesMetier' => $priorites,
        ];
    }
}
