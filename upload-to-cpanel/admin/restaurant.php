<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

$dayLabels = sg_day_labels();
$hours = sg_hours();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: restaurant.php');
        exit;
    }

    $name = trim($_POST['restaurant_name'] ?? '');
    $tagline = trim($_POST['restaurant_tagline'] ?? '');

    if ($name === '') {
        $_SESSION['flash_err'] = 'Restoran adı boş ola bilməz.';
        header('Location: restaurant.php');
        exit;
    }

    sg_set_setting('restaurant_name', $name);
    sg_set_setting('restaurant_tagline', $tagline);

    $newHours = [];
    foreach (array_keys($dayLabels) as $key) {
        $newHours[$key] = [
            'closed' => isset($_POST['closed'][$key]) ? 1 : 0,
            'open' => trim($_POST['open'][$key] ?? '11:00'),
            'close' => trim($_POST['close'][$key] ?? '23:00'),
        ];
    }
    sg_set_setting('hours', json_encode($newHours, JSON_UNESCAPED_UNICODE));

    $_SESSION['flash_ok'] = 'Restoran məlumatları yeniləndi.';
    header('Location: restaurant.php');
    exit;
}

$csrf = sg_csrf_token();
$pageTitle = 'Restoran';
$activeNav = 'restaurant';
require __DIR__ . '/includes/header.php';
?>

<form method="post">
  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">

  <div class="panel" style="max-width:640px;">
    <div class="panel-head"><h2>Ümumi məlumat</h2></div>
    <div class="field">
      <label>Restoran adı</label>
      <input type="text" name="restaurant_name" value="<?php echo h(sg_setting('restaurant_name')); ?>" required>
    </div>
    <div class="field">
      <label>Sloqan</label>
      <input type="text" name="restaurant_tagline" value="<?php echo h(sg_setting('restaurant_tagline')); ?>">
    </div>
  </div>

  <div class="panel" style="max-width:640px;">
    <div class="panel-head"><h2>İş saatları</h2></div>
    <div class="hours-grid">
      <?php foreach ($dayLabels as $key => $label): $d = $hours[$key] ?? ['open' => '11:00', 'close' => '23:00', 'closed' => 0]; ?>
        <div class="hours-row">
          <span class="day-name"><?php echo h($label); ?></span>
          <input type="time" name="open[<?php echo h($key); ?>]" value="<?php echo h($d['open']); ?>">
          <span>–</span>
          <input type="time" name="close[<?php echo h($key); ?>]" value="<?php echo h($d['close']); ?>">
          <label class="checkbox-row">
            <input type="checkbox" name="closed[<?php echo h($key); ?>]" <?php echo !empty($d['closed']) ? 'checked' : ''; ?>> Bağlıdır
          </label>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <button type="submit" class="btn btn-primary">Yadda saxla</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
