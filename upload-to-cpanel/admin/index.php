<?php
require_once __DIR__ . '/includes/auth.php';

if (sg_current_admin()) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $error = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } elseif (sg_admin_login($_POST['username'] ?? '', $_POST['password'] ?? '')) {
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'İstifadəçi adı və ya şifrə yanlışdır.';
    }
}
$csrf = sg_csrf_token();
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Giriş — Sushi Garden İdarəetmə Paneli</title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css">
</head>
<body>
  <div class="login-wrap">
    <form class="login-box" method="post">
      <img src="../assets/logo-icon.jpg" alt="Sushi Garden">
      <h1>İdarəetmə Paneli</h1>
      <?php if ($error): ?><div class="flash err"><?php echo h($error); ?></div><?php endif; ?>
      <div class="field">
        <label>İstifadəçi adı</label>
        <input type="text" name="username" required autofocus>
      </div>
      <div class="field">
        <label>Şifrə</label>
        <input type="password" name="password" required>
      </div>
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <button type="submit" class="btn btn-primary" style="width:100%; justify-content:center;">Daxil ol</button>
    </form>
  </div>
</body>
</html>
