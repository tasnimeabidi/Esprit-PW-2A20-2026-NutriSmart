<?php
/**
 * À ouvrir dans Chrome : même onglet que pour votre site (localhost conseillé).
 */
require __DIR__ . '/config_oauth.php';
header('Content-Type: text/plain; charset=utf-8');

$g = defined('OAUTH_GOOGLE_REDIRECT_URI_FIXED') && OAUTH_GOOGLE_REDIRECT_URI_FIXED !== ''
    ? OAUTH_GOOGLE_REDIRECT_URI_FIXED
    : getGoogleOAuthRedirectUri();

echo "=== Erreur redirect_uri_mismatch ? ===\r\n\r\n";
echo "1) Allez sur : https://console.cloud.google.com/apis/credentials\r\n";
echo "2) Cliquez sur le client OAuth \"ID client Web\" dont l'ID commence comme dans config_oauth.php\r\n";
echo "3) Section \"URI de redirection autorisés\" > Ajouter un URI\r\n";
echo "4) Collez EXACTEMENT (sans espace en trop) :\r\n\r\n";
echo $g . "\r\n\r\n";
echo "--- Si vous utilisez surtout 127.0.0.1 dans Chrome, ajoutez AUSSI cette ligne ---\r\n\r\n";
echo "http://127.0.0.1/Esprit-PW-2A20-2026-NutriSmart-planRepas/oauth-callback.php\r\n\r\n";
echo "puis modifiez OAUTH_GOOGLE_REDIRECT_URI_FIXED dans config_oauth.php pour utiliser le même hôte.\r\n\r\n";
echo "Facebook (redirect dynamique) :\r\n" . getOAuthRedirectUri() . "\r\n";
