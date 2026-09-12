<?php
require_once __DIR__ . '/includes/functions.php';

$restaurantName = sg_setting('restaurant_name', 'Sushi Garden');
$logoIconCustom = sg_setting('logo_icon', '');
$phoneWa = sg_setting('phone_wa', defined('SG_PHONE_WA') ? SG_PHONE_WA : '');
$colorTheme = sg_setting('color_theme', 'forest');
$completedSoundType = sg_setting('customer_completed_sound_type', 'bundled');
$completedSoundFile = sg_setting('customer_completed_sound', '');

$id = (int)($_GET['id'] ?? 0);
$token = (string)($_GET['t'] ?? '');
$order = ($id > 0 && $token !== '') ? sg_get_order($id) : null;
$valid = $order && !empty($order['track_token']) && hash_equals($order['track_token'], $token);

// Link/localStorage itirilibsə, müştəri sifariş nömrəsi + telefonla özü tapa bilsin.
$lookupError = '';
if (!$valid && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $lookupId = (int)($_POST['order_id'] ?? 0);
    $lookupPhone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
    $lookupOrder = $lookupId > 0 ? sg_get_order($lookupId) : null;
    if ($lookupOrder) {
        $storedDigits = preg_replace('/\D+/', '', (string)$lookupOrder['customer_phone']);
        // son 9 rəqəmi tutuşduraraq müqayisə et (ölkə kodu/aparıcı sıfır fərqlərini nəzərə almadan)
        if (substr($storedDigits, -9) === substr($lookupPhone, -9) && strlen($lookupPhone) >= 9) {
            header('Location: track.php?id=' . $lookupOrder['id'] . '&t=' . $lookupOrder['track_token']);
            exit;
        }
    }
    $lookupError = 'Sifariş tapılmadı. Sifariş nömrəsini və sifariş zamanı yazdığınız telefon nömrəsini yoxlayın.';
}

