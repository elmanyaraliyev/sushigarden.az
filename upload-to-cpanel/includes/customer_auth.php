<?php
// Müştəri qeydiyyat/giriş sistemi — admin girişindən tamamilə ayrıdır
// (fərqli sessiya açarları, fərqli cədvəl). Qonaq (qeydiyyatsız) sifariş
// vermə imkanı da qalır — bu, ƏLAVƏ bir seçimdir, məcburi deyil.
require_once __DIR__ . '/functions.php';

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function sg_current_customer() {
    if (empty($_SESSION['customer_id'])) return null;
    return [
        'id' => $_SESSION['customer_id'],
        'name' => $_SESSION['customer_name'] ?? '',
        'phone' => $_SESSION['customer_phone'] ?? '',
    ];
}

function sg_customer_register($name, $phone, $email, $password) {
    $name = trim($name);
    $phone = preg_replace('/\D+/', '', (string)$phone);
    $email = trim((string)$email);

    if ($name === '') return ['ok' => false, 'error' => 'Adınızı daxil edin.'];
    if (!sg_valid_az_phone($phone)) return ['ok' => false, 'error' => 'Düzgün mobil nömrə daxil edin (məs. 050 123 45 67).'];
    if (sg_is_phone_blocked($phone)) return ['ok' => false, 'error' => 'Bu nömrə ilə qeydiyyatdan keçmək mümkün deyil.'];
    if (strlen($password) < 6) return ['ok' => false, 'error' => 'Şifrə ən azı 6 simvol olmalıdır.'];

    $pdo = sg_db();
    $exists = $pdo->prepare('SELECT id FROM customers WHERE phone = ?');
    $exists->execute([$phone]);
    if ($exists->fetch()) {
        return ['ok' => false, 'error' => 'Bu telefon nömrəsi ilə artıq hesab mövcuddur. Daxil olmağa cəhd edin.'];
    }

    $stmt = $pdo->prepare('INSERT INTO customers (name, phone, email, password_hash) VALUES (?, ?, ?, ?)');
    $stmt->execute([$name, $phone, $email ?: null, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int)$pdo->lastInsertId();

    session_regenerate_id(true);
    $_SESSION['customer_id'] = $id;
    $_SESSION['customer_name'] = $name;
    $_SESSION['customer_phone'] = $phone;

    return ['ok' => true, 'id' => $id];
}

function sg_customer_login($phone, $password) {
    $phone = preg_replace('/\D+/', '', (string)$phone);
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM customers WHERE phone = ?');
    $stmt->execute([$phone]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$customer || !password_verify($password, $customer['password_hash'])) {
        return ['ok' => false, 'error' => 'Telefon nömrəsi və ya şifrə səhvdir.'];
    }
    session_regenerate_id(true);
    $_SESSION['customer_id'] = (int)$customer['id'];
    $_SESSION['customer_name'] = $customer['name'];
    $_SESSION['customer_phone'] = $customer['phone'];
    return ['ok' => true, 'id' => (int)$customer['id']];
}

function sg_customer_logout() {
    unset($_SESSION['customer_id'], $_SESSION['customer_name'], $_SESSION['customer_phone']);
}

function sg_customer_orders($customerId) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC');
    $stmt->execute([$customerId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
