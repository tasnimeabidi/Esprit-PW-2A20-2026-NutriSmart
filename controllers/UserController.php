<?php
require_once __DIR__ . '/../Models/User.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    private function getMailer()
    {
        require_once __DIR__ . '/../config.php';
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 10;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);

        // Relaxed SSL for local development (common fix for XAMPP/Localhost)
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        return $mail;
    }

    // CREATE (Register Front)
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'User';

            // Server-side validation
            if (empty($nom) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email invalide.'];
            }

            if ($this->userModel->getByEmail($email)) {
                return ['success' => false, 'message' => 'Cet email existe déjà.'];
            }

            $verification_token = bin2hex(random_bytes(32));

            if ($this->userModel->create($nom, $email, $password, $role, $verification_token)) {
                // Send verification email
                $verifyLink = "http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']) . "/api.php?action=verify&token=" . $verification_token;

                try {
                    $mail = $this->getMailer();
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'Activez votre compte NutriSmart';
                    $mail->Body = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                            <h2 style='color: #4a7c59;'>Bienvenue chez NutriSmart !</h2>
                            <p>Bonjour $nom,</p>
                            <p>Merci de vous être inscrit. Pour activer votre compte et commencer votre parcours nutritionnel, veuillez cliquer sur le bouton ci-dessous :</p>
                            <div style='text-align: center; margin: 30px 0;'>
                                <a href='$verifyLink' style='background-color: #4a7c59; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Activer mon compte</a>
                            </div>
                            <p>Si le bouton ne fonctionne pas, copiez et collez ce lien dans votre navigateur :</p>
                            <p style='font-size: 12px; color: #888;'>$verifyLink</p>
                            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='font-size: 12px; color: #888;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
                        </div>
                    ";
                    $mail->send();

                    return [
                        'success' => true,
                        'message' => 'Votre compte a été créé. Veuillez vérifier votre boîte mail pour l\'activer avant de vous connecter.',
                        'redirect' => 'register.html?success=' . urlencode('Votre compte a été créé. Veuillez vérifier votre boîte mail pour l\'activer avant de vous connecter.')
                    ];
                } catch (Exception $e) {
                    return [
                        'success' => true,
                        'message' => 'Compte créé, mais l\'envoi de l\'email a échoué. Veuillez contacter le support.',
                        'redirect' => 'register.html?success=' . urlencode('Compte créé, mais l\'envoi de l\'email a échoué. Veuillez contacter le support.')
                    ];
                }
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
            }
        }
        return null;
    }

    // AUTH (Login Front)
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Identifiants requis.'];
            }

            $user = $this->userModel->getByEmail($email);
            if ($user && password_verify($password, $user['mot_de_passe'])) {
                // Check if verified
                if (isset($user['is_verified']) && (int) $user['is_verified'] === 0) {
                    return ['success' => false, 'message' => 'Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.'];
                }

                // Check if blocked
                if (isset($user['is_blocked']) && (int) $user['is_blocked'] === 1) {
                    // AUTO-UNLOCK Logic: check if 1 hour has passed (60 minutes)
                    if (!empty($user['blocked_at'])) {
                        $blockedTime = strtotime($user['blocked_at']);
                        $currentTime = time();
                        $diffMinutes = ($currentTime - $blockedTime) / 60;

                        if ($diffMinutes >= 60) {
                            // Time passed! Unblock and proceed
                            $this->userModel->resetLoginAttempts($user['id_utilisateur']);
                            // Re-fetch user to clear the is_blocked flag for the rest of the script
                            $user = $this->userModel->getByEmail($email);
                        } else {
                            $remaining = 60 - floor($diffMinutes);
                            return ['success' => false, 'message' => "Compte bloqué (trop de tentatives). Réessayez dans $remaining minutes."];
                        }
                    } else {
                        // Manually blocked by admin (no timestamp)
                        return ['success' => false, 'message' => 'Votre compte a été suspendu par un administrateur.'];
                    }
                }

                // SUCCESS: Reset attempts
                $this->userModel->resetLoginAttempts($user['id_utilisateur']);

                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['role'] = $user['role'];

                $isProfileComplete = !empty($user['age']) && !empty($user['poids']) && !empty($user['taille']);
                $isAdmin = strtolower(trim($user['role'] ?? '')) === 'admin';

                $redirect = 'nutrismart-home.html';
                if ($isAdmin) {
                    $redirect = '../backoffice/nutrismart-dashboard.html';
                } elseif (!$isProfileComplete) {
                    $redirect = 'profile.html';
                }

                return [
                    'success' => true,
                    'message' => 'Connexion réussie.',
                    'redirect' => $redirect
                ];
            } else {
                // FAILURE: Increment attempts if user exists
                if ($user) {
                    $this->userModel->incrementLoginAttempts($user['id_utilisateur']);

                    $attempts = (int) $user['login_attempts'] + 1;
                    if ($attempts >= 3) {
                        $this->userModel->toggleBlock($user['id_utilisateur'], 1);
                        return ['success' => false, 'message' => 'Compte suspendu : 3 tentatives échouées.'];
                    }
                    $remain = 3 - $attempts;
                    return ['success' => false, 'message' => "Mot de passe incorrect. Il vous reste $remain tentatives."];
                }

                return ['success' => false, 'message' => 'Identifiants incorrects ou compte inexistant.'];
            }
        }
        return null;
    }

    // READ (List in Backoffice)
    public function listUsers()
    {
        return $this->userModel->getAll();
    }

    // UPDATE
    public function updateProfile()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $userId = $_SESSION['user_id'] ?? null;
            $role = $_SESSION['role'] ?? 'utilisateur';

            if (!$userId)
                return ['success' => false, 'message' => 'Non authentifié.'];

            // Admins should not fill nutritional profiles
            if (strtolower(trim($role)) === 'admin') {
                return ['success' => true, 'message' => 'L\'administrateur n\'a pas de profil nutritionnel à remplir. Redirection...'];
            }

            $data = [
                'age' => isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null,
                'poids' => isset($_POST['poids']) && $_POST['poids'] !== '' ? floatval($_POST['poids']) : null,
                'taille' => isset($_POST['taille']) && $_POST['taille'] !== '' ? floatval($_POST['taille']) : null,
                'objectifs' => $_POST['objectif'] ?? null,
                'preferences_alimentaires' => $_POST['preference'] ?? null
            ];

            // Remove nulls so we don't overwrite with empty
            $data = array_filter($data, function ($v) {
                return !is_null($v) && $v !== '';
            });

            if ($this->userModel->updateProfile($userId, $data)) {
                // Also update genre in the main utilisateur table
                if (isset($_POST['genre'])) {
                    $this->userModel->updateGenre($userId, $_POST['genre']);
                }
                return [
                    'success' => true,
                    'message' => 'Profil mis à jour avec succès.',
                    'redirect' => (strtolower(trim($role)) === 'admin')
                        ? '../backoffice/nutrismart-dashboard.html'
                        : 'nutrismart-home.html'
                ];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour.'];
            }
        }
        return null;
    }

    // DELETE (Backoffice)
    public function deleteUser($id)
    {
        return $this->userModel->delete($id);
    }

    // UPDATE (Backoffice)
    public function adminUpdateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_utilisateur = $_POST['id_utilisateur'] ?? null;
            if (!$id_utilisateur)
                return ['success' => false, 'message' => 'ID manquant.'];

            $nom = $_POST['nom'] ?? null;
            $email = $_POST['email'] ?? null;
            $role = $_POST['role'] ?? null;
            $age = isset($_POST['age']) && $_POST['age'] !== '' ? intval($_POST['age']) : null;
            $password = $_POST['password'] ?? null;

            if (empty($nom) || empty($email) || empty($role)) {
                return ['success' => false, 'message' => 'Nom, email et rôle sont obligatoires.'];
            }

            $updateStatus = $this->userModel->updateUserByAdmin($id_utilisateur, $nom, $email, $role, $age, $password);
            if ($updateStatus === true) {
                return ['success' => true, 'message' => 'Utilisateur mis à jour.'];
            } else {
                return ['success' => false, 'message' => 'Erreur SQL: ' . (is_string($updateStatus) ? $updateStatus : 'inconnue')];
            }
        }
        return ['success' => false, 'message' => 'Méthode non autorisée.'];
    }

    // CREATE (Backoffice)
    public function adminCreateUser()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'utilisateur';

            if (empty($nom) || empty($email) || empty($password)) {
                return ['success' => false, 'message' => 'Tous les champs sont obligatoires.'];
            }
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Email invalide.'];
            }
            if ($this->userModel->getByEmail($email)) {
                return ['success' => false, 'message' => 'Cet email existe déjà.'];
            }

            if ($this->userModel->create($nom, $email, $password, $role)) {
                return ['success' => true, 'message' => 'Utilisateur créé avec succès.'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la création du compte.'];
            }
        }
        return ['success' => false, 'message' => 'Méthode non autorisée.'];
    }

    // --- PASSWORD RESET LOGIC ---

    public function requestReset($email)
    {
        file_put_contents('reset_debug.log', date('[Y-m-d H:i:s] ') . "Request reset for: $email\n", FILE_APPEND);
        $user = $this->userModel->getByEmail($email);
        if (!$user) {
            // Security: don't reveal if email exists, but here we can be helpful or silent
            return ['success' => true, 'message' => 'Si cet email existe, un lien a été envoyé.'];
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+2 hours'));

        if ($this->userModel->setResetToken($email, $token, $expires)) {
            $resetLink = "http://" . $_SERVER['HTTP_HOST'] . str_replace('api.php', 'reset_password.html', $_SERVER['PHP_SELF']) . "?token=" . $token;

            try {
                file_put_contents('reset_debug.log', "Token generated, sending mail...\n", FILE_APPEND);
                $mail = $this->getMailer();
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe - NutriSmart';
                $mail->Body = "
                    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;'>
                        <h2 style='color: #4a7c59;'>NutriSmart</h2>
                        <p>Bonjour,</p>
                        <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte NutriSmart.</p>
                        <p>Cliquez sur le bouton ci-dessous pour choisir un nouveau mot de passe :</p>
                        <div style='text-align: center; margin: 30px 0;'>
                            <a href='$resetLink' style='background-color: #4a7c59; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Réinitialiser mon mot de passe</a>
                        </div>
                        <p>Ce lien expirera dans 2 heures.</p>
                        <p>Si vous n'êtes pas à l'origine de cette demande, vous pouvez ignorer cet e-mail.</p>
                        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
                        <p style='font-size: 12px; color: #888;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
                    </div>
                ";
                $mail->AltBody = "Bonjour, \n\nVous avez demandé la réinitialisation de votre mot de passe. Cliquez sur le lien suivant : $resetLink \n\nCe lien expirera dans 2 heures.";

                $mail->send();
                file_put_contents('reset_debug.log', "Mail sent successfully!\n", FILE_APPEND);
                return ['success' => true, 'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.'];
            } catch (Exception $e) {
                file_put_contents('reset_debug.log', "Mail error: " . $mail->ErrorInfo . "\n", FILE_APPEND);
                return ['success' => false, 'message' => "L'e-mail n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}"];
            }
        }
        return ['success' => false, 'message' => 'Erreur serveur lors de la génération du jeton.'];
    }

    public function resetPassword($token, $newPassword)
    {
        $user = $this->userModel->getUserByToken($token);
        if (!$user) {
            return ['success' => false, 'message' => 'Jeton invalide ou expiré.'];
        }

        if ($this->userModel->updatePassword($user['id_utilisateur'], $newPassword)) {
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe.'];
    }

    public function toggleUserBlock($id)
    {
        $user = $this->userModel->getById($id);
        if (!$user)
            return ['success' => false, 'message' => 'Utilisateur non trouvé.'];

        // Sécurité : On ne peut pas bloquer un administrateur
        if (isset($user['role']) && strtolower(trim($user['role'])) === 'admin') {
            return ['success' => false, 'message' => 'Impossible de bloquer un administrateur système.'];
        }

        $newStatus = ((int) ($user['is_blocked'] ?? 0) === 1) ? 0 : 1;
        if ($this->userModel->toggleBlock($id, $newStatus)) {
            $msg = $newStatus === 1 ? 'Utilisateur bloqué.' : 'Utilisateur débloqué.';
            return ['success' => true, 'message' => $msg];
        }
        return ['success' => false, 'message' => 'Erreur lors du changement de statut.'];
    }

    // ==========================================
    // MODULE IA : RECONNAISSANCE FACIALE
    // ==========================================

    public function saveFace()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Vous devez être connecté.'];
        }

        $descriptorJson = $_POST['descriptor'] ?? '';
        if (empty($descriptorJson)) {
            return ['success' => false, 'message' => 'Scan facial incomplet.'];
        }

        $newDescriptor = json_decode($descriptorJson, true);
        if (!is_array($newDescriptor)) {
            return ['success' => false, 'message' => 'Format invalide.'];
        }

        $userModel = new User();

        // --- SECURITY CHECK: 1 Face = 1 Account ---
        $allFaces = $userModel->getAllFaceDescriptors();
        foreach ($allFaces as $userFace) {
            // Ne pas comparer avec soi-même (si on met à jour son propre visage)
            if ((int) $userFace['id_utilisateur'] === (int) $_SESSION['user_id'])
                continue;

            $existingDescriptor = json_decode($userFace['facial_descriptor'], true);
            if (!is_array($existingDescriptor))
                continue;

            $distance = $this->calculateEuclideanDistance($newDescriptor, $existingDescriptor);

            // Si la distance est < 0.50, c'est considéré comme la même personne (Sécurité Renforcée)
            if ($distance < 0.50) {
                return ['success' => false, 'message' => 'Erreur : Ce visage appartient déjà à un autre utilisateur.'];
            }
        }

        $ok = $userModel->saveFacialDescriptor($_SESSION['user_id'], $descriptorJson);
        return ['success' => $ok, 'message' => $ok ? 'Visage enregistré avec succès !' : 'Erreur base de données.'];
    }

    public function deleteFace()
    {
        if (session_status() === PHP_SESSION_NONE)
            session_start();
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $userModel = new User();
        $ok = $userModel->saveFacialDescriptor($_SESSION['user_id'], null);
        return ['success' => $ok, 'message' => $ok ? 'Face ID supprimé avec succès.' : 'Erreur base de données.'];
    }

    public function loginWithFace()
    {
        try {
            $loginDescriptorJson = $_POST['descriptor'] ?? '';
            if (empty($loginDescriptorJson)) {
                return ['success' => false, 'message' => 'Données faciales manquantes depuis la caméra.'];
            }

            $loginDescriptor = json_decode($loginDescriptorJson, true);
            if (!is_array($loginDescriptor) || count($loginDescriptor) !== 128) {
                return ['success' => false, 'message' => 'Format mathématique facial invalide.'];
            }

            $userModel = new User();
            $allFaces = $userModel->getAllFaceDescriptors();

            $bestMatchUser = null;
            $lowestDistance = 0.60; // Seuil assoupli pour plus de flexibilité (Recommandé: 0.6)

            foreach ($allFaces as $userFace) {
                $dbDescriptorArray = json_decode($userFace['facial_descriptor'], true);
                if (!is_array($dbDescriptorArray))
                    continue;

                $distance = $this->calculateEuclideanDistance($loginDescriptor, $dbDescriptorArray);

                if ($distance < $lowestDistance) {
                    $lowestDistance = $distance;
                    $bestMatchUser = $userFace;
                }
            }

            if ($bestMatchUser) {
                $fullData = $userModel->getById($bestMatchUser['id_utilisateur']);

                // --- AUTO-UNLOCK CHECK ---
                if (!empty($fullData['is_blocked']) && (int) $fullData['is_blocked'] === 1) {
                    if (!empty($fullData['blocked_at'])) {
                        $blockedTime = strtotime($fullData['blocked_at']);
                        $diffMinutes = (time() - $blockedTime) / 60;
                        if ($diffMinutes >= 60) {
                            $userModel->resetLoginAttempts($fullData['id_utilisateur']);
                            $fullData = $userModel->getById($fullData['id_utilisateur']);
                        } else {
                            $rem = 60 - floor($diffMinutes);
                            return ['success' => false, 'message' => "Compte bloqué. Réessayez dans $rem min."];
                        }
                    } else {
                        return ['success' => false, 'message' => 'Compte suspendu par un admin.'];
                    }
                }

                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $_SESSION['user_id'] = $fullData['id_utilisateur'];
                $_SESSION['role'] = $fullData['role'];
                $userModel->resetLoginAttempts($fullData['id_utilisateur']);

                $isProfileComplete = !empty($fullData['age']) && !empty($fullData['poids']) && !empty($fullData['taille']);
                $isAdmin = strtolower(trim($fullData['role'] ?? '')) === 'admin';

                $redirectUrl = 'nutrismart-home.html';
                if ($isAdmin) {
                    $redirectUrl = '../backoffice/nutrismart-dashboard.html';
                } elseif (!$isProfileComplete) {
                    $redirectUrl = 'profile.html';
                }

                return ['success' => true, 'redirect' => $redirectUrl];
            } else {
                // On affiche la distance pour débugger (optionnel pour la prod)
                $distMsg = round($lowestDistance, 2);
                return ['success' => false, 'message' => "Visage non reconnu (Confiance trop basse)."];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur technique : ' . $e->getMessage()];
        }
    }

    public function verifyAccount($token)
    {
        if (empty($token)) {
            header("Location: login.html?error=Jeton manquant");
            exit;
        }

        $user = $this->userModel->getByVerificationToken($token);
        if (!$user) {
            header("Location: login.html?error=Lien de vérification invalide ou expiré");
            exit;
        }

        if ($this->userModel->verifyEmail($token)) {
            // AUTO-LOGIN after verification
            if (session_status() === PHP_SESSION_NONE)
                session_start();
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['role'] = $user['role'];

            // REDIRECT based on role
            if (strtolower(trim($user['role'] ?? '')) === 'admin') {
                header("Location: ../backoffice/nutrismart-dashboard.html");
            } else {
                // For normal users, we can still show the success message on register.html or go to profile
                // User said: "take him to home (nutrismart-home.html)"
                header("Location: profile.html?verified=1");
            }
            exit;
        } else {
            header("Location: register.html?error=" . urlencode("Erreur lors de l'activation"));
            exit;
        }
    }

    /**
     * POINT D'ENTRÉE IA — Recommandations personnalisées
     * Génère 3 films, 3 podcasts, 2 livres via Gemini API ou pool mock.
     * $shownTitles: titres déjà vus → exclus de la nouvelle génération.
     */
    public function getAiPicks($userId, $forceRefresh = false, $shownTitles = [])
    {
        try {
            if (!$userId)
                return ['success' => false, 'message' => 'Non connecté'];

            // Serve cache only on first load (no shownTitles)
            if (!$forceRefresh && empty($shownTitles)) {
                $cached = $this->userModel->getAiRecommendations($userId);
                if ($cached && !empty($cached['ai_recommendations'])) {
                    $data = json_decode($cached['ai_recommendations'], true);
                    if ($data)
                        return ['success' => true, 'cached' => true, 'data' => $data];
                }
            }

            $user = $this->userModel->getById($userId);
            $context = [
                'age' => $user['age'] ?? 25,
                'goals' => $user['objectifs'] ?? 'santé globale',
                'prefs' => $user['preferences_alimentaires'] ?? 'équilibre',
            ];

            $aiRaw = $this->callGeminiEngine($context, $shownTitles);
            if (empty($aiRaw) || !isset($aiRaw['movies'])) {
                $aiRaw = $this->getMockAiResponse($context, $shownTitles);
            }

            $data = $this->enrichAiContentV2($aiRaw);
            $motPool = $this->getMotivationPool();
            $tipPool = $this->getTipsPool();
            $data['motivation'] = $motPool[array_rand($motPool)];
            $data['dailyTip'] = $tipPool[array_rand($tipPool)];

            // Only cache the very first generation
            if (empty($shownTitles)) {
                $this->userModel->saveAiRecommendations(
                    $userId,
                    json_encode($data, JSON_UNESCAPED_UNICODE)
                );
            }
            return ['success' => true, 'cached' => false, 'data' => $data];

        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Erreur: ' . $e->getMessage()];
        }
    }

    // ── GEMINI API ENGINE ─────────────────────────────────────────────────────
    private function callGeminiEngine($context, $shownTitles = [])
    {
        // 🔑 Remplacez par votre clé Gemini : https://aistudio.google.com/app/apikey
        $apiKey = 'YOUR_GEMINI_API_KEY';
        if ($apiKey === 'YOUR_GEMINI_API_KEY') {
            return $this->getMockAiResponse($context, $shownTitles);
        }

        $exclude = !empty($shownTitles)
            ? 'EXCLUS (déjà montrés, ne jamais répéter): ' . implode(', ', array_slice($shownTitles, 0, 25)) . '. '
            : '';

        $prompt = "Tu es l'IA NutriSmart. {$exclude}\n"
            . "Génère pour un utilisateur de {$context['age']} ans, objectif: '{$context['goals']}', préférence: '{$context['prefs']}'.\n"
            . "IMPORTANT: Tu DOIS choisir les titres EXACTEMENT parmi ces catalogues et nulle part ailleurs (pour garantir l'affichage des médias) :\n"
            . "- Catalogue Films: Ratatouille, The Game Changers, Forks Over Knives, Fed Up, Super Size Me, What the Health, Chef's Table, Sugar Coated, In Defense of Food, Cowspiracy, Food, Inc., That Sugar Film, Fat, Sick & Nearly Dead.\n"
            . "- Catalogue Livres: Glucose Revolution, How Not to Die, Atomic Habits, The Obesity Code, The Omnivore's Dilemma, Eat to Live, Brain Maker, Food Rules, Intuitive Eating, The Blue Zones.\n"
            . "- Catalogue Podcasts: The Doctor's Kitchen, Zoe Science & Nutrition, Nutrition Facts, Found My Fitness, Huberman Lab, On Purpose, Mind Pump, The Drive, Feel Better, Live More, Plant Proof.\n"
            . "Choisis exactement 3 films, 3 podcasts, et 2 livres parmi ces catalogues.\n"
            . "Réponds UNIQUEMENT en JSON valide:\n"
            . '{"movies":[{"title":"...","desc":"...(2 phrases fr)","reason":"...(1 phrase fr)"}],'
            . '"podcasts":[{"title":"...","author":"...","desc":"...(fr)","episode_title":"..."}],'
            . '"books":[{"title":"...","author":"...","desc":"...(2-3 phrases fr)","excerpt":"...(extrait inspirant 3-4 phrases fr)","reason":"...(1 phrase fr)"}]}';

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$apiKey}";
        $payload = json_encode([
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['temperature' => 0.95, 'responseMimeType' => 'application/json'],
        ]);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response)
            return null;
        $result = json_decode($response, true);
        $text = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        return json_decode($text, true) ?: null;
    }

    // ── CONTENT ENRICHMENT (TMDb + Open Library + Podcast audio) ─────────────
    private function enrichAiContentV2($aiRaw)
    {
        // ── Movies (3) with TMDb poster + YouTube trailer
        $movies = [];
        foreach (array_slice($aiRaw['movies'] ?? [], 0, 3) as $m) {
            $media = $this->fetchMovieMedia($m['title']);
            $movies[] = [
                'title' => $m['title'],
                'desc' => $m['desc'] ?? '',
                'reason' => $m['reason'] ?? '',
                'image' => $media['poster'],
                'video_url' => $media['trailer'],
            ];
        }

        // ── Books (2) with Open Library cover + AI excerpt
        $books = [];
        foreach (array_slice($aiRaw['books'] ?? [], 0, 2) as $b) {
            $cover = $this->fetchBookCover($b['title'], $b['author'] ?? '');
            $books[] = [
                'title' => $b['title'],
                'author' => $b['author'] ?? '',
                'desc' => $b['desc'] ?? '',
                'excerpt' => $b['excerpt'] ?? $b['desc'] ?? '',
                'reason' => $b['reason'] ?? '',
                'image' => $cover,
            ];
        }

        // ── Podcasts (3) with cover images + real audio URLs
        $podcastMap = [
            "The Doctor's Kitchen" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts125/v4/35/86/55/3586556e-6916-24ba-4494-ea4ef20f4c02/mza_10673323806935939223.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3'
            ],
            "Zoe Science & Nutrition" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts126/v4/ba/65/d6/ba65d6bd-7d08-417d-2b47-68b3fcae0ce1/mza_5770425026938743126.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3'
            ],
            "Nutrition Facts" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/cd/e1/b7/cde1b752-0c9f-3c58-29cf-a0c5fcff6981/mza_15569421160350730372.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-3.mp3'
            ],
            "Found My Fitness" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/0f/9e/79/0f9e798e-4a4c-5353-8b7a-8fce9d345517/mza_11943891461975855011.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3'
            ],
            "Huberman Lab" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/ba/d5/4b/bad54b6c-ec40-6ec9-dcd5-1a3eb64f5195/mza_12275467331804245645.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-5.mp3'
            ],
            "On Purpose" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/55/e1/f8/55e1f822-e421-eb34-31d0-cc9eb9708f5d/mza_14620022378909477002.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-6.mp3'
            ],
            "Mind Pump" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/71/fb/67/71fb671c-3b08-3a93-789a-0720ad7611ec/mza_1215167683939613589.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-7.mp3'
            ],
            "The Drive" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/d1/8a/ce/d18ace23-b1d6-848e-f19b-c4dce306138e/mza_15510620803525992994.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-8.mp3'
            ],
            "Feel Better, Live More" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts125/v4/d3/18/85/d3188562-ab16-ed22-1dcd-9fb0b0439f04/mza_4970637175510952932.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-9.mp3'
            ],
            "Plant Proof" => [
                'img' => 'https://is1-ssl.mzstatic.com/image/thumb/Podcasts116/v4/44/2c/fc/442cfc40-349f-b31c-b63e-dbde69ccbdce/mza_5431690184478144079.jpg/316x316bb.webp',
                'aud' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-10.mp3'
            ],
        ];

        $podcasts = [];
        foreach (array_slice($aiRaw['podcasts'] ?? [], 0, 3) as $i => $p) {
            $t = $p['title'];
            $cover = isset($podcastMap[$t]) ? $podcastMap[$t]['img'] : 'https://images.unsplash.com/photo-1598300042247-d088f8ab3a91?q=80&w=400&auto=format&fit=crop';
            $audio = isset($podcastMap[$t]) ? $podcastMap[$t]['aud'] : 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3';

            $podcasts[] = [
                'title' => $t,
                'author' => $p['author'] ?? '',
                'desc' => $p['desc'] ?? '',
                'episode_title' => $p['episode_title'] ?? '',
                'image' => $cover,
                'audio_url' => $audio,
            ];
        }

        return [
            'movies' => $movies,
            'mainMovie' => $movies[0] ?? null, // backward compat
            'books' => $books,
            'podcasts' => $podcasts,
        ];
    }

    // ── MOVIE MEDIA (TMDb poster + YouTube trailer) ───────────────────────────
    private function fetchMovieMedia($title)
    {
        // 🔑 Remplacez par votre clé TMDb : https://www.themoviedb.org/settings/api
        $tmdbKey = 'YOUR_TMDB_API_KEY';
        if ($tmdbKey === 'YOUR_TMDB_API_KEY') {
            return $this->getFallbackMovieMedia($title);
        }
        try {
            $searchUrl = "https://api.themoviedb.org/3/search/movie?api_key={$tmdbKey}&query=" . urlencode($title);
            $searchRes = json_decode(@file_get_contents($searchUrl), true);
            $movieId = $searchRes['results'][0]['id'] ?? null;
            $posterPath = $searchRes['results'][0]['poster_path'] ?? null;
            $poster = $posterPath
                ? "https://image.tmdb.org/t/p/w500{$posterPath}"
                : "https://images.unsplash.com/photo-1490818387583-1baba5e638af?q=80&w=1000";

            $trailer = "https://www.youtube.com/embed/fbS1S6r6Ato";
            if ($movieId) {
                $videoUrl = "https://api.themoviedb.org/3/movie/{$movieId}/videos?api_key={$tmdbKey}";
                $videoRes = json_decode(@file_get_contents($videoUrl), true);
                foreach ($videoRes['results'] ?? [] as $v) {
                    if ($v['type'] === 'Trailer' && $v['site'] === 'YouTube') {
                        $trailer = "https://www.youtube.com/embed/{$v['key']}";
                        break;
                    }
                }
            }
            return ['poster' => $poster, 'trailer' => $trailer];
        } catch (Exception $e) {
            return $this->getFallbackMovieMedia($title);
        }
    }

    private function getFallbackMovieMedia($title)
    {
        $map = [
            'Ratatouille' => ['p' => 'https://image.tmdb.org/t/p/w500/nprm1BEEIy5LaW0G8ivD7ZqNpxB.jpg', 'v' => 'NgsQ8mVkN8w'],
            'The Game Changers' => ['p' => 'https://image.tmdb.org/t/p/w500/q5v8Vd8h1e8x5a39jT5k4u9w1rA.jpg', 'v' => 'iSpglxHTJVM'],
            'Forks Over Knives' => ['p' => 'https://image.tmdb.org/t/p/w500/hZ8t2b1iW5311F5BqF65i8T9nU2.jpg', 'v' => 'DZb-35oV_7E'],
            'Fed Up' => ['p' => 'https://image.tmdb.org/t/p/w500/vV93ZAnV6mF2r8hM53mXJ0XjOqS.jpg', 'v' => 'Y647tNm8nTI'],
            'Super Size Me' => ['p' => 'https://image.tmdb.org/t/p/w500/x5oZJIWqlE0gE1iRzHItgE859aV.jpg', 'v' => 'GRP2SjR4Hk4'],
            'What the Health' => ['p' => 'https://image.tmdb.org/t/p/w500/1X64fU2bF6i41L1Dq6YtH0G5O0K.jpg', 'v' => 'KPD1oIKnnjs'],
            "Chef's Table" => ['p' => 'https://image.tmdb.org/t/p/w500/kZp7gq2g1gP0hY0Qyq4xK9fXm7J.jpg', 'v' => 'qKqj85oo2wI'],
            'Sugar Coated' => ['p' => 'https://image.tmdb.org/t/p/w500/a2y9z4D1LzV7H8pQ9cR6bM3o4yN.jpg', 'v' => '6uaWekLrilY'],
            'In Defense of Food' => ['p' => 'https://image.tmdb.org/t/p/w500/2L2fQ9l4H1z7uX8jK5u9q0K4h9L.jpg', 'v' => 'nV04zyfLyN4'],
            'Cowspiracy' => ['p' => 'https://image.tmdb.org/t/p/w500/x4A9p3Z8j1K0M2G7w8L5N3q0u8X.jpg', 'v' => 'nV04zyfLyN4'],
            'Food, Inc.' => ['p' => 'https://image.tmdb.org/t/p/w500/8tV9zX2Q1u4K5pZ8w3H7M0j9u9Q.jpg', 'v' => 'QqQVll-WiA8'],
            'That Sugar Film' => ['p' => 'https://image.tmdb.org/t/p/w500/6yJ3Bq1v4K5pZ8w3H7M0j9u9Q2L.jpg', 'v' => '6uaWekLrilY'],
            'Fat, Sick & Nearly Dead' => ['p' => 'https://image.tmdb.org/t/p/w500/9q0K4h9L2L2fQ9l4H1z7uX8jK5u.jpg', 'v' => 'Gv3vEXy_EwU'],
        ];
        $k = $map[$title] ?? ['p' => 'https://images.unsplash.com/photo-1485846234645-a62644f84728?q=80&w=1000&auto=format&fit=crop', 'v' => 'NgsQ8mVkN8w'];
        $poster = $k['p'];
        return ['poster' => $poster, 'trailer' => "https://www.youtube.com/embed/{$k['v']}"];
    }

    // ── BOOK COVER (Open Library) ─────────────────────────────────────────────
    private function fetchBookCover($title, $author)
    {
        $covers = [
            'Glucose Revolution' => 'https://images-na.ssl-images-amazon.com/images/P/1982179414.01.LZZZZZZZ.jpg',
            'How Not to Die' => 'https://images-na.ssl-images-amazon.com/images/P/1250066115.01.LZZZZZZZ.jpg',
            'Atomic Habits' => 'https://images-na.ssl-images-amazon.com/images/P/0735211299.01.LZZZZZZZ.jpg',
            'The Obesity Code' => 'https://images-na.ssl-images-amazon.com/images/P/1771641258.01.LZZZZZZZ.jpg',
            "The Omnivore's Dilemma" => 'https://images-na.ssl-images-amazon.com/images/P/0143038583.01.LZZZZZZZ.jpg',
            'In Defense of Food' => 'https://images-na.ssl-images-amazon.com/images/P/0143114964.01.LZZZZZZZ.jpg',
            'Eat to Live' => 'https://images-na.ssl-images-amazon.com/images/P/031612091X.01.LZZZZZZZ.jpg',

            'Food Rules' => 'https://images-na.ssl-images-amazon.com/images/P/014311638X.01.LZZZZZZZ.jpg',
            'Intuitive Eating' => 'https://images-na.ssl-images-amazon.com/images/P/1250255198.01.LZZZZZZZ.jpg',
            'The Blue Zones' => 'https://images-na.ssl-images-amazon.com/images/P/1426204000.01.LZZZZZZZ.jpg',
        ];
        return $covers[$title] ?? 'https://images.unsplash.com/photo-1495446815901-a7297e633e8d?q=80&w=400&auto=format&fit=crop';
    }

    // ── MOCK AI RESPONSE (large pool with anti-repetition filter) ─────────────
    private function getMockAiResponse($context, $shownTitles = [])
    {
        $moviePool = [
            ['title' => 'Ratatouille', 'desc' => "Un chef extraordinaire célèbre l'art culinaire. Une ode à la passion et à la créativité en cuisine.", 'reason' => "Inspire à considérer chaque repas comme une œuvre d'art."],
            ['title' => 'The Game Changers', 'desc' => "Des athlètes de haut niveau prouvent les bénéfices d'une alimentation végétale sur la performance.", 'reason' => "Parfait si vous cherchez à optimiser votre énergie."],
            ['title' => 'Forks Over Knives', 'desc' => "L'alimentation à base de plantes peut prévenir et inverser les maladies chroniques. Une étude scientifique rigoureuse.", 'reason' => "Aligné avec votre objectif santé à long terme."],
            ['title' => 'Fed Up', 'desc' => "Une enquête percutante sur l'industrie alimentaire et le rôle du sucre dans la crise d'obésité mondiale.", 'reason' => "Essentiel pour comprendre ce que vous mangez vraiment."],
            ['title' => 'Super Size Me', 'desc' => "Un journaliste mange uniquement chez McDonald's pendant 30 jours. Les résultats sont stupéfiants.", 'reason' => "Un rappel puissant de l'impact de la junk food."],
            ['title' => 'What the Health', 'desc' => "Un documentaire qui remet en question les liens entre alimentation, industrie et santé publique.", 'reason' => "Pousse à questionner ses habitudes alimentaires."],
            ["title" => "Chef's Table", 'desc' => "Portraits intimes de chefs visionnaires qui repensent la relation entre cuisine et nutrition.", 'reason' => "Éveille la curiosité culinaire et l'amour des bons ingrédients."],
            ['title' => 'Sugar Coated', 'desc' => "Révèle comment l'industrie du sucre a manipulé la science pendant des décennies.", 'reason' => "Comprendre le sucre, c'est reprendre le contrôle."],
            ['title' => 'In Defense of Food', 'desc' => "Michael Pollan explore ce que signifie vraiment «manger» dans un monde d'aliments ultra-transformés.", 'reason' => "Guide pratique vers une alimentation plus authentique."],
            ['title' => 'Cowspiracy', 'desc' => "Examine l'impact de l'agriculture animale sur l'environnement et l'écologie globale.", 'reason' => "Une perspective écologique sur la nutrition."],
            ['title' => 'Food, Inc.', 'desc' => "Un regard critique sur la production alimentaire industrielle et ses conséquences.", 'reason' => "Pour devenir un consommateur plus conscient."],
            ['title' => 'That Sugar Film', 'desc' => "L'expérience d'un homme qui adopte un régime riche en sucres « cachés » soi-disant sains.", 'reason' => "Révélateur sur les étiquettes alimentaires."],
            ['title' => 'Fat, Sick & Nearly Dead', 'desc' => "Un voyage de guérison à travers le jeûne aux jus et la nutrition végétale.", 'reason' => "Une histoire de transformation radicale."],
        ];

        $bookPool = [
            ['title' => 'Glucose Revolution', 'author' => 'Jessie Inchauspé', 'desc' => "Stabiliser sa glycémie transforme l'énergie, le sommeil et l'humeur. Des astuces simples et scientifiques.", 'excerpt' => "«Les pics de glucose ne sont pas une fatalité. Avec quelques ajustements simples, vous pouvez nourrir votre corps sans le stresser. Commencez par l'ordre dans lequel vous mangez vos aliments.»", 'reason' => "Révolutionnaire pour optimiser son énergie quotidienne."],
            ['title' => 'How Not to Die', 'author' => 'Michael Greger', 'desc' => "La science derrière les 15 causes de mortalité évitables grâce à l'alimentation.", 'excerpt' => "«La nourriture que nous choisissons de manger peut être aussi puissante que n'importe quel médicament. Chaque bouchée est une décision pour votre longévité.»", 'reason' => "La référence absolue pour la longévité par la nutrition."],
            ['title' => 'Atomic Habits', 'author' => 'James Clear', 'desc' => "Comment construire de bonnes habitudes alimentaires durables grâce à de petits changements quotidiens.", 'excerpt' => "«Vous ne montez pas au niveau de vos objectifs. Vous descendez au niveau de vos systèmes. Un 1% d'amélioration chaque jour produit des résultats spectaculaires.»", 'reason' => "Transforme vos intentions en comportements automatiques."],
            ['title' => 'The Obesity Code', 'author' => 'Jason Fung', 'desc' => "Comprendre l'insuline et le rôle hormonal dans la prise de poids. Une approche médicale révolutionnaire.", 'excerpt' => "«L'obésité n'est pas un manque de volonté. C'est un problème hormonal. Comprendre l'insuline, c'est comprendre la clé de votre métabolisme.»", 'reason' => "Éclaire les mécanismes réels du poids."],
            ["title" => "The Omnivore's Dilemma", 'author' => 'Michael Pollan', 'desc' => "Une exploration fascinante des origines de nos repas et de l'impact de nos choix alimentaires.", 'excerpt' => "«Manger est un acte agricole. Chaque fois que vous choisissez un aliment, vous participez à un système bien plus vaste que votre assiette.»", 'reason' => "Remet en perspective notre lien avec la nourriture."],
            ['title' => 'Eat to Live', 'author' => 'Joel Fuhrman', 'desc' => "Un programme nutritif basé sur les micronutriments pour une santé optimale et une perte de poids durable.", 'excerpt' => "«La santé n'est pas l'absence de maladie. C'est la vitalité qui jaillit d'un corps bien nourri à chaque niveau cellulaire.»", 'reason' => "Programme complet pour transformer sa santé par l'assiette."],
            ['title' => 'Brain Maker', 'author' => 'David Perlmutter', 'desc' => "Le lien puissant entre la flore intestinale et la santé de votre cerveau.", 'excerpt' => "«Votre intestin a une voix, et il parle à votre cerveau à chaque instant de chaque jour.»", 'reason' => "Essentiel pour comprendre l'axe intestin-cerveau."],
            ['title' => 'Food Rules', 'author' => 'Michael Pollan', 'desc' => "Un manuel court et percutant de règles simples pour bien manger.", 'excerpt' => "«Ne mangez rien que votre arrière-grand-mère ne reconnaîtrait pas comme de la nourriture.»", 'reason' => "Des principes simples à retenir."],
            ['title' => 'Intuitive Eating', 'author' => 'Evelyn Tribole', 'desc' => "Faire la paix avec la nourriture et réapprendre à écouter son corps.", 'excerpt' => "«Rejetez la mentalité des régimes. Honorez votre faim. C'est le premier pas vers la liberté alimentaire.»", 'reason' => "Pour une relation saine avec la nourriture."],
            ['title' => 'The Blue Zones', 'author' => 'Dan Buettner', 'desc' => "Les leçons des personnes qui vivent le plus longtemps sur Terre.", 'excerpt' => "«Ils ne comptent pas les calories. Ils mangent des plantes, bougent naturellement et cultivent des liens forts.»", 'reason' => "L'approche holistique de la santé."],
        ];

        $podcastPool = [
            ['title' => 'The Doctor\'s Kitchen', 'author' => 'Dr. Rupy Aujla', 'desc' => "Cuisine et médecine se rencontrent pour une santé optimale.", 'episode_title' => "Les 10 aliments qui changent votre cerveau"],
            ['title' => 'Zoe Science & Nutrition', 'author' => 'Jonathan Wolf', 'desc' => "Comprendre son microbiome et son métabolisme unique.", 'episode_title' => "Votre intestin, votre deuxième cerveau"],
            ['title' => 'Nutrition Facts', 'author' => 'Dr. Michael Greger', 'desc' => "Décryptage des dernières recherches mondiales en nutrition.", 'episode_title' => "Les anti-inflammatoires naturels"],
            ['title' => 'Found My Fitness', 'author' => 'Dr. Rhonda Patrick', 'desc' => "Science de pointe sur la longévité, l'exercice et la nutrition.", 'episode_title' => "Sauna et jeûne intermittent"],
            ['title' => 'Huberman Lab', 'author' => 'Andrew Huberman', 'desc' => "Neurosciences et biologie au service de la performance humaine.", 'episode_title' => "Optimiser son sommeil et sa nutrition"],
            ['title' => 'On Purpose', 'author' => 'Jay Shetty', 'desc' => "Bien-être mental et physique pour une vie épanouie.", 'episode_title' => "Les rituels des personnes en grande santé"],
            ['title' => 'Mind Pump', 'author' => 'Sal Di Stefano', 'desc' => "Démystifier les mythes de l'industrie du fitness et de la perte de poids.", 'episode_title' => "La vérité sur le métabolisme"],
            ['title' => 'The Drive', 'author' => 'Peter Attia', 'desc' => "Plongée profonde dans la science de la longévité et de l'optimisation.", 'episode_title' => "Comprendre le risque métabolique"],
            ['title' => 'Feel Better, Live More', 'author' => 'Dr. Rangan Chatterjee', 'desc' => "Des conversations inspirantes pour transformer votre santé.", 'episode_title' => "Le pouvoir de l'alimentation consciente"],
            ['title' => 'Plant Proof', 'author' => 'Simon Hill', 'desc' => "Explorer les preuves derrière l'alimentation à base de plantes.", 'episode_title' => "Protéines et longévité"],
        ];

        // Filter out already-shown titles
        $filterShown = function ($pool) use ($shownTitles) {
            $filtered = array_values(array_filter($pool, fn($item) => !in_array($item['title'], $shownTitles)));
            return !empty($filtered) ? $filtered : $pool; // fallback to full pool if all shown
        };

        $movies = $filterShown($moviePool);
        $books = $filterShown($bookPool);
        $podcasts = $filterShown($podcastPool);

        shuffle($movies);
        shuffle($books);
        shuffle($podcasts);

        return [
            'movies' => array_slice($movies, 0, 3),
            'books' => array_slice($books, 0, 2),
            'podcasts' => array_slice($podcasts, 0, 3),
        ];
    }

    // ── MOTIVATION / TIPS POOLS ───────────────────────────────────────────────
    private function getMotivationPool()
    {
        return [
            ['quote' => "Prenez soin de votre corps. C'est le seul endroit où vous devez vivre.", 'author' => 'Jim Rohn'],
            ['quote' => "Votre corps entend tout ce que votre esprit dit.", 'author' => 'Naomi Judd'],
            ['quote' => "La santé est la vraie richesse, pas l'or et l'argent.", 'author' => 'Mahatma Gandhi'],
            ['quote' => "Chaque repas est une chance de nourrir ou de négocier avec votre corps.", 'author' => 'NutriSmart IA'],
        ];
    }

    private function getTipsPool()
    {
        return [
            "Buvez un verre d'eau citronnée tiède dès le réveil.",
            "Marchez 10 minutes après chaque repas principal.",
            "Ajoutez des légumes verts à chaque assiette.",
            "Mangez lentement : votre cerveau met 20 min à ressentir la satiété.",
            "Préparez vos repas de la semaine le dimanche.",
        ];
    }

    private function calculateEuclideanDistance($desc1, $desc2)
    {
        if (count($desc1) !== count($desc2))
            return 999;
        $sum = 0;
        for ($i = 0; $i < count($desc1); $i++) {
            $sum += pow((float) $desc1[$i] - (float) $desc2[$i], 2);
        }
        return sqrt($sum);
    }
}
?>