<?php
// =========================================================================
// ⚠️ CONFIGURATION POUR LE SOCIAL LOGIN (OAUTH2)
// Vous DEVEZ remplacer ces valeurs par vos vraies clés secrètes obtenues 
// depuis Google Cloud Console et Facebook for Developers.
// =========================================================================

define('OAUTH_CONFIG', [
    'google' => [
        'client_id'     => '783998000082-6njhv9j0m41nheki10e7293n0rovm7l2.apps.googleusercontent.com',
        'client_secret' => 'VOTRE_SECRET_GOOGLE_ICI_POUR_GITHUB',
        
        // URL d'autorisation
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        // URL pour obtenir le Token
        'token_url'     => 'https://oauth2.googleapis.com/token',
        // URL pour obtenir les informations de l\'utilisateur
        'api_url'       => 'https://www.googleapis.com/oauth2/v3/userinfo',
        // Scopes demandés
        'scope'         => 'email profile'
    ],
    
    'facebook' => [
        'client_id'     => '1296056348517578',
        'client_secret' => '8336b4a87a0c73dbd71eb1bb44e819fa',
        
        // URL d'autorisation (v19.0 ou la version la plus récente de l\'API FB)
        'auth_url'      => 'https://www.facebook.com/v19.0/dialog/oauth',
        // URL pour obtenir le Token
        'token_url'     => 'https://graph.facebook.com/v19.0/oauth/access_token',
        // URL pour obtenir les informations de l\'utilisateur
        'api_url'       => 'https://graph.facebook.com/v19.0/me?fields=id,name,email,picture',
        // Scopes demandés
        'scope'         => 'email public_profile'
    ]
]);

// URL of the callback handler. This MUST exactly match the Authorized Redirect URI
// configured in your Google/Facebook Developer dashboards.
function getOAuthRedirectUri($provider) {
    // Determine base URL (http://localhost or https://votre-domaine.com)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    
    // Le chemin vers le fichier API dans votre application frontoffice
    $basePath = "/NutriSmart/Views/frontoffice/auth_api.php";
    
    return $protocol . "://" . $host . $basePath . "?action=oauth_callback&provider=" . urlencode($provider);
}
?>
