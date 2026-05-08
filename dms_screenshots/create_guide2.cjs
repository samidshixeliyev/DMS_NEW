'use strict';
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  ImageRun, Header, Footer, AlignmentType, HeadingLevel, BorderStyle,
  WidthType, ShadingType, PageNumber, PageBreak, LevelFormat, TableOfContents
} = require('docx');
const fs = require('fs');
const path = require('path');

const IMG = path.join(__dirname, 'images2');
const OUT = 'C:\\Users\\samid.sixaliyev\\Desktop\\workspace\\DMS_NEW\\dms-app\\DMS_Telimat_Sade.docx';

const PW = 11906, PH = 16838, MRG = 1080;
const CW = PW - MRG * 2;

const NAVY = '1a3a5c';
const BLUE = '2563eb';
const GRAY = '4b5563';

let figNo = 0;
const F = () => ++figNo;

function thin(color='cccccc'){ return {style:BorderStyle.SINGLE, size:2, color}; }

function img(file, caption) {
  const data = fs.readFileSync(path.join(IMG, file));
  const wPx = Math.round(CW * 96 / 1440);
  const hPx = Math.round(wPx * 900 / 1440);
  return [
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: {before:160, after:60},
      border: {top:thin('d1d5db'),bottom:thin('d1d5db'),left:thin('d1d5db'),right:thin('d1d5db')},
      children: [new ImageRun({type:'jpg', data,
        transformation:{width:wPx, height:hPx},
        altText:{title:caption, description:caption, name:caption}})]
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: {before:0, after:240},
      children: [new TextRun({text:`Şəkil ${F()}: ${caption}`, italics:true, size:18, color:'6b7280', font:'Arial'})]
    })
  ];
}

function h1(txt) {
  return new Paragraph({heading:HeadingLevel.HEADING_1, pageBreakBefore:true,
    spacing:{before:0, after:200},
    children:[new TextRun({text:txt, bold:true, size:40, color:NAVY, font:'Arial'})]});
}
function h2(txt) {
  return new Paragraph({heading:HeadingLevel.HEADING_2, spacing:{before:320, after:140},
    children:[new TextRun({text:txt, bold:true, size:28, color:NAVY, font:'Arial'})]});
}
function p(txt, color='111827') {
  return new Paragraph({alignment:AlignmentType.LEFT, spacing:{before:60, after:120},
    children:[new TextRun({text:txt, size:22, font:'Arial', color})]});
}
function b(txt) {
  return new Paragraph({numbering:{reference:'bul',level:0}, spacing:{before:50, after:50},
    children:[new TextRun({text:txt, size:22, font:'Arial', color:'374151'})]});
}
function step(n, txt) {
  return new Paragraph({spacing:{before:60, after:60},
    children:[
      new TextRun({text:`${n}.  `, bold:true, size:22, font:'Arial', color:BLUE}),
      new TextRun({text:txt, size:22, font:'Arial', color:'111827'})
    ]});
}
function note(icon, txt, bg, border, textColor) {
  const lb = {style:BorderStyle.SINGLE, size:12, color:border};
  const sb = {style:BorderStyle.SINGLE, size:2, color:border};
  return new Table({width:{size:CW, type:WidthType.DXA}, columnWidths:[CW],
    rows:[new TableRow({children:[new TableCell({
      borders:{top:sb, bottom:sb, left:lb, right:{style:BorderStyle.NONE}},
      shading:{fill:bg, type:ShadingType.CLEAR},
      margins:{top:100,bottom:100,left:180,right:140}, width:{size:CW, type:WidthType.DXA},
      children:[new Paragraph({spacing:{before:0,after:0},
        children:[
          new TextRun({text:icon+' ', bold:true, size:20, font:'Arial', color:border}),
          new TextRun({text:txt, size:20, font:'Arial', color:textColor})
        ]})]
    })]})]}
  );
}
function tip(txt)  { return note('💡', txt, 'eff6ff','2563eb','1e40af'); }
function warn(txt) { return note('⚠️', txt, 'fffbeb','d97706','92400e'); }
function imp(txt)  { return note('❗', txt, 'fef2f2','dc2626','991b1b'); }
function spacer(n=1){ return new Paragraph({spacing:{before:0,after:n*60}, children:[new TextRun('')]}); }
function pb2(){ return new Paragraph({children:[new PageBreak()]}); }

