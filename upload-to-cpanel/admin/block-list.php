<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: block-list.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $phone = sg_normalize_az_phone(trim($_POST['phone'] ?? ''));
        $reason = trim($_POST['reason'] ?? '');
        if (!$phone) {
            $errors[] = 'Düzgün telefon nömrəsi daxil edin.';
        } else {
            $stmt = $pdo->prepare('INSERT OR IGNORE INTO blocked_customers (phone, reason) VALUES (?, ?)');
            $stmt->execute([$phone, $reason]);
            $_SESSION['flash_ok'] = 'Nömrə bloklandı.';
            header('Location: block-list.php');
            exit;
        }
    }

    if ($action === 'remove') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare('DELETE FROM blocked_customers WHERE id = ?')->execute([$id]);
        $_SESSION['flash_ok'] = 'Blok götürüldü.';
        header('Location: block-list.php');
        exit;
    }
}

$blocked = $pdo->query('SELECT * FROM blocked_customers ORDER BY id DESC')->fetchAll(PDO::FETCH_ASSOC);
$csrf = sg_csrf_token();
$pageTitle = 'Bloklananlar';
$activeNav = 'block-list';
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<div class="panel" style="max-width:520px;">
  <div class="panel-head"><h2>Nömrə blokla</h2></div>
  <p style="color:var(--text-soft); font-size:.86rem; margin-top:-.6rem;">
    Bloklanan telefon nömrəsi ilə saytdan yeni sifariş yerləşdirmək mümkün olmayacaq.
  </p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="add">
    <div class="field">
      <label>Telefon nömrəsi</label>
      <input type="text" name="phone" placeholder="050 123 45 67" required>
    </div>
    <div class="field">
      <label>Səbəb (istəyə bağlı)</label>
      <input type="text" name="reason" placeholder="Məs. yalançı sifariş, kobud davranış">
    </div>
    <button type="submit" class="btn btn-primary">Blokla</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>Bloklanmış nömrələr (<?php echo count($blocked); ?>)</h2></div>
  <?php if (!$blocked): ?>
    <p class="empty-note">Hələ bloklanmış nömrə yoxdur.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Telefon</th>
          <th>Səbəb</th>
          <th>Tarix</th>
          <th style="text-align:right;">Əməliyyat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($blocked as $b): ?>
          <tr>
            <td><?php echo h($b['phone']); ?></td>
            <td><?php echo h($b['reason'] ?: '—'); ?></td>
            <td><?php echo h($b['created_at']); ?></td>
            <td style="text-align:right;">
              <form method="post" style="display:inline;" onsubmit="return confirm('Bu nömrənin blokunu götürmək istədiyinizə əminsiniz?');">
                <input type="hidden" name="action" value="remove">
                <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <button type="submit" class="btn btn-ghost btn-sm">Blokdan çıxar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
