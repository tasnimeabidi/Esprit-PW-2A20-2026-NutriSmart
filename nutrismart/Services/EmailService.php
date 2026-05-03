<?php

class EmailService {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../config/email_config.php';
    }

    public function sendBudgetExceededEmail($userEmail, $userName, $budgetAmount, $totalSpent, $purchases) {
        $subject = 'Alerte dépassement de budget - NutriSmart';

        $message = $this->buildBudgetExceededMessage($userName, $budgetAmount, $totalSpent, $purchases);

        return $this->sendEmail($userEmail, $subject, $message);
    }

    public function sendTestEmail($toEmail) {
        $subject = 'Test d\'envoi email - NutriSmart';
        $message = "<html><body>"
            . "<h2>Test d'envoi d'email NutriSmart</h2>"
            . "<p>Ceci est un email de test envoyé depuis votre instance NutriSmart.</p>"
            . "<p>Si vous recevez cet email, la configuration SMTP fonctionne correctement.</p>"
            . "</body></html>";

        $result = $this->sendEmail($toEmail, $subject, $message);
        error_log('EmailService test email to ' . $toEmail . ' result: ' . ($result ? 'success' : 'failure'));
        return $result;
    }

    public function sendPurchaseConfirmationEmail($userEmail, $userName, $alimentName, $qty, $totalPrice) {
        $subject = 'Confirmation de votre achat - NutriSmart';
        $message = "<html><body>"
            . "<div style=\"font-family: Arial, sans-serif; color: #333;\">"
            . "<h2>Merci pour votre achat, " . htmlspecialchars($userName) . "!</h2>"
            . "<p>Votre achat a été enregistré avec succès.</p>"
            . "<table style=\"width:100%; border-collapse: collapse;\">"
            . "<tr><td style=\"padding:8px; border:1px solid #ddd;\"><strong>Aliment</strong></td><td style=\"padding:8px; border:1px solid #ddd;\">" . htmlspecialchars($alimentName) . "</td></tr>"
            . "<tr><td style=\"padding:8px; border:1px solid #ddd;\"><strong>Quantité</strong></td><td style=\"padding:8px; border:1px solid #ddd;\">" . htmlspecialchars($qty) . "</td></tr>"
            . "<tr><td style=\"padding:8px; border:1px solid #ddd;\"><strong>Prix total</strong></td><td style=\"padding:8px; border:1px solid #ddd;\">" . htmlspecialchars($totalPrice) . " TND</td></tr>"
            . "</table>"
            . "<p>Vous pouvez consulter votre historique d'achats depuis votre espace NutriSmart.</p>"
            . "<p>Cordialement,<br>L'équipe NutriSmart</p>"
            . "</div>"
            . "</body></html>";

        $result = $this->sendEmail('youssef.mejri@esprit.tn', $subject, $message);
        error_log('EmailService purchase confirmation to ' . $userEmail . ' result: ' . ($result ? 'success' : 'failure'));
        return $result;
    }

    private function buildBudgetExceededMessage($userName, $budgetAmount, $totalSpent, $purchases) {
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
            $html .= "
                        <tr>
                            <td>" . htmlspecialchars($purchase['aliment_nom']) . "</td>
                            <td>" . htmlspecialchars($purchase['quantite']) . "</td>
                            <td>" . htmlspecialchars($purchase['prix_total']) . " TND</td>
                            <td>" . htmlspecialchars($purchase['date_achat']) . "</td>
                        </tr>";
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

    private function sendEmail($to, $subject, $htmlMessage) {
        if ($this->config['email']['use_resend'] ?? false) {
            return $this->sendResendEmail($to, $subject, $htmlMessage);
        }

        if ($this->config['email']['use_php_mail']) {
            // Utiliser la fonction mail() de PHP
            $headers = [
                'MIME-Version: 1.0',
                'Content-type: text/html; charset=UTF-8',
                'From: ' . $this->config['email']['from_name'] . ' <' . $this->config['email']['from_email'] . '>',
                'Reply-To: ' . $this->config['email']['from_email'],
                'X-Mailer: PHP/' . phpversion()
            ];

            $headersString = '';
            foreach ($headers as $header) {
                $headersString .= $header . "\r\n";
            }

            // Log l'email pour les tests
            error_log("EMAIL TO: $to\nSUBJECT: $subject\nMESSAGE:\n" . substr($htmlMessage, 0, 500) . "...\n---\n");

            return mail($to, $subject, $htmlMessage, $headersString);
        }

        return $this->sendSmtpEmail($to, $subject, $htmlMessage);
    }

    private function sendResendEmail($to, $subject, $htmlMessage) {
        $apiKey = $this->config['email']['resend_api_key'];
        $from = $this->config['email']['from_email'];

        $data = [
            'from' => $from,
            'to' => [$to],
            'subject' => $subject,
            'html' => $htmlMessage
        ];

        $ch = curl_init('https://api.resend.com/emails');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 200) {
            error_log("Resend email sent successfully to $to");
            return true;
        } else {
            error_log("Resend email failed: HTTP $httpCode, Response: $response");
            return false;
        }
    }

    private function sendSmtpEmail($to, $subject, $htmlMessage) {
        $host = $this->config['email']['smtp_host'];
        $port = $this->config['email']['smtp_port'];
        $username = $this->config['email']['smtp_username'];
        $password = $this->config['email']['smtp_password'];
        $fromEmail = $this->config['email']['from_email'];
        $fromName = $this->config['email']['from_name'];
        $secure = $this->config['email']['smtp_secure'] ?? 'tls';
        $timeout = 30;

        $remoteAddress = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host;
        $socket = fsockopen($remoteAddress, $port, $errno, $errstr, $timeout);
        if (!$socket) {
            error_log("SMTP connection failed: $errno - $errstr");
            return false;
        }
        stream_set_timeout($socket, $timeout);

        if (!$this->smtpRead($socket, 220)) {
            fclose($socket);
            return false;
        }

        $hostname = gethostname() ?: 'localhost';
        if (!$this->smtpSend($socket, "EHLO $hostname", 250)) {
            fclose($socket);
            return false;
        }

        if ($secure === 'tls') {
            if (!$this->smtpSend($socket, 'STARTTLS', 220)) {
                fclose($socket);
                return false;
            }
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                error_log('SMTP STARTTLS failed');
                fclose($socket);
                return false;
            }
            if (!$this->smtpSend($socket, "EHLO $hostname", 250)) {
                fclose($socket);
                return false;
            }
        }

        if (!$this->smtpSend($socket, 'AUTH LOGIN', 334)) {
            fclose($socket);
            return false;
        }
        if (!$this->smtpSend($socket, base64_encode($username), 334)) {
            fclose($socket);
            return false;
        }
        if (!$this->smtpSend($socket, base64_encode($password), 235)) {
            fclose($socket);
            return false;
        }

        if (!$this->smtpSend($socket, "MAIL FROM:<$fromEmail>", 250)) {
            fclose($socket);
            return false;
        }
        if (!$this->smtpSend($socket, "RCPT TO:<$to>", 250)) {
            fclose($socket);
            return false;
        }
        if (!$this->smtpSend($socket, 'DATA', 354)) {
            fclose($socket);
            return false;
        }

        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . $fromName . ' <' . $fromEmail . '>',
            'Reply-To: ' . $fromEmail,
            'Subject: ' . $subject,
            'Date: ' . date('r')
        ];

        $data = implode("\r\n", $headers) . "\r\n\r\n" . $htmlMessage . "\r\n.";
        if (!$this->smtpSend($socket, $data, 250)) {
            fclose($socket);
            return false;
        }

        $this->smtpSend($socket, 'QUIT', 221);
        fclose($socket);
        return true;
    }

    private function smtpRead($socket, $expectedCode) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        $code = intval(substr($response, 0, 3));
        if ($code !== $expectedCode) {
            error_log("SMTP read failed. Expected $expectedCode, got $response");
            return false;
        }
        return true;
    }

    private function smtpSend($socket, $command, $expectedCode) {
        if (fwrite($socket, $command . "\r\n") === false) {
            error_log("SMTP send failed: $command");
            return false;
        }
        return $this->smtpRead($socket, $expectedCode);
    }
}