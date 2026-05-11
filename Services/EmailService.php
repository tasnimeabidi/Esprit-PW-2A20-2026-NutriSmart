<?php
/**
 * Point d’entrée unique : l’implémentation est dans Models/EmailService.php (API Brevo).
 * Évite deux classes EmailService différentes (SMTP vs Brevo).
 */
declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/EmailService.php';
