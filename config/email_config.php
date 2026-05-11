<?php
/**
 * Emails NutriSmart (module budget Youssef Mejri).
 * Par défaut désactivé — passez 'enabled' à true et renseignez SMTP ou Resend.
 */
return [
    'email' => [
        'enabled' => false,
        'smtp_host' => '',
        'smtp_port' => 587,
        'smtp_secure' => 'tls',
        'smtp_username' => '',
        'smtp_password' => '',
        'from_email' => '',
        'from_name' => 'NutriSmart',
        'use_php_mail' => false,
        'use_resend' => false,
        'resend_api_key' => '',
    ],
];
