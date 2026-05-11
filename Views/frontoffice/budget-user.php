<?php
/**
 * Livrable ranim.7z (nutrismart/css + Views/frontoffice/*) intégré ici.
 * Feuilles chargées depuis css/ranim-budget/ (copie depuis ranim.7z dans htdocs).
 * Connexion par session NutriSmart (pas de user_id en URL).
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    header('Location: login.html?next=' . rawurlencode('budget-user.php'));
    exit;
}

$user_id = (int) $_SESSION['user_id'];

require_once __DIR__ . '/../../Services/BudgetService.php';
require_once __DIR__ . '/../../Services/AchatService.php';
require_once __DIR__ . '/../../Services/UserService.php';

$budgetService = new BudgetService();
$achatService = new AchatService();
$userService = new UserService();

$user = $userService->getUserById($user_id);
if (!$user) {
    die('Utilisateur non trouvé');
}

$budget = $budgetService->getBudgetByUserId($user_id);

if (!$budget && isset($_GET['no_budget'])) {
    $noBudgetMessage = "Vous devez d'abord définir un budget pour pouvoir effectuer des achats.";
} else {
    $noBudgetMessage = null;
}

$totalDepenses = $achatService->getTotalDepensesByUserId($user_id);

$achats = $achatService->getAchatsByUserId($user_id);

$reste = $budget ? ((float) $budget['montant'] - $totalDepenses) : 0.0;
$mMontant = $budget ? (float) $budget['montant'] : 0.0;
$pourcentage = ($budget && $mMontant > 0) ? min(100.0, ($totalDepenses / $mMontant) * 100.0) : 0.0;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NutriSmart — Budget</title>
    <link rel="stylesheet" href="css/ranim-budget/mp-dashboard.css">
    <link rel="stylesheet" href="css/ranim-budget/shared-styles.css">
    <link rel="stylesheet" href="css/ranim-budget/style.css">
    <link rel="stylesheet" href="css/ranim-budget/budget.css">
    <link rel="stylesheet" href="css/shared-styles.css?v=4">
    <style>
        /* Budget-specific styles */
        .budget-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
            background: #f5f5f0;
            min-height: 100%;
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
            background: #fff;
            border-radius: 18px;
            padding: 1.75rem 2rem;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 20px rgba(26, 77, 58, 0.08);
            border: none;
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .budget-form-card .budget-form-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #1a4d3a;
            margin: 0;
            letter-spacing: -0.02em;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Classe dédiée : évite les règles globales .budget-form de ranim-budget/budget.css */
        .budget-monthly-form {
            margin: 0;
            width: 100%;
        }

        .budget-form-row {
            display: flex;
            align-items: center;
            gap: 15px;
            width: 100%;
        }

        .budget-input {
            flex: 1;
            min-width: 0;
            padding: 0.85rem 1rem;
            border: 1px solid #1a4d3a;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            color: #1a4d3a;
            background: #fff;
            transition: border-color 0.2s, box-shadow 0.2s;
            box-sizing: border-box;
        }

        .budget-input::placeholder {
            color: #6b8a7c;
            font-weight: 500;
        }

        .budget-input:focus {
            outline: none;
            border-color: #528b63;
            box-shadow: 0 0 0 3px rgba(82, 139, 99, 0.2);
        }

        .budget-submit-btn {
            flex-shrink: 0;
            padding: 0.85rem 1.5rem;
            border: none;
            border-radius: 999px;
            background: #528b63;
            color: #fff;
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            box-sizing: border-box;
            white-space: nowrap;
            transition: filter 0.2s, transform 0.15s;
        }

        .budget-submit-btn:hover {
            filter: brightness(1.05);
        }

        .budget-submit-btn:active {
            transform: scale(0.99);
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

            .budget-form-row {
                flex-direction: column;
                align-items: stretch;
            }

            .budget-submit-btn {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
<div class="app app--fo-topnav">

<div id="site-nav-root"></div>

<main class="main">
    <div class="budget-page">

        <header class="main-header">
            <div>
                <h1 class="serif">💰 Budget & Courses</h1>
                <p class="date">Gestion des dépenses alimentaires</p>
            </div>
        </header>

        <?php if (!empty($_GET['success'])): ?>
            <div class="alert alert-success">✓ Budget enregistré avec succès !</div>
        <?php elseif (!empty($_GET['error'])): ?>
            <div class="alert alert-error">⚠ <?php echo htmlspecialchars((string) $_GET['error']); ?></div>
        <?php endif; ?>

        <?php if ($noBudgetMessage): ?>
            <div class="alert alert-error">⚠ <?php echo htmlspecialchars($noBudgetMessage); ?></div>
        <?php endif; ?>

        <!-- Budget Form -->
        <div class="budget-form-card">
            <h3 class="budget-form-title"><span aria-hidden="true">🎯</span> Définir votre budget mensuel</h3>
            <form action="add.budget.php" method="POST" class="budget-monthly-form" autocomplete="off">
                <div class="budget-form-row">
                    <input type="number" class="budget-input" name="budget" inputmode="decimal" placeholder="Montant en TND" step="0.01" min="0" required value="<?php echo $budget ? htmlspecialchars((string) $budget['montant']) : ''; ?>">
                    <button type="submit" class="budget-submit-btn">Enregistrer le budget</button>
                </div>
            </form>
        </div>

        <!-- Hero Section -->
        <div class="budget-hero-section">
            <div class="budget-hero-card">
                <span class="badge">📊 Suivi Actif</span>
                <h2>Gestion Budget Alimentaire</h2>
                <div class="budget-tags">
                    <span>Budget: <?php echo $budget ? number_format($mMontant, 2) . ' TND' : 'Non défini'; ?></span>
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
                <a href="user-achat.php" class="btn" style="background: linear-gradient(135deg, #e67e22, #f39c12); color: white; padding: 0.75rem 1.5rem;">+ Acheter des aliments</a>
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
    const budgetAmount = <?php echo $budget ? $mMontant : 0; ?>;
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
<script src="js/avatar-utils.js"></script>
<script src="js/site-nav-loader.js"></script>
</div>
</body>
</html>