<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();

$pdo = sg_db();
$totalProducts = (int)$pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$activeProducts = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE active = 1')->fetchColumn();
$hiddenProducts = $totalProducts - $activeProducts;
$totalCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$activeCategories = (int)$pdo->query('SELECT COUNT(*) FROM categories WHERE active = 1')->fetchColumn();
$withPhotos = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE image IS NOT NULL AND image != ''")->fetchColumn();
$totalOrders = (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$readyOrders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'ready'")->fetchColumn();

$pageTitle = 'İdarə paneli';
$activeNav = 'dashboard';
require __DIR__ . '/includes/header.php';
?>

<div class="cards">
  <div class="card"><div class="num"><?php echo $totalOrders; ?></div><div class="label">Cəmi sifariş</div></div>
  <div class="card"><div class="num"><?php echo $pendingOrders; ?></div><div class="label">Gözləyən sifariş</div></div>
  <div class="card"><div class="num"><?php echo $readyOrders; ?></div><div class="label">Hazır sifariş</div></div>
  <div class="card"><div class="num"><?php echo $totalProducts; ?></div><div class="label">Ümumi məhsul</div></div>
  <div class="card"><div class="num"><?php echo $activeProducts; ?></div><div class="label">Görünən məhsul</div></div>
  <div class="card"><div class="num"><?php echo $activeCategories; ?> / <?php echo $totalCategories; ?></div><div class="label">Aktiv kateqoriya</div></div>
</div>

<div class="panel">
  <div class="panel-head">
    <h2>Tez əməliyyatlar</h2>
  </div>
  <div style="display:flex; gap:.8rem; flex-wrap:wrap;">
    <a href="orders.php" class="btn btn-primary">🧾 Sifarişlərə bax</a>
    <a href="product-form.php" class="btn btn-ghost">+ Yeni məhsul əlavə et</a>
    <a href="categories.php" class="btn btn-ghost">Kateqoriyaları idarə et</a>
    <a href="products.php" class="btn btn-ghost">Bütün məhsullara bax</a>
    <a href="../index.php" target="_blank" class="btn btn-ghost">↗ Canlı saytı gör</a>
  </div>
</div>

<div class="panel">
  <div class="panel-head"><h2>Necə işləyir?</h2></div>
  <p style="color:var(--text-soft); font-size:.92rem; max-width:70ch;">
    Bu paneldən menyunuzdakı bütün məhsulları idarə edə bilərsiniz: yeni məhsul əlavə etmək,
    mövcud məhsulun adını/tərkibini/qiymətini dəyişmək, foto əlavə edib kəsib düzəltmək,
    məhsulu müvəqqəti sayt üzərindən gizlətmək (silmədən) və kateqoriyaları sıralamaq.
    Etdiyiniz hər dəyişiklik dərhal canlı saytda (sushigarden.az) görünəcək.
  </p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
