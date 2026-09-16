<?php
require_once __DIR__ . '/../../includes/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sg_current_admin() {
    return isset($_SESSION['admin_id'])
        ? [
            'id' => $_SESSION['admin_id'],
            'username' => $_SESSION['admin_username'],
            'role' => $_SESSION['admin_role'] ?? 'admin',
            'permissions' => array_filter(explode(',', $_SESSION['admin_permissions'] ?? '')),
        ]
        : null;
}

function sg_is_owner_admin() {
    $a = sg_current_admin();
    return $a && $a['role'] === 'admin';
}

/**
 * Sahibkarın "Sifariş meneceri" hesablarına ayrıca verə biləcəyi səlahiyyətlər —
 * açar => admin/includes/header.php-də göstərilən etiket. users.php-dəki
 * checkbox-lar və hər səhifənin başındakı sg_require_permission() çağırışı
 * bu SİYAHIDAKI eyni açarlardan istifadə etməlidir.
 */
function sg_staff_permissions() {
    return [
        'products'  => 'Menyu (məhsul və kateqoriyalar)',
        'gallery'   => 'Qalereya',
        'customers' => 'İstifadəçilər (müştəri siyahısı)',
        'reviews'   => 'Rəylər',
    ];
}

/**
 * Sahibkar həmişə hər şeyə çıxışlıdır; "sifariş meneceri" yalnız ona
 * xüsusi verilmiş açarlar üçün true qaytarır.
 */
function sg_admin_has_permission($key) {
    if (sg_is_owner_admin()) return true;
    $a = sg_current_admin();
    return $a && in_array($key, $a['permissions'], true);
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

/**
 * Sahibkar hesabları həmişə keçir; "sifariş meneceri" hesabları isə yalnız
 * sahibkarın onlara açıq şəkildə verdiyi $key səlahiyyəti varsa keçir —
 * əks halda sifarişlər səhifəsinə yönləndirilir.
 */
function sg_require_permission($key) {
    sg_require_login();
    if (!sg_admin_has_permission($key)) {
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
        $_SESSION['admin_permissions'] = $user['permissions'] ?? '';
        return true;
    }
    return false;
}

function sg_admin_logout() {
    $_SESSION = [];
    session_destroy();
}
