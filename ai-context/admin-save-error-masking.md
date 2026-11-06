# Admin save errors can be hidden

`runSaveEvent()` in `templates/admin/ui.html` posts every `.formulize-admin-form` on the page in sequence. A response starting with `/* eval */` is stored as `redirect`, `/* evalnow */` and `/* evalnowandreturn */` run straight away, and anything else is `alert()`ed (auto-dismissed by Playwright). At the end, `redirect` is eval'd if **any** form set it. So on a form's admin page, the Settings tab's `form_settings_save.php` (which usually prints `/* eval */ reloadWithScrollPosition(...)`) can reload the page even though the Elements tab's response was `Error: ...` — the test goes green over a real server error. It only surfaces when the other form happens to return nothing, e.g. a test failing on the 3rd element delete but not the 2nd.

**Diagnosing:** don't trust apache log sizes or a passing step. Run the spec with `--trace on`, unzip `trace.zip`, read `0-trace.network` (JSON lines; `snapshot.request.url` / `postData.text`, response body in `resources/<snapshot.response.content._sha1>`), and look at every save.php body.

See [Admin Pages and Saves](coding-guidelines.md#admin-pages-and-saves) for how saving works, and [local-environment-and-testing](local-environment-and-testing.md) for the test setup.
