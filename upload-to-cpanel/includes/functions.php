<?php
require_once __DIR__ . '/db.php';

function h($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function sg_money($n) {
    $n = (float)$n;
    if ($n == floor($n)) {
        return number_format($n, 0, ',', ' ') . ' ₼';
    }
    return number_format($n, 2, ',', ' ') . ' ₼';
}

/**
 * Aktiv kateqoriyaları (və içindəki aktiv məhsulları) sıra ilə qaytarır.
 * Performans üçün TƏK sorğu ilə (JOIN) — kateqoriya sayı artsa belə
 * bazaya gedən sorğu sayı sabit qalır (əvvəlki versiyada hər kateqoriya
 * üçün ayrı sorğu var idi).
 */
function sg_get_menu($onlyActive = true) {
    $pdo = sg_db();
    $catWhere = $onlyActive ? 'WHERE c.active = 1' : '';
    $prodActiveClause = $onlyActive ? 'AND p.active = 1' : '';

    $sql = "
        SELECT
            c.id AS c_id, c.name AS c_name, c.name_en AS c_name_en, c.name_ru AS c_name_ru,
            c.sort_order AS c_sort, c.active AS c_active,
            p.id AS p_id, p.name AS p_name, p.name_en AS p_name_en, p.name_ru AS p_name_ru,
            p.description AS p_description, p.description_en AS p_description_en, p.description_ru AS p_description_ru,
            p.price AS p_price, p.image AS p_image, p.active AS p_active,
            p.featured AS p_featured, p.sort_order AS p_sort
        FROM categories c
        LEFT JOIN products p ON p.category_id = c.id $prodActiveClause
        $catWhere
        ORDER BY c.sort_order ASC, c.id ASC, p.sort_order ASC, p.id ASC
    ";
    $rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    $cats = [];
    foreach ($rows as $row) {
        $cid = $row['c_id'];
        if (!isset($cats[$cid])) {
            $cats[$cid] = [
                'id' => $cid,
                'name' => $row['c_name'],
                'name_en' => $row['c_name_en'],
                'name_ru' => $row['c_name_ru'],
                'sort_order' => $row['c_sort'],
                'active' => $row['c_active'],
                'products' => [],
            ];
        }
        if ($row['p_id'] !== null) {
            $cats[$cid]['products'][] = [
                'id' => $row['p_id'],
                'name' => $row['p_name'],
                'name_en' => $row['p_name_en'],
                'name_ru' => $row['p_name_ru'],
                'description' => $row['p_description'],
                'description_en' => $row['p_description_en'],
                'description_ru' => $row['p_description_ru'],
                'price' => $row['p_price'],
                'image' => $row['p_image'],
                'active' => $row['p_active'],
                'featured' => $row['p_featured'],
            ];
        }
    }
    return array_values($cats);
}

/** Ön səhifədə göstərilən "Tövsiyə olunanlar" seçimi (featured=1, aktiv, foto ilə/foto siz fərq etməz). */
function sg_get_featured($limit = 4) {
    $pdo = sg_db();
    $stmt = $pdo->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        JOIN categories c ON c.id = p.category_id
        WHERE p.featured = 1 AND p.active = 1 AND c.active = 1
        ORDER BY p.sort_order ASC, p.id ASC
        LIMIT ?
    ");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sg_all_categories() {
    $pdo = sg_db();
    return $pdo->query('SELECT * FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
}

function sg_category($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sg_product($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function sg_next_sort_order($table, $categoryId = null) {
    $pdo = sg_db();
    if ($table === 'products' && $categoryId !== null) {
        $stmt = $pdo->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM products WHERE category_id = ?');
        $stmt->execute([$categoryId]);
    } else {
        $stmt = $pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM categories');
    }
    return (int)$stmt->fetchColumn();
}

/**
 * Base64 (data URL) şəklini serverdə saxlayır: JPEG-ə çevirir, uyğun
 * ölçüyə salır (maks. en 900px) və /uploads/products qovluğuna yazır.
 * Uğurlu olarsa fayl adını, olmazsa null qaytarır.
 */
function sg_save_cropped_image($dataUrl) {
    if (strpos($dataUrl, 'data:image') !== 0) {
        return null;
    }
    $comma = strpos($dataUrl, ',');
    if ($comma === false) return null;
    $binary = base64_decode(substr($dataUrl, $comma + 1));
    if ($binary === false || strlen($binary) < 10) return null;

    if (!sg_ensure_writable_dir(SG_UPLOADS_DIR)) {
        error_log('Sushi Garden: uploads/products qovluğu yazıla bilmir: ' . SG_UPLOADS_DIR);
        return null;
    }

    $filename = 'p' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
    $path = SG_UPLOADS_DIR . '/' . $filename;

    $written = false;
    $img = @imagecreatefromstring($binary);
    if ($img) {
        $maxW = 900;
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $maxW) {
            $newH = (int)round($h * ($maxW / $w));
            $resized = imagecreatetruecolor($maxW, $newH);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }
        $written = @imagejpeg($img, $path, 86);
        imagedestroy($img);
    }

    if (!$written) {
        // GD yazısı uğursuz oldu — xam faylı birbaşa diskə yazmağa cəhd et (son ehtiyat variant)
        $written = @file_put_contents($path, $binary) !== false;
    }

    if (!$written || !is_file($path)) {
        error_log('Sushi Garden: şəkil diskə yazıla bilmədi: ' . $path);
        return null;
    }

    @chmod($path, 0644);
    return $filename;
}

/**
 * Qovluğun mövcud və yazılabilən olmasını təmin edir (yoxdursa yaradır,
 * icazələri düzəltməyə çalışır). Uğurlu olarsa true qaytarır.
 */
function sg_ensure_writable_dir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (is_dir($dir) && !is_writable($dir)) {
        @chmod($dir, 0755);
    }
    return is_dir($dir) && is_writable($dir);
}

/**
 * Görünüş bölməsindən (loqo/hero) yüklənən şəkli SG_ROOT/uploads/branding/-ə
 * saxlayır və saytdan istifadə üçün nisbi yolu ("uploads/branding/xxx.jpg") qaytarır.
 * Uğursuz olarsa null qaytarır.
 */
function sg_save_branding_upload($fileArray, $maxW = 1200) {
    if (empty($fileArray) || !isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $binary = @file_get_contents($fileArray['tmp_name']);
    if ($binary === false) return null;

    $dir = SG_ROOT . '/uploads/branding';
    if (!sg_ensure_writable_dir($dir)) {
        error_log('Sushi Garden: uploads/branding qovluğu yazıla bilmir: ' . $dir);
        return null;
    }

    $filename = 'b' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
    $path = $dir . '/' . $filename;

    $written = false;
    $img = @imagecreatefromstring($binary);
    if ($img) {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w > $maxW) {
            $newH = (int)round($h * ($maxW / $w));
            $resized = imagecreatetruecolor($maxW, $newH);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $maxW, $newH, $w, $h);
            imagedestroy($img);
            $img = $resized;
        }
        $written = @imagejpeg($img, $path, 88);
        imagedestroy($img);
    }

    if (!$written) {
        $written = @file_put_contents($path, $binary) !== false;
    }

    if (!$written || !is_file($path)) {
        error_log('Sushi Garden: brendinq şəkli diskə yazıla bilmədi: ' . $path);
        return null;
    }

    @chmod($path, 0644);
    return 'uploads/branding/' . $filename;
}

function sg_delete_product_image($filename) {
    if (!$filename) return;
    $path = SG_UPLOADS_DIR . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

function sg_csrf_token() {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf'];
}

/**
 * assets/ altındakı brend şəkilləri üçün <picture> çıxarır: WebP dəstəklənirsə
 * daha kiçik webp faylı, dəstəklənməzsə JPEG ehtiyat variantı yüklənir.
 * $base — uzantısız yol, məs. "assets/logo-icon" (.jpg və .webp faylları olmalıdır).
 */
function sg_picture($base, $alt, $imgAttrs = '') {
    printf(
        '<picture><source srcset="%1$s.webp" type="image/webp"><img src="%1$s.jpg" alt="%2$s" %3$s></picture>',
        h($base), h($alt), $imgAttrs
    );
}

function sg_csrf_check($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    return !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], (string)$token);
}

