/**
 * Formulize forms that run in place.
 *
 * A Formulize form submits itself to get from one state to the next. Saving, turning a page,
 * adding, deleting or cloning subform entries, going into a sub entry and coming back out of it:
 * each of those posts the form (formulize_mainform, or go_parent to go back), the server does the
 * work, and its answer is the screen as it now is. This file runs that same cycle without leaving
 * the page. Nothing about the form changes. It is submitted by the same code as ever
 * (validateAndSubmit, submitForm, add_sub, goSub, verifyDone, a screen template's own script), to
 * the same address, with the same fields. The request is just sent with fetch, marked with
 * X-Formulize-Async, and the server answers with the screen's content alone (see
 * formulize_renderAsyncResponse in include/functions.php), which replaces the content the form
 * came from.
 *
 * A host owns the stretch of the page between two comments, <!--formulize-content--> and
 * <!--/formulize-content-->. index.php prints them around everything a screen renders, so a page
 * gets a host for its screen without any theme having to do anything. Something that shows a form
 * somewhere else, like the right drawer, makes a host of its own in an element (create()).
 *
 * The forms Formulize submits by script are given a submit() of their own, which hands the form
 * to the host. That is what brings every existing call site here without any of them changing.
 *
 * What is left alone, to go as it always has:
 * - a go_parent form with no parent entry to return to: the form is being left, so the host's
 *   onLeave decides what happens. On a page that is to submit it, to wherever it goes.
 * - a form addressed anywhere but this site.
 * - any request that fails, and any answer that is not the screen's content (a redirect, a page
 *   from somewhere else), which is shown as the browser would have shown it.
 * Without this file, forms post as they always have.
 *
 * Each time content goes in, the scripts in it run, in order. Before it goes in, the content on its
 * way out is let go of: its rich text editors are destroyed, and everything it bound to the document
 * or the window in the .formulizeRender jQuery namespace is unbound. That namespace is the
 * convention for any binding a render makes outside its own markup, because the render can be
 * replaced. Once the new content's scripts have run, formulize_contentLoaded is dispatched on the
 * window, which is what a render listens for, alongside load, to do what it does once the page has
 * loaded.
 *
 * Public API (window.formulize.formHost):
 *   page                          - the host for the page's own screen, if it has one
 *   create(element, options)      - a host in an element, empty until it loads something
 *   host.load(url)                - show a screen in the host
 *   host.submit(form)             - submit a form in the host, in place
 *   host.contains(node)           - is the node in what the host is showing?
 *
 * options, all optional:
 *   onLeave(form, submitNatively) - the form is being left (a go_parent with nowhere in the form to
 *                                   go back to). Default: submitNatively(), the page goes there.
 *   onPage(response, html)        - the answer was not the screen's content. Default: show it as
 *                                   the page, as the browser would have.
 *   onContent(host)               - new content is in and its scripts have run.
 *   onBusy(busy)                  - a request has gone out (true), or its answer is in (false).
 */
