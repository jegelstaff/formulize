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

  // Paging state for the currently loaded entry form. Populated from the
  // fz-multipage-nav metadata the endpoint emits; null for single-page forms.
  let currentEntryNav = null;

  function moduleBase() {
    return (window.formulize && window.formulize.xoopsUrl) || '';
  }

  // Read the paging metadata emitted by the endpoint (null when absent, i.e. a
  // plain single-page form screen).
  function readNavMeta() {
    if (!bodyEl) return null;
    const el = bodyEl.querySelector('script.fz-multipage-nav');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
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
      .then(function () {
        // Each freshly loaded form starts as unchanged. The endpoint only defines
        // formulizechanged when it is undefined, so reset it here to clear any value
        // left over from a previous drawer session.
        window.formulizechanged = 0;
        currentEntryNav = readNavMeta();
        applyDrawerMeta();
        renderEntryFooter();
      })
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

  // Build the drawer footer controls for the loaded entry form. Single-page forms get
  // Cancel + Save; multi-page forms (per the fz-multipage-nav metadata) get Previous, a
  // "Page X of Y" indicator, and Next or Finish (when the next step is the thanks page).
  function renderEntryFooter() {
    if (!footEl) return;
    footEl.innerHTML = '';

    var notice = document.createElement('span');
    notice.className = 'fz-drawer__notice js-drawer-notice';
    footEl.appendChild(notice);

    var nav = currentEntryNav;
    var multiPage = nav && nav.totalPages > 1;

    if (!multiPage) {
      footEl.appendChild(makeButton('Cancel', 'fz-btn fz-btn--ghost', closeEntryDrawer));
      footEl.appendChild(makeButton('Save', 'fz-btn fz-btn--primary', saveEntryFromDrawer));
      return;
    }

    if (nav.previousPage) {
      footEl.appendChild(makeButton('‹ Previous', 'fz-btn fz-btn--ghost', function () {
        goToPage(nav.previousPage);
      }));
    }

    var indicator = document.createElement('span');
    indicator.className = 'fz-drawer__page-indicator';
    indicator.textContent = 'Page ' + nav.currentPage + ' of ' + nav.totalPages;
    footEl.appendChild(indicator);

    if (nav.nextIsThanks) {
      footEl.appendChild(makeButton('Finish', 'fz-btn fz-btn--primary', finishDrawer));
    } else {
      footEl.appendChild(makeButton('Next ›', 'fz-btn fz-btn--primary', function () {
        goToPage(nav.nextPage);
      }));
    }
  }

  function makeButton(label, className, onClick) {
    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = className;
    btn.textContent = label;
    btn.addEventListener('click', onClick);
    return btn;
  }

  // Briefly surface a message in the drawer footer (e.g. "No changes to save").
  function showDrawerNotice(message) {
    var el = footEl ? footEl.querySelector('.js-drawer-notice') : null;
    if (!el) return;
    el.textContent = message;
    clearTimeout(showDrawerNotice._timer);
    showDrawerNotice._timer = setTimeout(function () { el.textContent = ''; }, 3000);
  }

  // Formulize only flags a form as changed once a field is touched; mirror that so we
  // never do a pointless no-op save (which would also bypass required-field validation,
  // since validation only runs when something changed).
  function formHasChanges() {
    return !(typeof window.formulizechanged !== 'undefined' && !window.formulizechanged);
  }

  // Run the current page's validation function and flush any CKEditors. Returns false
  // when validation fails (so the caller should stay on the page).
  //
  // Formulize gates its generated field validation behind `formulizechanged`, so a page
  // that has not been touched skips all its required-field checks. For navigation we want
  // required fields enforced regardless, so we force the flag true around the validation
  // call only — the real change state is restored afterwards so the save decision is
  // unaffected (an untouched page is still treated as "no changes" and not re-saved).
  function validateCurrentForm(form) {
    var validateFn = window['xoopsFormValidate_' + form.id];
    var ok = true;
    if (typeof validateFn === 'function') {
      var savedChanged = window.formulizechanged;
      window.formulizechanged = 1;
      try { ok = !!validateFn(form); } finally { window.formulizechanged = savedChanged; }
    }
    if (ok && typeof updateCKEditors === 'function') { updateCKEditors(); }
    return ok;
  }

  // Set the drawer header from the title metadata the endpoint emits.
  function applyDrawerMeta() {
    if (!bodyEl || !titleEl) return;
    var el = bodyEl.querySelector('script.fz-drawer-meta');
    if (!el) return;
    try {
      var meta = JSON.parse(el.textContent);
      if (meta && typeof meta.title === 'string') titleEl.textContent = meta.title;
    } catch (e) { /* ignore */ }
  }

  // POST the current page's fields to readelements.php to persist them. Returns the
  // jqXHR so callers can chain. The entry id is carried in the field names, so this
  // works for both new and existing entries.
  function saveCurrentPage(form) {
    // hidden inputs (tokens) must be enabled so they are included in the FormData
    form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
    var saveUrl = moduleBase() + SAVE_ENDPOINT +
      '?fid='  + encodeURIComponent(form.getAttribute('data-fid') || '') +
      '&frid=' + encodeURIComponent(form.getAttribute('data-frid') || 0);
    return jQuery.post({
      url: saveUrl,
      data: new FormData(form),
      cache: false,
      contentType: false,
      processData: false
    });
  }

  // Release locks, close the drawer, and refresh the list. Used after the final save.
  function closeAndRefresh() {
    releaseEntryLocks();
    closeDrawer();
    currentEntryNav = null;
    if (typeof window.formulize.onEntrySaved === 'function') {
      window.formulize.onEntrySaved();
    }
  }

  // Save a single-page entry, then close and refresh.
  function saveEntryFromDrawer() {
    if (typeof jQuery === 'undefined') return;
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    if (!form) return;
    if (!formHasChanges()) {
      showDrawerNotice('No changes to save.');
      return;
    }
    if (!validateCurrentForm(form)) return;
    saveCurrentPage(form).then(closeAndRefresh);
  }

  // Navigate to another page of a multi-page entry form. Navigation (forwards or
  // backwards) validates the current page first and blocks on invalid required fields,
  // matching how Formulize behaves elsewhere. If the page has changes it is saved as
  // part of the same request — the endpoint runs readelements.php and then renders the
  // target page; otherwise we just fetch the target page. A new entry created on the
  // first save is carried into later pages by the endpoint, so no id tracking is needed.
  function goToPage(targetPage) {
    if (typeof jQuery === 'undefined' || !currentEntryNav) return;
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    if (!form || !validateCurrentForm(form)) return;

    var changed = formHasChanges();
    var url = moduleBase() + ENDPOINT +
      '?sid='      + encodeURIComponent(currentEntryNav.screenId) +
      '&fid='      + encodeURIComponent(form.getAttribute('data-fid') || '') +
      '&frid='     + encodeURIComponent(form.getAttribute('data-frid') || 0) +
      '&entry_id=' + encodeURIComponent(currentEntryNav.entryId || '') +
      '&page='     + encodeURIComponent(targetPage) +
      '&prevpage=' + encodeURIComponent(currentEntryNav.currentPage) +
      '&formname=' + FORM_NAME;

    var opts = { credentials: 'same-origin' };
    if (changed) {
      form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
      var fd = new FormData(form);
      fd.append('formulize_save', '1');
      opts.method = 'POST';
      opts.body = fd;
    }

    releaseEntryLocks(); // release the current page's locks before swapping it out
    bodyEl.innerHTML = '<div class="fz-drawer__loading">Loading…</div>';

    fetch(url, opts)
      .then(function (r) { return r.text(); })
      .then(function (html) { return injectFragment(bodyEl, html); })
      .then(function () {
        window.formulizechanged = 0;
        currentEntryNav = readNavMeta();
        applyDrawerMeta();
        renderEntryFooter();
        if (bodyEl) bodyEl.scrollTop = 0;
      })
      .catch(function () {
        bodyEl.innerHTML = '<div class="fz-drawer__loading">Could not load page.</div>';
      });
  }

  // Finish a multi-page entry: save the final page (if changed) then close and refresh.
  // The thanks page is never requested — in elements-only mode it renders empty — so
  // finishing is just a save-and-close on the last real page.
  function finishDrawer() {
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    if (!form || !formHasChanges()) { closeAndRefresh(); return; }
    if (!validateCurrentForm(form)) return;
    saveCurrentPage(form).then(closeAndRefresh);
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
    currentEntryNav = null;
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
