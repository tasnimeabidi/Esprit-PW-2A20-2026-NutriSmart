<?php
include_once '../../controllers/SuiviController.php';
$controller = new SuiviController();
$logs = $controller->listLogs();

// Basic stats calculation for demo
$totalConsumed = 0;
$totalBurned = 0;
$weeklyData = [];
$logsList = [];

while($row = $logs->fetch(PDO::FETCH_ASSOC)) {
    $logsList[] = $row;
    if($row['type'] === 'meal') $totalConsumed += $row['calories'];
    if($row['type'] === 'activity') $totalBurned += $row['calories'];
}

// AI Analysis Logic
$deficit = $totalConsumed - $totalBurned;
$goal = 2100;
$remaining = $goal - $totalConsumed;
$progressPercent = min(100, ($totalConsumed / $goal) * 100);

$aiTip = "Continuez comme ça !";
if($remaining < 200) $aiTip = "Attention, vous approchez de votre limite calorique. Privilégiez l'eau et les fibres ce soir.";
if($totalBurned > 500) $aiTip = "Bravo pour votre activité physique ! Pensez à augmenter votre apport en protéines pour la récupération.";
if($totalConsumed < 1200 && date('H') > 18) $aiTip = "Votre apport est faible pour aujourd'hui. Un dîner nutritif est recommandé pour éviter la fatigue demain.";

// Get Weight History for Chart (Dynamic)
$db = $controller->getDb();
$weightLogs = $db->prepare("SELECT poids, date_mesure FROM journal_poids WHERE id_utilisateur = 1 ORDER BY date_mesure DESC, id DESC LIMIT 7");
$weightLogs->execute();
$chartWeights = array_reverse($weightLogs->fetchAll(PDO::FETCH_ASSOC));

