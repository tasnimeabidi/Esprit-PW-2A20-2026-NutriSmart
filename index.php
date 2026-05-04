<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ========================= CORE DEPENDENCIES ========================= */
require_once "config/database.php";
require_once "Models/User.php";
require_once "Models/Publication.php";
require_once "Models/Commentaire.php";
require_once "Models/Reaction.php";
require_once "Models/Notification.php";

/* Controllers */
require_once "controllers/AuthController.php";

/* ========================= DATABASE ========================= */
$db = (new Database())->connect();

/* ========================= ROUTING ========================= */
$action = $_GET['action'] ?? 'home';

/* ========================= AUTH ROUTES ========================= */
if ($action === "login") {
    (new AuthController($db))->login(); exit;
}
if ($action === "register") {
    (new AuthController($db))->register(); exit;
}
if ($action === "logout") {
    session_unset();
    session_destroy();
    header("Location: index.php?action=login"); exit;
}

/* ========================= BLOG + POSTS + COMMENTS + REACTIONS + NOTIFICATIONS ========================= */
if (in_array($action, [
    "blog",
    "create",
    "delete",
    "update",
    "add_comment",
    "update_comment",
    "delete_comment",
    "update_comment_ajax",
    "react_post",
    "react_comment",
    "get_notifications",
    "mark_notifications_read"
])) {
    require "controllers/PublicationController.php"; exit;
}

/* ========================= ADMIN ========================= */
if ($action === "admin_dashboard") {
    require "Views/backoffice/nutrismart-dashboard.php"; exit;
}

/* ========================= DEFAULT (HOME PAGE) ========================= */
require "Views/frontoffice/nutrismart-website.php";
exit;