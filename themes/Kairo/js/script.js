'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initDrawer();
  initAccordions();
  initCardToggles();
  showApp();
});

// ============================================================
// Sidebar toggle
// ============================================================

function initSidebar() {
  const app = document.getElementById('fz-app');
  const toggle = document.querySelector('.js-sidebar-toggle');
  if (!app || !toggle) return;

  const STORAGE_KEY = 'fz-sidebar-closed';
  const isMobile = () => window.innerWidth <= 768;

  function setSidebarState(closed) {
    if (isMobile()) {
      app.classList.toggle('fz-app--sidebar-open', !closed);
      app.classList.remove('fz-app--sidebar-closed');
    } else {
      app.classList.toggle('fz-app--sidebar-closed', closed);
      app.classList.remove('fz-app--sidebar-open');
    }
    toggle.setAttribute('aria-expanded', String(!closed));
    try { localStorage.setItem(STORAGE_KEY, String(closed)); } catch (_) { /* ignore */ }
  }

  toggle.addEventListener('click', () => {
    const closed = isMobile()
      ? app.classList.contains('fz-app--sidebar-open')
      : !app.classList.contains('fz-app--sidebar-closed');
    setSidebarState(closed);
  });

  // Restore saved state on desktop; use data attribute default otherwise
  if (!isMobile()) {
    try {
      const saved = localStorage.getItem(STORAGE_KEY);
      const defaultOpen = app.dataset.sidebarDefault === 'open';
      setSidebarState(saved !== null ? saved === 'true' : !defaultOpen);
    } catch (_) { /* ignore */ }
  }

  // Close mobile sidebar on outside click
  document.addEventListener('click', (e) => {
    if (!isMobile() || !app.classList.contains('fz-app--sidebar-open')) return;
    const sidebar = document.getElementById('fz-sidebar');
    if (sidebar && !sidebar.contains(e.target) && !toggle.contains(e.target)) {
      setSidebarState(true);
    }
  });
}

// ============================================================
// Right slide-out drawer
// Exposed as window.formulize.drawer.open({ title, html, footerHtml })
// ============================================================

