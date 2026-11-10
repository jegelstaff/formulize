# Local Development & Testing Environment

How to reach a local copy of Formulize, run the tests against it, and debug what happens. For setting up the environment in the first place, see [docs/deploying_locally.md](../docs/deploying_locally.md). For how to run the suites and write specs, see [docs/testing.md](../docs/testing.md) and [docs/creating_tests.md](../docs/creating_tests.md). This note covers what isn't in those.

## Assume the app isn't reachable until you're told it is

The tracked `mainfile.php` on `master` is an uninstalled placeholder, so the site isn't running until someone has installed it or set up a local copy. Until the user tells you it's ready, assume the site is down. Don't try to verify changes in a browser, run Playwright specs, or `curl` pages expecting them to work. Ask the user to set it up or confirm it first.

**It's not set up** if `mainfile.php` still contains the literal `sdata#--#` placeholder. `XOOPS_INSTALL` is then undefined and every request redirects to `install/index.php`, which Playwright reports as `net::ERR_TOO_MANY_REDIRECTS`. The MariaDB container can still answer queries while the app is down, so a working database query doesn't mean the app is up.

**It's only partly set up** if requests fail with something like `Theme '' is not valid`. The usual cause is that `mainfile.php` and the database don't match. Every installation has its own table prefix, so a prefix that doesn't match the tables means every query hits tables that don't exist, config rows come back empty, and the theme name is blank. Treat this as down, and ask the user to align them.

## Docker Stack

Two containers, defined in `docker-compose.yaml`:

- `formulize-web-1`: PHP/Apache web server at `http://localhost:8080`
- `formulize-mariadb-1`: MariaDB database, at `localhost:3306`

The names and ports can differ. Compose names containers after the folder the repository is in, so a checkout in `formulize2` has `formulize2-web-1`. `FORMULIZE_WEB_PORT` and `FORMULIZE_DB_PORT` in the root `.env` set the ports, and the tests follow them. Check with `docker ps`.

**The table prefix varies** between setups. Don't hardcode it. Discover it with `SHOW TABLES LIKE '%tplfile%';`.

## Database Access

Connect through the MariaDB client inside the container. There's no `mysql` binary:

```bash
docker exec formulize-mariadb-1 mariadb -u user -ppassword formulize -e "YOUR SQL HERE;"
```

In Git Bash on Windows, put `MSYS_NO_PATHCONV=1` in front of any `docker exec` command with a path inside the container, such as `/var/www/html/...`. Otherwise Git Bash turns it into a Windows path.

This access is for reading, to check what the application stored or to get something a browser can't reach, like a confirmation code. Don't use it to create what a test needs. See [testing-through-the-application](testing-through-the-application.md).

## What each set of tests needs

The e2e tests build a system in order and then check it, so what a spec needs depends on where it sits. [docs/testing.md](../docs/testing.md) describes the system they build, which is called the museum system. For an outline of its forms, users and groups, see [testing-museum-system](testing-museum-system.md).

