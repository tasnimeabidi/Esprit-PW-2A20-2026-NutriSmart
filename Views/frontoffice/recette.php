<?php
session_start();

// Gestion des favoris via AJAX
if (isset($_POST['action']) && $_POST['action'] === 'toggle_favorite') {
        // Gestion des favoris via AJAX
        if (!isset($_SESSION['favoris'])) {
            $_SESSION['favoris'] = [];
        }
        
        $recette_id = $_POST['recette_id'];
        $recette_nom = $_POST['recette_nom'];
        $recette_img = $_POST['recette_img'];
        $recette_cal = $_POST['recette_cal'];
        $recette_prot = $_POST['recette_prot'];
        $recette_cat = $_POST['recette_cat'] ?? '';
        $recette_desc = $_POST['recette_desc'] ?? '';
        $recette_ing = $_POST['recette_ing'] ?? '';
        $recette_prep = $_POST['recette_prep'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        
        $key = array_search($recette_id, array_column($_SESSION['favoris'], 'id'));
        
        if ($key !== false) {
            // Retirer des favoris
            unset($_SESSION['favoris'][$key]);
            $_SESSION['favoris'] = array_values($_SESSION['favoris']); // Réindexer
            echo json_encode(['status' => 'removed', 'count' => count($_SESSION['favoris'])]);
        } else {
            // Ajouter aux favoris
            $_SESSION['favoris'][] = [
                'id' => $recette_id,
                'nom' => $recette_nom,
                'img' => $recette_img,
                'cal' => $recette_cal,
                'prot' => $recette_prot,
                'cat' => $recette_cat,
                'desc' => $recette_desc,
                'ing' => $recette_ing,
                'prep' => $recette_prep,
                'video' => $video_url
            ];
            echo json_encode(['status' => 'added', 'count' => count($_SESSION['favoris'])]);
        }
        exit;
}

include_once __DIR__ . '/../../Models/config.php';
include_once __DIR__ . '/../../Models/Aliment.php';
include_once __DIR__ . '/../../Models/Recette.php';

$db = (new Database())->getConnection();
$aliments = (new Aliment($db))->readAll()->fetchAll(PDO::FETCH_ASSOC);
$recettes_db = (new Recette($db))->readByStatus('approved');
$db_recettes = $recettes_db ? $recettes_db->fetchAll(PDO::FETCH_ASSOC) : [];

// Initialiser les favoris
if (!isset($_SESSION['favoris'])) {
    $_SESSION['favoris'] = [];
}
$favoris = $_SESSION['favoris'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>NutriSmart — Recettes</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;900&family=DM+Sans:wght@400;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/shared-styles.css">
  <link rel="stylesheet" href="css/recette.css">
</head>
<body>

<!-- Conteneur pour les notifications -->
<div id="notification-container"></div>

<!-- NAV -->
<nav id="navbar">
  <div class="logo-container">
    <a href="nutrismart-website.html" class="nav-logo">
      <svg width="30" height="30" viewBox="0 0 100 100" fill="none" style="overflow:visible">
        <mask id="m"><rect x="-20" y="-20" width="140" height="140" fill="white"/><circle cx="92" cy="35" r="18" fill="black"/><circle cx="84" cy="62" r="14" fill="black"/></mask>
        <g mask="url(#m)">
          <path d="M 20 80 C 35 45 65 25 90 10 C 90 60 70 90 20 80 Z" fill="#4a7c59"/>
          <path d="M 20 80 C 10 30 40 10 90 10 C 65 25 35 45 20 80 Z" fill="#8fbc8f"/>
        </g>
        <path d="M 22 78 L 12 92" stroke="#4a7c59" stroke-width="7" stroke-linecap="round"/>
      </svg>
      <div><span style="color:#4a7c59">Nutri</span><span style="color:#8fbc8f">Smart</span></div>
    </a>
    <a href="proposer-recette.php" class="btn-proposer">Proposer une recette</a>
  </div>
  <ul class="nav-links">
    <li><a href="nutrismart-website.html">Accueil</a></li>
    <li><a href="suivi-statistiques.php">Suivi</a></li>
    <li><a href="profile.html">Profil</a></li>
    <li><a href="recette.php" class="active">Recettes</a></li>
    <li><a href="contact.html">Contact</a></li>
    <li><a href="../backoffice/nutrismart-dashboard.php" style="color:#f2994a;font-weight:700">Admin</a></li>
  </ul>
  <div class="nav-auth"><a href="register.html" class="nav-cta">Commencer</a></div>
</nav>

<!-- HERO -->
<div class="hero">
  <h1>Recettes <em>& Aliments Santé</em></h1>
  <p>Recettes équilibrées + aliments ajoutés par l'administration</p>
  
  <!-- BARRE DE RECHERCHE PAR ALIMENT -->
  <div class="search-container">
    <div class="search-box">
      <input type="text" 
             class="search-input" 
             id="searchAliment" 
             placeholder="🔍 Rechercher des recettes par aliment (ex: tomate, poulet, riz...)" 
             autocomplete="off">
      <button class="search-btn" onclick="searchRecettesByAliment()">Rechercher</button>
    </div>
    <div class="search-results-info" id="searchResultsInfo"></div>
  </div>
</div>

<!-- SECTION FAVORIS -->
<div class="section-title" style="margin-top: 2rem;">
  <h2>⭐ Mes Recettes Favorites <span style="font-size:.9rem;color:#F2994A;font-weight:normal;">(<?= count($favoris) ?>)</span></h2>
</div>

<!-- BOUTON GÉNÉRATION LISTE DE COURSES -->
<div style="text-align: center; margin-bottom: 1rem;">
  <button class="btn" id="generateShoppingListBtn" onclick="generateShoppingList()" style="background: #FF9800; color: white; font-weight: 700;">📝 Générer Liste de Courses</button>
</div>
<div class="favoris-grid" id="favoris-grid">
  <?php if (empty($favoris)): ?>
    <div class="favoris-empty" id="favoris-empty">Aucune recette favorite pour le moment. Cliquez sur l'étoile pour ajouter vos recettes préférées !</div>
  <?php else: ?>
    <?php foreach($favoris as $fav): 
      // Préparer les données pour JavaScript - stocker dans un data attribute
      $fav_recipe_data = [
        'id' => $fav['id'],
        'nom' => $fav['nom'],
        'img' => $fav['img'],
        'cal' => $fav['cal'],
        'prot' => $fav['prot'],
        'cat' => $fav['cat'] ?? '',
        'desc' => $fav['desc'] ?? '',
        'ing' => !empty($fav['ing']) ? $fav['ing'] : '',
        'prep' => !empty($fav['prep']) ? $fav['prep'] : '',
        'video' => $fav['video'] ?? ''
      ];
      $fav_recipe_data_json = htmlspecialchars(json_encode($fav_recipe_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
    ?>
      <div class="card" id="fav-<?= htmlspecialchars($fav['id']) ?>">
        <span class="favorite-star recipe-star active" data-recipe='<?= $fav_recipe_data_json ?>'>★</span>
        <img src="<?= htmlspecialchars($fav['img']) ?>" alt="<?= htmlspecialchars($fav['nom']) ?>" onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=500';">
        <div class="card-body">
          <?php if(!empty($fav['cat'])): ?>
            <span class="badge"><?= htmlspecialchars($fav['cat']) ?></span>
          <?php endif; ?>
          <h3><?= htmlspecialchars($fav['nom']) ?></h3>
          <?php if(!empty($fav['desc'])): ?>
            <p><?= htmlspecialchars($fav['desc']) ?></p>
          <?php endif; ?>
          <p style="margin-top:.5rem;font-size:.82rem;color:#4CAF50;font-weight:700">🔥 <?= htmlspecialchars($fav['cal']) ?> kcal &nbsp;|&nbsp; 💪 <?= htmlspecialchars($fav['prot']) ?> protéines</p>
          <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
            <button class="btn" onclick="toggle(this)" style="flex: 1; margin-top: 0;">Voir la recette</button>
            <?php if(!empty($fav['video'])): ?>
              <a href="<?= htmlspecialchars($fav['video']) ?>" target="_blank" class="btn" style="background: #ff0050; border: none; flex: 1; margin-top: 0; display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 0.5rem; font-size: 0.85rem;">
                🎬 Vidéo
              </a>
            <?php endif; ?>
          </div>
          <div class="detail">
            <?php 
            // Vérifier si c'est une recette de la communauté (texte brut) ou une recette statique (JSON)
            $has_json_data = false;
            $ingredients = [];
            $preparations = [];
            
            if(!empty($fav['ing'])) {
              $ingredients = json_decode($fav['ing'], true);
              if(is_array($ingredients) && count($ingredients) > 0) $has_json_data = true;
            }
            
            if(!empty($fav['prep'])) {
              $preparations = json_decode($fav['prep'], true);
              if ($preparations === null) {
                  $preparations = [$fav['prep']];
              }
            }
            
            if($has_json_data):
              // Recette statique avec ingrédients et préparation séparés
            ?>
              <h4>Ingrédients</h4>
              <ul><?php 
                foreach($ingredients as $ing) {
                  if(!empty($ing)) echo "<li>".htmlspecialchars($ing)."</li>";
                }
              ?></ul>
              <h4>Préparation</h4>
              <ol><?php 
                foreach($preparations as $prep) {
                  if(!empty($prep)) echo "<li>".htmlspecialchars($prep)."</li>";
                }
              ?></ol>
            <?php elseif(!empty($preparations) && is_array($preparations) && count($preparations) > 0 && !empty($preparations[0])): 
              // Recette de la communauté avec texte brut
            ?>
              <h4>Préparation & Instructions</h4>
              <p><?php 
                $prep_text = is_array($preparations) ? ($preparations[0] ?? '') : $preparations;
                echo nl2br(htmlspecialchars($prep_text)); 
              ?></p>
            <?php else: ?>
              <p>Aucune préparation disponible</p>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

<!-- MODALE POUR LA LISTE DE COURSES -->
<div id="shoppingListModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; justify-content: center; align-items: center;">
  <div style="background: white; padding: 2rem; border-radius: 1rem; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto;">
    <h3 style="color: #2D6A2D; margin-bottom: 1rem;">🛒 Liste de Courses</h3>
    <div id="shoppingListContent">
      <!-- La liste sera générée ici -->
    </div>
    <div style="margin-top: 1.5rem; display: flex; gap: 1rem; justify-content: flex-end;">
      <button class="btn" onclick="exportShoppingList()" style="background: #4CAF50;">📄 Exporter</button>
      <button class="btn" onclick="closeShoppingListModal()" style="background: #f44336;">Fermer</button>
    </div>
  </div>
</div>

<!-- RECETTES STATIQUES -->
<div class="section-title">🍽️ Nos Recettes</div>
<div class="grid">
  <?php
  $static = [
    ['Salade Quinoa & Avocat',    'images/salade.jpg',     'Salade fraîche à base de quinoa et avocat.',    '12g','350','Salade',  ['100g quinoa','1 avocat','Tomates cerises','Roquette','Huile olive'],['Cuire quinoa.','Couper avocat & tomates.','Mélanger et assaisonner.']],
    ['Bol Smoothie Vert',         'images/smoothie.jpg',   'Riche en vitamines pour démarrer la journée.',  '3g', '180','Boisson', ['1 pomme verte','Épinards','1 banane','200ml eau de coco'],['Tout mettre dans un blender.','Mixer.','Servir frais.']],
    ['Riz au Poulet',             'images/riz-poulet.jpg', 'Plat léger et savoureux pour un dîner équilibré.','25g','450','Plat', ['150g riz basmati','200g poulet','Carottes, petits pois','Épices'],['Cuire le riz.','Griller le poulet.','Mélanger avec légumes vapeur.']],
    ['Omelette aux Légumes',      'images/omelette.jpg',   'Omelette moelleuse et saine.',                  '18g','250','Plat',   ['3 œufs','Poivrons, oignons','Herbes, sel'],['Battre les œufs.','Revenir les légumes.','Cuire jusqu\'à ferme.']],
    ['Steak & Légumes Sautés',    'images/steak.jpg',      'Riche en protéines et fibres.',                 '28g','400','Plat',   ['150g steak','Poivrons, brocolis','Huile olive'],['Revenir légumes.','Griller steak.','Servir ensemble.']],
    ['Pasta High Protéine',       'images/pasta.jpg',      'Pâtes complètes avec poulet et épinards.',      '30g','500','Plat',   ['100g pâtes','150g poulet','Épinards, ail'],['Cuire pâtes al dente.','Revenir poulet & épinards.','Mélanger.']],
    ['Poisson Grillé & Légumes',  'images/poisson.jpg',    'Riche en oméga-3 avec légumes vapeur.',         '25g','350','Poisson',['150g poisson blanc','Courgettes, brocolis','Citron'],['Assaisonner & griller 5 min.','Cuire légumes vapeur.','Servir avec citron.']],
    ['Salade de Fruits Fraîche',  'images/saladefruit.jpg','Dessert léger et vitaminé.',                    '2g', '120','Dessert',['Pomme, poire, kiwi','Fraises, myrtilles','Jus de citron'],['Couper les fruits.','Mélanger avec citron.','Servir frais.']],
  ];
  foreach($static as $i => [$nom,$img,$desc,$prot,$cal,$cat,$ing,$prep]):
    $recette_id = 'static_' . $i;
    $is_fav = in_array($recette_id, array_column($favoris, 'id'));
    
    // Préparer les données pour JavaScript - stocker dans un data attribute
    $recipe_data = [
      'id' => $recette_id,
      'nom' => $nom,
      'img' => $img,
      'cal' => $cal,
      'prot' => $prot,
      'cat' => $cat,
      'desc' => $desc,
      'ing' => $ing,
      'prep' => $prep
    ];
    $recipe_data_json = htmlspecialchars(json_encode($recipe_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  ?>
  <div class="card">
    <span class="favorite-star recipe-star <?= $is_fav ? 'active' : 'inactive' ?>" 
          data-recipe='<?= $recipe_data_json ?>'>★</span>
    <img src="<?= $img ?>" alt="<?= htmlspecialchars($nom) ?>" onerror="this.style.display='none'">
    <div class="card-body">
      <span class="badge"><?= htmlspecialchars($cat) ?></span>
      <h3><?= htmlspecialchars($nom) ?></h3>
      <p><?= htmlspecialchars($desc) ?></p>
      <p style="margin-top:.5rem;font-size:.82rem;color:#4CAF50;font-weight:700">🔥 <?= $cal ?> kcal &nbsp;|&nbsp; 💪 <?= $prot ?> protéines</p>
      <button class="btn" onclick="toggle(this)">Voir la recette</button>
      <div class="detail">
        <h4>Ingrédients</h4>
        <ul><?php foreach($ing as $ingredient) echo "<li>".htmlspecialchars($ingredient)."</li>"; ?></ul>
        <h4>Préparation</h4>
        <ol><?php foreach($prep as $preparation) echo "<li>".htmlspecialchars($preparation)."</li>"; ?></ol>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- RECETTES DE LA COMMUNAUTÉ (BASE DE DONNÉES) -->
<?php if (!empty($db_recettes)): ?>
<div class="section-title">✨ Recettes de la Communauté</div>
<div class="grid">
  <?php foreach($db_recettes as $r): 
    if ($r === reset($db_recettes)) {
        echo "<!-- DEBUG KEYS: " . implode(', ', array_keys($r)) . " -->";
    }
    $recette_id = 'community_' . $r['id'];
    $is_fav = in_array($recette_id, array_column($favoris, 'id'));
    $cal = (int)($r['calories_totales']??0);
    $temps = (int)($r['temps']??0);
    $desc = "Une recette saine ajoutée par nos administrateurs.";
    $instructions = $r['instructions'] ?? '';
    
    // Préparer les données pour JavaScript - stocker dans un data attribute
    $recipe_data = [
      'id' => $recette_id,
      'nom' => $r['nom'],
      'img' => 'images/default-recipe.jpg',
      'cal' => (string)$cal,
      'prot' => $temps.'min',
      'cat' => 'Nouvelle',
      'desc' => $desc,
      'ing' => '',
      'prep' => [$instructions],
      'video' => $r['video_url'] ?? ''
    ];
    $recipe_data_json = htmlspecialchars(json_encode($recipe_data, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
  ?>
  <div class="card">
    <span class="favorite-star recipe-star <?= $is_fav ? 'active' : 'inactive' ?>" 
          data-recipe='<?= $recipe_data_json ?>'>★</span>
    <!-- Image par défaut pour les recettes ajoutées manuellement -->
    <img src="images/default-recipe.jpg" alt="<?= htmlspecialchars($r['nom']) ?>" onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=500';">
    <div class="card-body">
      <span class="badge">Nouvelle</span>
      <h3><?= htmlspecialchars($r['nom']) ?></h3>
      <p>Une recette saine ajoutée par nos administrateurs.</p>
      <p style="margin-top:.5rem;font-size:.82rem;color:#4CAF50;font-weight:700">
        🔥 <?= (int)($r['calories_totales']??0) ?> kcal &nbsp;|&nbsp; 
        ⏱ <?= (int)($r['temps']??0) ?> min
      </p>
      <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
        <button class="btn" onclick="toggle(this)" style="flex: 1; margin-top: 0;">Voir la recette</button>
        <?php 
        $has_video = !empty($r['video_url']);
        if($has_video): ?>
          <a href="<?= htmlspecialchars($r['video_url']) ?>" target="_blank" class="btn" style="background: #ff0050; border: none; flex: 1; margin-top: 0; display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 0.5rem; font-size: 0.85rem;">
            🎬 Vidéo
          </a>
        <?php endif; ?>
      </div>
      <div class="detail">
        <h4>Préparation & Instructions</h4>
        <p><?= nl2br(htmlspecialchars($instructions)) ?></p>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ALIMENTS BASE DE DONNÉES -->
<?php if (!empty($aliments)): ?>
<div class="section-title">🥗 Aliments Nutritionnels</div>
<div class="grid">
  <?php foreach($aliments as $a): ?>
  <div class="card">
    <div class="card-body">
      <span class="badge"><?= htmlspecialchars($a['categorie'] ?? 'Autre') ?></span>
      <h3><?= htmlspecialchars($a['nom']) ?></h3>
      <div class="macros">
        <div class="macro"><?= (int)($a['calories']??0) ?> kcal<span>Calories</span></div>
        <div class="macro"><?= number_format((float)($a['proteines']??0),1) ?>g<span>Protéines</span></div>
        <div class="macro"><?= number_format((float)($a['glucides']??0),1) ?>g<span>Glucides</span></div>
        <div class="macro"><?= number_format((float)($a['lipides']??0),1) ?>g<span>Lipides</span></div>
      </div>
      <?php if(!empty($a['prix'])): ?>
      <p style="color:#F2994A;font-weight:700;font-size:.9rem">Prix : <?= number_format((float)$a['prix'],2) ?> €</p>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- WIDGET DE CHAT -->
<div class="chat-widget" id="chatWidget">
  <div class="chat-header">
    <h3>💬 Questions & Réponses</h3>
    <button class="chat-toggle" onclick="toggleChat()">−</button>
  </div>
  <div class="chat-body" id="chatBody">
    <div class="chat-messages" id="chatMessages">
      <div class="chat-message admin">
        <div class="message-content">Bonjour! Comment puis-je vous aider?</div>
      </div>
    </div>
    <div class="chat-input-container">
      <textarea class="chat-input" id="chatInput" placeholder="Posez votre question..."></textarea>
      <button class="chat-send-btn" onclick="sendMessage()">Envoyer</button>
    </div>
  </div>
</div>

<footer>&copy; 2026 NutriSmart. Tous droits réservés.</footer>

<script>
function toggle(btn) {
  // Trouver le conteneur de détails qui est maintenant après le div des boutons
  var cardBody = btn.closest('.card-body');
  var d = cardBody.querySelector('.detail');
  
  d.style.display = d.style.display === 'block' ? 'none' : 'block';
  btn.textContent = d.style.display === 'block' ? 'Masquer' : 'Voir la recette';
}

function showNotification(message, type) {
  var container = document.getElementById('notification-container');
  
  // Créer l'élément de notification
  var notif = document.createElement('div');
  notif.className = 'notification' + (type === 'remove' ? ' remove' : '');
  
  var icon = type === 'remove' ? '❌' : '⭐';
  
  notif.innerHTML = '<span class="notif-icon">' + icon + '</span><span class="notif-text">' + message + '</span>';
  
  container.appendChild(notif);
  
  // Afficher avec animation
  setTimeout(function() {
    notif.classList.add('show');
  }, 10);
  
  // Masquer et supprimer après 3 secondes
  setTimeout(function() {
    notif.classList.remove('show');
    setTimeout(function() {
      notif.remove();
    }, 300);
  }, 3000);
}

function toggleFavorite(recetteId, nom, img, cal, prot, cat, desc, ing, prep, video, starElement) {
  // Envoyer la requête AJAX
  var formData = new FormData();
  formData.append('action', 'toggle_favorite');
  formData.append('recette_id', recetteId);
  formData.append('recette_nom', nom);
  formData.append('recette_img', img);
  formData.append('recette_cal', cal);
  formData.append('recette_prot', prot);
  formData.append('recette_cat', cat || '');
  formData.append('recette_desc', desc || '');
  formData.append('video_url', video || '');
  
  // Si ing/prep sont des objets/tableaux, les convertir en JSON
  var ingData = '';
  var prepData = '';
  
  if (typeof ing === 'object' && ing !== null) {
    ingData = JSON.stringify(ing);
  } else {
    ingData = ing || '';
  }
  
  if (typeof prep === 'object' && prep !== null) {
    prepData = JSON.stringify(prep);
  } else {
    prepData = prep || '';
  }
  
  formData.append('recette_ing', ingData);
  formData.append('recette_prep', prepData);
  
  fetch('recette.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    // Mettre à jour l'étoile
    if (data.status === 'added') {
      starElement.classList.remove('inactive');
      starElement.classList.add('active');
      
      // Afficher notification
      showNotification('Recette ajoutée aux favoris', 'add');
      
      // Ajouter à la liste des favoris (passer les chaînes JSON)
      addToFavoriteList(recetteId, nom, img, cal, prot, cat, desc, ingData, prepData, video);
    } else {
      starElement.classList.remove('active');
      starElement.classList.add('inactive');
      
      // Afficher notification
      showNotification('Recette retirée des favoris', 'remove');
      
      // Retirer de la liste des favoris
      removeFromFavoriteList(recetteId);
    }
    
    // Mettre à jour le compteur
    updateFavoriteCount(data.count);
  })
  .catch(error => console.error('Erreur:', error));
}

function addToFavoriteList(id, nom, img, cal, prot, cat, desc, ing, prep, video) {
  var grid = document.getElementById('favoris-grid');
  var empty = document.getElementById('favoris-empty');
  
  if (empty) {
    empty.remove();
  }
  
  var card = document.createElement('div');
  card.className = 'card';
  card.id = 'fav-' + id;
  
  var ingredientsHtml = '';
  var preparationsHtml = '';
  var hasJsonData = false;
  
  var ingredients = [];
  var preparations = [];
  var rawIngredients = ing;
  var rawPreparations = prep;
  
  try {
    if (ing) {
      var parsedIng = JSON.parse(ing);
      if (Array.isArray(parsedIng)) {
        ingredients = parsedIng;
      }
    }
    if (prep) {
      var parsedPrep = JSON.parse(prep);
      if (Array.isArray(parsedPrep)) {
        preparations = parsedPrep;
      }
    }
  } catch(e) {
    console.log('Erreur parsing JSON:', e, 'ing:', ing, 'prep:', prep);
  }
  
  if (ingredients.length > 0) {
    hasJsonData = true;
    ingredientsHtml = '<h4>Ingrédients</h4><ul>';
    ingredients.forEach(function(i) {
      if (i) ingredientsHtml += '<li>' + i + '</li>';
    });
    ingredientsHtml += '</ul>';
    
    if (preparations.length > 0) {
      preparationsHtml = '<h4>Préparation</h4><ol>';
      preparations.forEach(function(p) {
        if (p) preparationsHtml += '<li>' + p + '</li>';
      });
      preparationsHtml += '</ol>';
    }
  } else if (typeof rawPreparations === 'string' && rawPreparations.trim()) {
    // Recette de la communauté avec texte brut
    var prepText = rawPreparations.replace(/\\r\\n/g, '<br>').replace(/\\n/g, '<br>').replace(/\\r/g, '<br>').replace(/\r\n/g, '<br>').replace(/\n/g, '<br>').replace(/\r/g, '<br>');
    preparationsHtml = '<h4>Préparation & Instructions</h4><p>' + prepText + '</p>';
  }
  
  var detailHtml = `
    <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
      <button class="btn" onclick="toggle(this)" style="flex: 1; margin-top: 0;">Voir la recette</button>
      ${video ? `
      <a href="${video}" target="_blank" class="btn" style="background: #ff0050; border: none; flex: 1; margin-top: 0; display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 0.5rem; font-size: 0.85rem;">
        🎬 Vidéo
      </a>
      ` : ''}
    </div>
    <div class="detail">${ingredientsHtml}${preparationsHtml}</div>
  `;
  
  // Créer l'élément étoile avec un event listener au lieu d'un onclick inline
  var starSpan = document.createElement('span');
  starSpan.className = 'favorite-star active';
  starSpan.innerHTML = '★';
  starSpan.setAttribute('data-recipe', JSON.stringify({
    id: id,
    nom: nom,
    img: img,
    cal: cal,
    prot: prot,
    cat: cat,
    desc: desc,
    ing: ingredients.length > 0 ? ingredients : rawIngredients,
    prep: preparations.length > 0 ? preparations : rawPreparations,
    video: video
  }));
  starSpan.addEventListener('click', function() {
    toggleFavorite(
      id,
      nom,
      img,
      cal,
      prot,
      cat,
      desc,
      ingredients.length > 0 ? ingredients : rawIngredients,
      preparations.length > 0 ? preparations : rawPreparations,
      video,
      this
    );
  });
  
  card.innerHTML = `
    <img src="${img}" alt="${nom}" onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=500';">
    <div class="card-body">
      ${cat ? '<span class="badge">' + cat + '</span>' : ''}
      <h3>${nom}</h3>
      ${desc ? '<p>' + desc + '</p>' : ''}
      <p style="margin-top:.5rem;font-size:.82rem;color:#4CAF50;font-weight:700">🔥 ${cal} kcal &nbsp;|&nbsp; 💪 ${prot} protéines</p>
      ${detailHtml}
    </div>
  `;
  
  // Insérer l'étoile au début de la carte
  card.insertBefore(starSpan, card.firstChild);
  grid.appendChild(card);
}

function removeFromFavoriteList(id) {
  var card = document.getElementById('fav-' + id);
  if (card) {
    card.remove();
    
    // Si plus de favoris, afficher le message
    var grid = document.getElementById('favoris-grid');
    if (grid && grid.children.length === 0) {
      var empty = document.createElement('div');
      empty.className = 'favoris-empty';
      empty.id = 'favoris-empty';
      empty.textContent = 'Aucune recette favorite pour le moment. Cliquez sur l\'étoile pour ajouter vos recettes préférées !';
      grid.appendChild(empty);
    }
  }
}

function updateFavoriteCount(count) {
  // Mettre à jour le compteur dans le titre si nécessaire
  var titleSpan = document.querySelector('.section-title span');
  if (titleSpan) {
    titleSpan.textContent = '(' + count + ')';
  }
}

// =====================
// CHAT FUNCTIONALITY
// =====================

function toggleChat() {
  var chatBody = document.getElementById('chatBody');
  var toggleBtn = document.querySelector('.chat-toggle');
  
  if (chatBody.classList.contains('hidden')) {
    chatBody.classList.remove('hidden');
    toggleBtn.textContent = '−';
  } else {
    chatBody.classList.add('hidden');
    toggleBtn.textContent = '+';
  }
}

function sendMessage() {
  var input = document.getElementById('chatInput');
  var message = input.value.trim();
  
  if (!message) return;
  
  // Vider le champ immédiatement
  input.value = '';
  
  // Envoyer le message au serveur
  var formData = new FormData();
  formData.append('action', 'send_message');
  formData.append('message', message);
  formData.append('page', 'recette');
  
  fetch('../../chat_handler.php', {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      // Recharger immédiatement les messages après l'envoi
      loadMessages();
    } else {
      console.error('Erreur lors de l\'envoi du message:', data.error);
      showNotification('Erreur: ' + (data.error || 'Erreur inconnue'), 'remove');
    }
  })
  .catch(error => {
    console.error('Erreur:', error);
    showNotification('Erreur de connexion', 'remove');
  });
}

function addMessageToChat(message, isUser) {
  var messagesContainer = document.getElementById('chatMessages');
  
  var messageDiv = document.createElement('div');
  messageDiv.className = 'chat-message ' + (isUser ? 'user' : 'admin');
  
  var contentDiv = document.createElement('div');
  contentDiv.className = 'message-content';
  contentDiv.textContent = message;
  
  messageDiv.appendChild(contentDiv);
  messagesContainer.appendChild(messageDiv);
  
  // Scroll vers le bas
  messagesContainer.scrollTop = messagesContainer.scrollHeight;
}

function loadMessages() {
  fetch('../../chat_handler.php?action=get_messages&page=recette')
  .then(response => response.json())
  .then(data => {
    console.log('📨 Réponse de l\'API chat:', data);
    
    if (data.success && data.messages) {
      console.log('✅ Nombre de messages reçus:', data.messages.length);
      
      var messagesContainer = document.getElementById('chatMessages');
      
      // Vider complètement le conteneur
      messagesContainer.innerHTML = '';
      
      // Ajouter le message de bienvenue si aucun message n'existe
      if (data.messages.length === 0) {
        console.log('ℹ️ Aucun message, affichage du message de bienvenue');
        messagesContainer.innerHTML = '<div class="chat-message admin"><div class="message-content">Bonjour! Comment puis-je vous aider?</div></div>';
      } else {
        // Ajouter tous les messages dans l'ordre
        data.messages.forEach(function(msg, index) {
          console.log(`Message #${index + 1}:`, {
            id: msg.id,
            message: msg.message,
            is_admin: msg.is_admin,
            user_name: msg.user_name
          });
          
          // Si c'est un message admin, isUser = false, sinon isUser = true
          var isUserMessage = !msg.is_admin;
          addMessageToChat(msg.message, isUserMessage);
        });
      }
    } else {
      console.error('❌ Erreur dans la réponse:', data);
    }
  })
  .catch(error => console.error('❌ Erreur lors du chargement des messages:', error));
}

// Charger les messages au démarrage
document.addEventListener('DOMContentLoaded', function() {
  loadMessages();
  
  // Actualiser les messages toutes les 5 secondes
  setInterval(loadMessages, 5000);
  
  // Permettre l'envoi avec Entrée (Shift+Entrée pour nouvelle ligne)
  document.getElementById('chatInput').addEventListener('keydown', function(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      sendMessage();
    }
  });
});

// =====================
// RECHERCHE PAR ALIMENT
// =====================

function searchRecettesByAliment() {
  const searchInput = document.getElementById('searchAliment');
  const searchTerm = searchInput.value.trim();
  const searchInfo = document.getElementById('searchResultsInfo');
  const recettesGrid = document.querySelector('.grid');
  
  // Afficher un message de chargement
  searchInfo.innerHTML = '🔍 Recherche en cours...';
  
  // Requête AJAX vers l'API
  fetch('api/search_recettes.php?aliment=' + encodeURIComponent(searchTerm))
    .then(response => {
      if (!response.ok) {
        throw new Error('Erreur HTTP: ' + response.status);
      }
      return response.json();
    })
    .then(data => {
      console.log('Réponse de l\'API:', data);
      
      if (data.status === 'success') {
        const recettes = data.data;
        
        if (recettes.length === 0) {
          searchInfo.innerHTML = '❌ Aucune recette trouvée avec l\'aliment "' + searchTerm + '"';
          recettesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #999;"><p style="font-size: 1.2rem;">😔 Aucune recette ne contient cet aliment</p><p style="margin-top: 1rem;">Essayez avec un autre aliment ou créez d\'abord des liaisons dans le back-office !</p></div>';
        } else {
          searchInfo.innerHTML = '✅ ' + recettes.length + ' recette(s) trouvée(s) avec "' + searchTerm + '"';
          displayRecettes(recettes);
        }
      } else {
        searchInfo.innerHTML = '❌ Erreur: ' + (data.message || 'Erreur inconnue');
        console.error('Erreur API:', data);
        recettesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #f44336;"><p style="font-size: 1.2rem;">⚠️ Erreur lors de la recherche</p><p style="margin-top: 1rem;">' + (data.message || 'Erreur inconnue') + '</p></div>';
      }
    })
    .catch(error => {
      searchInfo.innerHTML = '❌ Erreur de connexion';
      console.error('Erreur fetch:', error);
      recettesGrid.innerHTML = '<div style="grid-column: 1 / -1; text-align: center; padding: 3rem; color: #f44336;"><p style="font-size: 1.2rem;">⚠️ Erreur de connexion</p><p style="margin-top: 1rem;">Vérifiez que le serveur est bien démarré et que l\'API est accessible.</p><p style="margin-top: 0.5rem; font-size: 0.9rem;">Erreur: ' + error.message + '</p></div>';
    });
}

function displayRecettes(recettes) {
  const recettesGrid = document.querySelector('.grid');
  recettesGrid.innerHTML = '';
  
  recettes.forEach(recette => {
    const card = createRecetteCard(recette);
    recettesGrid.innerHTML += card;
  });
  
  // Réattacher les event listeners aux nouvelles étoiles
  attachStarListeners();
}

function createRecetteCard(recette) {
  const recetteData = {
    id: recette.id,
    nom: recette.nom_recette || recette.nom || 'Sans nom',
    img: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?auto=format&fit=crop&q=80&w=500',
    cal: recette.calories_totales || 0,
    prot: '0g',
    cat: 'Recette',
    desc: recette.instructions ? recette.instructions.substring(0, 100) + '...' : '',
    ing: '',
    prep: recette.instructions || '',
    video: recette.video_url || ''
  };
  
  const recetteDataJson = JSON.stringify(recetteData).replace(/"/g, '&quot;');
  
  // Vérifier si la recette est déjà en favoris
  const isFavorite = checkIfFavorite(recette.id);
  const starClass = isFavorite ? 'active' : 'inactive';
  
  return `
    <div class="card">
      <span class="favorite-star recipe-star ${starClass}" data-recipe="${recetteDataJson}">★</span>
      <img src="${recetteData.img}" alt="${recetteData.nom}" onerror="this.src='https://images.unsplash.com/photo-1490645935967-10de6ba17061?auto=format&fit=crop&q=80&w=500';">
      <div class="card-body">
        <span class="badge">${recetteData.cat}</span>
        <h3>${recetteData.nom}</h3>
        <p>${recetteData.desc}</p>
        <p style="margin-top:.5rem;font-size:.82rem;color:#4CAF50;font-weight:700">🔥 ${recetteData.cal} kcal</p>
        <div style="display: flex; gap: 0.5rem; margin-top: 1rem;">
          <button class="btn" onclick="toggle(this)" style="flex: 1; margin-top: 0;">Voir la recette</button>
          ${recetteData.video ? `
          <a href="${recetteData.video}" target="_blank" class="btn" style="background: #ff0050; border: none; flex: 1; margin-top: 0; display: flex; align-items: center; justify-content: center; text-decoration: none; padding: 0.5rem; font-size: 0.85rem;">
            🎬 Vidéo
          </a>
          ` : ''}
        </div>
        <div class="detail">
          <h4>📝 Préparation:</h4>
          <p>${recetteData.prep}</p>
        </div>
      </div>
    </div>
  `;
}

function checkIfFavorite(recetteId) {
  // Cette fonction pourrait être améliorée pour vérifier côté serveur
  // Pour l'instant, on vérifie si l'étoile existe déjà dans la page des favoris
  return document.getElementById('fav-' + recetteId) !== null;
}

function attachStarListeners() {
  const recipeStars = document.querySelectorAll('.recipe-star');
  
  recipeStars.forEach(function(star) {
    star.removeEventListener('click', handleStarClick); // Éviter les doublons
    star.addEventListener('click', handleStarClick);
  });
}

function handleStarClick() {
  const recipeDataStr = this.getAttribute('data-recipe');
  if (!recipeDataStr) {
    console.error('Pas de données de recette trouvées');
    return;
  }
  
  try {
    const recipe = JSON.parse(recipeDataStr);
    toggleFavorite(
      recipe.id,
      recipe.nom,
      recipe.img,
      recipe.cal,
      recipe.prot,
      recipe.cat,
      recipe.desc,
      recipe.ing,
      recipe.prep,
      recipe.video,
      this
    );
  } catch (e) {
    console.error('Erreur lors du parsing des données de recette:', e);
  }
}

function extractIngredientsFromRecipe(recipe) {
  if (!recipe) return [];

  const normalizeText = text => {
    if (typeof text !== 'string') return '';
    return text
      .replace(/\\r\\n/g, '\n')
      .replace(/\\n/g, '\n')
      .replace(/\\r/g, '\n')
      .replace(/\r/g, '\n');
  };

  if (Array.isArray(recipe.ing) && recipe.ing.length > 0) {
    return recipe.ing;
  }

  if (typeof recipe.ing === 'string' && recipe.ing.trim()) {
    const ingredientText = normalizeText(recipe.ing);
    try {
      const parsed = JSON.parse(ingredientText);
      if (Array.isArray(parsed) && parsed.length > 0) {
        return parsed;
      }
    } catch (e) {
      return ingredientText.split(/\n/).map(i => i.trim()).filter(i => i);
    }
  }

  let instructionsText = '';
  if (Array.isArray(recipe.prep) && recipe.prep.length > 0) {
    instructionsText = recipe.prep.join('\n');
  } else if (typeof recipe.prep === 'string') {
    instructionsText = recipe.prep;
  }

  instructionsText = normalizeText(instructionsText);
  if (!instructionsText) return [];

  const ingredientsIndex = instructionsText.search(/INGR[ÉE]DIENTS\s*:/i);
  if (ingredientsIndex >= 0) {
    const afterIngredients = instructionsText.slice(ingredientsIndex);
    const endIndex = afterIngredients.search(/\nPR[ÉE]PARATION(?:\s*&\s*INSTRUCTIONS)?\s*:/i);
    const ingredientBlock = endIndex >= 0 ? afterIngredients.slice(0, endIndex) : afterIngredients;
    return ingredientBlock
      .split(/\n/)
      .map(line => line.replace(/^[\s\-–•*]+/, '').trim())
      .filter(line => line && !/^INGR[ÉE]DIENTS\s*:/i.test(line));
  }

  return instructionsText
    .split(/\n/)
    .map(line => line.replace(/^[\s\-–•*]+/, '').trim())
    .filter(line => line && /^[0-9]*[\s]*[a-zA-ZéèêàùîôçÉÈÊÀÙÎÔÇ]*.*$/.test(line));
}

// =====================
// GÉNÉRATION LISTE DE COURSES
// =====================

function generateShoppingList() {
  const favoris = document.querySelectorAll('#favoris-grid .card');
  const ingredientsMap = new Map(); // Pour dédupliquer
  
  if (favoris.length === 0) {
    alert('Ajoutez d\'abord des recettes à vos favoris !');
    return;
  }
  
  favoris.forEach(card => {
    const star = card.querySelector('.favorite-star');
    if (star && star.classList.contains('active')) {
      const recipeDataStr = star.getAttribute('data-recipe');
      if (recipeDataStr) {
        try {
          const recipe = JSON.parse(recipeDataStr);
          const ingredientList = extractIngredientsFromRecipe(recipe);
          ingredientList.forEach(ing => {
            if (ing && ing.trim()) {
              const normalizedBase = ing.toLowerCase().replace(/^[\s\-–•*]+/, '').trim();
              const normalized = normalizedBase
                .replace(/^(?:\d+[\/\d\.,]*\s*)?(?:g|kg|ml|cl|l|tasse|tasses|cuill(?:ère|ères)|c[àa]s|c[àa]c|pi[eè]ce[s]?|pieces?|pinc[eé]e[s]?|sachet[s]?|tranche[s]?|bouquet|gousse[s]?|feuille[s]?|branche[s]?|zeste[s]?|de|d'|du|des)?\b\s*/i, '')
                .trim();
              const key = normalized || normalizedBase;
              if (!ingredientsMap.has(key)) {
                ingredientsMap.set(key, ing);
              }
            }
          });
        } catch (e) {
          console.error('Erreur parsing recette:', e);
        }
      }
    }
  });
  
  if (ingredientsMap.size === 0) {
    alert('Aucun ingrédient trouvé dans vos favoris.');
    return;
  }
  
  // Générer le HTML de la liste
  let listHtml = '<ul style="list-style: none; padding: 0;">';
  ingredientsMap.forEach((original, normalized) => {
    listHtml += `<li style="padding: 0.5rem 0; border-bottom: 1px solid #eee; display: flex; align-items: center; gap: 0.5rem;">
      <input type="checkbox" id="ing-${normalized.replace(/\s+/g, '-')}">
      <label for="ing-${normalized.replace(/\s+/g, '-')}" style="flex: 1; cursor: pointer;">${original}</label>
    </li>`;
  });
  listHtml += '</ul>';
  
  document.getElementById('shoppingListContent').innerHTML = listHtml;
  document.getElementById('shoppingListModal').style.display = 'flex';
}

function closeShoppingListModal() {
  document.getElementById('shoppingListModal').style.display = 'none';
}

function exportShoppingList() {
  const checkedItems = document.querySelectorAll('#shoppingListContent input[type="checkbox"]:checked');
  if (checkedItems.length === 0) {
    alert('Cochez au moins un ingrédient à exporter !');
    return;
  }
  
  let exportText = '🛒 Liste de Courses NutriSmart\n\n';
  checkedItems.forEach(item => {
    const label = item.nextElementSibling.textContent;
    exportText += '☐ ' + label + '\n';
  });
  
  // Créer un blob et télécharger
  const blob = new Blob([exportText], { type: 'text/plain' });
  const url = URL.createObjectURL(blob);
  const a = document.createElement('a');
  a.href = url;
  a.download = 'liste-courses-nutrismart.txt';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
  
  alert('Liste exportée avec succès !');
}

// Initialisation à l'ouverture de la page
document.addEventListener('DOMContentLoaded', function() {
  attachStarListeners();
  loadMessages();

  // Actualiser les messages toutes les 5 secondes
  setInterval(loadMessages, 5000);

  // Fermer la modale en cliquant en dehors
  const modal = document.getElementById('shoppingListModal');
  if (modal) {
    modal.addEventListener('click', function(e) {
      if (e.target === modal) {
        closeShoppingListModal();
      }
    });
  }

  // Permettre la recherche avec la touche Entrée
  const searchInput = document.getElementById('searchAliment');
  if (searchInput) {
    searchInput.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        searchRecettesByAliment();
      }
    });
  }

  // Envoyer le message avec Entrée
  const chatInput = document.getElementById('chatInput');
  if (chatInput) {
    chatInput.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
      }
    });
  }
});
</script>
</body>
</html>
