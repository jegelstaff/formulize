/**
 * Formulize embed helper.
 *
 * Include this on a page that embeds Formulize screens, and mark each iframe:
 *
 *   <iframe data-formulize-embed src="https://forms.example.com/my-screen"></iframe>
 *   <script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
 *
 * The screen reports its own height, so no height needs to be guessed here. The width is whatever
 * the surrounding page gives it, with a floor under it so it cannot collapse (see minWidthValue).
 * Messages are only accepted from the window of a registered iframe, and only when they come from
 * the origin that iframe was pointed at.
 */
(function (window, document) {
    'use strict';

    var frames = [];

    function originOf(url) {
        var link = document.createElement('a');
        link.href = url;
        return link.protocol + '//' + link.host;
    }

    /**
     * Make sure the screen knows it is being embedded.
     *
     * Nearly every browser tells it so itself, by sending Sec-Fetch-Dest on the request. The ones
     * that don't would show the whole site - menus, header and footer - inside the frame. The
     * formulize_embed parameter says it instead, so those browsers get the same thing as everyone
     * else.
     *
     * The code Formulize generates for you already carries the parameter, so this does nothing and
     * the iframe loads once. It is here for an iframe written by hand without it, which is worth
     * one extra load to get right rather than leaving somebody with a mystery.
     */
    function ensureEmbedParameter(iframe) {
        var src = iframe.getAttribute('src');
        if (!src || /[?&]formulize_embed=/.test(src)) {
            return;
        }
        iframe.setAttribute('src', src + (src.indexOf('?') === -1 ? '?' : '&') + 'formulize_embed=1');
    }

    /**
     * The narrowest the screen is allowed to become.
     *
     * An iframe fills its container, which is right when the container has a width of its own and
     * silently wrong when it does not. Inside anything sized to fit its contents - a float, an
     * inline-block, a flex or grid item, a table cell, width: fit-content - the container's width
     * depends on its children, a percentage width on a child cannot answer that, and the iframe
     * falls back to the 300px that CSS gives any replaced element with no size. The container then
     * shrinks to match. Nothing looks broken; the screen is just a sliver, and the person who
     * pasted the code in has no way to guess why.
     *
     * A minimum width fixes that from this side, because it is not only a clamp on the iframe: it
     * becomes the iframe's contribution to the container's own minimum width, so a container that
     * had collapsed grows to honour it. Capped at the width of the window, so that on a phone it
     * gives way rather than pushing a horizontal scrollbar onto the host page.
     *
     * It cannot do anything about a page whose column really is narrow, and it should not try -
     * that is the host's design, and a screen in a 600px column gets 600px. Screens stay usable
     * there because an iframe has its own viewport, so the site theme's narrow-screen rules apply
     * to the width of the frame rather than the width of the visitor's monitor.
     *
     * Browsers without min() are from before 2020. They get a flat 320px, which is narrow enough
     * that no phone gains a scrollbar and still wide enough to lift the 300px collapse.
     */
    function minWidthValue(iframe) {
        var requested = iframe.getAttribute('data-formulize-embed-min-width');
        if (requested === 'off' || requested === '0') {
            return '';
        }
        var floor = parseInt(requested, 10);
        if (!floor || floor < 0) {
            floor = 400; // a phone held sideways: enough for a form, rarely wider than a real column
        }
        if (window.CSS && window.CSS.supports && window.CSS.supports('width', 'min(1px, 100vw)')) {
            return 'min(' + floor + 'px, 100vw)';
        }
        return Math.min(floor, 320) + 'px';
    }

    function register(iframe) {
        if (iframe.formulizeEmbedRegistered) {
            return;
        }
        iframe.formulizeEmbedRegistered = true;
        ensureEmbedParameter(iframe);
        iframe.setAttribute('scrolling', 'no');
        iframe.style.width = '100%';
        iframe.style.minWidth = minWidthValue(iframe);
        iframe.style.border = '0';
        if (!iframe.style.height) {
            iframe.style.height = (iframe.getAttribute('data-formulize-embed-height') || 600) + 'px';
        }
        addClass(iframe, 'formulize-embed--loading');
        frames.push({iframe: iframe, origin: originOf(iframe.src)});
    }

    function scan() {
        var found = document.querySelectorAll('iframe[data-formulize-embed]');
        for (var i = 0; i < found.length; i++) {
            register(found[i]);
        }
    }

    function addClass(element, name) {
        if ((' ' + element.className + ' ').indexOf(' ' + name + ' ') === -1) {
            element.className = (element.className + ' ' + name).replace(/^\s+/, '');
        }
    }

    function removeClass(element, name) {
        element.className = (' ' + element.className + ' ').replace(' ' + name + ' ', ' ').replace(/^\s+|\s+$/g, '');
    }

    function pageOffset() {
        return window.pageYOffset || document.documentElement.scrollTop || 0;
    }

    /**
     * How much of the top of the window is already spoken for by the host page.
     *
     * A header that pins itself to the top of the window covers whatever is scrolled underneath it,
     * so stopping at the top of the frame leaves the frame behind the header rather than in front of
     * the reader. How tall that header is belongs to the host page, changes with its own breakpoints,
     * and is not something this script can be told in advance - so it is measured instead of guessed.
     *
     * A host that has set scroll-padding-top has already answered the question deliberately, and that
     * answer is taken as given. Otherwise: ask the browser what is actually drawn at the top of the
     * window right now, and keep whatever is pinned there.
     */
    function pinnedHeaderHeight() {
        var declared = parseFloat(window.getComputedStyle(document.documentElement).scrollPaddingTop);
        if (declared > 0) {
            return declared;
        }
        if (!document.elementsFromPoint) {
            return 0; // too old to ask; the margin below still applies
        }
        var covered = 0;
        var atTheTop = document.elementsFromPoint(Math.round((window.innerWidth || 0) / 2), 1);
        for (var i = 0; i < atTheTop.length; i++) {
            var position = window.getComputedStyle(atTheTop[i]).position;
            if (position !== 'fixed' && position !== 'sticky') {
                continue; // scrolls away with everything else, so it covers nothing
            }
            var bottom = atTheTop[i].getBoundingClientRect().bottom;
            if (bottom > covered) {
                covered = bottom;
            }
        }
        return covered;
    }

    /**
     * The distance between the top of the box holding the frame and the top of the frame itself.
     *
     * An embedded screen is usually put inside something with padding and a border of its own - a
     * card, a panel - and the top of that box is what a reader sees as the top of the screen. Scroll
     * to the frame and the box's own edge is left above the fold, so the thing looks cut off.
     */
    function containerEdgeHeight(iframe) {
        var container = iframe.parentElement;
        if (!container) {
            return 0;
        }
        var styles = window.getComputedStyle(container);
        return (parseFloat(styles.paddingTop) || 0) + (parseFloat(styles.borderTopWidth) || 0);
    }

    /**
     * How far above the frame to stop.
     *
     * Everything that can be measured is measured - what the host pins to the top of the window, and
     * the edge of the box the frame sits in - and one line of the host page's own text is added on
     * top, so the screen has a little room rather than being flush against whatever is above it.
     *
     * data-formulize-embed-scroll-margin overrides the lot with a fixed number. 0 turns it off.
     */
    function scrollMargin(iframe) {
        var requested = parseInt(iframe.getAttribute('data-formulize-embed-scroll-margin'), 10);
        if (requested >= 0) {
            return requested;
        }
        var rootFontSize = parseFloat(window.getComputedStyle(document.documentElement).fontSize) || 16;
        return Math.round(rootFontSize + pinnedHeaderHeight() + containerEdgeHeight(iframe));
    }

    function scrollIntoView(iframe, offsetWithinFrame) {
        var frameTop = iframe.getBoundingClientRect().top + pageOffset();
        var target = Math.max(0, frameTop + offsetWithinFrame - scrollMargin(iframe));
        var viewportTop = pageOffset();
        var viewportBottom = viewportTop + (window.innerHeight || document.documentElement.clientHeight);
        if (target >= viewportTop && target <= viewportBottom - 60) {
            return; // already in view, leave the reader where they are
        }
        if (window.requestAnimationFrame && 'scrollBehavior' in document.documentElement.style) {
            window.scrollTo({top: target, behavior: 'smooth'});
        } else {
            window.scrollTo(0, target);
        }
    }

    function offerNewWindow(iframe) {
        if (iframe.getAttribute('data-formulize-embed-fallback') === 'off' || iframe.formulizeEmbedFallbackShown) {
            return;
        }
        iframe.formulizeEmbedFallbackShown = true;
        var notice = document.createElement('p');
        notice.className = 'formulize-embed__fallback';
        var link = document.createElement('a');
        link.href = iframe.src;
        link.target = '_blank';
        link.rel = 'noopener';
        link.appendChild(document.createTextNode(
            iframe.getAttribute('data-formulize-embed-fallback-text') || 'Open this form in a new window'
        ));
        notice.appendChild(link);
        iframe.parentNode.insertBefore(notice, iframe.nextSibling);
    }

    function frameFor(source) {
        for (var i = 0; i < frames.length; i++) {
            if (frames[i].iframe.contentWindow === source) {
                return frames[i];
            }
        }
        return null;
    }

    function notify(iframe, type, message) {
        if (!window.CustomEvent) {
            return;
        }
        try {
            iframe.dispatchEvent(new CustomEvent('formulize:' + type, {detail: message, bubbles: true}));
        } catch (e) { /* CustomEvent constructor missing on old browsers */ }
    }

    window.addEventListener('message', function (event) {
        var message = event.data;
        if (!message || message.formulize !== true) {
            return;
        }
        var frame = frameFor(event.source);
        if (!frame || event.origin !== frame.origin) {
            return; // not one of ours, or not from where that iframe was pointed
        }
        switch (message.type) {
            case 'formulize:ready':
                removeClass(frame.iframe, 'formulize-embed--loading');
                if (message.height) {
                    frame.iframe.style.height = message.height + 'px';
                }
                break;
            case 'formulize:resize':
                frame.iframe.style.height = message.height + 'px';
                break;
            case 'formulize:scroll':
                scrollIntoView(frame.iframe, message.top || 0);
                break;
            case 'formulize:sessionUnavailable':
                addClass(frame.iframe, 'formulize-embed--no-session');
                offerNewWindow(frame.iframe);
                break;
            default:
                return;
        }
        notify(frame.iframe, message.type.replace('formulize:', ''), message);
    }, false);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scan, false);
    } else {
        scan();
    }

    window.FormulizeEmbed = {scan: scan, register: register};

})(window, document);
