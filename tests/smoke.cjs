/**
 * ARTEMIS (Laravel) smoke test suite — port of the legacy tests/smoke.js.
 *
 * Read-only by design: it never submits applications, approves/rejects
 * anything, or writes to the database. Safe to re-run anytime.
 *
 * Requires:
 *   - XAMPP MySQL running (artemis_db)
 *   - The Laravel dev server:  C:\xampp\php83\php.exe artisan serve
 *   - Chrome installed for Playwright to drive
 *
 * Usage:  node tests/smoke.js
 * Env overrides:
 *   ARTEMIS_BASE_URL   default http://127.0.0.1:8000
 *   ARTEMIS_CHROME     default C:\Program Files\Google\Chrome\Application\chrome.exe
 *   ARTEMIS_PHP        default C:\xampp\php83\php.exe
 *
 * Note: the logged-in checks do a REAL login with the demo accounts. When
 * reCAPTCHA keys are configured in .env the scripted login cannot solve the
 * widget, so those checks are reported as SKIP (not FAIL). To run them,
 * temporarily blank RECAPTCHA_SITE_KEY / RECAPTCHA_SECRET_KEY and restart
 * the dev server.
 */
const path = require('path');
const fs = require('fs');
const { execFileSync } = require('child_process');
const { chromium } = require('C:/xampp/htdocs/ARTEMIS (1)/node_modules/playwright-core');

const ROOT = path.join(__dirname, '..');
const BASE = process.env.ARTEMIS_BASE_URL || 'http://127.0.0.1:8000';
const CHROME_PATH = process.env.ARTEMIS_CHROME || 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe';
const PHP = process.env.ARTEMIS_PHP || 'C:\\xampp\\php83\\php.exe';

const STUDENT = { email: '23-75900@g.batstate-u.edu.ph', password: 'student123' };
const ADMIN = { email: 'admin@batstate-u.edu.ph', password: 'admin123' };

const BENIGN_CONSOLE_NOISE = [
  /Permissions policy violation: compute-pressure/i,
  /www\.google\.com\/recaptcha/i, // widget domain warning when browsing via 127.0.0.1
];
function isBenignNoise(text) {
  return BENIGN_CONSOLE_NOISE.some(re => re.test(text));
}

let pass = 0;
let fail = 0;
let skip = 0;
function check(name, ok, detail) {
  if (ok) { pass++; console.log(`  PASS  ${name}`); }
  else { fail++; console.log(`  FAIL  ${name}${detail ? ' — ' + detail : ''}`); }
}
function skipCheck(name, why) {
  skip++;
  console.log(`  SKIP  ${name} — ${why}`);
}

function walkPhpFiles(dir, out) {
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    if (entry.name === 'node_modules' || entry.name === 'vendor' || entry.name.startsWith('.')) continue;
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walkPhpFiles(full, out);
    else if (entry.name.endsWith('.php')) out.push(full);
  }
  return out;
}

async function login(page, creds) {
  await page.goto(`${BASE}/login`, { waitUntil: 'load' });
  await page.fill('#email', creds.email);
  await page.fill('#password', creds.password);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }), page.click('#submitBtn')]);
  return !page.url().includes('/login');
}

