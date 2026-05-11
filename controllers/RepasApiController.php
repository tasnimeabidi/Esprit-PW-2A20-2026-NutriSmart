<?php
declare(strict_types=1);

final class RepasApiController
{
    public function traiter(): void
    {
        $pdo = Database::getConnection();
        $model = new Repas($pdo);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($method === 'GET') {
                JsonApi::envoyer(200, $model->listerPourApi());
                return;
            }
            if ($method === 'POST') {
                $data = JsonApi::lireCorpsJson();
                $v = ValidateurRepasApi::valider($data, $pdo);
                if (!$v->ok()) {
                    JsonApi::erreur(400, $v->premierMessage());
                    return;
                }
                $row = $model->creer([
                    'idPlan' => trim((string) $data['idPlan']),
                    'idRecette' => isset($data['idRecette']) ? trim((string) $data['idRecette']) : '',
                    'type' => trim((string) $data['type']),
                    'calories' => isset($data['calories']) ? trim((string) $data['calories']) : '',
                ]);
                JsonApi::envoyer(201, $row);
                return;
            }
            if ($method === 'PUT') {
                $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
                if ($id < 1) {
                    JsonApi::erreur(400, 'Identifiant manquant ou invalide.');
                    return;
                }
                $ex = $model->getParIdApi($id);
                if ($ex === null) {
                    JsonApi::erreur(404, 'Repas introuvable.');
                    return;
                }
                $patch = JsonApi::lireCorpsJson();
                $merged = array_merge($ex, $patch);
                $v = ValidateurRepasApi::valider($merged, $pdo);
                if (!$v->ok()) {
                    JsonApi::erreur(400, $v->premierMessage());
                    return;
                }
                $row = $model->mettreAJour($id, $patch);
                JsonApi::envoyer(200, $row);
                return;
            }
            if ($method === 'DELETE') {
                $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
                if ($id < 1) {
                    JsonApi::erreur(400, 'Identifiant manquant ou invalide.');
                    return;
                }
                if (!$model->supprimer($id)) {
                    JsonApi::erreur(404, 'Repas introuvable.');
                    return;
                }
                JsonApi::envoyer(200, ['ok' => true]);
                return;
            }
            JsonApi::erreur(405, 'Méthode non autorisée.');
        } catch (Throwable $e) {
            JsonApi::erreur(500, 'Erreur serveur : ' . $e->getMessage());
        }
    }
}
