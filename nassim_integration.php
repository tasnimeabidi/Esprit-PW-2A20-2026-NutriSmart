<?php
declare(strict_types=1);

/**
 * Intégration du module NutriSmart ProjetNutrismart (Nassim, sous htdocs/nassim).
 * Même principe que les références « nutrismart-chahine » dans le dépôt : une cible
 * configurable, sans recopier le code du collaborateur ici.
 *
 * Surcharge possible : variable d'environnement NUTRISMART_NASSIM_BLOG_URL (URL absolue).
 */
final class NassimIntegration
{
    public static function blogUrl(): string
    {
        $fromEnv = getenv('NUTRISMART_NASSIM_BLOG_URL');
        if ($fromEnv !== false && $fromEnv !== '') {
            return $fromEnv;
        }

        return 'http://localhost/nassim/ProjetNutrismart/index.php?action=blog';
    }
}
