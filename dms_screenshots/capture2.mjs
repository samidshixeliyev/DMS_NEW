import puppeteer from 'puppeteer';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, 'images2');
if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });

const BASE = 'http://127.0.0.1:8080';
const wait = ms => new Promise(r => setTimeout(r, ms));

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--window-size=1440,900']
});
const page = await browser.newPage();
await page.setViewport({ width: 1440, height: 900 });

async function shot(name, fullPage = false) {
  await page.screenshot({ path: path.join(outDir, `${name}.jpg`), type: 'jpeg', quality: 93, fullPage });
  console.log(`✓ ${name}.jpg`);
}

async function login(user, pass) {
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
  await page.evaluate((u, p) => {
    document.querySelector('input[name="username"]').value = u;
    document.querySelector('input[name="password"]').value = p;
  }, user, pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 }).catch(() => {}),
    page.click('button[type="submit"]')
  ]);
}

async function logout() {
  await page.evaluate(() => {
    const f = document.createElement('form'); f.method='POST'; f.action='/logout';
    const t = document.createElement('input'); t.name='_token';
    const m = document.querySelector('meta[name="csrf-token"]');
    t.value = m ? m.content : '';
    f.appendChild(t); document.body.appendChild(f); f.submit();
  });
  await wait(2000);
}

// ── 1. LOGIN PAGE (clean) ──────────────────────────────────────────────────
await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
await shot('01_login_page');

// ── 2. LOGIN - filled in ───────────────────────────────────────────────────
await page.evaluate(() => {
  document.querySelector('input[name="username"]').value = 'samid661';
});
await wait(500);
await shot('02_login_filled');

// ── 3. LOGIN ERROR ─────────────────────────────────────────────────────────
await page.evaluate(() => {
  document.querySelector('input[name="username"]').value = 'maliyye';
  document.querySelector('input[name="password"]').value = 'yanlis123';
});
await page.click('button[type="submit"]');
await wait(2000);
await shot('03_login_error');

// ── LOGIN AS ADMIN ─────────────────────────────────────────────────────────
await login('admin', 'admin123');
await wait(1000);

// ── 4. MAIN PAGE after login (Hüquqi Aktlar) ──────────────────────────────
await page.goto(`${BASE}/legal-acts`, { waitUntil: 'networkidle0' });
await wait(1000);
await shot('04_huquqi_aktlar_main');

// ── 5. LEFT SIDEBAR zoom ──────────────────────────────────────────────────
await shot('05_sidebar_navigation');

// ── 6. FILTER PANEL expanded ──────────────────────────────────────────────
await page.goto(`${BASE}/legal-acts`, { waitUntil: 'networkidle0' });
// Ensure filter panel is visible
await wait(800);
await shot('06_filter_panel');

// ── 7. NEW DOCUMENT MODAL ─────────────────────────────────────────────────
await page.evaluate(() => {
  const btns = [...document.querySelectorAll('button')];
  const b = btns.find(b => b.textContent.includes('Yeni'));
  if (b) b.click();
});
await wait(1500);
await shot('07_yeni_senad_modal_top');

// Scroll modal down to see more fields
await page.evaluate(() => {
  const modal = document.querySelector('.modal-body, [class*="modal"], [class*="overflow-y-auto"]');
  if (modal) modal.scrollTop = 400;
});
await wait(500);
await shot('07b_yeni_senad_modal_bottom');

// Close modal
await page.keyboard.press('Escape');
await wait(500);

// ── 8. EXECUTOR PANEL (admin view, with data) ──────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
await wait(1000);
await shot('08_icraci_paneli_admin');

// ── 9. TASK DETAIL MODAL ───────────────────────────────────────────────────
const rows = await page.$$('table tbody tr');
if (rows.length > 0) {
  const btns = await rows[0].$$('button');
  if (btns.length > 0) { await btns[0].click(); await wait(1500); }
}
await shot('09_tapshiriq_detallari');
await page.keyboard.press('Escape');
await wait(500);

// ── 10. APPROVALS PAGE ─────────────────────────────────────────────────────
await page.goto(`${BASE}/approvals`, { waitUntil: 'networkidle0' });
await wait(1000);
await shot('10_tesdiq_gozleyanler');

// ── 11. REPORTS PAGE ───────────────────────────────────────────────────────
await page.goto(`${BASE}/reports`, { waitUntil: 'networkidle0' });
await wait(2500);
await shot('11_hesabat_yuxari');
// Scroll down to see statistics table
await page.evaluate(() => window.scrollBy(0, 400));
await wait(500);
await shot('11b_hesabat_asagi');

// ── 12. CHANGE PASSWORD MODAL ──────────────────────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
await wait(1000);
const allBtns = await page.$$('button');
for (const b of allBtns) {
  const txt = await b.evaluate(el => el.textContent.trim());
  if (txt.includes('ifr') && txt.includes('dey')) { await b.click(); break; }
}
await wait(1500);
await shot('12_sifre_deyis');
await page.keyboard.press('Escape');
await wait(500);

// ── LOGOUT ─────────────────────────────────────────────────────────────────
await logout();

// ── 13. LOGIN AS EXECUTOR ORXAN ────────────────────────────────────────────
await login('orxan', 'orxan123');
await wait(1000);

// ── 14. EXECUTOR OWN PANEL ────────────────────────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
await wait(1000);
await shot('13_icraci_oz_paneli');

// ── 15. STATUS SUBMISSION MODAL ────────────────────────────────────────────
const rows2 = await page.$$('table tbody tr');
if (rows2.length >= 2) {
  const btns2 = await rows2[1].$$('button');
  if (btns2.length >= 2) { await btns2[btns2.length - 1].click(); await wait(1500); }
}
await shot('14_status_bildirmek');

// Select a status from dropdown
await page.evaluate(() => {
  const sel = document.querySelector('select');
  if (sel && sel.options.length > 1) sel.selectedIndex = 1;
});
await wait(500);
await shot('14b_status_secilmis');
await page.keyboard.press('Escape');
await wait(500);

// ── 16. EXECUTOR HUQUQI AKTLAR VIEW ────────────────────────────────────────
await page.goto(`${BASE}/legal-acts`, { waitUntil: 'networkidle0' });
await wait(1000);
await shot('15_icraci_huquqi_aktlar');

// ── 17. DETAIL VIEW of an act ─────────────────────────────────────────────
const actRows = await page.$$('table tbody tr');
if (actRows.length > 0) {
  const eyeBtn = await actRows[0].$('a.btn, button');
  if (eyeBtn) { await eyeBtn.click(); await wait(1500); }
}
await shot('16_akt_detali');

await browser.close();
console.log('\nDone. Images saved to:', outDir);
