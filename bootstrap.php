<?php
/**
 * Point d'amorçage NutriSmart — autoload simple (MVC sans Composer).
 */
declare(strict_types=1);

define('NUTRISMART_BASE', __DIR__);

// Polyfills PHP < 8 (XAMPP avec PHP 7.x)
if (!function_exists('str_contains')) {
    function str_contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }
}
if (!function_exists('str_starts_with')) {
    function str_starts_with(string $haystack, string $needle): bool
    {
        return $needle === '' || strncmp($haystack, $needle, strlen($needle)) === 0;
    }
}
if (!function_exists('str_ends_with')) {
    function str_ends_with(string $haystack, string $needle): bool
    {
        $n = strlen($needle);

        return $n === 0 || substr($haystack, -$n) === $needle;
    }
}

/**
 * Charge un fichier .env type KEY=value (compatible clés API longues, contrairement à parse_ini_file).
 *
 * @return array<string, string>
 */
function nutrismart_load_env(string $path): array
{
    $out = [];
    if (!is_readable($path)) {
        return $out;
    }
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') {
        return $out;
    }
    $raw = preg_replace('/^\xEF\xBB\xBF/', '', $raw);
    foreach (preg_split("/\r\n|\n|\r/", $raw) as $line) {
        $line = trim($line);
        if ($line === '' || (isset($line[0]) && $line[0] === '#')) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k === '') {
            continue;
        }
        if ($v !== '' && strlen($v) >= 2) {
            $first = $v[0];
            $last = $v[strlen($v) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $v = substr($v, 1, -1);
            }
        }
        $out[$k] = $v;
    }

    return $out;
}

spl_autoload_register(function (string $class): void {
    $base = NUTRISMART_BASE . DIRECTORY_SEPARATOR;
    $paths = [
        $base . 'Models' . DIRECTORY_SEPARATOR . $class . '.php',
        $base . 'Model' . DIRECTORY_SEPARATOR . $class . '.php',
        $base . 'Services' . DIRECTORY_SEPARATOR . $class . '.php',
        $base . 'controllers' . DIRECTORY_SEPARATOR . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (is_file($path)) {
            require_once $path;
            return;
        }
    }
});

require_once NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
require_once NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'config.php';

$nutriEnvFile = NUTRISMART_BASE . DIRECTORY_SEPARATOR . '.env';
$nutriEnv = is_file($nutriEnvFile) ? nutrismart_load_env($nutriEnvFile) : [];
$apiKey = $nutriEnv['SENDINBLUE_API_KEY'] ?? $nutriEnv['BREVO_API_KEY'] ?? '';
$sender = $nutriEnv['SENDER_EMAIL'] ?? '';
$publicBase = isset($nutriEnv['NUTRISMART_PUBLIC_BASE']) ? rtrim((string) $nutriEnv['NUTRISMART_PUBLIC_BASE'], '/') : '';
if ($apiKey === '') {
    $apiKey = (string) getenv('SENDINBLUE_API_KEY');
}
if ($sender === '') {
    $sender = (string) getenv('SENDER_EMAIL');
}
if (!defined('SENDINBLUE_API_KEY')) {
    define('SENDINBLUE_API_KEY', $apiKey);
}
if (!defined('SENDER_EMAIL')) {
    define('SENDER_EMAIL', $sender);
}
if (!defined('NUTRISMART_PUBLIC_BASE')) {
    define('NUTRISMART_PUBLIC_BASE', $publicBase);
}

$ollamaBase = trim((string) ($nutriEnv['OLLAMA_BASE_URL'] ?? getenv('OLLAMA_BASE_URL') ?: ''));
if (!defined('OLLAMA_BASE_URL')) {
    define('OLLAMA_BASE_URL', $ollamaBase !== '' ? rtrim($ollamaBase, '/') : 'http://127.0.0.1:11434');
}
if (!defined('OLLAMA_CHAT_MODEL')) {
    define(
        'OLLAMA_CHAT_MODEL',
        trim((string) ($nutriEnv['OLLAMA_CHAT_MODEL'] ?? getenv('OLLAMA_CHAT_MODEL') ?: 'tinyllama'))
    );
}
if (!defined('OLLAMA_CHAT_NUM_GPU')) {
    $chatGpuEnv = trim((string) ($nutriEnv['OLLAMA_CHAT_NUM_GPU'] ?? getenv('OLLAMA_CHAT_NUM_GPU') ?: ''));
    define('OLLAMA_CHAT_NUM_GPU', $chatGpuEnv === '' ? 0 : (int) $chatGpuEnv);
}
if (!defined('OLLAMA_VISION_MODEL')) {
    /** Défaut léger (≈1.8 Go RAM) — pour llava/moondream lourd : définir dans .env */
    define(
        'OLLAMA_VISION_MODEL',
        trim((string) ($nutriEnv['OLLAMA_VISION_MODEL'] ?? getenv('OLLAMA_VISION_MODEL') ?: 'moondream'))
    );
}
if (!defined('OLLAMA_VISION_NUM_GPU')) {
    $visionGpuEnv = trim((string) ($nutriEnv['OLLAMA_VISION_NUM_GPU'] ?? getenv('OLLAMA_VISION_NUM_GPU') ?: ''));
    define('OLLAMA_VISION_NUM_GPU', $visionGpuEnv === '' ? 0 : (int) $visionGpuEnv);
}
if (!defined('OLLAMA_VISION_NUM_CTX')) {
    $visionCtxEnv = trim((string) ($nutriEnv['OLLAMA_VISION_NUM_CTX'] ?? getenv('OLLAMA_VISION_NUM_CTX') ?: ''));
    $visionCtx = $visionCtxEnv === '' ? 512 : (int) $visionCtxEnv;
    if ($visionCtx < 256) {
        $visionCtx = 256;
    }
    if ($visionCtx > 8192) {
        $visionCtx = 8192;
    }
    define('OLLAMA_VISION_NUM_CTX', $visionCtx);
}

if (!defined('SMTP_HOST')) {
    define('SMTP_HOST', trim((string) ($nutriEnv['SMTP_HOST'] ?? getenv('SMTP_HOST') ?: '')));
    define('SMTP_USER', trim((string) ($nutriEnv['SMTP_USER'] ?? getenv('SMTP_USER') ?: '')));
    define('SMTP_PASS', trim((string) ($nutriEnv['SMTP_PASS'] ?? getenv('SMTP_PASS') ?: '')));
    define('SMTP_PORT', (int) ($nutriEnv['SMTP_PORT'] ?? (getenv('SMTP_PORT') ?: 587)));
    define('SMTP_FROM', trim((string) ($nutriEnv['SMTP_FROM'] ?? ($sender ?: getenv('SMTP_FROM') ?: ''))));
    define('SMTP_FROM_NAME', trim((string) ($nutriEnv['SMTP_FROM_NAME'] ?? 'NutriSmart')));
    $smtpEnc = strtolower(trim((string) ($nutriEnv['SMTP_ENCRYPTION'] ?? getenv('SMTP_ENCRYPTION') ?: 'tls')));
    define('SMTP_ENCRYPTION', in_array($smtpEnc, ['tls', 'ssl', 'none'], true) ? $smtpEnc : 'tls');
}
