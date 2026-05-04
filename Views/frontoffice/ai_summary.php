<?php

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        "error" => "Invalid request"
    ]);
    exit;
}

$contenu = $_POST['contenu'] ?? '';

if (empty($contenu)) {
    echo json_encode([
        "error" => "Empty content"
    ]);
    exit;
}

/* =========================
   GEMINI API KEY
========================= */

$apiKey = "AIzaSyB5cZdqa6DM3FHkh18BJuZ3AgboyYVAOAA";

/* =========================
   PROMPT
========================= */

$prompt = "Résume ce post de blog nutrition en 2 phrases simples et claires : \n\n" . $contenu;

/* =========================
   API URL
========================= */

$url =
"https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key="
. $apiKey;

/* =========================
   REQUEST BODY
========================= */

$data = [
    "contents" => [
        [
            "parts" => [
                [
                    "text" => $prompt
                ]
            ]
        ]
    ]
];

/* =========================
   CURL
========================= */

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

curl_setopt($ch, CURLOPT_POST, true);

curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {

    echo json_encode([
        "error" => curl_error($ch)
    ]);

    curl_close($ch);
    exit;
}

curl_close($ch);

/* =========================
   RESPONSE
========================= */

$result = json_decode($response, true);

$summary =
$result['candidates'][0]['content']['parts'][0]['text']
?? "Erreur IA.";

/* =========================
   RETURN JSON
========================= */

echo json_encode([
    "summary" => $summary
]);