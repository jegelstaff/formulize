# The webmaster permission bypass

`icms_member_groupperm_Handler::checkRight($gperm_name, $gperm_itemid, $gperm_groupid, $gperm_modid = 1, $webmasterAlwaysTrue=true)` (libraries/icms/member/groupperm/Handler.php) short-circuits to `return true` **only when `ICMS_GROUP_ADMIN` (the webmaster group, id 1) is among the `$gperm_groupid`(s) being tested.**

Consequences:
- **"Does this user have permission X?"** — the caller passes the user's group list; if the user is a webmaster, group 1 is in the list → bypass → always true. **Webmasters always pass these checks** even when no grant row exists. Same for a check aimed directly at group 1.
- **"Which groups have permission X?" / a specific NON-webmaster group's permission** — enumeration helpers (e.g. `getGroupIds`, `getItemIds`) and any `checkRight` on a group other than 1 do a **plain DB lookup with no webmaster bypass**. So the webmaster group only shows up in those results / passes those checks if the permission was **actually granted** to it. Don't assume webmasters implicitly hold a permission for the purposes of enumeration or per-group checks.

For example, the system groups admin page gates each row on `checkRight('group_manager', $gid, $ugroups)`, which passes for a webmaster because their group list contains group 1, so it lists every group.

## Deleting a form's permissions

Don't use `deleteByModule()` for this. `icms_member_groupperm_Handler::deleteByModule($gperm_modid, $gperm_name = null, $gperm_itemid = null)` only uses the item id when `$gperm_name` is set too, so calling it with no name and an item id **deletes every permission in the module.** Build the criteria yourself:

```php
$criteria = new CriteriaCompo(new Criteria('gperm_modid', getFormulizeModId()));
$criteria->add(new Criteria('gperm_itemid', intval($fid)));
$gperm_handler->deleteAll($criteria);
```

Every Formulize permission is keyed by form id, so that's one form's set. `formulize_groupscope_settings` and `formulize_group_filters` have no handler, and go by fid with direct SQL. See `formulizeFormsHandler::delete()`.
