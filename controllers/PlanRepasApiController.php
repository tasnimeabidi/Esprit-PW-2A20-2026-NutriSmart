<?php
declare(strict_types=1);

final class PlanRepasApiController
{
    public function traiter(): void
    {
        $pdo = Database::getConnection();
        $model = new PlanRepas($pdo);
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        try {
            if ($method === 'GET') {
                JsonApi::envoyer(200, $model->listerPourApi());
                return;
            }
            if ($method === 'POST') {
                $data = JsonApi::lireCorpsJson();
                $v = ValidateurPlanRepasApi::valider($data);
                if (!$v->ok()) {
                    JsonApi::erreur(400, $v->premierMessage());
                    return;
                }
                $row = $model->creer([
                    'idUtilisateur' => trim((string) $data['idUtilisateur']),
                    'dateDebut' => trim((string) $data['dateDebut']),
                    'dateFin' => trim((string) $data['dateFin']),
                    'objectif' => trim((string) $data['objectif']),
                    'statut' => isset($data['statut']) ? trim((string) $data['statut']) : 'brouillon',
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
                    JsonApi::erreur(404, 'Plan repas introuvable.');
                    return;
                }
                $patch = JsonApi::lireCorpsJson();
                $merged = array_merge($ex, $patch);
                $v = ValidateurPlanRepasApi::valider($merged);
                if (!$v->ok()) {
                    JsonApi::erreur(400, $v->premierMessage());
                    return;
                }
                $row = $model->mettreAJour($id, [
                    'idUtilisateur' => trim((string) $merged['idUtilisateur']),
                    'dateDebut' => trim((string) $merged['dateDebut']),
                    'dateFin' => trim((string) $merged['dateFin']),
                    'objectif' => trim((string) $merged['objectif']),
                    'statut' => isset($merged['statut']) ? trim((string) $merged['statut']) : 'brouillon',
                ]);
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
                    JsonApi::erreur(404, 'Plan repas introuvable.');
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