/* ---------------------------------------------------------------------
 * Sayt parametrləri (site_settings k/v cədvəli) — restoran, əlaqə,
 * sosial şəbəkə və görünüş məlumatları admin paneldən buradan idarə olunur.
 * ------------------------------------------------------------------- */

function sg_setting($key, $default = '') {
    static $cache = null;
    if ($cache === null) {
        $pdo = sg_db();
        $cache = [];
        foreach ($pdo->query('SELECT k, v FROM site_settings')->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cache[$row['k']] = $row['v'];
        }
    }
    return array_key_exists($key, $cache) && $cache[$key] !== '' ? $cache[$key] : $default;
}

function sg_set_setting($key, $value) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('INSERT INTO site_settings (k, v) VALUES (?, ?) ON CONFLICT(k) DO UPDATE SET v = excluded.v');
    $stmt->execute([$key, (string)$value]);
}

function sg_hours() {
    $raw = sg_setting('hours', '');
    $decoded = $raw ? json_decode($raw, true) : null;
    return is_array($decoded) ? $decoded : [];
}

function sg_day_labels() {
    return [
        'mon' => 'Bazar ertəsi', 'tue' => 'Çərşənbə axşamı', 'wed' => 'Çərşənbə',
        'thu' => 'Cümə axşamı', 'fri' => 'Cümə', 'sat' => 'Şənbə', 'sun' => 'Bazar',
    ];
}

