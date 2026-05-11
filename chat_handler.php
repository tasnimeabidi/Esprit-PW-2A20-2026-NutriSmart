<?php
/**
 * Chat léger « Questions & Réponses » (session par navigateur, page recettes).
 */
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

session_start();

header('Content-Type: application/json; charset=utf-8');

const CHAT_MAX_MSG_LEN = 2000;
const CHAT_MAX_STORED = 80;

function chatSessionKey(string $page): string
{
    return 'nutrismart_chat_' . $page;
}

/**
 * Réponse assistant : priorité à l’IA Groq si clé configurée, sinon règles locales.
 */
function botReplyNutrismart(string $userMessage): string
{
    $cfg = GroqConfig::load();
    if (GroqConfig::isPlausibleApiKey($cfg['apiKey'])) {
        try {
            return GroqVisionClient::repondreQuestionAssistant($cfg['apiKey'], $cfg['model'], $userMessage);
        } catch (Throwable $e) {
            // Clé invalide, quota, réseau : repli sur les règles + réponses culinaires fixes
        }
    }

    return botReplyNutrismartFallback($userMessage);
}

function botReplyNutrismartFallback(string $userMessage): string
{
    $u = trim($userMessage);

    // Ordre : motifs les plus précis en premier

    if (preg_match('/omelet|omelette|omlette/i', $u) || (preg_match('/œuf|oeuf/i', $u) && preg_match('/faire|ingrédient|recette/i', $u))) {
        return 'Pour une omelette classique : 2 à 3 œufs, une pincée de sel, poivre au goût, 1 cuillère à soupe de lait ou d’eau (facultatif, pour plus de moelleux), un peu de beurre ou d’huile pour la poêle. Battez les œufs dans un bol, faites chauffer la matière grasse à feu moyen, versez la préparation et laissez prendre en raclant légèrement les bords si vous voulez une texture uniforme. Variantes : fromage râpé, herbes fraîches, champignons revenus au préalable.';
    }

    if (preg_match('/ingrédient|ingredients|que mettre|donner.*ingréd/i', $u) && preg_match('/faire|prépar|cuisiner|cuisine/i', $u)) {
        return 'Pour vous répondre précisément sur les ingrédients, indiquez le nom du plat (ex. « gâteau au chocolat », « taboulé »). En général : listez protéines, féculents/légumes, matière grasse, assaisonnement, puis les étapes dans l’ordre.';
    }

    if (preg_match('/^(hi|hello|hey|salut|coucou|bonjour|bonsoir|slt)\s*!?$/iu', $u)) {
        return 'Bonjour ! Posez-moi une question précise sur les recettes (quantités en grammes, calories, ingrédients, recherche…) et je vous réponds.';
    }

    if (preg_match('/merci|thank/i', $u)) {
        return 'Je vous en prie ! Une autre question sur une recette ou un ingrédient ?';
    }

    if (preg_match('/gramme|grammes|\ben g\b|quantit|quantités|portion|mesure|peser|poids|millilitre|millilitres|\bml\b|cuillère|tasse/i', $u)) {
        return 'Les quantités peuvent être indiquées en grammes (g) ou millilitres selon la recette : ouvrez une recette et consultez la partie ingrédients — les montants en g ou ml s’affichent quand ils sont renseignés en base. Pour comparer, utilisez aussi la recherche par aliment au-dessus de la liste.';
    }

    if (preg_match('/donner|affiche|montre|liste|où|voir.*ingrédient/i', $u) && preg_match('/gram|ingrédient|quantit/i', $u)) {
        return 'Pour voir les quantités en grammes : choisissez une recette dans la grille, puis lisez le bloc ingrédients / préparation. Si une liaison aliment–recette existe en base, les grammes apparaissent à côté du nom de l’aliment.';
    }

    if (preg_match('/calorie|kcal|énergie|energie|apport/i', $u)) {
        return 'La valeur « calories » sur une carte correspond aux calories totales estimées pour la recette. C’est une indication ; votre besoin réel dépend de votre profil (âge, activité…), configurable dans l’application.';
    }

    if (preg_match('/allerg|intolérance|sans gluten|lactose|arachide/i', $u)) {
        return 'Pour une allergie ou une intolérance : vérifiez chaque ingrédient listé et évitez tout produit non adapté. En cas de doute médical, demandez conseil à un professionnel de santé avant d’essayer une nouvelle recette.';
    }

    if (preg_match('/végét|veget|vegan|viande|poisson|œuf|oeuf/i', $u)) {
        return 'Vous pouvez adapter une recette en remplaçant la protéine (tofu, légumineuses, alternatives végétales…). Filtrez les recettes avec la recherche par aliment pour trouver des idées qui vous correspondent.';
    }

    if (preg_match('/recherche|filtre|trouve|cherche|aliment.*recette/i', $u)) {
        return 'Utilisez le champ « recherche par aliment » en haut de la page : tapez un ingrédient (ex. poulet, quinoa) pour n’afficher que les recettes qui le contiennent.';
    }

    if (preg_match('/favori|étoile|bookmark|sauvegard/i', $u)) {
        return 'Cliquez sur l’étoile sur une carte recette pour l’ajouter à vos favoris (session en cours). Vous retrouvez la liste dans la section favoris sur cette même page.';
    }

    if (preg_match('/vidéo|tiktok|youtube|lien/i', $u)) {
        return 'Certaines recettes incluent un lien vidéo : repérez le bouton ou le lien sur la carte ou dans le détail pour ouvrir la démo.';
    }

    if (preg_match('/temps|minute|min\b|cuisson|préparation|prep/i', $u)) {
        return 'Le temps de préparation (ou total) est affiché sur la fiche recette quand il est renseigné. Sinon, suivez les étapes : les durées courtes sont souvent indiquées dans les instructions.';
    }

    if (preg_match('/proposer|soumettre|envoyer.*recette|nouvelle recette/i', $u)) {
        return 'Pour proposer une recette : utilisez le bouton « Proposer une recette » dans le menu en haut. Remplissez le formulaire ; après validation par l’équipe, elle pourra apparaître pour tous.';
    }

    if (preg_match('/admin|modération|valid/i', $u)) {
        return 'Les recettes proposées passent par une validation côté équipe avant publication. Les statuts typiques sont « en attente », puis « approuvé » ou « rejeté ».';
    }

    if (preg_match('/comment|aide|utiliser|marche|fonctionne/i', $u)) {
        return 'Sur cette page : faites défiler les recettes approuvées, recherchez par aliment, ajoutez aux favoris, ou ouvrez une recette pour lire ingrédients et étapes. Le chat répond à vos questions courantes sur ces fonctions.';
    }

    if (preg_match('/plan.*repas|repas|menu/i', $u)) {
        return 'Pour composer des menus sur plusieurs jours, utilisez la partie Plan de repas de NutriSmart (hors de cette page recettes). Vous pourrez y associer des recettes à vos repas.';
    }

    if (preg_match('/minceur|maigrir|régime|perdre du poids/i', $u)) {
        return 'Les recettes « santé » sont des bases équilibrées ; pour un objectif poids personnalisé, combinez alimentation et activité et faites-vous accompagner si besoin par un professionnel.';
    }

    // Sans clé Groq : réponse directe minimale + invite à nommer un plat ou configurer l’IA
    $snippet = $u;
    if (mb_strlen($snippet, 'UTF-8') > 120) {
        $snippet = mb_substr($snippet, 0, 117, 'UTF-8') . '…';
    }

    return 'Pour « ' . $snippet . ' » : reformulez en nommant un plat précis (ex. « ingrédients pour une quiche lorraine »), ou utilisez la recherche par aliment en haut de page. Pour des réponses détaillées à chaque question culinaire, ajoutez une clé `GROQ_API_KEY` (ou `config/groq.local.php`) comme pour le scanner repas — l’assistant répondra alors par IA.';
}

