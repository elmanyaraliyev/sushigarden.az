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

<?php require __DIR__ . '/includes/footer.php'; ?>
