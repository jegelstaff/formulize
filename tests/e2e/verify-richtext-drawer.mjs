// Verify a rich-text (CKEditor) textarea initialises inside the Lyris right drawer.
// A textarea field with rich text enabled must be present on the first page of the
// screen under test for this to be meaningful (e.g. element 80,
// kitchen_sink_multi_line_textbox, on the Kitchen Sink form/screen sid=18).
// Run from tests/e2e: node verify-richtext-drawer.mjs
import { chromium } from 'playwright';

const base = 'http://localhost:8080';
const SID = process.env.SID || '18'; // Kitchen Sink list screen
const TAREA_ID = process.env.TAREA_ID || 'kitchen_sink_multi_line_textbox_tarea';
const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await ctx.newPage();

page.on('console', (msg) => {
  if (msg.type() === 'error') console.log('  [browser console.error]', msg.text());
});

async function login() {
  await page.goto(base + '/user.php');
  await page.locator('input[name="uname"]').fill('admin');
  await page.locator('input[name="pass"]').fill('password');
  await Promise.all([
    page.waitForURL(/\/modules\/formulize\/.*/),
    page.locator('input[name="pass"]').press('Enter'),
  ]);
}

await login();

await page.goto(base + `/modules/formulize/index.php?sid=${SID}`);
await page.waitForLoadState('networkidle');

// Open an entry in the drawer: first existing row, else the "Add new" control.
const editLink = page.locator('a.loe-edit-entry, a[onclick*="goDetails"]').first();
if (await editLink.count()) {
  await editLink.click();
} else {
  const addBtn = page.locator('a[onclick*="addNew"], button[onclick*="addNew"], .loe-add-entry, a:has-text("Add")').first();
  if (!(await addBtn.count())) {
    console.log('BLOCKED: no entry rows and no Add control on sid=' + SID);
    await browser.close();
    process.exit(2);
  }
  await addBtn.click();
}

// Wait for the drawer body to load the form.
const drawer = page.locator('.js-drawer');
await drawer.waitFor({ state: 'visible', timeout: 10000 });
await page.locator('.js-drawer-body form[data-fid]').first().waitFor({ state: 'attached', timeout: 10000 });

// Give CKEditor a moment to initialise (async .create()).
await page.waitForTimeout(2500);

// The rich-text textarea has id "<handle>_tarea". CKEditor replaces it with a
// .ck-editor container as a sibling. Check whether that container exists.
const richTextTareaId = TAREA_ID;
const result = await page.evaluate((tareaId) => {
  const ta = document.getElementById(tareaId);
  const drawerBody = document.querySelector('.js-drawer-body');
  const ckEditors = drawerBody ? drawerBody.querySelectorAll('.ck-editor').length : 0;
  return {
    textareaFound: !!ta,
    ckEditorContainers: ckEditors,
    updateCKEditorsDefined: typeof window.updateCKEditors === 'function',
    ckEditorsGlobal: (typeof window.CKEditors === 'object' && window.CKEditors) ? Object.keys(window.CKEditors) : null,
    classicEditorLoaded: typeof window.ClassicEditor !== 'undefined',
  };
}, richTextTareaId);

console.log('RESULT:', JSON.stringify(result, null, 2));

await page.screenshot({ path: 'richtext-drawer.png', fullPage: false });

if (result.ckEditorContainers > 0 && result.updateCKEditorsDefined) {
  console.log('PASS: rich-text editor initialised in the drawer.');
  await browser.close();
  process.exit(0);
} else {
  console.log('FAIL: rich-text editor did NOT initialise in the drawer.');
  await browser.close();
  process.exit(1);
}
