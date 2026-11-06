# Formulize Database Patterns & ORM

## Overview

Formulize uses a **three-layer ORM-like pattern** for data access, built on XOOPS/ImpressCMS foundations:

```
┌─────────────────┐
│  DATABASE       │  Physical MySQL tables
│  TABLES         │  Example: formulize_id, users, formulize
└────────┬────────┘
         │ Maps to
         ↓
┌─────────────────┐
│  OBJECT         │  PHP classes representing records
│  CLASSES        │  Example: formulizeForm, XoopsUser
│                 │  Base: XoopsObject / icms_core_Object
└────────┬────────┘
         │ Managed by
         ↓
┌─────────────────┐
│  HANDLER        │  Repository pattern for CRUD
│  CLASSES        │  Example: formulizeFormsHandler
│                 │  Core base: icms_core_ObjectHandler
│                 │  (Formulize handlers don't extend it)
└─────────────────┘
```

Form entries are the exception. They aren't objects, see [Formulize's Two-Table Pattern](#formulizes-two-table-pattern).

---

## XoopsObject Base Class

All entities extend from `XoopsObject` or `icms_core_Object`.

**Location:** `libraries/icms/core/Object.php`. `kernel/object.php` only holds the `XoopsObject` wrapper.

```
icms_core_Object (ImpressCMS base)
    ↓
XoopsObject (XOOPS compatibility wrapper)
    ↓
FormulizeObject (Formulize-specific extensions)
    ↓
Concrete Classes (formulizeForm, formulizeElement, etc.)
```

---

## Object Pattern: Property Definition

### Defining Properties with initVar()

In the object constructor, declare each property. Most map to database columns:

```php
class formulizeElement extends FormulizeObject {
    function __construct() {
        parent::__construct();

        // initVar($key, $data_type, $value, $required, $maxlength, $options)
        $this->initVar("ele_id", XOBJ_DTYPE_INT, NULL, false);
        $this->initVar("ele_type", XOBJ_DTYPE_TXTBOX, NULL, true, 100);
        $this->initVar("ele_caption", XOBJ_DTYPE_TXTAREA);
        $this->initVar("ele_handle", XOBJ_DTYPE_TXTBOX, NULL, false, 255);
        $this->initVar("ele_required", XOBJ_DTYPE_INT);
        $this->initVar("ele_value", XOBJ_DTYPE_ARRAY);  // Serialized
        $this->initVar("ele_encrypt", XOBJ_DTYPE_INT);
    }
}
```

### Data Type Constants

| Constant | Database Type | Use Case |
|----------|---------------|----------|
| `XOBJ_DTYPE_INT` | `INT` | Integer values |
| `XOBJ_DTYPE_TXTBOX` | `VARCHAR` | Short text (255 chars) |
| `XOBJ_DTYPE_TXTAREA` | `TEXT` | Long text |
| `XOBJ_DTYPE_ARRAY` | `TEXT` | PHP arrays (serialized) |
| `XOBJ_DTYPE_EMAIL` | `VARCHAR` | Email validation |
| `XOBJ_DTYPE_URL` | `VARCHAR` | URL validation |

---

## Getting and Setting Values

### getVar() - Reading Properties

```php
// Format parameter determines escaping/processing
$element->getVar('ele_caption');           // 's' = safe HTML (default)
$element->getVar('ele_caption', 'e');      // 'e' = edit mode (for forms)
$element->getVar('ele_caption', 'n');      // 'n'/'none' = unprocessed value, no escaping
$element->getVar('ele_id');                // numeric fields ignore format entirely
$element->getVar('ele_handle');            // plain text (no HTML-special chars possible), default format is fine

// Arrays are automatically unserialized (format has no effect on array-type fields)
$options = $element->getVar('ele_value');  // Returns PHP array
```

**Format Options** (see `icms_core_Object::getVar()` for the authoritative source):
- `'s'` / `'show'` - Safe (HTML escaped, or full textsanitizer processing for TXTAREA fields) - **DEFAULT**
- `'e'` / `'edit'` - Edit (for form inputs: HTML escaped, without the textsanitizer processing that `'s'` applies to TXTAREA fields)
- `'n'` / `'none'` - No processing. Use it when `htmlspecialchars` or textsanitizer processing would corrupt the value: reading a value back out to round-trip it through `setVar()`, or reading a value that could contain HTML-special characters and is going somewhere other than HTML output (a SQL string, a file name, a comparison).
- **Omitting the format parameter is fine for plain text values.** Handles, form/table names and other values that can't contain HTML-special characters are unchanged by the default escaping, so there's no need to pass `'n'` just to be safe.
- `'raw'` is **not** a real format value. It only behaves like `'n'` because it falls through an unmatched `switch` case. Don't use it.
- Numeric (`XOBJ_DTYPE_INT`) and array (`XOBJ_DTYPE_ARRAY`) fields ignore the format parameter entirely.

### setVar() - Writing Properties

```php
$element->setVar('ele_caption', 'User Input');
$element->setVar('ele_required', 1);

// Arrays automatically serialized on save
$options = array('Option 1', 'Option 2', 'Option 3');
$element->setVar('ele_value', $options);
```

**Important:** `setVar()` marks the property as changed and the object as "dirty". `assignVars()`, which handlers use to load a row, does not.

---

## Change Tracking

Objects track whether they've been modified, so handlers can skip unnecessary writes. This is what the elements handler's `insert()` does:

```php
// Nothing has changed, so there's nothing to write
if (!$element->isDirty()) {
    return true;
}

// Validate the values and put them in the $element->cleanVars property
// cleanVars() itself returns true or false
if (!$element->cleanVars()) {
    return false;
}

// INSERT if the element has no id yet, otherwise UPDATE
if ($element->isNew() || !$ele_id) {
    // INSERT
}
```

`isNew()` is only true after `setNew()` has been called, and the elements handler's `create()` doesn't call it, so a new element is recognised by having no `ele_id`. The UPDATE writes every column, not only the changed ones.

---

## Handler Pattern: Repository

Handlers manage all database operations for a specific object type.

### Getting Handlers

Handlers are cached per request, so call a getter wherever you need one. There's no need to cache the result yourself.

| Handler type | ICMS | Older XOOPS wrapper |
|---|---|---|
| Core | `icms::handler()` (static method) | `xoops_gethandler()` |
| Module | `icms_getModuleHandler()` (function) | `xoops_getmodulehandler()` |

```php
$member_handler = xoops_gethandler('member');
$member_handler = icms::handler('icms_member');
$form_handler = xoops_getmodulehandler('forms', 'formulize');
```

**The data handler is different:** create it with `new formulizeDataHandler($fid)`, once per form. It takes a form id, so it doesn't work through a getter.

### Handler Methods

The usual names are `create()`, `get()`, `getObjects()`, `getCount()`, `insert()` and `delete()`, but Formulize handlers don't share a base class, so check the handler you're using. For example, the forms handler has `getAllForms()`, and no `getObjects()` or `getCount()`.

This is the elements handler, in `modules/formulize/class/elements.php`:

```php
class formulizeElementsHandler {
    var $db;  // Database connection reference

    function __construct(&$db) {
        $this->db =& $db;  // Dependency injection
    }

    // Factory method
    function create() {
        return new formulizeElement();
    }

    // Read single, by id or handle. Results are cached for the request
    function get($idOrHandle, $bypassCache = false) { }

    // Read multiple (pass a form id as the second param to get that form's elements)
    function &getObjects($criteria = null, $id_form = 0, $id_as_key = false) { }

    // Create/Update
    function insert(&$element, $force = false) { }

    // Delete
    function delete($elementObject, $force = false) { }
}
```

### Writing a New Object and Handler

Formulize class files usually have the object at the top and the handler below it. The ICMS core keeps them in separate files.

`xoops_getmodulehandler('myEntity', 'formulize')` looks for `modules/formulize/class/myEntity.php`, or `myEntityHandler.php`, and a class called `formulizeMyEntityHandler`. It also accepts `mod_formulize_MyEntityHandler`.

```php
<?php

if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

class formulizeMyEntity extends FormulizeObject {

    function __construct() {
        parent::__construct();

        // Declare the properties
        $this->initVar('id', XOBJ_DTYPE_INT, NULL, false);
        $this->initVar('name', XOBJ_DTYPE_TXTBOX, '', true, 255);
        $this->initVar('description', XOBJ_DTYPE_TXTAREA);
        $this->initVar('options', XOBJ_DTYPE_ARRAY);
    }
}

class formulizeMyEntityHandler {
    var $db;
    var $table;

    function __construct(&$db) {
        $this->db =& $db;
        $this->table = $db->prefix('table_name');
    }

    function create() {
        return new formulizeMyEntity();
    }

    function get($id) {
        $id = intval($id);
        $sql = "SELECT * FROM {$this->table} WHERE id = $id";
        if (!$result = $this->db->query($sql)) {
            return false;
        }
        if (!$row = $this->db->fetchArray($result)) {
            return false;
        }
        $object = $this->create();
        $object->assignVars($row);
        return $object;
    }

    function insert(&$object, $force = false) {
        if (!$object->isDirty()) {
            return true;
        }
        if (!$object->cleanVars()) {
            return false;
        }
        // Each value is now in $object->cleanVars
        if ($object->isNew() || !$object->cleanVars['id']) {
            // INSERT logic
        } else {
            // UPDATE logic
        }
        return true;
    }

    function delete($object) {
        $id = is_object($object) ? $object->getVar('id') : intval($object);
        $sql = "DELETE FROM {$this->table} WHERE id = $id";
        return $this->db->query($sql);
    }
}
```

---

## CRUD Operations in Detail

### CREATE

An element is more than a row in the `formulize` table. It needs a column in the form's data table, and usually a place on the form's screens. `formulizeHandler::upsertElementSchemaAndResources()` does all of that, and it's how to create or update an element from code:

```php
$element = formulizeHandler::upsertElementSchemaAndResources(array(
    'fid'         => $form_id,
    'ele_type'    => 'text',
    'ele_caption' => 'Full Name',
    'ele_handle'  => 'full_name',
));
$new_id = $element->getVar('ele_id');
```

Pass `ele_id` in the array to update an existing element. It throws an exception if something goes wrong.

The lower-level steps it wraps are `$element_handler->create()`, `setVar()` and `$element_handler->insert()` to write the row, then `$form_handler->insertElementField($element, $dataType)` to add the column.

### READ

```php
$element_handler = xoops_getmodulehandler('elements', 'formulize');

// Fetch by ID or handle
$element = $element_handler->get($element_id);

if ($element) {
    echo $element->getVar('ele_caption');
}

// Get all elements for a form
$elements = $element_handler->getObjects(null, $form_id);

foreach ($elements as $element) {
    $type = $element->getVar('ele_type');
    $options = $element->getVar('ele_value');  // Auto-unserialized array
}
```

**SQL Executed,** unless the element is already cached:
```sql
SELECT * FROM {prefix}_formulize WHERE ele_id = 123
```

### UPDATE

```php
$element = $element_handler->get($element_id);

// Modify properties
$element->setVar('ele_caption', 'Updated Caption');
$element->setVar('ele_required', 1);

// Save (executes UPDATE because the element has an ele_id)
$element_handler->insert($element);
```

### DELETE

```php
$element = $element_handler->get($element_id);
$element_handler->delete($element);
```

The elements handler needs the object. `$form_handler->delete()` takes a form object or a form id.

---

## Database Table Mapping Examples

### User Object

**Database Table:** `users`

**Object Class:** `icms_member_user_Object`, in `libraries/icms/member/user/Object.php`. `XoopsUser` is the older wrapper around it.

```php
class icms_member_user_Object extends icms_core_Object {
    public function __construct($id = null) {
        $this->initVar('uid', XOBJ_DTYPE_INT, null, false);
        $this->initVar('uname', XOBJ_DTYPE_TXTBOX, null, true, 255);
        $this->initVar('email', XOBJ_DTYPE_TXTBOX, null, false, 60);
        $this->initVar('pass', XOBJ_DTYPE_TXTBOX, null, false, 255);
        $this->initVar('level', XOBJ_DTYPE_TXTBOX, 0, false);
    }

    // True if the user's groups have the module_admin permission for the module
    public function isAdmin($module_id = null) { }
}
```

```php
$member_handler = xoops_gethandler('member');
$user = $member_handler->getUser($uid);
echo $user->getVar('uname');
```

### Form Object (Formulize)

**Database Table:** `formulize_id`

**Object Class:** `formulizeForm`, in `modules/formulize/class/forms.php`

```php
class formulizeForm extends FormulizeObject {
    function __construct($form_id_or_handle="", $includeAllElements=false) {
        // Reads its own row from formulize_id into $formq, and gathers its elements, views, etc.
        parent::__construct();
        $this->initVar("id_form", XOBJ_DTYPE_INT, $formq[0]['id_form'], true);
        $this->initVar("form_title", XOBJ_DTYPE_TXTBOX, $formq[0]['form_title'], true, 255);
        $this->initVar("form_handle", XOBJ_DTYPE_TXTBOX, $formq[0]['form_handle'], false, 255);
        $this->initVar("note", XOBJ_DTYPE_TXTAREA, $formq[0]['note']);
        $this->initVar("elements", XOBJ_DTYPE_ARRAY, serialize($elements)); // Not a column
    }
}
```

Unlike elements, form objects load themselves in the constructor, and many of their properties are gathered from other tables rather than mapping to a column in `formulize_id`.

```php
$form_handler = xoops_getmodulehandler('forms', 'formulize');
$form = $form_handler->get($form_id);
$forms = $form_handler->getAllForms();
```

`getAllForms(true)` includes all elements, even those shown to no groups. Get the element ids with `$form->getVar('elements')`.

---

## Formulize's Two-Table Pattern

Formulize uses a **metadata + data** dual-table system:

### Metadata Tables (Static Structure)

**1. Form Definitions:** `formulize_id`
- One row per form
- Fields: `id_form`, `form_title`, `form_handle`, `singleentry` (entry mode, exposed on the form object as `single`), etc.

**2. Element Definitions:** `formulize`
- Multiple rows per form
- Fields: `ele_id`, `id_form`, `ele_caption`, `ele_handle`, `ele_type`, `ele_value` (serialized config), `ele_required`, etc.

**3. Screen Definitions:** `formulize_screen`, plus a table for each screen type
- Multiple screens per form
- Base table: `formulize_screen` (`sid`, `title`, `fid`, `frid`, `type`)
- Type tables hold the settings for that type, such as `formulize_screen_listofentries` and `formulize_screen_multipage`

### Data Tables (Dynamic Structure)

**Form Entry Data:** `formulize_<handle>`

Example: When you create a form with handle "students", Formulize creates:

```sql
CREATE TABLE {prefix}_formulize_students (
    entry_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    creation_datetime DATETIME NULL,
    mod_datetime DATETIME NULL,
    creation_uid INT DEFAULT 0,
    mod_uid INT DEFAULT 0,

    -- Dynamic columns, named after the element handles
    first_name TEXT,
    last_name TEXT,
    email TEXT,
    ...

    PRIMARY KEY (entry_id),
    INDEX i_creation_uid (creation_uid)
);
```

**Important:** Data tables use **arrays**, not objects, because they're too dynamic for the object pattern.

### Reading Entries

For whole entries and lists of entries, use `gatherDataset()`. See [Data Extraction](formulize-module-overview.md#data-extraction-extractphp). For a single value, or a lookup, use the data handler. See [docs/data_handler.md](../docs/data_handler.md) for its methods.

```php
$data_handler = new formulizeDataHandler($form_id);

$first_name = $data_handler->getElementValueInEntry($entry_id, 'first_name');
$meta = $data_handler->getEntryMeta($entry_id);
$entries = $data_handler->findAllEntriesWithValue('profile_name', 'John Smith');
```

### Writing Entries

Use `formulize_writeEntry()`, in `include/functions.php`. It calls the data handler's `writeEntry()`, and for a new entry it also records the groups that own it, which the data handler alone does not.

```php
// Keys are element handles or ids, in a single form. Values are database-ready, see prepDataForWrite()
$values = array(
    'first_name' => $first_name,
    'last_name' => $last_name,
);

$entry_id = formulize_writeEntry($values, 'new');   // or an existing entry id
formulize_updateDerivedValues($entry_id, $fid);     // Writing doesn't update derived values
```

- Writing only happens on POST requests, unless you pass `$forceUpdate`.
- It returns nothing if the values match what's already stored.
- The form's before and after save code runs inside `writeEntry()`.
- It doesn't check permissions, so check them first when you're acting for a user.

### A Missing Data Field Is Serious

Every element with data has a field in its form's data table, and in its revisions table if there is one. If a field is missing, Formulize must say so. `updateField()` in `class/forms.php` throws on purpose. Don't fix a failure like that by skipping the table. A missing field means something is already broken, and carrying on hides it. The throw has to come before anything is committed, so a rename doesn't leave the handle half changed.

---

## Direct Database Access (When Needed)

For complex queries or performance optimization:

```php
global $xoopsDB;

// Prefix table names
$table = $xoopsDB->prefix('formulize_id');

// Execute query
$sql = "SELECT * FROM $table WHERE id_form = " . intval($fid);
$result = $xoopsDB->query($sql);

// Fetch results
while ($row = $xoopsDB->fetchArray($result)) {
    // Process row
}
```

**Prefer handlers over direct SQL** for security and consistency. See [escaping](escaping.md) for how to escape values, including in a Criteria.
