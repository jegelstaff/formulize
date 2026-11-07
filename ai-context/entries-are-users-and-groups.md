# Entries-are-users and entries-are-groups

These two form settings let the data in an application manage its own users, groups and permissions.

Each one sounds simple: make an entry, and it makes a user account or a group. The value is in the complex interactions that are possible when you connect such forms together, and use them to organize users and permissions dynamically. An application can then need little to no backend maintenance over time. To add a department, someone creates an entry in the Departments form, and it works within the configuration the application already has. Nothing else has to be set up.

## Entries-are-users forms

These give end users an easy way to manage user accounts. Staff often use an application to create records of volunteers, students, or whatever kinds of people a system manages. With a Volunteer profile form set as entries-are-users, each new entry creates a user account, with no backend administration.

Conditions and rules decide which entries actually create accounts, and which groups the new accounts belong to.

## Entries-are-groups forms

These make the group structure and permissions respond to changes in the data. If information in an application is divided by project, then with a Projects form set as entries-are-groups, each new project entry creates groups.

One entry can create several groups, because it might need distinct groups for Project - Managers, Project - Staff and Project - Customers. These are the form's **categories**. There is always an All Users category.

## Template groups

Template groups are the abstract groups that belong to the entries-are-groups form itself, one for each category: Projects All Users, Projects Staff, Projects Volunteers and so on. **Permissions are assigned to template groups.**

When an entry is created, it gets its own group for each category. A project called Ravine Cleanup gets Ravine Cleanup - All Users, Ravine Cleanup - Staff and Ravine Cleanup - Volunteers. Each of those automatically gets a copy of the corresponding template group's permissions. When a template group's permissions change, its entry groups get the change automatically.

So an entry group's permissions come from its template group, and entry groups are left out of the default UI. Someone who really needs to can set an override on one, from the template group's permission panel. Don't set permissions on entry groups one at a time.

## Connecting the two

If an entries-are-users form has a linked element whose source is an entries-are-groups form, the groups a new user belongs to can be tied to the chosen group.

For example, a Volunteers form has a linked dropdown list pointing at the Projects form, and Projects has a Volunteers category. When a new volunteer account is created through the Volunteers form and a project is selected, the volunteer automatically joins the Volunteers group for that specific project.

