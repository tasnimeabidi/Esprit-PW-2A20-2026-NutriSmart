<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

class RecetteController
{
    private PDO $db;
    private Recette $recette;

    public function __construct()
    {
        $this->db = Database::getConnection();
        $this->recette = new Recette($this->db);
    }

    /** @return list<array<string, mixed>> */
    public function listAllRecettes(): array
    {
        $stmt = $this->recette->readAll();
        if (!$stmt) {
            return [];
        }
        $out = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $out[] = $this->normalizeRecetteRow($row);
        }
        return $out;
    }

    public function listRecettesByStatus(string $status = 'approved'): array
    {
        $stmt = $this->recette->readByStatus($status);
        $recettes = [];
        if ($stmt) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $recettes[] = $this->normalizeRecetteRow($row);
            }
        }
        return $recettes;
    }

    public function listRecettes(): array
    {
        return $this->listRecettesByStatus('approved');
    }

    /** @return array<string, mixed>|null */
    public function getRecette($id)
    {
        $this->recette->id = $id;
        if ($this->recette->readOne()) {
            return [
                'id' => $this->recette->id,
                'nom' => $this->recette->nom,
                'nom_recette' => $this->recette->nom,
                'instructions' => $this->recette->instructions,
                'calories_totales' => $this->recette->calories_totales,
                'temps' => $this->recette->temps,
                'status' => $this->recette->status,
                'video_url' => $this->recette->video_url,
            ];
        }
        return null;
    }

    public function createRecette(array $data, string $status = 'pending'): array
    {
        [$userId, $userName] = $this->resolveUserFromEmail($data['email'] ?? null);

        $this->recette->nom = $data['nom_recette'] ?? ($data['nom'] ?? '');
        $instructions = '';

        if (isset($data['ingredients']) && is_array($data['ingredients'])) {
            $instructions .= "INGRÉDIENTS:\n";
            foreach ($data['ingredients'] as $index => $ingredient) {
                $quantite = $data['quantites'][$index] ?? '';
                $unite = $data['unites'][$index] ?? '';
                $instructions .= "- {$quantite}{$unite} {$ingredient}\n";
            }
            $instructions .= "\n";
        }

        if (isset($data['etapes']) && is_array($data['etapes'])) {
            $instructions .= "PRÉPARATION:\n";
            foreach ($data['etapes'] as $index => $etape) {
                $stepNumber = $index + 1;
                $instructions .= "{$stepNumber}. {$etape}\n";
            }
        }

        if ($instructions === '' && isset($data['instructions'])) {
            $instructions = (string) $data['instructions'];
        }

        $this->recette->instructions = trim($instructions);
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;
        $this->recette->temps = $data['temps_preparation'] ?? null;
        $this->recette->video_url = $data['video_url'] ?? null;
        $this->recette->status = $status;
        $this->recette->user_id = $userId;
        $this->recette->user_name = $userName;
        $this->recette->proposer_email = trim((string) ($data['email'] ?? ''));

        if ($this->recette->create()) {
            return ['status' => 'success', 'message' => 'Recette créée avec succès'];
        }
        return ['status' => 'error', 'message' => 'Échec de la création de la recette'];
    }

    public function updateRecette(array $data): array
    {
        $this->recette->id = $data['id'];
        $existingUser = ['user_id' => null, 'user_name' => null];
        if ($this->recette->readOne()) {
            $existingUser = [
                'user_id' => $this->recette->user_id,
                'user_name' => $this->recette->user_name,
            ];
        }

        $this->recette->nom = $data['nom_recette'] ?? ($data['nom'] ?? '');
        $this->recette->instructions = $data['instructions'] ?? '';
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;
        $this->recette->temps = $data['temps_preparation'] ?? null;
        $this->recette->video_url = $data['video_url'] ?? null;
        $this->recette->status = $data['status'] ?? 'pending';
        $this->recette->user_id = $existingUser['user_id'];
        $this->recette->user_name = $existingUser['user_name'];

        if ($this->recette->update()) {
            return ['status' => 'success', 'message' => 'Recette mise à jour avec succès'];
        }
        return ['status' => 'error', 'message' => 'Échec de la mise à jour de la recette'];
    }

    /** Comme nutrismart-chahine : mise à jour complète de la ligne puis envoi mail. */
    public function approveRecette($id): array
    {
        $rid = (int) $id;
        $this->recette->id = $rid;
        if (!$this->recette->readOne()) {
            return ['status' => 'error', 'message' => "Échec de l'approbation"];
        }
        $oldStatus = (string) ($this->recette->status ?? '');
        $this->recette->status = 'approved';
        if (!$this->recette->update()) {
            return ['status' => 'error', 'message' => "Échec de l'approbation"];
        }
        if ($oldStatus !== 'approved') {
            $this->sendStatusChangeEmail($rid, 'approved');
        }

        return ['status' => 'success', 'message' => 'Recette approuvée'];
    }

    public function rejectRecette($id): array
    {
        $rid = (int) $id;
        $this->recette->id = $rid;
        if (!$this->recette->readOne()) {
            return ['status' => 'error', 'message' => 'Échec du rejet'];
        }
        $oldStatus = (string) ($this->recette->status ?? '');
        $this->recette->status = 'rejected';
        if (!$this->recette->update()) {
            return ['status' => 'error', 'message' => 'Échec du rejet'];
        }
        if ($oldStatus !== 'rejected') {
            $this->sendStatusChangeEmail($rid, 'rejected');
        }

        return ['status' => 'success', 'message' => 'Recette rejetée'];
    }

    /** Notifie l’auteur : e-mail du compte lié, sinon colonne proposer_email si présente. */
    private function sendStatusChangeEmail(int $recetteId, string $newStatus): void
    {
        $this->recette->id = $recetteId;
        if (!$this->recette->readOne()) {
            return;
        }

        $to = null;
        $userId = $this->recette->user_id;
        if ($userId !== null && $userId !== 0) {
            $st = $this->db->prepare('SELECT email FROM utilisateur WHERE id_utilisateur = ? LIMIT 1');
            $st->execute([(int) $userId]);
            $userData = $st->fetch(PDO::FETCH_ASSOC);
            if ($userData && !empty($userData['email'])) {
                $to = (string) $userData['email'];
            }
        }

        if (($to === null || $to === '') && !empty($this->recette->proposer_email)
            && filter_var($this->recette->proposer_email, FILTER_VALIDATE_EMAIL)) {
            $to = (string) $this->recette->proposer_email;
        }

        if (($to === null || $to === '')) {
            try {
                $st = $this->db->prepare('SELECT proposer_email FROM recette WHERE id = ? LIMIT 1');
                $st->execute([$recetteId]);
                $row = $st->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['proposer_email']) && filter_var((string) $row['proposer_email'], FILTER_VALIDATE_EMAIL)) {
                    $to = (string) $row['proposer_email'];
                }
            } catch (Throwable $e) {
                // colonne absente
            }
        }

        if ($to === null || $to === '') {
            error_log('NutriSmart: notification recette id=' . $recetteId . ' impossible — aucun e-mail (compte non lié ou proposer_email vide).');

            return;
        }

        $emailService = new EmailService();
        if (!$emailService->sendRecipeNotification($to, (string) $this->recette->nom, $newStatus)) {
            error_log('NutriSmart: envoi vers ' . $to . ' échoué — vérifiez .env (SENDINBLUE_API_KEY, SENDER_EMAIL) et le tableau de bord Brevo.');
        }
    }

    public function deleteRecette($id): array
    {
        $this->recette->id = $id;
        if ($this->recette->delete()) {
            return ['status' => 'success', 'message' => 'Recette supprimée avec succès'];
        }
        return ['status' => 'error', 'message' => 'Échec de la suppression de la recette'];
    }

    /** @return list<array<string, mixed>> */
    public function getRecettesByAliment(string $aliment_name): array
    {
        try {
            $apk = Aliment::primaryKeyColumn($this->db);
            $query = 'SELECT DISTINCT r.*
                  FROM recette r
                  INNER JOIN recette_aliment ra ON r.id = ra.id_recette
                  INNER JOIN aliment a ON ra.id_aliment = a.`' . $apk . '`
                  WHERE r.status = \'approved\' AND a.nom LIKE :aliment_name
                  ORDER BY r.id DESC';

            $stmt = $this->db->prepare($query);
            $search_term = '%' . $aliment_name . '%';
            $stmt->bindParam(':aliment_name', $search_term);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $out = [];
            foreach ($rows as $row) {
                $out[] = $this->normalizeRecetteRow($row);
            }
            return $out;
        } catch (Throwable $e) {
            return [];
        }
    }

    /** @return list<array<string, mixed>> */
    public function getAlimentsByRecetteId($id_recette): array
    {
        try {
            $apk = Aliment::primaryKeyColumn($this->db);
            $query = 'SELECT a.nom, a.categorie, ra.quantite_g
                  FROM aliment a
                  INNER JOIN recette_aliment ra ON a.`' . $apk . '` = ra.id_aliment
                  WHERE ra.id_recette = :id_recette
                  ORDER BY a.nom';

            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':id_recette', $id_recette);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /** @param array<string, mixed> $row */
    private function normalizeRecetteRow(array $row): array
    {
        if (!isset($row['nom_recette']) && isset($row['nom'])) {
            $row['nom_recette'] = $row['nom'];
        }
        return $row;
    }

    /**
     * Associe la recette à un compte : recherche par e-mail, ou création d’un utilisateur
     * minimal comme chez Chahine (sinon pas d’e-mail à l’approbation).
     *
     * @return array{0: ?int, 1: ?string}
     */
    private function resolveUserFromEmail(?string $email): array
    {
        $email = trim((string) $email);
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return [null, null];
        }

        $st = $this->db->prepare('SELECT id_utilisateur, nom FROM utilisateur WHERE email = ? LIMIT 1');
        $st->execute([$email]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            return [(int) $row['id_utilisateur'], trim((string) $row['nom'])];
        }

        $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'James', 'Olivia', 'Robert', 'Sophia'];
        $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
        $nom = $firstNames[random_int(0, count($firstNames) - 1)] . ' ' . $lastNames[random_int(0, count($lastNames) - 1)];
        $hash = password_hash('temp' . random_int(1000, 9999), PASSWORD_DEFAULT);

        try {
            $ins = $this->db->prepare(
                'INSERT INTO utilisateur (nom, email, mot_de_passe, role, verification_token, is_verified) VALUES (?, ?, ?, ?, NULL, 1)'
            );
            $ins->execute([$nom, $email, $hash, 'utilisateur']);

            return [(int) $this->db->lastInsertId(), $nom];
        } catch (Throwable $e) {
            try {
                $ins = $this->db->prepare(
                    'INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)'
                );
                $ins->execute([$nom, $email, $hash, 'utilisateur']);

                return [(int) $this->db->lastInsertId(), $nom];
            } catch (Throwable $e2) {
                error_log('NutriSmart resolveUserFromEmail: ' . $e2->getMessage());

                return [null, null];
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $controller = new RecetteController();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        echo json_encode($controller->createRecette($_POST));
        exit;
    }
    if ($action === 'update') {
        echo json_encode($controller->updateRecette($_POST));
        exit;
    }
    if ($action === 'delete') {
        echo json_encode($controller->deleteRecette($_POST['id']));
        exit;
    }
}
