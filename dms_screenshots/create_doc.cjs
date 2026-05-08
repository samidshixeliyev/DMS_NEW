const {
  Document, Packer, Paragraph, TextRun, Table, TableRow, TableCell,
  ImageRun, Header, Footer, AlignmentType, HeadingLevel, BorderStyle,
  WidthType, ShadingType, VerticalAlign, PageNumber, PageBreak,
  LevelFormat, TableOfContents
} = require('docx');
const fs = require('fs');
const path = require('path');

const imgDir = path.join(__dirname, 'images');
const outFile = 'C:\\Users\\samid.sixaliyev\\Desktop\\workspace\\DMS_NEW\\dms-app\\DMS_Istifadeci_Telimat.docx';

// A4 page: 11906 x 16838 DXA, margins 1134 each side (~2cm) => content 9638 DXA
const PAGE_W = 11906;
const PAGE_H = 16838;
const MARGIN = 1134;
const CONTENT_W = PAGE_W - MARGIN * 2; // 9638

// Navy color
const NAVY = '1a2e4a';
const LIGHT_BLUE = 'dbeafe';
const LIGHT_YELLOW = 'fef9c3';

function loadImg(name) {
  return fs.readFileSync(path.join(imgDir, name));
}

// Image dimensions: original is 1400x900 → scale to content width
function imgRun(filename, captionText, figNum) {
  const imgData = loadImg(filename);
  // Scale to fit content width (9638 DXA = ~16.9cm). In px @ 96dpi: 9638/1440*96 = 643px
  // Target width in EMU: 9638 DXA * 914400/1440 = ~6,120,040 EMU ≈ 6120000
  // Height proportional: 900/1400 * 6120000 = 3934285 EMU
  const width = 6100000;
  const height = Math.round(900 / 1400 * width);
  return [
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 160, after: 80 },
      children: [
        new ImageRun({
          type: 'jpg',
          data: imgData,
          transformation: {
            width: Math.round(width / 9144),   // EMU → points (1pt = 12700 EMU; use 9144 for screen)
            height: Math.round(height / 9144),
          },
          altText: { title: captionText, description: captionText, name: captionText }
        })
      ]
    }),
    new Paragraph({
      alignment: AlignmentType.CENTER,
      spacing: { before: 0, after: 240 },
      children: [
        new TextRun({ text: `Şəkil ${figNum}: ${captionText}`, italics: true, size: 20, color: '555555', font: 'Arial' })
      ]
    })
  ];
}

function h1(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_1,
    spacing: { before: 480, after: 200 },
    children: [new TextRun({ text, bold: true, size: 36, color: NAVY, font: 'Arial' })]
  });
}

function h2(text) {
  return new Paragraph({
    heading: HeadingLevel.HEADING_2,
    spacing: { before: 320, after: 160 },
    children: [new TextRun({ text, bold: true, size: 28, color: NAVY, font: 'Arial' })]
  });
}

function para(text, opts = {}) {
  return new Paragraph({
    spacing: { before: 80, after: 120 },
    alignment: opts.center ? AlignmentType.CENTER : AlignmentType.JUSTIFIED,
    children: [new TextRun({ text, size: opts.size || 22, font: 'Arial', bold: opts.bold, color: opts.color })]
  });
}

function bullet(text, bold_prefix = '') {
  return new Paragraph({
    numbering: { reference: 'bullets', level: 0 },
    spacing: { before: 40, after: 40 },
    children: bold_prefix
      ? [new TextRun({ text: bold_prefix, bold: true, size: 22, font: 'Arial' }),
         new TextRun({ text, size: 22, font: 'Arial' })]
      : [new TextRun({ text, size: 22, font: 'Arial' })]
  });
}

function step(num, text, detail = '') {
  return new Paragraph({
    numbering: { reference: 'numbers', level: 0 },
    spacing: { before: 60, after: 60 },
    children: detail
      ? [new TextRun({ text, bold: true, size: 22, font: 'Arial' }),
         new TextRun({ text: ' ' + detail, size: 22, font: 'Arial' })]
      : [new TextRun({ text, size: 22, font: 'Arial' })]
  });
}

function infoBox(text, color = LIGHT_BLUE) {
  const border = { style: BorderStyle.SINGLE, size: 4, color: '3b82f6' };
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: [CONTENT_W],
    rows: [new TableRow({
      children: [new TableCell({
        borders: { top: border, bottom: border, left: { style: BorderStyle.SINGLE, size: 12, color: '3b82f6' }, right: { style: BorderStyle.NONE } },
        shading: { fill: color, type: ShadingType.CLEAR },
        margins: { top: 120, bottom: 120, left: 200, right: 120 },
        width: { size: CONTENT_W, type: WidthType.DXA },
        children: [new Paragraph({
          spacing: { before: 0, after: 0 },
          children: [
            new TextRun({ text: 'Qeyd: ', bold: true, size: 20, font: 'Arial', color: '1e40af' }),
            new TextRun({ text, size: 20, font: 'Arial', color: '1e3a8a' })
          ]
        })]
      })]
    })]
  });
}

