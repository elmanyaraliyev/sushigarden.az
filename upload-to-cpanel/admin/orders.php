<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: orders.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        $id = (int)($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? '';
        if (sg_update_order_status($id, $status)) {
            $_SESSION['flash_ok'] = 'Sifariş #' . $id . ' statusu yeniləndi.';
        } else {
            $_SESSION['flash_err'] = 'Status yenilənmədi.';
        }
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        sg_delete_order($id);
        $_SESSION['flash_ok'] = 'Sifariş silindi.';
    }

    if ($action === 'bulk_delete') {
        $ids = array_map('intval', $_POST['ids'] ?? []);
        foreach ($ids as $id) {
            if ($id > 0) sg_delete_order($id);
        }
        $_SESSION['flash_ok'] = count($ids) . ' sifariş silindi.';
    }

    if ($action === 'reset_sequence') {
        if (sg_reset_order_sequence()) {
            $_SESSION['flash_ok'] = 'Sifariş nömrələməsi sıfırlandı — növbəti sifariş #1 olacaq.';
        } else {
            $_SESSION['flash_err'] = 'Nömrələmə sıfırlana bilmədi — hələ silinməmiş sifarişlər var.';
        }
    }

    if ($action === 'wipe_all') {
        // Geri qaytarılmaz əməliyyat — YALNIZ sahibkar (admin rolu) çağıra bilər,
        // sifariş meneceri (staff) heç vaxt bütün tarixçəni silə bilməz.
        if (!sg_is_owner_admin()) {
            $_SESSION['flash_err'] = 'Bu əməliyyat üçün icazəniz yoxdur.';
        } elseif (trim($_POST['confirm_text'] ?? '') !== 'SİL') {
            $_SESSION['flash_err'] = 'Təsdiq mətni yanlışdır — heç nə silinmədi.';
        } else {
            sg_wipe_all_orders();
            $_SESSION['flash_ok'] = 'Bütün sifariş tarixçəsi silindi. Növbəti sifariş #1 olacaq.';
        }
    }

    header('Location: orders.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

$statusFilter = $_GET['status'] ?? '';
$allOrders = sg_get_orders();
$total = count($allOrders);
$pendingCount = count(array_filter($allOrders, function ($o) { return $o['status'] === 'pending'; }));
$readyCount = count(array_filter($allOrders, function ($o) { return $o['status'] === 'ready'; }));

$orders = $statusFilter !== '' ? array_values(array_filter($allOrders, function ($o) use ($statusFilter) { return $o['status'] === $statusFilter; })) : $allOrders;

$csrf = sg_csrf_token();
$pageTitle = 'Sifarişlər';
$activeNav = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="cards">
  <div class="card"><div class="num"><?php echo $total; ?></div><div class="label">Cəmi sifariş</div></div>
  <div class="card"><div class="num"><?php echo $pendingCount; ?></div><div class="label">Gözləyən</div></div>
  <div class="card"><div class="num"><?php echo $readyCount; ?></div><div class="label">Hazır</div></div>
</div>

<div class="toolbar">
  <a href="orders.php" class="btn <?php echo $statusFilter === '' ? 'btn-primary' : 'btn-ghost'; ?> btn-sm">Hamısı</a>
  <a href="orders.php?status=pending" class="btn <?php echo $statusFilter === 'pending' ? 'btn-primary' : 'btn-ghost'; ?> btn-sm">Gözləyən (<?php echo $pendingCount; ?>)</a>
  <a href="orders.php?status=ready" class="btn <?php echo $statusFilter === 'ready' ? 'btn-primary' : 'btn-ghost'; ?> btn-sm">Hazır (<?php echo $readyCount; ?>)</a>
</div>

<div class="panel">
  <div class="panel-head"><h2><?php echo count($orders); ?> sifariş</h2></div>
  <?php if (!$orders): ?>
    <p class="empty-note">Hələ sifariş yoxdur.</p>
    <?php if ($total === 0): ?>
      <form method="post" onsubmit="return confirm('Sifariş nömrələnməsini sıfırlamaq istədiyinizə əminsiniz? Növbəti sifariş #1 olacaq.');">
        <input type="hidden" name="action" value="reset_sequence">
        <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
        <button type="submit" class="btn btn-ghost btn-sm">Sifariş nömrələnməsini sıfırla (#1-dən başlasın)</button>
      </form>
    <?php endif; ?>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th style="width:32px;"><input type="checkbox" id="check-all"></th>
          <th>Sifariş</th>
          <th>Müştəri</th>
          <th>Telefon</th>
          <th>Növ</th>
          <th>Məbləğ</th>
          <th>Bəxşiş</th>
          <th>Status</th>
          <th>Tarix</th>
          <th style="text-align:right;">Əməliyyat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($orders as $o): ?>
          <tr>
            <td><input type="checkbox" class="row-check" value="<?php echo (int)$o['id']; ?>"></td>
            <td>#<?php echo (int)$o['id']; ?></td>
            <td><?php echo h($o['customer_name']); ?></td>
            <td><?php echo h($o['customer_phone']); ?></td>
            <td><?php echo h(sg_service_type_label($o['service_type'])); ?></td>
            <td><?php echo sg_money($o['total']); ?></td>
            <td><?php echo sg_money($o['tip']); ?></td>
            <td>
              <form method="post" class="inline-status">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <select name="status" class="status-select status-<?php echo h($o['status']); ?>" onchange="this.form.submit()">
                  <?php foreach (sg_order_statuses() as $st): ?>
                    <option value="<?php echo h($st); ?>" <?php echo $o['status'] === $st ? 'selected' : ''; ?>><?php echo h(sg_order_status_label($st)); ?></option>
                  <?php endforeach; ?>
                </select>
              </form>
            </td>
            <td><?php echo h(date('d.m.Y H:i', strtotime($o['created_at']))); ?></td>
            <td style="text-align:right; white-space:nowrap;">
              <a href="order-view.php?id=<?php echo (int)$o['id']; ?>" class="btn btn-ghost btn-sm">Bax</a>
              <form method="post" style="display:inline;" onsubmit="return confirm('Sifarişi silmək istədiyinizə əminsiniz?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$o['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <button type="submit" class="btn btn-danger btn-sm">Sil</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div style="margin-top:1.2rem;">
      <button type="button" id="bulk-delete-btn" class="btn btn-danger btn-sm">Seçilənləri sil</button>
    </div>
    <form method="post" id="bulk-form" style="display:none;">
      <input type="hidden" name="action" value="bulk_delete">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    </form>
  <?php endif; ?>
</div>

<?php if (sg_is_owner_admin() && $total > 0): ?>
<div class="panel" style="max-width:520px;">
  <details style="border:1px solid var(--danger); border-radius:8px; padding:.8rem 1rem;">
    <summary style="cursor:pointer; color:var(--danger); font-weight:700;">⚠ Bütün sifariş tarixçəsini sil (geri qaytarıla bilməz)</summary>
    <p style="color:var(--text-soft); font-size:.86rem; margin:.7rem 0;">
      Bu, mövcud <?php echo $total; ?> sifarişin HAMISINI (və onlara bağlı rəyləri) həmişəlik siləcək və
      nömrələməni sıfırlayacaq ki, növbəti sifariş #1 olsun. Yalnız saytı ilk dəfə istifadəyə verməzdən əvvəl,
      test sifarişlərini təmizləmək üçün istifadə edin.
    </p>
    <form method="post" onsubmit="return confirm('SON XƏBƏRDARLIQ: ' + <?php echo json_encode((string)$total); ?> + ' sifariş həmişəlik silinəcək. Davam edilsin?');">
      <input type="hidden" name="action" value="wipe_all">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <div class="field" style="max-width:260px;">
        <label>Təsdiq üçün "SİL" yazın</label>
        <input type="text" name="confirm_text" autocomplete="off" required>
      </div>
      <button type="submit" class="btn btn-danger btn-sm">Bütün sifarişləri həmişəlik sil</button>
    </form>
  </details>
</div>
<?php endif; ?>

<script>
  var checkAll = document.getElementById('check-all');
  if (checkAll) {
    checkAll.addEventListener('change', function () {
      document.querySelectorAll('.row-check').forEach(function (cb) { cb.checked = checkAll.checked; });
    });
  }
  var bulkBtn = document.getElementById('bulk-delete-btn');
  if (bulkBtn) {
    bulkBtn.addEventListener('click', function () {
      var checked = document.querySelectorAll('.row-check:checked');
      if (!checked.length) { alert('Heç bir sifariş seçilməyib.'); return; }
      if (!confirm('Seçilmiş ' + checked.length + ' sifarişi silmək istədiyinizə əminsiniz?')) return;
      var form = document.getElementById('bulk-form');
      checked.forEach(function (cb) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        form.appendChild(input);
      });
      form.submit();
    });
  }
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
