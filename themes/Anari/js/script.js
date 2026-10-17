/**
 * Load all plugins on load
 */
document.addEventListener('DOMContentLoaded', function () {
  initHamburgerMenu();
});

/**
 * Add support for opening and closing the hamburger menu
 */
function initHamburgerMenu() {
  var flyoutMenu = document.querySelector('.js-flyout-menu');
  var triggerOpenButton = document.querySelector('.js-site-menu-open-trigger');
  var triggerCloseButton = document.querySelector('.js-site-menu-close-trigger');
  triggerOpenButton.addEventListener('click', function (evt) {
    evt.preventDefault();
    flyoutMenu.classList.add('site-layout__sidebar--open');
  });
  triggerCloseButton.addEventListener('click', function (evt) {
    evt.preventDefault();
    flyoutMenu.classList.remove('site-layout__sidebar--open');
  });
  // The menu's AI link opens the assistant in the drawer, over the page, so the menu
  // closes behind it instead of staying open under the drawer (on a phone, the menu is
  // a panel over the page)
  Array.prototype.forEach.call(document.querySelectorAll('.js-flyout-menu .js-open-ai'), function (aiLink) {
    aiLink.addEventListener('click', function () {
      flyoutMenu.classList.remove('site-layout__sidebar--open');
    });
  });
  // On a phone the open menu covers part of the page, which is dimmed behind it
  // (css/style.css), and a tap anywhere outside the menu closes it, as in Lyris.
  // Not a tap on what opens it: the menu button and Login.
  document.addEventListener('click', function (evt) {
    if (window.innerWidth > 767 || !flyoutMenu.classList.contains('site-layout__sidebar--open')) {
      return;
    }
    if (flyoutMenu.contains(evt.target) || triggerOpenButton.contains(evt.target) || (evt.target.closest && evt.target.closest('.js-open-login'))) {
      return;
    }
    flyoutMenu.classList.remove('site-layout__sidebar--open');
  });
  // Login, in the header (.js-open-login): the login block is in the menu, so
  // open the menu on it, ready to type the username, rather than going to a page
  // where the menu is closed. A site whose menu has no login block keeps the
  // link's own address.
  Array.prototype.forEach.call(document.querySelectorAll('.js-open-login'), function (link) {
    link.addEventListener('click', function (evt) {
      var username = flyoutMenu.querySelector('input[name="uname"]');
      if (!username) {
        return;
      }
      evt.preventDefault();
      flyoutMenu.classList.add('site-layout__sidebar--open');
      // once the menu has slid in, so the field is on screen
      setTimeout(function () { username.focus(); }, 550);
    });
  });
  // On a phone, someone who is not logged in has to find the login block in the
  // menu, so the first page they open in this tab opens the menu for them, once.
  // Only the first: open over every page, it would cover the pages they came to
  // read. 767px is the width below which this theme lays pages out for phones.
  if ('sidebarAnon' in flyoutMenu.dataset && window.innerWidth <= 767) {
    try {
      if (!sessionStorage.getItem('anari-menu-shown')) {
        sessionStorage.setItem('anari-menu-shown', '1');
        flyoutMenu.classList.add('site-layout__sidebar--open');
      }
    } catch (e) { /* storage blocked: leave the menu closed */ }
  }
  var login_button = document.querySelector('#block_login_form input[type=submit]');
  if (login_button) {
    login_button.addEventListener('click', function(evt) {
      flyoutMenu.classList.remove('site-layout__sidebar--open');
    });
  }
}

/**
 * Toggle cards
 */
(function initCardToggles($) {
  if (!$) return console.warn('jQuery not loaded');

  $('[data-toggle-detail]').hide();

  $('[data-toggle]').on('click', function () {
    var $this = $(this);
    var contentId = $this.attr('data-toggle');
    var $contentElement = $('[data-toggle-detail="' + contentId + '"]');
    $contentElement.toggle();
  });
})(window.jQuery);

/**
 * Tabs (navigation)
 */
(function initTabs($) {
  var $tabs = $('.js-tabs');
  $tabs.on('click', '[data-tab-set]', function () {
    var $currentTab = $(this);
    var tabSet = $currentTab.data('tab-set');
    $tabs.find('[data-tab-set="' + tabSet + '"]').each(function (index, tab) {
      $(tab).removeClass('pill-tabs__item--active');
    });

    $currentTab.addClass('pill-tabs__item--active');

    var tabContentId = $currentTab.data('tab-content');
    $('.js-tab-content').hide();
    $('#' + tabContentId).show();
  });
})(window.jQuery);
