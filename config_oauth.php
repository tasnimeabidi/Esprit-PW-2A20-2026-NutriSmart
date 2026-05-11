<?php
// =========================================================================
// ⚠️ CONFIGURATION POUR LE SOCIAL LOGIN (OAUTH2)
// Vous DEVEZ remplacer ces valeurs par vos vraies clés secrètes obtenues 
// depuis Google Cloud Console et Facebook for Developers.
// =========================================================================

define('OAUTH_CONFIG', [
    'google' => [
        'client_id'     => 'REMPLACEZ_PAR_VOTRE_GOOGLE_CLIENT_ID',
        'client_secret' => 'REMPLACEZ_PAR_VOTRE_GOOGLE_CLIENT_SECRET',
        
        // URL d'autorisation
        'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
        // URL pour obtenir le Token
        'token_url'     => 'https://oauth2.googleapis.com/token',
        // URL pour obtenir les informations de l\'utilisateur
        'api_url'       => 'https://www.googleapis.com/oauth2/v3/userinfo',
        // openid : recommandé par Google Sign-In (évite certaines erreurs côté token)
        'scope'         => 'openid email profile'
    ],
    
    'facebook' => [
        'client_id'     => 'REMPLACEZ_PAR_VOTRE_FACEBOOK_CLIENT_ID',
        'client_secret' => 'REMPLACEZ_PAR_VOTRE_FACEBOOK_CLIENT_SECRET',
        
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

// Facultatif : chaîne complète si besoin ; sinon URI dérivée automatiquement (oauth-callback.php).
if (!defined('OAUTH_REDIRECT_URI_FIXED')) {
    define('OAUTH_REDIRECT_URI_FIXED', '');
}

// Google : chaîne UNIQUE envoyée comme redirect_uri (Erreur 400 si elle n’est pas identique dans Google Cloud).
// Modifiez seulement si votre dossier ou port diffère ; sinon ajoutez cette ligne dans Identifiants > Client OAuth Web.
if (!defined('OAUTH_GOOGLE_REDIRECT_URI_FIXED')) {
    define(
        'OAUTH_GOOGLE_REDIRECT_URI_FIXED',
        'http://localhost/Esprit-PW-2A20-2026-NutriSmart-planRepas/oauth-callback.php'
    );
}

/**
 * Secret pour signer le paramètre OAuth « state » (fonctionne même si Chrome ≠ navigateur Cursor : pas de session PHP partagée).
 */
function oauth_state_secret_key() {
    return hash(
        'sha256',
        (OAUTH_CONFIG['google']['client_secret'] ?? '') . '|' . (OAUTH_CONFIG['facebook']['client_secret'] ?? '') . '|NutriSmart-oauth-state-v1'
    );
}

function oauth_build_signed_state($intent, $provider) {
    $intent = in_array($intent, ['login', 'register'], true) ? $intent : 'login';
    if (!in_array($provider, ['google', 'facebook'], true)) {
        $provider = 'google';
    }
    $payload = [
        'i' => $intent,
        'p' => $provider,
        'n' => bin2hex(random_bytes(8)),
        't' => time(),
    ];
    $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $b64 = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    $sig = hash_hmac('sha256', $b64, oauth_state_secret_key());
    return $b64 . '.' . $sig;
}

/**
 * @return array{intent:string,provider:string}|null
 */
function oauth_parse_signed_state($state) {
    if ($state === '' || strpos($state, '.') === false) {
        return null;
    }
    $parts = explode('.', $state, 2);
    if (count($parts) !== 2) {
        return null;
    }
    list($b64, $sig) = $parts;
    $expected = hash_hmac('sha256', $b64, oauth_state_secret_key());
    if (!hash_equals($expected, $sig)) {
        return null;
    }
    $pad = strlen($b64) % 4;
    if ($pad) {
        $b64 .= str_repeat('=', 4 - $pad);
    }
    $json = base64_decode(strtr($b64, '-_', '+/'));
    if ($json === false) {
        return null;
    }
    $data = json_decode($json, true);
    if (!is_array($data)) {
        return null;
    }
    if (time() - (int) ($data['t'] ?? 0) > 900) {
        return null;
    }
    $p = $data['p'] ?? '';
    if (!in_array($p, ['google', 'facebook'], true)) {
        return null;
    }
    $i = $data['i'] ?? 'login';
    if (!in_array($i, ['login', 'register'], true)) {
        $i = 'login';
    }
    return ['intent' => $i, 'provider' => $p];
}

/**
 * Chemin URL public de oauth-callback.php depuis DOCUMENT_ROOT (fiable sous XAMPP / Windows).
 */
function oauth_callback_public_path() {
    if (empty($_SERVER['DOCUMENT_ROOT'])) {
        return '';
    }
    $docRoot = realpath($_SERVER['DOCUMENT_ROOT']);
    $callbackFile = realpath(__DIR__ . DIRECTORY_SEPARATOR . 'oauth-callback.php');
    if (!$docRoot || !$callbackFile) {
        return '';
    }
    $docRoot = str_replace('\\', '/', $docRoot);
    $callbackFile = str_replace('\\', '/', $callbackFile);
    if (strpos($callbackFile, $docRoot) !== 0) {
        return '';
    }
    $rel = substr($callbackFile, strlen($docRoot));
    return '/' . ltrim(str_replace('\\', '/', $rel), '/');
}

/**
 * Fallback si le chemin fichier ne résout pas (CLI, alias inhabituel).
 */
function oauth_project_base_web_path() {
    $sn = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (preg_match('#^(.+)/Views/frontoffice/auth_api\.php$#', $sn, $m)) {
        return $m[1];
    }
    if (preg_match('#^(.+)/oauth-callback\.php$#', $sn, $m)) {
        return $m[1];
    }
    return '';
}

/**
 * URL du fichier oauth-callback.php (même hôte que la page pour la session PHP).
 */
function oauth_build_callback_url() {
    if (defined('OAUTH_REDIRECT_URI_FIXED') && OAUTH_REDIRECT_URI_FIXED !== '') {
        return OAUTH_REDIRECT_URI_FIXED;
    }

    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (string) $_SERVER['SERVER_PORT'] === '443');
    $protocol = $https ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    $path = oauth_callback_public_path();
    if ($path === '') {
        $base = oauth_project_base_web_path();
        if ($base === '') {
            $base = '/Esprit-PW-2A20-2026-NutriSmart-planRepas';
        }
        $path = rtrim($base, '/') . '/oauth-callback.php';
    }

    return $protocol . '://' . $host . $path;
}

function getOAuthRedirectUri() {
    return oauth_build_callback_url();
}

/**
 * Google : par défaut même URI que Facebook. Définir OAUTH_GOOGLE_REDIRECT_URI_FIXED uniquement si la console Google impose une URI précise (attention : doit être le même hôte que la session).
 */
function getGoogleOAuthRedirectUri() {
    if (defined('OAUTH_GOOGLE_REDIRECT_URI_FIXED') && OAUTH_GOOGLE_REDIRECT_URI_FIXED !== '') {
        return OAUTH_GOOGLE_REDIRECT_URI_FIXED;
    }
    return oauth_build_callback_url();
}
?>
