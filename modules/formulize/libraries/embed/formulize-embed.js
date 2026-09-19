/**
 * Formulize embed helper.
 *
 * Include this on a page that embeds Formulize screens, and mark each iframe:
 *
 *   <iframe data-formulize-embed src="https://forms.example.com/my-screen"></iframe>
 *   <script src="https://forms.example.com/modules/formulize/libraries/embed/formulize-embed.js"></script>
 *
 * The screen reports its own height, so no height needs to be guessed here. The width is whatever
 * the surrounding page gives it, with a floor under it so it cannot collapse (see applyMinWidth).
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
     * The width of an element's content box, which is what its children have to live in.
     */
    function contentWidth(element) {
        var styles = window.getComputedStyle(element);
        return element.getBoundingClientRect().width
            - (parseFloat(styles.paddingLeft) || 0) - (parseFloat(styles.paddingRight) || 0)
            - (parseFloat(styles.borderLeftWidth) || 0) - (parseFloat(styles.borderRightWidth) || 0);
    }

    /**
     * What the host page has where it put the frame: how much width, and whether anything around
     * the frame is being sized by it.
     *
     * Both answers come from the same measurement. The frame steps out of the flow and the
     * ancestors are asked their width again. Anything that answers differently was being sized by
     * the frame; the first one that answers the same has a width of its own, and that is the
     * column. Out of the flow rather than hidden, so that nothing disturbs the document inside the
     * frame - display: none is a reload in some browsers, and a form half filled in would be lost.
     *
     * It has to be measured because there is nothing in CSS that can answer either question from
     * in here. 100vw is the window, and the window is not the column: a frame in a 324px column on
     * a 360px phone was being given a 360px floor and hanging 36px over the side of the page. 100%
     * is the column, but it is worth nothing in the case the floor exists for - a container sized
     * to its contents has no width until its children have one, so a percentage on a child only
     * ever repeats back what the frame already said.
     */
    function columnAround(iframe) {
        var ancestors = [];
        for (var node = iframe.parentElement; node; node = node.parentElement) {
            ancestors.push({node: node, wasWide: contentWidth(node)});
            if (node === document.body) {
                break;
            }
        }
        var position = iframe.style.position;
        var visibility = iframe.style.visibility;
        iframe.style.position = 'absolute';
        iframe.style.visibility = 'hidden';
        var available = 0;
        var column = null;
        var sizedByTheFrame = false;
        for (var i = 0; i < ancestors.length; i++) {
            var isWide = contentWidth(ancestors[i].node);
            if (Math.abs(isWide - ancestors[i].wasWide) < 1) {
                available = isWide;
                column = ancestors[i].node;
                break;
            }
            sizedByTheFrame = true;
        }
        iframe.style.position = position;
        iframe.style.visibility = visibility;
        return {
            // clientWidth rather than 100vw as the last resort: it leaves out the scrollbar, which
            // vw does not, and a floor one scrollbar too wide is how a page gains a scrollbar
            available: available > 0 ? available : document.documentElement.clientWidth,
            sizedByTheFrame: sizedByTheFrame,
            node: column
        };
    }

    /**
     * Put a floor under the width, in the one case where the width can collapse.
     *
     * An iframe fills its container, which is right when the container has a width of its own and
     * silently wrong when it does not. Inside anything sized to fit its contents - a float, an
     * inline-block, a table cell, width: fit-content - the container's width depends on its
     * children, a percentage width on a child cannot answer that, and the iframe falls back to the
     * 300px that CSS gives any replaced element with no size. The container then shrinks to match.
     * Nothing looks broken; the screen is just a sliver, and the person who pasted the code in has
     * no way to guess why.
     *
     * A minimum width fixes that, because it is not only a clamp on the iframe: it becomes the
     * iframe's contribution to the container's own minimum width, so a container that had collapsed
     * grows to honour it.
     *
     * Everywhere else it is left off entirely, and everywhere else is nearly every page. width:100%
     * has already taken all the width there is to take, so a floor under that can only ever be one
     * of two things: the same number, or too big. Too big is not harmless - in a flex row it takes
     * the space a sibling needed, and in a narrow column it hangs over the side of the host's page.
     * So the floor goes on only when the measurement shows something around the frame really is
     * being sized by it, and is capped at the width of the column either way.
     *
     * It does not try to do anything about a column that really is narrow - that is the host's
     * design, and a screen in a 600px column gets 600px. Screens stay usable there because an
     * iframe has its own viewport, so the site theme's narrow-screen rules apply to the width of
     * the frame rather than the width of the visitor's monitor.
     *
     * The host's own stylesheet outranks all of this. It is their page, they can see the column
     * this frame is in and we cannot, so a min-width coming from a stylesheet is left to stand. It
     * is asked of the cascade with our own inline value cleared away first, or we would only ever
     * find our own answer. data-formulize-embed-min-width="off" turns the floor off outright.
     *
     * A stylesheet, though, and not the style attribute, which is cleared rather than honoured. The
     * asymmetry is deliberate: the style attribute on one of these frames is part of the snippet
     * Formulize generated, so it is ours to manage, and clearing it is what lets an old snippet
     * already pasted into somebody's page - carrying the 100vw floor that was wrong - be repaired
     * by nothing more than this script reaching it. A host who wants the last word on the width has
     * a stylesheet rule, which is not touched, and the attribute above, which stops this outright.
     * Either way a host who writes an inline floor and loses it is no worse off, because what
     * replaces it is a floor only where one is needed and never wider than their column.
     */
    function desiredMinWidth(iframe) {
        var requested = iframe.getAttribute('data-formulize-embed-min-width');
        if (requested === 'off' || requested === '0') {
            return '';
        }
        var ours = iframe.style.minWidth;
        iframe.style.minWidth = ''; // put back below, either way, before anything is painted
        var fromTheHost = window.getComputedStyle(iframe).minWidth;
        var column = (fromTheHost && fromTheHost !== '0px' && fromTheHost !== 'auto' && fromTheHost !== 'none')
            ? null // the host's stylesheet has an opinion about this frame, and it is their page
            : columnAround(iframe);
        iframe.style.minWidth = ours;
        if (!column || !column.sizedByTheFrame) {
            return ''; // nothing here is waiting on the frame for a width, so the frame has one
        }
        var floor = parseInt(requested, 10);
        if (!floor || floor < 0) {
            floor = 400; // a phone held sideways: enough for a form, rarely wider than a real column
        }
        return Math.round(Math.min(floor, column.available)) + 'px';
    }

    /**
     * Written only when it changes, because this is called from a ResizeObserver watching the very
     * box this writes into. Setting a value that is already set would resize nothing, but it is
     * still a write inside a resize callback, and browsers report those as an undelivered-
     * notification loop. Nothing changing means nothing written, and the question settles.
     */
    function applyMinWidth(iframe) {
        var value = desiredMinWidth(iframe);
        if (iframe.style.minWidth !== value) {
            iframe.style.minWidth = value;
        }
    }

    /**
     * The column is measured, so it has to be measured again whenever the column changes.
     *
     * Watching the column, rather than the window, because most of what changes a column never
     * touches the window at all: a webfont arriving and reflowing the page, an accordion or a tab
     * opening above the frame, a container in the middle of a transition. A resize listener sleeps
     * through every one of those. It is kept below anyway, for browsers too old for ResizeObserver,
     * where a turned phone is at least the common case.
     *
     * The column, specifically, and not the frame's own parent - which is the obvious box to watch
     * and the wrong one. In the case the floor exists for, the parent is sized by the frame, so our
     * own floor is holding it open: the column around it can narrow to nothing and that parent will
     * report the same width throughout, and the watch would sleep through the one situation it was
     * added for. The column is by definition the nearest box that is not sized by the frame, which
     * is exactly what makes it the one worth watching.
     *
     * Waiting for it to settle keeps this to one pass rather than one per frame of an animation,
     * since every pass asks the browser for a layout it would rather have put off.
     */
    function watchColumn(iframe) {
        if (!window.ResizeObserver) {
            return;
        }
        var watched = columnAround(iframe).node || document.body;
        if (!watched) {
            return;
        }
        var settling = null;
        new ResizeObserver(function () {
            clearTimeout(settling);
            settling = setTimeout(function () {
                applyMinWidth(iframe);
            }, 50);
        }).observe(watched);
    }

    function register(iframe) {
        if (iframe.formulizeEmbedRegistered) {
            return;
        }
        iframe.formulizeEmbedRegistered = true;
        ensureEmbedParameter(iframe);
        iframe.setAttribute('scrolling', 'no');
        iframe.style.width = '100%';
        iframe.style.border = '0';
        if (!iframe.style.height) {
            iframe.style.height = (iframe.getAttribute('data-formulize-embed-height') || 600) + 'px';
        }
        applyMinWidth(iframe);
        watchColumn(iframe);
        addClass(iframe, 'formulize-embed--loading');
        frames.push({iframe: iframe, origin: originOf(iframe.src)});
    }

    // the safety net described in watchColumn, for browsers without ResizeObserver. Harmless where
    // there is one: a pass that finds nothing changed writes nothing.
    var remeasuring = null;
    window.addEventListener('resize', function () {
        clearTimeout(remeasuring);
        remeasuring = setTimeout(function () {
            for (var i = 0; i < frames.length; i++) {
                applyMinWidth(frames[i].iframe);
            }
        }, 150);
    }, false);

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
                // the host page has had time to finish settling by now - web fonts, images - so
                // the column may not be the width it was when the frame was first registered
                applyMinWidth(frame.iframe);
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
