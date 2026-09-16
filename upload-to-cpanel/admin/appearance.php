<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } else {
        $action = $_POST['action'] ?? 'save';

        if ($action === 'save_site_url') {
            sg_set_setting('site_url', trim($_POST['site_url'] ?? ''));
            $_SESSION['flash_ok'] = 'Sayt linki yeniləndi.';
            header('Location: appearance.php');
            exit;
        }

        if ($action === 'save_theme') {
            $theme = $_POST['color_theme'] ?? 'forest';
            if (!array_key_exists($theme, sg_color_themes())) $theme = 'forest';
            sg_set_setting('color_theme', $theme);
            $_SESSION['flash_ok'] = 'Sayt teması yeniləndi.';
            header('Location: appearance.php');
            exit;
        }

        if ($action === 'save_theme_bg') {
            $result = sg_save_theme_bg_upload($_FILES['theme_bg_sakura'] ?? null);
            if ($result['ok']) {
                sg_delete_theme_bg('theme_bg_sakura');
                sg_set_setting('theme_bg_sakura', $result['file']);
                $_SESSION['flash_ok'] = 'Sakura fon şəkli yükləndi.';
            } else {
                $_SESSION['flash_err'] = $result['reason'];
            }
            header('Location: appearance.php');
            exit;
        }

        if ($action === 'remove_theme_bg') {
            sg_delete_theme_bg('theme_bg_sakura');
            $_SESSION['flash_ok'] = 'Sakura fon şəkli silindi.';
            header('Location: appearance.php');
            exit;
        }

        if (!empty($_FILES['logo_icon']['tmp_name'])) {
            $path = sg_save_branding_upload($_FILES['logo_icon'], 400);
            if ($path) { sg_set_setting('logo_icon', $path); } else { $errors[] = 'Loqo (kiçik) yüklənmədi.'; }
        }
        if (!empty($_FILES['logo_full']['tmp_name'])) {
            $path = sg_save_branding_upload($_FILES['logo_full'], 1000);
            if ($path) { sg_set_setting('logo_full', $path); } else { $errors[] = 'Loqo (tam) yüklənmədi.'; }
        }
        if (!empty($_FILES['hero_image']['tmp_name'])) {
            $path = sg_save_branding_upload($_FILES['hero_image'], 1600);
            if ($path) { sg_set_setting('hero_image', $path); } else { $errors[] = 'Hero şəkli yüklənmədi.'; }
        }

        if (!$errors) {
            $_SESSION['flash_ok'] = 'Görünüş yeniləndi.';
            header('Location: appearance.php');
            exit;
        }
    }
}

$csrf = sg_csrf_token();
$pageTitle = 'Görünüş';
$activeNav = 'appearance';
require __DIR__ . '/includes/header.php';

$siteUrl = sg_setting('site_url', '');
if (!$siteUrl) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $siteUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'sushigarden.az') . '/';
}
?>

<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<form method="post" enctype="multipart/form-data">
  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
  <input type="hidden" name="action" value="save">

  <div class="panel" style="max-width:640px;">
    <div class="panel-head"><h2>Loqo və hero şəkli</h2></div>

    <div class="field">
      <label>Loqo (kiçik, dairəvi ikon — header üçün)</label>
      <div class="img-preview-row">
        <?php if (sg_setting('logo_icon')): ?>
          <img src="../<?php echo h(sg_setting('logo_icon')); ?>" class="img-preview">
        <?php else: ?>
          <img src="../assets/logo-icon.jpg" class="img-preview">
        <?php endif; ?>
        <input type="file" name="logo_icon" accept="image/*">
      </div>
    </div>

    <div class="field">
      <label>Loqo (tam ölçü — hero bölməsində)</label>
      <div class="img-preview-row">
        <?php if (sg_setting('logo_full')): ?>
          <img src="../<?php echo h(sg_setting('logo_full')); ?>" class="img-preview">
        <?php else: ?>
          <img src="../assets/logo-full.jpg" class="img-preview">
        <?php endif; ?>
        <input type="file" name="logo_full" accept="image/*">
      </div>
    </div>

    <div class="field">
      <label>Hero fon şəkli (istəyə bağlı — yüklənməzsə loqo göstərilir)</label>
      <div class="img-preview-row">
        <?php if (sg_setting('hero_image')): ?>
          <img src="../<?php echo h(sg_setting('hero_image')); ?>" class="img-preview">
        <?php else: ?>
          <div class="img-preview" style="display:flex; align-items:center; justify-content:center; font-size:1.4rem;">—</div>
        <?php endif; ?>
        <input type="file" name="hero_image" accept="image/*">
      </div>
    </div>

    <button type="submit" class="btn btn-primary">Şəkilləri yadda saxla</button>
  </div>
