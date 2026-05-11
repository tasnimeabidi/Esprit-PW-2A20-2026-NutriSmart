<?php
/**
 * Clé et modèle Groq (API compatible OpenAI — vision).
 *
 * 1) GROQ_API_KEY (env / $_SERVER / $_ENV)
 * 2) config/groq.local.php (copiez groq.local.example.php)
 *
 * Modèle optionnel : GROQ_MODEL ou clé « model » dans groq.local.php
 *
 * @see https://console.groq.com/docs/vision
 */
declare(strict_types=1);

final class GroqConfig
{
    /** Modèle multimodal documenté Groq (vision + JSON). */
    private const DEFAULT_MODEL = 'meta-llama/llama-4-scout-17b-16e-instruct';

    private static function lireVariableEnvironnement(string $nom): string
    {
        $v = getenv($nom);
        if (is_string($v) && trim($v) !== '') {
            return trim($v);
        }
        if (isset($_SERVER[$nom]) && is_string($_SERVER[$nom]) && trim($_SERVER[$nom]) !== '') {
            return trim($_SERVER[$nom]);
        }
        if (isset($_ENV[$nom]) && is_string($_ENV[$nom]) && trim($_ENV[$nom]) !== '') {
            return trim($_ENV[$nom]);
        }

        return '';
    }

    /** @return array{apiKey: string, model: string} */
    public static function load(): array
    {
        $envKey = self::lireVariableEnvironnement('GROQ_API_KEY');
        $envModel = self::lireVariableEnvironnement('GROQ_MODEL');
        $localKey = '';
        $localModel = '';

        $localPath = NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'groq.local.php';
        if (is_file($localPath)) {
            /** @var mixed $local */
            $local = require $localPath;
            if (is_array($local)) {
                if (isset($local['apiKey'])) {
                    $localKey = trim((string) $local['apiKey']);
                } elseif (isset($local['api_key'])) {
                    $localKey = trim((string) $local['api_key']);
                }
                if (isset($local['model'])) {
                    $localModel = trim((string) $local['model']);
                }
            }
        }

        $key = self::pickApiKey($envKey, $localKey);
        $model = $envModel !== '' ? $envModel : $localModel;
        if ($model === '') {
            $model = self::DEFAULT_MODEL;
        }

        return ['apiKey' => $key, 'model' => $model];
    }

    public static function isPlausibleApiKey(string $key): bool
    {
        $key = trim($key);
        if ($key === '' || strlen($key) < 20) {
            return false;
        }
        if (!str_starts_with($key, 'gsk_')) {
            return false;
        }
        $lower = strtolower($key);
        if (
            str_contains($lower, 'remplac')
            || str_contains($lower, 'replace')
            || str_contains($lower, 'your_key')
            || str_contains($lower, 'example')
            || str_contains($lower, 'xxxx')
        ) {
            return false;
        }

        return true;
    }

    public static function pickApiKey(string $fromEnv, string $fromLocal): string
    {
        if (self::isPlausibleApiKey($fromEnv)) {
            return trim($fromEnv);
        }
        if (self::isPlausibleApiKey($fromLocal)) {
            return trim($fromLocal);
        }

        return '';
    }

    /** @return array<string, bool|string> */
    public static function diagnosticsScan(): array
    {
        $localPath = NUTRISMART_BASE . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'groq.local.php';
        $fichierExiste = is_file($localPath);
        $localKeyRaw = '';
        $fichierChargeErreur = false;
        if ($fichierExiste) {
            try {
                /** @var mixed $local */
                $local = require $localPath;
                if (is_array($local)) {
                    if (isset($local['apiKey'])) {
                        $localKeyRaw = trim((string) $local['apiKey']);
                    } elseif (isset($local['api_key'])) {
                        $localKeyRaw = trim((string) $local['api_key']);
                    }
                }
            } catch (Throwable $e) {
                $fichierChargeErreur = true;
            }
        }
        $envRaw = self::lireVariableEnvironnement('GROQ_API_KEY');
        $cfg = self::load();

        return [
            'fichierGroqLocalPhpExiste' => $fichierExiste,
            'fichierGroqLocalPhpErreurChargement' => $fichierChargeErreur,
            'cleNonVideDansEnvironnement' => $envRaw !== '',
            'cleNonVideDansFichierLocal' => $localKeyRaw !== '',
            'cleJugeeValidePourScan' => self::isPlausibleApiKey($cfg['apiKey']),
            'cheminRelatifAttendu' => 'config/groq.local.php',
        ];
    }
}
