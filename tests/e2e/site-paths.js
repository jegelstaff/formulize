// Where the site under test keeps its own files: custom code, screen templates, appearance files, locks, logs.
//
// A site can keep them in a folder of its own, sites/default or wherever FORMULIZE_SITE_PATH says, or in the
// folders inside the code that Formulize has always used. include/sitepaths.php names them all, so rather than
// repeat its rules here, this asks the site, by running PHP in the web container: mainfile.php without the rest of
// the system, then sitepaths.php, and reads back the constants. See ai-context/site-directory.md.
//
// Specs use this to check what the application wrote, never to write a state for a test. See
// ai-context/testing-through-the-application.md.
//
// Like docker-containers.js, this is plain CommonJS so utils.js and the specs can all use it. The answer is kept
// once the site is installed. Before then there are no settings to read, so it answers with the folders in the code
// and asks again next time.

const { execFileSync } = require('child_process');
const path = require('path');
const { webContainer } = require('./docker-containers');

// Where the repository is in the web container, and on this machine. docker-compose.yaml mounts one on the other.
const CONTAINER_ROOT = '/var/www/html';
const REPO_ROOT = path.resolve(__dirname, '../..');

// The folders in the code that Formulize has always used, for a site with no folder of its own
const IN_THE_CODE = {
	FORMULIZE_CODE_PATH: `${CONTAINER_ROOT}/modules/formulize/code`,
	FORMULIZE_SCREEN_TEMPLATES_PATH: `${CONTAINER_ROOT}/modules/formulize/templates/screens`,
	FORMULIZE_SITE_LANGUAGE_PATH: `${CONTAINER_ROOT}/modules/formulize/language`,
	FORMULIZE_TEMP_PATH: `${CONTAINER_ROOT}/modules/formulize/temp`,
	FORMULIZE_CACHE_PATH: `${CONTAINER_ROOT}/modules/formulize/cache`,
	FORMULIZE_LOG_PATH: `${CONTAINER_ROOT}/logs`,
	ICMS_UPLOAD_PATH: `${CONTAINER_ROOT}/uploads`,
	ICMS_THEME_PATH: `${CONTAINER_ROOT}/themes`,
};

let found = null;

function sitePaths() {
	if (found) {
		return found;
	}
	// The site's address doesn't matter here, but mainfile.php needs a request to work it out from. The PHP runs as
	// the web server's user, because sitepaths.php makes any of the site's folders that are missing.
	const php = `$xoopsOption = array('nocommon' => 1); $_SERVER['SERVER_NAME'] = 'localhost'; $_SERVER['SERVER_PORT'] = 80;
		include '${CONTAINER_ROOT}/mainfile.php';
		include_once '${CONTAINER_ROOT}/include/sitepaths.php';
		$c = get_defined_constants(true);
		echo json_encode($c['user']);`;
	try {
		const output = execFileSync('docker', ['exec', '-u', 'www-data', '-e', 'XDEBUG_MODE=off', webContainer(), 'php', '-r', php], {
			encoding: 'utf8',
			stdio: ['ignore', 'pipe', 'ignore'],
			timeout: 30000,
		});
		const constants = JSON.parse(output);
		if (constants && constants.FORMULIZE_CODE_PATH) {
			found = { ...constants, ICMS_THEME_PATH: `${CONTAINER_ROOT}/themes` };
			return found;
		}
	} catch (e) {
		// not installed yet: mainfile.php sends the request to the installer instead
	}
	return { ...IN_THE_CODE };
}

/** The value of one of the folders in include/sitepaths.php, as a path in the web container */
function sitePath(name) {
	return sitePaths()[name];
}

/** Whether the site keeps its files in a folder of its own, rather than in the code */
function hasSiteFolder() {
	return !!sitePaths().FORMULIZE_SITE_PATH;
}

/** A theme's appearance folder in the web container: looks, logos, favicons. See formulize_getAppearanceDir(). */
function appearanceDir(theme) {
	return hasSiteFolder() ? `${sitePath('ICMS_UPLOAD_PATH')}/appearance/${theme}` : `${CONTAINER_ROOT}/themes/${theme}/appearance`;
}

/** A theme's appearance folder in the site's URL, starting with a slash. See formulize_getAppearanceUrl(). */
function appearanceUrlPath(theme) {
	if (!hasSiteFolder()) {
		return `/themes/${theme}/appearance`;
	}
	const uploadsUrl = new URL(sitePath('ICMS_UPLOAD_URL'));
	return `${uploadsUrl.pathname.replace(/\/$/, '')}/appearance/${encodeURIComponent(theme)}`;
}

/** A path in the web container as a path on this machine, or null if it isn't in the repository */
function hostPath(containerPath) {
	if (containerPath !== CONTAINER_ROOT && !containerPath.startsWith(CONTAINER_ROOT + '/')) {
		return null;
	}
	return path.join(REPO_ROOT, containerPath.slice(CONTAINER_ROOT.length));
}

/** A path in the web container as a path in the site's URL, if the web server serves that folder, or null */
function urlPathFor(containerPath) {
	if (!containerPath.startsWith(CONTAINER_ROOT + '/')) {
		return null;
	}
	return containerPath.slice(CONTAINER_ROOT.length);
}

module.exports = { sitePath, hasSiteFolder, appearanceDir, appearanceUrlPath, hostPath, urlPathFor, CONTAINER_ROOT };
