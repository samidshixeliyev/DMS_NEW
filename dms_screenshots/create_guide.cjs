'use strict';
const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  ImageRun, Header, Footer, AlignmentType, HeadingLevel, BorderStyle,
  WidthType, ShadingType, PageNumber, PageBreak, LevelFormat, TableOfContents
} = require('docx');
const fs = require('fs');
const path = require('path');

// ── paths ──────────────────────────────────────────────────────────────────
const IMG  = path.join(__dirname, 'images2');
const OUT  = 'C:\\Users\\samid.sixaliyev\\Desktop\\workspace\\DMS_NEW\\dms-app\\DMS_Maliyyeci_Telimat.docx';

// ── page geometry (A4) ────────────────────────────────────────────────────
const PW = 11906, PH = 16838, MRG = 1080;
const CW = PW - MRG * 2;   // 9746 DXA ≈ 17.1 cm

// ── colours ───────────────────────────────────────────────────────────────
const NAVY   = '1a3a5c';
const BLUE   = '2563eb';
const GRAY   = '4b5563';
const WHITE  = 'FFFFFF';

// ── helpers ───────────────────────────────────────────────────────────────
function img(file, captionText, figNo) {
  const data = fs.readFileSync(path.join(IMG, file));
  // original 1440×900 → fit to content width 9746 DXA
  // 9746 / 1440 * 914400 EMU → height proportionally
  const wEMU = Math.round(CW / 1440 * 914400);
  const hEMU = Math.round(wEMU * 900 / 1440);
  // docx ImageRun uses PIXELS at 96 dpi: 1 DXA = 1/1440 inch * 96px = 0.0667px
  const wPx  = Math.round(CW * 96 / 1440);
  const hPx  = Math.round(wPx * 900 / 1440);
  return [
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing:   { before: 140, after: 60 },
      border:    { top: thin('d1d5db'), bottom: thin('d1d5db'), left: thin('d1d5db'), right: thin('d1d5db') },
      children: [ new ImageRun({ type:'jpg', data,
          transformation:{ width: wPx, height: hPx },
          altText:{ title: captionText, description: captionText, name: captionText } }) ]
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing:   { before: 0, after: 280 },
      children: [ new TextRun({ text: `Şəkil ${figNo}: ${captionText}`,
          italics: true, size: 18, color: '6b7280', font: 'Arial' }) ]
    })
  ];
}

function thin(color='cccccc'){ return { style: BorderStyle.SINGLE, size: 2, color }; }

// Title / Heading
function title(txt){ return new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:200},
  children:[new TextRun({ text:txt, bold:true, size:72, color:NAVY, font:'Arial'})] }); }
function subtitle(txt){ return new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:160},
  children:[new TextRun({ text:txt, bold:false, size:36, color:BLUE, font:'Arial'})] }); }

function h1(txt){ return new Paragraph({ heading: HeadingLevel.HEADING_1,
  pageBreakBefore: true, spacing:{before:0,after:220},
  children:[new TextRun({ text:txt, bold:true, size:40, color:NAVY, font:'Arial'})] }); }
function h2(txt){ return new Paragraph({ heading: HeadingLevel.HEADING_2,
  spacing:{before:340,after:160},
  children:[new TextRun({ text:txt, bold:true, size:30, color:NAVY, font:'Arial'})] }); }
function h3(txt){ return new Paragraph({ heading: HeadingLevel.HEADING_3,
  spacing:{before:240,after:100},
  children:[new TextRun({ text:txt, bold:true, size:24, color:'1e40af', font:'Arial'})] }); }

// Plain paragraph
function p(txt, opts={}){
  return new Paragraph({ alignment: opts.center ? AlignmentType.CENTER : AlignmentType.JUSTIFIED,
    spacing:{before:60, after: opts.after||120},
    children:[new TextRun({ text:txt, size: opts.size||22, font:'Arial',
        bold: opts.bold, color: opts.color||'111827' })] });
}
// Bold + normal inline
function pb(bold, normal, after=120){
  return new Paragraph({ alignment: AlignmentType.LEFT, spacing:{before:60,after},
    children:[
      new TextRun({ text: bold+' ', bold:true, size:22, font:'Arial', color:'111827' }),
      new TextRun({ text: normal, size:22, font:'Arial', color:'374151' })
    ] });
}
function spacer(n=1){ return new Paragraph({spacing:{before:0,after:n*60}, children:[new TextRun({text:''})]}); }
function pb2(){ return new Paragraph({ children:[new PageBreak()] }); }

// Bullets
function b(txt, bold_part=''){
  return new Paragraph({ numbering:{reference:'bul',level:0}, spacing:{before:40,after:40},
    children: bold_part
      ? [new TextRun({text:bold_part+' ', bold:true, size:22, font:'Arial'}),
         new TextRun({text:txt, size:22, font:'Arial', color:'374151'})]
      : [new TextRun({text:txt, size:22, font:'Arial', color:'374151'})] });
}
// Numbered steps
function st(txt, detail=''){
  return new Paragraph({ numbering:{reference:'num',level:0}, spacing:{before:80,after:80},
    children: detail
      ? [new TextRun({text:txt, bold:true, size:22, font:'Arial', color:'111827'}),
         new TextRun({text:' — '+detail, size:22, font:'Arial', color:'374151'})]
      : [new TextRun({text:txt, bold:true, size:22, font:'Arial', color:'111827'})] });
}

// Section label ("Nə görəcəksiniz?" / "Nə etməlisiniz?")
function sectionLabel(txt, color='1e40af'){
  return new Paragraph({ spacing:{before:200,after:60},
    children:[new TextRun({text:txt, bold:true, size:24, color, font:'Arial'})] });
}

// Info box (blue)
function info(txt){
  const b={style:BorderStyle.SINGLE,size:3,color:'93c5fd'};
  const lb={style:BorderStyle.SINGLE,size:14,color:'2563eb'};
  return new Table({ width:{size:CW,type:WidthType.DXA}, columnWidths:[CW],
    rows:[new TableRow({ children:[new TableCell({
        borders:{top:b,bottom:b,left:lb,right:{style:BorderStyle.NONE}},
        shading:{fill:'eff6ff',type:ShadingType.CLEAR},
        margins:{top:120,bottom:120,left:200,right:160}, width:{size:CW,type:WidthType.DXA},
        children:[new Paragraph({ spacing:{before:0,after:0},
          children:[
            new TextRun({text:'💡 Məsləhət: ', bold:true, size:20, font:'Arial', color:'1e40af'}),
            new TextRun({text:txt, size:20, font:'Arial', color:'1e3a8a'})
          ] })]
      })]
    })]
  });
}
// Warning box (yellow)
function warn(txt){
  const b={style:BorderStyle.SINGLE,size:3,color:'fcd34d'};
  const lb={style:BorderStyle.SINGLE,size:14,color:'d97706'};
  return new Table({ width:{size:CW,type:WidthType.DXA}, columnWidths:[CW],
    rows:[new TableRow({ children:[new TableCell({
        borders:{top:b,bottom:b,left:lb,right:{style:BorderStyle.NONE}},
        shading:{fill:'fffbeb',type:ShadingType.CLEAR},
        margins:{top:120,bottom:120,left:200,right:160}, width:{size:CW,type:WidthType.DXA},
        children:[new Paragraph({ spacing:{before:0,after:0},
          children:[
            new TextRun({text:'⚠️ Diqqət: ', bold:true, size:20, font:'Arial', color:'92400e'}),
            new TextRun({text:txt, size:20, font:'Arial', color:'78350f'})
          ] })]
      })]
    })]
  });
}
// Important box (red)
function important(txt){
  const b={style:BorderStyle.SINGLE,size:3,color:'fca5a5'};
  const lb={style:BorderStyle.SINGLE,size:14,color:'dc2626'};
  return new Table({ width:{size:CW,type:WidthType.DXA}, columnWidths:[CW],
    rows:[new TableRow({ children:[new TableCell({
        borders:{top:b,bottom:b,left:lb,right:{style:BorderStyle.NONE}},
        shading:{fill:'fef2f2',type:ShadingType.CLEAR},
        margins:{top:120,bottom:120,left:200,right:160}, width:{size:CW,type:WidthType.DXA},
        children:[new Paragraph({ spacing:{before:0,after:0},
          children:[
            new TextRun({text:'❗ Vacib: ', bold:true, size:20, font:'Arial', color:'991b1b'}),
            new TextRun({text:txt, size:20, font:'Arial', color:'7f1d1d'})
          ] })]
      })]
    })]
  });
}

