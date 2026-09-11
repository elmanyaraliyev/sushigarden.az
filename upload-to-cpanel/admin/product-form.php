<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();
$pdo = sg_db();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$editing = $id > 0;
$product = $editing ? sg_product($id) : null;
if ($editing && !$product) {
    $_SESSION['flash_err'] = 'Məhsul tapılmadı.';
    header('Location: products.php');
    exit;
}

$errors = [];
$values = [
    'category_id' => $product['category_id'] ?? (int)($_GET['category'] ?? 0),
    'name' => $product['name'] ?? '',
    'name_en' => $product['name_en'] ?? '',
    'name_ru' => $product['name_ru'] ?? '',
    'description' => $product['description'] ?? '',
    'description_en' => $product['description_en'] ?? '',
    'description_ru' => $product['description_ru'] ?? '',
    'price' => $product['price'] ?? '',
    'active' => $product ? (int)$product['active'] : 1,
    'featured' => $product ? (int)$product['featured'] : 0,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        // $_POST tamamilə boşdur, amma məlumat göndərilib — server qəbul limitini aşıb, PHP hər şeyi ataraq susub.
        $errors[] = 'Şəkil çox böyükdür, server onu qəbul etmədi. Daha kiçik şəkil seçib yenidən cəhd edin.';
    } elseif (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $errors[] = 'Səhifə köhnəlib, formu yenidən doldurun.';
    } else {
        $values['category_id'] = (int)($_POST['category_id'] ?? 0);
        $values['name'] = trim($_POST['name'] ?? '');
        $values['name_en'] = trim($_POST['name_en'] ?? '');
        $values['name_ru'] = trim($_POST['name_ru'] ?? '');
        $values['description'] = trim($_POST['description'] ?? '');
        $values['description_en'] = trim($_POST['description_en'] ?? '');
        $values['description_ru'] = trim($_POST['description_ru'] ?? '');
        $values['price'] = trim($_POST['price'] ?? '');
        $values['active'] = isset($_POST['active']) ? 1 : 0;
        $values['featured'] = isset($_POST['featured']) ? 1 : 0;
        $removeImage = isset($_POST['remove_image']);
        $uploadedPhoto = $_FILES['product_photo'] ?? null;
        $photoUploadFailed = $uploadedPhoto && $uploadedPhoto['error'] !== UPLOAD_ERR_OK && $uploadedPhoto['error'] !== UPLOAD_ERR_NO_FILE;
        if ($photoUploadFailed) {
            $sizeErrors = [UPLOAD_ERR_INI_SIZE => 1, UPLOAD_ERR_FORM_SIZE => 1];
            $errors[] = isset($sizeErrors[$uploadedPhoto['error']])
                ? 'Şəkil çox böyükdür, server onu qəbul etmədi. Daha kiçik şəkil seçib yenidən cəhd edin.'
                : 'Şəkil yüklənərkən xəta baş verdi, yenidən cəhd edin.';
        }

        if ($values['category_id'] <= 0) $errors[] = 'Kateqoriya seçin.';
        if ($values['name'] === '') $errors[] = 'Məhsul adı boş ola bilməz.';
        if (!is_numeric($values['price']) || (float)$values['price'] < 0) $errors[] = 'Qiymət düzgün rəqəm olmalıdır.';
        if ($values['featured'] && (!$product || !$product['featured'])) {
            $featuredCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE featured = 1')->fetchColumn();
            if ($featuredCount >= 8) $errors[] = 'Ən çoxu 8 məhsulu "Tövsiyə olunanlar"a əlavə edə bilərsiniz.';
        }

        if (!$errors) {
            $imageFilename = $product['image'] ?? null;

            if ($uploadedPhoto && $uploadedPhoto['error'] === UPLOAD_ERR_OK) {
                $newFile = sg_save_product_photo($uploadedPhoto);
                if ($newFile) {
                    if ($imageFilename) sg_delete_product_image($imageFilename);
                    $imageFilename = $newFile;
                } else {
                    $errors[] = 'Şəkil yadda saxlanılmadı, yenidən cəhd edin.';
                }
            } elseif ($removeImage && $imageFilename) {
                sg_delete_product_image($imageFilename);
                $imageFilename = null;
            }

            if (!$errors) {
                if ($editing) {
                    $stmt = $pdo->prepare('UPDATE products SET category_id=?, name=?, name_en=?, name_ru=?, description=?, description_en=?, description_ru=?, price=?, active=?, featured=?, image=? WHERE id=?');
                    $stmt->execute([
                        $values['category_id'], $values['name'], $values['name_en'] ?: null, $values['name_ru'] ?: null,
                        $values['description'], $values['description_en'] ?: null, $values['description_ru'] ?: null,
                        $values['price'], $values['active'], $values['featured'], $imageFilename, $id,
                    ]);
                    $_SESSION['flash_ok'] = 'Məhsul yeniləndi.';
                } else {
                    $order = sg_next_sort_order('products', $values['category_id']);
                    $stmt = $pdo->prepare('INSERT INTO products (category_id, name, name_en, name_ru, description, description_en, description_ru, price, active, featured, image, sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)');
                    $stmt->execute([
                        $values['category_id'], $values['name'], $values['name_en'] ?: null, $values['name_ru'] ?: null,
                        $values['description'], $values['description_en'] ?: null, $values['description_ru'] ?: null,
                        $values['price'], $values['active'], $values['featured'], $imageFilename, $order,
                    ]);
                    $_SESSION['flash_ok'] = 'Yeni məhsul əlavə olundu.';
                }
                header('Location: products.php');
                exit;
            }
        }
    }
}