/* ---------------------------------------------------------------------
 * Sifarişlər
 * ------------------------------------------------------------------- */

function sg_service_types() {
    return ['delivery', 'takeaway', 'dine_in'];
}

function sg_service_type_label($type) {
    $map = ['delivery' => 'Çatdırılma', 'takeaway' => 'Özü aparma', 'dine_in' => 'Restoranda'];
    return $map[$type] ?? $type;
}

function sg_order_statuses() {
    return ['pending', 'preparing', 'ready', 'completed', 'cancelled'];
}

function sg_order_status_label($status) {
    $map = [
        'pending' => 'Gözləyir', 'preparing' => 'Hazırlanır', 'ready' => 'Hazırdır',
        'completed' => 'Tamamlandı', 'cancelled' => 'Ləğv edilib',
    ];
    return $map[$status] ?? $status;
}

/**
 * Sifarişi yaradır — qiymətləri müştəri tərəfindən göndərilən dəyərlərə
 * yox, bazadakı REAL qiymətlərə əsasən özü hesablayır (manipulyasiyanın
 * qarşısını almaq üçün). $rawItems: [{id, qty}, ...]
 */
function sg_create_order($data, $rawItems) {
    $pdo = sg_db();

    $items = [];
    $subtotal = 0.0;
    foreach ($rawItems as $it) {
        $pid = (int)($it['id'] ?? 0);
        $qty = max(1, min(50, (int)($it['qty'] ?? 0)));
        if ($pid <= 0 || $qty <= 0) continue;
        $product = sg_product($pid);
        if (!$product || !$product['active']) continue;
        $lineTotal = round((float)$product['price'] * $qty, 2);
        $subtotal += $lineTotal;
        $items[] = [
            'product_id' => $pid,
            'name' => $product['name'],
            'price' => (float)$product['price'],
            'qty' => $qty,
            'line_total' => $lineTotal,
        ];
    }
    if (!$items) {
        return ['ok' => false, 'errors' => ['Səbətiniz boşdur.']];
    }

    $tip = max(0.0, min(500.0, (float)($data['tip'] ?? 0)));
    $subtotal = round($subtotal, 2);
    $total = round($subtotal + $tip, 2);

    $serviceType = in_array($data['service_type'] ?? '', sg_service_types(), true) ? $data['service_type'] : 'delivery';
    $name = trim((string)($data['name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));

    $errors = [];
    if ($name === '') $errors[] = 'Adınızı daxil edin.';
    if ($phone === '') $errors[] = 'Telefon nömrənizi daxil edin.';
    if ($serviceType === 'delivery' && $address === '') $errors[] = 'Çatdırılma ünvanını daxil edin.';
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO orders (customer_name, customer_phone, service_type, address, subtotal, tip, total, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $phone, $serviceType, $address, $subtotal, $tip, $total, 'pending']);
        $orderId = (int)$pdo->lastInsertId();

        $itemStmt = $pdo->prepare('
            INSERT INTO order_items (order_id, product_id, name, price, qty, line_total)
            VALUES (?, ?, ?, ?, ?, ?)
        ');
        foreach ($items as $it) {
            $itemStmt->execute([$orderId, $it['product_id'], $it['name'], $it['price'], $it['qty'], $it['line_total']]);
        }
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        return ['ok' => false, 'errors' => ['Sifariş yadda saxlanılmadı, yenidən cəhd edin.']];
    }

    return ['ok' => true, 'order_id' => $orderId, 'order_number' => '#' . $orderId, 'total' => $total];
}

function sg_get_orders($status = null) {
    $pdo = sg_db();
    if ($status) {
        $stmt = $pdo->prepare('SELECT * FROM orders WHERE status = ? ORDER BY id DESC');
        $stmt->execute([$status]);
    } else {
        $stmt = $pdo->query('SELECT * FROM orders ORDER BY id DESC');
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function sg_get_order($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ?');
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$order) return null;
    $itemsStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
    $itemsStmt->execute([$id]);
    $order['items'] = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    return $order;
}

function sg_update_order_status($id, $status) {
    if (!in_array($status, sg_order_statuses(), true)) return false;
    $pdo = sg_db();
    $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
    return $stmt->execute([$status, $id]);
}

function sg_delete_order($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    return $stmt->execute([$id]);
}
