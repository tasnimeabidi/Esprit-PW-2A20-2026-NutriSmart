<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../Models/User.php';

class AuthController {

    private $user;

    public function __construct($db) {
        $this->user = new User($db);
    }

    public function login() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            $data = $this->user->login($email);

            if ($data && password_verify($password, $data['mot_de_passe'])) {

                session_regenerate_id(true);

                // 🔥 SAME SESSION KEY (CRITICAL FIX)
                $_SESSION['id_utilisateur'] = $data['id_utilisateur'];
                $_SESSION['nom'] = $data['nom'];
                $_SESSION['role'] = $data['role'];

                if ($data['role'] === 'admin') {
                    header("Location: index.php?action=admin_dashboard");
                    exit;
                }

                header("Location: index.php?action=blog");
                exit;

            } else {
                $error = "Login incorrect";
            }
        }

        require __DIR__ . "/../Views/frontoffice/login.php";
    }

    public function register() {

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {

            $nom = trim($_POST['fullname'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $passwordRaw = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirmPassword'] ?? '';

            if ($nom && $email && $passwordRaw === $confirmPassword) {

                $password = password_hash($passwordRaw, PASSWORD_DEFAULT);

                $this->user->register($nom, $email, $password);

                header("Location: index.php?action=login");
                exit;
            }
        }

        require __DIR__ . "/../Views/frontoffice/register.php";
    }
}