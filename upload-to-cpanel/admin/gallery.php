<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: gallery.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_section') {
        $enabled = sg_setting('gallery_enabled', '1') === '1';
        sg_set_setting('gallery_enabled', $enabled ? '0' : '1');
        header('Location: gallery.php');
        exit;
    }

    if ($action === 'add') {
        if (empty($_FILES['image']['tmp_name'])) {
            $errors[] = 'Şəkil seçilmədi.';
        } else {
            $path = sg_save_gallery_upload($_FILES['image']);
            if ($path) {
                $order = (int)$pdo->query('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM gallery_items')->fetchColumn();
                $stmt = $pdo->prepare('INSERT INTO gallery_items (image, caption_az, caption_ru, caption_en, sort_order, active) VALUES (?, ?, ?, ?, ?, 1)');
                $stmt->execute([$path, trim($_POST['caption_az'] ?? ''), trim($_POST['caption_ru'] ?? ''), trim($_POST['caption_en'] ?? ''), $order]);
                $_SESSION['flash_ok'] = 'Şəkil əlavə olundu.';
                header('Location: gallery.php');
                exit;
            }
            $errors[] = 'Şəkil yüklənmədi. JPG/PNG formatında sınayın.';
        }
    }

    if ($action === 'toggle') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('UPDATE gallery_items SET active = 1 - active WHERE id = ?')->execute([$id]);
        header('Location: gallery.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        sg_delete_gallery_item($id);
        $_SESSION['flash_ok'] = 'Şəkil silindi.';
        header('Location: gallery.php');
        exit;
    }

    if ($action === 'move') {
        $id = (int)($_POST['id'] ?? 0);
        $dir = $_POST['dir'] ?? '';
        $items = $pdo->query('SELECT id, sort_order FROM gallery_items ORDER BY sort_order ASC, id ASC')->fetchAll(PDO::FETCH_ASSOC);
        $idx = null;
        foreach ($items as $i => $it) { if ((int)$it['id'] === $id) { $idx = $i; break; } }
        if ($idx !== null) {
            $swapWith = $dir === 'up' ? $idx - 1 : $idx + 1;
            if (isset($items[$swapWith])) {
                $a = $items[$idx]; $b = $items[$swapWith];
                $upd = $pdo->prepare('UPDATE gallery_items SET sort_order = ? WHERE id = ?');
                $upd->execute([$b['sort_order'], $a['id']]);
                $upd->execute([$a['sort_order'], $b['id']]);
            }
        }
        header('Location: gallery.php');
        exit;
    }
}

$items = sg_get_gallery_items();
$csrf = sg_csrf_token();
$galleryEnabled = sg_setting('gallery_enabled', '1') === '1';
$pageTitle = 'Qalereya';
$activeNav = 'gallery';
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<div class="panel" style="max-width:560px;">
  <div class="panel-head"><h2>Qalereya bölməsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem;">
    Deaktiv etsəniz, "Qalereya" keçidi saytın menyusundan tamamilə çıxarılır (həm masaüstü, həm mobil).
  </p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="toggle_section">
    <button type="submit" class="status-pill <?php echo $galleryEnabled ? 'active' : 'hidden'; ?>">
      <?php echo $galleryEnabled ? 'Aktivdir (saytda görünür)' : 'Deaktivdir (saytda gizlidir)'; ?>
    </button>
  </form>
</div>

<div class="panel" style="max-width:560px;">
  <div class="panel-head"><h2>Yeni şəkil əlavə et</h2></div>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="add">
    <div class="field">
      <label>Şəkil (JPG/PNG)</label>
      <input type="file" name="image" accept="image/*" required>
    </div>
    <div class="field">
      <label>Alt yazı (AZ) — istəyə bağlı</label>
      <input type="text" name="caption_az" placeholder="Məs. Yarpaq üzərində Sushi">
    </div>
    <div class="field">
      <label>Alt yazı (RU) — istəyə bağlı</label>
      <input type="text" name="caption_ru">
    </div>
    <div class="field">
      <label>Alt yazı (EN) — istəyə bağlı</label>
      <input type="text" name="caption_en">
    </div>
    <button type="submit" class="btn btn-primary">Əlavə et</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>Qalereya şəkilləri (<?php echo count($items); ?>)</h2></div>
  <p style="color:var(--text-soft); font-size:.86rem; margin-top:-.6rem;">Saytda 9-a qədər şəkil göstərilir (ilk 9 aktiv şəkil). Boş olarsa nümunə naxışlar görünür.</p>
  <?php if (!$items): ?>
    <p class="empty-note">Hələ şəkil yoxdur — saytda nümunə naxışlar göstərilir.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:60px;">Sıra</th>
          <th style="width:90px;">Şəkil</th>
          <th>Alt yazı (AZ)</th>
          <th>Vəziyyət</th>
          <th style="text-align:right;">Əməliyyat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $i => $it): ?>
          <tr>
            <td>
              <div class="order-arrows">
                <form method="post"><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="up"><input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>"><input type="hidden" name="csrf" value="<?php echo h($csrf); ?>"><button type="submit" <?php echo $i === 0 ? 'disabled' : ''; ?>>▲</button></form>
                <form method="post"><input type="hidden" name="action" value="move"><input type="hidden" name="dir" value="down"><input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>"><input type="hidden" name="csrf" value="<?php echo h($csrf); ?>"><button type="submit" <?php echo $i === count($items) - 1 ? 'disabled' : ''; ?>>▼</button></form>
              </div>
            </td>
            <td><img src="../<?php echo h($it['image']); ?>" style="width:70px; height:70px; object-fit:cover; border-radius:6px;"></td>
            <td><?php echo h($it['caption_az'] ?: '—'); ?></td>
            <td>
              <form method="post">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <button type="submit" class="status-pill <?php echo $it['active'] ? 'active' : 'hidden'; ?>"><?php echo $it['active'] ? 'Görünür' : 'Gizli'; ?></button>
              </form>
            </td>
            <td style="text-align:right;">
              <form method="post" style="display:inline;" onsubmit="return confirm('Şəkli silmək istədiyinizə əminsiniz?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$it['id']; ?>">
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
