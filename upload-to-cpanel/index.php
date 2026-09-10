<?php
require_once __DIR__ . '/includes/functions.php';
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
$mapsUrl = sg_setting('maps_url', defined('SG_MAPS_URL') ? SG_MAPS_URL : '#');
$igUrl = sg_setting('social_instagram', '');
$fbUrl = sg_setting('social_facebook', '');
$ttUrl = sg_setting('social_tiktok', '');
$hours = sg_hours();
$dayLabels = sg_day_labels();
$logoIconCustom = sg_setting('logo_icon', '');
$logoFullCustom = sg_setting('logo_full', '');
$heroImage = sg_setting('hero_image', '');

$csrf = sg_csrf_token();
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($restaurantName); ?> — Bakı</title>
<meta name="description" content="<?php echo h($restaurantName); ?> — <?php echo h($restaurantTagline); ?> Menyu, ünvan və onlayn sifariş.">
<meta property="og:title" content="<?php echo h($restaurantName); ?> — Bakı">
<meta property="og:description" content="<?php echo h($restaurantTagline); ?>">
<meta property="og:type" content="restaurant.menu">
<meta name="theme-color" content="#1D2D20">
<link rel="icon" href="<?php echo h($logoIconCustom ?: 'assets/logo-icon.jpg'); ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="preload" as="image" href="<?php echo h($logoFullCustom ?: 'assets/logo-full.webp'); ?>" fetchpriority="high">
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700;900&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<script>document.documentElement.classList.add('js');</script>
</head>
<body data-wa-phone="<?php echo h($phoneWa); ?>" data-csrf="<?php echo h($csrf); ?>">

<header>
  <nav class="nav">
    <button class="nav-toggle" id="nav-toggle" aria-label="Menyunu aç">☰</button>
    <a href="#top" class="wordmark">
      <?php if ($logoIconCustom): ?>
        <img src="<?php echo h($logoIconCustom); ?>" alt="<?php echo h($restaurantName); ?> loqosu" class="brand-icon" width="38" height="38" fetchpriority="high">
      <?php else: ?>
        <?php sg_picture('assets/logo-icon', h($restaurantName) . ' loqosu', 'class="brand-icon" width="38" height="38" fetchpriority="high"'); ?>
      <?php endif; ?>
      <span><?php echo h($restaurantName); ?></span>
    </a>
    <ul class="nav-links" id="nav-links">
      <li><a href="#menyu" data-i18n="nav_menu">Menyu</a></li>
      <li><a href="#elaqe" data-i18n="nav_contact">Əlaqə</a></li>
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
    <a class="nav-phone" href="tel:+<?php echo h($phoneWa); ?>"><?php echo h($phoneDisplay); ?></a>
  </nav>
</header>