// ── Color legend table ────────────────────────────────────────────────────
function colorLegend(){
  const rows_data = [
    ['ffffff','Ağ','Aktiv tapşırıq, son tarix hələ uzaqdır'],
    ['fef9c3','Sarı','Son tarixə 3 gün və ya az qalıb'],
    ['fee2e2','Qırmızı','Son tarix keçib — gecikmiş tapşırıq'],
    ['dcfce7','Yaşıl','Tamamlanmış və təsdiq edilmiş tapşırıq'],
    ['fed7aa','Narıncı','Hesabat göndərilib, menecer təsdiq gözlənilir'],
    ['bfdbfe','Açıq göy','Bir neçə icraçıdan yalnız bir hissəsi hesabat göndərib'],
  ];
  const cb={style:BorderStyle.SINGLE,size:1,color:'e5e7eb'};
  const hb={style:BorderStyle.SINGLE,size:6,color:'334155'};
  const header = new TableRow({children:[
    new TableCell({borders:{top:cb,bottom:hb,left:cb,right:cb}, shading:{fill:'1e3a5f',type:ShadingType.CLEAR},
      width:{size:2000,type:WidthType.DXA}, margins:{top:80,bottom:80,left:100,right:100},
      children:[new Paragraph({alignment:AlignmentType.CENTER, children:[new TextRun({text:'Rəng',bold:true,size:20,font:'Arial',color:'FFFFFF'})]})]}),
    new TableCell({borders:{top:cb,bottom:hb,left:cb,right:cb}, shading:{fill:'1e3a5f',type:ShadingType.CLEAR},
      width:{size:CW-2000,type:WidthType.DXA}, margins:{top:80,bottom:80,left:100,right:100},
      children:[new Paragraph({children:[new TextRun({text:'Mənası',bold:true,size:20,font:'Arial',color:'FFFFFF'})]})]})
  ]});
  const rows = rows_data.map(([fill,label,desc]) =>
    new TableRow({children:[
      new TableCell({borders:{top:cb,bottom:cb,left:cb,right:cb}, shading:{fill,type:ShadingType.CLEAR},
        width:{size:2000,type:WidthType.DXA}, margins:{top:80,bottom:80,left:100,right:100},
        children:[new Paragraph({alignment:AlignmentType.CENTER, children:[new TextRun({text:label,size:20,font:'Arial'})]})]}),
      new TableCell({borders:{top:cb,bottom:cb,left:cb,right:cb}, shading:{fill:'ffffff',type:ShadingType.CLEAR},
        width:{size:CW-2000,type:WidthType.DXA}, margins:{top:80,bottom:80,left:100,right:100},
        children:[new Paragraph({children:[new TextRun({text:desc,size:20,font:'Arial'})]})]})
    ]})
  );
  return new Table({width:{size:CW,type:WidthType.DXA}, columnWidths:[2000,CW-2000], rows:[header,...rows]});
}

// ══════════════════════════════════════════════════════════════════
//  CONTENT
// ══════════════════════════════════════════════════════════════════
const children = [

// COVER
  spacer(20),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:800,after:200},
    children:[new TextRun({text:'DMS', bold:true, size:120, color:NAVY, font:'Arial'})]}),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:0,after:160},
    children:[new TextRun({text:'Sənəd İdarəetmə Sistemi', bold:true, size:42, color:NAVY, font:'Arial'})]}),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:0,after:160},
    children:[new TextRun({text:'İstifadəçi Təlimatı', bold:true, size:48, color:BLUE, font:'Arial'})]}),
  spacer(6),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:0,after:0},
    children:[new TextRun({text:'May 2026', size:22, color:'9ca3af', font:'Arial'})]}),

  pb2(),

// MÜNDƏRİCAT
  new Paragraph({heading:HeadingLevel.HEADING_1, pageBreakBefore:false, spacing:{before:0,after:200},
    children:[new TextRun({text:'Mündəricat', bold:true, size:40, color:NAVY, font:'Arial'})]}),
  new TableOfContents('Mündəricat', {hyperlink:true, headingStyleRange:'1-2'}),
  pb2(),

