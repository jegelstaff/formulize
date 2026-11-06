# Verifying changes to the docs website

The /docs website is Jekyll; all styling is in docs/assets/css/formulize.css. To check a layout change in the real rendered page instead of reasoning about it:

- First check whether the site is already running at http://localhost:4000/ (baseurl is empty), with `curl -s localhost:4000/ | grep <new-class>`. If it is, it serves an edit within seconds, so don't start a second server.
- If nothing responds, start it yourself, in the background, from the `docs/` folder with `bundle exec jekyll serve`. It needs Ruby and Bundler, and `bundle install` the first time. Always use `bundle exec`. See [Writing and previewing documentation](../docs/documentation.md) for what the server won't pick up without a restart, such as changes to `_config.yml`.
- Playwright with cached Chromium is only installed under tests/e2e/node_modules. From a script outside that folder, `require()` it by its full path, ending in `tests/e2e/node_modules/@playwright/test`. Measure `getBoundingClientRect()` and computed `display` at a spread of viewport widths, either side of the `@media` breakpoints in the stylesheet, rather than eyeballing. Element screenshots get the sticky header painted over them; ignore that or un-stick the header first.
- The docs files are LF. Git Bash `grep -c $'\r$' file` wrongly reports every line as CRLF; count bytes with node before concluding anything about line endings.
