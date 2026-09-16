<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_permission('reviews');

$reviews = sg_get_reviews();
$avg = $reviews ? round(array_sum(array_column($reviews, 'rating')) / count($reviews), 1) : 0;

$pageTitle = 'Rəylər';
$activeNav = 'reviews';
require __DIR__ . '/includes/header.php';

function sg_stars_html($rating) {
    $out = '';
    for ($i = 1; $i <= 5; $i++) $out .= $i <= $rating ? '★' : '☆';
    return $out;
}
?>

<div class="panel">
  <div class="panel-head"><h2>Müştəri rəyləri (<?php echo count($reviews); ?>)</h2></div>
  <?php if ($reviews): ?>
    <p style="font-size:1.1rem; margin-top:-.6rem; margin-bottom:1rem;">
      Orta xal: <strong style="color:var(--accent);"><?php echo $avg; ?> / 5</strong>
      <span style="color:var(--gold); letter-spacing:.1em;"><?php echo sg_stars_html(round($avg)); ?></span>
    </p>
  <?php endif; ?>
  <?php if (!$reviews): ?>
    <p class="empty-note">Hələ heç bir rəy yoxdur.</p>
  <?php else: ?>
  <table>
    <thead>
      <tr>
        <th>Sifariş</th>
        <th>Müştəri</th>
        <th>Xal</th>
        <th>Qeyd</th>
        <th>Tarix</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($reviews as $r): ?>
        <tr>
          <td><a href="order-view.php?id=<?php echo (int)$r['order_id']; ?>">#<?php echo (int)$r['order_id']; ?></a></td>
          <td><?php echo h($r['customer_name']); ?><br><span style="color:var(--text-soft); font-size:.82rem;"><?php echo h($r['customer_phone']); ?></span></td>
          <td style="color:var(--gold); letter-spacing:.08em; white-space:nowrap;"><?php echo sg_stars_html($r['rating']); ?></td>
          <td><?php echo $r['comment'] ? h($r['comment']) : '<span style="color:var(--text-soft);">—</span>'; ?></td>
          <td><?php echo h(date('d.m.Y H:i', strtotime($r['created_at']))); ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
