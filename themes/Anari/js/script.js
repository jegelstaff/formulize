/**
 * Load all plugins on load
 */
document.addEventListener('DOMContentLoaded', function () {
  initHamburgerMenu();
  initAccessibleCardToggles();
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
  var login_button = document.querySelector('#block_login_form input[type=submit]');
  if (login_button) {
    login_button.addEventListener('click', function(evt) {
      flyoutMenu.classList.remove('site-layout__sidebar--open');
    });
  }
}

/**
 * Accessible Toggle cards
 */
function initAccessibleCardToggles() {
  const accordionHeaders = document.querySelectorAll('[data-accordion-header]');
  Array.prototype.forEach.call(accordionHeaders, accordionHeader => {
    let target = accordionHeader.parentElement.nextElementSibling;
    accordionHeader.onclick = (event) => {
      event.preventDefault();
      let expanded = accordionHeader.getAttribute('aria-expanded') === 'true' || false;
      accordionHeader.setAttribute('aria-expanded', !expanded);
      target.hidden = expanded;
    }
  });
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
