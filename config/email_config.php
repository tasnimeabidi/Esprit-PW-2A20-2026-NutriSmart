<?php
// Configuration des emails pour NutriSmart
// À configurer selon votre fournisseur d'email

return [
    'email' => [
        'smtp_host' => 'smtp.gmail.com', // smtp.gmail.com, smtp.outlook.com, etc.
        'smtp_port' => 587, // 587 pour TLS, 465 pour SSL
        'smtp_secure' => 'tls', // tls or ssl
        'smtp_username' => 'mejriy25@gmail.com', // Votre adresse email
        'smtp_password' => 'votre-mot-de-passe-application', // Mot de passe d'application (pas le mot de passe normal)
        'from_email' => 'onboarding@resend.dev',
        'from_name' => 'NutriSmart',
        'use_php_mail' => false, // true pour utiliser mail() de PHP, false pour SMTP
        'use_resend' => true, // true pour utiliser Resend API
        'resend_api_key' => 're_GieFfLTF_MHbYwYmkdggZNUnAPR74zsp2', // Votre clé API Resend
    ]
];