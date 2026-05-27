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

  // Load a Formulize entry URL into the drawer via an iframe.
  // Appends fz_inline=1, extracts the page h1 for the drawer title,
  // and reloads the parent list on any subsequent navigation (save/cancel).
  function openEntryInDrawer(url) {
    var sep = url.indexOf('?') >= 0 ? '&' : '?';
    var iframeUrl = url + sep + 'fz_inline=1';

    openDrawer({ title: '' });
    if (!bodyEl) return;
    bodyEl.innerHTML = '';

    var iframe = document.createElement('iframe');
    iframe.src = iframeUrl;
    iframe.className = 'fz-drawer__iframe';
    iframe.setAttribute('title', 'Entry detail');

    var initialLoad = true;
    iframe.addEventListener('load', function () {
      if (initialLoad) {
        initialLoad = false;
        try {
          var h1 = iframe.contentDocument.querySelector('h1');
          var drawerTitleEl = drawer.querySelector('.js-drawer-title');
          if (h1 && drawerTitleEl) drawerTitleEl.textContent = h1.textContent.trim();
        } catch (_) {}
        return;
      }
      // Subsequent navigation (save / cancel) → close drawer
      closeDrawer();
    });

    bodyEl.appendChild(iframe);
  }

  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (scrim)    scrim.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !drawer.hasAttribute('hidden')) closeDrawer();
  });

  window.formulize = window.formulize || {};
  window.formulize.drawer = { open: openDrawer, close: closeDrawer, openEntry: openEntryInDrawer };
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
