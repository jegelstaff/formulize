/**
 * Formulize right slide-out drawer — the shared component.
 *
 * This is the drawer mechanism itself: the markup, the open/close/resize
 * interaction, the fetch-and-inject of server-rendered form fragments, the
 * multi-page paging, the subform drill-down, the save round trip, and the
 * list-view wiring that makes a list's edit icon open an entry here instead of
 * navigating away. It is theme independent — every theme gets the same drawer,
 * and a theme that does nothing at all still gets a working, presentable one
 * from the base skin in templates/css/formulize.css.
 *
 * What a theme owns is the skin: colour, typography, shadows, the metrics of
 * the header/footer, and how its own form styling lands inside
 * `.formulize-drawer__body`. Everything a theme needs to hook is a class on the
 * markup built below, plus the CSS custom properties declared on
 * `.formulize-drawer` in the base stylesheet.
 *
 * The markup is created lazily, the first time something asks the drawer to
 * open, so a page that never uses it carries no extra DOM and no theme has to
 * add anything to its template to "support" the drawer.
 *
 * Public API (window.formulize.drawer):
 *   open({ title, html, footerHtml })  - show arbitrary content
 *   openEntry({ fid, frid, sid, entryId, title }) - load a Formulize entry form
 *   openAI()                           - show the embedded AI assistant
 *   close()                            - close whichever panel is showing
 *   saveEntry()                        - save the loaded entry form
 *   subformAction(action, args)        - called by the elements-only endpoint's
 *                                        subform stubs (add/edit/delete/clone)
 *   initListView({ fid, frid, editDestination })
 *                                      - wire a list of entries to the drawer
 *   isOpen()                           - is a drawer panel currently showing?
 *
 * Host hooks (window.formulize.*):
 *   onEntrySaved()                     - a top-level entry was saved and the drawer closed
 *   onDrawerClosed()                   - the drawer session ended (saved or cancelled);
 *                                        fires once, then is cleared
 */
