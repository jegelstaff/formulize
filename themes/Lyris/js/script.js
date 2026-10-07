'use strict';

document.addEventListener('DOMContentLoaded', () => {
  initSidebar();
  initAccordions();
  initCardToggles();
  initFormScrollSeparator();
  initListScroll();
  initFormEdges();
  showApp();
});

// ============================================================
// Sidebar toggle
// ============================================================

function initSidebar() {
  const app = document.getElementById('lyris-app');
  const toggle = document.querySelector('.js-sidebar-toggle');
  if (!app || !toggle) return;

  const STORAGE_KEY = 'lyris-sidebar-closed';
  const isMobile = () => window.innerWidth <= 768;

  function setSidebarState(closed) {
    if (isMobile()) {
      app.classList.toggle('lyris-app--sidebar-open', !closed);
      app.classList.remove('lyris-app--sidebar-closed');
    } else {
      app.classList.toggle('lyris-app--sidebar-closed', closed);
      app.classList.remove('lyris-app--sidebar-open');
    }
    toggle.setAttribute('aria-expanded', String(!closed));
    try { localStorage.setItem(STORAGE_KEY, String(closed)); } catch (_) { /* ignore */ }
  }

  toggle.addEventListener('click', () => {
    const closed = isMobile()
      ? app.classList.contains('lyris-app--sidebar-open')
      : !app.classList.contains('lyris-app--sidebar-closed');
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
    if (!isMobile() || !app.classList.contains('lyris-app--sidebar-open')) return;
    const sidebar = document.getElementById('lyris-sidebar');
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
// `.lyris-main` is the scroll container for a form screen (`overflow-y: auto`);
// the strip is a descendant of it, not of the page, so this listens on the
// container rather than on the window. Nothing to do on screens that render no
// tabs, and the drawer builds its own footer/scroller, so it is untouched.
function initFormScrollSeparator() {
  if (document.body.classList.contains('formulize-inline')) return;
  const scroller = document.querySelector('.lyris-main');
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

// On wider screens a list scrolls with the page (`.lyris-main`), between its
// title bar and footer, which are pinned (see "Content width" in style.css). The
// list card still scrolls sideways when the entries are wider than it, and that
// sideways scroller is as far as position: sticky reaches, so two things are
// done here instead:
// - the column headings and filter row are held under the title bar while the
//   page scrolls, by moving them down (--lyris-head-shift) as far as the card's
//   top has gone under the title bar, and no further than the card's end;
// - while the card's own sideways scrollbar is out of sight below the footer, a
//   copy of it is pinned above the footer, scrolling the card as it scrolls;
// - while the card's top or bottom is out of sight under the title bar or the
//   footer, the bar draws the card's edge at the cut (.is-over-top,
//   .is-over-bottom; see "the card's edge" in style.css), and the headings sit
//   just under the drawn top edge.
// Phones keep the list scrolling inside its own area, between pinned bars, so
// nothing is done there; nor in the drawer or an embedded screen.
function initListScroll() {
  if (document.body.classList.contains('formulize-inline')) return;
  const main = document.querySelector('.lyris-main');
  const screen = main ? main.querySelector('.lyris-list-screen') : null;
  const list = screen ? screen.querySelector('.lyris-list__body') : null;
  const title = screen ? screen.querySelector('.lyris-list__titlebar') : null;
  const footer = screen ? screen.querySelector('.lyris-list__footer') : null;
  if (!list || !title || !footer) return;
  const wide = window.matchMedia('(min-width: 769px)');

  const bar = document.createElement('div');
  bar.className = 'lyris-list__hscroll';
  bar.setAttribute('aria-hidden', 'true'); // the list scrolls sideways by itself too
  bar.hidden = true;
  const track = document.createElement('div');
  bar.appendChild(track);
  footer.appendChild(bar);

  let ticking = false;
  const apply = () => {
    ticking = false;
    if (!wide.matches) {
      list.style.removeProperty('--lyris-head-shift');
      title.classList.remove('is-over-top');
      footer.classList.remove('is-over-bottom');
      bar.hidden = true;
      return;
    }
    const box = list.getBoundingClientRect();
    const gap = parseFloat(getComputedStyle(list).marginBottom) || 0; // the space between the cards
    const head = list.querySelector('thead');
    const keep = (head ? head.offsetHeight : 0);
    // where the card's top shows: past the gap under the title bar. Above that, its
    // edge is drawn there, and the headings are held just inside it
    const top = title.getBoundingClientRect().bottom + gap;
    title.classList.toggle('is-over-top', box.top < top);
    title.style.setProperty('--lyris-edge-bg', head ? 'var(--fz-header-bg)' : 'var(--fz-card-bg)');
    const shift = Math.max(0, Math.min(top - box.top, box.height - keep));
    list.style.setProperty('--lyris-head-shift', Math.round(shift) + 'px');
    // and its bottom, short of the gap above the footer; its own sideways scrollbar
    // is along that bottom edge
    const under = box.bottom > footer.getBoundingClientRect().top - gap + 1;
    footer.classList.toggle('is-over-bottom', under);
    const wider = list.scrollWidth > list.clientWidth + 1;
    bar.hidden = !wider || !under;
    if (!bar.hidden) {
      track.style.width = list.scrollWidth + 'px';
      if (bar.scrollLeft !== list.scrollLeft) bar.scrollLeft = list.scrollLeft;
    }
  };
  const later = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(apply);
  };

  // each scrolls the other; the one being scrolled is left alone
  let leader = null;
  const follow = (from, to) => () => {
    if (leader && leader !== from) return;
    leader = from;
    to.scrollLeft = from.scrollLeft;
    requestAnimationFrame(() => { leader = null; });
  };
  bar.addEventListener('scroll', follow(bar, list), { passive: true });
  list.addEventListener('scroll', follow(list, bar), { passive: true });

  main.addEventListener('scroll', later, { passive: true });
  window.addEventListener('resize', later);
  if (wide.addEventListener) wide.addEventListener('change', later);
  if (window.ResizeObserver) new ResizeObserver(later).observe(list);
  apply();
}

// The same edges for a form's card on wider screens: drawn under the page tabs
// while the card's top is out of sight under them, and above the action bar
// while its bottom is out of sight under that (see "the card's edge" in
// style.css). Not on phones, in the drawer or in an embedded screen.
function initFormEdges() {
  if (document.body.classList.contains('formulize-inline')) return;
  const main = document.querySelector('.lyris-main');
  const form = main ? main.querySelector(':scope > #formulizeform') : null;
  const card = form ? form.querySelector('.lyris-form-screen') : null;
  const actions = form ? form.querySelector('#multipage-controls') : null;
  if (!card || !actions) return;
  const tabs = form.querySelector('#pageNavTable.pill-tabs');
  const wide = window.matchMedia('(min-width: 769px)');

  let ticking = false;
  const apply = () => {
    ticking = false;
    const box = card.getBoundingClientRect();
    if (tabs) tabs.classList.toggle('is-over-top', wide.matches && box.top < tabs.getBoundingClientRect().bottom - 1);
    const gap = parseFloat(getComputedStyle(card).marginBottom) || 0;
    actions.classList.toggle('is-over-bottom', wide.matches && box.bottom > actions.getBoundingClientRect().top - gap + 1);
  };
  const later = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(apply);
  };
  main.addEventListener('scroll', later, { passive: true });
  window.addEventListener('resize', later);
  if (wide.addEventListener) wide.addEventListener('change', later);
  if (window.ResizeObserver) new ResizeObserver(later).observe(card);
  apply();
}

// ============================================================
// Show app and fire page-shown event
// ============================================================

function showApp() {
  window.dispatchEvent(new CustomEvent('formulize_pageShown'));
}
