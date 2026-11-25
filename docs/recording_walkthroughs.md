---
layout: default
permalink: documentation/version_control/testing/recording_walkthroughs/
title: Recording walkthrough videos
---

# Recording walkthrough videos

A walkthrough is a video of someone using a Formulize application, recorded by a script. It is slowed down so a viewer can follow, with a caption explaining each step, a badge saying who is signed in, and a red dot showing where the mouse goes. Use one to show a client how a workflow you built is meant to be used, or to show a new feature.

Walkthroughs are written with [Playwright](https://playwright.dev/), the same tool as the [end to end tests](../), and use the same helpers. They aren't tests: they don't run in CI, and each one is recorded when you ask for it.

## What you need

- A local copy of Formulize, running, with the application you want to show. See [Getting a system to test against](../#getting-a-system-to-test-against).
- The e2e tests installed: `npm install` in `tests/e2e/`.
- [ffmpeg](https://ffmpeg.org/) on your path, to get `.mp4` files. Without it you get Playwright's `.webm` files, which some players and sites won't take.

## Record the example

`tests/e2e/walkthroughs/museum/` has a walkthrough of the museum system the tests build. It shows that each department sees only its own artifacts. It needs all of `formulize-core/01-setup` and the Anari theme, and it only reads, so you can record it as often as you like.

```bash
cd tests/e2e
npm run walkthrough -- museum/010
```

The video goes in `tests/e2e/walkthroughs/videos/`, named after the walkthrough, with a `.srt` subtitle file of its captions beside it. Git ignores that folder.

## Write a walkthrough

A walkthrough is a file ending in `.walkthrough.js`. Import `test` and `expect` from `walkthrough.js`, and use the `walk` object it gives each test:

```js
import { test, expect } from '../walkthrough.js';

test('Each department sees its own artifacts', async ({ walk, page }) => {
	await walk.say('Each department only sees the artifacts its own staff added.');
	await walk.signIn('ahstaff', { label: 'Ancient History staff' });
	await walk.menu('Artifacts');
	await expect(page.getByText('Showing entries: 1 to 8 of 8')).toBeVisible();
	await walk.say('Ancient History staff see their eight artifacts, such as the <b>Roman Coin</b>.');
	await walk.highlight(page.getByText('Roman Coin', { exact: true }).first());
	await walk.finish('A curator would see both departments\' artifacts.');
});
```

The test's title is the heading above the captions, and the name of the video.

| Helper | What it does |
|---|---|
| `say(html, ms)` | Shows a caption, and waits long enough to read it, unless you give `ms`. It stays up until the next caption |
| `quiet()` | Takes the caption down |
| `finish(html)` | A last caption, then a moment without it |
| `signIn(login, { label, password })` | Signs in on the login page, typing slowly, and puts the login and label in the badge. The password is `12345`, or the tests' admin password for the admin |
| `menu(label)` | Goes to a main menu link by its exact text |
| `go(url)` | Goes to an address. Use it rather than `page.goto()` whenever a form could be open |
| `click(locator)`, `type(locator, text)`, `pick(select, text)`, `setDate(locator, '2027-02-01')` | Moves the mouse to the target first, so the viewer sees where it goes. `pick` chooses the option whose text contains `text` |
| `highlight(locator, ms)` | Puts a yellow outline around something for a while |
| `openEntry(rowText)` | Opens the entry in a list row. With Lyris it opens in the drawer |
| `rowButton(rowText, label)` | Clicks a custom button in a list row |
| `save(label)` | Clicks a save button and waits for the save, on a page or in the drawer |
| `subform(caption, scope)`, `addSubformEntry(caption, fid, elementId, scope)` | A subform by its caption, and its Add One button |
| `calendarItem(label, monthsAhead)` | Opens an item on a calendar screen |
| `drawer()`, `row(text)`, `pause(ms)` | The drawer, a list row, and a plain wait |

Check the result of each step with `expect`, as a test would. If the workflow breaks, the recording stops and its video is named with `-failed`, so you don't share a video of something that didn't work.

## Walkthroughs for your own site

Keep a site's walkthroughs, and its videos, out of the Formulize repository. Put them in a folder of their own, and import from the repository's `walkthrough.js` by its full path:

```js
import { test, expect } from '/path/to/formulize/tests/e2e/walkthroughs/walkthrough.js';
```

Then point the recording at that folder:

```bash
cd tests/e2e
WALKTHROUGHS_DIR=/path/to/site-walkthroughs WALKTHROUGH_OUTPUT=/path/to/site-walkthroughs/videos npm run walkthrough
```

| Setting | What it does |
|---|---|
| `WALKTHROUGHS_DIR` | The folder to find walkthroughs in. Give the full path |
| `WALKTHROUGH_OUTPUT` | The folder the videos go in |
| `WALKTHROUGH_SLOWMO` | Milliseconds added before every click and keystroke. It's 120 unless you set it |
| `WALKTHROUGH_TIMEOUT` | How long one walkthrough may take, in milliseconds. It's 15 minutes unless you set it |

The site's address comes from `FORMULIZE_WEB_PORT` in the repository's `.env`, as it does for the tests.

## Recording a workflow that changes data

Most workflows can't be done twice: once attendance is taken or an entry is approved, it stays that way. So:

1. **Write down what happens:** who signs in, what they click, and what each caption says.
2. **Rehearse with practice entries.** Have the walkthrough read the names and dates it works on from environment variables, so it can run against a practice patient or customer until it goes through cleanly.
3. **Take a copy of the database,** then record the real take. If something goes wrong, you can load the copy back and try again. With the Docker setup, and your own container's name, that's:

   ```bash
   docker exec formulize-mariadb-1 sh -c 'mariadb-dump -u"$MYSQL_USER" -p"$MYSQL_PASSWORD" --databases "$MYSQL_DATABASE"' > before-recording.sql
   ```
4. **Check the data** afterwards, in the application.
5. **Delete the practice entries in the application,** the way a person would, from the lists.

## When it goes wrong

- **The video ends early, named `-failed`.** A step didn't work. The trace is in `tests/e2e/walkthroughs/results/`. Open it with `npx playwright show-trace <file>`.
- **A form in the drawer won't save.** The drawer only saves once something in its form has been clicked or focused. Choose dropdown options with `pick()`, which focuses the list first.
- **A form opens read-only.** An earlier step left it without releasing the entry. Leave forms with `menu()` or `go()`, never `page.goto()`.
- **Captions are cut off at the right.** They leave room for the drawer, so keep each caption to a sentence or two.