// ── GİRİŞ ──────────────────────────────────────────────────────────
  h1('1. Sistemə Giriş'),
  p('Brauzerdə (Chrome, Firefox, Brave) sistemin ünvanını açın. Giriş səhifəsi görünəcək:'),
  spacer(1),
  ...img('01_login_page.jpg', 'Giriş səhifəsi'),
  p('İstifadəçi adı və şifrənizi yazıb "Daxil ol" düyməsinə basın. Şifrəni görmək üçün sahənin sağındakı göz işarəsinə basa bilərsiniz.'),
  spacer(1),
  ...img('02_login_filled.jpg', 'İstifadəçi adı daxil edilib'),
  spacer(1),
  warn('Şifrə və ya istifadəçi adı yanlış olduqda aşağıdakı xəta görünür — sadəcə yenidən düzgün yazın:'),
  spacer(1),
  ...img('03_login_error.jpg', 'Yanlış giriş məlumatı — xəta mesajı'),
  spacer(1),
  imp('Şifrənizi unutmusunuzsa, administrator ilə əlaqə saxlayın. Sistem özü sıfırlama etmir.'),

// ── ANA EKRAN ──────────────────────────────────────────────────────
  h1('2. Ana Ekran və Menyu'),
  ...img('04_huquqi_aktlar_main.jpg', 'Sistemə daxil olduqdan sonra əsas ekran'),
  p('Sistemə daxil olduqdan sonra sol tərəfdə menyu görünür. Buradan istənilən bölməyə keçid edə bilərsiniz:'),
  spacer(1),
  ...img('05_sidebar_navigation.jpg', 'Sol menyu — naviqasiya'),
  spacer(2),
  b('Hüquqi Aktlar — bütün sənədlər burada saxlanılır'),
  b('İcraçı Paneli — sizə tapşırılmış işlər'),
  b('Təsdiq Gözləyənlər — menecerlər üçün, gələn hesabatlar'),
  b('Hesabat — statistika (menecerlər üçün)'),
  b('Şifrəni dəyiş — parol dəyişmək'),
  b('Çıxış — sistemdən çıxmaq'),

// ── HÜQUQİ AKTLAR ──────────────────────────────────────────────────
  h1('3. Hüquqi Aktlar — Sənədlər Siyahısı'),
  ...img('04_huquqi_aktlar_main.jpg', 'Hüquqi Aktlar siyahısı'),
  p('Bu səhifədə bütün fərman, əmr və sərəncamlar cədvəl şəklindədir. Hər sıra bir sənədi göstərir. Sütun başlığına bassanız həmin sütuna görə sıralama edə bilərsiniz.'),

  h2('Sənəd Axtarmaq'),
  ...img('06_filter_panel.jpg', 'Axtarış (filtr) sahələri'),
  p('Sənədi tapmaq üçün yuxarıdakı axtarış sahələrindən istifadə edin — nömrəsini, mövzusunu yazın, ya da tarix aralığı seçin. Sonra "Axtarış" düyməsinə basın. Sıfırlamaq üçün "×" düyməsinə basın.'),
  spacer(1),
  tip('Excel və ya Word düymələrinə basaraq cədvəli fayl kimi yükləyə bilərsiniz. Aktiv filtrlər ixraca da tətbiq olunur.'),

  h2('Yeni Sənəd Əlavə Etmək'),
  ...img('07_yeni_senad_modal_top.jpg', 'Yeni sənəd pəncərəsi — üst hissə'),
  ...img('07b_yeni_senad_modal_bottom.jpg', 'Yeni sənəd pəncərəsi — alt hissə'),
  p('Sağ yuxarıdakı yaşıl "Yeni əlavə et" düyməsinə basın. Açılan pəncərədə "*" ilə işarəli sahələr mütləq doldurulmalıdır: nömrə, tarix, növü, kim qəbul edib, əsas icraçı. Doldurun, "Yarat" düyməsinə basın.'),
  spacer(1),
  warn('Əsas icraçı seçilməzsə sənəd saxlanılmır.'),

// ── İCRAÇI PANELİ ──────────────────────────────────────────────────
  h1('4. İcraçı Paneli — Tapşırıqlarım'),
  ...img('08_icraci_paneli_admin.jpg', 'İcraçı Paneli — tapşırıqlar cədvəli'),
  p('Burada sizə tapşırılmış işlər görünür. Hər sıranın rəngi tapşırığın vəziyyətini bildirir (bax: Bölmə 6). Sıranın sonundakı göz işarəsi (👁) — detalları görməyə, karandaş işarəsi (✏️) — hesabat göndərməyə xidmət edir.'),
  spacer(1),
  ...img('13_icraci_oz_paneli.jpg', 'İcraçının öz paneli — şəxsi tapşırıqlar'),
  p('Tapşırığın tam məlumatlarına baxmaq üçün göz işarəsinə basın:'),
  ...img('09_tapshiriq_detallari.jpg', 'Tapşırıq detalları — sənəd və icra tarixçəsi'),

