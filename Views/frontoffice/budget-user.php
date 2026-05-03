<?php
// Check if user ID is provided in URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die('Erreur: ID utilisateur manquant');
}

$user_id = intval($_GET['user_id']);

require_once '../../Services/BudgetService.php';
require_once '../../Services/AchatService.php';
require_once '../../Models/User.php';
require_once '../../Services/UserService.php';

$budgetService = new BudgetService();
$achatService = new AchatService();
$userService = new UserService();

// Get user info
$user = $userService->getUserById($user_id);
if (!$user) {
    die('Utilisateur non trouvé');
}

// Get user's budget
$budget = $budgetService->getBudgetByUserId($user_id);
$totalDepenses = $achatService->getTotalDepensesByUserId($user_id);

// Get user's shopping list
$achats = $achatService->getAchatsByUserId($user_id);

// Calculate remaining budget
$reste = $budget ? ($budget['montant'] - $totalDepenses) : 0;
$pourcentage = $budget ? min(100, ($totalDepenses / $budget['montant']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSmart — Budget</title>
    <link rel="stylesheet" href="../../css/mp-dashboard.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* Budget-specific styles */
        .budget-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .budget-hero-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .budget-hero-card {
            background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%);
            border-radius: 20px;
            padding: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            min-height: 280px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .budget-hero-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: pulse 15s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .budget-hero-card .badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(10px);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
            width: fit-content;
        }

        .budget-hero-card h2 {
            font-size: 1.75rem;
            margin-bottom: 1rem;
            color: white;
        }

        .budget-tags {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .budget-tags span {
            background: rgba(255,255,255,0.15);
            padding: 0.5rem 1rem;
            border-radius: 0.75rem;
            font-size: 0.875rem;
        }

        .budget-stats-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(45, 90, 39, 0.08);
            border: 1px solid #e8ece9;
        }

        .budget-stats-card h3 {
            font-size: 1.125rem;
            color: #1e3d2f;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e8ece9;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 1rem;
            margin-bottom: 1rem;
        }

        .stat-item:last-child {
            margin-bottom: 0;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.5rem;
        }

        .stat-icon.expenses {
            background: #fff4e6;
        }

        .stat-icon.remaining {
            background: #e8f5e9;
        }

        .stat-info strong {
            display: block;
            font-size: 1.25rem;
            color: #1f2421;
        }

        .stat-info p {
            font-size: 0.875rem;
            color: #5c6b63;
            margin: 0;
        }

        .progress-section {
            margin-top: 1.5rem;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }

        .progress-label span:first-child {
            color: #5c6b63;
        }

        .progress-label strong {
            color: #1e3d2f;
        }

        .progress-track {
            height: 12px;
            background: #e8ece9;
            border-radius: 6px;
            overflow: hidden;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #4a7c59 0%, #8fbc8f 100%);
            border-radius: 6px;
            transition: width 0.5s ease;
        }

        .budget-form-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 24px rgba(45, 90, 39, 0.08);
            border: 1px solid #e8ece9;
        }

        .budget-form-card h3 {
            font-size: 1.25rem;
            color: #1e3d2f;
            margin-bottom: 1.5rem;
        }

        .budget-form {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .budget-form .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .budget-form input {
            width: 100%;
            padding: 1rem 1.5rem;
            border: 2px solid #e8ece9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .budget-form input:focus {
            outline: none;
            border-color: #4a7c59;
            box-shadow: 0 0 0 4px rgba(74, 124, 89, 0.1);
        }

        .budget-form .btn {
            padding: 1rem 2rem;
            white-space: nowrap;
        }

        .shopping-list-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(45, 90, 39, 0.08);
            border: 1px solid #e8ece9;
        }

        .shopping-list-card h2 {
            font-size: 1.35rem;
            color: #1e3d2f;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .shopping-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f3f5;
            transition: background-color 0.2s;
        }

        .shopping-item:last-child {
            border-bottom: none;
        }

        .shopping-item:hover {
            background: #fafcfb;
            border-radius: 8px;
        }

        .shopping-item .item-name {
            font-weight: 600;
            color: #1f2421;
        }

        .shopping-item .item-price {
            font-weight: 700;
            color: #4a7c59;
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            color: #5c6b63;
        }

        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 900px) {
            .budget-hero-section {
                grid-template-columns: 1fr;
            }

            .budget-form {
                flex-direction: column;
            }

            .budget-form .btn {
                width: 100%;
            }
        }
    </style>
</head>

<body>
<div class="app app--fo-topnav">