(function () {
    'use strict';

    window.formulize = window.formulize || {};
    if (window.formulize.drawer) { return; } // already initialised on this page

    // ---- Configuration ---------------------------------------------------------

    var ENDPOINT      = '/modules/formulize/include/formdisplay-elementsonly.php';
    var SAVE_ENDPOINT = '/modules/formulize/include/readelements.php';
    var FORM_NAME     = 'formulize_drawer';
    // drawer=1 makes the AI page render as a bare fragment (no theme, no header/footer)
    var AI_ENDPOINT   = '/ai/?drawer=1';

    // Where this site lives. Derived from this script's own src so the drawer works
    // in any theme without the theme having to publish XOOPS_URL for it; the
    // window.formulize.xoopsUrl fallback covers a host that inlines this file.
    var moduleBase = (function () {
        var src = (document.currentScript && document.currentScript.src) || '';
        var marker = '/modules/formulize/include/js/drawer.js';
        var cut = src.indexOf(marker);
        if (cut !== -1) { return src.slice(0, cut); }
        return (window.formulize && window.formulize.xoopsUrl) || '';
    }());

    // UI strings. English by default; a host can localise by publishing
    // window.formulize.drawerStrings (PHP does exactly that from the language files).
    // Merged lazily, on first use, so the host's script tag can come after this file.
    var DEFAULT_STRINGS = {
        save:            'Save',
        cancel:          'Cancel',
        back:            'Back',
        close:           'Close panel',
        loading:         'Loading…',
        loadFailed:      'Could not load form.',
        aiLoadFailed:    'Could not load the AI assistant.',
        aiTitle:         'AI Assistant',
        detailPanel:     'Detail panel',
        noChanges:       'No changes to save.',
        discardChanges:  'Discard unsaved changes to this entry?',
        confirmDelete:   'Are you sure you want to delete the checked entries?',
        confirmDuplicate:'Are you sure you want to duplicate the checked entries?',
        page:            'Page',
        of:              'of'
    };

    var mergedStrings = null;
    function S() {
        if (!mergedStrings) {
            mergedStrings = Object.assign({}, DEFAULT_STRINGS, window.formulize.drawerStrings || {});
        }
        return mergedStrings;
    }

    // Drag-resize limits. MIN_WIDTH and the 92vw cap mirror the min-width/max-width
    // declared on .formulize-drawer in the base stylesheet.
    var MIN_WIDTH        = 320;
    var MOBILE_BREAKPOINT = 768;
    var AI_DEFAULT_WIDTH = '640px'; // chat needs more room than the entry-form default
    var WIDTH_STORAGE_KEY    = 'fz-drawer-width';
    var AI_WIDTH_STORAGE_KEY = 'fz-drawer-width-ai';

    // ---- DOM -------------------------------------------------------------------

    var drawer = null, scrim = null, titleEl = null, bodyEl = null, aiBodyEl = null,
        footEl = null, backBtn = null, closeBtn = null, resizeHandle = null;

    var ICON_BACK  = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polyline points="15 18 9 12 15 6"/></svg>';
    var ICON_CLOSE = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';

    // Build the drawer once, on first use. Themes skin what this produces; nothing
    // here is theme specific, which is the point — no theme template has to carry
    // drawer markup for the drawer to exist.
    function ensureDom() {
        if (drawer) { return true; }
        if (!document.body) { return false; }

        scrim = document.createElement('div');
        scrim.className = 'formulize-drawer-scrim';
        scrim.setAttribute('aria-hidden', 'true');
        scrim.hidden = true;

        drawer = document.createElement('aside');
        drawer.className = 'formulize-drawer';
        drawer.id = 'formulize-drawer';
        drawer.setAttribute('role', 'dialog');
        drawer.setAttribute('aria-modal', 'true');
        drawer.setAttribute('aria-label', S().detailPanel);
        drawer.hidden = true;
        drawer.innerHTML =
            '<div class="formulize-drawer__resize-handle"></div>' +
            '<div class="formulize-drawer__head">' +
                '<button type="button" class="formulize-drawer__btn formulize-drawer__btn--ghost formulize-drawer__btn--icon formulize-drawer__back" aria-label="' + escapeAttr(S().back) + '" hidden>' + ICON_BACK + '</button>' +
                '<span class="formulize-drawer__title"></span>' +
                '<div class="formulize-drawer__spacer"></div>' +
                '<button type="button" class="formulize-drawer__btn formulize-drawer__btn--ghost formulize-drawer__btn--icon formulize-drawer__close" aria-label="' + escapeAttr(S().close) + '">' + ICON_CLOSE + '</button>' +
            '</div>' +
            '<div class="formulize-drawer__body"></div>' +
            // The AI assistant gets its own body so viewing an entry doesn't destroy
            // the conversation: both panels persist, and the drawer shows one or the other.
            '<div class="formulize-drawer__body formulize-drawer__body--ai" hidden></div>' +
            '<div class="formulize-drawer__foot"></div>';

        document.body.appendChild(scrim);
        document.body.appendChild(drawer);

        titleEl      = drawer.querySelector('.formulize-drawer__title');
        bodyEl       = drawer.querySelector('.formulize-drawer__body:not(.formulize-drawer__body--ai)');
        aiBodyEl     = drawer.querySelector('.formulize-drawer__body--ai');
        footEl       = drawer.querySelector('.formulize-drawer__foot');
        backBtn      = drawer.querySelector('.formulize-drawer__back');
        closeBtn     = drawer.querySelector('.formulize-drawer__close');
        resizeHandle = drawer.querySelector('.formulize-drawer__resize-handle');

        closeBtn.addEventListener('click', closeCurrentDrawer);
        backBtn.addEventListener('click', handleBack);
        scrim.addEventListener('click', closeCurrentDrawer);
        initResize();
        applyStoredDrawerWidth();
        window.addEventListener('resize', applyStoredDrawerWidth);
        return true;
    }

    function escapeAttr(s) {
        return String(s).replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
    }

    // ---- Width -----------------------------------------------------------------

    // What the drawer is currently showing: 'entry' (a Formulize entry form, the
    // common case) or 'ai' (the embedded AI assistant). The two have their own body
    // elements and never share state.
    var drawerMode = 'entry';

    var isMobileDrawer  = function () { return window.innerWidth <= MOBILE_BREAKPOINT; };
    var maxDrawerWidth  = function () { return window.innerWidth * 0.92; };
    var widthStorageKey = function () { return (drawerMode === 'ai' ? AI_WIDTH_STORAGE_KEY : WIDTH_STORAGE_KEY); };

    // Persist the user's drag-resized width across reloads. Desktop only — below the
    // breakpoint the drawer is full-width and must not carry a stale inline width
    // into that layout. Entry forms and the chat want very different widths, so each
    // remembers its own and resizing one never moves the other.
    function applyStoredDrawerWidth() {
        if (!drawer) { return; }
        if (isMobileDrawer()) {
            drawer.style.width = '';
            return;
        }
        var saved = null;
        try { saved = localStorage.getItem(widthStorageKey()); } catch (e) { /* ignore */ }
        if (saved) {
            drawer.style.width = saved;
        } else if (drawerMode === 'ai') {
            drawer.style.width = AI_DEFAULT_WIDTH;
        } else {
            drawer.style.width = ''; // fall back to the stylesheet's default
        }
    }

    // Drag-resize via the visible handle on the drawer's left edge. (Native CSS
    // `resize` was tried and dropped — its hit target is a near-invisible corner icon
    // flush against the viewport edge, and can sit underneath other fixed chrome.)
    function initResize() {
        var startX = 0;
        var startWidth = 0;

        function onMove(e) {
            var delta = startX - e.clientX; // dragging toward screen center widens the drawer
            var width = Math.min(Math.max(startWidth + delta, MIN_WIDTH), maxDrawerWidth());
            drawer.style.width = width + 'px';
        }

        function onUp() {
            document.removeEventListener('pointermove', onMove);
            document.removeEventListener('pointerup', onUp);
            resizeHandle.classList.remove('formulize-drawer__resize-handle--active');
            document.body.style.removeProperty('cursor');
            document.body.style.removeProperty('user-select');
            try { localStorage.setItem(widthStorageKey(), drawer.style.width); } catch (e) { /* ignore */ }
        }

        resizeHandle.addEventListener('pointerdown', function (e) {
            if (isMobileDrawer()) { return; }
            e.preventDefault();
            startX = e.clientX;
            startWidth = drawer.getBoundingClientRect().width;
            resizeHandle.classList.add('formulize-drawer__resize-handle--active');
            document.body.style.cursor = 'ew-resize';
            document.body.style.userSelect = 'none';
            document.addEventListener('pointermove', onMove);
            document.addEventListener('pointerup', onUp);
        });
    }

    // ---- Open / close ----------------------------------------------------------

    // Show one of the two body panels and set the mode. The hidden panel keeps its
    // DOM, which is the whole point for AI: an in-progress conversation survives a
    // detour through an entry form.
    function setDrawerMode(mode) {
        drawerMode = mode;
        if (bodyEl)   { bodyEl.hidden   = (mode === 'ai'); }
        if (aiBodyEl) { aiBodyEl.hidden = (mode !== 'ai'); }
        applyStoredDrawerWidth(); // each mode has its own remembered width
    }

    function revealDrawer() {
        drawer.hidden = false;
        scrim.hidden = false;
        document.documentElement.style.overflow = 'hidden';
    }

    function openDrawer(opts) {
        if (!ensureDom()) { return; }
        opts = opts || {};
        setDrawerMode('entry');
        titleEl.textContent = opts.title || '';
        bodyEl.innerHTML = opts.html || '';
        footEl.innerHTML = opts.footerHtml || '';
        revealDrawer();
    }

    // Is a drawer panel currently showing? Hosts use this to tell "the user is acting
    // inside the drawer" from "the user is acting on the page behind it" — see the
    // subform stubs in formdisplay-elementsonly.php.
    function drawerIsOpen() {
        return !!(drawer && !drawer.hidden);
    }

    function closeDrawer() {
        if (!drawer) { return; }
        drawer.hidden = true;
        scrim.hidden = true;
        document.documentElement.style.overflow = '';
    }

    // ---- Entry form state ------------------------------------------------------

    // Paging state for the currently loaded entry form. Populated from the
    // fz-multipage-nav metadata the endpoint emits; null for single-page forms.
    var currentEntryNav = null;

    // Subform drill-down state: the drawer shows one entry at a time, but a subform
    // element lets the user descend into a sub entry (and its subs, recursively).
    // currentFrame describes the entry loaded now; drawerStack holds its ancestors,
    // deepest last, so Back can restore each parent (re-fetched, so subform tables
    // reflect edits made below them).
    // frame: { params: {sid, fid, frid, entryId, subformElementId}, page, title }
    var drawerStack = [];
    var currentFrame = null;

    // Read the paging metadata emitted by the endpoint (null when absent, i.e. a
    // plain single-page form screen).
    function readNavMeta() {
        if (!bodyEl) { return null; }
        var el = bodyEl.querySelector('script.fz-multipage-nav');
        if (!el) { return null; }
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    // Read the metadata the endpoint emits alongside each form (title, rendered
    // fid/entryId, and — for the subform add flow — the resolved parent entry id).
    function readDrawerMeta() {
        if (!bodyEl) { return null; }
        var el = bodyEl.querySelector('script.fz-drawer-meta');
        if (!el) { return null; }
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    // Build an endpoint URL from frame params (+ optional page for multi-page forms).
    function buildEntryUrl(p, page) {
        var params = [];
        if (p.fid)     { params.push('fid=' + encodeURIComponent(p.fid)); }
        if (p.frid)    { params.push('frid=' + encodeURIComponent(p.frid)); }
        if (p.sid)     { params.push('sid=' + encodeURIComponent(p.sid)); }
        if (p.entryId) { params.push('entry_id=' + encodeURIComponent(p.entryId)); }
        if (p.subformElementId) { params.push('subformElementId=' + encodeURIComponent(p.subformElementId)); }
        if (page)      { params.push('page=' + encodeURIComponent(page)); }
        params.push('formname=' + FORM_NAME);
        return moduleBase + ENDPOINT + '?' + params.join('&');
    }

    // Fetch a server-rendered fragment into a drawer panel and run its scripts. The
    // panel-agnostic half of loading: both the entry form and the AI assistant use
    // it, and each layers its own bookkeeping on top. Rejects if the load failed,
    // having already put a message in the panel.
    function loadFragmentInto(targetEl, url, fetchOpts, failureMessage) {
        targetEl.innerHTML = '<div class="formulize-drawer__loading">' + S().loading + '</div>';
        var opts = fetchOpts || {};
        opts.credentials = 'same-origin';
        return fetch(url, opts)
            .then(function (r) { return r.text(); })
            .then(function (html) { return injectFragment(targetEl, html); })
            .catch(function (e) {
                targetEl.innerHTML = '<div class="formulize-drawer__loading">' + failureMessage + '</div>';
                throw e;
            });
    }

    // Fetch an entry form into the drawer body and re-sync all per-form state
    // (change flag, paging metadata, title, current-frame bookkeeping, footer, Back
    // control). Every drawer load — open, page turn, subform descend, back — funnels
    // through here. Returns a promise of the fz-drawer-meta object (null on failure).
    function fetchIntoDrawer(url, fetchOpts) {
        if (!bodyEl) { return Promise.resolve(null); }
        pruneDeadEditors();
        return loadFragmentInto(bodyEl, url, fetchOpts, S().loadFailed)
            .then(function () {
                // Each freshly loaded form starts as unchanged. The endpoint only defines
                // formulizechanged when it is undefined, so reset it here to clear any value
                // left over from a previous drawer session.
                window.formulizechanged = 0;
                currentEntryNav = readNavMeta();
                var meta = readDrawerMeta();
                if (meta && typeof meta.title === 'string') { titleEl.textContent = meta.title; }
                if (meta && currentFrame) {
                    // sync what the server actually rendered (it resolves screens/new ids itself)
                    if (meta.fid) { currentFrame.params.fid = meta.fid; }
                    if (meta.entryId && meta.entryId !== 'new') { currentFrame.params.entryId = meta.entryId; }
                }
                if (currentFrame) { currentFrame.page = currentEntryNav ? currentEntryNav.currentPage : 0; }
                renderEntryFooter();
                updateBackButton();
                bodyEl.scrollTop = 0;
                return meta;
            })
            .catch(function () {
                // loadFragmentInto has already put the failure message in the panel;
                // callers just need the null.
                return null;
            });
    }

    // Load a Formulize form/entry into the drawer as an editable, elements-only form.
    // opts: { fid, frid, entryId, sid }. The form submits in the standard Formulize
    // manner (POST to readelements.php) rather than navigating a full page.
    function openEntryInDrawer(opts) {
        if (typeof jQuery === 'undefined') { return; }
        if (!ensureDom()) { return; }
        opts = opts || {};
        drawerStack = [];
        currentFrame = { params: { fid: opts.fid, frid: opts.frid, sid: opts.sid, entryId: opts.entryId,
                                   subformElementId: opts.subformElementId }, page: 0 };
        openDrawer({ title: opts.title || '' });
        updateBackButton();
        fetchIntoDrawer(buildEntryUrl(currentFrame.params));
    }

    // Show the embedded AI assistant in the drawer. Unlike an entry, the chat is
    // loaded once and then kept: reopening just reveals the panel again, so the
    // conversation and any half-typed message are exactly where they were left.
    // (Across a page reload it is localStorage in ai/index.php that restores the
    // conversation, not this.) The assistant brings its own send controls, so the
    // drawer footer stays empty.
    var aiLoaded = false;

    function openAIInDrawer() {
        if (!ensureDom()) { return; }
        setDrawerMode('ai');
        titleEl.textContent = S().aiTitle;
        footEl.innerHTML = '';
        backBtn.hidden = true;
        revealDrawer();
        if (aiLoaded) { return; }
        aiLoaded = true;
        loadFragmentInto(aiBodyEl, moduleBase + AI_ENDPOINT, null, S().aiLoadFailed)
            .catch(function () {
                aiLoaded = false; // let the next open retry rather than showing the error forever
            });
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
    var executedFragmentScripts = {};

    function injectFragment(container, html) {
        container.innerHTML = html;
        var scripts = Array.prototype.slice.call(container.querySelectorAll('script'));
        return scripts.reduce(function (chain, oldScript) {
            return chain.then(function () {
                return new Promise(function (resolve) {
                    if (oldScript.src && executedFragmentScripts[oldScript.src]) {
                        oldScript.parentNode.removeChild(oldScript);
                        resolve();
                        return;
                    }
                    var s = document.createElement('script');
                    for (var a = 0; a < oldScript.attributes.length; a++) {
                        s.setAttribute(oldScript.attributes[a].name, oldScript.attributes[a].value);
                    }
                    if (oldScript.src) {
                        executedFragmentScripts[oldScript.src] = true;
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

    // ---- Footer ----------------------------------------------------------------

    // Build the drawer footer controls for the loaded entry form. Single-page forms
    // get Cancel + Save; multi-page forms (per the fz-multipage-nav metadata) get
    // Previous, a "Page X of Y" indicator, and Next or Finish (when the next step is
    // the thanks page).
    function renderEntryFooter() {
        if (!footEl || drawerMode === 'ai') { return; } // the AI panel has its own controls
        footEl.innerHTML = '';

        var notice = document.createElement('span');
        notice.className = 'formulize-drawer__notice';
        footEl.appendChild(notice);

        var nav = currentEntryNav;
        var multiPage = nav && nav.totalPages > 1;

        if (!multiPage) {
            if (drawerStack.length) {
                footEl.appendChild(makeButton('‹ ' + S().back, 'ghost', goBack));
            } else {
                footEl.appendChild(makeButton(S().cancel, 'ghost', closeEntryDrawer));
            }
            footEl.appendChild(makeButton(S().save, 'primary', saveEntryFromDrawer));
            return;
        }

        // Labels come from the endpoint, which resolves the screen's configured button
        // text and falls back to the standard Formulize language constants — the same
        // precedence the full page rendering uses. An empty label means the screen has
        // that button switched off, so we render nothing (matching core).
        if (nav.previousPage && nav.previousButtonText) {
            footEl.appendChild(makeButton('‹ ' + nav.previousButtonText, 'ghost', function () {
                goToPage(nav.previousPage);
            }));
        }

        var indicator = document.createElement('span');
        indicator.className = 'formulize-drawer__page-indicator';
        indicator.textContent = (nav.pageWord || S().page) + ' ' + nav.currentPage + ' ' +
                                (nav.ofWord || S().of) + ' ' + nav.totalPages;
        footEl.appendChild(indicator);

        if (nav.nextButtonText) {
            footEl.appendChild(makeButton(
                nav.nextIsThanks ? nav.nextButtonText : nav.nextButtonText + ' ›',
                'primary',
                nav.nextIsThanks ? finishDrawer : function () { goToPage(nav.nextPage); }
            ));
        }
    }

    function makeButton(label, variant, onClick) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'formulize-drawer__btn formulize-drawer__btn--' + variant;
        btn.textContent = label;
        btn.addEventListener('click', onClick);
        return btn;
    }

    // Briefly surface a message in the drawer footer (e.g. "No changes to save").
    function showDrawerNotice(message) {
        var el = footEl ? footEl.querySelector('.formulize-drawer__notice') : null;
        if (!el) { return; }
        el.textContent = message;
        clearTimeout(showDrawerNotice._timer);
        showDrawerNotice._timer = setTimeout(function () { el.textContent = ''; }, 3000);
    }

    // ---- Validation / save -----------------------------------------------------

    // Formulize only flags a form as changed once a field is touched; mirror that so
    // we never do a pointless no-op save (which would also bypass required-field
    // validation, since validation only runs when something changed).
    function formHasChanges() {
        return !(typeof window.formulizechanged !== 'undefined' && !window.formulizechanged);
    }

    // Run the current page's validation function and flush any CKEditors. Returns
    // false when validation fails (so the caller should stay on the page).
    //
    // Formulize gates its generated field validation behind `formulizechanged`, so a
    // page that has not been touched skips all its required-field checks. For
    // navigation we want required fields enforced regardless, so we force the flag
    // true around the validation call only — the real change state is restored
    // afterwards so the save decision is unaffected (an untouched page is still
    // treated as "no changes" and not re-saved).
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

    // The header Back control is only shown while descended into a sub entry.
    function updateBackButton() {
        if (backBtn) { backBtn.hidden = drawerStack.length === 0; }
    }

    // Drop CKEditor instances whose textarea was removed with the previous fragment.
    // The endpoint's init code skips ids already present in window.CKEditors, so a
    // dead instance would otherwise block the editor from initialising when the same
    // entry is loaded again (e.g. descend into a sub entry, go back, descend again).
    function pruneDeadEditors() {
        if (!window.CKEditors) { return; }
        Object.keys(window.CKEditors).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || !document.body.contains(el)) {
                try {
                    var destroyed = window.CKEditors[id].destroy();
                    if (destroyed && typeof destroyed.catch === 'function') { destroyed.catch(function () {}); }
                } catch (e) { /* already gone */ }
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
        var saveUrl = moduleBase + SAVE_ENDPOINT +
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

    // Tell the host page that the drawer session it opened has finished (saved or
    // cancelled), so it can put back whatever state it handed over. The hook belongs to
    // one session, so it is cleared as it fires; a host that wants another one registers
    // it again when it next opens the drawer. (onEntrySaved is deliberately not cleared:
    // initListView registers that once for the life of the page.)
    function notifyHostClosed() {
        var hook = window.formulize.onDrawerClosed;
        window.formulize.onDrawerClosed = null;
        if (typeof hook === 'function') { hook(); }
    }

    // Release locks, close the drawer, and refresh the host (the list, normally).
    // Used after the final save.
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
        notifyHostClosed();
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
        if (!drawerStack.length) { return; }
        if (formHasChanges() && !window.confirm(S().discardChanges)) { return; }
        popToParent();
    }

    // Save a single-page entry. At the top level this closes the drawer and refreshes
    // the list; in a sub entry it returns to the parent instead.
    function saveEntryFromDrawer() {
        if (typeof jQuery === 'undefined') { return; }
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form) { return; }
        if (!formHasChanges()) {
            if (drawerStack.length) { popToParent(); return; } // nothing to save; act as "done"
            showDrawerNotice(S().noChanges);
            return;
        }
        if (!validateCurrentForm(form)) { return; }
        saveCurrentPage(form).then(drawerStack.length ? popToParent : closeAndRefresh);
    }

    // Navigate to another page of a multi-page entry form. Navigation (forwards or
    // backwards) validates the current page first and blocks on invalid required
    // fields, matching how Formulize behaves elsewhere. If the page has changes it is
    // saved as part of the same request — the endpoint runs readelements.php and then
    // renders the target page; otherwise we just fetch the target page. A new entry
    // created on the first save is carried into later pages by the endpoint, so no id
    // tracking is needed.
    function goToPage(targetPage) {
        if (typeof jQuery === 'undefined' || !currentEntryNav) { return; }
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form || !validateCurrentForm(form)) { return; }

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

    // Finish a multi-page entry: save the final page (if changed) then close and
    // refresh (or, in a sub entry, return to the parent). The thanks page is never
    // requested — in elements-only mode it renders empty — so finishing is just a
    // save-and-done on the last real page.
    function finishDrawer() {
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        var done = drawerStack.length ? popToParent : closeAndRefresh;
        if (!form || !formHasChanges()) { done(); return; }
        if (!validateCurrentForm(form)) { return; }
        saveCurrentPage(form).then(done);
    }

    // Release any entry locks acquired by the loaded form (defined by the endpoint).
    function releaseEntryLocks() {
        if (typeof window.removeDrawerEntryLocks === 'function') {
            try { window.removeDrawerEntryLocks(); } catch (e) { /* nothing to release */ }
        }
    }

    // ---- Subform actions -------------------------------------------------------
    // The subform element's markup calls core's add_sub/goSub/sub_del/sub_clone. In
    // the drawer those are stubs (emitted by the elements-only endpoint) that delegate
    // here, and the drawer plays the role core's jQuery-UI modal plays on full page
    // loads: the sub entry is swapped in as the drawer's current form, with Back
    // returning to the parent. Server-side linking/deleting/cloning is the same core
    // code either way.
    function subformAction(action, args) {
        if (typeof jQuery === 'undefined' || !bodyEl) { return; }
        var form = bodyEl.querySelector('form');
        if (!form) { return; }

        if (action === 'edit') {
            // core saves the parent when drilling into a sub; mirror that so parent
            // changes aren't lost, but skip the round trip when nothing changed
            if (formHasChanges()) {
                if (!validateCurrentForm(form)) { return; }
                saveCurrentPage(form).then(function () { descendToSub(args); });
            } else {
                descendToSub(args);
            }
            return;
        }

        if (action === 'add') {
            if (!validateCurrentForm(form)) { return; }
            addSubEntry(form, args);
            return;
        }

        if (action === 'delete' || action === 'clone') {
            if (!bodyEl.querySelectorAll('.delbox:checked').length) { return; }
            var msg = action === 'delete' ? S().confirmDelete : S().confirmDuplicate;
            if (!window.confirm(msg)) { return; }
            subDeleteClone(form, action, args);
        }
    }

    // Snapshot the current entry onto the stack before loading a sub entry over it.
    function pushCurrentFrame() {
        if (!currentFrame) { return; }
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
    // creates the linked sub entries server-side, and returns the first new sub
    // entry's form, which becomes the drawer's current form.
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
        if (formHasChanges() || !parentEntryId) { fd.set('formulize_save', '1'); }

        var url = moduleBase + ENDPOINT +
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
            if (meta && meta.parentEntryId && parentFrame) { parentFrame.params.entryId = meta.parentEntryId; }
        });
    }

    // Delete or clone the checked sub entries: re-fetch the parent with the core flag
    // set; displayForm processes the flag (permission-checked) during the re-render,
    // so the response is the parent with its subform table updated. Stack is unchanged.
    function subDeleteClone(form, action, args) {
        form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
        var fd = new FormData(form); // includes the checked delbox values
        fd.set(action === 'delete' ? 'deletesubsflag' : 'clonesubsflag', args.subFid);
        if (formHasChanges()) { fd.set('formulize_save', '1'); }
        var p = currentFrame ? currentFrame.params : {};
        var page = (currentFrame && currentFrame.page > 1) ? currentFrame.page : 0;
        releaseEntryLocks();
        fetchIntoDrawer(buildEntryUrl(p, page), { method: 'POST', body: fd });
    }

    // ---- Closing ---------------------------------------------------------------

    function closeEntryDrawer() {
        releaseEntryLocks();
        if (footEl) { footEl.innerHTML = ''; }
        currentEntryNav = null;
        drawerStack = [];
        currentFrame = null;
        updateBackButton();
        closeDrawer();
        notifyHostClosed();
    }

    // Closing the AI panel must leave it intact — no lock release (it holds none) and
    // no teardown of the entry state, which belongs to the other panel and is still
    // valid.
    function closeCurrentDrawer() {
        if (drawerMode === 'ai') {
            closeDrawer();
            return;
        }
        closeEntryDrawer();
    }

    // Back only ever means "up one subform level", which is an entry-panel notion.
    function handleBack() {
        if (drawerMode === 'ai') { return; }
        goBack();
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && drawer && !drawer.hidden) { closeCurrentDrawer(); }
    });

    // ---- List of entries wiring ------------------------------------------------

    // Point a list of entries at the drawer. Core emits the call (see
    // interfaceJavascript() in entriesdisplay.php) for every theme, honouring the
    // screen's "edit destination" setting, so no theme template has to know anything
    // about the drawer to get this behaviour.
    //
    // config: { fid, frid, editDestination }
    function initListView(config) {
        config = config || {};
        // 'screen' leaves Formulize's default goDetails/addNew in place, so the edit
        // icon navigates to the full form screen as it always has.
        if (config.editDestination !== 'drawer') { return; }

        function openEntry(entryId, sid) {
            openEntryInDrawer({ fid: config.fid, frid: config.frid, entryId: entryId, sid: sid });
        }

        // Formulize's list markup calls goDetails(entry, screen) from the edit icon's
        // onclick and addNew() from the Add button. Both are plain page-navigating
        // functions defined by interfaceJavascript(); overriding them on window is what
        // redirects the same affordances into the drawer without touching the markup.
        window.goDetails = function (entryId, screen) { openEntry(entryId, screen || ''); };
        window.addNew    = function () { openEntry('', ''); };

        // After a drawer save, reload the list by submitting its controls form.
        // showLoading() captures the current scroll position and preserves all active
        // filters, sorting, and paging (they live as hidden fields in the controls
        // form), so the refreshed list reflects the change in place.
        window.formulize.onEntrySaved = function () {
            if (typeof showLoading === 'function') {
                showLoading();
            } else {
                window.location.reload();
            }
        };
    }

    // ---- Public API ------------------------------------------------------------

    window.formulize.drawer = {
        open: openDrawer,
        close: closeCurrentDrawer,
        openEntry: openEntryInDrawer,
        openAI: openAIInDrawer,
        saveEntry: saveEntryFromDrawer,
        subformAction: subformAction,
        initListView: initListView,
        isOpen: drawerIsOpen
    };

    // A theme's "open the AI assistant" affordance only has to carry the .js-open-ai
    // class and a real href to /ai/ (so it still works as a full page without JS);
    // the drawer takes it over here.
    function bindAILink() {
        var aiLink = document.querySelector('.js-open-ai');
        if (!aiLink) { return; }
        aiLink.addEventListener('click', function (e) {
            e.preventDefault();
            openAIInDrawer();
        });
    }

    // This file can be loaded from the head (footer.php publishes it site-wide) or
    // from within the page body, so cover both: wait for the document when it is
    // still parsing, bind immediately when it is not.
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindAILink);
    } else {
        bindAILink();
    }
}());
