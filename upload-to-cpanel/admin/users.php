<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();
$pdo = sg_db();
$me = sg_current_admin();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: users.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $perms = array_intersect(array_keys(sg_staff_permissions()), $_POST['permissions'] ?? []);
        if ($username === '') $errors[] = 'İstifadəçi adı boş ola bilməz.';
        if (strlen($password) < 6) $errors[] = 'Şifrə ən azı 6 simvol olmalıdır.';
        if (!$errors) {
            $exists = $pdo->prepare('SELECT COUNT(*) FROM admin_users WHERE username = ?');
            $exists->execute([$username]);
            if ($exists->fetchColumn() > 0) {
                $errors[] = 'Bu istifadəçi adı artıq mövcuddur.';
            } else {
                $stmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash, role, permissions, created_at) VALUES (?, ?, 'staff', ?, datetime('now'))");
                $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), implode(',', $perms)]);
                $_SESSION['flash_ok'] = 'Sifariş meneceri hesabı yaradıldı.';
                header('Location: users.php');
                exit;
            }
        }
    }

    if ($action === 'update_permissions') {
        $id = (int)($_POST['id'] ?? 0);
        $perms = array_intersect(array_keys(sg_staff_permissions()), $_POST['permissions'] ?? []);
        $stmt = $pdo->prepare("UPDATE admin_users SET permissions = ? WHERE id = ? AND role = 'staff'");
        $stmt->execute([implode(',', $perms), $id]);
        $_SESSION['flash_ok'] = 'Səlahiyyətlər yeniləndi.';
        header('Location: users.php');
        exit;
    }

    if ($action === 'reset_password') {
        $id = (int)($_POST['id'] ?? 0);
        $password = $_POST['password'] ?? '';
        if (strlen($password) < 6) {
            $_SESSION['flash_err'] = 'Şifrə ən azı 6 simvol olmalıdır.';
        } else {
            $stmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE id = ? AND role = 'staff'");
            $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
            $_SESSION['flash_ok'] = 'Şifrə yeniləndi.';
        }
        header('Location: users.php');
        exit;
    }

    if ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        // Yalnız "staff" hesablar silinə bilər — sahibkar (admin) hesabı bu
        // səhifədən heç vaxt silinmir, təsadüfən özünü kilidləməsin.
        $stmt = $pdo->prepare("DELETE FROM admin_users WHERE id = ? AND role = 'staff'");
        $stmt->execute([$id]);
        $_SESSION['flash_ok'] = 'Hesab silindi.';
        header('Location: users.php');
        exit;
    }
}

$staffUsers = $pdo->query("SELECT * FROM admin_users WHERE role = 'staff' ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$csrf = sg_csrf_token();
$pageTitle = 'İdarəçilər';
$activeNav = 'users';
require __DIR__ . '/includes/header.php';
?>
<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<div class="panel" style="max-width:520px;">
  <div class="panel-head"><h2>Yeni sifariş meneceri</h2></div>
  <p style="color:var(--text-soft); font-size:.86rem; margin-top:-.6rem;">
    Bu hesabla giriş edən şəxs həmişə "Sifarişlər" bölməsinə çıxışlıdır — sifarişləri qəbul edə,
    ləğv edə və hazır olacağı vaxtı təyin edə bilər. Aşağıda seçdiyiniz əlavə səlahiyyətlər olmasa,
    başqa heç bir bölməyə girişi olmaz — istənilən vaxt "Səlahiyyətlər" sütunundan dəyişə bilərsiniz.
  </p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="add">
    <div class="field">
      <label>İstifadəçi adı</label>
      <input type="text" name="username" required>
    </div>
    <div class="field">
      <label>Şifrə (ən azı 6 simvol)</label>
      <input type="password" name="password" required>
    </div>
    <div class="field">
      <label>Əlavə səlahiyyətlər (istəyə bağlı)</label>
      <?php foreach (sg_staff_permissions() as $key => $label): ?>
        <label class="checkbox-row" style="display:block; margin-bottom:.3rem;">
          <input type="checkbox" name="permissions[]" value="<?php echo h($key); ?>"> <?php echo h($label); ?>
        </label>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary">Hesab yarat</button>
  </form>
</div>

<div class="panel">
  <div class="panel-head"><h2>Sifariş menecerləri (<?php echo count($staffUsers); ?>)</h2></div>
  <?php if (!$staffUsers): ?>
    <p class="empty-note">Hələ heç bir sifariş meneceri hesabı yaradılmayıb.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>İstifadəçi adı</th>
          <th>Yaradılıb</th>
          <th>Əlavə səlahiyyətlər</th>
          <th style="text-align:right;">Əməliyyat</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($staffUsers as $u): $userPerms = array_filter(explode(',', $u['permissions'] ?? '')); ?>
          <tr>
            <td><?php echo h($u['username']); ?></td>
            <td><?php echo h($u['created_at']); ?></td>
            <td>
              <form method="post" style="display:flex; flex-direction:column; gap:.25rem;">
                <input type="hidden" name="action" value="update_permissions">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <?php foreach (sg_staff_permissions() as $key => $label): ?>
                  <label class="checkbox-row" style="display:flex; align-items:center; gap:.35rem; font-size:.82rem;">
                    <input type="checkbox" name="permissions[]" value="<?php echo h($key); ?>" <?php echo in_array($key, $userPerms, true) ? 'checked' : ''; ?>> <?php echo h($label); ?>
                  </label>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-ghost btn-sm" style="align-self:flex-start; margin-top:.2rem;">Yadda saxla</button>
              </form>
            </td>
            <td style="text-align:right;">
              <form method="post" style="display:inline-flex; gap:.4rem; align-items:center; justify-content:flex-end; flex-wrap:wrap;">
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
                <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
                <input type="password" name="password" placeholder="Yeni şifrə" style="width:140px;" minlength="6">
                <button type="submit" class="btn btn-ghost btn-sm">Şifrəni dəyiş</button>
              </form>
              <form method="post" style="display:inline;" onsubmit="return confirm('Bu hesabı silmək istədiyinizə əminsiniz?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
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
