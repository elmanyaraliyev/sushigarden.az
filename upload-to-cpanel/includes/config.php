<?php
// Sushi Garden — mərkəzi konfiqurasiya
// Bu fayl həm sayt (index.php), həm də idarəetmə paneli (admin/) tərəfindən istifadə olunur.

define('SG_ROOT', dirname(__DIR__));               // .../public
define('SG_DATA_DIR', SG_ROOT . '/data');
define('SG_DB_PATH', SG_DATA_DIR . '/sushigarden.sqlite');
define('SG_UPLOADS_DIR', SG_ROOT . '/uploads/products');
define('SG_UPLOADS_URL', 'uploads/products');       // index.php-dən nisbi yol

// Sayt üçün əsas məlumatlar (əlaqə, whatsapp və s.) tək yerdə saxlanılır ki,
// həm sayt, həm də admin paneli eyni məlumatları göstərsin.
define('SG_PHONE_DISPLAY', '+994 55 679 50 70');
define('SG_PHONE_WA', '994556795070');
define('SG_MAPS_URL', 'https://maps.app.goo.gl/j5iVUR4UZ4YD67s68');

date_default_timezone_set('Asia/Baku');