$categories = sg_all_categories();
$csrf = sg_csrf_token();
$pageTitle = $editing ? 'Məhsulu redaktə et' : 'Yeni məhsul';
$activeNav = 'products';
require __DIR__ . '/includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">

<div class="panel" style="max-width:760px;">
  <?php foreach ($errors as $e): ?><div class="flash err"><?php echo h($e); ?></div><?php endforeach; ?>

  <form method="post" enctype="multipart/form-data" id="product-form">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
    <input type="file" name="product_photo" id="cropped_image" style="display:none;">

    <div class="form-grid full">
      <div class="field">
        <label>Foto</label>
        <div class="img-preview-row">
          <?php if (!empty($product['image'])): ?>
            <img src="../uploads/products/<?php echo h($product['image']); ?>" class="img-preview" id="current-image">
          <?php else: ?>
            <div class="img-preview" id="current-image" style="display:flex; align-items:center; justify-content:center; font-size:1.6rem;">🍣</div>
          <?php endif; ?>
          <div>
            <input type="file" id="image-input" accept="image/*">
            <div style="margin-top:.4rem; font-size:.76rem; opacity:.55; max-width:280px; line-height:1.4;">
              Tövsiyə: kvadrat (1:1) və ya üfüqi (16:9) şəkil, minimum 700px en, JPG və ya PNG formatında,
              maksimum 20 MB (avtomatik kiçildilir). Şəkil seçəndən sonra kəsmə pəncərəsində formatı seçə bilərsiniz.
            </div>
            <?php if (!empty($product['image'])): ?>
              <div style="margin-top:.5rem;">
                <label class="checkbox-row" style="display:inline-flex;">
                  <input type="checkbox" name="remove_image" id="remove_image"> Fotonu sil
                </label>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <div id="cropper-wrap" style="display:none;">
          <div class="crop-area"><img id="crop-target" src="" style="max-width:100%;"></div>
          <div style="display:flex; gap:.5rem; margin-bottom:.6rem;">
            <button type="button" class="btn btn-ghost btn-sm crop-ratio active" data-ratio="1">◻ Kvadrat (1:1)</button>
            <button type="button" class="btn btn-ghost btn-sm crop-ratio" data-ratio="1.7778">▭ Üfüqi (16:9)</button>
          </div>
          <button type="button" class="btn btn-ghost btn-sm" id="crop-confirm">✓ Şəkli kəs və təsdiqlə</button>
          <button type="button" class="btn btn-ghost btn-sm" id="crop-cancel">Ləğv et</button>
        </div>
      </div>
    </div>

    <div class="form-grid">
      <div class="field">
        <label>Kateqoriya</label>
        <select name="category_id" required>
          <option value="">— Seçin —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?php echo (int)$cat['id']; ?>" <?php echo (int)$values['category_id'] === (int)$cat['id'] ? 'selected' : ''; ?>><?php echo h($cat['name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Qiymət (₼)</label>
        <input type="number" name="price" step="0.01" min="0" value="<?php echo h($values['price']); ?>" required>
      </div>
    </div>

    <div class="lang-block lang-block-az">
      <div class="lang-block-title">🇦🇿 Azərbaycanca <span class="req">(məcburi)</span></div>
      <div class="field">
        <label>Məhsul adı</label>
        <input type="text" name="name" value="<?php echo h($values['name']); ?>" required>
      </div>
      <div class="field">
        <label>Tərkib / təsvir</label>
        <textarea name="description" placeholder="Məs. Krab çubuğu, avokado, xiyar, kunjut"><?php echo h($values['description']); ?></textarea>
      </div>
    </div>

    <div class="lang-block">
      <div class="lang-block-title">🇷🇺 Русский <span class="opt">(istəyə bağlı)</span></div>
      <div class="field">
        <label>Название</label>
        <input type="text" name="name_ru" value="<?php echo h($values['name_ru']); ?>">
      </div>
      <div class="field">
        <label>Состав / описание</label>
        <textarea name="description_ru"><?php echo h($values['description_ru']); ?></textarea>
      </div>
    </div>

    <div class="lang-block">
      <div class="lang-block-title">🇬🇧 English <span class="opt">(optional)</span></div>
      <div class="field">
        <label>Product name</label>
        <input type="text" name="name_en" value="<?php echo h($values['name_en']); ?>">
      </div>
      <div class="field">
        <label>Ingredients / description</label>
        <textarea name="description_en"><?php echo h($values['description_en']); ?></textarea>
      </div>
    </div>

    <p style="color:var(--text-soft); font-size:.82rem; margin:-.4rem 0 1.2rem;">
      RU/EN sahələrini boş buraxsanız, sayt həmin dillərdə də Azərbaycanca adı/təsviri göstərəcək.
    </p>

    <div class="field">
      <label class="checkbox-row">
        <input type="checkbox" name="active" <?php echo $values['active'] ? 'checked' : ''; ?>> Saytda görünsün
      </label>
    </div>

    <div class="field">
      <label class="checkbox-row">
        <input type="checkbox" name="featured" <?php echo $values['featured'] ? 'checked' : ''; ?>> ★ Ön səhifədə "Tövsiyə olunanlar"da göstər (maks. 8)
      </label>
    </div>

    <div style="display:flex; gap:.8rem;">
      <button type="submit" class="btn btn-primary"><?php echo $editing ? 'Yadda saxla' : 'Əlavə et'; ?></button>
      <a href="products.php" class="btn btn-ghost">Ləğv et</a>
    </div>
  </form>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js" onerror="window.__cropperFailed=true"></script>
