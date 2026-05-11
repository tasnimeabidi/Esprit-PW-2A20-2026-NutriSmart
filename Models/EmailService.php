<?php
/**
 * Envoi transactionnel via l’API Brevo (ex-Sendinblue), comme nutrismart-chahine.
 */
declare(strict_types=1);

class EmailService
{
    /** Notification à chaque achat (boutique / liste de courses). */
    private const ADMIN_PURCHASE_NOTIFY_EMAIL = 'youssef.mejri@esprit.tn';

    private string $apiKey;

    private string $senderEmail;

    public function __construct()
    {
        $k = defined('SENDINBLUE_API_KEY') ? (string) SENDINBLUE_API_KEY : '';
        $this->apiKey = trim($k !== '' ? $k : (string) getenv('SENDINBLUE_API_KEY'));
        $s = defined('SENDER_EMAIL') ? (string) SENDER_EMAIL : '';
        $this->senderEmail = trim($s !== '' ? $s : (string) getenv('SENDER_EMAIL'));
    }

    private function baseUrl(): string
    {
        if (defined('NUTRISMART_PUBLIC_BASE') && is_string(NUTRISMART_PUBLIC_BASE) && NUTRISMART_PUBLIC_BASE !== '') {
            return rtrim(NUTRISMART_PUBLIC_BASE, '/');
        }

        return 'http://localhost/Esprit-PW-2A20-2026-NutriSmart-planRepas';
    }

    public function sendRecipeNotification(string $to, string $recipeName, string $status): bool
    {
        if ($this->apiKey === '' || $this->senderEmail === '') {
            error_log('NutriSmart: configuration email manquante (SENDINBLUE_API_KEY / SENDER_EMAIL dans .env)');

            return false;
        }

        $subject = $status === 'approved' ?
            '🎉 Votre recette a été approuvée !' :
            '❌ Votre proposition de recette a été refusée';

        $message = $this->getEmailTemplate($recipeName, $status);

        return $this->sendEmail($to, $subject, $message);
    }

