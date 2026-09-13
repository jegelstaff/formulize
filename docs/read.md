---
layout: default
permalink: developers/Public_API/read
title: read
---

# formulize-public-api/v1/form/{form}/read

Returns entries from a form as JSON. _{form}_ can be the form handle or the form id.

Send a `POST` with a JSON body, or a `GET` with query string parameters. `POST` is recommended: it carries nested filters comfortably, and it keeps an API key out of server logs and browser history.

If the API is not enabled, a 503 http error is returned.

## Authentication

A request either carries an API key or it does not.

__With an API key__, the request runs as the user the key belongs to, and sees exactly the data that user can see. Send the key as an `Authorization: Bearer` header. Create keys on the __Manage API Keys__ page in the Formulize admin.

```
Authorization: Bearer 8f3ca19d...
```

__Without an API key__, the request runs as the anonymous user. This is a supported way to publish data, not an error. It returns nothing at all unless an administrator has granted the Anonymous group permission to view the form, so nothing is exposed by accident.

An API key gives access to Formulize in exactly the same way as logging in with that user's username and password, and anyone who can see the key can use it. A key in the Javascript of a web page can be read by everyone who loads that page. So give the key to a user who can see only what those visitors should see, or rely on anonymous access if the data is meant for the public.

If your server does not pass the `Authorization` header through to PHP, API keys will not work, and the Public API setting will say so after you save it. See [The Authorization header](../Public_API/#the-authorization-header) on the Public API page for how to fix it.

## Parameters

| Parameter | Description |
|---|---|
| __fields__ | __Required.__ The element handles to return. Metadata field names such as `creation_datetime` can be included too. `entry_id` is always returned. |
| filter | Which entries to return. See below. |
| andOr | `AND` or `OR`, between the top level items of _filter_. Defaults to `AND`. |
| sortField | An element handle or metadata field name to sort by. Defaults to `entry_id`. |
| sortOrder | `ASC` or `DESC`. Defaults to `ASC`. |
| limitStart | The first record to return, counting from 0. Defaults to 0. |
| limitSize | How many records to return. Defaults to 100, and cannot exceed 10000. Use `null` for no limit. |
| relationship | `0`, the default, returns data from this form only. `-1` uses the Primary Relationship, so data from connected forms is included as well. |
| raw | `true` returns raw database values instead of readable ones. A list of element handles returns just those fields raw. |

Requesting a field you do not have permission to see is an error, not a silent omission. _fields_ is required because it decides how much data has to be gathered and converted, so asking for only what you need keeps the request fast.

## Filters

The preferred form is a list of conditions. Each needs an _element_ and a _value_. The _operator_ is optional and defaults to `LIKE`, so a plain condition is a partial text match.

```json
"filter": [
  {"element": "donor_type", "value": "major"},
  {"element": "amount", "value": "100", "operator": ">"}
]
```

Valid operators are `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE` and `NOT LIKE`. Use the value `{BLANK}` to find entries where a field is empty, or combine it with `!=` to find entries where it is not.

Filter linked elements by the value you can read on screen, not by the foreign key stored underneath. The API resolves it for you.

Top level items are joined by _andOr_. To use a different operator for part of the expression, wrap conditions in a group. `any` puts `OR` between them, `all` puts `AND`:

```json
"filter": [
  {"element": "status", "value": "active", "operator": "="},
  {"any": [
    {"element": "region", "value": "east", "operator": "="},
    {"element": "region", "value": "west", "operator": "="}
  ]}
]
```

That reads as _status = active AND (region = east OR region = west)_.

A `none` group finds entries that have __no connected entry__ matching its conditions. It needs a _relationship_, and every condition in it has to be on the same connected form. This returns donors who have not given any BCE artifacts, including donors who have given no artifacts at all:

```json
"relationship": -1,
"filter": [
  {"none": [
    {"element": "artifacts_era", "value": "BCE", "operator": "="}
  ]}
]
```

All the conditions in a `none` group have to be true of the same connected entry. `{"none": [era = CE, short_name LIKE Coin]}` rules out donors who gave a CE coin, not donors who gave a CE artifact and, separately, some coin. Use one `none` group per connected form, and more than one if you need separate tests on the same form.

A `none` group cannot be used when _andOr_ is `OR`, cannot use metadata fields, and cannot contain other groups. A `{BLANK}` test with `=` has to be the only condition in its `none` group; with `!=` it can sit alongside others.

A filter can also be a single number, meaning one entry id.

You can only filter on a field you have permission to see. Filtering on a field reveals its contents just as returning it does, so a field that would be refused in _fields_ is refused in _filter_ and in _sortField_ too. A value cannot contain `][` or `/**/`, which are reserved by the filter format.

## The response

```json
{
  "data": [
    { "entry_id": 412,
      "donor_name": "Aiko Tanaka",
      "amount": "250.00" }
  ],
  "meta": { "form": "donors", "count": 1, "limitStart": 0, "limitSize": 100 }
}
```

Page through a large result by increasing _limitStart_ by _limitSize_ until fewer rows come back than you asked for.

When _relationship_ is set, entries from connected forms appear under `related` inside the row they belong to, grouped by form handle. Each one carries its own _entry_id_, from its own form:

```json
{ "entry_id": 412,
  "country_name": "Canada",
  "related": {
    "cities": [
      {"entry_id": 88, "city_name": "Toronto"},
      {"entry_id": 91, "city_name": "Halifax"}
    ]
  } }
```

`related` is left out entirely when there is nothing connected.

## Errors

Errors return an http status code and a body like this:

```json
{ "error": { "code": "permission_denied",
             "message": "You do not have permission to view this form",
             "hint": "This request carried no Authorization header, so it was handled as anonymous..." } }
```

_message_ is a plain explanation that is suitable to show to people. _code_ does not change, so use it when your code needs to react to a particular error. _hint_ is only included on some errors, and is aimed at the developer: it explains the likely cause, such as a missing API key.

Two errors have no body at all, only the status code: 503 when the Public API is not enabled, and 404 when the address does not match any part of the API, such as a misspelling of `form` in the URL. Read the body with that in mind.

| Status | When |
|---|---|
| 400 | A parameter is missing or not valid, including a field name that does not exist |
| 401 | An API key was supplied but is not valid or has expired |
| 403 | This user, or the anonymous user, may not view this form |
| 404 | No such form, or no such method |
| 405 | An http method other than GET or POST |
| 503 | The Public API is not enabled |

## Examples

### Calling from another website

Javascript on another website can only read the response if an administrator has listed that site under __Websites allowed to call the Public API__, in Settings &rarr; Advanced &rarr; Public API. Leave that setting blank and no other website can call the API from a browser.

That setting controls web browsers. It is not a substitute for permissions: what any caller can read is still decided by Formulize permissions.

First, a small function that makes the request and turns every kind of failure into an error with a useful message. The rest of the examples use it:

```javascript
async function readForm(form, body) {
  let res;
  try {
    res = await fetch(`https://example.org/formulize-public-api/v1/form/${form}/read`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body)
    });
  } catch (networkError) {
    // fetch only fails like this when no response could be read at all: the site is
    // unreachable, or it has not allowed this website to call the Public API.
    throw new Error('Could not reach the Public API. The site may be down, or may not allow requests from this website.');
  }

  // Most errors carry a JSON body, but a 503 or an unknown address has none, so don't count on one.
  const json = await res.json().catch(() => null);

  if (!res.ok) {
    const error = new Error(json?.error?.message ?? `The Public API responded with http status ${res.status}`);
    error.status = res.status;
    error.code = json?.error?.code;
    error.hint = json?.error?.hint;
    throw error;
  }
  return json;
}
```

And a small function for putting data on the page. It makes an element and adds whatever you pass it as the contents. Text is always added as text, never as HTML, so values from the API cannot run as code. See [Cross-site Scripting Risks](#cross-site-scripting-risks) for why that matters.

```javascript
function el(tag, ...contents) {
  const element = document.createElement(tag);
  element.append(...contents);
  return element;
}
```

Reading data into a web page on another website, from a form that has been opened to the Anonymous group:

```javascript
async function loadDonors() {
  const { data, meta } = await readForm('donors', {
    fields: ['donor_name', 'amount'],
    filter: [ { element: 'amount', value: '100', operator: '>' } ],
    sortField: 'amount',
    sortOrder: 'DESC',
    limitSize: 50
  });

  // The data is in hand, so everything from here is ordinary synchronous code.
  document.querySelector('#count').textContent = meta.count;
  // Make an <li> for each entry, then put them all in the list in place of what was there.
  // el() and replaceChildren() both add values as text, never as HTML.
  document.querySelector('#list').replaceChildren(
    ...data.map(d => el('li', `${d.donor_name} - ${d.amount}`))
  );
}