// ── HESABAT GÖNDƏRMƏK ────────────────────────────────────────────────
  h1('5. İcra Hesabatı Göndərmək'),
  p('Tapşırığı tamamladıqdan sonra karandaş işarəsinə (✏️) basın:'),
  ...img('14_status_bildirmek.jpg', 'Hesabat göndərmə pəncərəsi'),
  ...img('14b_status_secilmis.jpg', 'Status seçilmiş vəziyyət'),
  spacer(1),
  step(1, '"Standart qeyd" açılır siyahısından iş vəziyyətini seçin — "İcra olunub", "Qismən icra olunub" və s.'),
  step(2, 'İstəsəniz "Sərbəst qeyd" sahəsinə əlavə izahat yazın'),
  step(3, 'Tələb olunursa "Faylları seçin" düyməsinə basıb sübut faylı əlavə edin'),
  step(4, '"Təsdiqlə" düyməsinə basın — hesabat menecerinizə göndərildi'),
  spacer(2),
  warn('Tapşırıq "Sübut sənəd məcburidir" kimi işarəlibsə, "İcra olunub" seçərkən mütləq fayl əlavə edin.'),
  spacer(1),
  p('Hesabat göndərildikdən sonra tapşırıq narıncı rəngə dönür — menecer təsdiqini gözləyir. Menecer təsdiqləsə yaşıl, rədd etsə yenidən aktiv olur.'),

// ── TƏSDİQ GÖZLƏYƏNLƏr ──────────────────────────────────────────────
  h1('6. Təsdiq Gözləyənlər (Menecerlər üçün)'),
  ...img('10_tesdiq_gozleyanler.jpg', 'Gələn hesabatlar — menecer görünüşü'),
  p('İcraçıların göndərdiyi hesabatlar burada görünür. Hər sırada icraçının qeydi və əlavə faylları var. "Təsdiq et" (✓) bassanız tapşırıq bağlanır, "Rədd et" (✗) bassanız icraçıya geri qaytarılır — rədd səbəbini yazın.'),

// ── HESABAT ──────────────────────────────────────────────────────────
  h1('7. Hesabat Səhifəsi (Menecerlər üçün)'),
  ...img('11_hesabat_yuxari.jpg', 'Hesabat — statistika kartları'),
  p('Yuxarıdakı rəngli kartlar ümumi vəziyyəti göstərir: cəmi tapşırıq, icra olunmuş, gecikmiş, davam edən. Sütunlu diaqram hər icraçının, halqa diaqramı isə ümumi paylaşımını göstərir.'),
  ...img('11b_hesabat_asagi.jpg', 'Hesabat — icraçılar üzrə detallı statistika'),
  p('Cədvəldə hər icraçının tamamlama faizi görünür. "Bütün idarələr" siyahısından müəyyən idarəni seçib filtrləmək olar. "Excel" düyməsi ilə yükləmək mümkündür.'),

// ── RƏNG KODLARI ────────────────────────────────────────────────────
  h1('6. Sıra Rəngləri — Tapşırığın Vəziyyəti'),
  p('Cədvəldəki sıranın rəngi tapşırığın hazırkı vəziyyətini göstərir:'),
  spacer(1),
  colorLegend(),

// ── ŞİFRƏ ─────────────────────────────────────────────────────────
  h1('8. Parol Dəyişmək'),
  ...img('12_sifre_deyis.jpg', 'Parol dəyişmə pəncərəsi'),
  p('Sol menyunun altındakı "Şifrəni dəyiş" düyməsinə basın. Cari parolu, yeni parolu və yenə yeni parolu yazıb "Deyiş" düyməsinə basın.'),
  spacer(1),
  imp('Yeni parolu yadda saxlayın. Unutsanız, administrator sıfırlamalıdır.'),

// ── ÇIXIŞ ─────────────────────────────────────────────────────────
  h1('9. Sistemdən Çıxmaq'),
  p('Sol menyunun altındakı qırmızı "Çıxış" düyməsinə basın. Sistem sizi giriş səhifəsinə yönləndirəcək.'),
  spacer(1),
  warn('Brauzeri bağlamaq sistemdən çıxmaq sayılmır. Həmişə "Çıxış" düyməsindən istifadə edin — xüsusilə paylaşılan kompüterlərdə.'),

