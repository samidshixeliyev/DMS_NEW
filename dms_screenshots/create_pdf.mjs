import puppeteer from './node_modules/puppeteer/lib/esm/puppeteer/puppeteer.js';
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const IMG = path.join(__dirname, 'images2');
const OUT = 'C:\\Users\\samid.sixaliyev\\Desktop\\workspace\\DMS_NEW\\dms-app\\DMS_Telimat.pdf';

// Convert image to base64
function imgB64(file) {
  const data = fs.readFileSync(path.join(IMG, file));
  return `data:image/jpeg;base64,${data.toString('base64')}`;
}

const html = `<!DOCTYPE html>
<html lang="az">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: Arial, sans-serif; font-size: 13px; color: #111827; line-height: 1.6; }
  .page { width: 210mm; margin: 0 auto; padding: 18mm 18mm 18mm 18mm; }

  /* Cover */
  .cover { text-align: center; padding-top: 60mm; page-break-after: always; }
  .cover .brand { font-size: 72pt; font-weight: bold; color: #1a3a5c; }
  .cover .sub1  { font-size: 22pt; font-weight: bold; color: #1a3a5c; margin-top: 8px; }
  .cover .sub2  { font-size: 26pt; font-weight: bold; color: #2563eb; margin-top: 10px; }
  .cover .date  { font-size: 11pt; color: #9ca3af; margin-top: 40px; }

  /* Headers */
  h1 { font-size: 18pt; font-weight: bold; color: #1a3a5c; margin: 0 0 12px 0;
       padding-top: 16px; border-bottom: 2px solid #1a3a5c; page-break-before: always; }
  h1.no-break { page-break-before: avoid; }
  h2 { font-size: 13pt; font-weight: bold; color: #1a3a5c; margin: 24px 0 8px 0; }

  /* Body text */
  p  { margin: 6px 0 10px 0; text-align: left; }

  /* Steps */
  .steps { margin: 10px 0 14px 0; }
  .step  { display: flex; gap: 10px; margin: 6px 0; align-items: flex-start; }
  .step-num { min-width: 22px; font-weight: bold; color: #2563eb; }

  /* Bullets */
  ul { margin: 6px 0 12px 18px; }
  li { margin: 4px 0; }

  /* Images */
  .img-wrap { margin: 14px 0 4px 0; border: 1px solid #d1d5db; text-align: center; }
  .img-wrap img { max-width: 100%; display: block; margin: 0 auto; }
  .img-cap { text-align: center; font-size: 10pt; color: #6b7280; font-style: italic; margin: 2px 0 14px 0; }

  /* Note boxes */
  .tip  { background:#eff6ff; border-left:4px solid #2563eb; padding:8px 12px; margin:10px 0; font-size:12px; color:#1e40af; }
  .warn { background:#fffbeb; border-left:4px solid #d97706; padding:8px 12px; margin:10px 0; font-size:12px; color:#92400e; }
  .imp  { background:#fef2f2; border-left:4px solid #dc2626; padding:8px 12px; margin:10px 0; font-size:12px; color:#991b1b; }

  /* Color table */
  table { border-collapse: collapse; width: 100%; margin: 12px 0; }
  th { background: #1e3a5f; color: white; padding: 7px 10px; font-size: 12px; }
  td { border: 1px solid #e5e7eb; padding: 7px 10px; font-size: 12px; }

  /* FAQ */
  .faq-q { font-weight: bold; color: #1a3a5c; margin: 20px 0 4px 0; font-size: 13px; }
  .faq-a { color: #374151; margin: 0 0 6px 0; }

  /* Header/Footer for print */
  @page { size: A4; margin: 18mm; }
  @media print {
    body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  }
</style>
</head>
<body>
<div class="page">

<!-- COVER -->
<div class="cover">
  <div class="brand">DMS</div>
  <div class="sub1">Sənəd İdarəetmə Sistemi</div>
  <div class="sub2">İstifadəçi Təlimatı</div>
  <div class="date">May 2026</div>
</div>

<!-- 1. GİRİŞ -->
<h1 class="no-break">1. Sistemə Giriş</h1>
<p>Brauzerdə (Chrome, Firefox, Brave) sistemin ünvanını açın. Giriş səhifəsi görünəcək:</p>
<div class="img-wrap"><img src="${imgB64('01_login_page.jpg')}"></div>
<div class="img-cap">Şəkil 1: Giriş səhifəsi</div>
<p>İstifadəçi adı və şifrənizi yazıb <strong>Daxil ol</strong> düyməsinə basın. Şifrəni görmək üçün sahənin sağındakı göz işarəsinə basa bilərsiniz.</p>
<div class="img-wrap"><img src="${imgB64('02_login_filled.jpg')}"></div>
<div class="img-cap">Şəkil 2: İstifadəçi adı daxil edilib</div>
<div class="warn">⚠️ Şifrə və ya istifadəçi adı yanlış olduqda xəta mesajı görünür — sadəcə yenidən düzgün yazın.</div>
<div class="img-wrap"><img src="${imgB64('03_login_error.jpg')}"></div>
<div class="img-cap">Şəkil 3: Yanlış giriş — xəta mesajı</div>
<div class="imp">❗ Şifrənizi unutmusunuzsa, administrator ilə əlaqə saxlayın. Sistem özü sıfırlama etmir.</div>

<!-- 2. ANA EKRAN -->
<h1>2. Ana Ekran və Menyu</h1>
<div class="img-wrap"><img src="${imgB64('04_huquqi_aktlar_main.jpg')}"></div>
<div class="img-cap">Şəkil 4: Sistemə daxil olduqdan sonra əsas ekran</div>
<p>Sistemə daxil olduqdan sonra sol tərəfdə menyu görünür. Buradan istənilən bölməyə keçid edə bilərsiniz:</p>
<div class="img-wrap"><img src="${imgB64('05_sidebar_navigation.jpg')}"></div>
<div class="img-cap">Şəkil 5: Sol menyu — naviqasiya</div>
<ul>
  <li><strong>Hüquqi Aktlar</strong> — bütün sənədlər burada saxlanılır</li>
  <li><strong>İcraçı Paneli</strong> — sizə tapşırılmış işlər</li>
  <li><strong>Təsdiq Gözləyənlər</strong> — menecerlər üçün, gələn hesabatlar</li>
  <li><strong>Hesabat</strong> — statistika (menecerlər üçün)</li>
  <li><strong>Şifrəni dəyiş</strong> — parol dəyişmək</li>
  <li><strong>Çıxış</strong> — sistemdən çıxmaq</li>
</ul>

<!-- 3. HÜQUQİ AKTLAR -->
<h1>3. Hüquqi Aktlar — Sənədlər Siyahısı</h1>
<div class="img-wrap"><img src="${imgB64('04_huquqi_aktlar_main.jpg')}"></div>
<div class="img-cap">Şəkil 6: Hüquqi Aktlar siyahısı</div>
<p>Bu səhifədə bütün fərman, əmr və sərəncamlar cədvəl şəklindədir. Hər sıra bir sənədi göstərir. Sütun başlığına bassanız həmin sütuna görə sıralama edə bilərsiniz.</p>

<h2>Sənəd Axtarmaq</h2>
<div class="img-wrap"><img src="${imgB64('06_filter_panel.jpg')}"></div>
<div class="img-cap">Şəkil 7: Axtarış (filtr) sahələri</div>
<p>Sənədi tapmaq üçün axtarış sahələrindən istifadə edin — nömrəsini, mövzusunu yazın ya da tarix aralığı seçin. Sonra <strong>Axtarış</strong> düyməsinə basın. Sıfırlamaq üçün <strong>×</strong> düyməsinə basın.</p>
<div class="tip">💡 Excel və ya Word düymələrinə basaraq cədvəli fayl kimi yükləyə bilərsiniz.</div>

<h2>Yeni Sənəd Əlavə Etmək</h2>
<div class="img-wrap"><img src="${imgB64('07_yeni_senad_modal_top.jpg')}"></div>
<div class="img-cap">Şəkil 8: Yeni sənəd pəncərəsi — üst hissə</div>
<div class="img-wrap"><img src="${imgB64('07b_yeni_senad_modal_bottom.jpg')}"></div>
<div class="img-cap">Şəkil 9: Yeni sənəd pəncərəsi — alt hissə</div>
<p>Sağ yuxarıdakı yaşıl <strong>Yeni əlavə et</strong> düyməsinə basın. Açılan pəncərədə <strong>*</strong> ilə işarəli sahələr mütləq doldurulmalıdır: nömrə, tarix, növü, kim qəbul edib, əsas icraçı. Doldurun, <strong>Yarat</strong> düyməsinə basın.</p>
<div class="warn">⚠️ Əsas icraçı seçilməzsə sənəd saxlanılmır.</div>

<!-- 4. İCRAÇI PANELİ -->
<h1>4. İcraçı Paneli — Tapşırıqlarım</h1>
<div class="img-wrap"><img src="${imgB64('08_icraci_paneli_admin.jpg')}"></div>
<div class="img-cap">Şəkil 10: İcraçı Paneli — tapşırıqlar cədvəli</div>
<p>Burada sizə tapşırılmış işlər görünür. Hər sıranın rəngi tapşırığın vəziyyətini bildirir (bax: Bölmə 6). Sıranın sonundakı <strong>👁 göz işarəsi</strong> detalları görməyə, <strong>✏️ karandaş işarəsi</strong> isə hesabat göndərməyə xidmət edir.</p>
<div class="img-wrap"><img src="${imgB64('13_icraci_oz_paneli.jpg')}"></div>
<div class="img-cap">Şəkil 11: İcraçının öz paneli</div>
<p>Tapşırığın tam məlumatlarına baxmaq üçün göz işarəsinə basın:</p>
<div class="img-wrap"><img src="${imgB64('09_tapshiriq_detallari.jpg')}"></div>
<div class="img-cap">Şəkil 12: Tapşırıq detalları — sənəd və icra tarixçəsi</div>

<!-- 5. HESABAT GÖNDƏRMƏK -->
<h1>5. İcra Hesabatı Göndərmək</h1>
<p>Tapşırığı tamamladıqdan sonra <strong>karandaş işarəsinə (✏️)</strong> basın:</p>
<div class="img-wrap"><img src="${imgB64('14_status_bildirmek.jpg')}"></div>
<div class="img-cap">Şəkil 13: Hesabat göndərmə pəncərəsi</div>
<div class="img-wrap"><img src="${imgB64('14b_status_secilmis.jpg')}"></div>
<div class="img-cap">Şəkil 14: Status seçilmiş vəziyyət</div>
<div class="steps">
  <div class="step"><div class="step-num">1.</div><div>"Standart qeyd" açılır siyahısından iş vəziyyətini seçin — "İcra olunub", "Qismən icra olunub" və s.</div></div>
  <div class="step"><div class="step-num">2.</div><div>İstəsəniz "Sərbəst qeyd" sahəsinə əlavə izahat yazın</div></div>
  <div class="step"><div class="step-num">3.</div><div>Tələb olunursa "Faylları seçin" düyməsinə basıb sübut faylı əlavə edin</div></div>
  <div class="step"><div class="step-num">4.</div><div>"Təsdiqlə" düyməsinə basın — hesabat menecerinizə göndərildi</div></div>
</div>
<div class="warn">⚠️ Tapşırıq "Sübut sənəd məcburidir" kimi işarəlibsə, fayl əlavə etmədən "İcra olunub" seçmək olmaz.</div>
<p>Hesabat göndərildikdən sonra tapşırıq <strong>narıncı rəngə</strong> dönür — menecer təsdiqini gözləyir. Menecer təsdiqləsə yaşıl, rədd etsə yenidən aktiv olur.</p>

<!-- 6. TƏSDİQ GÖZLƏYƏNLƏr -->
<h1>6. Təsdiq Gözləyənlər (Menecerlər üçün)</h1>
<div class="img-wrap"><img src="${imgB64('10_tesdiq_gozleyanler.jpg')}"></div>
<div class="img-cap">Şəkil 15: Gələn hesabatlar — menecer görünüşü</div>
<p>İcraçıların göndərdiyi hesabatlar burada görünür. Hər sırada icraçının qeydi və əlavə faylları var. <strong>Təsdiq et (✓)</strong> bassanız tapşırıq bağlanır, <strong>Rədd et (✗)</strong> bassanız icraçıya geri qaytarılır — rədd səbəbini yazın.</p>

<!-- 7. HESABAT -->
<h1>7. Hesabat Səhifəsi (Menecerlər üçün)</h1>
<div class="img-wrap"><img src="${imgB64('11_hesabat_yuxari.jpg')}"></div>
<div class="img-cap">Şəkil 16: Hesabat — statistika kartları</div>
<p>Yuxarıdakı rəngli kartlar ümumi vəziyyəti göstərir: cəmi tapşırıq, icra olunmuş, gecikmiş, davam edən. Sütunlu diaqram hər icraçının, halqa diaqramı ümumi paylaşımı göstərir.</p>
<div class="img-wrap"><img src="${imgB64('11b_hesabat_asagi.jpg')}"></div>
<div class="img-cap">Şəkil 17: Hesabat — icraçılar üzrə detallı statistika</div>
<p>Cədvəldə hər icraçının tamamlama faizi görünür. "Bütün idarələr" siyahısından müəyyən idarəni seçib filtrləmək olar. "Excel" düyməsi ilə yükləmək mümkündür.</p>

<!-- 8. RƏNG KODLARI -->
<h1>8. Sıra Rəngləri — Tapşırığın Vəziyyəti</h1>
<p>Cədvəldəki sıranın rəngi tapşırığın hazırkı vəziyyətini göstərir:</p>
<table>
  <tr><th>Rəng</th><th>Mənası</th></tr>
  <tr><td style="background:#ffffff">Ağ</td><td>Aktiv tapşırıq, son tarix hələ uzaqdır</td></tr>
  <tr><td style="background:#fef9c3">Sarı</td><td>Son tarixə 3 gün və ya az qalıb — tezləşin!</td></tr>
  <tr><td style="background:#fee2e2">Qırmızı</td><td>Son tarix keçib — gecikmiş tapşırıq</td></tr>
  <tr><td style="background:#dcfce7">Yaşıl</td><td>Tamamlanmış və təsdiq edilmiş tapşırıq</td></tr>
  <tr><td style="background:#fed7aa">Narıncı</td><td>Hesabat göndərilib, menecer təsdiq gözlənilir</td></tr>
  <tr><td style="background:#bfdbfe">Açıq göy</td><td>Bir neçə icraçıdan yalnız bir hissəsi hesabat göndərib</td></tr>
</table>

<!-- 9. ŞİFRƏ -->
<h1>9. Parol Dəyişmək</h1>
<div class="img-wrap"><img src="${imgB64('12_sifre_deyis.jpg')}"></div>
<div class="img-cap">Şəkil 18: Parol dəyişmə pəncərəsi</div>
<p>Sol menyunun altındakı <strong>Şifrəni dəyiş</strong> düyməsinə basın. Cari parolu, yeni parolu və yenə yeni parolu yazıb <strong>Deyiş</strong> düyməsinə basın.</p>
<div class="imp">❗ Yeni parolu yadda saxlayın. Unutsanız, administrator sıfırlamalıdır.</div>

<!-- 10. ÇIXIŞ -->
<h1>10. Sistemdən Çıxmaq</h1>
<p>Sol menyunun altındakı qırmızı <strong>Çıxış</strong> düyməsinə basın. Sistem sizi giriş səhifəsinə yönləndirəcək.</p>
<div class="warn">⚠️ Brauzeri bağlamaq sistemdən çıxmaq sayılmır. Həmişə "Çıxış" düyməsindən istifadə edin.</div>

<!-- FAQ -->
<h1>Tez-tez Verilən Suallar</h1>
<p class="faq-q">Şifrəmi unutmuşam, nə edim?</p>
<p class="faq-a">Administrator ilə əlaqə saxlayın. Sistem özü parol sıfırlama etmir.</p>
<p class="faq-q">İcraçı Panelim boşdur, niyə?</p>
<p class="faq-a">Hesabınıza hələ tapşırıq verilməyib. Menecerinizlə yoxlayın.</p>
<p class="faq-q">Hesabat göndərdim amma tapşırıq hələ açıqdır?</p>
<p class="faq-a">Normaldir. Narıncı rəng menecer təsdiqini gözlədiyini bildirir. Menecer təsdiqləyəndə yaşıl olacaq.</p>
<p class="faq-q">"Yeni əlavə et" düyməsi görünmür.</p>
<p class="faq-a">Bu düymə yalnız müvafiq icazəsi olan hesablarda görünür. Administratora müraciət edin.</p>
<p class="faq-q">"Təsdiq Gözləyənlər" və "Hesabat" bölmələri görünmür.</p>
<p class="faq-a">Bu bölmələr yalnız menecer rolundakı istifadəçilərdə görünür.</p>
<p class="faq-q">Fayl yükləmək istəyirəm, alınmır.</p>
<p class="faq-a">Sistem PDF, Word (.docx), JPG və PNG qəbul edir. Hər fayl max 10 MB, max 10 fayl.</p>

<br><br>
<p style="text-align:center; color:#9ca3af; font-style:italic; font-size:10pt;">
  DMS — Sənəd İdarəetmə Sistemi &nbsp;|&nbsp; İstifadəçi Təlimatı &nbsp;|&nbsp; May 2026
</p>

</div>
</body>
</html>`;

// Write HTML temporarily
const htmlPath = path.join(__dirname, '_tmp_guide.html');
fs.writeFileSync(htmlPath, html, 'utf8');

console.log('HTML hazırlandı, PDF-ə çevrilir...');

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox']
});
const page = await browser.newPage();
await page.goto(`file:///${htmlPath}`, {waitUntil: 'networkidle0'});
await page.pdf({
  path: OUT,
  format: 'A4',
  margin: {top:'18mm', right:'18mm', bottom:'18mm', left:'18mm'},
  printBackground: true,
  displayHeaderFooter: false,
});
await browser.close();

// Cleanup
fs.unlinkSync(htmlPath);

const size = Math.round(fs.statSync(OUT).size / 1024);
console.log('✅ PDF hazır:', OUT);
console.log('   Ölçü:', size, 'KB');
