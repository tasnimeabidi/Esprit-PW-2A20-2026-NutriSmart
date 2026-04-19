<?php
require_once __DIR__ . '/../config.php';

class User {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function create($nom, $email, $password, $role = 'utilisateur') {
        $stmt = $this->pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.age, p.poids, p.taille, p.objectifs, p.preferences_alimentaires 
            FROM utilisateur u 
            LEFT JOIN profil_nutritionnel p ON u.id_utilisateur = p.id_utilisateur 
            WHERE u.id_utilisateur = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getAll() {
        // Renaming columns to id so JS doesn't break entirely if possible
        $stmt = $this->pdo->query("
            SELECT u.id_utilisateur as id, u.nom, u.email, u.role, u.mot_de_passe as password_hash, p.age 
            FROM utilisateur u 
            LEFT JOIN profil_nutritionnel p ON u.id_utilisateur = p.id_utilisateur 
            ORDER BY u.id_utilisateur DESC
        ");
        return $stmt->fetchAll();
    }

    public function updateProfile($id_utilisateur, $data) {
        if (empty($data)) return false;

        // Check if profile exists
        $check = $this->pdo->prepare("SELECT id_utilisateur FROM profil_nutritionnel WHERE id_utilisateur = ?");
        $check->execute([$id_utilisateur]);
        $exists = $check->fetch();

        if ($exists) {
            $fields = [];
            $values = [];
            foreach ($data as $key => $val) {
                $fields[] = "$key = ?";
                $values[] = $val;
            }
            $values[] = $id_utilisateur;
            $stmt = $this->pdo->prepare("UPDATE profil_nutritionnel SET " . implode(', ', $fields) . " WHERE id_utilisateur = ?");
            return $stmt->execute($values);
        } else {
            // Define defaults since age, poids, taille are NOT NULL
            $age = $data['age'] ?? 0;
            $poids = $data['poids'] ?? 0;
            $taille = $data['taille'] ?? 0;
            $objectifs = $data['objectifs'] ?? null;
            $prefs = $data['preferences_alimentaires'] ?? null;

            $stmt = $this->pdo->prepare("INSERT INTO profil_nutritionnel (id_utilisateur, age, poids, taille, objectifs, preferences_alimentaires) VALUES (?, ?, ?, ?, ?, ?)");
            return $stmt->execute([$id_utilisateur, $age, $poids, $taille, $objectifs, $prefs]);
        }
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM utilisateur WHERE id_utilisateur = ?");
        return $stmt->execute([$id]);
    }

    public function updateUserByAdmin($id, $nom, $email, $role, $age, $password = null) {
        try {
            $this->pdo->beginTransaction();

            if (!empty($password)) {
                $stmt = $this->pdo->prepare("UPDATE utilisateur SET nom = ?, email = ?, role = ?, mot_de_passe = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nom, $email, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $stmt = $this->pdo->prepare("UPDATE utilisateur SET nom = ?, email = ?, role = ? WHERE id_utilisateur = ?");
                $stmt->execute([$nom, $email, $role, $id]);
            }

            if ($age !== null) {
                // Upsert logic for profile
                $check = $this->pdo->prepare("SELECT id_utilisateur FROM profil_nutritionnel WHERE id_utilisateur = ?");
                $check->execute([$id]);
                if ($check->fetch()) {
                    $stmt2 = $this->pdo->prepare("UPDATE profil_nutritionnel SET age = ? WHERE id_utilisateur = ?");
                    $stmt2->execute([$age, $id]);
                } else {
                    $stmt2 = $this->pdo->prepare("INSERT INTO profil_nutritionnel (id_utilisateur, age, poids, taille, objectifs, preferences_alimentaires) VALUES (?, ?, 0, 0, NULL, NULL)");
                    $stmt2->execute([$id, $age]);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (Exception $e) {
            $this->pdo->rollBack();
            return $e->getMessage();
        }
    }
}
?>
