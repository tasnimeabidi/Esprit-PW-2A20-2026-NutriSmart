<?php
require_once __DIR__ . '/../config.php';

class User {
    private $pdo;

    /** @var bool Colonne facial_descriptor déjà vérifiée / créée pour cette requête HTTP */
    private static $facialColumnEnsured = false;

    /** @var bool Colonne genre déjà vérifiée / créée pour cette requête HTTP */
    private static $genreColumnEnsured = false;

    /** @var bool Colonnes IA cache profil déjà vérifiées / créées pour cette requête HTTP */
    private static $aiProfileColumnsEnsured = false;

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
        $emailNorm = trim((string) $email);
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.*, p.age, p.poids, p.taille, p.objectifs, p.preferences_alimentaires
                FROM utilisateur u
                LEFT JOIN profil_nutritionnel p ON u.id_utilisateur = p.id_utilisateur
                WHERE LOWER(TRIM(u.email)) = LOWER(?)
            ");
            $stmt->execute([$emailNorm]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            error_log('User::getByEmail (jointure profil) : ' . $e->getMessage());
            $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 1');
            $stmt->execute([$emailNorm]);

            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }

    /** @var bool Colonnes anti-brute-force déjà vérifiées / créées */
    private static $loginSecuritySchemaEnsured = false;