loadDonors().catch(err => {
  // Show the reason on the page. textContent displays it as plain text, never as HTML.
  document.querySelector('#list').textContent = `Could not load donors: ${err.message}`;
  // The hint is meant for you, not your visitors, so it goes to the console.
  console.error(err.message, err.code ?? '', err.hint ?? '');
});
```

An `async` function returns a promise, so always attach a `catch` where you call it, or errors will pass silently. Showing `err.message` matters: a page that only says _Could not load donors_ hides whether the problem is permissions, a mistyped field name, or the API being turned off.

To react to a particular error, check `err.code` or `err.status` rather than the wording of the message:

```javascript
loadDonors().catch(err => {
  const list = document.querySelector('#list');
  if (err.code === 'permission_denied') {
    list.textContent = 'This list is not public yet.';
  } else {
    list.textContent = `Could not load donors: ${err.message}`;
  }
  console.error(err.message, err.code ?? '', err.hint ?? '');
});
```

The same request with an API key, which runs as the key's user instead of the anonymous user. Anyone who loads the page can read the key, so see [Authentication](#authentication) before using one this way. Add the key to the headers in `readForm`:

```javascript
const API_KEY = '8f3ca19d...';

// in readForm:
    headers: {
      'Authorization': `Bearer ${API_KEY}`,
      'Content-Type': 'application/json'
    },
