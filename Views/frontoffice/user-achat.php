<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?next=' . rawurlencode('user-achat.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../../Services/AlimentService.php';
require_once __DIR__ . '/../../Services/BudgetService.php';
require_once __DIR__ . '/../../Services/AchatService.php';
require_once __DIR__ . '/../../Services/UserService.php';

$alimentService = new AlimentService();
$budgetService = new BudgetService();
$achatService = new AchatService();
$userService = new UserService();

$user = $userService->getUserById($user_id);
if (!$user) {
    die('Utilisateur non trouvé');
}

$budget = $budgetService->getBudgetByUserId($user_id);

if (!$budget) {
    header('Location: budget-user.php?no_budget=1');
    exit;
}

$totalDepenses = (float) $achatService->getTotalDepensesByUserId($user_id);
$reste = $budget ? ((float) $budget['montant'] - $totalDepenses) : 0.0;

$aliments = $alimentService->getAllAliments();
$categories = array_values(array_unique(array_filter(array_column($aliments, 'categorie'))));

if (isset($_GET['search']) && $_GET['search'] !== '') {
    $aliments = $alimentService->searchAliments((string) $_GET['search']);
} elseif (isset($_GET['category']) && $_GET['category'] !== '') {
    $aliments = $alimentService->getAlimentsByCategory((string) $_GET['category']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSmart — Boutique Aliments</title>
    <link rel="stylesheet" href="css/mp-dashboard.css">
    <link rel="stylesheet" href="css/shared-styles.css?v=3">
    <script src="js/avatar-utils.js"></script>
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

<body data-site-nav="">
<div id="site-nav-root"></div>
<script src="js/site-nav-loader.js"></script>

<div class="app app--fo-topnav">

<main class="main">
    <div class="achat-page">

        <header class="main-header">
            <div>
                <h1 class="serif">🛒 Boutique Aliments</h1>
                <p class="date">Achetez vos aliments préférés</p>
            </div>
        </header>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success" style="padding:1rem 1.25rem;border-radius:12px;background:#d4edda;color:#155724;margin-bottom:1rem;">✓ Achat enregistré.</div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert alert-error" style="padding:1rem 1.25rem;border-radius:12px;background:#f8d7da;color:#721c24;margin-bottom:1rem;">⚠ <?php echo htmlspecialchars((string) $_GET['error']); ?></div>
        <?php endif; ?>

        <!-- Budget Banner -->
        <div class="budget-banner">
            <div class="budget-info">
                <div class="budget-stat">
                    <div class="label">Budget Total</div>
                    <div class="value"><?php echo $budget ? number_format((float) $budget['montant'], 2) : 'Non défini'; ?> TND</div>
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
                <a href="budget-user.php" class="btn" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white;">Gérer le budget</a>
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
                            <div class="aliment-price"><?php echo number_format((float) ($aliment['prix'] ?? 0), 2); ?> TND</div>
                        </div>


                        <form action="add-achat.php" method="POST">
                            <input type="hidden" name="aliment_id" value="<?php echo (int) $aliment['id']; ?>">
                            <input type="hidden" name="prix_unitaire" value="<?php echo htmlspecialchars((string) ((float) ($aliment['prix'] ?? 0))); ?>">
                            
                            <div class="purchase-form">
                                <input type="number" name="quantite" class="qty-input" value="1" min="1" max="99" step="1" required>
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
</div>

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
        
        let url = 'user-achat.php';
        if (search) url += '?search=' + encodeURIComponent(search);
        if (category) url += (search ? '&' : '?') + 'category=' + encodeURIComponent(category);
        
        window.location.href = url;
    }

    function filterByCategory(category) {
        const search = document.getElementById('searchInput').value;
        let url = 'user-achat.php';
        const params = [];
        if (search) params.push('search=' + encodeURIComponent(search));
        if (category) params.push('category=' + encodeURIComponent(category));
        if (params.length) url += '?' + params.join('&');
        window.location.href = url;
    }
</script>
</body>
</html>