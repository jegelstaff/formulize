# Writing user documentation

Write each piece of documentation for its audience. Audiences have different priorities and different
levels of familiarity with Formulize's features, so decide who the reader is before you write anything.

Then write from their point of view. Tell them what to do, what they will see, and what to do when it
goes wrong. Do not explain how something works internally, and do not justify or elaborate on design
decisions, unless that changes what the reader actually does.

## Who the reader is

There are four kinds of reader, and one person can be more than one of them. From webmaster to end
user, each can do less on the admin side of Formulize than the one before.

| Reader | What they do | What matters to them | What they're familiar with | What to write for them |
|---|---|---|---|---|
| Developer | Works on the Formulize code, or extends it with their own code. May not run a site. | How it works, and where to make a change | The code, but maybe not the admin side | How the code works. This is the only reader who needs mechanism. |
| Webmaster | Runs a Formulize site, with full access to the admin side, its settings and the server. | Setting things up and keeping the site working | The admin side and its terms, like forms, elements and screens | Every setting and server change they need, but not how the code works. |
| User | Does some admin, but not everything a webmaster does. They might build whole applications, or only edit the questions in a form. | Getting a specific job done on the admin side | The admin features they use, and maybe nothing else | The admin features they use. Explain terms and don't assume they can reach other settings or the server. |
| End user | Uses an application as it was configured, with no access to the admin side. | Getting their own work done in the application | Their application, and maybe not even the word Formulize | Only what they see in the application, in its terms. Never mention the admin side or configuration. |

Check who each section is for. One page can serve more than one reader, but a given section should be
aimed at one of them.

## How to apply this

- Lead with the action and the consequence. "Point the iframe at a different domain and visitors can
  see the form but not submit it" beats explaining session cookies and SameSite.
- Only name a technical thing when the reader must type it, configure it, or search for it. A CDN
  setting they must change gets named; a response header they never touch does not.
- Cut rationale that exists to defend the design. Keep rationale that tells them why an option
  exists or which one to pick.
- Symptom-first for failure modes: "If the screen appears with your site's menus, add
  `?formulize_embed=1`" rather than explaining what the browser did or didn't send.
- Notes for the people maintaining the code belong in code comments or developer documentation.

## Why

`docs/embedding_screens.md` was originally full of sentences written from the implementer's chair —
"`frame-ancestors` matches on the origin, so anything from the path onwards is trimmed off", "Formulize
builds absolute URLs out of its own hostname and base path", "Because the same URL now has two
renderings, Formulize sends `Vary: Sec-Fetch-Dest`". Its readers didn't know what those terms were or
why they'd matter, and the explanations were really there to justify the implementation to a fellow
developer. Such details may be relevant to Formulize developers, but not to someone trying to get
embedding to work on their site.
