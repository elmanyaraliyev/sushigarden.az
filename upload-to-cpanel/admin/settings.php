<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();
$admin = sg_current_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newUsername = trim($_POST['username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $newPassword2 = $_POST['new_password2'] ?? '';

        $stmt = $pdo->prepare('SELECT * FROM admin_users WHERE id = ?');
        $stmt->execute([$admin['id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $errors[] = 'Hazırkı şifrə yanlışdır.';
        }
        if ($newUsername === '') {
            $errors[] = 'İstifadəçi adı boş ola bilməz.';
        }
        if ($newPassword !== '' && strlen($newPassword) < 6) {
            $errors[] = 'Yeni şifrə ən azı 6 simvol olmalıdır.';
        }
        if ($newPassword !== $newPassword2) {
            $errors[] = 'Yeni şifrələr üst-üstə düşmür.';
        }

        if (!$errors) {
            if ($newPassword !== '') {
                $upd = $pdo->prepare('UPDATE admin_users SET username = ?, password_hash = ? WHERE id = ?');
                $upd->execute([$newUsername, password_hash($newPassword, PASSWORD_DEFAULT), $admin['id']]);
            } else {
                $upd = $pdo->prepare('UPDATE admin_users SET username = ? WHERE id = ?');
                $upd->execute([$newUsername, $admin['id']]);
            }
            $_SESSION['admin_username'] = $newUsername;
            $_SESSION['flash_ok'] = 'Parametrlər yeniləndi.';
            header('Location: settings.php');
            exit;
        }
    }
}

$csrf = sg_csrf_token();
$pageTitle = 'Parametrlər';
$activeNav = 'settings';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:520px;">
  <div class="panel-head"><h2>Giriş məlumatlarını dəyiş</h2></div>
  <?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <div class="field">
      <label>İstifadəçi adı</label>
      <input type="text" name="username" value="<?php echo h($admin['username']); ?>" required>
    </div>
    <div class="field">
      <label>Hazırkı şifrə</label>
      <input type="password" name="current_password" required>
    </div>
    <div class="field">
      <label>Yeni şifrə (dəyişmək istəmirsinizsə boş buraxın)</label>
      <input type="password" name="new_password">
    </div>
    <div class="field">
      <label>Yeni şifrə (təkrar)</label>
      <input type="password" name="new_password2">
    </div>
    <button type="submit" class="btn btn-primary">Yadda saxla</button>
  </form>
</div>

<div class="panel" style="max-width:520px;">
  <div class="panel-head"><h2>Yeni sifariş bildiriş səsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Yeni sifariş daxil olanda admin paneldə (hansı səhifədə olmağınızdan asılı olmayaraq) bu səs çalınacaq.
    Seçim bu brauzerdə yadda saxlanılır.
  </p>
  <div class="sound-options" id="sound-options">
    <label><input type="radio" name="sg_sound" value="chime"> Zəng (üçlü)</label>
    <label><input type="radio" name="sg_sound" value="beep1"> Bip (tək)</label>
    <label><input type="radio" name="sg_sound" value="beep2"> Bip (ikili)</label>
    <label><input type="radio" name="sg_sound" value="none"> Səssiz</label>
  </div>
  <button type="button" class="btn btn-ghost btn-sm" id="sound-test" style="margin-top:1rem;">🔊 Sına</button>
</div>

<script>
(function(){
  var KEY = 'sg_admin_sound';
  var radios = document.querySelectorAll('#sound-options input[type=radio]');
  var current = 'chime';
  try { current = localStorage.getItem(KEY) || 'chime'; } catch (e) {}
  radios.forEach(function(r){ r.checked = (r.value === current); });
  radios.forEach(function(r){
    r.addEventListener('change', function(){
      try { localStorage.setItem(KEY, r.value); } catch (e) {}
    });
  });
  document.getElementById('sound-test').addEventListener('click', function(){
    var sel = document.querySelector('#sound-options input[type=radio]:checked');
    if (sel && window.sgPlayAdminSound) window.sgPlayAdminSound(sel.value);
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