async function main() {
  console.log('== PHP lint (app code only, vendor excluded) ==');
  const phpFiles = [];
  for (const dir of ['app', 'routes', 'config', 'bootstrap', 'public']) {
    walkPhpFiles(path.join(ROOT, dir), phpFiles);
  }
  let lintFailures = 0;
  for (const file of phpFiles) {
    try {
      execFileSync(PHP, ['-l', file], { stdio: 'pipe' });
    } catch (e) {
      lintFailures++;
      console.log(`  FAIL  ${path.relative(ROOT, file)}\n${e.stdout}`);
    }
  }
  check(`${phpFiles.length} PHP files parse cleanly`, lintFailures === 0, `${lintFailures} file(s) failed`);

  const browser = await chromium.launch({ executablePath: CHROME_PATH, headless: true });

  console.log('\n== Public pages ==');
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    for (const route of ['/', '/login', '/events', '/track', '/ask-spartan']) {
      const errors = [];
      page.removeAllListeners('pageerror');
      page.removeAllListeners('console');
      page.on('pageerror', e => errors.push(e.message));
      page.on('console', m => { if (m.type() === 'error' && !isBenignNoise(m.text())) errors.push(m.text()); });
      const res = await page.goto(`${BASE}${route}`, { waitUntil: 'load' });
      check(`${route} responds 200`, res.status() === 200, `got ${res.status()}`);
      check(`${route} has no console/page errors`, errors.length === 0, errors.join(' | '));
    }
    await page.close();
  }

  console.log('\n== Guest guards ==');
  {
    const page = await browser.newPage();
    const res = await page.goto(`${BASE}/student/dashboard`, { waitUntil: 'load' });
    check('guest on /student/dashboard lands on /login', page.url().includes('/login'), `landed on ${page.url()}`);
    const res2 = await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load' });
    check('guest on /admin/dashboard lands on /login', page.url().includes('/login'), `landed on ${page.url()}`);
    await page.close();
  }

  console.log('\n== Ask Spartan endpoint ==');
  {
    const page = await browser.newPage();
    await page.goto(`${BASE}/ask-spartan`, { waitUntil: 'load' });
    const reply = await page.evaluate(async base => {
      const r = await fetch(base + '/chat', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ message: 'What is BANTOG?' }),
      });
      return (await r.json()).reply || '';
    }, BASE);
    check('/chat replies about BANTOG', /BANTOG/i.test(reply), reply.slice(0, 80));
    await page.close();
  }

  console.log('\n== Student dashboard + document requirements ==');
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('console', m => { if (m.type() === 'error' && !isBenignNoise(m.text())) errors.push(m.text()); });

    if (!(await login(page, STUDENT))) {
      skipCheck('student dashboard checks', 'login blocked (reCAPTCHA keys configured?)');
    } else {
      const res = await page.goto(`${BASE}/student/dashboard`, { waitUntil: 'load' });
      check('/student/dashboard responds 200', res.status() === 200, `got ${res.status()}`);

      // Ground truth straight from the ported PHP source of truth.
      const dumpFile = path.join(ROOT, 'storage', 'app', 'doc_requirements_dump.php');
      fs.writeFileSync(dumpFile, `<?php
require_once '${ROOT.replace(/\\/g, '/')}/app/Support/document_requirements.php';
echo json_encode(application_document_requirements());
`);
      const expected = JSON.parse(execFileSync(PHP, [dumpFile]).toString());
      fs.rmSync(dumpFile);

      await page.click('#sidebarApplyBtn, #mobileApplyBtn').catch(() => {});
      await page.waitForTimeout(300);
      for (const typeCode of Object.keys(expected)) {
        await page.click(`.apptype-btn[data-type-code="${typeCode}"]`);
        await page.waitForTimeout(250);
        const rendered = await page.$$eval('#requiredDocsContainer input[type="file"]', els =>
          els.map(e => ({ key: e.name.replace(/^doc_/, ''), required: e.hasAttribute('required') }))
        );
        const renderedMap = Object.fromEntries(rendered.map(r => [r.key, r.required]));
        const expectedMap = expected[typeCode];
        const mismatches = Object.keys(expectedMap).filter(k => renderedMap[k] !== expectedMap[k]);
        check(`${typeCode}: rendered docs match document_requirements.php`, mismatches.length === 0, mismatches.join(', '));
        await page.goto(`${BASE}/student/dashboard`, { waitUntil: 'load' });
        await page.click('#sidebarApplyBtn, #mobileApplyBtn').catch(() => {});
        await page.waitForTimeout(300);
      }

      check('no console/page errors on student dashboard', errors.length === 0, errors.join(' | '));
      await page.goto(`${BASE}/logout`, { waitUntil: 'load' });
      check('logout redirects to the landing page', new URL(page.url()).pathname === '/', `landed on ${page.url()}`);
    }
    await page.close();
  }

  console.log('\n== Admin dashboard ==');
  {
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    const errors = [];
    page.on('pageerror', e => errors.push(e.message));
    page.on('console', m => { if (m.type() === 'error' && !isBenignNoise(m.text())) errors.push(m.text()); });

    if (!(await login(page, ADMIN))) {
      skipCheck('admin dashboard checks', 'login blocked (reCAPTCHA keys configured?)');
    } else {
      const res = await page.goto(`${BASE}/admin/dashboard`, { waitUntil: 'load' });
      check('/admin/dashboard responds 200', res.status() === 200, `got ${res.status()}`);
      await page.click('.admin-nav-btn[data-section="applications"]');
      await page.waitForTimeout(400);
      const rowCount = await page.evaluate(() => APPLICATIONS.length);
      check('admin sees seeded applications', rowCount >= 5, `saw ${rowCount}`);
      check('no console/page errors on admin dashboard', errors.length === 0, errors.join(' | '));
      await page.goto(`${BASE}/logout`, { waitUntil: 'load' });
    }
    await page.close();
  }

  await browser.close();

  console.log(`\n${pass} passed, ${fail} failed, ${skip} skipped`);
  process.exit(fail > 0 ? 1 : 0);
}

main().catch(e => { console.error(e); process.exit(1); });
