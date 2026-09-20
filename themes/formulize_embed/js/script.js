'use strict';

/*
 * Theme JavaScript for embedded Formulize screens.
 *
 * Deliberately tiny. A screen in this theme renders no site chrome - no sidebar, no flyout menu,
 * no tabs - so the rest of what a full theme's script does targets markup that is not here.
 *
 * The body reveal and the formulize_pageShown event are fired from theme.html rather than from
 * this file, because they are tied to the postMessage handshake with the host page.
 */

document.addEventListener('DOMContentLoaded', function () {
    initAccordions();
});

/**
 * Accessible accordion behaviour for [data-accordion-header].
 *
 * Assigning onclick rather than adding a listener is deliberate, and matches what the Anari theme
 * does. The one control in Formulize that carries this attribute - the "change columns" toggle on
 * list screens - also carries an inline onclick calling toggleCols(), which performs the very same
 * toggle. Adding a listener would run both, flipping the panel twice and leaving it looking broken.
 * Assigning replaces the inline handler with this equivalent one, so the control behaves the same
 * whether a screen is embedded or not.
 *
 * That also means this is belt and braces for Formulize's own markup, which works without it. It
 * earns its place by covering custom screen templates that use the attribute on its own.
 */
function initAccordions() {
    var headers = document.querySelectorAll('[data-accordion-header]');
    Array.prototype.forEach.call(headers, function (header) {
        var target = header.parentElement && header.parentElement.nextElementSibling;
        if (!target) {
            return;
        }
        header.onclick = function (event) {
            event.preventDefault();
            var expanded = header.getAttribute('aria-expanded') === 'true';
            header.setAttribute('aria-expanded', String(!expanded));
            target.hidden = expanded;
        };
    });
}
