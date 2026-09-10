<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

function sg_redirect_back($msg = null, $err = null) {
    if ($msg) $_SESSION['flash_ok'] = $msg;
    if ($err) $_SESSION['flash_err'] = $err;
    header('Location: categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        sg_redirect_back(null, 'Səhifə köhnəlib, yenidən cəhd edin.');
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $nameRu = trim($_POST['name_ru'] ?? '');
        if ($name === '') {
            sg_redirect_back(null, 'Kateqoriya adı boş ola bilməz.');
        }
        $order = sg_next_sort_order('categories');
        $stmt = $pdo->prepare('INSERT INTO categories (name, name_en, name_ru, sort_order, active) VALUES (?, ?, ?, ?, 1)');
        $stmt->execute([$name, $nameEn ?: null, $nameRu ?: null, $order]);
        sg_redirect_back('"' . $name . '" kateqoriyası əlavə olundu.');
    }

    if ($action === 'rename') {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $nameEn = trim($_POST['name_en'] ?? '');
        $nameRu = trim($_POST['name_ru'] ?? '');
        if ($id && $name !== '') {
            $stmt = $pdo->prepare('UPDATE categories SET name = ?, name_en = ?, name_ru = ? WHERE id = ?');
            $stmt->execute([$name, $nameEn ?: null, $nameRu ?: null, $id]);
            sg_redirect_back('Kateqoriya adı yeniləndi.');
        }
        sg_redirect_back(null, 'Ad boş ola bilməz.');
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('UPDATE categories SET active = 1 - active WHERE id = ?');
        $stmt->execute([$id]);
        sg_redirect_back('Görünürlük dəyişdirildi.');
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $countStmt->execute([$id]);
        $count = (int)$countStmt->fetchColumn();
        if ($count > 0) {
            sg_redirect_back(null, 'Bu kateqoriyada ' . $count . ' məhsul var — əvvəlcə məhsulları başqa kateqoriyaya köçürün və ya silin.');
        }
        $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        sg_redirect_back('Kateqoriya silindi.');
    }

    if ($action === 'move') {
        $id = (int)($_POST['id'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        $cats = $pdo->query('SELECT id, sort_order FROM categories ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $idx = null;
        foreach ($cats as $i => $c) { if ((int)$c['id'] === $id) { $idx = $i; break; } }
        if ($idx !== null) {
            $swapWith = $dir === 'up' ? $idx - 1 : $idx + 1;
            if (isset($cats[$swapWith])) {
                $a = $cats[$idx]; $b = $cats[$swapWith];
                $upd = $pdo->prepare('UPDATE categories SET sort_order = ? WHERE id = ?');
                $upd->execute([$b['sort_order'], $a['id']]);
                $upd->execute([$a['sort_order'], $b['id']]);
            }
        }
        sg_redirect_back();
    }
}

$categories = $pdo->query('
    SELECT c.*, (SELECT COUNT(*) FROM products p WHERE p.category_id = c.id) AS product_count
    FROM categories c ORDER BY c.sort_order ASC, c.id ASC
')->fetchAll(PDO::FETCH_ASSOC);

$csrf = sg_csrf_token();
$pageTitle = 'Kateqoriyalar';
$activeNav = 'categories';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>Yeni kateqoriya əlavə et</h2></div>
  <form method="post" style="display:flex; gap:.8rem; flex-wrap:wrap; align-items:flex-end;">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <div class="field" style="flex:1; min-width:180px; margin-bottom:0;">
      <label>Kateqoriya adı (AZ)</label>
      <input type="text" name="name" placeholder="Məs. İçkilər" required>
    </div>
    <div class="field" style="flex:1; min-width:180px; margin-bottom:0;">
      <label>Ad (RU) — istəyə bağlı</label>
      <input type="text" name="name_ru" placeholder="Напр. Напитки">
    </div>
    <div class="field" style="flex:1; min-width:180px; margin-bottom:0;">
      <label>Ad (EN) — istəyə bağlı</label>
      <input type="text" name="name_en" placeholder="e.g. Drinks">
    </div>
    <button type="submit" class="btn btn-primary">+ Əlavə et</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>Bütün kateqoriyalar (<?php echo count($categories); ?>)</h2></div>
  <?php if (!$categories): ?>
    <p class="empty-note">Hələ kateqoriya yoxdur.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th style="width:60px;">Sıra</th>
        <th>Ad</th>
        <th>Məhsul sayı</th>
        <th>Vəziyyət</th>
        <th style="text-align:right;">Əməliyyat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $i => $cat): ?>
        <tr>
          <td>
            <div class="order-arrows">
              <form method="post"><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="up"><input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>"><input type="hidden" name="csrf" value="<?php echo h($csrf); ?>"><button type="submit" <?php echo $i === 0 ? 'disabled' : ''; ?>>▲</button></form>
              <form method="post"><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="down"><input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>"><input type="hidden" name="csrf" value="<?php echo h($csrf); ?>"><button type="submit" <?php echo $i === count($categories) - 1 ? 'disabled' : ''; ?>>▼</button></form>
            </div>
          </td>
          <td>
            <form method="post" style="display:flex; gap:.4rem; flex-wrap:wrap;">
              <input type="hidden" name="action" value="rename">
              <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <input type="text" name="name" value="<?php echo h($cat['name']); ?>" title="AZ" style="max-width:160px;">
              <input type="text" name="name_ru" value="<?php echo h($cat['name_ru'] ?? ''); ?>" title="RU" placeholder="RU" style="max-width:110px;">
              <input type="text" name="name_en" value="<?php echo h($cat['name_en'] ?? ''); ?>" title="EN" placeholder="EN" style="max-width:110px;">
              <button type="submit" class="btn btn-ghost btn-sm">Saxla</button>
            </form>
          </td>
          <td><?php echo (int)$cat['product_count']; ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="status-pill <?php echo $cat['active'] ? 'active' : 'hidden'; ?>">
                <?php echo $cat['active'] ? 'Görünür' : 'Gizli'; ?>
              </button>
            </form>
          </td>
          <td style="text-align:right;">
            <a href="products.php?category=<?php echo (int)$cat['id']; ?>" class="btn btn-ghost btn-sm">Məhsullar</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('Bu kateqoriyanı silmək istədiyinizə əminsiniz?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$cat['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="btn btn-danger btn-sm">Sil</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
