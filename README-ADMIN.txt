SUSHI GARDEN — SAYT + İDARƏETMƏ PANELİ
========================================

BU PAKETDƏ NƏ VAR?
-------------------
"upload-to-cpanel" qovluğunun İÇİNDƏKİ HƏR ŞEY sizin hostinqinizdəki
public_html qovluğuna yüklənməlidir (özü yox, içindəkilər).

Struktur:
  index.php              → Saytın əsas səhifəsi (menyu avtomatik bazadan gəlir)
  css/, js/, assets/     → Dizayn, şəkillər, sayt skripti
  admin/                 → İdarəetmə paneli (məhsul/kateqoriya idarəetməsi)
  data/                  → Verilənlər bazası faylı (sushigarden.sqlite) — TOXUNMAYIN
  uploads/products/      → Admin paneldən əlavə etdiyiniz məhsul şəkilləri buraya düşür
  includes/              → Sistemin daxili fayl(lar)ı — TOXUNMAYIN

YÜKLƏMƏ (DEPLOYMENT) ADDIMLARI
-------------------------------
1. Azhosting cPanel-ə daxil olun (https://sushigarden.az:2083 və ya
   https://185.194.218.236:2083 — DNS yönləndirilməyibsə IP ilə).
2. "File Manager" (Fayl Meneceri) açın, public_html qovluğuna keçin.
3. Əgər public_html-də köhnə index.html faylı varsa, silin (və ya adını
   dəyişin) ki, yeni index.php ilə qarışmasın.
4. "upload-to-cpanel" qovluğunun İÇİNDƏKİ bütün fayl və qovluqları
   (index.php, css, js, assets, admin, data, uploads, includes) seçib
   public_html-ə yükləyin. Ən rahat yol: bu qovluğu zip formatında
   yükləyib cPanel-in "Extract" funksiyası ilə açmaqdır.
5. Yükləmə bitəndən sonra sushigarden.az saytını açıb yoxlayın.

İDARƏETMƏ PANELİNƏ GİRİŞ
--------------------------
Ünvan:            https://sushigarden.az/admin/
İstifadəçi adı:    admin
Şifrə:             SushiGarden2026!

VACİB: İlk girişdən dərhal sonra "Parametrlər" bölməsindən bu şifrəni
öz seçdiyiniz güclü şifrə ilə əvəz edin.

PANELDƏ NƏ EDƏ BİLƏRSİNİZ?
----------------------------
- Yeni məhsul əlavə etmək (ad, tərkib/təsvir, qiymət, foto — istəyə görə RU/EN tərcümə də)
- Mövcud məhsulu redaktə etmək və ya silmək
- Məhsulu saytdan MÜVƏQQƏTİ gizlətmək (silmədən) — "Görünür/Gizli" düyməsi
- Foto əlavə edərkən şəkli birbaşa admin paneldə kəsib düzəltmək (kvadrat format)
- Yeni kateqoriya yaratmaq, adını dəyişmək, sıralamağı dəyişmək (yuxarı/aşağı ox)
- Kateqoriyanı bütünlüklə gizlətmək (məs. mövsümi menyu üçün)
- Admin istifadəçi adı/şifrəsini dəyişmək
- SİFARİŞLƏR: müştərilər saytda birbaşa sifariş verə bilir (çatdırılma/özü aparma/
  restoranda, bəxşiş, ad/telefon/ünvan) — bu sifarişlər "Sifarişlər" bölməsində
  görünür, statusunu dəyişə (Gözləyir → Hazırlanır → Hazırdır → Tamamlandı) və
  silə bilərsiniz. WhatsApp ilə sifariş düyməsi də paralel olaraq qalır.
- RESTORAN: restoran adı, sloqan və 7 günlük iş saatlarını idarə etmək
- ƏLAQƏLƏR: telefon(lar), ünvan və Google Maps linkini idarə etmək
- SOSİAL ŞƏBƏKƏLƏR: Instagram/Facebook/TikTok linklərini idarə etmək
- GÖRÜNÜŞ: loqo və hero şəklini dəyişmək, saytın linkinə uyğun QR kod yükləmək
  (masalara qoymaq üçün — müştərilər skan edib birbaşa menyuya keçir)
- Sayt üç dildə göstərilə bilir (AZ/RU/EN) — ziyarətçi başlıqdakı düymələrlə
  seçir; məhsul/kateqoriya adlarının RU/EN tərcüməsini admin paneldən daxil
  etsəniz həmin dildə görünəcək, boş buraxsanız Azərbaycanca qalacaq.

Etdiyiniz hər dəyişiklik YADDAN ÇIXMADAN dərhal canlı saytda görünür —
ayrıca "yenidən yükləmə" tələb olunmur.

HAZIRKI MENYU
--------------
10 kateqoriya, hər birində 20 məhsul (cəmi 200) — real qiymət və adlarla,
lakin NÜMUNƏ menyu kimi hazırlanıb. Bunları admin paneldən öz real
menyunuzla əvəz edə, qiymətləri düzəldə, lazımsız olanları silə/gizlədə
bilərsiniz.

TEXNİKİ QEYDLƏR
-----------------
- Sayt PHP + SQLite üzərində işləyir — cPanel hostinqlərin əksəriyyətində
  (o cümlədən Azhosting) əlavə quraşdırmaya ehtiyac yoxdur.
  MySQL verilənlər bazası yaratmağa EHTİYAC YOXDUR.
- data/ qovluğundakı sushigarden.sqlite faylı bütün menyu məlumatını
  saxlayır — mütləq ehtiyat nüsxəsini (backup) dövri olaraq özünüzə
  yükləyib saxlayın (cPanel File Manager-dən yükləmək kifayətdir).
- Admin panelin foto-kəsmə funksiyası internetdən kiçik bir kitabxana
  (cropperjs, cdnjs.cloudflare.com) yükləyir. Əgər hər hansı səbəbdən bu
  yüklənməsə, sistem avtomatik olaraq şəkli kəsmədən birbaşa yükləyir —
  panel yenə də işləyir, sadəcə kəsmə addımı olmur.
