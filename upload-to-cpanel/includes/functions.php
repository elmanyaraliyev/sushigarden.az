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
 * Kəsilmiş məhsul fotosunu (əsl multipart fayl yükləməsi kimi göndərilir —
 * base64 mətn sahəsi kimi DEYİL, çünki bəzi hostinqlərin ModSecurity/WAF
 * qaydaları çox uzun base64 mətn sahələrini sadəcə susaraq atır) serverdə
 * saxlayır: JPEG-ə çevirir, uyğun ölçüyə salır (maks. en 900px) və
 * /uploads/products qovluğuna yazır.
 *
 * Qaytarır: ['ok' => true, 'file' => 'p123.jpg'] və ya
 *           ['ok' => false, 'reason' => 'admin panelində göstəriləcək konkret səbəb']
 * — belə ki, uğursuz olanda admin dərhal DƏQIQ səbəbi görsün (icazə, limit, GD və s.),
 * ayrıca diaqnostika səhifəsinə ehtiyac qalmasın.
 */
function sg_save_product_photo($fileArray) {
    if (empty($fileArray) || !isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'reason' => 'Fayl serverə düzgün ötürülmədi (yükləmə xətası: kodu ' . h((string)($fileArray['error'] ?? '?')) . ').'];
    }
    $binary = @file_get_contents($fileArray['tmp_name']);
    if ($binary === false || strlen($binary) < 10) {
        return ['ok' => false, 'reason' => 'Müvəqqəti fayl oxuna bilmədi (server müvəqqəti qovluq problemi ola bilər).'];
    }

    if (!sg_ensure_writable_dir(SG_UPLOADS_DIR)) {
        error_log('Sushi Garden: uploads/products qovluğu yazıla bilmir: ' . SG_UPLOADS_DIR);
        return ['ok' => false, 'reason' => '"uploads/products" qovluğu yazıla bilmir (icazə problemi). cPanel → File Manager-də bu qovluğun üzərinə sağ klikləyib "Permissions" → 755 (olmasa 775) edin.'];
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
        $err = error_get_last();
        error_log('Sushi Garden: şəkil diskə yazıla bilmədi: ' . $path . ' — ' . ($err['message'] ?? ''));
        return ['ok' => false, 'reason' => 'Şəkil diskə yazıla bilmədi (disk yeri dolu ola bilər, ya da hostinqin "open_basedir" tənzimləməsi qovluğa yazışı bloklayır). Hostinq dəstəyinə "uploads/products qovluğuna PHP yazışını icazə verin" deyə müraciət edin.'];
    }

    @chmod($path, 0644);
    return ['ok' => true, 'file' => $filename];
}

/**
 * Qovluğa həqiqətən fayl yaradıla bilib-bilmədiyini əsl yazma cəhdi ilə yoxlayır.
 * PHP-nin is_writable()-i qovluqlar üçün ETIBARSIZDIR: yalnız "write" bitinə baxır,
 * "execute" (axtarış) bitini nəzərə almır — məs. 0644 icazəli qovluqda write biti
 * var deyə is_writable() true qaytarır, amma execute biti olmadığı üçün əslində
 * heç bir fayl yaradıla bilmir. Ona görə real fayl yazıb-silməklə yoxlayırıq.
 */
function sg_dir_actually_writable($dir) {
    if (!is_dir($dir)) return false;
    $test = rtrim($dir, '/') . '/.sg_write_test_' . getmypid() . '.tmp';
    $ok = @file_put_contents($test, 'x') !== false;
    if ($ok) @unlink($test);
    return $ok;
}

/**
 * Qovluğun mövcud və yazılabilən olmasını təmin edir (yoxdursa yaradır,
 * icazələri düzəltməyə çalışır). Uğurlu olarsa true qaytarır.
 */
