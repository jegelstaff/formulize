# The installable app

A site can be installed as an app. The code is in `modules/formulize/pwa/` (`worker.js`, `sw.php`, `manifest.php`, `remove-worker.js`), `include/functions/pwa.php`, and `include/js/formulize-app.js` and `pwa.js`. Pages ask `formulize_pwaFeaturesApply()` whether the app features apply.

## The service worker runs for everyone

**The worker is registered for every visitor on every page** whenever the setting is on and the site is secure, not only for people who've installed the app. That includes `localhost`, so local runs and CI run with a worker in charge.

It only handles plain page loads, and it doesn't keep pages, because they show people's entries. It keeps the offline page and its stylesheets.

**Requests sent as a page closes have to stay in the worker.** Chromium gives a `keepalive` fetch or `sendBeacon` from `pagehide` to the worker. If the worker doesn't respond to it, Chromium hands it back to the page, which is gone, so it's never sent. Firefox sends it either way. Releasing entry locks depends on this, so keep this branch in `worker.js`:

```js
if (request.keepalive) {
    event.respondWith(fetch(request));
    return;
}
```

When a request doesn't show up in the Apache log (`docker logs formulize-web-1`), suspect the worker. Playwright reports keepalive requests as `net::ERR_ABORTED` even when they succeed, so check the server log.

**A new worker waits for the site to be closed before it takes over.** Don't add `skipWaiting()` or `clients.claim()` to `worker.js`. A page loading through the old worker while a new one takes over can be left hanging, and every save of a look changes the worker's bytes. `remove-worker.js` is different: it takes over at once, to remove the worker from devices when the setting goes off.

A webmaster's page uses `theme_admin_set`, so anything that applies to the whole app reads the site's theme through `formulize_pwaSiteTheme()`.

## Testing

The specs are `02-validate/044-installable-app` and `03-validate-sequential/050-installable-app-turned-off`. The second runs on its own, because turning the setting off reaches every browser with the site open, and would land on whatever else was running.

- **Offline:** `context.setOffline(true)` works for the worker's own requests in Chromium. Playwright can't take Firefox offline for them, so test registration and the cache in Firefox, and offline in Chromium.
- **Standalone mode can't be emulated.** Stand in iOS's signal with an init script that defines `navigator.standalone`. That drives `formulize.isInstalledApp()`, but CSS with `fz-show-in-app` still hides things, so check the `hidden` attribute, not visibility.
- **Don't block service workers** to get a test to pass. Real people have one, so that steers around what they'd hit. See [testing-through-the-application](testing-through-the-application.md).
