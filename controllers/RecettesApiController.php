<?php
declare(strict_types=1);

final class RecettesApiController
{
    public function traiter(): void
    {
        $pdo = Database::getConnection();
        $model = new Recette($pdo);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($method === 'GET') {
                JsonApi::envoyer(200, $model->listerPourApi());
                return;
            }
            JsonApi::erreur(405, 'Méthode non autorisée.');
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
