const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

const BASE_URL = 'http://localhost:8000';
const OUTPUT_DIR = path.join(__dirname, 'guide_screenshots');

if (!fs.existsSync(OUTPUT_DIR)) fs.mkdirSync(OUTPUT_DIR, { recursive: true });

async function screenshot(page, filename, description) {
  const filepath = path.join(OUTPUT_DIR, filename);
  await page.screenshot({ path: filepath, fullPage: true });
  console.log(`✓ ${description} -> ${filename}`);
}

async function delay(ms) {
  return new Promise(r => setTimeout(r, ms));
}

async function goTo(page, url) {
  const fullUrl = url.startsWith('http') ? url : `${BASE_URL}${url}`;
  await page.goto(fullUrl, { waitUntil: 'domcontentloaded', timeout: 15000 });
  await delay(1200);
}

(async () => {
  const browser = await puppeteer.launch({
    headless: true,
    args: ['--no-sandbox', '--disable-setuid-sandbox'],
    defaultViewport: { width: 1440, height: 900 }
  });

  const page = await browser.newPage();

  try {
    // 1. Login page
    await goTo(page, '/login');
    await screenshot(page, '01_login.png', 'Login Page');

    // Login as admin
    await page.type('#username', 'admin');
    await page.type('#password', 'admin123');
    await screenshot(page, '02_login_filled.png', 'Login - Filled Form');

    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 15000 }),
      page.click('button[type="submit"]')
    ]);
    await delay(1500);

    // 2. Legal Acts list
    await goTo(page, '/legal-acts');
    await screenshot(page, '03_legal_acts_list.png', 'Legal Acts - Main List');

    // 3. Executor Dashboard (correct URL)
    await goTo(page, '/executor/dashboard');
    await screenshot(page, '04_executor_dashboard.png', 'Executor Dashboard');

    // 4. Approvals
    await goTo(page, '/approvals');
    await screenshot(page, '05_approvals.png', 'Approvals Page');

    // 5. Reports
    await goTo(page, '/reports');
    await screenshot(page, '06_reports.png', 'Reports Page');

    // 6. Catalogs - Document Types
    await goTo(page, '/act-types');
    await screenshot(page, '07_catalog_act_types.png', 'Catalog - Document Types');

    // 7. Catalogs - Departments
    await goTo(page, '/departments');
    await screenshot(page, '08_catalog_departments.png', 'Catalog - Departments');

    // 8. Catalogs - Issuing Authorities
    await goTo(page, '/issuing-authorities');
    await screenshot(page, '09_catalog_issuing_authorities.png', 'Catalog - Issuing Authorities');

    // 9. Catalogs - Executors
    await goTo(page, '/executors');
    await screenshot(page, '10_catalog_executors.png', 'Catalog - Executors');

    // 10. Catalogs - Execution Notes
    await goTo(page, '/execution-notes');
    await screenshot(page, '11_catalog_execution_notes.png', 'Catalog - Execution Notes');

    // 11. Admin - Users
    await goTo(page, '/users');
    await screenshot(page, '12_admin_users.png', 'Admin - Users');

    // 12. Admin - Activity Log (correct URL)
    await goTo(page, '/activity-logs');
    await screenshot(page, '13_admin_activity_log.png', 'Admin - Activity Log');

    // 13. Change password modal
    await goTo(page, '/legal-acts');
    // Try clicking the change-password link in sidebar footer
    const pwLink = await page.$('a[href*="change-password"], [data-bs-target*="changePassword"], [data-bs-target*="password"]');
    if (pwLink) {
      await pwLink.click();
      await delay(1000);
      await screenshot(page, '14_change_password.png', 'Change Password Modal');
    }

    // 14. Legal Act - view first record details
    await goTo(page, '/legal-acts');
    const allLinks = await page.$$eval('a[href]', els =>
      els.map(el => el.href).filter(h => /\/legal-acts\/\d+$/.test(h))
    );
    if (allLinks.length > 0) {
      await goTo(page, allLinks[0]);
      await screenshot(page, '15_legal_act_detail.png', 'Legal Act Detail View');
    }

    console.log('\n✅ All screenshots saved to:', OUTPUT_DIR);
    console.log('Files:\n  ' + fs.readdirSync(OUTPUT_DIR).sort().join('\n  '));

  } catch (err) {
    console.error('Error:', err.message);
    try { await screenshot(page, 'error_state.png', 'Error state'); } catch(e) {}
  } finally {
    await browser.close();
  }
})();
