<?php
require_once __DIR__ . '/includes/customer_auth.php';

if (sg_current_customer()) {
    header('Location: account.php');
    exit;
}

$restaurantName = sg_setting('restaurant_name', 'Sushi Garden');
$logoIconCustom = sg_setting('logo_icon', '');
$colorTheme = sg_setting('color_theme', 'forest');
$csrf = sg_csrf_token();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } else {
        $result = sg_customer_login($_POST['phone'] ?? '', $_POST['password'] ?? '');
        if ($result['ok']) {
            $redirect = $_GET['next'] ?? 'account.php';
            header('Location: ' . $redirect);
            exit;
        }
        $error = $result['error'];
    }
}
?>
<!doctype html>
<html lang="az" data-color-theme="<?php echo h($colorTheme); ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daxil ol — <?php echo h($restaurantName); ?></title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="<?php echo h(sg_favicon_url()); ?>" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,500;0,600;0,700;1,600&family=Playfair+Display:wght@700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/style.css?v=<?php echo (int)@filemtime(__DIR__ . '/css/style.css'); ?>">
<style>
  .auth-wrap{max-width:440px; margin:0 auto; padding:70px 24px 90px;}
  .auth-card{background:var(--bg-raised); border:1px solid var(--line); border-radius:16px; padding:2.2rem; box-shadow:var(--shadow);}
  .auth-head{text-align:center; margin-bottom:1.6rem;}
  .auth-head .wordmark{display:inline-flex; align-items:center; gap:.6rem; font-family:'Playfair Display',Georgia,serif; font-size:1.25rem; font-weight:800; text-decoration:none; color:var(--text); margin-bottom:1.2rem;}
  .auth-head .brand-icon{width:34px; height:34px; border-radius:50%; object-fit:cover;}
  .auth-head h1{font-size:1.5rem;}
  .auth-foot{text-align:center; margin-top:1.2rem; font-size:.86rem; color:var(--text-soft);}
  .auth-foot a{color:var(--accent); font-weight:600; text-decoration:none;}
</style>
</head>
<body style="background:var(--bg);">
<div class="auth-wrap">
  <div class="auth-head">
    <a href="index.php" class="wordmark">
      <img src="<?php echo h($logoIconCustom ?: 'assets/logo-icon.jpg'); ?>" class="brand-icon" alt="">
      <span><?php echo h($restaurantName); ?></span>
    </a>
    <h1>Hesabınıza daxil olun</h1>
  </div>
  <div class="auth-card">
    <?php if ($error): ?><div class="order-error" style="margin-bottom:1rem;"><?php echo h($error); ?></div><?php endif; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <div class="field-block">
        <label>Telefon</label>
        <input type="tel" name="phone" placeholder="050 123 45 67" required autofocus>
      </div>
      <div class="field-block">
        <label>Şifrə</label>
        <input type="password" name="password" required>
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Daxil ol</button>
    </form>
    <div class="auth-foot">Hesabınız yoxdur? <a href="register.php">Qeydiyyatdan keçin</a></div>
  </div>
</div>
</body>
</html>
