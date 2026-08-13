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
  const backBtn = document.querySelector('.js-drawer-back');
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

  // Subform drill-down state: the drawer shows one entry at a time, but a subform
  // element lets the user descend into a sub entry (and its subs, recursively).
  // currentFrame describes the entry loaded now; drawerStack holds its ancestors,
  // deepest last, so Back can restore each parent (re-fetched, so subform tables
  // reflect edits made below them).
  // frame: { params: {sid, fid, frid, entryId, subformElementId}, page, title }
  let drawerStack = [];
  let currentFrame = null;

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

  // Build an endpoint URL from frame params (+ optional page for multi-page forms).
  function buildEntryUrl(p, page) {
    var params = [];
    if (p.fid)     params.push('fid=' + encodeURIComponent(p.fid));
    if (p.frid)    params.push('frid=' + encodeURIComponent(p.frid));
    if (p.sid)     params.push('sid=' + encodeURIComponent(p.sid));
    if (p.entryId) params.push('entry_id=' + encodeURIComponent(p.entryId));
    if (p.subformElementId) params.push('subformElementId=' + encodeURIComponent(p.subformElementId));
    if (page)      params.push('page=' + encodeURIComponent(page));
    params.push('formname=' + FORM_NAME);
    return moduleBase() + ENDPOINT + '?' + params.join('&');
  }

  // Fetch an entry form into the drawer body and re-sync all per-form state
  // (change flag, paging metadata, title, current-frame bookkeeping, footer, Back
  // control). Every drawer load — open, page turn, subform descend, back — funnels
  // through here. Returns a promise of the fz-drawer-meta object (null on failure).
  function fetchIntoDrawer(url, fetchOpts) {
    if (!bodyEl) return Promise.resolve(null);
    bodyEl.innerHTML = '<div class="fz-drawer__loading">Loading…</div>';
    pruneDeadEditors();
    var opts = fetchOpts || {};
    opts.credentials = 'same-origin';
    return fetch(url, opts)
      .then(function (r) { return r.text(); })
      .then(function (html) { return injectFragment(bodyEl, html); })
      .then(function () {
        // Each freshly loaded form starts as unchanged. The endpoint only defines
        // formulizechanged when it is undefined, so reset it here to clear any value
        // left over from a previous drawer session.
        window.formulizechanged = 0;
        currentEntryNav = readNavMeta();
        var meta = readDrawerMeta();
        if (meta && typeof meta.title === 'string' && titleEl) titleEl.textContent = meta.title;
        if (meta && currentFrame) {
          // sync what the server actually rendered (it resolves screens/new ids itself)
          if (meta.fid) currentFrame.params.fid = meta.fid;
          if (meta.entryId && meta.entryId !== 'new') currentFrame.params.entryId = meta.entryId;
        }
        if (currentFrame) currentFrame.page = currentEntryNav ? currentEntryNav.currentPage : 0;
        renderEntryFooter();
        updateBackButton();
        bodyEl.scrollTop = 0;
        return meta;
      })
      .catch(function () {
        bodyEl.innerHTML = '<div class="fz-drawer__loading">Could not load form.</div>';
        return null;
      });
  }

  // Load a Formulize form/entry into the drawer as an editable, elements-only form.
  // opts: { fid, frid, entryId, sid }. The form submits in the standard Formulize
  // manner (POST to readelements.php) rather than navigating a full page.
  function openEntryInDrawer(opts) {
    if (typeof jQuery === 'undefined') return;
    opts = opts || {};
    drawerStack = [];
    currentFrame = { params: { fid: opts.fid, frid: opts.frid, sid: opts.sid, entryId: opts.entryId }, page: 0 };
    openDrawer({ title: opts.title || '' });
    updateBackButton();
    fetchIntoDrawer(buildEntryUrl(currentFrame.params));
  }

  // Inject an HTML fragment and execute its <script> tags in document order,
  // awaiting external (src) scripts before continuing. Setting innerHTML does not
  // run scripts, so we re-create each <script> node sequentially. This guarantees
  // dependencies like conditional.js are defined before the inline init code that
  // populates their globals runs — the ordering a real document gives for free,
  // but which jQuery .load() / innerHTML do not.
  // External libraries already executed for an earlier fragment (e.g. ckeditor.js
  // when descending through several rich-text forms) must not run twice — CKEditor
  // hard-errors on duplicate module registration — so remember executed srcs and
  // skip them; their globals persist even though the old script node was wiped.
  const executedFragmentScripts = new Set();

  function injectFragment(container, html) {
    container.innerHTML = html;
    var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
    return scripts.reduce(function (chain, oldScript) {
      return chain.then(function () {
        return new Promise(function (resolve) {
          if (oldScript.src && executedFragmentScripts.has(oldScript.src)) {
            oldScript.parentNode.removeChild(oldScript);
            resolve();
            return;
          }
          var s = document.createElement('script');
          for (var a = 0; a < oldScript.attributes.length; a++) {
            s.setAttribute(oldScript.attributes[a].name, oldScript.attributes[a].value);
          }
          if (oldScript.src) {
            executedFragmentScripts.add(oldScript.src);
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
      if (drawerStack.length) {
        footEl.appendChild(makeButton('‹ Back', 'fz-btn fz-btn--ghost', goBack));
      } else {
        footEl.appendChild(makeButton('Cancel', 'fz-btn fz-btn--ghost', closeEntryDrawer));
      }
      footEl.appendChild(makeButton('Save', 'fz-btn fz-btn--primary', saveEntryFromDrawer));
      return;
    }

    // Labels come from the endpoint, which resolves the screen's configured button text
    // and falls back to the standard Formulize language constants — the same precedence
    // the full page rendering uses. An empty label means the screen has that button
    // switched off, so we render nothing (matching core).
    if (nav.previousPage && nav.previousButtonText) {
      footEl.appendChild(makeButton('‹ ' + nav.previousButtonText, 'fz-btn fz-btn--ghost', function () {
        goToPage(nav.previousPage);
      }));
    }

    var indicator = document.createElement('span');
    indicator.className = 'fz-drawer__page-indicator';
    indicator.textContent = (nav.pageWord || 'Page') + ' ' + nav.currentPage + ' ' +
                            (nav.ofWord || 'of') + ' ' + nav.totalPages;
    footEl.appendChild(indicator);

    if (nav.nextButtonText) {
      footEl.appendChild(makeButton(
        nav.nextIsThanks ? nav.nextButtonText : nav.nextButtonText + ' ›',
        'fz-btn fz-btn--primary',
        nav.nextIsThanks ? finishDrawer : function () { goToPage(nav.nextPage); }
      ));
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

  // Read the metadata the endpoint emits alongside each form (title, rendered
  // fid/entryId, and — for the subform add flow — the resolved parent entry id).
  function readDrawerMeta() {
    if (!bodyEl) return null;
    var el = bodyEl.querySelector('script.fz-drawer-meta');
    if (!el) return null;
    try { return JSON.parse(el.textContent); } catch (e) { return null; }
  }

  // The header Back control is only shown while descended into a sub entry.
  function updateBackButton() {
    if (backBtn) backBtn.hidden = drawerStack.length === 0;
  }

  // Drop CKEditor instances whose textarea was removed with the previous fragment.
  // The endpoint's init code skips ids already present in window.CKEditors, so a dead
  // instance would otherwise block the editor from initialising when the same entry
  // is loaded again (e.g. descend into a sub entry, go back, descend again).
  function pruneDeadEditors() {
    if (!window.CKEditors) return;
    Object.keys(window.CKEditors).forEach(function (id) {
      var el = document.getElementById(id);
      if (!el || !document.body.contains(el)) {
        try {
          var destroyed = window.CKEditors[id].destroy();
          if (destroyed && typeof destroyed.catch === 'function') destroyed.catch(function () {});
        } catch (_) { /* already gone */ }
        delete window.CKEditors[id];
      }
    });
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
    drawerStack = [];
    currentFrame = null;
    updateBackButton();
    if (typeof window.formulize.onEntrySaved === 'function') {
      window.formulize.onEntrySaved();
    }
  }

  // Return to the parent entry (re-fetched, so its subform table reflects whatever
  // happened below it). Assumes any saving/validation has already been handled.
  function popToParent() {
    if (!drawerStack.length) { closeAndRefresh(); return; }
    releaseEntryLocks();
    currentFrame = drawerStack.pop();
    fetchIntoDrawer(buildEntryUrl(currentFrame.params, currentFrame.page > 1 ? currentFrame.page : 0));
  }

  // Back control: leave the sub entry without saving (warn if it has changes).
  function goBack() {
    if (!drawerStack.length) return;
    if (formHasChanges() && !window.confirm('Discard unsaved changes to this entry?')) return;
    popToParent();
  }

  // Save a single-page entry. At the top level this closes the drawer and refreshes
  // the list; in a sub entry it returns to the parent instead.
  function saveEntryFromDrawer() {
    if (typeof jQuery === 'undefined') return;
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    if (!form) return;
    if (!formHasChanges()) {
      if (drawerStack.length) { popToParent(); return; } // nothing to save; act as "done"
      showDrawerNotice('No changes to save.');
      return;
    }
    if (!validateCurrentForm(form)) return;
    saveCurrentPage(form).then(drawerStack.length ? popToParent : closeAndRefresh);
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
    var url = buildEntryUrl({
      sid:     currentEntryNav.screenId,
      fid:     form.getAttribute('data-fid') || '',
      frid:    form.getAttribute('data-frid') || 0,
      entryId: currentEntryNav.entryId || ''
    }, targetPage) + '&prevpage=' + encodeURIComponent(currentEntryNav.currentPage);

    var opts = null;
    if (changed) {
      form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
      var fd = new FormData(form);
      fd.append('formulize_save', '1');
      opts = { method: 'POST', body: fd };
    }

    releaseEntryLocks(); // release the current page's locks before swapping it out
    fetchIntoDrawer(url, opts);
  }

  // Finish a multi-page entry: save the final page (if changed) then close and refresh
  // (or, in a sub entry, return to the parent). The thanks page is never requested —
  // in elements-only mode it renders empty — so finishing is just a save-and-done on
  // the last real page.
  function finishDrawer() {
    var form = bodyEl ? bodyEl.querySelector('form') : null;
    var done = drawerStack.length ? popToParent : closeAndRefresh;
    if (!form || !formHasChanges()) { done(); return; }
    if (!validateCurrentForm(form)) return;
    saveCurrentPage(form).then(done);
  }

  // Release any entry locks acquired by the loaded form (defined by the endpoint).
  function releaseEntryLocks() {
    if (typeof window.removeDrawerEntryLocks === 'function') {
      try { window.removeDrawerEntryLocks(); } catch (_) {}
    }
  }

  // ---- Subform actions --------------------------------------------------------
  // The subform element's markup calls core's add_sub/goSub/sub_del/sub_clone. In
  // the drawer those are stubs (emitted by the endpoint) that delegate here, and the
  // drawer plays the role core's jQuery-UI modal plays on full page loads: the sub
  // entry is swapped in as the drawer's current form, with Back returning to the
  // parent. Server-side linking/deleting/cloning is the same core code either way.
  function subformAction(action, args) {
    if (typeof jQuery === 'undefined' || !bodyEl) return;
    var form = bodyEl.querySelector('form');
    if (!form) return;

    if (action === 'edit') {
      // core saves the parent when drilling into a sub; mirror that so parent
      // changes aren't lost, but skip the round trip when nothing changed
      if (formHasChanges()) {
        if (!validateCurrentForm(form)) return;
        saveCurrentPage(form).then(function () { descendToSub(args); });
      } else {
        descendToSub(args);
      }
      return;
    }

    if (action === 'add') {
      if (!validateCurrentForm(form)) return;
      addSubEntry(form, args);
      return;
    }

    if (action === 'delete' || action === 'clone') {
      if (!bodyEl.querySelectorAll('.delbox:checked').length) return;
      var msg = action === 'delete'
        ? 'Are you sure you want to delete the checked entries?'
        : 'Are you sure you want to duplicate the checked entries?';
      if (!window.confirm(msg)) return;
      subDeleteClone(form, action, args);
    }
  }

  // Snapshot the current entry onto the stack before loading a sub entry over it.
  function pushCurrentFrame() {
    if (!currentFrame) return;
    currentFrame.title = titleEl ? titleEl.textContent : '';
    drawerStack.push(currentFrame);
  }

  // Open an existing sub entry as the drawer's current form. The endpoint resolves
  // the subform element's configured display screen from subformElementId.
  function descendToSub(args) {
    releaseEntryLocks();
    pushCurrentFrame();
    currentFrame = { params: { fid: args.subFid, entryId: args.entryId, subformElementId: args.subformElementId }, page: 0 };
    fetchIntoDrawer(buildEntryUrl(currentFrame.params));
  }

  // "Add new" on a subform element: one request that saves the parent's page (when
  // changed, and always for a brand-new parent — it must exist to be linked to),
  // creates the linked sub entries server-side, and returns the first new sub entry's
  // form, which becomes the drawer's current form.
  function addSubEntry(form, args) {
    form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
    var fd = new FormData(form);
    var parentFid  = form.getAttribute('data-fid')  || args.parentFid || '';
    var parentFrid = form.getAttribute('data-frid') || args.frid      || 0;
    var parentEntryId = (currentFrame && currentFrame.params.entryId) ? currentFrame.params.entryId : '';
    fd.set('target_sub', args.subFid);
    fd.set('target_sub_fid', parentFid);
    fd.set('target_sub_frid', parentFrid);
    fd.set('target_sub_mainformentry', parentEntryId);
    fd.set('target_sub_subformelement', args.subformElementId);
    fd.set('numsubents', args.numEntries || 1);
    if (formHasChanges() || !parentEntryId) fd.set('formulize_save', '1');

    var url = moduleBase() + ENDPOINT +
      '?fid=' + encodeURIComponent(parentFid) +
      '&frid=' + encodeURIComponent(parentFrid) +
      '&formname=' + FORM_NAME;

    releaseEntryLocks();
    var parentFrame = currentFrame;
    pushCurrentFrame();
    currentFrame = { params: { fid: args.subFid, entryId: '', subformElementId: args.subformElementId }, page: 0 };
    fetchIntoDrawer(url, { method: 'POST', body: fd }).then(function (meta) {
      // a brand-new parent was saved as part of this request; record its real id so
      // Back reloads the saved entry rather than a blank form
      if (meta && meta.parentEntryId && parentFrame) parentFrame.params.entryId = meta.parentEntryId;
    });
  }

  // Delete or clone the checked sub entries: re-fetch the parent with the core flag
  // set; displayForm processes the flag (permission-checked) during the re-render, so
  // the response is the parent with its subform table updated. Stack is unchanged.
  function subDeleteClone(form, action, args) {
    form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
    var fd = new FormData(form); // includes the checked delbox values
    fd.set(action === 'delete' ? 'deletesubsflag' : 'clonesubsflag', args.subFid);
    if (formHasChanges()) fd.set('formulize_save', '1');
    var p = currentFrame ? currentFrame.params : {};
    var page = (currentFrame && currentFrame.page > 1) ? currentFrame.page : 0;
    releaseEntryLocks();
    fetchIntoDrawer(buildEntryUrl(p, page), { method: 'POST', body: fd });
  }

  function closeEntryDrawer() {
    releaseEntryLocks();
    if (footEl) footEl.innerHTML = '';
    currentEntryNav = null;
    drawerStack = [];
    currentFrame = null;
    updateBackButton();
    closeDrawer();
  }

  if (closeBtn) closeBtn.addEventListener('click', closeEntryDrawer);
  if (backBtn)  backBtn.addEventListener('click', goBack);
  if (scrim)    scrim.addEventListener('click', closeEntryDrawer);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !drawer.hasAttribute('hidden')) closeEntryDrawer();
  });

  window.formulize = window.formulize || {};
  window.formulize.drawer = {
    open: openDrawer,
    close: closeEntryDrawer,
    openEntry: openEntryInDrawer,
    saveEntry: saveEntryFromDrawer,
    subformAction: subformAction
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
