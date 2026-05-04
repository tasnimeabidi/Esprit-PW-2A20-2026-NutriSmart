<?php
declare(strict_types=1);

final class MetierAvancePlanRepasApiController
{
    public function traiter(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method !== 'GET') {
            JsonApi::erreur(405, 'Méthode non autorisée.');
            return;
        }

        try {
            JsonApi::envoyer(200, MetierAvancePlanRepas::specification());
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
