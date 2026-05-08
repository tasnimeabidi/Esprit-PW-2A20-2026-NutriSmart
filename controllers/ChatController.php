<?php
class ChatController {
    private $db;
    
    public function __construct() {
        require_once __DIR__ . '/../Models/config.php';
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    // Envoyer un message
    public function sendMessage($user_id, $message, $is_admin = false, $page = 'recette', $user_name = 'Client') {
        try {
            $query = "INSERT INTO chat_messages (user_id, user_name, message, is_admin, page) 
                      VALUES (:user_id, :user_name, :message, :is_admin, :page)";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':user_name', $user_name);
            $stmt->bindParam(':message', $message);
            $stmt->bindParam(':is_admin', $is_admin, PDO::PARAM_BOOL);
            $stmt->bindParam(':page', $page);
            
            if ($stmt->execute()) {
                return [
                    'success' => true,
                    'message_id' => $this->db->lastInsertId()
                ];
            }
            return ['success' => false, 'error' => 'Erreur lors de l\'envoi du message'];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Récupérer les messages d'une conversation
    public function getMessages($user_id, $page = 'recette', $limit = 50) {
        try {
            $query = "SELECT * FROM chat_messages 
                      WHERE user_id = :user_id AND page = :page 
                      ORDER BY created_at ASC 
                      LIMIT :limit";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':page', $page);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return [
                'success' => true,
                'messages' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Récupérer tous les utilisateurs avec messages (pour l'admin)
    public function getAllConversations($page = null) {
        try {
            $query = "SELECT DISTINCT user_id, user_name, page, 
                      MAX(created_at) as last_message_time,
                      SUM(CASE WHEN is_admin = 0 AND is_read = 0 THEN 1 ELSE 0 END) as unread_count
                      FROM chat_messages";
            
            if ($page) {
                $query .= " WHERE page = :page";
            }
            
            $query .= " GROUP BY user_id, user_name, page 
                        ORDER BY last_message_time DESC";
            
            $stmt = $this->db->prepare($query);
            
            if ($page) {
                $stmt->bindParam(':page', $page);
            }
            
            $stmt->execute();
            
            return [
                'success' => true,
                'conversations' => $stmt->fetchAll(PDO::FETCH_ASSOC)
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Marquer les messages comme lus
    public function markAsRead($user_id, $page) {
        try {
            $query = "UPDATE chat_messages 
                      SET is_read = 1 
                      WHERE user_id = :user_id AND page = :page AND is_admin = 0";
            $stmt = $this->db->prepare($query);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':page', $page);
            
            return ['success' => $stmt->execute()];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    // Compter les messages non lus
    public function getUnreadCount($user_id = null, $page = null) {
        try {
            $query = "SELECT COUNT(*) as count FROM chat_messages WHERE is_read = 0 AND is_admin = 0";
            $params = [];
            
            if ($user_id) {
                $query .= " AND user_id = :user_id";
                $params[':user_id'] = $user_id;
            }
            
            if ($page) {
                $query .= " AND page = :page";
                $params[':page'] = $page;
            }
            
            $stmt = $this->db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return [
                'success' => true,
                'count' => (int)$result['count']
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>
