// Verify PR #15 followup (review 2026-07-08):
//   Item A: mobile multipage action bar hides the jump-to-page selector; desktop keeps it.
//   Item B: .fz-form-screen and #pageNavTable have a horizontal gutter from the
//           .fz-main container edges; #multipage-controls is FULL-BLEED (no gutter,
//           review 2026-07-08 15:46Z); no horizontal scrollbar introduced.
// Also re-confirms the sticky action bar (issue #9) still pins and the list footer /
// drawer foot are unaffected.
// Run from tests/e2e:  node verify-pr15-followup.mjs
import { chromium } from 'playwright';

const base = 'http://localhost:8080';
const FORM_URL = process.env.URL || (base + '/modules/formulize/index.php?sid=3&ve=1'); // Donor form, edit an entry
const browser = await chromium.launch();

async function loginCtx(viewport) {
  const ctx = await browser.newContext({ viewport });
  const page = await ctx.newPage();
  await page.goto(base + '/user.php');
  await page.locator('input[name="uname"]').fill('admin');
  await page.locator('input[name="pass"]').fill('password');
  await Promise.all([
    page.waitForURL(/\/modules\/formulize\/.*/),
    page.locator('input[name="pass"]').press('Enter'),
  ]);
  return page;
}

const G = 20; // expected gutter = --s-5 = 20px
let pass = true;
const check = (name, cond, extra='') => { console.log(`${cond ? 'PASS' : 'FAIL'}  ${name}${extra ? '  ('+extra+')' : ''}`); if(!cond) pass = false; };

// ---------- DESKTOP (2007px, matching the review screenshot width) ----------
{
  const page = await loginCtx({ width: 2007, height: 1000 });
  await page.goto(FORM_URL);
  await page.waitForLoadState('networkidle');

  const m = await page.evaluate(() => {
    const r = (s) => { const e=document.querySelector(s); if(!e) return null; const b=e.getBoundingClientRect(); return {x:Math.round(b.x), right:Math.round(b.right)}; };
    const vis = (s) => { const e=document.querySelector(s); if(!e) return false; const st=getComputedStyle(e); return st.display!=='none' && st.visibility!=='hidden'; };
    const main = r('.fz-main');
    return {
      main,
      formScreen: r('.fz-form-screen'),
      pageNav: r('#pageNavTable'),
      controls: r('#multipage-controls'),
      pageSelectorVisible: vis('#page-selector'),
      pageIndicatorVisible: vis('#page-indicator'),
      docScrollW: document.documentElement.scrollWidth,
      winInner: window.innerWidth,
    };
  });

  console.log('\n=== DESKTOP 2007px ===');
  console.log(JSON.stringify(m, null, 2));
  if (m.main && m.formScreen) {
    check('desktop: .fz-form-screen left gutter', m.formScreen.x - m.main.x === G, `${m.formScreen.x - m.main.x}px`);
    check('desktop: .fz-form-screen right gutter', m.main.right - m.formScreen.right === G, `${m.main.right - m.formScreen.right}px`);
  }
  if (m.main && m.pageNav) {
    check('desktop: #pageNavTable left gutter', m.pageNav.x - m.main.x === G, `${m.pageNav.x - m.main.x}px`);
    check('desktop: #pageNavTable right gutter', m.main.right - m.pageNav.right === G, `${m.main.right - m.pageNav.right}px`);
  }
  if (m.main && m.controls) {
    // Review 2026-07-08 15:46Z: the bottom action bar must be FULL-BLEED (edge-to-edge
    // of .fz-main, like .fz-list__footer / .fz-drawer__foot) — NO side gutter.
    check('desktop: #multipage-controls full-bleed left (no gutter)', Math.abs(m.controls.x - m.main.x) <= 1, `left offset=${m.controls.x - m.main.x}px`);
    check('desktop: #multipage-controls full-bleed right (no gutter)', Math.abs(m.main.right - m.controls.right) <= 1, `right offset=${m.main.right - m.controls.right}px`);
  }
  check('desktop: jump-to-page (#page-selector) still VISIBLE', m.pageSelectorVisible === true);
  check('desktop: no horizontal scrollbar', m.docScrollW <= m.winInner, `scrollW=${m.docScrollW} inner=${m.winInner}`);

  // sticky action bar: short form should be pinned to bottom of .fz-main
  const sticky = await page.evaluate(() => {
    const main = document.querySelector('.fz-main');
    const bar = document.querySelector('#multipage-controls');
    if(!main||!bar) return null;
    const mb = main.getBoundingClientRect(), bb = bar.getBoundingClientRect();
    return { barBottom: Math.round(bb.bottom), mainBottom: Math.round(mb.bottom), diff: Math.round(mb.bottom - bb.bottom) };
  });
  console.log('sticky bar vs main bottom:', JSON.stringify(sticky));
  if (sticky) check('desktop: sticky bar pinned to bottom of .fz-main (short form)', Math.abs(sticky.diff) <= 2, `diff=${sticky.diff}px`);

  await page.screenshot({ path: 'pr15-desktop.png', fullPage: false });
  await page.context().close();
}