(function () {
    'use strict';

    window.formulize = window.formulize || {};
    if (window.formulize.formHost) { return; } // already on this page

    var START = 'formulize-content';
    var END = '/formulize-content';
    var ASYNC_HEADER = 'X-Formulize-Async';
    var ASYNC_RESPONSE_HEADER = 'X-Formulize-Async-Response';
    var TITLE_HEADER = 'X-Formulize-Title';
    var RENDER_NAMESPACE = '.formulizeRender';
    var CONTENT_LOADED_EVENT = 'formulize_contentLoaded';

    // The forms Formulize submits by script, which are submitted here instead
    var HOSTED_FORMS = ['formulize_mainform', 'go_parent'];

    // Script files that have run on this page. Every <script src> in arriving content is checked
    // against this rather than against the page, because the content a file came in with may have
    // been replaced since, taking its <script> with it, while what the file defined is still here.
    // Running a library again can do real harm (CKEditor refuses to register its modules twice).
    var ranScripts = null;

    function scriptHasRun(src) {
        if (!ranScripts) {
            ranScripts = {};
            Array.prototype.forEach.call(document.scripts, function (s) {
                if (s.src) { ranScripts[s.src] = true; }
            });
        }
        return !!ranScripts[src];
    }

    function absoluteUrl(address) {
        try { return new URL(address, window.location.href); } catch (e) { return null; }
    }

    function submitNatively(form) {
        HTMLFormElement.prototype.submit.call(form);
    }

    // The markers of the outermost screen in root, a page or arriving content. A screen can include
    // another screen, which brings markers of its own.
    function findMarkers(nodes) {
        var start = null, end = null, depth = 0;
        for (var i = 0; i < nodes.length; i++) {
            var n = nodes[i];
            if (n.nodeType !== Node.COMMENT_NODE) { continue; }
            if (n.data === START) {
                if (depth === 0 && !start) { start = n; }
                depth++;
            } else if (n.data === END && depth > 0) {
                depth--;
                if (depth === 0 && start && !end) { end = n; }
            }
        }
        return (start && end) ? { start: start, end: end } : null;
    }

    function commentsIn(root) {
        var out = [];
        var walker = document.createTreeWalker(root, NodeFilter.SHOW_COMMENT);
        while (walker.nextNode()) { out.push(walker.currentNode); }
        return out;
    }

    // ---- The host ------------------------------------------------------------------

    function Host(start, end, options) {
        this.start = start;
        this.end = end;
        this.options = options || {};
        this.busy = false;
        this.wire();
    }

    // What the host is showing: the nodes between its markers
    Host.prototype.nodes = function () {
        var out = [];
        for (var n = this.start.nextSibling; n && n !== this.end; n = n.nextSibling) { out.push(n); }
        return out;
    };

    Host.prototype.contains = function (node) {
        return this.nodes().some(function (n) { return n === node || n.contains(node); });
    };

    Host.prototype.forms = function () {
        var forms = [];
        this.nodes().forEach(function (n) {
            if (n.nodeType !== Node.ELEMENT_NODE) { return; }
            if (n.tagName === 'FORM') { forms.push(n); }
            Array.prototype.push.apply(forms, n.querySelectorAll('form'));
        });
        return forms;
    };

    // Give the forms Formulize submits by script a submit() that comes here. An own property, so it
    // is found before HTMLFormElement.prototype.submit, which submitNatively still reaches.
    Host.prototype.wire = function () {
        var host = this;
        this.forms().forEach(function (form) {
            if (HOSTED_FORMS.indexOf(form.getAttribute('name')) === -1) { return; }
            form.submit = function () { host.submit(form); };
            form.formulizeHost = host;
        });
    };

    Host.prototype.submit = function (form) {
        if (this.busy) { return; } // the form's last submission is still on its way
        var native = function () { submitNatively(form); };

        // go_parent goes back up to a parent entry when the form has one, which is part of the form.
        // Without one, it leaves the form, and what that means is up to whoever is showing it.
        if (form.getAttribute('name') === 'go_parent' && !form.elements.namedItem('parent_form')) {
            (this.options.onLeave || native)(form, native);
            return;
        }

        var url = absoluteUrl(form.getAttribute('action') || window.location.href);
        if (!url || url.origin !== window.location.origin) { native(); return; }

        var method = (form.getAttribute('method') || 'get').toUpperCase();
        var body = new FormData(form);
        if (method !== 'POST') {
            url.search = new URLSearchParams(body).toString();
            body = null;
        }
        this.request(url.href, { method: method, body: body }, native);
    };

    Host.prototype.load = function (url) {
        return this.request(url, { method: 'GET' }, null);
    };

    // Send a request, and put the answer in. fallback is what the browser would have done instead,
    // which is done if the request fails.
    Host.prototype.request = function (url, init, fallback) {
        var host = this;
        this.setBusy(true);
        // The render that is showing is on its way out, and its entries with it: release them now,
        // as leaving the page would have, so that the render that replaces it can have them
        if (typeof window.formulize_releaseEntryLocks === 'function') {
            try { window.formulize_releaseEntryLocks(); } catch (e) { /* nothing to release */ }
        }
        init.credentials = 'same-origin';
        init.headers = {};
        init.headers[ASYNC_HEADER] = '1';
        return fetch(url, init).then(function (response) {
            return response.text().then(function (html) {
                if (response.redirected || response.headers.get(ASYNC_RESPONSE_HEADER) !== '1') {
                    host.setBusy(false);
                    (host.options.onPage || showAsPage)(response, html);
                    return;
                }
                var title = response.headers.get(TITLE_HEADER);
                return host.replace(html, title ? decodeURIComponent(title) : null);
            });
        }, function (error) {
            host.setBusy(false);
            if (fallback) { fallback(); } else { throw error; }
        }).then(function () {
            host.setBusy(false);
        });
    };

    Host.prototype.setBusy = function (busy) {
        this.busy = busy;
        if (typeof this.options.onBusy === 'function') { this.options.onBusy(busy); }
    };

    // Let go of the content on its way out
    Host.prototype.teardown = function () {
        var host = this;
        if (window.CKEditors) {
            Object.keys(window.CKEditors).forEach(function (id) {
                var el = document.getElementById(id);
                if (el && !host.contains(el)) { return; } // another form's editor
                try {
                    var destroyed = window.CKEditors[id].destroy();
                    if (destroyed && typeof destroyed.catch === 'function') { destroyed.catch(function () {}); }
                } catch (e) { /* already gone */ }
                delete window.CKEditors[id];
            });
        }
        if (window.jQuery) {
            window.jQuery(document).off(RENDER_NAMESPACE);
            window.jQuery(window).off(RENDER_NAMESPACE);
        }
    };

    // Put new content in, from an async response: the scripts and stylesheets it needs, then the
    // screen's content between its markers
    Host.prototype.replace = function (html, title) {
        var host = this;
        var template = document.createElement('template');
        template.innerHTML = html;
        var arriving = Array.prototype.slice.call(template.content.childNodes);
        var markers = findMarkers(arriving);
        var assets = [];
        var content = arriving;
        if (markers) {
            assets = arriving.slice(0, arriving.indexOf(markers.start));
            content = arriving.slice(arriving.indexOf(markers.start) + 1, arriving.indexOf(markers.end));
        }

        // Stylesheet files go in the head, once. Scripts, and styles written into the page, run with
        // the content: the scripts first, in their order, and the styles go in with the content, so
        // they leave with it.
        var scripts = [];
        assets.forEach(function (n) {
            if (n.nodeType !== Node.ELEMENT_NODE) { return; }
            if (n.tagName === 'LINK') {
                var linked = absoluteUrl(n.getAttribute('href'));
                var href = linked ? linked.href : '';
                var have = Array.prototype.some.call(document.querySelectorAll('link[rel="stylesheet"]'), function (l) { return l.href === href; });
                if (!have) { document.head.appendChild(n); }
            } else if (n.tagName === 'SCRIPT') {
                scripts.push(n);
            } else if (n.tagName === 'STYLE') {
                content.unshift(n);
            }
        });

        // what has run comes from the page as it is now, before the arriving scripts are in it
        scriptHasRun('');
        this.teardown();
        this.nodes().forEach(function (n) { n.parentNode.removeChild(n); });
        var parent = this.end.parentNode;
        content.forEach(function (n) { parent.insertBefore(n, host.end); });
        if (title) { document.title = title; }

        // before any script runs, so that one submitting a form as the page loads (the server leaving
        // the form, by submitting go_parent) comes here too
        this.wire();
        // and the request is over: the content is in, and what its scripts do is up to them
        this.setBusy(false);

        var contentScripts = [];
        content.forEach(function (n) {
            if (n.nodeType !== Node.ELEMENT_NODE) { return; }
            if (n.tagName === 'SCRIPT') { contentScripts.push(n); }
            Array.prototype.push.apply(contentScripts, n.querySelectorAll('script'));
        });
        return runScripts(scripts.concat(contentScripts)).then(function () {
            // the new content starts at the top, as a new page would; a render that puts the reader
            // back where they were (a save that stays on the page) does so on formulize_contentLoaded
            var scroller = typeof window.formulize_scrollContainer === 'function' ? window.formulize_scrollContainer() : null;
            if (scroller) { scroller.scrollTop(0); }
            window.dispatchEvent(new CustomEvent(CONTENT_LOADED_EVENT, { detail: { host: host } }));
            if (typeof host.options.onContent === 'function') { host.options.onContent(host); }
        });
    };

    // Run arriving scripts in order, waiting for each file to load before going on, the order a
    // document gives for free and inserted markup does not. A script made by the parser for markup
    // put in this way never runs, so each is replaced by a copy, which does. One that came in a
    // head (not in the content) is run in the head and then taken out again.
    function runScripts(scripts) {
        return scripts.reduce(function (chain, old) {
            return chain.then(function () {
                return new Promise(function (resolve) {
                    // resolved against the page: markup from an async response was parsed elsewhere
                    var address = old.getAttribute('src') ? absoluteUrl(old.getAttribute('src')) : null;
                    var src = address ? address.href : '';
                    if (src && scriptHasRun(src)) {
                        if (old.parentNode) { old.parentNode.removeChild(old); }
                        resolve();
                        return;
                    }
                    var s = document.createElement('script');
                    for (var a = 0; a < old.attributes.length; a++) {
                        s.setAttribute(old.attributes[a].name, old.attributes[a].value);
                    }
                    var inHead = !old.parentNode || old.parentNode.nodeType === Node.DOCUMENT_FRAGMENT_NODE;
                    var put = function () {
                        if (inHead) { document.head.appendChild(s); } else { old.parentNode.replaceChild(s, old); }
                    };
                    if (src) {
                        s.setAttribute('src', src);
                        ranScripts[src] = true;
                        s.addEventListener('load', resolve);
                        s.addEventListener('error', resolve);
                        put();
                    } else {
                        s.textContent = old.textContent;
                        try { put(); } finally {
                            if (inHead && s.parentNode) { s.parentNode.removeChild(s); }
                        }
                        resolve();
                    }
                });
            });
        }, Promise.resolve());
    }

    // The answer was a page, not a screen's content: show it, as the browser would have
    function showAsPage(response, html) {
        if (response.redirected) {
            window.location.assign(response.url);
            return;
        }
        document.open();
        document.write(html);
        document.close();
    }

    // ---- Making hosts --------------------------------------------------------------

    // A host in an element: the markers go in the element, and the host shows whatever it loads
    // between them
    function create(element, options) {
        var start = document.createComment(START);
        var end = document.createComment(END);
        element.appendChild(start);
        element.appendChild(end);
        return new Host(start, end, options);
    }

    var api = window.formulize.formHost = {
        page: null,
        create: create
    };

    // The page's own screen, if it has one. When the page is still loading this waits for it, so
    // that the screen's forms are all there.
    function hostPage() {
        if (api.page || !document.body) { return; }
        var markers = findMarkers(commentsIn(document.body));
        // the screen's markup has to have closed whatever it opened for its two ends to be side by
        // side; when they are not, what is between them is not clear, and its forms post as ever
        if (markers && markers.start.parentNode === markers.end.parentNode) {
            api.page = new Host(markers.start, markers.end);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hostPage);
    } else {
        hostPage();
    }
}());