function initDrawer() {
  const scrim = document.querySelector('.js-drawer-scrim');
  const drawer = document.querySelector('.js-drawer');
  const closeBtn = document.querySelector('.js-drawer-close');
  if (!drawer) return;

  const titleEl  = drawer.querySelector('.js-drawer-title');
  const bodyEl   = drawer.querySelector('.js-drawer-body');
  const footEl   = drawer.querySelector('.js-drawer-foot');

  function openDrawer({ title = '', html = '', footerHtml = '' } = {}) {
    if (titleEl) titleEl.textContent = title;
    if (bodyEl)  bodyEl.innerHTML = html;
    if (footEl)  footEl.innerHTML = footerHtml;
    drawer.removeAttribute('hidden');
    if (scrim) scrim.removeAttribute('hidden');
    document.documentElement.style.overflow = 'hidden';
  }

  function closeDrawer() {
    drawer.setAttribute('hidden', '');
    if (scrim) scrim.setAttribute('hidden', '');
    document.documentElement.style.overflow = '';
  }

  const ENDPOINT = '/modules/formulize/include/formdisplay-elementsonly.php';
  const SAVE_ENDPOINT = '/modules/formulize/include/readelements.php';
  const FORM_NAME = 'formulize_drawer';

  function moduleBase() {
    return (window.formulize && window.formulize.xoopsUrl) || '';
  }

  // Load a Formulize form/entry into the drawer as an editable, elements-only form.
  // opts: { fid, frid, entryId, sid }. The form submits in the standard Formulize
  // manner (POST to readelements.php) rather than navigating a full page.
  function openEntryInDrawer(opts) {
    if (typeof jQuery === 'undefined') return;
    opts = opts || {};

    var params = [];
    if (opts.fid)     params.push('fid=' + encodeURIComponent(opts.fid));
    if (opts.frid)    params.push('frid=' + encodeURIComponent(opts.frid));
    if (opts.sid)     params.push('sid=' + encodeURIComponent(opts.sid));
    if (opts.entryId) params.push('entry_id=' + encodeURIComponent(opts.entryId));
    params.push('formname=' + FORM_NAME);

    var url = moduleBase() + ENDPOINT + '?' + params.join('&');

    openDrawer({ title: opts.title || '' });
    if (!bodyEl) return;

    bodyEl.innerHTML = '<div class="fz-drawer__loading">Loading…</div>';
    fetch(url, { credentials: 'same-origin' })
      .then(function (r) { return r.text(); })
      .then(function (html) { return injectFragment(bodyEl, html); })
      .then(function () { renderEntryFooter(); })
      .catch(function () {
        bodyEl.innerHTML = '<div class="fz-drawer__loading">Could not load form.</div>';
      });
  }

  // Inject an HTML fragment and execute its <script> tags in document order,
  // awaiting external (src) scripts before continuing. Setting innerHTML does not
  // run scripts, so we re-create each <script> node sequentially. This guarantees
  // dependencies like conditional.js are defined before the inline init code that
  // populates their globals runs — the ordering a real document gives for free,
  // but which jQuery .load() / innerHTML do not.
  function injectFragment(container, html) {
    container.innerHTML = html;
    var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
    return scripts.reduce(function (chain, oldScript) {
      return chain.then(function () {
        return new Promise(function (resolve) {
          var s = document.createElement('script');
          for (var a = 0; a < oldScript.attributes.length; a++) {
            s.setAttribute(oldScript.attributes[a].name, oldScript.attributes[a].value);
          }
          if (oldScript.src) {
            s.addEventListener('load', resolve);
            s.addEventListener('error', resolve);
            oldScript.parentNode.replaceChild(s, oldScript);
          } else {
            s.textContent = oldScript.textContent;
            oldScript.parentNode.replaceChild(s, oldScript);
            resolve();
          }
        });
      });
    }, Promise.resolve());
  }

  // Build the drawer footer Save / Cancel controls for the loaded entry form.
  function renderEntryFooter() {
    if (!footEl) return;
    footEl.innerHTML = '';

    var saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'fz-btn fz-btn--primary';
    saveBtn.textContent = 'Save';
    saveBtn.addEventListener('click', saveEntryFromDrawer);

    var cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'fz-btn fz-btn--ghost';
    cancelBtn.textContent = 'Cancel';
    cancelBtn.addEventListener('click', closeDrawer);

    footEl.appendChild(cancelBtn);
    footEl.appendChild(saveBtn);
  }

  // Submit the drawer's elements-only form the standard Formulize way.
  function saveEntryFromDrawer() {
    if (typeof jQuery === 'undefined') return;
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    if (!form) return;

    var validateFn = window['xoopsFormValidate_' + form.id];
    if (typeof validateFn === 'function' && !validateFn(form)) return;
    if (typeof updateCKEditors === 'function') { updateCKEditors(); }

    // hidden inputs (tokens) must be enabled so they are included in the FormData
    form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });

    var fid  = form.getAttribute('data-fid') || '';
    var frid = form.getAttribute('data-frid') || 0;
    var saveUrl = moduleBase() + SAVE_ENDPOINT + '?fid=' + encodeURIComponent(fid) + '&frid=' + encodeURIComponent(frid);

    jQuery.post({
      url: saveUrl,
      data: new FormData(form),
      cache: false,
      contentType: false,
      processData: false,
      success: function () {
        releaseEntryLocks();
        closeDrawer();
        // List-refresh hook — to be wired up separately.
        if (typeof window.formulize.onEntrySaved === 'function') {
          window.formulize.onEntrySaved();
        }
      }
    });
  }

  // Release any entry locks acquired by the loaded form (defined by the endpoint).
  function releaseEntryLocks() {
    if (typeof window.removeDrawerEntryLocks === 'function') {
      try { window.removeDrawerEntryLocks(); } catch (_) {}
    }
  }

  function closeEntryDrawer() {
    releaseEntryLocks();
    if (footEl) footEl.innerHTML = '';
    closeDrawer();
  }

  if (closeBtn) closeBtn.addEventListener('click', closeEntryDrawer);
  if (scrim)    scrim.addEventListener('click', closeEntryDrawer);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !drawer.hasAttribute('hidden')) closeEntryDrawer();
  });

  window.formulize = window.formulize || {};
  window.formulize.drawer = {
    open: openDrawer,
    close: closeEntryDrawer,
    openEntry: openEntryInDrawer,
    saveEntry: saveEntryFromDrawer
  };
}

// ============================================================
// Accordion (accessible toggle via data-accordion-header)
// ============================================================

function initAccordions() {
  document.querySelectorAll('[data-accordion-header]').forEach((header) => {
    const target = header.parentElement && header.parentElement.nextElementSibling;
    if (!target) return;
    header.addEventListener('click', (e) => {
      e.preventDefault();
      const expanded = header.getAttribute('aria-expanded') === 'true';
      header.setAttribute('aria-expanded', String(!expanded));
      target.hidden = expanded;
    });
  });
}

// ============================================================
// Card toggles (data-toggle / data-toggle-detail)
// ============================================================

function initCardToggles() {
  document.querySelectorAll('[data-toggle-detail]').forEach((el) => {
    el.hidden = true;
  });
  document.querySelectorAll('[data-toggle]').forEach((trigger) => {
    trigger.addEventListener('click', () => {
      const id = trigger.dataset.toggle;
      document.querySelectorAll(`[data-toggle-detail="${id}"]`).forEach((el) => {
        el.hidden = !el.hidden;
      });
    });
  });
}

// ============================================================
// Show app and fire page-shown event
// ============================================================

function showApp() {
  window.dispatchEvent(new CustomEvent('formulize_pageShown'));
}
