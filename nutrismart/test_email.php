<?php
require_once 'Services/EmailService.php';

$sent = null;
$error = null;
$email = $_POST['email'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Veuillez saisir une adresse email valide.';
    } else {
        $emailService = new EmailService();
        $sent = $emailService->sendTestEmail($email);
        if (!$sent) {
            $error = 'L\'envoi de l\'email a échoué. Vérifiez la configuration SMTP dans config/email_config.php et consultez le journal PHP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test SMTP NutriSmart</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f7f7f7; }
        .container { max-width: 600px; margin: 0 auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input[type=text] { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ccc; border-radius: 4px; }
        button { background: #3dba52; color: white; border: none; padding: 10px 16px; border-radius: 4px; cursor: pointer; }
        .success { color: #155724; background: #d4edda; border: 1px solid #c3e6cb; padding: 12px; border-radius: 4px; margin-top: 12px; }
        .error { color: #721c24; background: #f8d7da; border: 1px solid #f5c6cb; padding: 12px; border-radius: 4px; margin-top: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Test d'envoi email NutriSmart</h1>
        <p>Entrez une adresse email pour tester la configuration SMTP.</p>
        <form method="post" action="test_email.php">
            <label for="email">Adresse email :</label>
            <input type="text" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
            <button type="submit">Envoyer un email de test</button>
        </form>

        <?php if ($sent === true): ?>
            <div class="success">Email envoyé avec succès. Vérifiez votre boîte de réception.</div>
        <?php elseif ($sent === false): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <h2>Configuration requise</h2>
        <p>Ouvrez <code>config/email_config.php</code> et mettez à jour :</p>
        <ul>
            <li><code>use_resend</code> à <code>true</code></li>
            <li><code>resend_api_key</code> avec votre clé API Resend</li>
            <li>Ou configurez SMTP si vous préférez</li>
        </ul>
    </div>
</body>
</html>