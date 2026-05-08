<?php
include_once __DIR__ . '/../../controllers/RecetteController.php';

$message = "";
$status = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new RecetteController();
    $result = $controller->createRecette($_POST, 'pending');
    
    if ($result['status'] === 'success') {
        header("Location: recette.php");
        exit();
    }

    $message = $result['message'];
    $status = $result['status'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Proposer une Recette — NutriSmart</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/shared-styles.css">
  <style>
    body { background: #fdfaf5; }
    
    .page-hero {
      text-align: center;
      padding: 6rem 2rem 4rem;
      background: linear-gradient(rgba(45, 106, 45, 0.03), rgba(242, 153, 74, 0.03));
    }
    
    .page-hero h1 {
      font-family: 'Playfair Display', serif;
      font-size: clamp(2.5rem, 6vw, 4rem);
      color: #2D6A2D;
      margin-bottom: 1rem;
    }
    
    .page-hero p {
      color: #7A7A7A;
      max-width: 600px;
      margin: 0 auto;
      font-size: 1.1rem;
    }

    .form-section {
      padding: 0 5% 6rem;
      margin-top: -2rem;
    }

    .form-card {
      background: #ffffff;
      border-radius: 2rem;
      padding: 4rem;
      box-shadow: 0 30px 80px rgba(45, 106, 45, 0.08);
      max-width: 800px;
      margin: 0 auto;
      border: 1px solid rgba(45, 106, 45, 0.05);
    }

    .form-title {
      font-family: 'Playfair Display', serif;
      font-size: 2rem;
      color: #2D6A2D;
      margin-bottom: 2.5rem;
      text-align: center;
    }

    .form-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }

    .form-group {
      margin-bottom: 1.8rem;
    }

    .form-group.full {
       grid-column: 1 / -1;
    }

    label {
      display: block;
      font-weight: 700;
      font-size: 0.9rem;
      color: #2D6A2D;
      margin-bottom: 0.7rem;
      text-transform: uppercase;
      letter-spacing: 0.03em;
    }

    input, textarea, select {
      width: 100%;
      padding: 1.2rem;
      border-radius: 1rem;
      border: 2px solid #f1f1f1;
      background: #fafafa;
      font-family: 'DM Sans', sans-serif;
      font-size: 1rem;
      transition: all 0.3s;
    }

    input:focus, textarea:focus {
      outline: none;
      border-color: #4CAF50;
      background: #fff;
      box-shadow: 0 10px 25px rgba(76, 175, 80, 0.1);
    }

    .submit-btn {
      width: 100%;
      padding: 1.2rem;
      border-radius: 3rem;
      background: #4CAF50;
      color: white;
      font-weight: 700;
      font-size: 1.1rem;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
      margin-top: 1rem;
      box-shadow: 0 15px 35px rgba(76, 175, 80, 0.25);
    }

    .submit-btn:hover {
      background: #2D6A2D;
      transform: translateY(-3px);
      box-shadow: 0 20px 45px rgba(45, 106, 45, 0.3);
    }

    .alert {
      padding: 1.5rem;
      border-radius: 1rem;
      margin-bottom: 2rem;
      text-align: center;
      font-weight: 600;
    }
    .alert-success {
      background: #e8f5e9;
      color: #2e7d32;
      border: 1px solid #c8e6c9;
    }
    .alert-error {
      background: #ffebee;
      color: #c62828;
      border: 1px solid #ffcdd2;
    }

    .ingredient-row {
      display: grid;
      grid-template-columns: 2fr 1fr 1fr auto;
      gap: 1rem;
      align-items: end;
      margin-bottom: 1rem;
    }

    .ingredient-row input,
    .ingredient-row select {
      margin-bottom: 0;
    }

    .add-btn, .remove-btn {
      padding: 0.8rem 1.2rem;
      border-radius: 0.6rem;
      font-weight: 600;
      font-size: 0.9rem;
      border: none;
      cursor: pointer;
      transition: all 0.3s;
    }

    .add-btn {
      background: #4CAF50;
      color: white;
      width: 100%;
      margin-top: 1rem;
    }

    .add-btn:hover {
      background: #45a049;
    }

    .remove-btn {
      background: #f44336;
      color: white;
      padding: 0.8rem 1rem;
    }

    .remove-btn:hover {
      background: #da190b;
    }

    .step-row {
      display: grid;
      grid-template-columns: auto 1fr auto;
      gap: 1rem;
      align-items: center;
      margin-bottom: 1rem;
    }

    .step-number {
      background: #4CAF50;
      color: white;
      width: 35px;
      height: 35px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
    }

    .section-subtitle {
      font-family: 'Playfair Display', serif;
      font-size: 1.4rem;
      color: #2D6A2D;
      margin: 2rem 0 1rem;
      padding-bottom: 0.5rem;
      border-bottom: 2px solid #e8f5e9;
    }

    @media (max-width: 768px) {
      .ingredient-row {
        grid-template-columns: 1fr;
      }
      
      .remove-btn {
        width: 100%;
      }
    }
  </style>
  <script src="../../js/recette-validation.js" defer></script>
</head>
<body>

<!-- NAV REPLICA -->
<nav id="navbar">
  <div class="logo-container">
    <a href="nutrismart-website.html" class="nav-logo" style="text-decoration:none; display:flex; align-items:center; gap:0.5rem;">
      <svg width="30" height="30" viewBox="0 0 100 100" fill="none" style="overflow:visible">
        <mask id="m"><rect x="-20" y="-20" width="140" height="140" fill="white"/><circle cx="92" cy="35" r="18" fill="black"/><circle cx="84" cy="62" r="14" fill="black"/></mask>
        <g mask="url(#m)">
          <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#4a7c59"/>
          <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8fbc8f"/>
        </g>
        <path d="M 22 78 L 12 92" stroke="#4a7c59" stroke-width="7" stroke-linecap="round"/>
      </svg>
      <div style="font-size:1.7rem; font-weight:700;"><span style="color:#4a7c59">Nutri</span><span style="color:#8fbc8f">Smart</span></div>
    </a>
    <a href="proposer-recette.php" style="font-size: 0.7rem; font-weight: 700; color: #F2994A; text-decoration: none; text-transform: uppercase; letter-spacing: 0.05em; border: 1px solid rgba(242, 153, 74, 0.3); padding: 3px 8px; border-radius: 4px;">Proposer une recette</a>
  </div>
  <ul class="nav-links" style="display:flex; list-style:none; gap:1.5rem;">
    <li><a href="nutrismart-website.html" style="text-decoration:none; color:#748074; font-weight:600;">Accueil</a></li>
    <li><a href="recette.php" style="text-decoration:none; color:#4a7c59; font-weight:700;">Recettes</a></li>
    <li><a href="contact.html" style="text-decoration:none; color:#748074; font-weight:600;">Contact</a></li>
  </ul>
</nav>

<div class="page-hero">
  <h1>Partagez vos <em>Saveurs</em></h1>
  <p>Devenez un créateur NutriSmart. Proposez vos recettes les plus saines et inspirez des milliers d'utilisateurs.</p>
</div>

<section class="form-section">
  <div class="form-card">
    <h2 class="form-title">Votre Recette Santé</h2>

    <?php if ($message): ?>
      <div class="alert alert-<?= $status ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>
    
    <form action="proposer-recette.php" method="POST">
      <div class="form-grid">
        <div class="form-group full">
          <label>Nom de la Recette</label>
          <input type="text" name="nom_recette" placeholder="Ex: Salade de Quinoa au Citron" required>
        </div>
        
        <div class="form-group">
          <label>Calories estimées (kcal)</label>
          <input type="number" name="calories_totales" placeholder="Ex: 350">
        </div>
        
        <div class="form-group">
          <label>Temps de préparation (min)</label>
          <input type="number" name="temps_preparation" placeholder="Ex: 20">
        </div>
        
        <!-- SECTION INGRÉDIENTS -->
        <div class="form-group full">
          <div class="section-subtitle">🥗 Ingrédients</div>
          <div id="ingredients-container">
            <div class="ingredient-row">
              <div class="form-group" style="margin-bottom: 0;">
                <label>Ingrédient</label>
                <input type="text" name="ingredients[]" placeholder="Ex: Quinoa" required>
              </div>
              <div class="form-group" style="margin-bottom: 0;">
                <label>Quantité</label>
                <input type="number" step="0.01" name="quantites[]" placeholder="100" required>
              </div>
              <div class="form-group" style="margin-bottom: 0;">
                <label>Unité</label>
                <select name="unites[]" required>
                  <option value="g">g (grammes)</option>
                  <option value="ml">ml (millilitres)</option>
                  <option value="pieces">pièce(s)</option>
                </select>
              </div>
              <div class="form-group" style="margin-bottom: 0; visibility: hidden;">
                <label>&nbsp;</label>
                <button type="button" class="remove-btn">✕</button>
              </div>
            </div>
          </div>
          <button type="button" class="add-btn" data-action="add-ingredient">+ Ajouter un ingrédient</button>
        </div>
        
        <!-- SECTION ÉTAPES DE PRÉPARATION -->
        <div class="form-group full">
          <div class="section-subtitle">👨‍🍳 Étapes de Préparation</div>
          <div id="steps-container">
            <div class="step-row">
              <div class="step-number">1</div>
              <textarea name="etapes[]" rows="2" placeholder="Décrivez la première étape..." required></textarea>
              <div style="visibility: hidden;">
                <button type="button" class="remove-btn">✕</button>
              </div>
            </div>
          </div>
          <button type="button" class="add-btn" data-action="add-step">+ Ajouter une étape</button>
        </div>
      </div>
      
      <!-- SECTION EMAIL -->
      <div class="form-group full" style="margin-top: 2rem; padding-top: 2rem; border-top: 2px solid #e8f5e9;">
        <label for="email">Votre Email <span style="color: #f44336;">*</span></label>
        <input type="email" id="email" name="email" placeholder="votre.email@exemple.com" required style="max-width: 400px;">
        <p style="font-size: 0.85rem; color: #666; margin-top: 0.5rem;">
          Nous utiliserons cette adresse pour vous informer de l'état de votre proposition de recette.
        </p>
      </div>
      
      <button type="submit" class="submit-btn">Soumettre ma Recette</button>
      
      <p style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color:#666;">
        Votre proposition sera examinée par nos administrateurs avant d'être publiée.
      </p>
    </form>
  </div>
</section>

<footer style="text-align:center; padding:3rem; color:#999; font-size:0.9rem; background:#fff; border-top:1px solid #f1f1f1;">
  &copy; 2026 NutriSmart. Tous droits réservés.
</footer>

<script>
// === ATTACHER LES ÉVÉNEMENTS AU CHARGEMENT ===
document.addEventListener('DOMContentLoaded', function() {
  // Bouton ajouter ingrédient
  document.querySelectorAll('[data-action="add-ingredient"]').forEach(btn => {
    btn.addEventListener('click', addIngredient);
  });
  
  // Bouton ajouter étape
  document.querySelectorAll('[data-action="add-step"]').forEach(btn => {
    btn.addEventListener('click', addStep);
  });
});

// === AJOUTER UN INGRÉDIENT ===
function addIngredient() {
  const container = document.getElementById('ingredients-container');
  const ingredientRow = document.createElement('div');
  ingredientRow.className = 'ingredient-row';
  ingredientRow.innerHTML = `
    <div class="form-group" style="margin-bottom: 0;">
      <label>Ingrédient</label>
      <input type="text" name="ingredients[]" placeholder="Ex: Tomates" required>
    </div>
    <div class="form-group" style="margin-bottom: 0;">
      <label>Quantité</label>
      <input type="number" step="0.01" name="quantites[]" placeholder="200" required>
    </div>
    <div class="form-group" style="margin-bottom: 0;">
      <label>Unité</label>
      <select name="unites[]" required>
        <option value="g">g (grammes)</option>
        <option value="ml">ml (millilitres)</option>
        <option value="pieces">pièce(s)</option>
      </select>
    </div>
    <div class="form-group" style="margin-bottom: 0;">
      <label>&nbsp;</label>
      <button type="button" class="remove-btn" data-action="remove-ingredient">✕</button>
    </div>
  `;
  container.appendChild(ingredientRow);
  
  // Attacher l'événement au nouveau bouton supprimer
  ingredientRow.querySelector('[data-action="remove-ingredient"]').addEventListener('click', function() {
    removeIngredient(this);
  });
}

// === SUPPRIMER UN INGRÉDIENT ===
function removeIngredient(btn) {
  btn.closest('.ingredient-row').remove();
}

// === AJOUTER UNE ÉTAPE ===
function addStep() {
  const container = document.getElementById('steps-container');
  const stepCount = container.querySelectorAll('.step-row').length + 1;
  const stepRow = document.createElement('div');
  stepRow.className = 'step-row';
  stepRow.innerHTML = `
    <div class="step-number">${stepCount}</div>
    <textarea name="etapes[]" rows="2" placeholder="Décrivez l'étape ${stepCount}..." required></textarea>
    <button type="button" class="remove-btn" data-action="remove-step">✕</button>
  `;
  container.appendChild(stepRow);
  
  // Attacher l'événement au nouveau bouton supprimer
  stepRow.querySelector('[data-action="remove-step"]').addEventListener('click', function() {
    removeStep(this);
  });
}

// === SUPPRIMER UNE ÉTAPE ===
function removeStep(btn) {
  const container = document.getElementById('steps-container');
  btn.closest('.step-row').remove();
  
  // Renuméroter les étapes
  const steps = container.querySelectorAll('.step-row');
  steps.forEach((step, index) => {
    const stepNumber = step.querySelector('.step-number');
    const textarea = step.querySelector('textarea');
    stepNumber.textContent = index + 1;
    textarea.placeholder = `Décrivez l'étape ${index + 1}...`;
  });
}

// === GESTION DU FORMULAIRE ===
document.querySelector('form').addEventListener('submit', function(e) {
  // Le formulaire sera soumis normalement
  // Les données seront traitées côté serveur
});
</script>

</body>
</html>