$logs = $controller->listLogs();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Intelligent — NutriSmart</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400;0,700;0,900;1,400&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/shared-styles.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
            --primary: #4CAF50;
            --mid: #8BC34A;
            --lime: #C4D4A8;
            --orange: #F2994A;
            --peach: #FBC49A;
            --forest: #2D6A2D;
            --gray: #7A7A7A;
            --cream: #F7F3EC;
            --white: #ffffff;
            --sand: #F9F7F2;
            --ai-glow: rgba(76, 175, 80, 0.4);
        }

        body {
            background: var(--cream);
            color: #333;
            overflow-x: hidden;
        }

        /* ── HERO ── */
        .stats-hero {
            padding: 8rem 5rem 5rem;
            text-align: left;
            position: relative;
            background: linear-gradient(135deg, rgba(45, 106, 45, 0.03) 0%, rgba(247, 243, 236, 0.9) 100%);
            display: flex;
            justify-content: space-between;
            align-items: center;
            overflow: hidden;
        }

        .stats-hero::before {
            content: "";
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, var(--ai-glow) 0%, transparent 70%);
            opacity: 0.3;
            filter: blur(40px);
            z-index: 0;
        }

        .hero-text {
            max-width: 600px;
            z-index: 1;
        }

        .ai-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(76, 175, 80, 0.1);
            color: var(--forest);
            padding: 0.4rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(76, 175, 80, 0.2);
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(76, 175, 80, 0); }
            100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
        }

        .stats-hero h1 {
            font-family: "Playfair Display", serif;
            font-size: clamp(2.8rem, 6vw, 4.2rem);
            font-weight: 900;
            color: var(--forest);
            line-height: 1.1;
        }

        .stats-hero h1 em {
            color: var(--orange);
            font-style: italic;
        }

        /* ── DASHBOARD GRID ── */
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(12, 1fr);
            gap: 1.5rem;
            padding: 0 5rem 6rem;
            max-width: 1440px;
            margin: 0 auto;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            border-radius: 2rem;
            padding: 2.2rem;
            box-shadow: 0 8px 32px rgba(45, 106, 45, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.5);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 45px rgba(45, 106, 45, 0.12);
            background: var(--white);
        }

        .col-4 { grid-column: span 4; }
        .col-8 { grid-column: span 8; }
        .col-6 { grid-column: span 6; }
        .col-12 { grid-column: span 12; }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-family: "Playfair Display", serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: var(--forest);
        }

        .ai-insight {
            background: rgba(76, 175, 80, 0.08);
            padding: 1rem 1.5rem;
            border-radius: 1.2rem;
            font-size: 0.9rem;
            color: var(--forest);
            border-left: 4px solid var(--primary);
            margin-top: auto;
            line-height: 1.5;
        }

        /* ── DUAL LOGGER ── */
        .logger-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 1rem;
        }

        .logger-box {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.5rem;
            border-radius: 1.5rem;
            transition: 0.3s;
        }

        .logger-box:hover {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--lime);
        }

        .logger-label {
            font-size: 0.8rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--lime);
            margin-bottom: 1rem;
            display: block;
        }

        .input-group {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .logger-field {
            background: rgba(0,0,0,0.2);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 1rem;
            padding: 0.8rem 1rem;
            color: white;
            outline: none;
            width: 100%;
        }

        .predict-box {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: var(--white);
            color: var(--forest);
            border-radius: 1rem;
            animation: fadeIn 0.3s ease;
        }

        .ai-calculating {
            display: none;
            font-size: 0.8rem;
            color: var(--lime);
            font-style: italic;
            margin-top: 0.5rem;
        }

        /* ── AI SMART LOGGER ── */
        .smart-logger {
            background: var(--forest);
            color: var(--white);
            grid-column: span 12;
            padding: 3rem;
            border-radius: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 2rem;
            margin-bottom: 2rem;
            position: relative;
            overflow: hidden;
        }

        .smart-logger::before {
            content: "";
            position: absolute;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(139, 195, 74, 0.2) 0%, transparent 70%);
            bottom: -150px;
            left: -100px;
        }

        .logger-content { width: 50%; }
        .logger-content h2 { font-family: "Playfair Display", serif; font-size: 2rem; margin-bottom: 0.5rem; }

        .logger-input-wrapper {
            width: 45%;
            position: relative;
        }

        .logger-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.2);
            padding: 1.2rem 1.5rem;
            border-radius: 3rem;
            color: white;
            font-size: 1rem;
            backdrop-filter: blur(10px);
            outline: none;
            transition: all 0.3s;
        }

        .logger-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: var(--lime);
        }

        .logger-btn {
            background: var(--lime);
            color: var(--forest);
            border: none;
            padding: 0.8rem 1.4rem;
            border-radius: 2rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            display: block;
            margin-top: 10px;
        }

        .logger-btn:hover { background: var(--white); transform: scale(1.02); }

        /* ── CHARTS ── */
        .calorie-viz {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .progress-ring {
            width: 140px;
            height: 140px;
            position: relative;
            display: grid;
            place-items: center;
        }

        .ring-svg { transform: rotate(-90deg); }
        .ring-bg { fill: none; stroke: var(--sand); stroke-width: 10; }
        .ring-fill { 
            fill: none; 
            stroke: var(--primary); 
            stroke-width: 10; 
            stroke-linecap: round;
            stroke-dasharray: 376.8; /* 2 * PI * 60 */
            stroke-dashoffset: 75.36; /* 80% */
            transition: stroke-dashoffset 1.5s ease-in-out;
        }

        .ring-inner-text {
            position: absolute;
            text-align: center;
        }

        .ring-val { font-family: "Playfair Display", serif; font-size: 2rem; font-weight: 900; color: var(--forest); display: block; }
        .ring-lbl { font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--gray); }

        /* ── PREDICTIVE TREND ── */
        .trend-chart {
            height: 220px;
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            padding: 2rem 1rem 0;
            position: relative;
        }

        .bar-group {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.8rem;
            max-width: 50px;
        }

        .bar-container {
            width: 100%;
            background: var(--sand);
            height: 160px;
            border-radius: 2rem;
            display: flex;
            align-items: flex-end;
            overflow: hidden;
            position: relative;
        }

        .bar-actual {
            width: 100%;
            background: linear-gradient(to top, var(--primary), var(--mid));
            border-radius: 2rem;
            transition: height 1s ease-out;
        }

        .bar-predict {
            width: 100%;
            background: var(--peach);
            border-radius: 2rem;
            height: 0;
            opacity: 0.6;
            transition: height 1s 0.5s ease-out;
            position: absolute;
            bottom: 0;
        }

        .bar-group.prediction .bar-actual { opacity: 0.3; }
        .bar-group.prediction .bar-predict { height: 100%; }

        .day-label { font-size: 0.75rem; font-weight: 600; color: var(--gray); font-family: "DM Sans", sans-serif; }

        /* ── ACTIVITY FEED ── */
        .activity-feed { display: flex; flex-direction: column; gap: 1rem; }
        .act-card {
            background: var(--sand);
            padding: 1.2rem;
            border-radius: 1.2rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: 0.2s;
        }
        .act-card:hover { transform: translateX(8px); background: #eeebe3; }

        .act-icon-box {
            width: 48px;
            height: 48px;
            background: white;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }

        .act-desc { flex: 1; }
        .act-desc h4 { font-size: 0.95rem; font-weight: 700; color: var(--forest); }
        .act-desc p { font-size: 0.8rem; color: var(--gray); margin-top: 2px; }

        .act-cal { text-align: right; }
        .cal-val { font-family: "Playfair Display", serif; font-weight: 800; color: var(--orange); font-size: 1.1rem; }
        .cal-lbl { font-size: 0.65rem; color: var(--gray); text-transform: uppercase; }

        /* ── AI FLOATING CHAT ── */
        .ai-chat-toggle {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 70px;
            height: 70px;
            background: var(--forest);
            border-radius: 50%;
            display: grid;
            place-items: center;
            cursor: pointer;
            box-shadow: 0 10px 30px rgba(45, 106, 45, 0.3);
            z-index: 1000;
            transition: all 0.3s;
        }

        .ai-chat-toggle:hover {
            transform: scale(1.1) rotate(5deg);
            background: var(--primary);
        }

        .chat-indicator {
            position: absolute;
            top: 2px;
            right: 2px;
            width: 14px;
            height: 14px;
            background: var(--orange);
            border: 2px solid var(--white);
            border-radius: 50%;
        }

        .ai-chat-box {
            position: fixed;
            bottom: 6.5rem;
            right: 2rem;
            width: 380px;
            height: 500px;
            background: var(--white);
            border-radius: 2rem;
            box-shadow: 0 20px 60px rgba(0,0,0,0.15);
            display: none;
            flex-direction: column;
            overflow: hidden;
            z-index: 1000;
            border: 1px solid rgba(45, 106, 45, 0.1);
        }

        .chat-header {
            background: var(--forest);
            color: white;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .bot-avatar { width: 40px; height: 40px; background: var(--lime); border-radius: 12px; display: grid; place-items: center; font-size: 1.2rem; }

        .chat-messages {
            flex: 1;
            padding: 1.5rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .msg { max-width: 80%; padding: 1rem; border-radius: 1.2rem; font-size: 0.9rem; line-height: 1.5; }
        .msg.bot { background: var(--sand); color: #333; border-bottom-left-radius: 2px; }
        .msg.user { background: var(--primary); color: white; align-self: flex-end; border-bottom-right-radius: 2px; }

        .chat-input {
            padding: 1.2rem;
            border-top: 1px solid #eee;
            display: flex;
            gap: 0.8rem;
        }

        .chat-input input {
            flex: 1;
            border: none;
            background: var(--sand);
            padding: 0.8rem 1.2rem;
            border-radius: 1.5rem;
            outline: none;
        }

        .send-btn { 
            background: var(--forest); 
            color: white; 
            border: none; 
            width: 40px; 
            height: 40px; 
            border-radius: 50%; 
            cursor: pointer; 
        }

        /* ── MODAL ── */
        .modal {
            display: none; position: fixed; z-index: 1000; left: 0; top: 0;
            width: 100%; height: 100%; background: rgba(0,0,0,0.8);
            backdrop-filter: blur(4px); place-items: center;
        }
        .modal-content {
            background: var(--white); border: 1px solid #ddd;
            border-radius: 1.5rem; padding: 2rem; width: 400px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: fadeIn .3s ease both;
        }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; }
        .modal-title { font-size: 1.2rem; font-weight: 800; color: var(--forest); }
        .close-modal { cursor: pointer; font-size: 1.5rem; color: var(--gray); }

        .edit-form { display: flex; flex-direction: column; gap: 1rem; }
        .f-group { display: flex; flex-direction: column; gap: 0.3rem; }
        .f-group label { font-size: 0.75rem; font-weight: 700; color: var(--gray); }
        .f-input {
            border: 1px solid #ddd; border-radius: 0.8rem; padding: 0.75rem;
            font-family: inherit; font-size: 0.9rem; outline: none;
        }
        .f-input:focus { border-color: var(--primary); }

        @media (max-width: 1100px) {
            .dashboard-grid { padding: 0 2rem 4rem; }
            .col-4, .col-8, .col-6 { grid-column: span 12; }
            .smart-logger { flex-direction: column; align-items: flex-start; }
            .logger-content, .logger-input-wrapper { width: 100%; }
            .stats-hero { padding: 8rem 2rem 4rem; }
        }
    </style>
</head>
<body>
    <!-- NAV -->
    <nav id="navbar">
        <a href="nutrismart-website.html" class="nav-logo">
            <svg width="34" height="34" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg" style="overflow: visible">
                <mask id="biteMask">
                    <rect x="-20" y="-20" width="140" height="140" fill="white" />
                    <circle cx="92" cy="35" r="18" fill="black" />
                    <circle cx="84" cy="62" r="14" fill="black" />
                </mask>
                <g mask="url(#biteMask)">
                    <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#4a7c59" />
                    <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8fbc8f" />
                </g>
                <path d="M 22 78 L 12 92" stroke="#4a7c59" stroke-width="7" stroke-linecap="round" />
            </svg>
            <div><span style="color: #4a7c59">Nutri</span><span style="color: #8fbc8f">Smart</span></div>
        </a>
        <ul class="nav-links">
            <li><a href="nutrismart-website.html">Accueil</a></li>
            <li><a href="suivi-statistiques.html" class="active">Suivi et Statistiques</a></li>
            <li><a href="profile.html">Profil</a></li>
            <li><a href="recette.html">Recettes</a></li>
            <li><a href="contact.html">Contact</a></li>
            <li><a href="../backoffice/nutrismart-dashboard.php" style="color: var(--orange); font-weight: 700;">Dashboard Admin</a></li>
        </ul>
        <div class="nav-auth">
            <a href="register.html" class="nav-cta">Commencer gratuitement</a>
        </div>
    </nav>

    <!-- HERO -->
    <header class="stats-hero">
        <div class="hero-text">
            <div class="ai-badge">✦ Intelligence Artificielle Active</div>
            <h1>Boostez votre motivation avec le <em>Suivi Intelligent.</em></h1>
            <p>Notre IA analyse vos données quotidiennes pour vous offrir des prévisions précises et des conseils nutritionnels personnalisés en temps réel.</p>
        </div>
        <div class="hero-visual">
            <button onclick="exportDashboard()" class="logger-btn" style="background: var(--white); color: var(--forest); border: 1px solid var(--forest); display: flex; align-items: center; gap: 8px; font-size: 0.9rem; padding: 0.8rem 1.5rem;">
                <span>📄 Exporter mon Rapport PDF</span>
            </button>
        </div>
    </header>

    <!-- SMART LOGGER -->
    <div style="padding: 0 5rem">
        <section class="smart-logger">
            <div class="logger-content">
                <h2>Enregistrement Intelligent ✦</h2>
                <p>Décrivez votre journée. Notre IA calcule instantanément l'impact sur votre objectif.</p>
                <div id="aiCoach" class="ai-insight" style="margin-top: 2rem; background: rgba(255,255,255,0.1); color: white; border-color: var(--lime);">
                    ✨ <strong>Statut du jour :</strong> Vous avez consommé <?php echo $totalConsumed; ?> kcal. Il vous reste encore de la marge pour un dîner équilibré !
                </div>
            </div>

            <div class="logger-input-wrapper" style="width: 60%;">
                <div class="logger-container">
                    <!-- Meal Logger -->
                    <div class="logger-box" id="mealBox">
                        <span class="logger-label">🍎 Journal Alimentaire</span>
                        <div class="input-group">
                            <input type="text" id="foodInput" class="logger-field" placeholder="Qu'avez-vous mangé ?">
                            <input type="number" id="qtyInput" class="logger-field" placeholder="Qté (g/ml)" style="width: 100px;">
                        </div>
                        <button onclick="analyzeAI('meal')" class="logger-btn" style="position: static; transform: none; width: 100%;">Analyser le repas</button>
                        <div id="mealAI" class="ai-calculating">🧬 L'IA analyse les nutriments...</div>
                        <div id="mealResult" class="predict-box">
                            <div>Prédiction IA: <strong id="predCal">450</strong> kcal</div>
                            <button onclick="saveLog('meal')" class="btn-primary" style="margin-top: 0.5rem; padding: 0.4rem 1rem; border-radius: 0.5rem; width: 100%; cursor: pointer;">Confirmer & Sauvegarder</button>
                        </div>
                    </div>

                    <!-- Sport Logger -->
                    <div class="logger-box" id="sportBox">
                        <span class="logger-label">⚡ Journal Sportif</span>
                        <div class="input-group">
                            <input type="text" id="sportInput" class="logger-field" placeholder="Quel sport ?">
                            <input type="number" id="durInput" class="logger-field" placeholder="Min" style="width: 80px;">
                        </div>
                        <button onclick="analyzeAI('sport')" class="logger-btn" style="background: var(--orange); color: white; width: 100%;">Évaluer l'effort</button>
                        <div id="sportAI" class="ai-calculating">🔥 Calcul de la dépense calorique...</div>
                        <div id="sportResult" class="predict-box">
                            <div>Dépense IA: <strong id="predBurn">-320</strong> kcal</div>
                            <button onclick="saveLog('activity')" class="btn-primary" style="margin-top: 0.5rem; background: var(--orange); border: none; padding: 0.7rem 1rem; border-radius: 0.5rem; width: 100%; cursor: pointer;">Enregistrer l'activité</button>
                        </div>
                    </div>

                </div>
            </div>
        </section>
    </div>

    <!-- DASHBOARD -->
    <main class="dashboard-grid">
        <!-- Calorie viz -->
        <div class="stat-card col-4">
            <div class="card-header">
                <h3 class="card-title">Calories Actuelles</h3>
                <div class="card-icon">🥗</div>
            </div>
            <div class="calorie-viz">
                <div class="progress-ring">
                    <svg class="ring-svg" width="140" height="140">
                        <circle class="ring-bg" cx="70" cy="70" r="60" />
                        <circle class="ring-fill" cx="70" cy="70" r="60" style="stroke-dashoffset: <?php echo 376.8 - (376.8 * ($progressPercent/100)); ?>;" />
                    </svg>
                    <div class="ring-inner-text">
                        <span class="ring-val"><?php echo number_format($totalConsumed); ?></span>
                        <span class="ring-lbl">Consommées</span>
                    </div>
                </div>
                <div style="text-align: center;">
                    <p style="font-size: 0.9rem; font-weight: 700; color: var(--forest);">Objectif: <?php echo $goal; ?> kcal</p>
                    <p style="font-size: 0.75rem; color: var(--gray);">Reste: <?php echo max(0, $remaining); ?> kcal</p>
                </div>
            </div>
            <div class="ai-insight">
                💡 <strong>Conseil IA:</strong> <?php echo $aiTip; ?>
            </div>
        </div>

        <!-- Predictive Weight -->
        <div class="stat-card col-8">
            <div class="card-header">
                <div style="display: flex; flex-direction: column;">
                    <h3 class="card-title">Courbe de Poids & Prévisions</h3>
                    <?php if(!empty($chartWeights)): ?>
                        <div style="font-size: 2.2rem; font-weight: 900; color: var(--forest); font-family: 'Playfair Display', serif;">
                            <?php echo number_format(end($chartWeights)['poids'], 2); ?> <span style="font-size: 1rem; font-weight: 600; color: var(--gray);">kg</span>
                        </div>
                    <?php endif; ?>
                </div>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="promptWeight()" style="background: var(--forest); color: white; border: none; padding: 6px 12px; border-radius: 8px; font-size: 0.75rem; cursor: pointer; font-weight: 700; box-shadow: 0 4px 10px rgba(45, 106, 45, 0.2);">⚖️ Étalonner</button>
                    <div class="card-icon">📈</div>
                </div>
            </div>
            <div class="trend-chart">
                <?php 
                $days = ['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'];
                if(empty($chartWeights)): ?>
                    <!-- NO DATA STATE: Entry Point -->
                    <div style="grid-column: 1 / -1; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem; width: 100%; height: 100%;">
                        <div style="font-size: 2.5rem;">⚖️</div>
                        <p style="color: var(--gray); font-size: 0.9rem;">Prêt à démarrer ? Entrez votre poids de départ.</p>
                        <button onclick="promptWeight()" style="background: var(--forest); color: white; border: none; padding: 0.8rem 1.5rem; border-radius: 1rem; cursor: pointer; font-weight: 700; transition: 0.3s;" onmouseover="this.style.background='var(--primary)'" onmouseout="this.style.background='var(--forest)'">
                            Définir mon poids initial
                        </button>
                    </div>
                <?php 
                else:
                    foreach($chartWeights as $w): 
                        $h = min(100, ($w['poids'] / 120) * 100); // Scale 0-120kg
                        $dayNum = date('w', strtotime($w['date_mesure']));
                ?>
                <div class="bar-group">
                    <div class="bar-container" title="<?php echo $w['poids']; ?> kg">
                        <div class="bar-actual" style="height: <?php echo $h; ?>%;"></div>
                    </div>
                    <span class="day-label"><?php echo $days[$dayNum]; ?></span>
                </div>
                <?php endforeach; ?>
                
                <!-- Prediction Bar Area -->
                <div class="bar-group prediction">
                    <div class="bar-container" title="Prédiction IA"><div class="bar-predict" style="height: 60%;"></div></div>
                    <span class="day-label">IA</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="ai-insight" style="background: rgba(242, 153, 74, 0.08); border-color: var(--orange);">
                🔭 <strong>Prédiction:</strong> En maintenant ce rythme, vous atteindrez votre objectif de <strong>72kg</strong> dans 14 jours.
            </div>
        </div>

        <!-- Macros -->
        <div class="stat-card col-6">
            <div class="card-header">
                <h3 class="card-title">Nutriments IA</h3>
                <div class="card-icon">🧬</div>
            </div>
            <div class="macro-bars">
                <div class="macro-item">
                    <div class="macro-top"><span>Protéines (Cible +10g)</span><span>85g</span></div>
                    <div class="bar-outer"><div class="bar-inner" style="width: 70%; background: var(--primary);"></div></div>
                </div>
                <div class="macro-item">
                    <div class="macro-top"><span>Glucides (Stable)</span><span>210g</span></div>
                    <div class="bar-outer"><div class="bar-inner" style="width: 84%; background: var(--orange);"></div></div>
                </div>
                <div class="macro-item">
                    <div class="macro-top"><span>Lipides (Baisse conseillée)</span><span>45g</span></div>
                    <div class="bar-outer"><div class="bar-inner" style="width: 64%; background: var(--peach);"></div></div>
                </div>
            </div>
        </div>

        <!-- Activity -->
        <div class="stat-card col-6">
            <div class="card-header">
                <h3 class="card-title">Activités Analysées</h3>
                <div class="card-icon">⚡</div>
            </div>
            <div class="activity-feed">
                <?php foreach(array_reverse($logsList) as $row): ?>
                <div class="act-card" id="log-<?php echo $row['id']; ?>">
                    <div class="act-icon-box"><?php echo $row['type'] == 'meal' ? '🥗' : ($row['type'] == 'weight' ? '⚖️' : '🏃‍♂️'); ?></div>
                    <div class="act-desc">
                        <h4><?php echo htmlspecialchars($row['description']); ?></h4>
                        <p><?php echo date('d M', strtotime($row['date'])); ?> • <?php echo ucfirst($row['type'] == 'weight' ? 'Poids' : ($row['type'] == 'meal' ? 'Repas' : 'Sport')); ?></p>
                    </div>
                    <div class="act-cal">
                        <span class="cal-val" style="color: <?php echo $row['type'] == 'meal' ? 'var(--orange)' : ($row['type'] == 'weight' ? 'var(--forest)' : 'var(--primary)'); ?>">
                            <?php 
                                if($row['type'] == 'weight') echo 'Impact';
                                else echo ($row['type'] == 'meal' ? '+' : '-') . $row['calories']; 
                            ?>
                        </span>
                        <div class="cal-lbl"><?php echo $row['type'] == 'weight' ? 'Relatif' : 'kcal'; ?></div>
                    </div>
                    <div class="act-actions" style="margin-left: 10px; display: flex; gap: 8px;">
                        <button onclick="editLog(<?php echo $row['id']; ?>, '<?php echo $row['type']; ?>', '<?php echo addslashes($row['description']); ?>', <?php echo $row['calories']; ?>)" style="background: none; border: none; cursor: pointer; opacity: 0.6; font-size: 1.1rem;">✏️</button>
                        <button onclick="deleteLog(<?php echo $row['id']; ?>, '<?php echo $row['type']; ?>')" style="background: none; border: none; cursor: pointer; opacity: 0.5; font-size: 1.1rem;">🗑️</button>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if(empty($logsList)): ?>
                    <p style="text-align: center; color: var(--gray); padding: 2rem;">Aucun log pour le moment. Commencez par enregistrer un repas !</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- AI CHAT FLOATING -->
    <div class="ai-chat-toggle" onclick="toggleChat()">
        <span style="font-size: 2rem">🤖</span>
        <div class="chat-indicator"></div>
    </div>

    <div class="ai-chat-box" id="aiChat">
        <div class="chat-header">
            <div class="bot-avatar">✦</div>
            <div>
                <h4 style="font-weight: 800">NutriBot IA</h4>
                <p style="font-size: 0.75rem; opacity: 0.8">En ligne • Votre Expert Santé</p>
            </div>
        </div>
        <div class="chat-messages">
            <div class="msg bot">
                Bonjour ! Je suis NutriBot. J'ai analysé vos activités d'aujourd'hui. Vous avez brillamment dépensé 890 kcal ! 
                Voulez-vous que je vous suggère un menu pour demain ?
            </div>
            <div class="msg user">Oui, s'il te plaît. Quelque chose de riche en protéines.</div>
            <div class="msg bot">
                Entendu ! Je vous suggère un <strong>Saumon grillé au citron</strong> accompagné de <strong>quinoa</strong> et d'asperges. 
                Cela correspond parfaitement à votre besoin de récupération.
            </div>
        </div>
        <div class="chat-input">
            <input type="text" placeholder="Posez une question à l'IA...">
            <button class="send-btn">➔</button>
        </div>
    </div>

    <!-- FOOTER -->
    <footer id="contact">
      <div class="footer-inner">
        <div class="footer-brand">
          <div class="footer-logo">
            <svg width="34" height="34" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
              <mask id="biteMaskFooter">
                <rect x="-20" y="-20" width="140" height="140" fill="white" />
                <circle cx="92" cy="35" r="18" fill="black" />
                <circle cx="84" cy="62" r="14" fill="black" />
              </mask>
              <g mask="url(#biteMaskFooter)">
                <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#4a7c59" />
                <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8fbc8f" />
              </g>
              <path d="M 22 78 L 12 92" stroke="#4a7c59" stroke-width="7" stroke-linecap="round" />
            </svg>
            <span style="margin-left: 10px">NutriSmart</span>
          </div>
          <p class="footer-desc">Votre plateforme de nutrition intelligente et personnalisée. Mangez mieux, vivez pleinement.</p>
        </div>
        <div class="footer-col">
          <h5>Plateforme</h5>
          <ul>
            <li><a href="nutrismart-website.html#features">Fonctionnalités</a></li>
            <li><a href="nutrismart-website.html#how-it-works">Comment ça marche</a></li>
            <li><a href="nutrismart-website.html#modules">Modules</a></li>
          </ul>
        </div>
        <div class="footer-col">
          <h5>Ressources</h5>
          <ul>
            <li><a href="recette.html">Recettes</a></li>
            <li><a href="suivi-statistiques.html">Suivi de progrès</a></li>
            <li><a href="contact.html">Support</a></li>
          </ul>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© 2026 NutriSmart — Tous droits réservés</span>
        <div class="footer-bottom-links">
          <a href="#">Confidentialité</a>
          <a href="#">CGU</a>
          <a href="contact.html">Contact</a>
        </div>
      </div>
    </footer>

    <!-- EDIT MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Modifier l'entrée</h3>
                <span class="close-modal" onclick="closeEditModal()">&times;</span>
            </div>
            <form id="editLogForm" class="edit-form">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="editLogId">
                <input type="hidden" name="type" id="editLogType">
                <input type="hidden" name="user_id" value="1">
                
                <div class="f-group">
                    <label>Description</label>
                    <input type="text" name="description" id="editLogDesc" class="f-input">
                </div>
                <div class="f-group">
                    <label>Calories (kcal)</label>
                    <input type="number" name="calories" id="editLogCals" class="f-input">
                </div>
                <button type="submit" class="logger-btn" style="width: 100%; margin-top: 1rem;">Mettre à jour 🔄</button>
            </form>
        </div>
    </div>

    <!-- DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="modal">
        <div class="modal-content" style="max-width: 400px; text-align: center; background: white; color: #333; padding: 2rem; border-radius: 1.5rem;">
            <div class="modal-header" style="border-bottom: none; justify-content: center; padding-bottom: 0.5rem;">
                <h3 class="modal-title" style="color: #2c4c3e; font-size: 1.5rem;">Confirmer la suppression ?</h3>
            </div>
            <p style="margin: 1rem 0; color: #666; font-size: 1rem; line-height: 1.5;">Cette action est irréversible. Voulez-vous vraiment supprimer ce log de votre historique ?</p>
            <div style="display: flex; gap: 12px; justify-content: center; margin-top: 1.5rem;">
                <button id="confirmDeleteBtn" class="logger-btn" style="background: #e74c3c; color: white; width: 130px; position: static; transform: none; border-radius: 0.8rem; box-shadow: 0 4px 15px rgba(231,76,60,0.2);">Supprimer</button>
                <button onclick="closeDeleteModal()" class="logger-btn" style="background: #f1f1f1; color: #444; width: 130px; position: static; transform: none; border-radius: 0.8rem;">Annuler</button>
            </div>
        </div>
    </div>

    <script>
        function toggleChat() {
            const chat = document.getElementById('aiChat');
            chat.style.display = chat.style.display === 'flex' ? 'none' : 'flex';
        }

        const aiDatabase = {
            foods: { "pomme": 0.52, "apple": 0.52, "quinoa": 1.2, "poulet": 2.39, "chicken": 2.39, "riz": 1.3, "rice": 1.3, "oeuf": 1.55, "egg": 1.55, "burger": 2.9, "pizza": 2.6, "salade": 0.15, "salad": 0.15, "spaghetti": 1.5, "pasta": 1.5 },
            sports: { "course": 10, "run": 10, "velo": 7, "cycling": 7, "musculation": 5, "gym": 6, "natation": 8, "swim": 8, "marche": 4, "walk": 4, "tennis": 7 }
        };

        let lastPrediction = { type: '', desc: '', qty: 0, cal: 0 };

        function analyzeAI(type) {
            const loader = document.getElementById(type + 'AI');
            const result = document.getElementById(type + 'Result');
            const inputVal = document.getElementById(type === 'meal' ? 'foodInput' : 'sportInput').value.toLowerCase();
            const qtyVal = parseFloat(document.getElementById(type === 'meal' ? 'qtyInput' : 'durInput').value) || 0;

            console.log("Analyzing:", type, inputVal, qtyVal);

            if(!inputVal || qtyVal <= 0) { 
                alert("Veuillez remplir la description et la quantité/durée."); 
                return; 
            }
            
            // Contrôle de saisie
            const disallowed = ['car', 'voiture', 'avion', 'plane'];
            if (disallowed.some(word => inputVal.includes(word))) {
                alert("Erreur: Veuillez entrer une description d'aliment ou d'activité valide (pas d'objets).");
                return;
            }

            result.style.display = 'none';
            loader.style.display = 'block';

            setTimeout(() => {
                loader.style.display = 'none';
                let calResult = 0;
                
                if(type === 'meal') {
                    const foundKey = Object.keys(aiDatabase.foods).find(f => inputVal.includes(f));
                    const baseCal = aiDatabase.foods[foundKey] || 1.2; // Default to 1.2 kcal/g if not in DB
                    calResult = Math.round(baseCal * qtyVal);
                    document.getElementById('predCal').innerText = calResult;
                } else {
                    const foundKey = Object.keys(aiDatabase.sports).find(s => inputVal.includes(s));
                    const baseBurn = aiDatabase.sports[foundKey] || 7; // Default to 7 kcal/min
                    calResult = Math.round(baseBurn * qtyVal);
                    document.getElementById('predBurn').innerText = calResult;
                }

                lastPrediction = { type, desc: inputVal, qty: qtyVal, cal: calResult };
                result.style.display = 'block';
                console.log("Prediction success:", lastPrediction);
            }, 800);
        }

        function saveLog(type) {
            const formData = new FormData();
            formData.append('action', 'create');
            formData.append('user_id', '1');
            formData.append('type', type);
            
            if(!lastPrediction.desc) {
                alert("Veuillez d'abord analyser votre entrée.");
                return;
            }
            formData.append('description', lastPrediction.desc);
            formData.append('calories', lastPrediction.cal);
            formData.append('quantite', lastPrediction.qty);
            
            formData.append('date', new Date().toISOString().split('T')[0]);

            fetch('../../controllers/SuiviController.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert("Erreur: " + data.message);
            });
        }

        function promptWeight() {
            const w = prompt("Veuillez entrer votre poids actuel pour étalonner l'IA (kg) :", "70");
            if (w && !isNaN(w)) {
                const formData = new FormData();
                formData.append('action', 'create');
                formData.append('user_id', '1');
                formData.append('type', 'weight');
                formData.append('weight', w);
                formData.append('description', w + ' kg (Étalonnage manuel)');
                formData.append('date', new Date().toISOString().split('T')[0]);

                fetch('../../controllers/SuiviController.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if(data.status === 'success') location.reload();
                    else alert("Erreur: " + data.message);
                });
            }
        }

        let deleteTarget = { id: null, type: null };
        const dModal = document.getElementById('deleteModal');

        function deleteLog(id, type) {
            deleteTarget = { id, type };
            dModal.style.display = 'grid';
        }

        function closeDeleteModal() { dModal.style.display = 'none'; }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if(!deleteTarget.id) return;
            
            const params = new URLSearchParams();
            params.append('action', 'delete');
            params.append('id', deleteTarget.id);
            params.append('type', deleteTarget.type);

            fetch('../../controllers/SuiviController.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params
            })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert("Erreur: " + data.message);
            })
            .catch(err => alert("Erreur de communication avec le serveur."))
            .finally(() => closeDeleteModal());
        });

        // Close delete modal on outside click
        window.onclick = function(event) { 
            if (event.target == eModal) closeEditModal(); 
            if (event.target == dModal) closeDeleteModal();
        }

        const eModal = document.getElementById('editModal');
        function editLog(id, type, desc, cal) {
            document.getElementById('editLogId').value = id;
            document.getElementById('editLogType').value = type;
            document.getElementById('editLogDesc').value = desc || '';
            document.getElementById('editLogCals').value = cal || '';
            eModal.style.display = 'grid';
        }
        function closeEditModal() { eModal.style.display = 'none'; }
        window.onclick = function(event) { if (event.target == eModal) closeEditModal(); }

        document.getElementById('editLogForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const desc = document.getElementById('editLogDesc').value.toLowerCase();
            const disallowed = ['car', 'voiture', 'avion', 'plane'];
            if (disallowed.some(word => desc.includes(word))) {
                alert("Erreur: Veuillez entrer une description valide.");
                return;
            }
            const formData = new FormData(this);
            formData.append('date', new Date().toISOString().split('T')[0]);
            fetch('../../controllers/SuiviController.php', { method: 'POST', body: formData })
            .then(r => r.json())
            .then(data => {
                if(data.status === 'success') location.reload();
                else alert(data.message);
            })
            .catch(err => {
                alert("Erreur de communication avec le serveur.");
            });
        });
        async function exportDashboard() {
            const { jsPDF } = window.jspdf;
            const element = document.querySelector('.dashboard-grid');
            
            // Show loading or hide buttons
            const btns = document.querySelectorAll('.act-actions, .logger-btn');
            btns.forEach(b => b.style.opacity = '0');

            try {
                const canvas = await html2canvas(element, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#F7F3EC'
                });
                
                const imgData = canvas.toDataURL('image/png');
                const pdf = new jsPDF('l', 'mm', 'a4');
                const imgProps = pdf.getImageProperties(imgData);
                const pdfWidth = pdf.internal.pageSize.getWidth();
                const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;
                
                pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
                pdf.save('Rapport_NutriSmart.pdf');
            } catch (err) {
                console.error(err);
                alert("Erreur lors de la génération du PDF.");
            } finally {
                btns.forEach(b => b.style.opacity = '1');
            }
        }
    </script>
</body>
</html>