$page = isset($_REQUEST['page']) ? preg_replace('/[^a-z0-9_-]/i', '', (string) $_REQUEST['page']) : 'recette';
if ($page === '') {
    $page = 'recette';
}

$key = chatSessionKey($page);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action !== 'send_message') {
        echo json_encode(['success' => false, 'error' => 'Action non reconnue.']);
        exit;
    }

    $message = trim((string) ($_POST['message'] ?? ''));
    if ($message === '') {
        echo json_encode(['success' => false, 'error' => 'Message vide.']);
        exit;
    }
    if (mb_strlen($message, 'UTF-8') > CHAT_MAX_MSG_LEN) {
        echo json_encode(['success' => false, 'error' => 'Message trop long.']);
        exit;
    }

    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        $_SESSION[$key] = [];
    }

    $_SESSION[$key][] = ['message' => $message, 'is_admin' => false];
    $_SESSION[$key][] = ['message' => botReplyNutrismart($message), 'is_admin' => true];

    if (count($_SESSION[$key]) > CHAT_MAX_STORED) {
        $_SESSION[$key] = array_slice($_SESSION[$key], -CHAT_MAX_STORED);
    }

    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'get_messages') {
    $raw = $_SESSION[$key] ?? [];
    if (!is_array($raw)) {
        $raw = [];
    }
    $out = [];
    foreach ($raw as $i => $row) {
        if (!is_array($row) || !isset($row['message'])) {
            continue;
        }
        $out[] = [
            'id' => $i,
            'message' => (string) $row['message'],
            'is_admin' => !empty($row['is_admin']),
            'user_name' => !empty($row['is_admin']) ? 'NutriSmart' : 'Vous',
        ];
    }
    echo json_encode(['success' => true, 'messages' => $out]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Requête non valide.']);
