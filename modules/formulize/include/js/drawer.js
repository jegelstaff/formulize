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
 *   saveEntry()                        - save the loaded entry form and leave it
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

    // Where forms are loaded from. Writes go to the addresses each form carries instead; see
    // writeAddress and saveAddress.
    var ENDPOINT      = '/modules/formulize/include/formdisplay-elementsonly.php';
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
        of:              'of',
        formPages:       'Form pages',
        fixPageErrors:   'Please complete the required fields on this page first.'
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
    var MIN_WIDTH        = 390;
    var MOBILE_BREAKPOINT = 768;
    // Width of the DRAWER (not the window) at which its footer stops being the full
    // screen form's mobile action bar and becomes the full screen form's ordinary one:
    // content-width buttons on a single row instead of two 44px tap targets to a row.
    // Keyed off the drawer because the drawer is drag-resizable, so the window's width
    // says nothing about how much room the footer actually has. Same 768 the mobile
    // layout everywhere else in Formulize turns on at.
    var WIDE_BREAKPOINT  = 768;
    var AI_DEFAULT_WIDTH = '640px'; // chat needs more room than the entry-form default
    var WIDTH_STORAGE_KEY    = 'formulize-drawer-width';
    var AI_WIDTH_STORAGE_KEY = 'formulize-drawer-width-ai';

    // ---- DOM -------------------------------------------------------------------

    var drawer = null, scrim = null, titleEl = null, bodyEl = null, aiBodyEl = null,
        footEl = null, backBtn = null, closeBtn = null, resizeHandle = null, tabsEl = null,
        busyEl = null;

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
            // The multi-page tab strip, mirroring the full screen form's tabs. It lives
            // here rather than in a theme's screen template because it is chrome around
            // the form, not part of it: the templates render inside __body and inside the
            // posted <form>, so tabs emitted there would scroll away with the fields and
            // be submitted with them. Built by renderPageTabs; hidden when the screen is
            // not configured for tabs, or the form has only one page.
            '<nav class="formulize-drawer__tabs" aria-label="' + escapeAttr(S().formPages) + '" hidden></nav>' +
            '<div class="formulize-drawer__body fz-content"></div>' +
            // The AI assistant gets its own body so viewing an entry doesn't destroy
            // the conversation: both panels persist, and the drawer shows one or the other.
            '<div class="formulize-drawer__body formulize-drawer__body--ai" hidden></div>' +
            '<div class="formulize-drawer__foot"></div>' +
            // Formulize's saving or working animation, shown over the faded form while the
            // drawer waits on the server (see showDrawerBusy). Full screen these are
            // #savingmessage and #workingmessage; the drawer saves and loads over XHR and so
            // needs its own copy of the same assets in its own chrome.
            '<div class="formulize-drawer__busy" aria-hidden="true"></div>';

        document.body.appendChild(scrim);
        document.body.appendChild(drawer);

        titleEl      = drawer.querySelector('.formulize-drawer__title');
        bodyEl       = drawer.querySelector('.formulize-drawer__body:not(.formulize-drawer__body--ai)');
        aiBodyEl     = drawer.querySelector('.formulize-drawer__body--ai');
        footEl       = drawer.querySelector('.formulize-drawer__foot');
        tabsEl       = drawer.querySelector('.formulize-drawer__tabs');
        backBtn      = drawer.querySelector('.formulize-drawer__back');
        closeBtn     = drawer.querySelector('.formulize-drawer__close');
        resizeHandle = drawer.querySelector('.formulize-drawer__resize-handle');
        busyEl       = drawer.querySelector('.formulize-drawer__busy');

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
            // A remembered width always wins over the default, but never takes the
            // drawer below the minimum — a value stored before the minimum was
            // raised would otherwise fight the stylesheet's min-width (issue #124).
            drawer.style.width = Math.max(parseFloat(saved) || MIN_WIDTH, MIN_WIDTH) + 'px';
        } else if (drawerMode === 'ai') {
            drawer.style.width = AI_DEFAULT_WIDTH;
        } else {
            drawer.style.width = ''; // fall back to the stylesheet's default
        }
        syncDrawerWidthClass();
    }

    // Flag the drawer as "wide" once it is at least as wide as the mobile breakpoint, so
    // the stylesheet can give its footer the full screen form's ordinary button treatment
    // instead of the mobile action bar's (PR #127 review). Measured rather than read off
    // the inline style, because the width can come from the stylesheet's 30vw default, a
    // remembered px value, or a drag in progress. A hidden element measures 0, so this is
    // re-run on reveal.
    function syncDrawerWidthClass() {
        if (!drawer) { return; }
        var wide = !drawer.hidden && drawer.getBoundingClientRect().width >= WIDE_BREAKPOINT;
        drawer.classList.toggle('formulize-drawer--wide', wide);
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
            syncDrawerWidthClass(); // the footer's button treatment follows the drag live
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
        if (tabsEl && mode === 'ai') { tabsEl.hidden = true; } // the AI panel has no pages

        applyStoredDrawerWidth(); // each mode has its own remembered width
    }

    function revealDrawer() {
        drawer.hidden = false;
        scrim.hidden = false;
        document.documentElement.style.overflow = 'hidden';
        syncDrawerWidthClass(); // only measurable now that it is displayed
    }

    function openDrawer(opts) {
        if (!ensureDom()) { return; }
        opts = opts || {};
        setDrawerMode('entry');
        titleEl.textContent = opts.title || '';
        bodyEl.innerHTML = opts.html || '';
        footEl.innerHTML = opts.footerHtml || '';
        if (tabsEl) { tabsEl.hidden = true; } // static content has no paging metadata
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
        hideDrawerBusy();
        stopHoldingScroll();
        drawer.hidden = true;
        scrim.hidden = true;
        document.documentElement.style.overflow = '';
    }

    // ---- Entry form state ------------------------------------------------------

    // Paging state for the currently loaded entry form. Populated from the
    // formulize-multipage-nav metadata the endpoint emits; null for single-page forms.
    var currentEntryNav = null;

    // formulize-printable-view metadata the endpoint emits: where the printable view button
    // posts, and what. Null when the form has no printable view button.
    var currentPrintableView = null;

    // Whether anything has been saved since this drawer session opened. A save that
    // leaves the drawer open still has to be reflected in the list behind it when the
    // drawer is eventually closed.
    var savedDuringSession = false;

    // Subform drill-down state: the drawer shows one entry at a time, but a subform
    // element lets the user descend into a sub entry (and its subs, recursively).
    // currentFrame describes the entry loaded now; drawerStack holds its ancestors,
    // deepest last, so Back can restore each parent (re-fetched, so subform tables
    // reflect edits made below them).
    // frame: { params: {sid, fid, frid, entryId, subformElementId, entryFid, entryFrid}, page, title }
    // entryFid/entryFrid name the form and relationship entryId belongs to, when that may
    // not be the screen's form (an entry picked in a list); they only last until the first load.
    var drawerStack = [];
    var currentFrame = null;

    // Read the paging metadata emitted by the endpoint (null when absent, i.e. a
    // plain single-page form screen).
    function readNavMeta() {
        if (!bodyEl) { return null; }
        var el = bodyEl.querySelector('script.formulize-multipage-nav');
        if (!el) { return null; }
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    // Read the metadata the endpoint emits alongside each form (title, rendered
    // fid/entryId, and — for the subform add flow — the resolved parent entry id).
    function readDrawerMeta() {
        if (!bodyEl) { return null; }
        var el = bodyEl.querySelector('script.formulize-drawer-meta');
        if (!el) { return null; }
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    // Read the printable view metadata the endpoint emits (null when absent).
    function readPrintableViewMeta() {
        if (!bodyEl) { return null; }
        var el = bodyEl.querySelector('script.formulize-printable-view');
        if (!el) { return null; }
        try { return JSON.parse(el.textContent); } catch (e) { return null; }
    }

    // Where a form's writes go. The endpoint sends each form out addressed to a honeypot, as a
    // full screen form is, and puts the real addresses on it when someone first interacts with
    // it (see formdisplay-elementsonly.php), so a write sent before anyone has goes nowhere.
    // writeAddress is for a write that renders the form again, saveAddress for one that only saves.
    function writeAddress(form) {
        return form.getAttribute('action');
    }

    function saveAddress(form) {
        return form.getAttribute('data-save-action');
    }

    // Build an endpoint URL from frame params (+ optional page for multi-page forms). A write
    // passes the form's writeAddress as base; a load leaves it out.
    function buildEntryUrl(p, page, base) {
        var params = [];
        if (p.fid)     { params.push('fid=' + encodeURIComponent(p.fid)); }
        if (p.frid)    { params.push('frid=' + encodeURIComponent(p.frid)); }
        if (p.sid)     { params.push('sid=' + encodeURIComponent(p.sid)); }
        if (p.entryId) { params.push('entry_id=' + encodeURIComponent(p.entryId)); }
        if (p.subformElementId) { params.push('subformElementId=' + encodeURIComponent(p.subformElementId)); }
        if (p.entryFid)  { params.push('entry_fid=' + encodeURIComponent(p.entryFid)); }
        if (p.entryFrid) { params.push('entry_frid=' + encodeURIComponent(p.entryFrid)); }
        if (page)      { params.push('page=' + encodeURIComponent(page)); }
        // a sub entry: the server gives it a Back button when its screen has no close button
        if (drawerStack.length) { params.push('insub=1'); }
        params.push('formname=' + FORM_NAME);
        return (base || moduleBase + ENDPOINT) + '?' + params.join('&');
    }

    // ---- Busy: saving and loading ----------------------------------------------

    // While the drawer waits on the server, whatever it is showing stays where it is, faded
    // back and closed to clicks, under Formulize's animation: the saving one for a save, the
    // working one for a load, as full screen shows #savingmessage when a form is submitted
    // and #workingmessage when a list reloads. A theme that replaces those animations
    // replaces this one too (Lyris draws its spinner instead). The fading, and the short
    // wait before the animation appears so a quick load doesn't flash it, are in the
    // stylesheet (.formulize-drawer--busy).
    //
    // A save followed by a load - a changed entry going into a sub entry, saving and then
    // leaving - stays busy from the one into the other, so it reads as one wait rather than
    // two. The drawer is busy again before anything paints in between.
    //
    // The <img> is created on first use, so a drawer session that never waits never fetches
    // the files. footer.php publishes their URLs (formulize_savingGraphicUrl() and
    // formulize_workingGraphicUrl(), the same files full screen uses), with the english
    // files as the fallback for a host that publishes nothing.
    function showDrawerBusy(saving) {
        if (!drawer || !busyEl) { return; }
        var img = busyEl.querySelector('img');
        if (!img) {
            img = document.createElement('img');
            img.alt = '';
            busyEl.appendChild(img);
        }
        var graphic = saving ? 'saving' : 'working';
        var url = (window.formulize && window.formulize[graphic + 'GraphicUrl']) ||
                  (moduleBase + '/modules/formulize/images/' + graphic + '-english.gif');
        if (img.getAttribute('src') !== url) { img.setAttribute('src', url); }
        drawer.classList.add('formulize-drawer--busy');
        // a save holds the whole drawer, header included, as full screen does; a load can
        // still be left, by Back or by closing the drawer
        drawer.classList.toggle('formulize-drawer--saving', !!saving);
    }

    function hideDrawerBusy() {
        if (drawer) { drawer.classList.remove('formulize-drawer--busy', 'formulize-drawer--saving'); }
    }

    // Run a promise-returning request with the saving animation up for its duration.
    // Every write the drawer makes goes through here, so there is one place that decides
    // what "saving" looks like and none of the callers have to remember to clear it.
    function whileSaving(run) {
        showDrawerBusy(true);
        var done = function (v) { hideDrawerBusy(); return v; };
        var failed = function (e) { hideDrawerBusy(); throw e; };
        try {
            return Promise.resolve(run()).then(done, failed);
        } catch (e) {
            hideDrawerBusy();
            throw e;
        }
    }

    // New content in the drawer body fades in rather than appearing all at once. It is held
    // invisible from the moment it arrives (holdBodyForFadeIn, just before it goes in), while
    // its scripts run and the tabs and footer are rebuilt around it, and then let go
    // (fadeInBody), so it comes in whole. The tabs and footer come back from faded with it.
    function holdBodyForFadeIn() {
        if (bodyEl) { bodyEl.classList.add('formulize-drawer__body--entering'); }
    }

    function fadeInBody() {
        if (!bodyEl) { return; }
        bodyEl.classList.add('formulize-drawer__body--entering');
        hideDrawerBusy();
        void bodyEl.offsetHeight; // settle the held state, so letting go of it is a transition
        bodyEl.classList.remove('formulize-drawer__body--entering');
    }

    // ---- Scroll position -------------------------------------------------------

    // Put a reloaded form back where the one it replaced was scrolled to, and keep it there
    // until the form has finished taking shape. The fragment's scripts have run by the time
    // it first goes back, but some of what they start finishes later and changes the form's
    // height: a rich text editor taking over its textarea, conditional elements arriving from
    // the server, images loading. Any of those can move the form under the reader, or leave
    // it short of the position because the form was not yet tall enough to scroll that far.
    // So the position is put back every time the form's height changes, until the height has
    // held still for SCROLL_SETTLE_MS, or the reader scrolls, clicks, touches or types, at
    // which point the reader is in charge. Full screen does the same with a second attempt
    // once the page has loaded (the yposition restore in formdisplay.php).
    var SCROLL_SETTLE_MS = 1500;
    var releaseScrollHold = null;

    function holdScrollPosition(top) {
        stopHoldingScroll();
        bodyEl.scrollTop = top;
        if (!top || typeof ResizeObserver === 'undefined') { return; }
        var settled = setTimeout(stopHoldingScroll, SCROLL_SETTLE_MS);
        var observer = new ResizeObserver(function () {
            bodyEl.scrollTop = top;
            clearTimeout(settled);
            settled = setTimeout(stopHoldingScroll, SCROLL_SETTLE_MS);
        });
        // not 'scroll': setting scrollTop here fires that too
        var takeOver = ['wheel', 'touchstart', 'pointerdown', 'keydown'];
        releaseScrollHold = function () {
            observer.disconnect();
            clearTimeout(settled);
            takeOver.forEach(function (type) { bodyEl.removeEventListener(type, stopHoldingScroll); });
        };
        // the body is a fixed size; it is what is inside it that grows
        Array.prototype.forEach.call(bodyEl.children, function (child) { observer.observe(child); });
        takeOver.forEach(function (type) { bodyEl.addEventListener(type, stopHoldingScroll, { passive: true }); });
    }

    function stopHoldingScroll() {
        if (releaseScrollHold) {
            releaseScrollHold();
            releaseScrollHold = null;
        }
    }

    // ---- Fragment loading ------------------------------------------------------

    // Fetch a server-rendered fragment into a drawer panel and run its scripts. The
    // panel-agnostic half of loading: both the entry form and the AI assistant use
    // it, and each layers its own bookkeeping on top. Rejects if the load failed,
    // having already put a message in the panel.
    //
    // options, all optional:
    //   keepContent - leave what is already in the panel alone until the new fragment
    //                 arrives, instead of blanking it to the "Loading…" placeholder. Entry
    //                 forms use it: the form being left stays on screen, faded, under the
    //                 busy animation (see showDrawerBusy).
    //   onArrive    - called just before the fragment, or the failure message, goes into
    //                 the panel.
    function loadFragmentInto(targetEl, url, fetchOpts, failureMessage, options) {
        options = options || {};
        if (!options.keepContent) {
            targetEl.innerHTML = '<div class="formulize-drawer__loading">' + S().loading + '</div>';
        }
        var arrive = function () { if (typeof options.onArrive === 'function') { options.onArrive(); } };
        var opts = fetchOpts || {};
        opts.credentials = 'same-origin';
        return fetch(url, opts)
            .then(function (r) { return r.text(); })
            .then(function (html) { arrive(); return injectFragment(targetEl, html); })
            .catch(function (e) {
                arrive();
                targetEl.innerHTML = '<div class="formulize-drawer__loading">' + failureMessage + '</div>';
                throw e;
            });
    }

    // Fetch an entry form into the drawer body and re-sync all per-form state
    // (change flag, paging metadata, title, current-frame bookkeeping, footer, Back
    // control). Every drawer load — open, page turn, subform descend, back — funnels
    // through here. Returns a promise of the formulize-drawer-meta object (null on failure).
    // A POST is always a write: the fragment endpoint runs readelements.php before it
    // renders. So every drawer load that carries one shows the saving animation, which
    // covers save-in-place, a page hop that saves the page it is leaving, and the subform
    // add/delete/clone round trips -- the same set of actions that make full screen
    // submit the page and show its own saving graphic. Every other load shows the working
    // animation. Either way the form being left stays on screen, faded, until the new one
    // fades in over it.
    //
    // keepScroll puts the new form back at the scroll position of the one being left, for
    // a load that re-renders the same page (a save that stays, a subform delete or clone).
    // Full screen does the same through yposition, and only when the save lands on the page
    // it came from; every other load starts at the top. See holdScrollPosition.
    function fetchIntoDrawer(url, fetchOpts, keepScroll) {
        if (!bodyEl) { return Promise.resolve(null); }
        var scrollTo = keepScroll ? bodyEl.scrollTop : 0;
        stopHoldingScroll(); // the form a previous load was holding in place is on its way out
        var saving = !!(fetchOpts && String(fetchOpts.method || '').toUpperCase() === 'POST');
        showDrawerBusy(saving);
        var arrive = function () {
            holdBodyForFadeIn();
            pruneDeadEditors(); // only now: the form being left keeps its editors while it waits, faded
        };
        return loadFragmentInto(bodyEl, url, fetchOpts, S().loadFailed, { keepContent: true, onArrive: arrive })
            .then(function () {
                // Each freshly loaded form starts as unchanged. The endpoint only defines
                // formulizechanged when it is undefined, so reset it here to clear any value
                // left over from a previous drawer session.
                window.formulizechanged = 0;
                currentEntryNav = readNavMeta();
                currentPrintableView = readPrintableViewMeta();
                var meta = readDrawerMeta();
                if (meta && typeof meta.title === 'string') { titleEl.textContent = meta.title; }
                if (meta && currentFrame) {
                    // sync what the server actually rendered (it resolves screens/new ids itself)
                    if (meta.fid) { currentFrame.params.fid = meta.fid; }
                    if (meta.entryId && meta.entryId !== 'new') { currentFrame.params.entryId = meta.entryId; }
                    // the entry id is now the rendered form's own, so it must not be mapped again
                    delete currentFrame.params.entryFid;
                    delete currentFrame.params.entryFrid;
                }
                if (currentFrame) { currentFrame.page = currentEntryNav ? currentEntryNav.currentPage : 0; }
                renderPageTabs();
                renderEntryFooter();
                updateBackButton();
                holdScrollPosition(scrollTo);
                fadeInBody();
                return meta;
            })
            .catch(function () {
                // loadFragmentInto has already put the failure message in the panel;
                // callers just need the null.
                fadeInBody();
                return null;
            });
    }

    // Load a Formulize form/entry into the drawer as an editable, elements-only form.
    // opts: { fid, frid, entryId, sid, entryFid, entryFrid }. The form submits in the standard Formulize
    // manner (POST to readelements.php) rather than navigating a full page.
    function openEntryInDrawer(opts) {
        if (typeof jQuery === 'undefined') { return; }
        if (!ensureDom()) { return; }
        opts = opts || {};
        drawerStack = [];
        savedDuringSession = false;
        currentFrame = { params: { fid: opts.fid, frid: opts.frid, sid: opts.sid, entryId: opts.entryId,
                                   subformElementId: opts.subformElementId,
                                   entryFid: opts.entryFid, entryFrid: opts.entryFrid }, page: 0 };
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

    // The footer's buttons come from the theme's drawer bottomtemplate, as the full screen
    // bar's come from its multiPage bottomtemplate: the server decides which buttons the
    // form has and what each says, the template puts them in the theme's order, and the
    // drawer moves them into the footer and gives each one its action (renderEntryFooter).
    // formulize-multipage-nav carries the paging state the actions and the tab strip need.
    //
    // Draw the multi-page tab strip, mirroring the full screen form's tabs: one tab per
    // reachable page, in page order, the current one marked. The server decides which
    // pages are reachable (conditions, private elements) with the same function the full
    // screen strip uses, so the two surfaces always show the same tabs.
    //
    // Unlike the full screen strip there is no leading "save and leave" tab: that is not
    // a page. The server makes it the footer's save and leave button instead, so it is a
    // button, and only ever in one place.
    function renderPageTabs() {
        if (!tabsEl) { return; }
        tabsEl.innerHTML = '';

        var nav = currentEntryNav;
        if (drawerMode === 'ai' || !drawerTabsVisible(nav)) {
            tabsEl.hidden = true;
            return;
        }
        tabsEl.hidden = false;

        var activeTab = null;
        nav.pages.forEach(function (page) {
            var isActive = (page.page === nav.currentPage);
            var tab = document.createElement('button');
            tab.type = 'button';
            tab.className = 'formulize-drawer__tab' + (isActive ? ' formulize-drawer__tab--active' : '');
            tab.textContent = page.title || ((nav.pageWord || S().page) + ' ' + page.page);
            // the wrapped strip shows every title in full, so this is belt and braces --
            // it still gives the tab a tooltip and a stable accessible name, and keeps
            // long titles readable if the scrolling (clipping) variant is switched back on
            tab.title = tab.textContent;
            tab.setAttribute('aria-label', tab.textContent);
            if (isActive) {
                tab.setAttribute('aria-current', 'page');
                tab.disabled = true;
                activeTab = tab;
            } else {
                tab.addEventListener('click', function () { goToPageFromTab(page.page); });
            }
            tabsEl.appendChild(tab);
        });

        // Keep the page you are on visible. The strip wraps now, so every tab is on
        // screen and this is a no-op against a container that does not scroll -- it is
        // left in place, and deliberately harmless, so re-enabling the scrolling variant
        // (see .formulize-drawer__tabs--scroll in formulize.css) needs no JS change.
        if (activeTab && typeof activeTab.scrollIntoView === 'function') {
            try { activeTab.scrollIntoView({ block: 'nearest', inline: 'center' }); }
            catch (e) { activeTab.scrollIntoView(false); }
        }
    }

    // A tab click is a page move like any other, but it needs to say so when it is
    // refused: a button that does nothing reads as broken, and a tab that does nothing
    // reads worse, because the tab visibly fails to activate.
    function goToPageFromTab(targetPage) {
        if (!goToPage(targetPage)) { showDrawerNotice(S().fixPageErrors); }
    }

    // A screen configured for buttons only (navstyle 0) or for no navigation at all
    // (navstyle 3) gets no tabs here, exactly as it gets none full screen. Neither does a
    // form with only one reachable page, so a single page form is untouched.
    function drawerTabsVisible(nav) {
        return !!(nav && nav.showTabs && nav.totalPages > 1 && nav.pages && nav.pages.length > 1);
    }

    function renderEntryFooter() {
        if (!footEl || drawerMode === 'ai') { return; } // the AI panel has its own controls
        footEl.innerHTML = '';

        // What came back is not a form - an error, or word that the entry can't be shown - so
        // there is nothing to save or page through, and the header's close and Back are all it
        // needs. Left empty, the footer is hidden altogether (.formulize-drawer__foot:empty).
        if (!bodyEl || !bodyEl.querySelector('form')) { return; }

        var notice = document.createElement('span');
        notice.className = 'formulize-drawer__notice';
        footEl.appendChild(notice);

        var nav = currentEntryNav;
        var inSub = drawerStack.length > 0;

        // Page meta leads, as it does in the full screen bar (the Lyris bottomtemplate
        // emits $pageIndicator/$pageSelector before any button). With tabs on show the
        // strip already says which page you are on, so the footer does not repeat it.
        // (The non-tab case is where a combined page indicator/selector control belongs
        // - see issue #109.)
        if (nav && nav.totalPages > 1 && !drawerTabsVisible(nav)) {
            var indicator = document.createElement('span');
            indicator.className = 'formulize-drawer__page-indicator';
            indicator.textContent = (nav.pageWord || S().page) + ' ' + nav.currentPage + ' ' +
                                    (nav.ofWord || S().of) + ' ' + nav.totalPages;
            footEl.appendChild(indicator);
        }

        // Then the buttons, in the order the theme's drawer bottomtemplate printed them.
        // They are `<input type="button" class="formulize-form-submit-button">` named for
        // what they do, the same element the full screen bar uses, so everything visual
        // comes from the rules the themes already apply to that bar, including the role
        // colours keyed off the name (Lyris paints `[name^="save"]` accent). Subform entries
        // rendered inline in the form print the template too, with no buttons in it, so
        // every container goes once its buttons are out.
        Array.prototype.slice.call(bodyEl.querySelectorAll('.formulize-drawer-buttons')).forEach(function (container) {
            Array.prototype.slice.call(container.querySelectorAll('.formulize-form-submit-button')).forEach(function (btn) {
                btn.addEventListener('click', footerAction(btn.name, nav, inSub));
                footEl.appendChild(btn);
            });
            container.parentNode.removeChild(container);
        });
    }

    // What each footer button does, by its name (see formulize_drawerButtonTemplateVariables
    // in formdisplay.php), mirroring what the same button does full screen:
    //
    //   printbutton  the printable view, in a new window.
    //   leave        save and leave. Full screen's page one previous button and the leading
    //                tab of a tabbed screen send the user to the thanks page; the drawer
    //                saves and closes.
    //   prev         submitForm(previousPage) — save this page and go back one.
    //   save         submitForm(currentPage, currentPage) — save in place, stay here.
    //   close        verifyDone() — leave without saving, confirming first if there are
    //                edits. In a sub entry "leaving" is returning to the parent, exactly as
    //                full screen's close does there, and it is the Back button when the
    //                screen has no close button.
    //   next         submitForm(nextPage) — save this page and go on; when the next page is
    //                the thanks page this is the finish action, which in the drawer means
    //                saving and closing (the thanks page renders empty in elements-only
    //                mode, so there is nothing to show).
    function footerAction(name, nav, inSub) {
        switch (name) {
            case 'printbutton':
                return openPrintableView;
            case 'leave':
                return saveEntryFromDrawer;
            case 'prev':
                return function () { goToPage(nav.previousPage); };
            case 'save':
                return saveAndStay;
            case 'close':
                return inSub ? goBack : closeEntryDrawer;
            case 'next':
                return nav.nextIsThanks ? finishDrawer : function () { goToPage(nav.nextPage); };
        }
        return function () {};
    }

    // Open the printable view of the loaded entry, posting exactly what the full screen
    // printable view button posts. The form is built here rather than server side because
    // the fragment is injected inside the drawer's own form, and a nested form would be
    // dropped by the parser.
    function openPrintableView() {
        var printableView = currentPrintableView;
        if (!printableView || !printableView.action) { return; }
        var fields = printableView.fields || {};
        var form = document.createElement('form');
        form.method = 'post';
        form.action = printableView.action;
        form.target = '_blank';
        form.style.display = 'none';
        Object.keys(fields).forEach(function (name) {
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = name;
            input.value = (fields[name] === null || typeof fields[name] === 'undefined') ? '' : String(fields[name]);
            form.appendChild(input);
        });
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
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

    // Gate for every route that abandons the loaded entry: the footer Cancel button,
    // the header close button, a click on the scrim, and Escape. Returns false when
    // the user chose to stay, in which case the caller must leave the drawer exactly
    // as it is (open, with the edits still in the fields).
    //
    // The `form` test is what keeps this from crying wolf. formHasChanges() reports
    // "changed" when window.formulizechanged is undefined, which is right for a loaded
    // form (the shared global is reset to 0 by fetchIntoDrawer after every load, so
    // undefined can only mean something went wrong) but wrong for a drawer that is not
    // showing an entry form at all -- a failed fetch, or arbitrary content pushed in
    // through open(). Those have nothing to discard and must close silently.
    function confirmDiscardIfChanged() {
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form || !formHasChanges()) { return true; }
        return window.confirm(S().discardChanges);
    }

    // Run the current page's validation function and flush any CKEditors. Returns
    // false when validation fails (so the caller should stay on the page).
    //
    // Formulize gates every generated field check behind `formulizechanged`: each
    // element's validation body is emitted wrapped in `if(formulizechanged) { ... }`
    // (see _drawValidationJS in formdisplay.php), so an untouched page passes
    // validation outright, required fields included. Full screen inherits that gate
    // as-is -- a page hop (multipage_boilerplate.php's submitForm) and a save
    // (validateAndSubmit) both call the very same xoopsFormValidate_formulize_mainform
    // with whatever formulizechanged currently is, and neither overrides it. So full
    // screen lets you leave an untouched page with empty required fields, and stops
    // you only once you have actually edited something.
    //
    // This must not second-guess that. An earlier version forced the flag to 1 around
    // the call so navigation would always enforce required fields, which made the
    // drawer refuse tab hops that full screen allows (issue reported on PR #127).
    // Honouring the real flag is what keeps the two surfaces identical.
    function validateCurrentForm(form) {
        var validateFn = window['xoopsFormValidate_' + form.id];
        var ok = true;
        if (typeof validateFn === 'function') { ok = !!validateFn(form); }
        if (ok && typeof updateCKEditors === 'function') { updateCKEditors(); }
        return ok;
    }

    // The header Back control is only shown while descended into a sub entry.
    function updateBackButton() {
        if (backBtn) { backBtn.hidden = drawerStack.length === 0; }
    }

    // Drop CKEditor instances whose textarea was removed with a previous fragment, or is
    // about to be: this runs as a new fragment arrives, while the form it replaces is still
    // in the drawer body (fetchIntoDrawer keeps it on screen through the load), so every
    // editor in the body is on its way out too. The endpoint's init code skips ids already
    // present in window.CKEditors, so a dead instance would otherwise block the editor from
    // initialising when the same entry is rendered again: after a save that stays, the rich
    // text field came back as a bare textarea; or descend into a sub entry, go back, descend
    // again.
    function pruneDeadEditors() {
        if (!window.CKEditors) { return; }
        Object.keys(window.CKEditors).forEach(function (id) {
            var el = document.getElementById(id);
            if (!el || !document.body.contains(el) || bodyEl.contains(el)) {
                try {
                    var destroyed = window.CKEditors[id].destroy();
                    if (destroyed && typeof destroyed.catch === 'function') { destroyed.catch(function () {}); }
                } catch (e) { /* already gone */ }
                delete window.CKEditors[id];
            }
        });
    }

    // POST the current page's fields to readelements.php to persist them. Returns a
    // promise so callers can chain. The entry id is carried in the field names, so this
    // works for both new and existing entries. The saving animation is up for the round
    // trip, as it is full screen (PR #127 review) — this is the drawer's other write
    // path; the fragment endpoint's POSTs are covered in fetchIntoDrawer.
    function saveCurrentPage(form) {
        // hidden inputs (tokens) must be enabled so they are included in the FormData
        form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
        var saveUrl = saveAddress(form) +
            '?fid='  + encodeURIComponent(form.getAttribute('data-fid') || '') +
            '&frid=' + encodeURIComponent(form.getAttribute('data-frid') || 0);
        return whileSaving(function () {
            return jQuery.post({
                url: saveUrl,
                data: new FormData(form),
                cache: false,
                contentType: false,
                processData: false
            });
        }).then(function (response) {
            // Tell the AI chat's activity log what was just saved. readelements.php cannot report
            // this itself: it has to return an empty body, because the inline-edit caller in
            // entriesdisplay.php alerts any non-empty response to the user as an error. Nothing
            // needs to come back from the server anyway - this side is the one that knows what it
            // asked to be saved.
            if (window.formulize && typeof window.formulize.recordActivity === 'function') {
                var frame = currentFrame && currentFrame.params ? currentFrame.params : {};
                window.formulize.recordActivity({
                    event: 'saving-data',
                    fid:   form.getAttribute('data-fid') || frame.fid || null,
                    sid:   frame.sid || null,
                    entry: frame.entryId || null
                });
            }
            return response;
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
        savedDuringSession = false;
        releaseEntryLocks();
        closeDrawer();
        currentEntryNav = null;
        currentPrintableView = null;
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
        if (!confirmDiscardIfChanged()) { return; }
        popToParent();
    }

    // Save and stay on the entry — what the screen's Save button does full screen
    // (submitForm to the same page / validateAndSubmit without 'leave'). The save goes
    // through the elements-only endpoint, which persists the page and re-renders it, so a
    // brand new entry comes back as the saved entry rather than a fresh blank form.
    function saveAndStay() {
        if (typeof jQuery === 'undefined') { return; }
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form || !currentFrame) { return; }
        if (currentEntryNav) {
            if (!validateCurrentForm(form)) { return; }
            if (formHasChanges()) { savedDuringSession = true; }
            goToPage(currentEntryNav.currentPage);
            return;
        }
        if (!formHasChanges()) { showDrawerNotice(S().noChanges); return; }
        if (!validateCurrentForm(form)) { return; }
        form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
        var fd = new FormData(form);
        fd.append('formulize_save', '1');
        savedDuringSession = true;
        releaseEntryLocks(); // the re-render acquires its own
        fetchIntoDrawer(buildEntryUrl(currentFrame.params, 0, writeAddress(form)), { method: 'POST', body: fd }, true);
    }

    // Save and leave the entry. At the top level this closes the drawer and refreshes
    // the list; in a sub entry it returns to the parent instead.
    function saveEntryFromDrawer() {
        if (typeof jQuery === 'undefined') { return; }
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form) { return; }
        // nothing to save, so this is just "leave" - which is what the full screen save
        // and leave button does with an untouched form too
        if (!formHasChanges()) {
            if (drawerStack.length) { popToParent(); } else { closeAndRefresh(); }
            return;
        }
        if (!validateCurrentForm(form)) { return; }
        savedDuringSession = true;
        saveCurrentPage(form).then(drawerStack.length ? popToParent : closeAndRefresh);
    }

    // Navigate to another page of a multi-page entry form. Navigation (forwards or
    // backwards) validates the current page first and blocks on invalid required
    // fields, matching how Formulize behaves elsewhere. If the page has changes it is
    // saved as part of the same request — the endpoint runs readelements.php and then
    // renders the target page; otherwise we just fetch the target page. A new entry
    // created on the first save is carried into later pages by the endpoint, so no id
    // tracking is needed.
    // Returns false when the move was refused because the current page does not
    // validate, so a caller can say so rather than looking inert.
    function goToPage(targetPage) {
        if (typeof jQuery === 'undefined' || !currentEntryNav) { return false; }
        var form = bodyEl ? bodyEl.querySelector('form') : null;
        if (!form || !validateCurrentForm(form)) { return false; }

        var changed = formHasChanges();
        var url = buildEntryUrl({
            sid:     currentEntryNav.screenId,
            fid:     form.getAttribute('data-fid') || '',
            frid:    form.getAttribute('data-frid') || 0,
            entryId: currentEntryNav.entryId || ''
        }, targetPage, changed ? writeAddress(form) : '') + '&prevpage=' + encodeURIComponent(currentEntryNav.currentPage);

        var opts = null;
        if (changed) {
            form.querySelectorAll('input[type="hidden"]').forEach(function (i) { i.disabled = false; });
            var fd = new FormData(form);
            fd.append('formulize_save', '1');
            opts = { method: 'POST', body: fd };
        }

        releaseEntryLocks(); // release the current page's locks before swapping it out
        // a save in place (saveAndStay) asks for the page it is on, and stays where it was scrolled
        fetchIntoDrawer(url, opts, parseInt(targetPage, 10) === parseInt(currentEntryNav.currentPage, 10));
        return true;
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

        var url = writeAddress(form) +
            '?fid=' + encodeURIComponent(parentFid) +
            '&frid=' + encodeURIComponent(parentFrid) +
            '&insub=1' + // the new sub entry comes back in this response
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
        fetchIntoDrawer(buildEntryUrl(p, page, writeAddress(form)), { method: 'POST', body: fd }, true);
    }

    // ---- Closing ---------------------------------------------------------------

    // Every way of dismissing the entry panel ends up here -- the screen's done/close
    // button, the header close button, the scrim, Escape, and the public close() -- so
    // the unsaved-changes warning lives here rather than being repeated at each call
    // site. Save and leave does not come through here (closeAndRefresh closes directly),
    // so a successful save never prompts. Save and stay does end here eventually, but it
    // resets window.formulizechanged on the re-render, so it does not prompt either
    // unless the user has edited the form again since.
    function closeEntryDrawer() {
        // The discard warning runs first, and nothing is torn down until it passes:
        // choosing to stay must leave the drawer exactly as it was, including the
        // pending-refresh flag below.
        if (!confirmDiscardIfChanged()) { return; }
        // Save can now leave the drawer open, so a session may end with saved changes the
        // list behind it has not seen yet.
        var hostNeedsRefresh = savedDuringSession;
        savedDuringSession = false;
        releaseEntryLocks();
        if (footEl) { footEl.innerHTML = ''; }
        currentEntryNav = null;
        currentPrintableView = null;
        drawerStack = [];
        currentFrame = null;
        updateBackButton();
        closeDrawer();
        if (hostNeedsRefresh && typeof window.formulize.onEntrySaved === 'function') {
            window.formulize.onEntrySaved();
        }
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
    // config: { fid, frid, sid, editDestination } - sid is the screen the list opens
    // entries in full screen; a screen passed to goDetails() overrides it, as it does there.
    function initListView(config) {
        config = config || {};
        // 'screen' leaves Formulize's default goDetails/addNew in place, so the edit
        // icon navigates to the full form screen as it always has.
        if (config.editDestination !== 'drawer') { return; }

        function openEntry(entryId, sid) {
            if (sid) {
                // a screen named by the template's own link: the entry id is the template's
                // to get right for that screen's form, so full screen shows it as given
                openEntryInDrawer({ fid: config.fid, frid: config.frid, entryId: entryId, sid: sid });
                return;
            }
            // the list's configured screen may be on another form in the relationship;
            // the endpoint maps the list's entry onto that form, as full screen does
            openEntryInDrawer({ fid: config.fid, frid: config.frid, entryId: entryId, sid: config.sid || '',
                                entryFid: config.fid, entryFrid: config.frid });
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
        Array.prototype.forEach.call(document.querySelectorAll('.js-open-ai'), function (aiLink) {
            aiLink.addEventListener('click', function (e) {
                e.preventDefault();
                // On the assistant's own page (/ai/) it is already open, full size. A second
                // copy in the drawer would share its element ids and break both, so the link
                // just takes the person to the message box there.
                var fullPage = document.getElementById('ai-assistant-container');
                if (fullPage && !fullPage.closest('.formulize-drawer')) {
                    var input = document.getElementById('user-input');
                    if (input && !input.disabled) { input.focus(); }
                    return;
                }
                openAIInDrawer();
            });
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
