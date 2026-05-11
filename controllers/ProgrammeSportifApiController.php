<?php
declare(strict_types=1);

final class ProgrammeSportifApiController
{
    public function traiter(): void
    {
        $pdo = Database::getConnection();
        $model = new ProgrammeSportif($pdo);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($method === 'GET') {
                JsonApi::envoyer(200, $model->listerPourApi());
                return;
            }
            if ($method === 'POST') {
                $data = JsonApi::lireCorpsJson();
                $v = ValidateurProgrammeSportifApi::valider($data, $pdo, null);
                if (!$v->ok()) {
                    JsonApi::erreur(400, $v->premierMessage());
                    return;
                }
                $row = $model->creer([
                    'idPlan' => trim((string) $data['idPlan']),
                    'typeSport' => trim((string) $data['typeSport']),
                    'niveau' => isset($data['niveau']) ? trim((string) $data['niveau']) : '',
                    'intensite' => isset($data['intensite']) ? trim((string) $data['intensite']) : '',
                    'dateSeance' => trim((string) $data['dateSeance']),
                    'dureeMin' => trim((string) $data['dureeMin']),
                    'statut' => isset($data['statut']) ? trim((string) $data['statut']) : 'prevue',
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
                    JsonApi::erreur(404, 'Programme sportif introuvable.');
                    return;
                }
                $patch = JsonApi::lireCorpsJson();
                $merged = array_merge($ex, $patch);
                $v = ValidateurProgrammeSportifApi::valider($merged, $pdo, $id);
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
                    JsonApi::erreur(404, 'Programme sportif introuvable.');
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
