import puppeteer from 'puppeteer';
import path from 'path';
import { fileURLToPath } from 'url';
import fs from 'fs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outDir = path.join(__dirname, 'images');
if (!fs.existsSync(outDir)) fs.mkdirSync(outDir, { recursive: true });

const BASE = 'http://127.0.0.1:8080';

const browser = await puppeteer.launch({
  headless: true,
  args: ['--no-sandbox', '--disable-setuid-sandbox', '--window-size=1400,900']
});

const page = await browser.newPage();
await page.setViewport({ width: 1400, height: 900 });

async function shot(name) {
  await page.screenshot({ path: path.join(outDir, `${name}.jpg`), type: 'jpeg', quality: 92, fullPage: false });
  console.log(`✓ ${name}.jpg`);
}

// Helper to fill login form via JS
async function login(username, password) {
  await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
  await page.evaluate((u, p) => {
    document.querySelector('input[name="username"]').value = u;
    document.querySelector('input[name="password"]').value = p;
  }, username, password);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle0', timeout: 10000 }).catch(() => {}),
    page.click('button[type="submit"]')
  ]);
}

// ─── 1. Login page ───────────────────────────────────────────────────────────
await page.goto(`${BASE}/login`, { waitUntil: 'networkidle0' });
await shot('01_login');

// ─── 2. Login page with error ─────────────────────────────────────────────────
await page.evaluate(() => {
  document.querySelector('input[name="username"]').value = 'manager';
  document.querySelector('input[name="password"]').value = 'wrong';
});
await page.click('button[type="submit"]');
await new Promise(r => setTimeout(r, 1500));
await shot('02_login_error');

// ─── 3. Login as admin ────────────────────────────────────────────────────────
await login('admin', 'admin123');
await new Promise(r => setTimeout(r, 1000));
await shot('03_legal_acts_list');

// ─── 4. Legal acts - filter open ─────────────────────────────────────────────
await page.goto(`${BASE}/legal-acts`, { waitUntil: 'networkidle0' });
await shot('04_legal_acts_filters');

// ─── 5. New document modal ────────────────────────────────────────────────────
await page.evaluate(() => {
  const btn = document.querySelector('button');
  const btns = [...document.querySelectorAll('button')];
  const yeni = btns.find(b => b.textContent.includes('Yeni'));
  if (yeni) yeni.click();
});
await new Promise(r => setTimeout(r, 1500));
await shot('05_new_document_modal');

// ─── 6. Executor panel (admin view) ──────────────────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
await shot('06_executor_panel_admin');

// ─── 7. Task detail modal ─────────────────────────────────────────────────────
const eyeBtns = await page.$$('button[title], a[title]');
const viewBtn = await page.$('button svg[data-icon="eye"], button.btn-info, a.btn-info');
// click the first eye icon button in the table
const rows = await page.$$('table tbody tr');
if (rows.length > 0) {
  const eyeBtn = await rows[0].$('button');
  if (eyeBtn) { await eyeBtn.click(); await new Promise(r => setTimeout(r, 1500)); }
}
await shot('07_task_detail_modal');

// Close any open modal
await page.keyboard.press('Escape');
await new Promise(r => setTimeout(r, 500));

// ─── 8. Approvals page ────────────────────────────────────────────────────────
await page.goto(`${BASE}/approvals`, { waitUntil: 'networkidle0' });
await shot('08_approvals');

// ─── 9. Reports page ─────────────────────────────────────────────────────────
await page.goto(`${BASE}/reports`, { waitUntil: 'networkidle0' });
await new Promise(r => setTimeout(r, 2000)); // wait for charts
await shot('09_reports');

// ─── 10. Change password modal ────────────────────────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
const allBtns = await page.$$('button');
for (const b of allBtns) {
  const txt = await b.evaluate(el => el.textContent.trim());
  if (txt.includes('ifr')) { await b.click(); break; }
}
await new Promise(r => setTimeout(r, 1500));
await shot('10_change_password_modal');
await page.keyboard.press('Escape');

// ─── 11. Logout & login as executor ──────────────────────────────────────────
await page.evaluate(() => {
  const form = document.createElement('form');
  form.method = 'POST'; form.action = '/logout';
  const t = document.createElement('input');
  t.name = '_token';
  const m = document.querySelector('meta[name="csrf-token"]');
  t.value = m ? m.content : '';
  form.appendChild(t);
  document.body.appendChild(form);
  form.submit();
});
await new Promise(r => setTimeout(r, 2000));

await login('orxan', 'orxan123');
await new Promise(r => setTimeout(r, 1000));

// ─── 12. Executor's own panel ─────────────────────────────────────────────────
await page.goto(`${BASE}/executor/dashboard`, { waitUntil: 'networkidle0' });
await shot('11_executor_panel_user');

// ─── 13. Status submission modal ─────────────────────────────────────────────
const editBtns = await page.$$('button');
for (const b of editBtns) {
  const cls = await b.evaluate(el => el.className);
  if (cls.includes('warning') || cls.includes('edit') || cls.includes('pencil')) {
    await b.click(); break;
  }
}
// Try clicking last action button in row 2
const rows2 = await page.$$('table tbody tr');
if (rows2.length >= 2) {
  const btnsInRow = await rows2[1].$$('button');
  if (btnsInRow.length >= 2) {
    await btnsInRow[btnsInRow.length - 1].click();
    await new Promise(r => setTimeout(r, 1500));
  }
}
await shot('12_status_submit_modal');

// ─── 14. Executor Hüquqi Aktlar view ─────────────────────────────────────────
await page.keyboard.press('Escape');
await page.goto(`${BASE}/legal-acts`, { waitUntil: 'networkidle0' });
await shot('13_legal_acts_executor_view');

await browser.close();
console.log('\nAll screenshots saved to:', outDir);
