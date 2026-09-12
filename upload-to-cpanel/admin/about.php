<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

$fields = [
    'about_subtext' => 'Alt başlıq mətni (loqonun yanındakı qısa təsvir)',
    'about_concept_title' => '"Fəlsəfəmiz" bölməsinin başlığı',
    'about_concept_p1' => '"Fəlsəfəmiz" — 1-ci paraqraf',
    'about_concept_p2' => '"Fəlsəfəmiz" — 2-ci paraqraf',
];
$defaultsAz = [
    'about_subtext' => 'Bakının mərkəzində əl işi suşi təcrübəsi — təbii materiallar, yapon dəqiqliyi və səmimi qonaqpərvərliklə hər gün yenidən hazırlanır.',
    'about_concept_title' => 'Bağ Konsepsiyamız',
    'about_concept_p1' => 'Sushi Garden bir restorandan çox — canlı bir bağdır. Hər boşqab təbiətin sadəliyini, hər dad isə ustaların səbrini əks etdirir.',
    'about_concept_p2' => 'Təzə balıq hər səhər tədarük olunur, düyü əl ilə hazırlanır, tərəvəzlər isə mövsümə uyğun seçilir — sürətli qidalanma deyil, yavaş və düşünülmüş bir sənət.',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: about.php');
        exit;
    }
    foreach (array_keys($fields) as $key) {
        foreach (['az', 'ru', 'en'] as $lang) {
            $val = trim($_POST[$key . '_' . $lang] ?? '');
            sg_set_setting($key . '_' . $lang, $val);
        }
    }
    $_SESSION['flash_ok'] = '"Haqqımızda" mətnləri yeniləndi.';
    header('Location: about.php');
    exit;
}

$csrf = sg_csrf_token();
$pageTitle = 'Haqqımızda';
$activeNav = 'about';
require __DIR__ . '/includes/header.php';
?>

<form method="post" class="panel" style="max-width:760px;">
  <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">
  <div class="panel-head"><h2>Sayt → Haqqımızda bölməsi</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1.4rem;">
    RU/EN sahələrini boş buraxsanız, sayt həmin dillərdə də Azərbaycanca mətni göstərəcək.
  </p>

  <?php foreach ($fields as $key => $label): ?>
    <div class="lang-block lang-block-az" style="margin-bottom:1.4rem;">
      <div class="lang-block-title"><?php echo h($label); ?></div>
      <div class="field">
        <label>🇦🇿 Azərbaycanca <span class="req">(məcburi)</span></label>
        <?php if ($key === 'about_concept_title'): ?>
          <input type="text" name="<?php echo h($key); ?>_az" value="<?php echo h(sg_setting($key . '_az', $defaultsAz[$key])); ?>" required>
        <?php else: ?>
          <textarea name="<?php echo h($key); ?>_az" rows="2" required><?php echo h(sg_setting($key . '_az', $defaultsAz[$key])); ?></textarea>
        <?php endif; ?>
      </div>
      <div class="field">
        <label>🇷🇺 Русский <span class="opt">(istəyə bağlı)</span></label>
        <?php if ($key === 'about_concept_title'): ?>
          <input type="text" name="<?php echo h($key); ?>_ru" value="<?php echo h(sg_setting($key . '_ru', '')); ?>">
        <?php else: ?>
          <textarea name="<?php echo h($key); ?>_ru" rows="2"><?php echo h(sg_setting($key . '_ru', '')); ?></textarea>
        <?php endif; ?>
      </div>
      <div class="field">
        <label>🇬🇧 English <span class="opt">(optional)</span></label>
        <?php if ($key === 'about_concept_title'): ?>
          <input type="text" name="<?php echo h($key); ?>_en" value="<?php echo h(sg_setting($key . '_en', '')); ?>">
        <?php else: ?>
          <textarea name="<?php echo h($key); ?>_en" rows="2"><?php echo h(sg_setting($key . '_en', '')); ?></textarea>
        <?php endif; ?>
      </div>
    </div>
  <?php endforeach; ?>

  <button type="submit" class="btn btn-primary">Yadda saxla</button>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
