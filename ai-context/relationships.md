# Relationships

A relationship describes how forms connect, **from the point of view of an entry in a form**: what one entry in this form can have in the other form. It is not a SQL join definition. "Each Donor has many Artifacts" and "each Artifact has one Donor" are the same connection, seen from each side.

That is why a connection is only ever **one to one**, **one to many** or **many to one**. There is no many-to-many option and no junction table to look for. From any single entry, the other side is either one entry or many.

The admin UI says "relationships", and the code often says "frameworks": `class/frameworks.php`, the `frid` parameters, and the `formulize_frameworks` and `formulize_framework_links` tables. They are the same thing.

## Linked elements drive connections

A linked element takes its options from the entries in another form, and that is the fundamental source of connections. Making an element linked adds a connection to the Primary Relationship, with the linked element's form as the many side and the source form as the one side (`updateLinkedElementConnectionsInRelationships()`).

What the connection can do depends on the element:

| Element | One entry can link to | Many entries can link to the same source entry? |
|---|---|---|
| Linked dropdown list or autocomplete box | One source entry | Yes |
| Linked checkbox series or multi-autocomplete box | Several source entries | Yes |

Linking several entries like this is a many-to-many in conventional terms. In Formulize it still works as one to many or many to one, depending on which form you're looking from.

A connection can also be based on two elements in different forms holding the same value, with no linked element involved (`fl_common_value`).

## One to one connections

One to one connections often have a deep effect on how forms and elements behave when they're displayed together. They can keep entries in the two forms connected, or display a different entry from one form based on a value entered in the other. Each link has two settings for this:

| Setting | What it does |
|---|---|
| `one2one_bookkeeping` | When someone saves an entry in one form, create a matching entry in the other form if there isn't one |
| `one2one_conditional` | When the forms are shown together and the linking value changes, change the entry in the other form to match |

## Settings on any link

- **`unified_display`:** forms can be shown together. `getUnifiedDisplayFids($fid)` works out which: the form itself, the forms it's one to one with, and the "one" side of any connection where it's the "many" side.
- **`unified_delete`:** deleting an entry in one form also deletes the linked entries in the other form.

`fl_relationship` stores the type: `1` is one to one, `2` is one to many with form 1 as the one side, and `3` is one to many with form 2 as the one side. Because the same connection can be stored either way round, use the `frameworks.php` methods rather than reading the codes yourself.

## The Primary Relationship

The Primary Relationship is relationship `-1`. Introduced in Formulize 8, it is an enduring catalogue of all the connections in the system, so users don't have to manage relationships manually. It is the default for all new screens, and it's updated automatically as connections are created between forms, through linked elements, subform elements and so on.

Don't write code or instructions that expect someone to build a relationship by hand. Other relationships are narrower sets of connections, and a `frid` of `0` means no relationship, only the form itself.

## Getting data through a relationship

Data follows the same point of view. `gatherDataset()` returns one item for each entry in the main form, and each item includes that entry's connected entries from forms directly connected to the main form. Forms further away are not included. See [gatherDataset](../docs/function-gatherDataset.md), and [Conditions on connected forms](../docs/filters.md#conditions-on-connected-forms) for how a filter behaves across them.

For people building applications, see [How forms connect](../docs/relationships.md).
