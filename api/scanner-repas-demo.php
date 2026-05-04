<?php
/** Scanner repas : GET = aide + diagnostics ; POST multipart champ « image » = Groq ou démo. */
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

const SCANNER_MIMES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $cfg = GroqConfig::load();
    $cleOk = GroqConfig::isPlausibleApiKey($cfg['apiKey']);
    JsonApi::envoyer(200, array_merge([
        'endpoint' => 'scanner-repas-demo',
        'methode' => 'POST',
        'champFichier' => 'image',
        'contentType' => 'multipart/form-data',
        'visionConfigure' => $cleOk,
        'modeAttendu' => $cleOk ? 'groq' : 'demo',
        'fournisseur' => 'groq',
        'modelVision' => $cfg['model'],
        'note' => 'Clé Groq → analyse vision ; sinon réponse démo + OCR côté navigateur.',
    ], GroqConfig::diagnosticsScan()));
    return;
}

if ($method !== 'POST') {
    JsonApi::erreur(405, 'Utilisez GET (aide) ou POST (image).');
    return;
}

$f = $_FILES['image'] ?? null;
if (!is_array($f) || (int) ($f['error'] ?? 0) !== UPLOAD_ERR_OK) {
    JsonApi::erreur(400, 'Champ « image » manquant ou upload invalide.');
    return;
}

$tmp = (string) ($f['tmp_name'] ?? '');
if ($tmp === '' || !is_uploaded_file($tmp)) {
    JsonApi::erreur(400, 'Fichier upload invalide.');
    return;
}

$mime = scanner_repas_mime($tmp, $f);
if (!in_array($mime, SCANNER_MIMES, true)) {
    JsonApi::erreur(400, 'Type non autorisé : ' . $mime);
    return;
}

$size = filesize($tmp);
if ($size === false) {
    $size = 0;
}

$cfg = GroqConfig::load();
$groq = GroqConfig::isPlausibleApiKey($cfg['apiKey']);
$max = $groq ? 4 * 1024 * 1024 : 8 * 1024 * 1024;
if ($size > $max) {
    JsonApi::erreur(400, 'Image trop volumineuse (max ' . (int) ($max / 1024 / 1024) . ' Mo).');
    return;
}

if (!$groq) {
    JsonApi::envoyer(200, scanner_repas_demo_payload($mime, $size));
    return;
}

try {
    $bin = file_get_contents($tmp);
    if ($bin === false) {
        throw new RuntimeException('Lecture du fichier impossible.');
    }
    $out = GroqVisionClient::analyserPhotoRepas(
        $cfg['apiKey'],
        $cfg['model'],
        $mime,
        base64_encode($bin)
    );
    JsonApi::envoyer(200, array_merge([
        'mode' => 'groq',
        'mime' => $mime,
        'tailleOctets' => $size,
        'model' => $cfg['model'],
    ], $out));
} catch (Throwable $e) {
    JsonApi::erreur(502, 'Groq : ' . $e->getMessage());
}

/**
 * @param array<string, mixed> $upload
 */
function scanner_repas_mime(string $tmp, array $upload): string
{
    $mime = 'application/octet-stream';
    if (class_exists('finfo')) {
        $d = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (is_string($d) && $d !== '') {
            $mime = $d;
        }
    }
    if ($mime === 'application/octet-stream') {
        $decl = (string) ($upload['type'] ?? '');
        if (in_array($decl, SCANNER_MIMES, true)) {
            $mime = $decl;
        }
    }

    return $mime;
}

/** @return array<string, mixed> */
function scanner_repas_demo_payload(string $mime, int $size): array
{
    return [
        'mode' => 'demo',
        'mime' => $mime,
        'tailleOctets' => $size,
        'ocrTexteExemple' =>
            'Pas d’analyse vision sans clé Groq. Ajoutez GROQ_API_KEY ou config/groq.local.php, ou saisissez les ingrédients à la main.',
        'caloriesEstimees' => 0,
        'equivalentSport' => '— (non calculé sans analyse de la photo)',
        'ingredients' => [],
        'message' => 'Mode démo : configurez la clé Groq pour analyser la photo.',
    ];
}