<script>
(function(){
  var imageInput = document.getElementById('image-input');
  var cropperWrap = document.getElementById('cropper-wrap');
  var cropTarget = document.getElementById('crop-target');
  var photoField = document.getElementById('cropped_image'); // real <input type="file">, göndərilir
  var currentImage = document.getElementById('current-image');
  var removeCheckbox = document.getElementById('remove_image');
  var cropper = null;

  // Şəkli əsl fayl kimi (base64 mətn sahəsi kimi YOX — bəzi hostinqlərin
  // ModSecurity/WAF qaydaları çox uzun base64 sahələrini səssizcə atır)
  // gizli file input-a qoyuruq ki, form normal multipart faylı kimi göndərsin.
  function setPreviewFromBlob(blob){
    var file = new File([blob], 'product-photo.jpg', { type: 'image/jpeg' });
    var dt = new DataTransfer();
    dt.items.add(file);
    photoField.files = dt.files;

    var url = URL.createObjectURL(blob);
    if (currentImage.tagName === 'IMG') {
      currentImage.src = url;
    } else {
      var img = document.createElement('img');
      img.src = url;
      img.className = 'img-preview';
      img.id = 'current-image';
      currentImage.replaceWith(img);
      currentImage = img;
    }
    if (removeCheckbox) removeCheckbox.checked = false;
  }

  // Kropper (CDN) yüklənməsə belə, şəkli HƏMİŞƏ canvas ilə kiçildib göndəririk —
  // əks halda telefon şəklinin əsl ölçüsü (8-12 MB) serverin qəbul limitini aşıb
  // BÜTÜN FORMU sıradan çıxarır (heç bir sahə saxlanılmır, qəribə "köhnəlib" xətası çıxır).
  function resizeToBlob(srcDataUrl, maxDim, cb){
    var img = new Image();
    img.onload = function(){
      var w = img.naturalWidth, h = img.naturalHeight;
      if (w > maxDim || h > maxDim) {
        if (w > h) { h = Math.round(h * maxDim / w); w = maxDim; }
        else { w = Math.round(w * maxDim / h); h = maxDim; }
      }
      var canvas = document.createElement('canvas');
      canvas.width = w; canvas.height = h;
      canvas.getContext('2d').drawImage(img, 0, 0, w, h);
      canvas.toBlob(cb, 'image/jpeg', 0.85);
    };
    img.src = srcDataUrl;
  }

  imageInput.addEventListener('change', function(e){
    var file = e.target.files[0];
    if (!file) return;
    if (file.size > 20 * 1024 * 1024) {
      alert('Şəkil çox böyükdür (maks. 20 MB). Daha kiçik şəkil seçin.');
      imageInput.value = '';
      return;
    }
    var reader = new FileReader();
    reader.onload = function(ev){
      if (window.__cropperFailed || typeof Cropper === 'undefined') {
        resizeToBlob(ev.target.result, 900, setPreviewFromBlob);
        return;
      }
      cropTarget.src = ev.target.result;
      cropperWrap.style.display = 'block';
      if (cropper) cropper.destroy();
      currentRatio = 1;
      ratioBtns.forEach(function(b){ b.classList.toggle('active', b.getAttribute('data-ratio') === '1'); });
      cropper = new Cropper(cropTarget, {
        aspectRatio: 1,
        viewMode: 1,
        autoCropArea: 1,
        background: false
      });
    };
    reader.readAsDataURL(file);
  });

  var currentRatio = 1;
  var ratioBtns = document.querySelectorAll('.crop-ratio');
  ratioBtns.forEach(function(btn){
    btn.addEventListener('click', function(){
      if (!cropper) return;
      currentRatio = parseFloat(btn.getAttribute('data-ratio'));
      cropper.setAspectRatio(currentRatio);
      ratioBtns.forEach(function(b){ b.classList.toggle('active', b === btn); });
    });
  });

  document.getElementById('crop-confirm').addEventListener('click', function(){
    if (!cropper) return;
    var outW = 900, outH = Math.round(outW / currentRatio);
    var canvas = cropper.getCroppedCanvas({ width: outW, height: outH });
    canvas.toBlob(function(blob){ setPreviewFromBlob(blob); }, 'image/jpeg', 0.9);
    cropperWrap.style.display = 'none';
    cropper.destroy();
    cropper = null;
  });

  document.getElementById('crop-cancel').addEventListener('click', function(){
    cropperWrap.style.display = 'none';
    imageInput.value = '';
    if (cropper) { cropper.destroy(); cropper = null; }
  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