// ── FAQ ────────────────────────────────────────────────────────────
  h1('Tez-tez Verilən Suallar'),

  spacer(1),
  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'Şifrəmi unutmuşam, nə edim?', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Administrator ilə əlaqə saxlayın. Sistem özü parol sıfırlama etmir.'),

  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'İcraçı Panelim boşdur, niyə?', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Sizin hesabınıza hələ tapşırıq verilməyib, yaxud tapşırıq başqa hesaba göndərilib. Menecerinizlə yoxlayın.'),

  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'Hesabat göndərdim amma tapşırıq hələ açıqdır, niyə?', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Normaldir. Hesabat göndərildikdən sonra tapşırıq narıncı rənglə menecer təsdiqini gözləyir. Menecer təsdiqləyəndə yaşıl olacaq.'),

  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'"Yeni əlavə et" düyməsi görünmür.', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Bu düymə yalnız müvafiq icazəsi olan hesablarda görünür. Administratora müraciət edin.'),

  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'"Təsdiq Gözləyənlər" və "Hesabat" bölmələri görünmür.', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Bu bölmələr yalnız menecer rolundakı istifadəçilərdə görünür.'),

  new Paragraph({spacing:{before:180,after:60}, children:[new TextRun({text:'Fayl yükləmək istəyirəm, alınmır.', bold:true, size:22, font:'Arial', color:NAVY})]}),
  p('Sistem PDF, Word (.docx), JPG və PNG qəbul edir. Hər fayl max 10 MB, max 10 fayl.'),

  spacer(4),
  new Paragraph({alignment:AlignmentType.CENTER, spacing:{before:400,after:0},
    children:[new TextRun({text:'DMS — Sənəd İdarəetmə Sistemi  |  İstifadəçi Təlimatı  |  May 2026', size:18, color:'9ca3af', font:'Arial', italics:true})]})
];

// ── DOCUMENT ────────────────────────────────────────────────────────────────
const borderLine = {style:BorderStyle.SINGLE, size:6, color:'1a3a5c', space:4};

const doc = new Document({
  numbering: {config:[
    {reference:'bul', levels:[{level:0, format:LevelFormat.BULLET, text:'•',
      alignment:AlignmentType.LEFT,
      style:{paragraph:{indent:{left:500, hanging:260}}}}]},
  ]},
  styles: {
    default: {document:{run:{font:'Arial', size:22}}},
    paragraphStyles: [
      {id:'Heading1', name:'Heading 1', basedOn:'Normal', next:'Normal', quickFormat:true,
        run:{size:40, bold:true, font:'Arial', color:NAVY},
        paragraph:{spacing:{before:480, after:200}, outlineLevel:0}},
      {id:'Heading2', name:'Heading 2', basedOn:'Normal', next:'Normal', quickFormat:true,
        run:{size:28, bold:true, font:'Arial', color:NAVY},
        paragraph:{spacing:{before:300, after:140}, outlineLevel:1}},
    ]
  },
  sections:[{
    properties:{page:{size:{width:PW, height:PH},
      margin:{top:MRG, right:MRG, bottom:MRG, left:MRG}}},
    headers:{default: new Header({children:[
      new Paragraph({alignment:AlignmentType.RIGHT,
        border:{bottom:borderLine}, spacing:{after:120},
        children:[new TextRun({text:'DMS — İstifadəçi Təlimatı', size:18, color:'6b7280', font:'Arial'})]})
    ]})},
    footers:{default: new Footer({children:[
      new Paragraph({alignment:AlignmentType.CENTER,
        border:{top:borderLine}, spacing:{before:120},
        children:[
          new TextRun({text:'Səhifə ', size:18, color:'6b7280', font:'Arial'}),
          new TextRun({children:[PageNumber.CURRENT], size:18, color:'6b7280', font:'Arial'}),
          new TextRun({text:' / ', size:18, color:'6b7280', font:'Arial'}),
          new TextRun({children:[PageNumber.TOTAL_PAGES], size:18, color:'6b7280', font:'Arial'}),
        ]})
    ]})},
    children
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUT, buf);
  console.log('✅ Hazır:', OUT);
  console.log('   Ölçü:', Math.round(buf.length/1024), 'KB');
}).catch(err => { console.error('❌ Xəta:', err.message); process.exit(1); });
