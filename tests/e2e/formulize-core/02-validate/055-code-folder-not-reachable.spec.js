const { test, expect } = require('@playwright/test');

// modules/formulize/code/ holds what administrators write: form procedures, derived value formulas, static content
// and the intro and thanks text of form screens. Formulize reads and runs those files itself. Requested from a browser,
// a file would run on its own, outside Formulize, or show content meant only for some people, so the .htaccess in the
// folder refuses every request for anything in it.
//
// The museum system has code files of its own there, such as its derived values and the Survey Intro's content, so
// one of those is asked for as well as index.html, which every site has. A file that doesn't exist is refused the same
// way, so a 403 doesn't depend on which files a site has.

for (const file of ['index.html', 'derived_donors_name.php', 'no_such_file.php']) {
	test(`modules/formulize/code/${file} can't be requested`, async ({ request }) => {
		const response = await request.get(`/modules/formulize/code/${file}`, { maxRedirects: 0 });
		expect(response.status(), `the response to a request for ${file}`).toBe(403);
	});
}
