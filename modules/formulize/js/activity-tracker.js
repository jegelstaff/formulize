/**
 * Formulize Activity Tracker
 *
 * Records user navigation and form submissions into localStorage so the AI
 * chat window can include recent activity as context when sending messages.
 * Works across tabs: all Formulize tabs write to the same localStorage key,
 * and the chat window receives live updates via the browser's `storage` event.
 *
 * Storage key: 'formulize_activity_log' — JSON array of event objects,
 * pruned to the last 60 events within the past 30 minutes.
 */
(function () {
    'use strict';

    var STORAGE_KEY = 'formulize_activity_log';
    var MAX_EVENTS  = 60;
    var MAX_AGE_MS  = 30 * 60 * 1000; // 30 minutes

    function getLog() {
        try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
        catch (e) { return []; }
    }

    function writeLog(log) {
        var cutoff = Date.now() - MAX_AGE_MS;
        var pruned = [];
        for (var i = 0; i < log.length; i++) {
            if (log[i].ts > cutoff) pruned.push(log[i]);
        }
        if (pruned.length > MAX_EVENTS) pruned = pruned.slice(pruned.length - MAX_EVENTS);
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(pruned)); } catch (e) {}
    }

    function addEvent(evt) {
        var log = getLog();
        evt.ts = Date.now();
        log.push(evt);
        writeLog(log);
    }

    function parseParams(search) {
        var params = {};
        if (!search || search.length < 2) return params;
        var pairs = search.substring(1).split('&');
        for (var i = 0; i < pairs.length; i++) {
            var kv = pairs[i].split('=');
            if (kv.length >= 2) {
                try { params[decodeURIComponent(kv[0])] = decodeURIComponent(kv[1]); } catch (e) {}
            }
        }
        return params;
    }

    function fieldVal(form, name) {
        var el = form.querySelector('[name="' + name + '"]');
        return (el && el.value) ? el.value : null;
    }

    var path    = window.location.pathname;
    var params  = parseParams(window.location.search);
    var isAdmin = path.indexOf('/admin/') !== -1 || path.indexOf('/admin.php') !== -1;

    // Don't record visits to the AI chat page itself
    if (path.indexOf('ai_chat.php') !== -1) return;

    // Record this page visit. Admin URL params (ele_id, op) add useful detail.
    addEvent({
        type:   'pageview',
        url:    path + window.location.search,
        title:  document.title,
        fid:    params.fid    || null,
        sid:    params.sid    || null,
        ele_id: isAdmin && params.ele_id ? params.ele_id : null,
        entry:  params.ve     || params.entry_id || null,
        op:     isAdmin && params.op ? params.op : null,
        admin:  isAdmin       || undefined
    });

    // Intercept form submissions (capture phase catches all forms, including those
    // that AJAX-submit — the submit event still fires before jQuery/fetch takes over).
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form) return;

        var evt = {
            type:     'form_submit',
            title:    document.title,
            url:      path + window.location.search,
            fid:      fieldVal(form, 'fid'),
            entry_id: fieldVal(form, 'entry_id'),
            admin:    isAdmin || undefined
        };

        if (isAdmin) {
            // The admin handler name tells us exactly what is being saved
            // (e.g. 'element_options', 'form_settings', 'screen_list_headings')
            var handler = fieldVal(form, 'formulize_admin_handler');
            var adminKey = fieldVal(form, 'formulize_admin_key');
            if (handler) evt.admin_handler = handler;
            if (adminKey) evt.admin_key = adminKey;
            // Capture admin-context IDs from URL since forms don't always have hidden fid
            if (!evt.fid && params.fid) evt.fid = params.fid;
            if (params.sid)    evt.sid    = params.sid;
            if (params.ele_id) evt.ele_id = params.ele_id;
        }

        addEvent(evt);
    }, true);

}());
