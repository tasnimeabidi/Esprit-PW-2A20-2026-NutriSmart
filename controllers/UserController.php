<?php
require_once __DIR__ . '/../Models/User.php';

class UserController
{
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // CREATE (Register Front)
    public function register()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = $_POST['nom'] ?? '';
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $role = $_POST['role'] ?? 'utilisateur';

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

            if ($this->userModel->create($nom, $email, $password, $role)) {
                $user = $this->userModel->getByEmail($email);
                if ($user) {
                    if (session_status() === PHP_SESSION_NONE)
                        session_start();
                    $_SESSION['user_id'] = $user['id_utilisateur'];
                    $_SESSION['role'] = $user['role'];
                }

                // Role-based redirection
                if (strtolower(trim($_SESSION['role'])) === 'admin') {
                    header('Location: ../backoffice/nutrismart-dashboard.html');
                } else {
                    // Normal users must complete their profile right after registering
                    header('Location: profile.html');
                }
                exit;
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
                if (session_status() === PHP_SESSION_NONE)
                    session_start();
                $_SESSION['user_id'] = $user['id_utilisateur'];
                $_SESSION['role'] = $user['role'];

                if (strtolower(trim($_SESSION['role'])) === 'admin') {
                    header('Location: ../backoffice/nutrismart-dashboard.html');
                } else {
                    header('Location: nutrismart-home.html');
                }
                exit;
            } else {
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

            if (!$userId)
                return ['success' => false, 'message' => 'Non authentifié.'];

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
                return ['success' => true, 'message' => 'Profil mis à jour avec succès.'];
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
}
?>