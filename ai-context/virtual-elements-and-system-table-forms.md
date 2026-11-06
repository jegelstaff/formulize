# Virtual elements and the system table forms

`users.php` and `groups.php` draw their pages with Formulize forms built over the `users` and `groups` tables, the System Users and System Groups forms. `ensureUsersTableForm()` and `ensureGroupsTableForm()` in `usersAndGroups.php` create and sync those forms on each page load. They use `adHocTableFormTrait.php`.

## Virtual elements

Virtual elements are columns with no backing database field, whose values are injected after the query. All of them extend `formulizeVirtualElement` and `formulizeVirtualElementHandler`, in `class/virtualElement.php`.

The base class sets:

- `$isVirtualElement = true`, which `admin/element.php` uses to block creating one by hand. It calls `die()` with an inline error, because `ui.php` has already sent headers, so `redirect_header()` won't work
- `$isSystemElement = true`, which hides the Options and Advanced admin tabs
- `$hasData = false`, `$needsDataType = false`

Handlers have `prepareDataForDataset($value, $handle, $entry_id)`. The values are already injected, so it just returns `$value`.

| Class | Column | Page | Shows | Injected by |
|---|---|---|---|---|
| `eagGroupCategoriesElement.php` | `group_categories` | `groups.php` | Categories of template groups | `injectGroupCategoriesData()` |
| `eagGroupTypeElement.php` | `eag_type` | `groups.php` | "Regular" for plain groups, "Form-based" for template groups | `injectGroupTypeData()` |
| `eagGroupMembersElement.php` | `group_members` | `groups.php` | Up to 15 member names, then a total. Template groups have no direct members, so they show nothing here | `injectGroupMembersData()` |
| `userEauTypeElement.php` | `eau_type` | `users.php` | The singular name of the user's entries-are-users form | `injectUserEauTypeData()` |

The injection functions are in `usersAndGroups.php`.

### How search on a virtual column works

1. `dataExtractionTableForm()` in `extract.php` builds an `$elementsByHandle` map. Virtual elements, where `ele_value['virtual']` is set, are included with `field = null` and `type = ele_type`.
2. When `parseTableFormFilter()` sees `field === null` and a `type`, it calls `formulize_tryDelegatedSearchWhere($type, ...)` instead of comparing a column.
3. That function loads `{type}Element.php` and calls `buildSearchWhereClause()` on its handler.
4. LIKE operators need spaces added before being passed to `buildSearchWhereClause()`. The table form path doesn't add them in advance the way `formulize_parseFilter()` does.

`buildSearchWhereClause()` usually uses a correlated `EXISTS` subquery. `userEauType` can't, because it would need dynamic table names, so it searches each entries-are-users form's table in PHP and returns `main.uid IN (...)`.

### Registering one

The `extraElements` entries in `ensureGroupsTableForm()` must include the `'type'`, such as `'type' => 'eagGroupCategories'`, because `dataExtractionTableForm()` uses it to know which handler to delegate search to. `adHocTableFormTrait.php` reads `$extra['type'] ?? 'text'`, and stores the right `ele_type` on create and sync.

## Which columns show

- **`groups.php`** uses a hardcoded `$advanceViewArray` on its pseudo screen object. The form's `headerlist` and `defaultColumns` don't drive the columns here, so add a new column to `$advanceViewArray`.
- **`users.php`** goes through `dataExtraction()`, not `dataExtractionTableForm()`, and the form's `headerlist` and `defaultColumns` in `ensureUsersTableForm()` do apply.

## Editing groups as form entries

`class/groupTableElement.php` holds `formulizeGroupTableElement` and its handler. Its subclasses are `groupNameElement.php` (`groupProperty = 'name'`) and `groupDescriptionElement.php` (`groupProperty = 'description'`).

- `$isGroupTableElement = true` makes `readelements.php` call `formulizeElementsHandler::processGroupSubmission()`, which delegates to this handler. Nothing is written to a form data table. It updates the `groups` table directly, or creates a group when `$entryId` is `'new'`.
- `$groupProperty` is the `groups` table column. `loadValue()` reads it from `groups` where `groupid = $entry_id`.
- Handles are `formulize_group_{lowercasetype}_{fid}`, such as `formulize_group_name_5`. `_adHocCanonicalHandle()` handles `group*` types, and `_adHocElementName()` finds the element's name, from a language constant or its `->name` property.
- `ensureGroupsTableForm()` uses `columnTypes` to give the `name` and `description` columns these types.
- `formulizeForm::isSystemGroupsTableForm()` identifies the form. `formulizeFormsHandler::getGroupTableElementTypes()` lists the types, in order.
- `groups.php` creates and syncs a form screen on the form's `defaultform` when the page loads, holding only the name and description elements.
