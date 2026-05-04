<?php

header("Content-Type: application/json");

/* =========================
   GET INPUT
========================= */

$content = $_POST['content'] ?? '';

if (empty(trim($content))) {
    echo json_encode([
        "success" => false,
        "summary" => "Contenu vide"
    ]);
    exit;
}

/* =========================
   API KEY (PUT YOUR KEY HERE)
========================= */

$apiKey = "AIzaSyB5cZdqa6DM3FHkh18BJuZ3AgboyYVAOAA";

/* =========================
   MODEL (YOUR WORKING ONE)
========================= */

$model = "gemini-2.5-flash";

/* =========================
   URL
========================= */

$url = "https://generativelanguage.googleapis.com/v1beta/models/"
     . $model
     . ":generateContent?key=" . $apiKey;

/* =========================
   PROMPT
========================= */

$prompt = "Résume ce texte en français simplement et clairement en 2-3 phrases :\n\n" . $content;

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
   CURL REQUEST
========================= */

$ch = curl_init($url);

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        "success" => false,
        "summary" => "CURL ERROR: " . curl_error($ch)
    ]);
    exit;
}

curl_close($ch);

$result = json_decode($response, true);

/* =========================
   DEBUG IF FAILS
========================= */

if (!$result) {
    echo json_encode([
        "success" => false,
        "summary" => "INVALID RESPONSE",
        "raw" => $response
    ]);
    exit;
}

/* =========================
   CHECK GEMINI RESPONSE
========================= */

if (!isset($result['candidates'][0]['content']['parts'][0]['text'])) {

    echo json_encode([
        "success" => false,
        "summary" => "API ERROR",
        "debug" => $result
    ]);
    exit;
}

/* =========================
   SUCCESS
========================= */

echo json_encode([
    "success" => true,
    "summary" => $result['candidates'][0]['content']['parts'][0]['text']
]);