$steps = [
    'pending' => ['label' => 'Qəbul edildi', 'icon' => '📥'],
    'preparing' => ['label' => 'Hazırlanır', 'icon' => '👨‍🍳'],
    'ready' => ['label' => 'Hazırdır', 'icon' => '📦'],
    'completed' => ['label' => 'Tamamlandı', 'icon' => '✅'],
];
if ($valid && $order['service_type'] === 'delivery') {
    $steps['ready']['label'] = 'Yoldadır';
    $steps['ready']['icon'] = '🛵';
    $steps['completed']['label'] = 'Çatdırıldı';
}
$stepKeys = array_keys($steps);
$currentIndex = $valid ? array_search($order['status'], $stepKeys, true) : false;
$isCancelled = $valid && $order['status'] === 'cancelled';
$existingReview = $valid ? sg_get_review($order['id']) : null;
$needsReviewPrompt = $valid && $order['status'] === 'completed' && !$existingReview;
?>
<!doctype html>
<html lang="az" data-color-theme="<?php echo h($colorTheme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $valid ? 'Sifariş #' . (int)$order['id'] : 'Sifariş tapılmadı'; ?> — <?php echo h($restaurantName); ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?php echo h(sg_favicon_url()); ?>" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<script src="js/notify-sounds.js"></script>
<style>
  .track-wrap{max-width:640px; margin:0 auto; padding:70px 24px 90px;}
  .track-card{background:var(--bg-raised); border:1px solid var(--line); border-radius:16px; padding:2.2rem; box-shadow:var(--shadow);}
  .track-head{text-align:center; margin-bottom:2rem;}
  .track-head .wordmark{display:inline-flex; align-items:center; gap:.6rem; font-family:'Playfair Display',Georgia,serif; font-size:1.25rem; font-weight:800; text-decoration:none; color:var(--text); margin-bottom:1.2rem;}
  .track-head .brand-icon{width:34px; height:34px; border-radius:50%; object-fit:cover;}
  .track-order-no{font-family:'Cormorant Garamond',serif; font-size:1.8rem; font-weight:800;}
  .track-placed{color:var(--text-soft); font-size:.86rem; margin-top:.3rem;}
  .track-stepper{display:flex; justify-content:space-between; margin:2.2rem 0; position:relative;}
  .track-stepper::before{content:''; position:absolute; top:20px; left:8%; right:8%; height:2px; background:var(--line); z-index:0;}
  .track-step{position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; gap:.5rem; flex:1;}
  .track-step .dot{width:40px; height:40px; border-radius:50%; background:var(--bg); border:2px solid var(--line); display:flex; align-items:center; justify-content:center; font-size:1.1rem; transition:border-color .3s, background .3s;}
  .track-step.done .dot{background:var(--accent); border-color:var(--accent);}
  .track-step.current .dot{border-color:var(--accent); box-shadow:0 0 0 4px color-mix(in srgb, var(--accent) 25%, transparent); animation:sg-track-pulse 1.6s ease-in-out infinite;}
  @keyframes sg-track-pulse{0%,100%{box-shadow:0 0 0 4px color-mix(in srgb, var(--accent) 25%, transparent);} 50%{box-shadow:0 0 0 8px color-mix(in srgb, var(--accent) 10%, transparent);}}
  .track-step .label{font-size:.74rem; text-align:center; color:var(--text-soft); max-width:80px;}
  .track-step.done .label, .track-step.current .label{color:var(--text); font-weight:600;}
  .track-cancelled{text-align:center; padding:1.4rem; background:rgba(220,90,80,.14); border:1px solid rgba(220,90,80,.35); border-radius:10px; color:#F3A79E; font-weight:700; margin:1.6rem 0;}
  .track-section{border-top:1px solid var(--line); padding-top:1.2rem; margin-top:1.2rem;}
  .track-section h3{font-size:.78rem; text-transform:uppercase; letter-spacing:.1em; color:var(--gold); margin-bottom:.7rem;}
  .track-info-row{display:flex; justify-content:space-between; font-size:.92rem; padding:.3rem 0; gap:1rem;}
  .track-info-row .k{color:var(--text-soft);}
  .track-items{list-style:none; margin:0; padding:0;}
  .track-items li{display:flex; justify-content:space-between; font-size:.92rem; padding:.4rem 0; border-bottom:1px dotted var(--line);}
  .track-totals .row{display:flex; justify-content:space-between; font-size:.9rem; padding:.25rem 0; color:var(--text-soft);}
  .track-totals .row.total{font-weight:700; font-size:1.05rem; color:var(--text); padding-top:.4rem;}
  .track-error{text-align:center; padding:2rem;}
  .track-live-note{text-align:center; font-size:.76rem; color:var(--text-soft); margin-top:1.4rem;}
  .track-history-row{
    display:flex; align-items:center; justify-content:space-between; gap:.8rem;
    padding:.8rem 0; border-bottom:1px dotted var(--line);
  }
  .track-history-row:last-child{border-bottom:none;}
  .track-history-main{min-width:0;}
  .track-history-num{font-weight:700; font-size:.92rem;}
  .track-history-num a{color:inherit; text-decoration:none;}
  .track-history-num a:hover{color:var(--accent);}
  .track-history-meta{font-size:.78rem; color:var(--text-soft); margin-top:.15rem;}
  .track-history-actions{display:flex; align-items:center; gap:.6rem; flex-shrink:0;}
  .track-history-price{font-weight:700; color:var(--gold); white-space:nowrap;}
  .track-repeat-mini{
    background:none; border:1px solid var(--line); color:var(--text); border-radius:8px;
    padding:.4rem .7rem; font-size:.76rem; font-weight:600; cursor:pointer; white-space:nowrap;
    transition:border-color .15s, color .15s;
  }
  .track-repeat-mini:hover{border-color:var(--accent); color:var(--accent);}

  .review-popup-overlay{
    position:fixed; inset:0; z-index:400; display:none;
    align-items:center; justify-content:center; padding:20px;
    background:rgba(0,0,0,.6);
  }
  .review-popup-overlay.show{display:flex;}
  .review-popup-card{
    background:var(--bg-raised); border:1px solid var(--line); border-radius:16px;
    padding:2rem; max-width:380px; width:100%; text-align:center; box-shadow:var(--shadow);
    animation:sg-review-pop .4s cubic-bezier(.34,1.56,.64,1);
  }
  @keyframes sg-review-pop{0%{transform:scale(.85); opacity:0;} 100%{transform:scale(1); opacity:1;}}
  .review-popup-icon{font-size:2.6rem; margin-bottom:.4rem;}
  .review-popup-card h3{font-family:'Playfair Display',serif; font-size:1.35rem; margin-bottom:.4rem;}
  .review-popup-card p{color:var(--text-soft); font-size:.9rem; margin-bottom:1rem;}
  .review-stars{font-size:2.2rem; letter-spacing:.15em; margin-bottom:1rem; cursor:pointer;}
  .review-stars span{color:var(--line); transition:color .15s, transform .15s;}
  .review-stars span.filled{color:var(--gold);}
  .review-stars span:hover{transform:scale(1.15);}
  .review-popup-card textarea{
    width:100%; padding:.65rem .8rem; border-radius:6px; border:1px solid var(--line);
    background:var(--bg); color:var(--text); font-size:.9rem; font-family:inherit;
    resize:vertical; min-height:3em; margin-bottom:1rem;
  }
  .review-popup-actions{display:flex; gap:.6rem;}
  .review-popup-actions .btn{flex:1; justify-content:center;}
  .review-thanks{color:var(--accent); font-weight:700; padding:1rem 0;}
</style>
</head>
<body style="background:var(--bg);">

<div class="track-wrap">
  <div class="track-head">
    <a href="index.php" class="wordmark">
      <img src="<?php echo h($logoIconCustom ?: 'assets/logo-icon.jpg'); ?>" class="brand-icon" alt="">
      <span><?php echo h($restaurantName); ?></span>
    </a>
  </div>

  <div class="track-card">
    <?php if (!$valid): ?>
      <div class="track-error">
        <p style="font-size:2.4rem; margin-bottom:.6rem;">🔍</p>
        <h2 style="font-family:'Cormorant Garamond',serif;">Sifarişinizi tapın</h2>
        <p style="color:var(--text-soft); margin-top:.6rem;">Sifariş nömrənizi və sifariş zamanı yazdığınız telefon nömrənizi daxil edin.</p>
      </div>
      <?php if ($lookupError): ?><div class="order-error" style="margin-bottom:1rem;"><?php echo h($lookupError); ?></div><?php endif; ?>
      <form method="post" style="display:flex; flex-direction:column; gap:.9rem; max-width:340px; margin:0 auto;">
        <div class="field-block" style="margin-bottom:0;">
          <label>Sifariş nömrəsi</label>
          <input type="text" name="order_id" inputmode="numeric" placeholder="məs. 10" required>
        </div>
        <div class="field-block" style="margin-bottom:0;">
          <label>Telefon nömrəniz</label>
          <input type="tel" name="phone" placeholder="050 123 45 67" required>
        </div>
        <button type="submit" class="btn btn-primary" style="justify-content:center;">Sifarişi tap</button>
      </form>
      <div style="text-align:center; margin-top:1.4rem;">
        <a href="index.php" class="btn btn-ghost" style="display:inline-flex;">Menyuya qayıt</a>
      </div>
    <?php else: ?>
      <div style="text-align:center;">
        <div class="track-order-no" id="track-order-no">Sifariş #<?php echo (int)$order['id']; ?></div>
        <div class="track-placed">Verilib: <?php echo h(date('d.m.Y H:i', strtotime($order['created_at']))); ?></div>
      </div>

      <?php if ($isCancelled): ?>
        <div class="track-cancelled" id="track-status-banner">❌ Bu sifariş ləğv edilib</div>
      <?php else: ?>
        <div class="track-stepper" id="track-stepper">
          <?php foreach ($steps as $key => $s): $idx = array_search($key, $stepKeys, true); ?>
            <div class="track-step <?php echo $idx < $currentIndex ? 'done' : ($idx === $currentIndex ? 'current' : ''); ?>" data-step="<?php echo h($key); ?>">
              <div class="dot"><?php echo $idx <= $currentIndex ? h($s['icon']) : '·'; ?></div>
              <div class="label"><?php echo h($s['label']); ?></div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <div class="track-section">
        <h3>Sifariş məlumatı</h3>
        <div class="track-info-row"><span class="k">Xidmət növü</span><span><?php echo h(sg_service_type_label($order['service_type'])); ?></span></div>
        <?php if ($order['service_type'] === 'delivery' && !empty($order['address'])): ?>
          <div class="track-info-row"><span class="k">Ünvan</span><span><?php echo h($order['address']); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['requested_time'])): ?>
          <div class="track-info-row"><span class="k">Təxmini vaxt</span><span><?php echo h(date('H:i', strtotime($order['requested_time']))); ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['party_size'])): ?>
          <div class="track-info-row"><span class="k">Adam sayı</span><span><?php echo (int)$order['party_size']; ?></span></div>
        <?php endif; ?>
        <?php if (!empty($order['notes'])): ?>
          <div class="track-info-row"><span class="k">Qeyd</span><span><?php echo h($order['notes']); ?></span></div>
        <?php endif; ?>
      </div>

      <div class="track-section">
        <h3>Məhsullar</h3>
        <ul class="track-items">
          <?php foreach ($order['items'] as $it): ?>
            <li><span><?php echo h($it['name']); ?> × <?php echo (int)$it['qty']; ?></span><span><?php echo sg_money($it['line_total']); ?></span></li>
          <?php endforeach; ?>
        </ul>
        <div class="track-totals" style="margin-top:.8rem;">
          <div class="row"><span>Aralıq yekun</span><span><?php echo sg_money($order['subtotal']); ?></span></div>
          <div class="row"><span>Bəxşiş</span><span><?php echo sg_money($order['tip']); ?></span></div>
          <div class="row total"><span>Ümumi</span><span><?php echo sg_money($order['total']); ?></span></div>
        </div>
        <?php
        $repeatItems = array_map(function ($it) {
            return ['product_id' => $it['product_id'], 'name' => $it['name'], 'price' => (float)$it['price'], 'qty' => (int)$it['qty']];
        }, $order['items']);
        ?>
        <button type="button" class="btn btn-ghost track-repeat-btn" style="margin-top:1rem; width:100%; justify-content:center;" data-repeat-items="<?php echo h(json_encode($repeatItems, JSON_UNESCAPED_UNICODE)); ?>">🔁 Bu sifarişi təkrarla</button>
      </div>

      <div class="track-section" id="track-history-section" style="display:none;">
        <h3>Keçmiş sifarişləriniz</h3>
        <div id="track-history-list"></div>
      </div>

      <p class="track-live-note">Bu səhifə statusu avtomatik yeniləyir — bağlamadan gözləyə bilərsiniz.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($valid): ?>
