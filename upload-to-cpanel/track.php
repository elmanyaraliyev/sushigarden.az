<?php
require_once __DIR__ . '/includes/functions.php';

$restaurantName = sg_setting('restaurant_name', 'Sushi Garden');
$logoIconCustom = sg_setting('logo_icon', '');
$phoneWa = sg_setting('phone_wa', defined('SG_PHONE_WA') ? SG_PHONE_WA : '');
$colorTheme = sg_setting('color_theme', 'forest');

$id = (int)($_GET['id'] ?? 0);
$token = (string)($_GET['t'] ?? '');
$order = ($id > 0 && $token !== '') ? sg_get_order($id) : null;
$valid = $order && !empty($order['track_token']) && hash_equals($order['track_token'], $token);

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
?>
<!doctype html>
<html lang="az" data-color-theme="<?php echo h($colorTheme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $valid ? 'Sifariş #' . (int)$order['id'] : 'Sifariş tapılmadı'; ?> — <?php echo h($restaurantName); ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?php echo h($logoIconCustom ?: 'assets/logo-icon.jpg'); ?>">
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800;900&family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<style>
  .track-wrap{max-width:640px; margin:0 auto; padding:70px 24px 90px;}
  .track-card{background:var(--bg-raised); border:1px solid var(--line); border-radius:16px; padding:2.2rem; box-shadow:var(--shadow);}
  .track-head{text-align:center; margin-bottom:2rem;}
  .track-head .wordmark{display:inline-flex; align-items:center; gap:.6rem; font-family:'Playfair Display',serif; font-size:1.2rem; font-weight:700; text-decoration:none; color:var(--text); margin-bottom:1.2rem;}
  .track-head .brand-icon{width:34px; height:34px; border-radius:50%; object-fit:cover;}
  .track-order-no{font-family:'Playfair Display',serif; font-size:1.8rem; font-weight:800;}
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
        <h2 style="font-family:'Playfair Display',serif;">Sifariş tapılmadı</h2>
        <p style="color:var(--text-soft); margin-top:.6rem;">Link düzgün deyil və ya sifariş artıq mövcud deyil.</p>
        <a href="index.php" class="btn btn-primary" style="margin-top:1.4rem; display:inline-flex;">Menyuya qayıt</a>
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
      </div>

      <p class="track-live-note">Bu səhifə statusu avtomatik yeniləyir — bağlamadan gözləyə bilərsiniz.</p>
    <?php endif; ?>
  </div>
</div>

<?php if ($valid && !$isCancelled): ?>
<script>
(function(){
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

  function poll(){
    fetch('track-status.php?id=<?php echo (int)$order['id']; ?>&t=<?php echo h($order['track_token']); ?>')
      .then(function(r){ return r.json(); })
      .then(function(data){
        if (!data.ok) return;
        if (data.status !== lastStatus) {
          lastStatus = data.status;
          if (data.status === 'cancelled') { window.location.reload(); return; }
          applyStatus(data.status);
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
      })
      .catch(function(){});
  }
  setInterval(poll, 12000);
})();
</script>
<?php endif; ?>

</body>
</html>
