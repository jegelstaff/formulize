/**
 * Formulize embed helper.
 *
 * Include this on a page that embeds Formulize screens, and mark each iframe:
 *
 *   <iframe data-formulize-embed src="https://forms.example.com/my-screen"></iframe>
 *   <script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
 *
 * The screen reports its own height, so no height needs to be guessed here. Messages are only
 * accepted from the window of a registered iframe, and only when they come from the origin that
 * iframe was pointed at.
 */
(function (window, document) {
    'use strict';

    var frames = [];

    function originOf(url) {
        var link = document.createElement('a');
        link.href = url;
        return link.protocol + '//' + link.host;
    }

    function register(iframe) {
        if (iframe.formulizeEmbedRegistered) {
            return;
        }
        iframe.formulizeEmbedRegistered = true;
        iframe.setAttribute('scrolling', 'no');
        iframe.style.width = '100%';
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

    function scrollIntoView(iframe, offsetWithinFrame) {
        var frameTop = iframe.getBoundingClientRect().top + pageOffset();
        var target = Math.max(0, frameTop + offsetWithinFrame - 20);
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