| Tests | What they do | What they need first |
|---|---|---|
| `01-setup/001-install-formulize.spec.js` | Runs the installer. The site is switched to Anari right after, because most of the suite is written for it. See [Which theme the suite covers](testing-robust-specs.md#which-theme-the-suite-covers) | An uninstalled system, with an empty database. See [Starting from an uninstalled system](#starting-from-an-uninstalled-system) |
| The rest of `01-setup/` | Build the museum system, in file-name order, one worker | Everything numbered below them. They create what they need, so they can't run over their own results |
| `02-validate/` | Read and exercise the finished system, in parallel | All of `01-setup/` |
| `03-validate-sequential/` | Change things the system shares, check them, then put them back, one at a time | All of `01-setup/`, and nothing else running |
| `04-appearance/` | Switch the site to the Lyris theme and test its look, one at a time | All of `01-setup/`, and nothing else running |

### Choosing where to start

- **Before running any spec outside `04-appearance/`, check that the site is on Anari,** for the site and the admin, on **Admin > Appearance > Settings**. A `04-appearance/` spec that stopped partway can leave the site on Lyris, and the other specs then can't find what they look for. See [Which theme the suite covers](testing-robust-specs.md#which-theme-the-suite-covers).
- **To run a validate spec:** if the museum system is intact, just run it. There's no cleanup to do first.
- **If a `03-validate-sequential/` or `04-appearance/` spec stopped partway:** it may not have put things back, so restore a snapshot before running anything else.
- **To re-run a `01-setup/` spec:** restore the snapshot from just before it.
- **To re-run `01-setup/` from 005:** restore `snap_after_001.sql`. 005 needs an installed system with no forms. If the Departments or Staff form already exists, its first test fails because the admin home shows an app accordion instead of "Create a new form". If their data tables exist, creating the forms fails with a "table already exists" error.
- **To run everything from the start,** or if there's no snapshot to use: start from an uninstalled system.

### Starting from an uninstalled system

This replaces the site you're working on, so ask the user first.

1. Snapshot the database.
2. Back up `mainfile.php` and `install.lock`.
3. Put the placeholder mainfile in place with `git show HEAD:mainfile.php > mainfile.php`, and remove `install.lock`.
4. Drop and create the `formulize` database.
5. Delete the untracked files in `modules/formulize/code/` and `modules/formulize/templates/screens/<theme>/<sid>/`, then clear `templates_c/`.
6. Run `001-install-formulize.spec.js`, then take `snap_after_001.sql`.

The installer spec can need a longer timeout on a slow machine.

## Database Snapshots

Re-running the whole setup chain is slow. Instead, snapshot the database after each phase, and restore the one just before the spec you're working on. Keep snapshots in a folder outside the repo, named for the last spec that ran: `snap_after_001.sql`, `snap_after_006.sql`, `snap_after_010.sql`, `snap_after_015.sql`, `snap_after_020.sql`.

```bash
# Create, after a spec passes
docker exec formulize-mariadb-1 sh -c "mariadb-dump -u user -ppassword --databases formulize 2>/dev/null" > /path/to/snaps/snap_after_015.sql

# Restore
docker exec formulize-mariadb-1 mariadb -u user -ppassword -e "DROP DATABASE IF EXISTS formulize; CREATE DATABASE formulize;"
docker exec -i formulize-mariadb-1 mariadb -u user -ppassword formulize < /path/to/snaps/snap_after_015.sql
```

- A snapshot is out of date once you change an earlier spec. Changing 010 invalidates `snap_after_010` and later, so regenerate the chain forward.
- Snapshots also go stale over time, and that can break the whole app, not just one test. A snapshot weeks older than the code can make every page throw `Undefined array key` errors and draw `The '' theme does not exist`. Check its age against `git log` before restoring. If there's been substantial schema or config work since, regenerate the chain from 001.
- Back up the current database before overwriting it with an old snapshot, so a mistake can be undone.
- After a restore, stale compiled templates can draw old markup. See [compiled-templates-theme-fromfile](compiled-templates-theme-fromfile.md).
- Group and form ids vary from run to run, so never hardcode them in tests.

## Running Playwright Tests

Tests live in `tests/e2e/`. Run them from that folder with `npm test`, because `playwright.config.js` is there. Running `npx playwright` from the repo root fails with `test.use() not expected here`.

```bash
cd tests/e2e
npm test -- formulize-core/01-setup/005-create-users-and-groups.spec.js --reporter=list
npm test -- formulize-core/01-setup/005-create-users-and-groups.spec.js --grep "Block A"
```

Keep the output of each run in a log file, so you can read a failure without running it again.

**Set `PW_TEST_HTML_REPORT_OPEN=never` when running tests in the background.** On a failing run, the HTML reporter opens the report and keeps the process alive serving it, so a backgrounded run never exits. Passing runs exit cleanly, so the difference is easy to misdiagnose.

```powershell
# PowerShell: separate statements with ';', not '&&'
$env:PW_TEST_HTML_REPORT_OPEN='never'; cd tests\e2e; npm test -- formulize-core/01-setup/015-set-permissions-menu-screens.spec.js
```

A final `N passed` or `N failed` line in the output means the run has finished.

Key settings in `tests/e2e/playwright.config.js`:

- `maxFailures: 1`, `retries: 0`, `workers: 1`, `fullyParallel: false`. CI overrides the last two for `02-validate/`. See [Concurrency](testing-robust-specs.md#concurrency)
- `timeout: 120000` per test, or `E2E_TEST_TIMEOUT` from the root `.env`
- `navigationTimeout: 60000`, or `E2E_NAVIGATION_TIMEOUT` from the root `.env`
- `expect.timeout: 20000` for assertions
- `baseURL` from `FORMULIZE_WEB_PORT` in the root `.env`, or `http://localhost:8080`
- `outputDir: ./test-results`, with the report in `./test-report`

## Debugging Failures

On failure, Playwright writes these to `test-results/<test-name-hash>/`:

- `error-context.md`: an ARIA snapshot of the page at failure
- `video.webm`: a video recording
- `trace.zip`: a full trace, viewed with `npx playwright show-trace <file>`

To look at a live page, log in with the suite's admin account. Its password is `password` unless `E2E_TEST_ADMIN_PASSWORD` says otherwise. The `monastery` branch's admin password is `admin`.

```bash
curl -s -c cookies.txt -d "uname=admin&pass=password&op=login" "http://localhost:8080/user.php" -L > /dev/null
curl -s -b cookies.txt "http://localhost:8080/modules/formulize/groups.php" | grep -i "template-group"
```

For a quick DOM check, write a small Playwright script and run it from `tests/e2e`, where Playwright is installed. From a script elsewhere, `require()` it by its full path. Check browser features in Firefox as well as Chromium.

## Screen template saves write into the repository

Saving a screen's template in the admin UI writes to `modules/formulize/templates/screens/<theme>/<sid>/`, inside the repository, and custom code goes to `modules/formulize/code/`. Running the suite creates files like this. They're not changes to commit.

## Known Quirks

### Pages drawn from old compiled templates after a reinstall or snapshot restore

A spec can fail locally while passing in CI on the same commit, because the page came from a template compiled days earlier. Delete `templates_c/*.php` and keep `index.html`. See [compiled-templates-theme-fromfile](compiled-templates-theme-fromfile.md).

### ensureGroupsTableForm() / ensureUsersTableForm() first-load issue

`groups.php` and `users.php` call these at the top. On the very first load, when the System Groups or System Users form doesn't exist yet, a database error can show on screen. Later loads just sync the form. Work around this in tests by navigating to these pages twice, or by waiting past the error.

### DB patch warning blocks "Create a new form"

If the module's database version is behind the code, `primaryRelationshipExists()` returns false, `need81ElementTypeConversion()` is true, or the `custom_code` folder exists, the admin shows a database patch warning instead of the normal page, and the "Create a new form" link isn't there. See [update-patches](update-patches.md#what-forces-the-update-page).

`formulize_frameworks` must contain a row with `frame_id = -1, frame_name = 'Primary Relationship'`. `createPrimaryRelationship()` makes it during setup. Truncating the table removes it, and the admin then shows "database structure not up to date". Restore a snapshot rather than putting the row back by hand.
