<?php
require_once __DIR__ . '/includes/auth.php';
sg_require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!sg_csrf_check($_POST['csrf'] ?? '')) {
        $_SESSION['flash_err'] = 'Səhifə köhnəlib, yenidən cəhd edin.';
        header('Location: seo.php');
        exit;
    }
    sg_set_setting('seo_title', trim($_POST['seo_title'] ?? ''));
    sg_set_setting('seo_description', trim($_POST['seo_description'] ?? ''));
    sg_set_setting('seo_keywords', trim($_POST['seo_keywords'] ?? ''));
    $_SESSION['flash_ok'] = 'SEO parametrləri yeniləndi.';
    header('Location: seo.php');
    exit;
}

$csrf = sg_csrf_token();
$pageTitle = 'SEO';
$activeNav = 'seo';
require __DIR__ . '/includes/header.php';

$seoTitle = sg_setting('seo_title', '');
$seoDescription = sg_setting('seo_description', '');
$seoKeywords = sg_setting('seo_keywords', '');
?>

<div class="panel" style="max-width:680px;">
  <div class="panel-head"><h2>Axtarış motorları üçün (SEO)</h2></div>
  <p style="color:var(--text-soft); font-size:.88rem; margin-top:-.6rem; margin-bottom:1rem;">
    Google, Yandex kimi axtarış motorlarında saytınız necə görünəcəyini buradan idarə edin.
    "sushi" və ya "Sushi Garden" axtarışında öndə çıxmaq üçün başlıq/təsvirdə açar sözlər (sushi, Bakı, sifariş, çatdırılma) olmalıdır.
  </p>
  <form method="post">
    <input type="hidden" name="csrf" value="<?php echo h($csrf); ?>">

    <div class="field">
      <label>Səhifə başlığı (title)</label>
      <input type="text" name="seo_title" value="<?php echo h($seoTitle); ?>" placeholder="Sushi Garden — Bakıda Suşi Restoranı | Onlayn Sifariş və Çatdırılma" maxlength="70">
      <div style="font-size:.76rem; color:var(--text-soft); margin-top:.3rem;">Tövsiyə: 50-60 simvol. Google axtarış nəticələrində göy başlıq kimi görünür.</div>
    </div>

    <div class="field">
      <label>Təsvir (meta description)</label>
      <textarea name="seo_description" maxlength="160" rows="3" placeholder="Sushi Garden — Bakıda təzə suşi, sifariş və çatdırılma..."><?php echo h($seoDescription); ?></textarea>
      <div style="font-size:.76rem; color:var(--text-soft); margin-top:.3rem;">Tövsiyə: 140-160 simvol. Google axtarış nəticəsində başlığın altında görünən mətn.</div>
    </div>

    <div class="field">
      <label>Açar sözlər (vergüllə ayırın)</label>
      <textarea name="seo_keywords" rows="2" placeholder="sushi, sushi garden, suşi bakı, sushi sifarişi..."><?php echo h($seoKeywords); ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary">Yadda saxla</button>
  </form>
</div>

<div class="panel" style="max-width:680px;">
  <div class="panel-head"><h2>Kateqoriya və məhsullar üçün SEO</h2></div>
  <p style="color:var(--text-soft); font-size:.9rem; line-height:1.7;">
    Sayt tək səhifə olduğu üçün hər məhsulun ayrıca linki (URL) yoxdur — ona görə "hər məhsul üçün ayrıca başlıq/təsvir"
    mənasında SEO tətbiq edilə bilmir. Bunun əvəzinə, bütün kateqoriyalar və məhsullar (ad, təsvir, qiymət)
    <strong>avtomatik olaraq</strong> Google-ın "Menu" strukturlaşdırılmış datasına (schema.org) əlavə olunur —
    bu, Google-a menyunuzun tam məzmununu göstərir və axtarış nəticələrində menyu kimi görünmə şansını artırır.
    Əlavə iş görmək lazım deyil: Menyu/Kateqoriyalar bölmələrindəki AZ adı və təsviri artıq bunun üçün istifadə olunur —
    daha aydın, açar-söz zəngin adlar/təsvirlər yazmaq (məs. sadəcə "Set 1" yox, "Klassik Sushi Seti") SEO-ya birbaşa kömək edir.
  </p>
</div>

<div class="panel" style="max-width:680px;">
  <div class="panel-head"><h2>Əlavə tövsiyələr</h2></div>
  <p style="color:var(--text-soft); font-size:.9rem; line-height:1.7;">
    Sayt texniki tərəfdən artıq hazırdır: strukturlaşdırılmış data (Restaurant schema), robots.txt, sitemap.xml.
    Google-da "sushi Bakı" axtarışında görünmək üçün ƏN TƏSİRLİ addım isə texniki SEO-dan kənardır:
  </p>
  <ol style="color:var(--text-soft); font-size:.9rem; line-height:1.9; padding-left:1.2rem;">
    <li><strong>Google Business Profile</strong> (business.google.com) — pulsuz, restoranı Google Maps-ə və axtarış nəticələrinə əlavə edir. Bu addım ən böyük təsirə malikdir.</li>
    <li>Müştərilərdən Google-da rəy (review) istəyin — reytinq və rəy sayı sıralamaya birbaşa təsir edir.</li>
    <li><a href="https://search.google.com/search-console" target="_blank" rel="noopener">Google Search Console</a>-da saytı qeydiyyatdan keçirib sitemap.xml təqdim edin.</li>
  </ol>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