<header class="fo-topnav">
    <a href="accueil.html" class="fo-topnav-brand">
        <span class="brand-mark" aria-hidden="true">
            <svg
                width="36"
                height="36"
                viewBox="0 0 100 100"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
                style="overflow: visible"
            >
                <mask id="biteMaskAccueil">
                    <rect x="-20" y="-20" width="140" height="140" fill="white" />
                    <circle cx="92" cy="35" r="18" fill="black" />
                    <circle cx="84" cy="62" r="14" fill="black" />
                </mask>
                <g mask="url(#biteMaskAccueil)">
                    <path
                        d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z"
                        fill="#4a7c59"
                    />
                    <path
                        d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z"
                        fill="#8fbc8f"
                    />
                </g>
                <path
                    d="M 22 78 L 12 92"
                    stroke="#4a7c59"
                    stroke-width="7"
                    stroke-linecap="round"
                />
            </svg>
        </span>
        <span class="brand-text">
            <span class="brand-nutri">Nutri</span><span class="brand-smart">Smart</span>
        </span>
    </a>

    <nav class="fo-topnav-center">
        <a href="accueil.html" class="fo-topnav-link">Accueil</a>
        <a href="#" class="fo-topnav-link is-active">Budget</a>
       <a href="user-achat.php?user_id=<?php echo $user_id; ?>" class="fo-topnav-link">Boutique</a>
          
      
        <a href="contact.html" class="fo-topnav-link">Contact</a>
        <a href="profile.html" class="fo-topnav-link">Profile</a>
    </nav>
    <div class="fo-topnav-right">
        <div class="fo-topnav-user">
            <div class="sidebar-avatar" aria-hidden="true"><?php echo strtoupper(substr($user['nom'], 0, 2)); ?></div>
            <div>
                <div class="name"><?php echo htmlspecialchars($user['nom']); ?></div>
                <div class="badge">Membre</div>
            </div>
        </div>
    </div>
</header>

<main class="main">
    <div class="budget-page">

        <header class="main-header">
            <div>
                <h1 class="serif">💰 Budget & Courses</h1>
                <p class="date">Gestion des dépenses alimentaires</p>
            </div>
        </header>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">✓ Budget enregistré avec succès !</div>
        <?php elseif (isset($_GET['error'])): ?>
            <div class="alert alert-error">⚠ <?php echo htmlspecialchars($_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Budget Form -->
        <div class="budget-form-card">
            <h3>🎯 Définir votre budget mensuel</h3>
            <form action="add_budget.php?user_id=<?php echo $user_id; ?>" method="POST" class="budget-form">
                <div class="form-group">
                    <input type="number" name="budget" placeholder="Entrez votre budget en TND" step="0.01" min="0" required value="<?php echo $budget ? htmlspecialchars($budget['montant']) : ''; ?>">
                </div>
                <button type="submit" class="btn">Enregistrer le budget</button>
            </form>
        </div>

        <!-- Hero Section -->
        <div class="budget-hero-section">
            <div class="budget-hero-card">
                <span class="badge">📊 Suivi Actif</span>
                <h2>Gestion Budget Alimentaire</h2>
                <div class="budget-tags">
                    <span>Budget: <?php echo $budget ? number_format($budget['montant'], 2) . ' TND' : 'Non défini'; ?></span>
                    <span>Objectif: Économie</span>
                </div>
            </div>

            <div class="budget-stats-card">
                <h3>Statistiques du mois</h3>

                <div class="stat-item">
                    <div class="stat-icon expenses">💸</div>
                    <div class="stat-info">
                        <strong><?php echo number_format($totalDepenses, 2); ?> TND</strong>
                        <p>Dépenses totales</p>
                    </div>
                </div>

                <div class="stat-item">
                    <div class="stat-icon remaining">📊</div>
                    <div class="stat-info">
                        <strong><?php echo number_format(max(0, $reste), 2); ?> TND</strong>
                        <p>Budget restant</p>
                    </div>
                </div>

                <div class="progress-section">
                    <div class="progress-label">
                        <span>Progression</span>
                        <strong><?php echo number_format($pourcentage, 1); ?>%</strong>
                    </div>
                    <div class="progress-track">
                        <div class="progress-fill" style="width: <?php echo $pourcentage; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Shopping List -->
        <div class="shopping-list-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                <h2>🛒 Liste de courses</h2>
                <a href="user-achat.php?user_id=<?php echo $user_id; ?>" class="btn" style="background: linear-gradient(135deg, #e67e22, #f39c12); color: white; padding: 0.75rem 1.5rem;">+ Acheter des aliments</a>
            </div>

            <?php if (empty($achats)): ?>
                <div class="empty-state">
                    <div class="icon">🛍️</div>
                    <p>Aucun article dans votre liste de courses</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #5c6b63;">Commencez vos achats en visitant notre boutique d'aliments</p>
                </div>
            <?php else: ?>
                <?php foreach ($achats as $achat): ?>
                    <div class="shopping-item">
                        <span class="item-name"><?php echo htmlspecialchars($achat['nom_aliment']); ?></span>
                        <strong class="item-price"><?php echo number_format($achat['prix_total'], 2); ?> TND</strong>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>
</div>
</body>
</html>