<?php
session_start();

// Fichier de stockage des messages
$chatFile = __DIR__ . '/data/chat_messages.json';

// Créer le dossier data s'il n'existe pas
if (!file_exists(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0777, true);
}

// Créer le fichier s'il n'existe pas
if (!file_exists($chatFile)) {
    file_put_contents($chatFile, '[]');
}

// Créer un ID unique pour l'utilisateur
if (!isset($_SESSION['chat_user_id'])) {
    $_SESSION['chat_user_id'] = 'client_' . uniqid();
}

header('Content-Type: application/json');

// Fonction pour lire les messages
function readMessages() {
    global $chatFile;
    $content = file_get_contents($chatFile);
    return json_decode($content, true) ?: [];
}

// Fonction pour écrire les messages
function writeMessages($messages) {
    global $chatFile;
    file_put_contents($chatFile, json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'send_message':
        $message = trim($_POST['message'] ?? '');
        $isAdmin = isset($_POST['is_admin']) && $_POST['is_admin'] === 'true';
        $userId = $isAdmin ? ($_POST['user_id'] ?? '') : $_SESSION['chat_user_id'];
        
        if (!empty($message)) {
            $messages = readMessages();
            
            $newMessage = [
                'id' => uniqid(),
                'user_id' => $userId,
                'user_name' => $isAdmin ? 'Administrateur' : 'Client',
                'message' => $message,
                'is_admin' => $isAdmin,
                'page' => $_POST['page'] ?? 'recette',
                'created_at' => date('Y-m-d H:i:s'),
                'is_read' => false
            ];
            
            $messages[] = $newMessage;
            writeMessages($messages);
            
            echo json_encode(['success' => true, 'message' => $newMessage]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message vide']);
        }
        break;
        
    case 'get_messages':
        $userId = $_GET['user_id'] ?? $_SESSION['chat_user_id'];
        $page = $_GET['page'] ?? 'recette';
        $messages = readMessages();
        
        // Filtrer les messages pour cet utilisateur et cette page
        // Inclure TOUS les messages (client ET admin) pour cet utilisateur
        $userMessages = array_filter($messages, function($msg) use ($userId, $page) {
            return $msg['user_id'] === $userId && $msg['page'] === $page;
        });
        
        echo json_encode(['success' => true, 'messages' => array_values($userMessages)]);
        break;
        
    case 'get_conversations':
        $messages = readMessages();
        $conversations = [];
        
        foreach ($messages as $msg) {
            $key = $msg['user_id'] . '_' . $msg['page'];
            
            if (!isset($conversations[$key])) {
                $conversations[$key] = [
                    'user_id' => $msg['user_id'],
                    'user_name' => $msg['user_name'],
                    'page' => $msg['page'],
                    'last_message_time' => $msg['created_at'],
                    'unread_count' => 0
                ];
            }
            
            // Mettre à jour la dernière date
            if (strtotime($msg['created_at']) > strtotime($conversations[$key]['last_message_time'])) {
                $conversations[$key]['last_message_time'] = $msg['created_at'];
            }
            
            // Compter les messages non lus du client
            if (!$msg['is_admin'] && !$msg['is_read']) {
                $conversations[$key]['unread_count']++;
            }
        }
        
        // Trier par date décroissante
        usort($conversations, function($a, $b) {
            return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
        });
        
        echo json_encode(['success' => true, 'conversations' => $conversations]);
        break;
        
    case 'mark_read':
        $userId = $_POST['user_id'] ?? '';
        $page = $_POST['page'] ?? '';
        
        if ($userId && $page) {
            $messages = readMessages();
            
            foreach ($messages as &$msg) {
                if ($msg['user_id'] === $userId && $msg['page'] === $page && !$msg['is_admin']) {
                    $msg['is_read'] = true;
                }
            }
            
            writeMessages($messages);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Paramètres manquants']);
        }
        break;
        
    default:
        echo json_encode(['success' => false, 'error' => 'Action non reconnue']);
}
?>