// ---------- LONG FORM on a SHORT viewport: bar pins at viewport bottom while body scrolls ----------
{
  const page = await loginCtx({ width: 1200, height: 400 });
  await page.goto(FORM_URL);
  await page.waitForLoadState('networkidle');
  const before = await page.evaluate(() => {
    const bar = document.querySelector('#multipage-controls');
    return { scrollable: document.documentElement.scrollHeight > window.innerHeight, barBottom: bar ? Math.round(bar.getBoundingClientRect().bottom) : null };
  });
  // scroll the body down
  await page.evaluate(() => window.scrollTo(0, document.documentElement.scrollHeight));
  await page.waitForTimeout(150);
  const after = await page.evaluate(() => {
    const bar = document.querySelector('#multipage-controls');
    const main = document.querySelector('.fz-main');
    if(!bar||!main) return null;
    const bb = bar.getBoundingClientRect(), mb = main.getBoundingClientRect();
    return { barBottom: Math.round(bb.bottom), mainBottom: Math.round(mb.bottom), winInner: window.innerHeight, leftOffset: Math.round(bb.x - mb.x), rightOffset: Math.round(mb.right - bb.right) };
  });
  console.log('\n=== LONG FORM / SHORT VIEWPORT (1200x400) ===');
  console.log('before scroll:', JSON.stringify(before), 'after scroll:', JSON.stringify(after));
  if (after) {
    // bar stays pinned near the bottom of the visible .fz-main / viewport after scrolling
    check('long form: bar pinned at bottom of .fz-main after scroll', Math.abs(after.mainBottom - after.barBottom) <= 2, `diff=${after.mainBottom - after.barBottom}px`);
    check('long form: bar still full-bleed after scroll', Math.abs(after.leftOffset) <= 1 && Math.abs(after.rightOffset) <= 1, `L=${after.leftOffset} R=${after.rightOffset}`);
  }
  await page.context().close();
}

// ---------- MOBILE (390px) ----------
{
  const page = await loginCtx({ width: 390, height: 844 });
  await page.goto(FORM_URL);
  await page.waitForLoadState('networkidle');

  const m = await page.evaluate(() => {
    const vis = (s) => { const e=document.querySelector(s); if(!e) return {exists:false}; const st=getComputedStyle(e); const b=e.getBoundingClientRect(); return {exists:true, display:st.display, visible: st.display!=='none' && b.height>0}; };
    const off = () => { const bar=document.querySelector('#multipage-controls'); const main=document.querySelector('.fz-main'); if(!bar||!main) return null; const bb=bar.getBoundingClientRect(), mb=main.getBoundingClientRect(); return { left: Math.round(bb.x - mb.x), right: Math.round(mb.right - bb.right) }; };
    return {
      controlsOffset: off(),
      pageSelector: vis('#page-selector'),
      pageIndicator: vis('#page-indicator'),
      prevVisible: !!document.querySelector('#multipage-controls #prev, #multipage-controls .formulize-form-submit-button'),
      indicatorMarginTop: (()=>{ const e=document.querySelector('#page-indicator'); return e?getComputedStyle(e).marginTop:null; })(),
      docScrollW: document.documentElement.scrollWidth,
      winInner: window.innerWidth,
    };
  });

  console.log('\n=== MOBILE 390px ===');
  console.log(JSON.stringify(m, null, 2));
  check('mobile: jump-to-page (#page-selector) HIDDEN (display:none)', m.pageSelector.exists && m.pageSelector.display === 'none', `display=${m.pageSelector.display}`);
  check('mobile: "Page X of Y" indicator still visible', m.pageIndicator.visible === true);
  check('mobile: prev/next buttons still present', m.prevVisible === true);
  check('mobile: indicator top margin reclaimed (0)', m.indicatorMarginTop === '0px', `marginTop=${m.indicatorMarginTop}`);
  check('mobile: no horizontal scrollbar', m.docScrollW <= m.winInner, `scrollW=${m.docScrollW} inner=${m.winInner}`);
  if (m.controlsOffset) check('mobile: #multipage-controls full-bleed (no gutter)', Math.abs(m.controlsOffset.left) <= 1 && Math.abs(m.controlsOffset.right) <= 1, `L=${m.controlsOffset.left} R=${m.controlsOffset.right}`);

  await page.screenshot({ path: 'pr15-mobile.png', fullPage: false });
  await page.context().close();
}

// ---------- REGRESSION: list footer + drawer unaffected ----------
{
  const page = await loginCtx({ width: 1400, height: 900 });
  // a plain list screen (sid=3 default view = list)
  await page.goto(base + '/modules/formulize/index.php?sid=3');
  await page.waitForLoadState('networkidle');
  const listFooter = await page.evaluate(() => {
    const f = document.querySelector('.fz-list__footer');
    if(!f) return {exists:false};
    const st = getComputedStyle(f);
    return { exists:true, marginLeft: st.marginLeft, marginRight: st.marginRight };
  });
  console.log('\n=== REGRESSION: list footer ===');
  console.log(JSON.stringify(listFooter));
  if (listFooter.exists) {
    check('list footer: no injected side margin', listFooter.marginLeft === '0px' && listFooter.marginRight === '0px');
  } else {
    console.log('(list footer not present on this screen — skipping)');
  }
  await page.context().close();
}

await browser.close();
console.log('\n' + (pass ? 'ALL CHECKS PASSED' : 'SOME CHECKS FAILED'));
process.exit(pass ? 0 : 1);