</form>

<div class="panel" style="max-width:640px;">
  <div class="panel-head"><h2>Sayt teması</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.4rem;">Saytın rəng temasını seçin — struktur/tərtibat eyni qalır, yalnız fon və vurğu rəngləri dəyişir.</p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="save_theme">
    <div class="theme-swatch-grid">
      <?php $currentTheme = sg_setting('color_theme', 'forest'); ?>
      <?php foreach (sg_color_themes() as $key => $t): ?>
        <label class="theme-swatch <?php echo $key === $currentTheme ? 'active' : ''; ?>">
          <input type="radio" name="color_theme" value="<?php echo h($key); ?>" <?php echo $key === $currentTheme ? 'checked' : ''; ?>>
          <span class="theme-swatch-preview" style="background:<?php echo h($t['bg']); ?>;">
            <span style="background:<?php echo h($t['accent']); ?>;"></span>
            <span style="background:<?php echo h($t['gold']); ?>;"></span>
          </span>
          <span class="theme-swatch-name"><?php echo h($t['label']); ?></span>
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Temanı tətbiq et</button>
  </form>
</div>
<script>
document.querySelectorAll('.theme-swatch input[type="radio"]').forEach(function (r) {
  r.addEventListener('change', function () {
    document.querySelectorAll('.theme-swatch').forEach(function (s) { s.classList.remove('active'); });
    r.closest('.theme-swatch').classList.add('active');
  });
});
</script>

<div class="panel" style="max-width:640px;">
  <div class="panel-head"><h2>Sakura teması fon şəkli</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.4rem;">
    "Sakura (Yaponiya)" teması seçildikdə saytın fonunda göstəriləcək şəkil —
    yükləməsəniz, standart Yaponiya mənzərəsi (paqoda + Fuji dağı) istifadə olunur,
    öz şəklinizi yükləsəniz o əvəzinə göstərilir.
  </p>
  <div class="img-preview-row">
    <?php if (sg_setting('theme_bg_sakura')): ?>
      <img src="../<?php echo h(sg_setting('theme_bg_sakura')); ?>" class="img-preview">
    <?php else: ?>
      <div class="img-preview" style="display:flex; align-items:center; justify-content:center; font-size:1.4rem;">—</div>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data" style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <input type="hidden" name="action" value="save_theme_bg">
      <input type="file" name="theme_bg_sakura" accept="image/*">
      <button type="submit" class="btn btn-primary btn-sm">Yüklə</button>
    </form>
    <?php if (sg_setting('theme_bg_sakura')): ?>
      <form method="post" onsubmit="return confirm('Fon şəklini silmək istədiyinizə əminsiniz?');">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
        <input type="hidden" name="action" value="remove_theme_bg">
        <button type="submit" class="btn btn-danger btn-sm">Sil</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<div class="panel" style="max-width:640px;">
  <div class="panel-head"><h2>Sayt linki (QR kod üçün)</h2></div>
  <form method="post" style="display:flex; gap:.8rem; flex-wrap:wrap; align-items:flex-end;">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="save_site_url">
    <div class="field" style="flex:1; min-width:220px; margin-bottom:0;">
      <label>Saytın tam linki</label>
      <input type="text" name="site_url" value="<?php echo h($siteUrl); ?>" placeholder="https://sushigarden.az/">
    </div>
    <button type="submit" class="btn btn-ghost">Yadda saxla</button>
  </form>
</div>

<div class="panel" style="max-width:400px;">
  <div class="panel-head"><h2>QR kod</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem;">Masalara qoymaq üçün çap edin — müştərilər skan edərək birbaşa menyuya keçəcək.</p>
  <div style="text-align:center;">
    <img src="https://api.qrserver.com/v1/create-qr-code/?size=320x320&data=<?php echo urlencode($siteUrl); ?>" alt="QR kod" width="320" height="320" style="max-width:100%; border:1px solid var(--line); border-radius:8px;">
    <div style="margin-top:1rem;">
      <a href="https://api.qrserver.com/v1/create-qr-code/?size=800x800&data=<?php echo urlencode($siteUrl); ?>" target="_blank" rel="noopener" class="btn btn-ghost btn-sm">↗ Böyük ölçüdə aç / yüklə</a>
    </div>
  </div>
  <p style="color:var(--text-soft); font-size:.78rem; margin-top:1rem;">Qeyd: QR şəkli xarici bir xidmətdən (api.qrserver.com) yüklənir — bu yalnız admin panelə baxarkən internetə ehtiyac yaradır, canlı saytın işləməsinə təsiri yoxdur.</p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
