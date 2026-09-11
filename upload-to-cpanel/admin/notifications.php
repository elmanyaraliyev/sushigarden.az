<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'reset_completed_sound') {
            sg_set_setting('customer_completed_sound', '');
            $_SESSION['flash_ok'] = 'Standart səsə qaytarıldı.';
            header('Location: notifications.php');
            exit;
        }
        if ($action === 'upload_completed_sound') {
            if (empty($_FILES['completed_sound']['tmp_name'])) {
                $errors[] = 'Fayl seçilmədi.';
            } else {
                $path = sg_save_sound_upload($_FILES['completed_sound']);
                if ($path) {
                    sg_set_setting('customer_completed_sound', $path);
                    $_SESSION['flash_ok'] = 'Müştəri "sifariş tamamlandı" səsi yeniləndi.';
                    header('Location: notifications.php');
                    exit;
                }
                $errors[] = 'Fayl yüklənmədi — MP3/WAV/OGG formatında və maksimum 2 MB olmalıdır.';
            }
        }
    }
}

$csrf = sg_csrf_token();
$pageTitle = 'Bildirişlər';
$activeNav = 'notifications';
require __DIR__ . '/includes/header.php';

$completedSoundCustom = sg_setting('customer_completed_sound', '');
?>
<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<div class="panel" style="max-width:560px;">
  <div class="panel-head"><h2>Admin — yeni sifariş bildiriş səsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Yeni sifariş daxil olanda admin paneldə (hansı səhifədə olmağınızdan asılı olmayaraq) bu səs çalınacaq.
    Seçim bu brauzerdə yadda saxlanılır.
  </p>
  <div class="sound-options" id="sound-options">
    <label><input type="radio" name="sg_sound" value="chime"> Zəng (üçlü)</label>
    <label><input type="radio" name="sg_sound" value="beep1"> Bip (tək)</label>
    <label><input type="radio" name="sg_sound" value="beep2"> Bip (ikili)</label>
    <label><input type="radio" name="sg_sound" value="bundled"> Restoran zəngi</label>
    <label><input type="radio" name="sg_sound" value="custom"> Öz səsim</label>
    <label><input type="radio" name="sg_sound" value="none"> Səssiz</label>
  </div>
  <div id="custom-sound-row" style="margin-top:.8rem; display:none;">
    <input type="file" id="custom-sound-file" accept="audio/*">
    <div style="margin-top:.4rem; font-size:.76rem; opacity:.6; max-width:360px; line-height:1.4;">
      MP3/WAV/OGG fayl seçin (tövsiyə: 2-3 saniyəlik qısa səs, maks. 300 KB — brauzerin yaddaşında saxlanılır).
    </div>
    <div id="custom-sound-current" style="margin-top:.4rem; font-size:.8rem; color:var(--text-soft);"></div>
  </div>
  <button type="button" class="btn btn-ghost btn-sm" id="sound-test" style="margin-top:1rem;">🔊 Sına</button>
  <p style="color:var(--text-soft); font-size:.82rem; margin-top:1.2rem;">
    Diqqət: başqa bir sekmədə/proqramda olarkən yeni sifariş gələndə brauzerinizdən icazə istənilə bilər
    ("Bildiriş göndərməyə icazə verin?") — mütləq "İcazə ver" seçin ki, fon rejimində də bildiriş görünsün.
  </p>
</div>

<div class="panel" style="max-width:560px;">
  <div class="panel-head"><h2>Müştəri — "Sifariş tamamlandı" səsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Müştəri öz sifarişini izləyərkən (track.php) siz statusu "Tamamlandı" edən kimi bu səs onun ekranında çalınır.
    Bütün müştərilər üçün eynidir (serverdə saxlanılır).
  </p>
  <div style="display:flex; align-items:center; gap:1rem; flex-wrap:wrap; margin-bottom:1rem;">
    <audio controls style="height:36px;" src="../<?php echo h($completedSoundCustom ?: 'assets/sounds/order-completed.mp3'); ?>"></audio>
    <span style="font-size:.82rem; color:var(--text-soft);">
      <?php echo $completedSoundCustom ? '✓ Öz faylınız aktivdir' : 'Standart səs istifadə olunur'; ?>
    </span>
  </div>
  <form method="post" enctype="multipart/form-data" style="display:flex; gap:.6rem; flex-wrap:wrap; align-items:center;">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="upload_completed_sound">
    <input type="file" name="completed_sound" accept="audio/mp3,audio/wav,audio/ogg,.mp3,.wav,.ogg" required>
    <button type="submit" class="btn btn-primary btn-sm">Yüklə</button>
  </form>
  <?php if ($completedSoundCustom): ?>
    <form method="post" style="margin-top:.6rem;">
      <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
      <input type="hidden" name="action" value="reset_completed_sound">
      <button type="submit" class="btn btn-ghost btn-sm">Standart səsə qaytar</button>
    </form>
  <?php endif; ?>
  <p style="color:var(--text-soft); font-size:.78rem; margin-top:1rem;">MP3/WAV/OGG, maksimum 2 MB.</p>
</div>

<script>
(function(){
  var KEY = 'sg_admin_sound';
  var CUSTOM_KEY = 'sg_admin_custom_sound';
  var radios = document.querySelectorAll('#sound-options input[type=radio]');
  var customRow = document.getElementById('custom-sound-row');
  var customFile = document.getElementById('custom-sound-file');
  var customCurrent = document.getElementById('custom-sound-current');
  var current = 'chime';
  try { current = localStorage.getItem(KEY) || 'chime'; } catch (e) {}
  radios.forEach(function(r){ r.checked = (r.value === current); });

  function syncCustomRow(){
    customRow.style.display = (document.querySelector('#sound-options input[value="custom"]').checked) ? 'block' : 'none';
    var hasCustom = false;
    try { hasCustom = !!localStorage.getItem(CUSTOM_KEY); } catch (e) {}
    customCurrent.textContent = hasCustom ? '✓ Öz səsiniz yaddadır.' : 'Hələ heç bir fayl yüklənməyib.';
  }
  syncCustomRow();

  radios.forEach(function(r){
    r.addEventListener('change', function(){
      try { localStorage.setItem(KEY, r.value); } catch (e) {}
      syncCustomRow();
    });
  });

  if (customFile) {
    customFile.addEventListener('change', function(){
      var file = customFile.files[0];
      if (!file) return;
      if (file.size > 350 * 1024) {
        alert('Fayl çox böyükdür (maks. 300-350 KB). Daha qısa/kiçik həcmli səs seçin.');
        customFile.value = '';
        return;
      }
      var reader = new FileReader();
      reader.onload = function(ev){
        try {
          localStorage.setItem(CUSTOM_KEY, ev.target.result);
          localStorage.setItem(KEY, 'custom');
          document.querySelector('#sound-options input[value="custom"]').checked = true;
          syncCustomRow();
        } catch (e) {
          alert('Səs yaddaşa yazıla bilmədi (brauzer yaddaşı dola bilər). Daha kiçik fayl sınayın.');
        }
      };
      reader.readAsDataURL(file);
    });
  }

  document.getElementById('sound-test').addEventListener('click', function(){
    var sel = document.querySelector('#sound-options input[type=radio]:checked');
    if (sel && window.sgPlayAdminSound) window.sgPlayAdminSound(sel.value);
  });

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(function(){});
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