// Step box — "Bu addımı yerinə yetirmək üçün:" styled box
function stepBox(title_txt, steps_arr){
  const hdrB={style:BorderStyle.SINGLE,size:2,color:'bfdbfe'};
  const cb  ={style:BorderStyle.SINGLE,size:2,color:'bfdbfe'};
  const stepRows = steps_arr.map((s,i) =>
    new TableRow({ children:[new TableCell({
        borders:{top:cb,bottom:cb,left:cb,right:cb},
        shading:{fill:'f8faff',type:ShadingType.CLEAR},
        margins:{top:80,bottom:80,left:160,right:120}, width:{size:CW,type:WidthType.DXA},
        children:[new Paragraph({ spacing:{before:0,after:0},
          children:[
            new TextRun({text:`${i+1}. `, bold:true, size:22, font:'Arial', color:'1e40af'}),
            new TextRun({text:s, size:22, font:'Arial', color:'111827'})
          ] })]
      })]
    })
  );
  const hdr = new TableRow({ children:[new TableCell({
      borders:{top:hdrB,bottom:{style:BorderStyle.SINGLE,size:6,color:'3b82f6'},left:hdrB,right:hdrB},
      shading:{fill:'dbeafe',type:ShadingType.CLEAR},
      margins:{top:100,bottom:100,left:160,right:120}, width:{size:CW,type:WidthType.DXA},
      children:[new Paragraph({ spacing:{before:0,after:0},
        children:[new TextRun({text:'📋 '+title_txt, bold:true, size:22, font:'Arial', color:'1e3a8a'})] })]
    })]
  });
  return new Table({ width:{size:CW,type:WidthType.DXA}, columnWidths:[CW],
    rows:[hdr,...stepRows] });
}

// Colour legend table for Ch.8
function colorLegend(){
  const rows_data = [
    ['ffffff','⬜ Ağ (normal)','Aktiv tapşırıqdır. Son tarix hələ uzaqdır, heç bir problem yoxdur.'],
    ['fef9c3','🟡 Sarı','Son icra tarixinə 3 gün və ya daha az qalıb. Diqqət tələb edir!'],
    ['fee2e2','🔴 Qırmızı','Son tarix keçib. Tapşırıq gecikib — "İcra müddəti bitib".'],
    ['dcfce7','🟢 Yaşıl','Tapşırıq icra edilib və menecer tərəfindən təsdiqlənib.'],
    ['fed7aa','🟠 Narıncı','İcraçı hesabat göndərib, menecer təsdiqini gözləyir.'],
    ['bfdbfe','🔵 Açıq göy','Bir neçə icraçıdan yalnız bir hissəsi hesabat göndərib (qismən icra).'],
  ];
  const hBorder = {style:BorderStyle.SINGLE,size:2,color:'e2e8f0'};
  const hH = new TableRow({ children:[
    new TableCell({ borders:{top:hBorder,bottom:{style:BorderStyle.SINGLE,size:6,color:'334155'},left:hBorder,right:hBorder},
        shading:{fill:'1e3a5f',type:ShadingType.CLEAR}, width:{size:2200,type:WidthType.DXA},
        margins:{top:100,bottom:100,left:120,right:120},
        children:[new Paragraph({alignment:AlignmentType.CENTER, children:[new TextRun({text:'Rəng',bold:true,size:20,font:'Arial',color:'FFFFFF'})]})] }),
    new TableCell({ borders:{top:hBorder,bottom:{style:BorderStyle.SINGLE,size:6,color:'334155'},left:hBorder,right:hBorder},
        shading:{fill:'1e3a5f',type:ShadingType.CLEAR}, width:{size:CW-2200,type:WidthType.DXA},
        margins:{top:100,bottom:100,left:120,right:120},
        children:[new Paragraph({ children:[new TextRun({text:'Mənası',bold:true,size:20,font:'Arial',color:'FFFFFF'})]})] }),
  ]});
  const cb={style:BorderStyle.SINGLE,size:1,color:'e5e7eb'};
  const rows = rows_data.map(([fill,label,desc]) =>
    new TableRow({ children:[
      new TableCell({ borders:{top:cb,bottom:cb,left:cb,right:cb},
          shading:{fill,type:ShadingType.CLEAR}, width:{size:2200,type:WidthType.DXA},
          margins:{top:90,bottom:90,left:100,right:100},
          children:[new Paragraph({alignment:AlignmentType.CENTER, children:[new TextRun({text:label,size:20,font:'Arial'})]})] }),
      new TableCell({ borders:{top:cb,bottom:cb,left:cb,right:cb},
          shading:{fill:'ffffff',type:ShadingType.CLEAR}, width:{size:CW-2200,type:WidthType.DXA},
          margins:{top:90,bottom:90,left:120,right:100},
          children:[new Paragraph({ children:[new TextRun({text:desc,size:20,font:'Arial'})]})] }),
    ]})
  );
  return new Table({ width:{size:CW,type:WidthType.DXA}, columnWidths:[2200,CW-2200], rows:[hH,...rows] });
}

// FAQ item
function faq(q, a){
  return [
    new Paragraph({ spacing:{before:240,after:60},
      children:[new TextRun({text:'❓  '+q, bold:true, size:22, font:'Arial', color:NAVY})] }),
    new Paragraph({ spacing:{before:0,after:160},
      children:[new TextRun({text:'→  '+a, size:22, font:'Arial', color:'374151'})] }),
  ];
}

// horizontal rule
function hr(){
  return new Paragraph({ spacing:{before:120,after:120},
    border:{ bottom:{style:BorderStyle.SINGLE,size:4,color:'e2e8f0'} }, children:[new TextRun('')]});
}

// ══════════════════════════════════════════════════════════════════════════
//  DOCUMENT CONTENT
// ══════════════════════════════════════════════════════════════════════════
let figNo = 0;
const F = () => ++figNo;

