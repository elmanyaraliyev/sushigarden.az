<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_permission('customers');
$pdo = sg_db();

$customers = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM orders o WHERE o.customer_id = c.id) AS order_count,
           (SELECT MAX(o.created_at) FROM orders o WHERE o.customer_id = c.id) AS last_order_at,
           (SELECT SUM(o.total) FROM orders o WHERE o.customer_id = c.id AND o.status != 'cancelled') AS lifetime_total
    FROM customers c
    ORDER BY c.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'İstifadəçilər';
$activeNav = 'customers';
require __DIR__ . '/includes/header.php';
?>

<div class="panel">
  <div class="panel-head"><h2>Qeydiyyatdan keçmiş müştərilər (<?php echo count($customers); ?>)</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem;">
    Yalnız hesab açıb daxil olan müştərilər burada görünür — qonaq kimi (qeydiyyatsız) sifariş verənlər Sifarişlər bölməsində qalır.
  </p>
  <?php if (!$customers): ?>
    <p class="empty-note">Hələ heç bir qeydiyyatlı müştəri yoxdur.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Ad</th>
        <th>Telefon</th>
        <th>Qeydiyyat</th>
        <th>Sifariş sayı</th>
        <th>Son sifariş</th>
        <th>Ümumi məbləğ</th>
        <th style="text-align:right;">Əməliyyat</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c): ?>
        <tr>
          <td><?php echo h($c['name']); ?></td>
          <td><?php echo h($c['phone']); ?></td>
          <td><?php echo h(date('d.m.Y', strtotime($c['created_at']))); ?></td>
          <td><?php echo (int)$c['order_count']; ?></td>
          <td><?php echo $c['last_order_at'] ? h(date('d.m.Y H:i', strtotime($c['last_order_at']))) : '—'; ?></td>
          <td><?php echo $c['lifetime_total'] ? sg_money($c['lifetime_total']) : '—'; ?></td>
          <td style="text-align:right;">
            <a href="customer-view.php?id=<?php echo (int)$c['id']; ?>" class="btn btn-ghost btn-sm">Bax</a>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
