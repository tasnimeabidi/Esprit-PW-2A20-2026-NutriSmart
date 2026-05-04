<?php
class Notification {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /* ── Create a notification ── */
    public function create($userId, $message) {
        $stmt = $this->db->prepare("
            INSERT INTO notification (id_utilisatuer, message, is_read, date_notification)
            VALUES (?, ?, 0, NOW())
        ");
        return $stmt->execute([$userId, $message]);
    }

    /* ── Get all notifications for a user ── */
    public function getForUser($userId) {
        $stmt = $this->db->prepare("
            SELECT * FROM notification
            WHERE id_utilisatuer = ?
            ORDER BY date_notification DESC
            LIMIT 20
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /* ── Count unread notifications ── */
    public function countUnread($userId) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notification
            WHERE id_utilisatuer = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    /* ── Mark all as read for a user ── */
    public function markAllRead($userId) {
        $stmt = $this->db->prepare("
            UPDATE notification SET is_read = 1
            WHERE id_utilisatuer = ?
        ");
        return $stmt->execute([$userId]);
    }

    /* ── Mark single notification as read ── */
    public function markRead($notifId, $userId) {
        $stmt = $this->db->prepare("
            UPDATE notification SET is_read = 1
            WHERE id_notification = ? AND id_utilisatuer = ?
        ");
        return $stmt->execute([$notifId, $userId]);
    }
}