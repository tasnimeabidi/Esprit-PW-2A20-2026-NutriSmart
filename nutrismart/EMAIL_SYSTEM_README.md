# Système d'Email de Dépassement de Budget - NutriSmart

## Description
Ce système envoie automatiquement un email aux utilisateurs lorsque leur budget alimentaire est dépassé. L'email contient un résumé du dépassement et la liste détaillée des achats.

## Fonctionnalités
- ✅ Détection automatique des dépassements de budget
- ✅ Email HTML professionnel en français
- ✅ Liste détaillée des achats de l'utilisateur
- ✅ Calcul automatique du montant de dépassement
- ✅ Évite le spam (une notification par dépassement)

## Déclencheurs
Les emails sont envoyés automatiquement lors de :
- Ajout d'un nouvel achat (`addPurchase`)
- Modification d'un achat existant (`updatePurchase`)
- Mise à jour du budget (`setBudget`)

## Configuration

### 1. Configuration Email (`config/email_config.php`)
```php
return [
    'email' => [
        'smtp_host' => 'smtp.gmail.com', // ou votre serveur SMTP
        'smtp_port' => 587, // 587 pour TLS, 465 pour SSL
        'smtp_username' => 'votre-email@gmail.com',
        'smtp_password' => 'votre-mot-de-passe-application',
        'from_email' => 'noreply@nutrismart.com',
        'from_name' => 'NutriSmart',
        'use_php_mail' => true, // true = mail() PHP, false = SMTP
    ]
];
```

### 2. Configuration pour Gmail
1. Activez la vérification en 2 étapes
2. Générez un "mot de passe d'application" : https://myaccount.google.com/apppasswords
3. Utilisez ce mot de passe (pas votre mot de passe normal) dans `smtp_password`

### 3. Configuration pour XAMPP
Modifiez `php.ini` (C:\xampp\php\php.ini) :
```ini
[mail function]
SMTP = smtp.gmail.com
smtp_port = 587
sendmail_from = votre-email@gmail.com
sendmail_path = "\"C:\xampp\sendmail\sendmail.exe\" -t"
```

Modifiez `sendmail.ini` (C:\xampp\sendmail\sendmail.ini) :
```ini
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=tls
auth_username=votre-email@gmail.com
auth_password=votre-mot-de-passe-application
```

## Contenu de l'Email
L'email contient :
- Message d'alerte avec budget/dépenses/dépassement
- Tableau détaillé des achats (aliment, quantité, prix, date)
- Mise en forme HTML professionnelle
- Logo et couleurs NutriSmart

## Fichiers Modifiés
- `Services/EmailService.php` - Nouveau service d'email
- `Services/BudgetService.php` - Ajout de la logique de notification
- `Services/AchatService.php` - Déclenchement après achats
- `config/email_config.php` - Configuration email
## Corrections Importantes
- ✅ **Résolution des inclusions circulaires** : Supprimé les inclusions mutuelles entre BudgetService et AchatService pour éviter l'épuisement de mémoire
- ✅ **Chargement à la demande** : Les services sont instanciés seulement quand nécessaire pour éviter les dépendances circulaires
## Test
Pour tester le système :
1. Configurez un budget faible (ex: 10 TND)
2. Ajoutez des achats dépassant ce budget
3. Vérifiez les logs PHP (`C:\xampp\php\logs\php_error_log`)
4. L'email sera loggé même si l'envoi échoue

## Sécurité
- Les mots de passe sont stockés dans la configuration (à sécuriser en production)
- Utilisez des mots de passe d'application Gmail
- Les emails sont envoyés uniquement lors de dépassements réels

## Production
Pour la production :
1. Utilisez un vrai serveur SMTP
2. Stockez les configurations dans des variables d'environnement
3. Ajoutez une table de notifications pour éviter le spam
4. Configurez un système de queue pour les emails