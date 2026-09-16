<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_owner();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Səhifə köhnəlib, yenidən cəhd edin.';
    } else {
        $action = $_POST['action'] ?? '';
        if ($action === 'save_completed_sound') {
            $type = $_POST['completed_sound_type'] ?? 'bundled';
            if (!array_key_exists($type, sg_sound_presets())) $type = 'bundled';

            if ($type === 'custom' && !empty($_FILES['completed_sound_file']['tmp_name'])) {
                $path = sg_save_sound_upload($_FILES['completed_sound_file']);
                if ($path) {
                    sg_set_setting('customer_completed_sound', $path);
                } else {
                    $errors[] = 'Fayl yüklənmədi — MP3/WAV/OGG formatında və maksimum 2 MB olmalıdır.';
                }
            }
            if ($type === 'custom' && !sg_setting('customer_completed_sound', '') && empty($_FILES['completed_sound_file']['tmp_name'])) {
                $errors[] = '"Öz səsim" seçmisiniz, amma fayl yükləməmisiniz.';
            }

            if (!$errors) {
                sg_set_setting('customer_completed_sound_type', $type);
                $_SESSION['flash_ok'] = 'Müştəri "sifariş tamamlandı" səsi yeniləndi.';
                header('Location: notifications.php');
                exit;
            }
        }
    }
}

$csrf = sg_csrf_token();
$pageTitle = 'Bildirişlər';
$activeNav = 'notifications';
require __DIR__ . '/includes/header.php';

$presets = sg_sound_presets();
$completedType = sg_setting('customer_completed_sound_type', 'bundled');
$completedCustomFile = sg_setting('customer_completed_sound', '');
?>
<?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

<div class="panel sound-picker" style="max-width:560px;">
  <div class="panel-head"><h2>🔔 Admin — yeni sifariş bildiriş səsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Yeni sifariş daxil olanda admin paneldə (hansı səhifədə olmağınızdan asılı olmayaraq) bu səs çalınacaq.
    Seçim bu brauzerdə yadda saxlanılır.
  </p>
  <div class="sound-options" id="admin-sound-options">
    <?php foreach ($presets as $val => $label): ?>
      <label><input type="radio" name="sg_sound" value="<?php echo h($val); ?>"> <?php echo h($label === 'Standart' ? 'Restoran zəngi' : $label); ?></label>
    <?php endforeach; ?>
  </div>
  <div id="admin-custom-sound-row" style="margin-top:.8rem; display:none;">
    <input type="file" id="admin-custom-sound-file" accept="audio/*">
    <div style="margin-top:.4rem; font-size:.76rem; opacity:.6; max-width:360px; line-height:1.4;">
      MP3/WAV/OGG fayl seçin (tövsiyə: 2-3 saniyəlik qısa səs, maks. 300 KB — brauzerin yaddaşında saxlanılır).
    </div>
    <div id="admin-custom-sound-current" style="margin-top:.4rem; font-size:.8rem; color:var(--text-soft);"></div>
  </div>
  <button type="button" class="btn btn-ghost btn-sm sound-test-btn" data-target="admin-sound-options" style="margin-top:1rem;">🔊 Sına</button>
  <p style="color:var(--text-soft); font-size:.82rem; margin-top:1.2rem;">
    Diqqət: başqa bir sekmədə/proqramda olarkən yeni sifariş gələndə brauzerinizdən icazə istənilə bilər
    ("Bildiriş göndərməyə icazə verin?") — mütləq "İcazə ver" seçin ki, fon rejimində də bildiriş görünsün.
  </p>
</div>