<main id="top">

  <section class="hero">
    <div class="wrap hero-grid">
      <div>
        <p class="eyebrow" data-i18n="hero_eyebrow">Bakı · Yapon mətbəxi</p>
        <h1 data-i18n="hero_title">Bakının qəlbində<br><em>təzə suşi</em> bağı</h1>
        <p class="lede"><?php echo h($restaurantName); ?>-də hər rulon gündəlik gətirilən təzə balıqdan, sakit bağ ovqatında, əl işi diqqətlə hazırlanır.</p>
        <div class="cta-row">
          <a href="#menyu" class="btn btn-primary" data-i18n="hero_cta_menu">Menyuya bax</a>
          <a href="https://wa.me/<?php echo h($phoneWa); ?>?text=Salam%2C%20<?php echo urlencode($restaurantName); ?>-d%C9%99n%20sifari%C5%9F%20vermək%20ist%C9%99yir%C9%99m" target="_blank" rel="noopener" class="btn btn-ghost" data-i18n="hero_cta_wa">WhatsApp ilə sifariş</a>
        </div>
        <div class="hero-facts">
          <span class="fact"><strong><?php echo $totalItems; ?>+</strong> <span data-i18n="hero_fact_items">menyu seçimi</span></span>
          <span class="fact"><strong>11–23</strong> <span data-i18n="hero_fact_hours">hər gün açıq</span></span>
          <span class="fact"><strong>WhatsApp</strong> <span data-i18n="hero_fact_wa">ilə sürətli sifariş</span></span>
        </div>
      </div>
      <div class="hero-art">
        <div class="frame">
          <?php if ($heroImage): ?>
            <img src="<?php echo h($heroImage); ?>" alt="<?php echo h($restaurantName); ?>" width="744" height="651" fetchpriority="high">
          <?php elseif ($logoFullCustom): ?>
            <img src="<?php echo h($logoFullCustom); ?>" alt="<?php echo h($restaurantName); ?> — loqo" width="744" height="651" fetchpriority="high">
          <?php else: ?>
            <?php sg_picture('assets/logo-full', h($restaurantName) . ' — loqo', 'width="744" height="651" fetchpriority="high"'); ?>
          <?php endif; ?>
        </div>
        <div class="tag" data-i18n="hero_tag">Əl işi · Gündəlik təzə</div>
      </div>
    </div>
    <div class="wave-strip" aria-hidden="true"></div>
  </section>

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
        <p class="eyebrow" data-i18n="menu_eyebrow">Menyu</p>
        <h2 data-i18n="menu_title">Fəsil seçimləri</h2>
      </div>

      <?php if (count($catsWithItems) > 1): ?>
      <div class="menu-tabs-wrap">
        <nav class="menu-tabs" id="menu-tabs">
          <button type="button" class="active" data-cat="all" data-i18n="menu_tab_all">Hamısı</button>
          <?php foreach ($catsWithItems as $cat): ?>
            <button type="button" data-cat="<?php echo (int)$cat['id']; ?>" data-i18n-ru="<?php echo h($cat['name_ru'] ?? ''); ?>" data-i18n-en="<?php echo h($cat['name_en'] ?? ''); ?>"><?php echo h($cat['name']); ?></button>
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

  <section class="contact" id="elaqe">
    <div class="wrap contact-grid">
      <div class="reveal">
        <p class="eyebrow" data-i18n="contact_eyebrow">Əlaqə</p>
        <h2 style="font-size:2rem; margin-top:.5rem;" data-i18n="contact_title">Bizi tapın</h2>
        <ul class="info-list">
          <li><span class="k" data-i18n="contact_address">Ünvan</span><span class="v"><?php echo h($address); ?> — <a href="<?php echo h($mapsUrl); ?>" target="_blank" rel="noopener" data-i18n="contact_maps_link">Google Maps-da bax</a></span></li>
          <li><span class="k" data-i18n="contact_phone">Telefon</span><span class="v"><a href="tel:+<?php echo h($phoneWa); ?>" style="text-decoration:none;"><?php echo h($phoneDisplay); ?></a></span></li>
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
          <?php if ($igUrl): ?><a href="<?php echo h($igUrl); ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
          <?php if ($fbUrl): ?><a href="<?php echo h($fbUrl); ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
          <?php if ($ttUrl): ?><a href="<?php echo h($ttUrl); ?>" target="_blank" rel="noopener">TikTok</a><?php endif; ?>
          <a href="https://wa.me/<?php echo h($phoneWa); ?>" target="_blank" rel="noopener">WhatsApp</a>
        </div>
      </div>
      <a class="map-placeholder reveal" href="<?php echo h($mapsUrl); ?>" target="_blank" rel="noopener" style="text-decoration:none;">
        <span>📍 <?php echo h($restaurantName); ?> — <span data-i18n="contact_map_open">Google Maps-da aç</span></span><span class="sub" data-i18n="contact_map_route">Marşrutu almaq üçün klikləyin</span>
      </a>
    </div>
  </section>

</main>

<footer id="footer">
  <div class="wrap">
    <div class="footer-grid">
      <div>
        <a href="#top" class="wordmark"><?php echo h($restaurantName); ?></a>
        <p><?php echo h($restaurantTagline); ?></p>
      </div>
      <div class="footer-col">
        <h4 data-i18n="footer_nav_title">Naviqasiya</h4>
        <ul>
          <li><a href="#menyu" data-i18n="nav_menu">Menyu</a></li>
          <li><a href="#elaqe" data-i18n="nav_contact">Əlaqə</a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4 data-i18n="footer_contact_title">Əlaqə</h4>
        <ul>
          <li><a href="tel:+<?php echo h($phoneWa); ?>"><?php echo h($phoneDisplay); ?></a></li>
          <li><a href="https://wa.me/<?php echo h($phoneWa); ?>" target="_blank" rel="noopener">WhatsApp</a></li>
          <?php if ($igUrl): ?><li><a href="<?php echo h($igUrl); ?>" target="_blank" rel="noopener">Instagram</a></li><?php endif; ?>
        </ul>
      </div>
    </div>
    <div class="footer-row">
      <span>© <span id="year"></span> <?php echo h($restaurantName); ?> · sushigarden.az</span>
      <a href="#top" class="back-to-top" data-i18n="back_to_top">↑ Yuxarı</a>
    </div>
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
            <label><input type="radio" name="service_type" value="dine_in"><span data-i18n="service_dine_in">Restoranda yemək</span></label>
          </div>
        </div>

        <div class="field-block">
          <label data-i18n="field_name">Adınız</label>
          <input type="text" id="cust-name" data-i18n-placeholder="field_name_ph" placeholder="Adınız">
        </div>
        <div class="field-block">
          <label data-i18n="field_phone">Telefon</label>
          <input type="tel" id="cust-phone" placeholder="+...">
        </div>
        <div class="field-block" id="address-block">
          <label data-i18n="field_address">Ünvan</label>
          <input type="text" id="cust-address" data-i18n-placeholder="field_address_ph" placeholder="Çatdırılma ünvanınızı daxil edin...">
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
      <button id="success-close" class="btn btn-primary" data-i18n="close_btn">Bağla</button>
    </div>
  </div>
</div>

<script src="js/site.js?v=<?php echo (int)@filemtime(__DIR__ . '/js/site.js'); ?>" defer></script>
</body>
</html>
