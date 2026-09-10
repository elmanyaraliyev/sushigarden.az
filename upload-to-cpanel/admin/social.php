<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: social.php');
        exit;
    }

    sg_set_setting('social_instagram', trim($_POST['social_instagram'] ?? ''));
    sg_set_setting('social_facebook', trim($_POST['social_facebook'] ?? ''));
    sg_set_setting('social_tiktok', trim($_POST['social_tiktok'] ?? ''));

    $_SESSION['flash_ok'] = 'Sosial şəbəkə linkləri yeniləndi.';
    header('Location: social.php');
    exit;
}

$csrf = sg_csrf_token();
$pageTitle = 'Sosial Şəbəkələr';
$activeNav = 'social';
require __DIR__ . '/includes/header.php';
?>

<form method="post" class="panel" style="max-width:640px;">
  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
  <div class="panel-head"><h2>Sosial şəbəkə linkləri</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1.2rem;">Boş buraxılan link saytda göstərilmir.</p>

  <div class="field">
    <label>Instagram</label>
    <input type="text" name="social_instagram" placeholder="https://instagram.com/..." value="<?php echo h(sg_setting('social_instagram')); ?>">
  </div>
  <div class="field">
    <label>Facebook</label>
    <input type="text" name="social_facebook" placeholder="https://facebook.com/..." value="<?php echo h(sg_setting('social_facebook')); ?>">
  </div>
  <div class="field">
    <label>TikTok</label>
    <input type="text" name="social_tiktok" placeholder="https://tiktok.com/@..." value="<?php echo h(sg_setting('social_tiktok')); ?>">
  </div>

  <button type="submit" class="btn btn-primary">Yadda saxla</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
