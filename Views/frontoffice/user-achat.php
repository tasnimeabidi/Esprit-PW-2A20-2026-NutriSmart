<?php
// Check if user ID is provided in URL
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    die('Erreur: ID utilisateur manquant');
}

$user_id = intval($_GET['user_id']);

require_once '../../Services/AlimentService.php';
require_once '../../Services/BudgetService.php';
require_once '../../Services/AchatService.php';
require_once '../../Models/User.php';
require_once '../../Services/UserService.php';

$alimentService = new AlimentService();
$budgetService = new BudgetService();
$achatService = new AchatService();
$userService = new UserService();

// Get user info
$user = $userService->getUserById($user_id);
if (!$user) {
    die('Utilisateur non trouvé');
}

// Get user's budget and spending
$budget = $budgetService->getBudgetByUserId($user_id);
$totalDepenses = $achatService->getTotalDepensesByUserId($user_id);
$reste = $budget ? ($budget['montant'] - $totalDepenses) : 0;

// Get aliments
$aliments = $alimentService->getAllAliments();
$categories = array_unique(array_column($aliments, 'categorie'));

// Handle search
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $aliments = $alimentService->searchAliments($_GET['search']);
} elseif (isset($_GET['category']) && !empty($_GET['category'])) {
    $aliments = $alimentService->getAlimentsByCategory($_GET['category']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSmart — Boutique Aliments</title>
    <link rel="stylesheet" href="../../css/mp-dashboard.css">
    <link rel="stylesheet" href="../../css/shared-styles.css">
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        /* User Achat specific styles */
        .achat-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .budget-banner {
            background: linear-gradient(135deg, #2d5a27 0%, #4a7c59 100%);
            color: white;
            border-radius: 20px;
            padding: 1.5rem 2rem;
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 8px 24px rgba(45, 90, 39, 0.2);
        }

        .budget-info {
            display: flex;
            gap: 2rem;
            align-items: center;
        }

        .budget-stat {
            text-align: center;
        }

        .budget-stat .label {
            font-size: 0.8rem;
            opacity: 0.8;
            margin-bottom: 0.25rem;
        }

        .budget-stat .value {
            font-size: 1.5rem;
            font-weight: 700;
        }

        .search-section {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 24px rgba(45, 90, 39, 0.08);
            border: 1px solid #e8ece9;
        }

        .search-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 0.75rem 1rem;
            border: 2px solid #e8ece9;
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s;
        }

        .search-input:focus {
            outline: none;
            border-color: #4a7c59;
            box-shadow: 0 0 0 4px rgba(74, 124, 89, 0.1);
        }

        .category-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .category-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #e8ece9;
            background: white;
            border-radius: 999px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .category-btn:hover {
            background: #f8f9fa;
            border-color: #4a7c59;
        }

        .category-btn.active {
            background: #e8f5e9;
            border-color: #4a7c59;
            color: #1e3d2f;
            font-weight: 600;
        }

        .aliments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .aliment-card {
            background: white;
            border-radius: 16px;
            padding: 1.5rem;
            border: 1px solid #e8ece9;
            box-shadow: 0 4px 20px rgba(45, 90, 39, 0.08);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .aliment-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(45, 90, 39, 0.15);
        }

        .aliment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .aliment-name {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1e3d2f;
            margin-bottom: 0.25rem;
        }

        .aliment-category {
            font-size: 0.75rem;
            color: #5c6b63;
            background: #f8f9fa;
            padding: 0.25rem 0.75rem;
            border-radius: 999px;
            font-weight: 600;
        }

        .aliment-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4a7c59;
            margin-bottom: 1rem;
        }

        .aliment-nutrition {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
            margin-bottom: 1.5rem;
        }

        .nutrient-item {
            background: #f8f9fa;
            padding: 0.5rem;
            border-radius: 8px;
            text-align: center;
        }

        .nutrient-value {
            font-size: 0.9rem;
            font-weight: 700;
            color: #1e3d2f;
        }

        .nutrient-label {
            font-size: 0.7rem;
            color: #5c6b63;
            text-transform: uppercase;
        }

        .purchase-form {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .qty-input {
            width: 80px;
            padding: 0.5rem;
            border: 1px solid #e8ece9;
            border-radius: 8px;
            font-size: 1rem;
            text-align: center;
        }

        .buy-btn {
            flex: 1;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, #4a7c59, #8fbc8f);
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .buy-btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }

        .buy-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            opacity: 0.6;
        }

        .out-of-budget {
            color: #e67e22;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: #5c6b63;
        }

        .empty-state .icon {
            font-size: 4rem;
            margin-bottom: 1rem;
        }

        .filters-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .results-count {
            font-size: 0.9rem;
            color: #5c6b63;
        }

        @media (max-width: 768px) {
            .budget-info {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .search-controls {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-input {
                min-width: 100%;
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
        <a href="budget-user.php?user_id=<?php echo $user_id; ?>" class="fo-topnav-link">Budget</a>
        <a href="#" class="fo-topnav-link is-active">Boutique</a>
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
    <div class="achat-page">

        <header class="main-header">
            <div>
                <h1 class="serif">🛒 Boutique Aliments</h1>
                <p class="date">Achetez vos aliments préférés</p>
            </div>
        </header>

        <!-- Budget Banner -->
        <div class="budget-banner">
            <div class="budget-info">
                <div class="budget-stat">
                    <div class="label">Budget Total</div>
                    <div class="value"><?php echo $budget ? number_format($budget['montant'], 2) : 'Non défini'; ?> TND</div>
                </div>
                <div class="budget-stat">
                    <div class="label">Dépenses</div>
                    <div class="value"><?php echo number_format($totalDepenses, 2); ?> TND</div>
                </div>
                <div class="budget-stat">
                    <div class="label">Restant</div>
                    <div class="value" style="color: <?php echo $reste < 0 ? '#e67e22' : '#ffffff'; ?>">
                        <?php echo number_format(max(0, $reste), 2); ?> TND
                    </div>
                </div>
            </div>
            <div>
                <a href="budget-user.php?user_id=<?php echo $user_id; ?>" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">Gérer le budget</a>
            </div>
        </div>

        <!-- Search & Filters -->
        <div class="search-section">
            <div class="filters-row">
                <h3 style="margin: 0; color: #1e3d2f;">Filtrer les aliments</h3>
                <div class="results-count"><?php echo count($aliments); ?> aliments disponibles</div>
            </div>
            
            <div class="search-controls">
                <input type="text" class="search-input" id="searchInput" placeholder="Rechercher un aliment..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                
                <div class="category-filters">
                    <button class="category-btn <?php echo (!isset($_GET['category']) || empty($_GET['category'])) ? 'active' : ''; ?>" onclick="filterByCategory('')">Tous</button>
                    <?php foreach($categories as $category): ?>
                        <button class="category-btn <?php echo (isset($_GET['category']) && $_GET['category'] == $category) ? 'active' : ''; ?>" onclick="filterByCategory('<?php echo $category; ?>')"><?php echo htmlspecialchars($category); ?></button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Aliments Grid -->
        <div class="aliments-grid">
            <?php if (empty($aliments)): ?>
                <div class="empty-state">
                    <div class="icon">🍎</div>
                    <h3>Aucun aliment trouvé</h3>
                    <p>Essayez une autre recherche ou consultez toutes les catégories</p>
                </div>
            <?php else: ?>
                <?php foreach($aliments as $aliment): ?>
                    <div class="aliment-card">
                        <div class="aliment-header">
                            <div>
                                <div class="aliment-name"><?php echo htmlspecialchars($aliment['nom']); ?></div>
                                <div class="aliment-category"><?php echo htmlspecialchars($aliment['categorie']); ?></div>
                            </div>
                            <div class="aliment-price"><?php echo number_format($aliment['prix'], 2); ?> TND</div>
                        </div>


                        <form action="add-achat.php?user_id=<?php echo $user_id; ?>" method="POST">
                            <input type="hidden" name="aliment_id" value="<?php echo $aliment['id']; ?>">
                            <input type="hidden" name="prix_unitaire" value="<?php echo $aliment['prix']; ?>">
                            
                            <div class="purchase-form">
                                <input type="number" name="quantite" class="qty-input" value="1" min="1" max="99" required>
                                <button type="submit" class="buy-btn" <?php echo ($reste <= 0) ? 'disabled' : ''; ?>>
                                    <?php echo ($reste <= 0) ? 'Budget insuffisant' : 'Acheter'; ?>
                                </button>
                            </div>
                            <?php if ($reste > 0): ?>
                                <div style="text-align: center; margin-top: 0.5rem; font-size: 0.8rem; color: #5c6b63;">
                                    Total: <span id="total-<?php echo $aliment['id']; ?>">0.00</span> TND
                                </div>
                            <?php else: ?>
                                <div class="out-of-budget">Budget insuffisant pour effectuer des achats</div>
                            <?php endif; ?>
                        </form>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</main>

<script>
    // Real-time total calculation
    document.querySelectorAll('.qty-input').forEach(input => {
        input.addEventListener('input', function() {
            const form = this.closest('form');
            const prixUnitaire = parseFloat(form.querySelector('input[name="prix_unitaire"]').value);
            const quantite = parseInt(this.value) || 0;
            const total = prixUnitaire * quantite;
            
            const totalSpan = form.querySelector('span[id^="total-"]');
            if (totalSpan) {
                totalSpan.textContent = total.toFixed(2);
            }
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            performSearch();
        }
    });

    function performSearch() {
        const search = document.getElementById('searchInput').value;
        const category = new URLSearchParams(window.location.search).get('category') || '';
        
        let url = 'user-achat.php?user_id=<?php echo $user_id; ?>';
        if (search) url += '&search=' + encodeURIComponent(search);
        if (category) url += '&category=' + encodeURIComponent(category);
        
        window.location.href = url;
    }

    function filterByCategory(category) {
        const search = document.getElementById('searchInput').value;
        let url = 'user-achat.php?user_id=<?php echo $user_id; ?>';
        if (search) url += '&search=' + encodeURIComponent(search);
        if (category) url += '&category=' + encodeURIComponent(category);
        
        window.location.href = url;
    }
</script>
</body>
</html>