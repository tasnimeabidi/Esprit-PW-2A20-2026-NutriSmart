<?php
/**
 * ROUTEUR API OAUTH (SOCIAL LOGIN)
 * Ce fichier est dédié exclusivement aux processus de connexion via 
 * des plateformes tierces comme Google et Facebook.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? '';

switch ($action) {

    // ==========================================
    // 1. REDIRECTION VERS LE FOURNISSEUR (OAUTH REDIRECT)
    // ==========================================
    case 'oauth_redirect':
        // Cette étape est déclenchée quand l'utilisateur clique sur "S'inscrire avec Google/Facebook"
        // Nous générons une clé de sécurité (State) puis nous renvoyons le navigateur vers la page 
        // officielle de Google ou Facebook pour qu'ils demandent le consentement.
        
        require_once __DIR__ . '/../../config_oauth.php';
        $provider = $_GET['provider'] ?? '';
        
        if (!in_array($provider, ['google', 'facebook'])) {
            die("Plateforme non supportée ou non reconnue.");
        }
        
        $config = OAUTH_CONFIG[$provider];
        
        $intent = $_GET['intent'] ?? 'login';
        $_SESSION['oauth_intent'] = $intent;
        
        // Génération d'un jeton anti-faille CSRF (sécurité)
        $state = bin2hex(random_bytes(16));
        $_SESSION['oauth2state'] = $state;
        
        // Construction de l'URL d'autorisation
        $params = [
            'client_id'     => $config['client_id'],
            'redirect_uri'  => getOAuthRedirectUri($provider),
            'response_type' => 'code',
            'scope'         => $config['scope'],
            'state'         => $state
        ];
        
        if ($provider === 'google') {
            $params['access_type'] = 'offline';
            $params['prompt'] = 'consent';
        }
        
        // Redirection automatique vers Google/Facebook
        header('Location: ' . $config['auth_url'] . '?' . http_build_query($params));
        exit;

    // ==========================================
    // 2. RETOUR DU FOURNISSEUR (OAUTH CALLBACK)
    // ==========================================
    case 'oauth_callback':
        // Après que l'utilisateur ait autorisé l'application, Google/Facebook nous renvoie ici
        // avec un code secret. Nous échangeons ce code contre un Access Token, puis nous 
        // récupérons son adresse email. Enfin, nous le connectons/créons silencieusement sa session.
        
        require_once __DIR__ . '/../../config_oauth.php';
        require_once __DIR__ . '/../../Models/User.php';
        
        $provider = $_GET['provider'] ?? '';
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';
        $error = $_GET['error'] ?? '';

        if (!in_array($provider, ['google', 'facebook'])) die("Plateforme non supportée.");
        if (!empty($error)) die("Erreur : " . htmlspecialchars($error));
        
        // Vérification de sécurité Anti-CSRF
        if (empty($state) || $state !== ($_SESSION['oauth2state'] ?? null)) {
            unset($_SESSION['oauth2state']);
            die("État OAuth invalide. Sécurité compromise.");
        }
        unset($_SESSION['oauth2state']);
        
        if (empty($code)) die("Code d'autorisation manquant.");

        $intent = $_SESSION['oauth_intent'] ?? 'login';
        unset($_SESSION['oauth_intent']);

        $config = OAUTH_CONFIG[$provider];
        
        // Demande de l'Access Token
        $tokenParams = [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => getOAuthRedirectUri($provider),
            'code' => $code,
            'grant_type' => 'authorization_code'
        ];

        $ch = curl_init($config['token_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($tokenParams));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        $tokenResponse = curl_exec($ch);
        $tokenError = curl_error($ch);
        curl_close($ch);

        if ($tokenError) die("Erreur de requête Token : " . $tokenError);
        $tokenData = json_decode($tokenResponse, true);
        
        if (empty($tokenData['access_token'])) {
            die("Impossible d'obtenir l'Access Token. Avez-vous renseigné vos VRAIES CLÉS API dans config_oauth.php ?<br><br>Réponse : " . htmlspecialchars($tokenResponse));
        }

        // Récupération des informations de profil
        $accessToken = $tokenData['access_token'];
        $ch = curl_init($config['api_url']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $userResponse = curl_exec($ch);
        $userError = curl_error($ch);
        curl_close($ch);

        if ($userError) die("Erreur UserInfo : " . $userError);
        $userData = json_decode($userResponse, true);

        // Analyse de la réponse selon le fournisseur
        $oauth_id = ''; $name = ''; $email = '';
        if ($provider === 'google') {
            $oauth_id = $userData['sub'] ?? '';
            $name = $userData['name'] ?? 'Utilisateur Google';
            $email = $userData['email'] ?? '';
        } elseif ($provider === 'facebook') {
            $oauth_id = $userData['id'] ?? '';
            $name = $userData['name'] ?? 'Utilisateur Facebook';
            $email = $userData['email'] ?? '';
        }

        if (empty($oauth_id)) die("Données profil introuvables.");
        if (empty($email)) $email = $oauth_id . "@" . $provider . ".local"; 

        // Authentification dans le système NutriSmart
        $userModel = new User();
        $user = $userModel->findOrCreateSocialUser($name, $email, $provider, $oauth_id, $intent);

        if (isset($user['error'])) {
            // Redirect with error based on intent
            if ($intent === 'register') {
                header("Location: register.html?error=" . urlencode($user['error']));
            } else {
                header("Location: login.html?error=" . urlencode($user['error']));
            }
            exit;
        }

        if ($user) {
            // Rejet si le compte est banni
            if (isset($user['is_blocked']) && (int)$user['is_blocked'] === 1) {
                die("Votre compte a été suspendu.");
            }
            // Création de la session réussie
            $_SESSION['user_id'] = $user['id_utilisateur'];
            $_SESSION['role'] = $user['role'];
            $userModel->resetLoginAttempts($user['id_utilisateur']);
            
            // Check if profile is complete (essential fields are not empty)
            $isProfileComplete = !empty($user['age']) && !empty($user['poids']) && !empty($user['taille']);
            $isAdmin = strtolower(trim($user['role'] ?? '')) === 'admin';

            if ($isAdmin) {
                header("Location: ../backoffice/nutrismart-dashboard.html");
            } elseif (!$isProfileComplete) {
                header("Location: profile.html");
            } else {
                header("Location: nutrismart-home.html");
            }
            exit;
        } else {
            die("Erreur de sauvegarde base de données.");
        }

    default:
        die("Action OAUTH non reconnue.");
}
?>
