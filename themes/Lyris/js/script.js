'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initAccordions();
  initCardToggles();
  initFormScrollSeparator();
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
// Full screen form: separator under the pinned top-level tabs
// ============================================================

// The sticky `#pageNavTable.pill-tabs` strip reveals a hairline + shadow along
// its bottom edge once the form scrolls underneath it. That used to be a CSS
// scroll-driven animation (`animation-timeline: scroll()`), which Chrome and
// Safari 26 run but Firefox does not implement, so Firefox fell through to the
// `@supports` fallback and drew the separator permanently — issue #121 item 2.
// A passive scroll listener works identically in every engine.
//
// `.fz-main` is the scroll container for a form screen (`overflow-y: auto`);
// the strip is a descendant of it, not of the page, so this listens on the
// container rather than on the window. Nothing to do on screens that render no
// tabs, and the drawer builds its own footer/scroller, so it is untouched.
function initFormScrollSeparator() {
  if (document.body.classList.contains('fz-inline')) return;
  const scroller = document.querySelector('.fz-main');
  const tabs = scroller ? scroller.querySelector('#pageNavTable.pill-tabs') : null;
  if (!scroller || !tabs) return;

  // The separator fades in as soon as the container leaves the top; the 120ms
  // CSS transition stands in for the old 0→24px scroll-linked scrub.
  let ticking = false;

  const apply = () => {
    ticking = false;
    tabs.classList.toggle('is-scrolled', scroller.scrollTop > 0);
  };

  scroller.addEventListener('scroll', () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(apply);
  }, { passive: true });

  apply(); // a reloaded page can restore a non-zero scroll position
}

// ============================================================
// Show app and fire page-shown event
// ============================================================

function showApp() {
  window.dispatchEvent(new CustomEvent('formulize_pageShown'));
}
