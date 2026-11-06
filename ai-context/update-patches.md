# Update patches

A new site gets its database from the install files. An existing site gets the same changes from the module update. A change to the database has to reach both, and the module's `dbversion` is what tells an existing site it has work to do.

## What each kind of change needs

| Change | What to do |
|---|---|
| New module setting | Declare it in `$modversion['config'][]` in `modules/formulize/xoops_version.php`, add its words to `language/english/modinfo.php`, list it in `include/configsettings_registry.php` to place it on an admin settings page, and raise `dbversion`. That's all: the module update creates the setting on existing sites. Don't write a patch that inserts into the `config` table, and don't copy `010_ai_admin_settings.php`, which does. |
| New column or table | Add it to `modules/formulize/sql/mysql.sql` for new sites, write a patch for existing ones, raise `dbversion`, and update the object and handler that use it. |
| Changing existing data | Write a patch, and raise `dbversion`. |
| A core ICMS preference | Change `install/makedata.php` and `install/sql/pdo.mysql.formulize_standalone.sql` for new sites, and write a patch for existing ones. `009_purifier_allow_hr.php` is an example. |
| Something that must happen on every update | Add it to `002_always_run.php`, which clears compiled templates and caches, among other things. |

`dbversion` is declared at the top of `xoops_version.php` as a plain number. Raise it by one for each change that needs it.

## Writing a patch

Add a file to `modules/formulize/admin/patches/`, with the next number. Files are found automatically. The function name must be `formulize_patch_` plus the file name without `.php`. If it isn't, the update skips the file without saying so.

```php
<?php
if (!defined('XOOPS_ROOT_PATH')) {
    exit();
}

// Say what this changes and why, and why a new site doesn't need it.
function formulize_patch_016_my_change($prev_dbversion, $required_dbversion) {
    global $xoopsDB;

    if ($prev_dbversion >= 27) {
        return true; // already applied
    }

    $table = $xoopsDB->prefix('formulize_screen_listofentries');

    $existing = $xoopsDB->queryF("SHOW COLUMNS FROM $table LIKE 'my_column'");
    if ($existing AND $xoopsDB->getRowsNum($existing) > 0) {
        echo '<p>My column already added. result: OK</p>';
        return true;
    }

    if (!$xoopsDB->queryF("ALTER TABLE $table ADD `my_column` varchar(10) NOT NULL default ''")) {
        echo '<p>016_my_change: could not add the column: ' . htmlspecialchars($xoopsDB->error()) . '</p>';
        return false;
    }

    echo '<p>Added my column.</p>';
    return true;
}
```

- **Gate on the `dbversion` that introduces the patch.** The gate is the number you're raising `dbversion` to.
- **Return `false` when something fails.** The update stops there, `dbversion` doesn't move, and the person can fix the problem and run it again. Anything else counts as success.
- **Make it safe to run twice.** After a failure, every patch before the failed one runs again, because the stored `dbversion` hasn't changed. A site can also arrive with the change already made, so check first, as the example does.
- **Patches run in file name order,** after all of them have been included. A later patch can rely on an earlier one.

`include/on_update.php` runs all of this. Its comments are the reference.

`000_schema_migrations.php` is closed to new work. It brings a site up to `dbversion` 18 and is skipped on sites already there.

A new site gets the change from `sql/mysql.sql`, so a passing test suite doesn't show that the patch works. Try it against a copy of a database from before the change.

## What forces the update page

`admin/ui.php` sends admins to the update page when the stored `dbversion` is behind, and also when `primaryRelationshipExists()` is false, `need81ElementTypeConversion()` is true, or there's code still to convert. That second group has no version check, so it can look as though gating `000` strands those repairs. It doesn't. They all date from before 8.1, so any site where they're true is below 18 anyway.

## When a patch can't simply be run again

`001_derived_value_formula_migration.php` rewrites code files, and running it on files it has already rewritten corrupts them. It runs whenever the stored `dbversion` is below 2. `dbversion` lives in the database and the migrated code lives in files, so the two can disagree when a database and a code folder come from different sides of that migration. If the code is already migrated, use **Set Database Version** before updating, so that the patch is skipped. If it has run by mistake, restore the code files, then [recompute the derived values](derived-values-and-custom-code.md#recomputing) that were calculated while they were wrong.

Two admin tools exist for updates that go wrong:

- `ui.php?op=patchDB-only` runs the patches without updating templates or settings. Adding `&f=NNN` limits it to one patch file, for when a full run times out, and `&start=<file name>` resumes a patch that loops over files. A filtered run never raises `dbversion`.
- `ui.php?op=setDBVersion` sets the number directly and runs nothing. Setting it too high skips patches for good.
