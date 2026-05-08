<?php
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

// Handle case where user has no budget
if (!$budget && isset($_GET['no_budget'])) {
    // User tried to access user-achat without budget, show message
    $noBudgetMessage = "Vous devez d'abord définir un budget pour pouvoir effectuer des achats.";
} else {
    $noBudgetMessage = null;
}

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

        .budget-chart-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 24px rgba(45, 90, 39, 0.08);
            border: 1px solid #e8ece9;
            margin-bottom: 2.5rem;
        }

        .budget-chart-card h3 {
            margin-bottom: 1rem;
            color: #1e3d2f;
            font-size: 1.25rem;
        }

        .chart-tabs {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .chart-tab {
            padding: 0.75rem 1.25rem;
            border: 1px solid #d7e3d8;
            border-radius: 999px;
            cursor: pointer;
            background: #f8faf7;
            color: #38603d;
            font-weight: 600;
        }

        .chart-tab.active {
            background: #3dba52;
            border-color: #3dba52;
            color: white;
        }

        .chart-summary {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
            margin-top: 1.5rem;
        }

        .chart-summary-item {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 1rem;
            text-align: center;
        }

        .chart-summary-item strong {
            display: block;
            font-size: 1.2rem;
            color: #1e3d2f;
            margin-bottom: 0.25rem;
        }

        .chart-summary-item p {
            margin: 0;
            color: #5c6b63;
            font-size: 0.9rem;
        }

        .budget-chart-card canvas {
            width: 100% !important;
            max-height: 360px;
            height: 320px !important;
            display: block;
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

        <?php if ($noBudgetMessage): ?>
            <div class="alert alert-error">⚠ <?php echo htmlspecialchars($noBudgetMessage); ?></div>
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

        <!-- Expense Trend Chart -->
        <div class="budget-chart-card">
            <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:1rem; align-items:center; margin-bottom:1rem;">
                <div>
                    <h3>📈 Historique des dépenses</h3>
                    <p style="margin:0; color:#5c6b63;">Visualisez vos dépenses par semaine ou par mois et comparez-les à votre budget.</p>
                </div>
                <div class="chart-tabs">
                    <button type="button" id="weeklyViewBtn" class="chart-tab active" onclick="setTrendView('weekly')">Semaine</button>
                    <button type="button" id="monthlyViewBtn" class="chart-tab" onclick="setTrendView('monthly')">Mois</button>
                </div>
            </div>

            <canvas id="budgetTrendChart" width="800" height="320"></canvas>

            <div class="chart-summary">
                <div class="chart-summary-item">
                    <strong id="chart-max-expense">0 TND</strong>
                    <p>Dépense maximale</p>
                </div>
                <div class="chart-summary-item">
                    <strong id="chart-total-expense">0 TND</strong>
                    <p>Total des dépenses</p>
                </div>
                <div class="chart-summary-item">
                    <strong id="chart-budget-target">0 TND</strong>
                    <p>Budget comparé</p>
                </div>
            </div>
        </div>

        <!-- Shopping List -->
        <div class="shopping-list-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
                <h2>🛒 Liste de courses</h2>
                <a href="user-achat.php?user_id=<?php echo $user_id; ?>" class="btn" style="background: linear-gradient(135deg, #e67e22, #f39c12); color: white; padding: 0.75rem 1.5rem;">+ Acheter des aliments</a>
            </div>
            <div style="display:flex; align-items:center; gap:0.75rem; flex-wrap:wrap; margin-bottom:1rem;">
                <label for="shopping-sort" style="font-weight:600; margin:0;">Trier par :</label>
                <select id="shopping-sort" onchange="changeShoppingSort(this.value)" style="padding:0.5rem; border:1px solid #ddd; border-radius:4px;">
                    <option value="quantite" selected>Quantité</option>
                    <option value="prix_total">Prix Total</option>
                </select>
                <button type="button" id="shopping-sort-order" onclick="toggleShoppingSortDirection()" style="padding:0.5rem 0.75rem; border:none; border-radius:4px; background:#3dba52; color:white; cursor:pointer;">Desc</button>
            </div>

            <?php if (empty($achats)): ?>
                <div class="empty-state">
                    <div class="icon">🛍️</div>
                    <p>Aucun article dans votre liste de courses</p>
                    <p style="margin-top: 1rem; font-size: 0.9rem; color: #5c6b63;">Commencez vos achats en visitant notre boutique d'aliments</p>
                </div>
            <?php else: ?>
                <div id="shopping-items-container"></div>
            <?php endif; ?>
        </div>

    </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const shoppingData = <?php echo empty($achats) ? '[]' : json_encode($achats, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const budgetAmount = <?php echo $budget ? floatval($budget['montant']) : 0; ?>;
    let shoppingSort = { field: 'quantite', direction: 'desc' };
    let trendView = 'weekly';
    let trendChart = null;

    function setTrendView(view) {
        trendView = view;
        document.getElementById('weeklyViewBtn').classList.toggle('active', view === 'weekly');
        document.getElementById('monthlyViewBtn').classList.toggle('active', view === 'monthly');
        renderTrendChart();
    }

    function formatCurrency(value) {
        return parseFloat(value).toFixed(2) + ' TND';
    }

    function getWeeklyLabelsAndTotals(items) {
        const weeks = {};

        items.forEach(item => {
            const date = new Date(item.date_achat);
            if (Number.isNaN(date.getTime())) return;
            const year = date.getFullYear();
            const week = getWeekNumber(date);
            const label = `${year}-S${week.toString().padStart(2, '0')}`;
            weeks[label] = (weeks[label] || 0) + Number(item.prix_total || 0);
        });

        return Object.entries(weeks)
            .sort((a, b) => a[0].localeCompare(b[0]))
            .reduce((acc, [label, value]) => {
                acc.labels.push(label);
                acc.totals.push(parseFloat(value.toFixed ? value.toFixed(2) : Number(value).toFixed(2)));
                return acc;
            }, { labels: [], totals: [] });
    }

    function getMonthlyLabelsAndTotals(items) {
        const months = {};

        items.forEach(item => {
            const date = new Date(item.date_achat);
            if (Number.isNaN(date.getTime())) return;
            const label = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            months[label] = (months[label] || 0) + Number(item.prix_total || 0);
        });

        return Object.entries(months)
            .sort((a, b) => a[0].localeCompare(b[0]))
            .reduce((acc, [label, value]) => {
                acc.labels.push(label);
                acc.totals.push(parseFloat(value.toFixed ? value.toFixed(2) : Number(value).toFixed(2)));
                return acc;
            }, { labels: [], totals: [] });
    }

    function getWeekNumber(date) {
        const d = new Date(Date.UTC(date.getFullYear(), date.getMonth(), date.getDate()));
        const dayNum = d.getUTCDay() || 7;
        d.setUTCDate(d.getUTCDate() + 4 - dayNum);
        const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
        return Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
    }

    function renderTrendChart() {
        const data = trendView === 'weekly'
            ? getWeeklyLabelsAndTotals(shoppingData)
            : getMonthlyLabelsAndTotals(shoppingData);

        const labels = data.labels.length ? data.labels : ['Aucune dépense'];
        const totals = data.totals.length ? data.totals : [0];
        const budgetLine = labels.map(() => trendView === 'weekly' ? parseFloat((budgetAmount / 4).toFixed(2)) : budgetAmount);
        const maxExpense = Math.max(...totals, 0);
        const totalExpense = totals.reduce((sum, value) => sum + value, 0);

        document.getElementById('chart-max-expense').textContent = formatCurrency(maxExpense);
        document.getElementById('chart-total-expense').textContent = formatCurrency(totalExpense);
        document.getElementById('chart-budget-target').textContent = formatCurrency(trendView === 'weekly' ? budgetAmount / 4 : budgetAmount);

        const chartData = {
            labels,
            datasets: [
                {
                    label: 'Dépenses',
                    data: totals,
                    borderColor: '#3dba52',
                    backgroundColor: 'rgba(61, 186, 82, 0.18)',
                    tension: 0.25,
                    fill: true,
                    pointRadius: 4,
                    pointBackgroundColor: '#3dba52'
                },
                {
                    label: 'Objectif budget',
                    data: budgetLine,
                    borderColor: '#ff8c00',
                    backgroundColor: 'rgba(255, 140, 0, 0.12)',
                    borderDash: [6, 4],
                    pointRadius: 0,
                    fill: false
                }
            ]
        };

        const config = {
            type: 'line',
            data: chartData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        callbacks: {
                            label: context => `${context.dataset.label}: ${formatCurrency(context.parsed.y)}`
                        }
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            color: '#5c6b63'
                        },
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        ticks: {
                            color: '#5c6b63',
                            callback: value => `${value} TND`
                        },
                        grid: {
                            color: 'rgba(232, 236, 233, 0.8)'
                        }
                    }
                }
            }
        };

        const ctx = document.getElementById('budgetTrendChart').getContext('2d');
        if (trendChart) {
            trendChart.destroy();
        }
        trendChart = new Chart(ctx, config);
    }

    function changeShoppingSort(field) {
        shoppingSort.field = field;
        renderShoppingList();
    }

    function toggleShoppingSortDirection() {
        shoppingSort.direction = shoppingSort.direction === 'asc' ? 'desc' : 'asc';
        document.getElementById('shopping-sort-order').textContent = shoppingSort.direction === 'asc' ? 'Asc' : 'Desc';
        renderShoppingList();
    }

    function renderShoppingList() {
        const container = document.getElementById('shopping-items-container');
        if (!container || !shoppingData.length) return;
        const items = [...shoppingData];
        items.sort((a, b) => {
            let aValue = Number(a[shoppingSort.field] || 0);
            let bValue = Number(b[shoppingSort.field] || 0);
            if (aValue < bValue) return shoppingSort.direction === 'asc' ? -1 : 1;
            if (aValue > bValue) return shoppingSort.direction === 'asc' ? 1 : -1;
            return 0;
        });
        container.innerHTML = items.map(item => `
            <div class="shopping-item" style="display:flex; justify-content:space-between; align-items:center; gap:1rem; padding:1rem 0; border-bottom:1px solid #eee;">
                <div>
                    <span class="item-name" style="display:block; font-weight:600;">${item.aliment_nom || 'N/A'}</span>
                    <small style="color:#5c6b63;">Quantité: ${item.quantite || 0}</small>
                </div>
                <strong class="item-price">${parseFloat(item.prix_total || 0).toFixed(2)} TND</strong>
            </div>`).join('');
    }

    // Initial render
    if (shoppingData.length > 0) {
        renderShoppingList();
    }
    renderTrendChart();
</script>
</div>
</body>
</html>