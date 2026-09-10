<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

$id = (int)($_GET['id'] ?? 0);
$order = sg_get_order($id);
if (!$order) {
    $_SESSION['flash_err'] = 'Sifariş tapılmadı.';
    header('Location: orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: order-view.php?id=' . $id);
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'update_status') {
        sg_update_order_status($id, $_POST['status'] ?? '');
        $_SESSION['flash_ok'] = 'Status yeniləndi.';
        header('Location: order-view.php?id=' . $id);
        exit;
    }

    if ($action === 'delete') {
        sg_delete_order($id);
        $_SESSION['flash_ok'] = 'Sifariş silindi.';
        header('Location: orders.php');
        exit;
    }
}

$csrf = sg_csrf_token();
$pageTitle = 'Sifariş #' . $id;
$activeNav = 'orders';
require __DIR__ . '/includes/header.php';
?>

<div class="order-view-grid">
  <div class="panel">
    <div class="panel-head"><h2>Sifariş #<?php echo (int)$order['id']; ?></h2></div>
    <ul class="detail-list">
      <li><span class="k">Tarix</span><span class="v"><?php echo h(date('d.m.Y', strtotime($order['created_at']))); ?></span></li>
      <li><span class="k">Saat</span><span class="v"><?php echo h(date('H:i', strtotime($order['created_at']))); ?></span></li>
      <li><span class="k">Status</span><span class="v"><span class="status-pill status-<?php echo h($order['status']); ?>"><?php echo h(sg_order_status_label($order['status'])); ?></span></span></li>
    </ul>
    <div class="panel-head" style="margin-top:1.4rem;"><h2>Müştəri</h2></div>
    <ul class="detail-list">
      <li><span class="k">Ad</span><span class="v"><?php echo h($order['customer_name']); ?></span></li>
      <li><span class="k">Telefon</span><span class="v"><?php echo h($order['customer_phone']); ?></span></li>
      <li><span class="k">Xidmət</span><span class="v"><?php echo h(sg_service_type_label($order['service_type'])); ?></span></li>
      <?php if ($order['service_type'] === 'delivery'): ?>
        <li><span class="k">Ünvan</span><span class="v"><?php echo h($order['address']); ?></span></li>
      <?php endif; ?>
    </ul>
  </div>

  <div class="panel">
    <div class="panel-head"><h2>Məhsullar</h2></div>
    <table>
      <thead>
        <tr><th>Məhsul</th><th>Qiymət</th><th>Say</th><th style="text-align:right;">Cəm</th></tr>
      </thead>
      <tbody>
        <?php foreach ($order['items'] as $it): ?>
          <tr>
            <td><?php echo h($it['name']); ?></td>
            <td><?php echo sg_money($it['price']); ?></td>
            <td><?php echo (int)$it['qty']; ?></td>
            <td style="text-align:right;"><?php echo sg_money($it['line_total']); ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div class="order-summary">
      <div class="row"><span>Aralıq yekun</span><span><?php echo sg_money($order['subtotal']); ?></span></div>
      <div class="row"><span>Bəxşiş</span><span><?php echo sg_money($order['tip']); ?></span></div>
      <div class="row total"><span>Ümumi</span><span><?php echo sg_money($order['total']); ?></span></div>
    </div>
  </div>
</div>

<div class="panel order-actions-bar">
  <form method="post" style="display:flex; gap:.8rem; align-items:center; flex-wrap:wrap;">
    <input type="hidden" name="action" value="update_status">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <select name="status">
      <?php foreach (sg_order_statuses() as $st): ?>
        <option value="<?php echo h($st); ?>" <?php echo $order['status'] === $st ? 'selected' : ''; ?>><?php echo h(sg_order_status_label($st)); ?></option>
      <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-primary">Statusu dəyiş</button>
  </form>
  <form method="post" onsubmit="return confirm('Bu sifarişi silmək istədiyinizə əminsiniz?');">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <button type="submit" class="btn btn-danger">Sifarişi sil</button>
  </form>
  <a href="orders.php" class="btn btn-ghost">Sifarişlərə qayıt</a>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
