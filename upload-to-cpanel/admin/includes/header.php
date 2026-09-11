<?php
// $pageTitle və $activeNav çağıran səhifədə təyin olunmalıdır
$admin = sg_current_admin();
$sg_pendingOrders = (int)sg_db()->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
?>
<!doctype html>
<html lang="az">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo h($pageTitle ?? 'İdarəetmə Paneli'); ?> — Sushi Garden</title>
<link rel="icon" href="<?php echo h(sg_favicon_url(true)); ?>" type="image/jpeg">
<link href="https://fonts.googleapis.com/css2?family=Noto+Serif:wght@600;700&family=Noto+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/admin.css?v=<?php echo (int)@filemtime(__DIR__ . '/../assets/admin.css'); ?>">
</head>
<body>
<div class="admin-shell">
  <aside class="sidebar">
    <div class="brand">
      <img src="../assets/logo-icon.jpg" alt="">
      <span>Sushi Garden</span>
    </div>
    <nav>
      <a href="dashboard.php" class="<?php echo ($activeNav ?? '') === 'dashboard' ? 'active' : ''; ?>">📊 İdarə paneli</a>
      <a href="orders.php" class="<?php echo ($activeNav ?? '') === 'orders' ? 'active' : ''; ?>">🧾 Sifarişlər<?php echo $sg_pendingOrders ? ' <span class="nav-badge">' . $sg_pendingOrders . '</span>' : ''; ?></a>
      <a href="restaurant.php" class="<?php echo ($activeNav ?? '') === 'restaurant' ? 'active' : ''; ?>">🏠 Restoran</a>
      <a href="appearance.php" class="<?php echo ($activeNav ?? '') === 'appearance' ? 'active' : ''; ?>">🎨 Görünüş</a>
      <a href="categories.php" class="<?php echo ($activeNav ?? '') === 'categories' ? 'active' : ''; ?>">📂 Kateqoriyalar</a>
      <a href="products.php" class="<?php echo ($activeNav ?? '') === 'products' ? 'active' : ''; ?>">🍣 Menyu</a>
      <a href="contacts.php" class="<?php echo ($activeNav ?? '') === 'contacts' ? 'active' : ''; ?>">☎️ Əlaqələr</a>
      <a href="social.php" class="<?php echo ($activeNav ?? '') === 'social' ? 'active' : ''; ?>">🔗 Sosial Şəbəkələr</a>
      <a href="settings.php" class="<?php echo ($activeNav ?? '') === 'settings' ? 'active' : ''; ?>">⚙️ Parametrlər</a>
    </nav>
    <div class="view-site">
      <a href="../index.php" target="_blank" rel="noopener">↗ Saytı görüntülə</a>
    </div>
  </aside>
  <div class="main">
    <div class="topbar">
      <h1><?php echo h($pageTitle ?? ''); ?></h1>
      <div class="user">
        <span><?php echo h($admin['username'] ?? ''); ?></span>
        <a href="logout.php">Çıxış</a>
      </div>
    </div>
    <div class="content">
      <?php if (!empty($_SESSION['flash_ok'])): ?>
        <div class="flash ok"><?php echo h($_SESSION['flash_ok']); unset($_SESSION['flash_ok']); ?></div>
      <?php endif; ?>
      <?php if (!empty($_SESSION['flash_err'])): ?>
        <div class="flash err"><?php echo h($_SESSION['flash_err']); unset($_SESSION['flash_err']); ?></div>
      <?php endif; ?>
