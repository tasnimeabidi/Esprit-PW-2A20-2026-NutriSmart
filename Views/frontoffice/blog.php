<?php
declare(strict_types=1);

/**
 * Redirige vers le blog dynamique (publications, commentaires, résumé IA) du dépôt Nassim.
 */
require_once __DIR__ . '/../../nassim_integration.php';

header('Location: ' . NassimIntegration::blogUrl(), true, 302);
exit;
