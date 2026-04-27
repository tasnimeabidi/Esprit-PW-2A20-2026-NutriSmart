<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../Models/Publication.php';
require_once __DIR__ . '/../Models/Commentaire.php';

/* =========================
   DATABASE CONNECTION
========================= */
$db = (new Database())->connect();

/* =========================
   MODELS
========================= */
$postModel = new Publication($db);
$commentModel = new Commentaire($db);

/* =========================
   ACTION ROUTING
========================= */
$action = $_GET['action'] ?? 'blog';

/* =========================
   BLOG PAGE
========================= */
if ($action === "blog") {

    $posts = $postModel->getAll();

    if (!is_array($posts)) {
        $posts = [];
    }

    $commentModelForView = $commentModel;

    require __DIR__ . '/../Views/frontoffice/blog.php';
    exit;
}

/* =========================
   CREATE POST
========================= */
if ($action === "create") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    if ($titre === '' || $contenu === '') {
        header("Location: index.php?action=blog");
        exit;
    }

    $image = null;

    if (!empty($_FILES['image']['name'])) {

        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            header("Location: index.php?action=blog&error=invalid_file");
            exit;
        }

        $uploadDir = __DIR__ . "/../public/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $image = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
    }

    $postModel->create($_SESSION['id_utilisateur'], $titre, $contenu, $image);

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   DELETE POST
========================= */
if ($action === "delete") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $id = $_GET['id'] ?? null;

    if ($id) {
        $post = $postModel->getById($id);

        if ($post && $post['id_utilisateur'] == $_SESSION['id_utilisateur']) {
            $postModel->delete($id);
        }
    }

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   UPDATE POST
========================= */
if ($action === "update") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $id = $_POST['id'] ?? null;

    if (!$id) {
        header("Location: index.php?action=blog");
        exit;
    }

    $post = $postModel->getById($id);

    if (!$post || $post['id_utilisateur'] != $_SESSION['id_utilisateur']) {
        header("Location: index.php?action=blog");
        exit;
    }

    $titre = trim($_POST['titre'] ?? '');
    $contenu = trim($_POST['contenu'] ?? '');

    $image = $post['image'];

    if (!empty($_FILES['image']['name'])) {

        $allowedTypes = ['image/jpeg', 'image/png'];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);

        if (!in_array($fileType, $allowedTypes)) {
            header("Location: index.php?action=blog&error=invalid_file");
            exit;
        }

        $uploadDir = __DIR__ . "/../public/uploads/";

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $image = time() . "_" . $_FILES['image']['name'];
        move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $image);
    }

    $postModel->update($id, $titre, $contenu, $image);

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   ADD COMMENT
========================= */
if ($action === "add_comment") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $postId = $_POST['id_publication'] ?? null;
    $contenu = trim($_POST['contenu'] ?? '');

    if ($postId && $contenu !== '' && strlen($contenu) <= 500) {
        $commentModel->create(
            $postId,
            $_SESSION['id_utilisateur'],
            $contenu
        );
    }

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   UPDATE COMMENT
========================= */
if ($action === "update_comment") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $id = $_POST['id_commentaire'] ?? null;
    $contenu = trim($_POST['contenu'] ?? '');

    if ($id) {

        $comment = $commentModel->getById($id);

        if ($comment && $comment['id_utilisateur'] == $_SESSION['id_utilisateur']) {
            $commentModel->update($id, $contenu);
        }
    }

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   DELETE COMMENT
========================= */
if ($action === "delete_comment") {

    if (!isset($_SESSION['id_utilisateur'])) {
        header("Location: index.php?action=login");
        exit;
    }

    $id = $_GET['id'] ?? null;

    if ($id) {

        $comment = $commentModel->getById($id);

        if ($comment && $comment['id_utilisateur'] == $_SESSION['id_utilisateur']) {
            $commentModel->delete($id);
        }
    }

    header("Location: index.php?action=blog");
    exit;
}

/* =========================
   AJAX UPDATE COMMENT
========================= */
if ($action === "update_comment_ajax") {

    if (!isset($_SESSION['id_utilisateur'])) {
        echo "error_no_session";
        exit;
    }

    $id = $_POST['id'] ?? null;
    $contenu = trim($_POST['contenu'] ?? '');

    if (!$id || $contenu === '') {
        echo "error_empty";
        exit;
    }

    $comment = $commentModel->getById($id);

    if (!$comment) {
        echo "error_not_found";
        exit;
    }

    if ($comment['id_utilisateur'] != $_SESSION['id_utilisateur']) {
        echo "error_unauthorized";
        exit;
    }

    $result = $commentModel->update($id, $contenu);

    echo $result ? "ok" : "error_db";
    exit;
}