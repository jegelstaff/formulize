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

There are three ways to call this endpoint, and they are tried in this order.

__An existing session.__ Javascript running on a page of this site is already logged in, so it needs no key at all. This is the safest way to use the API from a browser.

__An API key.__ Send it as an `Authorization: Bearer` header. Create keys on the __Manage API Keys__ page in the Formulize admin. Every key belongs to a user, and the request sees exactly the data that user can see.

```
Authorization: Bearer 8f3ca19d...
```

__Anonymous.__ A request with no session and no key runs as the anonymous user. This is a supported way to publish data, not an error. It returns nothing at all unless an administrator has granted the Anonymous group permission to view the form, so nothing is exposed by accident.

An API key gives access to Formulize in exactly the same way as logging in with someone's username and password. __Never put an API key in the Javascript of a public web page__, where anyone can read it. For a public page, rely on anonymous access instead, and grant the Anonymous group view permission only on the forms you intend to publish.

If your server does not pass the `Authorization` header through to PHP, API keys will not work, and the Public API setting will say so after you save it. On Apache, adding `CGIPassAuth On` to your .htaccess file usually solves it.

## Calling from another website

Javascript on another website can only read the response if an administrator has listed that site under __Websites allowed to call the Public API__, in Settings &rarr; Advanced &rarr; Public API. Leave that setting blank and only pages on this site can call the API from a browser.

That setting controls web browsers. It is not a substitute for permissions: what any caller can read is still decided by Formulize permissions.

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

Groups cannot contain other groups. Formulize filters support one level of grouping, and a nested group is rejected rather than quietly flattened.

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
             "message": "You do not have permission to view this form" } }
```

| Status | When |
|---|---|
| 400 | A parameter is missing or not valid, including a field name that does not exist |
| 401 | An API key was supplied but is not valid or has expired |
| 403 | This user, or the anonymous user, may not view this form |
| 404 | No such form, or no such method |
| 405 | An http method other than GET or POST |
| 503 | The Public API is not enabled |

## Examples

Reading data into a web page, with no API key, from a form that has been opened to the Anonymous group:

```javascript
async function loadDonors() {
  const res = await fetch(
    'https://example.org/formulize-public-api/v1/form/donors/read',
    { method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        fields: ['donor_name', 'amount'],
        filter: [ { element: 'amount', value: '100', operator: '>' } ],
        sortField: 'amount',
        sortOrder: 'DESC',
        limitSize: 50
      }) }
  );
  if (!res.ok) {
    const { error } = await res.json();
    throw new Error(error.message);
  }
  const { data, meta } = await res.json();

  // The data is in hand, so everything from here is ordinary synchronous code.
  document.querySelector('#count').textContent = meta.count;
  document.querySelector('#list').innerHTML =
    data.map(d => `<li>${d.donor_name} - ${d.amount}</li>`).join('');
}

loadDonors().catch(err => {
  document.querySelector('#list').textContent = 'Could not load donors.';
  console.error(err);
});
```

An `async` function returns a promise, so always attach a `catch` where you call it, or errors will pass silently.

Reading two forms at once, by starting both requests before waiting for either:

```javascript
const read = (form, body) =>
  fetch(`https://example.org/formulize-public-api/v1/form/${form}/read`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body)
  }).then(r => r.json());

const [donors, events] = await Promise.all([
  read('donors', { fields: ['donor_name', 'amount'] }),
  read('events', { fields: ['event_name', 'event_date'] })
]);
```

Rendering connected forms:

```javascript
const { data } = await read('countries', {
  fields: ['country_name', 'city_name'],
  relationship: -1
});

document.querySelector('#out').innerHTML = data.map(country => `
  <h2>${country.country_name}</h2>
  <ul>${(country.related?.cities ?? [])
        .map(c => `<li>${c.city_name}</li>`).join('')}</ul>
`).join('');
```

From a server, a script, or a tool such as Zapier or Make, where the key stays secret:

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
