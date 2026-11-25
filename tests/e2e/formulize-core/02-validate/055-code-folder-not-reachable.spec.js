const { test, expect } = require('@playwright/test');
const { sitePath, hasSiteFolder, urlPathFor } = require('../../site-paths');

// The code folder (FORMULIZE_CODE_PATH, modules/formulize/code/ unless the site has a folder of its own) holds what
// administrators write: form procedures, derived value formulas, static content and the intro and thanks text of
// form screens. Formulize reads and runs those files itself. Requested from a browser, a file would run on its own,
// outside Formulize, or show content meant only for some people, so the web server refuses every request for anything
// in it: the .htaccess in modules/formulize/code/, or sites/.htaccess for a site folder in the web root.
//
// The museum system has code files of its own there, such as its derived values and the Survey Intro's content, so
// one of those is asked for as well as index.html. A file that doesn't exist is refused the same way, so a 403
// doesn't depend on which files a site has.

for (const file of ['index.html', 'derived_donors_name.php', 'no_such_file.php']) {
	test(`the code folder's ${file} can't be requested`, async ({ request }) => {
		const folder = urlPathFor(sitePath('FORMULIZE_CODE_PATH'));
		test.skip(!folder, 'the code folder is outside the web root, so the web server has no way to serve it');
		const response = await request.get(`${folder}/${file}`, { maxRedirects: 0 });
		expect(response.status(), `the response to a request for ${folder}/${file}`).toBe(403);
	});
}

// A site with a folder of its own in the web root keeps its database login there, in settings.php, and its generated
// files in var/. sites/.htaccess refuses requests for anything in the site's folder but its uploads.
for (const file of ['settings.php', 'var/tokens/index.html', 'private/logs/formulize_log_active.log']) {
	test(`the site folder's ${file} can't be requested`, async ({ request }) => {
		test.skip(!hasSiteFolder(), 'the site keeps its files in the code');
		const folder = urlPathFor(sitePath('FORMULIZE_SITE_PATH'));
		test.skip(!folder, 'the site folder is outside the web root, so the web server has no way to serve it');
		const response = await request.get(`${folder}/${file}`, { maxRedirects: 0 });
		expect(response.status(), `the response to a request for ${folder}/${file}`).toBe(403);
	});
}