<div class="review-popup-overlay" id="review-popup">
  <div class="review-popup-card" id="review-popup-card">
    <div class="review-popup-icon">🎉</div>
    <h3>Sifarişiniz tamamlandı!</h3>
    <p>Necə idi? Rəyinizi bildirin:</p>
    <div class="review-stars" id="review-stars">
      <span data-star="1">★</span><span data-star="2">★</span><span data-star="3">★</span><span data-star="4">★</span><span data-star="5">★</span>
    </div>
    <textarea id="review-comment" placeholder="Qeyd (istəyə bağlı)"></textarea>
    <div class="review-popup-actions">
      <button type="button" class="btn btn-ghost" id="review-skip">Bağla</button>
      <button type="button" class="btn btn-primary" id="review-submit">Göndər</button>
    </div>
  </div>
</div>
<?php endif; ?>

<?php if ($valid): ?>
<script>
(function(){
  // ---- Rəy pəncərəsi: 5 ulduzlu qiymətləndirmə + qeyd ----
  var orderId = <?php echo (int)$order['id']; ?>;
  var orderToken = <?php echo json_encode($order['track_token']); ?>;
  var popup = document.getElementById('review-popup');
  var starsWrap = document.getElementById('review-stars');
  var stars = starsWrap ? starsWrap.querySelectorAll('span') : [];
  var commentEl = document.getElementById('review-comment');
  var submitBtn = document.getElementById('review-submit');
  var skipBtn = document.getElementById('review-skip');
  var selectedRating = 0;

  function paintStars(n){
    stars.forEach(function(s){
      s.classList.toggle('filled', parseInt(s.getAttribute('data-star'), 10) <= n);
    });
  }
  stars.forEach(function(s){
    s.addEventListener('mouseenter', function(){ paintStars(parseInt(s.getAttribute('data-star'), 10)); });
    s.addEventListener('click', function(){ selectedRating = parseInt(s.getAttribute('data-star'), 10); paintStars(selectedRating); });
  });
  if (starsWrap) starsWrap.addEventListener('mouseleave', function(){ paintStars(selectedRating); });

  function showReviewPopup(){
    if (!popup) return;
    popup.classList.add('show');
  }
  function hideReviewPopup(){
    if (popup) popup.classList.remove('show');
  }
  if (skipBtn) skipBtn.addEventListener('click', hideReviewPopup);
  if (submitBtn) {
    submitBtn.addEventListener('click', function(){
      if (!selectedRating) { alert('Zəhmət olmasa ulduz seçin.'); return; }
      submitBtn.disabled = true;
      fetch('review.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: orderId, t: orderToken, rating: selectedRating, comment: commentEl ? commentEl.value.trim() : '' })
      })
        .then(function(r){ return r.json(); })
        .then(function(data){
          submitBtn.disabled = false;
          if (data.ok) {
            document.getElementById('review-popup-card').innerHTML = '<div class="review-thanks">✓ Rəyiniz üçün təşəkkür edirik!</div>';
            setTimeout(hideReviewPopup, 1800);
          } else {
            alert(data.error || 'Xəta baş verdi.');
          }
        })
        .catch(function(){ submitBtn.disabled = false; });
    });
  }

  <?php if ($needsReviewPrompt): ?>
  setTimeout(showReviewPopup, 900);
  <?php endif; ?>

  <?php if ($isCancelled): ?>
  return;
  <?php endif; ?>

  // ---- Status izləmə (polling) ----
  var stepKeys = <?php echo json_encode($stepKeys); ?>;
  var stepper = document.getElementById('track-stepper');
  var lastStatus = <?php echo json_encode($order['status']); ?>;

  function applyStatus(status){
    var idx = stepKeys.indexOf(status);
    stepper.querySelectorAll('.track-step').forEach(function(el){
      var key = el.getAttribute('data-step');
      var i = stepKeys.indexOf(key);
      el.classList.remove('done', 'current');
      if (i < idx) el.classList.add('done');
      else if (i === idx) el.classList.add('current');
      var dot = el.querySelector('.dot');
      dot.textContent = i <= idx ? dot.textContent : '·';
    });
  }

  function playPing(){
    try {
      var ctx = new (window.AudioContext || window.webkitAudioContext)();
      var osc = ctx.createOscillator(), gain = ctx.createGain();
      osc.type = 'sine'; osc.frequency.value = 660;
      gain.gain.setValueAtTime(0.0001, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.3, ctx.currentTime + 0.02);
      gain.gain.exponentialRampToValueAtTime(0.0001, ctx.currentTime + 0.3);
      osc.connect(gain); gain.connect(ctx.destination);
      osc.start(); osc.stop(ctx.currentTime + 0.35);
      setTimeout(function(){ ctx.close(); }, 500);
    } catch (e) {}
  }

  // Müştəri "tamamlandı" səsi — hər iki admin bölməsi ilə eyni ton generatorunu
  // (js/notify-sounds.js) paylaşır; "bundled"/"custom" real fayl çalır.
  var completedSoundType = <?php echo json_encode($completedSoundType); ?>;
  var completedSoundFile = <?php echo json_encode($completedSoundFile); ?>;
  function playCompletedSound(){
    if (completedSoundType === 'none') return;
    if (completedSoundType === 'bundled') {
      try { new Audio('assets/sounds/order-completed.mp3').play().catch(function(){}); } catch (e) {}
      return;
    }
    if (completedSoundType === 'custom' && completedSoundFile) {
      try { new Audio(completedSoundFile).play().catch(function(){}); } catch (e) {}
      return;
    }
    if (typeof sgPlayToneSound === 'function') sgPlayToneSound(completedSoundType);
  }

  var originalTitle = document.title;
  var titleFlashTimer = null;
  function startTitleFlash(){
    stopTitleFlash();
    var on = false;
    titleFlashTimer = setInterval(function(){
      document.title = on ? originalTitle : '✅ Sifariş tamamlandı!';
      on = !on;
    }, 1000);
  }
  function stopTitleFlash(){
    if (titleFlashTimer) { clearInterval(titleFlashTimer); titleFlashTimer = null; document.title = originalTitle; }
  }
  document.addEventListener('visibilitychange', function(){ if (!document.hidden) stopTitleFlash(); });

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(function(){});
  }

  function poll(){
    fetch('track-status.php?id=<?php echo (int)$order['id']; ?>&t=<?php echo h($order['track_token']); ?>')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data.ok) return;
        if (data.status !== lastStatus) {
          lastStatus = data.status;
          if (data.status === 'cancelled') { window.location.reload(); return; }
          applyStatus(data.status);
          if (data.status === 'completed') {
            playCompletedSound();
            showReviewPopup();
            if (document.hidden) {
              startTitleFlash();
              if ('Notification' in window && Notification.permission === 'granted') {
                try {
                  var n = new Notification('✅ Sifariş tamamlandı — <?php echo h($restaurantName); ?>', {
                    body: 'Sifariş #<?php echo (int)$order['id']; ?> hazırdır!',
                    icon: '<?php echo h(sg_favicon_url()); ?>',
                    tag: 'sg-order-completed'
                  });
                  n.onclick = function(){ window.focus(); };
                } catch (e) {}
              }
            }
          } else {
            playPing();
          }
        }
      })
      .catch(function(){});
  }
  setInterval(poll, 12000);
})();
</script>
<?php endif; ?>

