<?php

class Commentaire {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    // Get comments for a post
    public function getByPostId($postId) {
        $stmt = $this->db->prepare("
            SELECT c.*, u.nom
            FROM commentaire c
            JOIN utilisateur u ON c.id_utilisateur = u.id_utilisateur
            WHERE c.id_publication = ?
            ORDER BY c.date_commentaire ASC
        ");

        $stmt->execute([$postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Add comment
    public function create($postId, $userId, $contenu) {
        $stmt = $this->db->prepare("
            INSERT INTO commentaire (id_publication, id_utilisateur, contenu)
            VALUES (?, ?, ?)
        ");

        return $stmt->execute([$postId, $userId, $contenu]);
    }

    // Update comment (ONLY OWNER WILL BE CHECKED IN CONTROLLER)
    public function update($id, $contenu) {
        $stmt = $this->db->prepare("
            UPDATE commentaire
            SET contenu = ?
            WHERE id_commentaire = ?
        ");

        return $stmt->execute([$contenu, $id]);
    }

    // Delete comment
    public function delete($id) {
        $stmt = $this->db->prepare("
            DELETE FROM commentaire
            WHERE id_commentaire = ?
        ");

        return $stmt->execute([$id]);
    }

    // Get single comment
    public function getById($id) {
        $stmt = $this->db->prepare("
            SELECT * FROM commentaire WHERE id_commentaire = ?
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}