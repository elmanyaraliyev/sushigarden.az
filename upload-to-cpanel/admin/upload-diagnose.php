<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

function sg_diag_row($label, $ok, $detail = '') {
    $icon = $ok === null ? '⚪' : ($ok ? '✅' : '❌');
    echo '<tr><td>' . $icon . ' ' . h($label) . '</td><td style="color:var(--text-soft); font-size:.85rem;">' . h($detail) . '</td></tr>';
}

$pageTitle = 'Foto Yükləmə Diaqnostikası';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<div class="panel" style="max-width:760px;">
  <div class="panel-head"><h2>Server yoxlaması</h2></div>
  <table>
    <?php
    // 1) GD
    $hasGd = extension_loaded('gd');
    sg_diag_row('GD şəkil kitabxanası', $hasGd, $hasGd ? (function_exists('gd_info') ? (gd_info()['GD Version'] ?? '') : '') : 'PHP-də GD aktiv deyil');

    // 2) PHP limitləri
    sg_diag_row('upload_max_filesize', null, ini_get('upload_max_filesize'));
    sg_diag_row('post_max_size', null, ini_get('post_max_size'));
    sg_diag_row('memory_limit', null, ini_get('memory_limit'));

    // 3) uploads/products qovluğu
    $dir = SG_UPLOADS_DIR;
    $dirExisted = is_dir($dir);
    $ensured = sg_ensure_writable_dir($dir);
    $perms = is_dir($dir) ? substr(sprintf('%o', fileperms($dir)), -4) : '-';
    sg_diag_row('Qovluq mövcuddur (' . $dir . ')', is_dir($dir), $dirExisted ? 'əvvəldən var idi' : 'indi yaradıldı');
    sg_diag_row('Qovluq yazılabilirdir (icazə: ' . $perms . ')', $ensured);

    // 4) Əsl test yazısı
    $testFile = null;
    $writeOk = false;
    $writeError = '';
    if ($ensured) {
        $testFile = rtrim($dir, '/') . '/diag_test_' . time() . '.txt';
        error_clear_last();
        $writeOk = @file_put_contents($testFile, 'test') !== false;
        if (!$writeOk) {
            $err = error_get_last();
            $writeError = $err['message'] ?? 'naməlum xəta';
        } else {
            @unlink($testFile);
        }
    }
    sg_diag_row('Test faylı diskə yazıla bildi', $writeOk, $writeError);

    // 5) GD ilə test şəkli yaratma + yazma
    $gdWriteOk = null;
    $gdError = '';
    if ($hasGd && $ensured) {
        $img = imagecreatetruecolor(10, 10);
        $testImgFile = rtrim($dir, '/') . '/diag_test_' . time() . '.jpg';
        error_clear_last();
        $gdWriteOk = @imagejpeg($img, $testImgFile, 80);
        imagedestroy($img);
        if ($gdWriteOk) {
            @unlink($testImgFile);
        } else {
            $err = error_get_last();
            $gdError = $err['message'] ?? 'naməlum xəta';
        }
    }
    sg_diag_row('GD ilə test şəkli yaradıla bildi', $gdWriteOk, $gdError);
    ?>
  </table>

  <?php if ($writeOk): ?>
    <div class="flash ok" style="margin-top:1.2rem;">Hər şey qaydasındadır — foto yükləmə işləməlidir. Yenə xəta çıxırsa, mənə bu səhifənin skrinşotunu göndərin.</div>
  <?php else: ?>
    <div class="flash err" style="margin-top:1.2rem;">
      Qovluq yazıla bilmir. cPanel → File Manager-də <code><?php echo h(str_replace(SG_ROOT, '', $dir)); ?></code> qovluğunun üstünə sağ klikləyib
      "Permissions" seçin, dəyəri <strong>755</strong> edin (olmasa <strong>775</strong> sınayın). Kömək etməsə, bu səhifənin skrinşotunu mənə göndərin.
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