<script>
(function(){
  // ---- Sifarişi təkrarlamaq: məhsulları "sg_repeat_cart" açarına yazıb menyuya qayıdırıq ----
  function goRepeat(items){
    if (!items || !items.length) return;
    try { localStorage.setItem('sg_repeat_cart', JSON.stringify({ items: items })); } catch (e) {}
    window.location.href = 'index.php';
  }
  var directRepeatBtn = document.querySelector('.track-repeat-btn');
  if (directRepeatBtn) {
    directRepeatBtn.addEventListener('click', function(){
      var items = [];
      try { items = JSON.parse(directRepeatBtn.getAttribute('data-repeat-items') || '[]'); } catch (e) {}
      goRepeat(items);
    });
  }

  // ---- Keçmiş sifarişlər siyahısı (bu brauzerdən verilmiş bütün sifarişlər) ----
  var historySection = document.getElementById('track-history-section');
  var historyList = document.getElementById('track-history-list');
  if (!historySection || !historyList) return;

  var mine = [];
  try { mine = JSON.parse(localStorage.getItem('sg_my_orders') || '[]'); } catch (e) {}
  if (!mine.length) return;

  mine = mine.slice().reverse(); // ən yenisi öndə
  historySection.style.display = 'block';
  mine.forEach(function(o){
    var row = document.createElement('div');
    row.className = 'track-history-row';
    var dateStr = o.placedAt ? new Date(o.placedAt).toLocaleString('az-AZ', { day:'2-digit', month:'2-digit', year:'numeric', hour:'2-digit', minute:'2-digit' }) : '';
    row.innerHTML =
      '<div class="track-history-main">' +
        '<div class="track-history-num"><a href="track.php?id=' + o.id + '&t=' + o.t + '">' + (o.number || ('#' + o.id)) + '</a></div>' +
        '<div class="track-history-meta">' + dateStr + '</div>' +
      '</div>' +
      '<div class="track-history-actions">' +
        (typeof o.total === 'number' ? '<span class="track-history-price">' + o.total.toFixed(2).replace('.', ',') + ' ₼</span>' : '') +
        '<button type="button" class="track-repeat-mini">🔁 Təkrarla</button>' +
      '</div>';
    row.querySelector('.track-repeat-mini').addEventListener('click', function(){
      fetch('track-status.php?id=' + o.id + '&t=' + o.t)
        .then(function(r){ return r.json(); })
        .then(function(data){
          if (data.ok && data.items) goRepeat(data.items);
        })
        .catch(function(){});
    });
    historyList.appendChild(row);
  });
})();
</script>

</body>
</html>
