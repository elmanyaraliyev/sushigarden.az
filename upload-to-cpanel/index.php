<?php
require_once __DIR__ . '/includes/customer_auth.php';
$customer = sg_current_customer();
$menu = sg_get_menu(true); // yalnız aktiv kateqoriya/məhsullar
$featured = sg_get_featured(4);

$catsWithItems = array_values(array_filter($menu, function ($c) { return !empty($c['products']); }));
$totalItems = 0;
foreach ($catsWithItems as $c) { $totalItems += count($c['products']); }

$restaurantName = sg_setting('restaurant_name', 'Sushi Garden');
$restaurantTagline = sg_setting('restaurant_tagline', 'Bakının qəlbində təzə suşi bağı.');
$phoneDisplay = sg_setting('phone_display', defined('SG_PHONE_DISPLAY') ? SG_PHONE_DISPLAY : '');
$phoneWa = sg_setting('phone_wa', defined('SG_PHONE_WA') ? SG_PHONE_WA : '');
$address = sg_setting('address', '');
$contactEmail = sg_setting('contact_email', 'info@sushigarden.az');
$mapsUrl = sg_setting('maps_url', defined('SG_MAPS_URL') ? SG_MAPS_URL : '#');
$igUrl = sg_setting('social_instagram', '');
$fbUrl = sg_setting('social_facebook', '');
$ttUrl = sg_setting('social_tiktok', '');
$hours = sg_hours();
$dayLabels = sg_day_labels();
$logoIconCustom = sg_setting('logo_icon', '');
$logoFullCustom = sg_setting('logo_full', '');
$heroImage = sg_setting('hero_image', '');
$colorTheme = sg_setting('color_theme', 'forest');

$csrf = sg_csrf_token();

/* ---------------------------------------------------------------------
 * SEO: başlıq/təsvir, Open Graph, kanonik URL və Restaurant strukturlaşdırılmış
 * datası (JSON-LD) — Google/Yandex/Bing-in restoranı düzgün anlaması üçün.
 * ------------------------------------------------------------------- */
$siteUrl = 'https://sushigarden.az/';
$seoTitle = sg_setting('seo_title', '') ?: ($restaurantName . ' — Bakıda Suşi Restoranı | Onlayn Sifariş və Çatdırılma');
$seoDescription = sg_setting('seo_description', '') ?: ($restaurantName . ' — Bakıda təzə suşi, sushi roll, hot roll, burrito və noodles. '
    . $restaurantTagline . ' Onlayn sifariş, sürətli çatdırılma, özü aparma və restoranda yemək seçimi.');
$seoKeywords = sg_setting('seo_keywords', 'sushi, sushi garden, suşi bakı, sushi baku, sushi sifarişi, sushi çatdırılma, yapon mətbəxi bakı, sushi roll, hot roll, sushi bar');
$ogImagePath = $logoFullCustom ?: 'assets/logo-full.jpg';
$ogImageUrl = $siteUrl . ltrim($ogImagePath, '/');

$dayToSchema = ['mon' => 'Monday', 'tue' => 'Tuesday', 'wed' => 'Wednesday', 'thu' => 'Thursday', 'fri' => 'Friday', 'sat' => 'Saturday', 'sun' => 'Sunday'];
$openingHours = [];
foreach ($dayToSchema as $key => $schemaDay) {
    $d = $hours[$key] ?? null;
    if ($d && empty($d['closed']) && !empty($d['open']) && !empty($d['close'])) {
        $openingHours[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => $schemaDay, 'opens' => $d['open'], 'closes' => $d['close']];
    }
}
$sameAs = array_values(array_filter([$igUrl, $fbUrl, $ttUrl, $phoneWa ? 'https://wa.me/' . $phoneWa : '']));

$restaurantSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Restaurant',
    'name' => $restaurantName,
    'image' => $ogImageUrl,
    'url' => $siteUrl,
    '@id' => $siteUrl,
    'servesCuisine' => ['Japanese', 'Sushi', 'Asian Fusion'],
    'priceRange' => '₼₼',
    'telephone' => $phoneDisplay ?: ('+' . $phoneWa),
    'address' => ['@type' => 'PostalAddress', 'streetAddress' => $address, 'addressLocality' => 'Bakı', 'addressCountry' => 'AZ'],
];
if ($openingHours) $restaurantSchema['openingHoursSpecification'] = $openingHours;
if ($sameAs) $restaurantSchema['sameAs'] = $sameAs;
if ($mapsUrl && $mapsUrl !== '#') $restaurantSchema['hasMap'] = $mapsUrl;
?>
<!doctype html>
<html lang="az" data-color-theme="<?php echo h($colorTheme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($seoTitle); ?></title>
<meta name="description" content="<?php echo h($seoDescription); ?>">
<meta name="keywords" content="<?php echo h($seoKeywords); ?>">
<link rel="canonical" href="<?php echo h($siteUrl); ?>">
<meta property="og:title" content="<?php echo h($seoTitle); ?>">
<meta property="og:description" content="<?php echo h($seoDescription); ?>">
<meta property="og:type" content="restaurant.menu">
<meta property="og:url" content="<?php echo h($siteUrl); ?>">
<meta property="og:image" content="<?php echo h($ogImageUrl); ?>">
<meta property="og:locale" content="az_AZ">
<meta property="og:site_name" content="<?php echo h($restaurantName); ?>">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?php echo h($seoTitle); ?>">
<meta name="twitter:description" content="<?php echo h($seoDescription); ?>">
<meta name="twitter:image" content="<?php echo h($ogImageUrl); ?>">
<meta name="robots" content="index, follow">
<meta name="theme-color" content="#12261A">
<link rel="icon" href="<?php echo h(sg_favicon_url()); ?>" type="image/jpeg">
<link rel="apple-touch-icon" href="<?php echo h(sg_favicon_url()); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?php echo h($logoFullCustom ?: 'assets/logo-full.webp'); ?>" fetchpriority="high">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<script type="application/ld+json"><?php echo json_encode($restaurantSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>
<script>document.documentElement.classList.add('js');</script>
</head>
<body data-wa-phone="<?php echo h($phoneWa); ?>" data-csrf="<?php echo h($csrf); ?>" data-customer-name="<?php echo h($customer['name'] ?? ''); ?>" data-customer-phone="<?php echo h($customer['phone'] ?? ''); ?>">

<header>
  <nav class="nav">
    <button class="nav-toggle" id="nav-toggle" aria-label="Menyunu aç">☰</button>
    <a href="#top" class="wordmark" data-tab-link="menu">
      <?php if ($logoIconCustom): ?>
        <img src="<?php echo h($logoIconCustom); ?>" alt="<?php echo h($restaurantName); ?> loqosu" class="brand-icon" width="38" height="38" fetchpriority="high">
      <?php else: ?>
        <?php sg_picture('assets/logo-icon', h($restaurantName) . ' loqosu', 'class="brand-icon" width="38" height="38" fetchpriority="high"'); ?>
      <?php endif; ?>
      <span><?php echo h($restaurantName); ?></span>
    </a>
    <ul class="nav-links" id="nav-links">
      <li><button type="button" class="active" data-tab="menu" data-i18n="nav_menu">Menyu</button></li>
      <li><button type="button" data-tab="about" data-i18n="nav_about">Haqqımızda</button></li>
      <li><button type="button" data-tab="gallery" data-i18n="nav_gallery">Qalereya</button></li>
      <li><button type="button" data-tab="contact" data-i18n="nav_contact">Əlaqə</button></li>
      <?php if ($customer): ?>
        <li class="nav-orders-item"><a href="account.php">👤 <?php echo h($customer['name']); ?></a></li>
      <?php else: ?>
        <li class="nav-orders-item"><a href="track.php" class="my-orders-link">📦 <span data-i18n="my_orders">Sifarişim</span></a></li>
        <li class="nav-orders-item"><a href="login.php">🔑 <span data-i18n="nav_login">Giriş</span></a></li>
      <?php endif; ?>
    </ul>
    <div class="lang-switch" id="lang-switch">
      <button type="button" class="lang-trigger" id="lang-trigger" aria-haspopup="true" aria-expanded="false">
        <span class="flag-wrap" id="lang-current-flag"><svg viewBox="0 0 30 20" class="flag"><rect width="30" height="20" fill="#3F9C35"/><rect width="30" height="6.67" fill="#00B9E4"/><rect y="6.67" width="30" height="6.67" fill="#EF3340"/><circle cx="15.5" cy="10" r="3.6" fill="#fff"/><circle cx="16.8" cy="10" r="3" fill="#EF3340"/><polygon points="19.2,10 20.6,10.5 19.7,9.3 19.7,10.7 20.6,9.5" fill="#fff"/></svg></span>
        <span class="lang-code" id="lang-current-code">AZ</span>
        <svg class="lang-caret" viewBox="0 0 10 6"><polyline points="1,1 5,5 9,1" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
      </button>
      <div class="lang-menu" id="lang-menu">
        <button type="button" data-lang="az" class="lang-option active">
          <svg viewBox="0 0 30 20" class="flag"><rect width="30" height="20" fill="#3F9C35"/><rect width="30" height="6.67" fill="#00B9E4"/><rect y="6.67" width="30" height="6.67" fill="#EF3340"/><circle cx="15.5" cy="10" r="3.6" fill="#fff"/><circle cx="16.8" cy="10" r="3" fill="#EF3340"/><polygon points="19.2,10 20.6,10.5 19.7,9.3 19.7,10.7 20.6,9.5" fill="#fff"/></svg>
          <span>AZ</span>
        </button>
        <button type="button" data-lang="ru" class="lang-option">
          <svg viewBox="0 0 30 20" class="flag"><rect width="30" height="6.67" fill="#fff"/><rect y="6.67" width="30" height="6.66" fill="#0039A6"/><rect y="13.33" width="30" height="6.67" fill="#D52B1E"/></svg>
          <span>RU</span>
        </button>
        <button type="button" data-lang="en" class="lang-option">
          <svg viewBox="0 0 60 30" class="flag"><rect width="60" height="30" fill="#00247D"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/><path d="M0,0 L60,30 M60,0 L0,30" stroke="#CF142B" stroke-width="2"/><path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/><path d="M30,0 V30 M0,15 H60" stroke="#CF142B" stroke-width="6"/></svg>
          <span>EN</span>
        </button>
      </div>
    </div>
    <div class="nav-actions">
      <?php if ($customer): ?>
        <a class="nav-phone nav-orders-desktop" href="account.php">👤 <?php echo h($customer['name']); ?></a>
      <?php else: ?>
        <a class="nav-phone my-orders-link nav-orders-desktop" href="track.php">📦 <span data-i18n="my_orders">Sifarişim</span></a>
        <a class="nav-phone nav-orders-desktop" href="login.php">🔑 <span data-i18n="nav_login">Giriş</span></a>
      <?php endif; ?>
      <a class="nav-phone" href="tel:+<?php echo h($phoneWa); ?>"><?php echo h($phoneDisplay); ?></a>
    </div>
  </nav>
</header>

<main id="top">
  <h1 class="sr-only">Sushi Garden — Bakıda Suşi Restoranı: Onlayn Sifariş, Sushi Roll, Hot Roll və Çatdırılma</h1>
  <div class="stage">

    <section class="panel active" id="panel-menu" data-panel="menu">

      <?php if ($featured): ?>
      <section class="promo-carousel">
        <div class="wrap">
          <div class="carousel-shell">
            <div class="carousel-viewport">
              <div class="carousel-track" id="carousel-track">
                <?php foreach ($featured as $p): ?>
                  <div class="carousel-slide">
                    <div class="slide-info">
                      <span class="badge" data-i18n="badge_featured">Tövsiyə</span>
                      <h3 class="name" data-i18n-ru="<?php echo h($p['name_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($p['name_en'] ?? ''); ?>"><?php echo h($p['name']); ?></h3>
                      <?php if (!empty($p['description'])): ?><p class="desc" data-i18n-ru="<?php echo h($p['description_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($p['description_en'] ?? ''); ?>"><?php echo h($p['description']); ?></p><?php endif; ?>
                      <div class="slide-foot">
                        <span class="price"><?php echo sg_money($p['price']); ?></span>
                        <button class="btn btn-primary js-add-to-cart" data-id="<?php echo (int)$p['id']; ?>" data-name="<?php echo h($p['name']); ?>" data-price="<?php echo h($p['price']); ?>" data-i18n="add_to_cart">Səbətə əlavə et</button>
                      </div>
                    </div>
                    <div class="slide-media">
                      <?php if (!empty($p['image'])): ?>
                        <img src="<?php echo h(SG_UPLOADS_URL . '/' . $p['image']); ?>" alt="<?php echo h($p['name']); ?>" loading="lazy" width="640" height="480">
                      <?php else: ?>
                        <span class="ph">🍣</span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
            <?php if (count($featured) > 1): ?>
            <button type="button" class="carousel-arrow prev" id="carousel-prev" aria-label="Əvvəlki">‹</button>
            <button type="button" class="carousel-arrow next" id="carousel-next" aria-label="Sonrakı">›</button>
            <div class="carousel-dots" id="carousel-dots">
              <?php foreach ($featured as $i => $p): ?>
                <button type="button" data-slide="<?php echo (int)$i; ?>" class="<?php echo $i === 0 ? 'active' : ''; ?>" aria-label="Slayd <?php echo (int)$i + 1; ?>"></button>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>
      </section>
      <?php endif; ?>

      <section class="menu-section" id="menyu">
        <div class="wrap">
          <div class="section-head reveal">
            <h2 data-i18n="menu_eyebrow">Menyu</h2>
          </div>

          <?php if (count($catsWithItems) > 1): ?>
          <div class="menu-tabs-wrap">
            <nav class="menu-tabs" id="menu-tabs">
              <?php foreach ($catsWithItems as $ci => $cat): ?>
                <button type="button" class="<?php echo $ci === 0 ? 'active' : ''; ?>" data-cat="<?php echo (int)$cat['id']; ?>" data-i18n-ru="<?php echo h($cat['name_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($cat['name_en'] ?? ''); ?>"><?php echo h($cat['name']); ?></button>
              <?php endforeach; ?>
            </nav>
          </div>
          <?php endif; ?>

          <div class="menu-cols" id="menu-cols">
            <?php
            $half = (int)ceil(count($catsWithItems) / 2);
            $columns = [array_slice($catsWithItems, 0, $half), array_slice($catsWithItems, $half)];
            foreach ($columns as $col):
            ?>
            <div>
              <?php foreach ($col as $cat): ?>
                <div class="menu-cat reveal" id="cat-<?php echo (int)$cat['id']; ?>" data-cat-id="<?php echo (int)$cat['id']; ?>">
                  <h3 data-i18n-ru="<?php echo h($cat['name_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($cat['name_en'] ?? ''); ?>"><?php echo h($cat['name']); ?></h3>
                  <?php foreach ($cat['products'] as $p): ?>
                    <div class="menu-item">
                      <button class="add" data-id="<?php echo (int)$p['id']; ?>" data-name="<?php echo h($p['name']); ?>" data-price="<?php echo h($p['price']); ?>">+</button>
                      <?php if (!empty($p['image'])): ?>
                        <img class="thumb" src="<?php echo h(SG_UPLOADS_URL . '/' . $p['image']); ?>" alt="<?php echo h($p['name']); ?>" loading="lazy" width="52" height="52">
                      <?php endif; ?>
                      <div class="info">
                        <div class="name" data-i18n-ru="<?php echo h($p['name_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($p['name_en'] ?? ''); ?>"><?php echo h($p['name']); ?></div>
                        <?php if (!empty($p['description'])): ?><div class="desc" data-i18n-ru="<?php echo h($p['description_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($p['description_en'] ?? ''); ?>"><?php echo h($p['description']); ?></div><?php endif; ?>
                      </div>
                      <div class="price"><?php echo sg_money($p['price']); ?></div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
      </section>

    </section>

    <section class="panel" id="panel-about" data-panel="about">
      <div class="about-wrap">
        <div class="wrap about-hero">
          <div class="reveal">
            <p class="eyebrow" data-i18n="about_eyebrow">Haqqımızda</p>
            <h2>Sushi Garden: <em>Where Art Meets Nature</em></h2>
            <p data-i18n="about_subtext">Bakının mərkəzində əl işi suşi təcrübəsi — təbii materiallar, yapon dəqiqliyi və səmimi qonaqpərvərliklə hər gün yenidən hazırlanır.</p>
          </div>
          <div class="about-logo reveal">
            <?php if ($logoFullCustom): ?>
              <img src="<?php echo h($logoFullCustom); ?>" alt="<?php echo h($restaurantName); ?> — Sushi Ağacı">
            <?php else: ?>
              <?php sg_picture('assets/logo-full', h($restaurantName) . ' — Sushi Ağacı', ''); ?>
            <?php endif; ?>
          </div>
        </div>
        <div class="concept-band">
          <div class="concept-inner reveal">
            <p class="eyebrow" data-i18n="about_concept_eyebrow">Fəlsəfəmiz</p>
            <h2 data-i18n="about_concept_title">Bağ Konsepsiyamız</h2>
            <p data-i18n="about_concept_p1">Sushi Garden bir restorandan çox — canlı bir bağdır. Hər boşqab təbiətin sadəliyini, hər dad isə ustaların səbrini əks etdirir.</p>
            <p data-i18n="about_concept_p2">Təzə balıq hər səhər tədarük olunur, düyü əl ilə hazırlanır, tərəvəzlər isə mövsümə uyğun seçilir — sürətli qidalanma deyil, yavaş və düşünülmüş bir sənət.</p>
            <p class="copyright-note"><span class="copyright-icon">©</span> <span id="year-about"></span> <?php echo h($restaurantName); ?> — <span data-i18n="copyright_text">Müəllif hüquqları qorunur</span></p>
          </div>
        </div>
      </div>
    </section>

    <section class="panel" id="panel-gallery" data-panel="gallery">
      <div class="gallery-wrap wrap">
        <div class="section-head reveal">
          <p class="eyebrow" data-i18n="gallery_eyebrow">Qalereya</p>
          <h2 data-i18n="gallery_title">Təbiətdən İlhamlanan Anlar</h2>
        </div>
        <div class="gallery-grid">
          <?php
          $galleryTiles = [
            ['az' => 'Yarpaq Üzərində Suşi', 'ru' => 'Суши на листе', 'en' => 'Sushi on a Leaf', 'a' => '#274A32', 'b' => '#12261A'],
            ['az' => 'Bağ Masası',           'ru' => 'Садовый стол',  'en' => 'The Garden Table', 'a' => '#1F3B29', 'b' => '#0E1D14'],
            ['az' => 'Premium Seçim',        'ru' => 'Премиум выбор', 'en' => 'Premium Selection', 'a' => '#2C5238', 'b' => '#13291B'],
            ['az' => 'Fəsil Toxumları',      'ru' => 'Сезонные ноты', 'en' => 'Seasonal Notes',   'a' => '#22412C', 'b' => '#0E1D14'],
            ['az' => 'Şəf Toxunuşu',         'ru' => 'Рука шефа',     'en' => "Chef's Touch",     'a' => '#2E5A3B', 'b' => '#12261A'],
            ['az' => 'Axşam Süfrəsi',        'ru' => 'Вечерний стол', 'en' => 'Evening Table',    'a' => '#1B3423', 'b' => '#0E1D14'],
            ['az' => 'Yaşıl Guşə',           'ru' => 'Зелёный уголок','en' => 'Green Corner',     'a' => '#264A30', 'b' => '#12261A'],
            ['az' => 'Xüsusi Sifariş',       'ru' => 'Особый заказ',  'en' => 'Chef\'s Special',  'a' => '#305B3C', 'b' => '#13291B'],
            ['az' => 'Təbiət və Dad',        'ru' => 'Природа и вкус','en' => 'Nature & Flavor',  'a' => '#1E3B27', 'b' => '#0E1D14'],
          ];
          foreach ($galleryTiles as $t):
          ?>
          <div class="gallery-tile reveal" style="--tile-a:<?php echo h($t['a']); ?>; --tile-b:<?php echo h($t['b']); ?>;">
            <div class="leaf"></div>
            <div class="cap" data-i18n-ru="<?php echo h($t['ru']); ?>" data-i18n-en="<?php echo h($t['en']); ?>"><?php echo h($t['az']); ?><span data-i18n="gallery_tag">Sushi Garden</span></div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </section>

    <section class="panel" id="panel-contact" data-panel="contact">
      <div class="contact-wrap wrap">
        <div class="contact-card reveal">
          <div class="section-head">
            <p class="eyebrow" data-i18n="contact_eyebrow">Əlaqə</p>
            <h2 data-i18n="contact_title">Bizi Tapın</h2>
          </div>
          <div class="contact-grid">
            <div>
              <ul class="info-list">
                <li><span class="k" data-i18n="contact_address">Ünvan</span><span class="v"><?php echo h($address); ?></span></li>
                <li><span class="k" data-i18n="contact_phone">Telefon</span><span class="v"><a href="tel:+<?php echo h($phoneWa); ?>" style="text-decoration:none;"><?php echo h($phoneDisplay); ?></a></span></li>
                <?php if ($contactEmail): ?>
                <li><span class="k" data-i18n="contact_email">E-poçt</span><span class="v"><a href="mailto:<?php echo h($contactEmail); ?>" style="text-decoration:none;"><?php echo h($contactEmail); ?></a></span></li>
                <?php endif; ?>
              </ul>
              <?php if ($hours): ?>
              <ul class="hours-list">
                <?php foreach ($dayLabels as $key => $label): $d = $hours[$key] ?? null; ?>
                  <li>
                    <span class="day"><?php echo h($label); ?></span>
                    <span class="time"><?php echo (!$d || !empty($d['closed'])) ? '<span data-i18n="hours_closed">İstirahət günü</span>' : h($d['open']) . ' – ' . h($d['close']); ?></span>
                  </li>
                <?php endforeach; ?>
              </ul>
              <?php endif; ?>
              <div class="social-row">
                <?php if ($igUrl): ?><a href="<?php echo h($igUrl); ?>" target="_blank" rel="noopener">IG</a><?php endif; ?>
                <?php if ($fbUrl): ?><a href="<?php echo h($fbUrl); ?>" target="_blank" rel="noopener">FB</a><?php endif; ?>
                <?php if ($ttUrl): ?><a href="<?php echo h($ttUrl); ?>" target="_blank" rel="noopener">TT</a><?php endif; ?>
                <a href="https://wa.me/<?php echo h($phoneWa); ?>" target="_blank" rel="noopener">WA</a>
              </div>
            </div>
            <a class="map-placeholder" href="<?php echo h($mapsUrl); ?>" target="_blank" rel="noopener">
              <span class="pin">📍</span>
              <span><?php echo h($restaurantName); ?> — <span data-i18n="contact_map_open">Google Maps-da aç</span></span>
              <span class="sub" data-i18n="contact_map_route">Marşrutu almaq üçün klikləyin</span>
            </a>
          </div>
        </div>
      </div>
    </section>

  </div>
</main>

<footer id="footer">
  <div class="wrap footer-row">
    <span class="copyright-note"><span class="copyright-icon">©</span> <span id="year"></span> <?php echo h($restaurantName); ?> — <span data-i18n="copyright_text">Müəllif hüquqları qorunur</span></span>
    <span>
      <a href="https://wa.me/<?php echo h($phoneWa); ?>" target="_blank" rel="noopener">WhatsApp</a>
      <?php if ($igUrl): ?> · <a href="<?php echo h($igUrl); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
    </span>
  </div>
</footer>

<div id="overlay"></div>
<button id="cart-pill" aria-label="Sifarişi göstər">
  <span data-i18n="cart_pill_label">Sifariş</span>
  <span class="count" id="cart-count">0</span>
</button>
<div id="cart-drawer">
  <div class="inner">
    <div class="drawer-head">
      <h3 data-i18n="cart_title">Sifarişiniz</h3>
      <button class="drawer-close" id="drawer-close" aria-label="Bağla">×</button>
    </div>

    <div id="cart-form-view">
      <ul id="cart-list"></ul>
      <div id="cart-empty" class="cart-empty" data-i18n="cart_empty">Hələ heç nə seçilməyib. Menyudan "+" düyməsinə basaraq əlavə edin.</div>

      <div id="cart-checkout" style="display:none;">
        <div class="tip-block">
          <div class="tip-label" data-i18n="tip_label">Bəxşiş</div>
          <div class="tip-options" id="tip-options">
            <button type="button" class="tip-btn active" data-tip="0" data-i18n="tip_none">Bəxşişsiz</button>
            <button type="button" class="tip-btn" data-tip="1">1 AZN</button>
            <button type="button" class="tip-btn" data-tip="2">2 AZN</button>
            <button type="button" class="tip-btn" data-tip="5">5 AZN</button>
            <button type="button" class="tip-btn" data-tip="10">10 AZN</button>
            <button type="button" class="tip-btn" data-tip="custom" id="tip-custom-btn" data-i18n="tip_custom">Digər məbləğ</button>
          </div>
          <input type="number" id="tip-custom-input" min="0" step="0.5" placeholder="Məbləği daxil edin (AZN)" style="display:none;">
        </div>

        <div class="cart-totals">
          <div class="row"><span data-i18n="subtotal_label">Aralıq yekun</span><span id="sum-subtotal">0.00</span></div>
          <div class="row"><span data-i18n="tip_label">Bəxşiş</span><span id="sum-tip">0.00</span></div>
          <div class="row total"><span data-i18n="total_label">Ümumi</span><span id="sum-total">0.00</span></div>
        </div>

        <div class="service-block">
          <div class="tip-label" data-i18n="service_label">Xidmət növü</div>
          <div class="service-options">
            <label><input type="radio" name="service_type" value="delivery" checked><span data-i18n="service_delivery">Çatdırılma</span></label>
            <label><input type="radio" name="service_type" value="takeaway"><span data-i18n="service_takeaway">Özü ilə aparma</span></label>
          </div>
        </div>

        <div class="field-block">
          <label data-i18n="field_name">Adınız</label>
          <input type="text" id="cust-name" data-i18n-placeholder="field_name_ph" placeholder="Adınız">
        </div>
        <div class="field-block">
          <label data-i18n="field_phone">Telefon</label>
          <input type="tel" id="cust-phone" placeholder="050 123 45 67">
        </div>
        <div class="field-block" id="address-block">
          <label data-i18n="field_address">Ünvan</label>
          <input type="text" id="cust-address" data-i18n-placeholder="field_address_ph" placeholder="Çatdırılma ünvanınızı daxil edin...">
        </div>

        <div class="field-block">
          <label data-i18n="field_time">Nə vaxt hazır olsun?</label>
          <select id="cust-time">
            <option value="asap" data-i18n="time_asap">Tez bir zamanda (~25 dəqiqə)</option>
            <option value="2h" data-i18n="time_2h">2 saat sonra</option>
            <option value="3h" data-i18n="time_3h">3 saat sonra</option>
            <option value="5h" data-i18n="time_5h">5 saat sonra</option>
          </select>
        </div>
        <div class="field-block">
          <label data-i18n="field_party">Adam sayı (istəyə bağlı)</label>
          <input type="number" id="cust-party" min="1" max="100" data-i18n-placeholder="field_party_ph" placeholder="Neçə nəfərsiniz?">
        </div>
        <div class="field-block">
          <label data-i18n="field_notes">Qeyd (istəyə bağlı)</label>
          <textarea id="cust-notes" data-i18n-placeholder="field_notes_ph" placeholder="Məs. zəng, allergiya, əlavə çubuqlar..." rows="2"></textarea>
        </div>

        <div id="order-error" class="order-error" style="display:none;"></div>

        <div class="cta-row" style="margin-top:1rem;">
          <button id="place-order" class="btn btn-primary" data-i18n="place_order">Sifarişi yerləşdir</button>
          <a href="#" id="send-whatsapp" class="btn btn-ghost" data-i18n="send_whatsapp">WhatsApp ilə göndər</a>
        </div>
        <button id="clear-cart" class="btn btn-ghost btn-block" data-i18n="clear_cart">Təmizlə</button>
      </div>
    </div>

    <div id="cart-success-view" style="display:none; text-align:center; padding:1.5rem 0;">
      <h3 data-i18n="thanks_title">Təşəkkürlər!</h3>
      <p data-i18n="thanks_text">Sifarişiniz uğurla qəbul edildi.</p>
      <p><span data-i18n="thanks_order_no">Sifariş nömrəsi:</span> <strong id="order-number"></strong></p>
      <a href="#" id="track-order-link" class="btn btn-ghost" data-i18n="track_order" style="margin:0 0 .8rem; display:inline-flex;">Sifarişi izlə</a>
      <button id="success-close" class="btn btn-primary" data-i18n="close_btn">Bağla</button>
    </div>
  </div>
</div>

<div id="product-modal" class="product-modal" aria-hidden="true">
  <div class="product-modal-backdrop" id="product-modal-backdrop"></div>
  <div class="product-modal-card">
    <button class="product-modal-close" id="product-modal-close" aria-label="Bağla">×</button>
    <div class="product-modal-media" id="product-modal-media"></div>
    <div class="product-modal-body">
      <h3 id="product-modal-name"></h3>
      <p id="product-modal-desc"></p>
      <div class="product-modal-foot">
        <span class="price" id="product-modal-price"></span>
        <button class="btn btn-primary" id="product-modal-add" data-i18n="add_to_cart">Səbətə əlavə et</button>
      </div>
    </div>
  </div>
</div>

<script src="js/site.js?v=<?php echo (int)@filemtime(__DIR__ . '/js/site.js'); ?>" defer></script>
</body>
</html>