function sg_ensure_writable_dir($dir) {
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    if (!is_dir($dir)) return false;

    // is_writable()-ə güvənmədən HƏMİŞƏ 0755 tətbiq edirik (execute biti daxil
    // olsun deyə), sonra əsl yazma testi ilə təsdiqləyirik.
    if (!sg_dir_actually_writable($dir)) @chmod($dir, 0755);
    if (!sg_dir_actually_writable($dir)) @chmod($dir, 0775);
    if (!sg_dir_actually_writable($dir)) @chmod($dir, 0777);

    return sg_dir_actually_writable($dir);
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

/**
 * Admin panelindən yüklənən bildiriş səs faylını (mp3/wav/ogg) uploads/sounds/-a
 * saxlayır. Uğurlu olarsa saytın kökünə nisbətən yol qaytarır (məs.
 * "uploads/sounds/xxx.mp3"), olmazsa null.
 */
function sg_save_sound_upload($fileArray, $maxBytes = 2097152) {
    if (empty($fileArray) || !isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    if ((int)$fileArray['size'] > $maxBytes) return null;

    $ext = strtolower(pathinfo($fileArray['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['mp3', 'wav', 'ogg'], true)) return null;

    $binary = @file_get_contents($fileArray['tmp_name']);
    if ($binary === false || strlen($binary) < 10) return null;

    $dir = SG_ROOT . '/uploads/sounds';
    if (!sg_ensure_writable_dir($dir)) {
        error_log('Sushi Garden: uploads/sounds qovluğu yazıla bilmir: ' . $dir);
        return null;
    }

    $filename = 's' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.' . $ext;
    $path = $dir . '/' . $filename;
    if (@file_put_contents($path, $binary) === false || !is_file($path)) {
        error_log('Sushi Garden: səs faylı diskə yazıla bilmədi: ' . $path);
        return null;
    }
    @chmod($path, 0644);
    return 'uploads/sounds/' . $filename;
}

/**
 * Qalereya şəklini uploads/gallery/-a saxlayır (loqo yükləməsi ilə eyni
 * GD-based kiçiltmə məntiqi). Nisbi yol qaytarır ("uploads/gallery/xxx.jpg").
 */
function sg_save_gallery_upload($fileArray, $maxW = 1400) {
    if (empty($fileArray) || !isset($fileArray['tmp_name']) || $fileArray['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $binary = @file_get_contents($fileArray['tmp_name']);
    if ($binary === false) return null;

    $dir = SG_ROOT . '/uploads/gallery';
    if (!sg_ensure_writable_dir($dir)) {
        error_log('Sushi Garden: uploads/gallery qovluğu yazıla bilmir: ' . $dir);
        return null;
    }

    $filename = 'g' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
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
        error_log('Sushi Garden: qalereya şəkli diskə yazıla bilmədi: ' . $path);
        return null;
    }
    @chmod($path, 0644);
    return 'uploads/gallery/' . $filename;
}

/**
 * "sakura" teması üçün admin-yüklənə bilən fon şəkli (məs. istifadəçinin öz
 * göndərdiyi Yaponiya mənzərəsi) — tam-eninə "hero" fon kimi göstərilir,
 * ona görə qalereya şəkillərindən fərqli olaraq daha geniş ölçüdə saxlanılır.
 */
function sg_save_theme_bg_upload($fileArray, $maxW = 1920) {
    if (empty($fileArray) || !isset($fileArray['tmp_name'])) {
        return ['ok' => false, 'reason' => 'Şəkil seçilmədi.'];
    }
    if ($fileArray['error'] !== UPLOAD_ERR_OK) {
        // UPLOAD_ERR_INI_SIZE/FORM_SIZE — fayl serverin icazə verdiyi maksimum ölçüdən
        // böyükdür; bu halda PHP faylı $_FILES-ə heç qoymur, admin isə "heç nə baş
        // vermədi" görür — ona görə səbəbi konkret izah edirik.
        $reasons = [
            UPLOAD_ERR_INI_SIZE => 'Şəkil serverin icazə verdiyi maksimum ölçüdən böyükdür (php.ini upload_max_filesize). Şəkli sıxışdırıb (məs. 2-3 MB-dan az) yenidən sınayın.',
            UPLOAD_ERR_FORM_SIZE => 'Şəkil çox böyükdür. Daha kiçik ölçüdə şəkil seçin.',
            UPLOAD_ERR_PARTIAL => 'Şəkil tam yüklənmədi, internet bağlantısı kəsilmiş ola bilər. Yenidən cəhd edin.',
            UPLOAD_ERR_NO_FILE => 'Şəkil seçilmədi.',
        ];
        return ['ok' => false, 'reason' => $reasons[$fileArray['error']] ?? ('Yükləmə xətası (kodu ' . h((string)$fileArray['error']) . ').')];
    }
    $binary = @file_get_contents($fileArray['tmp_name']);
    if ($binary === false || strlen($binary) < 10) {
        return ['ok' => false, 'reason' => 'Müvəqqəti fayl oxuna bilmədi (server müvəqqəti qovluq problemi ola bilər).'];
    }

    $dir = SG_ROOT . '/uploads/theme-bg';
    if (!sg_ensure_writable_dir($dir)) {
        error_log('Sushi Garden: uploads/theme-bg qovluğu yazıla bilmir: ' . $dir);
        return ['ok' => false, 'reason' => '"uploads/theme-bg" qovluğu yazıla bilmir (icazə problemi). cPanel → File Manager-də bu qovluğun icazəsini 755 (olmasa 775) edin.'];
    }

    $filename = 't' . time() . '_' . substr(bin2hex(random_bytes(4)), 0, 8) . '.jpg';
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
        $err = error_get_last();
        error_log('Sushi Garden: tema fon şəkli diskə yazıla bilmədi: ' . $path . ' — ' . ($err['message'] ?? ''));
        return ['ok' => false, 'reason' => 'Şəkil diskə yazıla bilmədi (disk yeri dolu ola bilər, ya da hostinqin icazələri buna mane olur).'];
    }
    @chmod($path, 0644);
    return ['ok' => true, 'file' => 'uploads/theme-bg/' . $filename];
}

function sg_delete_theme_bg($key) {
    $current = sg_setting($key, '');
    if ($current) {
        $path = SG_ROOT . '/' . $current;
        if (is_file($path)) @unlink($path);
    }
    sg_set_setting($key, '');
}

function sg_get_gallery_items($activeOnly = false) {
    $pdo = sg_db();
    $sql = 'SELECT * FROM gallery_items' . ($activeOnly ? ' WHERE active = 1' : '') . ' ORDER BY sort_order ASC, id ASC';
    return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

function sg_delete_gallery_item($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT image FROM gallery_items WHERE id = ?');
    $stmt->execute([$id]);
    $image = $stmt->fetchColumn();
    if ($image) {
        $path = SG_ROOT . '/' . $image;
        if (is_file($path)) @unlink($path);
    }
    $del = $pdo->prepare('DELETE FROM gallery_items WHERE id = ?');
    return $del->execute([$id]);
}

function sg_delete_product_image($filename) {
    if (!$filename) return;
    $path = SG_UPLOADS_DIR . '/' . $filename;
    if (is_file($path)) {
        @unlink($path);
    }
}

/**
 * Admin paneldən seçilə bilən sayt rəng temaları. Hər açar css/style.css-də
 * :root[data-color-theme="AÇAR"]{...} bloku ilə eyni olmalıdır. Struktur/tərtibat
 * dəyişmir — yalnız fon/vurğu rəngləri (bu funksiyadakı bg/accent/gold önizləmə üçündür).
 */
function sg_color_themes() {
    return [
        'forest'   => ['label' => 'Meşə Yaşılı', 'bg' => '#12261A', 'accent' => '#9DB49D', 'gold' => '#C9A96B'],
        'amber'    => ['label' => 'Narıncı Alov', 'bg' => '#241811', 'accent' => '#E0A458', 'gold' => '#E0A458'],
        'ocean'    => ['label' => 'Mavi Okean', 'bg' => '#0E1D26', 'accent' => '#6FB3C0', 'gold' => '#8FD0C9'],
        'sumi'     => ['label' => 'Qırmızı Yaponiya', 'bg' => '#1A1210', 'accent' => '#C1443B', 'gold' => '#D98B60'],
        'sakura'   => ['label' => 'Sakura (Yaponiya)', 'bg' => '#FBF3EC', 'accent' => '#C97B90', 'gold' => '#C9A15A'],
        'beige'    => ['label' => 'Açıq Bej', 'bg' => '#F7F2EA', 'accent' => '#9C7A3E', 'gold' => '#B99457'],
        'rosegold' => ['label' => 'Rose Gold', 'bg' => '#FBEEEA', 'accent' => '#B76E79', 'gold' => '#D4A373'],
    ];
}

/**
 * Favicon linkini fayl dəyişmə vaxtına (mtime) görə keş-sındıran sorğu parametri
 * ilə qaytarır — brauzerlərin favicon-u çox aqressiv keşləməsinin qarşısını almaq üçün
 * (loqo dəyişəndə köhnə ikon uzun müddət görünüb qalırdı).
 */
function sg_favicon_url($fromAdmin = false) {
    $path = sg_setting('logo_icon', '') ?: 'assets/logo-icon.jpg';
    $fsPath = SG_ROOT . '/' . ltrim($path, '/');
    $v = @filemtime($fsPath) ?: time();
    $prefix = $fromAdmin ? '../' : '';
    return $prefix . $path . '?v=' . $v;
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
 * Telefon nömrəsinin həqiqi Azərbaycan mobil nömrəsi formatına uyğun olub-olmadığını
 * yoxlayır (yalnız "boş deyil" yox — "123456789" kimi təsadüfi rəqəmlər keçməsin deyə).
 * Qəbul edilən formatlar: 0501234567, 501234567, +994501234567, 994501234567
 * (aralarında boşluq/tire ola bilər). Operator kodu 10/50/51/55/60/70/77/99 olmalıdır.
 */
function sg_valid_az_phone($phone) {
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if (strpos($digits, '994') === 0 && strlen($digits) === 12) {
        $local = substr($digits, 3);
    } elseif (strpos($digits, '0') === 0 && strlen($digits) === 10) {
        $local = substr($digits, 1);
    } elseif (strlen($digits) === 9) {
        $local = $digits;
    } else {
        return false;
    }
    $validPrefixes = ['10', '50', '51', '55', '60', '70', '77', '99'];
    return strlen($local) === 9 && in_array(substr($local, 0, 2), $validPrefixes, true);
}

/**
 * Nömrəni Block List və müqayisələr üçün TƏK formata salır: 9 rəqəmli yerli
 * hissə (ölkə kodu/aparıcı sıfır olmadan), məs. "050 123 45 67" -> "501234567".
 * Düzgün formatda deyilsə false qaytarır.
 */
function sg_normalize_az_phone($phone) {
    if (!sg_valid_az_phone($phone)) return false;
    $digits = preg_replace('/\D+/', '', (string)$phone);
    if (strpos($digits, '994') === 0 && strlen($digits) === 12) {
        return substr($digits, 3);
    }
    if (strpos($digits, '0') === 0 && strlen($digits) === 10) {
        return substr($digits, 1);
    }
    return $digits;
}

function sg_is_phone_blocked($phone) {
    $normalized = sg_normalize_az_phone($phone);
    if (!$normalized) return false;
    $stmt = sg_db()->prepare('SELECT COUNT(*) FROM blocked_customers WHERE phone = ?');
    $stmt->execute([$normalized]);
    return (bool)$stmt->fetchColumn();
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

    $serviceType = in_array($data['service_type'] ?? '', ['delivery', 'takeaway'], true) ? $data['service_type'] : 'delivery';
    $name = trim((string)($data['name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $address = trim((string)($data['address'] ?? ''));
    $notes = trim((string)($data['notes'] ?? ''));
    $partySize = isset($data['party_size']) && $data['party_size'] !== '' ? max(1, min(100, (int)$data['party_size'])) : null;

    $errors = [];
    if ($name === '') $errors[] = 'Adınızı daxil edin.';
    if ($phone === '') $errors[] = 'Telefon nömrənizi daxil edin.';
    elseif (!sg_valid_az_phone($phone)) $errors[] = 'Düzgün mobil nömrə daxil edin (məs. 050 123 45 67).';
    elseif (sg_is_phone_blocked($phone)) $errors[] = 'Bu nömrə ilə sifariş vermək mümkün deyil.';
    if ($serviceType === 'delivery' && $address === '') $errors[] = 'Çatdırılma ünvanını daxil edin.';
    if ($errors) {
        return ['ok' => false, 'errors' => $errors];
    }

    // Nə vaxt hazır olsun: "ən tez zamanda" (minimum 30 dəqiqə) və ya müştərinin özünün
    // seçdiyi konkret tarix/saat ("YYYY-MM-DD HH:MM:SS" formatında göndərilir). Sifariş
    // verilən andan ən azı 30 dəqiqə sonraya qədər çatdırma/hazırlıq mümkündür.
    $timeChoice = (string)($data['requested_time'] ?? 'asap');
    $asapTime = time() + 30 * 60;
    if ($timeChoice === 'asap') {
        $requestedTime = date('Y-m-d H:i:s', $asapTime);
    } else {
        $ts = strtotime($timeChoice);
        // Keçmişə və ya 30 dəqiqədən yaxın vaxta sifariş qəbul edilmir — belə olarsa "ən tez zamanda"ya düşür.
        $requestedTime = ($ts !== false && $ts >= time() + 30 * 60)
            ? date('Y-m-d H:i:s', $ts)
            : date('Y-m-d H:i:s', $asapTime);
    }

    $trackToken = bin2hex(random_bytes(12));
    $customerId = isset($data['customer_id']) ? (int)$data['customer_id'] : null;

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('
            INSERT INTO orders (customer_name, customer_phone, service_type, address, subtotal, tip, total, status, notes, party_size, requested_time, track_token, customer_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ');
        $stmt->execute([$name, $phone, $serviceType, $address, $subtotal, $tip, $total, 'pending', $notes, $partySize, $requestedTime, $trackToken, $customerId]);
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

    return ['ok' => true, 'order_id' => $orderId, 'order_number' => '#' . $orderId, 'total' => $total, 'track_token' => $trackToken];
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

/**
 * Admin/sifariş meneceri sifarişin hazır olacağı vaxtı əl ilə təyin/dəyişdirir
 * (məs. müştəri "ən tez zamanda" seçib, admin real vaxtı bildirmək istəyir).
 */
function sg_update_order_time($id, $datetime) {
    $ts = strtotime((string)$datetime);
    if ($ts === false) return false;
    $pdo = sg_db();
    $stmt = $pdo->prepare('UPDATE orders SET requested_time = ? WHERE id = ?');
    return $stmt->execute([date('Y-m-d H:i:s', $ts), $id]);
}

function sg_delete_order($id) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('DELETE FROM orders WHERE id = ?');
    return $stmt->execute([$id]);
}

/**
 * Sifariş nömrələməsini sıfırlayır — YALNIZ bütün sifarişlər silindikdən sonra
 * mənalıdır (əks halda növbəti sifariş yenə MAX(id)+1 alacaq).
 */
/**
 * BÜTÜN sifariş tarixçəsini (orders, order_items, reviews — foreign key
 * cascade ilə) həmişəlik silir və nömrələməni sıfırlayır ki, növbəti sifariş
 * yenidən #1-dən başlasın. GERİ QAYTARILA BİLMƏZ — yalnız admin özü, açıq
 * təsdiqdən sonra çağırmalıdır.
 */
function sg_wipe_all_orders() {
    $pdo = sg_db();
    $pdo->exec('DELETE FROM orders');
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'orders'");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'order_items'");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'reviews'");
    return true;
}

function sg_reset_order_sequence() {
    $pdo = sg_db();
    $count = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
    if ($count > 0) return false;
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'orders'");
    $pdo->exec("DELETE FROM sqlite_sequence WHERE name = 'order_items'");
    return true;
}

/**
 * Bildiriş səs seçimləri — admin öz sifariş bildirişi VƏ müştərinin "sifariş
 * tamamlandı" bildirişi eyni bu siyahıdan seçim edir (iki fərqli admin
 * panel bölməsi bir-birindən asılı olmadan görünsə də, eyni mənbədən gəlir).
 */
function sg_sound_presets() {
    return [
        'chime' => 'Zəng (üçlü)',
        'beep1' => 'Bip (tək)',
        'beep2' => 'Bip (ikili)',
        'bundled' => 'Standart',
        'custom' => 'Öz səsim',
        'none' => 'Səssiz',
    ];
}

function sg_get_review($orderId) {
    $pdo = sg_db();
    $stmt = $pdo->prepare('SELECT * FROM reviews WHERE order_id = ?');
    $stmt->execute([$orderId]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

function sg_save_review($orderId, $customerId, $rating, $comment) {
    $rating = max(1, min(5, (int)$rating));
    $comment = trim((string)$comment);
    $pdo = sg_db();
    $stmt = $pdo->prepare('INSERT OR IGNORE INTO reviews (order_id, customer_id, rating, comment) VALUES (?, ?, ?, ?)');
    $stmt->execute([$orderId, $customerId, $rating, $comment]);
    return $stmt->rowCount() > 0;
}

function sg_get_reviews() {
    $pdo = sg_db();
    return $pdo->query("
        SELECT r.*, o.customer_name, o.customer_phone, o.created_at AS order_created_at
        FROM reviews r
        JOIN orders o ON o.id = r.order_id
        ORDER BY r.id DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
}
