# Formulize: notes for AI coding agents

Formulize is a platform for creating data-driven applications. It is written primarily in PHP. It runs as a module (`modules/formulize/`) inside a fork of the ImpressCMS/XOOPS CMS, which lives in the same repository (`libraries/icms/`, `kernel/`, `class/`, `include/`). The database is MariaDB, but it is also compatible with MySQL. The core ICMS system uses Smarty for templating. Formulize's screen templates are plain PHP.

This file holds the rules that apply to almost every change. Background on specific parts of the system is in [`ai-context/`](ai-context/). Unless a change is small and self-contained, start with [architecture-overview](ai-context/architecture-overview.md) for how the whole system fits together, then [formulize-module-overview](ai-context/formulize-module-overview.md) for how it behaves. Read the relevant file before working in any area. The developer documentation in [`docs/`](docs/) covers more, and [`docs/developer.html`](docs/developer.html) lists its pages.

The files in `ai-context/` are a general guide and may not match every case. Check them against the code when implementing features. When you change them, keep each topic in one file and link to it from the others, so copies can't drift apart.

## Rules that apply everywhere

- **Formulize has no controllers, routes or form handlers.** Every page submits back to itself, `readelements.php` saves whatever was submitted, and the display functions do their own controlling. Don't go looking for a route, and don't write a save handler for a form that's on a page. See [formulize-module-overview](ai-context/formulize-module-overview.md).
- **The typical path is from `initialize.php` to a screen's `render()` method,** which hands off to `displayFormPages()`, `displayEntries()` and the other display functions. Read those functions to understand how Formulize works, but don't call them from new code. Go through a screen, which can be one you build in code. See [Rendering a screen in code](ai-context/formulize-module-overview.md#rendering-a-screen-in-code).
- **Get form data through `gatherDataset()`.** It's the universal data layer. **Scope is critical:** an empty scope means no limit on which entries come back. When the data is going to a user, read [how to set the scope](ai-context/formulize-module-overview.md#data-extraction-extractphp) first.
- **The ICMS/XOOPS core is ours.** It is a Formulize fork, not a vendored dependency. Edit it directly. Don't wrap calls to Formulize functions in `function_exists()` guards. See [icms-core-is-ours-to-edit](ai-context/icms-core-is-ours-to-edit.md).
- **Formulize module functions are available on every page.** Core `include/common.php` loads the module bootstrap before `header.php` runs. See [bootstrap-include-order](ai-context/bootstrap-include-order.md).
- **Criteria objects do not escape values.** `icms_db_criteria_Item::render()` inserts values into SQL as-is. Escape anything from user input before it goes into a Criteria, with `formulize_db_escape()` in module code or with `global $xoopsDB` in core code. See [escaping](ai-context/escaping.md).
- **HTML escaping:** write `htmlspecialchars($v)` without `ENT_QUOTES`. PHP 8.1+ escapes single quotes by default. See [escaping](ai-context/escaping.md#html).
- **Whatever people typed has to be made safe before it's shown.** Escape plain values. Purify values that are meant to be HTML, and anything an author's markup has data mixed into. See [xss-and-purification](ai-context/xss-and-purification.md).
- **Every word Formulize shows comes from a language file.** That includes words in scripts. Add a constant to the right file in `modules/formulize/language/english/`, and don't write the words into PHP, templates or JavaScript. See [Translating text](docs/translating_text.md).
- **A change to the database has to reach existing sites as well as new ones.** It needs `sql/mysql.sql`, a patch in `admin/patches/` and a higher `dbversion`. A new module setting needs only its declaration and a higher `dbversion`. See [update-patches](ai-context/update-patches.md).
- **The admin UI runs jQuery 1.4.2 and jQuery UI 1.8.2.** There is no `.on()`, `.prop()`, or deferreds, and `.data('x')` does not read `data-x` attributes. See the jQuery section of [coding-guidelines](ai-context/coding-guidelines.md#javascript-in-admin-templates).
- **The user-facing pages run jQuery 1.12.4 and jQuery UI 1.11.4.**
- **New Smarty templates must be registered** in `modules/formulize/xoops_version.php`. PHP screen templates are not. See [Template Registration](ai-context/formulize-module-overview.md#template-registration).
- **Don't put a closing `?>`** at the end of PHP files.
- **Webmasters bypass "does this user have permission X" checks**, but not "which groups have permission X" lookups. See [webmaster-permission-bypass](ai-context/webmaster-permission-bypass.md).
- **`docs/` is the public formulize.org website** (Jekyll). Write it for the people using Formulize. See [writing-user-docs](ai-context/writing-user-docs.md) and [docs-site-verification](ai-context/docs-site-verification.md).
- **Test Formulize by using it.** Set up, act and tidy up through the admin UI and screens, the way a person would. Don't write to the database or disk to get a state, even to check a change by hand. The PHP tests in `tests/` cover internal pieces and don't replace this. See [testing-through-the-application](ai-context/testing-through-the-application.md).
- **Run end-to-end tests from `tests/e2e/`** with `npm test --`, not `npx playwright` from the repo root. See [local-environment-and-testing](ai-context/local-environment-and-testing.md).
- **Don't build on one theme's internals.** Anari was introduced for Formulize 7. Lyris was introduced with Formulize 8.30 and is intended as the standard theme into the future; use Formulize-level contracts. See [themes-anari-and-lyris](ai-context/themes-anari-and-lyris.md).
- **Front-facing markup and styling follow Formulize UI.** Read its reference before writing HTML or CSS for anything users see, use only the `fz-` classes and tokens it lists, and say in your report that you followed it. See [formulize-ui](ai-context/formulize-ui.md).

## How to work safely

See [agent-conduct](ai-context/agent-conduct.md) for these rules in full.

- To check whether a guard holds, test that the action is refused. Don't run the destructive operation and rely on restoring afterwards.
- Test auth, 2FA and permission flows against a disposable user, never a real account.
- Don't claim "the only caller" or "never runs" based on a search that excluded some results.
- Answer Docker, Compose and CI questions by running a throwaway reproduction, not from memory.
- If an MCP server's tools aren't loaded, ask the person to reconnect it. Don't script calls to the system yourself.
- Don't read files that hold live credentials, such as `trust/` files, an installed site's `mainfile.php`, or `.env`. Filter out the secrets if you need something from them.

## Background by area

| Working on | Read |
|---|---|
| Overall architecture, file layout, request flow | [architecture-overview](ai-context/architecture-overview.md), [docs/code_flow.md](docs/code_flow.md) |
| How pages save and draw, screens, permissions | [formulize-module-overview](ai-context/formulize-module-overview.md) |
| Handlers, objects, `getVar` formats, writing entries | [database-patterns](ai-context/database-patterns.md), [docs/functions.md](docs/functions.md) |
| Schema changes, new settings, the module update | [update-patches](ai-context/update-patches.md) |
| Derived values, form procedures, code in elements | [derived-values-and-custom-code](ai-context/derived-values-and-custom-code.md) |
| Code style, admin pages and saves, debugging, version control | [coding-guidelines](ai-context/coding-guidelines.md) |
| Relationships between forms, linked elements | [relationships](ai-context/relationships.md) |
| Entries-are-users, entries-are-groups, template groups, enforced memberships | [entries-are-users-and-groups](ai-context/entries-are-users-and-groups.md) |
| Conditions and the filter UI | [conditions](ai-context/conditions.md) |
| Showing data: escaping, purification, XSS | [xss-and-purification](ai-context/xss-and-purification.md), [escaping](ai-context/escaping.md) |
| Security tokens and CSRF | [security-tokens](ai-context/security-tokens.md) |
| Group permissions, deleting a form's permissions | [webmaster-permission-bypass](ai-context/webmaster-permission-bypass.md) |
| Anything that refers to an element | [element-references](ai-context/element-references.md) |
| A new element type | [docs/custom_form_elements.md](docs/custom_form_elements.md) |
| Users/groups pages, virtual elements | [virtual-elements-and-system-table-forms](ai-context/virtual-elements-and-system-table-forms.md) |
| Admin save errors, admin page views | [admin-save-error-masking](ai-context/admin-save-error-masking.md), [admin-page-views-permission-return](ai-context/admin-page-views-permission-return.md) |
| Passwords, 2FA, account editing | [password-hashing](ai-context/password-hashing.md), [2fa-account-edit-phases](ai-context/2fa-account-edit-phases.md) |
| Anonymous access, signup | [anonymous-entry-access](ai-context/anonymous-entry-access.md), [self-registration-signup](ai-context/self-registration-signup.md) |
| The right slide-out drawer | [drawer](ai-context/drawer.md) |
| Embedding screens in other sites | [embedding-screens](ai-context/embedding-screens.md) |
| The installable app and service worker | [installable-app](ai-context/installable-app.md) |
| Lock files, temp files, caching | [filesystem-dependencies](ai-context/filesystem-dependencies.md) |
| Changing the Public API or the MCP server | [mcp-and-public-api](ai-context/mcp-and-public-api.md) |
| Using the Public API, MCP and the AI assistant | [docs/public_api.md](docs/public_api.md), [docs/AI-advanced.md](docs/AI-advanced.md), [docs/AI-mcp-reference.md](docs/AI-mcp-reference.md) |
| User-visible words and translations | [docs/translating_text.md](docs/translating_text.md) |
| Running tests, the Docker setup | [local-environment-and-testing](ai-context/local-environment-and-testing.md), [docs/testing.md](docs/testing.md) |
| Writing Playwright specs | [testing-through-the-application](ai-context/testing-through-the-application.md), [testing-robust-specs](ai-context/testing-robust-specs.md), [docs/creating_tests.md](docs/creating_tests.md) |
| Writing specs for users, groups and accounts | [testing-eau-eag](ai-context/testing-eau-eag.md) |
| A failed CI run | [testing-ci-failures](ai-context/testing-ci-failures.md) |
| Front-facing markup and CSS | [formulize-ui](ai-context/formulize-ui.md), [docs/templates.md](docs/templates.md) |
| Themes, the Login block and the open menu | [themes-anari-and-lyris](ai-context/themes-anari-and-lyris.md), [docs/themes.md](docs/themes.md) |
| Template changes not showing, `templates_c` | [compiled-templates-theme-fromfile](ai-context/compiled-templates-theme-fromfile.md) |
| The docs website | [writing-user-docs](ai-context/writing-user-docs.md), [docs-site-verification](ai-context/docs-site-verification.md) |
