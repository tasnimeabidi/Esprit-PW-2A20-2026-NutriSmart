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
            if ($method === 'POST') {
                $data = JsonApi::lireCorpsJson();
                $nom = isset($data['nom']) ? trim((string) $data['nom']) : '';
                if ($nom === '') {
                    JsonApi::erreur(400, 'Le nom de la recette est obligatoire.');
                    return;
                }
                $payload = [
                    'nom' => $nom,
                    'instructions' => isset($data['instructions']) ? trim((string) $data['instructions']) : '',
                    'calories_totales' => isset($data['calories_totales'])
                        ? trim((string) $data['calories_totales'])
                        : (isset($data['caloriesTotales']) ? trim((string) $data['caloriesTotales']) : ''),
                    'status' => isset($data['status']) ? trim((string) $data['status']) : 'active',
                ];
                $row = $model->creerPourApi($payload);
                JsonApi::envoyer(201, $row);
                return;
            }
            JsonApi::erreur(405, 'Méthode non autorisée.');
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
