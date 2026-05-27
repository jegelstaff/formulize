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
// Exposed as window.formulizeDrawer.open({ title, html, footerHtml })
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

  if (closeBtn) closeBtn.addEventListener('click', closeDrawer);
  if (scrim)    scrim.addEventListener('click', closeDrawer);
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !drawer.hasAttribute('hidden')) closeDrawer();
  });

  window.formulizeDrawer = { open: openDrawer, close: closeDrawer };
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
