<?php
/**
 * Point de retour OAuth (Google / Facebook). URI courte sans query string « action »,
 * pour correspondre facilement aux « URI de redirection autorisés » dans Google Cloud.
 */
$_GET['action'] = 'oauth_callback';
require __DIR__ . '/Views/frontoffice/auth_api.php';
