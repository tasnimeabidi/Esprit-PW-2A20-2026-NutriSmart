<?php
/**
 * ROUTEUR API CENTRALISÉ (GESTION UTILISATEUR CLASSIQUE)
 * Ce fichier gère exclusivement l'authentification standard (email/mot de passe),
 * la gestion du profil, et la déconnexion.
 */

error_reporting(E_ALL);
ini_set('display_errors', 0); // Désactive l'affichage direct des erreurs pour garder le JSON propre

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../controllers/UserController.php';

$action = $_GET['action'] ?? '';
$controller = new UserController();

// Fonction utilitaire pour renvoyer une réponse JSON propre au navigateur
function respondJson($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

switch ($action) {
    
    // ==========================================
    // 1. CONNEXION CLASSIQUE (LOGIN)
    // ==========================================
    case 'login':
        // Appelle le contrôleur pour vérifier les identifiants email/mot de passe.
        // Gère également la vérification des comptes suspendus ou trop de tentatives échouées.
        $result = $controller->login();
        respondJson($result ?: ['success' => false, 'message' => 'Erreur inconnue lors de la connexion.']);
        break;

    // ==========================================
    // 2. INSCRIPTION CLASSIQUE (REGISTER)
    // ==========================================
    case 'register':
        // Appelle le contrôleur pour créer un nouvel utilisateur dans la base de données.
        // Le mot de passe sera haché de manière sécurisée (Bcrypt).
        $result = $controller->register();
        respondJson($result ?: ['success' => false, 'message' => 'Erreur inconnue lors de l\'inscription.']);
        break;

    // ==========================================
    // 2.b. VÉRIFICATION D'EMAIL (ACTIVATION)
    // ==========================================
    case 'verify':
        // Active le compte via le lien reçu par email
        $token = $_GET['token'] ?? '';
        $controller->verifyAccount($token);
        break;

    // ==========================================
    // 3. MISE À JOUR DU PROFIL
    // ==========================================
    case 'update_profile':
        // Met à jour les mensurations (âge, poids, taille) de l'utilisateur
        // connecté dans la table profil_nutritionnel.
        $result = $controller->updateProfile();
        respondJson($result ?: ['success' => false, 'message' => 'Erreur inconnue lors de la mise à jour du profil.']);
        break;

    // ==========================================
    // 4. DEMANDE DE RÉINITIALISATION DE MOT DE PASSE
    // ==========================================
    case 'request_reset':
        // Reçoit l'email, génère un token sécurisé, l'enregistre en base
        // et envoie théoriquement un lien par email à l'utilisateur.
        $email = $_POST['email'] ?? '';
        if (empty($email)) {
            respondJson(['success' => false, 'message' => 'L\'email est requis.']);
        }
        $result = $controller->requestReset($email);
        respondJson($result ?: ['success' => false, 'message' => 'Erreur serveur.']);
        break;

    // ==========================================
    // 5. VALIDATION DU NOUVEAU MOT DE PASSE
    // ==========================================
    case 'update_password':
        // Vérifie si le token (lien cliqué) est valide et n'a pas expiré,
        // puis remplace l'ancien mot de passe par le nouveau.
        $token = $_POST['token'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($token) || empty($new_password)) {
            respondJson(['success' => false, 'message' => 'Données incomplètes.']);
        }
        if ($new_password !== $confirm_password) {
            respondJson(['success' => false, 'message' => 'Les mots de passe ne correspondent pas.']);
        }

        $result = $controller->resetPassword($token, $new_password);
        respondJson($result ?: ['success' => false, 'message' => 'Erreur de réinitialisation.']);
        break;

    // ==========================================
    // 5.b. RECONNAISSANCE FACIALE (FACE ID)
    // ==========================================
    case 'save_face':
        // Enregistre l'empreinte mathématique du visage de l'utilisateur connecté
        $result = $controller->saveFace();
        respondJson($result ?: ['success' => false, 'message' => 'Erreur serveur IA.']);
        break;

    case 'delete_face':
        // Supprime l'empreinte faciale
        $result = $controller->deleteFace();
        respondJson($result ?: ['success' => false, 'message' => 'Erreur suppression.']);
        break;

    case 'face_login':
        // Tente de connecter quelqu'un en scannant son visage et en comparant
        $result = $controller->loginWithFace();
        respondJson($result ?: ['success' => false, 'message' => 'Échec de la reconnaissance.']);
        break;

    // ==========================================
    // 5.c. MODULE IA : RECOMMANDATIONS (SMART PICKS)
    // ==========================================
    case 'get_ai_picks':
        if (!isset($_SESSION['user_id'])) {
            respondJson(['success' => false, 'message' => 'Non connecté']);
        }
        $forceRefresh  = ($_POST['forceRefresh']  ?? 'false') === 'true';
        $shownTitles   = json_decode($_POST['shownTitles'] ?? '[]', true) ?: [];
        $result = $controller->getAiPicks($_SESSION['user_id'], $forceRefresh, $shownTitles);
        respondJson($result ?: ['success' => false, 'message' => 'Erreur IA.']);
        break;

    // ==========================================
    // 6. VÉRIFICATION DE LA SESSION ACTIVE
    // ==========================================
    case 'session':
        // Utilisé par le JavaScript (sur toutes les pages) en arrière-plan pour savoir 
        // si un utilisateur est connecté, et adapter le menu de navigation (Nom, Email, Rôle).
        require_once __DIR__ . '/../../Models/User.php';
        $userModel = new User();
        $response = ['loggedIn' => false];

        if (isset($_SESSION['user_id'])) {
            $user = $userModel->getById($_SESSION['user_id']);
            
            // SECURITY FILTER: If admin, we don't show account in front-office (User requirement)
            $isAdmin = $user && isset($user['role']) && (strtolower(trim($user['role'])) === 'admin');

            // Si l'utilisateur est connecté, N'EST PAS admin, et N'EST PAS bloqué
            if ($user && !$isAdmin && (!isset($user['is_blocked']) || (int)$user['is_blocked'] !== 1)) {
                $response = [
                    'loggedIn' => true,
                    'name' => trim($user['nom']),
                    'email' => trim($user['email']),
                    'role' => trim($user['role']),
                    'genre' => trim($user['genre'] ?? ''),
                    'objectifs' => trim($user['objectifs'] ?? ''),
                    'hasFaceId' => !empty($user['facial_descriptor'])
                ];
            } elseif ($isAdmin) {
                // Admin is present but we hide it from front-office
                $response = ['loggedIn' => false, 'admin_detected' => true];
            } else {
                // S'il a été bloqué entre temps, on détruit la session
                if ($user && isset($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                    session_unset();
                    session_destroy();
                }
            }
        }
        respondJson($response);
        break;

    // ==========================================
    // 7. DÉCONNEXION (LOGOUT)
    // ==========================================
    case 'logout':
        // Détruit toutes les données de session du serveur et renvoie à l'accueil
        session_unset();
        session_destroy();
        
        // Comportement adaptatif selon la provenance (AJAX ou clic sur un lien normal)
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            respondJson(['success' => true, 'redirect' => 'login.html']);
        }
        // Redirection classique de retour à l'accueil visiteur
        header("Location: nutrismart-website.html");
        exit;

    // ==========================================
    // 8. FORMULAIRE DE CONTACT
    // ==========================================
    case 'contact':
        $nom = $_POST['nom'] ?? '';
        $email = $_POST['email'] ?? '';
        $sujet = $_POST['sujet'] ?? '';
        $message = $_POST['message'] ?? '';

        if (empty($nom) || empty($email) || empty($message)) {
            respondJson(['success' => false, 'message' => 'Veuillez remplir tous les champs obligatoires.']);
        }
        
        // Ici, on pourrait enregistrer en base ou envoyer un mail.
        // Pour la démo, on simule un succès.
        respondJson(['success' => true, 'message' => 'Votre message a été envoyé avec succès ! Notre équipe vous répondra sous 24h.']);
        break;

    // ==========================================
    // PAR DÉFAUT (ERREUR)
    // ==========================================
    default:
        respondJson(['success' => false, 'message' => 'Action API non reconnue.']);
        break;
}
?>