    /**
     * Garantit login_attempts / is_blocked / blocked_at (anciennes BDD sans migration).
     */
    public function ensureLoginSecuritySchema() {
        if (self::$loginSecuritySchemaEnsured) {
            return;
        }
        self::$loginSecuritySchemaEnsured = true;
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN login_attempts INT NOT NULL DEFAULT 0');
        } catch (Throwable $e) {
        }
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN is_blocked TINYINT(1) NOT NULL DEFAULT 0');
        } catch (Throwable $e) {
        }
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN blocked_at DATETIME NULL DEFAULT NULL');
        } catch (Throwable $e) {
        }
    }

    /** @var bool Colonnes inscription (email activation) déjà vérifiées / créées */
    private static $registrationSchemaEnsured = false;

    /**
     * Garantit verification_token / is_verified (imports SQL anciens sans ces colonnes → INSERT inscription échoue).
     */
    public function ensureRegistrationSchema() {
        if (self::$registrationSchemaEnsured) {
            return;
        }
        self::$registrationSchemaEnsured = true;
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN verification_token VARCHAR(64) NULL DEFAULT NULL');
        } catch (Throwable $e) {
        }
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN is_verified TINYINT(1) NOT NULL DEFAULT 0');
        } catch (Throwable $e) {
        }
    }

    /**
     * Lit le hash depuis utilisateur seul (sans jointure), avec tolérance nom de colonne (mot_de_passe / password).
     */
    public function getPasswordHashByEmail($email) {
        $emailNorm = trim((string) $email);
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE LOWER(TRIM(email)) = LOWER(?) LIMIT 1');
        $stmt->execute([$emailNorm]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return '';
        }
        $row = array_change_key_case($row, CASE_LOWER);
        foreach (['mot_de_passe', 'password', 'mdp', 'pass', 'pwd'] as $col) {
            if (!array_key_exists($col, $row)) {
                continue;
            }
            $h = trim((string) $row[$col]);
            if ($h !== '') {
                return $h;
            }
        }

        return '';
    }

    /**
     * Vérifie le mot de passe : bcrypt/argon, puis formats hérités (clair, MD5, SHA1, SHA256).
     */
    public function passwordMatchesInput($plain, $stored) {
        $plain = (string) $plain;
        $stored = trim((string) ($stored ?? ''));
        if ($plain === '' || $stored === '') {
            return false;
        }
        if (password_verify($plain, $stored)) {
            return true;
        }
        $info = password_get_info($stored);
        if (($info['algo'] ?? 0) !== 0) {
            return false;
        }
        if (hash_equals($stored, $plain)) {
            return true;
        }
        if (strlen($stored) === 32 && ctype_xdigit($stored) && hash_equals($stored, md5($plain))) {
            return true;
        }
        if (strlen($stored) === 40 && ctype_xdigit($stored) && hash_equals($stored, sha1($plain))) {
            return true;
        }
        if (strlen($stored) === 64 && ctype_xdigit($stored) && hash_equals($stored, hash('sha256', $plain))) {
            return true;
        }

        return false;
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

    /**
     * Crée la colonne genre si elle manque (imports SQL sans cette colonne).
     */
    private function ensureGenreColumn() {
        if (self::$genreColumnEnsured) {
            return;
        }
        self::$genreColumnEnsured = true;
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN genre VARCHAR(50) NULL DEFAULT NULL');
        } catch (Throwable $e) {
        }
    }

    public function updateGenre($id, $genre) {
        $this->ensureGenreColumn();
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET genre = ? WHERE id_utilisateur = ?");
        return $stmt->execute([$genre, $id]);
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
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE reset_token = ? AND reset_expires > ?");
        $stmt->execute([$token, date('Y-m-d H:i:s')]);
        return $stmt->fetch();
    }

    public function updatePassword($userId, $newPassword) {
        $stmt = $this->pdo->prepare("UPDATE utilisateur SET mot_de_passe = ?, reset_token = NULL, reset_expires = NULL WHERE id_utilisateur = ?");
        return $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);
    }

    public function toggleBlock($id, $status) {
        try {
            if ($status == 0) {
                $stmt = $this->pdo->prepare('UPDATE utilisateur SET is_blocked = 0, login_attempts = 0, blocked_at = NULL WHERE id_utilisateur = ?');

                return $stmt->execute([$id]);
            }
            $stmt = $this->pdo->prepare('UPDATE utilisateur SET is_blocked = 1, blocked_at = NULL WHERE id_utilisateur = ?');

            return $stmt->execute([$id]);
        } catch (Throwable $e) {
            error_log('User::toggleBlock : ' . $e->getMessage());

            return false;
        }
    }

    public function incrementLoginAttempts($userId) {
        try {
            $stmtPre = $this->pdo->prepare('SELECT login_attempts FROM utilisateur WHERE id_utilisateur = ?');
            $stmtPre->execute([$userId]);
            $prev = (int) $stmtPre->fetchColumn();
            if ($prev >= 3) {
                return $prev;
            }

            $stmt = $this->pdo->prepare('UPDATE utilisateur SET login_attempts = login_attempts + 1 WHERE id_utilisateur = ?');
            $stmt->execute([$userId]);

            $stmt2 = $this->pdo->prepare('SELECT login_attempts FROM utilisateur WHERE id_utilisateur = ?');
            $stmt2->execute([$userId]);
            $attempts = (int) $stmt2->fetchColumn();

            if ($attempts >= 3) {
                $stmt3 = $this->pdo->prepare('UPDATE utilisateur SET is_blocked = 1, blocked_at = NOW() WHERE id_utilisateur = ?');
                $stmt3->execute([$userId]);
            }

            return $attempts;
        } catch (Throwable $e) {
            error_log('User::incrementLoginAttempts : ' . $e->getMessage());

            return 0;
        }
    }

    public function resetLoginAttempts($userId) {
        try {
            $stmt = $this->pdo->prepare('UPDATE utilisateur SET login_attempts = 0, is_blocked = 0, blocked_at = NULL WHERE id_utilisateur = ?');

            return $stmt->execute([$userId]);
        } catch (Throwable $e) {
            error_log('User::resetLoginAttempts : ' . $e->getMessage());

            return false;
        }
    }

    // --- NEW: OAUTH2 SOCIAL LOGIN METHODS ---

    public function findOrCreateSocialUser($nom, $email, $provider, $oauth_id, $intent = 'login') {
        // 1. Try to find the user by oauth_id and provider
        $stmt = $this->pdo->prepare("SELECT * FROM utilisateur WHERE oauth_provider = ? AND oauth_id = ?");
        $stmt->execute([$provider, $oauth_id]);
        $user = $stmt->fetch();

        if ($user) {
            if ($intent === 'register') {
                return ['error' => 'Ce compte Google est déjà inscrit. Veuillez vous connecter.'];
            }
            return $this->getById($user['id_utilisateur']); // Load profile data as well
        }

        // 2. Try to find the user by email (in case they previously registered with email, but are now using Google/Facebook)
        if (!empty($email)) {
            $stmtEmail = $this->pdo->prepare("SELECT * FROM utilisateur WHERE email = ?");
            $stmtEmail->execute([$email]);
            $userByEmail = $stmtEmail->fetch();

            if ($userByEmail) {
                if ($intent === 'register') {
                    return ['error' => 'Un compte avec cette adresse e-mail existe déjà. Veuillez vous connecter.'];
                }
                // Link this existing account to the social provider and ensure it's verified
                $linkStmt = $this->pdo->prepare("UPDATE utilisateur SET oauth_provider = ?, oauth_id = ?, is_verified = 1 WHERE id_utilisateur = ?");
                $linkStmt->execute([$provider, $oauth_id, $userByEmail['id_utilisateur']]);
                
                // Fetch the updated user using getById to include profile data
                return $this->getById($userByEmail['id_utilisateur']);
            }
        }

        // 3. User doesn't exist at all, we create a new one.
        if ($intent === 'login') {
            return ['error' => 'Aucun compte trouvé avec ce compte Google. Veuillez vous inscrire.'];
        }

        // We set a random highly secure password since they login via OAuth and shouldn't use it directly.
        $randomPassword = bin2hex(random_bytes(20));
        $role = 'utilisateur';

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
     * Crée la colonne facial_descriptor si elle manque (schémas anciens sans migration).
     */
    private function ensureFacialDescriptorColumn() {
        if (self::$facialColumnEnsured) {
            return;
        }
        self::$facialColumnEnsured = true;
        try {
            $this->pdo->exec('ALTER TABLE utilisateur ADD COLUMN facial_descriptor TEXT NULL DEFAULT NULL');
        } catch (Throwable $e) {
            // Colonne déjà présente ou autre — la requête suivante tranchera
        }
    }

    /**
     * Enregistre le descripteur mathématique du visage (tableau de 128 nombres)
     */
    public function saveFacialDescriptor($userId, $jsonDescriptor) {
        $this->ensureFacialDescriptorColumn();
        try {
            $stmt = $this->pdo->prepare("UPDATE utilisateur SET facial_descriptor = ? WHERE id_utilisateur = ?");
            return $stmt->execute([$jsonDescriptor, $userId]);
        } catch (Throwable $e) {
            error_log('saveFacialDescriptor: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère tous les descripteurs faciaux de la base de données 
     * pour permettre la comparaison (uniquement ceux qui ont configuré un visage).
     */
    public function getAllFaceDescriptors() {
        $this->ensureFacialDescriptorColumn();
        try {
            $stmt = $this->pdo->query("SELECT id_utilisateur, nom, email, role, facial_descriptor FROM utilisateur WHERE facial_descriptor IS NOT NULL AND facial_descriptor != ''");
            return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            error_log('getAllFaceDescriptors: ' . $e->getMessage());
            return [];
        }
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

    // --- NEW: AI RECOMMENDATIONS ---

    /**
     * Crée ai_recommendations / ai_last_generated si absentes (schémas SQL plus anciens).
     */
    private function ensureAiProfileColumns() {
        if (self::$aiProfileColumnsEnsured) {
            return;
        }
        self::$aiProfileColumnsEnsured = true;
        try {
            $this->pdo->exec('ALTER TABLE profil_nutritionnel ADD COLUMN ai_recommendations LONGTEXT NULL DEFAULT NULL');
        } catch (Throwable $e) {
        }
        try {
            $this->pdo->exec('ALTER TABLE profil_nutritionnel ADD COLUMN ai_last_generated DATETIME NULL DEFAULT NULL');
        } catch (Throwable $e) {
        }
    }

    public function getAiRecommendations($id_utilisateur) {
        $this->ensureAiProfileColumns();
        $stmt = $this->pdo->prepare("SELECT ai_recommendations, ai_last_generated FROM profil_nutritionnel WHERE id_utilisateur = ?");
        $stmt->execute([$id_utilisateur]);
        return $stmt->fetch();
    }

    public function saveAiRecommendations($id_utilisateur, $json_data) {
        $this->ensureAiProfileColumns();
        // Ensure profile exists first
        $check = $this->pdo->prepare("SELECT id_utilisateur FROM profil_nutritionnel WHERE id_utilisateur = ?");
        $check->execute([$id_utilisateur]);
        if (!$check->fetch()) {
            $stmt = $this->pdo->prepare("INSERT INTO profil_nutritionnel (id_utilisateur, age, poids, taille) VALUES (?, 0, 0, 0)");
            $stmt->execute([$id_utilisateur]);
        }
        
        $stmt = $this->pdo->prepare("UPDATE profil_nutritionnel SET ai_recommendations = ?, ai_last_generated = NOW() WHERE id_utilisateur = ?");
        return $stmt->execute([$json_data, $id_utilisateur]);
    }
}
?>
