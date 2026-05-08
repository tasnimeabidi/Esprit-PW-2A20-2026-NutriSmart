<?php
class EmailService {
    private $apiKey;
    private $senderEmail;

    public function __construct() {
        $this->apiKey = SENDINBLUE_API_KEY;
        $this->senderEmail = SENDER_EMAIL;
    }

    public function sendRecipeNotification($toEmail, $recipeName, $status) {
        if (empty($this->apiKey) || empty($this->senderEmail)) {
            error_log("Email configuration missing");
            return false;
        }

        $subject = $status === 'approved' ? 
            "🎉 Votre recette a été approuvée !" : 
            "❌ Votre proposition de recette a été refusée";

        $message = $this->getEmailTemplate($recipeName, $status);

        return $this->sendEmail($toEmail, $subject, $message);
    }

    private function getEmailTemplate($recipeName, $status) {
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
        
        <p>Nous sommes ravis de vous annoncer que votre proposition de recette <strong>\"$recipeName\"</strong> a été examinée et <strong>approuvée</strong> par notre équipe !</p>
        
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
            <a href='http://localhost/nutrismart-chahine/nutrismart/Views/frontoffice/recette.php' 
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
        } else {
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
        
        <p>Nous vous remercions d'avoir proposé votre recette <strong>\"$recipeName\"</strong> à notre communauté NutriSmart.</p>
        
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
            <a href='http://localhost/nutrismart-chahine/nutrismart/Views/frontoffice/proposer-recette.php' 
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
    }

    private function sendEmail($to, $subject, $htmlContent) {
        $url = 'https://api.sendinblue.com/v3/smtp/email';

        $data = [
            'sender' => [
                'name' => 'NutriSmart',
                'email' => $this->senderEmail
            ],
            'to' => [
                [
                    'email' => $to
                ]
            ],
            'subject' => $subject,
            'htmlContent' => $htmlContent
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'api-key: ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode === 201) {
            return true;
        } else {
            error_log("Sendinblue API error: " . $response);
            return false;
        }
    }
}
?>