const children = [

// ────────────────────────────────────────────────────────────────────────
// COVER PAGE
// ────────────────────────────────────────────────────────────────────────
  spacer(30),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:1200,after:300},
    children:[new TextRun({text:'DMS', bold:true, size:120, color:NAVY, font:'Arial'})] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:200},
    children:[new TextRun({text:'Sənəd İdarəetmə Sistemi', bold:true, size:44, color:NAVY, font:'Arial'})] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:180},
    children:[new TextRun({text:'İstifadəçi Təlimatı', bold:true, size:52, color:BLUE, font:'Arial'})] }),
  spacer(4),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:80},
    children:[new TextRun({text:'Maliyyəçilər və qeyri-texniki istifadəçilər üçün', italics:true, size:26, color:GRAY, font:'Arial'})] }),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:0},
    children:[new TextRun({text:'Addım-addım tam bələdçi', italics:true, size:26, color:GRAY, font:'Arial'})] }),
  spacer(20),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:0,after:0},
    children:[new TextRun({text:'Tarix: May 2026', size:22, color:'9ca3af', font:'Arial'})] }),

  pb2(),

// ────────────────────────────────────────────────────────────────────────
// MÜNDƏRİCAT
// ────────────────────────────────────────────────────────────────────────
  new Paragraph({ heading: HeadingLevel.HEADING_1, pageBreakBefore: false,
    spacing:{before:0,after:200},
    children:[new TextRun({text:'Mündəricat', bold:true, size:40, color:NAVY, font:'Arial'})] }),
  new TableOfContents('Mündəricat', { hyperlink:true, headingStyleRange:'1-3' }),
  pb2(),

