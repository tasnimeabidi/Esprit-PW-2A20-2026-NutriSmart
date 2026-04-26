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
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Timeout    = 7;
        $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
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
                    $mail->Body    = "
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
                if (isset($user['is_verified']) && (int)$user['is_verified'] === 0) {
                    return ['success' => false, 'message' => 'Votre compte n\'est pas encore activé. Veuillez vérifier vos emails.'];
                }

                // Check if blocked
                if (isset($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
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
                    
                    $attempts = (int)$user['login_attempts'] + 1;
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
                return !is_null($v) && $v !== ''; });

            if ($this->userModel->updateProfile($userId, $data)) {
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

    public function requestReset($email) {
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
                $mail = $this->getMailer();
                $mail->addAddress($email);

                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Réinitialisation de votre mot de passe - NutriSmart';
                $mail->Body    = "
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
                return ['success' => true, 'message' => 'Un lien de réinitialisation a été envoyé à votre adresse email.'];
            } catch (Exception $e) {
                return ['success' => false, 'message' => "L'e-mail n'a pas pu être envoyé. Erreur: {$mail->ErrorInfo}"];
            }
        }
        return ['success' => false, 'message' => 'Erreur serveur lors de la génération du jeton.'];
    }

    public function resetPassword($token, $newPassword) {
        $user = $this->userModel->getUserByToken($token);
        if (!$user) {
            return ['success' => false, 'message' => 'Jeton invalide ou expiré.'];
        }

        if ($this->userModel->updatePassword($user['id_utilisateur'], $newPassword)) {
            return ['success' => true, 'message' => 'Mot de passe mis à jour avec succès.'];
        }
        return ['success' => false, 'message' => 'Erreur lors de la mise à jour du mot de passe.'];
    }

    public function toggleUserBlock($id) {
        $user = $this->userModel->getById($id);
        if (!$user) return ['success' => false, 'message' => 'Utilisateur non trouvé.'];
        
        // Sécurité : On ne peut pas bloquer un administrateur
        if (isset($user['role']) && strtolower(trim($user['role'])) === 'admin') {
            return ['success' => false, 'message' => 'Impossible de bloquer un administrateur système.'];
        }
        
        $newStatus = ((int)($user['is_blocked'] ?? 0) === 1) ? 0 : 1;
        if ($this->userModel->toggleBlock($id, $newStatus)) {
            $msg = $newStatus === 1 ? 'Utilisateur bloqué.' : 'Utilisateur débloqué.';
            return ['success' => true, 'message' => $msg];
        }
        return ['success' => false, 'message' => 'Erreur lors du changement de statut.'];
    }

    // ==========================================
    // MODULE IA : RECONNAISSANCE FACIALE
    // ==========================================

    public function saveFace() {
        if (session_status() === PHP_SESSION_NONE) session_start();
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
            if ((int)$userFace['id_utilisateur'] === (int)$_SESSION['user_id']) continue;

            $existingDescriptor = json_decode($userFace['facial_descriptor'], true);
            if (!is_array($existingDescriptor)) continue;

            $distance = $this->calculateEuclideanDistance($newDescriptor, $existingDescriptor);
            
            // Si la distance est < 0.50, c'est considéré comme la même personne (Sécurité Renforcée)
            if ($distance < 0.50) {
                return ['success' => false, 'message' => 'Erreur : Ce visage appartient déjà à un autre utilisateur.'];
            }
        }

        $ok = $userModel->saveFacialDescriptor($_SESSION['user_id'], $descriptorJson);
        return ['success' => $ok, 'message' => $ok ? 'Visage enregistré avec succès !' : 'Erreur base de données.'];
    }

    public function deleteFace() {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (!isset($_SESSION['user_id'])) {
            return ['success' => false, 'message' => 'Non authentifié.'];
        }

        $userModel = new User();
        $ok = $userModel->saveFacialDescriptor($_SESSION['user_id'], null);
        return ['success' => $ok, 'message' => $ok ? 'Face ID supprimé avec succès.' : 'Erreur base de données.'];
    }

    public function loginWithFace() {
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
                if (!is_array($dbDescriptorArray)) continue;

                $distance = $this->calculateEuclideanDistance($loginDescriptor, $dbDescriptorArray);

                if ($distance < $lowestDistance) {
                    $lowestDistance = $distance;
                    $bestMatchUser = $userFace;
                }
            }

            if ($bestMatchUser) {
                $fullData = $userModel->getById($bestMatchUser['id_utilisateur']);
                
                // --- AUTO-UNLOCK CHECK ---
                if (!empty($fullData['is_blocked']) && (int)$fullData['is_blocked'] === 1) {
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

                if (session_status() === PHP_SESSION_NONE) session_start();
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

    public function verifyAccount($token) {
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
            if (session_status() === PHP_SESSION_NONE) session_start();
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

    private function calculateEuclideanDistance($desc1, $desc2) {
        if (count($desc1) !== count($desc2)) return 999;
        $sum = 0;
        for ($i = 0; $i < count($desc1); $i++) {
            $sum += pow((float)$desc1[$i] - (float)$desc2[$i], 2);
        }
        return sqrt($sum);
    }
}
?>