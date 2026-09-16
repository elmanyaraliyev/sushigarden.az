<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM customers WHERE id = ?');
$stmt->execute([$id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$customer) {
    $_SESSION['flash_err'] = 'İstifadəçi tapılmadı.';
    header('Location: customers.php');
    exit;
}

$orders = $pdo->prepare('SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC');
$orders->execute([$id]);
$orders = $orders->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'İstifadəçi: ' . $customer['name'];
$activeNav = 'customer-view';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:640px;">
  <div class="panel-head"><h2><?php echo h($customer['name']); ?></h2></div>
  <div class="field"><label>Telefon</label><div><?php echo h($customer['phone']); ?></div></div>
  <?php if (!empty($customer['email'])): ?><div class="field"><label>E-poçt</label><div><?php echo h($customer['email']); ?></div></div><?php endif; ?>
  <div class="field"><label>Qeydiyyat tarixi</label><div><?php echo h(date('d.m.Y H:i', strtotime($customer['created_at']))); ?></div></div>
  <a href="customers.php" class="btn btn-ghost btn-sm">← İstifadəçilərə qayıt</a>
</div>

<div class="panel">
  <div class="panel-head"><h2>Sifariş tarixçəsi (<?php echo count($orders); ?>)</h2></div>
  <?php if (!$orders): ?>
    <p class="empty-note">Bu istifadəçi hələ sifariş verməyib.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>№</th>
        <th>Tarix</th>
        <th>Xidmət</th>
        <th>Status</th>
        <th>Məbləğ</th>
        <th style="text-align:right;">Əməliyyat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
        <tr>
          <td>#<?php echo (int)$o['id']; ?></td>
          <td><?php echo h(date('d.m.Y H:i', strtotime($o['created_at']))); ?></td>
          <td><?php echo h(sg_service_type_label($o['service_type'])); ?></td>
          <td><span class="status-pill status-<?php echo h($o['status']); ?>"><?php echo h(sg_order_status_label($o['status'])); ?></span></td>
          <td><?php echo sg_money($o['total']); ?></td>
          <td style="text-align:right;"><a href="order-view.php?id=<?php echo (int)$o['id']; ?>" class="btn btn-ghost btn-sm">Bax</a></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
