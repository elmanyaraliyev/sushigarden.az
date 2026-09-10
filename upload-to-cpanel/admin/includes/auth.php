<?php
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sg_current_admin() {
    return isset($_SESSION['admin_id']) ? ['id' => $_SESSION['admin_id'], 'username' => $_SESSION['admin_username']] : null;
}

function sg_require_login() {
    if (!sg_current_admin()) {
        header('Location: index.php');
        exit;
    }
}

function sg_admin_login($username, $password) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE username = ?');
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_username'] = $user['username'];
        return true;
    }
    return false;
}

function sg_admin_logout() {
    $_SESSION = [];
    session_destroy();
}
