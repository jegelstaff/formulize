---
layout: default
permalink: documentation/install_as_an_app/
title: Installing a Formulize Site as an App
---

# Installing a Formulize site as an app

You can install a Formulize site on your phone, tablet or computer, the same way you would install
any other app. It then has its own icon on your home screen, in your dock or on your taskbar, and it
opens in a window of its own, without the browser's address bar and tabs around it.

Everything else is the same as using the site in your browser: the same menus, the same screens and
the same data. You still need an internet connection to use it. If the connection drops, the app
shows a page saying you're offline, with a **Try again** button.

## Installing it

Sign in, open the site's menu, and look at the bottom of it. The item there is named for what it
does on your device. It only appears where your browser can install the site, and once the site is
installed, it doesn't appear in the app.

| Where | What to do |
|---|---|
| **Chrome or Edge on a computer** | Click **Install this site as an app**, then **Install**. The app opens in its own window, and you can pin it to your taskbar or dock. |
| **Chrome or Samsung Internet on Android** | Tap **Add this site to your home screen**, then **Install**. The app's icon appears on your home screen. Chrome may also offer it in a banner at the bottom of the screen. |
| **iPhone or iPad** | Tap **Add this site to your home screen** and follow the steps it shows: tap the Share button in Safari's toolbar, then **Add to Home Screen**. If it isn't in the list, scroll down to find it. |
| **Safari on a Mac** | Click **Add this site to your Dock** and follow the steps it shows: in Safari's **File** menu, choose **Add to Dock**. This needs Safari 17 or later. |
| **Firefox** | There is no item in the menu. On Android, use **Add to Home screen** in Firefox's own menu. On a computer, use Chrome, Edge or Safari to install the site. |

You can also install the site without signing in, from your browser's own menu: **Install page as
app** in Chrome, **Apps** then **Install this site as an app** in Edge, or the steps above on an
iPhone, an iPad or a Mac.

On an iPhone or iPad, the app keeps its own sign-in, separate from Safari, so you'll be asked to
sign in the first time you open it.

## Using it

The app opens on the site's home page. When you open a link to one of the site's pages that would
normally open a new window, it opens in the app instead. Links to other websites open in your
browser.

In Chrome and Edge, the app's window has its own back and reload buttons at the top, as a browser
does.

On an iPhone or iPad, the app's window has no buttons of its own. Once you've moved on from the first
page, a back button appears at the top left of the site's header. While the next page is loading, a thin bar runs
across the top of the screen.

## Removing it

Remove it the way you remove any other app on that device:

- **Android:** touch and hold the icon, then choose **Uninstall**.
- **iPhone or iPad:** touch and hold the icon, then choose to remove it.
- **Chrome or Edge on a computer:** in the app's window, open the menu at the top and choose
  **Uninstall**.
- **Safari on a Mac:** drag the app from the **Applications** folder in your home folder to the
  Trash.

Removing the app doesn't affect your account or anything on the site.

## For administrators

Installing is turned on for every site that is served over https. The settings are under
**Admin > Settings > Advanced**, in **Installing as an app**:

- **Let people install this site as an app on their phone or computer.** Turn this off, and the
  site stops offering to be installed. Where it is already installed, it keeps working as a web page
  in its own window, and the offline page is removed the next time the app is opened. The icon stays
  on the device until the person removes it.
- **Name under the app's icon.** Phones cut long names short under an icon. If your site's name is
  long, give a shorter one here. When it's empty, the site's name is used.

The app's colours are your site's colours, from the Appearance settings: its title bar is the colour
of the site's top bar, and it opens on the colour of the page background.

### Giving the app your own icon

The app uses the Formulize icon until you give it your own. Make these five PNG files and put them in
the `uploads/pwa` folder of your site:

| File | Size | What it is |
|---|---|---|
| `icon-512.png` | 512 × 512 | Your icon. A transparent background is fine. |
| `icon-192.png` | 192 × 192 | The same, smaller. |
| `icon-maskable-512.png` | 512 × 512 | Your icon on a solid background that fills the square, with everything that matters inside the circle in the middle 80%. Phones crop the corners off this one to fit their own icon shape. |
| `icon-maskable-192.png` | 192 × 192 | The same, smaller. |
| `apple-touch-icon.png` | 180 × 180 | Your icon on a solid background, with no transparency. This one is for iPhones and iPads. |

Only `icon-512.png` is required. As soon as it is there, your icons are used in place of the
Formulize ones. Any of the other plain icons that are missing are made from it. If the maskable icons
are missing, phones show your icon on a plain background instead.

Devices pick up a new icon on their own schedule, which can take a day or more. On an iPhone or iPad,
the icon is fixed when the app is installed, so people need to remove the app and install it again
to see a new one.