function warningBox(text) {
  const border = { style: BorderStyle.SINGLE, size: 4, color: 'f59e0b' };
  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: [CONTENT_W],
    rows: [new TableRow({
      children: [new TableCell({
        borders: { top: border, bottom: border, left: { style: BorderStyle.SINGLE, size: 12, color: 'f59e0b' }, right: { style: BorderStyle.NONE } },
        shading: { fill: 'fffbeb', type: ShadingType.CLEAR },
        margins: { top: 120, bottom: 120, left: 200, right: 120 },
        width: { size: CONTENT_W, type: WidthType.DXA },
        children: [new Paragraph({
          spacing: { before: 0, after: 0 },
          children: [
            new TextRun({ text: 'Diqqet: ', bold: true, size: 20, font: 'Arial', color: '92400e' }),
            new TextRun({ text, size: 20, font: 'Arial', color: '78350f' })
          ]
        })]
      })]
    })]
  });
}

function spacer(pts = 1) {
  return new Paragraph({ spacing: { before: 0, after: pts * 20 }, children: [new TextRun('')] });
}

function pageBreak() {
  return new Paragraph({ children: [new PageBreak()] });
}

// Color legend table for chapter 8
function colorTable() {
  const cellBorder = { style: BorderStyle.SINGLE, size: 1, color: 'e2e8f0' };
  const borders = { top: cellBorder, bottom: cellBorder, left: cellBorder, right: cellBorder };
  const makeRow = (fill, label, meaning) => new TableRow({
    children: [
      new TableCell({
        borders,
        shading: { fill, type: ShadingType.CLEAR },
        width: { size: 1400, type: WidthType.DXA },
        margins: { top: 80, bottom: 80, left: 120, right: 120 },
        children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: label, bold: true, size: 20, font: 'Arial' })] })]
      }),
      new TableCell({
        borders,
        width: { size: 8238, type: WidthType.DXA },
        margins: { top: 80, bottom: 80, left: 120, right: 120 },
        children: [new Paragraph({ children: [new TextRun({ text: meaning, size: 20, font: 'Arial' })] })]
      })
    ]
  });

  // Header row
  const headerBorder = { style: BorderStyle.SINGLE, size: 1, color: 'e2e8f0' };
  const headerBorders = { top: headerBorder, bottom: { style: BorderStyle.SINGLE, size: 4, color: '94a3b8' }, left: headerBorder, right: headerBorder };
  const headerRow = new TableRow({
    children: [
      new TableCell({ borders: headerBorders, shading: { fill: '1e3a5f', type: ShadingType.CLEAR }, width: { size: 1400, type: WidthType.DXA }, margins: { top: 100, bottom: 100, left: 120, right: 120 }, children: [new Paragraph({ alignment: AlignmentType.CENTER, children: [new TextRun({ text: 'Reng', bold: true, size: 20, font: 'Arial', color: 'FFFFFF' })] })] }),
      new TableCell({ borders: headerBorders, shading: { fill: '1e3a5f', type: ShadingType.CLEAR }, width: { size: 8238, type: WidthType.DXA }, margins: { top: 100, bottom: 100, left: 120, right: 120 }, children: [new Paragraph({ children: [new TextRun({ text: 'Menasi', bold: true, size: 20, font: 'Arial', color: 'FFFFFF' })] })] }),
    ]
  });

  return new Table({
    width: { size: CONTENT_W, type: WidthType.DXA },
    columnWidths: [1400, 8238],
    rows: [
      headerRow,
      makeRow('ffffff', 'Ag (normal)', 'Aktiv tapsiiriq — son tarix uzagdadir, hec bir problem yoxdur.'),
      makeRow('fef9c3', 'Sari', 'Son icra tarixine 3 gun ve ya daha az qalibdir. Tecili diqqet telebedir.'),
      makeRow('fee2e2', 'Qirmizi', 'Son icra tarixi kecibdir — "Icra muddeti bitib". Tapsiiriq gecikibdir.'),
      makeRow('dcfce7', 'Yasil', 'Tapsiriq yerine yetirilmis ve menece tesdiqlenmisdir — "Icra olunub".'),
      makeRow('fed7aa', 'Narinc', 'Icraci hesabat gondermisdir, menecer tesdiqini gozleyir.'),
      makeRow('bfdbfe', 'Aciq goy', 'Qismeni icra — bir nece icracidan yalniz bir hissesi hesabat gondermisdir.'),
    ]
  });
}

// FAQ section
function faqItem(q, a) {
  return [
    new Paragraph({
      spacing: { before: 200, after: 60 },
      children: [new TextRun({ text: q, bold: true, size: 22, font: 'Arial', color: NAVY })]
    }),
    new Paragraph({
      spacing: { before: 0, after: 120 },
      children: [new TextRun({ text: a, size: 22, font: 'Arial' })]
    })
  ];
}

