<?php
/**
 * Expose window.NUTRISMART_API_BASE = chemin absolu vers /api (ex. /projetNutriSmart/api).
 * À inclure en HTTP depuis les pages sous Views/ — évite les erreurs de résolution ../../api.
 */
declare(strict_types=1);

header('Content-Type: application/javascript; charset=utf-8');

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = $scriptName !== '' ? dirname($scriptName) : '';
// Ce fichier est dans .../Views/frontoffice/ → racine projet web = deux niveaux au-dessus
$projectWeb = $scriptDir !== '' ? dirname(dirname($scriptDir)) : '';
$base = ($projectWeb !== '' && $projectWeb !== '/' && $projectWeb !== '\\')
    ? rtrim(str_replace('\\', '/', $projectWeb), '/') . '/api'
    : '/api';

echo 'window.NUTRISMART_API_BASE=' . json_encode($base, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';';
