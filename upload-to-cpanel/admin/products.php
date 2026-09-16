<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_permission('products');
$pdo = sg_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: products.php' . (isset($_GET['category']) ? '?category=' . (int)$_GET['category'] : ''));
        exit;
    }
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($action === 'toggle') {
        $stmt = $pdo->prepare('UPDATE products SET active = 1 - active WHERE id = ?');
        $stmt->execute([$id]);
        $_SESSION['flash_ok'] = 'Görünürlük dəyişdirildi.';
    }

    if ($action === 'toggle_featured') {
        $countStmt = $pdo->query('SELECT COUNT(*) FROM products WHERE featured = 1');
        $current = (int)$countStmt->fetchColumn();
        $p = sg_product($id);
        if ($p && !$p['featured'] && $current >= 8) {
            $_SESSION['flash_err'] = 'Ən çoxu 8 məhsulu "Tövsiyə olunanlar"a əlavə edə bilərsiniz — əvvəlcə birini çıxarın.';
        } else {
            $stmt = $pdo->prepare('UPDATE products SET featured = 1 - featured WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['flash_ok'] = 'Tövsiyə olunanlar siyahısı yeniləndi.';
        }
    }

    if ($action === 'delete') {
        $p = sg_product($id);
        if ($p) {
            sg_delete_product_image($p['image']);
            $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
            $stmt->execute([$id]);
            $_SESSION['flash_ok'] = '"' . $p['name'] . '" silindi.';
        }
    }

    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}

// Filtrlər
$catFilter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 40;

$where = [];
$params = [];
if ($catFilter) { $where[] = 'p.category_id = ?'; $params[] = $catFilter; }
if ($search !== '') { $where[] = 'p.name LIKE ?'; $params[] = '%' . $search . '%'; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSql");
$totalStmt->execute($params);
$total = (int)$totalStmt->fetchColumn();
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT p.*, c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    $whereSql
    ORDER BY c.sort_order ASC, p.sort_order ASC, p.id ASC
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categories = sg_all_categories();
$csrf = sg_csrf_token();
$pageTitle = 'Məhsullar';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';

function sg_qs($overrides) {
    $params = array_merge($_GET, $overrides);
    return '?' . http_build_query($params);
}
?>

<div class="toolbar">
  <form method="get" style="display:flex; gap:.8rem; flex-wrap:wrap;">
    <select name="category" onchange="this.form.submit()">
      <option value="0">Bütün kateqoriyalar</option>
      <?php foreach ($categories as $cat): ?>
        <option value="<?php echo (int)$cat['id']; ?>" <?php echo $catFilter === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo h($cat['name']); ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" placeholder="Məhsul axtar..." value="<?php echo h($search); ?>">
    <button type="submit" class="btn btn-ghost">Axtar</button>
  </form>
  <a href="product-form.php<?php echo $catFilter ? '?category=' . $catFilter : ''; ?>" class="btn btn-primary" style="margin-left:auto;">+ Yeni məhsul</a>
</div>

<div class="panel">
  <div class="panel-head"><h2><?php echo $total; ?> məhsul tapıldı</h2></div>
  <?php if (!$products): ?>
    <p class="empty-note">Heç bir məhsul tapılmadı.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th></th>
        <th>Ad</th>
        <th>Kateqoriya</th>
        <th>Qiymət</th>
        <th>Tövsiyə</th>
        <th>Vəziyyət</th>
        <th style="text-align:right;">Əməliyyat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($products as $p): ?>
        <tr>
          <td>
            <?php if (!empty($p['image'])): ?>
              <img class="thumb-sm" src="../uploads/products/<?php echo h($p['image']); ?>" alt="">
            <?php else: ?>
              <div class="thumb-empty">🍣</div>
            <?php endif; ?>
          </td>
          <td><?php echo h($p['name']); ?></td>
          <td><?php echo h($p['category_name']); ?></td>
          <td><?php echo sg_money($p['price']); ?></td>
          <td>
            <form method="post">
              <input type="hidden" name="action" value="toggle_featured">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="btn-sm" style="background:none; border:none; cursor:pointer; font-size:1.1rem; color:<?php echo $p['featured'] ? 'var(--accent)' : 'var(--line)'; ?>;" title="Ön səhifədə Tövsiyə olunanlarda göstər">
                <?php echo $p['featured'] ? '★' : '☆'; ?>
              </button>
            </form>
          </td>
          <td>
            <form method="post">
              <input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="status-pill <?php echo $p['active'] ? 'active' : 'hidden'; ?>">
                <?php echo $p['active'] ? 'Görünür' : 'Gizli'; ?>
              </button>
            </form>
          </td>
          <td style="text-align:right;">
            <a href="product-form.php?id=<?php echo (int)$p['id']; ?>" class="btn btn-ghost btn-sm">Redaktə</a>
            <form method="post" style="display:inline;" onsubmit="return confirm('“<?php echo h(addslashes($p['name'])); ?>” məhsulunu silmək istədiyinizə əminsiniz?');">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
              <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
              <button type="submit" class="btn btn-danger btn-sm">Sil</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <?php if ($totalPages > 1): ?>
  <div class="pagination">
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
      <?php if ($i === $page): ?>
        <span class="current"><?php echo $i; ?></span>
      <?php else: ?>
        <a href="<?php echo h(sg_qs(['page' => $i])); ?>"><?php echo $i; ?></a>
      <?php endif; ?>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
