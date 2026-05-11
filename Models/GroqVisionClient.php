<?php
/**
 * Vision repas via Groq (API OpenAI-compatible).
 * @see https://console.groq.com/docs/vision
 */
declare(strict_types=1);

final class GroqVisionClient
{
    private const API_URL = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * @return array{ocrTexteExemple: string, caloriesEstimees: int, equivalentSport: string, ingredients: list<array{nom: string, quantiteGrammes: int, kcalPour100g: int}>, nonAlimentaire?: bool, message?: string}
     * @throws RuntimeException
     */
    public static function analyserPhotoRepas(string $apiKey, string $model, string $mediaType, string $base64Data): array
    {
        $prompt = <<<'TXT'
Tu es nutritionniste pour l'application NutriSmart. Tu analyses une photo de repas ou de nourriture.

Réponds UNIQUEMENT par un objet JSON valide UTF-8, sans markdown, sans texte avant ou après, avec exactement ces clés :
{
  "repasOuNourritureVisible": true ou false,
  "ocrTexteExemple": "texte en français qui décrit le repas et cite explicitement chaque aliment/boisson listé dans ingredients (portions estimées), ou une courte phrase si ce n'est pas de la nourriture",
  "caloriesEstimees": nombre entier (estimation raisonnable pour tout ce qui est visible),
  "equivalentSport": "phrase courte en français, ex. environ X min de marche rapide",
  "ingredients": [
    { "nom": "nom court de l'aliment en français", "quantiteGrammes": entier estimé, "kcalPour100g": entier (valeur nutritionnelle typique pour cet aliment) }
  ]
}

Règle "repasOuNourritureVisible" :
- false si la photo ne montre PAS de repas ni d’aliments identifiables (objet, écran, document, visage, paysage, main seule, flou sans plat, etc.).
- Si false : "ingredients" doit être [], "caloriesEstimees" doit être 0, "equivalentSport" doit être "—", "ocrTexteExemple" doit expliquer brièvement en français que ce n’est pas un repas/aliment (ex. « Image sans nourriture identifiable »).

Règles strictes pour "ingredients" :
- Liste EXHAUSTIVE : tout aliment ou boisson clairement visible (plat, accompagnements, garnitures, verre dans l’image, etc.).
- N’invente JAMAIS un aliment absent de la photo.
- Ne confonds pas omelette / œufs avec du poulet.
- Un type distinct = une ligne ; regroupe seulement des unités identiques.
Si l'image n'est pas un aliment : repasOuNourritureVisible = false, ingredients = [], caloriesEstimees = 0.
TXT;

        $dataUrl = 'data:' . $mediaType . ';base64,' . $base64Data;

        $payload = [
            'model' => $model,
            'max_tokens' => 4096,
            'temperature' => 0.3,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => $dataUrl],
                        ],
                    ],
                ],
            ],
        ];

        $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extension cURL requise pour appeler l’API Groq.');
        }

        $ch = curl_init(self::API_URL);
        if ($ch === false) {
            throw new RuntimeException('curl_init a échoué.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => 120,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL : ' . $errstr . ' (' . $errno . ')');
        }

        $decoded = json_decode($raw, true);
        if ($http < 200 || $http >= 300) {
            $msg = is_array($decoded) && isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : 'HTTP ' . $http;
            if ($http === 401 || $http === 403) {
                throw new RuntimeException('GROQ_AUTH_FAILED:' . $msg);
            }
            throw new RuntimeException('Groq : ' . $msg);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Réponse Groq non JSON.');
        }

        $text = self::extraireContenuAssistant($decoded);
        $parsed = self::parserJsonNutrition($text);

        if ($parsed === null) {
            throw new RuntimeException('Groq n’a pas renvoyé un JSON exploitable. Extrait : ' . mb_substr($text, 0, 200));
        }

        $parsed['message'] = 'Analyse générée via l’API Groq. Estimation indicative, pas un avis médical.';

        return $parsed;
    }

    /**
     * Chat texte (sans image) — réponses directes aux questions utilisateur.
     *
     * @throws RuntimeException
     */
    public static function repondreQuestionAssistant(string $apiKey, string $model, string $questionUtilisateur): string
    {
        $questionUtilisateur = trim($questionUtilisateur);
        if ($questionUtilisateur === '') {
            throw new InvalidArgumentException('Question vide.');
        }

        $system = <<<'TXT'
Tu es l’assistant NutriSmart sur la page « recettes » d’une application web nutrition.

Règles :
- Réponds en français, de manière directe et utile : réponds vraiment à la question posée (ingrédients, étapes simples, quantités indicatives, conseils pratiques).
- Si on demande les ingrédients d’un plat (ex. omelette, salade), liste les ingrédients usuels et éventuellement des variantes courtes.
- Reste concis : environ 5 à 12 phrases maximum, pas de blabla sur « comment utiliser le site » sauf si l’utilisateur demande explicitement comment naviguer sur le site.
- Si la question touche à une pathologie ou un régime strict, termine par une phrase rappelant de consulter un professionnel de santé.
- Pas de markdown lourd : puces simples ou phrases numérotées si besoin.
TXT;

        $payload = [
            'model' => $model,
            'max_tokens' => 600,
            'temperature' => 0.45,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $questionUtilisateur],
            ],
        ];

        $jsonBody = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);

        if (!function_exists('curl_init')) {
            throw new RuntimeException('Extension cURL requise pour appeler l’API Groq.');
        }

        $ch = curl_init(self::API_URL);
        if ($ch === false) {
            throw new RuntimeException('curl_init a échoué.');
        }

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_POSTFIELDS => $jsonBody,
            CURLOPT_TIMEOUT => 60,
        ]);

        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($raw === false) {
            throw new RuntimeException('cURL : ' . $errstr . ' (' . $errno . ')');
        }

        $decoded = json_decode($raw, true);
        if ($http < 200 || $http >= 300) {
            $msg = is_array($decoded) && isset($decoded['error']['message'])
                ? (string) $decoded['error']['message']
                : 'HTTP ' . $http;
            throw new RuntimeException('Groq : ' . $msg);
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('Réponse Groq non JSON.');
        }

        $text = self::extraireContenuAssistant($decoded);
        if (trim($text) === '') {
            throw new RuntimeException('Réponse Groq vide.');
        }

        return trim($text);
    }

    /** @param array<string, mixed> $decoded */
    private static function extraireContenuAssistant(array $decoded): string
    {
        $choices = $decoded['choices'] ?? null;
        if (!is_array($choices) || $choices === []) {
            return '';
        }
        $first = $choices[0];
        if (!is_array($first)) {
            return '';
        }
        $msg = $first['message'] ?? null;
        if (!is_array($msg)) {
            return '';
        }

        return trim((string) ($msg['content'] ?? ''));
    }

    /**
     * @return array{ocrTexteExemple: string, caloriesEstimees: int, equivalentSport: string, ingredients: list<array{nom: string, quantiteGrammes: int, kcalPour100g: int}>, nonAlimentaire?: bool, message?: string}|null
     */
    private static function parserJsonNutrition(string $text): ?array
    {
        $clean = trim($text);
        if ($clean === '') {
            return null;
        }
        if (preg_match('/```(?:json)?\s*([\s\S]*?)\s*```/u', $clean, $m)) {
            $clean = trim($m[1]);
        }
        $start = strpos($clean, '{');
        $end = strrpos($clean, '}');
        if ($start === false || $end === false || $end < $start) {
            return null;
        }
        $slice = substr($clean, $start, $end - $start + 1);
        $data = json_decode($slice, true);
        if (!is_array($data)) {
            return null;
        }

        $ocr = isset($data['ocrTexteExemple']) ? trim((string) $data['ocrTexteExemple']) : '';
        $cal = isset($data['caloriesEstimees']) ? (int) $data['caloriesEstimees'] : 0;
        $sport = isset($data['equivalentSport']) ? trim((string) $data['equivalentSport']) : '';
        $ingredients = self::normaliserListeIngredients($data['ingredients'] ?? null);

        $repasVisible = null;
        if (array_key_exists('repasOuNourritureVisible', $data)) {
            $repasVisible = (bool) $data['repasOuNourritureVisible'];
        } elseif (array_key_exists('estNourriture', $data)) {
            $repasVisible = (bool) $data['estNourriture'];
        }

        $nonAlimentaire = false;
        if ($repasVisible === false) {
            $nonAlimentaire = true;
        } elseif ($repasVisible === null && $ingredients === [] && $cal === 0
            && $ocr !== '' && self::texteIndiqueNonAlimentaire($ocr)) {
            $nonAlimentaire = true;
        }

        if ($nonAlimentaire) {
            $ingredients = [];
            $cal = 0;
            $sport = $sport !== '' && $sport !== '—' ? $sport : '—';
            if ($ocr === '') {
                $ocr = 'Aucun repas ou aliment identifiable sur cette image.';
            }
        }

        if ($ocr === '' && $sport === '' && $ingredients === []) {
            return null;
        }

        $payload = [
            'ocrTexteExemple' => $ocr !== '' ? $ocr : ($ingredients !== [] ? 'Repas analysé (liste d’ingrédients).' : 'Analyse fournie par Groq.'),
            'caloriesEstimees' => max(0, $cal),
            'equivalentSport' => $sport !== '' ? $sport : 'Équivalent sport non précisé.',
            'ingredients' => $ingredients,
            'nonAlimentaire' => $nonAlimentaire,
        ];
        if ($repasVisible !== null) {
            $payload['repasOuNourritureVisible'] = $repasVisible;
        }

        return $payload;
    }

    private static function texteIndiqueNonAlimentaire(string $ocr): bool
    {
        return (bool) preg_match(
            '/non\s*alimentaire|sans\s+nourriture|sans\s+repas|pas\s+(?:de\s+)?(?:repas|nourriture)|aucun\s+(?:repas|aliment)|'
            . 'image\s+sans\s+nourriture|pas\s+un\s+repas|objet\s+non\s+comestible|ne\s+montre\s+pas\s+(?:de\s+)?(?:repas|nourriture)/iu',
            $ocr
        );
    }

    /**
     * @param mixed $raw
     * @return list<array{nom: string, quantiteGrammes: int, kcalPour100g: int}>
     */
    private static function normaliserListeIngredients($raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) {
                continue;
            }
            $nom = trim((string) ($row['nom'] ?? $row['name'] ?? ''));
            if ($nom === '') {
                continue;
            }
            $q = isset($row['quantiteGrammes']) ? (int) $row['quantiteGrammes'] : (int) ($row['quantite'] ?? $row['quantity'] ?? 100);
            $k = isset($row['kcalPour100g']) ? (int) $row['kcalPour100g'] : (int) ($row['kcalPer100g'] ?? $row['kcal_pour_100g'] ?? 0);
            $out[] = [
                'nom' => $nom,
                'quantiteGrammes' => max(1, $q),
                'kcalPour100g' => max(1, $k > 0 ? $k : 150),
            ];
            if (count($out) >= 40) {
                break;
            }
        }

        return $out;
    }
}
