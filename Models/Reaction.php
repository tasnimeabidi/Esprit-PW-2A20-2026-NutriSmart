<?php
class Reaction {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /* ── Get counts for a post ── */
    public function countForPost($postId) {
        $stmt = $this->db->prepare("
            SELECT type_reaction, COUNT(*) as total
            FROM reaction
            WHERE id_publication = ? AND id_commentaire IS NULL
            GROUP BY type_reaction
        ");
        $stmt->execute([$postId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['like' => 0, 'dislike' => 0];
        foreach ($rows as $r) {
            $result[$r['type_reaction']] = (int)$r['total'];
        }
        return $result;
    }

    /* ── Get counts for a comment ── */
    public function countForComment($commentId) {
        $stmt = $this->db->prepare("
            SELECT type_reaction, COUNT(*) as total
            FROM reaction
            WHERE id_commentaire = ?
            GROUP BY type_reaction
        ");
        $stmt->execute([$commentId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result = ['like' => 0, 'dislike' => 0];
        foreach ($rows as $r) {
            $result[$r['type_reaction']] = (int)$r['total'];
        }
        return $result;
    }

    /* ── Get current user reaction on a post ── */
    public function getUserReactionOnPost($userId, $postId) {
        $stmt = $this->db->prepare("
            SELECT type_reaction FROM reaction
            WHERE id_utilisateur = ? AND id_publication = ? AND id_commentaire IS NULL
        ");
        $stmt->execute([$userId, $postId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['type_reaction'] : null;
    }

    /* ── Get current user reaction on a comment ── */
    public function getUserReactionOnComment($userId, $commentId) {
        $stmt = $this->db->prepare("
            SELECT type_reaction FROM reaction
            WHERE id_utilisateur = ? AND id_commentaire = ?
        ");
        $stmt->execute([$userId, $commentId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $row['type_reaction'] : null;
    }

    /* ── Toggle reaction on a post ── */
    public function toggleOnPost($userId, $postId, $type) {
        $existing = $this->getUserReactionOnPost($userId, $postId);

        if ($existing === $type) {
            // Same reaction → remove it
            $stmt = $this->db->prepare("
                DELETE FROM reaction
                WHERE id_utilisateur = ? AND id_publication = ? AND id_commentaire IS NULL
            ");
            $stmt->execute([$userId, $postId]);
            return 'removed';
        } elseif ($existing) {
            // Different reaction → update it
            $stmt = $this->db->prepare("
                UPDATE reaction SET type_reaction = ?, date_reaction = NOW()
                WHERE id_utilisateur = ? AND id_publication = ? AND id_commentaire IS NULL
            ");
            $stmt->execute([$type, $userId, $postId]);
            return 'updated';
        } else {
            // No reaction yet → insert
            $stmt = $this->db->prepare("
                INSERT INTO reaction (id_utilisateur, id_publication, id_commentaire, type_reaction, date_reaction)
                VALUES (?, ?, NULL, ?, NOW())
            ");
            $stmt->execute([$userId, $postId, $type]);
            return 'added';
        }
    }

    /* ── Toggle reaction on a comment ── */
    public function toggleOnComment($userId, $commentId, $postId, $type) {
        $existing = $this->getUserReactionOnComment($userId, $commentId);

        if ($existing === $type) {
            $stmt = $this->db->prepare("
                DELETE FROM reaction
                WHERE id_utilisateur = ? AND id_commentaire = ?
            ");
            $stmt->execute([$userId, $commentId]);
            return 'removed';
        } elseif ($existing) {
            $stmt = $this->db->prepare("
                UPDATE reaction SET type_reaction = ?, date_reaction = NOW()
                WHERE id_utilisateur = ? AND id_commentaire = ?
            ");
            $stmt->execute([$type, $userId, $commentId]);
            return 'updated';
        } else {
            $stmt = $this->db->prepare("
                INSERT INTO reaction (id_utilisateur, id_publication, id_commentaire, type_reaction, date_reaction)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $postId, $commentId, $type]);
            return 'added';
        }
    }
}