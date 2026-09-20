---
layout: default
permalink: documentation/Public_API/filters
redirect_from:
 - developers/Public_API/filters
title: filters
---

# Filters

A filter decides which entries a Public API request works with. Endpoints that take a _filter_ parameter, such as [reading entries from a form](read), all use the format described here.

## Conditions

A filter is a list of conditions. Each condition needs an _element_ and a _value_. The _operator_ is optional and defaults to `LIKE`, which is a partial text match that ignores case.

```json
"filter": [
  {"element": "donor_type", "value": "major"},
  {"element": "amount", "value": "100", "operator": ">"}
]
```

Valid operators are `=`, `!=`, `>`, `<`, `>=`, `<=`, `LIKE` and `NOT LIKE`.

Use the value `{BLANK}` to find entries where a field is empty, or combine it with `!=` to find entries where it is not.

Filter linked elements by the value you can read on screen, not by the foreign key stored underneath. The API resolves it for you.

A filter can also be a single number, meaning one entry id.

You can only filter on a field you have permission to see. Filtering on a field reveals its contents just as returning it does, so a field that would be refused in _fields_ is refused in _filter_ and in _sortField_ too. A value cannot contain `][` or `/**/`, which are reserved by the filter format.

In a `GET` request, send the list of conditions as JSON in the query string, URL encoded.

## Joining conditions with andOr

The top level items in a filter are joined by the _andOr_ parameter. _andOr_ is sent alongside _filter_ in the request, not inside it. It is `AND` unless you say otherwise, so every condition has to be true. Set it to `OR` and any one of them is enough:

```json
"andOr": "OR",
"filter": [
  {"element": "donor_type", "value": "major"},
  {"element": "amount", "value": "100", "operator": ">"}
]
```

That returns major donors, and also anyone who gave more than 100.

## Groups: any and all

To mix `AND` and `OR` in one filter, put some of the conditions in a group. A group is an object with a single key, `any` or `all`, holding a list of conditions:

* `any` puts `OR` between its conditions, so at least one of them has to be true.
* `all` puts `AND` between its conditions, so every one of them has to be true.

The group as a whole counts as one top level item, and is joined to the other items by _andOr_.

Use `any` when the rest of the filter is joined by `AND`:

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

Use `all` when the rest of the filter is joined by `OR`. This returns the artifacts in the Ancient History collection that have "coin" in their name, and every artifact in the Weapons collection:

```json
"andOr": "OR",
"filter": [
  {"all": [
    {"element": "artifacts_collections", "value": "Ancient"},
    {"element": "artifacts_short_name", "value": "coin"}
  ]},
  {"element": "artifacts_collections", "value": "Weapons"}
]
```

That reads as _(collection is like Ancient AND name is like coin) OR collection is like Weapons_.

Some rules for groups:

* A group must contain at least one condition.
* Groups cannot be nested inside other groups. Formulize filters support one level of grouping.
* A list inside the filter is not a group. `[[{...}, {...}], {...}]` is refused, because the API expects every item to be either a condition or a group. Write `[{"all": [{...}, {...}]}, {...}]` instead.
* A `{BLANK}` test is really two tests. "Is blank" means empty or missing, and "is not blank" means not empty and not missing. So an "is blank" test can only go in an `any` group, and an "is not blank" test can only go in an `all` group. Either one can always go at the top level of the filter.

## Conditions on connected forms

When a request uses a _relationship_, a filter can include elements from the connected forms as well as the main form. A condition on a connected form's element works in two ways at once:

* It keeps the main form entries that have __at least one__ connected entry matching the condition.
* It also limits which connected entries are included with each main form entry, to just the ones that match.

For example, reading donors with `"relationship": -1` and this filter:

```json
"filter": [
  {"element": "artifacts_era", "value": "BCE", "operator": "!="}
]
```

returns every donor who gave at least one artifact that is not BCE, with only those artifacts included. A donor who gave both BCE and CE artifacts is still returned, with the BCE ones left out. A donor who gave no artifacts at all is not returned, because they have no artifact that matches.

So a condition like this cannot answer the question _which donors have not given any BCE artifacts?_ That is what a `none` group is for.

## Groups: none

A `none` group finds main form entries that have __no connected entry__ matching its conditions. This returns donors who have not given any BCE artifacts, including donors who have given no artifacts at all:

```json
"relationship": -1,
"filter": [
  {"none": [
    {"element": "artifacts_era", "value": "BCE", "operator": "="}
  ]}
]
```

Compare that with the `!=` example above. Given these donors:

| Donor | Artifacts |
|---|---|
| A | one BCE, one CE |
| B | two CE |
| C | none |

the `!=` condition returns A and B, and the `none` group returns B and C.

All the conditions in a `none` group have to be true of the __same__ connected entry. `{"none": [era = CE, short_name LIKE Coin]}` rules out donors who gave a CE coin. It does not rule out a donor who gave a CE artifact and, separately, some other coin. If you need two separate tests, use two `none` groups.

Rules for `none` groups:

* It needs a _relationship_ that connects the form to other forms.
* Every condition in it has to be on the same connected form. It cannot use elements from the main form, or metadata fields such as `creation_uid`, which always belong to the main form. Use a separate `none` group for each connected form.
* _andOr_ has to be `AND`. To combine a `none` group with alternatives, put the alternatives in an `any` group.
* It cannot contain other groups.
* An "is blank" test, `{BLANK}` with `=`, has to be the only condition in its `none` group. An "is not blank" test, `{BLANK}` with `!=`, can sit alongside other conditions.
