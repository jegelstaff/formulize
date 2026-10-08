---
layout: default
permalink: documentation/embedding_screens/analytics/
title: Google Analytics and Embedded Screens
---

# Google Analytics and embedded screens

This builds on [Embedding screens in another website](../).

The Google Analytics tag on your page counts visits to the page, but it cannot see inside an
embedded screen. Google's automatic form tracking does not see the form either, because the form
comes from your Formulize site, not from your page.

Instead, the screen tells your page what the visitor is doing: starting the form, moving to the next
page, missing a required field, finishing. Add the script below to your page and these become Google
Analytics events. They are recorded in the same visit as everything else on your page, so you can see
which page or campaign led someone to fill in your form.

**Do not add your Google tag to the embed theme.** Inside the frame, Google Analytics would count the
visitor as a new person on a separate visit, and many browsers block its cookie there altogether.
You would lose any record of how they reached your page.

## Add the script to your page

Your page needs the Google tag already, in its `<head>`. Then put this script after the embed code,
on every page that embeds a screen:

```html
<script>
(function () {
  if (typeof gtag !== 'function' || window.formulizeAnalyticsListening) { return; }
  window.formulizeAnalyticsListening = true;

  // Screens that count as a key event when the visitor finishes them, by screen number.
  // Change these to your own screens. Screens not listed still send form_submit.
  var keyEvents = {
    12: ['generate_lead', {}],
    15: ['sign_up', {method: 'newsletter'}]
  };

  var screens = {};

  function screenFor(event) {
    var screen = event.detail.screen || {id: 0, title: ''};
    if (!screens[screen.id]) {
      screens[screen.id] = {
        id: screen.id,
        params: {
          form_id: 'formulize-screen-' + screen.id,
          form_name: screen.title,
          form_destination: event.target.src.split('?')[0]
        },
        page: null, lastField: null, started: false, completed: false, abandoned: false
      };
    }
    return screens[screen.id];
  }

  function send(name, s, extra) {
    var params = {}, key;
    for (key in s.params) { params[key] = s.params[key]; }
    for (key in (extra || {})) { params[key] = extra[key]; }
    gtag('event', name, params);
  }

  document.addEventListener('formulize:ready', function (event) {
    var s = screenFor(event), page = event.detail.page;
    if (page && page.count > 1 && !page.thanks && (!s.page || s.page.number !== page.number)) {
      send('form_step', s, {step_number: page.number, step_count: page.count, step_title: page.title});
    }
    s.page = page;
  });

  document.addEventListener('formulize:started', function (event) {
    var s = screenFor(event);
    if (!s.started) {
      s.started = true;
      send('form_start', s);
    }
  });

  document.addEventListener('formulize:fieldFocus', function (event) {
    screenFor(event).lastField = event.detail.field;
  });

  document.addEventListener('formulize:validationFailed', function (event) {
    send('form_error', screenFor(event), {form_field: event.detail.field || '(not known)'});
  });

  document.addEventListener('formulize:completed', function (event) {
    var s = screenFor(event);
    if (s.completed) { return; }
    s.completed = true;
    send('form_submit', s);
    if (keyEvents[s.id]) {
      send(keyEvents[s.id][0], s, keyEvents[s.id][1]);
    }
  });

  window.addEventListener('pagehide', function () {
    for (var id in screens) {
      var s = screens[id];
      if (s.started && !s.completed && !s.abandoned) {
        s.abandoned = true;
        send('form_abandon', s, {
          form_field: s.lastField || '(not known)',
          step_number: s.page ? s.page.number : 1
        });
      }
    }
  });
})();
</script>
```

Change the screen numbers in `keyEvents` to your own. A screen's number is the `sid` in the embed
code. `sign_up` and `generate_lead` are the names Google suggests for sign-ups and enquiries, and
using them gets you Google's standard reports for them. Any event name works.

## What you'll see in Google Analytics

| Event | When it's sent | Extra details |
|---|---|---|
| `form_start` | The visitor types something, or makes a choice, in the form for the first time. Clicking into a field does not count. Sent once each time someone views your page. | |
| `form_step` | The visitor arrives at a page of a multi-page form, including the first page. Not sent for forms with only one page. | `step_number`, `step_count`, `step_title` |
| `form_error` | The visitor tried to save and was told that something was missing or wrong. | `form_field`: the field the visitor was sent back to |
| `form_abandon` | The visitor started the form, then left your page without finishing it. | `form_field`: the last field they were in. `step_number`: the page they were on |
| `form_submit` | The visitor reached the form's thank-you page. | |
| your key events | At the same time as `form_submit`, for the screens listed in `keyEvents`. | `method`, if you set one |