    /** E-mail d’activation de compte (inscription), même canal Brevo que Chahine. */
    public function sendAccountVerificationEmail(string $to, string $nom, string $verifyLink): bool
    {
        if ($this->apiKey === '' || $this->senderEmail === '') {
            return false;
        }

        $safeNom = htmlspecialchars($nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = 'Activez votre compte NutriSmart';
        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">'
            . '<h2 style="color: #4a7c59;">Bienvenue chez NutriSmart !</h2>'
            . '<p>Bonjour ' . $safeNom . ',</p>'
            . '<p>Merci de vous être inscrit. Pour activer votre compte, cliquez sur le bouton ci-dessous :</p>'
            . '<div style="text-align: center; margin: 30px 0;">'
            . '<a href="' . htmlspecialchars($verifyLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="background-color: #4a7c59; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Activer mon compte</a>'
            . '</div>'
            . '<p style="font-size: 12px; color: #888;">Si le bouton ne fonctionne pas :<br>' . htmlspecialchars($verifyLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
            . '<hr style="border: 0; border-top: 1px solid #eee; margin: 20px 0;">'
            . '<p style="font-size: 12px; color: #888;">Message automatique — merci de ne pas répondre.</p>'
            . '</body></html>';

        return $this->sendEmail($to, $subject, $html);
    }

    /** Lien de réinitialisation mot de passe (même canal API que l’activation compte). */
    public function sendPasswordResetEmail(string $to, string $resetLink): bool
    {
        if ($this->apiKey === '' || $this->senderEmail === '') {
            return false;
        }

        $safeLink = htmlspecialchars($resetLink, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $subject = 'Réinitialisation de votre mot de passe — NutriSmart';
        $html = '<!DOCTYPE html><html lang="fr"><head><meta charset="UTF-8"></head><body style="font-family: Arial, sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #eee; border-radius: 10px;">'
            . '<h2 style="color: #4a7c59;">NutriSmart</h2>'
            . '<p>Bonjour,</p>'
            . '<p>Vous avez demandé la réinitialisation de votre mot de passe.</p>'
            . '<div style="text-align: center; margin: 30px 0;">'
            . '<a href="' . $safeLink . '" style="background-color: #4a7c59; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Réinitialiser mon mot de passe</a>'
            . '</div>'
            . '<p style="font-size: 13px;">Ce lien expire dans 2 heures. Si vous n’êtes pas à l’origine de cette demande, ignorez ce message.</p>'
            . '<p style="font-size: 12px; color: #888;">Si le bouton ne fonctionne pas :<br>' . $safeLink . '</p>'
            . '</body></html>';

        return $this->sendEmail($to, $subject, $html);
    }

    /**
     * Confirmation utilisateur + notification admin (youssef.mejri@esprit.tn) à chaque achat.
     */
    public function sendPurchaseConfirmationEmail(string $userEmail, string $userName, string $alimentName, $qty, $totalPrice): bool
    {
        if ($this->apiKey === '' || $this->senderEmail === '') {
            error_log('NutriSmart: configuration email manquante (SENDINBLUE_API_KEY / SENDER_EMAIL dans .env)');

            return false;
        }

        $result = true;
        $userEmail = trim($userEmail);

        if ($userEmail !== '') {
            $subject = 'Confirmation de votre achat - NutriSmart';
            $message = '<html><body>'
                . '<div style="font-family: Arial, sans-serif; color: #333;">'
                . '<h2>Merci pour votre achat, ' . htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '!</h2>'
                . '<p>Votre achat a été enregistré avec succès.</p>'
                . '<table style="width:100%; border-collapse: collapse;">'
                . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Aliment</strong></td><td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars($alimentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
                . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Quantité</strong></td><td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars((string) $qty, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
                . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Prix total</strong></td><td style="padding:8px; border:1px solid #ddd;">' . htmlspecialchars((string) $totalPrice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ' TND</td></tr>'
                . '</table>'
                . '<p>Vous pouvez consulter votre historique d\'achats depuis votre espace NutriSmart.</p>'
                . '<p>Cordialement,<br>L\'équipe NutriSmart</p>'
                . '</div>'
                . '</body></html>';

            $result = $this->sendEmail($userEmail, $subject, $message);
            error_log('NutriSmart Brevo: confirmation achat → ' . $userEmail . ' → ' . ($result ? 'ok' : 'échec'));
        }

        $adminSubject = '[NutriSmart] Nouvel achat — notification';
        $qtySafe = htmlspecialchars((string) $qty, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $priceSafe = htmlspecialchars((string) $totalPrice, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $emailDisplay = $userEmail !== '' ? htmlspecialchars($userEmail, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : '—';
        $adminMessage = '<html><body style="font-family: Arial, sans-serif; color: #333;">'
            . '<h2 style="color: #2d5a27;">Nouvel achat enregistré</h2>'
            . '<p>Un utilisateur vient d\'effectuer un achat depuis la boutique / la liste de courses.</p>'
            . '<table style="width:100%; max-width:520px; border-collapse: collapse;">'
            . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Acheteur</strong></td><td style="padding:8px; border:1px solid #ddd;">'
            . htmlspecialchars($userName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>E-mail</strong></td><td style="padding:8px; border:1px solid #ddd;">' . $emailDisplay . '</td></tr>'
            . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Aliment</strong></td><td style="padding:8px; border:1px solid #ddd;">'
            . htmlspecialchars($alimentName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td></tr>'
            . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Quantité</strong></td><td style="padding:8px; border:1px solid #ddd;">' . $qtySafe . '</td></tr>'
            . '<tr><td style="padding:8px; border:1px solid #ddd;"><strong>Prix total</strong></td><td style="padding:8px; border:1px solid #ddd;">' . $priceSafe . ' TND</td></tr>'
            . '</table>'
            . '<p style="font-size:12px;color:#666;">Message automatique — NutriSmart</p>'
            . '</body></html>';

        $adminOk = $this->sendEmail(self::ADMIN_PURCHASE_NOTIFY_EMAIL, $adminSubject, $adminMessage);
        error_log('NutriSmart Brevo: notification achat admin → ' . self::ADMIN_PURCHASE_NOTIFY_EMAIL . ' → ' . ($adminOk ? 'ok' : 'échec'));

        return $result && $adminOk;
    }

    /** Alerte dépassement de budget (utilisateur). */
    public function sendBudgetExceededEmail(string $userEmail, string $userName, float $budgetAmount, float $totalSpent, array $purchases): bool
    {
        if ($this->apiKey === '' || $this->senderEmail === '') {
            return false;
        }

        $subject = 'Alerte dépassement de budget - NutriSmart';
        $message = $this->buildBudgetExceededMessage($userName, $budgetAmount, $totalSpent, $purchases);

        return $this->sendEmail($userEmail, $subject, $message);
    }

    private function buildBudgetExceededMessage(string $userName, float $budgetAmount, float $totalSpent, array $purchases): string
    {
        $overAmount = $totalSpent - $budgetAmount;

        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background-color: #3dba52; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .warning { background-color: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; border-radius: 5px; margin: 20px 0; }
                .purchases-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                .purchases-table th, .purchases-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .purchases-table th { background-color: #f2f2f2; }
                .footer { background-color: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>🚨 Alerte dépassement de budget</h1>
            </div>

            <div class='content'>
                <p>Bonjour <strong>{$userName}</strong>,</p>

                <div class='warning'>
                    <h3>⚠️ Votre budget a été dépassé !</h3>
                    <p><strong>Budget défini :</strong> {$budgetAmount} TND</p>
                    <p><strong>Dépenses totales :</strong> {$totalSpent} TND</p>
                    <p><strong>Dépassement :</strong> {$overAmount} TND</p>
                </div>

                <h3>Achats de l'utilisateur :</h3>
                <table class='purchases-table'>
                    <thead>
                        <tr>
                            <th>Aliment</th>
                            <th>Quantité</th>
                            <th>Prix Total</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>";

        foreach ($purchases as $purchase) {
            $html .= '
                        <tr>
                            <td>' . htmlspecialchars((string) ($purchase['aliment_nom'] ?? '')) . '</td>
                            <td>' . htmlspecialchars((string) ($purchase['quantite'] ?? '')) . '</td>
                            <td>' . htmlspecialchars((string) ($purchase['prix_total'] ?? '')) . ' TND</td>
                            <td>' . htmlspecialchars((string) ($purchase['date_achat'] ?? '')) . '</td>
                        </tr>';
        }

        $html .= "
                    </tbody>
                </table>

                <p>Nous vous recommandons de réviser vos achats et d'ajuster votre budget si nécessaire.</p>
                <p>Pour plus d'informations, connectez-vous à votre compte NutriSmart.</p>

                <p>Cordialement,<br>L'équipe NutriSmart</p>
            </div>

            <div class='footer'>
                <p>Cet email a été envoyé automatiquement par NutriSmart.</p>
                <p>Ne pas répondre à cet email.</p>
            </div>
        </body>
        </html>";

        return $html;
    }

    private function getEmailTemplate(string $recipeName, string $status): string
    {
        $safeName = htmlspecialchars($recipeName, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $base = $this->baseUrl();
        $urlRecettes = $base . '/Views/frontoffice/recette.php';
        $urlProposer = $base . '/Views/frontoffice/proposer-recette.php';

        if ($status === 'approved') {
            return "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Votre recette approuvée</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #4CAF50, #2D6A2D); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
        <h1 style='margin: 0; font-size: 28px;'>🎉 Félicitations !</h1>
    </div>

    <div style='background: white; border: 1px solid #ddd; border-radius: 0 0 10px 10px; padding: 30px;'>
        <h2 style='color: #2D6A2D; margin-top: 0;'>Votre recette a été approuvée</h2>

        <p>Bonjour,</p>

        <p>Nous sommes ravis de vous annoncer que votre proposition de recette <strong>\"{$safeName}\"</strong> a été examinée et <strong>approuvée</strong> par notre équipe !</p>

        <p>Votre recette est désormais visible sur notre plateforme NutriSmart et pourra inspirer des milliers d'utilisateurs soucieux de leur santé.</p>

        <div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #4CAF50;'>
            <p style='margin: 0;'><strong>📋 Prochaines étapes :</strong></p>
            <ul style='margin: 10px 0 0 20px;'>
                <li>Votre recette est maintenant publiée</li>
                <li>Vous pouvez la retrouver dans la section \"Recettes de la Communauté\"</li>
                <li>N'hésitez pas à proposer d'autres recettes !</li>
            </ul>
        </div>

        <p style='margin-bottom: 30px;'>Merci d'avoir contribué à enrichir notre communauté de recettes saines !</p>

        <div style='text-align: center; margin-top: 30px;'>
            <a href='{$urlRecettes}'
               style='background: #4CAF50; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>
                Voir ma recette
            </a>
        </div>

        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

        <p style='font-size: 14px; color: #666; text-align: center; margin: 0;'>
            Cordialement,<br>
            <strong>L'équipe NutriSmart</strong><br>
            <a href='mailto:nutrismartwebsite@gmail.com' style='color: #4CAF50;'>nutrismartwebsite@gmail.com</a>
        </p>
    </div>
</body>
</html>";
        }

        return "
<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Proposition de recette refusée</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #f44336, #c62828); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
        <h1 style='margin: 0; font-size: 28px;'>❌ Proposition refusée</h1>
    </div>

    <div style='background: white; border: 1px solid #ddd; border-radius: 0 0 10px 10px; padding: 30px;'>
        <h2 style='color: #c62828; margin-top: 0;'>Votre recette n'a pas été retenue</h2>

        <p>Bonjour,</p>

        <p>Nous vous remercions d'avoir proposé votre recette <strong>\"{$safeName}\"</strong> à notre communauté NutriSmart.</p>

        <p>Après examen attentif de notre équipe, nous avons décidé de ne pas publier cette recette pour le moment. Cela peut être dû à plusieurs raisons :</p>

        <div style='background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ff9800;'>
            <p style='margin: 0;'><strong>📋 Raisons possibles :</strong></p>
            <ul style='margin: 10px 0 0 20px;'>
                <li>Informations nutritionnelles incomplètes</li>
                <li>Instructions insuffisamment détaillées</li>
                <li>Recette ne correspondant pas à nos critères de santé</li>
                <li>Contenu dupliqué</li>
            </ul>
        </div>

        <p style='margin-bottom: 30px;'>N'hésitez pas à proposer d'autres recettes en améliorant ces aspects !</p>

        <div style='text-align: center; margin-top: 30px;'>
            <a href='{$urlProposer}'
               style='background: #f44336; color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block;'>
                Proposer une nouvelle recette
            </a>
        </div>

        <hr style='border: none; border-top: 1px solid #eee; margin: 30px 0;'>

        <p style='font-size: 14px; color: #666; text-align: center; margin: 0;'>
            Cordialement,<br>
            <strong>L'équipe NutriSmart</strong><br>
            <a href='mailto:nutrismartwebsite@gmail.com' style='color: #f44336;'>nutrismartwebsite@gmail.com</a>
        </p>
    </div>
</body>
</html>";
    }

    private function sendEmail(string $to, string $subject, string $htmlContent): bool
    {
        $data = [
            'sender' => [
                'name' => 'NutriSmart',
                'email' => $this->senderEmail,
            ],
            'to' => [
                ['email' => $to],
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent,
        ];

        $payload = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($payload === false) {
            error_log('NutriSmart mail: json_encode payload échoué');

            return false;
        }

        $endpoints = [
            'https://api.brevo.com/v3/smtp/email',
            'https://api.sendinblue.com/v3/smtp/email',
        ];

        foreach ($endpoints as $url) {
            if ($this->postBrevoTransactional($url, $payload)) {
                return true;
            }
        }

        return false;
    }

    /** @param non-falsy-string $url */
    private function postBrevoTransactional(string $url, string $payload): bool
    {
        $headers = [
            'Content-Type: application/json',
            'api-key: ' . $this->apiKey,
        ];

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $payload,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 45,
                CURLOPT_CONNECTTIMEOUT => 15,
            ]);
            $response = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $cerr = curl_error($ch);
            curl_close($ch);

            if ($response !== false && ($code === 200 || $code === 201 || $code === 202)) {
                return true;
            }

            error_log("NutriSmart mail API {$url} HTTP {$code} curl_err={$cerr} resp=" . substr((string) $response, 0, 800));
        }

        $headerLines = array_merge($headers, ['Content-Length: ' . strlen($payload)]);
        $ctx = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", $headerLines),
                'content' => $payload,
                'timeout' => 45,
                'ignore_errors' => true,
            ],
        ]);

        $response = @file_get_contents($url, false, $ctx);
        $first = (isset($http_response_header) && isset($http_response_header[0]))
            ? $http_response_header[0]
            : '';
        if ($response !== false && preg_match('#\s(200|201|202)\s#', $first)) {
            return true;
        }

        error_log('NutriSmart mail fallback HTTP ' . $first . ' body=' . substr((string) $response, 0, 600));

        return false;
    }
}
