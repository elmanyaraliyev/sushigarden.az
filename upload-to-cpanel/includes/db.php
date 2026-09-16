<?php
require_once __DIR__ . '/config.php';

/**
 * PDO SQLite bağlantısı qaytarır (tək instance).
 * Verilənlər bazası bir dəfə yaradılır və faylda saxlanılır — ayrıca
 * MySQL/DB quraşdırmasına ehtiyac yoxdur, cPanel-də PHP-nin pdo_sqlite
 * modulu kifayətdir (demək olar bütün paylaşılan hostinglərdə var).
 */
function sg_db() {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    if (!is_dir(SG_DATA_DIR)) {
        @mkdir(SG_DATA_DIR, 0755, true);
    }
    $isNew = !file_exists(SG_DB_PATH);
    $pdo = new PDO('sqlite:' . SG_DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    if ($isNew) {
        sg_install_schema($pdo);
    }
    sg_migrate($pdo);
    return $pdo;
}

/**
 * Köhnə DB fayllarına yeni sütunlar əlavə edir (məs. gələcək yeniləmələrdə).
 * Mövcud quraşdırmalara zərər vermədən təhlükəsiz şəkildə işləyir.
 */
function sg_migrate(PDO $pdo) {
    $cols = $pdo->query("PRAGMA table_info(products)")->fetchAll(PDO::FETCH_ASSOC);
    $names = array_column($cols, 'name');
    if (!in_array('featured', $names, true)) {
        $pdo->exec("ALTER TABLE products ADD COLUMN featured INTEGER NOT NULL DEFAULT 0");
    }
    foreach (['name_en', 'name_ru', 'description_en', 'description_ru'] as $col) {
        if (!in_array($col, $names, true)) {
            $pdo->exec("ALTER TABLE products ADD COLUMN $col TEXT NULL");
        }
    }

    $catCols = $pdo->query("PRAGMA table_info(categories)")->fetchAll(PDO::FETCH_ASSOC);
    $catNames = array_column($catCols, 'name');
    foreach (['name_en', 'name_ru'] as $col) {
        if (!in_array($col, $catNames, true)) {
            $pdo->exec("ALTER TABLE categories ADD COLUMN $col TEXT NULL");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS orders (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            customer_name TEXT NOT NULL,
            customer_phone TEXT NOT NULL,
            service_type TEXT NOT NULL DEFAULT 'delivery',
            address TEXT NOT NULL DEFAULT '',
            subtotal REAL NOT NULL DEFAULT 0,
            tip REAL NOT NULL DEFAULT 0,
            total REAL NOT NULL DEFAULT 0,
            status TEXT NOT NULL DEFAULT 'pending',
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");
    $orderCols = $pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC);
    $orderColNames = array_column($orderCols, 'name');
    foreach ([
        'notes' => "TEXT NOT NULL DEFAULT ''",
        'party_size' => "INTEGER NULL",
        'requested_time' => "TEXT NULL",
        'track_token' => "TEXT NULL",
        'customer_id' => "INTEGER NULL",
    ] as $col => $def) {
        if (!in_array($col, $orderColNames, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN $col $def");
        }
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS gallery_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            image TEXT NOT NULL,
            caption_az TEXT NOT NULL DEFAULT '',
            caption_ru TEXT NOT NULL DEFAULT '',
            caption_en TEXT NOT NULL DEFAULT '',
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS reviews (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL UNIQUE,
            customer_id INTEGER NULL,
            rating INTEGER NOT NULL,
            comment TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            phone TEXT NOT NULL UNIQUE,
            email TEXT NULL,
            password_hash TEXT NOT NULL,
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS order_items (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            order_id INTEGER NOT NULL,
            product_id INTEGER NULL,
            name TEXT NOT NULL,
            price REAL NOT NULL DEFAULT 0,
            qty INTEGER NOT NULL DEFAULT 1,
            line_total REAL NOT NULL DEFAULT 0,
            FOREIGN KEY(order_id) REFERENCES orders(id) ON DELETE CASCADE
        );
    ");

    // Sifariş qəbulu üçün məhdud səlahiyyətli ("sifariş meneceri") hesablar —
    // yalnız sahibkarın (admin rolu) idarə edə bildiyi ayrıca istifadəçilər.
    $adminCols = $pdo->query("PRAGMA table_info(admin_users)")->fetchAll(PDO::FETCH_ASSOC);
    $adminColNames = array_column($adminCols, 'name');
    if (!in_array('role', $adminColNames, true)) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN role TEXT NOT NULL DEFAULT 'admin'");
    }
    if (!in_array('created_at', $adminColNames, true)) {
        $pdo->exec("ALTER TABLE admin_users ADD COLUMN created_at TEXT NOT NULL DEFAULT ''");
    }

    // Bloklanmış müştərilər (telefon nömrəsinə görə) — bloklanan nömrə ilə
    // sifariş yerləşdirmək bloklanır (order.php bunu yoxlayır).
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS blocked_customers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            phone TEXT NOT NULL UNIQUE,
            reason TEXT NOT NULL DEFAULT '',
            created_at TEXT NOT NULL DEFAULT (datetime('now'))
        );
    ");

    // site_settings-i mövcud SG_* sabitlərindən defolt dəyərlərlə bir dəfəlik doldururuq
    // (yalnız hələ heç bir dəyər yazılmayıbsa) ki, admin panel bu sahələri idarə edə bilsin.
    $seedCheck = $pdo->query("SELECT COUNT(*) FROM site_settings WHERE k = 'restaurant_name'")->fetchColumn();
    if (!$seedCheck) {
        $defaults = [
            'restaurant_name' => 'Sushi Garden',
            'restaurant_tagline' => 'Bakının qəlbində təzə suşi bağı.',
            'phone_display' => defined('SG_PHONE_DISPLAY') ? SG_PHONE_DISPLAY : '',
            'phone_wa' => defined('SG_PHONE_WA') ? SG_PHONE_WA : '',
            'phone_wa2' => '',
            'address' => 'Bakı, Azərbaycan',
            'contact_email' => 'info@sushigarden.az',
            'maps_url' => defined('SG_MAPS_URL') ? SG_MAPS_URL : '',
            'hours' => json_encode([
                'mon' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'tue' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'wed' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'thu' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'fri' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'sat' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
                'sun' => ['open' => '11:00', 'close' => '23:00', 'closed' => 0],
            ], JSON_UNESCAPED_UNICODE),
            'social_instagram' => '',
            'social_facebook' => '',
            'social_whatsapp' => '',
            'social_tiktok' => '',
            'logo_icon' => '',
            'logo_full' => '',
            'hero_image' => '',
            'seo_title' => 'Sushi Garden — Bakıda Suşi Restoranı | Onlayn Sifariş və Çatdırılma',
            'seo_description' => 'Sushi Garden — Bakıda təzə suşi, sushi roll, hot roll, burrito və noodles. Onlayn sifariş, sürətli çatdırılma, özü aparma və restoranda yemək seçimi.',
            'seo_keywords' => 'sushi, sushi garden, suşi bakı, sushi baku, sushi sifarişi, sushi çatdırılma, yapon mətbəxi bakı, sushi roll, hot roll, sushi bar',
            'customer_completed_sound_type' => 'bundled',
            'customer_completed_sound' => '',
            'about_subtext_az' => 'Bakının mərkəzində əl işi suşi təcrübəsi — təbii materiallar, yapon dəqiqliyi və səmimi qonaqpərvərliklə hər gün yenidən hazırlanır.',
            'about_subtext_ru' => 'В центре Баку — суши ручной работы: натуральные ингредиенты, японская точность и искреннее гостеприимство каждый день.',
            'about_subtext_en' => 'In the heart of Baku, a handcrafted sushi experience — natural ingredients, Japanese precision, and genuine hospitality, made fresh every day.',
            'about_concept_title_az' => 'Bağ Konsepsiyamız',
            'about_concept_title_ru' => 'Концепция нашего сада',
            'about_concept_title_en' => 'Our Garden Concept',
            'about_concept_p1_az' => 'Sushi Garden bir restorandan çox — canlı bir bağdır. Hər boşqab təbiətin sadəliyini, hər dad isə ustaların səbrini əks etdirir.',
            'about_concept_p1_ru' => 'Sushi Garden — больше, чем ресторан, это живой сад. Каждое блюдо отражает простоту природы, а каждый вкус — терпение мастера.',
            'about_concept_p1_en' => 'Sushi Garden is more than a restaurant — it is a living garden. Every plate reflects the simplicity of nature, and every flavor the patience of a craftsman.',
            'about_concept_p2_az' => 'Təzə balıq hər səhər tədarük olunur, düyü əl ilə hazırlanır, tərəvəzlər isə mövsümə uyğun seçilir — sürətli qidalanma deyil, yavaş və düşünülmüş bir sənət.',
            'about_concept_p2_ru' => 'Свежая рыба поставляется каждое утро, рис готовится вручную, а овощи подбираются по сезону — это не быстрая еда, а медленное, продуманное искусство.',
            'about_concept_p2_en' => 'Fresh fish arrives every morning, rice is prepared by hand, and vegetables are chosen by season — not fast food, but a slow, considered art.',
        ];
        $insSetting = $pdo->prepare('INSERT OR IGNORE INTO site_settings (k, v) VALUES (?, ?)');
        foreach ($defaults as $k => $v) {
            $insSetting->execute([$k, $v]);
        }
    }
}

function sg_install_schema(PDO $pdo) {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS categories (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            sort_order INTEGER NOT NULL DEFAULT 0,
            active INTEGER NOT NULL DEFAULT 1
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            category_id INTEGER NOT NULL,
            name TEXT NOT NULL,
            description TEXT NOT NULL DEFAULT '',
            price REAL NOT NULL DEFAULT 0,
            image TEXT NULL,
            active INTEGER NOT NULL DEFAULT 1,
            sort_order INTEGER NOT NULL DEFAULT 0,
            created_at TEXT NOT NULL DEFAULT (datetime('now')),
            FOREIGN KEY(category_id) REFERENCES categories(id) ON DELETE CASCADE
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admin_users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT NOT NULL UNIQUE,
            password_hash TEXT NOT NULL
        );
    ");
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS site_settings (
            k TEXT PRIMARY KEY,
            v TEXT NOT NULL
        );
    ");

    // Default admin — dərhal Parametrlər bölməsindən dəyişdirilməlidir.
    $stmt = $pdo->prepare('INSERT INTO admin_users (username, password_hash) VALUES (?, ?)');
    $stmt->execute(['admin', password_hash('SushiGarden2026!', PASSWORD_DEFAULT)]);
}
