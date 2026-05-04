<?php

class Publication {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getAll() {

        $stmt = $this->db->query("
            SELECT p.*, u.nom
            FROM publication p
            JOIN utilisateur u 
            ON p.id_utilisateur = u.id_utilisateur
            ORDER BY p.date_publication DESC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {

        $stmt = $this->db->prepare("
            SELECT * 
            FROM publication 
            WHERE id_publication = ?
        ");

        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($userId, $titre, $contenu, $image) {

        $stmt = $this->db->prepare("
            INSERT INTO publication 
            (id_utilisateur, titre, contenu, image, date_publication)
            VALUES (?, ?, ?, ?, NOW())
        ");

        return $stmt->execute([$userId, $titre, $contenu, $image]);
    }

    public function update($id, $titre, $contenu, $image = null) {

        if ($image) {
            $stmt = $this->db->prepare("
                UPDATE publication
                SET titre = ?, contenu = ?, image = ?
                WHERE id_publication = ?
            ");

            return $stmt->execute([$titre, $contenu, $image, $id]);
        }

        $stmt = $this->db->prepare("
            UPDATE publication
            SET titre = ?, contenu = ?
            WHERE id_publication = ?
        ");

        return $stmt->execute([$titre, $contenu, $id]);
    }

    public function delete($id) {

        $stmt = $this->db->prepare("
            DELETE FROM publication 
            WHERE id_publication = ?
        ");

        return $stmt->execute([$id]);
    }
    public function getAllAdmin() {
    $stmt = $this->db->query("
        SELECT p.*, u.nom
        FROM publication p
        JOIN utilisateur u ON p.id_utilisateur = u.id_utilisateur
        ORDER BY p.date_publication DESC
    ");

    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
public function getLikes($id) {

    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM reaction 
        WHERE id_publication = ? AND type_reaction = 'like'
    ");

    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}

public function getDislikes($id) {

    $stmt = $this->db->prepare("
        SELECT COUNT(*) 
        FROM reaction 
        WHERE id_publication = ? AND type_reaction = 'dislike'
    ");

    $stmt->execute([$id]);
    return $stmt->fetchColumn();
}
}
