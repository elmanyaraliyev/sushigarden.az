<?php
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sg_current_admin() {
    return isset($_SESSION['admin_id'])
        ? ['id' => $_SESSION['admin_id'], 'username' => $_SESSION['admin_username'], 'role' => $_SESSION['admin_role'] ?? 'admin']
        : null;
}

function sg_is_owner_admin() {
    $a = sg_current_admin();
    return $a && $a['role'] === 'admin';
}

function sg_require_login() {
    if (!sg_current_admin()) {
        header('Location: index.php');
        exit;
    }
}

/**
 * "Sifariş meneceri" (role=staff) hesabları YALNIZ sifariş bölmələrinə daxil
 * ola bilər — bu funksiya digər bütün admin səhifələrinin başında çağırılır
 * və staff hesabı üçün avtomatik orders.php-ə yönləndirir.
 */
function sg_require_owner() {
    sg_require_login();
    if (!sg_is_owner_admin()) {
        header('Location: orders.php');
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
        $_SESSION['admin_role'] = $user['role'] ?? 'admin';
        return true;
    }
    return false;
}

function sg_admin_logout() {
    $_SESSION = [];
    session_destroy();
}