<div class="panel sound-picker" style="max-width:560px;">
  <div class="panel-head"><h2>🔔 Müştəri — "Sifariş tamamlandı" səsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Müştəri öz sifarişini izləyərkən (track.php) siz statusu "Tamamlandı" edən kimi bu səs onun ekranında çalınır.
    Bütün müştərilər üçün eynidir (serverdə saxlanılır) — yuxarıdakı bölmə ilə eyni seçim siyahısını paylaşır.
  </p>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="hidden" name="action" value="save_completed_sound">
    <div class="sound-options" id="completed-sound-options">
      <?php foreach ($presets as $val => $label): ?>
        <label><input type="radio" name="completed_sound_type" value="<?php echo h($val); ?>" <?php echo $completedType === $val ? 'checked' : ''; ?>> <?php echo h($label); ?></label>
      <?php endforeach; ?>
    </div>
    <div id="completed-custom-sound-row" style="margin-top:.8rem; display:<?php echo $completedType === 'custom' ? 'block' : 'none'; ?>;">
      <input type="file" name="completed_sound_file" accept="audio/mp3,audio/wav,audio/ogg,.mp3,.wav,.ogg">
      <div style="margin-top:.4rem; font-size:.78rem; color:var(--text-soft);">
        <?php echo $completedCustomFile ? '✓ Öz faylınız aktivdir. Dəyişmək üçün yeni fayl seçin.' : 'MP3/WAV/OGG, maksimum 2 MB.'; ?>
      </div>
    </div>
    <button type="submit" class="btn btn-primary btn-sm" style="margin-top:1rem;">Yadda saxla</button>
    <button type="button" class="btn btn-ghost btn-sm sound-test-btn" data-target="completed-sound-options" data-bundled-src="../assets/sounds/order-completed.mp3" data-custom-src="<?php echo $completedCustomFile ? h('../' . $completedCustomFile) : ''; ?>">🔊 Sına</button>
  </form>
</div>

<script src="../js/notify-sounds.js"></script>
<script>
(function(){
  // ---- Admin öz bildiriş səsi (localStorage) ----
  var KEY = 'sg_admin_sound';
  var CUSTOM_KEY = 'sg_admin_custom_sound';
  var radios = document.querySelectorAll('#admin-sound-options input[type=radio]');
  var customRow = document.getElementById('admin-custom-sound-row');
  var customFile = document.getElementById('admin-custom-sound-file');
  var customCurrent = document.getElementById('admin-custom-sound-current');
  var current = 'chime';
  try { current = localStorage.getItem(KEY) || 'chime'; } catch (e) {}
  radios.forEach(function(r){ r.checked = (r.value === current); });

  function syncCustomRow(){
    var checked = document.querySelector('#admin-sound-options input[value="custom"]');
    customRow.style.display = (checked && checked.checked) ? 'block' : 'none';
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
          document.querySelector('#admin-sound-options input[value="custom"]').checked = true;
          syncCustomRow();
        } catch (e) {
          alert('Səs yaddaşa yazıla bilmədi (brauzer yaddaşı dola bilər). Daha kiçik fayl sınayın.');
        }
      };
      reader.readAsDataURL(file);
    });
  }

  // ---- Müştəri "tamamlandı" səsi seçimi (serverə göndərilir) ----
  var completedRadios = document.querySelectorAll('#completed-sound-options input[type=radio]');
  var completedCustomRow = document.getElementById('completed-custom-sound-row');
  completedRadios.forEach(function(r){
    r.addEventListener('change', function(){
      completedCustomRow.style.display = (r.value === 'custom') ? 'block' : 'none';
    });
  });

  // ---- Hər iki bölmə üçün ortaq "Sına" düyməsi ----
  // data-bundled-src/data-custom-src olan düymə (müştəri bölməsi) serverdəki
  // faylı çalır; olmayan (admin bölməsi) window.sgPlayAdminSound-dan istifadə edir.
  document.querySelectorAll('.sound-test-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      var targetId = btn.getAttribute('data-target');
      var sel = document.querySelector('#' + targetId + ' input[type=radio]:checked');
      if (!sel) return;
      var bundledSrc = btn.getAttribute('data-bundled-src');
      if (sel.value === 'none') return;
      if (sel.value === 'custom' && bundledSrc) {
        var customSrc = btn.getAttribute('data-custom-src');
        if (customSrc) new Audio(customSrc).play().catch(function(){});
        else alert('Hələ öz səsiniz yüklənməyib.');
        return;
      }
      if (sel.value === 'bundled' && bundledSrc) {
        new Audio(bundledSrc).play().catch(function(){});
        return;
      }
      if (['chime', 'beep1', 'beep2'].indexOf(sel.value) !== -1) {
        sgPlayToneSound(sel.value);
        return;
      }
      if (window.sgPlayAdminSound) window.sgPlayAdminSound(sel.value);
    });
  });

  if ('Notification' in window && Notification.permission === 'default') {
    Notification.requestPermission().catch(function(){});
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
