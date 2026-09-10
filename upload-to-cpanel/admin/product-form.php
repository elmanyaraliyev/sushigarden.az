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
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
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
        $croppedImage = $_POST['cropped_image'] ?? '';

        if ($values['category_id'] <= 0) $errors[] = 'Kateqoriya seçin.';
        if ($values['name'] === '') $errors[] = 'Məhsul adı boş ola bilməz.';
        if (!is_numeric($values['price']) || (float)$values['price'] < 0) $errors[] = 'Qiymət düzgün rəqəm olmalıdır.';
        if ($values['featured'] && (!$product || !$product['featured'])) {
            $featuredCount = (int)$pdo->query('SELECT COUNT(*) FROM products WHERE featured = 1')->fetchColumn();
            if ($featuredCount >= 8) $errors[] = 'Ən çoxu 8 məhsulu "Tövsiyə olunanlar"a əlavə edə bilərsiniz.';
        }

        if (!$errors) {
            $imageFilename = $product['image'] ?? null;

            if ($croppedImage) {
                $newFile = sg_save_cropped_image($croppedImage);
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
    <input type="hidden" name="cropped_image" id="cropped_image" value="">

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

    <div class="field">
      <label>Məhsul adı (AZ)</label>
      <input type="text" name="name" value="<?php echo h($values['name']); ?>" required>
    </div>

    <div class="field">
      <label>Tərkib / təsvir (AZ)</label>
      <textarea name="description" placeholder="Məs. Krab çubuğu, avokado, xiyar, kunjut"><?php echo h($values['description']); ?></textarea>
    </div>

    <div class="form-grid">
      <div class="field">
        <label>Ad (RU) — istəyə bağlı</label>
        <input type="text" name="name_ru" value="<?php echo h($values['name_ru']); ?>">
      </div>
      <div class="field">
        <label>Ad (EN) — istəyə bağlı</label>
        <input type="text" name="name_en" value="<?php echo h($values['name_en']); ?>">
      </div>
    </div>
    <div class="form-grid">
      <div class="field">
        <label>Təsvir (RU) — istəyə bağlı</label>
        <textarea name="description_ru"><?php echo h($values['description_ru']); ?></textarea>
      </div>
      <div class="field">
        <label>Təsvir (EN) — istəyə bağlı</label>
        <textarea name="description_en"><?php echo h($values['description_en']); ?></textarea>
      </div>
    </div>

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
  var croppedField = document.getElementById('cropped_image');
  var currentImage = document.getElementById('current-image');
  var removeCheckbox = document.getElementById('remove_image');
  var cropper = null;

  function setPreview(dataUrl){
    croppedField.value = dataUrl;
    if (currentImage.tagName === 'IMG') {
      currentImage.src = dataUrl;
    } else {
      var img = document.createElement('img');
      img.src = dataUrl;
      img.className = 'img-preview';
      img.id = 'current-image';
      currentImage.replaceWith(img);
      currentImage = img;
    }
    if (removeCheckbox) removeCheckbox.checked = false;
  }

  imageInput.addEventListener('change', function(e){
    var file = e.target.files[0];
    if (!file) return;
    var reader = new FileReader();
    reader.onload = function(ev){
      // Kropper kitabxanası (CDN) yüklənməyibsə — internet problemi, ad-blocker və s. —
      // şəkli kəsmədən birbaşa yükləyirik ki, admin panel yenə də işləsin.
      if (window.__cropperFailed || typeof Cropper === 'undefined') {
        setPreview(ev.target.result);
        return;
      }
      cropTarget.src = ev.target.result;
      cropperWrap.style.display = 'block';
      if (cropper) cropper.destroy();
      cropper = new Cropper(cropTarget, {
        aspectRatio: 1,
        viewMode: 1,
        autoCropArea: 1,
        background: false
      });
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('crop-confirm').addEventListener('click', function(){
    if (!cropper) return;
    var canvas = cropper.getCroppedCanvas({ width: 700, height: 700 });
    setPreview(canvas.toDataURL('image/jpeg', 0.9));
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