```

Reading two forms at once, by starting both requests before waiting for either. If either request fails, `Promise.all` fails with that request's error, so the same `catch` handles it:

```javascript
const [donors, events] = await Promise.all([
  readForm('donors', { fields: ['donor_name', 'amount'] }),
  readForm('events', { fields: ['event_name', 'event_date'] })
]);
```

Rendering connected forms:

```javascript
const { data } = await readForm('countries', {
  fields: ['country_name', 'city_name'],
  relationship: -1
});

document.querySelector('#out').replaceChildren(...data.flatMap(country => [
  el('h2', country.country_name),
  el('ul', ...(country.related?.cities ?? []).map(city => el('li', city.city_name)))
]));
```

### Cross-site Scripting Risks

__Do not put values from the API into `innerHTML`, `outerHTML`, `insertAdjacentHTML()` or other functions and methods that treat a string as HTML.__ The Formulize API returns values exactly as they were entered, regardless of whether you ask for _raw_ values (in the `read` method, _raw_ values as a concept relates to things like foreign keys in the Formulize database).

When Formulize displays data in its own screens it makes the data safe to show, but when you request data through the API, you get exactly what is in the database. It's your job to take appropriate steps to make it safe.

A value like `<img src=x onerror="...">` is returned as those characters, and a rich text field is returned as its HTML. Put that into `innerHTML` and the browser runs it as part of your page. Anyone who can fill in the form in Formulize, including anonymous visitors if the form allows them, could then run their own script on your website, for everyone who visits it.

This is called cross-site scripting (XSS). To show values safely, build the elements yourself and add values to them as text. `textContent`, `append()` and `replaceChildren()` all treat a string as plain text, which is what the examples above use. `innerHTML`, `outerHTML` and `insertAdjacentHTML()` treat a string as HTML, __so never give them values from the API__. If you really need to show a rich text field's formatting, clean the HTML first with a sanitizer such as [DOMPurify](https://github.com/cure53/DOMPurify): `DOMPurify.sanitize(value)`.

The greatest risk is when a form in Formulize is open for public submissions, and you are displaying data from those submissions. If there are restrictions on who can submit data, the risk is lower, but the risk is never zero, because even trusted users can have their accounts stolen, hijacked, etc, and then data that you might believe is trusted can actually be malicious.


### From a server, a script, or a tool such as Zapier or Make

```
curl -X POST https://example.org/formulize-public-api/v1/form/donors/read \
  -H "Authorization: Bearer 8f3ca19d..." \
  -H "Content-Type: application/json" \
  -d '{"fields":["donor_name","amount"],"limitSize":25}'
```

The same request as a `GET`, for quick testing. Lists are comma separated:

```
curl "https://example.org/formulize-public-api/v1/form/donors/read?fields=donor_name,amount&limitSize=25" \
  -H "Authorization: Bearer 8f3ca19d..."
```