// ═══════════════════════════════════════════
// BUILD DOCUMENT
// ═══════════════════════════════════════════
const children = [

  // ── COVER PAGE ──────────────────────────────────────────────────────────────
  spacer(80),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 2000, after: 400 },
    children: [new TextRun({ text: 'Senad Idareetme Sistemi', bold: true, size: 64, color: NAVY, font: 'Arial' })]
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 200 },
    children: [new TextRun({ text: 'Istifadeci Telimat', bold: true, size: 40, color: '2563eb', font: 'Arial' })]
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 0, after: 600 },
    children: [new TextRun({ text: 'Icracilar ve Menecerler ucun', italics: true, size: 28, color: '555555', font: 'Arial' })]
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 600, after: 0 },
    children: [new TextRun({ text: 'DMS -- Senad Idareetme Sistemi', size: 22, color: '888888', font: 'Arial' })]
  }),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 80, after: 0 },
    children: [new TextRun({ text: 'May 2026', size: 22, color: '888888', font: 'Arial' })]
  }),
  pageBreak(),

  // ── TABLE OF CONTENTS ───────────────────────────────────────────────────────
  new TableOfContents('Mundevicat', { hyperlink: true, headingStyleRange: '1-2' }),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 1: Sistemə Giriş
  // ══════════════════════════════════════════════════════════════════════════
  h1('1. Sisteme Giris'),
  para('Bu bolme sistemin giris sehifesini ve giris prosedurasini izah edir. Sisteme daxil olmaq ucun administrator terefinden sizin ucun yaradilmis istifadeci adi ve sifre teleb olunur.'),
  spacer(1),

  h2('1.1 Giris sehifesi'),
  para('Brauzerinizi acib sistemin veb unvanini daxil etdikde asagidaki giris sehifesi gorsnecekdir:'),
  spacer(1),
  ...imgRun('01_login.jpg', 'Sisteme giris sehifesi', 1),
  spacer(1),
  para('Giris sehifesinde asagidaki elementler movcuddur:'),
  bullet('DMS loqosu -- sehifenin yuxari hissesinde sistemin adini gosteren loqo'),
  bullet('Istifadeci adi sahesi -- sistemdeki istifadeci adinizi daxil edin'),
  bullet('Sifre sahesi -- sifrenizi daxil edin (goz isaresi ile sifre goruntulene biler)'),
  bullet('"Meni xatirla" secimi -- bu secim aktiv edildikde brauzer baginandan sonra da daxil olmus qalirsiniz'),
  bullet('"Daxil ol" duymesi -- melumatlar daxil edildikden sonra bu duymeye basin'),
  spacer(2),
  infoBox('Sifrenizi bashqalari ile paylasmayiniz. Eger sifrenizi unutmusunuzsa sistem administratorunuzla elaqe saxlayin.'),
  spacer(2),

  h2('1.2 Yanlis melumat daxil etme'),
  para('Istifadeci adi ve ya sifre yanlis daxil edildikde asagidaki kimi xeta bildirisi gorunecekdir:'),
  spacer(1),
  ...imgRun('02_login_error.jpg', 'Giris zamani xeta bildirisi', 2),
  spacer(1),
  para('Sehife "Istifadeci adi ve ya sifre yanlisd." xetasini gosterecek ve istifadeci adi sahesi qirmizi renge donecekdir. Bu halda:'),
  step(1, 'Istifadeci adini yoxlayin', '-- bos qalmamis olmali ve dogru yazilmalidir'),
  step(2, 'Sifre sahesindesifrenizi yeniden daxil edin', '-- caps lock aktiv olmamalidir'),
  step(3, '"Daxil ol" duymesine yeniden basin', ''),
  spacer(2),
  warningBox('Uc defe art yanslis giris cehdinden sonra hesabiniz muveqqeti blok edile biler. Bu halda administratora muraciet edin.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 2: Hüquqi Aktlar
  // ══════════════════════════════════════════════════════════════════════════
  h1('2. Menecer/Istifadeci ucun Huquqi Aktlar'),
  para('Sistemde daxil olduqdan sonra "Huquqi Aktlar" sehifesine yonlendirileceksiniz. Bu sehife senedlerin esas idareetme merkezi hesab olunur.'),
  spacer(1),

  h2('2.1 Huquqi Aktlar siyahisi'),
  spacer(1),
  ...imgRun('03_legal_acts_list.jpg', 'Huquqi Aktlar esas siyahisi (menecer gorunusu)', 3),
  spacer(1),
  para('Sehifenin sol terende naviqasiya menyusu yerlesir:'),
  bullet('Huquqi Aktlar -- butun huquqi aktlarin siyahisi'),
  bullet('Icraçi Paneli -- icra edenlerin tapsiiriqlari'),
  bullet('Tesdiq Gozleyanler -- tesdiq gozleyen tapsiiriqlar (menecer ucun)'),
  bullet('Hesabat -- statistika ve hesabat sehifesi (menecer ucun)'),
  bullet('Kataloqlar -- Senad novleri, Kim qebul edib, Idareler, Rehberler, Icra qeydleri'),
  spacer(1),
  para('Cadvelde asagidaki sutunlar movcuddur:'),
  bullet('Novu -- sendin növu (Fermán, Sərəncam, Emr ve s.)'),
  bullet('Nomresi -- sendin resmi nomresi'),
  bullet('Tarixi -- sendin tarixi'),
  bullet('Kim Qebul Edib -- sendin qebul eden qurum'),
  bullet('Senedin sahibi -- senedin aid oldugu seхs ve ya qurum'),
  bullet('Qisa Mezmun -- sendin qisa muhtevasi'),
  bullet('Tapsiriq No -- tapsiriq nomresi'),
  bullet('Tapsiriq -- tapsiriq metni'),
  bullet('Icraci -- tapsiriqin icracisi'),
  bullet('Bolme -- aidiyyatli idarə ve bolme'),
  bullet('Icra Muddeti -- tapsiirigin son icra tarixi'),
  spacer(1),
  para('Sehifenin yuxari sag hissesinde:'),
  bullet('"Yeni elave et" duyemsi -- yeni huquqi akt yaratmaq ucun (bax: 2.3)'),
  bullet('"Excel" duyemsi -- cedveli Excel formatinda ixrac etmek ucun'),
  bullet('"Word" duyemsi -- cedveli Word formatinda ixrac etmek ucun'),
  spacer(2),

  h2('2.2 Filtrlerden istifade'),
  spacer(1),
  ...imgRun('04_legal_acts_filters.jpg', 'Huquqi Aktlar sehifesinde filtr paneli', 4),
  spacer(1),
  para('Siyahinin yuxari hissesindeki "Filtrler" bolmesinden istifade ederek senedleri daraltmag mumkundur. Movcud filtler:'),
  bullet('Senedin nomresi -- nomre uzre axtar'),
  bullet('Qisa mezmun -- mezmun uzre axtar'),
  bullet('Senedin novu -- Fermán, Sərəncam, Emr secimleri'),
  bullet('Kim qebul edib -- qurum uzre filtr'),
  bullet('Icraci -- icraci uzre filtr'),
  bullet('Senad tarixi -- tarix araligi secin'),
  bullet('Icra muddeti -- icra muddeti araligi'),
  bullet('Muddut statusu -- "Hamisi", "Muddeti kecib", "Muddeti kecmir"'),
  bullet('Tapsiriq No -- tapsiriq nomresi uzre axtar'),
  bullet('Bolme -- idarə ve bolme uzre filtr'),
  spacer(1),
  para('Filtrləri tətbiq etmek ucun mavi "Axtar" duymesine, temizlemek ucun ise daire seklinde "X" duymesine basin.'),
  spacer(2),
  infoBox('Cadvelin sutun basliqlarindan birinin uzerine tikleyerek sutun uzre siralama edebilirsiniz. 25, 50 ve ya 100 netice siyahida gostermek ucun sol alt kuncden secim edin.'),
  spacer(2),

  h2('2.3 Yeni senad yaratmaq'),
  para('"Yeni elave et" duymesine basildiqda asagidaki modal pencerese acilacaqdir:'),
  spacer(1),
  ...imgRun('05_new_document_modal.jpg', 'Yeni senad yaratma formu', 5),
  spacer(1),
  para('Formda doldurulmasi zorunlu saheler (*) ile isarelenmisdir:'),
  bullet('Senedin nomresi (*) -- huquqi aktin resmi nomresi'),
  bullet('Senedin tarixi (*) -- aktin imzalanma tarixi'),
  bullet('Novu (*) -- akt novu secin (acilir siyahi)'),
  bullet('Kim qebul edib (*) -- akti veren qurum secin'),
  bullet('Esas icraci(lar) (*) -- bir ve ya bir nece esas icraci secin'),
  bullet('Diger icraci(lar) -- isteye bagli; komekci icracilar'),
  bullet('Icra muddeti -- tapsiirigin yerine yetirilme tarixi'),
  bullet('Tapsiriq No -- tapsiriqin nomresi'),
  bullet('Qisa mezmun (*) -- sendin qisa muhtevasi (metn sahesi)'),
  bullet('Tapsiriq -- umumiyas tapsiriq metni'),
  bullet('Elaqeli senad No -- bagli senedin nomresi (isteye bagli)'),
  bullet('Elaqeli senad tarixi -- bagli senedin tarixi (isteye bagli)'),
  bullet('"Subut senad mecburidir" -- bu secim aktiv edildikde icracilar "Icra olunub" statusu gosterdikde fayl yuklemesi mecburi olur'),
  spacer(1),
  para('Formun asagi hissesinde:'),
  bullet('"Imtina" duyemsi -- formu baglamaq ve iptala etmek ucun'),
  bullet('"Yarat" duyemsi -- senedi yaratmaq ve saxlamaq ucun'),
  spacer(2),
  warningBox('Bu funksiya yalniz tapsiriq verme icazesi olan menecer hesablari ucundir. Eger bu duyeme gorsorunmurse administrator ile elaqe saxlayin.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 3: İcraçı Paneli
  // ══════════════════════════════════════════════════════════════════════════
  h1('3. Icraci Paneli'),
  para('Icraci Paneli sistemin esas is ekranindan biridir. Burada icraçilara teyin edilmis tapsiiriqlar rengle kodlanmis sekilde gosterilir.'),
  spacer(1),

  h2('3.1 Admin/Menecer gorunusu'),
  para('Menecer ve ya admin kimi daxil olduqda Icraci Panelinde "Icraci secin" aciilir siyahisi movcuddur:'),
  spacer(1),
  ...imgRun('06_executor_panel_admin.jpg', 'Icraci Paneli -- admin/menecer gorunusu (real melumatlarla)', 6),
  spacer(1),
  para('Sehifenin yuxari hissesinde "Icraci secin" acilir siyahisi var -- buradan mueyyen bir icracinin tapsiiriqlarini suxe baxmag mumkundur.'),
  spacer(1),
  para('Cedvelde asagidaki sutunlar movcuddur:'),
  bullet('# -- Sira nomresi'),
  bullet('Novu -- Akt novu beci (Sərəncam, Emr ve s.)'),
  bullet('Nomresi -- Aktin nomresi'),
  bullet('Tarixi -- Aktin tarixi'),
  bullet('Kim Qebul Edib -- Akti veren qurum'),
  bullet('Qisa Mezmun -- Tapsiiriqin qisa aciqlamasi'),
  bullet('Tapsiriq No -- Tapsiriq nomresi'),
  bullet('Icra Muddeti -- Son icra tarixi (muddeti kecmis satiirlar qirmizi ile gosterilir)'),
  bullet('Status -- Hazirki icra statusu'),
  bullet('Rolum -- Icraci rolunu gosterir (Esas, Diger)'),
  bullet('Emeliyyat -- Bax (goz isaresi) ve Status deyis (kilitacisi isaresi) duymeleri'),
  spacer(2),

  h2('3.2 Icraci gorunusu'),
  para('Icraci kimi daxil olduqda yalniz size teyin edilmis tapsiiriqlar gorunecekdir:'),
  spacer(1),
  ...imgRun('11_executor_panel_user.jpg', 'Icraci Paneli -- icraci (Orxan Bayramov) gorunusu', 7),
  spacer(1),
  para('Bu nümunede Orxan Bayramovun 2 tapsiiriqi vardir:'),
  bullet('1-ci sira -- yasil rengde, "Icra olunub" beci ile isareli (tamamlanmis tapsiiriq)'),
  bullet('2-ci sira -- sari rengde, "1 gun qalib" bildirisi ile (tecili)'),
  spacer(1),
  para('Her satin sonunda iki emeliyyat duyemsi yerlesir:'),
  bullet('Goz isaresi (mavi) -- tapsiiriqin tam detallarini gormek ucun'),
  bullet('Karandas isaresi (sari/narinci) -- icra statusu bildirmek ucun'),
  spacer(2),
  infoBox('Satirin reng koduna gore tapsiiriyin veziyyetini birbasha gorebilirsiniz. Renglerin izahati ucun 8-ci Bolmeyebaxin.'),
  spacer(2),

  h2('3.3 Tapsiriq detallari'),
  para('Goz isaresi duymesine basildiqda "Senad melumat" adli modal pencerese acilir:'),
  spacer(1),
  ...imgRun('07_task_detail_modal.jpg', 'Senad melumat -- tapsiriq detallar modali', 8),
  spacer(1),
  para('Modal pencerese iki bolmeden ibaretdir:'),
  bullet('Sol bolme -- Senad melumatlaari: Nov, Nomre, Tarix, Qisa mezmun, Kim qebul edib, Esas icraci, Diger icraci, Tapsiriq No, Tapsiriq, Icra muddeti'),
  bullet('Sag bolme -- Status Tarixcesi: Zaman cetveliyle butun status deyisiklikleri gosterilir. Mavi noxte -- "icraya gOturuldu"; yasil noxte -- "icra olunub" (tarix, istifadeci ve tesdiq melumatilari ile birlikde)'),
  spacer(1),
  para('Modali baglamaq ucun "Bagla" duymesine basin.'),
  spacer(2),

  h2('3.4 Icra statusu bildirmek'),
  para('"Karandas" isaresi duymesine basildiqda "Status deyis" modali acilir:'),
  spacer(1),
  ...imgRun('12_status_submit_modal.jpg', 'Status bildirme modali', 9),
  spacer(1),
  para('Modalda asagidaki saheler movcuddur:'),
  bullet('Standart qeyd (*) -- Acilir siyahidan icra qeydini secin (mec: "Icra olunub", "Qismen icra olunub")'),
  bullet('Serbest qeyd -- Isteye bagli; elave izahat veya sifahi aciqlamaniz'),
  bullet('Subut senaetleri -- Word, PDF, JPG, PNG formatinda fayl yukleyin (maks. 10MB, 10 fayl)'),
  spacer(1),
  step(1, 'Standart qeyd acilir siyahasindan uygun statusu secin', ''),
  step(2, 'Lazim geldikde "Serbest qeyd" saheesine elave aciqlamanizi yazin', ''),
  step(3, 'Tapsiriq subut seneedi telebedirsə faylari secinh', ''),
  step(4, '"Tesdiiqle" duymesine basin', '-- status menecerinize gonderilecekdir'),
  spacer(2),
  infoBox('Standart qeyd "Icra olunub" secildikde tapsiriq menecer tesdiqine gonderilir. Tesdiqden sonra sira yasil renge doneur.'),
  spacer(2),
  warningBox('Eyni tapsiiriqda birdender cox icraci varsa, hamisi hesabat gonderene qeder tapsiriq "Qismeni icra" olaraq qalir.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 4: Tesdiq Gozleyanler
  // ══════════════════════════════════════════════════════════════════════════
  h1('4. Tesdiq Gozleyanler'),
  para('Bu bolme yalniz menecer rollundaki istifadeciler ucun elcatandir. Menecerler buradan icracilarindan gelen hesabatlari tesdiq ede ve ya redd ede bilerler.'),
  spacer(1),

  h2('4.1 Gozleyen tapsiiriqlar siyahisi'),
  spacer(1),
  ...imgRun('08_approvals.jpg', 'Tesdiq Gozleyanler sehifesi', 10),
  spacer(1),
  para('"Tesdiq Gozleyanler" sehifesinde asagidaki sutunlar movcuddur:'),
  bullet('# -- Sira nomresi'),
  bullet('Novu -- Akt novu'),
  bullet('Nomresi -- Aktin nomresi'),
  bullet('Tarixi -- Sendin tarixi'),
  bullet('Qisa Mezmun -- Tapsiiriqin qisa aciqlamasi'),
  bullet('Icraci -- Hesabati gonderan icraci'),
  bullet('Gondaran -- Hesabati sistem uzre gonderan istifadeci'),
  bullet('Gonderilme Tarixi -- Hesabaatin gonderilme tarixi ve vaxti'),
  bullet('Senaedlar -- Yuklenilmis subut senaedleri (varsa)'),
  bullet('Icra Muddati -- Tapsiirigin son icra tarixi'),
  bullet('Emeliyyat -- Tesdiq et ve Redd et duymeleri'),
  spacer(1),
  para('Eger hec bir tesdiq gozleyen tapsiiriq yoxdursa "Tesdiq gozleyan senad yoxdur" bildirisi gorunecekdir.'),
  spacer(2),

  h2('4.2 Tapsiiriqi tesdiqlemek ve ya redd etmek'),
  para('Tesdiq gozleyen tapsiiriq oldugunda "Emeliyyat" sutununda iki duyeme gorunecekdir:'),
  spacer(1),
  step(1, 'Icracinin gonderdiy hesabati oxuyun', '-- qeydleri ve subut senaedlerini yoxlayin'),
  step(2, '"Tesdiq et" duymesine basin', '-- tapsiiriq tamamlanmis sayilacaq, sira yasil renge donecek'),
  spacer(1),
  para('Eger hesabat qeyri-qaneedici dirsə:'),
  step(1, '"Redd et" duymesine basin', ''),
  step(2, 'Acilan penceresde redd sebebini yazin', '-- icraci bu melumati gorecek'),
  step(3, '"Gondaer" duymesine basin', '-- icraci bildirism alacaq ve tapsiiriqi yeniden icra edib gondere bilecek'),
  spacer(2),
  warningBox('Redd sebebini mutleq aciq ve anlasiql yazin ki icraci hansi duzeltmelerin gerekdigini bilsin.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 5: Hesabat
  // ══════════════════════════════════════════════════════════════════════════
  h1('5. Hesabat (Reports)'),
  para('Hesabat bolmesi menecerler ucun idareetme statistikasini goruuntulemeye imkan verir. Burada icra veziyyeti ucun qrafik ve cedveller movcuddur.'),
  spacer(1),

  h2('5.1 Hesabat sehifesi'),
  spacer(1),
  ...imgRun('09_reports.jpg', 'Hesabat sehifesi -- qrafik ve statistika', 11),
  spacer(1),
  para('Sehifenin yuxari hissesinde "Butun idareler" acilir siyahisinden istediyiniz idareni secerek filtreleme edebilersiniz.'),
  spacer(1),
  para('Statistika kartlari (yuxari hissede):'),
  bullet('Cemi -- Umumi tapsiriq sayi'),
  bullet('Icra olunub -- Tamamlanmis tapsiiriqlar'),
  bullet('Tesdiq gozleyir -- Menecer tesdiqini gozleyen tapsiiriqlar'),
  bullet('Imtina edilib -- Redd edilmis tapsiiriqlar'),
  bullet('Iceadadir -- Hazirda icra prosesindeki tapsiiriqlar'),
  bullet('Muddeti kecib -- Son tarixi kecmis tapsiiriqlar'),
  spacer(1),
  para('Qrafiklerin aciqlamasi:'),
  bullet('"Icracilar uzere" sutunlu diaqram -- Her icracinin icra veziyyetini rengle gosteri: yasil = icra olunub, narinci = tesdiq gozleyir, qirmizi = imtina, mavi = iceadadir, boz = baslanmayib'),
  bullet('"Umumi bolgu" halqa diaqrami -- Butun tapsiiriqlarin nisbet paylasiimini gosterir'),
  spacer(1),
  para('"Detalli statistika" cedvelinde:'),
  bullet('Icraci -- Icracinin adi'),
  bullet('Idare -- Isciinin aidiyyatli idaresi'),
  bullet('Vezife -- Icracinin vezifesi'),
  bullet('Cemi / Icra olunub / Tesdiq gozleyir / Imtina edilib / Iceadadir / Baslanmayib / Muddeti kecib -- sayilar'),
  bullet('Icra faizi -- Tamamlanma faizi (renkli progress bar ile)'),
  spacer(1),
  para('Sag yuxari kuncde "Excel" duymesi ile hesabati .xlsx formatinda ixrac edebilersiz.'),
  spacer(2),
  infoBox('Hesabatlar yalniz sizin sobe ve tabe sobelerin melumatlarini eks etdirir.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 6: İcraçı üçün Hüquqi Aktlar görünüşü
  // ══════════════════════════════════════════════════════════════════════════
  h1('6. Icraci ucun Huquqi Aktlar Gorunusu'),
  para('Icraci rolundaki istifadeciler Huquqi Aktlar sehifesinde yalniz oz sobeleri ile elaqeli senedleri gorurler.'),
  spacer(1),

  h2('6.1 Mehdud gorunus'),
  spacer(1),
  ...imgRun('13_legal_acts_executor_view.jpg', 'Icraci (Resad Memmedov) Huquqi Aktlar gorunusu', 12),
  spacer(1),
  para('Icraci gorunusunde:'),
  bullet('Sol menyu yalniz "Huquqi Aktlar" ve "Icraci Paneli" bolmelerini gosterir (Tesdiq Gozleyanler, Hesabat ve Admin bolmeleri gorunmur)'),
  bullet('Sehifenin ust hissesinde "MN Ktb" ve "Bas Qerargah" kimi idarə filtirteri goruunur -- bunlar o icracinin aidiyyatli bolmeleridir'),
  bullet('Cedvelde yalniz icracininas teyin oldugu tapsiiriqlar gorunur'),
  bullet('"Yeni elave et" duyemsi gorunmur -- icracilar yeni akt yarada bilmir'),
  spacer(2),
  infoBox('Icraci rollundaki istifadeciler yalniz oz tapsiiriqlarini gorebilir ve onlara icra statusu bildire biler. Butun senedlere girisi yoxdur.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 7: Şifrə Dəyişmək
  // ══════════════════════════════════════════════════════════════════════════
  h1('7. Sifre Deyismek'),
  para('Her istifadeci oz sifresini deyise biler. Bunun ucun sol menyunun en altindaki "Sifreni deiyis" linkine basin.'),
  spacer(1),

  h2('7.1 Sifreni deyis modali'),
  spacer(1),
  ...imgRun('10_change_password_modal.jpg', 'Sifre deyisma modali', 13),
  spacer(1),
  para('Acilan modalda uc sahə movcuddur (hamisi mecburidir *):'),
  bullet('Cari sifre -- Hazirki sifrenizi daxil edin'),
  bullet('Yeni sifre -- Yeni sifrenizi daxil edin'),
  bullet('Yeni sifre tekran -- Yeni sifrenizi yeniden daxil edin (uygunluq yoxlanilir)'),
  spacer(1),
  step(1, 'Sol menyunun altindan "Sifreni deyis" duymesine basin', ''),
  step(2, 'Cari sifrenizi daxil edin', ''),
  step(3, 'Yeni sifrenizi iki defe daxil edin', '-- her iki sahe eyni olmalidir'),
  step(4, '"Deyis" duymesine basin', '-- sifreniz yenilenecekdir'),
  spacer(2),
  warningBox('Guclu sifre secin: minimum 8 simvol, boyuk herf, reqem ve xususi simvol (! @ # $ kimi) istifade edin. Sifrenizi bashqalari ile paylasmayin.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 8: Sıra Rəng Kodları
  // ══════════════════════════════════════════════════════════════════════════
  h1('8. Sira Reng Kodlari'),
  para('Tapsiriq siyahisinda her sira muxtellif rengde gosterilir. Bu renkler tapsiiriyin veziyyetini birbasha gosterir:'),
  spacer(2),
  colorTable(),
  spacer(2),
  para('Reng sistemi sayesinde uzun siyahilarda bele hansi tapsiiriqlarin tecili oldugunu bir baxişda gormek mumkundur.'),
  spacer(2),
  infoBox('Gozler ucun daha rahat olmasi ucun emoranlarin mueyyen hissesinin rengi olmaya biler (ag/normal). Bu hemin tapsiriqlar ucun son tarix hele uzaqdadir demekdir.'),
  pageBreak(),

  // ══════════════════════════════════════════════════════════════════════════
  // CHAPTER 9: FAQ
  // ══════════════════════════════════════════════════════════════════════════
  h1('9. Tez-tez Sorusulan Suallar'),
  spacer(1),

  ...faqItem(
    'Tapsiiriqlarimi gormuram -- niye?',
    'Administrator terefinden sizin hesabiniza tapsiriq teyin edilmemis ola biler. Menecerinizle ve ya sistem administratorunuzla elaqe saxlayin.'
  ),
  ...faqItem(
    'Status gonderdim, amma tapsiriq hele "gozlenir" rengindedir?',
    'Bu normaaldir. Tapsiiriqi menecer hele tesdiqlemeyib. Tesdiqden sonra tapsiiriq yasil renge donecekdir.'
  ),
  ...faqItem(
    'Eyni tapsiiriqda iki icraci var -- ne etmeliyem?',
    'Her icraci oz statusunu ayrica bildirir. Hamisi gonderdikden sonra tapsiriq menecer terefinden tesdiqlenecekdir. Bir nefer gonderse sira mavi (qismeni) rengde gorunur.'
  ),
  ...faqItem(
    'Fayl yukleye bilmirem?',
    'Yalniz PDF, Word (.docx, .doc) ve sekil (JPG, PNG) formatlari qebul edilir. Maksimum fayl olcusu 10MB, maksimum fayl sayi 10-dur. Diger formatlari ZIP ile sixisdirib gonderin ve ya administratora muraciet edin.'
  ),
  ...faqItem(
    'Sifrenizi unutmusum -- ne etmeliyem?',
    'Sistem sifre beerpayini desteklemir. Sistem administratoru ile elaqe saxlayin. Administrator sizin ucun sifrenizi sifirlaayacaqdir.'
  ),
  ...faqItem(
    'Huquqi akt yarada bilmirem?',
    'Bu funksiya yalniz tapsiriq verme icazesi olan menecer hesablari ucundur. Lazim geldikde administratordan "can_assign" icazesi telab edin.'
  ),
  ...faqItem(
    'Hesabat ve Tesdiq Gozleyanler sehifelerini goremirem?',
    'Bu sehifeler yalniz menecer ve admin rolundaki istifadeciler ucun elcatandir. Icraci rolundaki istifadecilerin bu bolmelere girisi yoxdur.'
  ),
  spacer(4),
  new Paragraph({
    alignment: AlignmentType.CENTER,
    spacing: { before: 400, after: 0 },
    children: [new TextRun({ text: 'DMS -- Senad Idareetme Sistemi  |  Istifadeci Telimat  |  May 2026', size: 18, color: 'aaaaaa', font: 'Arial', italics: true })]
  }),
];

// ═══════════════════════════════════════════
// ASSEMBLE DOCUMENT
// ═══════════════════════════════════════════
const borderLine = { style: BorderStyle.SINGLE, size: 6, color: '1a2e4a', space: 4 };

const doc = new Document({
  numbering: {
    config: [
      {
        reference: 'bullets',
        levels: [{
          level: 0,
          format: LevelFormat.BULLET,
          text: '•',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 540, hanging: 260 } } }
        }]
      },
      {
        reference: 'numbers',
        levels: [{
          level: 0,
          format: LevelFormat.DECIMAL,
          text: '%1.',
          alignment: AlignmentType.LEFT,
          style: { paragraph: { indent: { left: 540, hanging: 260 } } }
        }]
      }
    ]
  },
  styles: {
    default: {
      document: { run: { font: 'Arial', size: 22 } }
    },
    paragraphStyles: [
      {
        id: 'Heading1', name: 'Heading 1', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 36, bold: true, font: 'Arial', color: NAVY },
        paragraph: { spacing: { before: 480, after: 200 }, outlineLevel: 0 }
      },
      {
        id: 'Heading2', name: 'Heading 2', basedOn: 'Normal', next: 'Normal', quickFormat: true,
        run: { size: 28, bold: true, font: 'Arial', color: NAVY },
        paragraph: { spacing: { before: 320, after: 160 }, outlineLevel: 1 }
      }
    ]
  },
  sections: [{
    properties: {
      page: {
        size: { width: PAGE_W, height: PAGE_H },
        margin: { top: MARGIN, right: MARGIN, bottom: MARGIN, left: MARGIN }
      }
    },
    headers: {
      default: new Header({
        children: [
          new Paragraph({
            alignment: AlignmentType.RIGHT,
            border: { bottom: borderLine },
            spacing: { after: 120 },
            children: [
              new TextRun({ text: 'DMS -- Senad Idareetme Sistemi', size: 18, color: '555555', font: 'Arial' })
            ]
          })
        ]
      })
    },
    footers: {
      default: new Footer({
        children: [
          new Paragraph({
            alignment: AlignmentType.CENTER,
            border: { top: borderLine },
            spacing: { before: 120 },
            children: [
              new TextRun({ text: 'Sehife ', size: 18, color: '555555', font: 'Arial' }),
              new TextRun({ children: [PageNumber.CURRENT], size: 18, color: '555555', font: 'Arial' }),
              new TextRun({ text: ' / ', size: 18, color: '555555', font: 'Arial' }),
              new TextRun({ children: [PageNumber.TOTAL_PAGES], size: 18, color: '555555', font: 'Arial' }),
            ]
          })
        ]
      })
    },
    children
  }]
});

Packer.toBuffer(doc).then(buf => {
  fs.writeFileSync(outFile, buf);
  console.log('Document created:', outFile);
  console.log('Size:', Math.round(buf.length / 1024), 'KB');
}).catch(err => {
  console.error('Error:', err.message);
  process.exit(1);
});
