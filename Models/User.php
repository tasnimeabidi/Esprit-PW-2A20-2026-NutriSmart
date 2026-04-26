<?php
require_once __DIR__ . '/../config.php';

class User {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function create($nom, $email, $password, $role = 'utilisateur', $verification_token = null) {
        $stmt = $this->pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role, verification_token, is_verified) VALUES (?, ?, ?, ?, ?, ?)");
        $is_verified = ($verification_token === null) ? 1 : 0; // Social login or no token = verified
        return $stmt->execute([$nom, $email, password_hash($password, PASSWORD_DEFAULT), $role, $verification_token, $is_verified]);
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
        $stmt = $this->pdo->prepare("
            SELECT u.*, p.age, p.poids, p.taille, p.objectifs, p.preferences_alimentaires 
            FROM utilisateur u 
            LEFT JOIN profil_nutritionnel p ON u.id_utilisateur = p.id_utilisateur 
            WHERE u.email = ?
        ");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function getAll() {
        // Renaming columns to id so JS doesn't break entirely if possible
        $stmt = $this->pdo->query("
            SELECT u.id_utilisateur as id, u.nom, u.email, u.role, u.is_blocked, u.mot_de_passe as password_hash, p.age 
            FROM utilisateur u 
            LEFT JOIN profil_nutritionnel p ON u.id_utilisateur = p.id_utilisateur 
            ORDER BY u.id_utilisateur DESC
        ");
        return $stmt->fetchAll();
    }

    public function updateProfile($id_utilisateur, $data) {
        if (empty($data)) return false;

        // Check if user is Admin - Admins shouldn't have nutritional profiles
        $user = $this->getById($id_utilisateur);
        if ($user && (strtolower(trim($user['role'])) === 'admin')) {
            return false;
        }

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

            if ($age !== null && strtolower(trim($role)) !== 'admin') {
                // Upsert logic for profile (ONLY for non-admins)
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

    // --- NEW PASSWORD RESET METHODS ---

    public function setResetToken($email, $token, $expires) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET reset_token = ?, reset_expires = ? WHERE email = ?");
        return $stmt->execute([$token, $expires, $email]);
    }

    public function getUserByToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE reset_token = ? AND reset_expires > NOW()");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }

    public function updatePassword($userId, $newPassword) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id_utilisateur = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }

    public function toggleBlock($id, $status) {
        if ($status == 0) {
            // Unblocking: reset everything
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET is_blocked = 0, login_attempts = 0, blocked_at = NULL WHERE id_utilisateur = ?");
            return $stmt->execute([$id]);
        } else {
            // Manual blocking
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET is_blocked = 1, blocked_at = NULL WHERE id_utilisateur = ?");
            return $stmt->execute([$id]);
        }
    }

    public function incrementLoginAttempts($userId) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET login_attempts = login_attempts + 1 WHERE id_utilisateur = ?");
        $stmt->execute([$userId]);
        
        // Fetch current attempts to check if we should set blocked_at
        $stmt2 = $this->pdo->prepare("SELECT login_attempts FROM utilisateur WHERE id_utilisateur = ?");
        $stmt2->execute([$userId]);
        $attempts = (int)$stmt2->fetchColumn();

        if ($attempts >= 3) {
            $stmt3 = $this->pdo->prepare("UPDATE utilisateur SET is_blocked = 1, blocked_at = NOW() WHERE id_utilisateur = ?");
            $stmt3->execute([$userId]);
        }
        return $attempts;
    }

    public function resetLoginAttempts($userId) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET login_attempts = 0, is_blocked = 0, blocked_at = NULL WHERE id_utilisateur = ?");
        return $stmt->execute([$userId]);
    }

    // --- NEW: OAUTH2 SOCIAL LOGIN METHODS ---

    public function findOrCreateSocialUser($nom, $email, $provider, $oauth_id) {
        // 1. Try to find the user by oauth_id and provider
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE oauth_provider = ? AND oauth_id = ?");
        $stmt->execute([$provider, $oauth_id]);
        $user = $stmt->fetch();

        if ($user) {
            return $user; // Found via social ID
        }

        // 2. Try to find the user by email (in case they previously registered with email, but are now using Google/Facebook)
        if (!empty($email)) {
            $stmtEmail = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmtEmail->execute([$email]);
            $userByEmail = $stmtEmail->fetch();

            if ($userByEmail) {
                // Link this existing account to the social provider and ensure it's verified
                $linkStmt = $this->pdo->prepare("UPDATE utilisateur SET oauth_provider = ?, oauth_id = ?, is_verified = 1 WHERE id_utilisateur = ?");
                $linkStmt->execute([$provider, $oauth_id, $userByEmail['id_utilisateur']]);
                
                // Fetch the updated user
                $stmtEmail->execute([$email]);
                return $stmtEmail->fetch();
            }
        }

        // 3. User doesn't exist at all, we create a new one.
        // We set a random highly secure password since they login via OAuth and shouldn't use it directly.
        $randomPassword = bin2hex(random_bytes(20));
        $role = 'User';

        $insertStmt = $this->pdo->prepare("INSERT INTO utilisateur (nom, email, mot_de_passe, role, oauth_provider, oauth_id, is_verified) VALUES (?, ?, ?, ?, ?, ?, 1)");
        $success = $insertStmt->execute([
            $nom, 
            $email, 
            password_hash($randomPassword, PASSWORD_DEFAULT), 
            $role, 
            $provider, 
            $oauth_id
        ]);

        if ($success) {
            $newId = $this->pdo->lastInsertId();
            return $this->getById($newId);
        }

        return false;
    }

    // --- NEW: FACIAL RECOGNITION (FACE ID) METHODS ---

    /**
     * Enregistre le descripteur mathématique du visage (tableau de 128 nombres)
     */
    public function saveFacialDescriptor($userId, $jsonDescriptor) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET facial_descriptor = ? WHERE id_utilisateur = ?");
        return $stmt->execute([$jsonDescriptor, $userId]);
    }

    /**
     * Récupère tous les descripteurs faciaux de la base de données 
     * pour permettre la comparaison (uniquement ceux qui ont configuré un visage).
     */
    public function getAllFaceDescriptors() {
        $stmt = $this->pdo->query("SELECT id_utilisateur, nom, email, role, facial_descriptor FROM utilisateur WHERE facial_descriptor IS NOT NULL AND facial_descriptor != ''");
        return $stmt->fetchAll();
    }

    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET is_verified = 1, verification_token = NULL WHERE verification_token = ?");
        return $stmt->execute([$token]);
    }

    public function getByVerificationToken($token) {
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE verification_token = ?");
        $stmt->execute([$token]);
        return $stmt->fetch();
    }
}
?>