// ════════════════════════════════════════════════════════════════════════
// GİRİŞ (Introduction)
// ════════════════════════════════════════════════════════════════════════
  h1('Bu Təlimat Haqqında'),
  p('Bu sənəd "DMS — Sənəd İdarəetmə Sistemi" proqramından istifadə etmək üçün hazırlanmış tam bələdçidir. Təlimat maliyyə sahəsində çalışan, proqramlaşdırma biliyi olmayan istifadəçilər üçün sadə dildə yazılmışdır.'),
  spacer(2),
  p('Bu təlimatda öyrənəcəksiniz:', {bold:true}),
  b('Sistemə necə daxil olacağınızı'),
  b('Hüquqi aktları (sənədləri) necə tapacağınızı və baxacağınızı'),
  b('Tapşırıqlarınızı necə görəcəyinizi'),
  b('İcra haqqında hesabat necə göndərəcəyinizi'),
  b('Parolu necə dəyişdirəcəyinizi'),
  spacer(2),
  info('Bu sistemi istifadə etmək üçün heç bir texniki bilik lazım deyil. Yalnız kompüteri açıb brauzer (Chrome, Brave, Firefox) istifadə edə bilsəniz, kifayətdir.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 1 — GİRİŞ
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 1. Sistemə Giriş'),
  p('Sistemə daxil olmaq üçün kompüterinizdə brauzer (internet proqramı) açmağınız kifayətdir. Başqa heç bir proqram lazım deyil.'),

  h2('1.1. Giriş Səhifəsi'),
  p('Administratorunuzdan aldığınız internet ünvanını brauzerinizin ünvan çubuğuna yazın və Enter düyməsinə basın. Qarşınızda aşağıdakı ekran açılacaqdır:'),
  spacer(1),
  ...img('01_login_page.jpg', 'DMS sisteminin giriş səhifəsi', F()),
  spacer(1),
  sectionLabel('Bu ekranda nə görəcəksiniz?'),
  b('Ortada "DMS" yazısı olan mavi loqo'),
  b('"İstifadəçi adı" yazı sahəsi — buraya adınızı yazacaqsınız'),
  b('"Şifrə" yazı sahəsi — buraya parolunuzu yazacaqsınız'),
  b('"Məni xatırla" qutucuğu — işarələsəniz, növbəti dəfə parol yazmağa ehtiyac olmayacaq'),
  b('"Daxil ol" mavi düyməsi — yazıları daxil etdikdən sonra buna basacaqsınız'),
  spacer(2),

  h2('1.2. Sistemə Necə Daxil Olunur'),
  spacer(1),
  ...img('02_login_filled.jpg', 'İstifadəçi adı daxil edilmiş giriş səhifəsi', F()),
  spacer(1),
  stepBox('Sistemə daxil olmaq üçün bu addımları izləyin:', [
    'Administratorunuzdan aldığınız İSTİFADƏÇİ ADINI "İstifadəçi adı" sahəsinə yazın',
    'Şifrənizi "Şifrə" sahəsinə yazın (yazılar nöqtə şəklində görünəcək — bu normaldır)',
    'Şifrənizi görməK istəyirsinizsə, şifrə sahəsinin sağındakı göz işarəsinə () basın',
    '"Daxil ol" mavi düyməsinə basın',
    'Sistem sizi avtomatik əsas səhifəyə yönləndirəcəkdir',
  ]),
  spacer(2),
  warn('Şifrənizi başqa şəxslərlə paylaşmayın. Bu sizin şəxsi sisteminizdir.'),
  spacer(2),

  h2('1.3. Yanlış Parol Yazılması'),
  p('İstifadəçi adı və ya şifrə yanlış daxil edildikdə sistem sizə xəbərdarlıq verəcəkdir:'),
  spacer(1),
  ...img('03_login_error.jpg', 'Yanlış məlumat daxil edildikdə görünən xəta bildirisi', F()),
  spacer(1),
  sectionLabel('Bu ekranda nə görəcəksiniz?'),
  b('Qırmızı çərçivəli "İstifadəçi adı" sahəsi — bu sahədə xəta var'),
  b('Yuxarıda qırmızı xəta mesajı: "İstifadəçi adı və ya şifrə yanlışdır."'),
  spacer(2),
  sectionLabel('Nə etməlisiniz?'),
  b('İstifadəçi adını yoxlayın — boş qalmamalı, düzgün yazılmalıdır'),
  b('Şifrəni yenidən daxil edin — "Caps Lock" (böyük hərf) açıq olmamalıdır'),
  b('"Daxil ol" düyməsinə yenidən basın'),
  spacer(2),
  important('Şifrənizi unutmusunuzsa, sisteminizin administratoru ilə əlaqə saxlayın. Sistem özü parol sıfırlama imkanı vermir.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 2 — ANA EKRAN VƏ NAVİQASİYA
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 2. Ana Ekran və Naviqasiya'),
  p('Sistemə daxil olduqdan sonra əsas ekran açılacaqdır. Bu ekranın iki hissəsi var: sol menyu paneli və sağdakı əsas məzmun sahəsi.'),

  h2('2.1. Sol Menyu Paneli'),
  spacer(1),
  ...img('05_sidebar_navigation.jpg', 'Sol tərəfdəki naviqasiya menyusu', F()),
  spacer(1),
  p('Sol tərəfdəki qaranlıq menyuda sisteminizin bütün bölmələri sıralanmışdır. Hər bölməyə üzərinə basmaqla keçid edə bilərsiniz:'),
  spacer(1),
  pb('🔵 ƏSAS bölməsi:', ''),
  b('"Hüquqi Aktlar" — hüquqi sənədlərin siyahısı (ən çox istifadə edəcəyiniz yer)'),
  b('"İcraçı Paneli" — sizə tapşırılmış işlərin siyahısı'),
  b('"Təsdiq Gözləyənlər" — (yalnız menecerlər üçün) tədqiq gözləyən hesabatlar'),
  b('"Hesabat" — (yalnız menecerlər üçün) statistika məlumatları'),
  spacer(1),
  pb('📂 KATALOQLAR bölməsi:', '(məlumat kitabları)'),
  b('"Sənəd növləri" — fərman, əmr, sərəncam və s. növlər'),
  b('"Kim qəbul edib" — sənədi qəbul edən qurumlar'),
  b('"İdarələr" — şirkətin bölmə strukturu'),
  b('"Rəhbərlər" — rəhbər şəxslərin siyahısı'),
  b('"İcra qeydləri" — icra statusu seçimləri'),
  spacer(1),
  pb('Sol menyunun ən altında:', ''),
  b('"Şifrəni dəyiş" — parolunuzu dəyişmək üçün'),
  b('"Çıxış" — sistemdən çıxmaq üçün'),
  spacer(2),
  info('Ekran kiçikdirsə, sol menyunu sol üst küncündəki "◀" düyməsinə basaraq gizlədib yer açabilərsiniz. Yenidən göstərmək üçün həmin yerə yenidən basın.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 3 — HÜQUQİ AKTLAR SƏHİFƏSİ
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 3. Hüquqi Aktlar Səhifəsi'),
  p('Bu səhifə sistemin ürəyidir. Bütün hüquqi sənədlər — fərmanlar, əmrlər, sərəncamlar — burada saxlanılır. Sol menyudan "Hüquqi Aktlar" düyməsinə bassanız bu səhifə açılacaqdır.'),

  h2('3.1. Səhifənin Ümumi Görünüşü'),
  spacer(1),
  ...img('04_huquqi_aktlar_main.jpg', 'Hüquqi Aktlar əsas səhifəsi', F()),
  spacer(1),
  sectionLabel('Bu səhifədə nə görəcəksiniz?'),
  b('Yuxarıda "Hüquqi Aktlar" başlığı'),
  b('Sağ yuxarıda yaşıl "Yeni əlavə et" düyməsi (yeni sənəd əlavə etmək üçün)'),
  b('Filtrlər paneli — sənədləri axtarmaq üçün'),
  b('Sənədlərin cədvəli — bütün sənədlər sütun-sütun göstərilir'),
  b('Cədvəlin altında səhifə nömrəsi — çox sənəd varsa, səhifə-səhifə baxa bilərsiniz'),
  spacer(2),
  p('Cədvəldəki sütunların mənası:', {bold:true}),
  pb('Növü:', 'Sənədin növü — Fərman, Əmr, Sərəncam kimi rəngli etiket'),
  pb('Nömrəsi:', 'Sənədin rəsmi nömrəsi'),
  pb('Tarixi:', 'Sənədin imzalandığı tarix'),
  pb('Kim Qəbul Edib:', 'Sənədi hansı qurum qəbul edib'),
  pb('Sənədin sahibi:', 'Sənədin aid olduğu şəxs və ya qurum'),
  pb('Qısa Məzmun:', 'Sənədin nə barədə olduğunun qısa xülasəsi'),
  pb('Tapşırıq №:', 'Tapşırığın sıra nömrəsi'),
  pb('Tapşırıq:', 'İcraçıya verilmiş tapşırığın mətni'),
  pb('İcraçı:', 'Tapşırığı yerinə yetirəcək şəxsin adı'),
  pb('Bölmə:', 'İcraçının aid olduğu idarə/bölmə'),
  pb('İcra Müddəti:', 'Tapşırığın son yerinə yetirilmə tarixi'),
  spacer(2),
  info('Sütunların başlığına (adına) bassanız, həmin sütuna görə sıralama edə bilərsiniz. Məsələn, tarixi ən yenidən ən köhnəyə sıralamaq üçün "Tarixi" başlığına basın.'),
  spacer(2),

  h2('3.2. Sənəd Axtarmaq — Filtrlər Paneli'),
  p('Çox sənəd olduqda axtardığınız sənədi tapmaq üçün filtrlərdən istifadə edin.'),
  spacer(1),
  ...img('06_filter_panel.jpg', 'Filtrlər paneli — sənəd axtarmaq üçün', F()),
  spacer(1),
  sectionLabel('Axtarış (filtr) sahələrinin mənası:'),
  pb('Sənədin nömrəsi:', 'Sənədin nömrəsini bilirsinizsə buraya yazın (məsələn: 245)'),
  pb('Qısa məzmun:', 'Sənədin mövzusundan bir söz yazın (məsələn: "büdcə")'),
  pb('Sənədin növü:', 'Açılır siyahıdan növ seçin: Fərman, Əmr, Sərəncam...'),
  pb('Kim qəbul edib:', 'Sənədi hansı qurum qəbul edibsə seçin'),
  pb('İcraçı:', 'Müəyyən bir icraçının tapşırıqlarını görmək üçün seçin'),
  pb('Sənəd tarixi:', 'Tarix aralığı seçin (başlanğıc və son tarix)'),
  pb('İcra müddəti:', 'Son icra tarixi aralığı seçin'),
  pb('Müddət statusu:', '"Müddəti keçib" və ya "Müddəti keçməyib" seçin'),
  pb('Tapşırıq №:', 'Tapşırıq nömrəsini yazın'),
  pb('Bölmə:', 'Müəyyən bölməni seçin'),
  spacer(2),
  stepBox('Axtarış aparmaq üçün:', [
    'Axtarmaq istədiyiniz məlumatı bir və ya bir neçə sahəyə yazın/seçin',
    'Mavi "Axtarış" (böyüdücü şüşə işarəli) düyməsinə basın',
    'Cədvəldə yalnız uyğun sənədlər görünəcəkdir',
    'Axtarışı ləğv edib bütün sənədlərə qayıtmaq üçün "×" (X) düyməsinə basın',
  ]),
  spacer(2),
  info('Heç bir şey yazmasanız və "Axtarış" düyməsinə bassanız, bütün sənədlər görünəcəkdir.'),
  spacer(2),

  h2('3.3. Excel və Word-ə Çıxarış (İxrac)'),
  p('Sənədlər siyahısını özünüzə götürmək istəyirsinizsə, cədvəlin sağ yuxarı hissəsindəki "Excel" və ya "Word" düymələrinə basın:'),
  b('"Excel" düyməsi — sənədlər siyahısını Excel (.xlsx) faylı kimi yükləyir'),
  b('"Word" düyməsi — sənədlər siyahısını Word (.docx) faylı kimi yükləyir'),
  spacer(1),
  info('Aktiv olan filtrlər ixraca da tətbiq olunur — yəni yalnız filtrləyib seçdiyiniz sənədlər faylda olacaq.'),
  spacer(2),

  h2('3.4. Yeni Hüquqi Akt (Sənəd) Yaratmaq'),
  p('Yeni sənəd daxil etmək üçün sağ yuxarıdakı yaşıl "Yeni əlavə et" düyməsinə basın:'),
  spacer(1),
  ...img('07_yeni_senad_modal_top.jpg', '"Yeni sənəd" pəncərəsi — yuxarı hissə', F()),
  spacer(1),
  ...img('07b_yeni_senad_modal_bottom.jpg', '"Yeni sənəd" pəncərəsi — aşağı hissə', F()),
  spacer(1),
  sectionLabel('Pəncərədəki sahələrin mənası (* — mütləq doldurulmalıdır):'),
  pb('Sənədin nömrəsi *:', 'Hüquqi aktın rəsmi nömrəsi (məsələn: 112 və ya 2024/45)'),
  pb('Sənədin tarixi *:', 'Aktın imzalandığı tarix — təqvimə basıb seçin'),
  pb('Növü *:', 'Açılır siyahıdan seçin: Fərman / Əmr / Sərəncam / digər'),
  pb('Kim qəbul edib *:', 'Sənədi qəbul edən qurumu açılır siyahıdan seçin'),
  pb('Əsas icraçı(lar) *:', 'Bu tapşırığı yerinə yetirəcək əsas şəxsi seçin. Birdən çox seçmək mümkündür.'),
  pb('Digər icraçı(lar):', 'Köməkçi icraçılar (isteğe bağlı). Mütləq deyil.'),
  pb('İcra müddəti:', 'Tapşırığın son tamamlanma tarixi — təqvimə basıb seçin'),
  pb('Tapşırıq №:', 'Tapşırıq üçün nömrə'),
  pb('Qısa məzmun *:', 'Sənədin nə barədə olduğunu 1-2 cümlə ilə yazın'),
  pb('Tapşırıq:', 'İcraçıya verilən tapşırığın tam mətni'),
  pb('Əlaqəli sənəd №:', 'Bu sənəd başqa bir sənədlə bağlıdırsa, o sənədin nömrəsi'),
  pb('Əlaqəli sənəd tarixi:', 'Əlaqəli sənədin tarixi'),
  pb('"Sübut sənəd məcburidir" seçimi:', 'Bu seçimi işarələsəniz, icraçı hesabat göndərərkən mütləq fayl əlavə etməlidir'),
  spacer(2),
  stepBox('Yeni sənəd yaratmaq üçün:', [
    '"Yeni əlavə et" yaşıl düyməsinə basın — pəncərə açılacaqdır',
    'Ulduzlu (*) sahələri mütləq doldurun',
    'Lazım olan digər məlumatları da doldurun',
    '"Yarat" mavi düyməsinə basın — sənəd saxlanılacaqdır',
    'Ləğv etmək istəsəniz "İmtina" düyməsinə basın',
  ]),
  spacer(2),
  warn('Əsas icraçı seçilməzsə sənədi saxlamaq mümkün deyil. Mütləq ən azı bir əsas icraçı seçin.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 4 — İCRAÇI PANELİ
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 4. İcraçı Paneli — Tapşırıqlarım'),
  p('İcraçı Paneli sizə tapşırılmış işlərin siyahısını göstərir. Sol menyudan "İcraçı Paneli" düyməsinə basın.'),

  h2('4.1. Panelin Ümumi Görünüşü (Menecer tərəfindən)'),
  spacer(1),
  ...img('08_icraci_paneli_admin.jpg', 'İcraçı Paneli — bütün tapşırıqların siyahısı', F()),
  spacer(1),
  sectionLabel('Bu ekranda nə görəcəksiniz?'),
  b('Cədvəldə sizə aid tapşırıqlar — hər sıra bir tapşırıqdır'),
  b('Sarı sıralar — son tarixi yaxınlaşmış tapşırıqlar (diqqət!)'),
  b('Qırmızı sıralar — müddəti keçmiş tapşırıqlar (gecikmiş!)'),
  b('Yaşıl sıralar — tamamlanmış tapşırıqlar'),
  b('Hər sıranın sonunda iki düymə: 👁 (baxış) və 🖊 (status bildirmək)'),
  spacer(2),
  p('Cədvəldəki sütunların mənası:', {bold:true}),
  pb('Növü:', 'Sənədin növü (Əmr, Sərəncam və s.)'),
  pb('Nömrəsi:', 'Sənədin nömrəsi'),
  pb('Tarixi:', 'Sənədin tarixi'),
  pb('Kim Qəbul Edib:', 'Sənədi verən qurum'),
  pb('Qısa Məzmun:', 'Tapşırığın mövzusu'),
  pb('Tapşırıq №:', 'Tapşırıq nömrəsi'),
  pb('İcra Müddəti:', 'Son icra tarixi — "İcra müddəti bitib" yazısı varsa, gecikibsiniz!'),
  pb('Status:', 'Tapşırığın hazırkı vəziyyəti'),
  pb('Rolum:', '"Əsas" — əsas məsul sizsiniz; "Digər" — kömək edirsiniz'),
  pb('Əməliyyat:', 'Düymələr — göz işarəsi (detallar) və karandaş işarəsi (status göndər)'),
  spacer(2),

  h2('4.2. Öz Tapşırıqlarınız (İcraçı görünüşü)'),
  spacer(1),
  ...img('13_icraci_oz_paneli.jpg', 'İcraçının öz paneli — şəxsi tapşırıqlar', F()),
  spacer(1),
  p('İcraçı kimi daxil olduğunuzda yalnız sizə tapşırılmış işlər görünür. Nümunədə Orxan Bayramovun iki tapşırığı var:'),
  b('1-ci sıra — yaşıl rəng: "İcra olunub ✓" — bu tapşırıq artıq tamamlanıb'),
  b('2-ci sıra — sarı rəng: "1 gün qalıb" — sabah son tarixdir, tezliklə tamamlanmalıdır!'),
  spacer(2),
  info('Sıraların rəngi tapşırığın vəziyyətini göstərir. Rənglərin tam izahatı üçün Fəsil 7-yə baxın.'),
  spacer(2),

  h2('4.3. Tapşırığın Tam Detallarına Baxmaq'),
  p('Tapşırıq haqqında ətraflı məlumat görmək üçün həmin sıranın sonundakı 👁 (göz) işarəsinə basın:'),
  spacer(1),
  ...img('09_tapshiriq_detallari.jpg', 'Tapşırıq detalları pəncərəsi', F()),
  spacer(1),
  sectionLabel('Bu pəncərədə nə görəcəksiniz?'),
  b('Sol tərəf — Sənəd məlumatları: növü, nömrəsi, tarixi, mövzusu, icraçısı, son tarixi'),
  b('Sağ tərəf — Status Tarixçəsi: bu tapşırıqla bağlı bütün hadisələr tarixlə göstərilir'),
  b('Mavi nöqtə — "İcraya götürüldü" (tapşırıq verildi)'),
  b('Yaşıl nöqtə — "İcra olunub" (iş tamamlandı, kim tərəfindən, nə vaxt)'),
  spacer(2),
  stepBox('Detallar pəncərəsini bağlamaq üçün:', [
    '"Bağla" düyməsinə basın',
    'Yaxud pəncərənin sağ yuxarısındakı "×" işarəsinə basın',
  ]),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 5 — İCRA STATUSU BİLDİRMƏK
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 5. Tapşırığı Yerinə Yetirdikdən Sonra Hesabat Göndərmək'),
  p('Tapşırıqda istənilən işi tamamladıqdan sonra sistemdə bunu qeyd etməlisiniz. Buna "icra statusu bildirmək" deyilir. Bu, menecerinizə işin bitdiyini bildirmək kimidir.'),

  h2('5.1. Status Bildirmə Pəncərəsi'),
  p('İcraçı Panelindəki tapşırığın yanındakı 🖊 (karandaş/qələm) işarəsinə basın:'),
  spacer(1),
  ...img('14_status_bildirmek.jpg', 'Status bildirmə pəncərəsi', F()),
  spacer(1),
  sectionLabel('Bu pəncərədə nə görəcəksiniz?'),
  b('"Standart qeyd" açılır siyahısı — iş vəziyyətini seçirsiniz'),
  b('"Sərbəst qeyd" yazı sahəsi — əlavə izahat yazmaq üçün (istəyə bağlı)'),
  b('"Sübut sənədləri" — sübut faylı əlavə etmək üçün (lazım gəldikdə)'),
  b('"İmtina" düyməsi — heç nə göndərmədən geri çəkilmək üçün'),
  b('"Təsdiqlə" düyməsi — hesabatı menecerinizə göndərmək üçün'),
  spacer(2),

  h2('5.2. Status Seçimi'),
  spacer(1),
  ...img('14b_status_secilmis.jpg', 'Standart qeyd seçilmiş vəziyyət', F()),
  spacer(1),
  p('"Standart qeyd" açılır siyahısından iş vəziyyətinizə uyğun seçimi tapın:'),
  b('"İcra olunub" — işi tam bitirdiyiniz halda seçin (menecer tərəfindən təsdiq gözlənilir)'),
  b('"Qismən icra olunub" — işin bir hissəsi bitibsə seçin'),
  b('"İcradadır" — iş davam edirdirsə seçin'),
  b('Digər variantlar — mövcud siyahıdan uyğun olanı seçin'),
  spacer(2),
  stepBox('İcra hesabatı göndərmək üçün addımlar:', [
    'İcraçı Panelindəki tapşırıqda karandaş işarəsinə basın — pəncərə açılacaqdır',
    '"Standart qeyd" açılır siyahısından iş vəziyyətinizi seçin',
    'Əgər əlavə izahat vermək istəyirsinizsə, "Sərbəst qeyd" sahəsinə yazın',
    'Tapşırıq sübut sənədi tələb edirsə, "Faylları seçin" düyməsinə basıb fayl əlavə edin',
    '"Təsdiqlə" düyməsinə basın — hesabatınız menecerinizə göndərildi!',
  ]),
  spacer(2),
  info('Fayl əlavə edə bilirsiniz: PDF sənəd, Word sənəd (.docx), şəkil (JPG, PNG). Maksimum fayl ölçüsü 10 MB, maksimum 10 fayl.'),
  spacer(2),
  warn('Tapşırıq "Sübut sənəd məcburidir" kimi işarəlenmişsə, "İcra olunub" seçimi edərkən mütləq fayl əlavə etməlisiniz. Əks halda sistem qəbul etməyəcək.'),
  spacer(2),

  h2('5.3. Hesabat Göndərildikdən Sonra Nə Baş Verir?'),
  p('Hesabatı göndərdikdən sonra tapşırığın vəziyyəti dəyişir:'),
  b('Tapşırığın sırası narıncı rəngə dönür — bu, menecer təsdiqini gözlədiyini bildirir'),
  b('Menecer hesabatınızı yoxlayır'),
  b('Menecer TƏSDİQLƏRSƏ — tapşırıq yaşıl rəngə dönür, iş bitmiş sayılır'),
  b('Menecer RƏDD EDƏRSƏ — tapşırıq yenidən aktiv olur, siz yenidən hesabat göndərməlisiniz'),
  spacer(2),
  info('Eyni tapşırıqda birdən çox icraçı varsa, hamı hesabat göndərəndən sonra menecer xəbərdar olur. Bir nəfər göndərərsə, tapşırıq açıq mavi rəngdə görünür — "Qismən" vəziyyəti.'),
  spacer(2),

  h2('5.4. Rədd Edilmiş Tapşırıq'),
  p('Menecer hesabatınızı qəbul etmədikdə tapşırığın yanında "Rədd edilib" görünür. Bu halda:'),
  b('Tapşırığın 👁 (göz) düyməsinə basıb Status Tarixçəsinə baxın — menecer rədd sebebini yazmışdır'),
  b('Sebebi oxuyun, lazımi düzəlişlər edin'),
  b('Yenidən karandaş düyməsinə basıb yeni hesabat göndərin'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 6 — TƏSDİQ GÖZLƏYƏNLƏr (Menecerlər üçün)
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 6. Təsdiq Gözləyənlər (Menecerlər üçün)'),
  p('Bu bölmə yalnız menecer rolundakı istifadəçilər üçündür. İcraçılar hesabat göndərdikdə menecer burada onları görür və ya rədd edir.'),

  h2('6.1. Səhifənin Görünüşü'),
  spacer(1),
  ...img('10_tesdiq_gozleyanler.jpg', 'Təsdiq Gözləyənlər səhifəsi', F()),
  spacer(1),
  sectionLabel('Bu səhifədə nə görəcəksiniz?'),
  b('Təsdiq gözləyən tapşırıqların cədvəli'),
  b('Hər sırada: sənədin növü, nömrəsi, tarixi, mövzusu, icraçının adı, hesabatın göndərilmə tarixi'),
  b('"Sənədlər" sütununda — icraçının əlavə etdiyi sübut faylları (varsa) görünür'),
  b('Boş olduqda "Təsdiq gözləyən sənəd yoxdur" mesajı görünür'),
  spacer(2),

  h2('6.2. Hesabatı Təsdiqləmək'),
  stepBox('Tapşırığı təsdiqləmək üçün:', [
    '"Təsdiq Gözləyənlər" səhifəsinə keçin',
    'İcraçının göndərdiyi hesabatı oxuyun — qeydlərə və fayllarına baxın',
    'Əgər hər şey qaydasındadırsa, "Təsdiq et" (✓) düyməsinə basın',
    'Tapşırıq tamamlanmış sayılacaq, yaşıl rəngə dönəcəkdir',
  ]),
  spacer(2),

  h2('6.3. Hesabatı Rədd Etmək'),
  stepBox('Tapşırığı rədd etmək üçün:', [
    '"Rədd et" (✗) düyməsinə basın — pəncərə açılacaqdır',
    'Rədd səbəbini aydın şəkildə yazın (icraçı bunu oxuyacaq)',
    '"Göndər" düyməsinə basın',
    'İcraçı bildiriş alacaq və tapşırığı yenidən yerinə yetirə bilər',
  ]),
  spacer(2),
  warn('Rədd sebebini aydın yazın ki, icraçı hansı düzəlişi etməli olduğunu anlasın.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 7 — HESABAT
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 7. Hesabat Səhifəsi (Menecerlər üçün)'),
  p('Hesabat bölməsi tapşırıqların icra vəziyyəti barədə statistik məlumat verir. Sol menyudan "Hesabat" düyməsinə basın.'),

  h2('7.1. Hesabat Ümumi Görünüşü'),
  spacer(1),
  ...img('11_hesabat_yuxari.jpg', 'Hesabat səhifəsi — statistika kartları və qrafiklər', F()),
  spacer(1),
  sectionLabel('Yuxarıdakı rəngeli kartların mənası:'),
  pb('Cəmi:', 'Ümumi tapşırıq sayı'),
  pb('İcra olunub:', 'Tamamlanmış tapşırıqların sayı'),
  pb('Təsdiq gözləyir:', 'İcraçı göndərib, menecer hələ təsdiqləməyib'),
  pb('İmtina edilib:', 'Rədd edilmiş tapşırıqların sayı'),
  pb('İcradadır:', 'Davam edən tapşırıqların sayı'),
  pb('Müddəti keçib:', 'Son tarixi keçmiş, tamamlanmamış tapşırıqlar'),
  spacer(2),
  sectionLabel('Qrafiklərin izahatı:'),
  b('"İcraçılar üzrə" sütunlu diaqram — hər icraçının tapşırıqlarının vəziyyəti rənglə göstərilir'),
  b('"Ümumi bölgü" halqa diaqramı — bütün tapşırıqların ümumi paylaşımını göstərir'),
  spacer(2),
  ...img('11b_hesabat_asagi.jpg', 'Hesabat — ətraflı statistika cədvəli', F()),
  spacer(1),
  p('"Detallı statistika" cədvəlində hər icraçı üçün ayrıca məlumat var:'),
  b('İcraçının adı, idarəsi, vəzifəsi'),
  b('Cəmi tapşırıq sayı'),
  b('İcra olunmuş, rədd edilmiş, davam edən, başlanmamış tapşırıqların sayı'),
  b('"İcra faizi" — rəngli progress bar ilə tapşırıqların tamamlanma faizi'),
  spacer(2),
  p('"Bütün idarələr" açılır siyahısından müəyyən idarəni seçib yalnız o idarənin statistikasını görmək mümkündür.'),
  spacer(2),
  info('Sağ yuxarıdakı "Excel" düyməsinə basaraq hesabatı Excel faylı kimi kompüterinizə yükləyə bilərsiniz.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 8 — RƏNG KODLARI
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 8. Sıra Rəng Kodları — Tapşırıqların Vəziyyəti'),
  p('Tapşırıqlar cədvəlindəki sıraların rəngi tapşırığın vəziyyətini dərhal göstərir. Bir baxışda hansı tapşırığa diqqət etmək lazım olduğunu anlaya bilərsiniz:'),
  spacer(2),
  colorLegend(),
  spacer(2),
  p('Praktiki nümunə:', {bold:true}),
  b('Açdığınız cədvəldə qırmızı sıralar varsa — o tapşırıqlar gecikibdir, dərhal diqqət verin!'),
  b('Sarı sıralar varsa — son tarix yaxınlaşır, tezliklə tamamlayın'),
  b('Yaşıl sıralar — artıq bitmiş işlərdir, narahat olmayın'),
  spacer(2),
  info('Rəngi olmayan (ağ/normal) sıralar aktiv tapşırıqlardır, son tarixi hələ uzaqdır.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 9 — ŞİFRƏ DƏYİŞMƏK
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 9. Parolu (Şifrəni) Dəyişmək'),
  p('Parolunuzu dəyişmək üçün sol menyunun ən altındakı "Şifrəni dəyiş" düyməsinə basın:'),
  spacer(1),
  ...img('12_sifre_deyis.jpg', 'Parol dəyişmə pəncərəsi', F()),
  spacer(1),
  sectionLabel('Bu pəncərədə nə görəcəksiniz?'),
  b('"Cari şifrə" sahəsi — hazırkı parolunuz'),
  b('"Yeni şifrə" sahəsi — yeni parolunuz'),
  b('"Yeni şifrə təkrar" sahəsi — yeni parolu yenidən yazın (uyğunluğu yoxlanılır)'),
  b('"İmtina" düyməsi — pəncərəni bağlamaq'),
  b('"Deyiş" düyməsi — parolunuzu dəyişmək'),
  spacer(2),
  stepBox('Parolunuzu dəyişmək üçün:', [
    'Sol menyunun altından "Şifrəni dəyiş" düyməsinə basın',
    '"Cari şifrə" sahəsinə hazırkı parolunuzu yazın',
    '"Yeni şifrə" sahəsinə yeni seçdiyiniz parolu yazın',
    '"Yeni şifrə təkrar" sahəsinə eyni parolu bir daha yazın',
    '"Deyiş" düyməsinə basın — parolunuz yeniləndi!',
  ]),
  spacer(2),
  warn('Güclü parol seçin: ən azı 8 simvol, böyük hərf, rəqəm və xüsusi işarə (məsələn: ! @ # $) istifadə edin. Zəif parol hesabınızın təhlükəsizliyini azaldır.'),
  spacer(2),
  important('Yeni parolu yadda saxlayın. Unutsanız, bərpası üçün administratora müraciət etməli olacaqsınız.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 10 — SİSTEMDƏN ÇIXIŞ
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 10. Sistemdən Çıxmaq'),
  p('İşiniz bitdikdən sonra sistemdən çıxmağı unutmayın. Bu, xüsusilə paylaşılan və ya ofis kompüterlərindən istifadə edərkən vacibdir.'),
  spacer(1),
  stepBox('Sistemdən çıxmaq üçün:', [
    'Sol menyunun ən altındakı qırmızı "Çıxış" düyməsinə basın',
    'Sistem sizi giriş səhifəsinə yönləndirəcəkdir',
    'Artıq kompüterinizi bağlaya bilərsiniz',
  ]),
  spacer(2),
  important('Brauzeri bağlamaq sistemi çıxmaq kimi sayılmır! Həmişə mütləq "Çıxış" düyməsinə basın. Əks halda başqası kompüterdən istifadə edərkən hesabınıza daxil ola bilər.'),

// ════════════════════════════════════════════════════════════════════════
// FƏSİL 11 — TƏZ-TEZ SORUŞULAN SUALLAR (FAQ)
// ════════════════════════════════════════════════════════════════════════
  h1('Fəsil 11. Tez-tez Soruşulan Suallar'),
  p('Aşağıda istifadəçilərin ən çox verdiyi suallar və onların cavabları verilmişdir.'),
  spacer(2),
  hr(),
  ...faq('Sistemə daxil ola bilmirəm, parolumu unutmuşam. Nə edim?',
    'Parolunuzu sıfırlamaq üçün sistem administratorunuzla əlaqə saxlayın. Administratorun adı və əlaqəsi üçün müdirinizə müraciət edin. Sistem parolunuzu özünüz sıfırlaya bilməzsiniz.'),
  hr(),
  ...faq('İcraçı Panelində heç bir tapşırıq görmürəm. Niyə?',
    'Bu o deməkdir ki, sizin hesabınıza hələ heç bir tapşırıq verilməmişdir, yaxud tapşırıq fərqli istifadəçi hesabına göndərilmişdir. Menecerinizlə əlaqə saxlayın.'),
  hr(),
  ...faq('Hesabat göndərdim, lakin tapşırıq hələ də aktiv görünür. Bu normaldırmı?',
    'Bəli, bu tamamilə normaldır. Hesabatı göndərdikdən sonra tapşırıq narıncı rəngə dönür — bu, menecer təsdiqini gözlədiyini göstərir. Menecer təsdiqləyənə qədər bu vəziyyətdə qalar.'),
  hr(),
  ...faq('Eyni tapşırıqda mən də, həmkarım da varıq. Hər ikimiz hesabat göndərməliyikmi?',
    'Bəli, tapşırıqda iştirak edən hər icraçı öz hesabatını ayrıca göndərməlidir. Yalnız hamı göndərdikdən sonra menecer xəbərdar olur. Bir nəfər göndərərsə, tapşırıq açıq mavi rəngdə "qismən" kimi görünür.'),
  hr(),
  ...faq('Fayl yükləmək istəyirəm, lakin yüklənmir. Nə etməliyəm?',
    'Sistem yalnız PDF, Word (.docx, .doc) və şəkil faylları (JPG, PNG) qəbul edir. Hər faylın ölçüsü 10 MB-dan, fayl sayı isə 10-dan artıq olmamalıdır. Fayl formatınızı yoxlayın.'),
  hr(),
  ...faq('"Yeni əlavə et" düyməsi görünmür. Niyə?',
    'Bu düymə yalnız tapşırıq vermə icazəsi olan menecer hesablarında görünür. Sizin hesabınızda bu icazə olmaya bilər. Administratora müraciət edin.'),
  hr(),
  ...faq('"Təsdiq Gözləyənlər" və "Hesabat" bölmələri görünmür.',
    'Bu bölmələr yalnız menecer rolundakı istifadəçilər üçündür. İcraçı rolundakı istifadəçilərdə bu bölmələr görünmür.'),
  hr(),
  ...faq('Sənəd siyahısında çox sənəd var, necə tez tapa bilərəm?',
    'Filtrlər panelindən istifadə edin. Sənədin nömrəsini, mövzusunu yazın, ya da tarix aralığı seçin. "Axtarış" düyməsinə bassanız, yalnız uyğun sənədlər görünəcəkdir.'),
  hr(),
  ...faq('Excel-ə ixrac etdim, fayl boş gəldi. Niyə?',
    'Aktiv filtrlərə görə ixrac edilir. Əgər filtr tətbiq edilmişsə və uyğun sənəd yoxdursa, fayl boş gəlir. Filtrləri sıfırlayın (X düyməsi) və yenidən ixrac edin.'),
  hr(),
  spacer(4),

// ════════════════════════════════════════════════════════════════════════
// SÖZLÜK
// ════════════════════════════════════════════════════════════════════════
  h1('Əlavə: Terminlərin İzahatı'),
  p('Bu sözlüktə sistemdə istifadə olunan bəzi texniki sözlər sadə dildə izah edilmişdir:'),
  spacer(1),
  pb('Hüquqi akt:', 'Dövlət qurumları tərəfindən verilmiş rəsmi sənəd — fərman, əmr, sərəncam'),
  pb('İcraçı:', 'Tapşırığı yerinə yetirməkdən məsul olan şəxs'),
  pb('Menecer:', 'Tapşırıq verən və hesabatları təsdiqləyən rəhbər'),
  pb('Status:', 'Tapşırığın hazırkı vəziyyəti — "davam edir", "tamamlandı" kimi'),
  pb('Sübut sənədi:', 'İşin tamamlandığını göstərən əlavə fayl — akt, hesabat, şəkil'),
  pb('İcra müddəti:', 'Tapşırığın tamamlanmalı olduğu son tarix'),
  pb('Filtr:', 'Axtarış meyarı — siyahını daraltmaq üçün istifadə olunur'),
  pb('İxrac (export):', 'Məlumatları Excel və ya Word faylı kimi kompüterinizə yükləmək'),
  pb('Modal pəncərə:', 'Əsas ekranın üstündə açılan kiçik pəncərə'),
  pb('Brauzer:', 'İnterneti açmaq üçün istifadə olunan proqram (Chrome, Firefox, Brave kimi)'),
  spacer(4),
  new Paragraph({ alignment: AlignmentType.CENTER, spacing:{before:400,after:0},
    children:[new TextRun({text:'DMS — Sənəd İdarəetmə Sistemi  |  İstifadəçi Təlimatı  |  May 2026', size:18, color:'9ca3af', font:'Arial', italics:true})] }),
];

// ══════════════════════════════════════════════════════════════════════════
//  ASSEMBLE
// ══════════════════════════════════════════════════════════════════════════
const borderLine = { style: BorderStyle.SINGLE, size: 6, color: '1a3a5c', space: 4 };

const doc = new Document({
  numbering: { config: [
    { reference:'bul', levels:[{ level:0, format:LevelFormat.BULLET, text:'•',
        alignment:AlignmentType.LEFT,
        style:{ paragraph:{ indent:{ left:540, hanging:280 } } } }] },
    { reference:'num', levels:[{ level:0, format:LevelFormat.DECIMAL, text:'%1.',
        alignment:AlignmentType.LEFT,
        style:{ paragraph:{ indent:{ left:540, hanging:280 } } } }] },
  ]},
  styles: {
    default: { document: { run: { font:'Arial', size:22 } } },
    paragraphStyles: [
      { id:'Heading1', name:'Heading 1', basedOn:'Normal', next:'Normal', quickFormat:true,
        run:{ size:40, bold:true, font:'Arial', color:NAVY },
        paragraph:{ spacing:{ before:480, after:220 }, outlineLevel:0 } },
      { id:'Heading2', name:'Heading 2', basedOn:'Normal', next:'Normal', quickFormat:true,
        run:{ size:30, bold:true, font:'Arial', color:NAVY },
        paragraph:{ spacing:{ before:340, after:160 }, outlineLevel:1 } },
      { id:'Heading3', name:'Heading 3', basedOn:'Normal', next:'Normal', quickFormat:true,
        run:{ size:24, bold:true, font:'Arial', color:'1e40af' },
        paragraph:{ spacing:{ before:240, after:100 }, outlineLevel:2 } },
    ]
  },
  sections:[{
    properties:{ page:{ size:{ width:PW, height:PH },
        margin:{ top:MRG, right:MRG, bottom:MRG, left:MRG } } },
    headers:{ default: new Header({ children:[
      new Paragraph({ alignment:AlignmentType.RIGHT,
        border:{ bottom: borderLine },
        spacing:{ after:120 },
        children:[new TextRun({ text:'DMS — Sənəd İdarəetmə Sistemi  |  İstifadəçi Təlimatı',
            size:18, color:'6b7280', font:'Arial' })] })
    ]})},
    footers:{ default: new Footer({ children:[
      new Paragraph({ alignment:AlignmentType.CENTER,
        border:{ top: borderLine },
        spacing:{ before:120 },
        children:[
          new TextRun({ text:'Səhifə ', size:18, color:'6b7280', font:'Arial' }),
          new TextRun({ children:[PageNumber.CURRENT], size:18, color:'6b7280', font:'Arial' }),
          new TextRun({ text:' / ', size:18, color:'6b7280', font:'Arial' }),
          new TextRun({ children:[PageNumber.TOTAL_PAGES], size:18, color:'6b7280', font:'Arial' }),
        ] })
    ]})},
    children
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(OUT, buf);
  console.log('✅ Sənəd yaradıldı:', OUT);
  console.log('   Ölçü:', Math.round(buf.length/1024), 'KB');
}).catch(err => { console.error('❌ Xəta:', err.message); process.exit(1); });