Group memberships declared for the users in an entries-are-users form are always enforced, and always subject to any conditions attached to them. For example, users get the Project - Managers group, but only if the "Is a Manager" question is "Yes". See [Enforced memberships](#enforced-memberships). For how forms connect, see [relationships](relationships.md).

For people building applications, see [Forms that create users and groups](../docs/entries_are_users_and_groups.md).

## How it's wired

### Groups

- Template groups have `is_group_template=1` in the `groups` table. They store `form_id`, the entries-are-groups form, but not `entry_id`.
- Entry groups have `is_group_template=0`, plus `form_id` and `entry_id`. They're named `"{PI value} - {CategoryName}"`.
- A form's `group_categories` setting maps group ids to category names.
- `formulizeHandler::syncEntryGroups($fid, $entryId)` creates an entry's groups.
- `formulizeHandler::getAllGroupsForTemplateCategory($templateGroupId)` finds all the entry groups in a template group's category.
- `formulizeHandler::resolveDefaultGroupId()` resolves a template group to the actual entry group or groups.
- `formulizeHandler::getTemplateToEntryGroupMap($templateGroupId = null)` returns the map of template groups to entry groups, or one template group's entry groups. It's cached per request.

### User accounts and memberships

- Conditions are stored as a multidimensional array: key `0` holds the base conditions, and key `{group_id}` holds the conditions for that group. See [conditions](conditions.md) for their format.
- `entries_are_users_default_groups_element_links` maps `templateGroupId => [eleId1, eleId2]`, for resolving template groups to actual entry groups.
- The admin UI is in `form_settings.html`, with a conditions panel for each group.
- `userAccountElement.php::processUserAccountSubmission()` creates and updates the user object. Group memberships are handled separately.
- Group memberships are applied at the end of `readelements.php`, after derived values, by `formulizeHandler::processEauGroupMembershipsForWrittenEntries()`. It covers an entries-are-users form being saved, and a connected form being saved when it holds an element named in `entries_are_users_default_groups_element_links`. For each user it calls `formulizeElementsHandler::processUserGroupMemberships($userId, $formId, $entryId)`, which delegates to `formulizeUserAccountGroupMembershipElementHandler`, and then to `GroupMembershipService::processUserGroupMemberships()`.

### The name columns in the users table

The column names don't mean what they say:

- **`uname` is the person's full name**, ie: Curator One. An entries-are-users form writes it from the First name and Last name elements, with a space between. It's what Formulize shows for a user, including in a list of users.
- **`login_name` is what they log in with**, ie: curator1. The Username element writes it.
- **`name` is often empty.** Code that reads it falls back to `uname`, ie: `parseUserAndToday()`.

A list of users stores the uid and always shows `uname`, since `selectElement` enforces `{USERNAMES}` as its only option. So code that turns typed text into a user, like `selectElement::prepareLiteralTextForDB()`, looks in `uname` for the full name and `login_name` for the login.

### Enforced memberships

Members whose membership is enforced can't be removed. On save, `GroupMembershipService::filterMandatoryMemberships()` puts them back, so the group member management UI on `groups.php` (the `eagGroupMembers` element) must not offer a Remove button for them.

`queryMembers()` sets `protected` from `isGroupMandatoryForUser($uid, $groupId)`, and **both** render paths must respect it: `renderSingleWidget()` on the server, and `gmmSearchMembers` in the JavaScript. Don't remove that guard to make a test's Remove click work. Fix the test. See [Which memberships can be removed](testing-eau-eag.md#which-memberships-can-be-removed).

A membership is mandatory if all of these hold: the group, or its template category, is in some entries-are-users form's `entries_are_users_default_groups`, the user has an entry in that form meeting the base conditions, and any condition on that group holds. **A default group with no condition of its own is mandatory for all its members.**

Only required membership is enforced. Nothing prevents someone being added to a group.

### Copying permissions from template groups

- `formulizePermHandler::copyGroupPermissions()` copies `group_permission`, `formulize_group_filters` and groupscope settings from one group to another, with a DELETE then INSERT...SELECT, so it can be re-run. An optional `$modid` limits it to one module, and an optional `$groupIdMapping` resolves groupscope targets. Template group references in groupscope settings are replaced with the corresponding entry group ids.
- `formulizePermHandler::synchronizeGroupReferencesInElement(&$element, $groupMap)` is a general two-way sync. Given a map of `sourceId => [targetIds]`, it adds the targets where the source is present, and removes them where it's absent. Template sync and `managepermissions.php` both use it.
- `formulizeHandler::copyAllTemplateSettingsToEntryGroups($fid, $templateToEntryGroupMap, $newGroupIds)` does the full copy: `copyGroupPermissions()` for each template and entry group pair, then `synchronizeGroupReferencesInElement()` for all elements. `syncEntryGroups()` calls it after creating new entry groups.
- `formulizeHandler::propagateTemplateGroupPermissions()` passes on changes. `form_permissions_save.php` and `application_menu_entries_save.php` call it after permission changes to template groups.
- Saving a single element also syncs its group references: `element_display_save.php` and `element_options_save.php` call `formulizeHandler::upsertElementSchemaAndResources()`.

### Deleting a user

Delete means delete. `modules/formulize/users.php` removes the `users` row and the `groups_users_link` rows. `$member_handler->deleteUser()` in ICMS only sets `level` to -1 and scrambles the password, so the name and email stay. Those accounts didn't show in the UI but still collided on email and username. Anything that deletes a user should do what `users.php` does, and never call `deleteUser()` on its own. To keep someone's record, make the account inactive.
