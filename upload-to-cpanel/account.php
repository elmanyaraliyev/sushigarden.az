<?php
require_once __DIR__ . '/includes/customer_auth.php';

$customer = sg_current_customer();
if (!$customer) {
    header('Location: login.php?next=account.php');
    exit;
}

$restaurantName = sg_setting('restaurant_name', 'Sushi Garden');
$logoIconCustom = sg_setting('logo_icon', '');
$colorTheme = sg_setting('color_theme', 'forest');
$orders = sg_customer_orders($customer['id']);
?>
<!doctype html>
<html lang="az" data-color-theme="<?php echo h($colorTheme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Hesabım — <?php echo h($restaurantName); ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?php echo h(sg_favicon_url()); ?>" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<style>
  .track-wrap{max-width:640px; margin:0 auto; padding:70px 24px 90px;}
  .track-card{background:var(--bg-raised); border:1px solid var(--line); border-radius:16px; padding:2.2rem; box-shadow:var(--shadow);}
  .track-head{text-align:center; margin-bottom:2rem;}
  .track-head .wordmark{display:inline-flex; align-items:center; gap:.6rem; font-family:'Playfair Display',Georgia,serif; font-size:1.25rem; font-weight:800; text-decoration:none; color:var(--text); margin-bottom:1.2rem;}
  .track-head .brand-icon{width:34px; height:34px; border-radius:50%; object-fit:cover;}
  .account-profile{display:flex; align-items:center; justify-content:space-between; gap:1rem; flex-wrap:wrap; margin-bottom:1.6rem;}
  .account-profile h1{font-size:1.5rem;}
  .account-profile p{color:var(--text-soft); font-size:.88rem; margin-top:.25rem;}
  .track-history-row{
    display:flex; align-items:center; justify-content:space-between; gap:.8rem;
    padding:.9rem 0; border-bottom:1px dotted var(--line);
  }
  .track-history-row:last-child{border-bottom:none;}
  .track-history-main{min-width:0;}
  .track-history-num{font-weight:700; font-size:.94rem;}
  .track-history-num a{color:inherit; text-decoration:none;}
  .track-history-num a:hover{color:var(--accent);}
  .track-history-meta{font-size:.78rem; color:var(--text-soft); margin-top:.15rem;}
  .track-status-badge{
    display:inline-block; font-size:.68rem; font-weight:700; text-transform:uppercase;
    letter-spacing:.05em; padding:.2rem .55rem; border-radius:999px; margin-top:.3rem;
    background:var(--bg); color:var(--text-soft); border:1px solid var(--line);
  }
  .track-status-badge.st-completed{background:color-mix(in srgb, var(--accent) 20%, transparent); color:var(--accent); border-color:var(--accent);}
  .track-status-badge.st-cancelled{background:rgba(220,90,80,.14); color:#F3A79E; border-color:rgba(220,90,80,.35);}
  .track-history-actions{display:flex; align-items:center; gap:.6rem; flex-shrink:0;}
  .track-history-price{font-weight:700; color:var(--gold); white-space:nowrap;}
  .track-repeat-mini{
    background:none; border:1px solid var(--line); color:var(--text); border-radius:8px;
    padding:.4rem .7rem; font-size:.76rem; font-weight:600; cursor:pointer; white-space:nowrap;
    transition:border-color .15s, color .15s;
  }
  .track-repeat-mini:hover{border-color:var(--accent); color:var(--accent);}
  .empty-note{color:var(--text-soft); font-size:.9rem; text-align:center; padding:1.5rem 0;}
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
    <div class="account-profile">
      <div>
        <h1>Salam, <?php echo h($customer['name']); ?></h1>
        <p><?php echo h($customer['phone']); ?></p>
      </div>
      <a href="logout.php" class="btn btn-ghost btn-sm">Çıxış</a>
    </div>

    <div class="track-section" style="border-top:1px solid var(--line); padding-top:1.2rem;">
      <h3 style="font-size:.78rem; text-transform:uppercase; letter-spacing:.1em; color:var(--gold); margin-bottom:.9rem;">Sifariş tarixçəniz</h3>
      <?php if (!$orders): ?>
        <p class="empty-note">Hələ heç bir sifariş verməmisiniz.</p>
      <?php else: ?>
        <?php foreach ($orders as $o):
          $orderFull = sg_get_order($o['id']);
          $repeatItems = array_map(function ($it) {
              return ['product_id' => $it['product_id'], 'name' => $it['name'], 'price' => (float)$it['price'], 'qty' => (int)$it['qty']];
          }, $orderFull['items']);
        ?>
          <div class="track-history-row">
            <div class="track-history-main">
              <div class="track-history-num"><a href="track.php?id=<?php echo (int)$o['id']; ?>&t=<?php echo h($o['track_token']); ?>">#<?php echo (int)$o['id']; ?></a></div>
              <div class="track-history-meta"><?php echo h(date('d.m.Y H:i', strtotime($o['created_at']))); ?></div>
              <span class="track-status-badge st-<?php echo h($o['status']); ?>"><?php echo h(sg_order_status_label($o['status'])); ?></span>
            </div>
            <div class="track-history-actions">
              <span class="track-history-price"><?php echo sg_money($o['total']); ?></span>
              <button type="button" class="track-repeat-mini" data-repeat-items='<?php echo h(json_encode($repeatItems, JSON_UNESCAPED_UNICODE)); ?>'>🔁 Təkrarla</button>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
document.querySelectorAll('.track-repeat-mini').forEach(function (btn) {
  btn.addEventListener('click', function () {
    var items = [];
    try { items = JSON.parse(btn.getAttribute('data-repeat-items') || '[]'); } catch (e) {}
    if (!items.length) return;
    try { localStorage.setItem('sg_repeat_cart', JSON.stringify({ items: items })); } catch (e) {}
    window.location.href = 'index.php';
  });
});
</script>

</body>
</html>
