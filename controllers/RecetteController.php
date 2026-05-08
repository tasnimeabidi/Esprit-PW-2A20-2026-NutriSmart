<?php
include_once __DIR__ . '/../Models/config.php';
include_once __DIR__ . '/../Models/Recette.php';
include_once __DIR__ . '/../Models/User.php';
include_once __DIR__ . '/../Models/EmailService.php';

class RecetteController {
    private $db;
    private $recette;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->recette = new Recette($this->db);
    }

    public function listRecettes() {
        $stmt = $this->recette->readAll();
        $recettes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recettes[] = $row;
        }
        return $recettes;
    }

    public function listRecettesByStatus($status) {
        $stmt = $this->recette->readByStatus($status);
        $recettes = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $recettes[] = $row;
        }
        return $recettes;
    }

    public function getRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->readOne()) {
            return [
                "id" => $this->recette->id,
                "nom" => $this->recette->nom,
                "instructions" => $this->recette->instructions,
                "calories_totales" => $this->recette->calories_totales,
                "temps" => $this->recette->temps,
                "status" => $this->recette->status,
                "video_url" => $this->recette->video_url
            ];
        }
        return null;
    }

    public function createRecette($data, $status = 'pending') {
        // Create user if email is provided
        $user_id = null;
        $user_name = null;
        if (!empty($data['email'])) {
            $user = new User($this->db);
            $user->email = $data['email'];
            
            // Check if user already exists
            $existingUser = $user->findByEmail($data['email'])->fetch(PDO::FETCH_ASSOC);
            if ($existingUser) {
                $user_id = $existingUser['id_user'];
                $user_name = $existingUser['nom'] . ' ' . $existingUser['prenom'];
            } else {
                // Create new user with random data
                $firstNames = ['John', 'Jane', 'Michael', 'Sarah', 'David', 'Emma', 'James', 'Olivia', 'Robert', 'Sophia'];
                $lastNames = ['Smith', 'Johnson', 'Williams', 'Brown', 'Jones', 'Garcia', 'Miller', 'Davis', 'Rodriguez', 'Martinez'];
                
                $user->nom = $lastNames[array_rand($lastNames)];
                $user->prenom = $firstNames[array_rand($firstNames)];
                $user->password = password_hash('temp' . rand(1000, 9999), PASSWORD_DEFAULT);
                $user->role = 'client';
                
                if ($user->create()) {
                    $user_id = $user->id_user;
                    $user_name = $user->prenom . ' ' . $user->nom;
                }
            }
        }

        $this->recette->nom = $data['nom_recette'] ?? '';
        
        // Traiter les ingrédients structurés
        $instructions = '';;
        if (isset($data['ingredients']) && is_array($data['ingredients'])) {
            $instructions .= "INGRÉDIENTS:\n";
            foreach ($data['ingredients'] as $index => $ingredient) {
                $quantite = $data['quantites'][$index] ?? '';
                $unite = $data['unites'][$index] ?? '';
                $instructions .= "- {$quantite}{$unite} {$ingredient}\n";
            }
            $instructions .= "\n";
        }
        
        // Traiter les étapes de préparation
        if (isset($data['etapes']) && is_array($data['etapes'])) {
            $instructions .= "PRÉPARATION:\n";
            foreach ($data['etapes'] as $index => $etape) {
                $stepNumber = $index + 1;
                $instructions .= "{$stepNumber}. {$etape}\n";
            }
        }
        
        // Si pas de données structurées, utiliser le champ instructions classique
        if (empty($instructions) && isset($data['instructions'])) {
            $instructions = $data['instructions'];
        }
        
        $this->recette->instructions = trim($instructions);
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;
        $this->recette->temps = $data['temps_preparation'] ?? null;
        $this->recette->video_url = $data['video_url'] ?? null;
        $this->recette->status = $status;
        $this->recette->user_id = $user_id;
        $this->recette->user_name = $user_name;

        if ($this->recette->create()) {
            return ["status" => "success", "message" => "Recette créée avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la création de la recette"];
    }

    public function updateRecette($data) {
        // First read existing data to preserve user_id and user_name
        $this->recette->id = $data['id'];
        $existingUser = null;
        if ($this->recette->readOne()) {
            $existingUser = [
                'user_id' => $this->recette->user_id,
                'user_name' => $this->recette->user_name
            ];
        }
        
        $this->recette->nom = $data['nom_recette'] ?? '';
        $this->recette->instructions = $data['instructions'] ?? '';
        $this->recette->calories_totales = $data['calories_totales'] ?? 0;
        $this->recette->temps = $data['temps_preparation'] ?? null;
        $this->recette->video_url = $data['video_url'] ?? null;
        $this->recette->status = $data['status'] ?? 'pending';
        $this->recette->user_id = $existingUser['user_id'] ?? null;
        $this->recette->user_name = $existingUser['user_name'] ?? null;

        if ($this->recette->update()) {
            return ["status" => "success", "message" => "Recette mise à jour avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la mise à jour de la recette"];
    }

    public function approveRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->readOne()) {
            $oldStatus = $this->recette->status;
            $this->recette->status = 'approved';
            
            if ($this->recette->update()) {
                // Send email notification if status changed
                if ($oldStatus !== 'approved') {
                    $this->sendStatusChangeEmail($id, 'approved');
                }
                return ["status" => "success", "message" => "Recette approuvée avec succès"];
            }
        }
        return ["status" => "error", "message" => "Échec de l'approbation de la recette"];
    }

    public function rejectRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->readOne()) {
            $oldStatus = $this->recette->status;
            $this->recette->status = 'rejected';
            
            if ($this->recette->update()) {
                // Send email notification if status changed
                if ($oldStatus !== 'rejected') {
                    $this->sendStatusChangeEmail($id, 'rejected');
                }
                return ["status" => "success", "message" => "Recette rejetée avec succès"];
            }
        }
        return ["status" => "error", "message" => "Échec du rejet de la recette"];
    }

    public function deleteRecette($id) {
        $this->recette->id = $id;
        if ($this->recette->delete()) {
            return ["status" => "success", "message" => "Recette supprimée avec succès"];
        }
        return ["status" => "error", "message" => "Échec de la suppression de la recette"];
    }

    public function getRecettesByAliment($aliment_name) {
        $query = "SELECT DISTINCT r.* 
                  FROM recette r
                  INNER JOIN recette_aliment ra ON r.id = ra.id_recette
                  INNER JOIN aliment a ON ra.id_aliment = a.id_aliment
                  WHERE r.status = 'approved' AND a.nom LIKE :aliment_name
                  ORDER BY r.id DESC";
        
        $stmt = $this->db->prepare($query);
        $search_term = "%" . $aliment_name . "%";
        $stmt->bindParam(':aliment_name', $search_term);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAlimentsByRecetteId($id_recette) {
        $query = "SELECT a.nom, a.categorie, ra.quantite_g 
                  FROM aliment a
                  INNER JOIN recette_aliment ra ON a.id_aliment = ra.id_aliment
                  WHERE ra.id_recette = :id_recette
                  ORDER BY a.nom";
        
        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id_recette', $id_recette);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function sendStatusChangeEmail($recetteId, $newStatus) {
        // Get recipe details
        $this->recette->id = $recetteId;
        if (!$this->recette->readOne() || empty($this->recette->user_id)) {
            return; // No user associated with this recipe
        }

        // Get user email
        $user = new User($this->db);
        $user->id_user = $this->recette->user_id;
        $userStmt = $user->readOne();
        $userData = $userStmt ? $userStmt->fetch(PDO::FETCH_ASSOC) : false;
        
        if (!$userData || empty($userData['email'])) {
            return; // No email found
        }

        // Send email
        $emailService = new EmailService();
        $emailService->sendRecipeNotification($userData['email'], $this->recette->nom, $newStatus);
    }
}

// Handle AJAX/API requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    $controller = new RecetteController();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        echo json_encode($controller->createRecette($_POST));
        exit;
    } elseif ($action === 'update') {
        echo json_encode($controller->updateRecette($_POST));
        exit;
    } elseif ($action === 'delete') {
        echo json_encode($controller->deleteRecette($_POST['id']));
        exit;
    }
}
?>
