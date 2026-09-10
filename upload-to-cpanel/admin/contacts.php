<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: contacts.php');
        exit;
    }

    $phoneDisplay = trim($_POST['phone_display'] ?? '');
    $phoneWa = preg_replace('/[^0-9]/', '', $_POST['phone_wa'] ?? '');
    $phoneWa2 = trim($_POST['phone_wa2'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $mapsUrl = trim($_POST['maps_url'] ?? '');

    if ($phoneDisplay === '' || $phoneWa === '') {
        $_SESSION['flash_err'] = 'Telefon nömrəsi boş ola bilməz.';
        header('Location: contacts.php');
        exit;
    }

    sg_set_setting('phone_display', $phoneDisplay);
    sg_set_setting('phone_wa', $phoneWa);
    sg_set_setting('phone_wa2', $phoneWa2);
    sg_set_setting('address', $address);
    sg_set_setting('maps_url', $mapsUrl);

    $_SESSION['flash_ok'] = 'Əlaqə məlumatları yeniləndi.';
    header('Location: contacts.php');
    exit;
}

$csrf = sg_csrf_token();
$pageTitle = 'Əlaqələr';
$activeNav = 'contacts';
require __DIR__ . '/includes/header.php';
?>

<form method="post" class="panel" style="max-width:640px;">
  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
  <div class="panel-head"><h2>Əlaqə məlumatları</h2></div>

  <div class="field">
    <label>Telefon (göstəriləcək format)</label>
    <input type="text" name="phone_display" placeholder="+994 55 679 50 70" value="<?php echo h(sg_setting('phone_display')); ?>" required>
  </div>
  <div class="field">
    <label>WhatsApp nömrəsi (yalnız rəqəm, ölkə kodu ilə)</label>
    <input type="text" name="phone_wa" placeholder="994556795070" value="<?php echo h(sg_setting('phone_wa')); ?>" required>
  </div>
  <div class="field">
    <label>Əlavə telefon (istəyə bağlı)</label>
    <input type="text" name="phone_wa2" value="<?php echo h(sg_setting('phone_wa2')); ?>">
  </div>
  <div class="field">
    <label>Ünvan</label>
    <input type="text" name="address" value="<?php echo h(sg_setting('address')); ?>">
  </div>
  <div class="field">
    <label>Google Maps linki</label>
    <input type="text" name="maps_url" value="<?php echo h(sg_setting('maps_url')); ?>">
  </div>

  <button type="submit" class="btn btn-primary">Yadda saxla</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