Every event also carries `form_id` (`formulize-screen-` and the screen number), `form_name` (the
screen's title) and `form_destination` (your Formulize site's address). These are the same details
Google Analytics records for forms it tracks by itself, so you can compare your embedded forms with
any others.

Fields are named by their element handle, eg. `email_address`. **Nothing the visitor typed is ever
sent** — only which field it was.

Events show up in **Reports → Realtime** within a minute. The standard reports take a day or two.

## Setting up Google Analytics

**Mark your key events.** In **Admin → Data display → Key events**, add `sign_up`, `generate_lead`, or
whatever names you used in `keyEvents`. Once they are key events, Google Analytics links each one to
how the visitor arrived.

**Register the extra details.** Google Analytics records `form_field`, `step_number` and
`step_title`, but you can only use them in reports once they are registered. In **Admin → Data display
→ Custom definitions**, create a custom dimension for each one, with the scope set to **Event** and the
event parameter set to its name. Without them you can count `form_error` events, for example, but you
can't see which field caused them.

**Find where people give up.** In **Explore**, start a **Funnel exploration** with these steps:
`form_start`, then `form_step` with `step_number` equal to 2 (one step for each page of a multi-page
form), then `form_submit`. To see the fields people abandon on, break down `form_abandon` by
`form_field`.

**Check that events are arriving** with [Tag Assistant](https://tagassistant.google.com/). Connect it
to your page, fill in part of the form, and the events appear in **Admin → DebugView** as they happen.

## Using a different analytics tool

The script above is the only part that is specific to Google. The screen fires these events on its
iframe, and you can send them to any analytics tool:

| Event | When it fires | `event.detail` |
|---|---|---|
| `formulize:ready` | The screen has loaded a page. This includes the page it shows after saving, so it can fire several times. | `height`, and `page`: `{number, count, title, thanks}`, or `null` for a screen that is not a form |
| `formulize:started` | First time the visitor changes anything on the current page | `field` |
| `formulize:fieldFocus` | The visitor moved into a different field | `field` |
| `formulize:validationFailed` | The visitor tried to save and was told something was missing or wrong | `field`, or `null` if it could not be identified |
| `formulize:saved` | Something was saved. In a multi-page form, every page saves as the visitor moves on, so this fires on every page. | `newEntry`: `true` the first time a new entry is saved. `page` |
| `formulize:completed` | The visitor reached the thank-you page | `page` |

Every event also carries `screen`: `{id, title}`. `event.target` is the iframe. The events bubble, so
one listener on `document` covers every screen on your page.

## Things to know

**Forms without a thank-you page never send `formulize:completed`, or `form_submit`.** These are
multi-page forms where **The final page of the form should be** is set to *The last page with
questions*. When the visitor clicks Save and Finish, they leave the form straight away. To count
those visitors, listen for `formulize:saved` with `newEntry` set to `true`.

**Started means the visitor changed something.** Many forms put the cursor in their first field
automatically, so counting a click into a field would count visitors who never touched the form.

**Abandonment is counted when the visitor leaves your page.** Someone who opens another tab and comes
back later has not abandoned the form. On phones, a visitor who closes the browser without leaving
the page is sometimes not counted at all.

**If you made your own embed theme**, by copying `themes/formulize_embed` from an earlier version of
Formulize, your copy does not send these events. In the current `formulize_embed/theme.html`, copy the
script at the bottom, and the line just before it that prints `formulizeEmbedState`, into your copy.

## Troubleshooting

**No events arrive at all.** Check that your page loads the Google tag before this script; the script
does nothing if it can't find `gtag`. Then check the browser's developer console for errors. If
`formulize:ready` events arrive but no others, your Formulize site is older than these events, and
needs updating.

**`form_submit` never arrives.** The form has no thank-you page. See
[Things to know](#things-to-know).

**`form_field` is `(not known)`.** The visitor was in something other than a form element, such as a
button, or a custom form template has removed the `formulize-input-` classes that identify each
element.
